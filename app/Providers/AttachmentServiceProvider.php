<?php

namespace App\Providers;

use App\Model\Attachment;
use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Ramsey\Uuid\Uuid;

class AttachmentServiceProvider extends ServiceProvider
{
    /**
     * Filter attachments by their extension.
     * @param $type
     * @return false|mixed|string
     */
    public static function filterExtensions($type = false)
    {
        if ($type) {
            switch ($type) {
                case 'videosFallback':
                    if (getSetting('media.transcoding_driver') === 'ffmpeg' || getSetting('media.transcoding_driver') === 'coconut') {
                        return getSetting('media.allowed_file_extensions');
                    } else {
                        $extensions = explode(',', getSetting('media.allowed_file_extensions'));
                        $extensions = array_diff($extensions, self::getTypeByExtension('video'));
                        $extensions[] = 'mp4';
                        return implode(',', $extensions);
                    }
                case 'imagesOnly':
                    return implode(',', self::getTypeByExtension('images'));
                case 'manualPayments':
                    return 'jpg,jpeg,png,pdf,xls,xlsx';
            }
        }

        return false;
    }

    /**
     * Get attachment type by extension.
     *
     * @param $type
     * @return string
     */
    public static function getAttachmentType($type)
    {
        switch ($type) {
            case 'avi':
            case 'mp4':
            case 'wmw':
            case 'mpeg':
            case 'm4v':
            case 'moov':
            case 'mov':
            case 'mkv':
            case 'wmv':
            case 'asf':
                return 'video';
            case 'mp3':
            case 'wav':
            case 'ogg':
                return 'audio';
            case 'png':
            case 'jpg':
            case 'jpeg':
                return 'image';
            case 'pdf':
            case 'xls':
            case 'xlsx':
                return 'document';
            default:
                return 'image';
        }
    }

    /**
     * Get file extensions by types.
     *
     * @param $type
     * @return array
     */
    public static function getTypeByExtension($type)
    {
        switch ($type) {
            case 'video':
                return ['mp4', 'avi', 'wmv', 'mpeg', 'm4v', 'moov', 'mov', 'mkv', 'asf'];
            case 'audio':
                return ['mp3', 'wav', 'ogg'];
            default:
                return ['jpg', 'jpeg', 'png', 'webp'];
        }
    }

    /**
     * Return matching bookmarks category types to actual attachment types.
     *
     * @param $type
     * @return bool|string
     */
    public static function getActualTypeByBookmarkCategory($type)
    {
        switch ($type) {
            case 'photos':
                return 'image';
            case 'audio':
                return 'audio';
            case 'videos':
                return 'video';
            default:
                return false;
        }
    }

    /*
     * Gets recommended folder naming path based on mimetype
     */
    public static function getDirectoryByType($type)
    {
        switch ($type) {
            case 'video/mp4':
            case 'video/avi':
            case 'video/quicktime':
            case 'video/x-m4v':
            case 'video/mpeg':
            case 'video/wmw':
            case 'video/x-matroska':
            case 'video/x-ms-asf':
            case 'video/x-ms-wmv':
            case 'video/x-ms-wmx':
            case 'video/x-ms-wvx':
                $directory = 'videos';
                break;
            case 'audio/mpeg':
            case 'audio/ogg':
            case 'audio/wav':
                $directory = 'audio';
                break;
            case 'application/vnd.ms-excel':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
            case 'application/pdf':
                $directory = 'documents';
                break;
            default:
                $directory = 'images';
                break;
        }
        return $directory;
    }

    /**
     * Processes the attachment
     * Creates DB entry
     * Uploads it to the storage disk.
     *
     * @param $file
     * @param $directory
     * @param $generateThumbnail
     * @return mixed
     * @throws \Exception
     */
    public static function createAttachment($file, $directory, $generateThumbnail = false, $generateBlurredShot = false, $applyWatermark = true, ?int $userId = null)
    {

        do {
            $fileId = Uuid::uuid4()->getHex();
        } while (Attachment::query()->where('id', $fileId)->first() != null);

        $fileExtension = $file->guessExtension();
        $attachmentType = self::getAttachmentType($fileExtension);

        if ($attachmentType === 'image') {
            // Converting all images to jpegs
            $asset = MediaEncoderServiceProvider::encodeImage(
                $file,
                $directory,
                $fileId,
                $generateThumbnail,
                $generateBlurredShot,
                $applyWatermark
            );
        } elseif ($attachmentType === 'video') {
            // Convert videos to mp4s
            $asset = MediaEncoderServiceProvider::encodeVideo(
                $file,
                $directory,
                $fileId,
                $generateThumbnail,
                $generateBlurredShot,
                $applyWatermark
            );
        } else {
            // For regular files, just upload them directly really
            $asset = MediaEncoderServiceProvider::encodeRegularFile(
                $file,
                $directory,
                $fileId
            );
        }

        // Creating the db entry
        $storageDriver = config('filesystems.defaultFilesystemDriver');
        return Attachment::create([
            'id' => $fileId,
            'user_id' => $userId ?? Auth::id(),
            'filename' => $asset['filePath'],
            'type' => $fileExtension,
            'driver' => self::getStorageProviderID($storageDriver),
            'coconut_id' => ($asset['coconut_id'] ?? null),
            'has_thumbnail' => $asset['hasThumbnail'] ? 1 : null,
            'has_blurred_preview' => $asset['hasBlurredPreview'] ? 1 : null,
            'blurred_filename' => $asset['blurredFilename'] ?? null,
            'length' => $asset['length'] ?? null,
        ]);
    }

    /**
     * Gets (full path, based on path virtual attribute) thumbnail path by resolution.
     * [Used to get final thumbnail URL].
     * @param $attachment
     * @param $width
     * @param $height
     * @param string $basePath Legacy argument kept for external callers.
     * @return string|string[]
     */
    public static function getThumbnailPathForAttachmentByResolution($attachment, $width, $height, $basePath = '/posts/images/')
    {
        if ($attachment->driver == Attachment::S3_DRIVER && getSetting('storage.aws_cdn_enabled') && getSetting('storage.aws_cdn_presigned_urls_enabled')) {
            return self::signAPrivateDistributionPolicy(
                'https://'.getSetting('storage.cdn_domain_name').'/'.self::getThumbnailFilenameByAttachmentAndResolution($attachment, $width, $height)
            );
        } else {
            return self::replaceAttachmentFilenameInUrl(
                $attachment,
                self::getThumbnailFilenameByAttachmentAndResolution($attachment, $width, $height)
            );
        }
    }

    /**
     * Gets (full path, based on path virtual attribute) thumbnail path by resolution.
     * * [Used to get final thumbnail URL].
     * @param $attachment
     * @param $basePath Legacy argument kept for external callers.
     * @return array|false|mixed|string|string[]
     */
    public static function getBlurredPreviewPathForAttachment($attachment, $basePath = 'posts/images/')
    {
        if(!$attachment->has_blurred_preview) return false;
        if ($attachment->driver == Attachment::S3_DRIVER && getSetting('storage.aws_cdn_enabled') && getSetting('storage.aws_cdn_presigned_urls_enabled')) {
            return self::signAPrivateDistributionPolicy(
                'https://'.getSetting('storage.cdn_domain_name').'/'.self::getBlurredPreviewByAttachment($attachment)
            );
        } else {
            return self::replaceAttachmentFilenameInUrl(
                $attachment,
                self::getBlurredPreviewByAttachment($attachment)
            );
        }
    }

    /**
     * Removes attachment from storage disk.
     * Note: This should be called internally, where we assume the attachment has been granted ownership already.
     * @param $attachment
     */
    public static function removeAttachment($attachment)
    {
        $diskName = self::getStorageProviderName($attachment->driver);
        $storage = Storage::disk($diskName);

        $keys = [];
        $keys[] = ltrim($attachment->filename, '/');

        if (self::getAttachmentType($attachment->type) === 'image' || self::getAttachmentType($attachment->type) === 'video') {
            $thumb = self::getThumbnailFilenameByAttachmentAndResolution($attachment, 150, 150);
            if ($thumb) $keys[] = ltrim($thumb, '/');

            if ($attachment->has_blurred_preview) {
                $blur = self::getBlurredPreviewByAttachment($attachment);
                if ($blur) $keys[] = ltrim($blur, '/');
            }
        }

        foreach ($keys as $key) {
            try {
                $storage->delete($key);
                Log::info('Attachment delete attempt', [
                    'disk' => $diskName,
                    'driver' => $attachment->driver,
                    'key' => $key,
                ]);
            } catch (\Throwable $e) {
                Log::error('Attachment delete exception', [
                    'disk' => $diskName,
                    'driver' => $attachment->driver,
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Returns file thumbnail relative path, by resolution.
     * [Used to get storage paths].
     * @param $attachment
     * @param $width
     * @param $height
     * @return string|string[]
     */
    public static function getThumbnailFilenameByAttachmentAndResolution($attachment, $width, $height)
    {
        if (self::getAttachmentType($attachment->type) === 'video') {
            return self::getMediaVariantFilename($attachment, 'thumbnails');
        }

        return self::getMediaVariantFilename($attachment, "{$width}X{$height}");
    }

    /**
     * Returns blurred preview relative path.
     * Uses the attachment's actual directory (posts/messenger/etc), not a hardcoded basePath.
     */
    public static function getBlurredPreviewByAttachment($attachment)
    {
        if (!empty($attachment->blurred_filename)) {
            return $attachment->blurred_filename;
        }

        return self::getMediaVariantFilename($attachment, 'blurred');
    }

    protected static function getMediaVariantFilename($attachment, string $variantDirectory): string
    {
        $filename = ltrim((string) $attachment->filename, '/');
        $dir = trim(pathinfo($filename, PATHINFO_DIRNAME), '.');

        if (basename($dir) === 'tmp') {
            $dir = trim(dirname($dir), '.');
        }

        $base = $attachment->id.'.jpg';

        if ($dir === '') {
            return "{$variantDirectory}/{$base}";
        }

        return "{$dir}/{$variantDirectory}/{$base}";
    }

    protected static function replaceAttachmentFilenameInUrl($attachment, string $replacementFilename): string
    {
        $filename = ltrim((string) $attachment->filename, '/');

        if ($filename === '') {
            return $attachment->path;
        }

        return str_replace($filename, ltrim($replacementFilename, '/'), $attachment->path);
    }

    /**
     * Returns file path by attachment.
     * TODO: Reproduce this one for non-attachment (avatars & admin uploads) as well.
     * @param $attachment
     * @return string
     */
    public static function getFilePathByAttachment($attachment)
    {
        $oldDriver = null;
        // Changing to attachment file system driver, if different from the configured one
        if($attachment->driver !== self::getStorageProviderID(getSetting('storage.driver'))){
            $oldDriver = config('filesystems.default');
            SettingsServiceProvider::setDefaultStorageDriver(self::getStorageProviderName($attachment->driver));
        }

        $fileUrl = '';
        if ($attachment->driver == Attachment::S3_DRIVER) {
            if (getSetting('storage.aws_cdn_enabled') && getSetting('storage.aws_cdn_presigned_urls_enabled')) {
                $fileUrl = self::signAPrivateDistributionPolicy(
                    'https://'.getSetting('storage.cdn_domain_name').'/'.$attachment->filename
                );
            } elseif (getSetting('storage.aws_cdn_enabled')) {
                $fileUrl = 'https://'.getSetting('storage.cdn_domain_name').'/'.$attachment->filename;
            } else {
                $fileUrl = 'https://'.getSetting('storage.aws_bucket_name').'.s3.'.getSetting('storage.aws_region').'.amazonaws.com/'.$attachment->filename;
            }
        }
        elseif ($attachment->driver == Attachment::WAS_DRIVER || $attachment->driver == Attachment::DO_DRIVER || $attachment->driver == Attachment::R2_DRIVER) {
            $fileUrl = Storage::url($attachment->filename);
        }
        elseif($attachment->driver == Attachment::MINIO_DRIVER){
            $fileUrl = rtrim(getSetting('storage.minio_endpoint'), '/').'/'.getSetting('storage.minio_bucket_name').'/'.$attachment->filename;
        }
        elseif($attachment->driver == Attachment::PUSHR_DRIVER){
            $fileUrl = rtrim(getSetting('storage.pushr_cdn_hostname'), '/').'/'.$attachment->filename;
        }
        elseif ($attachment->driver == Attachment::PUBLIC_DRIVER) {
            $fileUrl = Storage::disk('public')->url($attachment->filename);
        }

        // Changing filesystem driver back, if needed
        if($oldDriver !== null) {
            SettingsServiceProvider::setDefaultStorageDriver($oldDriver);
        }
        return $fileUrl;
    }

    /**
     * Method used for signing assets via CF.
     *
     * @param $cloudFrontClient
     * @param $resourceKey
     * @param $customPolicy
     * @param $privateKey
     * @param $keyPairId
     * @return mixed
     */
    private static function signPrivateDistributionPolicy(
        $cloudFrontClient,
        $resourceKey,
        $customPolicy,
        $privateKey,
        $keyPairId
    ) {
        try {
            $result = $cloudFrontClient->getSignedUrl([
                'url' => $resourceKey,
                'policy' => $customPolicy,
                'private_key' => $privateKey,
                'key_pair_id' => $keyPairId,
            ]);

            return $result;
        } catch (AwsException $e) {
        }
    }

    /**
     * Method used for signing assets via CF.
     *
     * @param $resourceKey
     * @return mixed
     */
    public static function signAPrivateDistributionPolicy($resourceKey)
    {
        $resourceKey = str_replace('\\', '/', $resourceKey); // Windows glitching otherwise
        $expires = time() + 24 * 60 * 60; // 24 hours (60 * 60 seconds) from now.
        $customPolicy = <<<POLICY
{
    "Statement": [
        {
            "Resource": "{$resourceKey}",
            "Condition": {
                "DateLessThan": {"AWS:EpochTime": {$expires}}
            }
        }
    ]
}
POLICY;
        $privateKey = base_path().'/'.getSetting('storage.aws_cdn_private_key_path');
        $keyPairId = getSetting('storage.aws_cdn_key_pair_id');

        $cloudFrontClient = new CloudFrontClient([
            'profile' => 'default',
            'version' => '2014-11-06',
            'region' => getSetting('storage.aws_region'),
        ]);

        return self::signPrivateDistributionPolicy(
            $cloudFrontClient,
            $resourceKey,
            $customPolicy,
            $privateKey,
            $keyPairId
        );
    }

    public static function getStorageProviderID($storageDriver) {
        if($storageDriver)
            if($storageDriver == 'public'){
                return Attachment::PUBLIC_DRIVER;
            }
        if($storageDriver == 's3'){
            return Attachment::S3_DRIVER;
        }
        if($storageDriver == 'wasabi'){
            return Attachment::WAS_DRIVER;
        }
        if($storageDriver == 'do_spaces'){
            return Attachment::DO_DRIVER;
        }
        if($storageDriver == 'minio'){
            return Attachment::MINIO_DRIVER;
        }
        if($storageDriver == 'pushr'){
            return Attachment::PUSHR_DRIVER;
        }
        if($storageDriver == 'r2'){
            return Attachment::R2_DRIVER;
        }
        else{
            return Attachment::PUBLIC_DRIVER;
        }
    }

    public static function getStorageProviderName($storageDriver) {
        if($storageDriver){
            if($storageDriver == Attachment::PUBLIC_DRIVER){
                return 'public';
            }
            if($storageDriver == Attachment::S3_DRIVER){
                return 's3';
            }
            if($storageDriver == Attachment::WAS_DRIVER){
                return 'wasabi';
            }
            if($storageDriver == Attachment::DO_DRIVER){
                return 'do_spaces';
            }
            if($storageDriver == Attachment::MINIO_DRIVER){
                return 'minio';
            }
            if($storageDriver == Attachment::PUSHR_DRIVER){
                return 'pushr';
            }
            if($storageDriver == Attachment::R2_DRIVER){
                return 'r2';
            }
        }
        else{
            return 'public';
        }
    }

    /**
     * Attempts to fetch file name from a give url.
     * @param $url
     * @return bool|mixed
     */
    public static function getFileNameFromUrl($url) {
        if(preg_match('/[^\/\\&\?]+\.\w{3,4}(?=([\?&].*$|$))/', $url, $matches)){
            return $matches[0];
        }
        return false;
    }

    public static function hasBlurredPreview($attachment)
    {
        if(getSetting('media.use_blurred_previews_for_locked_posts') && $attachment->has_blurred_preview){
            return true;
        }
        return false;
    }

    public static function getPostPreviewData($post)
    {
        $attachment = isset($post->attachments[0]) ? $post->attachments[0] : null;
        $hasBlurred = $attachment && self::hasBlurredPreview($attachment);

        return [
            'attachmentExists' => (bool) $attachment,
            'hasBlurred' => $hasBlurred,
            'backgroundImage' => $hasBlurred
                ? $attachment->BlurredPreview
                : asset('/img/post-locked.svg'),
            'backgroundClass' => $hasBlurred
                ? ''
                : 'bg-contain svg-locked-post',
        ];
    }

    public static function extensionsToMimeTypes(string $extensions): array
    {
        $extensionToMime = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'wav' => 'audio/wav',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'moov' => 'video/quicktime',
            'm4v' => 'video/x-m4v',
            'mpeg' => 'video/mpeg',
            'wmv' => 'video/x-ms-wmv',
            'asf' => 'video/x-ms-asf',
        ];

        $extensionsArray = array_unique(array_map('trim', explode(',', strtolower($extensions))));

        return array_values(array_unique(array_filter(
            array_map(fn ($ext) => $extensionToMime[$ext] ?? null, $extensionsArray)
        )));
    }

    public static function getAdminFileUploadVisibility(): string
    {
        $s3Enabled = getSetting('storage.driver') === 's3';
        $cdnEnabled = (bool) getSetting('storage.aws_cdn_enabled');
        $signedUrlsEnabled = (bool) getSetting('storage.aws_cdn_presigned_urls_enabled');

        // For S3, use 'private' if CDN or signed URLs are enabled, otherwise 'public'
        if ($s3Enabled) {
            return ($cdnEnabled || $signedUrlsEnabled) ? 'private' : 'public';
        }

        // Default to 'public' for local or other drivers
        return 'public';
    }

    public static function getUploadMaxFilesize(): int {
        return getSetting('media.max_file_upload_size') ? ((int) getSetting('media.max_file_upload_size') * 1000) : 4000;
    }

    /**
     * Copy a file inside the same disk.
     * - Uses native ->copy when possible
     * - For Pushr (or any adapter that fails copy), falls back to get+put.
     */
    public static function copyOnDisk(int $driverId, string $src, string $dst, string $visibility = 'public'): void
    {
        $diskName = self::getStorageProviderName($driverId);
        $disk = Storage::disk($diskName);

        // Pushr: keep custom path
        if ($driverId === Attachment::PUSHR_DRIVER) {
            self::pushrCDNCopyByPath($src, $dst, $driverId, $visibility);
            return;
        }

        // Non-pushr: try native copy, then fallback
        try {
            $disk->copy($src, $dst);
            return;
        } catch (\Throwable $e) {
            // Fallback
        }

        $contents = $disk->get($src);
        $disk->put($dst, $contents, $visibility);
    }

    /**
     * (3) Fix: allow copying arbitrary source path, not just $attachment->filename.
     * (5) Keep: still exists as a Pushr-specific workaround.
     */
    public static function pushrCDNCopyByPath(string $srcPath, string $dstPath, int $driverId, string $visibility = 'public'): void
    {
        $diskName = self::getStorageProviderName($driverId);
        $storage = Storage::disk($diskName);

        $remoteFile = $storage->get($srcPath);

        // temp local
        $localStorage = Storage::disk('public');
        $tmpFile = 'tmp/'.basename($srcPath);

        $localStorage->put($tmpFile, $remoteFile);
        $storage->put($dstPath, $localStorage->get($tmpFile), $visibility);
        $localStorage->delete($tmpFile);
    }

    /**
     * Clone an uploaded “draft” attachment into a message-specific attachment.
     *
     * Fix (6): uses stored extension from filename (because images get converted to jpg).
     * Also copies derivatives (thumb + blurred) if they exist.
     */
    public static function cloneAttachmentForMessage(Attachment $src, int $messageId): Attachment
    {
        // Derive stored extension from the stored filename
        $storedExt = strtolower(pathinfo($src->filename, PATHINFO_EXTENSION) ?: $src->type);

        $newId = Uuid::uuid4()->getHex();

        // Keep existing convention for message attachments:
        $newMain = "messenger/images/{$newId}.{$storedExt}";

        // 1) Copy main file
        self::copyOnDisk($src->driver, $src->filename, $newMain);

        // 2) Copy thumbnail (if image)
        // Your thumbnail naming in encodeImage is {dir}/150X150/{id}.jpg
        // For messenger images you used 'messenger/images/150X150/{id}.jpg'
        if (self::getAttachmentType($storedExt) === 'image') {
            $srcThumb = "messenger/images/150X150/{$src->id}.jpg";
            $dstThumb = "messenger/images/150X150/{$newId}.jpg";

            $diskName = self::getStorageProviderName($src->driver);
            if (Storage::disk($diskName)->exists($srcThumb)) {
                self::copyOnDisk($src->driver, $srcThumb, $dstThumb);
            }
        }

        // 3) Copy blurred preview if it exists.
        $srcBlur = self::getBlurredPreviewByAttachment($src);
        $dstBlur = "messenger/images/blurred/".Uuid::uuid4()->getHex().".jpg";
        $newBlurredFilename = null;
        $diskName = self::getStorageProviderName($src->driver);
        if (Storage::disk($diskName)->exists($srcBlur)) {
            self::copyOnDisk($src->driver, $srcBlur, $dstBlur);
            $newBlurredFilename = $dstBlur;
        }

        // 4) Create DB entry for the new attachment
        return Attachment::create([
            'id' => $newId,
            'user_id' => $src->user_id ?? Auth::id(),
            'filename' => $newMain,
            'driver' => $src->driver,
            'type' => $storedExt,
            'message_id' => $messageId,
            'has_thumbnail' => $src->has_thumbnail,
            'has_blurred_preview' => $src->has_blurred_preview,
            'blurred_filename' => $newBlurredFilename,
            'length' => $src->length,
            'coconut_id' => $src->coconut_id,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadAttachamentRequest;
use App\Model\Attachment;
use App\Providers\AttachmentServiceProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Log;
use Pusher\Pusher;

class AttachmentController extends Controller
{
    /**
     * Process the attachment and upload it to the selected storage driver.
     *
     * @param UploadAttachamentRequest $request
     * @param string|false $type Dummy param to follow route parameters
     * @param UploadedFile|false $chunkedFile If using chunk uploads, this final chunked file is sent over this request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(UploadAttachamentRequest $request, $type = false, $chunkedFile = false)
    {
        if($chunkedFile){
            $file = $chunkedFile;
        }
        else{
            $file = $request->file('file');
        }

        if(!$type){
            $type = $request->route('type');
        }
        $fileMimeType = $file->getMimeType();

        try {
            $generateThumbnail = false;
            $generateBlurredPreview = true;
            $applyWatermark = true;
            $directory = AttachmentServiceProvider::getDirectoryByType($fileMimeType);
            if ($type == 'post') {
                $directory = 'posts/'.$directory;
                $generateThumbnail = true;
            } elseif ($type == 'message') {
                $directory = 'messenger/'.$directory;
                $generateThumbnail = true;
            } elseif ($type == 'payment-request'){
                $directory = 'payment-request/'.$directory;
            }
            elseif ($type == 'story'){
                $directory = 'stories/'.$directory;
                $generateBlurredPreview = false;
                $applyWatermark = false;
                $generateThumbnail = true;
            }
            elseif ($type == 'reel'){
                $directory = 'reels/'.$directory;
                $generateBlurredPreview = false;
                $generateThumbnail = true;
            }
            $attachment = AttachmentServiceProvider::createAttachment($file, $directory, $generateThumbnail, $generateBlurredPreview, $applyWatermark);
            if($chunkedFile){
                unlink($file->getPathname());
            }

        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => [$exception->getMessage(), $exception->getTrace()], 'message' => $exception->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'attachmentID' => $attachment->id,
            'path' => $attachment->path,
            'type' => AttachmentServiceProvider::getAttachmentType($attachment->type),
            'thumbnail' => $attachment->thumbnail,
            'blurred' => $attachment->blurred_preview,
            'coconut_id' => $attachment->coconut_id,
            'has_thumbnail' => $attachment->has_thumbnail,
            'length' => $attachment->length,
        ]);
    }

    /**
     * Chunk uploadining method.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws UploadMissingFileException
     * @throws \Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException
     */
    public function uploadChunk(Request $request, $type = false) {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));
        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }
        $save = $receiver->receive();
        // check if the upload has finished (in chunk mode it will send smaller files)
        if ($save->isFinished()) {
            $saveRequest = new UploadAttachamentRequest(['file'=>$save->getFile()]);
            $saveRequest->validate($saveRequest->rules());
            return $this->upload($saveRequest, $type, $save->getFile());
        }
        // we are in chunk mode, lets send the current progress
        $handler = $save->handler();
        return response()->json(['success' => true, 'data' => ['percentage'=>$handler->getPercentageDone()]]);
    }

    /**
     * Removes attachment out of db & out of the storage driver.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeAttachment(Request $request)
    {
        try {
            $attachment = Attachment::where('id', $request->get('attachmentId'))->where('user_id', Auth::user()->id)->first();
            if ($attachment != null) {
                $attachment->delete();
                return response()->json(['success' => true, 'data' => [__('Attachments removed successfully')]]);
            }
            else{
                return response()->json(['success' => false, 'data' => [__('Attachment not found')]], 403);
            }
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => [$exception->getMessage()]]);
        }
    }

    /**
     * Handles coconut webhook.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Pusher\ApiErrorException
     * @throws \Pusher\PusherException
     */
    public static function handleCoconutHook(Request $request) {

        Log::channel('coconut')->info(__("New coconut payload available"));
        Log::channel('coconut')->info(json_encode($request->all()));

        $attachmentID = $request->get('attachmentId');
        $attachment = Attachment::where('id', $attachmentID)->first();
        $username = $attachment->user->username;

        if(config('broadcasting.connections.pusher.key')){
            $options = [
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'useTLS' => true,
            ];
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                $options
            );
        }

        if ($request->get('event') === 'job.completed') {

            $storage = Storage::disk(AttachmentServiceProvider::getStorageProviderName($attachment->driver));
            $storage->delete($attachment->filename);

            $payload = $request->all();

            $baseDirectory = 'posts/videos';
            if (str_contains($attachment->filename, '/tmp/')) {
                $baseDirectory = trim(dirname(dirname($attachment->filename)), '/');
            }

            $attachment->filename = "{$baseDirectory}/{$attachmentID}.mp4";
            $attachment->type = "mp4";
            $attachment->has_thumbnail = 1;

            $attachment->has_blurred_preview = $attachment->blurred_filename ? 1 : null;

            $duration = data_get($payload, 'data.input.metadata.streams.0.duration');
            if (!$duration) {
                $streams = data_get($payload, 'data.input.metadata.streams', []);
                foreach ($streams as $s) {
                    if (($s['codec_type'] ?? null) === 'video' && !empty($s['duration'])) {
                        $duration = $s['duration'];
                        break;
                    }
                }
            }

            if (!$duration) {
                $duration = data_get($payload, 'data.input.metadata.format.duration')
                    ?? data_get($payload, 'data.input.metadata.duration')
                    ?? data_get($payload, 'data.input.duration');
            }

            if ($duration !== null && $duration !== '') {
                $attachment->length = (int) round((float) $duration);
            }

            $attachment->save();

            if (config('broadcasting.connections.pusher.key')) {
                unset($attachment->user);
                $attachment->setAttribute('success', true);
                $pusher->trigger($username, 'video-processing', $attachment);
            }
        }
        elseif($request->get('event') === 'job.failed' || $request->get('event') === 'output.failed'){
            // Notify the UI via a websocket call
            if(config('broadcasting.connections.pusher.key')){
                $attachment->setAttribute('success', false);
                $pusher->trigger($username, 'video-processing', $attachment);
            }
        }

        return response()->json(['success' => true, 'message' => __("Video updated")], 200);

    }
}

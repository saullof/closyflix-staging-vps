<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MediaSettings extends Settings
{
    public ?string $ffmpeg_path = null;

    public ?string $ffprobe_path = null;

    public string $transcoding_driver;

    public string $ffmpeg_video_conversion_quality_preset;

    public string $ffmpeg_audio_encoder;

    public ?string $coconut_api_key;

    public string $coconut_audio_encoder;

    public string $coconut_video_conversion_quality_preset;

    public bool $coconut_enforce_mp4_conversion;

    public ?string $coconut_video_region;

    public bool $enforce_mp4_conversion;

    public int $max_videos_length;

    public ?string $allowed_file_extensions;

    public int $max_file_upload_size;

    public bool $use_chunked_uploads;

    public int $upload_chunk_size;

    public bool $apply_watermark;

    public bool $use_url_watermark;

    public ?string $watermark_image;

    public bool $disable_media_right_click;

    public int $max_avatar_cover_file_size;

    public string $users_covers_size;

    public string $users_avatars_size;

    public bool $use_blurred_previews_for_locked_posts;

    public string $ffmpeg_video_encoder = 'libx264';

    public string $ffmpeg_video_speed_preset = 'ultrafast';

    public ?string $watermark_position = 'bottom-right';

    public ?int $watermark_scale_percent = 25;

    public ?int $watermark_opacity = 90;

    public static function group(): string
    {
        return 'media';
    }
}

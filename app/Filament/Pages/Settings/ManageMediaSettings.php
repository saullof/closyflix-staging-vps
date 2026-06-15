<?php

namespace App\Filament\Pages\Settings;

use App\Providers\AttachmentServiceProvider;
use App\Settings\MediaSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use BackedEnum;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ManageMediaSettings extends SettingsPage
{
    use HasPageShield;

    protected static ?string $slug = 'settings/media';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-photo';

    protected static string $settings = MediaSettings::class;

    protected static ?string $title = 'Media Settings';

    protected const FFMPEG_SPEED_PRESETS = [
        'nvenc' => [
            'default' => 'p1',
            'options' => [
                'p1' => 'p1 (fastest)',
                'p2' => 'p2',
                'p3' => 'p3',
                'p4' => 'p4',
                'p5' => 'p5 (recommended)',
                'p6' => 'p6',
                'p7' => 'p7 (best quality, slowest)',
            ],
        ],
        'qsv' => [
            'default' => 'veryfast',
            'options' => [
                'veryfast' => 'veryfast (recommended)',
                'faster' => 'faster',
                'fast' => 'fast',
                'medium' => 'medium',
                'slow' => 'slow (better quality, slower)',
            ],
        ],
        'amf' => [
            'default' => 'balanced',
            'options' => [
                'speed' => 'Speed',
                'balanced' => 'Balanced',
                'quality' => 'Quality',
            ],
        ],
        'cpu' => [
            'default' => 'ultrafast',
            'options' => [
                'ultrafast' => 'ultrafast (fastest)',
                'superfast' => 'superfast',
                'veryfast'  => 'veryfast (recommended)',
                'faster'    => 'faster',
                'fast'      => 'fast',
                'medium'    => 'medium (better quality, slower)',
            ],
        ],
    ];

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->persistTabInQueryString('tab')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('General')
                        ->columns(2)
                        ->schema([

                            TextInput::make('allowed_file_extensions')
                                ->label('Allowed file extensions')
                                ->helperText('If no transcoding service is available, video formats will fallback to mp4 only. '),

                            TextInput::make('max_file_upload_size')
                                ->label('Max upload size (MB)')
                                ->helperText('Maximum allowed size for uploaded media files.'),

                            Toggle::make('use_chunked_uploads')
                                ->label('Use chunked uploads')
                                ->helperText("Uploads large files in smaller parts to avoid size limits (e.g., Cloudflare restrictions)."),

                            TextInput::make('upload_chunk_size')
                                ->label('Upload chunk size (MB)')
                                ->helperText('Sets how large each part of a file upload can be (in MB). Keep within server upload limits.')
                                ->helperText('The size of each upload chunk in megabytes.'),

                            TextInput::make('users_covers_size')
                                ->helperText('Target size for user cover images. Higher resolutions improve quality but increase file size. Maintain the original aspect ratio for best results.')
                                ->label('User cover size (WxH)'),

                            TextInput::make('users_avatars_size')
                                ->helperText('Target size for user avatar images. Higher resolutions improve quality but increase file size. Maintain the original aspect ratio for best results.')
                                ->label('User avatar size (WxH)'),

                            Toggle::make('disable_media_right_click')
                                ->helperText('If enabled, right click on media (posts,  messages & stories) will be disabled.')
                                ->label('Disable right-click on media'),

                            TextInput::make('max_avatar_cover_file_size')
                                ->helperText('Maximum file size in MB for both avatar and cover images.')
                                ->label('Max avatar/cover Size (MB)'),

                            Toggle::make('use_blurred_previews_for_locked_posts')
                                ->helperText('If enabled, locked content will display blurred previews. Video files require the video transcoding service.')
                                ->label('Blur previews for locked posts')
                                ->columnSpanFull(),

                        ]),

                    Tabs\Tab::make('Videos')
                        ->columns(2)
                        ->schema([

                            Placeholder::make('coconut_warnings')
                                ->hiddenLabel()
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString(view('filament.partials.coconut-warnings')->render()))
                                ->visible(
                                    // TODO: Watermark should stay visible for images eithe way
                                    fn ($get) => $get('transcoding_driver') === 'coconut'
                                        && (
                                            getSetting('storage.driver') === 'public'
                                            || (!getSetting('websockets.pusher_app_id') && !getSetting('websockets.soketi_host_address'))
                                        )
                                )
                                ->columnSpanFull(),

                            Select::make('transcoding_driver')
                                ->label('Transcoding driver')
                                ->options([
                                    'none' => 'None',
                                    'ffmpeg' => 'FFmpeg',
                                    'coconut' => 'Coconut',
                                ])
                                ->placeholder('Select a driver')
                                ->helperText('Select the video transcoding engine to use.')
                                ->reactive()
                                ->required(),

                            TextInput::make('max_videos_length')
                                ->label('Max video length')
                                ->visible(fn ($get) => in_array($get('transcoding_driver'), ['ffmpeg', 'coconut']))
                                ->required()
                                ->integer()
                                ->helperText('Maximum allowed video length in seconds (0 = unlimited).'),

                            TextInput::make('ffmpeg_path')
                                ->label('FFmpeg path')
                                ->required()
                                ->helperText("FFmpeg executable path. EG: /usr/bin/ffmpeg")
                                ->visible(fn ($get) => $get('transcoding_driver') === 'ffmpeg'),

                            TextInput::make('ffprobe_path')
                                ->label('FFprobe path')
                                ->required()
                                ->helperText("FFmpeg executable path. EG: /usr/bin/ffprobe")
                                ->visible(fn ($get) => $get('transcoding_driver') === 'ffmpeg'),

                            Select::make('ffmpeg_audio_encoder')
                                ->label('Audio encoder')
                                ->options([
                                    'aac' => 'AAC Encoder',
                                    'libfdk_aac' => 'libfdk_aac Encoder',
                                    'libmp3lame' => 'LAME MP3 Encoder',
                                ])
                                ->required()
                                ->helperText("AAC is recommended, it usually offers the best compatibility.")
                                ->visible(fn ($get) => $get('transcoding_driver') === 'ffmpeg'),

                            Select::make('ffmpeg_video_encoder')
                                ->label('Video encoder')
                                ->options([
                                    'libx264' => 'CPU (libx264)',
                                    'h264_nvenc' => 'NVIDIA GPU (NVENC)',
                                    'h264_qsv' => 'Intel GPU (Quick Sync / Arc)',
                                    'h264_amf' => 'AMD GPU (AMF)',
                                ])
                                ->default('libx264')
                                ->required()
                                ->helperText('Select the encoder to use. If the encoder is not available on the server, the system will fallback to libx264.')
                                ->visible(fn (Get $get) => $get('transcoding_driver') === 'ffmpeg')
                                ->live() // <-- important
                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                    // Reset the dependent field to a valid preset for the selected encoder
                                    if ($state && str_ends_with($state, '_nvenc')) {
                                        $set('ffmpeg_video_speed_preset', 'p5');
                                    } elseif ($state && str_ends_with($state, '_qsv')) {
                                        $set('ffmpeg_video_speed_preset', 'veryfast');
                                    } elseif ($state && str_ends_with($state, '_amf')) {
                                        $set('ffmpeg_video_speed_preset', 'balanced');
                                    } else {
                                        $set('ffmpeg_video_speed_preset', 'veryfast');
                                    }
                                }),

                            Select::make('ffmpeg_video_conversion_quality_preset')
                                ->label('Video quality preset')
                                ->options([
                                    'legacy' => 'Ultra small (legacy)',
                                    'size' => 'Size optimized',
                                    'balanced' => 'Balanced',
                                    'quality' => 'Quality optimized',
                                ])
                                ->required()
                                ->helperText('Controls target bitrate (file size & quality).')
                                ->visible(fn ($get) => $get('transcoding_driver') === 'ffmpeg'),

                            Select::make('ffmpeg_video_speed_preset')
                                ->key(fn (Get $get) => 'ffmpeg_video_speed_preset_'.($get('ffmpeg_video_encoder') ?: 'libx264'))
                                ->label('Speed preset')
                                ->options(fn (Get $get) => self::speedPresetOptions((string) ($get('ffmpeg_video_encoder') ?: 'libx264')))
                                ->dehydrateStateUsing(fn ($state, Get $get) => self::coerceSpeedPreset(
                                    (string) ($get('ffmpeg_video_encoder') ?: 'libx264'),
                                    is_string($state) ? $state : null
                                ))
                                ->required()
                                ->helperText('Controls encoding speed vs efficiency. Slower can improve quality at the same bitrate.')
                                ->visible(fn (Get $get) => $get('transcoding_driver') === 'ffmpeg')
                                ->live(),

                            Toggle::make('enforce_mp4_conversion')
                                ->label('Force MP4 conversion')
                                ->helperText('Disables automatic MP4 re-encoding, lowering resource usage. Watermarks and blurred previews won\'t apply to MP4 files.')
                                ->visible(fn ($get) => $get('transcoding_driver') === 'ffmpeg'),

                            TextInput::make('coconut_api_key')
                                ->label('Coconut API key')
                                ->helperText('The coconut API Key')
                                ->required()
                                ->visible(fn ($get) => $get('transcoding_driver') === 'coconut')
                                ->password()
                                ->revealable()
                                ->autocomplete(false),

                            Select::make('coconut_video_region')
                                ->label('Region')
                                ->options([
                                    'us-east-1' => 'us-east-1 (North Virginia)',
                                    'us-west-2' => 'us-west-2 (Oregon)',
                                    'eu-west-1' => 'eu-west-1 (Ireland)',
                                ])
                                ->required()
                                ->helperText('Make sure you\'re using the same region under which you registered the account on')
                                ->visible(fn ($get) => $get('transcoding_driver') === 'coconut'),

                            Select::make('coconut_audio_encoder')
                                ->label('Audio encoder')
                                ->options([
                                    'aac' => 'AAC Encoder',
                                    'mp3' => 'MP3 Encoder',
                                ])
                                ->required()
                                ->helperText("AAC is recommended, it usually offers the best compatibility.")
                                ->visible(fn ($get) => $get('transcoding_driver') === 'coconut'),

                            Select::make('coconut_video_conversion_quality_preset')
                                ->label('Video quality preset')
                                ->options([
                                    'coconut_size' => 'Size optimized',
                                    'coconut_balanced' => 'Balanced',
                                    'coconut_quality' => 'Quality optimized',
                                ])
                                ->required()
                                ->helperText("Better quality speeds up processing, but files will be bigger than the original.")
                                ->visible(fn ($get) => $get('transcoding_driver') === 'coconut'),

                            Toggle::make('coconut_enforce_mp4_conversion')
                                ->label('Force MP4 conversion')
                                ->helperText('Disables automatic MP4 re-encoding, lowering resource usage. Watermarks and blurred previews won\'t apply to MP4 files.')
                                ->visible(fn ($get) => $get('transcoding_driver') === 'coconut'),
                        ]),

                    Tabs\Tab::make('Watermark')
                        ->columns(2)
                        ->schema([

                            Toggle::make('apply_watermark')
                                ->label('Apply watermark')
                                ->helperText('For images, GD library is required. For videos, either ffmpeg or coconut transcoder.')
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if (!$state) {
                                        $set('use_url_watermark', false);
                                    }
                                }),

                            Toggle::make('use_url_watermark')
                                ->label('Use watermark URL')
                                ->helperText('Adds profile url link as watermark to media. FFmpeg only (not supported by Coconut).')
                                ->reactive()
                                ->visible(fn (Get $get) => (bool) $get('apply_watermark'))
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state) {
                                        $set('watermark_image', null);
                                    } else {
                                        // reset to default whenever URL watermark is disabled
                                        $set('watermark_opacity', 80);
                                    }
                                }),

                            FileUpload::make('watermark_image')
                                ->label('Watermark image')
                                ->directory('assets')
                                ->multiple(false)
                                ->visibility(AttachmentServiceProvider::getAdminFileUploadVisibility())
                                ->image()
                                ->columnSpan(
                                    fn (Get $get) => $get('transcoding_driver') === 'ffmpeg' ? 2 : 1
                                )
                                ->imagePreviewHeight('80px')
                                ->maxSize(AttachmentServiceProvider::getUploadMaxFilesize())
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                                ->visible(fn (Get $get) => (bool) $get('apply_watermark') && !(bool) $get('use_url_watermark'))
                                ->afterStateUpdated(function (Set $set, $state) {
                                    // If an image is selected, make sure URL watermark is off
                                    if (!empty($state)) {
                                        $set('use_url_watermark', false);
                                    }
                                }),

                            Select::make('watermark_position')
                                ->label('Watermark position')
                                ->options([
                                    'top-left' => 'Top left',
                                    'top-right' => 'Top right',
                                    'bottom-left' => 'Bottom left',
                                    'bottom-right' => 'Bottom right',
                                ])
                                ->default('bottom-right')
                                ->visible(fn (Get $get) => (bool) $get('apply_watermark'))
                                ->required(fn (Get $get) => (bool) $get('apply_watermark')),

                            TextInput::make('watermark_scale_percent')
                                ->label('Watermark size')
                                ->helperText('The % of media width. EG: 25 means ~25% of the video/image width. FFmpeg only.')
                                ->integer()
                                ->minValue(5)
                                ->maxValue(60)
                                ->default(25)
                                ->visible(
                                    fn (Get $get) => (bool) $get('apply_watermark')
                                    && !(bool) $get('use_url_watermark')
                                    && $get('transcoding_driver') === 'ffmpeg'
                                )
                                ->required(fn (Get $get) => (bool) $get('apply_watermark')),

                            TextInput::make('watermark_opacity')
                                ->label('Watermark opacity')
                                ->helperText('Only supported for FFmpeg and text watermarks. Value between 0-100.')
                                ->integer()
                                ->minValue(0)
                                ->maxValue(100)
                                ->default(80)
                                ->visible(fn (Get $get) => (bool) $get('apply_watermark') && (bool) $get('use_url_watermark'))
                                ->required(fn (Get $get) => (bool) $get('apply_watermark') && (bool) $get('use_url_watermark')),
                        ]),
                ]),

        ]);
    }

    protected static function speedPresetGroup(string $encoder): string
    {
        if (str_ends_with($encoder, '_nvenc')) {
            return 'nvenc';
        }

        if (str_ends_with($encoder, '_qsv')) {
            return 'qsv';
        }

        if (str_ends_with($encoder, '_amf')) {
            return 'amf';
        }

        return 'cpu';
    }

    protected static function speedPresetOptions(string $encoder): array
    {
        return self::FFMPEG_SPEED_PRESETS[self::speedPresetGroup($encoder)]['options'];
    }

    protected static function coerceSpeedPreset(string $encoder, ?string $value): string
    {
        $group = self::speedPresetGroup($encoder);
        $meta = self::FFMPEG_SPEED_PRESETS[$group];

        return array_key_exists((string) $value, $meta['options'])
            ? (string) $value
            : $meta['default'];
    }
}

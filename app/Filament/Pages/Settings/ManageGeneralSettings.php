<?php

namespace App\Filament\Pages\Settings;

use App\Providers\AttachmentServiceProvider;
use App\Providers\LocalesServiceProvider;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\File;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Pages\SettingsPage;
use BackedEnum;
use App\Services\PwaAssetGenerator;

class ManageGeneralSettings extends SettingsPage
{
    use HasPageShield;

    protected static string $settings = GeneralSettings::class;

    protected static ?string $slug = 'settings/general';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = 'General Settings';

    protected bool $shouldRegeneratePwaAssets = false;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->shouldRegeneratePwaAssets = $this->hasPwaChanges($data);

        return $data;
    }

    protected function afterSave(): void
    {
        if (!$this->shouldRegeneratePwaAssets) {
            return;
        }

        app(PwaAssetGenerator::class)->generate();
    }

    protected function hasPwaChanges(array $data): bool
    {
        return
            (bool) ($data['pwa_enabled'] ?? false) !== (bool) getSetting('site.pwa_enabled')
            || ($data['pwa_background_color'] ?? null) !== getSetting('site.pwa_background_color')
            || $this->normalizeFileValue($data['pwa_icon'] ?? null) !== getSetting('site.pwa_icon')
            || $this->normalizeFileValue($data['pwa_splash_logo'] ?? null) !== getSetting('site.pwa_splash_logo');
    }

    protected function normalizeFileValue(mixed $value): ?string
    {
        if (is_array($value)) {
            return $value[0] ?? null;
        }

        return filled($value) ? (string) $value : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('General Settings')
                ->columnSpanFull()
                ->persistTabInQueryString('tab')
                ->tabs([
                    Tab::make('Site')
                        ->schema([
                            TextInput::make('name')->label('Site name')->required(),
                            TextInput::make('app_url')->label('App URL')->required(),
                            TextInput::make('description')->label('Site description'),
                            TextInput::make('slogan')->label('Site slogan'),

                            Toggle::make('enforce_user_identity_checks')
                                ->label('Enforce ID check')
                                ->helperText('If enabled, users will only be able to post content & start streams if ID is verified.'),

                            Toggle::make('hide_identity_checks')
                                ->label('Hide identity checks')
                                ->helperText('If enabled, the ID check module link will be hidden from the menus. Useful for one-creator mode.'),

                            Toggle::make('enforce_email_validation')
                                ->label('Enforce email validation')
                                ->helperText('If enabled, users will be blocked from accessing the site until they verify their email.'),

                            Toggle::make('hide_create_post_menu')
                                ->label('Hide create post')
                                ->helperText('If enabled, the create post module link will be hidden from the menus. Useful for one-creator mode.'),

                            Toggle::make('hide_stream_create_menu')
                                ->label('Hide create stream')
                                ->helperText('If enabled, the create stream module link will be hidden from the menus. Useful for one-creator mode.'),

                        ])
                        ->columns(2),

                    Tab::make('Branding')
                        ->schema([
                            FileUpload::make('light_logo')
                                ->label('Light logo')
                                ->directory('assets')
                                ->multiple(false)
                                ->visibility(AttachmentServiceProvider::getAdminFileUploadVisibility())
                                ->image()
                                ->imagePreviewHeight('80px')
                                ->maxSize(AttachmentServiceProvider::getUploadMaxFilesize())
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']),

                            FileUpload::make('dark_logo')
                                ->label('Dark logo')
                                ->directory('assets')
                                ->multiple(false)
                                ->visibility(AttachmentServiceProvider::getAdminFileUploadVisibility())
                                ->image()
                                ->imagePreviewHeight('80px')
                                ->maxSize(AttachmentServiceProvider::getUploadMaxFilesize())
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']),

                            FileUpload::make('favicon')
                                ->label('Favicon')
                                ->directory('assets')
                                ->multiple(false)
                                ->visibility(AttachmentServiceProvider::getAdminFileUploadVisibility())
                                ->image()
                                ->imagePreviewHeight('60px')
                                ->maxSize(AttachmentServiceProvider::getUploadMaxFilesize())
                                ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/svg+xml']),

                            FileUpload::make('default_og_image')
                                ->label('OG image')
                                ->directory('assets')
                                ->multiple(false)
                                ->visibility(AttachmentServiceProvider::getAdminFileUploadVisibility())
                                ->image()
                                ->imagePreviewHeight('80px')
                                ->maxSize(AttachmentServiceProvider::getUploadMaxFilesize())
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                            FileUpload::make('login_page_background_image')
                                ->label('Login background')
                                ->directory('assets')
                                ->multiple(false)
                                ->visibility(AttachmentServiceProvider::getAdminFileUploadVisibility())
                                ->image()
                                ->imagePreviewHeight('80px')
                                ->maxSize(AttachmentServiceProvider::getUploadMaxFilesize())
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                        ])
                        ->columns(2),

                    Tab::make('Appearance')
                        ->schema([
                            Toggle::make('allow_theme_switch')
                                ->label('Allow theme switch')
                                ->helperText('Lets users toggle between light and dark mode manually.'),

                            Radio::make('default_user_theme')
                                ->label('Default Theme')
                                ->options([
                                    'light' => 'Light',
                                    'dark' => 'Dark',
                                ])
                                ->helperText('The default appearance theme for new visitors and users.'),

                            Toggle::make('allow_direction_switch')
                                ->label('Allow direction switch')
                                ->helperText('Let users switch between left-to-right (LTR) and right-to-left (RTL) layout.'),

                            Radio::make('default_site_direction')
                                ->label('Default direction')
                                ->options([
                                    'ltr' => 'Left to right',
                                    'rtl' => 'Right to left',
                                ])
                                ->helperText('The default text direction for the site. RTL is used for Arabic, Hebrew, etc.'),

                            Toggle::make('enable_smooth_page_change_transitions')
                                ->label('Smooth page transitions')
                                ->helperText('Enable visual fade/slide animations when navigating between pages.'),

                        ])
                        ->columns(2),

                    Tab::make('Localization')
                        ->schema([
                            Toggle::make('allow_language_switch')
                                ->label('Allow language switch')
                                ->helperText('Let users choose their preferred language from the available options.'),

                            Select::make('default_site_language')
                                ->label('Default language')
                                ->options(fn () => LocalesServiceProvider::getAvailableLanguageOptions(false))
                                ->required()
                                ->searchable()
                                ->placeholder('Select a language')
                                ->helperText('The default website language, shown to users by default.'),

                            Toggle::make('use_browser_language_if_available')
                                ->label('Use browser language')
                                ->helperText('Automatically set the language based on the user’s browser preference.'),

                            Select::make('timezone')
                                ->label('Timezone')
                                ->options(
                                    collect(timezone_identifiers_list())
                                        ->mapWithKeys(fn ($tz) => [$tz => $tz])
                                        ->toArray()
                                )
                                ->searchable()
                                ->helperText('Set the default timezone for your site.'),

                        ])
                        ->columns(2),

                    Tab::make('Homepage')
                        ->schema([
                            Radio::make('homepage_type')
                                ->label('Homepage Type')
                                ->options([
                                    'landing' => 'Landing page',
                                    'login' => 'Login page',
                                    'explore' => 'Explore page',
                                ])
                                ->helperText('Choose what visitors see first when visiting the site without being logged in.'),

                            Radio::make('redirect_page_after_register')
                                ->label('Redirect after register')
                                ->options([
                                    'feed' => 'Feed page',
                                    'settings' => 'User settings',
                                ])
                                ->helperText('Select where new users are taken immediately after signing up.'),

                            TextInput::make('homepage_redirect')
                                ->label('Homepage redirect URL')
                                ->helperText('Optional: override the default homepage with a custom URL.'),
                        ])
                        ->columns(2),

                    Tab::make('Explore page')
                        ->schema([
                            Toggle::make('explore_enabled')
                                ->label('Enable Explore page')
                                ->helperText('Turns the Explore page on/off across the site. If disabled, the route will 404.')
                                ->live(),

                            Select::make('explore_menu_visibility')
                                ->label('Explore menu visibility')
                                ->options([
                                    'none'  => 'Hide from everyone',
                                    'auth'  => 'Logged in only',
                                    'guest' => 'Guests only',
                                    'both'  => 'Everyone',
                                ])
                                ->default('guest')
                                ->helperText('Controls who sees Explore in the navigation menu.')
                                ->native(false)
                                ->live(),

                            Radio::make('explore_mode')
                                ->label('Explore mode')
                                ->options([
                                    'paywall' => 'Paywall',
                                    'public'  => 'Public',
                                ])
                                ->descriptions([
                                    'paywall' => 'Can only browse content user has access to, acts like a feed search module.',
                                    'public'  => 'Anyone can browse content, but content is locked when there is no access.',
                                ])
                                ->helperText('If running paywall mode, `people` and `live` categories will still be available for anyone to browse. Private profiles are hidden either way.')
                                ->columnSpanFull()
                                ->live(),
                        ])
                        ->columns(2),

                    Tab::make('Social links')
                        ->schema([
                            TextInput::make('social_facebook_url')
                                ->label('Facebook URL')
                                ->rules(['nullable'])
                                ->placeholder('https://facebook.com/yourpage'),

                            TextInput::make('social_instagram_url')
                                ->label('Instagram URL')
                                ->rules(['nullable'])
                                ->placeholder('https://instagram.com/yourhandle'),

                            TextInput::make('social_twitter_url')
                                ->label('X (Twitter) URL')
                                ->rules(['nullable'])
                                ->placeholder('https://x.com/yourhandle'),

                            TextInput::make('social_tiktok_url')
                                ->label('TikTok URL')
                                ->rules(['nullable'])
                                ->placeholder('https://tiktok.com/@yourhandle'),

                            TextInput::make('social_youtube_url')
                                ->label('YouTube URL')
                                ->placeholder('https://youtube.com/@yourchannel'),

                            TextInput::make('social_whatsapp_url')
                                ->label('WhatsApp link')
                                ->rules(['nullable'])
                                ->placeholder('https://wa.me/15551234567'),

                            TextInput::make('social_telegram_link')
                                ->label('Telegram link')
                                ->placeholder('https://t.me/yourchannel'),

                            TextInput::make('social_reddit_url')
                                ->label('Reddit URL')
                                ->rules(['nullable'])
                                ->placeholder('https://reddit.com/r/yoursub'),
                        ])
                        ->columns(2),

                    Tab::make('Code & Ads')
                        ->schema([
                            Textarea::make('custom_code_css')
                                ->label('Custom CSS code')
                                ->rows(6)
                                ->hint('Paste raw <style> code or rules.'),

                            Textarea::make('custom_code_js')
                                ->label('Custom JS code')
                                ->rows(6)
                                ->hint('Paste raw <script> code or JavaScript.')
                                ->helperText("Paste JS or a <script>. Analytics/ads must be consent-gated (type='text/plain' data-category='analytics')."),

                            Textarea::make('ads_sidebar_spot')
                                ->label('Sidebar ad HTML')
                                ->rows(6)
                                ->hint('Will be shown on user feed & profile sidebars.'),
                        ]),

                    Tab::make('PWA')
                        ->schema([
                            Toggle::make('pwa_enabled')
                                ->label('Enable PWA')
                                ->helperText('Enable the manifest, service worker registration, and install-related UI.')
                                ->live(),

                            Toggle::make('pwa_install_prompt_enabled')
                                ->label('Enable install prompt')
                                ->helperText('Show a custom install banner or modal on supported devices.'),

                            ColorPicker::make('pwa_theme_color')
                                ->label('Theme color')
                                ->helperText('Used by supported browsers for the app UI color.'),

                            ColorPicker::make('pwa_background_color')
                                ->label('Background color')
                                ->helperText('Used for splash/background surfaces during launch.'),

                            FileUpload::make('pwa_icon')
                                ->label('App icon')
                                ->directory('assets/pwa')
                                ->multiple(false)
                                ->visibility(AttachmentServiceProvider::getAdminFileUploadVisibility())
                                ->image()
                                ->imageEditor()
                                ->imagePreviewHeight('100px')
                                ->maxSize(AttachmentServiceProvider::getUploadMaxFilesize())
                                ->acceptedFileTypes(['image/png'])
                                ->required(fn (Get $get): bool => (bool) $get('pwa_enabled'))
                                ->helperText('Square PNG icon, recommended 1024x1024. Required when PWA is enabled.'),

                            FileUpload::make('pwa_splash_logo')
                                ->label('Splash logo')
                                ->directory('assets/pwa')
                                ->multiple(false)
                                ->visibility(AttachmentServiceProvider::getAdminFileUploadVisibility())
                                ->image()
                                ->imageEditor()
                                ->imagePreviewHeight('100px')
                                ->maxSize(AttachmentServiceProvider::getUploadMaxFilesize())
                                ->acceptedFileTypes(['image/png'])
                                ->helperText('Optional logo for generated iOS splash screens. Falls back to the app icon if empty.'),
                        ])
                        ->columns(2),
                ]),
        ]);
    }
}

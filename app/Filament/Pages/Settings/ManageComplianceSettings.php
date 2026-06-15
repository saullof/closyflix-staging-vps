<?php

namespace App\Filament\Pages\Settings;

use App\Services\AgeCheck\CountryDetector;
use App\Settings\ComplianceSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use BackedEnum;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\HtmlString;

class ManageComplianceSettings extends SettingsPage
{
    use HasPageShield;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $slug = 'settings/compliance';

    protected static string $settings = ComplianceSettings::class;

    protected static ?string $title = 'Compliance Settings';

    protected function beforeFill(): void
    {
        $this->ensureAgeGateSettingsExist();
    }

    protected function beforeSave(): void
    {
        $this->ensureAgeGateSettingsExist();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ((bool) ($data['age_gate_enabled'] ?? false)) {
            $data['age_gate_driver'] = ($data['age_gate_mode'] ?? 'checker') === 'oauth'
                ? 'ageverif_oauth'
                : 'ageverif_checker';
        } elseif ((bool) ($data['enable_age_verification_dialog'] ?? false)) {
            $data['age_gate_driver'] = 'built_in';
        } elseif (!in_array($data['age_gate_driver'] ?? null, ['built_in', 'ageverif_checker', 'ageverif_oauth'], true)) {
            $data['age_gate_driver'] = 'none';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $driver = $data['age_gate_driver'] ?? 'none';

        $data['enable_age_verification_dialog'] = $driver === 'built_in';
        $data['age_gate_enabled'] = in_array($driver, ['ageverif_checker', 'ageverif_oauth'], true);
        $data['age_gate_mode'] = $driver === 'ageverif_oauth' ? 'oauth' : 'checker';

        if ($driver === 'ageverif_oauth' && ($data['age_gate_country_detection_driver'] ?? 'none') === 'none') {
            $data['age_gate_countries_mode'] = 'everyone';
            $data['age_gate_countries'] = [];
            $data['age_gate_require_unknown_country'] = true;
        }

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Compliance Settings')
                ->persistTabInQueryString('tab')
                ->columnSpanFull()
                ->tabs([

                    Tab::make('General')
                        ->columns(2)
                        ->schema([
                            Toggle::make('enable_cookies_box')
                                ->label('Enable cookies box')
                                ->helperText("Cookies consent dialog box to be used for GDPR.")
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Post & Creator Limits')
                        ->columns(2)
                        ->schema([
                            TextInput::make('admin_approved_posts_limit')
                                ->numeric()
                                ->label('Admin approved posts limit')
                                ->helperText("The number of posts that needs admin approval. After this number of posts has been reached, the creator can post freely (value = 0 means no limit)."),
                            TextInput::make('minimum_posts_until_creator')
                                ->numeric()
                                ->label('Posts before monetization')
                                ->helperText("The minimum number of posts for users to be able to earn money. Users won`t be able to receive money until they reach this limit (value = 0 means no limit)."),
                            TextInput::make('minimum_posts_deletion_limit')
                                ->numeric()
                                ->label('Minimum deletion limit')
                                ->helperText("The minimum posts deletion limit for creators. Enforce them to have a minimum number of posts on their accounts (value = 0 means no limit)."),
                            TextInput::make('monthly_posts_before_inactive')
                                ->numeric()
                                ->label('Monthly post requirement')
                                ->helperText("The minimum monthly posts number a creator must publish before having his account marked as inactive. If value = 0, no inactivity rule will be applied."),
                            Toggle::make('disable_creators_ppv_delete')
                                ->label('Prevent deletion of purchased PPV')
                                ->helperText("If enabled, creators won't be able to delete paid PPV content (paid posts/messages) if already paid by a customer."),
                            Toggle::make('allow_text_only_ppv')
                                ->label('Allow text-only PPV')
                                ->helperText("If enabled, creators will be allowed to sell text-only PPV messages & posts (no media requirements)."),
                        ]),

                    Tab::make('ID Checks')
                        ->columns(2)
                        ->schema([
                            Toggle::make('enforce_tos_check_on_id_verify')
                                ->label('TOS agreement on ID verify')
                                ->helperText("If enabled, a TOS & Creator agreement checkbox will be shown on ID-verify page. CCBill compliance requirement."),

                            Toggle::make('enforce_media_agreement_on_id_verify')->label('Media agreement on ID verify')
                                ->helperText("If enabled, a media-agreement checkbox will be shown on ID-verify page. CCBill compliance requirement."),

                            RichEditor::make('id_verify_custom_message_box')
                                ->label('Custom ID verify message')
                                ->helperText('Shows additional info on the ID verification page, near the manual upload form.')
                                ->toolbarButtons([
                                    'bold', 'italic', 'underline', 'strike', 'link',
                                    'bulletList', 'orderedList',
                                ])
                                ->formatStateUsing(fn ($state) => is_string($state) ? $state : '')
                                ->dehydrateStateUsing(fn ($state) => is_string($state) ? $state : '')
                                ->columnSpanFull(),

                        ]),

                    Tab::make('Age Gate')
                        ->columns(2)
                        ->schema([
                            Select::make('age_gate_driver')
                                ->label('Site entry age gate')
                                ->options([
                                    'none' => 'Disabled',
                                    'built_in' => 'Built-in consent dialog',
                                    'ageverif_checker' => 'AgeVerif client-side checker',
                                    'ageverif_oauth' => 'AgeVerif backend OAuth',
                                ])
                                ->default('none')
                                ->required()
                                ->live()
                                ->helperText('Choose who handles the site entry age gate. Backend OAuth lets this site decide before rendering; client-side checker lets AgeVerif handle the browser check.')
                                ->columnSpanFull(),

                            Placeholder::make('age_gate_ageverif_callback_info')
                                ->label('')
                                ->hiddenLabel()
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth')
                                ->content(new HtmlString(view('filament.partials.ageverif-callback-info-box')->render()))
                                ->columnSpanFull(),

                            TextInput::make('age_verification_cancel_url')
                                ->label('Cancel redirect URL')
                                ->helperText('Where visitors are sent when they leave or decline the built-in consent dialog.')
                                ->visible(fn ($get) => $get('age_gate_driver') === 'built_in')
                                ->columnSpanFull(),

                            TextInput::make('age_gate_ageverif_public_key')
                                ->label('AgeVerif public key')
                                ->helperText('Used by the AgeVerif Checker script.')
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_checker')
                                ->columnSpanFull(),

                            TextInput::make('age_gate_ageverif_oauth_client_id')
                                ->label('AgeVerif OAuth client ID')
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth'),

                            TextInput::make('age_gate_ageverif_oauth_client_secret')
                                ->label('AgeVerif OAuth client secret')
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth')
                                ->password()
                                ->revealable()
                                ->autocomplete('new-password'),

                            Select::make('age_gate_ageverif_challenges')
                                ->label('Allowed AgeVerif challenges')
                                ->helperText('Optional. Leave empty to use all challenges enabled in AgeVerif.')
                                ->placeholder('Use all enabled challenges')
                                ->options([
                                    'selfie' => 'Selfie',
                                    'email_age' => 'Email age',
                                    'credit_card' => 'Credit card',
                                    'ticket' => 'Ticket',
                                    'anonymage' => 'Anonymage',
                                    'pleenk' => 'Pleenk',
                                    'paypal' => 'PayPal',
                                    'agego' => 'AgeGo',
                                    'agekey' => 'AgeKey',
                                ])
                                ->multiple()
                                ->native(false)
                                ->dehydrateStateUsing(fn ($state) => is_array($state) ? array_values(array_filter(array_map(fn ($challenge) => trim((string) $challenge), $state))) : [])
                                ->formatStateUsing(fn ($state) => is_string($state) && $state !== '' ? array_values(array_filter(array_map('trim', explode(',', $state)))) : (is_array($state) ? $state : []))
                                ->visible(fn ($get) => in_array($get('age_gate_driver'), ['ageverif_checker', 'ageverif_oauth'], true))
                                ->columnSpanFull(),

                            Select::make('age_gate_country_detection_driver')
                                ->label('Server-side country detection')
                                ->options([
                                    'none' => 'None - require everyone',
                                    'cloudflare' => 'Cloudflare CF-IPCountry header',
                                    'abstract' => 'AbstractAPI IP Geolocation',
                                ])
                                ->default('none')
                                ->required()
                                ->live()
                                ->helperText('Used only by backend OAuth mode. AgeVerif portal geolocation applies only to the client-side checker driver.')
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth')
                                ->columnSpanFull(),

                            TextInput::make('age_gate_abstract_api_key')
                                ->label('AbstractAPI IP Geolocation key')
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth' && $get('age_gate_country_detection_driver') === 'abstract')
                                ->required(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth' && $get('age_gate_country_detection_driver') === 'abstract')
                                ->password()
                                ->revealable()
                                ->autocomplete('new-password')
                                ->columnSpanFull(),

                            Select::make('age_gate_countries_mode')
                                ->label('Require verification for')
                                ->options([
                                    'everyone' => 'Everyone',
                                    'selected' => 'Selected countries',
                                ])
                                ->default('everyone')
                                ->required()
                                ->live()
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth' && $get('age_gate_country_detection_driver') !== 'none'),

                            TagsInput::make('age_gate_countries')
                                ->label('Countries requiring verification')
                                ->helperText('Only used by backend OAuth mode. Use ISO 3166-1 alpha-2 country codes, for example: GB, FR, DE.')
                                ->placeholder('GB')
                                ->separator(',')
                                ->suggestions(['GB', 'FR', 'DE', 'IT', 'ES', 'US', 'CA', 'AU'])
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth' && $get('age_gate_country_detection_driver') !== 'none' && $get('age_gate_countries_mode') === 'selected')
                                ->dehydrateStateUsing(fn ($state) => is_array($state) ? array_values(array_filter(array_map(fn ($country) => strtoupper(trim((string) $country)), $state))) : [])
                                ->rule('array')
                                ->nestedRecursiveRules([
                                    'regex:/^[A-Z]{2}$/',
                                ]),

                            Toggle::make('age_gate_require_unknown_country')
                                ->label('Require verification when country cannot be detected')
                                ->helperText('If enabled, failed or unavailable server-side country detection falls back to requiring verification.')
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth' && $get('age_gate_country_detection_driver') !== 'none'),

                            Placeholder::make('age_gate_country_detection_debug')
                                ->label('Country detection debug')
                                ->content(fn ($get) => new HtmlString($this->countryDetectionDebugHtml($get)))
                                ->visible(fn ($get) => config('app.debug') && $get('age_gate_driver') === 'ageverif_oauth')
                                ->columnSpanFull(),

                            TextInput::make('age_gate_minimum_age')
                                ->label('Minimum age')
                                ->numeric()
                                ->minValue(18)
                                ->default(18)
                                ->required()
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth'),

                            TextInput::make('age_gate_cookie_lifetime_days')
                                ->label('Access cookie lifetime')
                                ->numeric()
                                ->minValue(1)
                                ->default(90)
                                ->suffix('days')
                                ->required()
                                ->visible(fn ($get) => $get('age_gate_driver') === 'ageverif_oauth'),

                        ]),

                    Tab::make('Release Forms')
                        ->columns(2)
                        ->schema([
                            Toggle::make('enable_release_forms')
                                ->label('Enable release forms')
                                ->helperText('Adds a Release forms tab to user settings so creators can upload release documents for admin review.')
                                ->live()
                                ->columnSpanFull(),

                            Toggle::make('release_forms_verified_users_only')
                                ->label('Only for ID-verified users')
                                ->helperText('If enabled, only users with an approved identity check can see and submit release forms.')
                                ->columnSpanFull(),

                            RichEditor::make('release_forms_custom_message_box')
                                ->label('Custom release forms message')
                                ->helperText('Shows additional info on the Release forms page. Useful for sample form links and upload instructions.')
                                ->toolbarButtons([
                                    'bold', 'italic', 'underline', 'strike', 'link',
                                    'bulletList', 'orderedList',
                                ])
                                ->formatStateUsing(fn ($state) => is_string($state) ? $state : '')
                                ->dehydrateStateUsing(fn ($state) => is_string($state) ? $state : '')
                                ->columnSpanFull(),
                        ]),

                    Tab::make('DAC7')
                        ->columns(2)
                        ->schema([
                            Toggle::make('tax_info_dac7_enabled')
                                ->label('Enable DAC7')
                                ->helperText('Enables the tax information tab for collecting required EU/UK tax details from users.'),
                            Toggle::make('tax_info_dac7_withdrawals_enforced')
                                ->label('Enforce for withdrawals')
                                ->helperText('Blocks withdrawals for users who haven’t completed their DAC7 tax information.'),
                            TextInput::make('tax_info_dac7_earnings_limit_before_enforced')
                                ->numeric()
                                ->label('Earnings limit before enforcement')
                                ->helperText("Minimum year-to-date gross earnings after which DAC7 tax information is required for withdrawals. 0 = enforce from the first transaction.")->columnSpanFull(),

                        ]),

                ]),
        ]);
    }

    protected function ensureAgeGateSettingsExist(): void
    {
        $table = config('settings.repositories.database.table', 'settings');

        if (!SchemaFacade::hasTable($table)) {
            return;
        }

        $now = now();

        foreach ($this->ageGateDefaults() as $name => $default) {
            $exists = DB::table($table)
                ->where('group', 'compliance')
                ->where('name', $name)
                ->exists();

            if ($exists) {
                if ($name === 'age_gate_ageverif_challenges') {
                    $this->normalizeSavedAgeGateChallenges($table);
                }

                continue;
            }

            DB::table($table)->insert([
                'group' => 'compliance',
                'name' => $name,
                'payload' => json_encode($default),
                'locked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function normalizeSavedAgeGateChallenges(string $table): void
    {
        $setting = DB::table($table)
            ->where('group', 'compliance')
            ->where('name', 'age_gate_ageverif_challenges')
            ->first(['payload']);

        if (!$setting) {
            return;
        }

        $payload = json_decode((string) $setting->payload, true);

        if (is_array($payload)) {
            return;
        }

        $normalized = is_string($payload) && $payload !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $payload))))
            : [];

        DB::table($table)
            ->where('group', 'compliance')
            ->where('name', 'age_gate_ageverif_challenges')
            ->update([
                'payload' => json_encode($normalized),
                'updated_at' => now(),
            ]);
    }

    protected function countryDetectionDebugHtml(callable $get): string
    {
        $countryDetector = app(CountryDetector::class);
        $detectedCountry = $countryDetector->detect(request());
        $selectedCountries = array_values(array_filter(array_map(
            fn ($country) => strtoupper(trim((string) $country)),
            is_array($get('age_gate_countries')) ? $get('age_gate_countries') : []
        )));
        $requiresVerification = $this->debugRequiresVerification(
            $detectedCountry,
            (string) $get('age_gate_country_detection_driver'),
            (string) $get('age_gate_countries_mode'),
            $selectedCountries,
            (bool) $get('age_gate_require_unknown_country')
        );

        $rows = [
            'Request IP' => request()->ip() ?: 'unknown',
            'X-Forwarded-For' => request()->header('X-Forwarded-For') ?: 'none',
            'CF-IPCountry' => request()->header('CF-IPCountry') ?: 'none',
            'Detection driver' => $get('age_gate_country_detection_driver') ?: 'none',
            'Detected country' => $detectedCountry ?: 'unknown',
            'Selected countries' => $selectedCountries ? implode(', ', $selectedCountries) : 'none',
            'Require if unknown' => (bool) $get('age_gate_require_unknown_country') ? 'yes' : 'no',
            'Current decision' => $requiresVerification ? 'verification required' : 'verification skipped',
        ];

        $items = collect($rows)
            ->map(fn ($value, $label) => $this->debugRow($label, (string) $value))
            ->implode('');

        $abstractDebug = $countryDetector->debugAbstract(request());

        if ($abstractDebug !== []) {
            $abstractItems = collect($abstractDebug)
                ->map(fn ($value, $label) => $this->debugRow('Abstract '.$label, (string) $value))
                ->implode('');

            $items .= '<div class="mt-1">'
                .'<p class="font-semibold mb-1">AbstractAPI response preview</p>'
                .$abstractItems
                .'</div>';
        }

        return '<div class="alert-info alert admin-debug-box">'
            .'<p class="font-semibold mb-1">Country detection debug</p>'
            .'<p class="mb-1">Debug only. Values use saved detector credentials and the current admin request.</p>'
            .'<div>'.$items.'</div>'
            .'</div>';
    }

    protected function debugRow(string $label, string $value): string
    {
        $isLongValue = strlen($value) > 80;

        if ($isLongValue) {
            return '<div class="admin-debug-row admin-debug-row-block mb-1">'
                .'<span class="font-semibold">'.e($label).'</span>'
                .'<code>'.e($value).'</code>'
                .'</div>';
        }

        return '<div class="admin-debug-row d-flex justify-between v-align-center mb-1">'
            .'<span class="font-semibold">'.e($label).'</span>'
            .'<code>'.e($value).'</code>'
            .'</div>';
    }

    protected function debugRequiresVerification(?string $country, string $detectionDriver, string $countriesMode, array $selectedCountries, bool $requireUnknown): bool
    {
        if ($detectionDriver === 'none') {
            return true;
        }

        if ($countriesMode === 'everyone') {
            return true;
        }

        if (!$country) {
            return $requireUnknown;
        }

        return in_array($country, $selectedCountries, true);
    }

    protected function ageGateDefaults(): array
    {
        return [
            'age_gate_enabled' => false,
            'age_gate_driver' => 'none',
            'age_gate_mode' => 'checker',
            'age_gate_ageverif_public_key' => null,
            'age_gate_ageverif_oauth_client_id' => null,
            'age_gate_ageverif_oauth_client_secret' => null,
            'age_gate_ageverif_challenges' => [],
            'age_gate_country_detection_driver' => 'none',
            'age_gate_abstract_api_key' => null,
            'age_gate_countries_mode' => 'everyone',
            'age_gate_countries' => [],
            'age_gate_require_unknown_country' => true,
            'age_gate_minimum_age' => 18,
            'age_gate_cookie_lifetime_days' => 90,
        ];
    }
}

<?php

namespace App\Filament\Pages\Settings;

use App\Providers\SettingsServiceProvider;
use App\Settings\RuntimeSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;

class ManageRuntimeSettings extends SettingsPage
{
    use HasPageShield;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $slug = 'settings/runtime';

    protected static string $settings = RuntimeSettings::class;

    protected static ?string $title = 'Runtime Settings';

    protected string $previousCacheDriver = 'file';

    protected string $previousSessionDriver = 'file';

    public ?string $confirmedSessionDriverChange = null;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Runtime Settings')
                ->columnSpanFull()
                ->persistTabInQueryString('tab')
                ->tabs([
                    Tabs\Tab::make('Cache')
                        ->columns(2)
                        ->schema([
                            Select::make('cache_driver')
                                ->label('Cache driver')
                                ->options($this->cacheDriverOptions())
                                ->required()
                                ->reactive()
                                ->placeholder('Select a driver')
                                ->helperText('Changing the cache driver may clear or bypass currently cached data until the new store warms up.')
                                ->columnSpanFull(),

                            TextInput::make('cache_prefix')
                                ->label('Cache key prefix')
                                ->helperText('Optional prefix used to avoid key collisions with other apps.')
                                ->columnSpanFull(),

                            TextInput::make('cache_redis_host')
                                ->label('Redis host')
                                ->default('127.0.0.1')
                                ->required()
                                ->visible(fn ($get) => $get('cache_driver') === 'redis'),
                            TextInput::make('cache_redis_port')
                                ->label('Redis port')
                                ->numeric()
                                ->default(6379)
                                ->required()
                                ->visible(fn ($get) => $get('cache_driver') === 'redis'),
                            TextInput::make('cache_redis_password')
                                ->label('Redis password')
                                ->visible(fn ($get) => $get('cache_driver') === 'redis')
                                ->password()
                                ->revealable()
                                ->autocomplete('new-password')
                                ->columnSpanFull(),

                        ]),

                    Tabs\Tab::make('Sessions')
                        ->columns(2)
                        ->schema([
                            Select::make('session_driver')
                                ->label('Session driver')
                                ->options([
                                    'file' => 'File',
                                    'database' => 'Database',
                                    'redis' => 'Redis',
                                ])
                                ->required()
                                ->reactive()
                                ->placeholder('Select a driver')
                                ->helperText('Changing the session driver may log out currently signed-in users because active sessions are stored by the current driver.')
                                ->columnSpanFull(),

                            Toggle::make('session_expire_on_close')
                                ->label('Expire on browser close'),
                            Toggle::make('session_encrypt')
                                ->label('Encrypt session data'),

                            TextInput::make('session_lifetime')
                                ->label('Lifetime')
                                ->numeric()
                                ->minValue(1)
                                ->suffix('minutes')
                                ->required()
                                ->columnSpanFull(),

                            TextInput::make('session_redis_host')
                                ->label('Redis host')
                                ->default('127.0.0.1')
                                ->required()
                                ->visible(fn ($get) => $get('session_driver') === 'redis'),
                            TextInput::make('session_redis_port')
                                ->label('Redis port')
                                ->numeric()
                                ->default(6379)
                                ->required()
                                ->visible(fn ($get) => $get('session_driver') === 'redis'),
                            TextInput::make('session_redis_password')
                                ->label('Redis password')
                                ->visible(fn ($get) => $get('session_driver') === 'redis')
                                ->password()
                                ->revealable()
                                ->autocomplete('new-password')
                                ->columnSpanFull(),

                        ]),
                ]),
        ]);
    }

    protected function beforeSave(): void
    {
        $settings = app(RuntimeSettings::class);
        $this->previousCacheDriver = $settings->cache_driver;
        $this->previousSessionDriver = $settings->session_driver;
    }

    protected function afterSave(): void
    {
        try {
            SettingsServiceProvider::setUpRuntimeCredentials(applySessionConfig: false);

            $key = 'runtime-cache-test-'.uniqid();
            Cache::store($this->data['cache_driver'])->put($key, 'ok', 30);

            if (Cache::store($this->data['cache_driver'])->get($key) !== 'ok') {
                throw new \RuntimeException('Cache test failed: the saved value could not be read back.');
            }

            Cache::store($this->data['cache_driver'])->forget($key);
        } catch (\Throwable $e) {
            config([
                'cache.default' => $this->previousCacheDriver,
                'session.driver' => $this->previousSessionDriver,
            ]);

            $settings = app(RuntimeSettings::class);
            $settings->cache_driver = $this->previousCacheDriver;
            $settings->session_driver = $this->previousSessionDriver;
            $settings->save();

            $this->data['cache_driver'] = $this->previousCacheDriver;
            $this->data['session_driver'] = $this->previousSessionDriver;
            $this->addError('cache_driver', 'Runtime settings test failed: '.$e->getMessage());

            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }
    }

    protected function getSavedNotificationMessage(): ?string
    {
        return null;
    }

    protected function cacheDriverOptions(): array
    {
        return [
            'file' => 'File',
            'database' => 'Database',
            'redis' => 'Redis',
        ];
    }
}

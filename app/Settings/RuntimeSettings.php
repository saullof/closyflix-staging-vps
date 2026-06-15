<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class RuntimeSettings extends Settings
{
    public string $cache_driver = 'file';

    public ?string $cache_prefix = null;

    public ?string $cache_redis_host = '127.0.0.1';

    public ?string $cache_redis_port = '6379';

    public ?string $cache_redis_password = null;

    public string $session_driver = 'file';

    public int $session_lifetime = 43200;

    public bool $session_expire_on_close = false;

    public bool $session_encrypt = false;

    public ?string $session_redis_host = '127.0.0.1';

    public ?string $session_redis_port = '6379';

    public ?string $session_redis_password = null;

    public static function group(): string
    {
        return 'runtime';
    }
}

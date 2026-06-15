<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $legacyPixPublicKey = $this->getLegacySetting('payments.pagarme_public_key');
        $legacyPixSecretKey = $this->getLegacySetting('payments.pagarme_secret_key');
        $legacyPixWebhookSecret = $this->getLegacySetting('payments.stripe_pix_webhook_secret');

        $this->addSettingIfMissing('payments.stripe_pix_public_key', $legacyPixPublicKey);
        $this->addSettingIfMissing('payments.stripe_pix_secret_key', $legacyPixSecretKey);
        $this->addSettingIfMissing('payments.stripe_pix_webhooks_secret', $legacyPixWebhookSecret);
        $this->addSettingIfMissing('payments.stripe_pix_checkout_disabled', false);
    }

    public function down(): void
    {
        $this->migrator->delete('payments.stripe_pix_public_key');
        $this->migrator->delete('payments.stripe_pix_secret_key');
        $this->migrator->delete('payments.stripe_pix_webhooks_secret');
        $this->migrator->delete('payments.stripe_pix_checkout_disabled');
    }

    private function addSettingIfMissing(string $key, mixed $value): void
    {
        if ($this->migrator->exists($key)) {
            return;
        }

        $this->migrator->add($key, $value);
    }

    private function getLegacySetting(string $key): mixed
    {
        if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'key') || !Schema::hasColumn('settings', 'value')) {
            return null;
        }

        return DB::table('settings')->where('key', $key)->value('value');
    }
};
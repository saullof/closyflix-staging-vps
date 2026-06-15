<?php

namespace App\Services\AgeCheck;

use App\Settings\ComplianceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CountryDetector
{
    public function __construct(
        protected ComplianceSettings $settings,
    ) {
    }

    public function detect(Request $request): ?string
    {
        return match ($this->settings->age_gate_country_detection_driver) {
            'cloudflare' => $this->fromCloudflare($request),
            'abstract' => $this->fromAbstract($request),
            default => null,
        };
    }

    protected function fromCloudflare(Request $request): ?string
    {
        $country = strtoupper((string) $request->header('CF-IPCountry'));

        return preg_match('/^[A-Z]{2}$/', $country) ? $country : null;
    }

    protected function fromAbstract(Request $request): ?string
    {
        if (!filled($this->settings->age_gate_abstract_api_key)) {
            return null;
        }

        $ip = $request->ip();

        if (!$ip) {
            return null;
        }

        return Cache::remember('agecheck:abstract:country:'.$ip, now()->addHours(12), function () use ($ip) {
            $response = $this->abstractResponse($ip);

            if (!$response->successful()) {
                return null;
            }

            $country = strtoupper((string) $response->json('country_code'));

            return preg_match('/^[A-Z]{2}$/', $country) ? $country : null;
        });
    }

    public function debugAbstract(Request $request): array
    {
        if ($this->settings->age_gate_country_detection_driver !== 'abstract') {
            return [];
        }

        if (!filled($this->settings->age_gate_abstract_api_key)) {
            return [
                'error' => 'missing API key',
            ];
        }

        $ip = $request->ip();

        if (!$ip) {
            return [
                'error' => 'missing request IP',
            ];
        }

        try {
            $response = $this->abstractResponse($ip);
        } catch (\Throwable $exception) {
            return [
                'ip' => $ip,
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'ip' => $ip,
            'status' => $response->status(),
            'successful' => $response->successful() ? 'yes' : 'no',
            'country_code' => strtoupper((string) $response->json('country_code')) ?: 'none',
            'body' => mb_substr($response->body(), 0, 1200),
        ];
    }

    protected function abstractResponse(string $ip)
    {
        return Http::timeout(3)->get(config('agecheck.abstract.endpoint'), [
            'api_key' => $this->settings->age_gate_abstract_api_key,
            'ip_address' => $ip,
            'fields' => 'country_code',
        ]);
    }
}

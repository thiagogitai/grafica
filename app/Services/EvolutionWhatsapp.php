<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EvolutionWhatsapp
{
    public static function enabled(): bool
    {
        return env('WHATSAPP_DRIVER', 'link') === 'evolution';
    }

    public static function sendText(string $toNumberE164, string $message): bool
    {
        $base = rtrim((string) env('EVOLUTION_API_BASE', ''), '/');
        $instance = env('EVOLUTION_API_INSTANCE');
        $token = env('EVOLUTION_API_TOKEN');
        if (!$base || !$instance || !$token) {
            return false;
        }

        // Documentação típica Evolution API: POST /message/sendText/{instance}
        $url = $base . '/message/sendText/' . urlencode($instance);
        $payload = [
            'number' => $toNumberE164,
            'text' => $message,
        ];

        $resp = Http::timeout((int) env('EVOLUTION_API_TIMEOUT', 8))
            ->withToken($token)
            ->acceptJson()
            ->post($url, $payload);

        return $resp->ok();
    }
}


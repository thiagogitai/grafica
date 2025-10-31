<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RemoteCatalog
{
    public static function baseUrl(): ?string
    {
        $base = env('REMOTE_CATALOG_BASE');
        return $base ? rtrim($base, '/') : null;
    }

    public static function pathSegment(): string
    {
        return trim((string) env('REMOTE_CATALOG_PATH', 'products'), '/');
    }

    public static function token(): ?string
    {
        return env('REMOTE_CATALOG_TOKEN') ?: null;
    }

    public static function fetchNormalized(string $slug): ?array
    {
        $base = static::baseUrl();
        if (!$base) {
            return null;
        }

        $cacheKey = 'remote_catalog:' . $slug;
        return Cache::remember($cacheKey, now()->addMinutes((int) env('REMOTE_CATALOG_TTL', 10)), function () use ($base, $slug) {
            $url = $base . '/' . static::pathSegment() . '/' . urlencode($slug);

            $req = Http::timeout((int) env('REMOTE_CATALOG_TIMEOUT', 5))
                ->acceptJson();
            if ($token = static::token()) {
                $req = $req->withToken($token);
            }

            $resp = $req->get($url);
            if (!$resp->ok()) {
                return null;
            }

            $raw = $resp->json();
            if (!is_array($raw)) {
                return null;
            }

            return static::normalize($raw);
        });
    }

    public static function normalize(array $raw): ?array
    {
        // base_price
        $basePrice = null;
        foreach (['base_price','basePrice','price'] as $k) {
            if (isset($raw[$k]) && is_numeric($raw[$k])) { $basePrice = (float) $raw[$k]; break; }
        }
        // title_override
        $title = $raw['title_override'] ?? $raw['title'] ?? $raw['name'] ?? null;
        $redirect = (bool) ($raw['redirect_to_upload'] ?? false);

        // options: aceita várias estruturas e mapeia para {name,label,choices:[{value,label,add}]}
        $opts = [];
        $sourceOptions = $raw['options'] ?? $raw['fields'] ?? $raw['attributes'] ?? [];
        if (!is_array($sourceOptions)) { $sourceOptions = []; }

        foreach ($sourceOptions as $opt) {
            if (!is_array($opt)) { continue; }
            $name = $opt['name'] ?? $opt['key'] ?? null;
            if (!$name) { continue; }
            $label = $opt['label'] ?? ucfirst(str_replace('_',' ', $name));
            $choicesRaw = $opt['choices'] ?? $opt['values'] ?? $opt['options'] ?? [];
            $choices = [];
            if (is_array($choicesRaw)) {
                foreach ($choicesRaw as $c) {
                    if (!is_array($c)) { continue; }
                    $value = $c['value'] ?? $c['key'] ?? $c['id'] ?? $c['label'] ?? null;
                    $clabel = $c['label'] ?? (is_scalar($value) ? (string) $value : 'Opção');
                    $add = null;
                    foreach (['add','delta','price_delta','increment'] as $ak) {
                        if (isset($c[$ak]) && is_numeric($c[$ak])) { $add = (float) $c[$ak]; break; }
                    }
                    $choices[] = [
                        'value' => $value,
                        'label' => $clabel,
                        'add' => $add ?? 0.0,
                    ];
                }
            }
            $opts[] = [
                'name' => $name,
                'label' => $label,
                'type' => 'select',
                'choices' => $choices,
            ];
        }

        return [
            'title_override' => $title,
            'base_price' => $basePrice,
            'redirect_to_upload' => $redirect,
            'options' => $opts,
        ];
    }
}


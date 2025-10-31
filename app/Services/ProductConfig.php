<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductConfig
{
    public static function slugForProduct(Product $product): string
    {
        return Str::slug($product->name);
    }

    public static function configPathForSlug(string $slug): string
    {
        $dir = env('LOCAL_CATALOG_DIR');
        if ($dir) {
            $dir = rtrim($dir, DIRECTORY_SEPARATOR);
            if (!str_starts_with($dir, DIRECTORY_SEPARATOR) && !preg_match('~^[A-Za-z]:\\\\~', $dir)) {
                // caminho relativo ao base_path
                $dir = base_path($dir);
            }
            return $dir . DIRECTORY_SEPARATOR . $slug . '.json';
        }
        return base_path('resources/data/products/' . $slug . '.json');
    }

    public static function loadForProduct(Product $product): ?array
    {
        $slug = static::slugForProduct($product);
        // 1) Tenta buscar de catálogo remoto
        $remote = RemoteCatalog::fetchNormalized($slug);
        if (is_array($remote)) {
            return $remote;
        }
        // 2) Fallback para arquivo local
        $path = static::configPathForSlug($slug);
        if (is_file($path)) {
            $json = file_get_contents($path);
            $data = json_decode($json, true);
            return is_array($data) ? $data : null;
        }
        // tentativa de localizar por nome dentro da pasta local
        $dir = dirname($path);
        if (is_dir($dir)) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') as $file) {
                $raw = @file_get_contents($file);
                if ($raw === false) continue;
                $arr = json_decode($raw, true);
                if (!is_array($arr)) continue;
                $candidate = $arr['slug'] ?? $arr['name'] ?? null;
                if ($candidate && Str::slug((string)$candidate) === $slug) {
                    return $arr;
                }
            }
        }
        return null;
    }

    public static function computePrice(array $config, array $selected, float $basePrice): float
    {
        $price = isset($config['base_price']) && is_numeric($config['base_price'])
            ? (float) $config['base_price']
            : $basePrice;

        $options = $config['options'] ?? [];
        foreach ($options as $opt) {
            $name = $opt['name'] ?? null;
            if (!$name || !array_key_exists($name, $selected)) {
                continue;
            }
            $value = $selected[$name];
            foreach ($opt['choices'] ?? [] as $choice) {
                if (($choice['value'] ?? null) == $value) {
                    $add = isset($choice['add']) ? (float) $choice['add'] : 0.0;
                    $price += $add;
                    break;
                }
            }
        }

        $qty = isset($selected['quantity']) ? (int) $selected['quantity'] : 1;
        $qty = max(1, $qty);
        return $price * $qty;
    }
}

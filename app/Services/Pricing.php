<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;

class Pricing
{
    /**
     * Obtem o markup global (em percentual).
     */
    public static function globalPercentage(): float
    {
        return (float) Setting::get('price_percentage', 0);
    }

    /**
     * Calcula o multiplicador aplicado ao valor base (1 + percentual/100).
     */
    public static function multiplierFor(Product $product, bool $includeGlobal = true): float
    {
        $factor = 1.0 + ((float) ($product->markup_percentage ?? 0)) / 100;
        if ($includeGlobal) {
            $factor *= 1.0 + (self::globalPercentage() / 100);
        }
        return $factor;
    }

    /**
     * Retorna o preço com markup aplicado.
     */
    public static function apply(float $basePrice, Product $product, bool $includeGlobal = true): float
    {
        return $basePrice * self::multiplierFor($product, $includeGlobal);
    }
}

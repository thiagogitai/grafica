<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Recupera um valor simples salvo nas configurações.
     */
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Retorna um valor booleano persistido como string/número.
     */
    public static function boolean(string $key, bool $default = false): bool
    {
        $value = static::get($key);
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower((string) $value);
        if (in_array($value, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }
        if (in_array($value, ['0', 'false', 'off', 'no', ''], true)) {
            return false;
        }

        return $default;
    }

    /**
     * Grava ou actualiza um valor de configuração.
     */
    public static function set($key, $value)
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}

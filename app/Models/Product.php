<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public const TEMPLATE_STANDARD = 'standard';
    public const TEMPLATE_CONFIG_AUTO = 'config:auto';
    public const TEMPLATE_FLYER = 'flyer';
    public const TEMPLATE_CONFIG_PREFIX = 'config:';

    protected $attributes = [
        'template' => self::TEMPLATE_STANDARD,
    ];

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'template',
        'request_only',
        'markup_percentage',
        'width',
        'height',
        'is_duplex',
    ];

    protected $casts = [
        'request_only' => 'boolean',
        'is_duplex' => 'boolean',
        'markup_percentage' => 'float',
    ];

    /**
     * Lista de templates disponíveis no painel.
     */
    public static function templateOptions(): array
    {
        return [
            self::TEMPLATE_STANDARD => 'Produto padrão (exibe preço fixo)',
            self::TEMPLATE_CONFIG_AUTO => 'Configuração automática (JSON do produto)',
            self::templateOptionKey('impressao-de-revista') => 'Template: Impressão de Revista',
            self::templateOptionKey('impressao-de-livro') => 'Template: Impressão de Livro',
            self::templateOptionKey('impressao-online-de-livretos-personalizados') => 'Template: Impressão de Livretos',
            self::templateOptionKey('impressao-de-apostila') => 'Template: Impressão de Apostila',
            self::TEMPLATE_FLYER => 'Calculadora de Flyers/Panfletos',
        ];
    }

    public static function templateOptionKey(string $slug): string
    {
        return self::TEMPLATE_CONFIG_PREFIX . trim($slug);
    }

    public function templateType(): string
    {
        $value = $this->attributes['template'] ?? self::TEMPLATE_STANDARD;
        if (str_starts_with($value, self::TEMPLATE_CONFIG_PREFIX)) {
            return 'config';
        }

        return $value;
    }

    public function templateSlug(): ?string
    {
        $value = $this->attributes['template'] ?? self::TEMPLATE_STANDARD;
        if (str_starts_with($value, self::TEMPLATE_CONFIG_PREFIX)) {
            return substr($value, strlen(self::TEMPLATE_CONFIG_PREFIX));
        }

        return null;
    }

    public function usesConfigTemplate(): bool
    {
        return $this->templateType() === 'config';
    }

    public function markupFactor(bool $includeGlobal = false): float
    {
        $factor = 1 + ((float) ($this->markup_percentage ?? 0)) / 100;
        if ($includeGlobal) {
            $global = (float) \App\Models\Setting::get('price_percentage', 0);
            $factor *= (1 + $global / 100);
        }
        return $factor;
    }

    public function effectiveTemplateLabel(): string
    {
        $templates = self::templateOptions();
        if (isset($templates[$this->template])) {
            return $templates[$this->template];
        }

        if ($slug = $this->templateSlug()) {
            return 'Template configurável: ' . ucfirst(str_replace('-', ' ', $slug));
        }

        return 'Template desconhecido';
    }
}

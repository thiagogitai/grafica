<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

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
        'category_id',
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
            self::templateOptionKey('livro') => 'Template: Livro',
            self::templateOptionKey('impressao-de-apostila') => 'Template: Apostila',
            self::templateOptionKey('impressao-de-jornal-de-bairro') => 'Template: Jornal de Bairro',
            self::templateOptionKey('impressao-online-de-livretos-personalizados') => 'Template: Livretos Personalizados',
            self::templateOptionKey('impressao-de-revista') => 'Template: Revista',
            self::templateOptionKey('impressao-de-tabloide') => 'Template: Tabloide',
            self::templateOptionKey('impressao-de-panfleto') => 'Template: Panfleto',
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

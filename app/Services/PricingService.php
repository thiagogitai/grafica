<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class PricingService
{
    private const BASE_URL = 'https://www.lojagraficaeskenazi.com.br';

    public function supports(string $slug): bool
    {
        return is_file($this->fieldKeysPath($slug));
    }

    public function quote(string $slug, array $options): array
    {
        if (!$this->supports($slug)) {
            throw new \RuntimeException("Field keys map missing for {$slug}");
        }

        $payload = $this->buildPayload($slug, $options);

        $response = Http::withHeaders($this->defaultHeaders())->timeout(30)
            ->post(self::BASE_URL . "/product/{$slug}/pricing", $payload);

        if (!$response->successful()) {
            throw new \RuntimeException(sprintf(
                'API pricing request failed for %s (%s): %s',
                $slug,
                $response->status(),
                $response->body()
            ));
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new \RuntimeException('Unexpected pricing response format.');
        }

        $price = $this->extractPrice($data);
        if ($price === null) {
            throw new \RuntimeException('Pricing API did not return a numeric value.');
        }

        return [
            'price' => $price,
            'payload' => $payload,
            'response' => $data,
        ];
    }

    protected function buildPayload(string $slug, array $options): array
    {
        $definition = $this->loadFieldDefinition($slug);
        $quantityKey = $definition['quantity_key'] ?? 'Q1';
        $quantity = $this->normalizeQuantity($options['quantity'] ?? $options['qty'] ?? 1);

        $fields = $definition['fields'] ?? [];
        $preparedFields = [];
        foreach ($fields as $name => $meta) {
            $preparedFields[] = array_merge($meta, ['name' => $name]);
        }
        usort($preparedFields, fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $apiOptions = [];
        foreach ($preparedFields as $field) {
            $fieldName = $field['name'];
            $value = $this->resolveOptionValue($fieldName, $options, $field);
            if ($value === null || $value === '') {
                throw new \RuntimeException("Missing value for option {$fieldName}");
            }

            $apiOptions[] = [
                'Key' => $field['key'],
                'Value' => $value,
            ];
        }

        return [
            'pricingParameters' => [
                $quantityKey => (string) $quantity,
                'Options' => $apiOptions,
            ],
        ];
    }

    protected function normalizeQuantity($quantity): int
    {
        $quantity = (int) $quantity;
        return $quantity > 0 ? $quantity : 1;
    }

    protected function resolveOptionValue(string $name, array $options, array $meta)
    {
        if (array_key_exists($name, $options)) {
            return (string) $options[$name];
        }

        if (isset($options['options']) && array_key_exists($name, $options['options'])) {
            return (string) $options['options'][$name];
        }

        return $meta['default'] ?? null;
    }

    protected function loadFieldDefinition(string $slug): array
    {
        $path = $this->fieldKeysPath($slug);
        $contents = file_get_contents($path);
        $data = json_decode($contents, true);

        if (!is_array($data)) {
            throw new \RuntimeException("Invalid field key definition for {$slug}");
        }

        return $data;
    }

    protected function extractPrice(array $response): ?float
    {
        $candidates = [
            Arr::get($response, 'Cost'),
            Arr::get($response, 'NonMarkupCost'),
            Arr::get($response, 'price'),
            Arr::get($response, 'FormattedCost'),
            Arr::get($response, 'FormattedNonMarkupCost'),
        ];

        foreach ($candidates as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $number = $this->toFloat($value);
            if ($number !== null) {
                return $number;
            }
        }

        return null;
    }

    protected function toFloat($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace('.', '', $value);
        $normalized = str_replace(',', '.', $normalized);
        $normalized = preg_replace('/[^0-9\\.]/', '', (string) $normalized);

        return $normalized !== '' && is_numeric($normalized)
            ? (float) $normalized
            : null;
    }

    protected function fieldKeysPath(string $slug): string
    {
        return base_path("resources/data/products/{$slug}-field-keys.json");
    }

}

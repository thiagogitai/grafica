<?php

namespace App\Http\Controllers;

use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ProductPriceController extends Controller
{
    public function __construct(
        private readonly PricingService $pricingService
    ) {
    }

    public function validatePrice(Request $request): JsonResponse
    {
        $input = $request->isJson() ? $request->json()->all() : $request->all();
        unset($input['force_validation'], $input['_force']);

        $productSlug = $input['product_slug'] ?? $request->input('product_slug');
        $productSlug = $this->normalizeSlug($productSlug);
        if (!$productSlug) {
            return response()->json([
                'success' => false,
                'error' => 'product_slug é obrigatório',
                'validated' => false,
            ], 400);
        }
        unset($input['product_slug']);

        $quantity = (int) ($input['quantity'] ?? $input['quantidade'] ?? 1);
        if ($quantity <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Quantidade inválida',
                'validated' => false,
            ], 400);
        }
        $input['quantity'] = $quantity;
        ksort($input);
        Log::info('validatePrice payload', ['product_slug' => $productSlug, 'payload' => $input]);

        $apiResult = $this->tryQuoteWithOfficialApi($productSlug, $input);
        if ($apiResult) {
            Log::info('validatePrice response', [
                'product_slug' => $productSlug,
                'price' => $apiResult['price'],
            ]);
            return response()->json([
                'success' => true,
                'price' => $apiResult['price'],
                'formatted_price' => $apiResult['response']['FormattedCost'] ?? null,
                'validated' => true,
                'quantity' => $quantity,
                'source' => 'matrix_api',
                'meta' => $this->buildMetaFromResponse($apiResult['response'] ?? []),
                'payload' => $apiResult['payload'] ?? null,
            ]);
        }

        $proxyResult = $this->fallbackToLegacyProxy($productSlug, $input);
        if ($proxyResult) {
            Log::info('validatePrice response (legacy)', [
                'product_slug' => $productSlug,
                'price' => $proxyResult['price'],
            ]);
            return response()->json([
                'success' => true,
                'price' => $proxyResult['price'],
                'validated' => true,
                'quantity' => $quantity,
                'source' => 'legacy_proxy',
                'meta' => $proxyResult['meta'] ?? null,
                'payload' => $proxyResult['payload'] ?? null,
            ]);
        }

        Log::error('Falha ao validar preço', [
            'product_slug' => $productSlug,
            'payload' => $input,
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Não foi possível validar o preço no momento.',
            'validated' => false,
        ], 502);
    }

    protected function tryQuoteWithOfficialApi(string $slug, array $options): ?array
    {
        if (!$this->pricingService->supports($slug)) {
            return null;
        }

        try {
            return $this->pricingService->quote($slug, $options);
        } catch (\Throwable $e) {
            Log::error("Erro ao consultar API oficial ({$slug}): {$e->getMessage()}");
            return null;
        }
    }

    protected function fallbackToLegacyProxy(string $slug, array $options): ?array
    {
        try {
            $proxyController = app(ApiPricingProxyController::class);
            $proxyRequest = new Request(array_merge($options, [
                'product_slug' => $slug,
            ]));
            $response = $proxyController->obterPreco($proxyRequest);
            $payload = json_decode($response->getContent(), true);

            if (($payload['success'] ?? false) && isset($payload['price'])) {
                return [
                    'price' => (float) $payload['price'],
                    'meta' => $payload['meta'] ?? null,
                    'payload' => $payload['payload'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Fallback proxy falhou para {$slug}: {$e->getMessage()}");
        }

        return null;
    }

    protected function buildMetaFromResponse(array $response): ?array
    {
        if (empty($response)) {
            return null;
        }

        $weight = $response['Weight'] ?? $response['hdnTotalWeight'] ?? null;
        if (is_string($weight)) {
            $weight = str_replace(',', '.', $weight);
        }
        $weight = is_numeric($weight) ? (float) $weight : null;

        $perUnitWeight = $response['PerUnitWeight'] ?? null;
        if (is_string($perUnitWeight)) {
            $perUnitWeight = str_replace(',', '.', $perUnitWeight);
        }
        $perUnitWeight = is_numeric($perUnitWeight) ? (float) $perUnitWeight : null;

        $dimensions = [
            'length' => Arr::get($response, 'Dimensions.Length') ?? Arr::get($response, 'Attributes.Length'),
            'width' => Arr::get($response, 'Dimensions.Width') ?? Arr::get($response, 'Attributes.Width'),
            'height' => Arr::get($response, 'Dimensions.Height') ?? Arr::get($response, 'Attributes.Height'),
        ];
        $dimensions = array_filter($dimensions, fn ($value) => $value !== null && $value !== '');

        $packages = Arr::get($response, 'Packages');

        if ($weight === null && $perUnitWeight === null && empty($dimensions) && empty($packages)) {
            return null;
        }

        return [
            'weight' => $weight,
            'formatted_weight' => $response['FormattedWeight'] ?? null,
            'per_unit_weight' => $perUnitWeight,
            'dimensions' => $dimensions ?: null,
            'packages' => $packages ?: null,
        ];
    }

    /**
     * Normaliza slugs vindos do front para os slugs oficiais com field-keys.
     */
    private function normalizeSlug(?string $slug): ?string
    {
        if (!$slug) {
            return null;
        }

        $slug = trim(strtolower($slug));

        $aliases = [
            'livro' => 'impressao-de-livro',
            'impressao-de-livro' => 'impressao-de-livro',
            'apostila' => 'impressao-de-apostila',
            'impressao-de-apostila' => 'impressao-de-apostila',
            'revista' => 'impressao-de-revista',
            'impressao-de-revista' => 'impressao-de-revista',
            'jornal' => 'impressao-de-jornal-de-bairro',
            'jornal-de-bairro' => 'impressao-de-jornal-de-bairro',
            'impressao-de-jornal-de-bairro' => 'impressao-de-jornal-de-bairro',
            'tabloide' => 'impressao-de-tabloide',
            'impressao-de-tabloide' => 'impressao-de-tabloide',
            'flyer' => 'impressao-de-flyer',
            'panfleto' => 'impressao-de-flyer',
            'impressao-de-flyer' => 'impressao-de-flyer',
            'cartao' => 'impressao-cartao-de-visita',
            'cartao-de-visita' => 'impressao-cartao-de-visita',
            'impressao-cartao-de-visita' => 'impressao-cartao-de-visita',
        ];

        return $aliases[$slug] ?? $slug;
    }
}

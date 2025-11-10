<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Services\ProductConfig;
use App\Services\Pricing;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $products = Product::all();
        $categories = \App\Models\Category::all();
        $requestOnlyGlobal = Setting::boolean('request_only', false);
        $settings = [
            'hero_title' => Setting::get('hero_title', 'A sua gráfica online'),
            'hero_subtitle' => Setting::get('hero_subtitle', 'Tudo o que precisa para o seu negócio. Rápido. Simples. A um preço justo.'),
            'about_title' => Setting::get('about_title', 'Sobre nós'),
            'about_description' => Setting::get('about_description', ''),
            'whatsapp_number' => Setting::get('whatsapp_number'),
            'features' => json_decode(Setting::get('features', '[]'), true) ?: [],
            'testimonials' => json_decode(Setting::get('testimonials', '[]'), true) ?: [],
        ];

        return view('home', [
            'products' => $products,
            'categories' => $categories,
            'settings' => $settings,
            'requestOnlyGlobal' => $requestOnlyGlobal,
        ]);
    }

    /**
     * Show the product details.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show(Product $product)
    {
        $requestOnlyGlobal = Setting::boolean('request_only', false);
        $requestOnlyProduct = $product->request_only;
        $requestOnlyCombined = $requestOnlyGlobal || $requestOnlyProduct;

        $bookFamilySlugs = $this->bookFamilySlugs();
        $bookTemplateAliases = $this->bookTemplateAliases();

        $detectedSlug = ProductConfig::slugForProduct($product);
        $templateSlug = $product->templateSlug();
        $normalizedTemplateSlug = $templateSlug && isset($bookTemplateAliases[$templateSlug])
            ? $bookTemplateAliases[$templateSlug]
            : $templateSlug;

        $configSlugToLoad = null;
        if (in_array($detectedSlug, $bookFamilySlugs, true)) {
            $configSlugToLoad = $detectedSlug;
        } elseif ($normalizedTemplateSlug && in_array($normalizedTemplateSlug, $bookFamilySlugs, true)) {
            $configSlugToLoad = $normalizedTemplateSlug;
        }

        if ($configSlugToLoad) {
            $config = ProductConfig::loadForProduct($product, $configSlugToLoad);
            if ($config) {
                $defaultOptions = $this->buildDefaultOptions($config);
                $initialQuote = null;
                if (!empty($defaultOptions)) {
                    try {
                        $initialQuote = app(PricingService::class)->quote($configSlugToLoad, $defaultOptions);
                    } catch (\Throwable $e) {
                        Log::warning("Não foi possível obter preço inicial para produto {$product->id}: {$e->getMessage()}");
                    }
                }

                $markupFactor = Pricing::multiplierFor($product, true);
                $initialPrice = $initialQuote['price'] ?? null;
                $initialFormattedPrice = null;
                $initialQuantity = (int) ($defaultOptions['quantity'] ?? 1);
                $initialUnitFormatted = null;
                if ($initialPrice !== null) {
                    $initialPrice *= $markupFactor;
                    $initialFormattedPrice = 'R$' . number_format($initialPrice, 2, ',', '.');
                }
                if ($initialPrice !== null && $initialQuantity > 0) {
                    $initialUnit = $initialPrice / $initialQuantity;
                    $initialUnitFormatted = 'R$' . number_format($initialUnit, 2, ',', '.');
                }

                return view('products.livro', [
                    'product' => $product,
                    'config' => $config,
                    'configSlug' => $configSlugToLoad,
                    'requestOnlyGlobal' => $requestOnlyGlobal,
                    'requestOnlyProduct' => $requestOnlyProduct,
                    'requestOnly' => $requestOnlyCombined,
                    'initialPrice' => $initialPrice,
                    'initialFormattedPrice' => $initialFormattedPrice,
                    'initialUnitFormattedPrice' => $initialUnitFormatted,
                    'initialQuantity' => $initialQuantity,
                    'initialOptionsJson' => !empty($defaultOptions) ? json_encode($defaultOptions) : null,
                    'markupFactor' => $markupFactor,
                    'hero' => $this->heroContentFor($configSlugToLoad, $product),
                ]);
            }
        }

        // Tratar template flyer - carregar config impressao-de-flyer
        if ($product->template === Product::TEMPLATE_FLYER) {
            $slug = 'impressao-de-flyer';
            $config = ProductConfig::loadForProduct($product, $slug);
            if ($config) {
                return view('product-json', [
                    'product' => $product,
                    'config' => $config,
                    'configSlug' => $slug,
                    'requestOnlyGlobal' => $requestOnlyGlobal,
                    'requestOnlyProduct' => $requestOnlyProduct,
                    'requestOnly' => $requestOnlyCombined,
                ]);
            }
        }

        switch ($product->templateType()) {
            case 'config':
                // AUTO-DETECÇÃO: Se for config:auto, tentar múltiplas variações do slug
                $slug = $product->templateSlug();
                if (!$slug || $product->template === Product::TEMPLATE_CONFIG_AUTO) {
                    $slug = ProductConfig::slugForProduct($product);
                }
                
                $config = ProductConfig::loadForProduct($product, $slug);
                
                // Se não encontrou, tentar variações comuns
                if (!$config) {
                    $baseSlug = $slug;
                    $variations = [
                        $baseSlug,
                        str_replace('impressao-de-', '', $baseSlug),
                        str_replace('impressao-online-de-', '', $baseSlug),
                        str_replace('impressao-', '', $baseSlug),
                        'impressao-' . $baseSlug,
                        'impressao-de-' . $baseSlug,
                    ];
                    
                    // Remover duplicatas mantendo ordem
                    $variations = array_unique($variations);
                    
                    foreach ($variations as $variation) {
                        if ($variation === $slug) continue; // Já tentou
                        $config = ProductConfig::loadForProduct($product, $variation);
                        if ($config) {
                            $slug = $variation;
                            break;
                        }
                    }
                }
                
                if ($config) {
                    return view('product-json', [
                        'product' => $product,
                        'config' => $config,
                        'configSlug' => $slug,
                        'requestOnlyGlobal' => $requestOnlyGlobal,
                        'requestOnlyProduct' => $requestOnlyProduct,
                        'requestOnly' => $requestOnlyCombined,
                    ]);
                }
                break;
        }

        return view('products.default', [
            'product' => $product,
            'requestOnlyGlobal' => $requestOnlyGlobal,
            'requestOnlyProduct' => $requestOnlyProduct,
            'requestOnly' => $requestOnlyCombined,
        ]);
    }

    private function buildDefaultOptions(array $config): array
    {
        $defaults = ['quantity' => 50];
        foreach (($config['options'] ?? []) as $opt) {
            $name = $opt['name'] ?? null;
            if (!$name) {
                continue;
            }

            if ($name === 'quantity') {
                $defaults['quantity'] = (int) ($opt['default'] ?? $defaults['quantity']);
                continue;
            }

            $value = $opt['default'] ?? null;
            if ($value === null) {
                $choice = $opt['choices'][0] ?? null;
                if ($choice) {
                    $value = $choice['value'] ?? $choice['label'] ?? null;
                }
            }

            if ($value !== null) {
                $defaults[$name] = $value;
            }
        }

        return $defaults;
    }

    private function bookFamilySlugs(): array
    {
        return [
            'impressao-de-livro',
            'impressao-de-apostila',
            'impressao-de-jornal-de-bairro',
            'impressao-online-de-livretos-personalizados',
            'impressao-de-revista',
            'impressao-de-tabloide',
            'impressao-de-panfleto',
        ];
    }

    private function bookTemplateAliases(): array
    {
        return [
            'livro' => 'impressao-de-livro',
            'apostila' => 'impressao-de-apostila',
            'jornal' => 'impressao-de-jornal-de-bairro',
            'jornal-de-bairro' => 'impressao-de-jornal-de-bairro',
            'livretos' => 'impressao-online-de-livretos-personalizados',
            'revista' => 'impressao-de-revista',
            'tabloide' => 'impressao-de-tabloide',
            'panfleto' => 'impressao-de-panfleto',
        ];
    }

    private function heroContentFor(string $slug, Product $product): array
    {
        $defaults = [
            'title' => $product->name,
            'subtitle' => '',
            'highlights' => [],
        ];

        $heroMap = [
            'impressao-de-livro' => $defaults,
            'impressao-de-apostila' => [
                'title' => 'Impressão de Apostilas',
                'subtitle' => 'Monte apostilas com capas personalizadas, miolos combinados e acabamento em espiral como na loja oficial.',
                'highlights' => [
                    'Formatação completa: capa, miolo 1 e miolo 2',
                    'Provas de cor e extras idênticos à matriz',
                    'Preço e prazo validados direto na API Eskenazi',
                ],
            ],
            'impressao-online-de-livretos-personalizados' => [
                'title' => 'Impressão de Livretos',
                'subtitle' => 'Fluxo oficial para livretos personalizados, com capas laminadas e costura conforme os padrões Eskenazi.',
                'highlights' => [
                    'Formatos especiais e laminação premium',
                    'Validação de preço em tempo real',
                    'Upload integrado e acompanhamento do frete',
                ],
            ],
            'impressao-de-revista' => [
                'title' => 'Impressão de Revista',
                'subtitle' => 'Replicamos o configurador oficial de revistas, com dobras, grampeação e UV reserva.',
                'highlights' => [
                    'Capas com laminação e UV reserva',
                    'Miolos couchê em várias gramaturas',
                    'Prazo e preço da matriz, sem variações',
                ],
            ],
            'impressao-de-tabloide' => [
                'title' => 'Impressão de Tabloide',
                'subtitle' => 'Tabloides com dobras, múltiplos formatos e tiragens controladas, exatamente como na Loja Eskenazi.',
                'highlights' => [
                    'Formatos grandes com dobras automáticas',
                    'Extras e frete replicados da matriz',
                    'Validação oficial do preço',
                ],
            ],
            'impressao-de-jornal-de-bairro' => [
                'title' => 'Impressão de Jornal de Bairro',
                'subtitle' => 'Jornais completos com grampeação, miolo em offset e prazos da gráfica oficial.',
                'highlights' => [
                    'Miolos em offset 48, 64, 80 páginas',
                    'Acabamentos e extras fiéis ao catálogo',
                    'Preço e prazo conferidos na API Eskenazi',
                ],
            ],
            'impressao-de-panfleto' => [
                'title' => 'Impressão de Panfletos',
                'subtitle' => 'Seleção rápida de papéis, cores e laminações, com o mesmo cálculo usado na matriz.',
                'highlights' => [
                    'Papéis couchê e offset com laminação',
                    'Integração com o upload e frete do carrinho',
                    'Preço validado instantaneamente com a matriz',
                ],
            ],
        ];

        return $heroMap[$slug] ?? $defaults;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Services\ProductConfig;
use Illuminate\Http\Request;

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

        switch ($product->templateType()) {
            case Product::TEMPLATE_FLYER:
                $prices = json_decode(file_get_contents(base_path('precos_flyer.json')), true);
                return view('flyers.show', [
                    'product' => $product,
                    'prices' => $prices,
                    'requestOnlyGlobal' => $requestOnlyGlobal,
                    'requestOnlyProduct' => $requestOnlyProduct,
                    'requestOnly' => $requestOnlyCombined,
                ]);

            case 'config':
                $slug = $product->templateSlug();
                if (!$slug || $product->template === Product::TEMPLATE_CONFIG_AUTO) {
                    $slug = ProductConfig::slugForProduct($product);
                }
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
                break;
        }

        return view('product', [
            'product' => $product,
            'requestOnlyGlobal' => $requestOnlyGlobal,
            'requestOnlyProduct' => $requestOnlyProduct,
            'requestOnly' => $requestOnlyCombined,
        ]);
    }
}

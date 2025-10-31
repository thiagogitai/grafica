<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Setting;
use App\Services\ProductConfig;

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
        $settings = [
            'hero_title' => Setting::get('hero_title', 'A sua gráfica online'),
            'hero_subtitle' => Setting::get('hero_subtitle', 'Tudo o que precisa para o seu negócio. Rápido. Simples. A um preço justo.'),
            'about_title' => Setting::get('about_title', 'Sobre nós'),
            'about_description' => Setting::get('about_description', ''),
            'whatsapp_number' => Setting::get('whatsapp_number'),
            'features' => json_decode(Setting::get('features', '[]'), true) ?: [],
            'testimonials' => json_decode(Setting::get('testimonials', '[]'), true) ?: [],
        ];
        return view('home', compact('products', 'categories', 'settings'));
    }

    /**
     * Show the product details.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show(Product $product)
    {
        if (preg_match('/flyer|panfleto|impressão em papel a4/i', $product->name)) {
            $prices = json_decode(file_get_contents(base_path('precos_flyer.json')), true);
            return view('flyers.show', ['product' => $product, 'prices' => $prices]);
        }

        $config = ProductConfig::loadForProduct($product);
        if ($config) {
            return view('product-json', compact('product', 'config'));
        }

        return view('product', compact('product'));
    }
}

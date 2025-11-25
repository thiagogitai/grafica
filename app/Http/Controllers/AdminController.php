<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $orders = Order::with('user')->latest()->get();
        $products = Product::all();
        return view('admin.dashboard', compact('orders', 'products'));
    }

    public function orders()
    {
        $orders = Order::with('user')->latest()->paginate(10);
        return view('admin.orders', compact('orders'));
    }

    public function products()
    {
        $products = Product::paginate(10);
        return view('admin.products', compact('products'));
    }

    public function createProduct()
    {
        return view('admin.create-product');
    }

    public function showProduct(Product $product)
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'request_only' => 'nullable|boolean',
            'markup_percentage' => 'nullable|numeric|min:0|max:500',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => 0,
            'image' => $imagePath,
            'template' => Product::TEMPLATE_CONFIG_AUTO,
            'request_only' => $request->boolean('request_only'),
            'markup_percentage' => $request->input('markup_percentage', 0),
        ]);

        return redirect()->route('admin.products')->with('success', 'Produto criado com sucesso!');
    }

    public function editProduct(Product $product)
    {
        return view('admin.edit-product', compact('product'));
    }

    public function updateProduct(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'request_only' => 'nullable|boolean',
            'markup_percentage' => 'nullable|numeric|min:0|max:500',
        ]);

        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image && \Storage::disk('public')->exists($product->image)) {
                \Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => 0,
            'image' => $imagePath,
            'template' => Product::TEMPLATE_CONFIG_AUTO,
            'request_only' => $request->boolean('request_only'),
            'markup_percentage' => $request->input('markup_percentage', 0),
        ]);

        return redirect()->route('admin.products')->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroyProduct(Product $product)
    {
        // Delete image if exists
        if ($product->image && \Storage::disk('public')->exists($product->image)) {
            \Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('admin.products')->with('success', 'Produto excluído com sucesso!');
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $order->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Status do pedido atualizado!');
    }

    public function settings()
    {
        $socialLinks = json_decode(\App\Models\Setting::get('social_links', '[]'), true);
        if (!is_array($socialLinks)) {
            $socialLinks = [];
        }

        $testimonials = json_decode(\App\Models\Setting::get('testimonials', '[]'), true);
        if (!is_array($testimonials)) {
            $testimonials = [];
        }

        $settings = [
            'hero_title' => \App\Models\Setting::get('hero_title'),
            'hero_subtitle' => \App\Models\Setting::get('hero_subtitle'),
            'whatsapp_number' => \App\Models\Setting::get('whatsapp_number'),
            'request_only' => \App\Models\Setting::boolean('request_only', false),
            'disable_price_editor' => \App\Models\Setting::boolean('disable_price_editor', false),
            'mercadopago_public_key' => \App\Models\Setting::get('mercadopago_public_key'),
            'mercadopago_access_token' => \App\Models\Setting::get('mercadopago_access_token'),
            'loggi_api_token' => \App\Models\Setting::get('loggi_api_token'),
            'loggi_account_id' => \App\Models\Setting::get('loggi_account_id'),
            'footer_text' => \App\Models\Setting::get('footer_text', ''),
            'social_links' => $socialLinks,
            'about_title' => \App\Models\Setting::get('about_title'),
            'about_description' => \App\Models\Setting::get('about_description'),
            'features' => \App\Models\Setting::get('features'),
            'testimonials' => $testimonials,
        ];
        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'required|string|max:500',
            'whatsapp_number' => 'nullable|string|max:20',
            'request_only' => 'nullable|boolean',
            'disable_price_editor' => 'nullable|boolean',
            'mercadopago_public_key' => 'nullable|string|max:255',
            'mercadopago_access_token' => 'nullable|string|max:255',
            'loggi_api_token' => 'nullable|string|max:255',
            'loggi_account_id' => 'nullable|string|max:255',
            'footer_text' => 'nullable|string|max:500',
            'social_links' => 'nullable|array',
            'social_links.*' => 'nullable|string|max:255',
            'about_title' => 'required|string|max:255',
            'about_description' => 'nullable|string',
            'features' => 'nullable|string',
            'testimonials' => 'nullable|array',
            'testimonials.*.name' => 'nullable|string|max:150',
            'testimonials.*.role' => 'nullable|string|max:150',
            'testimonials.*.text' => 'nullable|string|max:1000',
            'testimonials.*.existing_image' => 'nullable|string|max:255',
            'testimonials.*.image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Validação de JSON para features/testimonials
        $featuresArr = [];
        if ($request->filled('features')) {
            $featuresArr = json_decode($request->features, true);
            if (!is_array($featuresArr)) {
                return back()->withErrors(['features' => 'JSON inválido em Recursos.'])->withInput();
            }
        }
        \App\Models\Setting::set('hero_title', $request->hero_title);
        \App\Models\Setting::set('hero_subtitle', $request->hero_subtitle);
        if ($request->has('whatsapp_number')) {
            \App\Models\Setting::set('whatsapp_number', $request->whatsapp_number);
        }
        if ($request->has('mercadopago_public_key')) {
            \App\Models\Setting::set('mercadopago_public_key', $request->mercadopago_public_key);
        }
        if ($request->has('mercadopago_access_token')) {
            \App\Models\Setting::set('mercadopago_access_token', $request->mercadopago_access_token);
        }
        if ($request->has('loggi_api_token')) {
            \App\Models\Setting::set('loggi_api_token', $request->loggi_api_token);
        }
        if ($request->has('loggi_account_id')) {
            \App\Models\Setting::set('loggi_account_id', $request->loggi_account_id);
        }
        \App\Models\Setting::set('disable_price_editor', $request->boolean('disable_price_editor'));
        if ($request->has('footer_text')) {
            \App\Models\Setting::set('footer_text', $request->footer_text ?? '');
        }
        $socialLinksInput = collect($request->input('social_links', []))
            ->filter(fn($value) => filled($value))
            ->toArray();
        \App\Models\Setting::set('social_links', json_encode($socialLinksInput, JSON_UNESCAPED_UNICODE));
        \App\Models\Setting::set('request_only', $request->boolean('request_only'));
        \App\Models\Setting::set('about_title', $request->about_title);
        \App\Models\Setting::set('about_description', $request->about_description ?? '');
        \App\Models\Setting::set('features', json_encode($featuresArr, JSON_UNESCAPED_UNICODE));
        $testimonialsInput = collect($request->input('testimonials', []))
            ->map(function ($testimonial, $index) use ($request) {
                $imagePath = trim($testimonial['existing_image'] ?? '');
                if ($request->hasFile("testimonials.$index.image_file")) {
                    $file = $request->file("testimonials.$index.image_file");
                    if ($file && $file->isValid()) {
                        if ($imagePath && \Storage::disk('public')->exists($imagePath)) {
                            \Storage::disk('public')->delete($imagePath);
                        }
                        $imagePath = $file->store('testimonials', 'public');
                    }
                }
                return [
                    'name' => trim($testimonial['name'] ?? ''),
                    'role' => trim($testimonial['role'] ?? ''),
                    'text' => trim($testimonial['text'] ?? ''),
                    'image' => $imagePath ?: null,
                ];
            })
            ->filter(fn($testimonial) => $testimonial['name'] || $testimonial['text'] || $testimonial['image'])
            ->values()
            ->all();
        \App\Models\Setting::set('testimonials', json_encode($testimonialsInput, JSON_UNESCAPED_UNICODE));

        return redirect()->back()->with('success', 'Configurações atualizadas com sucesso!');
    }

    public function pricing()
    {
        $products = Product::orderBy('name')->get();
        $globalMarkup = \App\Models\Setting::get('price_percentage', 0);

        return view('admin.pricing', [
            'products' => $products,
            'globalMarkup' => $globalMarkup,
        ]);
    }

    public function updatePricing(Request $request)
    {
        $request->validate([
            'global_markup' => 'nullable|numeric|min:0|max:500',
            'product_markups' => 'nullable|array',
            'product_markups.*' => 'nullable|numeric|min:0|max:500',
        ]);

        if ($request->filled('global_markup')) {
            \App\Models\Setting::set('price_percentage', $request->input('global_markup', 0));
        }

        $productMarkups = $request->input('product_markups', []);
        if (!empty($productMarkups)) {
            foreach ($productMarkups as $productId => $markup) {
                $product = Product::find($productId);
                if (!$product) {
                    continue;
                }
                $product->markup_percentage = (float) $markup;
                $product->save();
            }
        }

        return redirect()->route('admin.pricing')->with('success', 'Faturamento atualizado com sucesso!');
    }
}

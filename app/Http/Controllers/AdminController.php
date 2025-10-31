<?php

namespace App\Http\Controllers;

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

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image' => $imagePath,
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
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            'price' => $request->price,
            'image' => $imagePath,
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
        $settings = [
            'hero_title' => \App\Models\Setting::get('hero_title'),
            'hero_subtitle' => \App\Models\Setting::get('hero_subtitle'),
            'whatsapp_number' => \App\Models\Setting::get('whatsapp_number'),
            'price_percentage' => \App\Models\Setting::get('price_percentage'),
            'about_title' => \App\Models\Setting::get('about_title'),
            'about_description' => \App\Models\Setting::get('about_description'),
            'features' => \App\Models\Setting::get('features'),
            'testimonials' => \App\Models\Setting::get('testimonials'),
        ];
        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'required|string|max:500',
            'whatsapp_number' => 'nullable|string|max:20',
            'price_percentage' => 'nullable|numeric|min:0|max:100',
            'about_title' => 'required|string|max:255',
            'about_description' => 'nullable|string',
            'features' => 'nullable|string',
            'testimonials' => 'nullable|string',
        ]);

        // Validação de JSON para features/testimonials
        $featuresArr = [];
        if ($request->filled('features')) {
            $featuresArr = json_decode($request->features, true);
            if (!is_array($featuresArr)) {
                return back()->withErrors(['features' => 'JSON inválido em Recursos.'])->withInput();
            }
        }
        $testimonialsArr = [];
        if ($request->filled('testimonials')) {
            $testimonialsArr = json_decode($request->testimonials, true);
            if (!is_array($testimonialsArr)) {
                return back()->withErrors(['testimonials' => 'JSON inválido em Depoimentos.'])->withInput();
            }
        }

        \App\Models\Setting::set('hero_title', $request->hero_title);
        \App\Models\Setting::set('hero_subtitle', $request->hero_subtitle);
        if ($request->has('whatsapp_number')) {
            \App\Models\Setting::set('whatsapp_number', $request->whatsapp_number);
        }
        if ($request->has('price_percentage')) {
            \App\Models\Setting::set('price_percentage', $request->price_percentage);
        }
        \App\Models\Setting::set('about_title', $request->about_title);
        \App\Models\Setting::set('about_description', $request->about_description ?? '');
        \App\Models\Setting::set('features', json_encode($featuresArr, JSON_UNESCAPED_UNICODE));
        \App\Models\Setting::set('testimonials', json_encode($testimonialsArr, JSON_UNESCAPED_UNICODE));

        return redirect()->back()->with('success', 'Configurações atualizadas com sucesso!');
    }
}

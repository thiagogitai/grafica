<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Mostra a página para upload do arquivo de arte.
     */
    public function create(Request $request, Product $product)
    {
        $options = $request->except(['product']);
        return view('upload', compact('product', 'options'));
    }

    /**
     * Armazena o arquivo de arte e adiciona o produto ao carrinho.
     */
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'file_front' => 'required|file|mimes:pdf,jpg,png,cdr,ai,psd|max:51200',
            'file_back' => 'nullable|file|mimes:pdf,jpg,png,cdr,ai,psd|max:51200',
            'margin_confirmation' => 'required',
        ], [
            'file_front.required' => 'O arquivo da frente é obrigatório.',
            'margin_confirmation.required' => 'Você deve confirmar que verificou as margens de segurança.',
        ]);

        $cart = session()->get('cart', []);
        $artworkFiles = [];
        $cartItemId = uniqid($product->id . '_');

        // Salva o arquivo da frente
        if ($request->hasFile('file_front')) {
            $file = $request->file('file_front');
            $extension = $file->getClientOriginalExtension();
            $fileName = "{$cartItemId}_frente.{$extension}";
            $path = $file->storeAs('public/uploads', $fileName);
            $artworkFiles['front'] = Str::after($path, 'public/');
        }

        // Salva o arquivo do verso
        if (!empty($product->is_duplex) && $request->hasFile('file_back')) {
            $file = $request->file('file_back');
            $extension = $file->getClientOriginalExtension();
            $fileName = "{$cartItemId}_verso.{$extension}";
            $path = $file->storeAs('public/uploads', $fileName);
            $artworkFiles['back'] = Str::after($path, 'public/');
        }

        // Opções de personalização
        $options = $request->only(['capa_tipo', 'paginas', 'papel_tipo', 'quantity']);

        // Adiciona ao carrinho
        $cart[$cartItemId] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => $options['quantity'] ?? 1,
            'price' => $product->price,
            'image' => $product->image,
            'artwork' => $artworkFiles,
            'options' => $options,
        ];

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Produto adicionado ao carrinho com sucesso!');
    }
}


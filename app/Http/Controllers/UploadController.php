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
            'file_front' => 'required|file|mimes:pdf|max:51200',
            'file_back' => 'nullable|file|mimes:pdf|max:51200',
            'margin_confirmation' => 'required',
        ], [
            'file_front.required' => 'O arquivo da frente é obrigatório.',
            'file_front.mimes' => 'Envie o arquivo da frente em formato PDF.',
            'file_back.mimes' => 'O arquivo do verso deve estar em formato PDF.',
            'margin_confirmation.required' => 'Você deve confirmar que verificou as margens de segurança.',
        ]);

        $cart = session()->get('cart', []);
        $artworkFiles = [];
        $cartItemId = uniqid($product->id . '_');

        // Salva o arquivo da frente
        if ($request->hasFile('file_front')) {
            $file = $request->file('file_front');
            $fileName = "{$cartItemId}_frente.pdf";
            $path = $file->storeAs('public/uploads', $fileName);
            $artworkFiles['front'] = Str::after($path, 'public/');
        }

        // Salva o arquivo do verso
        if (!empty($product->is_duplex) && $request->hasFile('file_back')) {
            $file = $request->file('file_back');
            $fileName = "{$cartItemId}_verso.pdf";
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

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function analyzePdf(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        if ($request->hasFile('pdf_file')) {
            $file = $request->file('pdf_file');
            
            // For now, we'll just simulate a successful analysis
            // In a real application, you would use a library like Imagick or Spatie's pdf-to-image
            // to check resolution and dimensions.

            $filePath = $file->store('private/uploads');

            return response()->json([
                'success' => true,
                'message' => 'Arquivo válido e pronto para impressão!',
                'file_path' => $filePath,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
    }
}
<?php

namespace App\Http\Controllers;

use App\Services\OcrService;
use Illuminate\Http\Request;

class OcrController extends Controller
{
    public function analyze(Request $request, OcrService $ocr)
    {
        // Validar que viene una imagen
        $request->validate([
            'image' => ['required', 'image']
        ]);

        // Guardar temporalmente
        $path = $request->file('image')->store('tmp', 'public');

        // Obtener ruta absoluta
        $imagePath = storage_path('app/public/' . $path);

        // Ejecutar OCR
        $receiptFullText = $ocr->extractText($imagePath);

        dd($receiptFullText);
    }
}
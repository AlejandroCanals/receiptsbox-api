<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReceiptController;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;


Route::prefix('v1')->group(function () {
    // 🔐 Auth routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', function (Request $request) {
            return $request->user();
        });
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // 📦 Receipts CRUD
        Route::apiResource('receipts', ReceiptController::class);

        // 🧠 OCR test endpoint (Google Vision)
        Route::post('/test-vision', function (Request $request) {
            if (!$request->hasFile('image')) {
                return response()->json(['error' => 'No image uploaded'], 400);
            }
        
            $file = $request->file('image');
            $path = $file->store('tmp', 'public');
        
            $client = new ImageAnnotatorClient([
                'credentials' => config('services.google.credentials_path'),
            ]);
        
            try {
                $imageContent = file_get_contents(storage_path('app/public/' . $path));
        
                // Crear feature y request
                $feature = (new Feature())->setType(Type::TEXT_DETECTION);
                $image = (new Image())->setContent($imageContent);
                $annotateRequest = (new AnnotateImageRequest())
                    ->setImage($image)
                    ->setFeatures([$feature]);
        
                // ✅ NUEVO: crear objeto BatchAnnotateImagesRequest
                $batchRequest = (new BatchAnnotateImagesRequest())
                    ->setRequests([$annotateRequest]);
        
                // Ejecutar OCR
                $response = $client->batchAnnotateImages($batchRequest);
                $annotationResponse = $response->getResponses()[0] ?? null;
        
                if (!$annotationResponse) {
                    return response()->json(['error' => 'Empty Vision response'], 500);
                }
        
                if ($annotationResponse->hasError()) {
                    return response()->json([
                        'error' => $annotationResponse->getError()->getMessage(),
                    ], 500);
                }
        
                // Extraer texto OCR
                $annotation = $annotationResponse->getFullTextAnnotation();
                $fullText = $annotation ? $annotation->getText() : null;
        
                return response()->json([
                    'message' => 'OK',
                    'text' => $fullText,
                ]);
            } catch (\Throwable $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            } finally {
                $client->close();
            }
        });
    });
});

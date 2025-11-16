<?php

namespace App\Jobs;

use App\Models\Receipt;
use App\Services\OcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

use Throwable;

class ProcessReceiptImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Receipt $receipt;
    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(Receipt $receipt)
    {
        $this->receipt = $receipt;
    }

    public function handle(OcrService $ocr): void
    {


        /*
        try {
            // Inicializar cliente Google Vision
            $imageAnnotator = new ImageAnnotatorClient([
                'credentials' => config('services.google.credentials_path')
            ]);
            
            // Leer imagen
            $imageContent = file_get_contents($imagePath);
            
            // Ejecutar detección de texto
            $response = $imageAnnotator->textDetection($imageContent);
            $texts = $response->getTextAnnotations();
            
            if (empty($texts)) {
                throw new \Exception('No text detected in image');
            }
            
            // El primer elemento contiene todo el texto
            $fullText = $texts[0]->getDescription();
            
            Log::info('Google Vision OCR Text:', [
                'receipt_id' => $this->receipt->id,
                'text' => $fullText
            ]);
            
            // Extraer información con regex mejorados
            $this->receipt->merchant = $this->extractMerchant($fullText);
            $this->receipt->amount = $this->extractAmount($fullText);
            $this->receipt->date = $this->extractDate($fullText);
            $this->receipt->status = 'processed';
            $this->receipt->save();

            Log::info('Receipt processed with Google Vision:', [
                'id' => $this->receipt->id,
                'merchant' => $this->receipt->merchant,
                'amount' => $this->receipt->amount,
                'date' => $this->receipt->date
            ]);
            
            $imageAnnotator->close();

        } catch (Throwable $e) {
            Log::error('Receipt processing failed with Google Vision:', [
                'receipt_id' => $this->receipt->id,
                'error' => $e->getMessage()
            ]);
            
            $this->receipt->update(['status' => 'failed']);
            throw $e;
        }*/
    }

    /**
     * Extrae merchant (más simple y efectivo con Google Vision)
     */
    private function extractMerchant(string $text): ?string
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        
        $exclude = [
            'ticket', 'factura', 'recibo', 'total', 'fecha', 'nif', 'cif',
            'c/', 'calle', 'avenida', 'barcelona', 'madrid', 'malaga', 
            'menorca', 'mallorca', 'cp:', 'telefono', 'email', 'web'
        ];
        
        foreach (array_slice($lines, 0, 8) as $line) {
            if (strlen($line) < 3 || strlen($line) > 50) continue;
            
            $lower = strtolower($line);
            $hasExclude = false;
            foreach ($exclude as $word) {
                if (stripos($lower, $word) !== false) {
                    $hasExclude = true;
                    break;
                }
            }
            if ($hasExclude) continue;
            
            // Si tiene muchos números, skip
            if (preg_match_all('/\d/', $line) > strlen($line) * 0.4) continue;
            
            // Si tiene precio, skip
            if (preg_match('/\d+[.,]\d{2}/', $line)) continue;
            
            // Si solo tiene letras y espacios, es candidato
            if (preg_match('/^[A-ZÁÉÍÓÚa-záéíóú\s\.\',&\-]+$/u', $line)) {
                return ucwords(strtolower(trim($line)));
            }
        }
        
        return null;
    }

    /**
     * Extrae el total (prioriza líneas con "total")
     */
    private function extractAmount(string $text): ?float
    {
        $lines = preg_split('/\r?\n/', $text);
        $amounts = [];
        
        foreach ($lines as $i => $line) {
            // Normalizar separadores
            $line = str_replace(',', '.', $line);
            
            // Buscar montos
            if (preg_match_all('/(\d{1,5}\.\d{2})/', $line, $matches)) {
                foreach ($matches[1] as $match) {
                    $value = floatval($match);
                    if ($value < 0.1) continue;
                    
                    $score = $value; // Base score
                    
                    // MEGA bonus si tiene "total"
                    if (preg_match('/total/i', $line)) {
                        $score += 10000;
                    }
                    
                    // Bonus si está al final
                    if ($i > count($lines) - 5) {
                        $score += 100;
                    }
                    
                    // Penalizar "base" o "neto"
                    if (preg_match('/base|neto|imponible/i', $line)) {
                        $score -= 5000;
                    }
                    
                    $amounts[] = ['value' => $value, 'score' => $score];
                }
            }
        }
        
        if (empty($amounts)) return null;
        
        usort($amounts, fn($a, $b) => $b['score'] <=> $a['score']);
        return $amounts[0]['value'];
    }

    /**
     * Extrae la fecha
     */
    private function extractDate(string $text): ?string
    {
        // DD/MM/YYYY
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/', $text, $m)) {
            try {
                $year = strlen($m[3]) === 2 ? 2000 + (int)$m[3] : (int)$m[3];
                return sprintf('%04d-%02d-%02d', $year, (int)$m[2], (int)$m[1]);
            } catch (\Exception $e) {}
        }
        
        return null;
    }

    public function failed(Throwable $exception): void
    {
        $this->receipt->update(['status' => 'failed']);
    }
}
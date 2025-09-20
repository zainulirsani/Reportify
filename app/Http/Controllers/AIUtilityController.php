<?php
namespace App\Http\Controllers;

use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIUtilityController extends Controller
{
    public function __construct(protected AIService $aiService)
    {
    }

    public function rewriteDescription(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:2000',
        ]);

        try {
            $rewrittenSuggestions = $this->aiService->rewriteTextForClarity($validated['text']);
            
            // LANGKAH DEBUGGING: Catat apa yang dikembalikan oleh service
            Log::info('Saran dari AIService:', ['suggestions' => $rewrittenSuggestions]);
            
            return response()->json(['suggestions' => $rewrittenSuggestions]);
        } catch (\Exception $e) {
            Log::error('Error di AIUtilityController: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal terhubung dengan AI. Coba lagi nanti.'], 500);
        }
    }
}
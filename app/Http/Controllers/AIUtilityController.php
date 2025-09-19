<?php
namespace App\Http\Controllers;

use App\Services\AIService;
use Illuminate\Http\Request;

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
            // Method ini sekarang mengembalikan array
            $rewrittenSuggestions = $this->aiService->rewriteTextForClarity($validated['text']);
            
            // Kirim array tersebut dalam key 'suggestions'
            return response()->json(['suggestions' => $rewrittenSuggestions]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal terhubung dengan AI. Coba lagi nanti.'], 500);
        }
    }
}
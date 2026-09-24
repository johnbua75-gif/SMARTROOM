<?php

namespace App\Http\Controllers;

use App\Services\FacultyScheduleAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacultyScheduleAssistantController extends Controller
{
    public function ask(Request $request, FacultyScheduleAssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'use_llm' => ['sometimes', 'boolean'],
        ]);

        $useLlm = (bool) ($validated['use_llm'] ?? false);
        $result = $assistant->respond($request->user(), (string) $validated['message'], $useLlm);

        return response()->json([
            'success' => true,
            'intent' => $result['intent'] ?? 'unknown',
            'answer' => $result['answer'] ?? 'I could not process that request.',
            'items' => $result['items'] ?? [],
            'llm_available' => (bool) env('GROQ_API_KEY'),
            'suggestions' => [
                'What is my schedule today?',
                'Where is my next class?',
                'Do I have classes tomorrow?',
                'Show my Wednesday schedule.',
                'When is my first class?',
            ],
        ]);
    }
}

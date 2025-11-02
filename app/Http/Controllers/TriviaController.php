<?php

namespace App\Http\Controllers;

use App\Services\OpenTriviaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TriviaController extends Controller
{
    public function __construct(
        private OpenTriviaService $triviaService
    ) {}

    /**
     * Get trivia categories from Open Trivia Database
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = $this->triviaService->getCategories();
            
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trivia questions from Open Trivia Database
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function questions(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'amount' => ['required', 'integer', 'min:1', 'max:50'],
                'category' => ['nullable', 'integer', 'min:1'],
                'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
                'type' => ['nullable', Rule::in(['multiple', 'boolean'])]
            ]);

            $questions = $this->triviaService->getQuestions($validated);
            
            // Check if the service returned an error
            if (isset($questions['error']) && $questions['error']) {
                return response()->json([
                    'success' => false,
                    'message' => $questions['message'],
                    'data' => []
                ], 503); // Service Unavailable
            }
            
            return response()->json([
                'success' => true,
                'data' => $questions
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters provided',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch questions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
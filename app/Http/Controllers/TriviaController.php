<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionsRequest;
use App\Services\OpenTriviaService;
use Illuminate\Http\JsonResponse;

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
     * @param QuestionsRequest $request
     * @return JsonResponse
     */
    public function questions(QuestionsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch questions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
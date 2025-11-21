<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenTriviaService
{
    private const BASE_URL = 'https://opentdb.com';
    private const CATEGORIES_ENDPOINT = '/api_category.php';
    private const QUESTIONS_ENDPOINT = '/api.php';
    private const TOKEN_ENDPOINT = '/api_token.php';
    private const CATEGORIES_CACHE_KEY = 'trivia_categories';
    private const CATEGORIES_CACHE_TTL = 3600; // 1 hour in seconds
    private const TOKEN_CACHE_TTL = 19800; // 5.5 hours (buffer less than API's 6-hour expiry)

    /**
     * Fetch categories from Open Trivia Database API with caching
     *
     * @return array
     */
    public function getCategories(): array
    {
        try {
            return Cache::remember(self::CATEGORIES_CACHE_KEY, self::CATEGORIES_CACHE_TTL, function () {
                $response = Http::timeout(10)
                    ->retry(3, 1000)
                    ->get(self::BASE_URL . self::CATEGORIES_ENDPOINT);

                if (!$response->successful()) {
                    throw new RequestException($response);
                }

                $data = $response->json();
                
                if (!isset($data['trivia_categories']) || !is_array($data['trivia_categories'])) {
                    throw new \Exception('Invalid API response format');
                }

                return $this->formatCategories($data['trivia_categories']);
            });
        } catch (\Exception $e) {
            Log::error('Failed to fetch trivia categories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getFallbackCategories();
        }
    }

    /**
     * Get category name by ID
     *
     * @param int|null $categoryId
     * @return string|null
     */
    public function getCategoryName(?int $categoryId): ?string
    {
        if (!$categoryId) {
            return null;
        }

        $categories = $this->getCategories();
        $category = collect($categories)->firstWhere('id', $categoryId);
        
        return $category['name'] ?? null;
    }

    /**
     * Fetch questions from Open Trivia Database API
     *
     * @param array $params
     * @return array
     */
    public function getQuestions(array $params): array
    {
        try {
            $validatedParams = $this->validateQuestionParams($params);
            
            // Try to add session token if available
            $userId = $params['user_id'] ?? (auth()->check() ? auth()->id() : null);
            $token = $this->getSessionToken($userId);
            
            if ($token) {
                $validatedParams['token'] = $token;
            }
            
            $response = Http::timeout(15)
                ->retry(3, 1000)
                ->get(self::BASE_URL . self::QUESTIONS_ENDPOINT, $validatedParams);

            if (!$response->successful()) {
                throw new RequestException($response);
            }

            $data = $response->json();
            
            if (!isset($data['results']) || !is_array($data['results'])) {
                throw new \Exception('Invalid API response format');
            }

            // Handle token exhaustion (code 4) by trying without token
            if (isset($data['response_code']) && $data['response_code'] === 4 && $token) {
                $this->clearToken($userId);
                unset($validatedParams['token']);
                return $this->getQuestions($validatedParams);
            }

            // Check for other API response codes
            if (isset($data['response_code']) && $data['response_code'] !== 0) {
                throw new \Exception($this->getApiErrorMessage($data['response_code']));
            }

            return $this->processQuestions($data['results']);
        } catch (\Exception $e) {
            Log::error('Failed to fetch trivia questions', [
                'error' => $e->getMessage()
            ]);

            return $this->handleApiErrors($e);
        }
    }

    /**
     * Validate question parameters
     *
     * @param array $params
     * @return array
     */
    private function validateQuestionParams(array $params): array
    {
        $validated = [];

        // Amount (required, 1-50)
        $amount = $params['amount'] ?? 10;
        $validated['amount'] = max(1, min(50, (int) $amount));

        // Category (optional)
        if (isset($params['category']) && is_numeric($params['category'])) {
            $validated['category'] = (int) $params['category'];
        }

        // Difficulty (optional)
        if (isset($params['difficulty']) && in_array($params['difficulty'], ['easy', 'medium', 'hard'])) {
            $validated['difficulty'] = $params['difficulty'];
        }

        // Type (optional)
        if (isset($params['type']) && in_array($params['type'], ['multiple', 'boolean'])) {
            $validated['type'] = $params['type'];
        }

        return $validated;
    }

    /**
     * Process questions by decoding HTML entities and shuffling answers
     *
     * @param array $questions
     * @return array
     */
    private function processQuestions(array $questions): array
    {
        return array_map(function ($question) {
            // Decode HTML entities
            $processed = $this->decodeHtmlEntities($question);
            
            // Shuffle answers
            $processed = $this->shuffleAnswers($processed);
            
            return $processed;
        }, $questions);
    }

    /**
     * Decode HTML entities in question data
     *
     * @param array $question
     * @return array
     */
    private function decodeHtmlEntities(array $question): array
    {
        $fieldsTodecode = ['question', 'correct_answer'];
        
        foreach ($fieldsTodecode as $field) {
            if (isset($question[$field])) {
                $question[$field] = html_entity_decode($question[$field], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        // Decode incorrect answers array
        if (isset($question['incorrect_answers']) && is_array($question['incorrect_answers'])) {
            $question['incorrect_answers'] = array_map(function ($answer) {
                return html_entity_decode($answer, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }, $question['incorrect_answers']);
        }

        return $question;
    }

    /**
     * Shuffle answer options for a question
     *
     * @param array $question
     * @return array
     */
    private function shuffleAnswers(array $question): array
    {
        if (!isset($question['correct_answer']) || !isset($question['incorrect_answers'])) {
            return $question;
        }

        // Combine all answers
        $allAnswers = array_merge(
            [$question['correct_answer']],
            $question['incorrect_answers']
        );

        // Shuffle the combined answers
        shuffle($allAnswers);

        // Add shuffled answers to question
        $question['shuffled_answers'] = $allAnswers;

        return $question;
    }

    /**
     * Format categories for frontend consumption
     *
     * @param array $categories
     * @return array
     */
    private function formatCategories(array $categories): array
    {
        return array_map(function ($category) {
            return [
                'id' => $category['id'],
                'name' => $category['name']
            ];
        }, $categories);
    }

    /**
     * Get fallback categories when API fails
     *
     * @return array
     */
    private function getFallbackCategories(): array
    {
        return [
            ['id' => 9, 'name' => 'General Knowledge'],
            ['id' => 10, 'name' => 'Entertainment: Books'],
            ['id' => 11, 'name' => 'Entertainment: Film'],
            ['id' => 12, 'name' => 'Entertainment: Music'],
            ['id' => 17, 'name' => 'Science & Nature'],
            ['id' => 18, 'name' => 'Science: Computers'],
            ['id' => 19, 'name' => 'Science: Mathematics'],
            ['id' => 20, 'name' => 'Mythology'],
            ['id' => 21, 'name' => 'Sports'],
            ['id' => 22, 'name' => 'Geography'],
            ['id' => 23, 'name' => 'History'],
            ['id' => 24, 'name' => 'Politics'],
            ['id' => 25, 'name' => 'Art'],
            ['id' => 26, 'name' => 'Celebrities'],
            ['id' => 27, 'name' => 'Animals'],
        ];
    }

    /**
     * Handle API errors with graceful fallbacks
     *
     * @param \Exception $exception
     * @return array
     */
    private function handleApiErrors(\Exception $exception): array
    {
        return [
            'error' => true,
            'message' => 'Unable to fetch questions at this time. Please try again later.',
            'questions' => []
        ];
    }

    /**
     * Get human-readable error message for API response codes
     *
     * @param int $code
     * @return string
     */
    private function getApiErrorMessage(int $code): string
    {
        return match ($code) {
            1 => 'No results found for the given parameters',
            2 => 'Invalid parameter provided',
            3 => 'Token not found',
            4 => 'Token empty',
            5 => 'Rate limit exceeded',
            default => 'Unknown API error occurred'
        };
    }

    /**
     * Request a new session token from the API
     *
     * @return string|null
     */
    private function requestNewToken(): ?string
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 1000)
                ->get(self::BASE_URL . self::TOKEN_ENDPOINT, ['command' => 'request']);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            
            if (!isset($data['response_code']) || $data['response_code'] !== 0) {
                return null;
            }

            return $data['token'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get session token from cache or request new one
     *
     * @param int|null $userId
     * @return string|null
     */
    private function getSessionToken(?int $userId = null): ?string
    {
        $cacheKey = $this->getTokenCacheKey($userId);
        
        // Try to get token from cache
        $token = Cache::get($cacheKey);
        
        if ($token) {
            return $token;
        }

        // Request new token if not in cache
        $token = $this->requestNewToken();
        
        if ($token) {
            Cache::put($cacheKey, $token, self::TOKEN_CACHE_TTL);
        }
        
        return $token;
    }

    /**
     * Clear session token from cache
     *
     * @param int|null $userId
     * @return void
     */
    private function clearToken(?int $userId = null): void
    {
        $cacheKey = $this->getTokenCacheKey($userId);
        Cache::forget($cacheKey);
    }

    /**
     * Generate cache key for session token
     *
     * @param int|null $userId
     * @return string
     */
    private function getTokenCacheKey(?int $userId = null): string
    {
        if ($userId) {
            return "trivia_token_user_{$userId}";
        }
        
        return "trivia_token_guest_" . session()->getId();
    }


}
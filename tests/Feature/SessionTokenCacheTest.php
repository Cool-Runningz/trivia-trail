<?php

use App\Models\User;
use App\Services\OpenTriviaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('session tokens are cached with correct user-based keys', function () {
    $user = User::factory()->create();
    
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([
            'response_code' => 0,
            'token' => 'cached_user_token'
        ]),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'Test question?',
                    'correct_answer' => 'Answer',
                    'incorrect_answers' => ['Wrong1', 'Wrong2', 'Wrong3'],
                    'difficulty' => 'easy',
                    'category' => 'Test'
                ]
            ]
        ])
    ]);

    $service = app(OpenTriviaService::class);
    
    // First call should request token and cache it
    $questions1 = $service->getQuestions([
        'amount' => 1,
        'user_id' => $user->id
    ]);
    
    // Verify token is cached
    $cachedToken = Cache::get("trivia_token_user_{$user->id}");
    expect($cachedToken)->toBe('cached_user_token');
    
    // Second call should use cached token
    $questions2 = $service->getQuestions([
        'amount' => 1,
        'user_id' => $user->id
    ]);
    
    // Should only make one token request
    $tokenRequests = 0;
    Http::assertSent(function ($request) use (&$tokenRequests) {
        if (str_contains($request->url(), 'api_token.php')) {
            $tokenRequests++;
        }
        return true;
    });
    
    expect($tokenRequests)->toBe(1);
});

test('guest session tokens use session-based cache keys', function () {
    // Start a session to simulate guest user
    session()->start();
    $sessionId = session()->getId();
    
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([
            'response_code' => 0,
            'token' => 'cached_guest_token'
        ]),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'Guest question?',
                    'correct_answer' => 'Answer',
                    'incorrect_answers' => ['Wrong1', 'Wrong2', 'Wrong3'],
                    'difficulty' => 'easy',
                    'category' => 'Test'
                ]
            ]
        ])
    ]);

    $service = app(OpenTriviaService::class);
    
    // Call with null user_id (guest)
    $questions = $service->getQuestions([
        'amount' => 1,
        'user_id' => null
    ]);
    
    // Verify token is cached with session-based key
    $cachedToken = Cache::get("trivia_token_guest_{$sessionId}");
    expect($cachedToken)->toBe('cached_guest_token');
});

test('token cache respects TTL settings', function () {
    $user = User::factory()->create();
    
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([
            'response_code' => 0,
            'token' => 'ttl_test_token'
        ]),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'TTL test question?',
                    'correct_answer' => 'Answer',
                    'incorrect_answers' => ['Wrong1', 'Wrong2', 'Wrong3'],
                    'difficulty' => 'easy',
                    'category' => 'Test'
                ]
            ]
        ])
    ]);

    $service = app(OpenTriviaService::class);
    
    // Make request to cache token
    $service->getQuestions([
        'amount' => 1,
        'user_id' => $user->id
    ]);
    
    // Verify token is cached
    $cacheKey = "trivia_token_user_{$user->id}";
    expect(Cache::has($cacheKey))->toBeTrue();
    
    // Manually expire the cache entry
    Cache::forget($cacheKey);
    expect(Cache::has($cacheKey))->toBeFalse();
    
    // Next request should fetch new token
    $service->getQuestions([
        'amount' => 1,
        'user_id' => $user->id
    ]);
    
    // Should have made 2 token requests
    $tokenRequests = 0;
    Http::assertSent(function ($request) use (&$tokenRequests) {
        if (str_contains($request->url(), 'api_token.php')) {
            $tokenRequests++;
        }
        return true;
    });
    
    expect($tokenRequests)->toBe(2);
});
<?php

use App\GameStatus;
use App\Models\Game;
use App\Models\User;
use App\Services\OpenTriviaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Cache::flush();
});

test('game creation uses session tokens when available', function () {
    // Mock token request and questions API
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([
            'response_code' => 0,
            'response_message' => 'Token Generated Successfully!',
            'token' => 'test_token_123'
        ]),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'What is 2 + 2?',
                    'correct_answer' => '4',
                    'incorrect_answers' => ['3', '5', '6'],
                    'difficulty' => 'easy',
                    'category' => 'Mathematics'
                ]
            ]
        ])
    ]);

    $response = $this->actingAs($this->user)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'easy',
        'category_id' => 9
    ]);

    $response->assertRedirect();
    
    // Verify token was requested
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api_token.php') && 
               str_contains($request->url(), 'command=request');
    });
    
    // Verify questions were requested with token
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.php') && 
               str_contains($request->url(), 'token=test_token_123');
    });
    
    // Verify game was created
    $game = Game::where('user_id', $this->user->id)->first();
    expect($game)->not->toBeNull()
        ->and($game->questions)->toHaveCount(1);
});

test('token is reused across multiple games for same user', function () {
    // Mock token request once and questions API multiple times
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([
            'response_code' => 0,
            'response_message' => 'Token Generated Successfully!',
            'token' => 'reusable_token_456'
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

    // Create first game
    $this->actingAs($this->user)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'easy',
        'category_id' => 9
    ]);

    // Create second game
    $this->actingAs($this->user)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'medium',
        'category_id' => 10
    ]);

    // Token should only be requested once
    Http::assertSentCount(3); // 1 token request + 2 question requests
    
    // Both question requests should use the same token
    $tokenRequests = 0;
    Http::assertSent(function ($request) use (&$tokenRequests) {
        if (str_contains($request->url(), 'api_token.php')) {
            $tokenRequests++;
        }
        return true;
    });
    
    expect($tokenRequests)->toBe(1);
    
    // Verify both games were created
    $games = Game::where('user_id', $this->user->id)->get();
    expect($games)->toHaveCount(2);
});

test('different users get different session tokens', function () {
    $user2 = User::factory()->create();
    
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::sequence()
            ->push([
                'response_code' => 0,
                'response_message' => 'Token Generated Successfully!',
                'token' => 'user1_token'
            ])
            ->push([
                'response_code' => 0,
                'response_message' => 'Token Generated Successfully!',
                'token' => 'user2_token'
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

    // User 1 creates game
    $this->actingAs($this->user)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'easy',
        'category_id' => 9
    ]);

    // User 2 creates game
    $this->actingAs($user2)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'easy',
        'category_id' => 9
    ]);

    // Should have requested 2 tokens and made 2 question requests
    Http::assertSentCount(4);
    
    // Verify different tokens were used
    $user1TokenUsed = false;
    $user2TokenUsed = false;
    
    Http::assertSent(function ($request) use (&$user1TokenUsed, &$user2TokenUsed) {
        if (str_contains($request->url(), 'api.php')) {
            if (str_contains($request->url(), 'token=user1_token')) {
                $user1TokenUsed = true;
            }
            if (str_contains($request->url(), 'token=user2_token')) {
                $user2TokenUsed = true;
            }
        }
        return true;
    });
    
    expect($user1TokenUsed)->toBeTrue()
        ->and($user2TokenUsed)->toBeTrue();
});

test('game flow continues without tokens when token API fails', function () {
    // Mock token API failure but successful questions API
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([], 500),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'Fallback question?',
                    'correct_answer' => 'Yes',
                    'incorrect_answers' => ['No', 'Maybe', 'Unknown'],
                    'difficulty' => 'easy',
                    'category' => 'Test'
                ]
            ]
        ])
    ]);

    $response = $this->actingAs($this->user)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'easy',
        'category_id' => 9
    ]);

    $response->assertRedirect();
    
    // Verify questions were still fetched (without token)
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.php') && 
               !str_contains($request->url(), 'token=');
    });
    
    // Verify game was created successfully
    $game = Game::where('user_id', $this->user->id)->first();
    expect($game)->not->toBeNull()
        ->and($game->questions)->toHaveCount(1);
});

test('token exhaustion is handled gracefully', function () {
    // Mock token exhaustion scenario
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([
            'response_code' => 0,
            'response_message' => 'Token Generated Successfully!',
            'token' => 'exhausted_token'
        ]),
        'https://opentdb.com/api.php*' => Http::sequence()
            ->push([
                'response_code' => 4, // Token exhausted
                'results' => []
            ])
            ->push([
                'response_code' => 0,
                'results' => [
                    [
                        'question' => 'After exhaustion question?',
                        'correct_answer' => 'Yes',
                        'incorrect_answers' => ['No', 'Maybe', 'Unknown'],
                        'difficulty' => 'easy',
                        'category' => 'Test'
                    ]
                ]
            ])
    ]);

    $response = $this->actingAs($this->user)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'easy',
        'category_id' => 9
    ]);

    $response->assertRedirect();
    
    // Should make 2 requests to questions API (first with token, second without)
    $questionRequests = 0;
    Http::assertSent(function ($request) use (&$questionRequests) {
        if (str_contains($request->url(), 'api.php')) {
            $questionRequests++;
        }
        return true;
    });
    
    expect($questionRequests)->toBe(2);
    
    // Verify game was created successfully
    $game = Game::where('user_id', $this->user->id)->first();
    expect($game)->not->toBeNull()
        ->and($game->questions)->toHaveCount(1);
});

test('existing game functionality remains unchanged', function () {
    // Mock successful API responses
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([
            'response_code' => 0,
            'token' => 'test_token'
        ]),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'What is 2 + 2?',
                    'correct_answer' => '4',
                    'incorrect_answers' => ['3', '5', '6'],
                    'difficulty' => 'easy',
                    'category' => 'Mathematics'
                ]
            ]
        ])
    ]);

    // Create game
    $response = $this->actingAs($this->user)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'easy',
        'category_id' => 9
    ]);

    $game = Game::where('user_id', $this->user->id)->first();
    
    // Test game show page
    $showResponse = $this->actingAs($this->user)->get(route('game.show', $game));
    $showResponse->assertOk();
    
    // Test answer submission
    $answerResponse = $this->actingAs($this->user)->post(route('game.answer', $game), [
        'question_index' => 0,
        'selected_answer' => '4'
    ]);
    
    $answerResponse->assertRedirect(route('game.results', $game));
    
    // Test results page
    $resultsResponse = $this->actingAs($this->user)->get(route('game.results', $game));
    $resultsResponse->assertOk();
    
    // Verify game completion
    $game->refresh();
    expect($game->status)->toBe(GameStatus::Completed)
        ->and($game->score)->toBeGreaterThan(0);
});

test('guest users can still play games with session-based tokens', function () {
    // Mock token and questions API
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([
            'response_code' => 0,
            'token' => 'guest_token'
        ]),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'Guest question?',
                    'correct_answer' => 'Yes',
                    'incorrect_answers' => ['No', 'Maybe', 'Unknown'],
                    'difficulty' => 'easy',
                    'category' => 'Test'
                ]
            ]
        ])
    ]);

    // Test that OpenTriviaService can handle null user_id (guest scenario)
    $service = app(OpenTriviaService::class);
    $questions = $service->getQuestions([
        'amount' => 1,
        'difficulty' => 'easy',
        'user_id' => null // Simulate guest user
    ]);

    expect($questions)->toBeArray()
        ->and($questions)->toHaveCount(1);
    
    // Verify token was requested and used
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api_token.php');
    });
    
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.php') && 
               str_contains($request->url(), 'token=guest_token');
    });
});
<?php

use App\Models\User;
use App\Models\GameRoom;
use App\Services\MultiplayerGameService;
use App\Services\OpenTriviaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('session tokens work across single player and multiplayer games', function () {
    $user = User::factory()->create();
    
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([
            'response_code' => 0,
            'token' => 'shared_user_token'
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

    // Create single player game first
    $singlePlayerResponse = $this->actingAs($user)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'easy',
        'category_id' => 9
    ]);
    
    $singlePlayerResponse->assertRedirect();
    
    // Create multiplayer room and game
    $room = GameRoom::factory()->create([
        'host_user_id' => $user->id,
        'room_code' => 'TEST123',
        'max_players' => 4,
        'current_players' => 1,
    ]);
    
    $room->participants()->create([
        'user_id' => $user->id,
        'joined_at' => now(),
        'score' => 0,
    ]);
    
    $multiplayerService = app(MultiplayerGameService::class);
    $multiplayerGame = $multiplayerService->startGame($room);
    
    // Should only request token once since same user
    $tokenRequests = 0;
    Http::assertSent(function ($request) use (&$tokenRequests) {
        if (str_contains($request->url(), 'api_token.php')) {
            $tokenRequests++;
        }
        return true;
    });
    
    expect($tokenRequests)->toBe(1);
    
    // Both games should use the same token
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.php') && 
               str_contains($request->url(), 'token=shared_user_token');
    });
});

test('trivia controller API endpoints use session tokens', function () {
    $user = User::factory()->create();
    
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::response([
            'response_code' => 0,
            'token' => 'api_endpoint_token'
        ]),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'API question?',
                    'correct_answer' => 'Yes',
                    'incorrect_answers' => ['No', 'Maybe', 'Unknown'],
                    'difficulty' => 'easy',
                    'category' => 'Test'
                ]
            ]
        ])
    ]);

    // Make API request through TriviaController
    $response = $this->actingAs($user)->postJson('/api/trivia/questions', [
        'amount' => 1,
        'difficulty' => 'easy',
        'category' => 9
    ]);
    
    $response->assertOk()
        ->assertJson([
            'success' => true
        ]);
    
    // Verify token was requested and used
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api_token.php');
    });
    
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.php') && 
               str_contains($request->url(), 'token=api_endpoint_token');
    });
});

test('session tokens are isolated between different users in multiplayer', function () {
    $host = User::factory()->create();
    $participant = User::factory()->create();
    
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::sequence()
            ->push([
                'response_code' => 0,
                'token' => 'host_token'
            ])
            ->push([
                'response_code' => 0,
                'token' => 'participant_token'
            ]),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'Multiplayer question?',
                    'correct_answer' => 'Answer',
                    'incorrect_answers' => ['Wrong1', 'Wrong2', 'Wrong3'],
                    'difficulty' => 'easy',
                    'category' => 'Test'
                ]
            ]
        ])
    ]);

    // Host creates room and game (uses host's token)
    $room = GameRoom::factory()->create([
        'host_user_id' => $host->id,
        'room_code' => 'MULTI123',
        'max_players' => 4,
        'current_players' => 2,
    ]);
    
    $room->participants()->create([
        'user_id' => $host->id,
        'joined_at' => now(),
        'score' => 0,
    ]);
    
    $room->participants()->create([
        'user_id' => $participant->id,
        'joined_at' => now(),
        'score' => 0,
    ]);
    
    $multiplayerService = app(MultiplayerGameService::class);
    $multiplayerGame = $multiplayerService->startGame($room);
    
    // Participant creates separate single player game
    $this->actingAs($participant)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'easy',
        'category_id' => 9
    ]);
    
    // Should have requested 2 tokens
    $tokenRequests = 0;
    Http::assertSent(function ($request) use (&$tokenRequests) {
        if (str_contains($request->url(), 'api_token.php')) {
            $tokenRequests++;
        }
        return true;
    });
    
    expect($tokenRequests)->toBe(2);
    
    // Verify both tokens were used
    $hostTokenUsed = false;
    $participantTokenUsed = false;
    
    Http::assertSent(function ($request) use (&$hostTokenUsed, &$participantTokenUsed) {
        if (str_contains($request->url(), 'api.php')) {
            if (str_contains($request->url(), 'token=host_token')) {
                $hostTokenUsed = true;
            }
            if (str_contains($request->url(), 'token=participant_token')) {
                $participantTokenUsed = true;
            }
        }
        return true;
    });
    
    expect($hostTokenUsed)->toBeTrue()
        ->and($participantTokenUsed)->toBeTrue();
});

test('system gracefully handles token failures across all components', function () {
    $user = User::factory()->create();
    
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

    // Test single player game
    $singlePlayerResponse = $this->actingAs($user)->post(route('game.store'), [
        'total_questions' => 1,
        'difficulty' => 'easy',
        'category_id' => 9
    ]);
    
    $singlePlayerResponse->assertRedirect();
    
    // Test API endpoint
    $apiResponse = $this->actingAs($user)->postJson('/api/trivia/questions', [
        'amount' => 1,
        'difficulty' => 'easy'
    ]);
    
    $apiResponse->assertOk();
    
    // Test multiplayer game
    $room = GameRoom::factory()->create([
        'host_user_id' => $user->id,
        'room_code' => 'FALLBACK',
        'max_players' => 4,
        'current_players' => 1,
    ]);
    
    $room->participants()->create([
        'user_id' => $user->id,
        'joined_at' => now(),
        'score' => 0,
    ]);
    
    $multiplayerService = app(MultiplayerGameService::class);
    $multiplayerGame = $multiplayerService->startGame($room);
    
    // All should work without tokens
    expect($multiplayerGame)->not->toBeNull();
    
    // Verify all requests were made without tokens
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.php') && 
               !str_contains($request->url(), 'token=');
    });
});

test('token cache keys are properly isolated between components', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    Http::fake([
        'https://opentdb.com/api_token.php*' => Http::sequence()
            ->push(['response_code' => 0, 'token' => 'user1_token'])
            ->push(['response_code' => 0, 'token' => 'user2_token']),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'Cache test question?',
                    'correct_answer' => 'Answer',
                    'incorrect_answers' => ['Wrong1', 'Wrong2', 'Wrong3'],
                    'difficulty' => 'easy',
                    'category' => 'Test'
                ]
            ]
        ])
    ]);

    $service = app(OpenTriviaService::class);
    
    // User 1 gets questions
    $questions1 = $service->getQuestions([
        'amount' => 1,
        'user_id' => $user1->id
    ]);
    
    // User 2 gets questions
    $questions2 = $service->getQuestions([
        'amount' => 1,
        'user_id' => $user2->id
    ]);
    
    // Verify separate cache entries
    $user1Token = Cache::get("trivia_token_user_{$user1->id}");
    $user2Token = Cache::get("trivia_token_user_{$user2->id}");
    
    expect($user1Token)->toBe('user1_token')
        ->and($user2Token)->toBe('user2_token')
        ->and($user1Token)->not->toBe($user2Token);
});
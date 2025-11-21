<?php

use App\Models\User;
use App\Models\GameRoom;
use App\Models\RoomParticipant;
use App\Models\RoomSettings;
use App\Services\MultiplayerGameService;
use App\DifficultyLevel;
use App\RoomStatus;
use App\ParticipantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('leaderboard handles ties correctly', function () {
    // Create users
    $user1 = User::factory()->create(['name' => 'Alice']);
    $user2 = User::factory()->create(['name' => 'Bob']);
    $user3 = User::factory()->create(['name' => 'Charlie']);
    $user4 = User::factory()->create(['name' => 'David']);

    // Create room
    $room = GameRoom::create([
        'host_user_id' => $user1->id,
        'room_code' => 'TEST123',
        'max_players' => 4,
        'current_players' => 4,
        'status' => RoomStatus::WAITING,
    ]);

    // Create room settings
    RoomSettings::create([
        'room_id' => $room->id,
        'total_questions' => 5,
        'difficulty' => DifficultyLevel::Easy,
        'time_per_question' => 30,
        'category_id' => null,
    ]);

    // Create participants with different scores
    // Alice: 30 points (1st place)
    // Bob: 20 points (tied for 2nd)
    // Charlie: 20 points (tied for 2nd)
    // David: 10 points (4th place)
    
    RoomParticipant::create([
        'room_id' => $room->id,
        'user_id' => $user1->id,
        'score' => 30,
        'status' => ParticipantStatus::PLAYING,
        'joined_at' => now()->subMinutes(4),
    ]);
    
    RoomParticipant::create([
        'room_id' => $room->id,
        'user_id' => $user2->id,
        'score' => 20,
        'status' => ParticipantStatus::PLAYING,
        'joined_at' => now()->subMinutes(3),
    ]);
    
    RoomParticipant::create([
        'room_id' => $room->id,
        'user_id' => $user3->id,
        'score' => 20,
        'status' => ParticipantStatus::PLAYING,
        'joined_at' => now()->subMinutes(2),
    ]);
    
    RoomParticipant::create([
        'room_id' => $room->id,
        'user_id' => $user4->id,
        'score' => 10,
        'status' => ParticipantStatus::PLAYING,
        'joined_at' => now()->subMinutes(1),
    ]);

    // Generate leaderboard using the service
    $service = app(MultiplayerGameService::class);
    $leaderboard = $service->generateLeaderboard($room);

    // Verify positions
    expect($leaderboard)->toHaveCount(4);
    
    // Alice should be 1st with 30 points
    expect($leaderboard[0]['position'])->toBe(1);
    expect($leaderboard[0]['user']['name'])->toBe('Alice');
    expect($leaderboard[0]['score'])->toBe(30);
    
    // Bob should be tied 2nd with 20 points
    expect($leaderboard[1]['position'])->toBe(2);
    expect($leaderboard[1]['user']['name'])->toBe('Bob');
    expect($leaderboard[1]['score'])->toBe(20);
    
    // Charlie should also be tied 2nd with 20 points (same position as Bob)
    expect($leaderboard[2]['position'])->toBe(2);
    expect($leaderboard[2]['user']['name'])->toBe('Charlie');
    expect($leaderboard[2]['score'])->toBe(20);
    
    // David should be 4th with 10 points (position jumps from 2 to 4, skipping 3)
    expect($leaderboard[3]['position'])->toBe(4);
    expect($leaderboard[3]['user']['name'])->toBe('David');
    expect($leaderboard[3]['score'])->toBe(10);
});
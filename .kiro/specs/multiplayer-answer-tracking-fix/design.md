# Design Document

## Overview

This design implements a simplified multiplayer game progression system that eliminates background job complexity and race conditions. The core approach uses host-controlled progression with automatic fallback, synchronous score calculation, and simple boolean flags for state tracking. All question transitions are triggered by explicit controller actions rather than scheduled jobs.

## Architecture

### High-Level Flow

```
Question Active → Players Answer → All Answered OR Timer Expires
                                            ↓
                                    Ready State Reached
                                            ↓
                        Host Sees "Next Question" Button
                                            ↓
                    Host Clicks (or 5-second auto-click)
                                            ↓
                        POST /multiplayer/game/{room}/next
                                            ↓
                    Calculate Scores Synchronously
                                            ↓
                        Update Game State to Show Results
                                            ↓
                        Frontend Polls → Sees Results
                                            ↓
                        Auto-redirect to Next Question
```

### Key Design Principles

1. **No Background Jobs for Progression** - All question transitions happen via synchronous HTTP requests
2. **Simple State Flags** - Use boolean flags (`all_players_answered`, `current_user_has_answered`) instead of complex counting
3. **Host-Controlled Pacing** - Host has explicit control with automatic fallback for safety
4. **Polling-Based Updates** - Frontend polls every 1 second to get updated state
5. **Synchronous Score Calculation** - Calculate scores immediately when advancing, no queued jobs

## Components and Interfaces

### Backend Components

#### 1. MultiplayerGameController (Modified)

**New/Modified Methods:**

```php
/**
 * Advance to next question (host-triggered)
 * POST /multiplayer/game/{roomCode}/next
 */
public function nextQuestion(string $roomCode): RedirectResponse
{
    // Verify user is host
    // Check game is in ready state (timer expired or all answered)
    // Calculate scores synchronously
    // Move to next question or complete game
    // Return redirect to appropriate page
}

/**
 * Show game state with enhanced flags
 * GET /multiplayer/game/{roomCode}
 */
public function show(string $roomCode): Response|RedirectResponse
{
    // Existing logic PLUS:
    // - Calculate all_players_answered flag
    // - Calculate current_user_has_answered flag
    // - Calculate is_ready_for_next flag
    // - Include ready_since timestamp for auto-advance
}
```

**Data Structure Returned to Frontend:**

```php
[
    'gameState' => [
        'room' => [...],
        'game_status' => 'active',
        'current_question' => [...],
        'current_question_index' => 0,
        'time_remaining' => 25,
        
        // New flags
        'all_players_answered' => false,
        'current_user_has_answered' => true,
        'is_ready_for_next' => false,
        'ready_since' => null, // timestamp when ready state was reached
        
        'participants' => [...],
        'round_results' => null,
    ]
]
```

#### 2. MultiplayerGameService (Modified)

**New/Modified Methods:**

```php
/**
 * Check if all active participants have answered
 */
public function allParticipantsAnswered(MultiplayerGame $game): bool
{
    $activeCount = $game->room->participants()
        ->where('status', ParticipantStatus::PLAYING)
        ->count();
    
    $answerCount = ParticipantAnswer::where('multiplayer_game_id', $game->id)
        ->where('question_id', $game->current_question_index)
        ->count();
    
    return $activeCount > 0 && $answerCount >= $activeCount;
}

/**
 * Check if current user has answered
 */
public function currentUserHasAnswered(MultiplayerGame $game, int $userId): bool
{
    $participant = $game->room->participants()
        ->where('user_id', $userId)
        ->first();
    
    if (!$participant) return false;
    
    return ParticipantAnswer::where('multiplayer_game_id', $game->id)
        ->where('participant_id', $participant->id)
        ->where('question_id', $game->current_question_index)
        ->exists();
}

/**
 * Calculate scores for current question (synchronous)
 */
public function calculateRoundScores(MultiplayerGame $game): void
{
    $currentQuestionIndex = $game->current_question_index;
    $difficulty = $game->room->settings->difficulty->value;
    
    $pointsForCorrect = match($difficulty) {
        'easy' => 10,
        'medium' => 20,
        'hard' => 30,
        default => 10,
    };
    
    $answers = ParticipantAnswer::where('multiplayer_game_id', $game->id)
        ->where('question_id', $currentQuestionIndex)
        ->get();
    
    foreach ($answers as $answer) {
        if ($answer->is_correct) {
            $answer->participant->addScore($pointsForCorrect);
        }
    }
}

/**
 * Advance to next question or complete game (synchronous)
 */
public function advanceToNextQuestion(MultiplayerGame $game): void
{
    // Calculate scores for current question
    $this->calculateRoundScores($game);
    
    // Check if there are more questions
    $nextIndex = $game->current_question_index + 1;
    $totalQuestions = $game->game->total_questions;
    
    if ($nextIndex >= $totalQuestions) {
        // Complete the game
        $game->update([
            'status' => MultiplayerGameStatus::COMPLETED,
        ]);
        
        $game->room->update([
            'status' => RoomStatus::COMPLETED,
        ]);
        
        return;
    }
    
    // Move to next question
    $game->update([
        'current_question_index' => $nextIndex,
        'question_started_at' => now(),
    ]);
}
```

**Methods to Remove:**

```php
// ❌ Remove these - no longer needed
public function triggerEarlyProgression(MultiplayerGame $game): void
public function isWithinTimeLimit(MultiplayerGame $game): bool // Keep but simplify
```

#### 3. MultiplayerGame Model (Modified)

**New Helper Methods:**

```php
/**
 * Check if ready for next question
 */
public function isReadyForNext(): bool
{
    // Ready if timer expired OR all players answered
    $timeExpired = $this->calculateTimeRemaining() <= 0;
    $allAnswered = app(MultiplayerGameService::class)
        ->allParticipantsAnswered($this);
    
    return $timeExpired || $allAnswered;
}

/**
 * Calculate time remaining for current question
 */
public function calculateTimeRemaining(): int
{
    if (!$this->question_started_at) {
        return 0;
    }
    
    $timePerQuestion = $this->room->settings->time_per_question ?? 30;
    $elapsed = $this->question_started_at->diffInSeconds(now());
    
    return max(0, $timePerQuestion - $elapsed);
}
```

#### 4. Jobs to Remove

```php
// ❌ Delete these files entirely
app/Jobs/NextQuestionJob.php
app/Jobs/StartNextQuestionJob.php

// ❌ Remove CalculateRoundScoresJob if it exists
app/Jobs/CalculateRoundScoresJob.php
```

### Frontend Components

#### 1. MultiplayerQuestion Component (Modified)

**New Props:**

```typescript
interface MultiplayerQuestionProps {
    roomCode: string;
    question: Question;
    questionNumber: number;
    totalQuestions: number;
    timeRemaining: number;
    
    // New props
    allPlayersAnswered: boolean;
    currentUserHasAnswered: boolean;
    isReadyForNext: boolean;
    readySince: string | null;
    isHost: boolean;
}
```

**New UI Elements:**

```typescript
// Show status message based on state
{currentUserHasAnswered && !isReadyForNext && (
    <div className="text-green-600">
        ✓ Answer submitted! Waiting for others...
    </div>
)}

{allPlayersAnswered && !isHost && (
    <div className="text-blue-600">
        All players answered! Waiting for host...
    </div>
)}

{timeRemaining === 0 && !isHost && (
    <div className="text-orange-600">
        Time's up! Waiting for host...
    </div>
)}

{isReadyForNext && isHost && (
    <NextQuestionButton 
        roomCode={roomCode}
        readySince={readySince}
        allPlayersAnswered={allPlayersAnswered}
        timeExpired={timeRemaining === 0}
    />
)}
```

#### 2. NextQuestionButton Component (New)

**Purpose:** Display "Next Question" button to host with auto-advance

```typescript
interface NextQuestionButtonProps {
    roomCode: string;
    readySince: string | null;
    allPlayersAnswered: boolean;
    timeExpired: boolean;
}

export function NextQuestionButton({
    roomCode,
    readySince,
    allPlayersAnswered,
    timeExpired
}: NextQuestionButtonProps) {
    const [countdown, setCountdown] = useState(5);
    const [isSubmitting, setIsSubmitting] = useState(false);
    
    // Auto-advance after 5 seconds
    useEffect(() => {
        if (!readySince) return;
        
        const readyTime = new Date(readySince).getTime();
        const now = Date.now();
        const elapsed = Math.floor((now - readyTime) / 1000);
        const remaining = Math.max(0, 5 - elapsed);
        
        setCountdown(remaining);
        
        if (remaining === 0) {
            handleNextQuestion();
            return;
        }
        
        const timer = setInterval(() => {
            setCountdown(prev => {
                if (prev <= 1) {
                    clearInterval(timer);
                    handleNextQuestion();
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);
        
        return () => clearInterval(timer);
    }, [readySince]);
    
    const handleNextQuestion = () => {
        if (isSubmitting) return;
        setIsSubmitting(true);
        
        router.post(
            multiplayer.game.next(roomCode).url,
            {},
            {
                onFinish: () => setIsSubmitting(false)
            }
        );
    };
    
    return (
        <div className="space-y-2">
            <Button
                onClick={handleNextQuestion}
                disabled={isSubmitting}
                size="lg"
                className="w-full"
            >
                {isSubmitting ? 'Loading...' : 'Next Question'}
            </Button>
            
            <p className="text-sm text-center text-muted-foreground">
                {allPlayersAnswered 
                    ? `All players answered! Auto-advancing in ${countdown}s...`
                    : `Time's up! Auto-advancing in ${countdown}s...`
                }
            </p>
        </div>
    );
}
```

#### 3. Game Page (Modified)

**Updated Polling Logic:**

```typescript
// Poll every 1 second during active gameplay
const { connectionStatus, lastUpdate } = useGamePolling(
    1000,
    gamePhase === 'active' // Only poll during active questions
);
```

**Pass New Props to Components:**

```typescript
<MultiplayerQuestion
    roomCode={room.room_code}
    question={current_question}
    questionNumber={current_question_index + 1}
    totalQuestions={room.settings.total_questions}
    timeRemaining={time_remaining}
    allPlayersAnswered={gameState.all_players_answered}
    currentUserHasAnswered={gameState.current_user_has_answered}
    isReadyForNext={gameState.is_ready_for_next}
    readySince={gameState.ready_since}
    isHost={isHost}
/>
```

## Data Models

### Database Schema Changes

**No schema changes required!** All existing tables support this design.

### State Transitions

```
ACTIVE (question_started_at set, timer running)
    ↓
    ├─→ All players answer → isReadyForNext = true
    └─→ Timer expires → isReadyForNext = true
    ↓
READY_FOR_NEXT (waiting for host action)
    ↓
Host clicks "Next Question" (or 5-second auto-advance)
    ↓
POST /multiplayer/game/{room}/next
    ↓
    ├─→ More questions → ACTIVE (next question)
    └─→ No more questions → COMPLETED
```

## Error Handling

### Backend Validation

```php
// In nextQuestion() controller method
public function nextQuestion(string $roomCode): RedirectResponse
{
    $room = GameRoom::where('room_code', $roomCode)->firstOrFail();
    $game = $room->multiplayerGame;
    
    // Verify user is host
    if ($room->host_user_id !== auth()->id()) {
        return back()->withErrors(['error' => 'Only the host can advance questions.']);
    }
    
    // Verify game is active
    if ($game->status !== MultiplayerGameStatus::ACTIVE) {
        return back()->withErrors(['error' => 'Game is not active.']);
    }
    
    // Verify ready state (timer expired OR all answered)
    if (!$game->isReadyForNext()) {
        return back()->withErrors(['error' => 'Not ready to advance yet.']);
    }
    
    // Proceed with advancement
    DB::transaction(function () use ($game) {
        app(MultiplayerGameService::class)->advanceToNextQuestion($game);
    });
    
    // Redirect to results or next question
    return redirect()->route('multiplayer.game.results', $roomCode);
}
```

### Frontend Error Handling

```typescript
// Handle failed next question request
router.post(
    multiplayer.game.next(roomCode).url,
    {},
    {
        onError: (errors) => {
            toast.error(errors.error || 'Failed to advance question');
        },
        onFinish: () => setIsSubmitting(false)
    }
);
```

### Edge Cases

1. **Host Disconnects During Ready State**
   - Auto-advance timer continues running
   - After 5 seconds, any player's poll will see the game hasn't advanced
   - Could add fallback: if ready_since > 10 seconds ago, allow any player to advance

2. **Multiple Players Click Next Simultaneously**
   - Backend validates host-only access
   - Non-hosts get error message
   - No race condition because it's a simple authorization check

3. **Network Issues During Advancement**
   - Frontend shows loading state
   - If request fails, user can click again
   - Backend is idempotent (checking ready state prevents issues)

## Testing Strategy

### Unit Tests

```php
// Test MultiplayerGameService methods
test('allParticipantsAnswered returns true when all answered')
test('allParticipantsAnswered returns false when some pending')
test('calculateRoundScores awards correct points')
test('advanceToNextQuestion moves to next question')
test('advanceToNextQuestion completes game on last question')
```

### Feature Tests

```php
// Test controller endpoints
test('host can advance when ready')
test('non-host cannot advance')
test('cannot advance before ready state')
test('auto-advance works after 5 seconds')
test('game completes after last question')
```

### Frontend Tests

```typescript
// Test NextQuestionButton component
test('shows countdown timer')
test('auto-advances after 5 seconds')
test('manual click works before auto-advance')
test('shows correct message for all-answered vs time-expired')
```

## Performance Considerations

### Synchronous Score Calculation

- Calculating scores for 10 players takes ~10-50ms
- Acceptable for synchronous execution
- No need for background jobs
- Simpler debugging and error handling

### Polling Frequency

- 1-second polling during active questions
- ~60 requests per minute per player
- For 10 players: 600 requests/minute
- Easily handled by Laravel with proper caching

### Database Queries

```php
// Optimize with eager loading
$game = MultiplayerGame::with([
    'room.participants.user',
    'room.settings',
    'game'
])->find($id);

// Use query optimization for answer counting
$answerCount = ParticipantAnswer::where('multiplayer_game_id', $gameId)
    ->where('question_id', $questionIndex)
    ->count(); // Uses index, very fast
```

## Migration Path

### Code to Remove

1. Delete `app/Jobs/NextQuestionJob.php`
2. Delete `app/Jobs/StartNextQuestionJob.php`
3. Delete `app/Jobs/CalculateRoundScoresJob.php` (if exists)
4. Remove job dispatching from `MultiplayerGameService`
5. Remove `triggerEarlyProgression()` method

### Code to Modify

1. Update `MultiplayerGameController::show()` to include new flags
2. Add `MultiplayerGameController::nextQuestion()` method
3. Update `MultiplayerGameService` with new synchronous methods
4. Update `MultiplayerQuestion` component with new UI
5. Create `NextQuestionButton` component
6. Update routes to include new endpoint

### Code to Keep

- All existing database tables and models
- Existing answer submission logic
- Existing polling infrastructure
- Existing results display logic

## Summary

This design eliminates background job complexity by using host-controlled progression with automatic fallback. All state transitions happen via synchronous HTTP requests, making the system predictable and easy to debug. The 5-second auto-advance provides safety against inactive hosts while maintaining simplicity.

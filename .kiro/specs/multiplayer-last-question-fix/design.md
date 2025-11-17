# Design Document

## Overview

This design addresses three critical bugs in the multiplayer trivia game flow by implementing robust question progression logic, early completion detection, and job reliability improvements. The solution focuses on:

1. **Last Question Completion**: Ensuring the game properly transitions to final results when the last question timer expires
2. **Early Progression**: Detecting when all players have answered and immediately progressing without waiting for the timer
3. **Job Reliability**: Preventing duplicate job executions and handling edge cases that cause progression failures

## Architecture

### High-Level Flow

```
Question Active → All Answered OR Timer Expires → Calculate Scores → Show Results → Next Question OR Complete Game
```

### Key Components

1. **NextQuestionJob** - Handles automatic progression and game completion
2. **MultiplayerGameService** - Business logic for answer detection and game state
3. **MultiplayerGameController** - Answer submission and early progression trigger
4. **MultiplayerGame Model** - State management and helper methods

## Components and Interfaces

### 1. NextQuestionJob Enhancements

**Current Issues:**
- Uses `sleep(2)` which blocks the queue worker
- Doesn't handle last question completion properly
- No protection against duplicate executions
- Recursive job scheduling can cause race conditions

**Design Changes:**

```php
class NextQuestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $multiplayerGameId;
    public ?string $executionToken; // Unique token to prevent duplicates

    public function __construct(int $multiplayerGameId, ?string $executionToken = null)
    {
        $this->multiplayerGameId = $multiplayerGameId;
        $this->executionToken = $executionToken ?? Str::uuid()->toString();
    }

    public function handle(): void
    {
        // 1. Load game with relationships
        // 2. Validate game exists and is active
        // 3. Check if this execution has been superseded
        // 4. Calculate scores for current question
        // 5. Transition to SHOWING_RESULTS status
        // 6. Determine if more questions exist
        // 7. If last question: complete game
        // 8. If more questions: schedule next question with delay
    }

    private function completeGame(MultiplayerGame $multiplayerGame): void
    {
        // Update game status to COMPLETED
        // Update room status to COMPLETED
        // Update participants to FINISHED
        // Schedule cleanup job
    }

    private function scheduleNextQuestion(MultiplayerGame $multiplayerGame, int $nextIndex): void
    {
        // Schedule a separate job to start the next question
        // This avoids blocking with sleep()
    }
}
```

**New Job: StartNextQuestionJob**

```php
class StartNextQuestionJob implements ShouldQueue
{
    public int $multiplayerGameId;
    public int $questionIndex;
    public string $executionToken;

    public function handle(): void
    {
        // 1. Load game
        // 2. Verify still in SHOWING_RESULTS status
        // 3. Check execution token matches expected
        // 4. Update to ACTIVE status
        // 5. Set current_question_index
        // 6. Set question_started_at
        // 7. Schedule NextQuestionJob for timer expiration
    }
}
```

### 2. MultiplayerGameService - Early Progression

**New Method: `allParticipantsAnswered`**

```php
public function allParticipantsAnswered(MultiplayerGame $multiplayerGame): bool
{
    $room = $multiplayerGame->room;
    $currentQuestionIndex = $multiplayerGame->current_question_index;

    // Count active participants (PLAYING status)
    $activeParticipantCount = $room->participants()
        ->where('status', ParticipantStatus::PLAYING)
        ->count();

    // Count answers for current question
    $answerCount = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
        ->where('question_id', $currentQuestionIndex)
        ->count();

    return $activeParticipantCount > 0 && $answerCount >= $activeParticipantCount;
}
```

**New Method: `triggerEarlyProgression`**

```php
public function triggerEarlyProgression(MultiplayerGame $multiplayerGame): void
{
    // Store the current execution token to invalidate pending jobs
    $newToken = Str::uuid()->toString();
    
    Cache::put(
        "multiplayer_game_{$multiplayerGame->id}_execution_token",
        $newToken,
        now()->addMinutes(10)
    );

    // Dispatch NextQuestionJob immediately
    NextQuestionJob::dispatch($multiplayerGame->id, $newToken);
}
```

### 3. MultiplayerGameController - Answer Submission

**Enhanced `answer` Method:**

```php
public function answer(string $roomCode, Request $request): RedirectResponse
{
    // ... existing validation ...

    DB::transaction(function () use ($multiplayerGame, $participant, $validated) {
        // Create participant answer
        ParticipantAnswer::create([...]);

        // Check if all participants have answered
        if (app(MultiplayerGameService::class)->allParticipantsAnswered($multiplayerGame)) {
            // Trigger early progression
            app(MultiplayerGameService::class)->triggerEarlyProgression($multiplayerGame);
        }
    });

    return back();
}
```

### 4. MultiplayerGame Model - Helper Methods

**New Method: `isLastQuestion`**

```php
public function isLastQuestion(): bool
{
    $totalQuestions = $this->game->total_questions;
    return $this->current_question_index >= ($totalQuestions - 1);
}
```

**New Method: `getNextQuestionIndex`**

```php
public function getNextQuestionIndex(): ?int
{
    $nextIndex = $this->current_question_index + 1;
    $totalQuestions = $this->game->total_questions;
    
    return $nextIndex < $totalQuestions ? $nextIndex : null;
}
```

## Data Models

### MultiplayerGame Table (No Changes Required)

Existing fields are sufficient:
- `current_question_index` - Tracks current question
- `question_started_at` - Timestamp for time calculations
- `status` - Game state (WAITING, ACTIVE, SHOWING_RESULTS, COMPLETED)

### Cache Keys for Job Coordination

```
multiplayer_game_{id}_execution_token - Current valid execution token
multiplayer_game_{id}_pending_job - Pending job ID for cancellation
```

## Error Handling

### Job Execution Failures

**Scenario 1: Game Not Found**
- Log warning with game ID
- Exit gracefully without throwing exception
- Prevents failed job retries

**Scenario 2: Game Not Active**
- Log info with current status
- Exit gracefully
- Handles race conditions where multiple jobs execute

**Scenario 3: Duplicate Job Execution**
- Check execution token against cached value
- If mismatch, log and exit
- Prevents double progression

### Answer Submission Failures

**Scenario 1: Time Expired**
- Return validation error
- Frontend shows "Time's up" message
- Answer not recorded

**Scenario 2: Already Answered**
- Return validation error
- Frontend prevents duplicate submission
- Idempotent behavior

**Scenario 3: Invalid Question Index**
- Return validation error
- Handles race condition where question changed
- User sees current question on next poll

## Testing Strategy

### Unit Tests

1. **NextQuestionJob**
   - Test last question detection
   - Test game completion logic
   - Test execution token validation
   - Test duplicate execution prevention

2. **MultiplayerGameService**
   - Test `allParticipantsAnswered` with various scenarios
   - Test early progression trigger
   - Test with inactive participants

3. **MultiplayerGame Model**
   - Test `isLastQuestion` boundary conditions
   - Test `getNextQuestionIndex` returns null on last question

### Integration Tests

1. **Complete Game Flow**
   - Start game → Answer all questions → Verify completion
   - Verify final results displayed
   - Verify room status updated

2. **Early Progression Flow**
   - All players answer before timer
   - Verify immediate progression
   - Verify timer-based job cancelled

3. **Last Question Scenarios**
   - Timer expires on last question
   - All players answer last question early
   - Mixed: some answer, some timeout

### Edge Cases

1. **Single Player Game**
   - Verify early progression works with 1 player
   - Verify completion on last question

2. **Player Disconnection**
   - Player disconnects mid-game
   - Verify remaining players can complete
   - Verify early progression excludes disconnected players

3. **Rapid Answer Submission**
   - All players answer within milliseconds
   - Verify only one progression job executes
   - Verify no race conditions

## Implementation Phases

### Phase 1: Fix Last Question Completion (Bug #1)
- Update `NextQuestionJob::handle()` to properly detect last question
- Ensure `completeGame()` is called when no more questions
- Update frontend to handle COMPLETED status

### Phase 2: Implement Early Progression (Bug #2)
- Add `allParticipantsAnswered()` to service
- Add `triggerEarlyProgression()` to service
- Update `answer()` controller method to check and trigger
- Add execution token system

### Phase 3: Improve Job Reliability (Bug #3)
- Add execution token validation to `NextQuestionJob`
- Extract `StartNextQuestionJob` to avoid blocking
- Add comprehensive logging
- Add duplicate execution guards

### Phase 4: Testing and Validation
- Write unit tests for all new methods
- Write integration tests for complete flows
- Manual testing with multiple players
- Load testing with concurrent games

## Performance Considerations

### Queue Worker Configuration

```php
// config/queue.php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90, // Prevent stuck jobs
        'after_commit' => true, // Wait for DB commit
    ],
],
```

### Job Delays

- Results display: 3 seconds (configurable)
- Question timer: Based on room settings (default 30s)
- Cleanup job: 1 hour after completion

### Database Queries

- Use eager loading: `with(['game', 'room.settings', 'room.participants'])`
- Index on `multiplayer_game_id` and `question_id` in `participant_answers`
- Index on `status` in `room_participants`

## Security Considerations

1. **Authorization**: Verify user is participant before accepting answers
2. **Validation**: Validate question index matches current question
3. **Time Checks**: Server-side time validation prevents cheating
4. **Idempotency**: Prevent duplicate answer submissions
5. **Rate Limiting**: Prevent spam answer submissions

## Monitoring and Logging

### Key Log Points

1. **Job Execution Start**: Log game ID, question index, status
2. **All Answered Detection**: Log when early progression triggered
3. **Game Completion**: Log final scores and participant count
4. **Job Superseded**: Log when duplicate execution prevented
5. **Errors**: Log with full context for debugging

### Metrics to Track

- Average time to complete questions
- Frequency of early progression
- Job execution failures
- Game completion rate
- Player dropout rate

## Migration Path

### Deployment Steps

1. Deploy code changes (backward compatible)
2. Monitor existing games for issues
3. Verify new games use updated logic
4. Monitor logs for errors
5. Collect metrics on early progression usage

### Rollback Plan

If issues arise:
1. Revert code deployment
2. Existing games continue with old logic
3. No data migration needed
4. Jobs will process with previous behavior

## Future Enhancements

1. **Configurable Results Display Time**: Allow hosts to set results duration
2. **Skip Results Option**: Let players vote to skip results display
3. **Pause/Resume**: Allow host to pause game between questions
4. **Spectator Mode**: Allow users to watch without participating
5. **Replay System**: Save and replay completed games

---
inclusion: fileMatch
fileMatchPattern: ['**/multiplayer/**', '**/room/**', '**/lobby/**', 'database/migrations/*room*', 'database/migrations/*multiplayer*', 'database/migrations/*participant*']
---

# Multiplayer Trivia Game Development Guidelines

## Database Schema Requirements

**Extended Tables for Multiplayer:**
- `game_rooms` - Multiplayer game rooms with host_user_id, room_code, max_players, status enum
- `room_participants` - Users in rooms with room_id, user_id, joined_at, status enum
- `multiplayer_games` - Games linked to rooms with room_id, extends single-player games
- `participant_answers` - Player responses with participant_id, answered_at
- `room_settings` - Room configuration with time_per_question, scoring_mode, etc.

**New Enums:**
- Room status: ['waiting', 'starting', 'active', 'completed', 'cancelled']
- Participant status: ['joined', 'ready', 'playing', 'finished', 'disconnected']
- Scoring modes: ['standard']

**Conventions:**
- Use 6-character random room codes for easy sharing and uniqueness
- Generate alphanumeric codes (A-Z, 0-9) for simplicity
- Include proper indexes on room_code, status, and foreign keys
- Add cascade deletes for room cleanup
- Store participant join order for turn-based features

## Room Code Generation

**Code Format:**
- 6-character alphanumeric codes (A-Z, 0-9)
- Example: K7X9M2, B4F8N1, Q3W7R5
- Case-insensitive input but store as uppercase
- No collision detection needed due to large namespace (2+ billion combinations)

**Generation Algorithm:**
```php
class RoomCodeGenerator
{
    public static function generate(): string
    {
        return strtoupper(Str::random(6));
    }
}
```

**Code Management:**
- Automatic expiration of unused codes after 24 hours
- Immediate cleanup when rooms are completed or cancelled
- Rate limiting on room creation to prevent abuse
- No collision detection needed (statistically impossible)

**User Experience:**
- Display room code prominently in large, readable font
- Auto-format input as user types (uppercase, 6 characters max)
- Provide "Copy Code" button for easy sharing
- Show room code in URL for direct linking: `/multiplayer/rooms/K7X9M2`
- Monospace font for better readability of mixed alphanumeric codes

## Real-Time Communication

**Inertia Polling:**
- Use Inertia's `usePoll` hook for real-time updates
- Implement efficient polling intervals based on game state
- Poll room state, participant updates, and game progress
- No WebSocket infrastructure required

**Polling Strategy:**
```typescript
// Active game polling (frequent updates)
usePoll(1000, { only: ['room', 'currentQuestion', 'participants'] })

// Lobby polling (moderate updates)
usePoll(3000, { only: ['room', 'participants'] })

// Results polling (infrequent updates)
usePoll(5000, { only: ['room', 'finalResults'] })
```

**State Updates:**
- Room participant changes detected via polling
- Game state progression through server-side updates
- Answer submissions trigger immediate re-poll
- Automatic polling frequency adjustment based on activity

**Polling Implementation:**
```typescript
// Example polling patterns for different game states
const { data } = usePoll(
  room.status === 'active' ? 1000 : 3000, // Faster during active games
  { 
    only: ['room', 'participants', 'currentQuestion'],
    onSuccess: (page) => {
      // Handle state updates
      if (page.props.room.status !== room.status) {
        // Room status changed, adjust polling
      }
    },
    onError: () => {
      // Handle polling errors gracefully
    }
  }
);
```

## Backend Architecture

**Controllers:**
- `RoomController` - Room creation, joining, management
- `MultiplayerGameController` - Game flow for multiplayer sessions
- `LobbyController` - Room discovery and matchmaking
- Keep single-player `GameController` separate for code clarity

**Form Requests:**
- `CreateRoomRequest` - Room creation validation
- `JoinRoomRequest` - Room joining with code validation
- `MultiplayerAnswerRequest` - Answer submission with timing
- `RoomSettingsRequest` - Host configuration updates

**Services:**
- `RoomService` - Room lifecycle management, participant handling
- `MultiplayerGameService` - Game orchestration, scoring, timing
- `MatchmakingService` - Auto-matching players by preferences
- `LeaderboardService` - Real-time scoring and rankings

**Models:**
- Use polymorphic relationships for single/multiplayer games
- Implement proper scopes for room filtering and participant queries
- Add real-time event dispatching in model observers

**Jobs & Queues:**
- `StartGameJob` - Delayed game start after countdown
- `NextQuestionJob` - Automated question progression
- `CleanupInactiveRoomsJob` - Remove abandoned rooms
- `CalculateRoundScoresJob` - Process scoring after each question

## Frontend Architecture

**Component Structure:**
```
components/
├── multiplayer/
│   ├── room-lobby.tsx          # Pre-game waiting area
│   ├── participant-list.tsx    # Show all players with answer status
│   ├── room-settings.tsx       # Host configuration panel
│   ├── game-countdown.tsx      # Starting countdown timer
│   ├── multiplayer-question.tsx # Question with live answer indicators
│   ├── round-results.tsx       # Leaderboard after each question
│   └── final-standings.tsx     # Game completion results
├── lobby/
│   ├── room-browser.tsx        # Public room discovery
│   ├── create-room-modal.tsx   # Room creation form
│   ├── join-room-modal.tsx     # Join by 6-character code
│   ├── room-code-input.tsx     # Specialized 6-character input component
│   └── quick-match.tsx         # Auto-matchmaking
└── ui/ # Existing shadcn/ui components
```

**TypeScript Interfaces:**
```typescript
interface GameRoom {
  id: number;
  room_code: string; // 6-character code like "K7X9M2"
  host_user_id: number;
  max_players: number;
  current_players: number;
  status: 'waiting' | 'starting' | 'active' | 'completed';
  settings: RoomSettings;
  participants: Participant[];
}

interface Participant {
  id: number;
  user: User;
  status: 'joined' | 'ready' | 'playing' | 'finished';
  score: number;
  has_answered_current: boolean; // For live answer status
  position?: number; // Only populated for round-end leaderboard
  joined_at: string;
}

interface RoomSettings {
  time_per_question: number; // seconds
  scoring_mode: 'standard';
  category_id?: number;
  difficulty: 'easy' | 'medium' | 'hard';
  total_questions: number;
}

interface MultiplayerAnswer {
  participant_id: number;
  answer: string;
  submitted_at: number; // timestamp for tracking submission order
}
```

**State Management:**
- Use Inertia's `usePoll` for real-time state synchronization
- Implement optimistic UI updates with server validation
- Handle polling errors and network issues gracefully
- Use React Context for local room state management
- Leverage Inertia's built-in state management for server data

## Routing Conventions

**Laravel Routes:**
```php
// Multiplayer room management
Route::middleware(['auth', 'verified'])->prefix('multiplayer')->name('multiplayer.')->group(function () {
    Route::get('/lobby', [LobbyController::class, 'index'])->name('lobby');
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::post('/rooms/join', [RoomController::class, 'join'])->name('rooms.join');
    Route::get('/rooms/{room:room_code}', [RoomController::class, 'show'])->name('rooms.show');
    Route::post('/rooms/{room}/start', [RoomController::class, 'start'])->name('rooms.start');
    Route::delete('/rooms/{room}/leave', [RoomController::class, 'leave'])->name('rooms.leave');
});

// Multiplayer game flow
Route::middleware(['auth', 'verified'])->prefix('multiplayer/game')->name('multiplayer.game.')->group(function () {
    Route::get('/{room:room_code}', [MultiplayerGameController::class, 'show'])->name('show');
    Route::post('/{room}/answer', [MultiplayerGameController::class, 'answer'])->name('answer');
    Route::get('/{room}/results', [MultiplayerGameController::class, 'results'])->name('results');
});
```

## Real-Time Features

**Question Timing:**
- Server-controlled countdown timers (30 seconds per question)
- Store `question_started_at` timestamp when question begins
- Calculate remaining time server-side: `30 - (now - question_started_at)`
- Poll timer updates every 1 second during active questions
- Automatic progression when timer expires (using Laravel jobs)
- Players can still answer after others finish, until timer expires

**Live Updates via Polling:**
- Participant list updates through regular polling
- Answer submission status indicators (without revealing answers)
- Score updates after each question via targeted polling
- Room status changes detected through state polling

**Polling Optimization:**
- Dynamic polling intervals based on game phase
- Conditional polling using Inertia's `only` parameter
- Pause polling during inactive periods
- Resume polling on user interaction or page focus

**Connection Handling:**
- Detect network issues through failed poll requests
- Automatic retry with exponential backoff
- Graceful degradation when polling fails
- Clear user feedback for connection status

## Security & Validation

**Room Security:**
- Generate random 6-character alphanumeric room codes
- Automatic code expiration for inactive rooms (24 hours)
- Validate room capacity before allowing joins
- Implement rate limiting on room creation and joining
- Prevent unauthorized game state manipulation

**Answer Validation:**
- Server-side timing validation with tolerance
- Prevent answer submission after time expires
- Validate participant membership before accepting answers
- Implement anti-cheat measures for timing manipulation

**Authorization:**
- Only room hosts can modify settings and start games
- Participants can only submit answers for their own account
- Implement proper room ownership validation
- Secure polling endpoint authorization with proper middleware

## Performance Optimization

**Database:**
- Index room_code, status, and participant queries
- Use database transactions for atomic room operations
- Implement efficient participant counting queries
- Cache active room lists for lobby display

**Polling Optimization:**
- Use Inertia's `only` parameter to fetch minimal data
- Implement smart polling intervals based on activity
- Cache frequently accessed room data in Redis
- Batch database queries for multiple participant updates

**Caching:**
- Cache room settings and participant lists in Redis
- Use Laravel's cache tags for efficient invalidation
- Implement proper cache invalidation on room updates
- Cache leaderboard calculations with short TTL during active games

## Testing Strategy

**Feature Tests:**
- Test complete multiplayer game flow from room creation to completion
- Verify polling-based state updates and synchronization
- Test participant joining/leaving scenarios
- Mock HTTP responses for polling endpoints in automated testing

**Integration Tests:**
- Test room capacity limits and overflow handling
- Verify answer timing validation and scoring
- Test host migration and disconnection scenarios
- Validate concurrent answer submission handling

**Load Testing:**
- Test multiple concurrent rooms and participants
- Verify polling performance under high concurrent load
- Test database performance under high participant load
- Validate polling frequency and server response times at scale

## Error Handling

**Connection Issues:**
- Graceful handling of failed polling requests
- Automatic retry with exponential backoff for failed polls
- Pause polling during network issues and resume when recovered
- Clear user feedback for connection status and polling errors

**Room Management:**
- Handle room capacity exceeded scenarios
- Manage host abandonment with automatic cleanup
- Provide clear error messages for invalid room codes
- Handle concurrent join attempts gracefully

**Game Flow:**
- Manage partial participant disconnections during games
- Handle answer submission conflicts and timing edge cases
- Provide recovery mechanisms for interrupted games
- Clear error states and recovery instructions

## Scoring Systems

**Standard Scoring:**
- Fixed points per correct answer (e.g., 100 points)
- 0 points for incorrect answers
- Simple, fair, and easy to understand

## Core Multiplayer Features

**Shared Timer + Answer Status (Primary Features):**
- 30-second countdown timer visible to all players
- Show "✓ Answered" indicators next to player names during questions
- Poll every 1 second to update both timer and answer status
- Creates shared urgency and competitive pressure
- Timer automatically advances to results when it hits 0
- Simple database: `question_started_at` timestamp and `answered_at` checks

**Round-End Leaderboard (Secondary Feature):**
- Show full leaderboard with scores after each question is completed
- Display who got the question right/wrong with dramatic reveals
- Show score changes and position updates
- No real-time polling needed - just update once when round ends

**Implementation:**
```typescript
// During question - poll for timer and answer status
const { data } = usePoll(1000, {
  only: ['room', 'participants', 'timeRemaining'],
  onSuccess: (page) => {
    // Update countdown timer
    updateTimer(page.props.timeRemaining);
    
    // Update "✓ Answered" indicators
    updateAnswerStatus(page.props.participants);
    
    // Auto-redirect when time expires
    if (page.props.timeRemaining <= 0) {
      // Server handles redirect to results
    }
  }
});

// After question - show leaderboard (no polling needed)
// Server redirects to round results page with full leaderboard data
```

**Database Requirements:**
- Add `question_started_at` timestamp to room state for timer calculation
- Add `answered_at` timestamp to `participant_answers` table
- Track `current_question_index` in room state
- Use Laravel jobs for automatic question progression when timer expires
- Calculate scores and positions server-side for round-end display

**User Experience Flow:**
1. **Question Starts**: 30-second timer begins, all players see countdown
2. **During Question**: Show live timer + "✓ Answered" status, poll every 1 second
3. **Timer Expires**: Record 0 points for players who didn't answer
4. **Check Game State**:
   - **More questions remaining**: Show round results → Next question
   - **Last question**: Show final game results → Game ends
5. **Round Results**: Display who got it right/wrong, score changes, brief pause
6. **Next Question**: New 30-second timer starts, resume polling

**Timer Expiration Logic:**
```php
// When 30 seconds expire
if ($room->hasMoreQuestions()) {
    // Show round results, then continue
    $room->update(['status' => 'showing_round_results']);
    // After 5 seconds, start next question
} else {
    // Show final results, game over
    $room->update(['status' => 'completed']);
}
```

**Timer Benefits:**
- **Shared experience**: All players race against same clock
- **No waiting**: Game progresses automatically, no slow players holding up others
- **Increased tension**: Countdown creates urgency and excitement
- **Fair play**: Everyone gets exactly 30 seconds, regardless of connection speed

## Matchmaking Features

**Quick Match:**
- Auto-match players with similar preferences
- Consider skill level, preferred difficulty, category
- Create rooms automatically when matches found
- Configurable wait times and fallback options

**Custom Rooms:**
- Host-created rooms with specific settings
- Public/private room options
- Room browser with filtering capabilities
- Invite system via room codes or direct links

**Tournament Mode:**
- Bracket-style elimination tournaments
- Swiss-system round-robin tournaments
- Automated bracket progression
- Prize/ranking systems for competitive play
# Design Document

## Overview

The trivia game system uses a hybrid approach combining pass-through API for content with backend persistence for game state. **Phase 1 (Single-Player)**: Laravel acts as a proxy to the Open Trivia Database API for categories and questions (no local storage needed), while game sessions, player answers, and scores are persisted in the database for state management and future multiplayer support.

**Future Phase 2 (Multiplayer)**: Will extend existing game state persistence to support multiple players per game session. Real-time synchronization will use Inertia's `usePoll` hook rather than WebSockets for simplicity.

The architecture follows Laravel's MVC pattern with selective persistence and Inertia.js for seamless frontend-backend communication.

## Architecture

### System Components

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   React Pages   │    │ Laravel Hybrid   │    │ Open Trivia DB  │
│                 │    │ Backend          │    │                 │
│ - GameSetup     │◄──►│ TriviaController │◄──►│ Categories API  │
│ - PlayGame      │    │ (Pass-through)   │    │ Questions API   │
│ - GameResults   │    │ GameController   │    │                 │
└─────────────────┘    │ (State Mgmt)     │    └─────────────────┘
         │              └──────────────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐    ┌──────────────────┐
│ React Components│    │ Database Models  │
│                 │    │                  │
│ - QuestionCard  │    │ - Game           │
│ - ScoreDisplay  │    │ - PlayerAnswer   │
│ - ProgressBar   │    │ (State Only)     │
│ - GameState     │    │                  │
└─────────────────┘    └──────────────────┘
```

### Data Flow

1. **Game Setup**: User selects parameters → Backend creates Game record → Fetches questions from API → Returns questions + game ID
2. **Question Display**: Frontend displays questions from API response → Backend tracks current question index in Game record
3. **Answer Processing**: Frontend submits answer → Backend validates → Creates PlayerAnswer record → Updates Game score/progress
4. **Game Completion**: Backend marks game complete → Returns final results from database → No question storage needed

## Components and Interfaces

### Backend Controllers

#### TriviaController
```php
class TriviaController extends Controller
{
    public function categories(): JsonResponse
    // Fetches categories from Open Trivia API with caching
    // Returns formatted category list for frontend
    
    public function questions(Request $request): JsonResponse  
    // Fetches questions from Open Trivia API based on parameters
    // Processes and returns formatted questions with shuffled answers
    // No local storage - pure pass-through with processing
}
```

#### GameController
```php
class GameController extends Controller
{
    public function setup(): Response
    // Shows game setup page with category selection
    
    public function store(GameStoreRequest $request): RedirectResponse
    // Creates new game record with selected parameters
    // Fetches questions from Open Trivia API (no storage)
    // Returns questions array + game ID to frontend
    
    public function show(Game $game): Response
    // Shows current question for active game
    // Questions come from original API response (stored in session/cache)
    // Game progress tracked in database
    
    public function answer(Game $game, AnswerRequest $request): JsonResponse
    // Processes player answer submission
    // Creates PlayerAnswer record in database
    // Updates game score and progress
    // Returns feedback and next question status
    
    public function results(Game $game): Response
    // Shows final game results from database
    // Calculates statistics from PlayerAnswer records
}
```

### Frontend Components

#### Pages
- **GameSetup.tsx**: Form for category, difficulty, and question count selection
- **PlayGame.tsx**: Question display with answer options and progress tracking
- **GameResults.tsx**: Final score display with game statistics

#### UI Components
- **QuestionCard**: Displays question text and shuffled answer buttons
- **ScoreDisplay**: Shows current score and points earned
- **ProgressBar**: Indicates question progress (X of Y)

### Services

#### OpenTriviaService (Pass-Through API)
```php
class OpenTriviaService
{
    public function getCategories(): array
    // Fetches categories from https://opentdb.com/api_category.php
    // Caches for 1 hour to reduce API calls
    // Returns formatted array for frontend consumption
    
    public function getQuestions(array $params): array
    // Fetches questions from https://opentdb.com/api.php
    // Validates parameters (category, difficulty, amount)
    // Processes response: decodes HTML entities, shuffles answers
    // Returns formatted questions ready for frontend
    
    private function decodeHtmlEntities(array $data): array
    // Processes API response to decode HTML entities
    
    private function shuffleAnswers(array $question): array
    // Randomizes answer order for each question
    
    private function handleApiErrors(Exception $e): array
    // Graceful error handling with fallback responses
}
```

## Data Models

### Database Schema (Game State Only)

#### Games Table
```php
Schema::create('games', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->integer('category_id')->nullable(); // Open Trivia DB category ID
    $table->enum('difficulty', ['easy', 'medium', 'hard', 'mixed']);
    $table->integer('total_questions');
    $table->integer('current_question_index')->default(0);
    $table->integer('score')->default(0);
    $table->enum('status', ['active', 'completed'])->default('active');
    $table->json('questions')->nullable(); // Store API response for session
    $table->timestamp('started_at')->useCurrent();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
```

#### Player Answers Table
```php
Schema::create('player_answers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('game_id')->constrained()->onDelete('cascade');
    $table->integer('question_index'); // Position in questions array
    $table->text('question'); // Store question text for reference
    $table->string('selected_answer');
    $table->string('correct_answer');
    $table->boolean('is_correct');
    $table->integer('points_earned');
    $table->timestamp('answered_at')->useCurrent();
    $table->timestamps();
    
    $table->unique(['game_id', 'question_index']);
});
```

### Eloquent Models

#### Game Model
```php
class Game extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'difficulty', 'total_questions',
        'current_question_index', 'score', 'status', 'questions'
    ];
    
    protected $casts = [
        'questions' => 'array', // Store API response
        'status' => GameStatus::class,
        'difficulty' => DifficultyLevel::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    public function playerAnswers(): HasMany
    public function currentQuestion(): array|null // From questions JSON
    public function isCompleted(): bool
    public function calculateFinalScore(): int
}
```

### Frontend TypeScript Interfaces

```typescript
interface Game {
  id: number;
  user_id: number;
  category_id: number | null;
  difficulty: 'easy' | 'medium' | 'hard' | 'mixed';
  total_questions: number;
  current_question_index: number;
  score: number;
  status: 'active' | 'completed';
  questions: Question[]; // From API, stored in game record
  started_at: string;
  completed_at: string | null;
}

interface Question {
  question: string;
  correct_answer: string;
  incorrect_answers: string[];
  difficulty: string;
  category: string;
  shuffled_answers: string[]; // Pre-shuffled by backend
}

interface PlayerAnswer {
  id: number;
  game_id: number;
  question_index: number;
  question: string;
  selected_answer: string;
  correct_answer: string;
  is_correct: boolean;
  points_earned: number;
  answered_at: string;
}

interface Category {
  id: number;
  name: string;
}
```

### No Categories/Questions Storage

The hybrid approach keeps the API pass-through for:
- **Categories**: Always fetched fresh from Open Trivia API (with caching)
- **Questions**: Fetched from API per game, stored temporarily in Game.questions JSON field
- **Game State**: Fully persisted for resume capability and multiplayer foundation
- **Player Answers**: Fully persisted for scoring and analytics

## Error Handling

### API Error Responses
- **External API Failures**: Graceful fallback with cached data or user notification
- **Validation Errors**: Clear error messages for invalid game parameters
- **Game State Errors**: Prevent invalid game state transitions
- **Network Timeouts**: Retry logic for external API calls

### Frontend Error Handling
- Loading states during API calls
- Error boundaries for component failures
- User-friendly error messages
- Automatic retry for transient failures

## Testing Strategy

### Backend Testing
- **Unit Tests**: Model relationships, business logic, API service methods
- **Feature Tests**: Controller endpoints, game flow, answer validation
- **Integration Tests**: External API integration, database transactions

### Frontend Testing
- **Component Tests**: UI component rendering and interactions
- **Integration Tests**: Page navigation and form submissions
- **E2E Tests**: Complete game flow from setup to results

### Test Data Management
- Mock external API responses for consistent testing
- Factory classes for generating test games and questions
- Database seeding for development and testing environments

## Performance Considerations

### Caching Strategy
- Cache category data from external API (1 hour TTL)
- Store questions temporarily in Game.questions JSON field for session duration
- Game state persisted in database for reliability and multiplayer readiness

### Database Optimization
- Indexes on foreign keys and frequently queried fields
- Eager loading for game relationships
- Pagination for large question sets

### Frontend Optimization
- Lazy loading for game components
- Optimistic UI updates for answer submissions
- Preload next question while displaying current results
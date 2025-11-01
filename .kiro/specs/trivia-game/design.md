# Design Document

## Overview

The trivia game system is built as a Laravel pass-through API with React frontend components. **Phase 1 (Single-Player)**: Laravel acts as a pure proxy to the Open Trivia Database API, fetching categories and questions in real-time without local storage. Game state is maintained entirely client-side during gameplay.

**Future Phase 2 (Multiplayer)**: Will introduce database models and tables to persist games, questions, and player answers. Real-time synchronization will use Inertia's `usePoll` hook rather than WebSockets for simplicity.

The architecture follows Laravel's MVC pattern with Inertia.js for seamless frontend-backend communication.

## Architecture

### System Components

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   React Pages   │    │ Laravel Pass-    │    │ Open Trivia DB  │
│                 │    │ Through API      │    │                 │
│ - GameSetup     │◄──►│ TriviaController │◄──►│ Categories API  │
│ - PlayGame      │    │ (Proxy Only)     │    │ Questions API   │
│ - GameResults   │    │                  │    │                 │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                       
         │                       
         ▼                       
┌─────────────────┐    
│ React Components│    
│                 │    
│ - QuestionCard  │    
│ - ScoreDisplay  │    
│ - ProgressBar   │    
│ - GameState     │    
└─────────────────┘    
```

### Data Flow

1. **Game Setup**: User selects parameters → Frontend validates → Backend fetches questions from API → Returns to frontend
2. **Question Display**: Frontend manages game state → Displays questions sequentially → Tracks progress locally
3. **Answer Processing**: Frontend validates answers → Calculates scores → Updates progress
4. **Game Completion**: Frontend calculates final results → Displays summary → No backend persistence

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

#### GameController (Minimal - Future Expansion)
```php
class GameController extends Controller
{
    public function setup(): Response
    // Shows game setup page with category selection
    
    public function play(): Response
    // Shows game play page (questions managed client-side)
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

### Frontend State Management

Since we're using a pass-through API approach, game state is managed entirely on the frontend:

#### Game State Interface
```typescript
interface GameState {
  id: string; // UUID for session tracking
  category: Category | null;
  difficulty: 'easy' | 'medium' | 'hard' | 'mixed';
  totalQuestions: number;
  questions: Question[];
  currentQuestionIndex: number;
  answers: PlayerAnswer[];
  score: number;
  status: 'setup' | 'playing' | 'completed';
  startedAt: Date;
  completedAt?: Date;
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
  questionIndex: number;
  selectedAnswer: string;
  isCorrect: boolean;
  pointsEarned: number;
  answeredAt: Date;
}

interface Category {
  id: number;
  name: string;
}
```

### No Database Models Required (Phase 1)

The pass-through approach eliminates the need for:
- Categories table (fetched from API)
- Questions table (fetched from API)  
- Games table (state managed client-side)
- Player answers table (tracked client-side)

This simplifies the backend significantly while maintaining all required functionality.

**Future Database Schema (Phase 2 - Multiplayer)**
When multiplayer support is added, we'll introduce:
- `games` table for persistent game sessions
- `game_players` table for multiplayer participation
- `player_answers` table for answer tracking across players
- Real-time sync via Inertia's `usePoll` hook (no WebSockets needed)

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
- No question caching (real-time fetching for variety)
- Frontend localStorage for game state persistence during page refreshes

### Database Optimization
- Indexes on foreign keys and frequently queried fields
- Eager loading for game relationships
- Pagination for large question sets

### Frontend Optimization
- Lazy loading for game components
- Optimistic UI updates for answer submissions
- Preload next question while displaying current results
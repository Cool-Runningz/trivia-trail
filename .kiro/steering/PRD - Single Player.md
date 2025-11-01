---
inclusion: always
---

---
inclusion: fileMatch
fileMatchPattern: ['**/game/**', '**/trivia/**', 'database/migrations/*game*', 'database/migrations/*question*', 'database/migrations/*category*']
---

# Trivia Game Development Guidelines

## Database Schema Requirements

**Core Tables:**
- `categories` - Game categories with name, description
- `questions` - Questions with category_id, difficulty enum, correct/incorrect answers
- `games` - User games with status enum ['active', 'completed'], score, settings
- `player_answers` - User responses with game_id, question_id, selected_answer, is_correct
- `game_questions` - Pivot table linking games to questions with order

**Conventions:**
- Use enum casts for status and difficulty fields
- Include timestamps and soft deletes on main entities
- Add proper foreign key constraints and indexes
- Follow Laravel migration naming: `create_[table]_table.php`

## API Integration Patterns

**Open Trivia Database:**
- Use Laravel HTTP client with retry logic and timeout handling
- Decode HTML entities from API responses: `html_entity_decode($text, ENT_QUOTES)`
- Store fetched questions locally to reduce API dependency
- Implement rate limiting to respect API constraints

## Backend Architecture

**Controllers:**
- Keep controllers thin, delegate business logic to services
- Use resource controllers: `GameController`, `QuestionController`
- Implement proper HTTP status codes and JSON responses
- Follow existing auth middleware patterns

**Form Requests:**
- Create validation classes: `GameStoreRequest`, `AnswerSubmissionRequest`
- Include authorization logic in form requests
- Use custom validation rules for game-specific logic

**Services:**
- `GameService` - Game creation, scoring, completion logic
- `QuestionService` - Question fetching, shuffling, validation
- `TriviaApiService` - External API integration and caching

**Models:**
- Use Eloquent relationships and eager loading
- Implement proper scopes for filtering (active games, user games)
- Add mutators/accessors for data formatting

## Frontend Architecture

**Component Structure:**
```
components/
├── game/
│   ├── game-setup.tsx      # Game configuration form
│   ├── question-card.tsx   # Individual question display
│   ├── answer-options.tsx  # Answer selection interface
│   ├── game-progress.tsx   # Progress indicator
│   └── results-summary.tsx # Final score display
└── ui/ # Existing shadcn/ui components
```

**TypeScript Interfaces:**
```typescript
interface Game {
  id: number;
  status: 'active' | 'completed';
  score: number;
  total_questions: number;
  category: Category;
  difficulty: 'easy' | 'medium' | 'hard';
}

interface Question {
  id: number;
  question: string;
  correct_answer: string;
  incorrect_answers: string[];
  difficulty: string;
}
```

**State Management:**
- Use Inertia.js forms for server communication
- Implement optimistic updates for answer submissions
- Handle loading states during question transitions
- Use React hooks for local component state

## Routing Conventions

**Laravel Routes:**
```php
Route::middleware(['auth', 'verified'])->prefix('game')->name('game.')->group(function () {
    Route::get('/setup', [GameController::class, 'create'])->name('create');
    Route::post('/', [GameController::class, 'store'])->name('store');
    Route::get('/{game}', [GameController::class, 'show'])->name('show');
    Route::post('/{game}/answer', [GameController::class, 'answer'])->name('answer');
    Route::get('/{game}/results', [GameController::class, 'results'])->name('results');
});
```

## Security & Validation

**Answer Validation:**
- Never trust client-side answer validation
- Store correct answers server-side, validate against database
- Prevent replay attacks by tracking answered questions
- Implement proper game ownership authorization

**Data Sanitization:**
- Sanitize all user inputs in Form Requests
- Escape HTML content in question text
- Validate enum values against allowed options

## Performance Optimization

**Database:**
- Eager load relationships: `Game::with(['questions', 'category'])`
- Use database indexes on foreign keys and frequently queried columns
- Implement query scopes for common filters

**Caching:**
- Cache category lists and static trivia data
- Use Redis for session-based game state if needed
- Implement proper cache invalidation strategies

## Testing Strategy

**Feature Tests:**
- Test complete game flow from setup to results
- Verify answer validation and scoring logic
- Test authorization (users can only access own games)
- Mock external API calls with realistic responses

**Edge Cases:**
- Handle incomplete games and session timeouts
- Test with malformed API responses
- Verify behavior with duplicate question attempts

## Error Handling

**API Failures:**
- Graceful degradation when trivia API is unavailable
- Fallback to cached questions or default categories
- User-friendly error messages with retry options

**Validation Errors:**
- Clear feedback for invalid game configurations
- Proper error states in React components
- Consistent error response format from Laravel
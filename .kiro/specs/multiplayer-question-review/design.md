# Design Document

## Overview

This feature adds a comprehensive question review section to the multiplayer game results screen. The review section will display all questions from the completed game in an accordion format, showing each question with the correct answer and the current user's selected answer. This allows players to learn from their performance and understand which questions they answered correctly or incorrectly.

## Architecture

### Component Structure

The implementation follows the existing React component architecture:

```
components/multiplayer/
├── FinalStandings.tsx (existing - will be enhanced)
└── QuestionReview.tsx (new component)
```

### Data Flow

1. **Backend**: The `MultiplayerGameController::showFinalResults()` method will be enhanced to include all questions and the current user's answers in the response
2. **Frontend**: The `FinalStandings` component will receive the additional data and render the new `QuestionReview` component
3. **State Management**: No additional state management needed - all data comes from Inertia props

## Components and Interfaces

### Backend Changes

#### MultiplayerGameController Enhancement

The `showFinalResults()` method will be modified to include:

```php
private function showFinalResults(GameRoom $room, MultiplayerGame $multiplayerGame): Response
{
    // Existing leaderboard generation...
    $leaderboard = $this->generateLeaderboard($room);
    
    // NEW: Get all questions with current user's answers
    $questionsWithAnswers = $this->getQuestionsWithUserAnswers(
        $multiplayerGame, 
        auth()->id()
    );
    
    return Inertia::render('multiplayer/Game', [
        'gameState' => [
            // ... existing data ...
            'round_results' => [
                'leaderboard' => $leaderboard,
                'question' => null,
                'participant_results' => [],
                'questions_review' => $questionsWithAnswers, // NEW
            ],
        ],
    ]);
}

private function getQuestionsWithUserAnswers(
    MultiplayerGame $multiplayerGame, 
    int $userId
): array
{
    // Get participant for current user
    $participant = $multiplayerGame->room->participants()
        ->where('user_id', $userId)
        ->first();
    
    // Get all questions from the game
    $questions = $multiplayerGame->game->questions;
    
    // Get all answers for this participant
    $answers = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
        ->where('participant_id', $participant->id)
        ->get()
        ->keyBy('question_id');
    
    // Map questions with user answers
    return collect($questions)->map(function ($question, $index) use ($answers) {
        $answer = $answers->get($index);
        
        return [
            'question_number' => $index + 1,
            'question_text' => $question['question'],
            'correct_answer' => $question['correct_answer'],
            'all_answers' => $question['shuffled_answers'] ?? [],
            'user_answer' => $answer?->selected_answer,
            'is_correct' => $answer?->is_correct ?? false,
            'answered' => $answer !== null,
        ];
    })->toArray();
}
```

### Frontend Changes

#### TypeScript Interface Updates

Add to `resources/js/types/index.d.ts`:

```typescript
export interface QuestionReviewItem {
    question_number: number;
    question_text: string;
    correct_answer: string;
    all_answers: string[];
    user_answer?: string;
    is_correct: boolean;
    answered: boolean;
}

export interface RoundResults {
    question: Question;
    correct_answer: string;
    participant_results: ParticipantResult[];
    leaderboard: LeaderboardEntry[];
    questions_review?: QuestionReviewItem[]; // NEW
}
```

#### New Component: QuestionReview

Create `resources/js/components/multiplayer/QuestionReview.tsx`:

```typescript
interface QuestionReviewProps {
    questions: QuestionReviewItem[];
}

export function QuestionReview({ questions }: QuestionReviewProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Question Review</CardTitle>
                <CardDescription>
                    Review all {questions.length} questions and your answers
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Accordion type="multiple" className="w-full">
                    {questions.map((item) => (
                        <AccordionItem key={item.question_number} value={`question-${item.question_number}`}>
                            <AccordionTrigger>
                                <div className="flex items-center gap-3 w-full">
                                    {/* Status Icon */}
                                    {item.answered ? (
                                        item.is_correct ? (
                                            <CheckCircle2 className="h-5 w-5 text-green-500" />
                                        ) : (
                                            <XCircle className="h-5 w-5 text-red-500" />
                                        )
                                    ) : (
                                        <Circle className="h-5 w-5 text-gray-400" />
                                    )}
                                    
                                    {/* Question Number and Preview */}
                                    <span className="text-left">
                                        Question {item.question_number} of {questions.length}
                                    </span>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent>
                                {/* Question details */}
                            </AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </CardContent>
        </Card>
    );
}
```

#### FinalStandings Component Enhancement

Update `resources/js/components/multiplayer/FinalStandings.tsx`:

```typescript
export function FinalStandings({ 
    leaderboard, 
    totalQuestions,
    questionsReview // NEW prop
}: FinalStandingsProps) {
    // ... existing code ...
    
    return (
        <div className="space-y-6">
            {/* Winner Celebration - existing */}
            {/* Full Leaderboard - existing */}
            {/* Game Stats - existing */}
            
            {/* NEW: Question Review Section */}
            {questionsReview && questionsReview.length > 0 && (
                <QuestionReview questions={questionsReview} />
            )}
            
            {/* Action Buttons - existing */}
        </div>
    );
}
```

## Data Models

No database schema changes required. The feature uses existing data:

- `MultiplayerGame` - contains the game questions
- `ParticipantAnswer` - contains user's answers
- `RoomParticipant` - links users to games

## Error Handling

### Missing Data Scenarios

1. **No answers for user**: Display "Not Answered" state in the review
2. **Incomplete game data**: Gracefully hide the review section if questions are unavailable
3. **Malformed question data**: Skip individual questions that have data issues

### Frontend Error Boundaries

The existing `MultiplayerErrorBoundary` will catch any rendering errors in the new component.

## Testing Strategy

### Backend Tests

1. Test `getQuestionsWithUserAnswers()` method:
   - Returns correct data structure
   - Handles users with no answers
   - Correctly maps answers to questions
   - Handles edge cases (0 questions, 50 questions)

### Frontend Tests

1. Component rendering tests:
   - Renders accordion with all questions
   - Shows correct/incorrect indicators
   - Displays user answers vs correct answers
   - Handles missing user answers

2. Integration tests:
   - Full game flow ending with question review
   - Verify data passed from backend to frontend

## UI/UX Design

### Visual Design

**Accordion Header:**
- Left: Status icon (green checkmark, red X, or gray circle)
- Center: "Question X of Y"
- Right: Chevron indicator for expand/collapse

**Accordion Content:**
- Question text (decoded HTML entities)
- User's answer with visual indicator
- Correct answer highlighted
- All answer options displayed for context

**Color Scheme:**
- Correct: Green (`text-green-500`, `bg-green-50`, `border-green-200`)
- Incorrect: Red (`text-red-500`, `bg-red-50`, `border-red-200`)
- Not Answered: Gray (`text-gray-400`, `bg-gray-50`)

### Responsive Design

- Mobile: Full-width accordion, stacked layout
- Tablet/Desktop: Same layout with better spacing
- Accordion ensures content doesn't overwhelm small screens

### Accessibility

- Proper ARIA labels on accordion items
- Keyboard navigation support (built into Radix UI Accordion)
- Screen reader announcements for correct/incorrect status
- Sufficient color contrast for all text

## Performance Optimization

### Data Loading

- Questions and answers loaded once with initial page load (no additional API calls)
- Data included in Inertia response payload

### Rendering Optimization

- Accordion lazy-renders content (only expanded items render full content)
- Handles up to 50 questions efficiently
- No virtualization needed for this scale

### Caching

- No additional caching needed (data is static after game completion)

## Security Considerations

1. **Data Privacy**: Only show current user's answers (not other players' individual answers)
2. **Authorization**: Verify user is a participant before showing their answers
3. **Data Validation**: Sanitize question text and answers (HTML entity decoding)

## Dependencies

### New Dependencies

- None - uses existing shadcn/ui Accordion component

### Existing Dependencies

- `@radix-ui/react-accordion` (already installed via shadcn/ui)
- `lucide-react` (for icons)
- Existing UI components (Card, Badge, etc.)

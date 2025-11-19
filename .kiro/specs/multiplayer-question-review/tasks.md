# Implementation Plan

- [x] 1. Update TypeScript interfaces for question review data
  - Add `QuestionReviewItem` interface to `resources/js/types/index.d.ts`
  - Update `RoundResults` interface to include optional `questions_review` property
  - _Requirements: 1.1, 1.2_

- [x] 2. Enhance backend controller to include question review data
  - [x] 2.1 Add `getQuestionsWithUserAnswers()` method to `MultiplayerGameController`
    - Retrieve all questions from the completed game
    - Fetch current user's participant record
    - Query all participant answers for the user
    - Map questions with user answers, including correct/incorrect status
    - _Requirements: 1.1, 1.2, 1.3, 1.4_
  
  - [x] 2.2 Update `showFinalResults()` method to include questions review
    - Call `getQuestionsWithUserAnswers()` method
    - Add `questions_review` to the `round_results` data structure
    - _Requirements: 1.1_

- [ ] 3. Create QuestionReview component
  - [ ] 3.1 Create `resources/js/components/multiplayer/QuestionReview.tsx`
    - Import necessary UI components (Card, Accordion, icons)
    - Define component props interface
    - Implement accordion structure with question items
    - _Requirements: 1.5, 3.1, 3.2_
  
  - [ ] 3.2 Implement accordion header with status indicators
    - Add status icon (CheckCircle2 for correct, XCircle for incorrect, Circle for not answered)
    - Display question number and total count
    - Apply appropriate color styling based on answer status
    - _Requirements: 2.1, 2.2, 3.4_
  
  - [ ] 3.3 Implement accordion content with answer details
    - Display question text with HTML entity decoding
    - Show user's selected answer with visual indicator
    - Highlight correct answer
    - Display all answer options for context
    - Apply color-coded styling (green for correct, red for incorrect)
    - _Requirements: 1.2, 1.3, 1.4, 2.3, 2.4_
  
  - [ ] 3.4 Add responsive and accessible design
    - Ensure mobile-friendly layout
    - Add proper ARIA labels for accessibility
    - Implement keyboard navigation support
    - _Requirements: 3.1, 3.2, 3.3_

- [ ] 4. Integrate QuestionReview into FinalStandings component
  - Update `FinalStandings` component props to accept `questionsReview`
  - Import and render `QuestionReview` component below game statistics
  - Add conditional rendering to handle missing review data
  - Update component export in `resources/js/components/multiplayer/index.ts`
  - _Requirements: 1.1, 1.5_

- [ ] 5. Update Game.tsx page to pass question review data
  - Verify `gameState.round_results.questions_review` is passed to `FinalStandings`
  - Ensure data flows correctly from controller to component
  - _Requirements: 1.1_

- [ ] 6. Verify and test the implementation
  - Test with games containing various question counts (1, 10, 50 questions)
  - Verify correct/incorrect indicators display properly
  - Test accordion expand/collapse functionality
  - Verify user answers match displayed data
  - Test responsive design on mobile and desktop
  - Check accessibility with keyboard navigation
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3, 3.4_

**Feature Overview**
> This feature enhances the multiplayer game results screen by adding a detailed question review section. Currently, the results screen only displays overall game statistics and final standings. This enhancement will allow players to review all questions from the game, see the correct answers, and compare them with their own selections.

## Task 2 - Enhance backend controller to include question review data
**Added` getQuestionsWithUserAnswers()` method:**
- Retrieves the current user's participant record from the room
- Fetches all questions from the completed game
- Queries all participant answers for the user and indexes them by question_id
- Maps each question with the user's answer, including:
   - Question number (1-indexed)
   - Question text
   - Correct answer
   - All answer options
   - User's selected answer
   - Whether the answer was correct
   - Whether the question was answered

**Updated `showFinalResults()` method:**
- Calls the new `getQuestionsWithUserAnswers()` method with the authenticated user's ID
- Adds the `questions_review` data to the `round_results` structure in the Inertia response
- This data will be available to the frontend components

---

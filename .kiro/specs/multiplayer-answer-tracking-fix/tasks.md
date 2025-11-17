# Implementation Plan

- [x] 1. Update MultiplayerGameService with synchronous methods
  - Add `allParticipantsAnswered()` method to check if all active players have answered
  - Add `currentUserHasAnswered()` method to check if specific user has answered
  - Add `calculateRoundScores()` synchronous method to replace job-based scoring
  - Add `advanceToNextQuestion()` synchronous method for question progression
  - Remove `triggerEarlyProgression()` method and related job dispatching code
  - _Requirements: 1.1, 1.2, 4.1, 5.1, 5.2, 5.3, 5.4_

- [x] 2. Update MultiplayerGame model with helper methods
  - Add `isReadyForNext()` method to check if ready for progression (timer expired OR all answered)
  - Add `calculateTimeRemaining()` method for consistent time calculation
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 3. Update MultiplayerGameController with new endpoint and enhanced state
  - Modify `show()` method to include `all_players_answered`, `current_user_has_answered`, `is_ready_for_next`, and `ready_since` flags
  - Add `nextQuestion()` method to handle host-triggered question advancement
  - Add host authorization validation in `nextQuestion()` method
  - Add ready state validation before allowing advancement
  - Use database transaction for score calculation and state updates
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4, 2.5, 4.2, 4.3, 5.3_

- [x] 5. Create NextQuestionButton component
  - Create new component at `resources/js/components/multiplayer/NextQuestionButton.tsx`
  - Implement 2-second countdown timer using useEffect
  - Add auto-advance logic that triggers after countdown
  - Add manual "Next Question" button for host
  - Display appropriate message based on all_players_answered vs time_expired state
  - Handle loading states during submission
  - _Requirements: 2.2, 2.3, 2.4, 2.5_

- [x] 6. Update MultiplayerQuestion component with new UI states
  - Add new props: `allPlayersAnswered`, `currentUserHasAnswered`, `isReadyForNext`, `readySince`, `isHost`
  - Add status message when current user has answered
  - Add "All players answered! Waiting for host..." message for non-hosts
  - Add "Time's up! Waiting for host..." message for non-hosts when timer expires
  - Integrate NextQuestionButton component for host users when ready
  - Remove participant answer count display (simplification)
  - _Requirements: 1.3, 1.4, 2.5, 4.1, 4.2, 6.1, 6.2, 6.3, 6.4_

- [x] 7. Update Game page to pass new props
  - Update `resources/js/pages/multiplayer/Game.tsx` to extract new flags from gameState
  - Pass `allPlayersAnswered`, `currentUserHasAnswered`, `isReadyForNext`, `readySince` to MultiplayerQuestion
  - Pass `isHost` flag based on current user vs room host comparison
  - _Requirements: 1.1, 2.2, 4.2_

- [x] 8. Remove obsolete job files and code
  - Delete `app/Jobs/NextQuestionJob.php`
  - Delete `app/Jobs/StartNextQuestionJob.php`
  - Delete `app/Jobs/CalculateRoundScoresJob.php` if it exists
  - Remove job dispatch calls from `MultiplayerGameController::answer()` method
  - Remove job scheduling from `MultiplayerGameService::startGame()` method
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 9. Update answer submission to remove job triggering
  - Modify `MultiplayerGameController::answer()` to remove early progression job dispatch
  - Keep answer recording logic but remove `triggerEarlyProgression()` call
  - Simplify transaction to only create ParticipantAnswer record
  - _Requirements: 4.3, 4.4, 5.2, 5.3_

- [x] 10. Export NextQuestionButton from multiplayer components index
  - Add export for NextQuestionButton in `resources/js/components/multiplayer/index.ts`
  - _Requirements: 2.2_

# Implementation Plan

- [x] 1. Add helper methods to MultiplayerGame model
  - Add `isLastQuestion()` method to check if current question is the last one
  - Add `getNextQuestionIndex()` method to safely get next question index or null
  - _Requirements: 1.1, 2.1, 2.2_

- [x] 2. Fix last question completion in NextQuestionJob
  - [x] 2.1 Update `handle()` method to properly detect last question using `getNextQuestionIndex()`
  - [x] 2.2 Ensure `completeGame()` is called when `getNextQuestionIndex()` returns null
  - [x] 2.3 Update `completeGame()` to set MultiplayerGame status to COMPLETED
  - [x] 2.4 Update `completeGame()` to set Room status to COMPLETED
  - [x] 2.5 Add logging for game completion with game ID and room code
  - _Requirements: 1.1, 1.2, 1.3, 2.2, 2.3_

- [x] 3. Create StartNextQuestionJob to avoid blocking queue
  - [x] 3.1 Create new job class `StartNextQuestionJob` with multiplayerGameId and questionIndex parameters
  - [x] 3.2 Implement `handle()` method to verify game is in SHOWING_RESULTS status
  - [x] 3.3 Update game to ACTIVE status and set current_question_index
  - [x] 3.4 Set question_started_at timestamp
  - [x] 3.5 Schedule NextQuestionJob for timer expiration
  - [x] 3.6 Add logging for question start
  - _Requirements: 5.1, 5.2, 5.5, 7.4_
  - _Note: Execution token removed - status checks provide sufficient race condition protection_

- [x] 4. Update NextQuestionJob to use delayed job instead of sleep
  - [x] 4.1 Remove `sleep(2)` from `startNextQuestion()` method
  - [x] 4.2 Update `startNextQuestion()` to dispatch StartNextQuestionJob with 3-second delay
  - _Requirements: 5.5, 7.1, 7.2, 7.3, 7.4_
  - _Note: Simplified to rely on status-based race condition prevention instead of execution tokens_

- [x] 5. Implement early progression detection in MultiplayerGameService
  - [x] 5.1 Add `allParticipantsAnswered()` method to check if all active participants have answered current question
  - [x] 5.2 Count active participants with PLAYING status
  - [x] 5.3 Count ParticipantAnswer records for current question
  - [x] 5.4 Return true only if answer count equals or exceeds active participant count
  - [x] 5.5 Handle edge case of zero active participants
  - _Requirements: 3.1, 3.2, 4.1, 4.2, 4.3, 4.4_

- [x] 6. Implement early progression trigger in MultiplayerGameService
  - [x] 6.1 Add `triggerEarlyProgression()` method to service
  - [x] 6.2 Dispatch NextQuestionJob immediately (status checks prevent race conditions)
  - [x] 6.3 Add logging for early progression trigger
  - _Requirements: 3.3, 3.4, 6.2, 6.5_
  - _Note: No execution token needed - the ACTIVE status check in NextQuestionJob prevents duplicate processing_

- [x] 7. Update MultiplayerGameController answer method for early progression
  - [x] 7.1 Wrap answer creation in database transaction
  - [x] 7.2 After creating ParticipantAnswer, call `allParticipantsAnswered()`
  - [x] 7.3 If all answered, call `triggerEarlyProgression()`
  - [x] 7.4 Add logging when early progression is triggered
  - _Requirements: 3.1, 3.2, 3.3, 3.5_

- [x] 8. Improve error handling and logging in NextQuestionJob
  - [x] 8.1 Add detailed logging at job execution start with game ID and status
  - [x] 8.2 Add logging when game not found with game ID
  - [x] 8.3 Add logging when game not active with current status
  - [x] 8.4 Add logging for score calculation completion
  - [x] 8.5 Add logging for results display transition
  - _Requirements: 5.1, 5.2, 5.4, 5.5_

- [x] 9. Update frontend Game component to handle COMPLETED status
  - [x] 9.1 Check for COMPLETED status in polling response
  - [x] 9.2 Redirect to final results page when status is COMPLETED
  - [x] 9.3 Show loading state during transition
  - _Requirements: 1.4, 1.5, 2.4_

- [x] 10. Verify MultiplayerGameController provides final standings data
  - [x] 10.1 Review `show()` method to ensure it handles COMPLETED status
  - [x] 10.2 Ensure final standings data is included in response for completed games
  - [x] 10.3 Add redirect to results page if game is completed
  - _Requirements: 1.2, 1.3, 2.3_

- [ ]* 11. Add comprehensive logging for debugging
  - [ ]* 11.1 Add log entry when all participants have answered
  - [ ]* 11.2 Add log entry for job scheduling with delay times
  - _Requirements: 5.5_

- [ ]* 12. Write unit tests for new functionality
  - [ ]* 12.1 Test `isLastQuestion()` returns true on last question and false otherwise
  - [ ]* 12.2 Test `getNextQuestionIndex()` returns null on last question
  - [ ]* 12.3 Test `allParticipantsAnswered()` with various participant/answer combinations
  - [ ]* 12.4 Test status-based race condition prevention in NextQuestionJob
  - _Requirements: 4.1, 4.2, 4.3, 5.3, 6.3_

- [ ]* 13. Write integration tests for complete flows
  - [ ]* 13.1 Test complete game flow from start to final results
  - [ ]* 13.2 Test early progression when all players answer before timer
  - [ ]* 13.3 Test last question completion with timer expiration
  - [ ]* 13.4 Test last question completion with early progression
  - [ ]* 13.5 Test mixed scenario: some players answer, some timeout
  - _Requirements: 3.5, 7.5_

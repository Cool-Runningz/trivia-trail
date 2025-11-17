# Requirements Document

## Introduction

This specification addresses three critical bugs in the multiplayer trivia game flow:

1. **Last Question Transition Bug**: When the timer expires on the last question, the game does not transition to the results screen and remains stuck on the question
2. **Early Completion Optimization**: When all players have submitted their answers, the game waits unnecessarily for the timer to expire instead of progressing immediately
3. **Question Progression Reliability**: Occasionally, when the timer expires, the game fails to progress to the next question, causing the game to hang

## Glossary

- **MultiplayerGame**: The backend model representing an active multiplayer trivia game session
- **NextQuestionJob**: A Laravel queued job that handles automatic progression between questions when timers expire
- **MultiplayerGameController**: The backend controller that handles the multiplayer game display, answer submission, and state transitions
- **MultiplayerGameService**: The service layer that encapsulates multiplayer game business logic
- **Game Component**: The React frontend component that displays the active multiplayer game
- **Final Standings**: The results screen showing the final leaderboard and game statistics after completion
- **ParticipantAnswer**: The model representing a player's answer to a specific question in a multiplayer game
- **RoomParticipant**: The model representing a player's participation in a game room

## Requirements

### Requirement 1: Last Question Completion

**User Story:** As a player, I want to automatically see the final results when the last question's timer expires, so that I know the game has ended and can view the final standings.

#### Acceptance Criteria

1. WHEN the timer expires on the last question, THE NextQuestionJob SHALL detect that no more questions remain
2. WHEN no more questions remain after showing results, THE NextQuestionJob SHALL invoke the completeGame method
3. WHEN completeGame is invoked, THE MultiplayerGame SHALL update its status to COMPLETED
4. WHEN the MultiplayerGame status is COMPLETED, THE MultiplayerGameController SHALL provide final standings data to the frontend
5. WHEN the frontend receives completed game status, THE Game Component SHALL display the Final Standings component

### Requirement 2: Last Question Transition Logic

**User Story:** As a player, I want the game to handle the last question completion consistently whether I answer or let the timer expire, so that the experience is predictable.

#### Acceptance Criteria

1. WHEN NextQuestionJob calculates the next question index, THE NextQuestionJob SHALL compare it against the total number of questions
2. IF the next question index is greater than or equal to total questions, THEN THE NextQuestionJob SHALL complete the game instead of scheduling another question
3. WHEN the game is marked as completed, THE MultiplayerGame SHALL persist the COMPLETED status to the database
4. WHEN polling detects a completed game status, THE Game Component SHALL render the final standings without requiring manual navigation

### Requirement 3: Early Question Progression

**User Story:** As a player, I want the game to automatically progress to the next question when all players have submitted their answers, so that I don't have to wait unnecessarily for the timer to expire.

#### Acceptance Criteria

1. WHEN a player submits an answer, THE MultiplayerGameController SHALL check if all active participants have answered the current question
2. WHEN all active participants have answered the current question, THE MultiplayerGameController SHALL trigger early progression to the next question
3. WHEN early progression is triggered, THE NextQuestionJob SHALL be dispatched immediately without waiting for the timer
4. WHEN early progression occurs, THE NextQuestionJob SHALL cancel any pending timer-based progression for the same question
5. WHEN early progression completes, THE Game Component SHALL display the results and transition to the next question smoothly

### Requirement 4: All Players Answered Detection

**User Story:** As a developer, I want the system to accurately detect when all players have answered, so that early progression works reliably.

#### Acceptance Criteria

1. WHEN checking for completion, THE MultiplayerGameService SHALL count ParticipantAnswer records for the current question
2. WHEN checking for completion, THE MultiplayerGameService SHALL count active RoomParticipant records
3. WHEN the count of answers equals the count of active participants, THE MultiplayerGameService SHALL return true for all answered
4. WHEN a participant is marked as inactive or disconnected, THE MultiplayerGameService SHALL exclude them from the active participant count

### Requirement 5: Job Reliability and Idempotency

**User Story:** As a developer, I want the NextQuestionJob to execute reliably and handle edge cases, so that question progression never fails.

#### Acceptance Criteria

1. WHEN NextQuestionJob executes, THE NextQuestionJob SHALL verify the MultiplayerGame still exists before processing
2. WHEN NextQuestionJob executes, THE NextQuestionJob SHALL verify the game status is ACTIVE before processing
3. WHEN NextQuestionJob executes multiple times for the same question, THE NextQuestionJob SHALL handle duplicate executions gracefully
4. IF the MultiplayerGame is not found or not active, THEN THE NextQuestionJob SHALL log the condition and exit without error
5. WHEN NextQuestionJob encounters an error, THE NextQuestionJob SHALL log detailed diagnostic information including game ID and current state

### Requirement 6: Job Scheduling and Cancellation

**User Story:** As a developer, I want to prevent duplicate job executions when early progression occurs, so that the game state remains consistent.

#### Acceptance Criteria

1. WHEN a NextQuestionJob is scheduled, THE NextQuestionJob SHALL store a unique identifier for the scheduled job
2. WHEN early progression is triggered, THE MultiplayerGameService SHALL attempt to cancel the pending timer-based NextQuestionJob
3. WHEN a NextQuestionJob executes, THE NextQuestionJob SHALL check if it has been superseded by an earlier execution
4. IF a job has been superseded, THEN THE NextQuestionJob SHALL exit without making state changes
5. WHEN transitioning to the next question, THE NextQuestionJob SHALL schedule only one new NextQuestionJob for the subsequent question

### Requirement 7: Results Display Timing

**User Story:** As a player, I want to see the results of each question briefly before moving to the next one, so that I can understand how I performed.

#### Acceptance Criteria

1. WHEN transitioning between questions, THE NextQuestionJob SHALL set the status to SHOWING_RESULTS
2. WHILE the status is SHOWING_RESULTS, THE Game Component SHALL display the correct answer and participant scores
3. WHEN showing results, THE NextQuestionJob SHALL wait for a configured duration before proceeding
4. WHEN the results display duration expires, THE NextQuestionJob SHALL transition to either the next question or final standings
5. WHEN early progression occurs, THE NextQuestionJob SHALL still show results for the configured duration before proceeding

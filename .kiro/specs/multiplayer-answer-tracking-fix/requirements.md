# Requirements Document

## Introduction

This specification addresses critical bugs in the multiplayer trivia game's answer tracking and question progression system. Currently, the system fails to properly track when participants have answered questions, and the complex auto-progression logic creates race conditions and unpredictable behavior. This specification simplifies the progression model to use host-controlled advancement with automatic fallback, eliminating job queue complexity while maintaining a smooth user experience.

## Glossary

- **MultiplayerGame**: The backend model representing an active multiplayer trivia game session
- **RoomParticipant**: A user who has joined a game room
- **ParticipantAnswer**: A record of a participant's answer to a specific question
- **Frontend Polling**: The mechanism by which the React frontend requests updated game state from the backend
- **all_players_answered**: A boolean flag indicating whether all active participants have answered the current question
- **current_user_has_answered**: A boolean flag indicating whether the current user has answered the current question
- **Host**: The user who created the game room and has control over game progression
- **Ready State**: The game state when the timer expires or all players have answered, waiting for host to advance
- **Auto-Advance**: Automatic progression to the next question after 5 seconds if host doesn't manually advance

## Requirements

### Requirement 1: All-Players-Answered Detection

**User Story:** As a game host, I want to know when all players have answered the current question, so that I can advance the game without waiting for the timer.

#### Acceptance Criteria

1. WHEN THE Frontend_Polling requests game state, THE MultiplayerGame SHALL include an all_players_answered boolean flag
2. WHEN all active participants have submitted answers for the current question, THE all_players_answered flag SHALL be true
3. WHEN all_players_answered is true, THE Frontend SHALL display "All players answered!" message to all participants
4. WHEN all_players_answered is true AND user is host, THE Frontend SHALL enable the "Next Question" button

### Requirement 2: Host-Controlled Question Progression

**User Story:** As a game host, I want to control when the game moves to the next question after everyone has answered or time expires, so that I can manage the game's pacing.

#### Acceptance Criteria

1. WHEN the timer reaches 0 seconds OR all active participants have answered, THE MultiplayerGame SHALL enter a "ready_for_next" state
2. WHEN THE MultiplayerGame enters "ready_for_next" state, THE Frontend SHALL display a "Next Question" button to the host
3. WHEN the host clicks "Next Question", THE MultiplayerGame SHALL calculate scores and immediately transition to the next question
4. WHEN THE MultiplayerGame has been in "ready_for_next" state for 5 seconds, THE System SHALL automatically advance to the next question
5. WHERE the host is not the current user, THE Frontend SHALL display "Waiting for host to continue..." message
6. IF there are no more questions remaining, THEN THE MultiplayerGame SHALL transition to final results instead of next question

### Requirement 3: Simplified Timer Management

**User Story:** As a player, I want to see a countdown timer that accurately reflects time remaining, so that I know how long I have to answer.

#### Acceptance Criteria

1. WHEN a question starts, THE MultiplayerGame SHALL record the question_started_at timestamp
2. WHEN THE Frontend polls for game state, THE MultiplayerGame SHALL calculate time_remaining as (time_per_question - elapsed_seconds)
3. WHEN time_remaining reaches 0, THE MultiplayerGame SHALL transition to "ready_for_next" state
4. WHEN in "ready_for_next" state, THE Frontend SHALL display time_remaining as 0

### Requirement 4: Current User Answer Status

**User Story:** As a player, I want to see confirmation that I've submitted my answer, so that I know the system received it.

#### Acceptance Criteria

1. WHEN a participant submits an answer, THE Frontend SHALL display "Answer submitted! Waiting for others..." message
2. WHEN THE Frontend polls game state, THE MultiplayerGame SHALL include a current_user_has_answered boolean flag
3. WHEN the current user has answered, THE Frontend SHALL disable the answer submission interface
4. WHEN a new question starts, THE current_user_has_answered flag SHALL reset to false

### Requirement 5: Elimination of Background Jobs

**User Story:** As a developer, I need the system to avoid complex job scheduling and race conditions, so that the game flow is predictable and debuggable.

#### Acceptance Criteria

1. THE MultiplayerGame SHALL NOT use NextQuestionJob for automatic progression
2. THE MultiplayerGame SHALL NOT use early progression job dispatching
3. WHEN advancing questions, THE System SHALL use synchronous controller actions triggered by host or auto-advance
4. WHEN calculating scores, THE System SHALL use synchronous service methods instead of queued jobs

### Requirement 6: Visual Feedback for Game State

**User Story:** As a player, I want clear visual feedback about the game state, so that I understand what's happening and what to expect next.

#### Acceptance Criteria

1. WHEN all players have answered, THE Frontend SHALL display "All players answered! Waiting for host..." message to non-host participants
2. WHEN all players have answered AND user is host, THE Frontend SHALL display "All players answered! Click Next Question or wait for auto-advance" message
3. WHEN timer reaches 0, THE Frontend SHALL display "Time's up! Waiting for host..." message to non-host participants
4. WHEN timer reaches 0 AND user is host, THE Frontend SHALL display "Time's up! Click Next Question or wait for auto-advance" message

### Requirement 7: Immediate Question Transitions

**User Story:** As a player, I want the game to move quickly between questions without unnecessary delays, so that the game feels fast-paced and engaging.

#### Acceptance Criteria

1. WHEN the host advances to the next question, THE MultiplayerGame SHALL transition immediately without showing round results
2. WHEN transitioning to a new question, THE MultiplayerGame SHALL increment current_question_index and set question_started_at to now
3. WHEN THE Frontend detects a new question_index, THE Frontend SHALL immediately display the new question
4. THE System SHALL NOT introduce artificial delays between questions

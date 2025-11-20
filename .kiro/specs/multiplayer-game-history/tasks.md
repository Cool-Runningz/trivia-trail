# Implementation Plan

- [x] 1. Add database indexes for history queries
  - Create migration to add composite index on `game_rooms(status, updated_at)`
  - Add index on `room_participants(user_id, room_id)` if not exists
  - _Requirements: 1.1, 5.2_

- [x] 2. Implement backend history query logic
  - [x] 2.1 Add `getGameHistory()` method to LobbyController
    - Query completed games from past 7 days where user was participant
    - Eager load participants, multiplayer game, and settings relationships
    - Order by completion date descending, limit to 20 results
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 5.3_

  - [x] 2.2 Add `formatHistoryEntry()` helper method
    - Calculate user's position/rank in the game
    - Format participant preview data (first 3-4 participants)
    - Extract game metadata (difficulty, category, question count)
    - _Requirements: 1.4, 4.1, 4.2, 4.3, 4.5_

  - [x] 2.3 Update LobbyController index method
    - Call `getGameHistory()` and pass to Inertia view
    - Handle cases where game data is incomplete
    - _Requirements: 1.1, 1.5_

- [x] 3. Add authorization for viewing historical games
  - [x] 3.1 Create or update GameRoomPolicy
    - Add `view()` method to check if user was a participant
    - Allow viewing completed games for participants
    - _Requirements: 3.1_

  - [x] 3.2 Update MultiplayerGameController results method
    - Add authorization check using policy
    - Handle authorization failures with redirect and error message
    - _Requirements: 3.1_

- [x] 4. Create frontend history components
  - [x] 4.1 Create GameHistory component
    - Accept gameHistory prop array
    - Render section header with "Last 7 days" label
    - Map history entries to HistoryEntry components
    - Show HistoryEmptyState when no games exist
    - _Requirements: 1.1, 6.1, 6.2, 6.3_

  - [x] 4.2 Create HistoryEntry component
    - Show completion time using relative format (e.g., "2 days ago")
    - Show metadata in format "Room code: HVAC12 | Category: Sports"
       - If there is no category since it's optional then just show room code
    - Make entire card clickable link to results page
    - Add hover effects for better UX
    - _Requirements: 1.4, 3.1, 4.1, 4.2, 4.5, 6.4_

  - [x] 4.4 Create HistoryEmptyState component
    - Display empty state card with dashed border
    - Show history icon and helpful message
    - _Requirements: 6.3_

- [x] 5. Integrate history into lobby page
  - [x] 5.1 Update Lobby page TypeScript interface
    - Add gameHistory prop to LobbyProps interface
    - Define HistoryEntry and ParticipantPreview types
    - _Requirements: 1.1_

  - [x] 5.2 Add GameHistory section to Lobby page
    - Render GameHistory component below RoomBrowser
    - Add proper spacing and section separation
    - Ensure responsive layout on mobile devices
    - _Requirements: 6.1, 6.2, 6.5_

- [ ] 6. Add helper utilities
  - [ ] 6.2 Add date formatting utility
    - Use date-fns formatDistanceToNow for relative dates
    - Handle edge cases for very recent games
    - _Requirements: 1.3_

- [ ] 7. Implement caching for performance
  - [ ] 7.1 Add cache layer to getGameHistory method
    - Cache history results per user with 5-minute TTL
    - Use cache key format: "game_history:{user_id}"
    - _Requirements: 5.5_

  - [ ] 7.2 Add cache invalidation on game completion
    - Clear cache for all participants when game completes
    - Update RoomService or MultiplayerGameService completion logic
    - _Requirements: 5.5_

- [ ] 8. Add error handling
  - [ ] 8.1 Handle missing game data in formatHistoryEntry
    - Check for null multiplayerGame or game relationships
    - Log warnings for incomplete data
    - Return null for invalid entries and filter them out
    - _Requirements: 1.1_

  - [ ] 8.2 Add frontend error handling
    - Handle null/undefined gameHistory prop gracefully
    - Add try-catch for navigation errors
    - Display user-friendly error messages
    - _Requirements: 6.3_

- [ ] 9. Update TypeScript type definitions
  - Add HistoryEntry interface to types/index.d.ts
  - Add ParticipantPreview interface
  - Export types for use across components
  - _Requirements: 1.4, 4.1_

- [ ]* 10. Add tests for history feature
  - [ ]* 10.1 Write backend unit tests
    - Test getGameHistory filters by date and status correctly
    - Test formatHistoryEntry calculates position accurately
    - Test that cancelled games are excluded
    - Test that only user's games are returned
    - _Requirements: 1.1, 1.2, 2.1, 2.2_

  - [ ]* 10.2 Write frontend component tests
    - Test HistoryEntry displays correct position badges
    - Test ParticipantAvatars shows correct count
    - Test HistoryEmptyState renders when no games
    - Test navigation to results page
    - _Requirements: 3.1, 4.1, 4.2, 6.3_

  - [ ]* 10.3 Write integration tests
    - Test full flow from lobby to history to results
    - Test authorization for viewing historical games
    - Test that history updates after game completion
    - _Requirements: 3.1, 3.2, 3.3_

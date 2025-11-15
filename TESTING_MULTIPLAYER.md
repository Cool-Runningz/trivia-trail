# Testing Multiplayer Trivia

## Setup Steps

### 1. Run Database Migrations
Make sure all multiplayer tables are created:
```bash
php artisan migrate
```

### 2. Start the Development Server
```bash
composer run dev
```

This will start:
- Laravel development server (port 8000)
- Vite dev server for frontend assets
- Queue worker for background jobs

### 3. Access the Application
Open your browser to: `http://localhost:8000`

## Testing the Multiplayer Feature

### Dashboard
The dashboard now features a game mode selection screen with two main options:
- **Single Player** - Play trivia at your own pace
- **Multiplayer** - Compete with friends in real-time (✨ NEW!)

You can also access these modes from the sidebar navigation.

### Multiplayer Flow

#### 1. Lobby Page
Click "Enter Multiplayer Lobby" on the dashboard (or "Multiplayer" in the sidebar) to access the lobby where you can:
- **Create Room** - Start a new multiplayer game room
- **Join Room** - Enter a 6-character room code to join an existing room
- **Browse Rooms** - See available public rooms

#### 2. Create a Room
- Click "Create Room"
- Configure game settings:
  - Category (optional)
  - Difficulty (easy/medium/hard)
  - Number of questions
  - Max players
- Click "Create" to generate a room with a 6-character code

#### 3. Room Lobby
After creating or joining a room:
- See the room code prominently displayed
- View participant list with status indicators
- Host can start the game when ready (minimum 2 players)
- Real-time polling updates participant list every 3 seconds

#### 4. Active Game
Once the host starts the game:
- 30-second countdown timer for each question
- Live "✓ Answered" indicators show who has submitted
- Answer submission locks in your choice
- Automatic progression when timer expires

#### 5. Round Results
After each question:
- See the correct answer highlighted
- View leaderboard with score changes
- Dramatic animations reveal results
- Auto-progress to next question after 5 seconds

#### 6. Final Results
After all questions:
- Winner celebration with trophy animation
- Full leaderboard with final standings
- Game statistics
- Options to play again or return to lobby

## Features Implemented

### Real-Time Updates (Polling)
- ✅ Dynamic polling intervals based on game phase
- ✅ Connection status indicators
- ✅ Automatic retry with exponential backoff
- ✅ Graceful error handling

### UI Components
- ✅ Multiplayer question with shared timer
- ✅ Live answer status for all participants
- ✅ Round results with leaderboard
- ✅ Final standings with animations
- ✅ Connection status alerts
- ✅ Error boundary for crash recovery

### Backend
- ✅ Room management (create, join, leave)
- ✅ Game flow orchestration
- ✅ Answer validation and scoring
- ✅ Automatic question progression
- ✅ Timer synchronization

## Testing with Multiple Players

To test the multiplayer experience properly, you'll need multiple browser sessions:

### Option 1: Multiple Browser Windows
1. Open the app in Chrome
2. Open the app in Firefox (or Chrome Incognito)
3. Create a room in one browser
4. Copy the room code
5. Join with the same code in the other browser

### Option 2: Multiple User Accounts
1. Register 2-3 different user accounts
2. Log in with different accounts in different browsers
3. Create and join rooms to test the multiplayer flow

## What to Test

### Room Management
- [ ] Create a room with different settings
- [ ] Join a room with a valid code
- [ ] Try joining with an invalid code (should show error)
- [ ] Leave a room before game starts
- [ ] Host starts game with 2+ players

### Game Flow
- [ ] Answer questions within the 30-second timer
- [ ] See other players' "✓ Answered" status update in real-time
- [ ] Timer automatically progresses when it hits 0
- [ ] Round results show correct answers and leaderboard
- [ ] Final results display after last question

### Real-Time Features
- [ ] Participant list updates when someone joins/leaves
- [ ] Timer counts down synchronously for all players
- [ ] Answer status indicators update immediately
- [ ] Connection status shows when network issues occur

### Error Handling
- [ ] Network disconnection shows reconnecting status
- [ ] Automatic retry after connection loss
- [ ] Error boundary catches component crashes
- [ ] Clear error messages for invalid actions

## Known Limitations

Since this is task 6 of 8, some features are not yet complete:

- **Task 7** (Integration) - Not all navigation is wired up
- **Task 8** (Security & Performance) - Rate limiting and caching not fully implemented
- **Optional Tests** - Unit and integration tests are marked optional

## Troubleshooting

### "Route not found" errors
Run: `php artisan route:list` to verify multiplayer routes exist

### Frontend not updating
1. Stop the dev server
2. Run: `npm run build`
3. Restart: `composer run dev`

### Database errors
Run: `php artisan migrate:fresh` (⚠️ This will delete all data!)

### Queue jobs not running
Make sure `composer run dev` is running (includes queue worker)

## Next Steps

After testing, you can continue with:
- **Task 7**: Complete system integration
- **Task 8**: Add security and performance optimizations
- **Optional**: Write tests for the implemented features

Enjoy testing the multiplayer trivia game! 🎮

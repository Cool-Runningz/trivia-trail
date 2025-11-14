# Task 1 - Set up database schema and core models

## Part 1: PHP Enums (The Status Types)
**What are Enums?**
Enums are a way to define a fixed set of possible values. Instead of using strings like 'waiting', 'active', etc. throughout your code, you define them once in an enum.

Example: `RoomStatus.php`

```php
enum RoomStatus: string
{
    case WAITING = 'waiting';
    case STARTING = 'starting';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
```

**Why use enums?**
- Type safety: Your IDE will autocomplete and catch typos
- Single source of truth: Change the value in one place
- Better code: RoomStatus::ACTIVE is clearer than 'active'

**How you'll use it:**
```php
// Instead of this:
$room->status = 'active';

// You write this:
$room->status = RoomStatus::ACTIVE;

// And you can check status like:
if ($room->status === RoomStatus::WAITING) {
    // do something
}
```
---

## Part 2: Database Migrations (The Tables)
**What are Migrations?**
Migrations are like version control for your database. They let you define tables and columns in PHP code, which Laravel then converts to SQL.

Example: `create_game_rooms_table.php`

Let me break down each part:
```php
Schema::create('game_rooms', function (Blueprint $table) {
    $table->id();  // Creates an auto-incrementing primary key 'id'
    
    $table->string('room_code', 6)->unique();
    // Creates a VARCHAR(6) column that must be unique
    // This is the code like "ABC123" that players use to join
    
    $table->foreignId('host_user_id')->constrained('users')->onDelete('cascade');
    // Creates a foreign key linking to the users table
    // 'constrained' adds the foreign key constraint
    // 'onDelete cascade' means if the user is deleted, delete this room too
    
    $table->integer('max_players')->default(8);
    // Integer column with a default value of 8
    
    $table->enum('status', ['waiting', 'starting', 'active', 'completed', 'cancelled'])
          ->default('waiting');
    // Enum column - database will only accept these exact values
    
    $table->timestamp('expires_at')->nullable();
    // Timestamp that can be null (optional)
    
    $table->timestamps();
    // Adds 'created_at' and 'updated_at' columns automatically
    
    // Indexes for faster queries
    $table->index('room_code');    // Fast lookups by room code
    $table->index('status');       // Fast filtering by status
    $table->index('host_user_id'); // Fast lookups by host
});
```
**Why indexes?** Indexes make database queries faster. Think of them like the index in a book - instead of reading every page to find "Chapter 5", you look at the index and jump right to it.

---

## Part 3: Eloquent Models (The PHP Classes)
**What are Models?**
Models are PHP classes that represent database tables. They let you interact with your database using PHP objects instead of writing SQL.

Let me walk through the GameRoom model in detail:
```php
class GameRoom extends Model
{
    // FILLABLE: Which columns can be mass-assigned
    protected $fillable = [
        'room_code',
        'host_user_id',
        'max_players',
        'current_players',
        'status',
        'expires_at',
    ];
```
**What is $fillable?** This is a security feature. It says "these are the only columns that can be set using mass assignment." This prevents users from injecting unexpected data.

```php
// With fillable, you can do:
GameRoom::create([
    'room_code' => 'ABC123',
    'host_user_id' => 1,
    'max_players' => 8,
]);

// Without fillable, you'd have to do:
$room = new GameRoom();
$room->room_code = 'ABC123';
$room->host_user_id = 1;
$room->save();
```
### Casts: Converting Data Types
```php
protected $casts = [
    'status' => RoomStatus::class,  // Convert to enum
    'expires_at' => 'datetime',     // Convert to Carbon date object
];
```
**What are casts?** Casts automatically convert database values to PHP types when you read/write them.

```php
// Without cast: $room->status is a string 'waiting'
// With cast: $room->status is RoomStatus::WAITING (an enum)

// Without cast: $room->expires_at is a string '2025-11-13 10:00:00'
// With cast: $room->expires_at is a Carbon object with methods like:
$room->expires_at->addHours(2);
$room->expires_at->isPast();
```
### Relationships: Connecting Models
This is where Laravel really shines! Relationships let you easily access related data.
```php
public function host(): BelongsTo
{
    return $this->belongsTo(User::class, 'host_user_id');
}
```
**What does this mean?**
- A GameRoom "belongs to" one User (the host)
- The foreign key is host_user_id

**How you use it:**
```php
$room = GameRoom::find(1);
$host = $room->host;  // Automatically fetches the User!

echo $host->name;  // Access the host's name
```

**Other relationship types:**
```php
// HasMany: One room has many participants
public function participants(): HasMany
{
    return $this->hasMany(RoomParticipant::class, 'room_id');
}

// Usage:
$room->participants;  // Collection of all participants
$room->participants()->count();  // Count participants
$room->participants()->where('status', 'ready')->get();  // Filter participants
// HasOne: One room has one settings record
public function settings(): HasOne
{
    return $this->hasOne(RoomSettings::class, 'room_id');
}

// Usage:
$room->settings->time_per_question;  // Access settings directly
```
### Scopes: Reusable Query Filters
Scopes are methods that let you create reusable query filters. They're super handy!
```php
public function scopeActive($query)
{
    return $query->whereIn('status', [
        RoomStatus::WAITING, 
        RoomStatus::STARTING, 
        RoomStatus::ACTIVE
    ]);
}
```
**How you use scopes:**
```php
// Without scope, you'd write:
$rooms = GameRoom::whereIn('status', ['waiting', 'starting', 'active'])->get();

// With scope, you write:
$rooms = GameRoom::active()->get();

// You can chain scopes:
$rooms = GameRoom::active()->notExpired()->get();

// Or combine with other queries:
$rooms = GameRoom::active()
    ->where('max_players', '>', 4)
    ->orderBy('created_at', 'desc')
    ->get();
```
**Another scope example:**
```php
public function scopeByStatus($query, RoomStatus $status)
{
    return $query->where('status', $status);
}

// Usage:
$waitingRooms = GameRoom::byStatus(RoomStatus::WAITING)->get();
```
---
## Part 4: The RoomParticipant Model
This model has some interesting helper methods:

```php
public function addScore(int $points): void
{
    $this->increment('score', $points);
}
```
**What does `increment` do?** It's a database-level operation that's atomic (safe for concurrent updates):

```php
// Instead of:
$participant->score = $participant->score + 100;
$participant->save();

// You do:
$participant->addScore(100);

// This runs: UPDATE room_participants SET score = score + 100 WHERE id = ?
```

**Boolean helper methods:**
```php
public function isHost(): bool
{
    return $this->room->host_user_id === $this->user_id;
}

// Usage:
if ($participant->isHost()) {
    // Allow starting the game
}
```
---
## Part 5: The MultiplayerGame Model
This model has some cool helper methods for game logic:
```php
public function getCurrentQuestion(): ?array
{
    $questions = $this->game->questions ?? [];
    return $questions[$this->current_question_index] ?? null;
}
```
**What's the `?array` return type?** The `?` means "nullable" - this method can return an array OR null if there's no question.

**What's the `??` operator?** It's the "null coalescing operator":
```php
$questions = $this->game->questions ?? [];
// If questions is null, use [] instead

return $questions[$this->current_question_index] ?? null;
// If that index doesn't exist, return null
```

**Timer calculation:**
```php
public function getTimeRemaining(): int
{
    if (!$this->question_started_at) {
        return 0;
    }

    $timePerQuestion = $this->room->settings->time_per_question ?? 30;
    $elapsed = now()->diffInSeconds($this->question_started_at);
    
    return max(0, $timePerQuestion - $elapsed);
}
```
This calculates how much time is left on the current question by:
1. Getting when the question started
2. Calculating how many seconds have passed
3. Subtracting from the total time allowed
4. Using max(0, ...) to never return negative numbers
---

## Part 6: Unique Constraints and Indexes
In the `room_participants` migration:
```php
$table->unique(['room_id', 'user_id']);
```
**What does this do?** It ensures a user can only join a room once. The database will reject any attempt to insert a duplicate combination.
```php
$table->index(['room_id', 'status']);
```
**Composite index**: This makes queries that filter by both room_id AND status very fast:
```php
// This query will be super fast:
RoomParticipant::where('room_id', 1)
    ->where('status', 'ready')
    ->get();
```
---

## Putting It All Together: Real Usage Example
Here's how you might use all of this in a controller:
```php
// Create a new room
$room = GameRoom::create([
    'room_code' => 'ABC123',
    'host_user_id' => auth()->id(),
    'max_players' => 8,
    'status' => RoomStatus::WAITING,
]);

// Create settings for the room
$room->settings()->create([
    'time_per_question' => 30,
    'difficulty' => DifficultyLevel::MEDIUM,
    'total_questions' => 10,
]);

// Add the host as a participant
$room->participants()->create([
    'user_id' => auth()->id(),
    'status' => ParticipantStatus::JOINED,
]);

// Later, find active rooms
$activeRooms = GameRoom::active()
    ->notExpired()
    ->with(['host', 'participants', 'settings'])  // Eager load relationships
    ->get();

// Check if a participant is the host
$participant = RoomParticipant::find(1);
if ($participant->isHost()) {
    // Start the game
}

// Add score to a participant
$participant->addScore(100);

// Get leaderboard
$leaderboard = RoomParticipant::inRoom($roomId)
    ->connected()
    ->orderByScore('desc')
    ->get();
```
---
## Key Laravel Concepts Summary
1. **Enums**: Fixed set of values with type safety
2. **Migrations**: Version control for database structure
3. **Models**: PHP classes representing database tables
4. **Fillable**: Security feature for mass assignment
5. **Casts**: Automatic type conversion
6. **Relationships**: Easy access to related data (belongsTo, hasMany, hasOne)
7. **Scopes**: Reusable query filters
8. **Helper Methods**: Custom methods on models for business logic
9. **Indexes**: Speed up database queries
10. **Foreign Keys**: Maintain data integrity between tables

---

# Task 2 - Implement room management services and utilities
1. **RoomCodeGenerator** (`app/Utilities/RoomCodeGenerator.php`)
- Generates unique 6-character alphanumeric room codes
- Uses secure random generation excluding similar-looking characters (0, O, I, 1)
- Validates room code format and checks for existence
- Provides formatting utilities for display (e.g., "ABC-123")

2. **RoomValidator** (`app/Utilities/RoomValidator.php`)
- Room capacity checking before allowing joins
- Rate limiting for room creation (max 5 rooms per hour per user)
- Security validation for room access and modifications
- Host privilege verification
- Room settings validation (player count, time limits, question count)

3. **RoomService** (`app/Services/RoomService.php`)
- Complete room lifecycle management:
   - `createRoom()` - Creates new rooms with settings and adds host as first participant
   - `joinRoom()` - Validates and adds participants to existing rooms
   - `leaveRoom()` - Handles participant removal with automatic host transfer
   - `updateSettings()` - Host-only room configuration updates
   - `cleanupRoom()` - Removes rooms and all related data
   - `cleanupExpiredRooms()` - Batch cleanup of expired rooms
- Participant management with status tracking
- Automatic host transfer when host leaves during waiting phase
- Room cancellation when host leaves during active game
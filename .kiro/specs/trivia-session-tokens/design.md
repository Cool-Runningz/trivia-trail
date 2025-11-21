# Design Document

## Overview

The session token enhancement extends the existing OpenTriviaService to integrate with the Open Trivia Database API's session token system. This prevents duplicate questions by leveraging the API's built-in tracking mechanism. The implementation focuses on minimal changes to the existing service while adding robust token management and fallback capabilities.

The design maintains the current hybrid approach where questions are fetched from the API and stored temporarily in game sessions, but now uses session tokens to ensure uniqueness across multiple games for the same user.

## Architecture

### Enhanced System Components

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Game Flow     │    │ OpenTriviaService│    │ Open Trivia DB  │
│                 │    │ (Enhanced)       │    │                 │
│ - GameSetup     │◄──►│ + Token Mgmt     │◄──►│ Token API       │
│ - PlayGame      │    │ + Question Fetch │    │ Questions API   │
│ - GameResults   │    │ + Fallback Logic │    │                 │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                       │
         │                       ▼
         │              ┌──────────────────┐
         │              │ Token Storage    │
         │              │                  │
         └──────────────│ - Cache/Session  │
                        │ - User-based     │
                        │ - TTL Management │
                        └──────────────────┘
```

### Token Lifecycle

1. **Token Request**: Service requests token from API when needed
2. **Token Storage**: Token stored in cache with user association
3. **Question Fetching**: Token appended to question API calls
4. **Token Exhaustion**: API returns code 4, service resets token
5. **Token Expiry**: 6-hour inactivity causes automatic token refresh
6. **Fallback**: On any token failure, service continues without tokens

## Components and Interfaces

### Enhanced OpenTriviaService

The existing service will be extended with token management methods:

```php
class OpenTriviaService
{
    // Existing methods remain unchanged
    public function getCategories(): array
    public function getQuestions(array $params): array
    
    // New token management methods
    private function getSessionToken(?int $userId = null): ?string
    private function requestNewToken(): ?string
    private function resetToken(string $token): bool
    private function storeToken(string $token, ?int $userId = null): void
    private function clearToken(?int $userId = null): void
    private function isTokenExhausted(array $response): bool
    private function handleTokenExhaustion(string $token, ?int $userId = null): ?string
}
```

### Token Storage Strategy

```php
// Cache-based storage with user association
$cacheKey = $userId ? "trivia_token_user_{$userId}" : "trivia_token_guest_" . session()->getId();
$ttl = 5 * 60 * 60; // 5 hours (less than API's 6-hour expiry)
```

### API Integration Points

#### Token Request Endpoint
```
GET https://opentdb.com/api_token.php?command=request
Response: {"response_code":0,"response_message":"Token Generated Successfully!","token":"TOKEN_HERE"}
```

#### Token Reset Endpoint
```
GET https://opentdb.com/api_token.php?command=reset&token=TOKEN_HERE
Response: {"response_code":0,"response_message":"Token has been reset and all question have been reset."}
```

#### Enhanced Questions Endpoint
```
GET https://opentdb.com/api.php?amount=10&category=9&difficulty=easy&token=TOKEN_HERE
```

## Data Models

### Token Storage Structure

```php
// Cached token data structure
[
    'token' => 'string',
    'created_at' => 'timestamp',
    'user_id' => 'int|null',
    'last_used' => 'timestamp'
]
```

### Enhanced API Response Handling

```php
// Response codes to handle
const TOKEN_SUCCESS = 0;
const NO_RESULTS = 1;
const INVALID_PARAMETER = 2;
const TOKEN_NOT_FOUND = 3;
const TOKEN_EMPTY = 4; // Key response code for exhaustion
const RATE_LIMIT = 5;
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Token Request Consistency
*For any* token request operation, when the API is available, the service should successfully obtain a valid token or return null with appropriate error handling
**Validates: Requirements 1.1**

### Property 2: Token Parameter Inclusion
*For any* question request with an available token, the API URL should include the token parameter in the correct format
**Validates: Requirements 1.2**

### Property 3: Token Persistence
*For any* stored session token, subsequent question requests should reuse the same token until it's exhausted or expired
**Validates: Requirements 1.4, 1.5**

### Property 4: Exhaustion Detection
*For any* API response with code 4, the service should correctly identify token exhaustion and trigger reset logic
**Validates: Requirements 2.1**

### Property 5: Token Reset Flow
*For any* exhausted token, the service should attempt reset and continue with the refreshed token or fall back gracefully
**Validates: Requirements 2.2, 2.3**

### Property 6: Fallback Reliability
*For any* token operation failure, the service should continue fetching questions without tokens and maintain full functionality
**Validates: Requirements 2.4**

### Property 7: Token Reuse Across Games
*For any* user with a valid stored token, starting a new game should reuse the existing token rather than requesting a new one
**Validates: Requirements 3.2**

### Property 8: Automatic Token Refresh
*For any* invalid or expired stored token, the service should automatically request a new token without user intervention
**Validates: Requirements 3.3, 3.4**

## Error Handling

### Token API Failures
- **Network Issues**: Retry with exponential backoff, fall back to non-token requests
- **Invalid Responses**: Log error, clear stored token, continue without tokens
- **Rate Limiting**: Respect API limits, use cached tokens when possible

### Token State Management
- **Expired Tokens**: Automatic refresh on next request
- **Invalid Tokens**: Clear from storage, request new token
- **Missing Tokens**: Request new token transparently

### Graceful Degradation
- All existing functionality remains available when tokens are unavailable
- Users experience no interruption in game flow
- Token features are transparent to the user interface

## Testing Strategy

### Unit Testing
- Mock HTTP responses for token API endpoints
- Test token storage and retrieval mechanisms
- Verify fallback behavior when token operations fail
- Test token exhaustion detection and reset logic

### Property-Based Testing
- Generate random user scenarios and verify token consistency
- Test token reuse across multiple game sessions
- Verify fallback behavior under various failure conditions
- Test automatic token refresh with different expiry scenarios

### Integration Testing
- Test complete flow from token request to question fetching
- Verify token persistence across application restarts
- Test concurrent user scenarios with separate tokens
- Validate API integration with real endpoints (in development)

## Performance Considerations

### Caching Strategy
- Store tokens in application cache with 5-hour TTL (buffer before API expiry)
- Use user-based cache keys for authenticated users
- Use session-based cache keys for guest users
- Implement cache warming for frequently used tokens

### API Optimization
- Minimize token API calls through effective caching
- Batch token operations when possible
- Implement circuit breaker pattern for token API failures
- Use existing retry logic for token requests

### Memory Management
- Limit token cache size to prevent memory bloat
- Implement LRU eviction for token storage
- Clean up expired tokens periodically
- Monitor cache hit rates for optimization

## Security Considerations

### Token Protection
- Store tokens securely in server-side cache
- Never expose tokens in client-side code
- Implement proper session isolation for guest users
- Use secure cache backends in production

### User Privacy
- Associate tokens with user IDs only when authenticated
- Clean up guest tokens after session expiry
- Implement proper data retention policies
- Ensure token data doesn't leak between users
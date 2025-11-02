# Implementation Plan

- [x] 1. Create external API service for pass-through functionality
  - Implement OpenTriviaService for real-time API communication
  - Add caching for categories and error handling for API failures
  - Process API responses with HTML decoding and answer shuffling
  - _Requirements: 1.1, 1.3, 3.1, 3.2, 3.3, 7.1, 7.2_

- [x] 1.1 Create OpenTriviaService class
  - Implement getCategories() method with 1-hour caching
  - Implement getQuestions() method with parameter validation
  - Add HTML entity decoding for question text and answers
  - Add answer shuffling logic to randomize options
  - Implement error handling with graceful fallbacks
  - _Requirements: 1.1, 1.2, 1.3, 3.2, 3.3, 7.1, 7.2_

- [ ] 2. Create backend controllers for API endpoints
  - Implement TriviaController as pass-through proxy
  - Create minimal GameController for page routing
  - Add proper HTTP status codes and JSON responses
  - _Requirements: 1.4, 2.1, 3.1_

- [ ] 2.1 Create TriviaController
  - Implement categories() method with /api/categories endpoint
  - Implement questions() method with /api/questions endpoint
  - Add request validation for question parameters
  - Integrate with OpenTriviaService for data fetching
  - _Requirements: 1.1, 1.2, 1.4, 2.2, 2.3, 2.4_

- [ ] 2.2 Create GameController for page routing
  - Implement setup() method for game setup page
  - Implement play() method for game play page
  - No game state management (handled client-side)
  - _Requirements: 2.1, 6.1_

- [ ] 3. Create form request validation classes
  - Implement QuestionsRequest for API parameter validation
  - Add server-side validation for category, difficulty, and count
  - _Requirements: 2.2, 2.3, 2.4_

- [ ] 3.1 Create QuestionsRequest
  - Validate category selection (optional)
  - Validate question count (1-50)
  - Validate difficulty level (easy, medium, hard, mixed)
  - Add proper error messages for validation failures
  - _Requirements: 2.2, 2.3, 2.4_

- [ ] 4. Create frontend TypeScript interfaces and types
  - Define game state interfaces for client-side management
  - Create type definitions for API responses
  - Add utility types for game logic
  - _Requirements: 3.1, 4.1, 5.1, 6.1_

- [ ] 4.1 Create game state interfaces
  - Define GameState interface with all game properties
  - Define Question interface matching API response format
  - Define PlayerAnswer interface for answer tracking
  - Define Category interface for category selection
  - _Requirements: 2.1, 3.1, 4.1, 5.1, 6.1_

- [ ] 5. Implement frontend game state management
  - Create custom React hooks for game state
  - Add localStorage persistence for page refreshes
  - Implement scoring logic (easy=10, medium=20, hard=30)
  - _Requirements: 3.4, 3.5, 4.1, 4.4, 5.1, 6.4_

- [ ] 5.1 Create useGameState hook
  - Manage game configuration and current state
  - Handle question progression and answer tracking
  - Calculate scores and final results
  - Persist state to localStorage for refresh handling
  - _Requirements: 3.4, 3.5, 4.1, 4.4, 5.1, 5.2, 5.3, 6.1, 6.4_

- [ ] 5.2 Create useTrivia hook
  - Fetch categories from backend API
  - Fetch questions based on game parameters
  - Handle loading states and error conditions
  - _Requirements: 1.1, 1.4, 2.1, 3.1_

- [ ] 6. Create frontend React components and pages
  - Implement GameSetup page with form validation
  - Implement PlayGame page with question display and client-side logic
  - Implement GameResults page with score summary
  - Create reusable UI components for game interface
  - _Requirements: 2.1, 3.1, 4.1, 5.1, 6.1_

- [ ] 6.1 Create GameSetup page component
  - Build form for category, difficulty, and question count selection
  - Add client-side validation and error handling
  - Integrate with categories API endpoint
  - Handle form submission and navigation to play page
  - _Requirements: 2.1, 2.2, 2.3_

- [ ] 6.2 Create PlayGame page component
  - Display current question with shuffled answer options
  - Show progress indicator and current score
  - Handle answer selection with immediate feedback
  - Implement automatic navigation to next question
  - Calculate and display points earned per question
  - _Requirements: 3.1, 3.3, 4.1, 4.2, 4.3, 6.1, 6.2, 6.3_

- [ ] 6.3 Create GameResults page component
  - Display final score, correct answers count, and percentage
  - Show detailed breakdown of performance
  - Add option to start new game
  - _Requirements: 5.1, 5.2, 5.3, 5.5_

- [ ] 6.4 Create reusable UI components
  - QuestionCard component for question display
  - ScoreDisplay component for score tracking
  - ProgressBar component for game progress
  - AnswerFeedback component for correct/incorrect feedback
  - LoadingSpinner component for API calls
  - _Requirements: 3.3, 4.1, 4.2, 4.3_

- [ ] 7. Add routing and navigation
  - Define Laravel routes for API endpoints and pages
  - Configure Inertia.js page routing
  - Implement proper authorization middleware
  - _Requirements: 6.1, 6.4, 6.5_

- [ ] 7.1 Create Laravel route definitions
  - Add API routes for categories and questions
  - Add page routes for game setup and play
  - Apply auth and verified middleware
  - _Requirements: 6.5_

- [ ] 7.2 Configure frontend routing and navigation
  - Set up Inertia.js page navigation between game states
  - Handle browser back/forward navigation appropriately
  - Prevent navigation during active gameplay
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 8. Add error handling and user feedback
  - Implement comprehensive error handling for API failures
  - Add user-friendly error messages and retry mechanisms
  - Handle network timeouts and connection issues
  - _Requirements: 1.3, 2.4_

- [ ] 8.1 Create error handling utilities
  - Add error boundary components for React
  - Implement retry logic for failed API calls
  - Create user-friendly error messages
  - Add fallback UI for when external API is unavailable
  - _Requirements: 1.3, 2.4_

- [ ]* 9. Add comprehensive testing
  - Write feature tests for API endpoints
  - Create unit tests for services and utilities
  - Add frontend component tests
  - _Requirements: All requirements validation_

- [ ]* 9.1 Write backend feature tests
  - Test TriviaController API endpoints
  - Test OpenTriviaService integration
  - Test error handling and validation
  - _Requirements: 1.1, 1.3, 2.4_

- [ ]* 9.2 Write unit tests for services
  - Test OpenTriviaService methods
  - Test HTML entity decoding
  - Test answer shuffling logic
  - _Requirements: 3.2, 3.3_

- [ ]* 9.3 Write frontend component tests
  - Test game state management hooks
  - Test form validation and submission
  - Test question display and answer selection
  - Test score calculation and progress tracking
  - _Requirements: 2.1, 3.1, 4.1, 5.1_
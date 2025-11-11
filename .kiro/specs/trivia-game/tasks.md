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

- [x] 2. Create backend controllers for API endpoints
  - Implement TriviaController as pass-through proxy
  - Create minimal GameController for page routing
  - Add proper HTTP status codes and JSON responses
  - _Requirements: 1.4, 2.1, 3.1_

- [x] 2.1 Create TriviaController
  - Implement categories() method with /api/categories endpoint
  - Implement questions() method with /api/questions endpoint
  - Add request validation for question parameters
  - Integrate with OpenTriviaService for data fetching
  - _Requirements: 1.1, 1.2, 1.4, 2.2, 2.3, 2.4_

- [x] 2.2 Create GameController for page routing
  - Implement setup() method for game setup page
  - Implement play() method for game play page
  - Prepare for game state management (database persistence)
  - _Requirements: 2.1, 6.1_

- [x] 3. Create form request validation classes
  - Implement QuestionsRequest for API parameter validation
  - Add server-side validation for category, difficulty, and count
  - _Requirements: 2.2, 2.3, 2.4_

- [x] 3.1 Create QuestionsRequest
  - Validate category selection (optional)
  - Validate question count (1-50)
  - Validate difficulty level (easy, medium, hard, mixed)
  - Add proper error messages for validation failures
  - _Requirements: 2.2, 2.3, 2.4_

- [x] 4. Create database migrations and models for game state
  - Create migrations for games and player_answers tables only
  - Implement Eloquent models with proper relationships
  - Add enum classes for status and difficulty
  - _Requirements: 2.5, 3.4, 4.5, 5.4, 6.4, 6.5_

- [x] 4.1 Create database migrations
  - Create games migration with questions JSON field for temporary storage
  - Create player_answers migration for answer tracking and scoring
  - No categories or questions tables needed (API pass-through)
  - _Requirements: 2.5, 3.4, 4.5, 5.4, 6.4, 6.5_

- [x] 4.2 Create Eloquent models
  - Implement Game model with relationships and business logic methods
  - Implement PlayerAnswer model with game relationship
  - Add enum classes for GameStatus and DifficultyLevel
  - _Requirements: 2.5, 3.4, 4.5, 5.4, 6.4, 6.5_

- [x] 5. Create additional form request classes for game state
  - Implement GameStoreRequest for game creation validation
  - Implement AnswerRequest for answer submission validation
  - Add proper authorization and validation rules
  - _Requirements: 2.2, 2.3, 2.4, 3.4, 6.5_

- [x] 5.1 Create GameStoreRequest
  - Validate category selection and game parameters
  - Validate question count and difficulty level
  - Add authorization to ensure authenticated users only
  - _Requirements: 2.2, 2.3, 2.4, 6.5_

- [x] 5.2 Create AnswerRequest
  - Validate answer submission format
  - Ensure game ownership and active status
  - Validate question exists and hasn't been answered
  - _Requirements: 3.4, 6.5_

- [x] 6. Implement GameController with hybrid state management
  - Create game setup, play, answer submission, and results methods
  - Integrate with OpenTriviaService to fetch questions (no storage)
  - Store questions temporarily in Game.questions JSON field
  - Implement proper game state transitions and validation
  - Add game ownership authorization
  - _Requirements: 2.1, 2.5, 3.1, 3.4, 4.1, 5.1, 6.1, 6.4, 6.5_

- [x] 6.1 Implement game setup and creation
  - Create setup() method to show game configuration page
  - Implement store() method to create new game with questions
  - Fetch questions from OpenTriviaService (no database storage)
  - Store questions temporarily in Game.questions JSON field
  - _Requirements: 2.1, 2.2, 2.3, 2.5, 3.1, 3.2_

- [x] 6.2 Implement game play and question display
  - Create show() method to display current question
  - Handle game state and progress tracking in database
  - Ensure proper game ownership authorization
  - Return current question from Game.questions JSON field
  - _Requirements: 3.1, 4.1, 4.2, 6.1, 6.4, 6.5_

- [x] 6.3 Implement answer submission and validation
  - Create answer() method to process player responses
  - Validate answers against questions stored in Game.questions
  - Create PlayerAnswer records in database
  - Calculate and update game score in database
  - Handle game completion when all questions answered
  - _Requirements: 3.4, 3.5, 4.3, 4.4, 5.1, 6.2_

- [x] 6.4 Implement game results and completion
  - Create results() method to show final game statistics
  - Calculate final score and percentage from PlayerAnswer records
  - Mark game as completed with completion timestamp
  - Ensure only completed games can access results
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 7. Create frontend TypeScript interfaces and types
  - Define interfaces matching backend models and API responses
  - Create type definitions for hybrid data flow
  - Add utility types for game logic
  - _Requirements: 3.1, 4.1, 5.1, 6.1_

- [x] 7.1 Create game and model interfaces
  - Define Game interface matching Eloquent model with questions JSON
  - Define Question interface matching API response format
  - Define PlayerAnswer interface for answer tracking
  - Define Category interface for category selection (API only)
  - _Requirements: 2.1, 3.1, 4.1, 5.1, 6.1_

- [ ] 8. Create frontend React components and pages
  - Implement GameSetup page with form validation
  - Implement PlayGame page with server-side game state
  - Implement GameResults page with score summary
  - Create reusable UI components for game interface
  - _Requirements: 2.1, 3.1, 4.1, 5.1, 6.1_

- [ ] 8.1 Create GameSetup page component
  - Build form for category, difficulty, and question count selection
  - Add client-side validation and error handling
  - Integrate with categories API endpoint (pass-through)
  - Handle form submission to create new game
  - _Requirements: 2.1, 2.2, 2.3_

- [ ] 8.2 Create PlayGame page component
  - Display current question from game state
  - Show progress indicator and current score from database
  - Handle answer selection with server submission
  - Implement navigation based on server response
  - _Requirements: 3.1, 3.3, 4.1, 4.2, 4.3, 6.1, 6.2, 6.3_

- [ ] 8.3 Create GameResults page component
  - Display final score, correct answers count, and percentage
  - Show detailed breakdown of performance from database
  - Add option to start new game
  - _Requirements: 5.1, 5.2, 5.3, 5.5_

- [ ] 8.4 Create reusable UI components
  - QuestionCard component for question display
  - ScoreDisplay component for score tracking
  - ProgressBar component for game progress
  - AnswerFeedback component for correct/incorrect feedback
  - LoadingSpinner component for API calls
  - _Requirements: 3.3, 4.1, 4.2, 4.3_

- [ ] 9. Add routing and navigation
  - Define Laravel routes for game endpoints
  - Configure Inertia.js page routing
  - Implement proper authorization middleware
  - _Requirements: 6.1, 6.4, 6.5_

- [ ] 9.1 Create Laravel route definitions
  - Add API routes for categories and questions (pass-through)
  - Add game routes for setup, play, answer, and results
  - Apply auth and verified middleware
  - Add route model binding for games
  - _Requirements: 6.5_

- [ ] 10. Add error handling and user feedback
  - Implement comprehensive error handling for API failures
  - Add user-friendly error messages and retry mechanisms
  - Handle game state validation errors
  - _Requirements: 1.3, 2.4_

- [ ] 10.1 Create error handling utilities
  - Add error boundary components for React
  - Implement retry logic for failed API calls
  - Create user-friendly error messages
  - Add fallback UI for when external API is unavailable
  - Handle game ownership and state validation errors
  - _Requirements: 1.3, 2.4, 6.5_

- [ ]* 11. Add comprehensive testing
  - Write feature tests for game endpoints
  - Create unit tests for models and services
  - Add frontend component tests
  - _Requirements: All requirements validation_

- [ ]* 11.1 Write backend feature tests
  - Test GameController hybrid game flow
  - Test TriviaController API endpoints (pass-through)
  - Test OpenTriviaService integration
  - Test game state management and validation
  - _Requirements: 1.1, 1.3, 2.4, 6.5_

- [ ]* 11.2 Write unit tests for models and services
  - Test Game model relationships and methods
  - Test OpenTriviaService methods
  - Test HTML entity decoding and answer shuffling
  - Test scoring calculations with database persistence
  - _Requirements: 3.2, 3.3, 3.5, 4.4_

- [ ]* 11.3 Write frontend component tests
  - Test form validation and submission
  - Test question display and answer selection
  - Test game navigation and state handling
  - _Requirements: 2.1, 3.1, 4.1, 5.1_
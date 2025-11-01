# Requirements Document

## Introduction

This document outlines the requirements for implementing a single-player trivia game system. The system will allow users to play trivia games by answering multiple-choice and true/false questions, earning points based on difficulty, and viewing their results. This phase focuses on the core game mechanics and API integration with the Open Trivia Database.

## Glossary

- **Trivia_System**: The complete trivia game application built on Laravel with React frontend
- **Open_Trivia_API**: External API service (opentdb.com) providing trivia questions and categories
- **Game_Session**: A single instance of a trivia game with specific parameters stored in the games table
- **Question_Set**: Collection of questions selected for a game session from local database or API
- **Player_Response**: User's answer submission for a specific question stored in player_answers table
- **Score_Calculator**: Component responsible for calculating points based on difficulty and correctness
- **Category**: Trivia topic classification (e.g., Science, History, Sports)
- **Difficulty_Level**: Question complexity rating (easy, medium, hard)

## Requirements

### Requirement 1

**User Story:** As a player, I want to see available trivia categories, so that I can choose topics I'm interested in.

#### Acceptance Criteria

1. WHEN the system requests categories from the Open_Trivia_API, THE Trivia_System SHALL fetch data from "https://opentdb.com/api_category.php"
2. WHEN the API request is successful, THE Trivia_System SHALL return a formatted list of categories with ID and name
3. WHEN the API request fails, THE Trivia_System SHALL return an appropriate error response with fallback to cached data
4. THE Trivia_System SHALL expose categories through "/api/categories" endpoint
5. THE Trivia_System SHALL cache category data for one hour to minimize external API calls

### Requirement 2

**User Story:** As a player, I want to configure my game settings, so that I can customize my trivia experience.

#### Acceptance Criteria

1. THE Trivia_System SHALL allow selection of Category or "All Categories" option
2. THE Trivia_System SHALL accept number of questions between 1 and 50 inclusive
3. THE Trivia_System SHALL provide Difficulty_Level options: easy, medium, hard, or mixed
4. WHEN game parameters are submitted, THE Trivia_System SHALL validate all inputs server-side
5. WHEN validation passes, THE Trivia_System SHALL create a new Game_Session record with status "active"

### Requirement 3

**User Story:** As a player, I want to answer trivia questions, so that I can test my knowledge and earn points.

#### Acceptance Criteria

1. THE Trivia_System SHALL fetch questions from Open_Trivia_API based on Game_Session parameters
2. THE Trivia_System SHALL decode HTML entities in question text and answers using proper encoding
3. THE Trivia_System SHALL present questions with shuffled answer options to prevent pattern recognition
4. WHEN a player submits an answer, THE Trivia_System SHALL validate the Player_Response server-side against stored correct answers
5. THE Trivia_System SHALL calculate points using Score_Calculator based on Difficulty_Level (easy=10, medium=20, hard=30)

### Requirement 4

**User Story:** As a player, I want to see my progress and score, so that I can track my performance during the game.

#### Acceptance Criteria

1. THE Trivia_System SHALL display current question number and total questions in Game_Session
2. THE Trivia_System SHALL show running score after each Player_Response
3. WHEN a Player_Response is submitted, THE Trivia_System SHALL highlight correct and incorrect answers with visual feedback
4. THE Trivia_System SHALL show points earned for each question based on Difficulty_Level
5. THE Trivia_System SHALL persist all Player_Response data to the database with timestamps

### Requirement 5

**User Story:** As a player, I want to see my final results, so that I can evaluate my performance.

#### Acceptance Criteria

1. WHEN all questions in Game_Session are answered, THE Trivia_System SHALL calculate final score using Score_Calculator
2. THE Trivia_System SHALL display total correct answers out of total questions attempted
3. THE Trivia_System SHALL show percentage score rounded to one decimal place
4. THE Trivia_System SHALL mark Game_Session status as "completed" with completion timestamp
5. THE Trivia_System SHALL provide option to start a new Game_Session with different parameters

### Requirement 6

**User Story:** As a player, I want to navigate between questions smoothly, so that I can focus on answering without interruption.

#### Acceptance Criteria

1. WHEN a Player_Response is submitted, THE Trivia_System SHALL advance to the next question automatically after showing feedback
2. THE Trivia_System SHALL prevent navigation to previous questions within an active Game_Session
3. WHEN the final question is answered, THE Trivia_System SHALL redirect to results page automatically
4. THE Trivia_System SHALL maintain Game_Session state during page refreshes
5. THE Trivia_System SHALL prevent unauthorized access to Game_Session questions by other users

### Requirement 7

**User Story:** As a system administrator, I want to populate the question database, so that players have access to trivia content.

#### Acceptance Criteria

1. THE Trivia_System SHALL provide console command "php artisan trivia:fetch" for question population
2. WHEN the command executes, THE Trivia_System SHALL fetch 50 questions from Open_Trivia_API per category
3. THE Trivia_System SHALL map API categories to local categories table with external ID tracking
4. THE Trivia_System SHALL store questions with proper Difficulty_Level and calculated point values
5. THE Trivia_System SHALL handle duplicate questions by checking external ID before insertion
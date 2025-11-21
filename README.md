# 🎯 Trivia Trail

A modern, real-time multiplayer trivia game built with Laravel and React. Challenge friends, test your knowledge, and climb the leaderboards in this engaging trivia experience.

![Trivia Trail](https://img.shields.io/badge/Laravel-12-red?style=flat-square&logo=laravel)
![React](https://img.shields.io/badge/React-19-blue?style=flat-square&logo=react)
![TypeScript](https://img.shields.io/badge/TypeScript-5.7-blue?style=flat-square&logo=typescript)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.0-teal?style=flat-square&logo=tailwindcss)

## ✨ Features

### 🎮 Game Modes
- **Single Player**: Practice solo with customizable difficulty and categories
- **Multiplayer**: Real-time games that allow you to battle against your friends.
- **Custom Rooms**: Create private rooms with 6-character codes (e.g., `K7X9M2`)

### 🏆 Gameplay Features
- **15+ Categories**: From General Knowledge to Science, History, and Entertainment
- **3 Difficulty Levels**: Easy, Medium, and Hard questions
- **Real-time Competition**: Live answer indicators and shared countdown timers
- **Comprehensive Results**: Detailed answer breakdowns and performance analytics

### 🚀 Technical Features
- **Real-time Updates**: Powered by Inertia.js polling for seamless multiplayer experience
- **Responsive Design**: Beautiful UI that works on desktop and mobile
- **Dark/Light Mode**: Automatic theme switching based on user preference


## 🛠 Tech Stack

### Backend
- **PHP 8.2+** - Modern PHP with strong typing
- **Laravel 12** - Full-stack PHP framework with Eloquent ORM
- **Laravel Fortify** - Authentication scaffolding with 2FA support
- **Inertia.js** - SPA adapter connecting Laravel and React
- **Laravel Pint** - PHP code formatting
- **Pest** - Modern PHP testing framework

### Frontend
- **React 19** - UI library with React Compiler enabled
- **TypeScript 5.7** - Type-safe JavaScript development
- **Tailwind CSS 4** - Utility-first CSS framework
- **Vite 7** - Lightning-fast build tool and dev server
- **Radix UI** - Headless, accessible UI components
- **Lucide React** - Beautiful icon library
- **shadcn/ui** - Beautifully designed component system


## 🎯 How to Play

### Single Player
1. **Setup Game**: Choose your category, difficulty, and number of questions (1-50)
2. **Answer Questions**: Multiple choice and true/false questions with immediate feedback
3. **Review Results**: See your score, accuracy, and detailed answer breakdown
4. **Track Progress**: View your game history and performance over time

### Multiplayer
1. **Create or Join Room**: Host a game or join with a room code
2. **Real-time Competition**: Race against the clock with customizable timers per question in a round
3. **Final Standings**: Complete leaderboard with tie handling and performance stats
4. **Question Review**: Analyze all questions and answers after the game

### Room Features
- **Host Controls**: Start games, manage settings, and advance questions
- **Live Status**: See when other players have answered in real-time
- **Automatic Progression**: Games advance automatically when time expires
- **Game History**: Browse your past multiplayer games with full details

## 🚀 Quick Start

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite (or your preferred database)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd trivia-trail
   ```

2. **Install dependencies and setup**
   ```bash
   composer run setup
   ```
   This command will:
   - Install PHP dependencies
   - Copy `.env.example` to `.env`
   - Generate application key
   - Run database migrations
   - Install Node.js dependencies
   - Build frontend assets

3. **Start development servers**
   ```bash
   composer run dev
   ```
   This starts all services concurrently:
   - Laravel development server (`:8000`)
   - Queue worker for background jobs
   - Log monitoring with Pail
   - Vite dev server with HMR

4. **Visit the application**
   Open [http://localhost:8000](http://localhost:8000) in your browser

### Alternative Commands

```bash
# Start with SSR support
composer run dev:ssr

# Run tests
composer run test

# Build for production
npm run build

# Format code
npm run format

# Type checking
npm run types
```

## 🎮 Game Configuration

### Question Sources
- [**Open Trivia Database**](https://opentdb.com): 4,000+ questions across 24 categories
   - **Session Tokens**: Prevents duplicate questions within user sessions
- 🙏🏽 Many thanks to the Open Trivia Database team for providing the extensive question database

### Multiplayer Settings
- **Room Capacity**: Up to 10 players
- **Question Timer**: Configurable timers from a preset of options
- **Room Codes**: 6-character alphanumeric codes for easy sharing


### Scoring System
- **Easy Questions**: 10 points each
- **Medium Questions**: 20 points each  
- **Hard Questions**: 30 points each
- **Tie Breakers**: Earlier room join time wins ties
- **Performance Tracking**: Accuracy percentages and response times

## 🧪 Testing

The application includes comprehensive test coverage:

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test tests/Feature/
php artisan test tests/Unit/

# Run with coverage
php artisan test --coverage
```

### Test Categories
- **Feature Tests**: End-to-end game flows and multiplayer scenarios
- **Unit Tests**: Individual service and utility testing
- **Integration Tests**: API integration and session token functionality
- **Property-Based Tests**: Correctness validation with random inputs

## 📁 Project Structure

```
trivia-trail/
├── app/
│   ├── Http/Controllers/     # Game, Room, and Multiplayer controllers
│   ├── Models/              # Eloquent models for games, rooms, users
│   ├── Services/            # Business logic (OpenTriviaService, etc.)
│   ├── Jobs/                # Background jobs for game management
│   └── Utilities/           # Helper classes and utilities
├── resources/js/
│   ├── components/          # React components
│   │   ├── game/           # Single-player game components
│   │   ├── multiplayer/    # Multiplayer-specific components
│   │   └── ui/             # Reusable UI components (shadcn/ui)
│   ├── pages/              # Inertia.js page components
│   ├── hooks/              # Custom React hooks
│   └── types/              # TypeScript type definitions
├── database/
│   ├── migrations/         # Database schema migrations
│   └── seeders/           # Database seeders
└── tests/
    ├── Feature/           # Feature and integration tests
    └── Unit/              # Unit tests
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`composer run test`)
5. Format code (`npm run format`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## 📝 License

This project is open-sourced software licensed under the [MIT license](LICENSE).


---

**Ready to test your knowledge?** 🧠 Start your trivia journey today!
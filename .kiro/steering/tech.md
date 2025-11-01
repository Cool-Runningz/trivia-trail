# Technology Stack

## Backend
- **PHP 8.2+** - Modern PHP with strong typing
- **Laravel 12** - Full-stack PHP framework
- **Laravel Fortify** - Authentication scaffolding
- **Inertia.js** - SPA adapter for Laravel
- **SQLite** - Default database (configurable)

## Frontend
- **React 19** - UI library with React Compiler enabled
- **TypeScript** - Type-safe JavaScript
- **Tailwind CSS 4** - Utility-first CSS framework
- **Vite** - Build tool and dev server
- **Radix UI** - Headless UI components
- **Lucide React** - Icon library

## Development Tools
- **Laravel Pint** - PHP code formatter
- **Prettier** - Frontend code formatter
- **ESLint** - JavaScript/TypeScript linting
- **Pest** - PHP testing framework
- **Laravel Wayfinder** - Type-safe routing

## Common Commands

### Setup
```bash
composer run setup  # Full project setup
```

### Development
```bash
composer run dev     # Start all dev services (server, queue, logs, vite)
composer run dev:ssr # Start with SSR support
npm run dev          # Frontend only
php artisan serve    # Backend only
```

### Building
```bash
npm run build        # Production build
npm run build:ssr    # Build with SSR
```

### Code Quality
```bash
composer run test    # Run PHP tests
npm run lint         # Fix JS/TS issues
npm run format       # Format frontend code
npm run types        # TypeScript check
```

### Database
```bash
php artisan migrate  # Run migrations
php artisan migrate:fresh --seed  # Fresh database with seeds
```
# Project Structure

## Backend (Laravel)

### Core Application
- `app/` - Application logic
  - `Actions/Fortify/` - Custom Fortify actions for auth
  - `Http/Controllers/` - HTTP controllers (minimal, mostly settings)
  - `Http/Middleware/` - Custom middleware (appearance, Inertia)
  - `Http/Requests/` - Form request validation
  - `Models/` - Eloquent models
  - `Providers/` - Service providers

### Configuration & Routes
- `config/` - Laravel configuration files
- `routes/` - Route definitions (web, API)
- `database/` - Migrations, factories, seeders

## Frontend (React + TypeScript)

### Core Structure
- `resources/js/` - All frontend code
  - `app.tsx` - Main application entry point
  - `ssr.tsx` - Server-side rendering entry

### Components
- `components/` - Reusable UI components
  - `ui/` - Base UI components (shadcn/ui style)
  - Custom app components (navigation, forms, etc.)

### Pages & Routing
- `pages/` - Inertia.js page components
  - `auth/` - Authentication pages
  - `settings/` - User settings pages
- `routes/` - Type-safe route definitions (Wayfinder generated)

### Utilities
- `actions/` - Generated action types for forms
- `hooks/` - Custom React hooks
- `lib/` - Utility functions
- `types/` - TypeScript type definitions
- `wayfinder/` - Route generation utilities

## Key Conventions

### File Naming
- **PHP**: PascalCase for classes (`UserController.php`)
- **React**: kebab-case for files (`user-menu.tsx`)
- **Components**: PascalCase for component names

### Component Structure
- Use Radix UI primitives for base components
- Implement shadcn/ui patterns with `cn()` utility
- Store reusable components in `components/ui/`
- Page-specific components can be co-located

### Routing
- Laravel handles backend routes
- Wayfinder generates type-safe frontend route helpers
- Use `@/routes` imports for navigation

### Styling
- Tailwind CSS with custom design system
- Dark/light mode support via CSS variables
- Responsive-first approach
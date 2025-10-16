# Copilot Instructions for Innovior IOT ESM Codebase

## Big Picture Architecture

- **Monorepo Structure**: Contains `client` (React/TypeScript frontend) and `server` (Laravel/PHP backend) directories. Each is independently deployable and developed.
- **Frontend**: Located in `client/shop-sync-react/`. Built with Vite, React, TypeScript, Tailwind, and shadcn-ui. Entry point: `src/main.tsx`. Components, hooks, contexts, and pages are organized in `src/`.
- **Backend**: Located in `server/ecommerce-stock-management/`. Laravel framework, with routes in `routes/`, models in `app/Models/`, controllers in `app/Http/Controllers/`, and config in `config/`. Entry: `public/index.php`.
- **Integration**: Frontend communicates with backend via HTTP APIs. API endpoints are defined in Laravel routes and controllers.

## Developer Workflows

- **Frontend**:
  - Install dependencies: `npm i` in `client/shop-sync-react/`
  - Start dev server: `npm run dev`
  - Build: `npm run build`
  - Lint: `npx eslint .`
- **Backend**:
  - Install dependencies: `composer install` in `server/ecommerce-stock-management/`
  - Start server: `php artisan serve`
  - Run migrations: `php artisan migrate`
  - Run tests: `php artisan test`
- **Docker**: Both frontend and backend have Dockerfiles. Use `docker-compose.yml` in `server/` for local orchestration.

## Project-Specific Conventions

- **Frontend**:
  - Uses Vite for fast builds and HMR.
  - TypeScript strictness enforced via `tsconfig.json`.
  - UI components follow shadcn-ui conventions.
  - State management via React Contexts in `src/contexts/`.
- **Backend**:
  - Follows Laravel's MVC conventions.
  - Custom services in `app/Services/`.
  - JWT authentication configured in `config/jwt.php`.
  - API routes in `routes/api.php`, auth routes in `routes/auth.php`.

## Integration Points & External Dependencies

- **Frontend**:
  - API calls are made from `src/services/`.
  - Auth logic in `src/auth/`.
- **Backend**:
  - Uses Laravel's Eloquent ORM for database access.
  - External packages managed via Composer.
  - Tailwind and Vite for asset compilation.

## Examples & Key Files

- **Frontend**:
  - `src/pages/` for main views
  - `src/components/` for reusable UI
  - `src/services/` for API integration
- **Backend**:
  - `routes/auth.php` for authentication endpoints
  - `app/Http/Controllers/` for request handling
  - `config/jwt.php` for JWT setup

## Tips for AI Agents

- Respect the separation between frontend and backend; changes in one may require updates in the other.
- When adding new API endpoints, update both backend routes/controllers and frontend services.
- Use Docker for consistent local development.
- Follow existing file and folder conventions for new code.

---

_If any section is unclear or missing important project-specific details, please provide feedback to improve these instructions._

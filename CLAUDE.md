# CLAUDE.md - Import Workflow

## Project Overview

Multi-tenant data import workflow platform built by Elaitech. Users configure, test, and execute multi-stage data import pipelines from HTTP/FTP/SFTP sources through an 8-step wizard UI.

## Tech Stack

- **Backend:** PHP 8.4, Laravel 12, PostgreSQL 16, Redis
- **Frontend:** Vue 3, TypeScript, Inertia.js, Tailwind CSS, shadcn-vue
- **Admin:** Filament 4.0
- **Auth:** Laravel Fortify + Sanctum
- **AI:** Laravel AI with OpenAI
- **Packages:** Two local packages in `packages/` — `import` (pipeline engine) and `data-mapper` (field mapping/transformers)

## Commands

```bash
# Setup
composer setup              # Full setup: install deps, key, migrate, npm install + build

# Development
composer dev                # Starts server, queue, logs (pail), and vite concurrently

# Testing
php artisan test            # Run test suite
composer test               # Clear config + run tests
./vendor/bin/phpunit        # Direct PHPUnit

# Code Style
./vendor/bin/pint           # Fix code style (Laravel Pint)

# Build
npm run build               # Production build
npm run dev                 # Vite dev server
npm run watch               # Vite build --watch

# Type Generation
php artisan typescript:transform   # Generate TS types from PHP DTOs

# Docker
make up                     # Start containers
make down                   # Stop containers
make bash                   # Shell into app container
make build                  # Build containers
make restart                # Down + up
make docker-up              # Clean + build + up
make docker-test            # Run tests in container
make docker-clean           # Stop, remove, prune
```

## Project Structure

```
app/
  Ai/Agents/             # AI agents (ImportMapping)
  Filament/              # Admin panel resources
  Http/Controllers/Dashboard/
    Import/              # Pipeline CRUD, stepper wizard, test endpoints
    Organization/        # Org management
  Http/Middleware/        # EnsureOrganizationContext, AuthenticateOrganizationToken
  Http/ViewModels/       # 22 view models for Inertia data passing
  Models/                # User, Organization (UUID PK), TargetField, etc.
  Policies/              # Authorization policies
  Services/Import/       # DatabaseResultSaver
packages/
  import/                # Pipeline engine: downloaders, readers, filters, pipes, jobs
  data-mapper/           # Field mapping: extractors, transformers, DTOs
resources/ts/
  Pages/Dashboard/       # Vue page components (Import, Organization, Products)
  components/            # shadcn-vue based UI components
  composables/           # Vue 3 composables
  types/                 # Auto-generated TypeScript types from PHP DTOs
routes/
  web.php               # Entry point, includes dashboard route files
  import-dashboard.php   # ~30 import pipeline routes
  product-dashboard.php  # Product activity log routes
  organization-dashboard.php  # Org management routes
config/
  import-pipelines.php   # Pipeline config (downloaders, readers, filters, queues)
  ai.php                 # AI provider configuration
```

## Architecture

### Pipeline System (7 Stages)
Download → Read → Filter → Map → Images Prepare → Prepare → Save

### Multi-Tenancy
- Organization-based isolation via `organization_uuid` on all import models
- Global Eloquent scopes for automatic tenant filtering
- `EnsureOrganizationContext` middleware sets tenant context
- Queue jobs restore tenant context before execution

### Data Flow
Controller → ViewModel → Inertia → Vue Page → Form Submit → Controller → Service/Package

### Roles (Spatie Permission)
Super Admin, Admin, Dev, Pipeline Manager

### Key Patterns
- Pipeline/Pipes pattern for import stages
- ViewModel pattern for controller-to-view data
- Factory pattern for dynamic downloader/reader/filter instantiation
- Local packages for reusable import and data-mapper logic
- Auto-generated TypeScript types from PHP DTOs via Spatie

## Conventions

- **PHP Style:** Laravel Pint (default preset, excludes tests/vendor/node_modules/storage/docker/bootstrap)
- **Frontend:** TypeScript strict, Vue 3 Composition API, shadcn-vue components
- **Models:** PascalCase; Organization uses UUID primary key
- **Controllers:** Resource controllers under `Dashboard/` namespace
- **ViewModels:** `*ViewModel` suffix (e.g., `ListPipelineViewModel`)
- **Routes:** Grouped by dashboard feature in separate route files
- **Database:** PostgreSQL with UUID for organizations, standard auto-increment elsewhere
- **Migrations:** In both `database/migrations/` (app-level) and `packages/import/database/migrations/`

## Environment

Key `.env` variables:
- `DB_CONNECTION=pgsql`, `DB_DATABASE=import_workflow`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=redis`
- `OPENAI_API_KEY` for AI mapping features

Docker services: app (PHP 8.4 FPM, port 84), postgres-db (port 5432), redis (port 6379), pgadmin (port 5050)

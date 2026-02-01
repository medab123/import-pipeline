# Import Workflow - Laravel Application

A Laravel application that uses the `elaitech/import` package for inventory import workflow functionality. The import logic has been extracted into a reusable Composer package located in `packages/import`.

## Features

- **Import Pipeline System**: Complete pipeline for downloading, reading, filtering, mapping, and processing data
- **Multiple Download Sources**: Support for HTTP/HTTPS, FTP, SFTP, and local file downloads
- **Multiple Reader Types**: CSV, JSON, XML readers with configurable options
- **Advanced Filtering**: Complex filter rules with multiple operators
- **Data Mapping**: Flexible field mapping with value transformers
- **Inertia.js UI**: Modern Vue.js-based interface for pipeline management
- **Placeholder Save**: Simulates product saving without actual persistence

## Requirements

- PHP 8.4+
- Composer
- Node.js 18+ and npm
- PostgreSQL 16+ (or MySQL 8+)
- Redis (optional, for caching)

## Installation

### Using Docker (Recommended)

1. Clone or navigate to the project directory:
```bash
cd import-workflow
```

2. Start Docker containers:
```bash
cd docker
docker-compose up -d
```

3. Install dependencies:
```bash
docker-compose exec app composer install
docker-compose exec app npm install
docker-compose exec app npm run build
```

4. Set up environment:
```bash
docker-compose exec app cp .env.example .env
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

5. Access the application:
- Application: http://localhost:8000
- Database: localhost:5432

### Manual Installation

1. Install PHP dependencies:
```bash
composer install
```

2. Install Node dependencies:
```bash
npm install
npm run build
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure database in `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=import_workflow
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Run migrations:
```bash
php artisan migrate
```

6. Start the development server:
```bash
php artisan serve
```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/         # Application controllers
│   └── ViewModels/         # Application view models
├── Models/                  # Application models
└── Providers/               # Application service providers

packages/
└── import/                  # Elaitech Import Package
    ├── src/
    │   ├── Models/          # Import-related models
    │   ├── Services/        # Import services (Downloader, Reader, Filter, Pipeline, etc.)
    │   ├── Http/            # Import controllers and view models
    │   ├── Contracts/       # Service contracts and interfaces
    │   ├── Enums/           # Import-related enums
    │   └── Providers/       # Package service providers
    ├── config/              # Package configuration files
    ├── database/migrations/  # Package migrations
    ├── routes/              # Package routes
    └── resources/ts/        # Frontend resources (Vue components, TypeScript)

resources/ts/
└── Pages/                   # Application Inertia.js pages

database/migrations/          # Application migrations
```

## Key Components

### Import Pipeline Stages

1. **Download**: Downloads data from various sources (HTTP, FTP, SFTP, Local)
2. **Read**: Parses data using configured readers (CSV, JSON, XML)
3. **Filter**: Applies filter rules to data
4. **Map**: Maps source fields to target fields
5. **Images Prepare**: Handles image processing configuration
6. **Prepare**: Final data preparation
7. **Save**: Placeholder save (simulates saving without persistence)

### SavePipe (Placeholder)

The `SavePipe` is a placeholder implementation that simulates saving products without actually persisting them to the database. It:
- Processes all prepared data
- Simulates create/update operations
- Returns statistics (created count, updated count, errors)
- Logs all operations

To implement real persistence, replace the `SavePipe` implementation with your domain-specific save logic.

## Usage

### Creating an Import Pipeline

1. Navigate to `/dashboard/import/pipelines`
2. Click "Create Pipeline"
3. Configure each step:
   - Basic Info: Name, description, frequency
   - Downloader: Configure download source
   - Reader: Configure data format and parsing
   - Filter: Set up filter rules
   - Mapper: Map source fields to target fields
   - Images Prepare: Configure image handling
   - Preview: Review configuration

### Testing Pipeline Steps

Each step can be tested independently:
- Test Downloader: `/dashboard/import/pipelines/{id}/downloader/test`
- Test Reader: `/dashboard/import/pipelines/{id}/reader/test`
- Test Filter: `/dashboard/import/pipelines/{id}/filter/test`
- Test Mapper: `/dashboard/import/pipelines/{id}/mapper/test`

## Configuration

### Import Pipeline Configuration

Configuration files:
- `config/import-pipelines.php`: Pipeline configuration
- `config/import-pipeline-schema.yaml`: Pipeline schema definition

### Package Integration

The `elaitech/import` package is installed as a local Composer package and is automatically discovered by Laravel. The package's service provider (`Elaitech\Import\Providers\ImportDashboardServiceProvider`) registers all necessary services:

- Downloader services
- Reader services
- Filter services
- Prepare services
- Pipeline services
- DataMapper services
- ImportDashboard services

All package routes, migrations, and configuration files are automatically loaded. You can publish package assets using:

```bash
php artisan vendor:publish --tag=import-config
php artisan vendor:publish --tag=import-migrations
php artisan vendor:publish --tag=import-routes
```

## Development

### Running Tests

```bash
php artisan test
```

### Code Style

```bash
./vendor/bin/pint
```

### TypeScript Generation

```bash
php artisan typescript:transform
```

## Docker Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f app

# Execute commands
docker-compose exec app php artisan migrate
docker-compose exec app composer install
```

## Notes

- **No Product Persistence**: This project does not include product models or persistence logic. The `SavePipe` is a placeholder.
- **No Company Dependencies**: Company-related dependencies have been removed. Pipelines are standalone.
- **Simplified Models**: Models have been simplified to remove domain-specific relationships.

## License

This project is a standalone extraction for import workflow functionality.

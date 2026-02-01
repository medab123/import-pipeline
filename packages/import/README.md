# Elaitech Import Package

A comprehensive import workflow system for Laravel applications.

## Installation

```bash
composer require elaitech/import
```

## Configuration

Publish the configuration files:

```bash
php artisan vendor:publish --tag=import-config
```

This will publish:
- `config/import-pipelines.php` - Main configuration file
- `config/import-pipeline-schema.yaml` - Pipeline schema definition

## Migrations

Publish and run migrations:

```bash
php artisan vendor:publish --tag=import-migrations
php artisan migrate
```

## Routes

The package automatically registers routes. If you need to customize them, you can publish the routes file:

```bash
php artisan vendor:publish --tag=import-routes
```

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

## Service Providers

The package automatically registers the following service providers:
- `ImportDashboardServiceProvider` - Main service provider

## License

MIT

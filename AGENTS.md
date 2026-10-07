# Repository Guidelines

## Project Status & Structure

This directory is currently empty apart from this guide. Its name, `LaravelAPI`, suggests a Laravel API boilerplate, but no application, dependency manifests, tests, or Git history are available yet. Update this guide when the project is initialized.

For a standard Laravel layout, place application code in `app/`, API routes in `routes/api.php`, configuration in `config/`, and migrations and seeders in `database/`. Keep tests in `tests/Feature/` and `tests/Unit/`. Use `resources/` and `public/` for assets if needed. Follow the generated project's actual structure.

## Build, Test, and Development Commands

No commands are currently configured. After initializing Laravel, verify these commands against its manifests:

- `composer install`: install PHP dependencies.
- `php artisan serve`: start the local development server.
- `php artisan migrate`: apply migrations to the configured database; verify the target first.
- `php artisan test`: run the test suite.
- `vendor/bin/pint`: format PHP code, if Laravel Pint is installed.

Add frontend commands only if a `package.json` exists.

## Coding Style & Naming Conventions

Until project tooling defines otherwise, use four-space indentation for PHP, PascalCase class names, camelCase methods, and descriptive names such as `UserController`. Match namespaces to directories. Keep controllers focused and move reusable business logic into dedicated classes. Follow the project's formatter configuration once available.

## Testing Guidelines

No testing framework or coverage threshold is configured yet. For Laravel, use feature tests for HTTP behavior and unit tests for isolated logic. Name test files `*Test.php`. Cover validation, authentication, authorization, and error responses when implementing endpoints. Use a separate test database.

## Commit & Pull Request Guidelines

No Git history exists to establish a commit convention. Use short, imperative subjects, such as `Add user authentication endpoint`. Keep changes focused. Pull requests should explain the purpose, link relevant issues, list verification performed, and document configuration or migration changes. Include request/response examples for API changes.

## Security & Configuration

Never commit credentials or populated `.env` files. Document required settings in `.env.example` using placeholder values. Keep secrets out of logs and test fixtures.

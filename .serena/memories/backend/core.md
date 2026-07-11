# Backend Core
- Laravel application code: app/; route entrypoints: routes/web.php, routes/settings.php, routes/console.php.
- Authentication is Laravel Fortify; auth actions/validation live in app/Actions/Fortify and app/Concerns, provider in app/Providers/FortifyServiceProvider.php.
- Models: app/Models; persistence definitions: database/migrations, database/factories, database/seeders.
- Tests use Pest: tests/Feature for behavior, tests/Unit for isolated units; auth and settings coverage already exists.
- Use named routes and Laravel Wayfinder when frontend code calls backend endpoints.
- Use Artisan make:* --no-interaction for framework files; factories for model setup in tests.
- Before Laravel changes, consult version-scoped Laravel Boost search-docs; use Boost schema/query tools for database inspection.
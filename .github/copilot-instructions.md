# Copilot Instructions for AI Agents

## Project Overview
This codebase is a PHP framework for building conversational AI applications (Yandex Alice / Yandex Dialogs), with a modular architecture inspired by middleware, event-driven, and scene-based design patterns. The main entry point is `src/Alice.php`, which coordinates context, response, and settings management.

## Key Components
- **src/**: Core framework logic.
  - `Alice.php`: Main orchestrator, initializes context and handles requests.
  - `Context.php`, `Response.php`, `Settings.php`: Manage session, user, and response data.
  - `Contracts/`, `Events/`, `Scenes/`, `Services/`, `State/`, `Support/`, `Traits/`, `Types/`: Modular subcomponents for extensibility (e.g., middleware, event dispatching, scene management, asset handling).
- **examples/**: Usage samples and integration demos.
- **context/**: JSON files defining actions, commands, intents, and scenes for conversational flows.
- **helpers.php**: Utility functions for common tasks.

## Developer Workflows
- **Autoloading**: Uses Composer (`composer.json`, `vendor/`). Always require `vendor/autoload.php` for entry scripts.
- **No explicit build step**: PHP is interpreted; changes are reflected immediately.
- **Testing**: No standard test directory detected. If adding tests, follow the structure of existing examples or create a `tests/` folder.
- **Debugging**: Use `examples/` scripts for manual testing. For advanced debugging, leverage Symfony VarDumper (`vendor/symfony/var-dumper`).

## Conventions & Patterns
- **Service boundaries**: Each subfolder in `src/` represents a distinct service or domain (e.g., Scenes, Services, State).
- **Event-driven**: Events are dispatched via `Events/Dispatcher.php` and handled by listeners in `Events/` and `Scenes/`.
- **Middleware**: Implement custom middleware in `Contracts/Middleware.php` and register in the main orchestrator.
- **Scene management**: Scenes and stages are defined in `Scenes/`, with context provided by JSON files in `context/`.
- **Type system**: Strong use of PHP classes for entities, directives, and cards in `Types/`.
- **Extensibility**: Add new features by creating classes in the appropriate domain folder and updating the main orchestrator.

## Integration Points
- **Composer dependencies**: Managed via `composer.json`. Run `composer install` to update dependencies.
- **External libraries**: Symfony VarDumper for debugging, PSR container for dependency injection.
- **JSON context files**: Used for defining conversational logic and flows.

## Examples
- See `examples/llm.php` for integrating with language models.
- See `examples/webhook.php` for webhook handling.
- But not all exampels are documented; explore the `src/` folder for find more.

## Quick Start
1. Run `composer install` if dependencies are missing.
2. Use scripts in `examples/` to test features.
3. Extend by adding new classes to `src/` and updating orchestrator logic.

---
If any section is unclear or missing, please provide feedback to improve these instructions.
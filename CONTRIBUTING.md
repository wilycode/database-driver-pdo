# Contributing to WilyCode Database Driver PDO

Thanks for your interest in contributing!

## Development requirements

- PHP 8.4+
- Composer 2
- `ext-pdo` and `ext-pdo_sqlite` for integration tests
- `wilycode/database-driver` and `wilycode/database-query` installed through Composer

## Before opening a pull request

1. Keep the public `DatabaseInterface` contract compatible with `wilycode/database-driver`.
2. Add regression tests for all behavioral changes, using in-memory SQLite where possible.
3. Run `composer validate --strict`, `composer install`, and `composer test`.
4. Update the English README and changelog when behavior or setup changes.
5. Never commit credentials or actual environment secrets.

Open an issue before making a breaking API change. Contributions are reviewed through GitHub pull requests.

# Contributing

Contributions are welcome. This document covers the basics to get started.

## Prerequisites

- PHP 7.4 or later
- Composer

## Setup

```bash
git clone https://github.com/lepresk/momo-api.git
cd momo-api
composer install
```

## Running tests

```bash
composer test
```

Tests use mocked HTTP responses via fixtures. No real API calls are made. If you add a new method, add a corresponding test in `tests/` with a fixture that matches the MTN API response format.

## Static analysis

```bash
composer phpstan
```

PHPStan is configured at the strictest level. Ensure there are no errors before submitting a PR.

## Submitting changes

1. Fork the repository
2. Create a branch from `main`: `git checkout -b feat/your-feature`
3. Make your changes and add tests
4. Ensure `composer test` and `composer phpstan` both pass
5. Open a pull request with a clear description of what you changed and why

## Commit style

Use [Conventional Commits](https://www.conventionalcommits.org):

```
feat: add remittance product support
fix: handle 409 conflict on duplicate reference ID
docs: update disbursement usage example
test: add collection balance fixture test
```

## Reporting issues

Open an issue on [GitHub](https://github.com/lepresk/momo-api/issues) with:
- the version you are using (`composer show lepresk/momo-api`)
- a minimal reproduction
- the expected vs actual behavior

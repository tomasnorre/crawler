# Project Guidelines

TYPO3 CMS extension `crawler` (Composer: `tomasnorre/crawler`, namespace `AOE\Crawler\`).
Crawls the TYPO3 page tree for cache warmup, indexing and publishing.

## Stack

- TYPO3 14.3 (TYPO3 13 support has been removed on `main`)
- PHP 8.2, 8.3, 8.4, 8.5
- MariaDB 10.4.3+ / MySQL 8.0.17+ / PostgreSQL 10.0+ / SQLite 3.8.3+
- PHPUnit 11 (unit + functional via `typo3/testing-framework`), Prophecy available
- Infection for mutation testing
- Playwright for acceptance tests (`Tests/Acceptance`, runs against the DDEV devbox)
- Static analysis: PHPStan level 8, Psalm, Rector, ECS

## Architecture

- `Classes/Queue/` – queue processing via the TYPO3 message bus (Symfony Messenger).
  `CrawlUrlMessageDispatcher` dispatches `Message/CrawlUrlMessage`, handled by `Handler/CrawlUrlHandler`.
  Priority transports: `crawler_high`, `crawler`, `crawler_low` (from `sitemap_priority`, see `CrawlPriority`).
  Consume with `typo3 messenger:consume crawler_high crawler crawler_low`.
- `Classes/QueueExecutor.php` + `Classes/CrawlStrategy/` – executes a crawl (Guzzle, sub-process or callback strategy).
- `Classes/Service/` – business logic (configuration, URL building, parameter expansion, page tree, backend log).
- `Classes/Domain/` – models and repositories (`tx_crawler_queue`, `tx_crawler_configuration`).
- `Classes/Command/` – CLI commands (`crawler:buildQueue`, `crawler:flushQueue`).
- `Classes/Controller/Backend/` – backend module (Start Crawling, Crawler Log).
- `Classes/Middleware/` – frontend middlewares that identify and authenticate crawler requests.
- `Classes/Event/` – PSR-14 events offered to integrators. Treat them as public API.
- DI is configured in `Configuration/Services.yaml`.
- `.devbox/` – DDEV setup for local development and acceptance tests.

## Style

- `declare(strict_types=1);` in every PHP file
- New classes are `final` by default (many older classes aren't yet – don't change that unasked)
- Use constructor injection, no `GeneralUtility::makeInstance()` for services
- Keep methods small: cognitive complexity is checked by PHPStan
- Don't add new entries to `phpstan-baseline.neon` or `psalm-baseline.xml`; fix the code instead.
  Shrinking the baselines is welcome.

## Commands

- Unit tests: `composer test:unit`
- Functional tests: `composer test:functional-sqlite` (needs no DB server), `composer test:functional` (MySQL), `composer test:functional-postgre`
- All tests: `composer test:all`
- Mutation tests: `composer test:mutation`
- Acceptance tests: `cd .devbox && ddev start`, then `cd Tests/Acceptance && npx playwright test --grep @v14`
- Static analysis: `composer analyse` (PHPStan), `composer psalm`, `composer rector`
- Code style check: `composer cs-fix`; apply fixes: `composer cs-fix -- --fix`
- All static checks: `composer static`
- Docs: `make docs` / `make test-docs`

Before calling a task done, run the unit tests, `composer test:functional-sqlite`, `composer analyse`, `composer psalm` and `composer cs-fix -- --fix`.

## Preferences

- Prefer unit tests over functional tests: they are faster and improve the mutation score.
  Only write functional tests when the database or the TYPO3 bootstrap is actually needed.

## Rules

- Every feature gets an acceptance test written in Playwright (tag it `@v14`)
- One feature, one PR
- One bugfix, one PR. Always write a failing test that reproduces the bug first, then fix it.
- Add an entry to `CHANGELOG.md` under the current dev version (Added / Changed / Fixed / Deprecated / Removed)
- Breaking changes: prefix the commit with `!!!` and list them in the CHANGELOG under "Removed" or "Changed"
- Commit messages follow the TYPO3 style: `[FEATURE]`, `[BUGFIX]`, `[TASK]`, `[DOCS]` prefix, imperative mood, e.g. `[BUGFIX] Export every log entry in the crawler log CSV`
- Update `Documentation/` when behaviour, configuration or public API changes

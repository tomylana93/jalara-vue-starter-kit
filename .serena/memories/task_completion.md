# Task Completion
- Every code change needs a programmatic test: add/update Pest coverage and run the narrowest relevant test via `php artisan test --compact <file-or-filter>`.
- Run `php artisan wayfinder:generate --with-form --no-interaction` first when routes, controllers, or invokable actions changed, so generated files pass the formatting/lint steps below.
- After PHP edits run `vendor/bin/pint --dirty --format agent`.
- Run PHP static analysis when backend types/logic are affected: `composer run types:check`.
- After frontend edits run relevant checks: `pnpm run lint:check`, `pnpm run format:check`, and `pnpm run types:check`.
- For broad or release-facing changes, use `composer run agent:gate` (runs ci:check — lint:check, format:check, types:check, tests — plus `pnpm run build`). `composer run ci:check` alone skips the frontend build.
- Do not claim completion without checking command exit output; report any checks not run and why.
- Canonical gate order and checklist: `.ai/guidelines/02-ai-workflow.md` §Finishing gate.
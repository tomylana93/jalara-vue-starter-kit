# Task Completion
- Every code change needs a programmatic test: add/update Pest coverage and run the narrowest relevant test via `php artisan test --compact <file-or-filter>`.
- After PHP edits run `vendor/bin/pint --dirty --format agent`.
- Run PHP static analysis when backend types/logic are affected: `composer run types:check`.
- After frontend edits run relevant checks: `pnpm run lint:check`, `pnpm run format:check`, and `pnpm run types:check`.
- Run `pnpm run build` when bundling/integration risk warrants it.
- For broad or release-facing changes, use `composer run ci:check`.
- Do not claim completion without checking command exit output; report any checks not run and why.
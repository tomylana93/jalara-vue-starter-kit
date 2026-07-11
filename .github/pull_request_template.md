## Summary

> Temporary PR titles must follow Conventional Commits, for example
> `feat: add reporting dashboard` or `fix: correct login throttling`.
> Use `!` or a `BREAKING CHANGE` footer for a major release.

Describe the change and the problem it solves.

## Target

- [ ] Temporary branch into `dev`
- [ ] Promotion from `dev` into `main`
- [ ] Hotfix into `main`
- [ ] Synchronization from `main` back into `dev`

## Validation

- [ ] `bash scripts/check-runtime.sh`
- [ ] `composer test`
- [ ] `pnpm run lint:check`
- [ ] `pnpm run format:check`
- [ ] `pnpm run types:check`
- [ ] `pnpm run build`

## Deployment impact

- [ ] No deployment changes
- [ ] Database migration included
- [ ] Queue workers must restart
- [ ] Shared files or environment values must change
- [ ] Manual deployment notes are included

## Notes

Add rollout, rollback, compatibility, or follow-up information here.

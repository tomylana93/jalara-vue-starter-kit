# Conventions
- Follow neighboring files and reuse existing components before creating new abstractions.
- PHP: explicit parameter and return types; constructor property promotion; curly braces always; descriptive names; TitleCase enum cases; PHPDoc instead of inline comments, with array shapes where useful.
- Laravel: use framework conventions, named routes, Eloquent factories in tests, and feature tests by default.
- Vue/TypeScript: single-root SFCs; no explicit any; type-only imports are separate top-level imports; imports grouped and alphabetized with @/** treated as internal.
- JavaScript/TypeScript control structures require braces and blank-line padding around control statements.
- Prettier: 4 spaces, semicolons, single quotes, print width 80; YAML uses 2 spaces.
- Frontend/backend route integration uses generated Wayfinder imports from @/actions or @/routes, never hardcoded application URLs.
# Confirm Password Hydration Fix Design

## Problem Statement

When an authenticated user opens the password-confirmation page with server-side rendering enabled, the browser reports a Vue hydration mismatch. The server renders the password label with `for="true"`, while the client omits the attribute. This produces console noise, means the server and client disagree about the rendered DOM, and weakens the label-to-input association.

Browser automation used during diagnosis also creates a local `.playwright-cli` directory containing timestamped snapshots and console logs. These machine-local artifacts currently appear as untracked repository files.

## Solution

Use the Vue and Reka UI label contract on the password-confirmation page so the password label renders the same `for="password"` association on both the server and client. Protect runtime behavior with an authenticated browser smoke test and protect the exact Vue call-site contract with a focused frontend source test. Browser DOM and raw feature-response probes normalize the attribute in the test environment, so the source-contract seam is the only verified red-capable regression signal for this mismatch.

Treat Playwright CLI session output as disposable local tooling output: ignore the directory repository-wide and remove the currently generated untracked artifacts.

## User Stories

1. As an authenticated user, I want the password-confirmation page to hydrate without errors, so that the page behaves consistently after server rendering.
2. As a keyboard or assistive-technology user, I want the password label associated with its input, so that the form remains accessible and predictable.
3. As a developer, I want a browser regression test for this page, so that an SSR/client rendering mismatch is caught before merging.
4. As a developer, I want browser-generated diagnostic artifacts excluded from Git, so that local troubleshooting does not pollute repository status.
5. As a reviewer, I want the fix limited to the incorrect page usage, so that the shared label component's public behavior is not expanded unnecessarily.

## Implementation Decisions

- Replace the React-style label attribute on the password-confirmation page with the Vue/Reka UI `for` attribute.
- Do not modify the shared label wrapper. Its existing contract already accepts and forwards the correct label props; translating an unsupported alias would hide incorrect call-site usage.
- Add a browser smoke test that authenticates a user, visits the password-confirmation route, verifies the form is visible, and asserts that the page emits neither JavaScript errors nor console logs.
- Add a narrow frontend source-contract test that requires the password label to use `for="password"` and rejects React-style `htmlFor`. This fallback is intentional: both the hydrated DOM and the raw feature response normalize the defective attribute in the test environment and therefore cannot demonstrate RED.
- Keep the existing backend password-confirmation feature tests unchanged unless implementation reveals a behavioral regression. The diagnosed defect is in client hydration, not route or controller behavior.
- Add the Playwright CLI output directory to Git ignore rules using a repository-root pattern.
- Delete the currently generated untracked Playwright CLI snapshot and console-log artifacts. Do not delete or ignore the repository's intentional browser tests or configured browser-test output paths.
- Add no dependencies and make no changes to routes, controllers, authorization, database schema, generated Wayfinder files, or shared UI APIs.

## Testing Decisions

- The browser test exercises the protected page with SSR enabled and guards against runtime JavaScript and console regressions.
- The frontend source-contract test is the red-capable regression test for the exact attribute error. It must fail when the call site uses `htmlFor` and pass when it uses `for="password"`.
- Follow the established Pest browser-test pattern that uses `visit()`, `assertNoJavaScriptErrors()`, and `assertNoConsoleLogs()`.
- Authenticate with a factory-created user before visiting the protected password-confirmation route so the test reaches the actual failing component rather than the login redirect.
- Run the focused source-contract test and browser smoke test first, then the existing password-confirmation feature tests, frontend lint/format checks, and the repository finishing gate.
- Verify repository status after cleanup to confirm `.playwright-cli` no longer appears and no unrelated files changed.

## Out of Scope

- Fixing older hydration warnings reported on unrelated settings pages.
- Changing the shared label component to support React-style aliases.
- Refactoring authentication layouts, form components, SSR infrastructure, or password-confirmation backend behavior.
- Committing Playwright traces, snapshots, screenshots, videos, or console logs as permanent test fixtures.

## Further Notes

- The diagnosis was based on captured browser logs from the password-confirmation page. The key evidence was a server-rendered `for="true"` value versus no client-rendered value, with the component trace pointing to the password label.
- `.playwright-cli` is currently untracked and contains only artifacts generated by the diagnostic session, so cleanup does not remove version-controlled project assets.

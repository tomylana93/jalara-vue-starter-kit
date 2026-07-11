# Frontend Core
- Inertia Vue 3 entrypoint: resources/js/app.ts; pages: resources/js/pages; layouts: resources/js/layouts; shared components: resources/js/components; composables: resources/js/composables.
- Typed shared definitions: resources/js/types; utilities: resources/js/lib.
- Wayfinder-generated code is under resources/js/actions, resources/js/routes, resources/js/wayfinder; import route/controller functions rather than hardcoding URLs.
- Vue components require a single root element. Use Inertia v3 APIs; Axios is not installed, so use Inertia's built-in HTTP facilities.
- UI styling uses Tailwind CSS v4. Prettier sorts Tailwind classes using resources/css/app.css.
- Generated Wayfinder code and resources/js/components/ui are excluded from normal ESLint checks.
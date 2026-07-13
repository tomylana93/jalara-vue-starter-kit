import { execFileSync } from 'node:child_process';

import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

import type { Plugin } from 'vite';

function exportLaravelLang(): boolean {
    try {
        execFileSync('php', ['artisan', 'lang:export'], {
            stdio: 'inherit',
        });

        return true;
    } catch {
        return false;
    }
}

function isLaravelPhpLangFile(path: string): boolean {
    return /(^|\/)lang\/.+\.php$/.test(path.replaceAll('\\', '/'));
}

function laravelLangExport(): Plugin {
    return {
        name: 'laravel-lang-export',
        buildStart(): void {
            if (!exportLaravelLang()) {
                throw new Error('Unable to export Laravel language files.');
            }
        },
        configureServer(server): void {
            server.watcher.add('lang/**/*.php');
            server.watcher.on('all', (_event, path) => {
                if (!isLaravelPhpLangFile(path)) {
                    return;
                }

                if (exportLaravelLang()) {
                    server.ws.send({ type: 'full-reload' });
                }
            });
        },
    };
}

export default defineConfig({
    plugins: [
        laravelLangExport(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
});

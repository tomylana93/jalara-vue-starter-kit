import assert from 'node:assert/strict';
import { readdir, readFile } from 'node:fs/promises';
import { join } from 'node:path';
import test from 'node:test';
import { NodeTypes, baseParse } from '@vue/compiler-dom';
import { parse as parseSfc } from '@vue/compiler-sfc';

const pagesPath = new URL('../../resources/js/pages', import.meta.url);

async function vueFiles(directory: string): Promise<string[]> {
    const entries = await readdir(directory, { withFileTypes: true });
    const paths = await Promise.all(
        entries.map((entry) => {
            const path = join(directory, entry.name);

            return entry.isDirectory() ? vueFiles(path) : [path];
        }),
    );

    return paths.flat().filter((path) => path.endsWith('.vue'));
}

test('all page fragments disable automatic attribute inheritance', async () => {
    const offenders: string[] = [];

    for (const pagePath of await vueFiles(pagesPath.pathname)) {
        const source = await readFile(pagePath, 'utf8');
        const { descriptor } = parseSfc(source, { filename: pagePath });
        const template = descriptor.template?.content;

        if (!template) {
            continue;
        }

        const rootNodes = baseParse(template).children.filter(
            (node) =>
                node.type !== NodeTypes.COMMENT &&
                !(
                    node.type === NodeTypes.TEXT &&
                    node.content.trim().length === 0
                ),
        );
        const options = descriptor.scriptSetup?.content ?? '';

        if (
            rootNodes.length > 1 &&
            !/defineOptions\(\{\s*inheritAttrs\s*:\s*false,/.test(options)
        ) {
            offenders.push(pagePath.replace(`${pagesPath.pathname}/`, ''));
        }
    }

    assert.deepEqual(offenders, []);
});

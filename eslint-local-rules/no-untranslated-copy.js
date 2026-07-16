const presentationAttributes = new Set([
    'aria-label',
    'description',
    'label',
    'placeholder',
    'title',
]);

function containsCopy(value) {
    return /\p{L}/u.test(value.trim());
}

function isSuppressed(sourceCode, node) {
    const target = node.parent ?? node;
    const precedingSource = sourceCode.text.slice(0, target.range[0]);

    return /<!--\s*localization-ignore:\s*[^>\s][^>]*-->\s*$/u.test(
        precedingSource,
    );
}

function rootIdentifier(node) {
    let current = node;

    while (current?.type === 'MemberExpression') {
        current = current.object;
    }

    return current?.type === 'Identifier' ? current.name : null;
}

export default {
    meta: {
        type: 'problem',
        docs: {
            description: 'disallow untranslated presentation copy in Vue templates',
        },
        messages: {
            attribute: 'Translate the static {{name}} attribute.',
            call: 'Translate the user-visible error passed to {{name}}.',
            property: 'Replace the static {{name}} property with a semantic translation key.',
            text: 'Translate this static template text.',
        },
        schema: [],
    },
    create(context) {
        const sourceCode = context.sourceCode;
        const templateVisitor = {
            VAttribute(node) {
                if (
                    node.directive ||
                    !presentationAttributes.has(node.key.name) ||
                    node.value === null ||
                    !containsCopy(node.value.value) ||
                    isSuppressed(sourceCode, node)
                ) {
                    return;
                }

                context.report({
                    node,
                    messageId: 'attribute',
                    data: { name: node.key.name },
                });
            },
            VText(node) {
                if (
                    !containsCopy(node.value) ||
                    isSuppressed(sourceCode, node)
                ) {
                    return;
                }

                context.report({ node, messageId: 'text' });
            },
        };
        const scriptVisitor = {
            CallExpression(node) {
                const argument = node.arguments[0];

                if (
                    node.callee.type !== 'MemberExpression' ||
                    node.callee.property.name !== 'push' ||
                    rootIdentifier(node.callee.object) !== 'errors' ||
                    argument?.type !== 'Literal' ||
                    typeof argument.value !== 'string' ||
                    !containsCopy(argument.value)
                ) {
                    return;
                }

                context.report({
                    node: argument,
                    messageId: 'call',
                    data: { name: 'errors.push' },
                });
            },
            Property(node) {
                const name = node.key.name ?? node.key.value;
                const value = node.value.value;

                if (
                    !presentationAttributes.has(name) ||
                    typeof value !== 'string' ||
                    !containsCopy(value) ||
                    /^[a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+$/u.test(value)
                ) {
                    return;
                }

                context.report({
                    node: node.value,
                    messageId: 'property',
                    data: { name },
                });
            },
        };

        const defineTemplateBodyVisitor =
            context.sourceCode.parserServices.defineTemplateBodyVisitor;

        return typeof defineTemplateBodyVisitor === 'function'
            ? defineTemplateBodyVisitor(templateVisitor, scriptVisitor)
            : scriptVisitor;
    },
};

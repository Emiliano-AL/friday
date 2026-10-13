import type { AnyExtension } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

/**
 * Crea una instancia NUEVA del StarterKit configurado.
 *
 * Las extensiones de Tiptap son stateful por editor: una misma instancia
 * configurada NO puede compartirse entre varios `useEditor` (la segunda
 * instancia rebinding rompe el schema/marks del primero — ver FR-006 y
 * research D4). Cada componente debe llamar a esta factory.
 */
function createStarterKit(): AnyExtension {
    return StarterKit.configure({
        link: {
            isAllowedUri: (url, ctx) =>
                ctx.defaultValidate(url) && /^(https?:|mailto:|#)/i.test(url),
            shouldAutoLink: (url) => /^(https?:|mailto:)/i.test(url),
            HTMLAttributes: {
                target: '_blank',
                rel: 'noopener noreferrer nofollow',
            },
            openOnClick: false,
        },
        heading: { levels: [2, 3] },
    });
}

export default createStarterKit;

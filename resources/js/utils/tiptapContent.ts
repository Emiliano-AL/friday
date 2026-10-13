export interface TiptapTextNode {
    type: 'text';
    text: string;
}

export interface TiptapParagraphNode {
    type: 'paragraph';
    content?: TiptapTextNode[];
}

export interface TiptapDoc {
    type: 'doc';
    content: TiptapParagraphNode[];
}

function emptyDoc(): TiptapDoc {
    return { type: 'doc', content: [{ type: 'paragraph' }] };
}

export function isTiptapJson(value: unknown): boolean {
    if (typeof value !== 'string' || value.trim() === '') {
        return false;
    }

    try {
        const parsed = JSON.parse(value) as unknown;

        return (
            typeof parsed === 'object' &&
            parsed !== null &&
            (parsed as { type?: unknown }).type === 'doc'
        );
    } catch {
        return false;
    }
}

export function docFromPlainText(text: string): TiptapDoc {
    if (text === '') {
        return emptyDoc();
    }

    return {
        type: 'doc',
        content: [
            {
                type: 'paragraph',
                content: [{ type: 'text', text }],
            },
        ],
    };
}

interface TiptapAnyNode {
    text?: string;
    content?: TiptapAnyNode[];
}

function collectText(node: TiptapAnyNode): string {
    if (typeof node.text === 'string') {
        return node.text;
    }

    return (node.content ?? []).map(collectText).join('') + ' ';
}

/**
 * Visible plain text of a description: Tiptap JSON is flattened, legacy plain text passes through.
 */
export function tiptapToPlainText(value: string | null | undefined): string {
    if (value === null || value === undefined || value.trim() === '') {
        return '';
    }

    if (!isTiptapJson(value)) {
        return value.trim();
    }

    return collectText(JSON.parse(value) as TiptapAnyNode)
        .replace(/\s+/g, ' ')
        .trim();
}

export function normalizeToTiptap(
    value: string | null | undefined,
): TiptapDoc {
    if (value === null || value === undefined || value.trim() === '') {
        return emptyDoc();
    }

    if (isTiptapJson(value)) {
        return JSON.parse(value) as TiptapDoc;
    }

    return docFromPlainText(value);
}

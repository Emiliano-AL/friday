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

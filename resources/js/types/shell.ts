export interface ProjectSummary {
    id: number;
    title: string;
    color: string | null;
    icon: string | null;
}

export interface ShellUser {
    name: string;
    email: string;
    avatar?: string | null;
}

export interface NavItem {
    label: string;
    icon: string;
    to: string | null;
    match: string;
    disabled?: boolean;
    badge?: number;
}

export interface CommandAction {
    group: 'create' | 'navigate';
    label: string;
    icon: string;
    shortcut?: string;
    keywords: string[];
    run: () => void;
}

export type ShellPopover = 'profile' | 'notifications' | null;

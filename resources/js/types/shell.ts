export interface ProjectSummary {
    id: number;
    title: string;
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
    label: string;
    icon: string;
    shortcut?: string;
    keywords: string[];
    run: () => void;
}

export type ShellPopover = 'profile' | 'notifications' | null;

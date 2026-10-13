export type TaskTypeValue = 'bug' | 'feature' | 'test' | 'other';
export type TaskPriorityValue = 'low' | 'medium' | 'high' | 'urgent';
export type TaskStatusValue =
    | 'backlog'
    | 'todo'
    | 'in_progress'
    | 'in_review'
    | 'done';

export interface BoardTask {
    id: number;
    code?: string | null;
    title: string;
    description: string | null;
    type: TaskTypeValue;
    typeLabel: string;
    priority: TaskPriorityValue;
    priorityLabel: string;
    status: TaskStatusValue;
    statusLabel: string;
    points?: number | null;
    project: { id: number; title: string } | null;
    sprint: { id: number; name: string } | null;
    assignee: { id: number; name: string; avatar: string | null } | null;
    authorId?: number | null;
    commentsCount: number;
    canUpdate: boolean;
    canDelete: boolean;
}

export type TasksTab = 'all' | 'mine' | 'standalone' | 'urgent' | 'done';
export type TasksView = 'list' | 'kanban';

export interface TaskProjectOption {
    id: number;
    title: string;
    members: { id: number; name: string; avatar: string | null }[];
    sprints: { id: number; name: string }[];
}

export interface TaskOption {
    id: number;
    title: string;
}

export interface ImportPreviewTask {
    line: number;
    title: string;
    type: 'other' | 'feature' | 'bug' | 'test' | null;
    typeLabel: string | null;
    priority: TaskPriorityValue | null;
    priorityLabel: string | null;
    assignee: { id: number; name: string } | null;
    responsibleText: string | null;
    excerpt: string;
    state: 'ok' | 'warning' | 'error' | 'duplicate' | 'repeated';
    messages: string[];
}

export interface ImportPreview {
    destination: { projectId: number | null; label: string };
    tasks: ImportPreviewTask[];
    excluded: Array<{ name: string; reason: string }>;
    counts: { importable: number; total: number };
}

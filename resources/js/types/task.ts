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
    title: string;
    description: string | null;
    type: TaskTypeValue;
    typeLabel: string;
    priority: TaskPriorityValue;
    priorityLabel: string;
    status: TaskStatusValue;
    statusLabel: string;
    project: { id: number; title: string } | null;
    sprint: { id: number; name: string } | null;
    assignee: { id: number; name: string; avatar: string | null } | null;
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

export interface CommentItem {
    id: number;
    body: string;
    author: { id: number; name: string };
    createdAt: string;
}

export interface TaskItem {
    id: number;
    title: string;
    description: string | null;
    type: string;
    typeLabel: string;
    priority: string;
    priorityLabel: string;
    status: string;
    statusLabel: string;
    assignee: { id: number; name: string } | null;
    sprint: { id: number; name: string } | null;
    comments: CommentItem[];
}

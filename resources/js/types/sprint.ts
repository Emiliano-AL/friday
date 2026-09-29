export type SprintStatusValue = 'planned' | 'active' | 'completed';

export interface SprintDetail {
    id: number;
    name: string;
    goal: string | null;
    status: SprintStatusValue;
    statusLabel: string;
    startDate: string;
    endDate: string;
    iteration: { current: number; total: number };
    daysRemaining: number;
    percentElapsed: number;
    canWrite: boolean;
    project: { id: number; title: string };
}

export interface SprintMetrics {
    total: number;
    done: number;
    coverage: number;
    breakdown: Array<{
        status: 'todo' | 'in_progress' | 'in_review' | 'done';
        label: string;
        count: number;
        percent: number;
    }>;
}

export interface SprintTaskContext {
    project: { id: number; title: string };
    members: Array<{ id: number; name: string; avatar: string | null }>;
    sprints: Array<{ id: number; name: string }>;
}

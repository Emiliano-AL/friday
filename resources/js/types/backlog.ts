import type { SprintStatusValue } from './sprint';
import type {
    TaskPriorityValue,
    TaskProjectOption,
    TaskStatusValue,
    TaskTypeValue,
} from './task';

export type BacklogFilter = 'all' | 'without_project' | 'with_project';

export type BacklogSort = 'priority' | 'recent';

export interface BacklogSummary {
    total: number;
    totalPoints: number;
    withoutProject: number;
    withProject: number;
}

export interface BacklogTask {
    id: number;
    code: string | null;
    title: string;
    description: string | null;
    type: TaskTypeValue;
    priority: TaskPriorityValue;
    status: TaskStatusValue;
    points: number | null;
    completedAt: string | null;
    createdAt: string;
    project: { id: number; title: string } | null;
    sprint: { id: number; name: string; status: SprintStatusValue } | null;
    assignee: { id: number; name: string; avatar: string | null } | null;
    authorId: number | null;
    canUpdate: boolean;
    canDelete: boolean;
}

export interface BacklogNextSprint {
    id: number;
    name: string;
    projectId: number;
    projectTitle: string;
    status: Exclude<SprintStatusValue, 'completed'>;
    startsAt: string;
    endsAt: string;
}

export interface BacklogPageProps {
    tasks: BacklogTask[];
    summary: BacklogSummary;
    nextSprint: BacklogNextSprint | null;
    projects: TaskProjectOption[];
}

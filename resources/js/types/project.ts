import type { BoardTask } from '@/types/task';
import type { SprintStatusValue } from '@/types/sprint';

export const PROJECT_PALETTE = [
    '#5b5bd6',
    '#2563eb',
    '#10b981',
    '#f59e0b',
    '#ef4444',
    '#06b6d4',
    '#8b5cf6',
    '#ec4899',
] as const;

export const PROJECT_ICONS = [
    'folder',
    'rocket',
    'bug_report',
    'design_services',
    'code',
    'database',
    'campaign',
    'terminal',
] as const;

export interface ActiveSprint {
    id: number;
    name: string;
    startDate: string;
    endDate: string;
    status: SprintStatusValue;
    statusLabel: string;
    goal: string | null;
}

export interface SprintSummary {
    id: number;
    name: string;
    startDate: string;
    endDate: string;
    status: SprintStatusValue;
    statusLabel: string;
    goal: string | null;
}

export interface ProjectMember {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    openTasksCount: number;
}

export interface ProjectTask extends BoardTask {
    code: string | null;
    points: number | null;
    authorId: number | null;
    createdAt: string;
    completedAt: string | null;
    sprint: { id: number; name: string; status: SprintStatusValue } | null;
}

export interface ProjectDetail {
    id: number;
    title: string;
    description: string | null;
    key: string | null;
    color: string | null;
    icon: string | null;
    targetDate: string | null;
    status: 'active' | 'archived' | 'completed';
    statusLabel: string;
    progress: number;
    taskDoneCount: number;
    taskTotalCount: number;
    activeSprint: ActiveSprint | null;
    owner: ProjectMember;
    members: ProjectMember[];
    sprints: SprintSummary[];
    tasks: ProjectTask[];
    allowedTransitions: string[];
    isOwner: boolean;
}

export interface ProjectPerson {
    id: number;
    name: string;
    avatar: string | null;
}

export interface DirectoryProject {
    id: number;
    title: string;
    description: string | null;
    key: string | null;
    color: string | null;
    icon: string | null;
    targetDate: string | null;
    status: 'active' | 'archived' | 'completed';
    statusLabel: string;
    progress: number;
    taskDoneCount: number;
    taskTotalCount: number;
    activeSprint: ActiveSprint | null;
    owner: ProjectPerson;
    members: ProjectPerson[];
    isOwner: boolean;
}

export interface ProjectCounts {
    active: number;
    completed: number;
    archived: number;
}

export interface ProjectKpis {
    activeProjects: number;
    completedSprintsThisQuarter: number;
}

export interface FlashMessage {
    success: string | null;
    error: string | null;
}

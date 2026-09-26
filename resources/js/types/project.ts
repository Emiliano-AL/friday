import type { TaskItem } from '@/Components/Tasks/types';

export interface ActiveSprint {
    id: number;
    name: string;
    startDate: string;
    endDate: string;
}

export interface SprintSummary {
    id: number;
    name: string;
    startDate: string;
    endDate: string;
}

export interface ProjectMember {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
}

export interface ProjectDetail {
    id: number;
    title: string;
    description: string | null;
    status: 'active' | 'archived' | 'completed';
    statusLabel: string;
    progress: number;
    taskDoneCount: number;
    taskTotalCount: number;
    activeSprint: ActiveSprint | null;
    owner: ProjectMember;
    members: ProjectMember[];
    sprints: SprintSummary[];
    tasks: TaskItem[];
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

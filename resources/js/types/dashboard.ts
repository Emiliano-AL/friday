import type { TaskPriorityValue, TaskStatusValue } from '@/types/task';

export interface ContextSprint {
    name: string;
    coverage: number;
}

export interface DashboardContext {
    priorityTasks: number;
    sprint: ContextSprint | null;
}

export interface SummarySprint {
    id: number;
    name: string;
    coverage: number;
    daysRemaining: number;
    project: { id: number; title: string };
}

export interface SummaryPayload {
    sprint: SummarySprint | null;
    tasks: { active: number; urgent: number };
    projects: { active: number };
}

export interface MyTaskItem {
    id: number;
    title: string;
    status: TaskStatusValue;
    statusLabel: string;
    priority: TaskPriorityValue;
    priorityLabel: string;
    project: { id: number; title: string } | null;
    commentsCount: number;
}

export interface MyTasksPayload {
    total: number;
    items: MyTaskItem[];
}

export interface BurndownPoint {
    date: string;
    ideal: number;
    done: number;
}

export interface BurndownPayload {
    startDate: string;
    endDate: string;
    total: number;
    points: BurndownPoint[];
}

export interface SprintPanelPayload {
    id: number;
    name: string;
    goal: string | null;
    project: { id: number; title: string };
    total: number;
    done: number;
    coverage: number;
    daysRemaining: number;
    burndown: BurndownPayload;
}

export interface ProjectRow {
    id: number;
    title: string;
    progress: number;
    note: string;
}

export interface ProjectsPanelPayload {
    items: ProjectRow[];
}

export interface WeeklyInsight {
    completed: number;
    avgCycleDays: number;
}

export interface ActivityItem {
    id: number;
    type: string;
    typeLabel: string;
    user: { id: number; name: string; avatar: string | null };
    task: { id: number; title: string } | null;
    project: { id: number; title: string };
    sprint: { id: number; name: string } | null;
    createdAt: string;
}

export interface ActivityFeedPayload {
    items: ActivityItem[];
}

export interface Dashboard {
    context: DashboardContext;
    summary: SummaryPayload;
    myTasks: MyTasksPayload;
    sprintPanel: SprintPanelPayload | null;
    projects: ProjectsPanelPayload;
    weeklyInsight: WeeklyInsight | null;
    activity: ActivityFeedPayload;
}

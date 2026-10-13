export interface SprintMetrics {
    total: number;
    done: number;
    coverage: number;
}

interface SprintLike {
    id: number;
}

interface TaskLike {
    status: string;
    sprint?: { id: number } | null;
}

/**
 * Derive real task metrics for one sprint from an already-loaded task list.
 */
export function sprintMetrics(
    sprint: SprintLike,
    tasks: TaskLike[],
): SprintMetrics {
    const mine = tasks.filter((task) => task.sprint?.id === sprint.id);
    const done = mine.filter((task) => task.status === 'done').length;
    const total = mine.length;

    return {
        total,
        done,
        coverage: total === 0 ? 0 : Math.round((done / total) * 100),
    };
}

/**
 * Count remaining weekday days (Mon–Fri) between tomorrow and the end date,
 * mirroring the sprint detail calculation on the server.
 */
export function weekdayDaysRemaining(today: Date, endDate: string): number {
    const end = new Date(`${endDate}T00:00:00`);
    const cursor = new Date(
        today.getFullYear(),
        today.getMonth(),
        today.getDate() + 1,
    );

    let count = 0;

    while (cursor <= end) {
        const day = cursor.getDay();

        if (day !== 0 && day !== 6) {
            count++;
        }

        cursor.setDate(cursor.getDate() + 1);
    }

    return count;
}

# Data Model: Accesos a "Nueva tarea" y "Acciones rápidas"

Sin cambios de base de datos ni de entidades. La tarea se crea con
`TaskController@store` y `SaveTaskRequest` existentes.

## Prop compartido nuevo (lectura)

`taskFormProjects` — misma forma que `TaskProjectOption` (`resources/js/types/task.ts`):

```ts
Array<{
    id: number;
    title: string;
    members: Array<{ id: number; name: string; avatar: string | null }>; // propietario + miembros, únicos
    sprints: Array<{ id: number; name: string }>;                        // solo activos y planificados
}>
```

Origen: `Project::taskFormOptionsFor(User $user)` (proyectos propios o donde es
miembro, ordenados por título). Se evalúa solo bajo demanda (prop *optional*).

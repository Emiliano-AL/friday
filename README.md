# Friday — Project Management System

**Friday** es una plataforma monolítica y ágil para la administración integral de proyectos y flujos de trabajo (software, operaciones, marketing o gestión personal). Permite el seguimiento granular mediante tareas aisladas o estructuradas por sprints, visualizaciones dinámicas (Kanban y Backlog) y métricas de avance global por proyecto.

---

## 1. Stack Tecnológico

- **Backend:** Laravel 13+
- **Frontend:** Vue 3 (Composition API, `<script setup>`)
- **Glue Layer:** Inertia.js V3 (SPA sin API REST desacoplada)
- **Estilos / UI:** Tailwind CSS
- **Autenticación:** Laravel Breeze / Socialite (OAuth)
- **Base de Datos:** PostgreSQL

---

## 2. Alcance del MVP

### 2.1 Autenticación
- Acceso exclusivo mediante **Laravel Socialite** (Google / GitHub).
- Manejo de roles y sesiones nativas de Laravel gestionadas a través de Inertia.

### 2.2 Gestión de Proyectos (CRUD)
- Soporte para proyectos multidisciplinarios (software, marketing, legal, operativo).
- Visualización de métricas de avance global (porcentaje de completitud basado en peso o conteo de tareas).
- Asignación de colaboradores o miembros de equipo.

### 2.3 Gestión de Sprints (CRUD)
- Creación, edición, activación y cierre de sprints.
- Fechas de inicio, fecha fin y objetivo del sprint (*goal*).
- Capacidad de asociar múltiples tareas activas o moverlas al backlog al cerrar ciclo.

### 2.4 Gestión de Tareas (Tasks)
- **Tareas vinculadas a Sprint:** Asignadas a iteraciones temporales activas o planificadas.
- **Tareas aisladas / Pendientes rápidos:** Tareas fuera de sprint para resolución inmediata o backlog general.
- Campos requeridos por tarea:
  - **Título** & **Descripción** (soporte para markdown o rich text).
  - **Proyecto:** `project_id` (obligatorio).
  - **Sprint:** `sprint_id` (opcional/nullable para tareas aisladas).
  - **Responsable:** `assignee_id` (usuario asignado).
  - **Tipo:** `bug`, `feature`, `test`, `other`.
  - **Prioridad:** `low`, `medium`, `high`, `urgent`.
  - **Status:** `backlog`, `todo`, `in_progress`, `in_review`, `done`.
  - **Comentarios:** Sistema relacional para registrar notas y actualizaciones de avance.

### 2.5 Vistas Principales
- **Vista Backlog:** Lista estructurada y ordenable por prioridad/sprint, optimizada para refinamiento rápido y triage de pendientes.
- **Vista Kanban:** Tablero visual interactivo dividido por estados (`todo`, `in_progress`, etc.) con soporte para drag-and-drop.

---

## 3. Arquitectura del Modelo de Datos (Boceto)

---

## 1. `users`

| Field | Type / Details | Description |
| :--- | :--- | :--- |
| `id` | PK | Unique user identifier |
| `name` | String | Full name of the user |
| `email` | String (Unique) | User email address |
| `avatar` | String (Nullable) | URL or path to user profile picture |
| `oauth_provider` | String | Provider name (e.g., `google`, `github`) |
| `oauth_id` | String | Provider-specific identifier |

---

## 2. `projects`

| Field | Type / Details | Description |
| :--- | :--- | :--- |
| `id` | PK | Unique project identifier |
| `title` | String | Project name |
| `description` | Text (Nullable) | Detailed description of the project |
| `status` | Enum (`active`, `archived`, `completed`) | Current project lifecycle status |
| `progress_percentage` | Decimal / Integer | Computed or cached overall completion percentage |

---

## 3. `sprints`

| Field | Type / Details | Description |
| :--- | :--- | :--- |
| `id` | PK | Unique sprint identifier |
| `project_id` | FK $\to$ `projects.id` | Associated project |
| `name` | String | Sprint title/iteration label |
| `goal` | Text (Nullable) | Objectives and deliverables for the sprint |
| `start_date` | Date / Timestamp | Sprint start schedule |
| `end_date` | Date / Timestamp | Sprint target end schedule |
| `status` | Enum (`draft`, `active`, `completed`) | Current status of the sprint |

---

## 4. `tasks`

| Field | Type / Details | Description |
| :--- | :--- | :--- |
| `id` | PK | Unique task identifier |
| `project_id` | FK $\to$ `projects.id` | Associated project |
| `sprint_id` | FK $\to$ `sprints.id` (Nullable) | Associated sprint (nullable for isolated/backlog tasks) |
| `assignee_id` | FK $\to$ `users.id` (Nullable) | User assigned to complete the task |
| `creator_id` | FK $\to$ `users.id` | User who created the task |
| `title` | String | Task title |
| `description` | Text (Nullable) | Full details / markdown content |
| `type` | Enum (`bug`, `feature`, `test`, `other`) | Nature of the work item |
| `priority` | Enum (`low`, `medium`, `high`, `urgent`) | Execution priority level |
| `status` | Enum (`backlog`, `todo`, `in_progress`, `in_review`, `done`) | Workflow stage |
| `order_column` | Integer | Sorting sequence for Backlog and Kanban displays |
| `created_at` | Timestamp | Standard Laravel timestamp |
| `updated_at` | Timestamp | Standard Laravel timestamp |

---

## 5. `task_comments`

| Field | Type / Details | Description |
| :--- | :--- | :--- |
| `id` | PK | Unique comment identifier |
| `task_id` | FK $\to$ `tasks.id` | Associated task |
| `user_id` | FK $\to$ `users.id` | Author of the comment |
| `content` | Text | Body of the note/comment |
| `created_at` | Timestamp | Standard Laravel timestamp |
| `updated_at` | Timestamp | Standard Laravel timestamp |
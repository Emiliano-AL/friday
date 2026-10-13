<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\SprintStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class UiTestingSeeder extends Seeder
{
    private const string USER_EMAIL = 'landa@mail.com';

    private const int SPRINTS_PER_PROJECT = 4;

    private const int MIN_TASKS_PER_SPRINT = 15;

    private const int MAX_TASKS_PER_SPRINT = 25;

    private const int STANDALONE_TASKS = 12;

    public function run(): void
    {
        $user = User::query()->where('email', self::USER_EMAIL)->firstOrFail();

        $this->limpiarDemoAnterior();

        $totalTareas = 0;

        foreach ($this->proyectosDemo() as $datosProyecto) {
            $project = Project::create([
                'owner_id' => $user->id,
                'title' => $datosProyecto['title'],
                'description' => $datosProyecto['description'],
                'status' => $datosProyecto['status'],
            ]);

            $titulos = array_keys($datosProyecto['tasks']);
            shuffle($titulos);

            $sprints = $this->crearSprints($project, $datosProyecto['sprints']);
            $cursor = 0;

            foreach ($sprints as $indice => $sprint) {
                $restantes = count($titulos) - $cursor;
                $cantidad = min(random_int(self::MIN_TASKS_PER_SPRINT, self::MAX_TASKS_PER_SPRINT), $restantes);

                for ($i = 0; $i < $cantidad; $i++) {
                    $titulo = $titulos[$cursor];
                    $cursor++;

                    Task::create([
                        'project_id' => $project->id,
                        'sprint_id' => $sprint->id,
                        'assignee_id' => mt_rand(1, 100) <= 70 ? $user->id : null,
                        'title' => $titulo,
                        'description' => $this->descripcionPara($titulo, $datosProyecto['tasks'][$titulo]),
                        'type' => $datosProyecto['tasks'][$titulo],
                        'priority' => $this->prioridadAleatoria(),
                        'status' => $this->estadoParaSprint($sprint->status),
                    ]);
                }

                $totalTareas += $cantidad;
            }
        }

        foreach ($this->tareasSueltasDemo() as $titulo => $tipo) {
            Task::create([
                'project_id' => null,
                'sprint_id' => null,
                'assignee_id' => $user->id,
                'title' => $titulo,
                'description' => $this->descripcionPara($titulo, $tipo),
                'type' => $tipo,
                'priority' => $this->prioridadAleatoria(),
                'status' => $this->estadoParaSuelta(),
            ]);
        }

        $this->command->info(sprintf(
            'Demo UI creada: %d proyectos con %d sprints, %d tareas en sprints y %d tareas sueltas para %s.',
            count($this->proyectosDemo()),
            count($this->proyectosDemo()) * self::SPRINTS_PER_PROJECT,
            $totalTareas,
            count($this->tareasSueltasDemo()),
            $user->email,
        ));
    }

    /**
     * Elimina los datos generados por una ejecución anterior de este seeder.
     */
    private function limpiarDemoAnterior(): void
    {
        Project::query()->whereIn('title', array_column($this->proyectosDemo(), 'title'))->delete();

        Task::query()
            ->whereNull('project_id')
            ->whereIn('title', array_keys($this->tareasSueltasDemo()))
            ->delete();
    }

    /**
     * Crea los 4 sprints de un proyecto con fechas y estados coherentes.
     *
     * @param  array<int, array{name: string, goal: string}>  $sprints
     * @return array<int, Sprint>
     */
    private function crearSprints(Project $project, array $sprints): array
    {
        $hoy = Carbon::today();

        $ventanas = [
            ['status' => SprintStatus::Completed, 'inicio' => $hoy->copy()->subWeeks(6), 'fin' => $hoy->copy()->subWeeks(4)],
            ['status' => SprintStatus::Completed, 'inicio' => $hoy->copy()->subWeeks(4), 'fin' => $hoy->copy()->subWeeks(2)],
            ['status' => SprintStatus::Active, 'inicio' => $hoy->copy()->subDays(3), 'fin' => $hoy->copy()->addDays(11)],
            ['status' => SprintStatus::Planned, 'inicio' => $hoy->copy()->addWeeks(2), 'fin' => $hoy->copy()->addWeeks(4)],
        ];

        $creados = [];

        foreach ($sprints as $indice => $datos) {
            $creados[] = Sprint::create([
                'project_id' => $project->id,
                'name' => $datos['name'],
                'goal' => $datos['goal'],
                'status' => $ventanas[$indice]['status'],
                'start_date' => $ventanas[$indice]['inicio']->toDateString(),
                'end_date' => $ventanas[$indice]['fin']->toDateString(),
            ]);
        }

        return $creados;
    }

    private function prioridadAleatoria(): TaskPriority
    {
        $azar = mt_rand(1, 100);

        if ($azar <= 20) {
            return TaskPriority::Low;
        }

        if ($azar <= 65) {
            return TaskPriority::Medium;
        }

        if ($azar <= 90) {
            return TaskPriority::High;
        }

        return TaskPriority::Urgent;
    }

    private function estadoParaSprint(SprintStatus $sprintStatus): TaskStatus
    {
        $azar = mt_rand(1, 100);

        return match ($sprintStatus) {
            SprintStatus::Completed => $azar <= 85 ? TaskStatus::Done : TaskStatus::InReview,
            SprintStatus::Active => match (true) {
                $azar <= 20 => TaskStatus::Done,
                $azar <= 55 => TaskStatus::InProgress,
                $azar <= 75 => TaskStatus::InReview,
                default => TaskStatus::Todo,
            },
            SprintStatus::Planned => $azar <= 70 ? TaskStatus::Todo : TaskStatus::Backlog,
        };
    }

    private function estadoParaSuelta(): TaskStatus
    {
        $azar = mt_rand(1, 100);

        if ($azar <= 45) {
            return TaskStatus::Todo;
        }

        if ($azar <= 70) {
            return TaskStatus::Backlog;
        }

        if ($azar <= 85) {
            return TaskStatus::InProgress;
        }

        return TaskStatus::InReview;
    }

    /**
     * Genera la descripción de una tarea: a veces vacía, a veces texto plano
     * heredado y a veces un documento JSON de Tiptap con formato.
     */
    private function descripcionPara(string $titulo, TaskType $tipo): ?string
    {
        $azar = mt_rand(1, 100);

        if ($azar <= 25) {
            return null;
        }

        $detalle = sprintf('%s. Revisar los criterios de aceptación con el equipo antes de darla por cerrada.', $titulo);

        if ($azar <= 60) {
            return $detalle;
        }

        $doc = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'marks' => [['type' => 'bold']],
                            'text' => 'Objetivo:',
                        ],
                        [
                            'type' => 'text',
                            'text' => ' '.$detalle,
                        ],
                    ],
                ],
                [
                    'type' => 'bulletList',
                    'content' => [
                        [
                            'type' => 'listItem',
                            'content' => [
                                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Definir el alcance con una estimación realista.']]],
                            ],
                        ],
                        [
                            'type' => 'listItem',
                            'content' => [
                                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Implementar y añadir pruebas de '.$tipo->label().'.']]],
                            ],
                        ],
                        [
                            'type' => 'listItem',
                            'content' => [
                                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Solicitar la revisión del equipo.']]],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return json_encode($doc, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<int, array{title: string, description: string, status: ProjectStatus, sprints: array<int, array{name: string, goal: string}>, tasks: array<string, TaskType>}>
     */
    private function proyectosDemo(): array
    {
        $genericas = $this->tareasGenericas();

        return [
            [
                'title' => 'Rediseño tienda online',
                'description' => 'Modernización completa de la tienda: nuevo catálogo, checkout exprés y panel de administración para el equipo comercial.',
                'status' => ProjectStatus::Active,
                'sprints' => [
                    ['name' => 'Cimientos', 'goal' => 'Definir la base visual y técnica del nuevo storefront.'],
                    ['name' => 'Catálogo y buscador', 'goal' => 'Completar la experiencia de descubrimiento de productos.'],
                    ['name' => 'Checkout exprés', 'goal' => 'Reducir el abandono del carrito con un pago en tres pasos.'],
                    ['name' => 'Pulido y lanzamiento', 'goal' => 'Cerrar deuda técnica y preparar el despliegue a producción.'],
                ],
                'tasks' => array_merge([
                    'Investigar la competencia y referencias del sector' => TaskType::Other,
                    'Definir la paleta de colores y tipografías' => TaskType::Feature,
                    'Crear el sistema de diseño inicial' => TaskType::Feature,
                    'Maquetar la página de inicio' => TaskType::Feature,
                    'Configurar el entorno de desarrollo' => TaskType::Other,
                    'Implementar la navegación principal' => TaskType::Feature,
                    'Corregir el menú móvil que se superpone al banner' => TaskType::Bug,
                    'Grid de productos con tarjetas reutilizables' => TaskType::Feature,
                    'Filtros por categoría y precio' => TaskType::Feature,
                    'Ordenar resultados por relevancia y precio' => TaskType::Feature,
                    'Buscador con autocompletado' => TaskType::Feature,
                    'Página de detalle de producto' => TaskType::Feature,
                    'Galería de imágenes con zoom' => TaskType::Feature,
                    'Corregir las imágenes que no cargan en Safari' => TaskType::Bug,
                    'Carrito de compras persistente' => TaskType::Feature,
                    'Resumen del pedido con totales' => TaskType::Feature,
                    'Formulario de dirección con validación' => TaskType::Feature,
                    'Integrar la pasarela de pago' => TaskType::Feature,
                    'Corregir el doble cobro al reintentar el pago' => TaskType::Bug,
                    'Pantalla de confirmación del pedido' => TaskType::Feature,
                    'Emails de confirmación y seguimiento' => TaskType::Feature,
                    'Pruebas end-to-end del flujo de compra' => TaskType::Test,
                    'Optimizar el tiempo de carga de la portada' => TaskType::Other,
                    'Revisar la accesibilidad del checkout' => TaskType::Test,
                    'Migrar el catálogo antiguo' => TaskType::Other,
                    'Diseñar las variantes de producto' => TaskType::Feature,
                    'Cupones de descuento por código' => TaskType::Feature,
                    'Programa de puntos para clientes frecuentes' => TaskType::Feature,
                    'Comparador de productos' => TaskType::Feature,
                    'Lista de deseos del usuario' => TaskType::Feature,
                    'Valoraciones con fotos de clientes' => TaskType::Feature,
                    'Seguimiento del pedido en tiempo real' => TaskType::Feature,
                    'Corregir el cálculo de envíos gratis' => TaskType::Bug,
                    'Corregir el descuento que no se aplica en el total' => TaskType::Bug,
                    'Panel de administración de pedidos' => TaskType::Feature,
                    'Informes de ventas por periodo' => TaskType::Feature,
                    'Gestión de devoluciones' => TaskType::Feature,
                    'Notificaciones push de ofertas' => TaskType::Feature,
                    'Probar el flujo completo en móviles' => TaskType::Test,
                    'Redactar las preguntas frecuentes' => TaskType::Other,
                    'Preparar el plan de lanzamiento' => TaskType::Other,
                    'Sesión de pruebas con usuarios reales' => TaskType::Test,
                    'Corregir los filtros que fallan con tildes' => TaskType::Bug,
                    'Ajustar el diseño responsivo del carrito' => TaskType::Feature,
                    'Integrar el chat de atención al cliente' => TaskType::Feature,
                    'Auditar el SEO de las fichas de producto' => TaskType::Other,
                ], $genericas),
            ],
            [
                'title' => 'App finanzas personales',
                'description' => 'Aplicación para registrar cuentas y movimientos, definir presupuestos mensuales y visualizar informes de gasto.',
                'status' => ProjectStatus::Active,
                'sprints' => [
                    ['name' => 'Fundaciones', 'goal' => 'Levantar la base de la aplicación y el acceso de usuarios.'],
                    ['name' => 'Cuentas y movimientos', 'goal' => 'Gestionar cuentas e importar movimientos sin duplicados.'],
                    ['name' => 'Presupuestos y alertas', 'goal' => 'Definir presupuestos por categoría con alertas visibles.'],
                    ['name' => 'Informes y exportación', 'goal' => 'Visualizar el gasto mensual y exportar los datos.'],
                ],
                'tasks' => array_merge([
                    'Definir los modelos de datos financieros' => TaskType::Feature,
                    'Investigar apps de finanzas del mercado' => TaskType::Other,
                    'Diseñar el onboarding de nuevos usuarios' => TaskType::Feature,
                    'Implementar registro e inicio de sesión' => TaskType::Feature,
                    'Corregir el error de sesión expirada' => TaskType::Bug,
                    'Crear cuenta bancaria manual' => TaskType::Feature,
                    'Importar movimientos desde CSV' => TaskType::Feature,
                    'Listar movimientos con paginación' => TaskType::Feature,
                    'Categorizar movimientos automáticamente' => TaskType::Feature,
                    'Corregir la duplicación al importar CSV' => TaskType::Bug,
                    'Crear presupuesto mensual por categoría' => TaskType::Feature,
                    'Editar y eliminar presupuestos' => TaskType::Feature,
                    'Alertas al superar el presupuesto' => TaskType::Feature,
                    'Gráfico de gastos por categoría' => TaskType::Feature,
                    'Corregir el gráfico que no actualiza al filtrar' => TaskType::Bug,
                    'Pruebas de cálculo de presupuestos' => TaskType::Test,
                    'Informe mensual de ingresos y gastos' => TaskType::Feature,
                    'Exportar movimientos a Excel' => TaskType::Feature,
                    'Exportar informe en PDF' => TaskType::Feature,
                    'Corregir los decimales en el cálculo del total' => TaskType::Bug,
                    'Pruebas de integración de la exportación' => TaskType::Test,
                    'Modo oscuro en toda la aplicación' => TaskType::Feature,
                    'Ajustes de perfil y moneda' => TaskType::Other,
                    'Actualizar la documentación de la API' => TaskType::Other,
                    'Revisión de seguridad de los datos' => TaskType::Test,
                    'Sincronización bancaria automática' => TaskType::Feature,
                    'Objetivos de ahorro con progreso' => TaskType::Feature,
                    'Recordatorios de pagos recurrentes' => TaskType::Feature,
                    'Dividir gastos entre varias personas' => TaskType::Feature,
                    'Etiquetas personalizadas para movimientos' => TaskType::Feature,
                    'Búsqueda de movimientos por concepto' => TaskType::Feature,
                    'Corregir la sincronización que duplica cuentas' => TaskType::Bug,
                    'Corregir el saldo que no cuadra tras borrar' => TaskType::Bug,
                    'Widget de gasto del mes para el escritorio' => TaskType::Feature,
                    'Consejos de ahorro según el gasto' => TaskType::Feature,
                    'Bloqueo de la app con PIN' => TaskType::Feature,
                    'Respaldo cifrado en la nube' => TaskType::Feature,
                    'Pruebas de rendimiento con muchos movimientos' => TaskType::Test,
                    'Localizar la app al inglés' => TaskType::Other,
                    'Revisar los textos legales y privacidad' => TaskType::Other,
                    'Plan de suscripción premium' => TaskType::Feature,
                    'Programa de referidos' => TaskType::Feature,
                    'Corregir la animación que bloquea la lista' => TaskType::Bug,
                    'Onboarding interactivo con ejemplos' => TaskType::Feature,
                    'Soporte para varias monedas' => TaskType::Feature,
                    'Auditoría de permisos de la API' => TaskType::Test,
                ], $genericas),
            ],
            [
                'title' => 'Plataforma de cursos en línea',
                'description' => 'Campus virtual con catálogo de cursos, lecciones en video, cuestionarios y certificados descargables.',
                'status' => ProjectStatus::Archived,
                'sprints' => [
                    ['name' => 'Base del campus', 'goal' => 'Publicar el catálogo y el registro de estudiantes.'],
                    ['name' => 'Contenido y lecciones', 'goal' => 'Cargar lecciones con video y materiales descargables.'],
                    ['name' => 'Evaluaciones y progreso', 'goal' => 'Evaluar con cuestionarios y mostrar el avance del alumno.'],
                    ['name' => 'Certificados y lanzamiento', 'goal' => 'Emitir certificados verificables y abrir el campus al público.'],
                ],
                'tasks' => array_merge([
                    'Definir la arquitectura del campus virtual' => TaskType::Other,
                    'Diseñar la página principal de cursos' => TaskType::Feature,
                    'Catálogo de cursos con filtros' => TaskType::Feature,
                    'Registro de estudiantes' => TaskType::Feature,
                    'Corregir la validación del correo duplicado' => TaskType::Bug,
                    'Reproductor de video con control de velocidad' => TaskType::Feature,
                    'Subir materiales descargables' => TaskType::Feature,
                    'Editor de lecciones con formato' => TaskType::Feature,
                    'Ordenar lecciones con arrastrar y soltar' => TaskType::Feature,
                    'Corregir el progreso que no se guarda al pausar' => TaskType::Bug,
                    'Cuestionarios por lección' => TaskType::Feature,
                    'Banco de preguntas reutilizable' => TaskType::Feature,
                    'Calificación automática de cuestionarios' => TaskType::Feature,
                    'Corregir el cálculo de la nota final' => TaskType::Bug,
                    'Pruebas del flujo de evaluación' => TaskType::Test,
                    'Barra de progreso del curso' => TaskType::Feature,
                    'Panel del instructor con estadísticas' => TaskType::Feature,
                    'Foro de preguntas por lección' => TaskType::Feature,
                    'Generar certificado en PDF' => TaskType::Feature,
                    'Validar certificado con código QR' => TaskType::Feature,
                    'Corregir la fecha del certificado según zona horaria' => TaskType::Bug,
                    'Pruebas de generación de certificados' => TaskType::Test,
                    'Optimizar la carga de videos' => TaskType::Other,
                    'Políticas de reembolso en el checkout' => TaskType::Other,
                    'Preparar el lanzamiento público' => TaskType::Other,
                    'Cupones de descuento para cursos' => TaskType::Feature,
                    'Rutas de aprendizaje combinadas' => TaskType::Feature,
                    'Mensajería entre estudiantes' => TaskType::Feature,
                    'Anuncios del instructor por curso' => TaskType::Feature,
                    'Modo de vista previa para invitados' => TaskType::Feature,
                    'Corregir el video que se reinicia al cambiar de lección' => TaskType::Bug,
                    'Corregir el certificado con nombre vacío' => TaskType::Bug,
                    'Gamificación con insignias' => TaskType::Feature,
                    'Leaderboard de estudiantes' => TaskType::Feature,
                    'Subtítulos automáticos de los videos' => TaskType::Feature,
                    'Descarga de lecciones para ver sin conexión' => TaskType::Feature,
                    'Pruebas de carga con clases en vivo' => TaskType::Test,
                    'Revisar los términos de uso del campus' => TaskType::Other,
                    'Programa de afiliados' => TaskType::Feature,
                    'Webinars mensuales de comunidad' => TaskType::Feature,
                    'Corregir el buscador que ignora acentos' => TaskType::Bug,
                    'Página de perfil público del instructor' => TaskType::Feature,
                    'Reseñas y valoración de cursos' => TaskType::Feature,
                    'Integración con pasarela de pago local' => TaskType::Feature,
                    'Auditoría de accesibilidad del reproductor' => TaskType::Test,
                ], $genericas),
            ],
        ];
    }

    /**
     * Tareas de desarrollo genéricas que complementan las temáticas de cada
     * proyecto hasta cubrir el máximo de 100 títulos únicos por proyecto.
     *
     * @return array<string, TaskType>
     */
    private function tareasGenericas(): array
    {
        return [
            'Escribir pruebas unitarias del módulo' => TaskType::Test,
            'Revisar la accesibilidad de las pantallas' => TaskType::Test,
            'Documentar la API para el equipo' => TaskType::Other,
            'Optimizar las consultas lentas' => TaskType::Other,
            'Actualizar las dependencias del proyecto' => TaskType::Other,
            'Configurar el entorno de staging' => TaskType::Other,
            'Revisar los textos y traducciones' => TaskType::Other,
            'Añadir logs de auditoría' => TaskType::Feature,
            'Refactorizar el servicio de notificaciones' => TaskType::Other,
            'Mejorar los mensajes de error' => TaskType::Feature,
            'Configurar la integración continua' => TaskType::Other,
            'Revisar la cobertura de tests' => TaskType::Test,
            'Actualizar el README del repositorio' => TaskType::Other,
            'Limpiar código muerto' => TaskType::Other,
            'Añadir paginación a las listas' => TaskType::Feature,
            'Revisar los permisos por rol' => TaskType::Test,
            'Añadir indicadores de carga' => TaskType::Feature,
            'Revisar el comportamiento sin conexión' => TaskType::Test,
            'Preparar el checklist de despliegue' => TaskType::Other,
            'Auditar las consultas N+1' => TaskType::Test,
            'Revisar el contraste de colores' => TaskType::Test,
            'Escribir la guía de contribución' => TaskType::Other,
            'Configurar alertas de monitoreo' => TaskType::Other,
            'Revisar el manejo global de errores' => TaskType::Test,
            'Probar en dispositivos antiguos' => TaskType::Test,
            'Revisar la configuración de caché' => TaskType::Other,
            'Documentar las decisiones técnicas' => TaskType::Other,
            'Añadir validaciones del lado del servidor' => TaskType::Feature,
            'Revisar la estructura de rutas' => TaskType::Other,
            'Crear bocetos de las pantallas restantes' => TaskType::Other,
            'Corregir las traducciones faltantes' => TaskType::Bug,
            'Arreglar la navegación por teclado' => TaskType::Bug,
            'Corregir el desbordamiento en móviles' => TaskType::Bug,
            'Arreglar el foco que se pierde en los modales' => TaskType::Bug,
            'Corregir las fechas en el huso horario local' => TaskType::Bug,
            'Escribir tests de humo del flujo crítico' => TaskType::Test,
            'Añadir tests de contrato de la API' => TaskType::Test,
            'Probar la carga con usuarios concurrentes' => TaskType::Test,
            'Revisar la deuda técnica del sprint' => TaskType::Other,
            'Preparar la demo para los stakeholders' => TaskType::Other,
        ];
    }

    /**
     * @return array<string, TaskType>
     */
    private function tareasSueltasDemo(): array
    {
        return [
            'Actualizar el portafolio personal' => TaskType::Other,
            'Revisar la suscripción del servidor' => TaskType::Other,
            'Escribir una entrada sobre Vue en el blog' => TaskType::Other,
            'Organizar las fotos de la conferencia' => TaskType::Other,
            'Renovar el dominio personal' => TaskType::Other,
            'Limpiar el correo y archivar los boletines' => TaskType::Other,
            'Preparar la charla del meetup' => TaskType::Other,
            'Hacer copia de seguridad del portátil' => TaskType::Other,
            'Probar el nuevo gestor de contraseñas' => TaskType::Other,
            'Planificar el viaje del equipo' => TaskType::Other,
            'Actualizar el currículum' => TaskType::Other,
            'Investigar herramientas de analítica' => TaskType::Other,
        ];
    }
}

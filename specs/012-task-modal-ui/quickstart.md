# Quickstart: Validación de Modal de Tarea Mejorado (spec 012)

**Fecha**: 2026-09-30 | **Feature**: [spec.md](spec.md) | **Contrato**: [contracts/012-task-modal-ui.md](contracts/012-task-modal-ui.md)

Guía para validar la feature de extremo a extremo. Prerequisito: entorno local
con Herd (servidor + `npm run dev` o build de Vite).

## 1. Verificaciones automáticas (regresión)

```bash
# 1. Tipado del frontend (obligatorio tras tocar componentes Vue)
npm run types:check

# 2. Build de producción (obligatorio)
npm run build

# 3. Regresión del dominio de tareas y del editor (suíte existente, sin tests nuevos)
php artisan test --compact tests/Feature/Tasks tests/Feature/RichText
```

**Resultado esperado**: `vue-tsc` sin errores; build exitoso; todas las pruebas
de Tasks/RichText en verde (el contrato HTTP de tareas no cambió).

> **Baseline registrada (2026-09-30)**: `npm run types:check` y `npm run build`
> ejecutados y en verde antes de iniciar la implementación de la spec 012.

## 2. Validación manual del diálogo (modo creación)

Desde `http://friday.test/tasks` (o el board de un proyecto), pulsar "Nueva
tarea" y comprobar:

- [ ] **Apertura compacta (G1, G2)**: se ven sin scroll el título, tipo,
  estado, prioridad, responsable, descripción y las acciones del pie; las
  secciones "Vinculación y alcance" y "Estimación y fechas" aparecen colapsadas
  con control "Mostrar" + chevron.
- [ ] **Acordeones (G2, G3, G4)**: expandir "Vinculación y alcance", elegir un
  proyecto y un sprint, colapsar y reexpandir — los valores se conservan.
- [ ] **Transición suave (SC-005)**: el despliegue/repliegue se anima de forma
  fluida (< 0,5 s), el chevron rota y el hint alterna Mostrar/Ocultar.
- [ ] **Altura de descripción (G6)**: el área muestra ~10 líneas vacías; pegar
  ~20 líneas de texto crece el área hasta su tope y luego recorre con scroll
  interno; las acciones del pie no se desplazan fuera del diálogo.
- [ ] **Pie unificado (G7)**: no aparece la caja antigua de "Eliminar"; a la
  izquierda está la casilla "Crear otra tarea al guardar", a la derecha
  "Cancelar" y "Crear Tarea".
- [ ] **Crear-otra (G8)**: activar la casilla, guardar: el formulario se limpia
  y queda listo para otra tarea.
- [ ] **Atajo (G8)**: Ctrl+Enter (Cmd+Enter en macOS) guarda la tarea.
- [ ] **Estimación (G9)**: "Estimación y fechas" se expande y sus campos siguen
  deshabilitados con badge "Próximamente".

## 3. Validación manual (modo edición)

Abrir una tarea existente (backlog o kanban):

- [ ] **Contexto visible (G3)**: si la tarea tiene proyecto/sprint, "Vinculación
  y alcance" aparece expandida mostrándolos; si no, colapsada.
- [ ] **Eliminar alineado (G7)**: "Eliminar tarea" está en el pie, a la
  izquierda, junto a "Cancelar"/"Guardar cambios" en la misma fila; al pulsarlo
  pide confirmación y, tras confirmar, cierra y elimina.
- [ ] **Errores visibles (G5)**: forzar un error que afecte a proyecto/sprint y
  comprobar que su sección se expande sola mostrando el mensaje (flujo real de
  este error hoy poco frecuente; verificar al menos el caso de validación del
  título, que sigue mostrándose en línea).

## 4. Validación de accesibilidad

- [ ] Navegar el diálogo con Tab: los encabezados de sección son botones
  enfocables que anuncian expandido/colapsado (`aria-expanded`).
- [ ] Los campos de las secciones colapsadas quedan fuera de la navegación
  mientras están ocultos.

## 5. Escenarios de borde (spec, Edge Cases)

- [ ] Crear tarea desde un proyecto/sprint con contexto: los valores
  preseleccionados se conservan y la sección de vinculación aparece expandida.
- [ ] Cambiar de proyecto con la sección colapsada: al guardar, el sprint
  incompatible queda limpio (regla existente `FR-010`).
- [ ] Viewport estrecho (~375 px): el diálogo apila campos en una columna y el
  pie mantiene el orden lógico.
- [ ] Reabrir el diálogo: el estado de colapso se restablece (no persiste).

**Nota**: los pasos 2–5 equivalen a las pruebas de aceptación de las user
stories del spec (US1–US3). Si todo lo anterior pasa, la feature está lista
para `/skill:speckit-tasks` / implementación.

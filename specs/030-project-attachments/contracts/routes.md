# Contract: Rutas de adjuntos del proyecto

Todas bajo el grupo `auth` existente en `routes/web.php`, controlador `ProjectAttachmentController`. Respuestas de escritura: `redirect()->route('projects.show', $project)` con flash `success` (patrón Inertia del proyecto); errores de validación vía `errors` de Inertia.

| Método | URI | Nombre | Acción | Body |
|--------|-----|--------|--------|------|
| POST | `projects/{project}/attachments/files` | `projects.attachments.files.store` | Sube un documento o imagen | multipart: `file` |
| POST | `projects/{project}/attachments/links` | `projects.attachments.links.store` | Agrega un link | `url`, `title?` |
| PUT | `projects/{project}/attachments/{attachment}` | `projects.attachments.update` | Renombra | `title` (requerido, ≤ 255) |
| DELETE | `projects/{project}/attachments/{attachment}` | `projects.attachments.destroy` | Elimina fila + archivos | — |
| GET | `projects/{project}/attachments/{attachment}` | `projects.attachments.show` | 302 a URL temporal (5 min) del archivo; `?thumbnail=1` para la miniatura; `?download=1` fuerza descarga con nombre original | — |

Códigos:

- No miembro, o adjunto de otro proyecto → **404**.
- Miembro sin permiso de editar/eliminar ese adjunto → **403**.
- Proyecto no activo en escritura → redirect back con error `project` ("El proyecto no admite cambios en su estado actual.").
- `show` sobre un link → 404 (los links se abren directo).
- Cupo excedido → error de validación en `file` / `url`.

## Prop Inertia en `projects/Show`

`attachments` — `Inertia::optional()`, solo llega con `router.reload({ only: ['attachments'] })`:

```ts
type ProjectAttachments = {
    items: Array<{
        id: number;
        type: 'document' | 'image' | 'link';
        typeLabel: string;           // Documento / Imagen / Link
        title: string;
        url: string | null;          // solo link
        domain: string | null;       // solo link
        originalName: string | null;
        mimeType: string | null;
        size: number | null;         // bytes
        width: number | null;
        height: number | null;
        author: { id: number; name: string; avatar: string | null } | null;
        createdAt: string;           // ISO 8601
        canManage: boolean;          // renombrar/eliminar
        openUrl: string | null;      // ruta show (archivos)
        downloadUrl: string | null;  // ruta show ?download=1
        thumbnailUrl: string | null; // ruta show ?thumbnail=1 (imágenes)
    }>;
    usage: { count: number; maxCount: 30; bytes: number; maxBytes: 209715200 };
    canCreate: boolean;
    limits: {
        imageMaxBytes: 5242880;
        documentMaxBytes: 15728640;
        imageExtensions: string[];   // decide qué límite aplica en la validación previa
        accept: string;
    };
};
```

`limits` alimenta la validación previa en el navegador (FR-014) para no duplicar constantes en el frontend.

## UI

- `ProjectTabs.vue`: nueva pestaña `files` ("Archivos", ícono `attach_file`).
- Nuevo componente `resources/js/Components/Projects/ProjectAttachments.vue`: filtros (Todos/Documentos/Imágenes/Links reutilizando `FilterPills`), barra de uso, zona de subida (input `multiple`, cola secuencial con progreso por archivo), formulario de link, lista con miniaturas, vista ampliada de imagen (reutilizando `Modal.vue`), renombrar inline, eliminar con confirmación, estado vacío y skeleton mientras carga.
- Links: `target="_blank" rel="noopener noreferrer"`.

# Research: Adjuntos y referencias del proyecto

## R1. Almacenamiento de archivos (S3)

- **Decision**: Usar el disco por defecto de Laravel (`config('filesystems.default')`). En producción `FILESYSTEM_DISK=s3` (o el bucket de Laravel Cloud, que es compatible con S3); en desarrollo y tests, `local` (`storage/app/private`, privado). Única dependencia nueva: `league/flysystem-aws-s3-v3 ^3.0` (requisito del driver `s3` según la documentación de Laravel 13) — **aprobada por el usuario (2026-10-17)**.
- **Rationale**: Hoy ningún código usa `Storage::`, así que cambiar el disco por defecto no afecta a nada más. `config/filesystems.php` ya trae el disco `s3` y `.env.example` las variables `AWS_*`. `Storage::fake()` en tests cubre ambos.
- **Alternatives considered**: Disco dedicado `attachments` (configuración extra sin segundo consumidor — YAGNI); subida directa del navegador a S3 con URL prefirmada (más piezas: CORS, confirmación posterior, y de todas formas hay que bajar la imagen al servidor para optimizarla).

## R2. Ruta de subida

- **Decision**: El navegador sube **un archivo por petición** (multipart) al servidor Laravel; con selección múltiple el frontend envía los archivos en secuencia. El servidor valida, optimiza (si es imagen) y escribe en el disco.
- **Rationale**: Resultado independiente por archivo (FR-015) sin lógica de validación parcial en el servidor; progreso por archivo con `form.progress` de Inertia (FR-016); los límites de cupo se comprueban por archivo, de modo que en una subida múltiple entran los que caben.
- **Alternatives considered**: Un único request con `files[]` (la validación de Laravel falla todo el request si un archivo falla; habría que validar a mano).

## R3. Optimización de imágenes

- **Decision**: Extensión **GD** nativa de PHP (ya disponible: `gd`, `exif`, `fileinfo`), sin librería nueva. Flujo para JPG/PNG/WebP:
  1. `getimagesize()` → rechazar si supera **40 megapíxeles** (protección de memoria; una imagen de 5 MB comprimida puede descomprimir a cientos de MB).
  2. Decodificar, aplicar orientación EXIF (`exif_read_data` → rotar/voltear) en JPG.
  3. Reducir a lado mayor ≤ 2560 px (`imagescale`) conservando proporción; PNG/WebP conservan transparencia (`imagealphablending(false)` + `imagesavealpha(true)`).
  4. Re-codificar en el **mismo formato** (JPG calidad 82, WebP calidad 80, PNG compresión 9). La re-codificación descarta EXIF/GPS (FR-021).
  5. Si el resultado pesa más que el original **y** no hubo redimensión ni corrección de orientación **y** el original no trae EXIF (sección IFD0), guardar el original (FR-020). Con EXIF siempre se guarda la versión re-codificada para no filtrar GPS (FR-021 prevalece).
  6. Miniatura: lado mayor 400 px, mismo formato (FR-022).
- GIF: se guarda tal cual (conserva animación) y la miniatura es el mismo archivo (FR-023).
- Procesamiento **síncrono** en el request (imagen ≤ 5 MB, < 1–2 s); se sube `memory_limit` a 512M solo dentro del optimizador (una imagen de 40 MP ocupa ~160 MB descomprimida).
- **Rationale**: Cero dependencias nuevas; GD está en Herd y en las imágenes PHP de Laravel Cloud.
- **Alternatives considered**: `intervention/image` (dependencia nueva para ~60 líneas de GD); Imagick (no garantizado en todos los entornos); cola asíncrona (estado "procesando" en UI, worker obligatorio — innecesario con 5 MB).

## R4. Validación de tipo real

- **Decision**: Regla `File::types([...])->max(...)` / `mimes:` de Laravel, que verifica el MIME detectado por contenido (fileinfo), no solo la extensión. La clasificación documento/imagen se decide por el MIME detectado.
- **Rationale**: Cubre FR-012 con validación nativa.

## R5. Acceso a archivos

- **Decision**: Ruta `GET projects/{project}/attachments/{attachment}` (y variante `?thumb=1`) que autoriza membresía y responde `redirect(Storage::temporaryUrl($path, now()+5 min, [ResponseContentDisposition => attachment/inline; filename=original]))`. El disco `local` ya tiene `serve => true`, así que `temporaryUrl` funciona en desarrollo.
- **Rationale**: Archivos privados (FR-018), enlaces temporales (FR-005), nombre original en la descarga (FR-017), sin servir bytes a través de PHP.
- **Alternatives considered**: Stream desde PHP (`Storage::download`) — consume workers y ancho de banda del servidor.

## R6. Carga de la pestaña "Archivos"

- **Decision**: Prop `attachments` como `Inertia::optional()` en `ProjectController@show`; al abrir la pestaña, `router.reload({ only: ['attachments'] })`. Las URLs firmadas se generan en esa respuesta (expiran en 5 min; si el usuario tarda, el endpoint de R5 regenera porque las miniaturas/enlaces apuntan a la ruta de la app, no a la URL firmada).
- **Rationale**: La vista General no paga el costo de listar adjuntos; patrón ya soportado por Inertia v3.
- **Decision (detalle)**: Las `<img>` y enlaces apuntan a la ruta de la app (R5), que redirige a la URL temporal. Así nunca hay URLs caducadas en la página.

## R7. Límites del servidor

- **Decision**: PHP debe aceptar 15 MB por archivo: `upload_max_filesize=16M`, `post_max_size=20M`. Hoy en local es 2M → configurar en Herd (php.ini) y en el entorno de producción. La validación de Laravel es la que produce el mensaje al usuario; el límite de PHP es solo la red de seguridad.

## R8. Cupos por proyecto (30 adjuntos / 200 MB)

- **Decision**: En el store, dentro de una transacción con `lockForUpdate()` sobre la fila del proyecto: contar adjuntos y sumar `size` antes de insertar; rechazar si se excede. El tamaño que cuenta es el **final** (post-optimización).
- **Rationale**: Evita que dos subidas simultáneas superen el cupo; costo trivial.

## R9. Limpieza de archivos

- **Decision**: Borrar archivo + miniatura en `ProjectAttachmentController@destroy` junto con la fila. Al eliminar un proyecto: `Storage::deleteDirectory("projects/{id}")` en `ProjectController@destroy`; las filas caen por `cascadeOnDelete`.
- **Rationale**: Todos los archivos de un proyecto viven bajo `projects/{id}/attachments/`, así que un solo borrado de directorio cubre FR-008 sin iterar.

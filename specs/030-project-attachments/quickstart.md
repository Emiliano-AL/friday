# Quickstart: validar adjuntos del proyecto

## Prerrequisitos

1. Aprobación y alta de la dependencia: `composer require league/flysystem-aws-s3-v3 "^3.0"`.
2. Límites PHP en Herd (php.ini de la versión 8.4): `upload_max_filesize = 16M`, `post_max_size = 20M`; reiniciar Herd. Verificar: `php -i | grep -E "upload_max|post_max"`.
3. `php artisan migrate`.
4. Desarrollo: `FILESYSTEM_DISK=local` (privado, `serve => true`). Producción: `FILESYSTEM_DISK=s3` + variables `AWS_*` (o bucket de Laravel Cloud).

## Tests

```bash
php artisan test --compact tests/Feature/Projects/ProjectAttachmentsTest.php
php artisan test --compact tests/Feature/Projects/ImageOptimizerTest.php
```

Cubren (con `Storage::fake()` y `UploadedFile::fake()->image(...)`/`->create(...)`):

- Miembro sube documento e imagen → fila + archivo en disco; no miembro → 404.
- Rechazo: documento > 15 MB, imagen > 5 MB, tipo no permitido, ejecutable renombrado a `.pdf`.
- Imagen 4000×3000 → almacenada con lado mayor 2560, miniatura 400, tamaño ≤ original.
- Cupos: el 31.º adjunto se rechaza; un archivo que lleva el total sobre 200 MB se rechaza.
- Link válido/ inválido; título por defecto = dominio.
- Renombrar/eliminar: autor ✓, propietario ✓, otro colaborador 403; eliminar borra archivos.
- Proyecto archivado: lectura ✓, escritura rechazada.
- Eliminar proyecto borra `projects/{id}/` del disco.
- `show` redirige a URL temporal; `?download=1` usa el nombre original.

## Manual (end-to-end)

1. `composer run dev`, entrar a un proyecto → pestaña **Archivos** → estado vacío.
2. Subir una foto de celular (3–5 MB) + un PDF a la vez → progreso por archivo; ambos aparecen; la foto con miniatura, orientación correcta, peso mostrado menor al original.
3. Subir una imagen de 6 MB → mensaje "no puede superar 5 MB" antes de transferir.
4. Agregar link `https://figma.com/...` sin título → muestra `figma.com`, abre en pestaña nueva.
5. Barra de uso refleja "3 de 30 adjuntos · X de 200 MB".
6. Como colaborador sin autoría: no ve acciones de renombrar/eliminar en adjuntos ajenos.
7. Archivar el proyecto → la pestaña es de solo lectura.

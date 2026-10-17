# Quickstart: validar la landing pública (028)

## Prerrequisitos

- App local servida por Herd (`https://friday.test`) y `npm run dev` corriendo.
- (Recomendado) Diseño exportado de Stitch en
  `specs/028-public-landing-page/design/` (ver research D2) para comparar.

## 1. Pruebas automatizadas

```bash
php artisan test --compact tests/Feature/Landing
php artisan test --compact tests/Feature/Home tests/Feature/Branding tests/Feature/ExampleTest.php
npm run check   # vue-tsc / lint del frontend
```

Esperado: todo en verde. `NoLegacyBrandInUiTest` confirma que no aparece
"Friday" (SC-005).

## 2. Invitado ve la landing (US1, SC-001)

1. Abre `https://friday.test/` en una ventana privada.
2. Esperado: carga la landing (no hay redirección a `/login`). La pestaña dice
   "… - Eunox" y se ven el header, hero, características, cómo funciona, CTA
   final y footer.

## 3. Conversión (US2, SC-003)

1. Haz clic en "Comenzar gratis" (header, hero y CTA final). Esperado: llega a
   `/register`.
2. Haz clic en "Iniciar sesión". Esperado: llega a `/login`.
3. Haz clic en "Características" y en "Cómo funciona". Esperado: la página se
   desplaza a la sección correspondiente.
4. Regístrate o inicia sesión. Esperado: termina en el dashboard (`/`).

## 4. Usuario autenticado (US3, SC-002)

1. Con sesión iniciada, abre `/`. Esperado: dashboard `AppHome` igual que
   antes.
2. Cierra sesión y vuelve a `/`. Esperado: landing.

## 5. Responsive y fidelidad (US4, SC-006, SC-007)

1. En DevTools, revisa los anchos 375, 768 y 1280 px. Esperado: no hay scroll
   horizontal, los CTA están visibles y el contenido queda en una columna en
   móvil.
2. Compara lado a lado con la captura de Stitch. Esperado: mismo orden de
   secciones, jerarquía y paleta. Las diferencias aceptadas son solo marca
   (Eunox) y responsive.

## 6. Producción

Después del deploy, `curl -sI https://www.eunox.app/` debe devolver `200`
(antes era `302` a `/login`).

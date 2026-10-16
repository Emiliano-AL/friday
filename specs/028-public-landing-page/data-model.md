# Data Model: Landing page pública (028)

Esta feature **no introduce entidades, tablas ni migraciones**. La landing es
estática (FR-012): no consulta datos de la aplicación.

## Props de la página

| Página Inertia | Props propias | Props compartidas usadas                   |
| -------------- | ------------- | ------------------------------------------ |
| `Landing`      | ninguna       | `name` (nombre público de la app, "Eunox") |

La página usa la sesión solo para decidir qué componente renderizar (ver
[contracts/root-route.md](./contracts/root-route.md)). No se envían datos del
usuario a la landing.

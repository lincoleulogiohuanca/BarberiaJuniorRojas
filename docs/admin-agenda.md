---
layout: default
title: Agenda y reservas
---

# Agenda y reservas (admin)

Menú WordPress: **Reservas** (CPT `jr_reservas`) y submenús de agenda / clientes.

## Estados de una cita

| Estado | Significado |
|--------|-------------|
| `pendiente` | Suele ser pago manual (Plin/transferencia) a la espera de verificación |
| `confirmada` | Cita activa en agenda (pago en estudio o Culqi OK) |
| `completada` | Servicio realizado (cuenta para fidelidad) |
| `cancelada` | No ocupa slot |
| `no_show` | Cliente no asistió |

## Agenda

1. Entra a **Reservas → Agenda** (o el listado del CPT filtrado por fecha).
2. Filtra por barbero / día según la UI.
3. Abre una reserva para editar notas internas, estado o barbero.

## Buenas prácticas

- **No borres** la reserva si solo cancelas: cambia a `cancelada` (libera agenda y puede avisar lista de espera).
- **Completada**: márcala al terminar el servicio (afecta nivel Gold/Platinum).
- **No-show**: marca si no vino; no cuentes como completada.
- Citas con **pago digital**: el cliente no cancela solo (política del tema); cancelación + refund Culqi desde admin.

## Reprogramar

- Cliente: desde **Mi cuenta** (mantiene la misma reserva, cambia fecha/hora).
- Admin: edita meta de fecha/hora en el metabox de la reserva.

## Plugin Core

Si el plugin **Junior Rojas Core** está activo, las citas se indexan en tabla `wp_jr_*` para disponibilidad rápida.  
Tras importar reservas antiguas: **Herramientas → JR DB Backfill**.

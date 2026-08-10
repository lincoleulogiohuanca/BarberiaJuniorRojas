---
layout: default
title: Pagos
---

# Pagos (admin)

## Culqi (tarjeta / Yape online)

1. **Reservas → Ajustes Culqi**
2. Llaves:
   - Local / staging: `pk_test_` / `sk_test_`
   - Producción: **`pk_live_` / `sk_live_`**
3. Secreto de webhook (opcional) y URL:

```text
https://TU-DOMINIO/wp-json/yuniorrojas/v1/culqi/webhook
```

4. Nunca pongas `sk_` en el front ni en GitHub.

### Comportamiento

- Cobro **antes** de crear la cita.
- Si falla guardar la reserva → intento de **reembolso automático**.
- Reintentos del cliente: **idempotency** evita doble cobro del mismo slot/monto.
- Cancelación admin de cita con `culqi_charge_id` → puede reembolsar.

## Medios manuales (Plin, transferencia, etc.)

1. **Reservas → Medios de pago** (CPT).
2. Configura número, QR, tipo (`manual` / `estudio` / `culqi`).
3. El cliente envía **código de operación** y/o **imagen de comprobante**.
4. En admin: **verificar** o **rechazar** el pago.

El comprobante debe ser del propio cliente (ownership del attachment).

## Pago en el estudio

- Cita queda **confirmada**; el dinero se cobra en el local.
- El cliente **puede cancelar** este tipo de cita desde Mi cuenta.

## Ingresos

**Reservas → Ingresos**: filtros por fechas, barbero, método y vista (completadas / proyección).

## Checklist rápido de pagos

- [ ] Llaves del entorno correcto (test vs live)
- [ ] Webhook respondiendo 200 en prod
- [ ] Medios publicados visibles en checkout
- [ ] Probar un cobro controlado + un rechazo

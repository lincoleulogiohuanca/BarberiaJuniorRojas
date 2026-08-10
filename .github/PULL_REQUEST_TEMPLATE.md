<!--
  Checklist de revisión antes de merge a main.
  Quita lo que no aplique; no dejes secretos en el PR.
-->

## Resumen

<!-- ¿Qué cambia y por qué? 1–3 bullets -->



## Test plan

- [ ] Probado en Local (o staging) lo que toca este PR
- [ ] Pasos para revisar:



## Checklist de dominio

### Reservas
- [ ] Disponibilidad / conflictos de horario (si toca slots o duración)
- [ ] Crear / reprogramar / cancelar (estudio) según el cambio
- [ ] Lista de espera / notificaciones (si aplica)

### Pagos (Culqi / manuales)
- [ ] No hay llaves `pk_` / `sk_` ni tokens en el diff
- [ ] Cobro Culqi: monto correcto (incl. fidelidad si aplica)
- [ ] Idempotencia / no doble cargo en retry (si toca cargos)
- [ ] Refund / rechazo admin (si aplica)
- [ ] Plin / comprobante: ownership attachment OK (si aplica)

### SMTP / emails
- [ ] Confirmaciones o recordatorios no se rompen (si toca mails)
- [ ] SMTP sigue configurable por admin / wp-config (sin secretos en Git)

### Core / BD
- [ ] Si tocaste `juniorrojas-core` o tablas: backfill documentado (*Herramientas → JR DB Backfill*)
- [ ] Sync índice de reservas / locks no se saltea en create/cancel

### Front / assets
- [ ] Módulos JS correctos (`js/modules/*`) y enqueue en `functions.php`
- [ ] Responsive básico de la pantalla tocada

### Deploy / CI
- [ ] CI **Theme smoke** en verde
- [ ] No sube `wp-config`, dumps SQL ni uploads
- [ ] ¿Requiere deploy?
  - [ ] Solo Local
  - [ ] Staging (push `developer`)
  - [ ] Production (Run workflow manual)

## Notas para el release

<!-- Rellenar si va a tag/release: impacto para CHANGELOG -->


## Capturas (opcional)

<!-- UI admin o front -->

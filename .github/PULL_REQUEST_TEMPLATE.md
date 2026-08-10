name: Pull Request
description: Checklist antes de merge a main
body:
  - type: checkboxes
    id: checks
    attributes:
      label: Checklist
      options:
        - label: Base branch correcta (normalmente `main` desde `developer`)
        - label: CI "Theme smoke" en verde
        - label: No hay secretos (Culqi, SMTP, FTP) en el diff
        - label: Probado en Local lo que cambia (reservas / pagos / admin)
        - label: Si toca schema/plugin Core → documentar backfill o migrate
  - type: textarea
    id: summary
    attributes:
      label: Resumen
      description: ¿Qué cambia y por qué?
    validations:
      required: true
  - type: textarea
    id: test
    attributes:
      label: Cómo probar
      placeholder: |
        1. …
        2. …
    validations:
      required: true
  - type: dropdown
    id: deploy
    attributes:
      label: ¿Requiere deploy a production?
      options:
        - No (solo docs / CI)
        - Staging automático (developer)
        - Production (aprobación environment)
    validations:
      required: true

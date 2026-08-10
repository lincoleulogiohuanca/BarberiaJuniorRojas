# Política de seguridad del repositorio
#
# GitHub (Settings → Security):
# - Dependabot alerts: ON (recomendado)
# - Dependabot security updates: ON
# - Secret scanning: ON cuando el plan/repo lo permita
#   (repos públicos: gratis; privados: según plan GitHub)
#
# Este proyecto NUNCA debe versionar:
# - wp-config.php / .env
# - sk_live_ / sk_test_ Culqi
# - contraseñas SMTP / FTP
# - dumps SQL con datos de clientes
#
# Reportes de vulnerabilidad
# --------------------------
# Abre un issue con label `security` (sin pegar secretos reales)
# o contacta al maintainer del repo en privado.
#
# Pipeline
# --------
# - Theme smoke: lint PHP + bloqueo de semillas Culqi hardcodeadas
# - Deploy: secretos solo en Environments (staging / production)
#
# Después de rotar una credencial filtrada:
# 1. Revocar en Culqi / hosting / SMTP
# 2. Actualizar solo en el servidor (wp-config / admin WP)
# 3. No commitear el valor nuevo

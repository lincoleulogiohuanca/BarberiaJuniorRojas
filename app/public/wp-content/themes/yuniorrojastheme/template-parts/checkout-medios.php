<?php
/**
 * Checkout: medios de pago dinámicos (CPT jr_medio_pago).
 *
 * @var array $medios_checkout
 * @var bool  $culqi_ok
 * @var bool  $culqi_test
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!isset($medios_checkout) || !is_array($medios_checkout)) {
    $medios_checkout = array();
}
?>
<div class="reservar-checkout__pago">
    <h2 class="reservar-checkout__section-title">1. Método de pago</h2>

    <div
        class="reservar-checkout__metodos"
        role="radiogroup"
        aria-label="<?php esc_attr_e('Método de pago', YUNIORROJAS_TEXT_DOMAIN); ?>"
        data-pago-metodos
    >
        <?php foreach ($medios_checkout as $idx => $medio) :
            $slug   = (string) ($medio['slug'] ?? '');
            $nombre = (string) ($medio['nombre'] ?? '');
            $icono  = (string) ($medio['icono'] ?? 'ti ti-cash');
            $tipo   = (string) ($medio['tipo'] ?? 'manual');
            $sel    = $idx === 0;
            ?>
            <button
                type="button"
                class="reservar-checkout__metodo<?php echo $sel ? ' is-selected' : ''; ?>"
                role="radio"
                aria-checked="<?php echo $sel ? 'true' : 'false'; ?>"
                data-pago-metodo="<?php echo esc_attr($slug); ?>"
                data-pago-tipo="<?php echo esc_attr($tipo); ?>"
                data-pago-medio-id="<?php echo esc_attr((string) (int) ($medio['id'] ?? 0)); ?>"
                data-abre-culqi="<?php echo !empty($medio['abre_culqi']) ? '1' : '0'; ?>"
                data-requiere-codigo="<?php echo !empty($medio['requiere_codigo']) ? '1' : '0'; ?>"
            >
                <i class="<?php echo esc_attr($icono); ?>" aria-hidden="true"></i>
                <span><?php echo esc_html($nombre); ?></span>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<?php foreach ($medios_checkout as $idx => $medio) :
    $slug   = (string) ($medio['slug'] ?? '');
    $tipo   = (string) ($medio['tipo'] ?? 'manual');
    $hidden = $idx !== 0;
    $instr  = (string) ($medio['instrucciones'] ?? $medio['descripcion'] ?? '');
    ?>
    <?php if ($tipo === 'culqi') : ?>
        <div class="reservar-checkout__tarjeta" data-pago-panel="<?php echo esc_attr($slug); ?>" <?php echo $hidden ? 'hidden' : ''; ?>>
            <div class="reservar-checkout__tarjeta-card reservar-checkout__tarjeta-card--culqi">
                <div class="reservar-checkout__tarjeta-head">
                    <h3 class="reservar-checkout__tarjeta-title"><?php echo esc_html((string) $medio['nombre']); ?></h3>
                    <i class="ti ti-shield-lock" aria-hidden="true"></i>
                </div>
                <?php if (!empty($culqi_ok)) : ?>
                    <p class="reservar-checkout__culqi-lead">
                        <?php echo esc_html($instr !== '' ? $instr : 'Al pulsar Proceder al pago se abre Culqi (tarjeta o Yape). Confirmación automática.'); ?>
                    </p>
                    <ul class="reservar-checkout__culqi-badges" aria-label="<?php esc_attr_e('Medios Culqi', YUNIORROJAS_TEXT_DOMAIN); ?>">
                        <li>Visa</li>
                        <li>Mastercard</li>
                        <li>Yape</li>
                    </ul>
                    <?php if (!empty($culqi_test)) : ?>
                        <p class="reservar-checkout__test-badge">
                            <i class="ti ti-flask" aria-hidden="true"></i>
                            Modo prueba Culqi.
                        </p>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="reservar-checkout__culqi-lead reservar-checkout__culqi-lead--warn">
                        Culqi no está configurado. Elige otro medio o avisa al estudio.
                    </p>
                <?php endif; ?>
            </div>
            <p class="reservar-checkout__secure">
                <i class="ti ti-info-circle" aria-hidden="true"></i>
                Procesado por Culqi. No almacenamos tarjetas ni códigos de aprobación.
            </p>
        </div>
    <?php elseif ($tipo === 'manual') :
        $num     = (string) ($medio['numero'] ?? '');
        $digits  = (string) ($medio['numero_digits'] ?? '');
        $titular = (string) ($medio['titular'] ?? '');
        $qr      = (string) ($medio['qr_url'] ?? '');
        $tiene_banco = trim((string) ($medio['banco_nombre'] ?? '')) !== ''
            || trim((string) ($medio['banco_cuenta'] ?? '')) !== ''
            || trim((string) ($medio['banco_cci'] ?? '')) !== '';
        ?>
        <div class="reservar-checkout__alt" data-pago-panel="<?php echo esc_attr($slug); ?>" <?php echo $hidden ? 'hidden' : ''; ?>>
            <div class="reservar-yape">
                <h3 class="reservar-yape__title"><?php echo esc_html((string) $medio['nombre']); ?></h3>
                <?php if ($instr !== '') : ?>
                    <p class="reservar-yape__lead"><?php echo esc_html($instr); ?></p>
                <?php endif; ?>

                <?php if ($qr !== '') : ?>
                    <div class="reservar-yape__qr">
                        <img src="<?php echo esc_url($qr); ?>" alt="" width="220" height="220" loading="lazy" decoding="async">
                    </div>
                <?php endif; ?>

                <?php if ($num !== '') : ?>
                    <div class="reservar-yape__numero">
                        <span class="reservar-yape__label">Número a transferir</span>
                        <div class="reservar-yape__numero-row">
                            <strong><?php echo esc_html($num); ?></strong>
                            <?php if ($digits !== '') : ?>
                                <button type="button" class="reservar-yape__copiar" data-copiar-yape data-copiar-valor="<?php echo esc_attr($digits); ?>">
                                    Copiar
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($titular !== '') : ?>
                            <p class="reservar-yape__titular">Titular: <strong><?php echo esc_html($titular); ?></strong></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($tiene_banco) : ?>
                    <div class="reservar-yape__banco">
                        <span class="reservar-yape__label">Datos bancarios</span>
                        <ul class="reservar-yape__banco-list">
                            <?php if (!empty($medio['banco_nombre'])) : ?>
                                <li><span>Banco</span> <strong><?php echo esc_html((string) $medio['banco_nombre']); ?></strong></li>
                            <?php endif; ?>
                            <?php if (!empty($medio['banco_titular'])) : ?>
                                <li><span>Titular</span> <strong><?php echo esc_html((string) $medio['banco_titular']); ?></strong></li>
                            <?php endif; ?>
                            <?php if (!empty($medio['banco_cuenta'])) : ?>
                                <li>
                                    <span>Cuenta</span>
                                    <strong><?php echo esc_html((string) $medio['banco_cuenta']); ?></strong>
                                    <button type="button" class="reservar-yape__copiar" data-copiar-yape
                                        data-copiar-valor="<?php echo esc_attr(preg_replace('/\s+/', '', (string) $medio['banco_cuenta'])); ?>">Copiar</button>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($medio['banco_cci'])) : ?>
                                <li>
                                    <span>CCI</span>
                                    <strong><?php echo esc_html((string) $medio['banco_cci']); ?></strong>
                                    <button type="button" class="reservar-yape__copiar" data-copiar-yape
                                        data-copiar-valor="<?php echo esc_attr(preg_replace('/\s+/', '', (string) $medio['banco_cci'])); ?>">Copiar</button>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <p class="reservar-yape__monto">
                    Monto a transferir:
                    <strong data-yape-monto>S/. 0.00</strong>
                </p>

                <div class="reservar-yape__comprobante">
                    <label class="reservar-checkout__label" for="checkout-codigo-<?php echo esc_attr($slug); ?>">Código de operación</label>
                    <input
                        type="text"
                        id="checkout-codigo-<?php echo esc_attr($slug); ?>"
                        class="reservar-checkout__input"
                        placeholder="Ej. 123456789"
                        autocomplete="off"
                        data-yape-codigo
                        data-medio-codigo="<?php echo esc_attr($slug); ?>"
                    >
                    <label class="reservar-checkout__label" for="checkout-file-<?php echo esc_attr($slug); ?>">Captura (opcional)</label>
                    <input
                        type="file"
                        id="checkout-file-<?php echo esc_attr($slug); ?>"
                        class="reservar-checkout__input reservar-checkout__input--file"
                        accept="image/jpeg,image/png,image/webp"
                        data-yape-comprobante
                        data-medio-comprobante="<?php echo esc_attr($slug); ?>"
                    >
                    <p class="reservar-yape__hint">
                        Transfiere el monto exacto, ingresa el código y confirma. El estudio verificará tu pago.
                    </p>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="reservar-checkout__alt" data-pago-panel="<?php echo esc_attr($slug); ?>" <?php echo $hidden ? 'hidden' : ''; ?>>
            <div class="reservar-checkout__alt-card">
                <i class="<?php echo esc_attr((string) ($medio['icono'] ?? 'ti ti-building-store')); ?>" aria-hidden="true"></i>
                <p><?php echo esc_html($instr !== '' ? $instr : 'Reservarás ahora y pagarás al llegar al estudio.'); ?></p>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

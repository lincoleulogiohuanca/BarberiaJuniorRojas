<?php
/**
 * Template Name: Contacto
 * Diseño: Contacto Junior Rojas
 */
get_header();

$contacto = yuniorrojas_contacto();
$whatsapp_link = 'https://wa.me/' . preg_replace('/\D+/', '', $contacto['whatsapp']);
$telefono_href = 'tel:' . preg_replace('/\s+/', '', $contacto['telefono']);
?>

<main class="page-contacto">

    <?php
    get_template_part('template-parts/page', 'hero', array(
        'titulo'    => 'Contacto',
        'subtitulo' => yuniorrojas_eslogan(),
    ));
    ?>

    <section class="contacto seccion">
        <div class="contenedor contacto__grid">

            <div class="contacto__info">
                <h2 class="contacto__title">Conéctate con nosotros</h2>

                <ul class="contacto__list">
                    <li class="contacto__item">
                        <i class="ti ti-brand-whatsapp" aria-hidden="true"></i>
                        <div class="contacto__item-body">
                            <span class="contacto__label">WhatsApp</span>
                            <a class="contacto__value" href="<?php echo esc_url($whatsapp_link); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html($contacto['whatsapp']); ?>
                            </a>
                            <a class="contacto__wa-btn" href="<?php echo esc_url($whatsapp_link); ?>" target="_blank" rel="noopener noreferrer">
                                Mensaje directo
                            </a>
                        </div>
                    </li>
                    <li class="contacto__item">
                        <i class="ti ti-phone" aria-hidden="true"></i>
                        <div class="contacto__item-body">
                            <span class="contacto__label">Teléfono</span>
                            <a class="contacto__value" href="<?php echo esc_url($telefono_href); ?>">
                                <?php echo esc_html($contacto['telefono']); ?>
                            </a>
                        </div>
                    </li>
                    <li class="contacto__item">
                        <i class="ti ti-map-pin" aria-hidden="true"></i>
                        <div class="contacto__item-body">
                            <span class="contacto__label">Ubicación</span>
                            <p class="contacto__value"><?php echo esc_html($contacto['direccion']); ?></p>
                        </div>
                    </li>
                </ul>

                <div class="contacto__horarios">
                    <h3 class="contacto__horarios-title">Horarios de atención</h3>
                    <ul class="contacto__horarios-list">
                        <?php foreach ((array) $contacto['horarios'] as $horario) : ?>
                            <?php
                            $dia  = is_array($horario) ? (string) ($horario['dia'] ?? '') : '';
                            $hora = is_array($horario) ? (string) ($horario['hora'] ?? '') : '';
                            if ($dia === '') {
                                continue;
                            }
                            $es_cerrado = (bool) preg_match('/cerrado/i', $hora);
                            ?>
                            <li class="contacto__horarios-item">
                                <span><?php echo esc_html($dia); ?></span>
                                <strong class="<?php echo $es_cerrado ? 'is-cerrado' : ''; ?>">
                                    <?php echo esc_html($hora); ?>
                                </strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="contacto__form-wrap">
                <h2 class="contacto__title">Envíanos un mensaje</h2>
                <div class="contacto__form">
                    <?php
                    $form_shortcode = yuniorrojas_field('formulario_contacto', 'option', '');
                    if ($form_shortcode) {
                        echo do_shortcode($form_shortcode);
                    } else {
                        ?>
                        <form class="jr-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="yuniorrojas_contacto">
                            <input type="hidden" name="yuniorrojas_form_ts" value="<?php echo esc_attr((string) time()); ?>">
                            <?php wp_nonce_field('yuniorrojas_contacto', 'yuniorrojas_contacto_nonce'); ?>

                            <div class="jr-form__honeypot" aria-hidden="true">
                                <label for="yuniorrojas_company">Empresa</label>
                                <input
                                    type="text"
                                    id="yuniorrojas_company"
                                    name="yuniorrojas_company"
                                    value=""
                                    tabindex="-1"
                                    autocomplete="off"
                                >
                            </div>

                            <label>
                                <span>Nombre completo</span>
                                <input type="text" name="nombre" placeholder="Tu nombre" required autocomplete="name">
                            </label>
                            <label>
                                <span>Correo electrónico</span>
                                <input type="email" name="email" placeholder="tu@correo.com" required autocomplete="email">
                            </label>
                            <label>
                                <span>Asunto</span>
                                <input type="text" name="asunto" placeholder="¿En qué podemos ayudarte?" required>
                            </label>
                            <label>
                                <span>Mensaje</span>
                                <textarea name="mensaje" rows="5" placeholder="Escribe tu mensaje aquí..." required></textarea>
                            </label>
                            <button type="submit" class="btn btn--primary btn--block jr-form__submit">Enviar mensaje</button>

                            <?php
                            $estado = isset($_GET['contacto']) ? sanitize_text_field(wp_unslash($_GET['contacto'])) : '';
                            if ($estado === 'ok') :
                                ?>
                                <p class="jr-form__notice jr-form__notice--ok">Mensaje enviado correctamente.</p>
                            <?php elseif ($estado === 'limit') : ?>
                                <p class="jr-form__notice jr-form__notice--error">Has enviado demasiados mensajes. Intenta en unos minutos.</p>
                            <?php elseif ($estado === 'error') : ?>
                                <p class="jr-form__notice jr-form__notice--error">No se pudo enviar. Revisa los datos e inténtalo de nuevo.</p>
                            <?php endif; ?>
                        </form>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>

    <section class="contacto-mapa" aria-label="<?php esc_attr_e('Ubicación en el mapa', 'yuniorrojas'); ?>">
        <?php if (!empty($contacto['mapa_embed'])) : ?>
            <div class="contacto-mapa__embed">
                <?php echo wp_kses($contacto['mapa_embed'], array(
                    'iframe' => array(
                        'src'             => true,
                        'width'           => true,
                        'height'          => true,
                        'style'           => true,
                        'allowfullscreen' => true,
                        'loading'         => true,
                        'referrerpolicy'  => true,
                    ),
                )); ?>
            </div>
        <?php else : ?>
            <div
                id="jr-mapa"
                class="contacto-mapa__leaflet"
                role="region"
                aria-label="<?php echo esc_attr(sprintf(
                    /* translators: %s: street address */
                    __('Mapa de %s', 'yuniorrojas'),
                    $contacto['direccion']
                )); ?>"
            ></div>
            <noscript>
                <div class="contacto-mapa__placeholder">
                    <div class="contacto-mapa__pin">
                        <i class="ti ti-map-pin-filled" aria-hidden="true"></i>
                        <span><?php esc_html_e('Encuéntranos aquí', 'yuniorrojas'); ?></span>
                    </div>
                    <p><?php echo esc_html($contacto['direccion']); ?></p>
                    <p>
                        <a
                            href="<?php echo esc_url(sprintf(
                                'https://www.openstreetmap.org/?mlat=%1$s&mlon=%2$s#map=%3$d/%1$s/%2$s',
                                rawurlencode((string) $contacto['mapa_lat']),
                                rawurlencode((string) $contacto['mapa_lng']),
                                (int) $contacto['mapa_zoom']
                            )); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <?php esc_html_e('Ver en OpenStreetMap', 'yuniorrojas'); ?>
                        </a>
                    </p>
                </div>
            </noscript>
        <?php endif; ?>
    </section>

    <?php
    get_template_part('template-parts/cta', 'banner', array(
        'titulo'   => '¿Listo para elevar tu estilo?',
        'texto'    => 'Reserva tu cita hoy y experimenta la verdadera elegancia masculina en manos de expertos.',
        'variante' => 'oro',
    ));
    ?>

</main>

<?php
get_footer();

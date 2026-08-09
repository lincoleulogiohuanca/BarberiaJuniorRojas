<footer class="footer">
    <div class="contenedor footer__grid">

        <div class="footer__info">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="footer__logo">
                <img
                    src="<?php echo esc_url(get_template_directory_uri() . '/img/logo monograma.png'); ?>"
                    alt="Junior Rojas Barber Studio">
            </a>

            <p class="footer__description">
                Definiendo el estándar de la barbería moderna a través
                de la elegancia masculina y la precisión técnica. Más
                que un corte, una experiencia de distinción.
            </p>

            <?php $redes = yuniorrojas_redes(); ?>
            <?php if (!empty($redes)) : ?>
                <ul class="footer__social-links">
                    <?php foreach ($redes as $red) : ?>
                        <?php
                        $nombre = (string) ($red['nombre'] ?? '');
                        $url    = (string) ($red['url'] ?? '');
                        $icono  = sanitize_key((string) ($red['icono'] ?? 'instagram'));
                        if ($url === '') {
                            continue;
                        }
                        ?>
                        <li>
                            <a
                                href="<?php echo esc_url($url); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="<?php echo esc_attr($nombre !== '' ? $nombre : $icono); ?>">
                                <i class="ti ti-brand-<?php echo esc_attr($icono); ?>" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="footer__menu">
            <h2 class="footer__heading">Navegación</h2>
            <?php
            wp_nav_menu(array(
                'theme_location' => 'menu-principal',
                'container'      => false,
                'menu_class'     => 'footer__menu footer__menu-links',
                'fallback_cb'    => false,
            ));
            ?>
        </div>

        <div class="footer__actions">
            <h2 class="footer__heading">Acciones</h2>
            <ul class="footer__menu footer__actions-links">
                <?php if (yuniorrojas_es_cliente()) : ?>
                    <?php
                    $footer_user   = wp_get_current_user();
                    $footer_name   = trim((string) $footer_user->display_name);
                    if ($footer_name === '') {
                        $footer_name = (string) $footer_user->user_login;
                    }
                    $footer_short  = explode(' ', $footer_name)[0] ?? $footer_name;
                    $footer_avatar = function_exists('yuniorrojas_cliente_avatar_url')
                        ? yuniorrojas_cliente_avatar_url((int) $footer_user->ID, 96)
                        : (string) get_avatar_url((int) $footer_user->ID, array('size' => 64));
                    $cuenta_url    = yuniorrojas_url_cuenta();
                    $logout_url    = wp_logout_url(home_url('/'));
                    ?>
                    <li class="footer__account" data-header-account>
                        <button
                            type="button"
                            class="footer__account-toggle"
                            data-header-account-toggle
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-controls="footer-account-menu">
                            <img
                                class="footer__account-avatar"
                                data-cliente-avatar-img
                                src="<?php echo esc_url($footer_avatar ?: get_template_directory_uri() . '/img/logo monograma.png'); ?>"
                                alt=""
                                width="36"
                                height="36"
                            >
                            <span class="footer__account-name"><?php echo esc_html($footer_short); ?></span>
                            <i class="ti ti-chevron-down footer__account-caret" aria-hidden="true"></i>
                        </button>
                        <div
                            id="footer-account-menu"
                            class="footer__account-menu"
                            data-header-account-menu
                            hidden>
                            <div class="footer__account-menu-head">
                                <strong><?php echo esc_html($footer_name); ?></strong>
                                <span><?php echo esc_html((string) $footer_user->user_email); ?></span>
                            </div>
                            <a href="<?php echo esc_url($cuenta_url); ?>">
                                <i class="ti ti-layout-dashboard" aria-hidden="true"></i>
                                Mi cuenta
                            </a>
                            <a href="<?php echo esc_url(add_query_arg('vista', 'citas', $cuenta_url)); ?>">
                                <i class="ti ti-calendar-event" aria-hidden="true"></i>
                                Mis citas
                            </a>
                            <a href="<?php echo esc_url(add_query_arg('vista', 'preferencias', $cuenta_url)); ?>">
                                <i class="ti ti-settings" aria-hidden="true"></i>
                                Preferencias
                            </a>
                            <a class="footer__account-logout" href="<?php echo esc_url($logout_url); ?>">
                                <i class="ti ti-logout" aria-hidden="true"></i>
                                Cerrar sesión
                            </a>
                        </div>
                    </li>
                <?php elseif (yuniorrojas_es_administrador()) : ?>
                    <li>
                        <a class="footer__menu-link footer__menu-link--outline" href="<?php echo esc_url(admin_url()); ?>">
                            Ir al panel
                        </a>
                    </li>
                <?php else : ?>
                    <li>
                        <a class="footer__menu-link footer__menu-link--outline" href="<?php echo esc_url(yuniorrojas_url_login()); ?>">
                            Iniciar Sesión
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (yuniorrojas_puede_reservar_en_front()) : ?>
                    <li>
                        <a class="footer__menu-link footer__menu-link--button" href="<?php echo esc_url(yuniorrojas_url_reservar()); ?>">
                            Reservar Ahora
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="footer__copyright">
            <p class="footer__copyright-text">
                &copy; <?php echo esc_html(gmdate('Y')); ?>
                <?php echo esc_html(yuniorrojas_field('copyright_text', 'option', 'Junior Rojas Barber Studio. Todos los derechos reservados.')); ?>
            </p>
            <p class="footer__tagline"><?php echo esc_html(yuniorrojas_eslogan()); ?></p>
        </div>
    </div>
</footer>

<?php
// Scroll top en páginas públicas; no en panel cliente (mi-cuenta).
if (!is_page('mi-cuenta') && !is_page_template('page-mi-cuenta.php')) {
    get_template_part('template-parts/scroll', 'to-top');
}
?>

<?php wp_footer(); ?>

</body>

</html>

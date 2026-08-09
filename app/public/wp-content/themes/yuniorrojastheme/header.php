<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>

<body <?php body_class('theme-jr'); ?>>
    <?php wp_body_open(); ?>

    <header class="header" data-open="false">
        <div class="contenedor header__inner">
            <a class="header__logo" href="<?php echo esc_url(home_url('/')); ?>">
                <img
                    src="<?php echo esc_url(yuniorrojas_logo_url()); ?>"
                    alt="<?php echo esc_attr(get_bloginfo('name') ?: 'Junior Rojas Barber Studio'); ?>">
            </a>

            <button
                type="button"
                class="header__toggle"
                aria-label="Abrir menú"
                data-menu-toggle
                aria-expanded="false"
                aria-controls="header-navigation">
                <span class="header__toggle-line"></span>
                <span class="header__toggle-line"></span>
            </button>

            <!--
              Shell a viewport completo: recorta el panel off-canvas
              para que NO amplíe el ancho del documento (scroll horizontal).
            -->
            <div class="header__shell" data-menu-shell>
                <div class="header__overlay" data-menu-overlay></div>

                <div
                    id="header-navigation"
                    class="header__navigation"
                    data-menu
                    data-open="false"
                    aria-label="Menú de navegación">

                    <nav class="header__menu">
                        <span class="header__menu-title">Navegación</span>
                        <?php
                        wp_nav_menu(array(
                            'theme_location' => 'menu-principal',
                            'container'      => false,
                            'menu_class'     => 'header__menu header__menu-items',
                            'fallback_cb'    => false,
                        ));
                        ?>
                    </nav>

                    <nav class="header__actions" aria-label="Acciones de cuenta">
                        <span class="header__actions-title">Acciones</span>
                        <ul class="header__menu header__actions-items">
                            <?php if (yuniorrojas_es_cliente()) : ?>
                                <?php
                                $header_user   = wp_get_current_user();
                                $header_name   = trim((string) $header_user->display_name);
                                if ($header_name === '') {
                                    $header_name = (string) $header_user->user_login;
                                }
                                $header_short  = explode(' ', $header_name)[0] ?? $header_name;
                                $header_avatar = get_avatar_url((int) $header_user->ID, array('size' => 64));
                                $cuenta_url    = yuniorrojas_url_cuenta();
                                $logout_url    = wp_logout_url(home_url('/'));
                                ?>
                                <li class="header__account" data-header-account>
                                    <button
                                        type="button"
                                        class="header__account-toggle"
                                        data-header-account-toggle
                                        aria-expanded="false"
                                        aria-haspopup="true"
                                        aria-controls="header-account-menu">
                                        <img
                                            class="header__account-avatar"
                                            src="<?php echo esc_url($header_avatar ?: get_template_directory_uri() . '/img/logo monograma.png'); ?>"
                                            alt=""
                                            width="32"
                                            height="32"
                                        >
                                        <span class="header__account-name"><?php echo esc_html($header_short); ?></span>
                                        <i class="ti ti-chevron-down header__account-caret" aria-hidden="true"></i>
                                    </button>
                                    <div
                                        id="header-account-menu"
                                        class="header__account-menu"
                                        data-header-account-menu
                                        hidden>
                                        <div class="header__account-menu-head">
                                            <strong><?php echo esc_html($header_name); ?></strong>
                                            <span><?php echo esc_html((string) $header_user->user_email); ?></span>
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
                                        <a class="header__account-logout" href="<?php echo esc_url($logout_url); ?>">
                                            <i class="ti ti-logout" aria-hidden="true"></i>
                                            Cerrar sesión
                                        </a>
                                    </div>
                                </li>
                            <?php elseif (yuniorrojas_es_administrador()) : ?>
                                <li>
                                    <a class="header__menu-link header__menu-link--outline" href="<?php echo esc_url(admin_url()); ?>">
                                        Ir al panel
                                    </a>
                                </li>
                            <?php else : ?>
                                <li>
                                    <a class="header__menu-link header__menu-link--outline" href="<?php echo esc_url(yuniorrojas_url_login()); ?>">
                                        Iniciar Sesión
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (yuniorrojas_puede_reservar_en_front()) : ?>
                                <li>
                                    <a class="header__menu-link header__menu-link--button" href="<?php echo esc_url(yuniorrojas_url_reservar()); ?>">
                                        Reservar Ahora
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

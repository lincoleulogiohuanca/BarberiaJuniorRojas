<?php
/**
 * Template Name: Iniciar Sesión
 * Diseño: Login Junior Rojas (split branding + formulario)
 */

if (!defined('ABSPATH')) {
    exit;
}

$login_error   = '';
$login_email   = '';
$ya_logueado   = is_user_logged_in();
$redirect_to   = isset($_REQUEST['redirect_to'])
    ? esc_url_raw(wp_unslash((string) $_REQUEST['redirect_to']))
    : yuniorrojas_url_cuenta();
$registro_url  = yuniorrojas_url_registro();
$form_action   = get_permalink() ?: home_url('/iniciar-sesion/');

if (!$ya_logueado && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yuniorrojas_login_nonce'])) {
    if (!yuniorrojas_verificar_nonce('yuniorrojas_login_nonce', 'yuniorrojas_login')) {
        $login_error = 'La solicitud no es válida. Recarga la página e inténtalo de nuevo.';
    } elseif (function_exists('yuniorrojas_rate_limit_ok') && !yuniorrojas_rate_limit_ok('login', 10, 15 * MINUTE_IN_SECONDS)) {
        $login_error = 'Demasiados intentos de acceso. Espera unos minutos e inténtalo de nuevo.';
    } else {
        $login_raw = sanitize_text_field(wp_unslash((string) ($_POST['log'] ?? '')));
        $password  = (string) wp_unslash($_POST['pwd'] ?? ''); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $remember  = !empty($_POST['rememberme']);
        $login_email = $login_raw;

        if ($login_raw === '' || $password === '') {
            $login_error = 'Ingresa tu correo y contraseña.';
        } else {
            $login_user = $login_raw;
            if (is_email($login_raw)) {
                $user_by_email = get_user_by('email', $login_raw);
                if ($user_by_email instanceof WP_User) {
                    $login_user = $user_by_email->user_login;
                }
            }

            $user = wp_signon(
                array(
                    'user_login'    => $login_user,
                    'user_password' => $password,
                    'remember'      => $remember,
                ),
                is_ssl()
            );

            if (is_wp_error($user)) {
                $login_error = 'Correo o contraseña incorrectos.';
            } elseif ($user instanceof WP_User && yuniorrojas_es_administrador($user)) {
                wp_logout();
                wp_set_current_user(0);
                $ya_logueado = false;
                $login_error = yuniorrojas_mensaje_admin_no_cliente();
            } else {
                $dest = $redirect_to;
                if ($dest === '' || !wp_validate_redirect($dest, false)) {
                    $dest = yuniorrojas_url_post_login($user instanceof WP_User ? $user : null);
                }
                wp_safe_redirect($dest);
                exit;
            }
        }
    }
}

$bg_url = '';
if (has_post_thumbnail()) {
    $bg_url = (string) get_the_post_thumbnail_url(null, 'full');
}

if ($bg_url === '') {
    $hero = yuniorrojas_field('imagen_hero', get_option('page_on_front'));
    if (is_array($hero) && !empty($hero['url'])) {
        $bg_url = (string) $hero['url'];
    }
}

$usuario_actual = $ya_logueado ? wp_get_current_user() : null;
$es_admin_logueado = $usuario_actual instanceof WP_User && yuniorrojas_es_administrador($usuario_actual);
$es_cliente_logueado = $usuario_actual instanceof WP_User && yuniorrojas_es_cliente($usuario_actual);

get_header();
?>

<main
    class="auth-login"
    <?php echo $bg_url !== '' ? 'style="--auth-login-image:url(' . esc_url($bg_url) . ')"' : ''; ?>>

    <section class="auth-login__brand" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
        <div class="auth-login__brand-overlay" aria-hidden="true"></div>
        <div class="auth-login__brand-content">
            <p class="auth-login__brand-name">Junior Rojas</p>
            <p class="auth-login__brand-tag">Barber Studio</p>
        </div>
    </section>

    <section class="auth-login__panel">
        <div class="auth-login__panel-inner">
            <h1 class="auth-login__title auth-login__title--line">Iniciar Sesión</h1>

            <?php if ($es_admin_logueado) : ?>
                <p class="auth-login__notice auth-login__notice--error" role="alert">
                    <?php echo esc_html(yuniorrojas_mensaje_admin_no_cliente()); ?>
                </p>
                <div class="auth-login__logged">
                    <a class="auth-login__submit" href="<?php echo esc_url(admin_url()); ?>">
                        Ir a mi panel
                    </a>
                    <a class="auth-login__logout" href="<?php echo esc_url(wp_logout_url(yuniorrojas_url_login())); ?>">
                        Cerrar sesión
                    </a>
                </div>
            <?php elseif ($es_cliente_logueado && $usuario_actual instanceof WP_User) : ?>
                <p class="auth-login__notice auth-login__notice--ok" role="status">
                    Ya tienes una sesión activa como
                    <strong><?php echo esc_html($usuario_actual->display_name !== '' ? $usuario_actual->display_name : $usuario_actual->user_login); ?></strong>.
                </p>

                <div class="auth-login__logged">
                    <a class="auth-login__submit" href="<?php echo esc_url(yuniorrojas_url_post_login($usuario_actual)); ?>">
                        Ir a mi cuenta
                    </a>
                    <a class="auth-login__logout" href="<?php echo esc_url(wp_logout_url(yuniorrojas_url_login())); ?>">
                        Cerrar sesión
                    </a>
                </div>
            <?php else : ?>

                <?php if ($login_error !== '') : ?>
                    <p class="auth-login__notice auth-login__notice--error" role="alert">
                        <?php echo esc_html($login_error); ?>
                    </p>
                <?php endif; ?>

                <form
                    class="auth-login__form"
                    method="post"
                    action="<?php echo esc_url($form_action); ?>"
                    novalidate>
                    <?php wp_nonce_field('yuniorrojas_login', 'yuniorrojas_login_nonce'); ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                    <label class="auth-login__field" for="auth-login-email">
                        <span class="auth-login__label">Correo electrónico</span>
                        <span class="auth-login__control">
                            <input
                                id="auth-login-email"
                                type="email"
                                name="log"
                                value="<?php echo esc_attr($login_email); ?>"
                                placeholder="usuariodont@gmail.com"
                                autocomplete="email"
                                required
                            >
                            <i class="ti ti-mail auth-login__icon" aria-hidden="true"></i>
                        </span>
                    </label>

                    <label class="auth-login__field" for="auth-login-password">
                        <span class="auth-login__label">Contraseña</span>
                        <span class="auth-login__control">
                            <input
                                id="auth-login-password"
                                type="password"
                                name="pwd"
                                value=""
                                placeholder=""
                                autocomplete="current-password"
                                required
                                data-password-input
                            >
                            <button
                                type="button"
                                class="auth-login__toggle"
                                data-password-toggle
                                aria-label="Mostrar contraseña"
                                aria-pressed="false">
                                <i class="ti ti-eye-off" aria-hidden="true" data-password-icon></i>
                            </button>
                        </span>
                    </label>

                    <div class="auth-login__meta">
                        <label class="auth-login__remember" for="auth-login-remember">
                            <input id="auth-login-remember" type="checkbox" name="rememberme" value="forever">
                            <span>Recordar</span>
                        </label>
                        <a class="auth-login__forgot" href="<?php echo esc_url(yuniorrojas_url_recuperar()); ?>">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    <button type="submit" class="auth-login__submit">
                        Iniciar Sesión
                    </button>
                </form>

                <div class="auth-login__footer">
                    <p>
                        ¿No tienes cuenta?
                        <a href="<?php echo esc_url($registro_url); ?>">Regístrate</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
get_footer();

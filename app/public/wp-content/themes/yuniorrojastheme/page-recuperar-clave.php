<?php
/**
 * Template Name: Recuperar contraseña
 * Diseño: Auth Junior Rojas (split branding + formulario cliente)
 */

if (!defined('ABSPATH')) {
    exit;
}

$login_url    = yuniorrojas_url_login();
$registro_url = yuniorrojas_url_registro();
$form_action  = get_permalink() ?: home_url('/recuperar-clave/');
$ya_logueado  = is_user_logged_in();

$rp_key   = isset($_REQUEST['key']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['key'])) : '';
$rp_login = isset($_REQUEST['login']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['login'])) : '';
$modo     = ($rp_key !== '' && $rp_login !== '') ? 'reset' : 'solicitar';

$errores  = array();
$exito    = '';
$email    = '';
$rp_user  = null;

if ($modo === 'reset') {
    $rp_user = check_password_reset_key($rp_key, $rp_login);
    if (is_wp_error($rp_user)) {
        $modo    = 'solicitar';
        $errores[] = 'El enlace de recuperación no es válido o ya expiró. Solicita uno nuevo.';
        $rp_key   = '';
        $rp_login = '';
        $rp_user  = null;
    }
}

if (!$ya_logueado && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($modo === 'reset' && isset($_POST['yuniorrojas_reset_nonce'])) {
        if (!yuniorrojas_verificar_nonce('yuniorrojas_reset_nonce', 'yuniorrojas_reset_clave')) {
            $errores[] = 'La solicitud no es válida. Recarga la página e inténtalo de nuevo.';
        } else {
            $pass  = (string) wp_unslash($_POST['password'] ?? ''); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $pass2 = (string) wp_unslash($_POST['password_confirm'] ?? ''); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            $check = check_password_reset_key($rp_key, $rp_login);
            if (is_wp_error($check)) {
                $errores[] = 'El enlace de recuperación no es válido o ya expiró. Solicita uno nuevo.';
                $modo      = 'solicitar';
            } elseif (strlen($pass) < 8) {
                $errores[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
            } elseif ($pass !== $pass2) {
                $errores[] = 'Las contraseñas no coinciden.';
            } else {
                reset_password($check, $pass);
                $exito = 'Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión.';
                $modo  = 'listo';
            }
        }
    } elseif ($modo === 'solicitar' && isset($_POST['yuniorrojas_recuperar_nonce'])) {
        if (!yuniorrojas_verificar_nonce('yuniorrojas_recuperar_nonce', 'yuniorrojas_recuperar_clave')) {
            $errores[] = 'La solicitud no es válida. Recarga la página e inténtalo de nuevo.';
        } else {
            $email = sanitize_email(wp_unslash((string) ($_POST['user_login'] ?? '')));

            if ($email === '' || !is_email($email)) {
                $errores[] = 'Ingresa un correo electrónico válido.';
            } else {
                $user = get_user_by('email', $email);
                if ($user instanceof WP_User) {
                    $result = retrieve_password($user->user_login);
                    if (is_wp_error($result)) {
                        $errores[] = $result->get_error_message();
                    }
                }
                // Mensaje genérico (no revelar si el correo existe).
                if ($errores === array()) {
                    $exito = 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña. Revisa tu bandeja de entrada y spam.';
                }
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
$eslogan        = yuniorrojas_eslogan();

$titulo = 'Recuperar acceso';
if ($modo === 'reset') {
    $titulo = 'Nueva contraseña';
} elseif ($modo === 'listo') {
    $titulo = 'Listo';
}

get_header();
?>

<main
    class="auth-login auth-login--recuperar"
    <?php echo $bg_url !== '' ? 'style="--auth-login-image:url(' . esc_url($bg_url) . ')"' : ''; ?>>

    <section class="auth-login__brand" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
        <div class="auth-login__brand-overlay" aria-hidden="true"></div>
        <div class="auth-login__brand-content auth-login__brand-content--start">
            <div class="auth-login__monogram" aria-hidden="true">JR</div>
            <p class="auth-login__brand-name">Junior Rojas</p>
            <p class="auth-login__brand-tag auth-login__brand-tag--gold">Barber Studio</p>
            <p class="auth-login__brand-lead">
                <?php echo esc_html($eslogan); ?>
                Recupera tu acceso con la misma precisión de siempre.
            </p>
        </div>
    </section>

    <section class="auth-login__panel">
        <div class="auth-login__panel-inner">
            <h1 class="auth-login__title auth-login__title--line"><?php echo esc_html($titulo); ?></h1>

            <?php if ($ya_logueado && $usuario_actual instanceof WP_User) : ?>
                <p class="auth-login__notice auth-login__notice--ok" role="status">
                    Ya tienes una sesión activa como
                    <strong><?php echo esc_html($usuario_actual->display_name !== '' ? $usuario_actual->display_name : $usuario_actual->user_login); ?></strong>.
                </p>
                <div class="auth-login__logged">
                    <a class="auth-login__submit" href="<?php echo esc_url(yuniorrojas_url_post_login($usuario_actual)); ?>">
                        Ir a mi cuenta
                    </a>
                    <a class="auth-login__logout" href="<?php echo esc_url(wp_logout_url($login_url)); ?>">Cerrar sesión</a>
                </div>

            <?php elseif ($modo === 'listo' || $exito !== '') : ?>
                <p class="auth-login__notice auth-login__notice--ok" role="status">
                    <?php echo esc_html($exito !== '' ? $exito : 'Operación completada.'); ?>
                </p>
                <div class="auth-login__logged">
                    <a class="auth-login__submit" href="<?php echo esc_url($login_url); ?>">
                        Iniciar sesión
                    </a>
                </div>

            <?php elseif ($modo === 'reset' && $rp_user instanceof WP_User) : ?>

                <?php if ($errores !== array()) : ?>
                    <div class="auth-login__notice auth-login__notice--error" role="alert">
                        <?php foreach ($errores as $error) : ?>
                            <p><?php echo esc_html($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="auth-login__lead">
                    Define una nueva contraseña para
                    <strong><?php echo esc_html($rp_user->user_email); ?></strong>.
                </p>

                <form
                    class="auth-login__form"
                    method="post"
                    action="<?php echo esc_url(add_query_arg(array('key' => $rp_key, 'login' => $rp_login), $form_action)); ?>"
                    novalidate>
                    <?php wp_nonce_field('yuniorrojas_reset_clave', 'yuniorrojas_reset_nonce'); ?>
                    <input type="hidden" name="key" value="<?php echo esc_attr($rp_key); ?>">
                    <input type="hidden" name="login" value="<?php echo esc_attr($rp_login); ?>">

                    <label class="auth-login__field" for="auth-reset-password">
                        <span class="auth-login__label">Nueva contraseña</span>
                        <span class="auth-login__control">
                            <input
                                id="auth-reset-password"
                                type="password"
                                name="password"
                                value=""
                                autocomplete="new-password"
                                required
                                minlength="8"
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

                    <label class="auth-login__field" for="auth-reset-password-confirm">
                        <span class="auth-login__label">Confirmar contraseña</span>
                        <span class="auth-login__control">
                            <input
                                id="auth-reset-password-confirm"
                                type="password"
                                name="password_confirm"
                                value=""
                                autocomplete="new-password"
                                required
                                minlength="8"
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

                    <button type="submit" class="auth-login__submit">
                        Guardar contraseña
                    </button>
                </form>

                <div class="auth-login__footer">
                    <p>
                        <a href="<?php echo esc_url($login_url); ?>">Volver a iniciar sesión</a>
                    </p>
                </div>

            <?php else : ?>

                <p class="auth-login__lead">
                    Ingresa el correo de tu cuenta y te enviaremos un enlace para restablecer la contraseña.
                </p>

                <?php if ($errores !== array()) : ?>
                    <div class="auth-login__notice auth-login__notice--error" role="alert">
                        <?php foreach ($errores as $error) : ?>
                            <p><?php echo esc_html($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form
                    class="auth-login__form"
                    method="post"
                    action="<?php echo esc_url($form_action); ?>"
                    novalidate>
                    <?php wp_nonce_field('yuniorrojas_recuperar_clave', 'yuniorrojas_recuperar_nonce'); ?>

                    <label class="auth-login__field" for="auth-recuperar-email">
                        <span class="auth-login__label">Correo electrónico</span>
                        <span class="auth-login__control">
                            <input
                                id="auth-recuperar-email"
                                type="email"
                                name="user_login"
                                value="<?php echo esc_attr($email); ?>"
                                placeholder="usuariodont@gmail.com"
                                autocomplete="email"
                                required
                            >
                            <i class="ti ti-mail auth-login__icon" aria-hidden="true"></i>
                        </span>
                    </label>

                    <button type="submit" class="auth-login__submit">
                        Enviar enlace
                    </button>
                </form>

                <div class="auth-login__footer">
                    <p>
                        ¿Recordaste tu clave?
                        <a href="<?php echo esc_url($login_url); ?>">Iniciar sesión</a>
                    </p>
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

<?php
/**
 * Template Name: Registro
 * Diseño: Registro Junior Rojas (split branding + formulario)
 */

if (!defined('ABSPATH')) {
    exit;
}

$ya_logueado   = is_user_logged_in();
$login_url     = yuniorrojas_url_login();
$form_action   = get_permalink() ?: home_url('/registro/');
$errores       = array();
$exito         = false;

$nombre   = '';
$email    = '';
$telefono = '';

if (!$ya_logueado && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yuniorrojas_registro_nonce'])) {
    if (!yuniorrojas_verificar_nonce('yuniorrojas_registro_nonce', 'yuniorrojas_registro')) {
        $errores[] = 'La solicitud no es válida. Recarga la página e inténtalo de nuevo.';
    } else {
        $nombre    = sanitize_text_field(wp_unslash((string) ($_POST['nombre'] ?? '')));
        $email     = sanitize_email(wp_unslash((string) ($_POST['email'] ?? '')));
        $telefono  = sanitize_text_field(wp_unslash((string) ($_POST['telefono'] ?? '')));
        $password  = (string) wp_unslash($_POST['password'] ?? ''); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $password2 = (string) wp_unslash($_POST['password_confirm'] ?? ''); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if ($nombre === '') {
            $errores[] = 'Ingresa tu nombre completo.';
        }
        if ($email === '' || !is_email($email)) {
            $errores[] = 'Ingresa un correo electrónico válido.';
        } elseif (email_exists($email)) {
            $errores[] = 'Este correo ya está registrado.';
        }
        if ($telefono === '') {
            $errores[] = 'Ingresa tu teléfono / WhatsApp.';
        }
        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $password2) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        if ($errores === array()) {
            $base_login = sanitize_user(strstr($email, '@', true) ?: $email, true);
            if ($base_login === '') {
                $base_login = 'usuario';
            }

            $login = $base_login;
            $i     = 1;
            while (username_exists($login)) {
                $login = $base_login . $i;
                $i++;
            }

            $user_id = wp_insert_user(
                array(
                    'user_login'   => $login,
                    'user_email'   => $email,
                    'user_pass'    => $password,
                    'display_name' => $nombre,
                    'first_name'   => $nombre,
                    'role'         => 'subscriber',
                )
            );

            if (is_wp_error($user_id)) {
                $errores[] = $user_id->get_error_message();
            } else {
                update_user_meta((int) $user_id, 'telefono', $telefono);
                update_user_meta((int) $user_id, 'whatsapp', $telefono);

                $signed = wp_signon(
                    array(
                        'user_login'    => $login,
                        'user_password' => $password,
                        'remember'      => true,
                    ),
                    is_ssl()
                );

                if (!is_wp_error($signed)) {
                    wp_safe_redirect(yuniorrojas_url_post_login($signed instanceof WP_User ? $signed : null));
                    exit;
                }

                $exito = true;
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

get_header();
?>

<main
    class="auth-login auth-login--registro"
    <?php echo $bg_url !== '' ? 'style="--auth-login-image:url(' . esc_url($bg_url) . ')"' : ''; ?>>

    <section class="auth-login__brand" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
        <div class="auth-login__brand-overlay" aria-hidden="true"></div>
        <div class="auth-login__brand-content auth-login__brand-content--start">
            <div class="auth-login__monogram" aria-hidden="true">JR</div>
            <p class="auth-login__brand-name">Junior Rojas</p>
            <p class="auth-login__brand-tag auth-login__brand-tag--gold">Barber Studio</p>
            <p class="auth-login__brand-lead">
                <?php echo esc_html($eslogan); ?>
                Precisión, disciplina y una experiencia premium.
            </p>
        </div>
    </section>

    <section class="auth-login__panel">
        <div class="auth-login__panel-inner">
            <h1 class="auth-login__title auth-login__title--line">Únete al Estudio</h1>

            <?php if ($ya_logueado && $usuario_actual instanceof WP_User) : ?>
                <p class="auth-login__notice auth-login__notice--ok" role="status">
                    Ya tienes una sesión activa como
                    <strong><?php echo esc_html($usuario_actual->display_name !== '' ? $usuario_actual->display_name : $usuario_actual->user_login); ?></strong>.
                </p>
                <div class="auth-login__logged">
                    <a class="auth-login__submit" href="<?php echo esc_url(yuniorrojas_url_post_login($usuario_actual)); ?>">
                        Ir a mi cuenta
                    </a>
                    <a class="auth-login__logout" href="<?php echo esc_url(wp_logout_url($form_action)); ?>">Cerrar sesión</a>
                </div>
            <?php elseif ($exito) : ?>
                <p class="auth-login__notice auth-login__notice--ok" role="status">
                    Cuenta creada correctamente. Ya puedes
                    <a href="<?php echo esc_url($login_url); ?>">iniciar sesión</a>.
                </p>
            <?php else : ?>

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
                    <?php wp_nonce_field('yuniorrojas_registro', 'yuniorrojas_registro_nonce'); ?>

                    <label class="auth-login__field" for="auth-registro-nombre">
                        <span class="auth-login__label">Nombre completo</span>
                        <span class="auth-login__control">
                            <input
                                id="auth-registro-nombre"
                                type="text"
                                name="nombre"
                                value="<?php echo esc_attr($nombre); ?>"
                                placeholder="Tu nombre"
                                autocomplete="name"
                                required
                            >
                            <i class="ti ti-user auth-login__icon" aria-hidden="true"></i>
                        </span>
                    </label>

                    <label class="auth-login__field" for="auth-registro-email">
                        <span class="auth-login__label">Correo electrónico</span>
                        <span class="auth-login__control">
                            <input
                                id="auth-registro-email"
                                type="email"
                                name="email"
                                value="<?php echo esc_attr($email); ?>"
                                placeholder="tu@correo.com"
                                autocomplete="email"
                                required
                            >
                            <i class="ti ti-mail auth-login__icon" aria-hidden="true"></i>
                        </span>
                    </label>

                    <label class="auth-login__field" for="auth-registro-telefono">
                        <span class="auth-login__label">Teléfono / WhatsApp</span>
                        <span class="auth-login__control">
                            <input
                                id="auth-registro-telefono"
                                type="tel"
                                name="telefono"
                                value="<?php echo esc_attr($telefono); ?>"
                                placeholder="+51 999 999 999"
                                autocomplete="tel"
                                required
                            >
                            <i class="ti ti-brand-whatsapp auth-login__icon" aria-hidden="true"></i>
                        </span>
                    </label>

                    <label class="auth-login__field" for="auth-registro-password">
                        <span class="auth-login__label">Contraseña</span>
                        <span class="auth-login__control">
                            <input
                                id="auth-registro-password"
                                type="password"
                                name="password"
                                value=""
                                placeholder=""
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

                    <label class="auth-login__field" for="auth-registro-password-confirm">
                        <span class="auth-login__label">Confirmar contraseña</span>
                        <span class="auth-login__control">
                            <input
                                id="auth-registro-password-confirm"
                                type="password"
                                name="password_confirm"
                                value=""
                                placeholder=""
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
                        Crear cuenta
                        <i class="ti ti-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <div class="auth-login__footer">
                    <p>
                        ¿Ya tienes una cuenta?
                        <a href="<?php echo esc_url($login_url); ?>">Iniciar sesión</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
get_footer();

<?php
/**
 * Metabox admin: Editar reserva (cliente, cita, gestión, sistema).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra metaboxes de reserva.
 */
function yuniorrojas_reserva_registrar_metaboxes(): void
{
    add_meta_box(
        'yuniorrojas_reserva_datos',
        __('Datos de la reserva', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_reserva_metabox_render',
        YUNIORROJAS_CPT_RESERVAS,
        'normal',
        'high'
    );

    add_meta_box(
        'yuniorrojas_reserva_acciones',
        __('Acciones de cita', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_reserva_metabox_acciones_render',
        YUNIORROJAS_CPT_RESERVAS,
        'side',
        'high'
    );
}
add_action('add_meta_boxes_' . YUNIORROJAS_CPT_RESERVAS, 'yuniorrojas_reserva_registrar_metaboxes');

/**
 * Opciones de servicios para select (incluye precio y duración del CPT).
 *
 * @return array<int, array{nombre:string,precio:string,duracion:int}>
 */
function yuniorrojas_reserva_admin_opciones_servicios(): array
{
    $q = new WP_Query(array(
        'post_type'              => YUNIORROJAS_CPT_SERVICIOS,
        'posts_per_page'         => 100,
        'post_status'            => 'publish',
        'orderby'                => 'title',
        'order'                  => 'ASC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ));

    $out = array();
    foreach ($q->posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $id       = (int) $post->ID;
        $duracion = (int) yuniorrojas_field('tiempo_de_servicio', $id, 60);
        if ($duracion <= 0) {
            $duracion = 60;
        }
        $out[$id] = array(
            'nombre'   => get_the_title($post),
            'precio'   => (string) yuniorrojas_field('precio', $id, '0'),
            'duracion' => $duracion,
        );
    }

    return $out;
}

/**
 * Opciones de barberos para select.
 *
 * @return array<int, string>
 */
function yuniorrojas_reserva_admin_opciones_barberos(): array
{
    $q = new WP_Query(array(
        'post_type'              => 'barberos',
        'posts_per_page'         => 100,
        'post_status'            => 'publish',
        'orderby'                => 'title',
        'order'                  => 'ASC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ));

    $out = array();
    foreach ($q->posts as $post) {
        if ($post instanceof WP_Post) {
            $out[(int) $post->ID] = get_the_title($post);
        }
    }

    return $out;
}

/**
 * Render principal del metabox.
 */
function yuniorrojas_reserva_metabox_render(WP_Post $post): void
{
    $reserva = yuniorrojas_obtener_reserva((int) $post->ID);
    if ($reserva === null) {
        echo '<p>' . esc_html__('No se pudo cargar la reserva.', YUNIORROJAS_TEXT_DOMAIN) . '</p>';
        return;
    }

    wp_nonce_field('yuniorrojas_guardar_reserva', 'yuniorrojas_reserva_nonce');

    $servicios = yuniorrojas_reserva_admin_opciones_servicios();
    $barberos  = yuniorrojas_reserva_admin_opciones_barberos();
    $estados   = yuniorrojas_reserva_estados();
    $pagos     = array();
    if (function_exists('yuniorrojas_medios_pago_todos')) {
        foreach (yuniorrojas_medios_pago_todos(false) as $m) {
            $slug = (string) ($m['slug'] ?? '');
            if ($slug !== '') {
                $pagos[$slug] = (string) ($m['nombre'] ?? $slug);
            }
        }
    }
    if ($pagos === array()) {
        $pagos = array(
            'estudio' => yuniorrojas_reserva_metodo_pago_label('estudio'),
            'culqi'   => yuniorrojas_reserva_metodo_pago_label('culqi'),
            'plin'    => yuniorrojas_reserva_metodo_pago_label('plin'),
        );
    }

    $hora_input = (string) ($reserva['hora'] ?? '');
    if ($hora_input !== '' && preg_match('/^\d{2}:\d{2}$/', $hora_input)) {
        // ok
    } elseif ($hora_input !== '') {
        $hora_input = yuniorrojas_parsear_hora_cita($hora_input);
    }

    $user_id   = (int) ($reserva['cliente_user_id'] ?? 0);
    $registrado = yuniorrojas_reserva_es_cliente_registrado($reserva);
    $origen     = (string) ($reserva['origen'] ?? ($registrado ? 'web' : 'admin'));
    $ro_attr    = $registrado ? ' readonly' : '';
    $user_link = '';
    $user_label = __('Sin cuenta vinculada', YUNIORROJAS_TEXT_DOMAIN);
    if ($user_id > 0) {
        $user = get_userdata($user_id);
        if ($user instanceof WP_User) {
            $user_label = $user->display_name !== '' ? $user->display_name : $user->user_login;
            $user_link  = get_edit_user_link($user_id);
        } else {
            $user_label = sprintf(/* translators: %d user id */ __('Usuario #%d', YUNIORROJAS_TEXT_DOMAIN), $user_id);
        }
    }
    ?>
    <div class="jr-reserva-admin" data-jr-reserva-admin<?php echo $registrado ? ' data-jr-cliente-registrado="1"' : ''; ?>>
        <div class="jr-reserva-admin__grid">
            <section class="jr-reserva-admin__block">
                <h3>
                    <?php esc_html_e('Cliente', YUNIORROJAS_TEXT_DOMAIN); ?>
                    <?php if ($registrado) : ?>
                        <span class="jr-reserva-admin__badge"><?php esc_html_e('Cuenta web', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                    <?php else : ?>
                        <span class="jr-reserva-admin__badge jr-reserva-admin__badge--manual"><?php esc_html_e('Reserva manual / llamada', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                    <?php endif; ?>
                </h3>
                <?php if ($registrado) : ?>
                    <p class="description">
                        <?php esc_html_e('Cliente con cuenta: nombre, teléfono y correo se editan en su perfil (Preferencias). Aquí solo gestionas la cita y las notas de esta reserva.', YUNIORROJAS_TEXT_DOMAIN); ?>
                        <?php if ($user_link !== '') : ?>
                            <a href="<?php echo esc_url($user_link); ?>"><?php esc_html_e('Ver usuario WP', YUNIORROJAS_TEXT_DOMAIN); ?></a>
                        <?php endif; ?>
                    </p>
                <?php else : ?>
                    <p class="description">
                        <?php esc_html_e('Reserva manual / llamada: puedes cargar o corregir todos los datos del cliente.', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </p>
                <?php endif; ?>
                <div class="jr-reserva-admin__row">
                    <p>
                        <label for="jr_cliente_nombres"><?php esc_html_e('Nombres', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <input type="text" class="widefat" id="jr_cliente_nombres" name="jr_cliente_nombres"
                            value="<?php echo esc_attr((string) $reserva['cliente_nombres']); ?>" required<?php echo $ro_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                    </p>
                    <p>
                        <label for="jr_cliente_apellidos"><?php esc_html_e('Apellidos', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <input type="text" class="widefat" id="jr_cliente_apellidos" name="jr_cliente_apellidos"
                            value="<?php echo esc_attr((string) $reserva['cliente_apellidos']); ?>" required<?php echo $ro_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                    </p>
                </div>
                <div class="jr-reserva-admin__row">
                    <p>
                        <label for="jr_cliente_telefono"><?php esc_html_e('Teléfono / WhatsApp', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <input type="text" class="widefat" id="jr_cliente_telefono" name="jr_cliente_telefono"
                            value="<?php echo esc_attr((string) $reserva['cliente_telefono']); ?>" required<?php echo $ro_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                    </p>
                    <p>
                        <label for="jr_cliente_email"><?php esc_html_e('Correo', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <input type="email" class="widefat" id="jr_cliente_email" name="jr_cliente_email"
                            value="<?php echo esc_attr((string) $reserva['cliente_email']); ?>" required<?php echo $ro_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                    </p>
                </div>
                <p>
                    <label for="jr_cliente_notas"><?php esc_html_e('Notas del cliente (esta cita)', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                    <textarea class="widefat" id="jr_cliente_notas" name="jr_cliente_notas" rows="3"><?php echo esc_textarea((string) ($reserva['cliente_notas'] ?? '')); ?></textarea>
                    <span class="description"><?php esc_html_e('Pedidos o preferencias de esta reserva (web o lo que pidió por teléfono).', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                </p>
                <p>
                    <label for="jr_notas_internas"><?php esc_html_e('Notas internas del estudio', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                    <textarea class="widefat" id="jr_notas_internas" name="jr_notas_internas" rows="3"><?php echo esc_textarea((string) ($reserva['notas_internas'] ?? '')); ?></textarea>
                    <span class="description"><?php esc_html_e('Solo visibles en el panel admin.', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                </p>
                <input type="hidden" name="jr_origen" value="<?php echo esc_attr($origen); ?>">
            </section>

            <section class="jr-reserva-admin__block">
                <h3><?php esc_html_e('Cita', YUNIORROJAS_TEXT_DOMAIN); ?></h3>
                <div class="jr-reserva-admin__row">
                    <p>
                        <label for="jr_servicio_id"><?php esc_html_e('Servicio', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <select class="widefat" id="jr_servicio_id" name="jr_servicio_id" required data-jr-servicio-select>
                            <option value=""><?php esc_html_e('Seleccionar…', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                            <?php foreach ($servicios as $sid => $info) : ?>
                                <?php
                                $nombre_srv = is_array($info) ? (string) ($info['nombre'] ?? '') : (string) $info;
                                $precio_srv = is_array($info) ? (string) ($info['precio'] ?? '0') : '0';
                                $dur_srv    = is_array($info) ? (int) ($info['duracion'] ?? 60) : 60;
                                ?>
                                <option
                                    value="<?php echo esc_attr((string) $sid); ?>"
                                    data-precio="<?php echo esc_attr($precio_srv); ?>"
                                    data-duracion="<?php echo esc_attr((string) $dur_srv); ?>"
                                    <?php selected((int) $reserva['servicio_id'], (int) $sid); ?>>
                                    <?php echo esc_html($nombre_srv); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label for="jr_barbero_id"><?php esc_html_e('Barbero', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <select class="widefat" id="jr_barbero_id" name="jr_barbero_id" required>
                            <option value=""><?php esc_html_e('Seleccionar…', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                            <?php foreach ($barberos as $bid => $label) : ?>
                                <option value="<?php echo esc_attr((string) $bid); ?>" <?php selected((int) $reserva['barbero_id'], (int) $bid); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                </div>
                <div class="jr-reserva-admin__row">
                    <p>
                        <label for="jr_fecha"><?php esc_html_e('Fecha', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <input type="date" class="widefat" id="jr_fecha" name="jr_fecha"
                            value="<?php echo esc_attr((string) $reserva['fecha']); ?>" required>
                    </p>
                    <p>
                        <label for="jr_hora"><?php esc_html_e('Hora', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <input type="time" class="widefat" id="jr_hora" name="jr_hora"
                            value="<?php echo esc_attr($hora_input); ?>" required>
                    </p>
                </div>
                <?php
                $servicio_actual = (int) ($reserva['servicio_id'] ?? 0);
                $precio_vista    = (string) ($reserva['precio'] ?? '');
                $duracion_vista  = (int) ($reserva['duracion'] ?? 0);
                if ($servicio_actual > 0 && isset($servicios[$servicio_actual]) && is_array($servicios[$servicio_actual])) {
                    $precio_vista   = (string) ($servicios[$servicio_actual]['precio'] ?? $precio_vista);
                    $duracion_vista = (int) ($servicios[$servicio_actual]['duracion'] ?? $duracion_vista);
                }
                if ($duracion_vista <= 0) {
                    $duracion_vista = 60;
                }
                ?>
                <div class="jr-reserva-admin__row">
                    <p>
                        <label for="jr_duracion"><?php esc_html_e('Duración (min)', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <input type="number" class="widefat" id="jr_duracion" name="jr_duracion" min="15" step="5"
                            value="<?php echo esc_attr((string) $duracion_vista); ?>" readonly data-jr-duracion>
                        <span class="description"><?php esc_html_e('Tomada del servicio seleccionado.', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                    </p>
                    <p>
                        <label for="jr_precio"><?php esc_html_e('Precio (S/.)', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <input type="text" class="widefat" id="jr_precio" name="jr_precio"
                            value="<?php echo esc_attr($precio_vista); ?>" readonly data-jr-precio>
                        <span class="description"><?php esc_html_e('Tomado del servicio seleccionado.', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                    </p>
                </div>
            </section>

            <section class="jr-reserva-admin__block">
                <h3><?php esc_html_e('Gestión', YUNIORROJAS_TEXT_DOMAIN); ?></h3>
                <div class="jr-reserva-admin__row">
                    <p>
                        <label for="jr_estado"><?php esc_html_e('Estado', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <select class="widefat" id="jr_estado" name="jr_estado" required>
                            <?php foreach ($estados as $estado) : ?>
                                <option value="<?php echo esc_attr($estado); ?>" <?php selected((string) $reserva['estado'], $estado); ?>>
                                    <?php echo esc_html(yuniorrojas_reserva_estado_label($estado)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label for="jr_metodo_pago"><?php esc_html_e('Método de pago', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <select class="widefat" id="jr_metodo_pago" name="jr_metodo_pago" required>
                            <?php
                            $metodo_actual = sanitize_key((string) ($reserva['metodo_pago'] ?? 'estudio'));
                            if ($metodo_actual === 'efectivo' || $metodo_actual === '') {
                                $metodo_actual = 'estudio';
                            }
                            if ($metodo_actual === 'yape' || $metodo_actual === 'transferencia') {
                                $metodo_actual = 'plin';
                            }
                            if ($metodo_actual === 'tarjeta') {
                                $metodo_actual = 'culqi';
                            }
                            if (!isset($pagos[$metodo_actual])) {
                                // Reserva con slug legacy no listado: mostrar option extra.
                                $pagos[$metodo_actual] = yuniorrojas_reserva_metodo_pago_label($metodo_actual);
                            }
                            foreach ($pagos as $value => $label) :
                                ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($metodo_actual, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                </div>
                <?php
                $codigo_op = (string) ($reserva['codigo_operacion'] ?? '');
                $comp_url  = (string) ($reserva['comprobante_url'] ?? '');
                $pago_ok   = !empty($reserva['pago_verificado']);
                $culqi_id  = (string) ($reserva['culqi_charge_id'] ?? '');
                $img_res   = (string) ($reserva['imagen'] ?? '');
                $img_id    = (int) get_post_meta((int) $post->ID, yuniorrojas_reserva_meta_key('imagen_resultado'), true);
                if ($img_id <= 0 && $img_res !== '' && ctype_digit((string) get_post_meta((int) $post->ID, yuniorrojas_reserva_meta_key('imagen_resultado'), true))) {
                    $img_id = (int) get_post_meta((int) $post->ID, yuniorrojas_reserva_meta_key('imagen_resultado'), true);
                }
                ?>
                <div class="jr-reserva-admin__row">
                    <p>
                        <label for="jr_codigo_operacion"><?php esc_html_e('Código de operación (Plin)', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                        <input type="text" class="widefat" id="jr_codigo_operacion" name="jr_codigo_operacion" value="<?php echo esc_attr($codigo_op); ?>">
                    </p>
                    <p>
                        <label for="jr_pago_verificado">
                            <input type="checkbox" id="jr_pago_verificado" name="jr_pago_verificado" value="1" <?php checked($pago_ok); ?>>
                            <?php esc_html_e('Pago verificado (confirmar dinero recibido)', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </label>
                        <span class="description"><?php esc_html_e('Al marcar y guardar, la cita pasa a Confirmada y se notifica al cliente.', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                    </p>
                </div>
                <?php if ($culqi_id !== '') : ?>
                    <p>
                        <strong><?php esc_html_e('Cargo Culqi:', YUNIORROJAS_TEXT_DOMAIN); ?></strong>
                        <code><?php echo esc_html($culqi_id); ?></code>
                        <?php if ($pago_ok) : ?>
                            — <?php esc_html_e('cobrado y verificado automáticamente', YUNIORROJAS_TEXT_DOMAIN); ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if ($comp_url !== '') : ?>
                    <p>
                        <strong><?php esc_html_e('Comprobante del cliente:', YUNIORROJAS_TEXT_DOMAIN); ?></strong>
                        <a href="<?php echo esc_url($comp_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Ver imagen', YUNIORROJAS_TEXT_DOMAIN); ?></a>
                    </p>
                <?php endif; ?>
                <p>
                    <label for="jr_imagen_resultado_id"><?php esc_html_e('Foto del resultado (historial del cliente)', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                    <input type="hidden" id="jr_imagen_resultado_id" name="jr_imagen_resultado_id" value="<?php echo esc_attr((string) $img_id); ?>" data-jr-resultado-id>
                    <button type="button" class="button" data-jr-resultado-pick><?php esc_html_e('Elegir / subir foto', YUNIORROJAS_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" data-jr-resultado-clear><?php esc_html_e('Quitar', YUNIORROJAS_TEXT_DOMAIN); ?></button>
                </p>
                <p data-jr-resultado-preview>
                    <?php if ($img_res !== '') : ?>
                        <img src="<?php echo esc_url($img_res); ?>" alt="" style="max-width:180px;height:auto;border-radius:6px;">
                    <?php endif; ?>
                </p>
            </section>

            <section class="jr-reserva-admin__block jr-reserva-admin__block--sistema">
                <h3><?php esc_html_e('Sistema', YUNIORROJAS_TEXT_DOMAIN); ?></h3>
                <ul class="jr-reserva-admin__meta">
                    <li>
                        <strong><?php esc_html_e('ID de reserva', YUNIORROJAS_TEXT_DOMAIN); ?>:</strong>
                        <?php echo esc_html((string) $post->ID); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Origen', YUNIORROJAS_TEXT_DOMAIN); ?>:</strong>
                        <?php
                        echo esc_html(
                            $origen === 'web'
                                ? __('Web (cliente con cuenta)', YUNIORROJAS_TEXT_DOMAIN)
                                : __('Admin / llamada', YUNIORROJAS_TEXT_DOMAIN)
                        );
                        ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Usuario WP', YUNIORROJAS_TEXT_DOMAIN); ?>:</strong>
                        <?php if ($user_link !== '') : ?>
                            <a href="<?php echo esc_url($user_link); ?>"><?php echo esc_html($user_label); ?></a>
                            <span class="description">(ID <?php echo esc_html((string) $user_id); ?>)</span>
                        <?php else : ?>
                            <?php echo esc_html($user_label); ?>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Título (automático)', YUNIORROJAS_TEXT_DOMAIN); ?>:</strong>
                        <span class="jr-reserva-admin__titulo-auto"><?php echo esc_html($post->post_title); ?></span>
                        <p class="description"><?php esc_html_e('Se regenera al guardar. No lo edites a mano.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
                    </li>
                </ul>
            </section>
        </div>
    </div>
    <?php
}

/**
 * Acciones laterales: cancelar cita.
 */
function yuniorrojas_reserva_metabox_acciones_render(WP_Post $post): void
{
    $reserva = yuniorrojas_obtener_reserva((int) $post->ID);
    $estado  = is_array($reserva) ? (string) ($reserva['estado'] ?? '') : '';
    $ya_cancelada = $estado === 'cancelada';

    echo '<p class="description">' . esc_html__('Usa Actualizar para guardar cambios. Cancelar cita marca el estado sin borrar el registro.', YUNIORROJAS_TEXT_DOMAIN) . '</p>';

    if ($ya_cancelada) {
        echo '<p><strong>' . esc_html__('Esta cita ya está cancelada.', YUNIORROJAS_TEXT_DOMAIN) . '</strong></p>';
        return;
    }

    $url = wp_nonce_url(
        add_query_arg(
            array(
                'action'  => 'jr_cancelar_reserva',
                'post_id' => (int) $post->ID,
            ),
            admin_url('admin-post.php')
        ),
        'jr_cancelar_reserva_' . (int) $post->ID,
        'jr_cancelar_nonce'
    );
    ?>
    <p>
        <a
            class="button button-secondary jr-reserva-cancelar-btn"
            href="<?php echo esc_url($url); ?>"
            data-jr-cancelar-cita
            onclick="return confirm('<?php echo esc_js(__('¿Cancelar esta cita? El registro se mantendrá en historial.', YUNIORROJAS_TEXT_DOMAIN)); ?>');">
            <?php esc_html_e('Cancelar cita', YUNIORROJAS_TEXT_DOMAIN); ?>
        </a>
    </p>
    <p class="description"><?php esc_html_e('La papelera es solo para pruebas o basura.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
    <?php
}

/**
 * Guarda metabox al actualizar.
 */
function yuniorrojas_reserva_guardar_metabox(int $post_id, WP_Post $post): void
{
    static $guardando = false;
    if ($guardando) {
        return;
    }

    if ($post->post_type !== YUNIORROJAS_CPT_RESERVAS) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['yuniorrojas_reserva_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['yuniorrojas_reserva_nonce'])), 'yuniorrojas_guardar_reserva')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $guardando = true;
    $result = yuniorrojas_admin_guardar_reserva($post_id, array(
        'cliente_nombres'   => isset($_POST['jr_cliente_nombres']) ? wp_unslash((string) $_POST['jr_cliente_nombres']) : '',
        'cliente_apellidos' => isset($_POST['jr_cliente_apellidos']) ? wp_unslash((string) $_POST['jr_cliente_apellidos']) : '',
        'cliente_telefono'  => isset($_POST['jr_cliente_telefono']) ? wp_unslash((string) $_POST['jr_cliente_telefono']) : '',
        'cliente_email'     => isset($_POST['jr_cliente_email']) ? wp_unslash((string) $_POST['jr_cliente_email']) : '',
        'cliente_notas'     => isset($_POST['jr_cliente_notas']) ? wp_unslash((string) $_POST['jr_cliente_notas']) : '',
        'notas_internas'    => isset($_POST['jr_notas_internas']) ? wp_unslash((string) $_POST['jr_notas_internas']) : '',
        'servicio_id'       => isset($_POST['jr_servicio_id']) ? absint($_POST['jr_servicio_id']) : 0,
        'barbero_id'        => isset($_POST['jr_barbero_id']) ? absint($_POST['jr_barbero_id']) : 0,
        'fecha'             => isset($_POST['jr_fecha']) ? wp_unslash((string) $_POST['jr_fecha']) : '',
        'hora'              => isset($_POST['jr_hora']) ? wp_unslash((string) $_POST['jr_hora']) : '',
        'duracion'          => isset($_POST['jr_duracion']) ? absint($_POST['jr_duracion']) : 60,
        'precio'            => isset($_POST['jr_precio']) ? wp_unslash((string) $_POST['jr_precio']) : '',
        'estado'               => isset($_POST['jr_estado']) ? wp_unslash((string) $_POST['jr_estado']) : 'confirmada',
        'metodo_pago'          => isset($_POST['jr_metodo_pago']) ? wp_unslash((string) $_POST['jr_metodo_pago']) : 'estudio',
        'codigo_operacion'     => isset($_POST['jr_codigo_operacion']) ? wp_unslash((string) $_POST['jr_codigo_operacion']) : '',
        'pago_verificado'      => isset($_POST['jr_pago_verificado']) ? '1' : '0',
        'imagen_resultado_id'  => isset($_POST['jr_imagen_resultado_id']) ? absint($_POST['jr_imagen_resultado_id']) : 0,
    ));
    $guardando = false;

    if (is_wp_error($result)) {
        set_transient(
            'jr_reserva_admin_error_' . get_current_user_id(),
            $result->get_error_message(),
            45
        );
    }
}
add_action('save_post_' . YUNIORROJAS_CPT_RESERVAS, 'yuniorrojas_reserva_guardar_metabox', 20, 2);

/**
 * Acción rápida: cancelar cita desde el lateral.
 */
function yuniorrojas_reserva_admin_cancelar_action(): void
{
    $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ($post_id <= 0 || get_post_type($post_id) !== YUNIORROJAS_CPT_RESERVAS) {
        wp_die(esc_html__('Reserva no válida.', YUNIORROJAS_TEXT_DOMAIN));
    }

    if (!current_user_can('edit_post', $post_id)) {
        wp_die(esc_html__('No tienes permiso para cancelar esta reserva.', YUNIORROJAS_TEXT_DOMAIN));
    }

    if (!isset($_GET['jr_cancelar_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_GET['jr_cancelar_nonce'])), 'jr_cancelar_reserva_' . $post_id)) {
        wp_die(esc_html__('Enlace no válido.', YUNIORROJAS_TEXT_DOMAIN));
    }

    update_post_meta($post_id, yuniorrojas_reserva_meta_key('estado'), 'cancelada');

    $reserva = yuniorrojas_obtener_reserva($post_id);
    if (is_array($reserva) && function_exists('yuniorrojas_notificar_reserva')) {
        yuniorrojas_notificar_reserva($post_id, 'cancelada');
    }
    if (is_array($reserva) && function_exists('yuniorrojas_lista_espera_avisar_hueco')) {
        $bid = (int) ($reserva['barbero_id'] ?? 0);
        $fecha = (string) ($reserva['fecha'] ?? '');
        if ($bid > 0 && $fecha !== '') {
            yuniorrojas_lista_espera_avisar_hueco($bid, $fecha);
        }
    }

    wp_safe_redirect(add_query_arg(
        array(
            'post'              => $post_id,
            'action'            => 'edit',
            'jr_reserva_notice' => 'cancelada',
        ),
        admin_url('post.php')
    ));
    exit;
}
add_action('admin_post_jr_cancelar_reserva', 'yuniorrojas_reserva_admin_cancelar_action');

/**
 * Avisos admin al guardar / cancelar.
 */
function yuniorrojas_reserva_admin_notices(): void
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== YUNIORROJAS_CPT_RESERVAS) {
        return;
    }

    $error = get_transient('jr_reserva_admin_error_' . get_current_user_id());
    if (is_string($error) && $error !== '') {
        delete_transient('jr_reserva_admin_error_' . get_current_user_id());
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
    }

    if (isset($_GET['jr_reserva_notice']) && sanitize_key(wp_unslash((string) $_GET['jr_reserva_notice'])) === 'cancelada') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Cita cancelada. Queda en el historial.', YUNIORROJAS_TEXT_DOMAIN) . '</p></div>';
    }
}
add_action('admin_notices', 'yuniorrojas_reserva_admin_notices');

/**
 * Ajustes de pantalla: título de solo lectura + ocultar ruido de publicación.
 */
function yuniorrojas_reserva_admin_screen_setup(): void
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== YUNIORROJAS_CPT_RESERVAS) {
        return;
    }

    // Sin editor de contenido.
    remove_post_type_support(YUNIORROJAS_CPT_RESERVAS, 'editor');
}
add_action('load-post.php', 'yuniorrojas_reserva_admin_screen_setup');
add_action('load-post-new.php', 'yuniorrojas_reserva_admin_screen_setup');

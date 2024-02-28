add_action( 'woocommerce_before_add_to_cart_button', 'mi_campo_texto_producto' );

function mi_campo_texto_producto() {
    if ( ! is_product() ) {
        return;
    }

    $producto_id = get_the_ID();
    $titulo_producto = get_the_title( $producto_id );
    preg_match( '/^\d+/', $titulo_producto, $matches );
    $valor_numerico = intval( $matches[0] ) * 1000; 
    if ($valor_numerico >= 100000) {
        $valor_numerico /= 1000;
    }
    // Obtener el ID del servicio del título del producto.
    $servicio_id = 0;
    if ( strpos( $titulo_producto, "Seguidores" ) !== false ) {
        if ( strpos( $titulo_producto, "Instagram" ) !== false ) {
            $servicio_id = 9968;
        } elseif ( strpos( $titulo_producto, "TikTok" ) !== false ) {
            $servicio_id = 9235;
        }
    } elseif ( strpos( $titulo_producto, "Likes" ) !== false ) {
        if ( strpos( $titulo_producto, "Instagram" ) !== false ) {
            $servicio_id = 10064;
        } elseif ( strpos( $titulo_producto, "TikTok" ) !== false ) {
            $servicio_id = 10672;
        }
    } elseif ( strpos( $titulo_producto, "Visualizaciones" ) !== false ) {
        $servicio_id = 1738;
    }

    ?>
    <p>
        <label for="campo_texto_producto">Link:</label>
        <input type="text" id="campo_texto_producto" name="campo_texto_producto" placeholder="Introduce el enlace">
    </p>
    <?php

    global $product;

    // Obtener el valor del campo del POST
    $campo_texto_producto = isset( $_POST['campo_texto_producto'] ) ? sanitize_text_field( $_POST['campo_texto_producto'] ) : '';

    // Almacenar el valor del campo en el metadato del producto
    update_post_meta( $product->get_id(), '_campo_texto_producto', $campo_texto_producto );

    // Almacenar el valor numérico en el metadato del producto
    update_post_meta( $product->get_id(), '_valor_numerico', $valor_numerico );

    // Almacenar el ID del servicio en el metadato del producto
    update_post_meta( $product->get_id(), '_service_id', $servicio_id );
}

add_filter( 'woocommerce_add_to_cart_validation', 'validar_campo_texto_producto', 10, 5 );

function validar_campo_texto_producto( $passed ) { 
    if ( empty( $_POST['campo_texto_producto'] ) ) {
        wc_add_notice( __( 'El campo de texto no puede estar vacío.', 'woocommerce' ), 'error' ); 
        $passed = false; 
    } 

    return $passed; 
}

add_action('woocommerce_payment_complete', 'enviar_datos_api_per_product');

function enviar_datos_api_per_product($order_id) {
    $url = 'https://smmfollows.com/api/v2';
    $api_key = 'YOUR_API_KEY';
    $order = wc_get_order($order_id);

    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();

        // Retrieve product information
        $servicio_id = get_post_meta($product_id, '_service_id', true);
        $custom_link = get_post_meta($product_id, '_campo_texto_producto', true);
        $valor_numerico = get_post_meta($product_id, '_valor_numerico', true);

        // Prepare API request data
        $body = array(
            'key' => $api_key,
            'action' => 'add',
            'service' => $servicio_id,
            'link' => $custom_link,
            'quantity' => $valor_numerico,
        );

        // Set headers
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
            'Content-Type' => 'application/json',
        );

        // Send API request and handle response
        try {
            $response = wp_remote_post($url, array(
                'method' => 'POST',
                'body' => json_encode($body),
                'headers' => $headers,
            ));

        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
        }
    }
}

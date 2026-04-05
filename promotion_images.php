<?php
/**
 * raw-json.php
 *
 * A tiny standalone PHP file that returns JSON for promotion images.
 * Place this file in your plugin root (or anywhere under your WP install).
 *
 * Behavior:
 * - If it can locate & load WordPress (wp-load.php) by walking up the directory tree,
 *   it will read the option `pinaka_pos_promotion_images` and return the same
 *   structured JSON as the REST endpoint.
 * - If WP is not found (or option empty), it returns a safe example JSON array.
 *
 * Access in browser:
 *  https://yourdomain.com/wp-content/plugins/pinaka-promotion-images/raw-json.php
 *
 * NOTE: exposing plugin files directly can be a security consideration; keep this
 * file internal or protect it if the data is sensitive.
 */

declare( strict_types=1 );

// Try to find and load wp-load.php (so we can use get_option).
$loaded_wp = false;
$dir = __DIR__;
for ($i = 0; $i < 6; $i++) {
    $maybe = $dir . '/wp-load.php';
    if ( file_exists( $maybe ) ) {
        require_once $maybe;
        $loaded_wp = true;
        break;
    }
    $dir = dirname( $dir );
}

// Helper to build result item for an attachment id or url
function build_item_from( $item ) {
    // If WP is available and item looks like attachment id
    if ( function_exists( 'wp_get_attachment_url' ) && is_numeric( $item ) && intval( $item ) ) {
        $id  = intval( $item );
        $url = wp_get_attachment_url( $id );
        if ( ! $url ) {
            return null;
        }

        $alt     = get_post_meta( $id, '_wp_attachment_image_alt', true );
        $title   = get_the_title( $id );
        $caption = wp_get_attachment_caption( $id );

        $thumb = wp_get_attachment_image_src( $id, 'thumbnail' );
        $full  = wp_get_attachment_image_src( $id, 'full' );

        return array(
            'id'      => $id,
            'url'     => esc_url_raw( $url ),
            'alt'     => $alt ? wp_kses_post( $alt ) : '',
            'title'   => $title ? wp_kses_post( $title ) : '',
            'caption' => $caption ? wp_kses_post( $caption ) : '',
            'sizes'   => array(
                'thumbnail' => $thumb ? esc_url_raw( $thumb[0] ) : '',
                'full'      => $full ? esc_url_raw( $full[0] ) : '',
            ),
        );
    }

    // Treat as URL string
    $url = is_string( $item ) ? $item : '';
    $url = filter_var( $url, FILTER_SANITIZE_URL );
    if ( empty( $url ) ) {
        return null;
    }

    return array(
        'id'      => null,
        'url'     => $url,
        'alt'     => '',
        'title'   => '',
        'caption' => '',
        'sizes'   => array(),
    );
}

// Get items: try WP option, else fallback example
$items = array();
if ( $loaded_wp && function_exists( 'get_option' ) ) {
    $raw = get_option( 'pinaka_pos_promotion_images', array() );
    if ( is_array( $raw ) ) {
        $items = $raw;
    }
}

if ( empty( $items ) ) {
    // Example fallback data (replace with your own URLs or IDs as needed)
    $items = array(
        'https://via.placeholder.com/1200x600.png?text=Promo+1',
        'https://via.placeholder.com/800x400.png?text=Promo+2',
    );
}

// Build normalized result array
$result = array();
foreach ( $items as $it ) {
    $row = build_item_from( $it );
    if ( null !== $row ) {
        $result[] = $row;
    }
}

// Output JSON (raw)
header( 'Content-Type: application/json; charset=utf-8' );
// Avoid JSON_HEX_TAG/AMP/ etc to keep URLs readable
echo json_encode( $result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
exit;

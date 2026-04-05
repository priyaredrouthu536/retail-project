<?php
/**
 * REST API variations controller
 *
 * Handles requests to the /products/<product_id>/variations endpoints.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

defined('ABSPATH') || exit;
/**
 * REST API variations controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Variations_V2_Controller
 */
class Pinaka_Product_Variations_Controller extends WC_REST_Product_Variations_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'pinaka-pos/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'products/variations';

    /**
     * Prepare a single variation output for response.
     *
     * @param  WC_Data         $object  Object data.
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function prepare_object_for_response($object, $request)
    {
        $data = array(
            'id'                    => $object->get_id(),
            'parent_id'             => $object->get_parent_id(),
            'date_created'          => wc_rest_prepare_date_response($object->get_date_created(), false),
            'date_created_gmt'      => wc_rest_prepare_date_response($object->get_date_created()),
            'date_modified'         => wc_rest_prepare_date_response($object->get_date_modified(), false),
            'date_modified_gmt'     => wc_rest_prepare_date_response($object->get_date_modified()),
            'description'           => wc_format_content($object->get_description()),
            'permalink'             => $object->get_permalink(),
            'sku'                   => $object->get_sku(),
            'price'                 => $object->get_price(),
            'regular_price'         => $object->get_regular_price(),
            'sale_price'            => $object->get_sale_price(),
            'date_on_sale_from'     => wc_rest_prepare_date_response($object->get_date_on_sale_from(), false),
            'date_on_sale_from_gmt' => wc_rest_prepare_date_response($object->get_date_on_sale_from()),
            'date_on_sale_to'       => wc_rest_prepare_date_response($object->get_date_on_sale_to(), false),
            'date_on_sale_to_gmt'   => wc_rest_prepare_date_response($object->get_date_on_sale_to()),
            'on_sale'               => $object->is_on_sale(),
            'status'                => $object->get_status(),
            'purchasable'           => $object->is_purchasable(),
            'virtual'               => $object->is_virtual(),
            'downloadable'          => $object->is_downloadable(),
            'downloads'             => $this->get_downloads($object),
            'download_limit'        => '' !== $object->get_download_limit() ? (int) $object->get_download_limit() : -1,
            'download_expiry'       => '' !== $object->get_download_expiry() ? (int) $object->get_download_expiry() : -1,
            'tax_status'            => $object->get_tax_status(),
            'tax_class'             => $object->get_tax_class(),
            'manage_stock'          => $object->managing_stock(),
            'stock_quantity'        => $object->get_stock_quantity(),
            'stock_status'          => $object->get_stock_status(),
            'backorders'            => $object->get_backorders(),
            'backorders_allowed'    => $object->backorders_allowed(),
            'backordered'           => $object->is_on_backorder(),
            'low_stock_amount'      => '' === $object->get_low_stock_amount() ? null : $object->get_low_stock_amount(),
            'weight'                => $object->get_weight(),
            'dimensions'            => array(
                'length' => $object->get_length(),
                'width'  => $object->get_width(),
                'height' => $object->get_height(),
            ),
            'shipping_class'        => $object->get_shipping_class(),
            'shipping_class_id'     => $object->get_shipping_class_id(),
            'image'                 => $this->get_image($object),
            'attributes'            => $this->get_attributes($object),
            'menu_order'            => $object->get_menu_order(),
            'meta_data'             => $object->get_meta_data(),
        );

        $context  = ! empty($request['context']) ? $request['context'] : 'view';
        $data     = $this->add_additional_fields_to_object($data, $request);
        $data     = $this->filter_response_by_context($data, $context);
        $response = rest_ensure_response($data);
        $response->add_links($this->prepare_links($object, $request));

        /**
         * Filter the data for a response.
         *
         * The dynamic portion of the hook name, $this->post_type,
         * refers to object type being prepared for the response.
         *
         * @param WP_REST_Response $response The response object.
         * @param WC_Data          $object   Object data.
         * @param WP_REST_Request  $request  Request object.
         */
        return $response;
    }

    /**
     * Bulk create, update and delete items.
     * We add product_id for avoiding error / warning.
     *
     * @since  3.0.0
     * @param WP_REST_Request $request Full details about the request.
     * @return array Of WP_Error or WP_REST_Response.
     */
    public function batch_items($request)
    {
        $params               = $request->get_url_params();
        $params['product_id'] = 0;
        $request->set_url_params($params);
        return parent::batch_items($request);
    }


     /**
     * Prepare a single variation for create or update.
     *
     * @param  WP_REST_Request $request Request object.
     * @param  bool            $creating If is creating a new object.
     * @return WP_Error|WC_Data
     */
    protected function prepare_object_for_database($request2, $creating = false)
    {
        $request =$request2;
        if (isset($request['id'])) {
            $variation = wc_get_product(absint($request['id']));
        } else {
            $variation = new WC_Product_Variation();
        }

        $variation->set_parent_id(absint($request['product_id']));

        if (isset($request['purchase_price'])) {
            global $wpdb;


            // Get the parent product ID
            $product_id = $request['product_id'];

            // Get the purchase price
            $purchase_price = $request['purchase_price'];

            // Query for a variation product with the same purchase price
            $variation_id = $wpdb->get_var(
                $wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} AS p
            LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product_variation'
            AND p.post_parent = %s
            AND pm.meta_key = 'purchase_price'
            AND pm.meta_value = %s LIMIT 1
        ", $product_id, $purchase_price)
            );
            if($variation_id) {
                $variation = wc_get_product($variation_id);
                $request->set_query_params([]);
                $request['attributes']=null;
            } else {



                // Get the attribute ID for 'purchase_date' attribute, or create it if it doesn't exist
                $purchase_date_attribute_name = 'purchase_date';
                $purchase_date_attribute_value = date('Y-m-d');
                $purchase_date_attribute_id = wc_attribute_taxonomy_id_by_name($purchase_date_attribute_name);
                if (! $purchase_date_attribute_id) {
                    $args = array(
                        'slug'         => sanitize_title($purchase_date_attribute_name),
                        'name'         => "Purchase Date",
                        'type'         => 'select',
                        'order_by'     => 'menu_order',
                        'has_archives' => false,
                    );
                    $purchase_date_attribute_id = wc_create_attribute($args);
                }

                // Get the parent product
                $parent_product = wc_get_product($request['product_id']);

                $product_id = $parent_product->get_id();


                // Check if the parent product is not a variable product
                if (! $parent_product->is_type('variable')) {

                    $new_product = new WC_Product_Variable($request['product_id']);
                    $new_product->set_name($parent_product->get_name());
                    $new_product->set_status($parent_product->get_status());
                    $new_product->set_regular_price('');
                    $new_product->set_sale_price('');
                    $new_product->set_price('');
                    $new_product->save();

                    $new_variation = new WC_Product_Variation();
                    $new_variation->set_regular_price($parent_product->get_regular_price());
                    $new_variation->set_sale_price($parent_product->get_sale_price());
                    $new_variation->set_price($parent_product->get_price());
                    $new_variation->set_short_description($parent_product->get_short_description());
                    $new_variation->set_parent_id($product_id);

                    $variation_id = $new_variation->save();

                    $parent_purchase_price = get_post_meta($product_id, 'purchase_price',true);


                    update_post_meta($variation_id, 'purchase_price', $parent_purchase_price);


                    $parent_product = wc_get_product($request['product_id']);

                }




                $taxonomy = 'pa_'.$purchase_date_attribute_name;
                $clean_keywords = array($purchase_date_attribute_value);
                $term_taxonomy_ids = wp_set_object_terms($product_id, $clean_keywords, $taxonomy, true);

                // Get existing attributes
                $product_attributes = get_post_meta($product_id, '_product_attributes', true);

                // get the count of existing attributes to set the "position" in the array
                $count = count($product_attributes);

                // Insert new attribute in existing array of attributes (if there is any)
                $product_attributes[$taxonomy] = array(
                    'name' => $taxonomy,
                    'value' => $purchase_date_attribute_value,
                    'position' => $count, // added
                    'is_visible' => '0',
                    'is_variation' => '1', // added (set the right value)
                    'is_taxonomy' => '1'
                );

                // Save the data
                update_post_meta($product_id, '_product_attributes', $product_attributes);



                $request['attributes']=array(
                    array(
                        'id'=>$purchase_date_attribute_id,
                        'option'=>$purchase_date_attribute_value,
                    )
                );


            }



        }





        // Status.
        if (isset($request['status'])) {
            $variation->set_status(get_post_status_object($request['status']) ? $request['status'] : 'draft');
        }

        // SKU.
        if (isset($request['sku'])) {
            $variation->set_sku(wc_clean($request['sku']));
        }

        // Thumbnail.
        if (isset($request['image'])) {
            if (is_array($request['image'])) {
                $variation = $this->set_variation_image($variation, $request['image']);
            } else {
                $variation->set_image_id('');
            }
        }

        // Virtual variation.
        if (isset($request['virtual'])) {
            $variation->set_virtual($request['virtual']);
        }

        // Downloadable variation.
        if (isset($request['downloadable'])) {
            $variation->set_downloadable($request['downloadable']);
        }

        // Downloads.
        if ($variation->get_downloadable()) {
            // Downloadable files.
            if (isset($request['downloads']) && is_array($request['downloads'])) {
                $variation = $this->save_downloadable_files($variation, $request['downloads']);
            }

            // Download limit.
            if (isset($request['download_limit'])) {
                $variation->set_download_limit($request['download_limit']);
            }

            // Download expiry.
            if (isset($request['download_expiry'])) {
                $variation->set_download_expiry($request['download_expiry']);
            }
        }

        // Shipping data.
        $variation = $this->save_product_shipping_data($variation, $request);

        // Stock handling.
        if (isset($request['manage_stock'])) {
            $variation->set_manage_stock($request['manage_stock']);
        }

        if (isset($request['stock_status'])) {
            $variation->set_stock_status($request['stock_status']);
        }

        if (isset($request['backorders'])) {
            $variation->set_backorders($request['backorders']);
        }

        if ($variation->get_manage_stock()) {
            if (isset($request['stock_quantity'])) {
                $variation->set_stock_quantity($request['stock_quantity']);
            } elseif (isset($request['inventory_delta'])) {
                $stock_quantity  = wc_stock_amount($variation->get_stock_quantity());
                $stock_quantity += wc_stock_amount($request['inventory_delta']);
                $variation->set_stock_quantity($stock_quantity);
            }
            // isset() returns false for value null, thus we need to check whether the value has been sent by the request.
            if (array_key_exists('low_stock_amount', $request->get_params())) {
                if (null === $request['low_stock_amount']) {
                    $variation->set_low_stock_amount('');
                } else {
                    $variation->set_low_stock_amount(wc_stock_amount($request['low_stock_amount']));
                }
            }
        } else {
            $variation->set_backorders('no');
            $variation->set_stock_quantity('');
            $variation->set_low_stock_amount('');
        }

        // Regular Price.
        if (isset($request['regular_price'])) {
            $variation->set_regular_price($request['regular_price']);
        }

        // Sale Price.
        if (isset($request['sale_price'])) {
            $variation->set_sale_price($request['sale_price']);
        }

        if (isset($request['date_on_sale_from'])) {
            $variation->set_date_on_sale_from($request['date_on_sale_from']);
        }

        if (isset($request['date_on_sale_from_gmt'])) {
            $variation->set_date_on_sale_from($request['date_on_sale_from_gmt'] ? strtotime($request['date_on_sale_from_gmt']) : null);
        }

        if (isset($request['date_on_sale_to'])) {
            $variation->set_date_on_sale_to($request['date_on_sale_to']);
        }

        if (isset($request['date_on_sale_to_gmt'])) {
            $variation->set_date_on_sale_to($request['date_on_sale_to_gmt'] ? strtotime($request['date_on_sale_to_gmt']) : null);
        }

        // Tax class.
        if (isset($request['tax_class'])) {
            $variation->set_tax_class($request['tax_class']);
        }

        // Description.
        if (isset($request['description'])) {
            $variation->set_description(wp_kses_post($request['description']));
        }

        // Update taxonomies.
        if (isset($request['attributes'])) {


            $attributes = array();
            $parent     = wc_get_product($variation->get_parent_id());

            if (! $parent) {
                return new WP_Error(
                    // Translators: %d parent ID.
                    "woocommerce_rest_{$this->post_type}_invalid_parent",
                    __('Cannot set attributes due to invalid parent product.', 'woocommerce'),
                    array( 'status' => 404 )
                );
            }

            $parent_attributes = $parent->get_attributes();

            foreach ($request['attributes'] as $attribute) {


                $attribute_id   = 0;
                $attribute_name = '';


                // Check ID for global attributes or name for product attributes.
                if (! empty($attribute['id'])) {
                    $attribute_id   = absint($attribute['id']);
                    $attribute_name = wc_attribute_taxonomy_name_by_id($attribute_id);
                } elseif (! empty($attribute['name'])) {
                    $attribute_name = sanitize_title($attribute['name']);
                }

                if (! $attribute_id && ! $attribute_name) {
                    continue;
                }

                if (! isset($parent_attributes[ $attribute_name ]) || ! $parent_attributes[ $attribute_name ]->get_variation()) {
                    continue;
                }

                $attribute_key   = sanitize_title($parent_attributes[ $attribute_name ]->get_name());
                $attribute_value = isset($attribute['option']) ? wc_clean(stripslashes($attribute['option'])) : '';

                if ($parent_attributes[ $attribute_name ]->is_taxonomy()) {
                    // If dealing with a taxonomy, we need to get the slug from the name posted to the API.
                    $term = get_term_by('name', $attribute_value, $attribute_name);

                    if ($term && ! is_wp_error($term)) {
                        $attribute_value = $term->slug;
                    } else {
                        $attribute_value = sanitize_title($attribute_value);
                    }
                }

                $attributes[ $attribute_key ] = $attribute_value;
            }


            $variation->set_attributes($attributes);
        }

        // Menu order.
        if ($request['menu_order']) {
            $variation->set_menu_order($request['menu_order']);
        }

        // Meta data.
        if (is_array($request['meta_data'])) {
            foreach ($request['meta_data'] as $meta) {
                $variation->update_meta_data($meta['key'], $meta['value'], isset($meta['id']) ? $meta['id'] : '');
            }
        }


        /**
         * Filters an object before it is inserted via the REST API.
         *
         * The dynamic portion of the hook name, `$this->post_type`,
         * refers to the object type slug.
         *
         * @param WC_Data         $variation Object object.
         * @param WP_REST_Request $request   Request object.
         * @param bool            $creating  If is creating a new object.
         */
        return apply_filters("woocommerce_rest_pre_insert_{$this->post_type}_object", $variation, $request2, $creating);
    }
}

<?php
namespace PinakaPos\Admin\Mapping;

if (!defined('WPINC')) {
    die;
}

class MappingPage {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    public function import_modifiers_csv_page() 
    {
        ?>
        <style>
            .pinaka-import-wrap {
                background: #fff;
                padding: 30px;
                margin: 20px 0;
                border-left: 4px solid #0073aa;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                border-radius: 6px;
            }
            .pinaka-import-wrap h1 {
                margin-top: 0;
                font-size: 1.8em;
                color: #0073aa;
            }
            .pinaka-import-wrap form {
                margin-top: 15px;
            }
            .pinaka-import-wrap input[type="file"] {
                display: block;
                margin-bottom: 15px;
            }
            .pinaka-import-wrap .button-primary {
                background: #0073aa;
                border-color: #006799;
                box-shadow: none;
            }
            .pinaka-import-wrap .button-primary:hover {
                background: #006799;
                border-color: #005d88;
            }
        </style>
        <div class="wrap">
            <div class="pinaka-import-wrap">
                <h1>Import Modifiers CSV</h1>
                <p>
                    <a href="<?php echo plugins_url('class-pinaka-modifer-dowload.php',dirname(__FILE__, 1)); ?>" class="button">Download Sample CSV</a>
                </p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('import_modifiers_csv_action', 'import_modifiers_csv_nonce'); ?>
                    <?php
                    $restaurants = get_option('pinaka_pos_restaurants', []);
                    ?>
                    <p>
                        <h1>Please select restaurant</h1>
                        <select name="restaurant_id" id="restaurant_id" style="width:100%;" autocomplete="off">
                            <option value="">-- Select Restaurant --</option>
                            <?php foreach ($restaurants as $restaurant): ?>
                                <option value="<?php echo esc_attr($restaurant['id']); ?>">
                                    <?php echo esc_html($restaurant['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <input type="file" name="modifiers_csv" accept=".csv" required>
                    <input type="submit" class="button button-primary" value="Import CSV">
                </form>
                <?php
                if ( isset($_GET['status']) && $_GET['status'] === 'modifiers_success' ) {
                    echo '<div class="notice notice-success"><p>✅ Modifiers CSV uploaded successfully!</p></div>';
                }
                ?>
            </div>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['modifiers_csv']))
            {
                if(!isset($_POST['restaurant_id']) || empty($_POST['restaurant_id'])) {
                    echo '<div class="notice notice-error"><p>Please select a restaurant before importing modifiers.</p></div>';
                    return;
                }
                $this->handle_import_modifiers_csv();
            }
            ?>
            <div class="pinaka-import-wrap">
                <h1>Import Add-ons CSV</h1>
                <p>
                    <a href="<?php echo plugins_url('class-pinaka-addon-dowload.php',dirname(__FILE__, 1)); ?>" class="button">Download Sample CSV</a>
                </p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('import_add_ons_csv_action', 'import_add_ons_csv_nonce'); ?>
                    <?php
                    $restaurants = get_option('pinaka_pos_restaurants', []);
                    ?>
                    <p>
                        <h1>Please select restaurant</h1>
                        <select name="restaurant_id" id="restaurant_id" style="width:100%;" autocomplete="off">
                            <option value="">-- Select Restaurant --</option>
                            <?php foreach ($restaurants as $restaurant): ?>
                                <option value="<?php echo esc_attr($restaurant['id']); ?>">
                                    <?php echo esc_html($restaurant['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <input type="file" name="add_ons_csv" accept=".csv" required>
                    <input type="submit" class="button button-primary" value="Import CSV">
                </form>
                <?php
                if ( isset($_GET['status']) && $_GET['status'] === 'addons_success' ) {
                    echo '<div class="notice notice-success"><p>✅ Add-ons CSV uploaded successfully!</p></div>';
                }
                ?>
            </div>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['add_ons_csv']))
            {
                if(!isset($_POST['restaurant_id']) || empty($_POST['restaurant_id'])) {
                    echo '<div class="notice notice-error"><p>Please select a restaurant before importing add-ons.</p></div>';
                    return;
                }
                $this->handle_import_addons_csv();
            }
            ?>
        </div>
        <?php
    }

	private function normalize_meta_array($value) 
    {
        if (empty($value)) return [];
        if (is_array($value)) return array_values(array_unique(array_map('intval', $value)));
        if (is_string($value)) {
            if (maybe_unserialize($value) !== $value) {
                $arr = maybe_unserialize($value);
                if (is_array($arr)) return array_values(array_unique(array_map('intval', $arr)));
            }
            if (strpos($value, ',') !== false) {
                return array_values(array_unique(array_map('intval', array_map('trim', explode(',', $value)))));
            }
            return [(int)$value];
        }
        return [(int)$value];
    }
    private function get_all_child_term_ids($parent_id, $taxonomy = 'product_cat') 
    {
        $term_ids = [];
        $children = get_terms([
            'taxonomy' => $taxonomy,
            'parent' => $parent_id,
            'hide_empty' => false
        ]);
        foreach ($children as $child) {
            $term_ids[] = $child->term_id;
            $term_ids = array_merge($term_ids, $this->get_all_child_term_ids($child->term_id, $taxonomy));
        }
        return $term_ids;
    }

    public function handle_import_modifiers_csv() 
    {
        if (!isset($_POST['import_modifiers_csv_nonce']) || !wp_verify_nonce($_POST['import_modifiers_csv_nonce'], 'import_modifiers_csv_action')) {
            wp_die('Security check failed.');
        }

        $user_id = get_current_user_id();
        $logs = [];
        $file = $_FILES['modifiers_csv']['tmp_name'];
        
        if (($handle = fopen($file, 'r')) !== false) 
        {
            $header = fgetcsv($handle);
            if (!$header || count($header) < 2) {
                echo "<div class='notice notice-error'><p>CSV file seems empty or missing expected columns.</p></div>";
                fclose($handle);
                return;
            }

            // Normalize header names (trim + lowercase)
            // $header = array_map(function($h) { return strtolower(trim($h)); }, $header);
            $header = array_map(function($h) {
                $h = preg_replace('/\x{FEFF}|\xEF\xBB\xBF/u', '', $h);
                return strtolower(trim($h));
            }, $header);
         
            if ($header[0] !== 'categories' || $header[1] !== 'modifiers') {
                echo "<div class='notice notice-error'><p>CSV header mismatch. Expected: <b>Categories, Modifiers</b>.</p></div>";
                fclose($handle);
                return;
            }
            while (($data = fgetcsv($handle)) !== false) 
            {
                list($cat_map, $modifiers_raw) = array_pad($data, 2, '');
                list($category_name, $subcategory_name) = array_map('trim', explode('>', $cat_map) + [null, null]);

                $category_term = $category_name ? get_term_by('name', $category_name, 'product_cat') : null;
                $subcategory_term = $subcategory_name ? get_term_by('name', $subcategory_name, 'product_cat') : null;

                if (!$category_term && $category_name) {
                    $category_term = get_term_by('slug', sanitize_title($category_name), 'product_cat');
                }
                if (!$subcategory_term && $subcategory_name) {
                    $subcategory_term = get_term_by('slug', sanitize_title($subcategory_name), 'product_cat');
                }

                if (!$category_term && !$subcategory_term) {
                    //$logs[] = "<span style='color:red;'>Skipped: categories not found for {$category_name} > {$subcategory_name}</span>";
                    continue;
                }

                $modifiers = array_filter(array_map('trim', explode(',', $modifiers_raw)));
                foreach ($modifiers as $modifier_name) 
                {
                    $modifier_name = ucfirst(strtolower($modifier_name));
                    $existing = get_page_by_title($modifier_name, OBJECT, 'modifier');
                    if ($existing) {
                        $existing_type = get_post_meta($existing->ID, 'modifier_type', true);
                        $existing_status = get_post_status($existing->ID);
                        if ($existing_type !== 'modifier') {
                            if ($existing_status === 'draft' || empty($existing_type)) {
                                // Just skip processing this modifier entirely
                                return; // or continue; inside a loop
                            }
                            $existing = false; // force to create new post
                        }
                    }
                    $post_id = $existing ? $existing->ID : wp_insert_post([
                        'post_title' => $modifier_name,
                        'post_type' => 'modifier',
                        'post_status' => 'publish',
                        'post_author' => $user_id
                    ]);
                    //$logs[] = $existing ? "Merged modifier: <b>{$modifier_name}</b>" : "Created modifier: <b>{$modifier_name}</b>";

                    // Update category & subcategory IDs
                    $category_ids = $this->normalize_meta_array(get_post_meta($post_id, 'category_ids', true));
                    $subcategory_ids = $this->normalize_meta_array(get_post_meta($post_id, 'subcategory_ids', true));
                    if ($category_term) $category_ids[] = $category_term->term_id;
                    if ($subcategory_term) $subcategory_ids[] = $subcategory_term->term_id;

                    update_post_meta($post_id, 'restaurant_id', sanitize_text_field($_POST['restaurant_id']));
                    update_post_meta($post_id, 'modifier_type', 'modifier');
                    update_post_meta($post_id, 'modifier_price', 0.00);
                    update_post_meta($post_id, 'category_ids', array_values(array_unique($category_ids)));
                    update_post_meta($post_id, 'subcategory_ids', array_values(array_unique($subcategory_ids)));

                    // Update products
                    $products = $this->normalize_meta_array(get_post_meta($post_id, 'product_ids', true));

                    if ($subcategory_term) 
                    {
                        $all_term_ids = array_merge([$subcategory_term->term_id], $this->get_all_child_term_ids($subcategory_term->term_id));
                        if (!empty($all_term_ids)) {
                            $product_ids = get_posts([
                                'post_type' => 'product',
                                'fields' => 'ids',
                                'posts_per_page' => -1,
                                'tax_query' => [[
                                    'taxonomy' => 'product_cat',
                                    'field'    => 'term_id',
                                    'terms'    => $all_term_ids,
                                    'include_children' => false
                                ]]
                            ]);
                            $products = array_merge($products, $product_ids);
                        }
                    }

                    update_post_meta($post_id, 'product_ids', array_values(array_unique($products)));
                }
            }
            fclose($handle);
        }

        $url = admin_url('admin.php?page=modifier-mapping');
        $url_with_status = add_query_arg( 'status', 'modifiers_success', $url );

        // Now you can redirect or output it
        wp_redirect( $url_with_status );
        exit;
    }
    public function handle_import_addons_csv() 
    {
        if (!isset($_POST['import_add_ons_csv_nonce']) || !wp_verify_nonce($_POST['import_add_ons_csv_nonce'], 'import_add_ons_csv_action')) {
            wp_die('Security check failed.');
        }
        $user_id = get_current_user_id();
        $logs = [];
        $file = $_FILES['add_ons_csv']['tmp_name']; 

        if (($handle = fopen($file, 'r')) !== false) 
        {
            $header = fgetcsv($handle);
            if (!$header || count($header) < 4) {
                echo "<div class='notice notice-error'><p>CSV file seems empty or missing expected columns.</p></div>";
                fclose($handle);
                return;
            }

            // Normalize header names
            // $header = array_map(function($h) { return strtolower(trim($h)); }, $header);
            $header = array_map(function($h) {
                $h = preg_replace('/\x{FEFF}|\xEF\xBB\xBF/u', '', $h);
                return strtolower(trim($h));
            }, $header);

            if ($header[0] !== 'category' || $header[1] !== 'subcategory' || $header[2] !== 'name' || $header[3] !== 'price') {
                echo "<div class='notice notice-error'><p>CSV header mismatch. Expected: <b>Category, Subcategory, Name, Price</b>.</p></div>";
                fclose($handle);
                return;
            }
            $last_category = '';
            $last_subcategory = '';

            while (($data = fgetcsv($handle, 1000)) !== false) 
            {
                if (count($data) < 4) {
                    $line = implode(" ", $data);
                    $data = preg_split('/\s{2,}|\t/', $line);
                }

                $category_name    = trim($data[0] ?? '');
                $subcategory_name = trim($data[1] ?? '');
                $modifier_name    = trim($data[2] ?? '');
                $modifier_price   = isset($data[3]) ? floatval(preg_replace('/[^\d.]/', '', $data[3])) : 0.00;

                if (empty($category_name))    $category_name    = $last_category;
                if (empty($subcategory_name)) $subcategory_name = $last_subcategory;

                if (empty($category_name) || empty($subcategory_name) || empty($modifier_name)) {
                    //$logs[] = "<span style='color:red;'>Skipped: incomplete row (Category/Subcategory/Modifier missing).</span>";
                    continue;
                }

                $last_category    = $category_name;
                $last_subcategory = $subcategory_name;

                $category_term = get_term_by('name', $category_name, 'product_cat') ?: get_term_by('slug', sanitize_title($category_name), 'product_cat');
                $subcategory_term = get_term_by('name', $subcategory_name, 'product_cat') ?: get_term_by('slug', sanitize_title($subcategory_name), 'product_cat');

                if (!$category_term && !$subcategory_term) {
                    //$logs[] = "<span style='color:red;'>Skipped: categories not found for {$category_name} > {$subcategory_name}</span>";
                    continue;
                }

                $modifier_name = ucfirst(strtolower($modifier_name));
                $existing = get_page_by_title($modifier_name, OBJECT, 'modifier');
                if ($existing) {
                    $existing_type = get_post_meta($existing->ID, 'modifier_type', true);
                    $existing_status = get_post_status($existing->ID);
                    if ($existing_type !== 'add-on') {
                        if ($existing_status === 'draft' || empty($existing_type)) {
                            // Just skip processing this modifier entirely
                            return; // or continue; inside a loop
                        }
                        $existing = false; // force to create new post
                    }
                }
                $post_id = $existing ? $existing->ID : wp_insert_post([
                    'post_title'  => $modifier_name,
                    'post_type'   => 'modifier',
                    'post_status' => 'publish',
                    'post_author' => $user_id
                ]);
                //$logs[] = $existing ? "Merged add-on: <b>{$modifier_name}</b>" : "Created add-on: <b>{$modifier_name}</b>";

                // Merge meta
                $category_ids = $this->normalize_meta_array(get_post_meta($post_id, 'category_ids', true));
                $subcategory_ids = $this->normalize_meta_array(get_post_meta($post_id, 'subcategory_ids', true));

                if ($category_term)    $category_ids[] = $category_term->term_id;
                if ($subcategory_term) $subcategory_ids[] = $subcategory_term->term_id;

                update_post_meta($post_id, 'restaurant_id', sanitize_text_field($_POST['restaurant_id']));
                update_post_meta($post_id, 'modifier_type', 'add-on');
                update_post_meta($post_id, 'modifier_price', $modifier_price);
                update_post_meta($post_id, 'category_ids', array_values(array_unique($category_ids)));
                update_post_meta($post_id, 'subcategory_ids', array_values(array_unique($subcategory_ids)));

                // Attach products to add-on using unified query
                $products = $this->normalize_meta_array(get_post_meta($post_id, 'product_ids', true));
                if ($subcategory_term) {
                    $all_term_ids = array_merge([$subcategory_term->term_id], $this->get_all_child_term_ids($subcategory_term->term_id));
                    if (!empty($all_term_ids)) {
                        $product_posts = get_posts([
                            'post_type' => 'product',
                            'posts_per_page' => -1,
                            'fields' => 'ids',
                            'tax_query' => [[
                                'taxonomy' => 'product_cat',
                                'field'    => 'term_id',
                                'terms'    => $all_term_ids,
                                'include_children' => false
                            ]]
                        ]);
                        $products = array_merge($products, $product_posts);
                    }
                }
                update_post_meta($post_id, 'product_ids', array_values(array_unique($products)));
            }
            fclose($handle);
        }
        $url = admin_url('admin.php?page=modifier-mapping');
        $url_with_status = add_query_arg( 'status', 'addons_success', $url );

        // Now you can redirect or output it
        wp_redirect( $url_with_status );
        exit;
        // echo "<div class='notice notice-success'><h3>Import Done Successfully</h3></div>";
    }
}


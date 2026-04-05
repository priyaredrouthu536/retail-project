<?php
class VendorsPage {
    public function render() {
        echo '<div class="wrap"><h1>' . esc_html__('Manage Vendors', 'pinaka-pos-wp') . '</h1>';
        echo '<p>' . esc_html__('This page will display and manage vendors.', 'pinaka-pos-wp') . '</p></div>';
    }
}

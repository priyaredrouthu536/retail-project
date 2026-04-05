<?php
class DashboardPage {
    public function render() {
        echo '<div class="wrap"><h1>' . esc_html__('Pinaka POS Dashboard', 'pinaka-pos-wp') . '</h1>';
        echo '<p>' . esc_html__('Welcome to the Pinaka POS Dashboard.', 'pinaka-pos-wp') . '</p></div>';
    }
}

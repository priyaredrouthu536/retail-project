<?php
class PaymentsPage {
    public function render() {
        echo '<div class="wrap"><h1>' . esc_html__('Manage Payments', 'pinaka-pos-wp') . '</h1>';
        echo '<p>' . esc_html__('This page will display and manage payments.', 'pinaka-pos-wp') . '</p></div>';
    }
}

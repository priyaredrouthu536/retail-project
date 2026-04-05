<?php
class ShiftsPage {
    public function render() {
        echo '<div class="wrap"><h1>' . esc_html__('Manage Shifts', 'pinaka-pos-wp') . '</h1>';
        echo '<p>' . esc_html__('This page will display and manage shifts.', 'pinaka-pos-wp') . '</p></div>';
    }
}

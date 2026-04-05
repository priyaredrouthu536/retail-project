<?php
require_once( dirname(__FILE__, 6) . '/wp-load.php' );
$file = dirname(__FILE__, 3) . '/uploads/add_ons_mapping.csv';

if (file_exists($file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="add_ons_mapping.csv"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    echo "File not found.";
}

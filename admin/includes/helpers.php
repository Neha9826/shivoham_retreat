<?php
// admin/includes/helpers.php

// A more robust helper to resolve image URLs for display
function resolve_admin_image_url($path) {
    if (empty($path)) {
        return '';
    }

    // Get the base URL from the server's request URI
    // This is more reliable than DOCUMENT_ROOT for subfolder installations
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Check if the current script is in a subdirectory of the admin panel
    // e.g., /admin/nearby_places/sections/get.php
    if (strpos($script_path, '/admin/') !== false) {
        $base_url .= substr($script_path, 0, strpos($script_path, '/admin/') + 7);
    } else {
        $base_url .= $script_path;
    }

    // The image path in the database is relative to the root of the project
    return rtrim($base_url, '/') . '/' . ltrim($path, '/');
}

// Clean CKEditor or textarea input before saving to DB
function clean_editor_input($html, $allowed_tags = '') {
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $clean = strip_tags($html, $allowed_tags);
    $clean = preg_replace('/(\r\n|\n|\r)+$/', '', $clean);
    $clean = trim($clean);
    return $clean;
}

?>
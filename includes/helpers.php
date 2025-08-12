<?php
if (!function_exists('build_image_url')) {
    function build_image_url($path) {
        // If already an absolute URL
        if (preg_match('#^https?://#i', $path)) return $path;
        // Prepend the admin folder to match your actual structure
        return '/ShivohamRetreat/admin/' . ltrim($path, '/');
    }
}

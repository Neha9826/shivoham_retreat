<?php
// Clean CKEditor or textarea input before saving to DB
function clean_editor_input($html, $allowed_tags = '') {
    // Convert <br> to newline before stripping
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

    // Remove unwanted HTML tags (keep only allowed ones)
    $clean = strip_tags($html, $allowed_tags);

    // Remove trailing newlines and spaces
    $clean = preg_replace('/(\r\n|\n|\r)+$/', '', $clean);

    // Trim spaces from start/end
    $clean = trim($clean);

    return $clean;
}

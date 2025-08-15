<?php
if (!function_exists('build_image_url')) {
    function build_image_url($path) {
        // If already an absolute URL
        if (preg_match('#^https?://#i', $path)) return $path;
        // Prepend the admin folder to match your actual structure
        return '/ShivohamRetreat/admin/' . ltrim($path, '/');
    }
}

if (!function_exists('blog_image_url')) {
    function blog_image_url(?string $dbPath, string $siteBase = '/ShivohamRetreat/'): string {
        $placeholder = rtrim($siteBase, '/').'/uploads/no-image.jpg';

        if (!$dbPath) return $placeholder;
        if (preg_match('#^https?://#i', $dbPath)) return $dbPath;

        $p = str_replace('\\', '/', $dbPath);
        while (strpos($p, '../') === 0) $p = substr($p, 3);
        $p = ltrim($p, '/');

        $doc = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
        $fsBase = $doc . rtrim($siteBase, '/');

        $relCandidates = [];
        if (strpos($p, 'admin/') === 0) {
            $relCandidates[] = $p;
            $relCandidates[] = substr($p, 6);
        } else {
            $relCandidates[] = $p;
            $relCandidates[] = 'admin/' . $p;
        }

        foreach ($relCandidates as $rel) {
            $fs = $fsBase . '/' . $rel;
            if (file_exists($fs)) {
                return rtrim($siteBase, '/') . '/' . $rel;
            }
        }

        return rtrim($siteBase, '/') . '/' . $p;
    }
}

<?php

declare(strict_types=1);

/**
 * SEO, Open Graph y meta tags reutilizables.
 *
 * @var array<string, mixed> $meta
 */
$meta = $meta ?? [];
$siteName = 'Likantor — Ingeniería en Estructuras';
$pageTitle = $meta['title'] ?? ($title ?? $siteName);
$fullTitle = ($meta['title_only'] ?? false) ? $pageTitle : $pageTitle . ' | LIKANTOR';
$description = $meta['description'] ?? 'Likantor Ingeniería en Estructuras — diseño, análisis y construcción de estructuras. Masterclasses educativas en línea.';
$canonical = $meta['canonical'] ?? url(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$ogType = $meta['og_type'] ?? 'website';
$ogImage = $meta['og_image'] ?? url('/assets/img/og-default.svg');
$robots = $meta['robots'] ?? 'index, follow';
$extraCss = $meta['extra_css'] ?? [];
$bodyClass = $meta['body_class'] ?? '';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="<?= e($robots) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<meta name="theme-color" content="#0f1724">

<!-- Open Graph -->
<meta property="og:locale" content="es_MX">
<meta property="og:type" content="<?= e($ogType) ?>">
<meta property="og:site_name" content="LIKANTOR">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">

<link rel="stylesheet" href="<?= asset('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('css/components.css') ?>">
<?php foreach ($extraCss as $cssFile): ?>
<link rel="stylesheet" href="<?= asset('css/' . $cssFile) ?>">
<?php endforeach; ?>

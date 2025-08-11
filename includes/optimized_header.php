<?php
/**
 * Optimized Header
 * 
 * This file includes all necessary CSS and meta tags with:
 * - Preloading of critical resources
 * - Non-blocking CSS loading
 * - Proper character encoding and viewport settings
 * - Security headers
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($title ?? 'SLGTI MIS', ENT_QUOTES, 'UTF-8'); ?></title>
    
    <!-- Preload critical CSS -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <!-- Load non-critical CSS asynchronously -->
    <link rel="stylesheet" href="/assets/css/styles.css" media="print" onload="this.media='all'">
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- DNS Prefetch -->
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    
    <!-- Preload critical fonts -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <!-- Preload critical scripts -->
    <link rel="preload" href="https://code.jquery.com/jquery-3.6.0.min.js" as="script">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" as="script">
    
    <!-- Fallback for browsers that don't support preload -->
    <noscript>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>
    
    <!-- Inline critical CSS -->
    <style>
        /* Critical above-the-fold CSS */
        body { 
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        .navbar { 
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        /* Add more critical styles as needed */
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include_once("includes/optimized_menu.php"); ?>
    
    <!-- Main Content Wrapper -->
    <div class="wrapper">
        <!-- Page Content -->
        <div id="content">
            <?php include_once("includes/optimized_top_nav.php"); ?>

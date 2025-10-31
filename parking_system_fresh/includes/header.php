<?php require_once __DIR__ . '/../config/init.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(SITE_NAME) ?> | Smart Parking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="<?= BASE_URL ?>/assets/css/styles.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">
                <i class="fa-solid fa-square-parking text-primary me-1"></i><?= e(SITE_NAME) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'search.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/search.php">Search Slots</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'my_bookings.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/my_bookings.php">My Bookings</a>
                        </li>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/admin/index.php" target="_blank">Admin Panel</a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                           <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user me-1"></i><?= e($_SESSION['user_name'] ?? 'Account') ?>
                           </a>
                           <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                               <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fa-solid fa-right-from-bracket me-1"></i>Logout</a></li>
                           </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'register.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-4 flex-grow-1">
        <?php
        // Display flash messages if any
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']); // Clear after displaying
            $alert_type = $message['type'] ?? 'info';
            // Validate alert type
            $valid_types = ['success', 'danger', 'warning', 'info', 'primary', 'secondary', 'light', 'dark'];
            if (!in_array($alert_type, $valid_types)) $alert_type = 'info';

            echo '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">';
            echo '<strong>' . e(ucfirst($alert_type)) . '!</strong> ' . e($message['text'] ?? 'Notification');
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
        ?>
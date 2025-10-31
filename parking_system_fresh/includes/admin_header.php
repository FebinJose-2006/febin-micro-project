<?php require_once __DIR__ . '/../config/init.php'; // Includes session_start() and $pdo ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="<?= BASE_URL ?>/assets/css/styles.css" rel="stylesheet">
    <style>
        body { background-color: #e9ecef; } /* Light gray background for admin */
        .card { margin-bottom: 1.5rem; }
         .table th { white-space: nowrap; } /* Prevent table headers wrapping */
         .table td { vertical-align: middle; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= BASE_URL ?>/admin/index.php">
                <i class="fa-solid fa-user-shield me-1"></i> Admin - <?= e(SITE_NAME) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbarNav" aria-controls="adminNavbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php" target="_blank" title="View Public Site">
                           <i class="fa-solid fa-external-link-alt me-1"></i> View Site
                        </a>
                    </li>
                     <li class="nav-item dropdown">
                           <a class="nav-link dropdown-toggle" href="#" id="navbarAdminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user-gear me-1"></i><?= e($_SESSION['user_name'] ?? 'Admin') ?>
                           </a>
                           <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarAdminDropdown">
                               <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                                   <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                                   </a>
                               </li>
                           </ul>
                        </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-4 flex-grow-1">
         <?php
         // Display flash messages if any (centralized in admin header)
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
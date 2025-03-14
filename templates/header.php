<?php
// Common header template for all pages
// Set default values if not provided
$page_title = isset($page_title) ? $page_title : 'Guardería';
$current_page = isset($current_page) ? $current_page : '';
$base_path = isset($base_path) ? $base_path : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/main.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top p-0 shadow">
        <div class="container-fluid">
            <a class="navbar-brand me-0 px-3" href="<?php echo $base_path; ?>dashboard.php">
                <i class="bi bi-building"></i> Guardería
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-1"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>dashboard.php">
                            <i class="bi bi-house-door-fill"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'children' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>children/">
                            <i class="bi bi-people-fill"></i> Niños
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'guardians' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>guardians/">
                            <i class="bi bi-person-badge-fill"></i> Responsables
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'attendance' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>attendance/">
                            <i class="bi bi-calendar-check-fill"></i> Asistencia
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'payments' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>payments/">
                            <i class="bi bi-cash-coin"></i> Pagos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'health' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>health/">
                            <i class="bi bi-heart-pulse-fill"></i> Salud
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'accounting' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>accounting/">
                            <i class="bi bi-calculator"></i> Contabilidad
                        </a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>users/">
                            <i class="bi bi-person-gear"></i> Usuarios
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item text-nowrap">
                        <a class="nav-link px-3" href="<?php echo $base_path; ?>logout.php">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-5">
        <div class="row">
            <main class="col-12 px-4">
                <!-- Content will be injected here -->
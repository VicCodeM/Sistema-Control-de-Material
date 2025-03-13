<?php
session_start();
require_once 'db/connection.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
$db = Database::getInstance();
// Get counts for dashboard statistics
$children_count = $db->query('SELECT COUNT(*) as count FROM children WHERE status = "active"')->fetchArray(SQLITE3_ASSOC)['count'];
$staff_count = $db->query('SELECT COUNT(*) as count FROM users WHERE role != "admin"')->fetchArray(SQLITE3_ASSOC)['count'];
$pending_payments = $db->query('SELECT COUNT(*) as count FROM payments WHERE payment_status = "pending"')->fetchArray(SQLITE3_ASSOC)['count'];
// Get today's attendance
$today = date('Y-m-d');
$today_attendance = $db->query(
    'SELECT COUNT(*) as count FROM child_attendance 
    WHERE date(check_in) = :today AND check_out IS NULL',
    [':today' => $today]
)->fetchArray(SQLITE3_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top p-0 shadow">
        <div class="container-fluid">
            <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-1"></i>
            </button>
            <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="dashboard.php">
                <i class="bi bi-building"></i> Guardería
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item text-nowrap">
                    <a class="nav-link px-3" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="bi bi-house-door-fill"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="children/">
                                <i class="bi bi-people-fill"></i> Niños
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="guardians/">
                                <i class="bi bi-person-badge-fill"></i> Responsables
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance/">
                                <i class="bi bi-calendar-check-fill"></i> Asistencia
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payments/">
                                <i class="bi bi-cash-coin"></i> Pagos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="health/">
                                <i class="bi bi-heart-pulse-fill"></i> Salud
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="accounting/">
                                <i class="bi bi-calculator"></i> Contabilidad
                            </a>
                        </li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users/">
                                <i class="bi bi-person-gear"></i> Usuarios
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="page-header d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2"><i class="bi bi-speedometer2"></i> Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Dashboard Cards -->
                <div class="row">
                    <!-- Children Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card bg-white text-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted">Niños Activos</h6>
                                        <h2 class="card-text"><?php echo $children_count; ?></h2>
                                    </div>
                                    <div class="card-icon">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                </div>
                                <a href="children/" class="btn btn-sm btn-outline-primary mt-3">
                                    <i class="bi bi-arrow-right"></i> Ver Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Staff Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card bg-white text-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted">Personal</h6>
                                        <h2 class="card-text"><?php echo $staff_count; ?></h2>
                                    </div>
                                    <div class="card-icon text-success">
                                        <i class="bi bi-person-workspace"></i>
                                    </div>
                                </div>
                                <a href="users/" class="btn btn-sm btn-outline-success mt-3">
                                    <i class="bi bi-arrow-right"></i> Ver Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card bg-white text-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted">Asistencia Hoy</h6>
                                        <h2 class="card-text"><?php echo $today_attendance; ?></h2>
                                    </div>
                                    <div class="card-icon text-info">
                                        <i class="bi bi-calendar-check-fill"></i>
                                    </div>
                                </div>
                                <a href="attendance/" class="btn btn-sm btn-outline-info mt-3">
                                    <i class="bi bi-arrow-right"></i> Ver Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payments Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card bg-white text-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted">Pagos Pendientes</h6>
                                        <h2 class="card-text"><?php echo $pending_payments; ?></h2>
                                    </div>
                                    <div class="card-icon text-warning">
                                        <i class="bi bi-cash-coin"></i>
                                    </div>
                                </div>
                                <a href="payments/" class="btn btn-sm btn-outline-warning mt-3">
                                    <i class="bi bi-arrow-right"></i> Ver Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Access Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-lightning-charge-fill me-2"></i>Acceso Rápido</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-md-3 col-sm-6">
                                        <a href="children/register.php" class="btn btn-lg btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                            <i class="bi bi-person-plus-fill fs-1 mb-2"></i>
                                            <span>Registrar Niño</span>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="attendance/check-in.php" class="btn btn-lg btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                            <i class="bi bi-box-arrow-in-right fs-1 mb-2"></i>
                                            <span>Registrar Entrada</span>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="payments/register.php" class="btn btn-lg btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                            <i class="bi bi-receipt fs-1 mb-2"></i>
                                            <span>Registrar Pago</span>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="guardians/register.php" class="btn btn-lg btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                            <i class="bi bi-person-badge-fill fs-1 mb-2"></i>
                                            <span>Registrar Responsable</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
    <script>
        // Funciones adicionales
    </script>
</body>
</html>
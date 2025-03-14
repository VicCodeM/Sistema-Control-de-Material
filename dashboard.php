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
<?php
$page_title = 'Dashboard';
$current_page = 'dashboard';
$base_path = '';
include 'templates/header.php';
?>
        <main class="main-content">
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
<?php include 'templates/footer.php'; ?>
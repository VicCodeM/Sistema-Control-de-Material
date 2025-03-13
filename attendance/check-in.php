<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$error = '';
$success = '';
$db = Database::getInstance();

// Get list of active children for the dropdown
$children_result = $db->query('SELECT id, first_name, last_name FROM children WHERE status = "active" ORDER BY last_name, first_name');
$children = [];
while ($child = $children_result->fetchArray(SQLITE3_ASSOC)) {
    $children[] = $child;
}

// Process check-in form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id = $_POST['child_id'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    
    if ($child_id) {
        try {
            // Check if child is already checked in today
            $today = date('Y-m-d');
            $check_result = $db->query(
                'SELECT COUNT(*) as count FROM child_attendance 
                WHERE child_id = :child_id AND date(check_in) = :today AND check_out IS NULL',
                [':child_id' => $child_id, ':today' => $today]
            );
            $already_checked_in = $check_result->fetchArray(SQLITE3_ASSOC)['count'] > 0;
            
            if ($already_checked_in) {
                $error = "Este niño ya tiene una entrada registrada hoy sin salida.";
            } else {
                // Register check-in
                $now = date('Y-m-d H:i:s');
                $db->query(
                    'INSERT INTO child_attendance (child_id, check_in, notes) VALUES (:child_id, :check_in, :notes)',
                    [':child_id' => $child_id, ':check_in' => $now, ':notes' => $notes]
                );
                
                // Get child name for success message
                $child_result = $db->query(
                    'SELECT first_name, last_name FROM children WHERE id = :id',
                    [':id' => $child_id]
                );
                $child = $child_result->fetchArray(SQLITE3_ASSOC);
                $child_name = $child['first_name'] . ' ' . $child['last_name'];
                
                $success = "Entrada registrada exitosamente para " . htmlspecialchars($child_name) . " a las " . date('H:i');
            }
        } catch (Exception $e) {
            $error = "Error al registrar la entrada: " . $e->getMessage();
        }
    } else {
        $error = "Por favor seleccione un niño.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Entrada - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top p-0 shadow">
        <div class="container-fluid">
            <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="../dashboard.php">
                <i class="bi bi-building"></i> Guardería
            </a>
            <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-1"></i>
            </button>
            <div class="navbar-nav ms-auto">
                <div class="nav-item text-nowrap">
                    <a class="nav-link px-3" href="../logout.php">
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
                            <a class="nav-link" href="../dashboard.php">
                                <i class="bi bi-house-door-fill"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../children/">
                                <i class="bi bi-people-fill"></i> Niños
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../guardians/">
                                <i class="bi bi-person-badge-fill"></i> Responsables
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="../attendance/">
                                <i class="bi bi-calendar-check-fill"></i> Asistencia
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../payments/">
                                <i class="bi bi-cash-coin"></i> Pagos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../health/">
                                <i class="bi bi-heart-pulse-fill"></i> Salud
                            </a>
                        </li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../accounting/">
                                <i class="bi bi-calculator"></i> Contabilidad
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../users/">
                                <i class="bi bi-person-gear"></i> Usuarios
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="page-header d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2"><i class="bi bi-box-arrow-in-right"></i> Registrar Entrada</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Registrar Entrada</h4>
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="child_id" class="form-label">Niño</label>
                                <select class="form-select" id="child_id" name="child_id" required>
                                    <option value="">Seleccione un niño</option>
                                    <?php foreach ($children as $child): ?>
                                        <option value="<?php echo $child['id']; ?>">
                                            <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notas</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Observaciones o comentarios sobre la entrada"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-box-arrow-in-right"></i> Registrar Entrada
                                </button>
                                <a href="../dashboard.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recently checked in children -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Entradas Recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Niño</th>
                                        <th>Hora de Entrada</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $today = date('Y-m-d');
                                    $recent_checkins = $db->query(
                                        'SELECT ca.id, ca.check_in, ca.check_out, c.id as child_id, c.first_name, c.last_name 
                                        FROM child_attendance ca 
                                        JOIN children c ON ca.child_id = c.id 
                                        WHERE date(ca.check_in) = :today 
                                        ORDER BY ca.check_in DESC',
                                        [':today' => $today]
                                    );
                                    
                                    $has_records = false;
                                    while ($record = $recent_checkins->fetchArray(SQLITE3_ASSOC)) {
                                        $has_records = true;
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) . "</td>";
                                        echo "<td>" . date('H:i', strtotime($record['check_in'])) . "</td>";
                                        echo "<td>";
                                        if ($record['check_out']) {
                                            echo "<span class='badge bg-success'>Salida registrada</span>";
                                        } else {
                                            echo "<span class='badge bg-warning'>En guardería</span>";
                                        }
                                        echo "</td>";
                                        echo "<td>";
                                        if (!$record['check_out']) {
                                            echo "<a href='check-out.php?id=" . $record['id'] . "' class='btn btn-sm btn-primary'>";
                                            echo "<i class='bi bi-box-arrow-right'></i> Registrar Salida";
                                            echo "</a>";
                                        } else {
                                            echo "<span class='text-muted'>Salida a las " . date('H:i', strtotime($record['check_out'])) . "</span>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                    
                                    if (!$has_records) {
                                        echo "<tr><td colspan='4' class='text-center'>No hay entradas registradas hoy</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
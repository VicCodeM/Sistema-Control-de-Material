<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();

// Get attendance records with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Filter by date if provided
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get total count for pagination
$count_result = $db->query(
    'SELECT COUNT(*) as count FROM child_attendance ca 
    WHERE date(ca.check_in) = :date',
    [':date' => $filter_date]
);
$total_count = $count_result->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_count / $per_page);

// Get attendance records for current page
$attendance_result = $db->query(
    'SELECT ca.*, c.first_name, c.last_name 
    FROM child_attendance ca 
    JOIN children c ON ca.child_id = c.id 
    WHERE date(ca.check_in) = :date 
    ORDER BY ca.check_in DESC 
    LIMIT :limit OFFSET :offset',
    [':date' => $filter_date, ':limit' => $per_page, ':offset' => $offset]
);

$attendance_records = [];
while ($record = $attendance_result->fetchArray(SQLITE3_ASSOC)) {
    $attendance_records[] = $record;
}

// Get summary statistics
$stats_result = $db->query(
    'SELECT 
        COUNT(*) as total_checkins,
        SUM(CASE WHEN check_out IS NULL THEN 1 ELSE 0 END) as still_present,
        SUM(CASE WHEN check_out IS NOT NULL THEN 1 ELSE 0 END) as checked_out
    FROM child_attendance
    WHERE date(check_in) = :date',
    [':date' => $filter_date]
);
$stats = $stats_result->fetchArray(SQLITE3_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asistencia - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">Guardería</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Asistencia</h2>
            <div>
                <a href="check-in.php" class="btn btn-success">
                    <i class="bi bi-box-arrow-in-right"></i> Registrar Entrada
                </a>
                <a href="history.php" class="btn btn-info">
                    <i class="bi bi-calendar3"></i> Historial Completo
                </a>
            </div>
        </div>

        <!-- Date filter -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filtrar por Fecha</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="date" class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $filter_date; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Entradas</h5>
                        <p class="card-text display-6"><?php echo $stats['total_checkins']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Presentes Ahora</h5>
                        <p class="card-text display-6"><?php echo $stats['still_present']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Salidas Registradas</h5>
                        <p class="card-text display-6"><?php echo $stats['checked_out']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Records -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Registros de Asistencia - <?php echo date('d/m/Y', strtotime($filter_date)); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Niño</th>
                                <th>Hora de Entrada</th>
                                <th>Hora de Salida</th>
                                <th>Duración</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attendance_records)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay registros de asistencia para esta fecha</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td>
                                            <a href="../children/view.php?id=<?php echo $record['child_id']; ?>">
                                                <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($record['check_in'])); ?></td>
                                        <td>
                                            <?php echo $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($record['check_out']) {
                                                $check_in = new DateTime($record['check_in']);
                                                $check_out = new DateTime($record['check_out']);
                                                $interval = $check_in->diff($check_out);
                                                echo $interval->format('%h h %i m');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($record['check_out']): ?>
                                                <span class="badge bg-success">Completo</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">En guardería</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$record['check_out']): ?>
                                                <a href="check-out.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-box-arrow-right"></i> Registrar Salida
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    <i class="bi bi-check-circle"></i> Completado
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&date=<?php echo $filter_date; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&date=<?php echo $filter_date; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&date=<?php echo $filter_date; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
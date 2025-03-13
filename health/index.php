<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();

// Get health incidents with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Filter parameters
$child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;
$incident_type = isset($_GET['incident_type']) ? $_GET['incident_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build query conditions
$conditions = [];
$params = [];

if ($child_id) {
    $conditions[] = 'hi.child_id = :child_id';
    $params[':child_id'] = $child_id;
}

if ($incident_type) {
    $conditions[] = 'hi.incident_type = :incident_type';
    $params[':incident_type'] = $incident_type;
}

$conditions[] = 'date(hi.incident_date) BETWEEN :start_date AND :end_date';
$params[':start_date'] = $start_date;
$params[':end_date'] = $end_date;

$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$count_query = 'SELECT COUNT(*) as count FROM health_incidents hi ' . $where_clause;
$count_result = $db->query($count_query, $params);
$total_count = $count_result->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_count / $per_page);

// Get health incidents for current page
$query = 'SELECT hi.*, c.first_name, c.last_name, u.name as reporter_name 
          FROM health_incidents hi 
          JOIN children c ON hi.child_id = c.id 
          LEFT JOIN users u ON hi.reported_by = u.id 
          ' . $where_clause . ' 
          ORDER BY hi.incident_date DESC 
          LIMIT :limit OFFSET :offset';

$params[':limit'] = $per_page;
$params[':offset'] = $offset;

$incidents_result = $db->query($query, $params);

$incidents = [];
while ($incident = $incidents_result->fetchArray(SQLITE3_ASSOC)) {
    $incidents[] = $incident;
}

// Get list of children for filter dropdown
$children_result = $db->query('SELECT id, first_name, last_name FROM children WHERE status = "active" ORDER BY last_name, first_name');
$children = [];
while ($child = $children_result->fetchArray(SQLITE3_ASSOC)) {
    $children[] = $child;
}

// Get list of incident types for filter dropdown
$types_result = $db->query('SELECT DISTINCT incident_type FROM health_incidents ORDER BY incident_type');
$incident_types = [];
while ($type = $types_result->fetchArray(SQLITE3_ASSOC)) {
    $incident_types[] = $type['incident_type'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Salud - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">Guardería</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Salud</h2>
            <a href="register.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Registrar Incidente
            </a>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="child_id" class="form-label">Niño</label>
                        <select class="form-select" id="child_id" name="child_id">
                            <option value="">Todos los niños</option>
                            <?php foreach ($children as $child): ?>
                                <option value="<?php echo $child['id']; ?>" <?php echo $child_id == $child['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="incident_type" class="form-label">Tipo de Incidente</label>
                        <select class="form-select" id="incident_type" name="incident_type">
                            <option value="">Todos los tipos</option>
                            <?php foreach ($incident_types as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo $incident_type == $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="start_date" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="end_date" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Health Incidents -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Incidentes de Salud</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Niño</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Acción Tomada</th>
                                <th>Reportado Por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($incidents)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No hay incidentes registrados para los filtros seleccionados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($incidents as $incident): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($incident['incident_date'])); ?></td>
                                        <td>
                                            <a href="../children/view.php?id=<?php echo $incident['child_id']; ?>">
                                                <?php echo htmlspecialchars($incident['first_name'] . ' ' . $incident['last_name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($incident['incident_type']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($incident['description'], 0, 50)) . (strlen($incident['description']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars(substr($incident['action_taken'], 0, 50)) . (strlen($incident['action_taken']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars($incident['reporter_name']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="view.php?id=<?php echo $incident['id']; ?>" class="btn btn-info" title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $incident['id']; ?>" class="btn btn-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&child_id=<?php echo $child_id; ?>&incident_type=<?php echo urlencode($incident_type); ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&child_id=<?php echo $child_id; ?>&incident_type=<?php echo urlencode($incident_type); ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&child_id=<?php echo $child_id; ?>&incident_type=<?php echo urlencode($incident_type); ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" aria-label="Next">
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
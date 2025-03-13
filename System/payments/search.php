<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();

// Initialize search parameters
$search_term = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
$amount_min = isset($_GET['amount_min']) ? (float)$_GET['amount_min'] : 0;
$amount_max = isset($_GET['amount_max']) ? (float)$_GET['amount_max'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'payment_date';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Set date ranges based on selection
$start_date = '';
$end_date = '';

switch ($date_range) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'this_week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'this_month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'last_month':
        $start_date = date('Y-m-01', strtotime('last month'));
        $end_date = date('Y-m-t', strtotime('last month'));
        break;
    case 'this_year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    case 'custom':
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        break;
    default: // 'all'
        $start_date = '2000-01-01'; // A date far in the past
        $end_date = '2099-12-31'; // A date far in the future
}

// Build query conditions
$conditions = [];
$params = [];

// Add date range condition
$conditions[] = 'date(p.payment_date) BETWEEN :start_date AND :end_date';
$params[':start_date'] = $start_date;
$params[':end_date'] = $end_date;

// Add payment status condition if specified
if ($payment_status) {
    $conditions[] = 'p.payment_status = :payment_status';
    $params[':payment_status'] = $payment_status;
}

// Add amount range conditions if specified
if ($amount_min > 0) {
    $conditions[] = 'p.amount >= :amount_min';
    $params[':amount_min'] = $amount_min;
}

if ($amount_max > 0) {
    $conditions[] = 'p.amount <= :amount_max';
    $params[':amount_max'] = $amount_max;
}

// Add search term condition if specified
if ($search_term) {
    $conditions[] = '(c.first_name LIKE :search_term OR c.last_name LIKE :search_term OR p.notes LIKE :search_term)';
    $params[':search_term'] = "%$search_term%";
}

// Build the WHERE clause
$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Build the ORDER BY clause
$allowed_sort_fields = ['payment_date', 'amount', 'child_name', 'payment_status'];
$sort_field = in_array($sort_by, $allowed_sort_fields) ? $sort_by : 'payment_date';

$order_clause = 'ORDER BY ';
switch ($sort_field) {
    case 'child_name':
        $order_clause .= 'c.last_name ' . ($sort_order === 'ASC' ? 'ASC' : 'DESC') . ', c.first_name ' . ($sort_order === 'ASC' ? 'ASC' : 'DESC');
        break;
    default:
        $order_clause .= 'p.' . $sort_field . ' ' . ($sort_order === 'ASC' ? 'ASC' : 'DESC');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$count_query = 'SELECT COUNT(*) as count FROM payments p JOIN children c ON p.child_id = c.id ' . $where_clause;
$count_result = $db->query($count_query, $params);
$total_count = $count_result->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_count / $per_page);

// Get payments for current page
$query = 'SELECT p.*, c.first_name, c.last_name 
          FROM payments p 
          JOIN children c ON p.child_id = c.id 
          ' . $where_clause . ' 
          ' . $order_clause . ' 
          LIMIT :limit OFFSET :offset';

$params[':limit'] = $per_page;
$params[':offset'] = $offset;

$payments_result = $db->query($query, $params);

$payments = [];
while ($payment = $payments_result->fetchArray(SQLITE3_ASSOC)) {
    $payments[] = $payment;
}

// Calculate summary statistics
$stats_query = 'SELECT 
    COUNT(*) as total_payments,
    SUM(amount) as total_amount,
    SUM(CASE WHEN payment_status = "paid" THEN amount ELSE 0 END) as paid_amount,
    SUM(CASE WHEN payment_status = "pending" THEN amount ELSE 0 END) as pending_amount,
    COUNT(CASE WHEN payment_status = "paid" THEN 1 END) as paid_count,
    COUNT(CASE WHEN payment_status = "pending" THEN 1 END) as pending_count
    FROM payments p JOIN children c ON p.child_id = c.id ' . $where_clause;

$stats_result = $db->query($stats_query, $params);
$stats = $stats_result->fetchArray(SQLITE3_ASSOC);

// Get list of children for export options
$children_result = $db->query('SELECT id, first_name, last_name FROM children ORDER BY last_name, first_name');
$children = [];
while ($child = $children_result->fetchArray(SQLITE3_ASSOC)) {
    $children[] = $child;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda Avanzada de Pagos - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .search-highlight {
            background-color: #ffff99;
            font-weight: bold;
        }
        .card-dashboard {
            transition: transform 0.2s;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">Guardería</a>
            <div class="d-flex">
                <a href="index.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-list"></i> Lista de Pagos
                </a>
                <a href="register.php" class="btn btn-outline-light">
                    <i class="bi bi-plus-circle"></i> Nuevo Pago
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h2 class="mb-4">Búsqueda Avanzada de Pagos</h2>
        
        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filtros de Búsqueda</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="search_term" class="form-label">Buscar por Nombre o Notas</label>
                        <input type="text" class="form-control" id="search_term" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Nombre del niño o texto en notas">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="payment_status" class="form-label">Estado del Pago</label>
                        <select class="form-select" id="payment_status" name="payment_status">
                            <option value="">Todos</option>
                            <option value="paid" <?php echo $payment_status == 'paid' ? 'selected' : ''; ?>>Pagados</option>
                            <option value="pending" <?php echo $payment_status == 'pending' ? 'selected' : ''; ?>>Pendientes (Deudores)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="date_range" class="form-label">Periodo</label>
                        <select class="form-select" id="date_range" name="date_range">
                            <option value="all" <?php echo $date_range == 'all' ? 'selected' : ''; ?>>Todo el tiempo</option>
                            <option value="today" <?php echo $date_range == 'today' ? 'selected' : ''; ?>>Hoy</option>
                            <option value="this_week" <?php echo $date_range == 'this_week' ? 'selected' : ''; ?>>Esta semana</option>
                            <option value="this_month" <?php echo $date_range == 'this_month' ? 'selected' : ''; ?>>Este mes</option>
                            <option value="last_month" <?php echo $date_range == 'last_month' ? 'selected' : ''; ?>>Mes anterior</option>
                            <option value="this_year" <?php echo $date_range == 'this_year' ? 'selected' : ''; ?>>Este año</option>
                            <option value="custom" <?php echo $date_range == 'custom' ? 'selected' : ''; ?>>Personalizado</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 custom-date <?php echo $date_range != 'custom' ? 'd-none' : ''; ?>">
                        <label for="start_date" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    
                    <div class="col-md-2 custom-date <?php echo $date_range != 'custom' ? 'd-none' : ''; ?>">
                        <label for="end_date" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="amount_min" class="form-label">Monto Mínimo</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="amount_min" name="amount_min" value="<?php echo $amount_min > 0 ? $amount_min : ''; ?>" placeholder="MX$">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="amount_max" class="form-label">Monto Máximo</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="amount_max" name="amount_max" value="<?php echo $amount_max > 0 ? $amount_max : ''; ?>" placeholder="MX$">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="sort_by" class="form-label">Ordenar por</label>
                        <select class="form-select" id="sort_by" name="sort_by">
                            <option value="payment_date" <?php echo $sort_by == 'payment_date' ? 'selected' : ''; ?>>Fecha de Pago</option>
                            <option value="amount" <?php echo $sort_by == 'amount' ? 'selected' : ''; ?>>Monto</option>
                            <option value="child_name" <?php echo $sort_by == 'child_name' ? 'selected' : ''; ?>>Nombre del Niño</option>
                            <option value="payment_status" <?php echo $sort_by == 'payment_status' ? 'selected' : ''; ?>>Estado del Pago</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="sort_order" class="form-label">Orden</label>
                        <select class="form-select" id="sort_order" name="sort_order">
                            <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descendente</option>
                            <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascendente</option>
                        </select>
                    </div>
                    
                    <div class="col-md-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <a href="search.php" class="btn btn-secondary ms-2">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Results Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-dashboard bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Pagos</h5>
                        <p class="card-text display-6"><?php echo $stats['total_payments'] ?? 0; ?></p>
                        <p class="card-text">MX$ <?php echo number_format($stats['total_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pagados</h5>
                        <p class="card-text display-6"><?php echo $stats['paid_count'] ?? 0; ?></p>
                        <p class="card-text">MX$ <?php echo number_format($stats['paid_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Pendientes (Deudores)</h5>
                        <p class="card-text display-6"><?php echo $stats['pending_count'] ?? 0; ?></p>
                        <p class="card-text">MX$ <?php echo number_format($stats['pending_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Promedio por Pago</h5>
                        <p class="card-text display-6">MX$ <?php echo $stats['total_payments'] > 0 ? number_format(($stats['total_amount'] / $stats['total_payments']), 2) : '0.00'; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Results Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Resultados de la Búsqueda</h5>
                <div>
                    <?php if ($payment_status == 'pending'): ?>
                    <span class="badge bg-warning text-dark">Mostrando Deudores</span>
                    <?php elseif ($payment_status == 'paid'): ?>
                    <span class="badge bg-success">Mostrando Pagados</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($payments)): ?>
                    <div class="alert alert-info">No se encontraron pagos que coincidan con los criterios de búsqueda.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Niño</th>
                                    <th>Fecha de Pago</th>
                                    <th>Periodo</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['id']); ?></td>
                                        <td>
                                            <a href="../children/view.php?id=<?php echo $payment['child_id']; ?>">
                                                <?php 
                                                $name = $payment['first_name'] . ' ' . $payment['last_name'];
                                                if ($search_term && (stripos($payment['first_name'], $search_term) !== false || stripos($payment['last_name'], $search_term) !== false)) {
                                                    $name = preg_replace('/(' . preg_quote($search_term, '/') . ')/i', '<span class="search-highlight">$1</span>', $name);
                                                }
                                                echo $name;
                                                ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($payment['week_start'])); ?> - 
                                            <?php echo date('d/m/Y', strtotime($payment['week_end'])); ?>
                                        </td>
                                        <td>MX$ <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $payment['payment_status'] == 'paid' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                <?php echo $payment['payment_status'] == 'paid' ? 'Pagado' : 'Pendiente'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="view.php?id=<?php echo $payment['id']; ?>" class="btn btn-info" title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $payment['id']; ?>" class="btn btn-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($payment['payment_status'] == 'pending'): ?>
                                                <a href="mark_paid.php?id=<?php echo $payment['id']; ?>" class="btn btn-success" title="Marcar como pagado">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search_term=<?php echo urlencode($search_term); ?>&payment_status=<?php echo $payment_status; ?>&date_range=<?php echo $date_range; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&amount_min=<?php echo $amount_min; ?>&amount_max=<?php echo $amount_max; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search_term=<?php echo urlencode($search_term); ?>&payment_status=<?php echo $payment_status; ?>&date_range=<?php echo $date_range; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&amount_min=<?php echo $amount_min; ?>&amount_max=<?php echo $amount_max; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search_term=<?php echo urlencode($search_term); ?>&payment_status=<?php echo $payment_status; ?>&date_range=<?php echo $date_range; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&amount_min=<?php echo $amount_min; ?>&amount_max=<?php echo $amount_max; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide custom date fields based on date range selection
        document.getElementById('date_range').addEventListener('change', function() {
            const customDateFields = document.querySelectorAll('.custom-date');
            if (this.value === 'custom') {
                customDateFields.forEach(field => field.classList.remove('d-none'));
            } else {
                customDateFields.forEach(field => field.classList.add('d-none'));
            }
        });
    </script>
</body>
</html>
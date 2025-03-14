<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();

// Get payments with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Filter parameters
$child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build query conditions
$conditions = [];
$params = [];

if ($child_id) {
    $conditions[] = 'p.child_id = :child_id';
    $params[':child_id'] = $child_id;
}

if ($payment_status) {
    $conditions[] = 'p.payment_status = :payment_status';
    $params[':payment_status'] = $payment_status;
}

$conditions[] = 'date(p.payment_date) BETWEEN :start_date AND :end_date';
$params[':start_date'] = $start_date;
$params[':end_date'] = $end_date;

$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$count_query = 'SELECT COUNT(*) as count FROM payments p ' . $where_clause;
$count_result = $db->query($count_query, $params);
$total_count = $count_result->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_count / $per_page);

// Get payments for current page
$query = 'SELECT p.*, c.first_name, c.last_name 
          FROM payments p 
          JOIN children c ON p.child_id = c.id 
          ' . $where_clause . ' 
          ORDER BY p.payment_date DESC 
          LIMIT :limit OFFSET :offset';

$params[':limit'] = $per_page;
$params[':offset'] = $offset;

$payments_result = $db->query($query, $params);

$payments = [];
while ($payment = $payments_result->fetchArray(SQLITE3_ASSOC)) {
    $payments[] = $payment;
}

// Get list of children for filter dropdown
$children_result = $db->query('SELECT id, first_name, last_name FROM children WHERE status = "active" ORDER BY last_name, first_name');
$children = [];
while ($child = $children_result->fetchArray(SQLITE3_ASSOC)) {
    $children[] = $child;
}

// Calculate summary statistics
$stats_query = 'SELECT 
    SUM(amount) as total_amount,
    SUM(CASE WHEN payment_status = "paid" THEN amount ELSE 0 END) as paid_amount,
    SUM(CASE WHEN payment_status = "pending" THEN amount ELSE 0 END) as pending_amount
    FROM payments p ' . $where_clause;

// Create a new params array without pagination parameters
$stats_params = array_filter($params, function($key) {
    return $key !== ':limit' && $key !== ':offset';
}, ARRAY_FILTER_USE_KEY);

$stats_result = $db->query($stats_query, $stats_params);
$stats = $stats_result->fetchArray(SQLITE3_ASSOC);
?>

<?php
$page_title = 'Gestión de Pagos';
$current_page = 'payments';
$base_path = '../';
include '../templates/header.php';
?>
        <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Pagos</h2>
            <div>
                <a href="search.php" class="btn btn-info me-2">
                    <i class="bi bi-search"></i> Búsqueda Avanzada
                </a>
                <a href="register.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Registrar Nuevo Pago
                </a>
            </div>
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
                        <label for="payment_status" class="form-label">Estado</label>
                        <select class="form-select" id="payment_status" name="payment_status">
                            <option value="">Todos los estados</option>
                            <option value="paid" <?php echo $payment_status == 'paid' ? 'selected' : ''; ?>>Pagado</option>
                            <option value="pending" <?php echo $payment_status == 'pending' ? 'selected' : ''; ?>>Pendiente</option>
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

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total</h5>
                        <p class="card-text display-6">MX$ <?php echo number_format($stats['total_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pagado</h5>
                        <p class="card-text display-6">MX$ <?php echo number_format($stats['paid_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Pendiente</h5>
                        <p class="card-text display-6">MX$ <?php echo number_format($stats['pending_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Registros de Pagos</h5>
            </div>
            <div class="card-body">
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
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No hay pagos registrados para los filtros seleccionados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['id']); ?></td>
                                        <td>
                                            <a href="../children/view.php?id=<?php echo $payment['child_id']; ?>">
                                                <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&child_id=<?php echo $child_id; ?>&payment_status=<?php echo $payment_status; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&child_id=<?php echo $child_id; ?>&payment_status=<?php echo $payment_status; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&child_id=<?php echo $child_id; ?>&payment_status=<?php echo $payment_status; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </main>
<?php include '../templates/footer.php'; ?>
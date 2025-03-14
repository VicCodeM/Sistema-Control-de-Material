<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Only admin and authorized staff can access accounting
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

$db = Database::getInstance();
$message = '';
$error = '';

// Get expense categories for dropdown
$categories_result = $db->query('SELECT id, name FROM expense_categories ORDER BY name');
$categories = [];
while ($category = $categories_result->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $category;
}

// Handle form submission for adding/editing expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add' || $action === 'edit') {
        $category_id = $_POST['category_id'] ?? null;
        $amount = $_POST['amount'] ?? 0;
        $description = $_POST['description'] ?? '';
        $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
        $payment_method = $_POST['payment_method'] ?? '';
        $receipt_number = $_POST['receipt_number'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        // Validate inputs
        if (!$category_id || !$amount || !$description || !$expense_date || !$payment_method) {
            $error = 'Por favor complete todos los campos requeridos';
        } else {
            if ($action === 'add') {
                // Add new expense
                $result = $db->query(
                    'INSERT INTO expenses (category_id, amount, description, expense_date, payment_method, receipt_number, notes, created_by) 
                    VALUES (:category_id, :amount, :description, :expense_date, :payment_method, :receipt_number, :notes, :created_by)',
                    [
                        ':category_id' => $category_id,
                        ':amount' => $amount,
                        ':description' => $description,
                        ':expense_date' => $expense_date,
                        ':payment_method' => $payment_method,
                        ':receipt_number' => $receipt_number,
                        ':notes' => $notes,
                        ':created_by' => $_SESSION['user_id']
                    ]
                );
                
                if ($result) {
                    $message = 'Gasto registrado correctamente';
                } else {
                    $error = 'Error al registrar el gasto';
                }
            } elseif ($action === 'edit' && isset($_POST['expense_id'])) {
                // Update existing expense
                $expense_id = $_POST['expense_id'];
                
                $result = $db->query(
                    'UPDATE expenses SET 
                    category_id = :category_id, 
                    amount = :amount, 
                    description = :description, 
                    expense_date = :expense_date, 
                    payment_method = :payment_method, 
                    receipt_number = :receipt_number, 
                    notes = :notes 
                    WHERE id = :id',
                    [
                        ':category_id' => $category_id,
                        ':amount' => $amount,
                        ':description' => $description,
                        ':expense_date' => $expense_date,
                        ':payment_method' => $payment_method,
                        ':receipt_number' => $receipt_number,
                        ':notes' => $notes,
                        ':id' => $expense_id
                    ]
                );
                
                if ($result) {
                    $message = 'Gasto actualizado correctamente';
                } else {
                    $error = 'Error al actualizar el gasto';
                }
            }
        }
    } elseif ($action === 'delete' && isset($_POST['expense_id'])) {
        // Delete expense
        $expense_id = $_POST['expense_id'];
        
        $result = $db->query('DELETE FROM expenses WHERE id = :id', [':id' => $expense_id]);
        
        if ($result) {
            $message = 'Gasto eliminado correctamente';
        } else {
            $error = 'Error al eliminar el gasto';
        }
    }
}

// Get expense to edit if requested
$expense_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $expense_id = $_GET['id'];
    $expense_result = $db->query(
        'SELECT * FROM expenses WHERE id = :id',
        [':id' => $expense_id]
    );
    $expense_to_edit = $expense_result->fetchArray(SQLITE3_ASSOC);
}

// Get expenses with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Filter parameters
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build query conditions
$conditions = [];
$params = [];

if ($category_id) {
    $conditions[] = 'e.category_id = :category_id';
    $params[':category_id'] = $category_id;
}

$conditions[] = 'date(e.expense_date) BETWEEN :start_date AND :end_date';
$params[':start_date'] = $start_date;
$params[':end_date'] = $end_date;

$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$count_query = 'SELECT COUNT(*) as count FROM expenses e ' . $where_clause;
$count_result = $db->query($count_query, $params);
$total_count = $count_result->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_count / $per_page);

// Get expenses for current page
$query = 'SELECT e.*, ec.name as category_name 
          FROM expenses e 
          JOIN expense_categories ec ON e.category_id = ec.id 
          ' . $where_clause . ' 
          ORDER BY e.expense_date DESC 
          LIMIT :limit OFFSET :offset';

$params[':limit'] = $per_page;
$params[':offset'] = $offset;

$expenses_result = $db->query($query, $params);

$expenses = [];
while ($expense = $expenses_result->fetchArray(SQLITE3_ASSOC)) {
    $expenses[] = $expense;
}

// Calculate summary statistics
$stats_query = 'SELECT 
    COUNT(*) as total_count,
    SUM(amount) as total_amount,
    AVG(amount) as avg_amount,
    MAX(amount) as max_amount
    FROM expenses e ' . $where_clause;

$stats_result = $db->query($stats_query, $params);
$stats = $stats_result->fetchArray(SQLITE3_ASSOC);
?>

<?php
$current_page = 'accounting';
$base_path = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Gastos - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php
$page_title = 'Gestión de Gastos';
$current_page = 'accounting';
$base_path = '../';
include '../templates/header.php';
?>
        <main class="main-content">
            <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cash-stack"></i> Gestión de Gastos</h2>
            <div>
                <a href="index.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-calculator"></i> Contabilidad
                </a>
                <a href="income.php" class="btn btn-outline-success me-2">
                    <i class="bi bi-cash"></i> Ingresos
                </a>
                <a href="reports.php" class="btn btn-outline-info me-2">
                    <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    <i class="bi bi-plus-circle"></i> Registrar Nuevo Gasto
                </button>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cash-stack"></i> Gestión de Gastos</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="bi bi-plus-circle"></i> Registrar Nuevo Gasto
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filtrar Gastos</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="category_id" class="form-label">Categoría</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Gastos</h5>
                        <p class="card-text display-6"><?php echo $stats['total_count'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Monto Total</h5>
                        <p class="card-text display-6">MX$ <?php echo number_format($stats['total_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Promedio</h5>
                        <p class="card-text display-6">MX$ <?php echo number_format($stats['avg_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Gasto Máximo</h5>
                        <p class="card-text display-6">MX$ <?php echo number_format($stats['max_amount'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista de Gastos</h5>
                <span class="badge bg-primary"><?php echo $total_count; ?> registros</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Categoría</th>
                                <th>Descripción</th>
                                <th>Monto</th>
                                <th>Método de Pago</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No hay gastos registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?php echo $expense['id']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                        <td class="text-danger">MX$ <?php echo number_format($expense['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($expense['payment_method']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="expenses.php?action=edit&id=<?php echo $expense['id']; ?>" class="btn btn-sm btn-outline-primary btn-action">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-action" data-bs-toggle="modal" data-bs-target="#deleteExpenseModal<?php echo $expense['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Delete Confirmation Modal -->
                                            <div class="modal fade" id="deleteExpenseModal<?php echo $expense['id']; ?>" tabindex="-1" aria-labelledby="deleteExpenseModalLabel<?php echo $expense['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteExpenseModalLabel<?php echo $expense['id']; ?>">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Está seguro de que desea eliminar este gasto?
                                                            <p class="mt-2 mb-0"><strong>Descripción:</strong> <?php echo htmlspecialchars($expense['description']); ?></p>
                                                            <p class="mb-0"><strong>Monto:</strong> MX$ <?php echo number_format($expense['amount'], 2); ?></p>
                                                            <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="POST" action="" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                                                <button type="submit" class="btn btn-danger">Eliminar</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
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
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&category_id=<?php echo $category_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Expense Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addExpenseModalLabel">
                        <?php echo isset($expense_to_edit) ? 'Editar Gasto' : 'Registrar Nuevo Gasto'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="expenseForm">
                        <input type="hidden" name="action" value="<?php echo isset($expense_to_edit) ? 'edit' : 'add'; ?>">
                        <?php if (isset($expense_to_edit)): ?>
                            <input type="hidden" name="expense_id" value="<?php echo $expense_to_edit['id']; ?>">
                        <?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo (isset($expense_to_edit) && $expense_to_edit['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="expense_date" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo isset($expense_to_edit) ? $expense_to_edit['expense_date'] : date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Monto (MX$) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?php echo isset($expense_to_edit) ? $expense_to_edit['amount'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">Seleccione un método</option>
                                    <option value="Efectivo" <?php echo (isset($expense_to_edit) && $expense_to_edit['payment_method'] == 'Efectivo') ? 'selected' : ''; ?>>Efectivo</option>
                                    <option value="Tarjeta de Débito" <?php echo (isset($expense_to_edit) && $expense_to_edit['payment_method'] == 'Tarjeta de Débito') ? 'selected' : ''; ?>>Tarjeta de Débito</option>
                                    <option value="Tarjeta de Crédito" <?php echo (isset($expense_to_edit) && $expense_to_edit['payment_method'] == 'Tarjeta de Crédito') ? 'selected' : ''; ?>>Tarjeta de Crédito</option>
                                    <option value="Transferencia" <?php echo (isset($expense_to_edit) && $expense_to_edit['payment_method'] == 'Transferencia') ? 'selected' : ''; ?>>Transferencia</option>
                                    <option value="Cheque" <?php echo (isset($expense_to_edit) && $expense_to_edit['payment_method'] == 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                                    <option value="Otro" <?php echo (isset($expense_to_edit) && $expense_to_edit['payment_method'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="description" name="description" value="<?php echo isset($expense_to_edit) ? htmlspecialchars($expense_to_edit['description']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="receipt_number" class="form-label">Número de Recibo/Factura</label>
                            <input type="text" class="form-control" id="receipt_number" name="receipt_number" value="<?php echo isset($expense_to_edit) ? htmlspecialchars($expense_to_edit['receipt_number']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notas Adicionales</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($expense_to_edit) ? htmlspecialchars($expense_to_edit['notes']) : ''; ?></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="expenseForm" class="btn btn-primary">
                        <?php echo isset($expense_to_edit) ? 'Actualizar' : 'Guardar'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show edit modal if needed
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET['action']) && $_GET['action'] === 'edit'): ?>
                var addExpenseModal = new bootstrap.Modal(document.getElementById('addExpenseModal'));
                addExpenseModal.show();
            <?php endif; ?>
        });
    </script>
</body>
</html>
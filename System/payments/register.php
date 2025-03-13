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

// Check if a specific child is pre-selected
$selected_child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id = $_POST['child_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $week_start = $_POST['week_start'] ?? '';
    $week_end = $_POST['week_end'] ?? '';
    $payment_status = $_POST['payment_status'] ?? 'pending';
    $payment_method = $_POST['payment_method'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if ($child_id && $amount > 0 && $payment_date && $week_start && $week_end && $payment_method) {
        try {
            $db->query(
                'INSERT INTO payments (child_id, amount, payment_date, week_start, week_end, payment_status, payment_method, notes) 
                VALUES (:child_id, :amount, :payment_date, :week_start, :week_end, :payment_status, :payment_method, :notes)',
                [
                    ':child_id' => $child_id,
                    ':amount' => $amount,
                    ':payment_date' => $payment_date,
                    ':week_start' => $week_start,
                    ':week_end' => $week_end,
                    ':payment_status' => $payment_status,
                    ':payment_method' => $payment_method,
                    ':notes' => $notes
                ]
            );
            
            // Get child name for success message
            $child_result = $db->query(
                'SELECT first_name, last_name FROM children WHERE id = :id',
                [':id' => $child_id]
            );
            $child = $child_result->fetchArray(SQLITE3_ASSOC);
            $child_name = $child['first_name'] . ' ' . $child['last_name'];
            
            $success = "Pago registrado exitosamente para " . htmlspecialchars($child_name) . " por un monto de MX$ " . number_format($amount, 2);
            
            // Redirect to view the child's profile after a successful registration
            if (isset($_POST['redirect_to_child']) && $_POST['redirect_to_child'] == 1) {
                header("Location: ../children/view.php?id=$child_id");
                exit();
            }
        } catch (Exception $e) {
            $error = "Error al registrar el pago: " . $e->getMessage();
        }
    } else {
        $error = "Por favor complete todos los campos requeridos correctamente";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pago - Guardería</title>
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
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Registrar Nuevo Pago</h4>
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
                                        <option value="<?php echo $child['id']; ?>" <?php echo $selected_child_id == $child['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="amount" class="form-label">Monto (MX$)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_date" class="form-label">Fecha de Pago</label>
                                    <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="week_start" class="form-label">Inicio del Periodo</label>
                                    <input type="date" class="form-control" id="week_start" name="week_start" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="week_end" class="form-label">Fin del Periodo</label>
                                    <input type="date" class="form-control" id="week_end" name="week_end" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_method" class="form-label">Método de Pago</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="">Seleccione un método</option>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Transferencia">Transferencia Bancaria</option>
                                        <option value="Tarjeta">Tarjeta de Crédito/Débito</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_status" class="form-label">Estado del Pago</label>
                                    <select class="form-select" id="payment_status" name="payment_status" required>
                                        <option value="pending">Pendiente</option>
                                        <option value="paid">Pagado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notas</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>

                            <?php if ($selected_child_id): ?>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="redirect_to_child" name="redirect_to_child" value="1" checked>
                                <label class="form-check-label" for="redirect_to_child">Volver al perfil del niño después de guardar</label>
                            </div>
                            <?php endif; ?>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Registrar Pago
                                </button>
                                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set default dates for period (current month)
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            
            document.getElementById('week_start').value = firstDay.toISOString().split('T')[0];
            document.getElementById('week_end').value = lastDay.toISOString().split('T')[0];
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
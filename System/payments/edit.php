<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();
$error = '';
$success = '';

// Check if payment ID is provided
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$payment_id) {
    header('Location: index.php');
    exit();
}

// Get payment details
$payment_result = $db->query(
    'SELECT p.*, c.first_name, c.last_name FROM payments p JOIN children c ON p.child_id = c.id WHERE p.id = :id',
    [':id' => $payment_id]
);
$payment = $payment_result->fetchArray(SQLITE3_ASSOC);

if (!$payment) {
    header('Location: index.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? 0;
    $payment_date = $_POST['payment_date'] ?? '';
    $week_start = $_POST['week_start'] ?? '';
    $week_end = $_POST['week_end'] ?? '';
    $payment_status = $_POST['payment_status'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if ($amount > 0 && $payment_date && $week_start && $week_end && $payment_method) {
        try {
            $db->query(
                'UPDATE payments SET amount = :amount, payment_date = :payment_date, week_start = :week_start, 
                week_end = :week_end, payment_status = :payment_status, payment_method = :payment_method, 
                notes = :notes WHERE id = :id',
                [
                    ':amount' => $amount,
                    ':payment_date' => $payment_date,
                    ':week_start' => $week_start,
                    ':week_end' => $week_end,
                    ':payment_status' => $payment_status,
                    ':payment_method' => $payment_method,
                    ':notes' => $notes,
                    ':id' => $payment_id
                ]
            );
            
            $success = "Pago actualizado exitosamente para " . htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']);
            
            // Refresh payment data
            $payment_result = $db->query(
                'SELECT p.*, c.first_name, c.last_name FROM payments p JOIN children c ON p.child_id = c.id WHERE p.id = :id',
                [':id' => $payment_id]
            );
            $payment = $payment_result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            $error = "Error al actualizar el pago: " . $e->getMessage();
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
    <title>Editar Pago - Guardería</title>
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
                        <h4 class="mb-0">Editar Pago #<?php echo htmlspecialchars($payment['id']); ?></h4>
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

                        <div class="alert alert-info">
                            <p class="mb-0"><strong>Niño:</strong> <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></p>
                        </div>

                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="amount" class="form-label">Monto (MX$)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" 
                                           value="<?php echo htmlspecialchars($payment['amount']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_date" class="form-label">Fecha de Pago</label>
                                    <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                           value="<?php echo htmlspecialchars($payment['payment_date']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="week_start" class="form-label">Inicio del Periodo</label>
                                    <input type="date" class="form-control" id="week_start" name="week_start" 
                                           value="<?php echo htmlspecialchars($payment['week_start']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="week_end" class="form-label">Fin del Periodo</label>
                                    <input type="date" class="form-control" id="week_end" name="week_end" 
                                           value="<?php echo htmlspecialchars($payment['week_end']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_method" class="form-label">Método de Pago</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="Efectivo" <?php echo $payment['payment_method'] == 'Efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                                        <option value="Transferencia" <?php echo $payment['payment_method'] == 'Transferencia' ? 'selected' : ''; ?>>Transferencia Bancaria</option>
                                        <option value="Tarjeta" <?php echo $payment['payment_method'] == 'Tarjeta' ? 'selected' : ''; ?>>Tarjeta de Crédito/Débito</option>
                                        <option value="Otro" <?php echo $payment['payment_method'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_status" class="form-label">Estado del Pago</label>
                                    <select class="form-select" id="payment_status" name="payment_status" required>
                                        <option value="pending" <?php echo $payment['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="paid" <?php echo $payment['payment_status'] == 'paid' ? 'selected' : ''; ?>>Pagado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notas</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($payment['notes']); ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
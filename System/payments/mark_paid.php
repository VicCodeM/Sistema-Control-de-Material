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

// Check if payment is already paid
if ($payment['payment_status'] === 'paid') {
    header('Location: index.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? $payment['payment_method'];
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? $payment['notes'];
    
    try {
        $db->query(
            'UPDATE payments SET payment_status = :status, payment_method = :method, payment_date = :date, notes = :notes WHERE id = :id',
            [
                ':status' => 'paid',
                ':method' => $payment_method,
                ':date' => $payment_date,
                ':notes' => $notes,
                ':id' => $payment_id
            ]
        );
        
        $success = "Pago marcado como pagado exitosamente para " . htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']);
        
        // Redirect after successful update
        header("Location: index.php?success=" . urlencode($success));
        exit();
    } catch (Exception $e) {
        $error = "Error al actualizar el estado del pago: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcar Pago como Pagado - Guardería</title>
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
                        <h4 class="mb-0">Marcar Pago como Pagado</h4>
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <h5>Detalles del Pago</h5>
                            <p><strong>Niño:</strong> <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></p>
                            <p><strong>Periodo:</strong> <?php echo date('d/m/Y', strtotime($payment['week_start'])); ?> - <?php echo date('d/m/Y', strtotime($payment['week_end'])); ?></p>
                            <p><strong>Monto:</strong> MX$ <?php echo number_format($payment['amount'], 2); ?></p>
                        </div>

                        <form method="POST" action="">
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
                                    <label for="payment_date" class="form-label">Fecha de Pago</label>
                                    <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notas</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($payment['notes']); ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Confirmar Pago
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
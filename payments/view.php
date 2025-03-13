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

// Check if payment ID is provided
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$payment_id) {
    header('Location: index.php');
    exit();
}

// Get payment details with child information
$payment_result = $db->query(
    'SELECT p.*, c.first_name, c.last_name FROM payments p JOIN children c ON p.child_id = c.id WHERE p.id = :id',
    [':id' => $payment_id]
);
$payment = $payment_result->fetchArray(SQLITE3_ASSOC);

if (!$payment) {
    header('Location: index.php');
    exit();
}

// Get guardian information for this child
$guardians_result = $db->query(
    'SELECT g.* FROM guardians g JOIN child_guardian cg ON g.id = cg.guardian_id WHERE cg.child_id = :child_id',
    [':child_id' => $payment['child_id']]
);

$guardians = [];
while ($guardian = $guardians_result->fetchArray(SQLITE3_ASSOC)) {
    $guardians[] = $guardian;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Pago - Guardería</title>
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
                        <h4 class="mb-0">Detalles del Pago #<?php echo htmlspecialchars($payment['id']); ?></h4>
                        <div>
                            <a href="index.php" class="btn btn-secondary btn-sm me-2">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <?php if ($payment['payment_status'] == 'pending'): ?>
                            <a href="mark_paid.php?id=<?php echo $payment['id']; ?>" class="btn btn-success btn-sm">
                                <i class="bi bi-check-circle"></i> Marcar como Pagado
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2">Información del Pago</h5>
                                <p><strong>Estado:</strong> 
                                    <span class="badge <?php echo $payment['payment_status'] == 'paid' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                        <?php echo $payment['payment_status'] == 'paid' ? 'Pagado' : 'Pendiente'; ?>
                                    </span>
                                </p>
                                <p><strong>Monto:</strong> MX$ <?php echo number_format($payment['amount'], 2); ?></p>
                                <p><strong>Fecha de Pago:</strong> <?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></p>
                                <p><strong>Método de Pago:</strong> <?php echo htmlspecialchars($payment['payment_method']); ?></p>
                                <p><strong>Periodo:</strong> <?php echo date('d/m/Y', strtotime($payment['week_start'])); ?> - <?php echo date('d/m/Y', strtotime($payment['week_end'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2">Información del Niño</h5>
                                <p><strong>Nombre:</strong> 
                                    <a href="../children/view.php?id=<?php echo $payment['child_id']; ?>">
                                        <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                    </a>
                                </p>
                                <?php if (!empty($guardians)): ?>
                                <p><strong>Tutores:</strong></p>
                                <ul>
                                    <?php foreach ($guardians as $guardian): ?>
                                    <li>
                                        <a href="../guardians/view.php?id=<?php echo $guardian['id']; ?>">
                                            <?php echo htmlspecialchars($guardian['first_name'] . ' ' . $guardian['last_name']); ?>
                                        </a>
                                        (<?php echo htmlspecialchars($guardian['relationship']); ?>)
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php else: ?>
                                <p><em>No hay tutores registrados para este niño.</em></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($payment['notes'])): ?>
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Notas</h5>
                            <p><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="edit.php?id=<?php echo $payment['id']; ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Editar Pago
                            </a>
                            <a href="index.php" class="btn btn-secondary">Volver a la Lista</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
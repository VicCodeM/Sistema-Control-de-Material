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

// Get attendance record ID from URL
$attendance_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$attendance_id) {
    header('Location: index.php');
    exit();
}

// Get attendance record details
$attendance_result = $db->query(
    'SELECT ca.*, c.first_name, c.last_name 
    FROM child_attendance ca 
    JOIN children c ON ca.child_id = c.id 
    WHERE ca.id = :id',
    [':id' => $attendance_id]
);
$attendance = $attendance_result->fetchArray(SQLITE3_ASSOC);

if (!$attendance) {
    header('Location: index.php');
    exit();
}

// Check if already checked out
if ($attendance['check_out']) {
    $error = "Este registro ya tiene una salida registrada.";
}

// Process check-out form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$attendance['check_out']) {
    $notes = $_POST['notes'] ?? '';
    
    try {
        // Register check-out
        $now = date('Y-m-d H:i:s');
        $db->query(
            'UPDATE child_attendance SET check_out = :check_out, notes = CASE WHEN notes IS NULL OR notes = "" THEN :notes ELSE notes || "\n" || :notes END WHERE id = :id',
            [':check_out' => $now, ':notes' => $notes, ':id' => $attendance_id]
        );
        
        $child_name = $attendance['first_name'] . ' ' . $attendance['last_name'];
        $success = "Salida registrada exitosamente para " . htmlspecialchars($child_name) . " a las " . date('H:i');
        
        // Refresh attendance data
        $attendance_result = $db->query(
            'SELECT ca.*, c.first_name, c.last_name 
            FROM child_attendance ca 
            JOIN children c ON ca.child_id = c.id 
            WHERE ca.id = :id',
            [':id' => $attendance_id]
        );
        $attendance = $attendance_result->fetchArray(SQLITE3_ASSOC);
    } catch (Exception $e) {
        $error = "Error al registrar la salida: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Salida - Guardería</title>
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
                        <h4 class="mb-0">Registrar Salida</h4>
                        <a href="check-in.php" class="btn btn-secondary btn-sm">
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

                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Información del Registro</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th style="width: 30%">Niño:</th>
                                        <td><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Hora de Entrada:</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($attendance['check_in'])); ?></td>
                                    </tr>
                                    <?php if ($attendance['check_out']): ?>
                                    <tr>
                                        <th>Hora de Salida:</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($attendance['check_out'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Duración:</th>
                                        <td>
                                            <?php 
                                            $check_in = new DateTime($attendance['check_in']);
                                            $check_out = new DateTime($attendance['check_out']);
                                            $interval = $check_in->diff($check_out);
                                            echo $interval->format('%h horas, %i minutos');
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($attendance['notes'])): ?>
                                    <tr>
                                        <th>Notas:</th>
                                        <td><?php echo nl2br(htmlspecialchars($attendance['notes'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <?php if (!$attendance['check_out']): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notas de Salida</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Observaciones o comentarios sobre la salida"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-right"></i> Registrar Salida
                                </button>
                                <a href="check-in.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="d-grid gap-2">
                            <a href="check-in.php" class="btn btn-primary">Volver a Entradas</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
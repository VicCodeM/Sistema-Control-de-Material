<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();
$incident_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$incident_id) {
    header('Location: index.php');
    exit();
}

// Get incident details
$incident_result = $db->query(
    'SELECT hi.*, c.first_name, c.last_name, u.name as reporter_name 
    FROM health_incidents hi 
    JOIN children c ON hi.child_id = c.id 
    LEFT JOIN users u ON hi.reported_by = u.id 
    WHERE hi.id = :id',
    [':id' => $incident_id]
);
$incident = $incident_result->fetchArray(SQLITE3_ASSOC);

if (!$incident) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Incidente - Guardería</title>
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
            <h2>Detalles del Incidente de Salud</h2>
            <div>
                <a href="edit.php?id=<?php echo $incident_id; ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Información del Incidente</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th style="width: 30%">Niño:</th>
                                <td>
                                    <a href="../children/view.php?id=<?php echo $incident['child_id']; ?>">
                                        <?php echo htmlspecialchars($incident['first_name'] . ' ' . $incident['last_name']); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th>Fecha y Hora:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($incident['incident_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Tipo de Incidente:</th>
                                <td><?php echo htmlspecialchars($incident['incident_type']); ?></td>
                            </tr>
                            <tr>
                                <th>Reportado Por:</th>
                                <td><?php echo htmlspecialchars($incident['reporter_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha de Registro:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($incident['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Descripción del Incidente</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($incident['description'])); ?></p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Acción Tomada</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($incident['action_taken'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Acciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../children/view.php?id=<?php echo $incident['child_id']; ?>" class="btn btn-primary">
                                <i class="bi bi-person"></i> Ver Perfil del Niño
                            </a>
                            <a href="register.php?child_id=<?php echo $incident['child_id']; ?>" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> Registrar Nuevo Incidente
                            </a>
                            <a href="edit.php?id=<?php echo $incident_id; ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Editar Este Incidente
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Otros Incidentes Recientes</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recent_incidents = $db->query(
                            'SELECT id, incident_date, incident_type 
                            FROM health_incidents 
                            WHERE child_id = :child_id AND id != :current_id 
                            ORDER BY incident_date DESC LIMIT 5',
                            [':child_id' => $incident['child_id'], ':current_id' => $incident_id]
                        );
                        
                        $has_incidents = false;
                        while ($recent = $recent_incidents->fetchArray(SQLITE3_ASSOC)) {
                            $has_incidents = true;
                            echo "<div class='mb-2'>";
                            echo "<a href='view.php?id=" . $recent['id'] . "'>";
                            echo date('d/m/Y', strtotime($recent['incident_date'])) . " - " . htmlspecialchars($recent['incident_type']);
                            echo "</a>";
                            echo "</div>";
                        }
                        
                        if (!$has_incidents) {
                            echo "<p class='text-center'>No hay otros incidentes registrados</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
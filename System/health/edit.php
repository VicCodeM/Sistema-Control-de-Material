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
$incident_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$incident_id) {
    header('Location: index.php');
    exit();
}

// Get incident details
$incident_result = $db->query(
    'SELECT * FROM health_incidents WHERE id = :id',
    [':id' => $incident_id]
);
$incident = $incident_result->fetchArray(SQLITE3_ASSOC);

if (!$incident) {
    header('Location: index.php');
    exit();
}

// Get child details
$child_result = $db->query(
    'SELECT first_name, last_name FROM children WHERE id = :id',
    [':id' => $incident['child_id']]
);
$child = $child_result->fetchArray(SQLITE3_ASSOC);

// Get list of active children for the dropdown
$children_result = $db->query('SELECT id, first_name, last_name FROM children WHERE status = "active" ORDER BY last_name, first_name');
$children = [];
while ($child_row = $children_result->fetchArray(SQLITE3_ASSOC)) {
    $children[] = $child_row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id = $_POST['child_id'] ?? 0;
    $incident_date = $_POST['incident_date'] ?? '';
    $incident_type = $_POST['incident_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $action_taken = $_POST['action_taken'] ?? '';
    
    if ($child_id && $incident_date && $incident_type && $description && $action_taken) {
        try {
            $db->query(
                'UPDATE health_incidents SET 
                child_id = :child_id, 
                incident_date = :incident_date, 
                incident_type = :incident_type, 
                description = :description, 
                action_taken = :action_taken 
                WHERE id = :id',
                [
                    ':child_id' => $child_id,
                    ':incident_date' => $incident_date,
                    ':incident_type' => $incident_type,
                    ':description' => $description,
                    ':action_taken' => $action_taken,
                    ':id' => $incident_id
                ]
            );
            $success = 'Incidente actualizado exitosamente';
            
            // Refresh incident data
            $incident_result = $db->query(
                'SELECT * FROM health_incidents WHERE id = :id',
                [':id' => $incident_id]
            );
            $incident = $incident_result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            $error = 'Error al actualizar el incidente: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor complete todos los campos requeridos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Incidente de Salud - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="../assets/js/sidebar.js" defer></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top p-0 shadow">
        <div class="container-fluid">
            <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="../dashboard.php">
                <i class="bi bi-building"></i> Guardería
            </a>
            <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-1"></i>
            </button>
            <div class="navbar-nav ms-auto">
                <div class="nav-item text-nowrap">
                    <a class="nav-link px-3" href="../logout.php">
                        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../dashboard.php">
                                <i class="bi bi-house-door-fill"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../children/">
                                <i class="bi bi-people-fill"></i> Niños
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../guardians/">
                                <i class="bi bi-person-badge-fill"></i> Responsables
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../attendance/">
                                <i class="bi bi-calendar-check-fill"></i> Asistencia
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../payments/">
                                <i class="bi bi-cash-coin"></i> Pagos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="../health/">
                                <i class="bi bi-heart-pulse-fill"></i> Salud
                            </a>
                        </li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../users/">
                                <i class="bi bi-person-gear"></i> Usuarios
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="page-header d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2"><i class="bi bi-pencil-square"></i> Editar Incidente de Salud</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="view.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger fade-in">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success fade-in">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="child_id" class="form-label">Niño</label>
                                <select class="form-select" id="child_id" name="child_id" required>
                                    <option value="">Seleccione un niño</option>
                                    <?php foreach ($children as $child_option): ?>
                                        <option value="<?php echo $child_option['id']; ?>" <?php echo $incident['child_id'] == $child_option['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($child_option['first_name'] . ' ' . $child_option['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="incident_date" class="form-label">Fecha y Hora del Incidente</label>
                                    <input type="datetime-local" class="form-control" id="incident_date" name="incident_date" 
                                           value="<?php echo date('Y-m-d\TH:i', strtotime($incident['incident_date'])); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="incident_type" class="form-label">Tipo de Incidente</label>
                                    <select class="form-select" id="incident_type" name="incident_type" required>
                                        <option value="">Seleccione un tipo</option>
                                        <option value="Fiebre" <?php echo $incident['incident_type'] == 'Fiebre' ? 'selected' : ''; ?>>Fiebre</option>
                                        <option value="Caída" <?php echo $incident['incident_type'] == 'Caída' ? 'selected' : ''; ?>>Caída</option>
                                        <option value="Golpe" <?php echo $incident['incident_type'] == 'Golpe' ? 'selected' : ''; ?>>Golpe</option>
                                        <option value="Alergia" <?php echo $incident['incident_type'] == 'Alergia' ? 'selected' : ''; ?>>Reacción Alérgica</option>
                                        <option value="Vómito" <?php echo $incident['incident_type'] == 'Vómito' ? 'selected' : ''; ?>>Vómito</option>
                                        <option value="Diarrea" <?php echo $incident['incident_type'] == 'Diarrea' ? 'selected' : ''; ?>>Diarrea</option>
                                        <option value="Tos" <?php echo $incident['incident_type'] == 'Tos' ? 'selected' : ''; ?>>Tos</option>
                                        <option value="Resfriado" <?php echo $incident['incident_type'] == 'Resfriado' ? 'selected' : ''; ?>>Resfriado</option>
                                        <option value="Otro" <?php echo $incident['incident_type'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción del Incidente</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($incident['description']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="action_taken" class="form-label">Acción Tomada</label>
                                <textarea class="form-control" id="action_taken" name="action_taken" rows="3" required><?php echo htmlspecialchars($incident['action_taken']); ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                                <a href="view.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
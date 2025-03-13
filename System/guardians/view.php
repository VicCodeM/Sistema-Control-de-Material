<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();
$guardian_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$guardian_id) {
    header('Location: index.php');
    exit();
}

// Get guardian details
$guardian_result = $db->query(
    'SELECT * FROM guardians WHERE id = :id',
    [':id' => $guardian_id]
);
$guardian = $guardian_result->fetchArray(SQLITE3_ASSOC);

if (!$guardian) {
    header('Location: index.php');
    exit();
}

// Get children associated with this guardian
$children_result = $db->query(
    'SELECT c.* FROM children c 
    JOIN child_guardian cg ON c.id = cg.child_id 
    WHERE cg.guardian_id = :guardian_id 
    ORDER BY c.last_name, c.first_name',
    [':guardian_id' => $guardian_id]
);

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
    <title>Detalles del Responsable - Guardería</title>
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
            <h2>Detalles del Responsable</h2>
            <div>
                <a href="edit.php?id=<?php echo $guardian_id; ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Información Personal</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th style="width: 40%">Nombre Completo:</th>
                                <td><?php echo htmlspecialchars($guardian['first_name'] . ' ' . $guardian['last_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Relación:</th>
                                <td><?php echo htmlspecialchars($guardian['relationship']); ?></td>
                            </tr>
                            <tr>
                                <th>Teléfono:</th>
                                <td><?php echo htmlspecialchars($guardian['phone']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo !empty($guardian['email']) ? htmlspecialchars($guardian['email']) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th>Dirección:</th>
                                <td><?php echo htmlspecialchars($guardian['address']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Niños Asociados</h5>
                        <a href="assign.php?guardian_id=<?php echo $guardian_id; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Asignar Niño
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($children)): ?>
                            <p class="text-center">No hay niños asignados a este responsable</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($children as $child): ?>
                                    <a href="../children/view.php?id=<?php echo $child['id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></h6>
                                            <span class="badge <?php echo $child['status'] == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $child['status'] == 'active' ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </div>
                                        <p class="mb-1">Fecha de nacimiento: <?php echo date('d/m/Y', strtotime($child['birth_date'])); ?></p>
                                    </a>
                                <?php endforeach; ?>
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
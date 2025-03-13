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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $relationship = $_POST['relationship'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';

    if ($first_name && $last_name && $relationship && $phone && $address) {
        try {
            $db->query(
                'UPDATE guardians SET 
                first_name = :first_name, 
                last_name = :last_name, 
                relationship = :relationship, 
                phone = :phone, 
                email = :email, 
                address = :address 
                WHERE id = :id',
                [
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':relationship' => $relationship,
                    ':phone' => $phone,
                    ':email' => $email,
                    ':address' => $address,
                    ':id' => $guardian_id
                ]
            );
            $success = 'Información del responsable actualizada exitosamente';
            
            // Refresh guardian data
            $guardian_result = $db->query(
                'SELECT * FROM guardians WHERE id = :id',
                [':id' => $guardian_id]
            );
            $guardian = $guardian_result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            $error = 'Error al actualizar la información: ' . $e->getMessage();
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
    <title>Editar Responsable - Guardería</title>
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
                        <h4 class="mb-0">Editar Información del Responsable</h4>
                        <a href="view.php?id=<?php echo $guardian_id; ?>" class="btn btn-secondary btn-sm">
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
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($guardian['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($guardian['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="relationship" class="form-label">Relación</label>
                                    <select class="form-select" id="relationship" name="relationship" required>
                                        <option value="" disabled>Seleccione una relación</option>
                                        <option value="Padre" <?php echo $guardian['relationship'] == 'Padre' ? 'selected' : ''; ?>>Padre</option>
                                        <option value="Madre" <?php echo $guardian['relationship'] == 'Madre' ? 'selected' : ''; ?>>Madre</option>
                                        <option value="Abuelo" <?php echo $guardian['relationship'] == 'Abuelo' ? 'selected' : ''; ?>>Abuelo</option>
                                        <option value="Abuela" <?php echo $guardian['relationship'] == 'Abuela' ? 'selected' : ''; ?>>Abuela</option>
                                        <option value="Tío" <?php echo $guardian['relationship'] == 'Tío' ? 'selected' : ''; ?>>Tío</option>
                                        <option value="Tía" <?php echo $guardian['relationship'] == 'Tía' ? 'selected' : ''; ?>>Tía</option>
                                        <option value="Tutor Legal" <?php echo $guardian['relationship'] == 'Tutor Legal' ? 'selected' : ''; ?>>Tutor Legal</option>
                                        <option value="Otro" <?php echo $guardian['relationship'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($guardian['phone']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($guardian['email'] ?? ''); ?>">
                                <div class="form-text">Opcional</div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Dirección</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($guardian['address']); ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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
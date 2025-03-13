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

// Get list of children for the dropdown
$children_result = $db->query('SELECT id, first_name, last_name FROM children WHERE status = "active" ORDER BY last_name, first_name');
$children = [];
while ($child = $children_result->fetchArray(SQLITE3_ASSOC)) {
    $children[] = $child;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $relationship = $_POST['relationship'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $child_id = $_POST['child_id'] ?? '';
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;

    if ($first_name && $last_name && $relationship && $phone && $address && $child_id) {
        try {
            $db->query(
                'INSERT INTO guardians (first_name, last_name, relationship, phone, email, address) 
                VALUES (:first_name, :last_name, :relationship, :phone, :email, :address)',
                [
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':relationship' => $relationship,
                    ':phone' => $phone,
                    ':email' => $email,
                    ':address' => $address
                ]
            );
            
            $guardian_id = $db->lastInsertRowID();
            
            // Create child-guardian relationship
            $db->query(
                'INSERT INTO child_guardian (child_id, guardian_id, is_primary) 
                VALUES (:child_id, :guardian_id, :is_primary)',
                [
                    ':child_id' => $child_id,
                    ':guardian_id' => $guardian_id,
                    ':is_primary' => $is_primary
                ]
            );
            
            $success = 'Responsable registrado exitosamente';
        } catch (Exception $e) {
            $error = 'Error al registrar el responsable: ' . $e->getMessage();
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
    <title>Registrar Responsable - Guardería</title>
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
                    <div class="card-header">
                        <h4 class="mb-0">Registrar Nuevo Responsable</h4>
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
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="relationship" class="form-label">Parentesco</label>
                                    <select class="form-select" id="relationship" name="relationship" required>
                                        <option value="">Seleccione...</option>
                                        <option value="Madre">Madre</option>
                                        <option value="Padre">Padre</option>
                                        <option value="Abuelo/a">Abuelo/a</option>
                                        <option value="Tío/a">Tío/a</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Dirección</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="child_id" class="form-label">Niño</label>
                                <select class="form-select" id="child_id" name="child_id" required>
                                    <option value="">Seleccione un niño...</option>
                                    <?php foreach ($children as $child): ?>
                                        <option value="<?php echo $child['id']; ?>">
                                            <?php echo htmlspecialchars($child['last_name'] . ', ' . $child['first_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_primary" name="is_primary">
                                <label class="form-check-label" for="is_primary">Responsable Principal</label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Registrar Responsable</button>
                                <a href="../dashboard.php" class="btn btn-secondary">Cancelar</a>
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
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
$child_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$child_id) {
    header('Location: index.php');
    exit();
}

// Get child details
$child_result = $db->query(
    'SELECT * FROM children WHERE id = :id',
    [':id' => $child_id]
);
$child = $child_result->fetchArray(SQLITE3_ASSOC);

if (!$child) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $enrollment_date = $_POST['enrollment_date'] ?? '';
    $allergies = $_POST['allergies'] ?? '';
    $special_notes = $_POST['special_notes'] ?? '';
    $status = $_POST['status'] ?? 'active';

    if ($first_name && $last_name && $birth_date && $enrollment_date) {
        try {
            $db->query(
                'UPDATE children SET 
                first_name = :first_name, 
                last_name = :last_name, 
                birth_date = :birth_date, 
                enrollment_date = :enrollment_date, 
                allergies = :allergies, 
                special_notes = :special_notes, 
                status = :status 
                WHERE id = :id',
                [
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':birth_date' => $birth_date,
                    ':enrollment_date' => $enrollment_date,
                    ':allergies' => $allergies,
                    ':special_notes' => $special_notes,
                    ':status' => $status,
                    ':id' => $child_id
                ]
            );
            $success = 'Información del niño actualizada exitosamente';
            
            // Refresh child data
            $child_result = $db->query(
                'SELECT * FROM children WHERE id = :id',
                [':id' => $child_id]
            );
            $child = $child_result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            $error = 'Error al actualizar la información: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor complete todos los campos requeridos';
    }
}
?>

<?php
$page_title = 'Editar Niño';
$current_page = 'children';
$base_path = '../';
include '../templates/header.php';
?>
        <main class="main-content">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Editar Información del Niño</h4>
                        <a href="view.php?id=<?php echo $child_id; ?>" class="btn btn-secondary btn-sm">
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
                                           value="<?php echo htmlspecialchars($child['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($child['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="birth_date" class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                           value="<?php echo $child['birth_date']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="enrollment_date" class="form-label">Fecha de Inscripción</label>
                                    <input type="date" class="form-control" id="enrollment_date" name="enrollment_date" 
                                           value="<?php echo $child['enrollment_date']; ?>" required>
                                </div>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Estado</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo $child['status'] == 'active' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactive" <?php echo $child['status'] == 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="allergies" class="form-label">Alergias</label>
                                    <textarea class="form-control" id="allergies" name="allergies" rows="2"><?php echo htmlspecialchars($child['allergies']); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="special_notes" class="form-label">Notas Especiales</label>
                                    <textarea class="form-control" id="special_notes" name="special_notes" rows="3"><?php echo htmlspecialchars($child['special_notes']); ?></textarea>
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
        </main>
<?php include '../templates/footer.php'; ?>
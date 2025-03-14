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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $enrollment_date = $_POST['enrollment_date'] ?? date('Y-m-d');
    $allergies = $_POST['allergies'] ?? '';
    $special_notes = $_POST['special_notes'] ?? '';

    if ($first_name && $last_name && $birth_date) {
        try {
            $db->query(
                'INSERT INTO children (first_name, last_name, birth_date, enrollment_date, allergies, special_notes) 
                VALUES (:first_name, :last_name, :birth_date, :enrollment_date, :allergies, :special_notes)',
                [
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':birth_date' => $birth_date,
                    ':enrollment_date' => $enrollment_date,
                    ':allergies' => $allergies,
                    ':special_notes' => $special_notes
                ]
            );
            $success = 'Niño registrado exitosamente';
        } catch (Exception $e) {
            $error = 'Error al registrar el niño: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor complete todos los campos requeridos';
    }
}
?>

<?php
$page_title = 'Registrar Niño';
$current_page = 'children';
$base_path = '../';
include '../templates/header.php';
?>
        <main class="main-content">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Registrar Nuevo Niño</h4>
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
                                    <label for="birth_date" class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="enrollment_date" class="form-label">Fecha de Inscripción</label>
                                    <input type="date" class="form-control" id="enrollment_date" name="enrollment_date" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="allergies" class="form-label">Alergias</label>
                                <textarea class="form-control" id="allergies" name="allergies" rows="2"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="special_notes" class="form-label">Notas Especiales</label>
                                <textarea class="form-control" id="special_notes" name="special_notes" rows="3"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Registrar Niño</button>
                                <a href="../dashboard.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        </main>
<?php include '../templates/footer.php'; ?>
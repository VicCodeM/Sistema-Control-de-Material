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

// Check if we're assigning to a specific guardian or child
$guardian_id = isset($_GET['guardian_id']) ? (int)$_GET['guardian_id'] : 0;
$child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;

// Get list of children for the dropdown
$children_result = $db->query('SELECT id, first_name, last_name FROM children WHERE status = "active" ORDER BY last_name, first_name');
$children = [];
while ($child = $children_result->fetchArray(SQLITE3_ASSOC)) {
    $children[] = $child;
}

// Get list of guardians for the dropdown
$guardians_result = $db->query('SELECT id, first_name, last_name, relationship FROM guardians ORDER BY last_name, first_name');
$guardians = [];
while ($guardian = $guardians_result->fetchArray(SQLITE3_ASSOC)) {
    $guardians[] = $guardian;
}

// If we have a specific guardian or child, get their details
$selected_guardian = null;
$selected_child = null;

if ($guardian_id) {
    $guardian_result = $db->query(
        'SELECT * FROM guardians WHERE id = :id',
        [':id' => $guardian_id]
    );
    $selected_guardian = $guardian_result->fetchArray(SQLITE3_ASSOC);
    
    if (!$selected_guardian) {
        header('Location: index.php');
        exit();
    }
}

if ($child_id) {
    $child_result = $db->query(
        'SELECT * FROM children WHERE id = :id',
        [':id' => $child_id]
    );
    $selected_child = $child_result->fetchArray(SQLITE3_ASSOC);
    
    if (!$selected_child) {
        header('Location: ../children/index.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_guardian_id = $_POST['guardian_id'] ?? 0;
    $post_child_id = $_POST['child_id'] ?? 0;
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    
    if ($post_guardian_id && $post_child_id) {
        try {
            // Check if relationship already exists
            $check_result = $db->query(
                'SELECT COUNT(*) as count FROM child_guardian WHERE child_id = :child_id AND guardian_id = :guardian_id',
                [':child_id' => $post_child_id, ':guardian_id' => $post_guardian_id]
            );
            $exists = $check_result->fetchArray(SQLITE3_ASSOC)['count'] > 0;
            
            if ($exists) {
                $error = "Esta relación ya existe.";
            } else {
                // If this is a primary guardian, update any existing primary guardians for this child
                if ($is_primary) {
                    $db->query(
                        'UPDATE child_guardian SET is_primary = 0 WHERE child_id = :child_id AND is_primary = 1',
                        [':child_id' => $post_child_id]
                    );
                }
                
                // Insert the new relationship
                $db->query(
                    'INSERT INTO child_guardian (child_id, guardian_id, is_primary) VALUES (:child_id, :guardian_id, :is_primary)',
                    [
                        ':child_id' => $post_child_id,
                        ':guardian_id' => $post_guardian_id,
                        ':is_primary' => $is_primary
                    ]
                );
                
                $success = "Relación establecida exitosamente.";
                
                // Redirect based on context
                if ($guardian_id) {
                    header("Location: view.php?id=$guardian_id");
                    exit();
                } elseif ($child_id) {
                    header("Location: ../children/view.php?id=$child_id");
                    exit();
                }
            }
        } catch (Exception $e) {
            $error = "Error al establecer la relación: " . $e->getMessage();
        }
    } else {
        $error = "Por favor seleccione un niño y un responsable.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Responsable - Guardería</title>
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
                        <h4 class="mb-0">
                            <?php if ($selected_guardian): ?>
                                Asignar Niño a <?php echo htmlspecialchars($selected_guardian['first_name'] . ' ' . $selected_guardian['last_name']); ?>
                            <?php elseif ($selected_child): ?>
                                Asignar Responsable a <?php echo htmlspecialchars($selected_child['first_name'] . ' ' . $selected_child['last_name']); ?>
                            <?php else: ?>
                                Asignar Responsable a Niño
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="guardian_id" class="form-label">Responsable</label>
                                <select class="form-select" id="guardian_id" name="guardian_id" <?php echo $selected_guardian ? 'disabled' : 'required'; ?>>
                                    <option value="">Seleccione un responsable</option>
                                    <?php foreach ($guardians as $guardian): ?>
                                        <option value="<?php echo $guardian['id']; ?>" <?php echo ($selected_guardian && $selected_guardian['id'] == $guardian['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($guardian['first_name'] . ' ' . $guardian['last_name'] . ' (' . $guardian['relationship'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($selected_guardian): ?>
                                    <input type="hidden" name="guardian_id" value="<?php echo $selected_guardian['id']; ?>">
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="child_id" class="form-label">Niño</label>
                                <select class="form-select" id="child_id" name="child_id" <?php echo $selected_child ? 'disabled' : 'required'; ?>>
                                    <option value="">Seleccione un niño</option>
                                    <?php foreach ($children as $child): ?>
                                        <option value="<?php echo $child['id']; ?>" <?php echo ($selected_child && $selected_child['id'] == $child['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($selected_child): ?>
                                    <input type="hidden" name="child_id" value="<?php echo $selected_child['id']; ?>">
                                <?php endif; ?>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_primary" name="is_primary">
                                <label class="form-check-label" for="is_primary">Responsable Principal</label>
                                <div class="form-text">Marque esta opción si este es el responsable principal del niño.</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Asignar</button>
                                <?php if ($selected_guardian): ?>
                                    <a href="view.php?id=<?php echo $selected_guardian['id']; ?>" class="btn btn-secondary">Cancelar</a>
                                <?php elseif ($selected_child): ?>
                                    <a href="../children/view.php?id=<?php echo $selected_child['id']; ?>" class="btn btn-secondary">Cancelar</a>
                                <?php else: ?>
                                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                                <?php endif; ?>
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
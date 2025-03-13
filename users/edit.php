<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();
$error = '';
$success = '';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    header('Location: index.php');
    exit();
}

// Get user details
$user_result = $db->query(
    'SELECT * FROM users WHERE id = :id',
    [':id' => $user_id]
);
$user = $user_result->fetchArray(SQLITE3_ASSOC);

if (!$user) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $change_password = isset($_POST['change_password']) && $_POST['change_password'] == '1';
    
    if ($name && $role) {
        try {
            if ($change_password) {
                if (empty($password)) {
                    $error = "Por favor ingrese una nueva contraseña.";
                } elseif ($password !== $confirm_password) {
                    $error = "Las contraseñas no coinciden.";
                } else {
                    // Hash new password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Update user with new password
                    $db->query(
                        'UPDATE users SET name = :name, role = :role, password = :password WHERE id = :id',
                        [
                            ':name' => $name,
                            ':role' => $role,
                            ':password' => $hashed_password,
                            ':id' => $user_id
                        ]
                    );
                    $success = "Usuario actualizado exitosamente con nueva contraseña.";
                }
            } else {
                // Update user without changing password
                $db->query(
                    'UPDATE users SET name = :name, role = :role WHERE id = :id',
                    [
                        ':name' => $name,
                        ':role' => $role,
                        ':id' => $user_id
                    ]
                );
                $success = "Usuario actualizado exitosamente.";
            }
            
            // Refresh user data
            $user_result = $db->query(
                'SELECT * FROM users WHERE id = :id',
                [':id' => $user_id]
            );
            $user = $user_result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            $error = "Error al actualizar el usuario: " . $e->getMessage();
        }
    } else {
        $error = "Por favor complete todos los campos requeridos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Guardería</title>
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
                        <h4 class="mb-0">Editar Usuario</h4>
                        <a href="index.php" class="btn btn-secondary btn-sm">
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
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                <div class="form-text">El nombre de usuario no se puede cambiar.</div>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Rol</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>Personal</option>
                                </select>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="change_password" name="change_password" value="1" onchange="togglePasswordFields()">
                                <label class="form-check-label" for="change_password">Cambiar Contraseña</label>
                            </div>

                            <div id="password_fields" style="display: none;">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordFields() {
            const passwordFields = document.getElementById('password_fields');
            const changePassword = document.getElementById('change_password');
            
            if (changePassword.checked) {
                passwordFields.style.display = 'block';
            } else {
                passwordFields.style.display = 'none';
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
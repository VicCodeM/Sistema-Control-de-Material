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

// Handle user status change if requested
if (isset($_GET['action']) && $_GET['action'] == 'change_status' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Don't allow deactivating own account
    if ($user_id == $_SESSION['user_id']) {
        $error = "No puedes desactivar tu propia cuenta.";
    } else {
        try {
            // Get current status
            $status_result = $db->query(
                'SELECT active FROM users WHERE id = :id',
                [':id' => $user_id]
            );
            $user_status = $status_result->fetchArray(SQLITE3_ASSOC);
            
            if ($user_status) {
                $new_status = $user_status['active'] ? 0 : 1;
                
                $db->query(
                    'UPDATE users SET active = :active WHERE id = :id',
                    [':active' => $new_status, ':id' => $user_id]
                );
                $success = "Estado del usuario actualizado correctamente.";
            } else {
                $error = "Usuario no encontrado.";
            }
        } catch (Exception $e) {
            $error = "Error al actualizar el estado: " . $e->getMessage();
        }
    }
}

// Get all users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$total_count = $db->query('SELECT COUNT(*) as count FROM users')->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_count / $per_page);

// Get users for current page
$users_result = $db->query(
    'SELECT id, username, name, role, active, created_at FROM users ORDER BY name LIMIT :limit OFFSET :offset',
    [':limit' => $per_page, ':offset' => $offset]
);

$users = [];
while ($user = $users_result->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $user;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top p-0 shadow">
        <div class="container-fluid">
            <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-1"></i>
            </button>
            <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="../dashboard.php">
                <i class="bi bi-building"></i> Guardería
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item text-nowrap">
                    <a class="nav-link px-3" href="../logout.php">
                        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
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
                            <a class="nav-link" href="../health/">
                                <i class="bi bi-heart-pulse-fill"></i> Salud
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../accounting/">
                                <i class="bi bi-calculator"></i> Contabilidad
                            </a>
                        </li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="../users/">
                                <i class="bi bi-person-gear"></i> Usuarios
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Usuarios</h2>
            <a href="register.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Registrar Nuevo Usuario
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-list-ul me-2"></i>Lista de Usuarios</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha de Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No hay usuarios registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                                                <?php echo $user['role'] == 'admin' ? 'Administrador' : 'Personal'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $user['active'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="?action=change_status&id=<?php echo $user['id']; ?>" 
                                                       class="btn <?php echo $user['active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                                       title="<?php echo $user['active'] ? 'Desactivar' : 'Activar'; ?>" 
                                                       onclick="return confirm('¿Está seguro de cambiar el estado de este usuario?')">
                                                        <i class="bi <?php echo $user['active'] ? 'bi-person-x' : 'bi-person-check'; ?>"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
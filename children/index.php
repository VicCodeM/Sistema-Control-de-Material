<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();

// Handle status change if requested
if (isset($_GET['action']) && $_GET['action'] == 'change_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $child_id = $_GET['id'];
    $new_status = $_GET['status'] == 'active' ? 'inactive' : 'active';
    
    try {
        $db->query(
            'UPDATE children SET status = :status WHERE id = :id',
            [':status' => $new_status, ':id' => $child_id]
        );
        $status_message = "Estado del niño actualizado correctamente.";
    } catch (Exception $e) {
        $error_message = "Error al actualizar el estado: " . $e->getMessage();
    }
}

// Get all children with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$total_count = $db->query('SELECT COUNT(*) as count FROM children')->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_count / $per_page);

// Get children for current page
$children_result = $db->query(
    'SELECT * FROM children ORDER BY last_name, first_name LIMIT :limit OFFSET :offset',
    [':limit' => $per_page, ':offset' => $offset]
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
    <title>Gestión de Niños - Guardería</title>
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
                            <a class="nav-link active" href="../children/">
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
                    <h1 class="h2"><i class="bi bi-people-fill"></i> Gestión de Niños</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="register.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Registrar Nuevo Niño
                        </a>
                    </div>
                </div>

                <?php if (isset($status_message)): ?>
                <div class="alert alert-success fade-in">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo htmlspecialchars($status_message); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger fade-in">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Niños</h5>
                            </div>
                            <div class="col-md-6">
                                <form class="d-flex" method="GET" action="">
                                    <input class="form-control me-2" type="search" placeholder="Buscar por nombre" name="search" aria-label="Search">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search me-1"></i> Buscar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Apellidos</th>
                                        <th>Fecha de Nacimiento</th>
                                        <th>Fecha de Inscripción</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($children)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="bi bi-emoji-frown fs-4 d-block mb-2"></i>
                                                No hay niños registrados
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($children as $child): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($child['id']); ?></td>
                                                <td><?php echo htmlspecialchars($child['first_name']); ?></td>
                                                <td><?php echo htmlspecialchars($child['last_name']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($child['birth_date'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($child['enrollment_date'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $child['status'] == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo $child['status'] == 'active' ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="view.php?id=<?php echo $child['id']; ?>" class="btn btn-info" title="Ver detalles">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="edit.php?id=<?php echo $child['id']; ?>" class="btn btn-warning" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="?action=change_status&id=<?php echo $child['id']; ?>&status=<?php echo $child['status']; ?>" 
                                                           class="btn <?php echo $child['status'] == 'active' ? 'btn-danger' : 'btn-success'; ?>" 
                                                           title="<?php echo $child['status'] == 'active' ? 'Desactivar' : 'Activar'; ?>" 
                                                           onclick="return confirm('¿Está seguro de cambiar el estado de este niño?')">
                                                            <i class="bi <?php echo $child['status'] == 'active' ? 'bi-x-circle' : 'bi-check-circle'; ?>"></i>
                                                        </a>
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
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true"><i class="bi bi-chevron-left"></i></span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        // Add animation classes to elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            // Animate card with fade in
            const card = document.querySelector('.card');
            if (card) {
                card.classList.add('animate__animated', 'animate__fadeIn');
            }
            
            // Initialize tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
            
            // Add tooltip to action buttons
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.setAttribute('data-bs-toggle', 'tooltip');
                btn.setAttribute('data-bs-placement', 'top');
            });
            
            // Enhance status change confirmation
            document.querySelectorAll('a[onclick*="confirm"]').forEach(link => {
                link.onclick = function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    const isActive = href.includes('status=active');
                    
                    Swal.fire({
                        title: '¿Está seguro?',
                        text: isActive ? '¿Desea desactivar este niño?' : '¿Desea activar este niño?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: isActive ? '#e63946' : '#4cc9f0',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: isActive ? 'Sí, desactivar' : 'Sí, activar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = href;
                        }
                    });
                };
            });
        });
    </script>
</body>
</html>
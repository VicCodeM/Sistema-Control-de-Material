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

<?php
$page_title = 'Gestión de Niños';
$current_page = 'children';
$base_path = '../';
include '../templates/header.php';
?>
        <main class="main-content">
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
                                    <th>Edad</th>
                                    <th>Fecha de Inscripción</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($children as $child): ?>
                                <?php 
                                    $birth_date = new DateTime($child['birth_date']);
                                    $today = new DateTime();
                                    $age = $birth_date->diff($today)->y;
                                ?>
                                <tr>
                                    <td><?php echo $child['id']; ?></td>
                                    <td><?php echo htmlspecialchars($child['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($child['last_name']); ?></td>
                                    <td><?php echo $age; ?> años</td>
                                    <td><?php echo date('d/m/Y', strtotime($child['enrollment_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $child['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $child['status'] === 'active' ? 'Activo' : 'Inactivo'; ?>
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
<?php include '../templates/footer.php'; ?>
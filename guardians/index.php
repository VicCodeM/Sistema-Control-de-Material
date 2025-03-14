<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();

// Handle delete if requested
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $guardian_id = $_GET['id'];
    
    try {
        // First check if this guardian is assigned to any children
        $check_result = $db->query(
            'SELECT COUNT(*) as count FROM child_guardian WHERE guardian_id = :guardian_id',
            [':guardian_id' => $guardian_id]
        );
        $has_children = $check_result->fetchArray(SQLITE3_ASSOC)['count'] > 0;
        
        if ($has_children) {
            $error_message = "No se puede eliminar el responsable porque está asignado a uno o más niños.";
        } else {
            $db->query(
                'DELETE FROM guardians WHERE id = :id',
                [':id' => $guardian_id]
            );
            $status_message = "Responsable eliminado correctamente.";
        }
    } catch (Exception $e) {
        $error_message = "Error al eliminar el responsable: " . $e->getMessage();
    }
}

// Get all guardians with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$total_count = $db->query('SELECT COUNT(*) as count FROM guardians')->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_count / $per_page);

// Get guardians for current page
$guardians_result = $db->query(
    'SELECT * FROM guardians ORDER BY last_name, first_name LIMIT :limit OFFSET :offset',
    [':limit' => $per_page, ':offset' => $offset]
);

$guardians = [];
while ($guardian = $guardians_result->fetchArray(SQLITE3_ASSOC)) {
    $guardians[] = $guardian;
}
?>

<?php
$page_title = 'Gestión de Responsables';
$current_page = 'guardians';
$base_path = '../';
include '../templates/header.php';
?>
        <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Responsables</h2>
            <a href="register.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Registrar Nuevo Responsable
            </a>
        </div>

        <?php if (isset($status_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($status_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-0">Lista de Responsables</h5>
                    </div>
                    <div class="col-md-6">
                        <form class="d-flex" method="GET" action="">
                            <input class="form-control me-2" type="search" placeholder="Buscar por nombre" name="search" aria-label="Search">
                            <button class="btn btn-outline-primary" type="submit">Buscar</button>
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
                                <th>Relación</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($guardians)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No hay responsables registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($guardians as $guardian): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($guardian['id']); ?></td>
                                        <td><?php echo htmlspecialchars($guardian['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($guardian['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($guardian['relationship']); ?></td>
                                        <td><?php echo htmlspecialchars($guardian['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($guardian['email'] ?? '-'); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="view.php?id=<?php echo $guardian['id']; ?>" class="btn btn-info" title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $guardian['id']; ?>" class="btn btn-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $guardian['id']; ?>" 
                                                   class="btn btn-danger" 
                                                   title="Eliminar" 
                                                   onclick="return confirm('¿Está seguro de eliminar este responsable?')">
                                                    <i class="bi bi-trash"></i>
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
    </main>
<?php include '../templates/footer.php'; ?>
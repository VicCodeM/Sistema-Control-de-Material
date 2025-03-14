<?php
// Common navbar template for all pages
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top p-0 shadow">
    <div class="container-fluid">
        <a class="navbar-brand me-0 px-3" href="<?php echo isset($base_path) ? $base_path : ''; ?>dashboard.php">
            <i class="bi bi-building"></i> Guardería
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-1"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="<?php echo isset($base_path) ? $base_path : ''; ?>dashboard.php">
                            <i class="bi bi-house-door-fill"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'children' ? 'active' : ''; ?>" href="<?php echo isset($base_path) ? $base_path : ''; ?>children/">
                            <i class="bi bi-people-fill"></i> <span>Niños</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'guardians' ? 'active' : ''; ?>" href="<?php echo isset($base_path) ? $base_path : ''; ?>guardians/">
                            <i class="bi bi-person-badge-fill"></i> <span>Responsables</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'attendance' ? 'active' : ''; ?>" href="<?php echo isset($base_path) ? $base_path : ''; ?>attendance/">
                            <i class="bi bi-calendar-check-fill"></i> <span>Asistencia</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'payments' ? 'active' : ''; ?>" href="<?php echo isset($base_path) ? $base_path : ''; ?>payments/">
                            <i class="bi bi-cash-coin"></i> <span>Pagos</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'health' ? 'active' : ''; ?>" href="<?php echo isset($base_path) ? $base_path : ''; ?>health/">
                            <i class="bi bi-heart-pulse-fill"></i> <span>Salud</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'accounting' ? 'active' : ''; ?>" href="<?php echo isset($base_path) ? $base_path : ''; ?>accounting/">
                            <i class="bi bi-calculator"></i> <span>Contabilidad</span>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>" href="<?php echo isset($base_path) ? $base_path : ''; ?>users/">
                            <i class="bi bi-person-gear"></i> <span>Usuarios</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item text-nowrap">
                        <a class="nav-link px-3" href="<?php echo isset($base_path) ? $base_path : ''; ?>logout.php">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <div class="container-fluid mt-5">
        <div class="row">
            <main class="col-12 px-4">
                <!-- Content will be injected here -->
            </main>
        </div>
    </div>
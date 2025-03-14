<?php
session_start();
require_once '../db/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Only admin and authorized staff can access accounting
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

$db = Database::getInstance();
$message = '';
$error = '';

// Default to current month if no date range is specified
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'monthly';

// Handle report generation
if (isset($_GET['generate']) && $_GET['generate'] === 'true') {
    // Get total income (payments + additional income)
    $income_query = "SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'paid' AND payment_date BETWEEN :start AND :end) +
        (SELECT COALESCE(SUM(amount), 0) FROM additional_income WHERE income_date BETWEEN :start AND :end) as total_income";

    $income_result = $db->query($income_query, [
        ':start' => $start_date,
        ':end' => $end_date
    ]);
    $total_income = $income_result->fetchArray(SQLITE3_ASSOC)['total_income'] ?? 0;

    // Get total expenses
    $expenses_query = "SELECT COALESCE(SUM(amount), 0) as total_expenses FROM expenses WHERE expense_date BETWEEN :start AND :end";
    $expenses_result = $db->query($expenses_query, [
        ':start' => $start_date,
        ':end' => $end_date
    ]);
    $total_expenses = $expenses_result->fetchArray(SQLITE3_ASSOC)['total_expenses'] ?? 0;

    // Calculate net profit/loss
    $net_profit = $total_income - $total_expenses;

    // Get income breakdown by category
    $income_categories_query = "SELECT 
        ic.name, 
        COALESCE(SUM(ai.amount), 0) as total
        FROM income_categories ic
        LEFT JOIN additional_income ai ON ic.id = ai.category_id AND ai.income_date BETWEEN :start AND :end
        GROUP BY ic.id
        ORDER BY total DESC";

    $income_categories_result = $db->query($income_categories_query, [
        ':start' => $start_date,
        ':end' => $end_date
    ]);

    $income_categories = [];
    while ($category = $income_categories_result->fetchArray(SQLITE3_ASSOC)) {
        $income_categories[] = $category;
    }

    // Get tuition payments
    $tuition_query = "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'paid' AND payment_date BETWEEN :start AND :end";
    $tuition_result = $db->query($tuition_query, [
        ':start' => $start_date,
        ':end' => $end_date
    ]);
    $tuition_total = $tuition_result->fetchArray(SQLITE3_ASSOC)['total'] ?? 0;

    // Add tuition to income categories
    array_unshift($income_categories, [
        'name' => 'Colegiaturas',
        'total' => $tuition_total
    ]);

    // Get expense breakdown by category
    $expense_categories_query = "SELECT 
        ec.name, 
        COALESCE(SUM(e.amount), 0) as total
        FROM expense_categories ec
        LEFT JOIN expenses e ON ec.id = e.category_id AND e.expense_date BETWEEN :start AND :end
        GROUP BY ec.id
        ORDER BY total DESC";

    $expense_categories_result = $db->query($expense_categories_query, [
        ':start' => $start_date,
        ':end' => $end_date
    ]);

    $expense_categories = [];
    while ($category = $expense_categories_result->fetchArray(SQLITE3_ASSOC)) {
        $expense_categories[] = $category;
    }

    // Save report to database
    $report_insert = $db->query(
        'INSERT INTO financial_reports (report_type, start_date, end_date, total_income, total_expenses, net_profit, generated_by, notes) 
        VALUES (:report_type, :start_date, :end_date, :total_income, :total_expenses, :net_profit, :generated_by, :notes)',
        [
            ':report_type' => $report_type,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':total_income' => $total_income,
            ':total_expenses' => $total_expenses,
            ':net_profit' => $net_profit,
            ':generated_by' => $_SESSION['user_id'],
            ':notes' => 'Reporte generado automáticamente'
        ]
    );

    if ($report_insert) {
        $message = 'Reporte financiero generado correctamente';
    } else {
        $error = 'Error al generar el reporte financiero';
    }
}

// Get previous reports
$reports_query = "SELECT fr.*, u.name as generated_by_name 
                FROM financial_reports fr 
                JOIN users u ON fr.generated_by = u.id 
                ORDER BY fr.generated_at DESC 
                LIMIT 10";
$reports_result = $db->query($reports_query);

$reports = [];
while ($report = $reports_result->fetchArray(SQLITE3_ASSOC)) {
    $reports[] = $report;
}
?>

<?php
$current_page = 'accounting';
$base_path = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Financieros - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <style>
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .report-card {
            transition: all 0.3s ease;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.2);
        }
        .category-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }
        .category-item:last-child {
            border-bottom: none;
        }
        .print-section {
            display: none;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .print-section {
                display: block;
            }
            .container {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php
$page_title = 'Reportes Financieros';
$current_page = 'accounting';
$base_path = '../';
include '../templates/header.php';
?>
        <main class="main-content">
                <div class="container mt-4 no-print">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="bi bi-file-earmark-bar-graph"></i> Reportes Financieros</h2>
                        <div>
                            <a href="index.php" class="btn btn-outline-primary me-2">
                                <i class="bi bi-calculator"></i> Contabilidad
                            </a>
                            <a href="expenses.php" class="btn btn-outline-danger me-2">
                                <i class="bi bi-cash-stack"></i> Gastos
                            </a>
                            <a href="income.php" class="btn btn-outline-success me-2">
                                <i class="bi bi-cash"></i> Ingresos
                            </a>
                            <button class="btn btn-primary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Imprimir Reporte
                            </button>
                        </div>
                    </div>
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h2><i class="bi bi-file-earmark-bar-graph"></i> Reportes Financieros</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Report Generator Form -->
        <div class="card mb-4 no-print">
            <div class="card-header bg-light">
                <h5 class="mb-0">Generar Nuevo Reporte</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="report_type" class="form-label">Tipo de Reporte</label>
                        <select class="form-select" id="report_type" name="report_type">
                            <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Mensual</option>
                            <option value="quarterly" <?php echo $report_type === 'quarterly' ? 'selected' : ''; ?>>Trimestral</option>
                            <option value="annual" <?php echo $report_type === 'annual' ? 'selected' : ''; ?>>Anual</option>
                            <option value="custom" <?php echo $report_type === 'custom' ? 'selected' : ''; ?>>Personalizado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <input type="hidden" name="generate" value="true">
                        <button type="submit" class="btn btn-primary w-100">Generar</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($_GET['generate']) && $_GET['generate'] === 'true'): ?>
            <!-- Print Header (Only visible when printing) -->
            <div class="print-section mb-4">
                <div class="text-center">
                    <h2>Guardería - Reporte Financiero</h2>
                    <p>Período: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                    <p>Generado el: <?php echo date('d/m/Y H:i'); ?></p>
                </div>
                <hr>
            </div>

            <!-- Financial Summary -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Resumen Financiero</h5>
                    <div class="no-print">
                        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Ingresos Totales</h5>
                                    <p class="card-text display-6">MX$ <?php echo number_format($total_income, 2); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card bg-danger text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Gastos Totales</h5>
                                    <p class="card-text display-6">MX$ <?php echo number_format($total_expenses, 2); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card <?php echo $net_profit >= 0 ? 'bg-primary' : 'bg-warning text-dark'; ?> text-white">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $net_profit >= 0 ? 'Ganancia Neta' : 'Pérdida Neta'; ?></h5>
                                    <p class="card-text display-6">MX$ <?php echo number_format(abs($net_profit), 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Income Breakdown -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Desglose de Ingresos</h5>
                            <div class="card mb-4">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($income_categories as $category): ?>
                                        <?php if ($category['total'] > 0): ?>
                                            <div class="category-item">
                                                <span><?php echo htmlspecialchars($category['name']); ?></span>
                                                <span class="text-success fw-bold">MX$ <?php echo number_format($category['total'], 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Expense Breakdown -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Desglose de Gastos</h5>
                            <div class="card mb-4">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($expense_categories as $category): ?>
                                        <?php if ($category['total'] > 0): ?>
                                            <div class="category-item">
                                                <span><?php echo htmlspecialchars($category['name']); ?></span>
                                                <span class="text-danger fw-bold">MX$ <?php echo number_format($category['total'], 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row no-print">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="incomeChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="expenseChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Previous Reports -->
        <div class="card no-print">
            <div class="card-header">
                <h5 class="mb-0">Reportes Anteriores</h5>
            </div>
            <div class="card-body">
                <?php if (empty($reports)): ?>
                    <p class="text-muted text-center">No hay reportes generados previamente</p>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($reports as $report): ?>
                            <div class="col">
                                <div class="card report-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <?php 
                                            switch ($report['report_type']) {
                                                case 'monthly':
                                                    echo 'Reporte Mensual';
                                                    break;
                                                case 'quarterly':
                                                    echo 'Reporte Trimestral';
                                                    break;
                                                case 'annual':
                                                    echo 'Reporte Anual';
                                                    break;
                                                default:
                                                    echo 'Reporte Personalizado';
                                            }
                                            ?>
                                        </h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($report['start_date'])); ?> - 
                                                <?php echo date('d/m/Y', strtotime($report['end_date'])); ?>
                                            </small>
                                        </p>
                                        <p class="mb-1"><strong>Ingresos:</strong> MX$ <?php echo number_format($report['total_income'], 2); ?></p>
                                        <p class="mb-1"><strong>Gastos:</strong> MX$ <?php echo number_format($report['total_expenses'], 2); ?></p>
                                        <p class="mb-1">
                                            <strong><?php echo $report['net_profit'] >= 0 ? 'Ganancia:' : 'Pérdida:'; ?></strong> 
                                            <span class="<?php echo $report['net_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                MX$ <?php echo number_format(abs($report['net_profit']), 2); ?>
                                            </span>
                                        </p>
                                        <p class="mb-0"><small>Generado por: <?php echo htmlspecialchars($report['generated_by_name']); ?></small></p>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <small class="text-muted">Generado el: <?php echo date('d/m/Y H:i', strtotime($report['generated_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Initialize charts if report is generated
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET['generate']) && $_GET['generate'] === 'true'): ?>
                // Income chart
                const incomeCtx = document.getElementById('incomeChart').getContext('2d');
                const incomeLabels = [];
                const incomeData = [];
                
                <?php foreach ($income_categories as $category): ?>
                    <?php if ($category['total'] > 0): ?>
                        incomeLabels.push('<?php echo addslashes($category['name']); ?>');
                        incomeData.push(<?php echo $category['total']; ?>);
                    <?php endif; ?>
                <?php endforeach; ?>
                
                new Chart(incomeCtx, {
                    type: 'pie',
                    data: {
                        labels: incomeLabels,
                        datasets: [{
                            data: incomeData,
                            backgroundColor: [
                                '#28a745', '#20c997', '#17a2b8', '#0d6efd', '#6610f2', '#6f42c1', '#fd7e14', '#ffc107'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            },
                            title: {
                                display: true,
                                text: 'Distribución de Ingresos'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed !== null) {
                                            label += 'MX$ ' + new Intl.NumberFormat('es-MX').format(context.parsed);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Expense chart
                const expenseCtx = document.getElementById('expenseChart').getContext('2d');
                const expenseLabels = [];
                const expenseData = [];
                
                <?php foreach ($expense_categories as $category): ?>
                    <?php if ($category['total'] > 0): ?>
                        expenseLabels.push('<?php echo addslashes($category['name']); ?>');
                        expenseData.push(<?php echo $category['total']; ?>);
                    <?php endif; ?>
                <?php endforeach; ?>
                
                new Chart(expenseCtx, {
                    type: 'pie',
                    data: {
                        labels: expenseLabels,
                        datasets: [{
                            data: expenseData,
                            backgroundColor: [
                                '#dc3545', '#e83e8c', '#fd7e14', '#ffc107', '#6f42c1', '#6610f2', '#0d6efd', '#20c997'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            },
                            title: {
                                display: true,
                                text: 'Distribución de Gastos'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed !== null) {
                                            label += 'MX$ ' + new Intl.NumberFormat('es-MX').format(context.parsed);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
            
            // Date range selection based on report type
            const reportTypeSelect = document.getElementById('report_type');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            if (reportTypeSelect) {
                reportTypeSelect.addEventListener('change', function() {
                    const today = new Date();
                    let startDate = new Date();
                    let endDate = new Date();
                    
                    switch (this.value) {
                        case 'monthly':
                            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                            break;
                        case 'quarterly':
                            const quarter = Math.floor(today.getMonth() / 3);
                            startDate = new Date(today.getFullYear(), quarter * 3, 1);
                            endDate = new Date(today.getFullYear(), (quarter + 1) * 3, 0);
                            break;
                        case 'annual':
                            startDate = new Date(today.getFullYear(), 0, 1);
                            endDate = new Date(today.getFullYear(), 11, 31);
                            break;
                        // For custom, leave the dates as they are
                    }
                    
                    if (this.value !== 'custom') {
                        startDateInput.value = startDate.toISOString().split('T')[0];
                        endDateInput.value = endDate.toISOString().split('T')[0];
                    }
                });
            }
        });
    </script>
</body>
</html>
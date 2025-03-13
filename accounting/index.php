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

// Get summary statistics
$current_month = date('Y-m');
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

// Get total income (payments + additional income)
$income_query = "SELECT 
    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'paid' AND payment_date BETWEEN :start AND :end) +
    (SELECT COALESCE(SUM(amount), 0) FROM additional_income WHERE income_date BETWEEN :start AND :end) as total_income";

$income_result = $db->query($income_query, [
    ':start' => $current_month_start,
    ':end' => $current_month_end
]);
$total_income = $income_result->fetchArray(SQLITE3_ASSOC)['total_income'] ?? 0;

// Get total expenses
$expenses_query = "SELECT COALESCE(SUM(amount), 0) as total_expenses FROM expenses WHERE expense_date BETWEEN :start AND :end";
$expenses_result = $db->query($expenses_query, [
    ':start' => $current_month_start,
    ':end' => $current_month_end
]);
$total_expenses = $expenses_result->fetchArray(SQLITE3_ASSOC)['total_expenses'] ?? 0;

// Calculate net profit/loss
$net_profit = $total_income - $total_expenses;

// Get recent transactions (both expenses and income)
$transactions_query = "SELECT 
    'expense' as type, 
    e.id, 
    e.amount, 
    e.description, 
    e.expense_date as transaction_date, 
    ec.name as category_name
    FROM expenses e
    JOIN expense_categories ec ON e.category_id = ec.id
    UNION ALL
    SELECT 
    'income' as type, 
    ai.id, 
    ai.amount, 
    ai.description, 
    ai.income_date as transaction_date, 
    ic.name as category_name
    FROM additional_income ai
    JOIN income_categories ic ON ai.category_id = ic.id
    ORDER BY transaction_date DESC
    LIMIT 10";

$transactions_result = $db->query($transactions_query);
$transactions = [];
while ($transaction = $transactions_result->fetchArray(SQLITE3_ASSOC)) {
    $transactions[] = $transaction;
}

// Get expense categories for chart
$expense_categories_query = "SELECT 
    ec.name, 
    COALESCE(SUM(e.amount), 0) as total
    FROM expense_categories ec
    LEFT JOIN expenses e ON ec.id = e.category_id AND e.expense_date BETWEEN :start AND :end
    GROUP BY ec.id
    ORDER BY total DESC";

$expense_categories_result = $db->query($expense_categories_query, [
    ':start' => $current_month_start,
    ':end' => $current_month_end
]);

$expense_categories = [];
$expense_amounts = [];
while ($category = $expense_categories_result->fetchArray(SQLITE3_ASSOC)) {
    if ($category['total'] > 0) {
        $expense_categories[] = $category['name'];
        $expense_amounts[] = $category['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contabilidad - Guardería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,.125);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .card-body {
            padding: 1.5rem;
        }
        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        .stat-card .stat-label {
            font-size: 1rem;
            color: rgba(255,255,255,0.8);
            margin-bottom: 0;
        }
        .transaction-item {
            border-left: 4px solid transparent;
            padding: 10px 15px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            transition: all 0.2s;
        }
        .transaction-item:hover {
            transform: translateX(5px);
        }
        .transaction-item.income {
            border-left-color: #28a745;
        }
        .transaction-item.expense {
            border-left-color: #dc3545;
        }
        .transaction-amount {
            font-weight: 700;
        }
        .transaction-date {
            color: #6c757d;
            font-size: 0.85rem;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">Guardería</a>
            <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-1"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Contabilidad</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="expenses.php">Gastos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="income.php">Ingresos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reportes</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cash-coin"></i> Panel de Contabilidad</h2>
            <div>
                <a href="expenses.php?action=add" class="btn btn-danger">
                    <i class="bi bi-dash-circle"></i> Registrar Gasto
                </a>
                <a href="income.php?action=add" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Registrar Ingreso
                </a>
                <a href="reports.php" class="btn btn-info">
                    <i class="bi bi-file-earmark-bar-graph"></i> Generar Reporte
                </a>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="stat-label">Ingresos del Mes</p>
                                <h3 class="stat-value">MX$ <?php echo number_format($total_income, 2); ?></h3>
                            </div>
                            <div class="icon">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="stat-label">Gastos del Mes</p>
                                <h3 class="stat-value">MX$ <?php echo number_format($total_expenses, 2); ?></h3>
                            </div>
                            <div class="icon">
                                <i class="bi bi-graph-down-arrow"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card <?php echo $net_profit >= 0 ? 'bg-primary' : 'bg-warning text-dark'; ?> text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="stat-label"><?php echo $net_profit >= 0 ? 'Ganancia Neta' : 'Pérdida Neta'; ?></p>
                                <h3 class="stat-value">MX$ <?php echo number_format(abs($net_profit), 2); ?></h3>
                            </div>
                            <div class="icon">
                                <i class="bi <?php echo $net_profit >= 0 ? 'bi-piggy-bank' : 'bi-exclamation-triangle'; ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Expense Distribution Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Distribución de Gastos</h5>
                        <span class="badge bg-secondary"><?php echo date('F Y'); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="expenseChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Transacciones Recientes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($transactions)): ?>
                            <p class="text-muted text-center">No hay transacciones recientes</p>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <div class="transaction-item <?php echo $transaction['type'] === 'income' ? 'income' : 'expense'; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($transaction['description']); ?></h6>
                                            <span class="transaction-date">
                                                <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?> | 
                                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($transaction['category_name']); ?>
                                            </span>
                                        </div>
                                        <div class="transaction-amount <?php echo $transaction['type'] === 'income' ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?> MX$ <?php echo number_format($transaction['amount'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="<?php echo $transactions[0]['type'] === 'income' ? 'income.php' : 'expenses.php'; ?>" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Initialize expense distribution chart
        const expenseCtx = document.getElementById('expenseChart').getContext('2d');
        const expenseChart = new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($expense_categories); ?>,
                datasets: [{
                    data: <?php echo json_encode($expense_amounts); ?>,
                    backgroundColor: [
                        '#4361ee', '#3a0ca3', '#4895ef', '#4cc9f0', '#f72585', '#7209b7', '#b5179e', '#560bad', '#480ca8', '#3f37c9'
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
    </script>
</body>
</html>
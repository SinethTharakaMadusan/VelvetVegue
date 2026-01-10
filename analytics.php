<?php
require_once 'Database.php';

$revenue_sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders";
$revenue_res = $conn->query($revenue_sql);
$total_revenue = $revenue_res->fetch_assoc()['total'];

$orders_sql = "SELECT COUNT(*) as total FROM orders";
$orders_res = $conn->query($orders_sql);
$total_orders = $orders_res->fetch_assoc()['total'];

$customers_sql = "SELECT COUNT(*) as total FROM users";
$customers_res = $conn->query($customers_sql);
$total_customers = $customers_res->fetch_assoc()['total'];

$monthly_sql = "
    SELECT 
        DATE_FORMAT(order_date, '%Y-%m') as month_year,
        SUM(total_amount) as sales
    FROM orders
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month_year
    ORDER BY month_year ASC
";
$monthly_res = $conn->query($monthly_sql);

$chart_labels = [];
$chart_data = [];

if ($monthly_res) {
    while ($row = $monthly_res->fetch_assoc()) {
        $dateObj = DateTime::createFromFormat('!Y-m', $row['month_year']);
        $chart_labels[] = $dateObj->format('M Y');
        $chart_data[] = $row['sales'];
    }
}

$json_labels = json_encode($chart_labels);
$json_data = json_encode($chart_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Analytics</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="Admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.revenue { background: #e0f7fa; color: #00bcd4; }
        .stat-icon.orders { background: #e8f5e9; color: #4caf50; }
        .stat-icon.customers { background: #f3e5f5; color: #9c27b0; }
        .stat-icon.avg { background: #fff3e0; color: #ff9800; }

        .stat-details h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #777;
        }
        .stat-details .value {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .chart-container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .chart-header {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .chart-header h2 {
            font-size: 18px;
            color: #333;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Reusing Sidebar Structure -->
        <div class="sidebar">
            <div class="logo-container">
                <img class="logo" src="image/logo.png" alt="Logo">
            </div>
            <div class="menu-items">
                <a href="Admin.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-box"></i> Products</a>
                <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
                <a href="analytics.php" class="active"><i class="fas fa-chart-bar"></i> Analytics</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            </div>
            <div class="user-profile">
                <img src="image/user.png" alt="User" class="user-avatar">
                <div class="user-info">
                    <h4>Admin User</h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>Analytics Overview</h1>
            </div>

            <!-- Metrics Cards -->
            <div class="analytics-grid">
                <div class="stat-card">
                    <div class="stat-icon revenue"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-details">
                        <h3>Total Revenue</h3>
                        <p class="value">LKR <?php echo number_format($total_revenue, 2); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orders"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-details">
                        <h3>Total Orders</h3>
                        <p class="value"><?php echo number_format($total_orders); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon customers"><i class="fas fa-users"></i></div>
                    <div class="stat-details">
                        <h3>Total Customers</h3>
                        <p class="value"><?php echo number_format($total_customers); ?></p>
                    </div>
                </div>
                 <div class="stat-card">
                    <div class="stat-icon avg"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-details">
                        <h3>Avg. Order Value</h3>
                        <p class="value">LKR <?php echo ($total_orders > 0) ? number_format($total_revenue / $total_orders, 2) : '0.00'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Sales Chart -->
            <div class="chart-container">
                <div class="chart-header">
                    <h2>Monthly Revenue Trends</h2>
                </div>
                <canvas id="salesChart" width="100%" height="40"></canvas>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo $json_labels; ?>,
                datasets: [{
                    label: 'Revenue (LKR)',
                    data: <?php echo $json_data; ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                         grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>

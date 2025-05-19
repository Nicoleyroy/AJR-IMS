<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
$message = '';
require 'includes/db.php';

// Fetch product names and quantities for the chart
$products = [];
$quantities = [];
$sql = "SELECT product_name, quantity FROM products";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row['product_name'];
        $quantities[] = $row['quantity'];
    }
}

// Fetch sold products data for the chart
$sold_products = [];
$sold_quantities = [];
$sold_sql = "SELECT product_name, SUM(quantity) as total_sold 
             FROM checkout_records 
             GROUP BY product_name 
             ORDER BY total_sold DESC";
$sold_result = $conn->query($sold_sql);
if ($sold_result && $sold_result->num_rows > 0) {
    while ($row = $sold_result->fetch_assoc()) {
        $sold_products[] = $row['product_name'];
        $sold_quantities[] = $row['total_sold'];
    }
}


// Fetch checkout history
$checkout_sql = "SELECT id, checkout_date, product_name, quantity, price, total_amount FROM checkout_records ORDER BY checkout_date DESC";
$checkout_result = $conn->query($checkout_sql);
$checkout_history = [];
if ($checkout_result && $checkout_result->num_rows > 0) {
    while ($row = $checkout_result->fetch_assoc()) {
        $checkout_history[] = $row;
    }
}

// Calculate total sales
$total_sales_sql = "SELECT SUM(total_amount) as total_sales FROM checkout_records";
$total_sales_result = $conn->query($total_sales_sql);
$total_sales = $total_sales_result->fetch_assoc()['total_sales'] ?? 0;

// Calculate total sold (sum of all quantities sold)
$total_sold_sql = "SELECT SUM(quantity) as total_sold FROM checkout_records";
$total_sold_result = $conn->query($total_sold_sql);
$total_sold = $total_sold_result->fetch_assoc()['total_sold'] ?? 0;

// Get total products count
$total_products_sql = "SELECT COUNT(*) as total_products FROM products";
$total_products_result = $conn->query($total_products_sql);
$total_products = $total_products_result->fetch_assoc()['total_products'] ?? 0;

// Get total quantities in stock
$total_quantities_sql = "SELECT SUM(quantity) as total_quantities FROM products";
$total_quantities_result = $conn->query($total_quantities_sql);
$total_quantities = $total_quantities_result->fetch_assoc()['total_quantities'] ?? 0;

// Almost Out of Products (quantity > 0 and <= 50)
$almost_out_sql = "SELECT product_id, product_name, quantity, prices FROM products WHERE quantity > 0 AND quantity <= 50";
$almost_out_result = $conn->query($almost_out_sql);
$almost_out_products = [];
if ($almost_out_result && $almost_out_result->num_rows > 0) {
    while ($row = $almost_out_result->fetch_assoc()) {
        $almost_out_products[] = $row;
    }
}

// Out of Products (quantity = 0)
$out_of_sql = "SELECT product_id, product_name, quantity, prices FROM products WHERE quantity = 0";
$out_of_result = $conn->query($out_of_sql);
$out_of_products = [];
if ($out_of_result && $out_of_result->num_rows > 0) {
    while ($row = $out_of_result->fetch_assoc()) {
        $out_of_products[] = $row;
    }
}

// Best Selling Products (by turnover)
$best_selling_sql = "SELECT cr.product_name, cr.product_id, SUM(cr.quantity) as total_quantity, SUM(cr.total_amount) as turnover
    FROM checkout_records cr
    GROUP BY cr.product_id, cr.product_name
    ORDER BY turnover DESC
    LIMIT 5";
$best_selling_result = $conn->query($best_selling_sql);
$best_selling_products = [];
if ($best_selling_result && $best_selling_result->num_rows > 0) {
    while ($row = $best_selling_result->fetch_assoc()) {
        $best_selling_products[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles\reports.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
</head>
<body>
    <div class="dashboard">
        <!-- SIDEBAR START -->
        <div class="sidebar">
            <div class="heads">
                <img class="logo" src="img\AJR.png" alt="logo">
                I M S
            </div>
            <ul class="menu">
                <li><a href="dashboard.php"> 
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Dashboard</span></a>
                </li>

                <li><a href="products.php">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                    </svg>
                    <span>Products</span></a>
                </li>

                <li><a href="reports.php" class="active">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Reports</span></a>
                </li>

                    <li><a href="inventory.php">
                       <svg width="20" height="20" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2">
                        <g>
                            <polyline points="6,42 6,20 24,6 42,20 42,42" fill="none" stroke="black" stroke-width="2"/>
                            <rect x="14" y="28" width="20" height="14" fill="none" stroke="black" stroke-width="2"/>
                            <line x1="14" y1="34" x2="34" y2="34" stroke="black" stroke-width="2"/>
                            <line x1="14" y1="38" x2="34" y2="38" stroke="black" stroke-width="2"/>
                            <line x1="14" y1="30" x2="34" y2="30" stroke="black" stroke-width="2"/>
                        </g>
                        </svg>
                        <span>Inventory</span></a>
                    </li>

                    <li><a href="checkout.php">
                       <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                    </svg>
                        <span>Check out</span></a>
                    </li>

                <li><a href="login.php" onclick="return confirmLogout()">
                    <svg width="20" height="20" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="4">
                            <rect x="6" y="6" width="24" height="36" rx="6" fill="none" stroke="black" stroke-width="4"/>
                            <path d="M30 24h12" stroke="black" stroke-width="4" stroke-linecap="round"/>
                            <path d="M36 18l6 6-6 6" stroke="black" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <span>Logout</span></a>
                </li>
            </ul>
        </div>
        <!-- SIDEBAR END -->

        <!-- MAIN CONTENT START -->
        <div class="main-content">
            <!-- TOPBAR START -->
            <div class="topbar">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Search">
                </div>
                <!-- topbar profile -->
                <div class="user-profile"><span><?= htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) ?></span></div>
            </div>
            <!-- TOPBAR END -->

            <br>
            <div class="content">
                  <!-- Summary Card -->
                  <section class="dashboard-cards">
                        
                        <div class="card">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#4e9a06" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path>
                                <path d="M12 6v2m0 8v2"></path>
                            </svg>
                            <div>
                                <h2>Total Sales</h2>
                               <p class="total">₱<?php echo number_format($total_sales, 2); ?></p>
                            </div>
                        </div>
                        <div class="card">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#4e9a06" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            <div>
                                <h2>Total Sold</h2>
                                  <p class="total"><?php echo number_format($total_sold); ?></p>
                            </div>
                        </div>
                        <div class="card">
                                 <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#4e9a06" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polygon points="21 16 12 22 3 16 3 8 12 2 21 8 21 16"/>
                                  <line x1="3" y1="8" x2="12" y2="14"/>
                                  <line x1="21" y1="8" x2="12" y2="14"/>
                                  <line x1="12" y1="22" x2="12" y2="14"/>
                                </svg>
                                
                            <div>
                                <h2>Total Products</h2>
                                <p class="total"><?php echo number_format($total_products); ?></p>
                            </div>
                        </div>
                        <div class="card">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#4e9a06" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>

                            <div>
                                <h2>Total Quantities</h2>
                                <p class="total"><?php echo number_format($total_quantities); ?></p>
                            </div>
                        </div>

                          <div class="card">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffa500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2v4"></path>
                                <path d="M12 18v4"></path>
                                <path d="M4.93 4.93l2.83 2.83"></path>
                                <path d="M16.24 16.24l2.83 2.83"></path>
                                <path d="M2 12h4"></path>
                                <path d="M18 12h4"></path>
                                <path d="M4.93 19.07l2.83-2.83"></path>
                                <path d="M16.24 7.76l2.83-2.83"></path>
                            </svg>
                            <div>
                                <h2>Low in Stock</h2>
                                <p class="total"><?php echo count($almost_out_products); ?></p>
                            </div>
                        </div>

                        <div class="card">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ff4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <div>
                                <h2>Out of Stock</h2>
                                <p class="total"><?php echo count($out_of_products); ?></p>
                            </div>
                        </div>
                  
                    </section>

                <!-- Product Quantities Chart -->
                <canvas id="productChart" width="400" height="120"></canvas>
                <!-- Sold Products Chart -->
                <canvas id="soldProductChart" width="400" height="100" style="background:#fff; border:1px solid #ccc;"></canvas>

                </div>

                <!-- Stock Status Tables -->
                <section class="stock-status-tables">
                    <div class="stock-table-card">
                        <h3 class="stock-table-title almost">Almost Out of Stock</h3>
                        <table class="stock-table">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($almost_out_products) > 0): ?>
                                    <?php foreach ($almost_out_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="empty">No products are almost out of stock.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="stock-table-card">
                        <h3 class="stock-table-title out">Out of Stock</h3>
                        <table class="stock-table">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Product</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($out_of_products) > 0): ?>
                                    <?php foreach ($out_of_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="empty">No products are out of stock.</td></tr>
                                <?php endif; ?>
                        </tbody>
                    </table>
                 </div>
             </section>

        </div>
        
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Logout</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-confirm" onclick="proceedLogout()">Logout</button>
            </div>
        </div>
    </div>

<script>
    const productNames = <?php echo json_encode($products); ?>;
    const productQuantities = <?php echo json_encode($quantities); ?>;

    // Generate a pastel color for each product
    function pastelColor(i) {
        const hue = (i * 47) % 360;
        return `hsl(${hue}, 70%, 80%)`;
    }
    const backgroundColors = productNames.map((_, i) => pastelColor(i));
    const borderColors = backgroundColors;

    const ctx = document.getElementById('productChart').getContext('2d');
    const productChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: productNames,
            datasets: [{
                label: 'Quantity',
                data: productQuantities,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Product Inventory Quantities',
                    font: { size: 26, weight: 'bold' },
                    color: '#666',
                    padding: { top: 20, bottom: 30 }
                },
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 500,
                    title: {
                        display: true,
                        text: 'Quantity',
                        font: { size: 18 }
                    },
                    ticks: { color: '#666', font: { size: 16 } },
                    grid: { color: '#eee' }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Products',
                        font: { size: 18 }
                    },
                    ticks: { color: '#666', font: { size: 16 } },
                    grid: { color: '#eee' }
                }
            }
        }
    });
</script>

<script>
    const soldProductNames = <?php echo json_encode($sold_products); ?>;
    const soldProductQuantities = <?php echo json_encode($sold_quantities); ?>;

    // Generate a pastel color for each product
    function pastelColor(i) {
        const hue = (i * 47) % 360;
        return `hsl(${hue}, 70%, 80%)`;
    }
    const soldBackgroundColors = soldProductNames.map((_, i) => pastelColor(i));
    const soldBorderColors = soldBackgroundColors;

    const soldCtx = document.getElementById('soldProductChart').getContext('2d');
    const soldProductChart = new Chart(soldCtx, {
        type: 'bar',
        data: {
            labels: soldProductNames,
            datasets: [{
                label: 'Quantity Sold',
                data: soldProductQuantities,
                backgroundColor: soldBackgroundColors,
                borderColor: soldBorderColors,
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Product Sold Quantities',
                    font: { size: 26, weight: 'bold' },
                    color: '#666',
                    padding: { top: 20, bottom: 30 }
                },
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 500,
                    title: {
                        display: true,
                        text: 'Quantity Sold',
                        font: { size: 18 }
                    },
                    ticks: { color: '#666', font: { size: 16 } },
                    grid: { color: '#eee' }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Products',
                        font: { size: 18 }
                    },
                    ticks: { color: '#666', font: { size: 16 } },
                    grid: { color: '#eee' }
                }
            }
        }
    });
</script>

<script>
        // Logout Modal Functions
        function confirmLogout() {
            document.getElementById('logoutModal').style.display = 'block';
            return false; // Prevent default link behavior
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function proceedLogout() {
            window.location.href = 'index.php';
        }

        // Initialize modal functionality when document is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('logoutModal');
                if (event.target == modal) {
                    closeLogoutModal();
                }
            }

            // Close modal when clicking the X
            document.querySelector('.close').onclick = closeLogoutModal;
        });
    </script>

</body>
</html>
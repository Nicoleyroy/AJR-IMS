<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
require 'includes/db.php';

// Fetch all products
$result = $conn->query("SELECT product_id, product_name, quantity as stocks_available FROM products ORDER BY product_id");
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Add status based on stock quantity
        if ($row['stocks_available'] <= 0) {
            $row['status'] = 'out_of_stock';
            $row['status_text'] = 'Out of Stock';
        } elseif ($row['stocks_available'] <= 10) {
            $row['status'] = 'almost_out';
            $row['status_text'] = 'Almost Out of Stock';
        } elseif ($row['stocks_available'] <= 30) {
            $row['status'] = 'low_stock';
            $row['status_text'] = 'Low Stock';
        } else {
            $row['status'] = 'in_stock';
            $row['status_text'] = 'In Stock';
        }
        $products[] = $row;
    }
}
// Download PDF
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $autoloader = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoloader)) {
        die('Composer autoloader not found. Please run composer install.');
    }
    require_once $autoloader;
    require_once __DIR__ . '/includes/db.php';

    try {
        if (!class_exists('\\Mpdf\\Mpdf')) {
            die('mPDF class not found. Please ensure mPDF is properly installed via composer.');
        }
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9
        ]);

        // Get current date and time
        $current_date = date('F d, Y');
        $current_time = date('h:i A');

        // Get products data
        $stmt = $conn->prepare("SELECT * FROM products");
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $count = 1;

        $html = '
            <html>
            <head>
                <style>
                    body {
                        font-family: "Helvetica Neue", sans-serif;
                        font-size: 12px;
                        padding: 20px;
                        color: #333;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .header h1 {
                        color: #2c3e50;
                        margin: 0;
                        font-size: 24px;
                    }
                    .header p {
                        color: #7f8c8d;
                        margin: 5px 0;
                    }
                    .report-info {
                        margin-bottom: 20px;
                        padding: 10px;
                        background-color: #f8f9fa;
                        border-radius: 5px;
                    }
                    .report-info p {
                        margin: 5px 0;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    table, th, td {
                        border: 1px solid #ddd;
                    }
                    th {
                        background-color: #f8f9fa;
                        color: #2c3e50;
                        font-weight: bold;
                        padding: 12px 8px;
                        text-align: left;
                    }
                    td {
                        padding: 10px 8px;
                    }
                    tr:nth-child(even) {
                        background-color: #f8f9fa;
                    }
                    .signature_section {
                        margin-top: 50px;
                        text-align: center;
                    }
                    .footer {
                        text-align: center;
                        font-size: 10px;
                        color: #7f8c8d;
                        margin-top: 20px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>AJR Inventory Management System</h1>
                    <p>Product Inventory Report</p>
                </div>

                <div class="report-info">
                    <p><strong> Date:</strong> ' . $current_date . '</p>
                    <p><strong> Time:</strong> ' . $current_time . '</p>
                    <p><strong>Total Products:</strong> ' . count($products) . '</p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 15%;">Product ID</th>
                            <th style="width: 35%;">Product Name</th>
                            <th style="width: 15%;">Packaging</th>
                            <th style="width: 15%;">Prices</th>
                            <th style="width: 15%;">Stocks</th>
                        </tr>
                    </thead>
                    <tbody>
        ';

        foreach ($products as $product) {
            $html .= '
                <tr>
                    <td>' . $count++ . '</td>
                    <td>' . htmlspecialchars($product['product_id']) . '</td>
                    <td>' . htmlspecialchars($product['product_name']) . '</td>
                    <td>' . htmlspecialchars($product['packaging']) . '</td>
                    <td>₱' . number_format($product['prices'], 2) . '</td>
                    <td>' . htmlspecialchars($product['quantity']) . '</td>
                </tr>
            ';
        }

        $html .= '
                    </tbody>
                </table>

                <div class="signature_section">
                    <p>_________________________________________________</p>
                    <p><strong>General Manager</strong></p>
                </div>

                <div class="footer">
                    <p>© ' . date('Y') . ' AJR IMS. All rights reserved.</p>
                </div>
            </body>
            </html>
        ';

        $mpdf->SetHTMLHeader('
            <div style="text-align: right; font-size: 10px; color: #7f8c8d;">
                Page {PAGENO} of {nbpg}
            </div>
        ');

        $mpdf->SetHTMLFooter('
            <div style="text-align: center; font-size: 10px; color: #7f8c8d;">
                AJR IMS - Product Inventory Report
            </div>
        ');

        $mpdf->WriteHTML($html);
        $mpdf->Output('AJR_Product_Inventory_' . date('Y-m-d') . '.pdf', 'I');
        exit;
    } catch (\Exception $e) {
        $message = 'Error generating PDF: ' . $e->getMessage();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles\inventory.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  
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

                <li><a href="reports.php">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Reports</span></a>
                </li>

                    <li><a href="inventory.php" class="active">
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
                <li><a href="#" onclick="return confirmLogout()">
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
                <div class="user-profile"><span><?= htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) ?></span></div>
            </div>
            <!-- TOPBAR END -->

            <!-- CONTENT AREA -->
            <div class="inventory-content">
                <h1>Inventory</h1>
                
                <!-- Search Filters -->
                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search..." onkeyup="searchProducts()">
                        <button onclick="clearSearch()" class="clear-search">Clear</button>
                    </div>

                    <div class="status-filter">
                        <select id="statusFilter" onchange="searchProducts()">
                            <option value="all">All Status</option>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="almost_out">Almost Out of Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                    <form action="" method="POST" class="print-form">
                        <button type="submit" class="print-btn">Print Inventory</button>
                    </form>
                </div>


                <div class="inventory-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Product Id</th>
                                <th>Product</th>
                                <th>Stocks Available</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['stocks_available']); ?></td>
                                <td><span class="status-badge <?php echo $product['status']; ?>"><?php echo $product['status_text']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
    function searchProducts() {
        var input = document.getElementById("searchInput");
        var statusSelect = document.getElementById("statusFilter");
        var filter = input.value.toLowerCase();
        var statusFilter = statusSelect.value;
        var tbody = document.querySelector("tbody");
        var rows = tbody.getElementsByTagName("tr");

        for (var i = 0; i < rows.length; i++) {
            var cells = rows[i].getElementsByTagName("td");
            var found = false;
            var statusMatch = true;
            
            // Check status filter first
            if (statusFilter !== "all") {
                var statusCell = cells[3]; // Status is in the 4th column
                var statusClass = statusCell.querySelector('.status-badge').classList[1];
                statusMatch = statusClass === statusFilter;
            }
            
            if (statusMatch) {
                for (var j = 0; j < cells.length; j++) {
                    var cell = cells[j];
                    if (cell) {
                        var text = cell.textContent || cell.innerText;
                        if (text.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break; // Stop checking other cells if found
                        }
                    }
                }
            }
            
            rows[i].style.display = (found && statusMatch) ? "" : "none";
        }
    }

    function clearSearch() {
        document.getElementById("searchInput").value = "";
        document.getElementById("statusFilter").value = "all";
        searchProducts();
    }
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
            const closeBtn = document.querySelector('.close');
            if (closeBtn) {
                closeBtn.onclick = closeLogoutModal;
            }

            // Add click event to logout link
            const logoutLink = document.querySelector('a[onclick="return confirmLogout()"]');
            if (logoutLink) {
                logoutLink.onclick = function(e) {
                    e.preventDefault();
                    confirmLogout();
                };
            }
        });
    </script>
</body>
</html>
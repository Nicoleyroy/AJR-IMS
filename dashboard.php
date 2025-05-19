<?php
session_start();
require 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Total products (how many unique product entries)
$result = $conn->query("SELECT COUNT(*) AS total FROM products");
$total_products = $result->fetch_assoc()['total'];

// Total stock (sum of all quantities)
$result = $conn->query("SELECT SUM(quantity) AS total FROM products");
$total_stock = $result->fetch_assoc()['total'];

// Out of stock (products with quantity 0)
$result = $conn->query("SELECT COUNT(*) AS total FROM products WHERE quantity = 0");
$out_of_stock = $result->fetch_assoc()['total'];

// Top selling products from checkout records
$top_selling_sql = "SELECT 
    cr.product_id,
    cr.product_name,
    SUM(cr.quantity) as total_quantity,
    SUM(cr.total_amount) as total_revenue
FROM checkout_records cr
GROUP BY cr.product_id, cr.product_name
ORDER BY total_quantity DESC
LIMIT 5";
$top_selling_result = $conn->query($top_selling_sql);
$top_selling = [];
if ($top_selling_result && $top_selling_result->num_rows > 0) {
    while ($row = $top_selling_result->fetch_assoc()) {
        $top_selling[] = [
            'product' => $row['product_name'],
            'quantity' => $row['total_quantity'],
            'amount' => $row['total_revenue']
        ];
    }
}

// Inventory list (limit to 10 for display)
$inventory = [];
$result = $conn->query("SELECT product_id, product_name, quantity FROM products LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $inventory[] = [
        'product_id' => $row['product_id'],
        'product' => $row['product_name'],
        'stocks' => $row['quantity'],
    ];
}

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

$conn->close();
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Home</title>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="styles\dashboard.css">
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
                    <li><a href="dashboard.php" class="active"> 
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

                <div class="content">
                    <br>
                    <section class="dashboard-cards">
                        
                        <div class="card">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#4e9a06" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                            <div>
                                <h2><?= $total_products ?></h2>
                                <p>Total Products</p>
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
                                <h2><?= $total_stock ?></h2>
                                <p>Total Stock</p>
                            </div>
                        </div>
                        <div class="card">
                              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#f7b731" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                              </svg>
                            <div>
                                <h2><?= $top_selling[0]['product'] ?? 'N/A' ?></h2>
                                <p>Top seller</p>
                            </div>
                        </div>
                        <div class="card">
                             <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ff4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <div>
                                <h2><?= $out_of_stock ?></h2>
                                <p>Out of stock</p>
                            </div>
                        </div>
                    </section>

                    <section class="dashboard-main">
                        <div class="chart-section">
                            <canvas id="productChart" width="400" height="150"></canvas>
                        </div>
                        <div class="chat-section">
                            <div class="chat-header">
                                <h3>ChatBot</h3>
                                <div class="chat-indicator" id="chat-indicator"></div>
                            </div>
                            <div class="chat-box" id="chat-box">
                                <!-- Messages will be appended here -->
                            </div>
                            <form class="chat-input" id="chat-form">
                                <input type="text" id="chat-input" placeholder="Send chat" autocomplete="off">
                                <button type="submit">&#10148;</button>
                            </form>
                        </div>
                    </section>


                    <section class="dashboard-tables">
                        <div class="table inventory">
                            <h3>Inventory</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product</th>
                                        <th>Stocks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory as $item): ?>
                                    <tr>
                                        <td><?= $item['product_id'] ?></td>
                                        <td><?= $item['product'] ?></td>
                                        <td><?= $item['stocks'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table top-selling">
                            <h3>Top Selling Products</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity Sold</th>
                                        <th>Total Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_selling as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>₱<?= number_format($item['amount'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
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
            // Pass PHP arrays to JS
            const productNames = <?php echo json_encode($products); ?>;
            const productQuantities = <?php echo json_encode($quantities); ?>;

            const ctx = document.getElementById('productChart').getContext('2d');
            
            // Generate a pastel color for each product
            function pastelColor(i) {
                const hue = (i * 47) % 360;
                return `hsl(${hue}, 70%, 80%)`;
            }
            const backgroundColors = productNames.map((_, i) => pastelColor(i));
            const borderColors = backgroundColors;

            const productChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: productNames,
                    datasets: [{
                        label: 'Product Quantities',
                        data: productQuantities,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Product Inventory Quantities',
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantity'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Products'
                            }
                        }
                    }
                }
            });

            // --- AI Chatbot JS ---
            const chatIndicator = document.getElementById('chat-indicator');
            const chatForm = document.getElementById('chat-form');
            const chatInput = document.getElementById('chat-input');
            const chatBox = document.getElementById('chat-box');
            let hasNewMessages = false;
            let isProcessing = false;

            // Function to show indicator
            function showChatIndicator() {
                chatIndicator.classList.add('active');
                hasNewMessages = true;
            }

            // Function to hide indicator
            function hideChatIndicator() {
                chatIndicator.classList.remove('active');
                hasNewMessages = false;
            }

            // Function to add message to chat
            function addMessage(content, type) {
                const message = document.createElement('div');
                message.className = `chat-message ${type}`;
                message.textContent = content;
                chatBox.appendChild(message);
                chatBox.scrollTop = chatBox.scrollHeight;
                return message;
            }

            // Function to set processing state
            function setProcessing(processing) {
                isProcessing = processing;
                chatInput.disabled = processing;
                chatForm.querySelector('button').disabled = processing;
            }

            // Handle form submission
            chatForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const message = chatInput.value.trim();
                if (!message || isProcessing) return;

                // Add user message
                addMessage(message, 'user');
                chatInput.value = '';

                // Add loading message
                const loadingMsg = addMessage('Thinking...', 'loading');
                setProcessing(true);

                try {
                    const res = await fetch('chat_api.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({message})
                    });
                    const data = await res.json();
                    
                    // Remove loading message
                    loadingMsg.remove();
                    
                    // Add bot response
                    addMessage(data.response, 'bot');
                    showChatIndicator();
                } catch (err) {
                    loadingMsg.remove();
                    addMessage('Error: Could not connect to the AI service. Please try again.', 'bot');
                } finally {
                    setProcessing(false);
                }
            });

            // Hide indicator when chat is viewed
            chatBox.addEventListener('scroll', function() {
                if (hasNewMessages) {
                    const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop === chatBox.clientHeight;
                    if (isAtBottom) {
                        hideChatIndicator();
                    }
                }
            });

            // Add welcome message
            window.addEventListener('load', () => {
                addMessage('Hello! I\'m your inventory assistant. How can I help you today?', 'bot');
            });

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

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('logoutModal');
                if (event.target == modal) {
                    closeLogoutModal();
                }
            }

            // Close modal when clicking the X
            document.querySelector('.close').onclick = closeLogoutModal;
        </script>
    </body>
</html>













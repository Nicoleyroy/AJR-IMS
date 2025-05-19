<?php
require 'includes/db.php';
session_start();
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = trim($_POST['product_id']); // Trim whitespace
        $quantity = (int)$_POST['quantity'];
        error_log('Scanned product_id: ' . $product_id); // Debug
        
        // Check if product exists and has sufficient quantity
        $check_stmt = $conn->prepare("SELECT quantity, product_name, prices FROM products WHERE product_id = ?");
        $check_stmt->bind_param("s", $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            if ($product['quantity'] >= $quantity) {
                // Insert checkout record into checkout_records
                $price = $product['prices'];
                $total_amount = $price * $quantity;
                $history_stmt = $conn->prepare("INSERT INTO checkout_records (product_id, product_name, quantity, price, total_amount) VALUES (?, ?, ?, ?, ?)");
                if ($history_stmt) {
                    $history_stmt->bind_param("ssidd", $product_id, $product['product_name'], $quantity, $price, $total_amount);
                    if ($history_stmt->execute()) {
                        // Deduct quantity from products table
                        $new_quantity = $product['quantity'] - $quantity;
                        $update_stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
                        if ($update_stmt) {
                            $update_stmt->bind_param("is", $new_quantity, $product_id);
                            if ($update_stmt->execute()) {
                                $message = "Successfully checked out $quantity units of {$product['product_name']}";
                            } else {
                                $error = "Error updating product quantity: " . $update_stmt->error;
                            }
                            $update_stmt->close();
                        } else {
                            $error = "Prepare failed for update: " . $conn->error;
                        }
                    } else {
                        $error = "Error recording checkout history: " . $history_stmt->error;
                    }
                    $history_stmt->close();
                } else {
                    $error = "Prepare failed: " . $conn->error;
                }
            } else {
                $error = "Insufficient quantity available. Only {$product['quantity']} units remaining.";
            }
        } else {
            $error = "Product not found for ID: $product_id";
        }
        $check_stmt->close();
    }
}

// Fetch recent checkout history
$history_query = "SELECT * FROM checkout_records ORDER BY checkout_date DESC LIMIT 5";
$history_result = $conn->query($history_query);
$recent_history = [];
if ($history_result) {
    while ($row = $history_result->fetch_assoc()) {
        $recent_history[] = $row;
    }
}

// Fetch all checkout history from checkout_records
$all_history_query = "SELECT * FROM checkout_records ORDER BY checkout_date DESC";
$all_history_result = $conn->query($all_history_query);
$all_history = [];
if ($all_history_result) {
    while ($row = $all_history_result->fetch_assoc()) {
        $all_history[] = $row;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/checkout.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
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

                    <li><a href="checkout.php" class="active">
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
            <div class="topbar">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Search">
                </div>
                <div class="user-profile"><span><?= htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) ?></span></div>
            </div>

            <div class="content">
                <div class="checkout-container">
                  <h2>Product Checkout</h2>
                
                  <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                   <?php endif; ?>
                
                   <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                  <?php endif; ?>

                <div class="checkout-methods">
                    <div class="barcode-scanner">
                        <h3>Scan Barcode</h3>
                        <div id="reader"></div>
                        <div id="result"></div>
                    </div>

                    <div class="manual-input">
                        <h3>Manual Input</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="product_id">Product ID:</label>
                                <input type="text" id="product_id" name="product_id" required>
                            </div>
                            <div class="form-group">
                                <label for="quantity">Quantity:</label>
                                <input type="number" id="quantity" name="quantity" min="1" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Checkout</button>
                        </form>
                    </div>
                </div>
            </div>
                <!-- HISTORY SECTION -->
                <br><br>
            <div class="history-container">
                <h3>Checkout History</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total Amount</th>
                                <th>Checkout Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_history as $checkout): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($checkout['id']); ?></td>
                                <td><?php echo htmlspecialchars($checkout['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($checkout['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($checkout['quantity']); ?></td>
                                <td>₱<?php echo number_format($checkout['price'], 2); ?></td>
                                <td>₱<?php echo number_format($checkout['total_amount'], 2); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($checkout['checkout_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
        function onScanSuccess(decodedText, decodedResult) {
            // Handle the scanned code
            document.getElementById('product_id').value = decodedText;
            document.getElementById('result').innerHTML = `Scanned: ${decodedText}`;
            
            // Optional: Play a success sound
            let audio = new Audio('data:audio/wav;base64,//uQRAAAAWMSLwUIYAAsYkXgoQwAEaYLWfkWgAI0wWs/ItAAAGDgYtAgAyN+QWaAAihwMWm4G8QQRDiMcCBcH3Cc+CDv/7xA4Tvh9Rz/y8QADBwMWgQAZG/ILNAARQ4GLTcDeIIIhxGOBAuD7hOfBB3/94gcJ3w+o5/5eIAIAAAVwWgQAVQ2ORaIQwEMAJiDg95G4nQL7mQVWI6GwRcfsZAcsKkJvxgxEjzFUgfHoSQ9Qq7KNwqHwuB13MA4a1q/DmBrHgPcmjiGoh//EwC5nGPEmS4RcfkVKOhJf+WOgoxJclFz3kgn//dBA+ya1GhurNn8zb//9NNutNuhz31f////9vt///z+IdAEAAAK4LQIAKobHItEIYCGAExBwe8jcToF9zIKrEdDYIuP2MgOWFSE34wYiR5iqQPj0JIeoVdlG4VD4XA67mAcNa1fhzA1jwHuTRxDUQ//iYBczjHiTJcIuPyKlHQkv/LHQUYkuSi57yQT//uggfZNajQ3Vmz+Zt//+mm3Wm3Q576v////+32///5/EOgAAADVghQAAAAA//uQZAUAB1WI0PZugAAAAAoQwAAAEk3nRd2qAAAAACiDgAAAAAAABCqEEQRLCgwpBGMlJkIz8jKhGvj4k6jzRnqasNKIeoh5gI7BJaC1A1AoNBjJgbyApVS4IDlZgDU5WUAxEKDNmmALHzZp0Fkz1FMTmGFl1FMEyodIavcCAUHDWrKAIA4aa2oCgILEBupZgHvAhEBcZ6joQBxS76AgccrFlczBvKLC0QI2cBoCFvfTDAo7eoOQInqDPBtvrDEZBNYN5xwNwxQRfw8ZQ5wQVLvO8OYU+mHvFLlDh05Mdg7BT6YrRPpCBznMB2r//xKJjyyOh+cImr2/4doscwD6neZjuZR4AgAABYAAAABy1xcdQtxYBYYZdifkUDgzzXaXn98Z0oi9ILU5mBjFANmRwlVJ3/6jYDAmxaiDG3/6xjQQCCKkRb/6kg/wW+kSJ5//rLobkLSiKmqP/0ikJuDaSaSf/6JiLYLEYnW/+kXg1WRVJL/9EmQ1YZIsv/6Qzwy5qk7/+tEU0nkls3/zIUMPKNX/6yZLf+kFgAfgGyLFAUwY');
            audio.play();
        }

        function onScanFailure(error) {
            // Handle scan failure
            console.warn(`Scan error: ${error}`);
        }

        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", 
            { 
                fps: 30,  // Increased frames per second for faster scanning
                qrbox: {width: 260, height: 160},  // Slightly wider scanning area
                aspectRatio: 1.0,
                showTorchButtonIfSupported: true,  // Add torch button if supported
                showZoomSliderIfSupported: true,   // Add zoom slider if supported
                defaultZoomValueIfSupported: 2,    // Default zoom level
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39
                ]
            }
        );
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
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
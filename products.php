<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
$message = '';

require 'includes/db.php';
$edit_mode = false;
$edit_product = [
    'product_id' => '',
    'product_name' => '',
    'packaging' => '',
    'prices' => '',
    'quantity' => ''
];

// Handle Edit button click: fetch product data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product_id'])) {
    require 'includes/db.php';
    $edit_id = $_POST['edit_product_id'];
    $edit_stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $edit_stmt->bind_param("s", $edit_id);
    $edit_stmt->execute();
    $result = $edit_stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $edit_product = $result->fetch_assoc();
        $edit_mode = true;
    }
    $edit_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Delete
    if (isset($_POST['delete_product_id'])) {
        $delete_id = $_POST['delete_product_id'];
        // Debug information
        error_log("Attempting to delete product with ID: " . $delete_id);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First delete related checkout records
            $delete_checkout_stmt = $conn->prepare("DELETE FROM checkout_records WHERE product_id = ?");
            if (!$delete_checkout_stmt) {
                throw new Exception("Error preparing checkout delete statement: " . $conn->error);
            }
            $delete_checkout_stmt->bind_param("s", $delete_id);
            if (!$delete_checkout_stmt->execute()) {
                throw new Exception("Error deleting checkout records: " . $delete_checkout_stmt->error);
            }
            $delete_checkout_stmt->close();

            // Then delete the product
            $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
            if (!$delete_stmt) {
                throw new Exception("Error preparing product delete statement: " . $conn->error);
            }
            $delete_stmt->bind_param("s", $delete_id);
            if (!$delete_stmt->execute()) {
                throw new Exception("Error deleting product: " . $delete_stmt->error);
            }
            $delete_stmt->close();

            // If we get here, commit the transaction
            $conn->commit();
            $message = 'Product and related records deleted successfully!';
            error_log("Product and related records deleted successfully");
            
            // Redirect to refresh the page
            header("Location: products.php?message=" . urlencode($message));
            exit();
        } catch (Exception $e) {
            // If there's an error, rollback the transaction
            $conn->rollback();
            error_log("Delete failed: " . $e->getMessage());
            $message = 'Error deleting product: ' . $e->getMessage();
        }
    }
    // Only process add/update if all required fields are present
    if (
        isset($_POST['product_id']) &&
        isset($_POST['product_name']) &&
        isset($_POST['packaging']) &&
        isset($_POST['prices']) &&
        isset($_POST['quantity'])
    ) {
        $product_id = $_POST['product_id'];
        $product_name = $_POST['product_name'];
        $packaging = $_POST['packaging'];
        $prices = $_POST['prices'];
        $quantity = $_POST['quantity'];

        // If in edit mode, update the product
        if (isset($_POST['edit_mode']) && $_POST['edit_mode'] === '1') {
            $update_stmt = $conn->prepare("UPDATE products SET product_name=?, packaging=?, prices=?, quantity=? WHERE product_id=?");
            $update_stmt->bind_param("ssdss", $product_name, $packaging, $prices, $quantity, $product_id);
            if ($update_stmt->execute()) {
                $message = 'Product updated successfully!';
            } else {
                $message = 'Error updating product: ' . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            // First check if product exists
            $check_stmt = $conn->prepare("SELECT quantity FROM products WHERE product_id = ?");
            $check_stmt->bind_param("s", $product_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if($result->num_rows > 0) {
                // Product exists, update quantity
                $row = $result->fetch_assoc();
                $new_quantity = $row['quantity'] + $quantity;
                
                $update_stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
                $update_stmt->bind_param("ds", $new_quantity, $product_id);
                
                if ($update_stmt->execute()) {
                    $message = 'Product quantity updated successfully!';
                } else {
                    $message = 'Error updating quantity: ' . $update_stmt->error;
                }
                $update_stmt->close();
            } else {
                // New product, insert it
                $insert_stmt = $conn->prepare("INSERT INTO products (product_id, product_name, packaging, prices, quantity) VALUES ( ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("sssdd", $product_id, $product_name, $packaging, $prices, $quantity);
                
                if ($insert_stmt->execute()) {
                    $message = 'New product added successfully!';
                } else {
                    $message = 'Error adding product: ' . $insert_stmt->error;
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    }
}

// Fetch all products
$result = $conn->query("SELECT * FROM products ORDER BY product_id");
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles\products.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
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

                <li><a href="products.php"class="active">
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

            <!-- CONTENT AREA -->
            <main class="col-md-12" style="margin-top: 10px;">
                <div class="main-content2">
                    <div class="row">
                        <div class="col-md-3">
                            <br> 
                            <h2>Add Product</h2>
                            <br>
                            <?php if ($message): ?>
                                <p style="color: <?= strpos($message, 'successfully') !== false ? 'green' : 'red' ?>;"> <?= $message ?> </p>
                            <?php endif; ?>
                            <form method="POST" action="">
                                <div class="barcode-scanner">
                                    <h3>Scan Barcode</h3>
                                    <div id="reader"></div>
                                    <div id="result"></div>
                                </div>
                                <br>
                                <label>Product ID: <input type="text" name="product_id" id="product_id" value="<?= htmlspecialchars($edit_product['product_id']) ?>" required <?= $edit_mode ? 'readonly' : '' ?>></label>
                                <label>Product Name: <input type="text" name="product_name" value="<?= htmlspecialchars($edit_product['product_name']) ?>" required></label>
                                <label>Packaging: <input type="text" name="packaging" value="<?= htmlspecialchars($edit_product['packaging']) ?>" required></label>
                                <label>Prices: <input type="number" step="0.01" name="prices" value="<?= htmlspecialchars($edit_product['prices']) ?>" required></label>
                                <label>Quantity: <input type="number" name="quantity" value="<?= htmlspecialchars($edit_product['quantity']) ?>" required></label>
                                <?php if ($edit_mode): ?>
                                    <input type="hidden" name="edit_mode" value="1">
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Update Product' : 'Add Product' ?></button>
                                <div class="input-group mb-3">
                                    <button style="width: 100%; border-radius: 5px; margin-right: 20px; display: none;" class="btn btn-primary" type="button" id="updateUserBtn" onclick="updateUser()">Update User</button>
                                </div>
                            </form>
                        </div>
                        <!-- Second Column: Product Table -->
                        <div class="col-md-9">
                            <div style="margin-top: 20px;" id="liveAlert"></div>
                            <div class="card">
                                <div class="card-body">
                                    <input type="text" style="width: 100%; height: 30px; margin-top: 20px; margin-bottom: 20px; border" class="form-control" id="search" placeholder="Search products..." aria-label="Search">
                                    <table id="productTable" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th scope="col">Product ID</th>
                                                <th scope="col">Product Name</th>
                                                <th scope="col">Packaging</th>
                                                <th scope="col">Price</th>
                                                <th scope="col">Quantity</th>
                                                <th scope="col">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="productTableBody" style="font-size: 13px;">
                                            <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['packaging']); ?></td>
                                                <td><?php echo htmlspecialchars($product['prices']); ?></td>
                                                <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                                                <td>
                                                    <button class="btn btn-warning btn-sm edit-btn" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>"><i class="mdi mdi-pencil"></i></button>
                                                    <button class="btn btn-danger btn-sm delete-btn" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>"><i class="mdi mdi-delete"></i></button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>     
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

    $(document).ready(function () {
        // Search/filter table rows
        $("#search").on("keyup", function () {
            let value = $(this).val().toLowerCase();
            $("#productTableBody tr").filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });

        // Delete button handler
        $(document).on('click', '.delete-btn', function () {
            if (confirm('Are you sure you want to delete this product?')) {
                var productId = $(this).data('product-id');
                var form = $('<form>', {
                    'method': 'POST',
                    'action': 'products.php'
                });
                form.append($('<input>', {
                    'name': 'delete_product_id',
                    'value': productId,
                    'type': 'hidden'
                }));
                $('body').append(form);
                form.submit();
            }
        });

        // Edit button handler
        $(document).on('click', '.edit-btn', function () {
            var productId = $(this).data('product-id');
            var form = $('<form>', {
                'method': 'POST',
                'action': 'products.php'
            });
            form.append($('<input>', {
                'name': 'edit_product_id',
                'value': productId,
                'type': 'hidden'
            }));
            $('body').append(form);
            form.submit();
        });

        // Initialize barcode scanner
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", 
            { 
                fps: 30,  // Increased frames per second for faster scanning
                qrbox: {width: 300, height: 200},  // Larger scanning area
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

        // Start the scanner
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    });
    </script>

    <!-- Hidden forms for delete and edit -->
    <form id="deleteForm" method="POST" action="" style="display:none;">
        <input type="hidden" name="delete_product_id" id="delete_product_id">
    </form>
    <form id="editForm" method="POST" action="" style="display:none;">
        <input type="hidden" name="edit_product_id" id="edit_product_id">
    </form>

    </body>
    </html>
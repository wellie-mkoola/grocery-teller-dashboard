<?php
// ============================================================
//  index.php — Grocery Teller Dashboard
//
//  PHP concepts practised:
//     session  — stores cart items across form submissions
//     require  — loads config.php for product prices
//     include  — loads header.php for the page title banner
//     isset    — safely checks POST data and session keys
//     header   — redirects after Buy to avoid form resubmit
// ============================================================

session_start();   // Open the session (must be before any HTML)
require('config.php'); // Load product list — stops if missing

// Make sure the cart exists in the session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// -------------------------------------------------------
// Handle "Add Item" button
// -------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'add') {

    $product  = $_POST['product']  ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);

    // Validate input using isset / array_key_exists
    if (array_key_exists($product, $products) && $quantity >= 1) {

        $price    = $products[$product];
        $subtotal = $price * $quantity;

        // Check if product already in cart — if yes, update quantity
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['name'] === $product) {
                $item['quantity'] += $quantity;
                $item['subtotal']  = $item['price'] * $item['quantity'];
                $found = true;
                break;
            }
        }
        unset($item); // always unset reference after foreach

        // New product — add fresh entry to cart
        if (!$found) {
            $_SESSION['cart'][] = [ 
                'name'     => $product,
                'price'    => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];
        }
    }

    // header() — redirect back to same page to clear POST data
    // This prevents the form re-submitting if user refreshes
    header("Location: index.php");
    exit();
}

// -------------------------------------------------------
// Handle "Buy" button — save to txt file and clear cart
// -------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'buy') {

    if (count($_SESSION['cart']) > 0) {

        // Calculate grand total
        $grandTotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $grandTotal += $item['subtotal'];
        }

        $dateTime = date("d M Y, H:i:s");

        // fopen "a" = append mode — adds to file without deleting old data
        $file = fopen("sales.txt", "a");
        if ($file) {
            fwrite($file, "=== SALE — $dateTime ===\n");
            foreach ($_SESSION['cart'] as $item) {
                fwrite($file,
                    $item['name'] . " | " .
                    "MWK " . number_format($item['price']) . " x " .
                    $item['quantity'] . " = " .
                    "MWK " . number_format($item['subtotal']) . "\n"
                );
            }
            fwrite($file, "TOTAL: MWK " . number_format($grandTotal) . "\n\n");
            fclose($file);
        }

        // Store a success message to show after redirect
        $_SESSION['message'] = "✅ Sale saved! Total: MWK " . number_format($grandTotal);

        // Clear the cart — purchase is done
        unset($_SESSION['cart']);
    }

    // Redirect to refresh the page cleanly
    header("Location: index.php");
    exit();
}

// -------------------------------------------------------
// Calculate running total from session cart
// -------------------------------------------------------
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['subtotal'];
}

// Read flash message if one exists
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // show once then delete
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grocery Teller Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>



<div class="dashboard">
    <?php include('header.php'); // include() loads the title banner ?>

    <!-- Flash message after buy -->
    <?php if ($message !== ''): ?>
        <div class="flash"><?= $message ?></div>
    <?php endif; ?>

    <p class="label">Select Item</p>

    <!--
        Single form — two buttons share it.
        Each button has a different value for "action"
        so PHP knows which button was clicked.
    -->
    <form method="POST">

        <!-- Product dropdown -->
        <select name="product" required>
            <option value="">--Select an item--</option>
            <?php foreach ($products as $name => $price): ?>
                <option value="<?= $name ?>">
                    <?= $name ?> mwk<?= number_format($price) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Quantity input -->
        <input
            type="number"
            name="quantity"
            placeholder="--Quantity--"
            min="1"
            max="99"
            required
        >

        <!-- Add Item button -->
        <button type="submit" name="action" value="add" class="btn-add">
            Add Item
        </button>

    </form>

    <!-- ===== Total row + Buy button ===== -->
    <div class="total-row">
        <span class="total-label"><b>Total Cost:</b></span>
        <span class="total-amount">MWK <?= number_format($total) ?></span>

        <!-- Buy is its own small form so it can POST action=buy -->
        <form method="POST" style="display:inline;">
            <button
                type="submit"
                name="action"
                value="buy"
                class="btn-buy"
                <?= count($_SESSION['cart']) === 0 ? 'disabled' : '' ?>
            >buy</button>
        </form>
    </div>

    <!-- ===== Items table ===== -->
    <table class="items-table">
        <thead>
            <tr>
                <th>ITEM</th>
                <th>UNIT-PRICE</th>
                <th>QUANTITY</th>
                <th>SUB-TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($_SESSION['cart']) === 0): ?>
                <tr>
                    <td colspan="4" class="empty-row">No items added yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                <tr>
                    <td><?= $item['name'] ?></td>
                    <td>MWK <?= number_format($item['price']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>MWK <?= number_format($item['subtotal']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</div><!-- end .dashboard -->

</body>
</html>

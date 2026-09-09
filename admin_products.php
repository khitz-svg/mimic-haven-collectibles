<?php

require_once 'auth.php';
require_once 'db.php';

requireLogin();

$userId = currentUserId();


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function peso(float $amount): string
{
    return '₱' . number_format($amount, 2);
}

function productImage(?string $image): string
{
    if (!$image) {
        return '';
    }

    $file = trim($image);

    $file = preg_replace(
        '/^.*?assets[\\\\\/]collections[\\\\\/]/i',
        '',
        $file
    );

    return 'assets/collections/' . rawurlencode($file);
}


/*
|--------------------------------------------------------------------------
| CHECK ADMIN ROLE
|--------------------------------------------------------------------------
*/

$roleStmt = $conn->prepare(
    "SELECT role
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$roleStmt->bind_param("i", $userId);
$roleStmt->execute();

$roleResult = $roleStmt->get_result();
$currentUser = $roleResult->fetch_assoc();

$roleStmt->close();

if (!$currentUser || $currentUser['role'] !== 'admin') {
    http_response_code(403);
    die('Access denied.');
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['admin_products_csrf'])) {
    $_SESSION['admin_products_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    $_SESSION['admin_products_csrf'];

$message = '';
$error = '';


/*
|--------------------------------------------------------------------------
| ALLOWED VALUES
|--------------------------------------------------------------------------
*/

$allowedCategories = [
    'Prize Figures',
    'Scale Figures',
    'Action Figures',
    'Statues'
];

$allowedAvailability = [
    'In Stock',
    'Pre-Order'
];

$allowedConditions = [
    'New',
    'Like New',
    'Used'
];


/*
|--------------------------------------------------------------------------
| HANDLE POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedToken =
        $_POST['csrf_token'] ?? '';

    if (!hash_equals(
        $csrfToken,
        $postedToken
    )) {

        $error =
            'Invalid security token. Please try again.';

    } else {

        $action =
            $_POST['action'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | ADD PRODUCT
        |--------------------------------------------------------------------------
        */

        if ($action === 'add') {

            $name =
                trim($_POST['name'] ?? '');

            $series =
                trim($_POST['series'] ?? '');

            $category =
                trim($_POST['category'] ?? '');

            $availability =
                trim($_POST['availability'] ?? '');

            $conditionStatus =
                trim($_POST['condition_status'] ?? '');

            $price =
                isset($_POST['price'])
                    ? (float)$_POST['price']
                    : 0;

            $stock =
                isset($_POST['stock'])
                    ? (int)$_POST['stock']
                    : 0;

            $image =
                trim($_POST['image'] ?? '');

            $description =
                trim($_POST['description'] ?? '');


            if ($name === '') {

                $error =
                    'Product name is required.';

            } elseif ($series === '') {

                $error =
                    'Series is required.';

            } elseif (
                !in_array(
                    $category,
                    $allowedCategories,
                    true
                )
            ) {

                $error =
                    'Invalid category.';

            } elseif (
                !in_array(
                    $availability,
                    $allowedAvailability,
                    true
                )
            ) {

                $error =
                    'Invalid availability.';

            } elseif (
                !in_array(
                    $conditionStatus,
                    $allowedConditions,
                    true
                )
            ) {

                $error =
                    'Invalid condition.';

            } elseif ($price < 0) {

                $error =
                    'Price cannot be negative.';

            } elseif ($stock < 0) {

                $error =
                    'Stock cannot be negative.';

            } else {

                $insertStmt = $conn->prepare(
                    "INSERT INTO products
                    (
                        name,
                        series,
                        category,
                        availability,
                        condition_status,
                        image,
                        description,
                        stock
                    )

                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $insertStmt->bind_param(
                    "sssssssi",
                    $name,
                    $series,
                    $category,
                    $availability,
                    $conditionStatus,
                    $image,
                    $description,
                    $stock
                );


                if ($insertStmt->execute()) {

                    $newProductId =
                        $insertStmt->insert_id;


                    /*
                    |--------------------------------------------------------------------------
                    | Add price if products table supports it
                    |--------------------------------------------------------------------------
                    */

                    /*
                     * Your current products table needs a price
                     * column because your storefront already uses prices.
                     *
                     * This update is performed separately below.
                     */

                    $priceUpdateStmt =
                        $conn->prepare(
                            "UPDATE products
                             SET price = ?
                             WHERE id = ?"
                        );

                    $priceUpdateStmt->bind_param(
                        "di",
                        $price,
                        $newProductId
                    );

                    $priceUpdateStmt->execute();
                    $priceUpdateStmt->close();


                    $message =
                        'Product added successfully.';

                } else {

                    $error =
                        'Failed to add product.';
                }

                $insertStmt->close();
            }


        /*
        |--------------------------------------------------------------------------
        | UPDATE PRODUCT
        |--------------------------------------------------------------------------
        */

        } elseif ($action === 'update') {

            $productId =
                filter_input(
                    INPUT_POST,
                    'product_id',
                    FILTER_VALIDATE_INT
                );

            $name =
                trim($_POST['name'] ?? '');

            $series =
                trim($_POST['series'] ?? '');

            $category =
                trim($_POST['category'] ?? '');

            $availability =
                trim($_POST['availability'] ?? '');

            $conditionStatus =
                trim($_POST['condition_status'] ?? '');

            $price =
                isset($_POST['price'])
                    ? (float)$_POST['price']
                    : 0;

            $stock =
                isset($_POST['stock'])
                    ? (int)$_POST['stock']
                    : 0;

            $image =
                trim($_POST['image'] ?? '');

            $description =
                trim($_POST['description'] ?? '');


            if (!$productId) {

                $error =
                    'Invalid product.';

            } elseif ($name === '') {

                $error =
                    'Product name is required.';

            } elseif ($series === '') {

                $error =
                    'Series is required.';

            } elseif (
                !in_array(
                    $category,
                    $allowedCategories,
                    true
                )
            ) {

                $error =
                    'Invalid category.';

            } elseif (
                !in_array(
                    $availability,
                    $allowedAvailability,
                    true
                )
            ) {

                $error =
                    'Invalid availability.';

            } elseif (
                !in_array(
                    $conditionStatus,
                    $allowedConditions,
                    true
                )
            ) {

                $error =
                    'Invalid condition.';

            } elseif ($price < 0) {

                $error =
                    'Price cannot be negative.';

            } elseif ($stock < 0) {

                $error =
                    'Stock cannot be negative.';

            } else {

                $updateStmt =
                    $conn->prepare(
                        "UPDATE products

                         SET
                            name = ?,
                            series = ?,
                            category = ?,
                            availability = ?,
                            condition_status = ?,
                            price = ?,
                            image = ?,
                            description = ?,
                            stock = ?

                         WHERE id = ?"
                    );


                $updateStmt->bind_param(
                    "sssssissii",
                    $name,
                    $series,
                    $category,
                    $availability,
                    $conditionStatus,
                    $price,
                    $image,
                    $description,
                    $stock,
                    $productId
                );


                if ($updateStmt->execute()) {

                    $message =
                        'Product updated successfully.';

                } else {

                    $error =
                        'Failed to update product.';
                }


                $updateStmt->close();
            }


        /*
        |--------------------------------------------------------------------------
        | DELETE PRODUCT
        |--------------------------------------------------------------------------
        */

        } elseif ($action === 'delete') {

            $productId =
                filter_input(
                    INPUT_POST,
                    'product_id',
                    FILTER_VALIDATE_INT
                );


            if (!$productId) {

                $error =
                    'Invalid product.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | Check existing references
                |--------------------------------------------------------------------------
                */

                $orderCheckStmt =
                    $conn->prepare(
                        "SELECT COUNT(*) AS total
                         FROM order_items
                         WHERE product_id = ?"
                    );

                $orderCheckStmt->bind_param(
                    "i",
                    $productId
                );

                $orderCheckStmt->execute();

                $orderCheckResult =
                    $orderCheckStmt->get_result();

                $orderCheck =
                    $orderCheckResult->fetch_assoc();

                $orderCheckStmt->close();


                $preorderCheckStmt =
                    $conn->prepare(
                        "SELECT COUNT(*) AS total
                         FROM preorders
                         WHERE product_id = ?"
                    );

                $preorderCheckStmt->bind_param(
                    "i",
                    $productId
                );

                $preorderCheckStmt->execute();

                $preorderCheckResult =
                    $preorderCheckStmt->get_result();

                $preorderCheck =
                    $preorderCheckResult->fetch_assoc();

                $preorderCheckStmt->close();


                if (
                    (int)$orderCheck['total'] > 0
                    ||
                    (int)$preorderCheck['total'] > 0
                ) {

                    $error =
                        'This product cannot be deleted because it is already used in an order or pre-order. You can edit it instead.';

                } else {

                    $deleteStmt =
                        $conn->prepare(
                            "DELETE FROM products
                             WHERE id = ?"
                        );

                    $deleteStmt->bind_param(
                        "i",
                        $productId
                    );


                    if ($deleteStmt->execute()) {

                        $message =
                            'Product deleted successfully.';

                    } else {

                        $error =
                            'Failed to delete product.';
                    }


                    $deleteStmt->close();
                }
            }


        } else {

            $error =
                'Invalid request.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET PRODUCTS
|--------------------------------------------------------------------------
*/

$productStmt = $conn->prepare(
    "SELECT
        id,
        name,
        series,
        category,
        availability,
        condition_status,
        price,
        stock,
        image,
        description,
        created_at

     FROM products

     ORDER BY id DESC"
);

$productStmt->execute();

$productResult =
    $productStmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage Products | Mimic Haven Collectibles
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        body {
            background: #182637;
            color: #fff;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .admin-container {
            max-width: 1450px;
            margin: 0 auto;
            padding: 35px 20px 70px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .admin-header h1 {
            margin: 0 0 8px;
        }

        .admin-header p {
            margin: 0;
            color: #aab2ba;
        }

        .admin-nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .admin-nav a {
            background: #9ADCF7;
            color: #182637;
            padding: 9px 13px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        .admin-nav a:hover {
            opacity: .85;
        }

        .message,
        .error {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message {
            background: #d9f7df;
            color: #1f5d2c;
        }

        .error {
            background: #ffdede;
            color: #8a1f1f;
        }

        .add-card {
            background: #191b1e;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .add-card h2 {
            margin-top: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #aab2ba;
            font-size: 12px;
            font-weight: 700;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            box-sizing: border-box;
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d0d5da;
            border-radius: 7px;
            background: #fff;
            color: #182637;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .primary-button {
            border: none;
            background: #9ADCF7;
            color: #182637;
            padding: 11px 17px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
        }

        .primary-button:hover {
            opacity: .85;
        }

        .table-wrapper {
            background: #fff;
            border-radius: 12px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1400px;
            border-collapse: collapse;
            color: #182637;
        }

        th {
            background: #9ADCF7;
            padding: 14px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
            font-size: 13px;
        }

        .product-cell {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .product-cell img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            background: #eef2f5;
            flex-shrink: 0;
        }

        .product-name {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .muted {
            color: #666;
            font-size: 12px;
            margin-top: 4px;
        }

        .tag {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            background: #eef2f5;
            color: #364552;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .actions details summary {
            cursor: pointer;
            display: inline-block;
            background: #182637;
            color: #fff;
            padding: 8px 11px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }

        .edit-form {
            margin-top: 10px;
            padding: 14px;
            background: #f3f5f7;
            border-radius: 8px;
            color: #182637;
        }

        .edit-form .form-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .danger-button {
            border: none;
            background: #ffdede;
            color: #8a1f1f;
            padding: 8px 11px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 700;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        @media (max-width: 900px) {

            .form-grid,
            .edit-form .form-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>


<body>


<div class="admin-container">


    <!-- HEADER -->

    <div class="admin-header">

        <div>

            <h1>
                Manage Products
            </h1>

            <p>
                Add, edit, classify, and maintain collectible products.
            </p>

        </div>


        <div class="admin-nav">

            <a href="admin.php">
                Dashboard
            </a>

            <a href="admin_orders.php">
                Orders
            </a>

            <a href="admin_preorders.php">
                Pre-Orders
            </a>

            <a href="admin_customers.php">
                Customers
            </a>

            <a href="account.php">
                My Account
            </a>

            <a href="logout.php">
                Logout
            </a>

        </div>

    </div>



    <!-- MESSAGES -->

    <?php if ($message): ?>

        <div class="message">
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>



    <!-- ADD PRODUCT -->

    <section class="add-card">

        <h2>
            Add Product
        </h2>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="add"
            >


            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Series
                    </label>

                    <input
                        type="text"
                        name="series"
                        maxlength="100"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Category
                    </label>

                    <select
                        name="category"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        <?php foreach (
                            $allowedCategories
                            as $category
                        ): ?>

                            <option
                                value="<?= e(
                                    $category
                                ) ?>"
                            >
                                <?= e(
                                    $category
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Availability
                    </label>

                    <select
                        name="availability"
                        required
                    >

                        <?php foreach (
                            $allowedAvailability
                            as $availability
                        ): ?>

                            <option
                                value="<?= e(
                                    $availability
                                ) ?>"
                            >
                                <?= e(
                                    $availability
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Condition
                    </label>

                    <select
                        name="condition_status"
                        required
                    >

                        <?php foreach (
                            $allowedConditions
                            as $condition
                        ): ?>

                            <option
                                value="<?= e(
                                    $condition
                                ) ?>"
                            >
                                <?= e(
                                    $condition
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Price (₱)
                    </label>

                    <input
                        type="number"
                        name="price"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Stock
                    </label>

                    <input
                        type="number"
                        name="stock"
                        min="0"
                        step="1"
                        value="0"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Image Path
                    </label>

                    <input
                        type="text"
                        name="image"
                        maxlength="255"
                        placeholder="example.webp"
                    >

                </div>


                <div class="form-group full">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        maxlength="5000"
                    ></textarea>

                </div>


                <div class="form-group full">

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Add Product
                    </button>

                </div>


            </div>

        </form>

    </section>



    <!-- PRODUCTS -->

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>
                        Product
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Availability
                    </th>

                    <th>
                        Condition
                    </th>

                    <th>
                        Price
                    </th>

                    <th>
                        Stock
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if (
                $productResult
                &&
                $productResult->num_rows > 0
            ): ?>


                <?php while (
                    $product =
                        $productResult->fetch_assoc()
                ): ?>


                    <tr>


                        <!-- PRODUCT -->

                        <td>

                            <div class="product-cell">


                                <?php
                                $img =
                                    productImage(
                                        $product[
                                            'image'
                                        ]
                                    );
                                ?>


                                <?php if ($img): ?>

                                    <img
                                        src="<?= e($img) ?>"
                                        alt="<?= e(
                                            $product[
                                                'name'
                                            ]
                                        ) ?>"
                                    >

                                <?php endif; ?>


                                <div>

                                    <div class="product-name">

                                        <?= e(
                                            $product[
                                                'name'
                                            ]
                                        ) ?>

                                    </div>


                                    <div class="muted">

                                        <?= e(
                                            $product[
                                                'series'
                                            ]
                                        ) ?>

                                    </div>


                                    <div class="muted">

                                        ID #<?= e(
                                            $product[
                                                'id'
                                            ]
                                        ) ?>

                                    </div>

                                </div>

                            </div>

                        </td>



                        <!-- CATEGORY -->

                        <td>

                            <span class="tag">

                                <?= e(
                                    $product[
                                        'category'
                                    ]
                                ) ?>

                            </span>

                        </td>



                        <!-- AVAILABILITY -->

                        <td>

                            <span class="tag">

                                <?= e(
                                    $product[
                                        'availability'
                                    ]
                                ) ?>

                            </span>

                        </td>



                        <!-- CONDITION -->

                        <td>

                            <?= e(
                                $product[
                                    'condition_status'
                                ]
                            ) ?>

                        </td>



                        <!-- PRICE -->

                        <td>

                            <strong>

                                <?= peso(
                                    (float)$product[
                                        'price'
                                    ]
                                ) ?>

                            </strong>

                        </td>



                        <!-- STOCK -->

                        <td>

                            <?= e(
                                $product[
                                    'stock'
                                ]
                            ) ?>

                        </td>



                        <!-- ACTIONS -->

                        <td>

                            <div class="actions">


                                <details>

                                    <summary>
                                        Edit Product
                                    </summary>


                                    <div class="edit-form">

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(
                                                    $csrfToken
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="update"
                                            >

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= e(
                                                    $product[
                                                        'id'
                                                    ]
                                                ) ?>"
                                            >


                                            <div class="form-grid">


                                                <div class="form-group">

                                                    <label>
                                                        Product Name
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="name"
                                                        value="<?= e(
                                                            $product[
                                                                'name'
                                                            ]
                                                        ) ?>"
                                                        maxlength="150"
                                                        required
                                                    >

                                                </div>


                                                <div class="form-group">

                                                    <label>
                                                        Series
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="series"
                                                        value="<?= e(
                                                            $product[
                                                                'series'
                                                            ]
                                                        ) ?>"
                                                        maxlength="100"
                                                        required
                                                    >

                                                </div>


                                                <div class="form-group">

                                                    <label>
                                                        Category
                                                    </label>

                                                    <select
                                                        name="category"
                                                        required
                                                    >

                                                        <?php foreach (
                                                            $allowedCategories
                                                            as $category
                                                        ): ?>

                                                            <option
                                                                value="<?= e(
                                                                    $category
                                                                ) ?>"
                                                                <?= $product[
                                                                    'category'
                                                                ] === $category
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >
                                                                <?= e(
                                                                    $category
                                                                ) ?>
                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </div>


                                                <div class="form-group">

                                                    <label>
                                                        Availability
                                                    </label>

                                                    <select
                                                        name="availability"
                                                        required
                                                    >

                                                        <?php foreach (
                                                            $allowedAvailability
                                                            as $availability
                                                        ): ?>

                                                            <option
                                                                value="<?= e(
                                                                    $availability
                                                                ) ?>"
                                                                <?= $product[
                                                                    'availability'
                                                                ] === $availability
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >
                                                                <?= e(
                                                                    $availability
                                                                ) ?>
                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </div>


                                                <div class="form-group">

                                                    <label>
                                                        Condition
                                                    </label>

                                                    <select
                                                        name="condition_status"
                                                        required
                                                    >

                                                        <?php foreach (
                                                            $allowedConditions
                                                            as $condition
                                                        ): ?>

                                                            <option
                                                                value="<?= e(
                                                                    $condition
                                                                ) ?>"
                                                                <?= $product[
                                                                    'condition_status'
                                                                ] === $condition
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >
                                                                <?= e(
                                                                    $condition
                                                                ) ?>
                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </div>


                                                <div class="form-group">

                                                    <label>
                                                        Price
                                                    </label>

                                                    <input
                                                        type="number"
                                                        name="price"
                                                        min="0"
                                                        step="0.01"
                                                        value="<?= e(
                                                            $product[
                                                                'price'
                                                            ]
                                                        ) ?>"
                                                        required
                                                    >

                                                </div>


                                                <div class="form-group">

                                                    <label>
                                                        Stock
                                                    </label>

                                                    <input
                                                        type="number"
                                                        name="stock"
                                                        min="0"
                                                        step="1"
                                                        value="<?= e(
                                                            $product[
                                                                'stock'
                                                            ]
                                                        ) ?>"
                                                        required
                                                    >

                                                </div>


                                                <div class="form-group">

                                                    <label>
                                                        Image Path
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="image"
                                                        maxlength="255"
                                                        value="<?= e(
                                                            $product[
                                                                'image'
                                                            ] ?? ''
                                                        ) ?>"
                                                    >

                                                </div>


                                                <div class="form-group full">

                                                    <label>
                                                        Description
                                                    </label>

                                                    <textarea
                                                        name="description"
                                                        maxlength="5000"
                                                    ><?= e(
                                                        $product[
                                                            'description'
                                                        ] ?? ''
                                                    ) ?></textarea>

                                                </div>


                                                <div class="form-group full">

                                                    <button
                                                        type="submit"
                                                        class="primary-button"
                                                    >
                                                        Save Changes
                                                    </button>

                                                </div>


                                            </div>

                                        </form>

                                    </div>

                                </details>



                                <!-- DELETE -->

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Delete this product? This cannot be undone.');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(
                                            $csrfToken
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= e(
                                            $product[
                                                'id'
                                            ]
                                        ) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="danger-button"
                                    >
                                        Delete
                                    </button>

                                </form>


                            </div>

                        </td>


                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="7"
                        class="empty"
                    >
                        No products found.
                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>


</div>


</body>

</html>
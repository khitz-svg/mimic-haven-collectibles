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
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function peso(float $amount): string
{
    return '₱' . number_format(
        $amount,
        2
    );
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

$roleStmt->bind_param(
    "i",
    $userId
);

$roleStmt->execute();

$roleResult =
    $roleStmt->get_result();

$currentUser =
    $roleResult->fetch_assoc();

$roleStmt->close();


if (
    !$currentUser ||
    $currentUser['role'] !== 'admin'
) {

    http_response_code(403);

    die(
        '<!DOCTYPE html>
        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <title>
                Access Denied | Mimic Haven Collectibles
            </title>

            <style>

                * {
                    box-sizing: border-box;
                }

                body {
                    margin: 0;
                    min-height: 100vh;

                    background: #182637;
                    color: #ffffff;

                    font-family:
                        Arial,
                        sans-serif;

                    display: flex;
                    align-items: center;
                    justify-content: center;

                    text-align: center;

                    padding: 30px;
                }

                .access-denied {
                    width: 100%;
                    max-width: 600px;
                }

                .access-denied h1 {
                    margin: 0 0 10px;

                    font-size: 64px;
                }

                .access-denied h2 {
                    margin: 0 0 18px;

                    font-size: 28px;
                }

                .access-denied p {
                    margin: 0 0 25px;

                    color: #aab2ba;

                    font-size: 15px;

                    line-height: 1.6;
                }

                .access-denied a {
                    color: #97DCF7;

                    text-decoration: none;

                    font-weight: 600;

                    font-size: 14px;
                }

                .access-denied a:hover {
                    text-decoration: underline;
                }

            </style>

        </head>

        <body>

            <div class="access-denied">

                <h1>
                    403
                </h1>

                <h2>
                    Access Denied
                </h2>

                <p>
                    You do not have administrator permission.
                </p>

                <a href="admin.php">
                    Back to Admin Dashboard
                </a>

            </div>

        </body>

        </html>'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    empty(
        $_SESSION['admin_products_csrf']
    )
) {

    $_SESSION['admin_products_csrf'] =
        bin2hex(
            random_bytes(32)
        );
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
    'Statues',
    'Others'
];

$allowedAvailability = [
    'In Stock',
    'Pre-Order'
];

$allowedConditions = [
    'MISB',
    'MIB',
    'New',
    'Like New',
    'Used'
];


/*
|--------------------------------------------------------------------------
| HANDLE POST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $postedToken =
        $_POST['csrf_token'] ?? '';


    if (
        !is_string($postedToken) ||
        !hash_equals(
            $csrfToken,
            $postedToken
        )
    ) {

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

        if (
            $action === 'add'
        ) {

            $name =
                trim(
                    $_POST['name'] ?? ''
                );

            $series =
                trim(
                    $_POST['series'] ?? ''
                );

            $category =
                trim(
                    $_POST['category'] ?? ''
                );

            $availability =
                trim(
                    $_POST['availability'] ?? ''
                );

            $conditionStatus =
                trim(
                    $_POST['condition_status'] ?? ''
                );

            $price =
                isset(
                    $_POST['price']
                )
                    ? (float)$_POST['price']
                    : 0;

            $stock =
                isset(
                    $_POST['stock']
                )
                    ? (int)$_POST['stock']
                    : 0;

            $image =
                trim(
                    $_POST['image'] ?? ''
                );

            $description =
                trim(
                    $_POST['description'] ?? ''
                );


            if (
                $name === ''
            ) {

                $error =
                    'Product name is required.';

            } elseif (
                $series === ''
            ) {

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

            } elseif (
                $price < 0
            ) {

                $error =
                    'Price cannot be negative.';

            } elseif (
                $stock < 0
            ) {

                $error =
                    'Stock cannot be negative.';

            } else {

                $insertStmt =
                    $conn->prepare(
                        "INSERT INTO products
                        (
                            name,
                            series,
                            category,
                            price,
                            condition_status,
                            availability,
                            image,
                            description,
                            stock
                        )

                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );


                $insertStmt->bind_param(
                    "sssdssssi",
                    $name,
                    $series,
                    $category,
                    $price,
                    $conditionStatus,
                    $availability,
                    $image,
                    $description,
                    $stock
                );


                if (
                    $insertStmt->execute()
                ) {

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

        } elseif (
            $action === 'update'
        ) {

            $productId =
                filter_input(
                    INPUT_POST,
                    'product_id',
                    FILTER_VALIDATE_INT
                );


            $name =
                trim(
                    $_POST['name'] ?? ''
                );

            $series =
                trim(
                    $_POST['series'] ?? ''
                );

            $category =
                trim(
                    $_POST['category'] ?? ''
                );

            $availability =
                trim(
                    $_POST['availability'] ?? ''
                );

            $conditionStatus =
                trim(
                    $_POST['condition_status'] ?? ''
                );

            $price =
                isset(
                    $_POST['price']
                )
                    ? (float)$_POST['price']
                    : 0;

            $stock =
                isset(
                    $_POST['stock']
                )
                    ? (int)$_POST['stock']
                    : 0;

            $image =
                trim(
                    $_POST['image'] ?? ''
                );

            $description =
                trim(
                    $_POST['description'] ?? ''
                );


            if (
                !$productId
            ) {

                $error =
                    'Invalid product.';

            } elseif (
                $name === ''
            ) {

                $error =
                    'Product name is required.';

            } elseif (
                $series === ''
            ) {

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

            } elseif (
                $price < 0
            ) {

                $error =
                    'Price cannot be negative.';

            } elseif (
                $stock < 0
            ) {

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
                    "sssssdssii",
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


                if (
                    $updateStmt->execute()
                ) {

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

        } elseif (
            $action === 'delete'
        ) {

            $productId =
                filter_input(
                    INPUT_POST,
                    'product_id',
                    FILTER_VALIDATE_INT
                );


            if (
                !$productId
            ) {

                $error =
                    'Invalid product.';

            } else {


                /*
                |--------------------------------------------------------------------------
                | CHECK ORDER REFERENCES
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


                /*
                |--------------------------------------------------------------------------
                | CHECK PRE-ORDER REFERENCES
                |--------------------------------------------------------------------------
                */

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


                    if (
                        $deleteStmt->execute()
                    ) {

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

$productStmt =
    $conn->prepare(
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

/* =========================================================
   ADMIN CONTAINER
========================================================= */

.admin-container {
    max-width: 1550px;

    margin: 0 auto;

    padding: 30px;

    box-sizing: border-box;
}


/* =========================================================
   ADMIN HEADER
========================================================= */

.admin-header {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        auto;

    align-items: center;

    gap: 30px;

    margin-bottom: 30px;
}


.admin-header-title {
    min-width: 0;
}


.admin-header-title h1 {
    margin: 0 0 8px;

    color: #ffffff;

    font-size: 30px;

    line-height: 1.15;
}


.admin-header-title p {
    margin: 0;

    color: #aab2ba;

    font-size: 13px;

    line-height: 1.6;
}


/* =========================================================
   ADMIN NAVIGATION
========================================================= */

.admin-nav {
    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 10px;

    flex-wrap: nowrap;
}


.admin-nav a {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 38px;

    padding:
        10px 16px;

    border-radius: 8px;

    background: #9ADCF7;

    color: #182637;

    font-family: Arial, sans-serif;

    font-size: 13px;

    font-weight: bold;

    text-decoration: none;

    white-space: nowrap;

    transition:
        opacity .2s ease,
        transform .2s ease;
}


.admin-nav a:hover {
    opacity: .85;

    transform:
        translateY(-1px);
}


.admin-nav a.active {
    background: #ffffff;

    color: #182637;
}


/* =========================================================
   MESSAGES
========================================================= */

.message,
.error {
    padding:
        14px 18px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 13px;

    line-height: 1.5;
}


.message {
    background: #d9f7df;

    color: #1f5d2c;
}


.error {
    background: #ffdede;

    color: #8a1f1f;
}


/* =========================================================
   ADD PRODUCT
========================================================= */

.add-card {
    background: #191b1e;

    border:
        1px solid
        rgba(255,255,255,.07);

    border-radius: 12px;

    padding: 24px;

    margin-bottom: 30px;
}


.add-card h2 {
    margin:
        0 0 20px;

    color: #ffffff;

    font-family:
        Montserrat,
        sans-serif;
}


.form-grid {
    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );

    gap: 16px;
}


.form-group {
    display: flex;

    flex-direction: column;

    gap: 6px;
}


.form-group.full {
    grid-column:
        1 / -1;
}


.form-group label {
    color: #aab2ba;

    font-size: 12px;

    font-weight: 700;
}


.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;

    box-sizing: border-box;

    padding:
        10px 12px;

    border:
        1px solid #d0d5da;

    border-radius: 7px;

    background: #ffffff;

    color: #182637;

    font-family:
        Arial,
        sans-serif;
}


.form-group textarea {
    min-height: 90px;

    resize: vertical;
}


.primary-button {
    border: none;

    background: #9ADCF7;

    color: #182637;

    padding:
        11px 17px;

    border-radius: 8px;

    cursor: pointer;

    font-weight: 700;
}


.primary-button:hover {
    opacity: .85;
}


/* =========================================================
   PRODUCT TABLE
========================================================= */

.table-wrapper {
    background: #ffffff;

    border-radius: 12px;

    overflow-x: auto;
}


.table-wrapper table {
    width: 100%;

    min-width: 1400px;

    border-collapse: collapse;

    color: #182637;
}


.table-wrapper th {
    background: #9ADCF7;

    padding: 14px;

    text-align: left;

    font-size: 12px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .5px;

    white-space: nowrap;
}


.table-wrapper td {
    padding: 14px;

    border-bottom:
        1px solid #dddddd;

    vertical-align: top;

    font-size: 13px;
}


.table-wrapper tr:last-child td {
    border-bottom: none;
}


/* =========================================================
   PRODUCT
========================================================= */

.product-cell {
    display: flex;

    align-items: flex-start;

    gap: 12px;
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
    margin-bottom: 4px;

    color: #182637;

    font-weight: 700;
}


.muted {
    margin-top: 4px;

    color: #666666;

    font-size: 12px;

    line-height: 1.5;
}


.tag {
    display: inline-block;

    padding:
        5px 9px;

    border-radius: 20px;

    background: #eef2f5;

    color: #364552;

    font-size: 11px;

    font-weight: 700;
}


/* =========================================================
   ACTIONS
========================================================= */

.actions {
    display: flex;

    flex-direction: column;

    gap: 8px;
}


.actions details summary {
    display: inline-block;

    cursor: pointer;

    padding:
        8px 11px;

    border-radius: 6px;

    background: #182637;

    color: #ffffff;

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
    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );
}


.danger-button {
    border: none;

    background: #ffdede;

    color: #8a1f1f;

    padding:
        8px 11px;

    border-radius: 6px;

    cursor: pointer;

    font-weight: 700;
}


.danger-button:hover {
    opacity: .85;
}


/* =========================================================
   EMPTY
========================================================= */

.empty {
    text-align: center;

    padding: 40px;

    color: #666666;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .admin-header {
        grid-template-columns: 1fr;
    }


    .admin-nav {
        justify-content: flex-start;

        flex-wrap: wrap;
    }

}


@media (max-width: 900px) {

    .form-grid,
    .edit-form .form-grid {
        grid-template-columns: 1fr;
    }

}


@media (max-width: 600px) {

    .admin-container {
        width: 100%;

        padding:
            20px 15px 60px;
    }


    .admin-nav {
        gap: 7px;
    }


    .admin-nav a {
        min-height: 36px;

        padding:
            9px 12px;

        font-size: 11px;
    }

}

    </style>

</head>


<body>


<div class="admin-container">


    <!-- =====================================================
         ADMIN HEADER
    ====================================================== -->

    <div class="admin-header">


        <div class="admin-header-title">

            <h1>
                Manage Products
            </h1>


            <p>
                Add, edit, classify, and maintain collectible products.
            </p>

        </div>


        <nav
            class="admin-nav"
            aria-label="Admin navigation"
        >

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


            <a
                href="admin_products.php"
                class="active"
            >
                Products
            </a>


            <a href="logout.php">
                Logout
            </a>

        </nav>


    </div>



    <!-- =====================================================
         MESSAGES
    ====================================================== -->

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



    <!-- =====================================================
         ADD PRODUCT
    ====================================================== -->

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


                <!-- NAME -->

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



                <!-- SERIES -->

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



                <!-- CATEGORY -->

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



                <!-- AVAILABILITY -->

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



                <!-- CONDITION -->

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



                <!-- PRICE -->

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



                <!-- STOCK -->

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



                <!-- IMAGE -->

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



                <!-- DESCRIPTION -->

                <div class="form-group full">

                    <label>
                        Description
                    </label>


                    <textarea
                        name="description"
                        maxlength="5000"
                    ></textarea>

                </div>



                <!-- SUBMIT -->

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



    <!-- =====================================================
         PRODUCT TABLE
    ====================================================== -->

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

                            <div
                                class="product-cell"
                            >


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
                                        src="<?= e(
                                            $img
                                        ) ?>"
                                        alt="<?= e(
                                            $product[
                                                'name'
                                            ]
                                        ) ?>"
                                    >

                                <?php endif; ?>


                                <div>


                                    <div
                                        class="product-name"
                                    >

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

                                        ID #

                                        <?= e(
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


                                <!-- EDIT -->

                                <details>


                                    <summary>
                                        Edit Product
                                    </summary>


                                    <div class="edit-form">


                                        <form
                                            method="POST"
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


                                            <div
                                                class="form-grid"
                                            >


                                                <!-- NAME -->

                                                <div
                                                    class="form-group"
                                                >

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



                                                <!-- SERIES -->

                                                <div
                                                    class="form-group"
                                                >

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



                                                <!-- CATEGORY -->

                                                <div
                                                    class="form-group"
                                                >

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



                                                <!-- AVAILABILITY -->

                                                <div
                                                    class="form-group"
                                                >

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



                                                <!-- CONDITION -->

                                                <div
                                                    class="form-group"
                                                >

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



                                                <!-- PRICE -->

                                                <div
                                                    class="form-group"
                                                >

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



                                                <!-- STOCK -->

                                                <div
                                                    class="form-group"
                                                >

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



                                                <!-- IMAGE -->

                                                <div
                                                    class="form-group"
                                                >

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



                                                <!-- DESCRIPTION -->

                                                <div
                                                    class="
                                                        form-group
                                                        full
                                                    "
                                                >

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



                                                <!-- SAVE -->

                                                <div
                                                    class="
                                                        form-group
                                                        full
                                                    "
                                                >

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
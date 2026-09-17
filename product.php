```php
<?php

/* =========================================================
   MIMIC HAVEN - PRODUCT DETAILS PAGE
========================================================= */

require_once 'auth.php';
require_once 'db.php';

$cartStorageKey = currentUserId()
    ? 'mimicHavenCart_' . currentUserId()
    : 'mimicHavenGuestCart';


/* =========================================================
   ASSET FUNCTIONS
========================================================= */

function asset(string $name): string {

    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {

        $assetFile =
            __DIR__ . "/assets/{$name}.{$ext}";

        if (file_exists($assetFile)) {
            return "assets/{$name}.{$ext}";
        }


        $rootFile =
            __DIR__ . "/{$name}.{$ext}";

        if (file_exists($rootFile)) {
            return "{$name}.{$ext}";
        }

    }

    return "";
}


function icon(string $name): string {

    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {

        $file =
            __DIR__ . "/assets/icons/{$name}.{$ext}";

        if (file_exists($file)) {
            return "assets/icons/{$name}.{$ext}";
        }

    }

    return "";
}


function e(string $value): string {

    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );

}


function peso(int $price): string {

    return '₱' . number_format($price);

}


/* =========================================================
   GET PRODUCT ID
========================================================= */

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$productId || $productId < 1) {

    http_response_code(404);

    die(
        '<!doctype html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Product Not Found | Mimic Haven Collectibles</title>
            <style>
                body {
                    margin: 0;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #07090c;
                    color: #ffffff;
                    font-family: Arial, sans-serif;
                    text-align: center;
                }

                .error-box {
                    max-width: 500px;
                    padding: 40px;
                }

                h1 {
                    font-size: 64px;
                    margin: 0 0 10px;
                }

                h2 {
                    margin: 0 0 15px;
                }

                p {
                    color: #aab2ba;
                }

                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 12px 20px;
                    background: #97DCF7;
                    color: #071018;
                    text-decoration: none;
                    font-weight: 700;
                    border-radius: 8px;
                }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>404</h1>
                <h2>Product Not Found</h2>
                <p>The product you are looking for does not exist.</p>
                <a href="collection.php">Back to Collection</a>
            </div>
        </body>
        </html>'
    );

}


/* =========================================================
   LOAD PRODUCT FROM MYSQL
   STOCK IS NOW INCLUDED
========================================================= */

$stmt = $conn->prepare(
    "SELECT
        id,
        name,
        series,
        category,
        price,
        condition_status,
        availability,
        image,
        description,
        stock
     FROM products
     WHERE id = ?
     LIMIT 1"
);


if (!$stmt) {

    http_response_code(500);

    die('Unable to load product.');

}


$stmt->bind_param(
    "i",
    $productId
);


if (!$stmt->execute()) {

    $stmt->close();

    http_response_code(500);

    die('Unable to load product.');

}


$result =
    $stmt->get_result();


$dbProduct =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   PRODUCT NOT FOUND
========================================================= */

if (!$dbProduct) {

    http_response_code(404);

    die(
        '<!doctype html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Product Not Found | Mimic Haven Collectibles</title>
            <style>
                body {
                    margin: 0;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #07090c;
                    color: #ffffff;
                    font-family: Arial, sans-serif;
                    text-align: center;
                }

                .error-box {
                    max-width: 500px;
                    padding: 40px;
                }

                h1 {
                    font-size: 64px;
                    margin: 0 0 10px;
                }

                h2 {
                    margin: 0 0 15px;
                }

                p {
                    color: #aab2ba;
                }

                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 12px 20px;
                    background: #97DCF7;
                    color: #071018;
                    text-decoration: none;
                    font-weight: 700;
                    border-radius: 8px;
                }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>404</h1>
                <h2>Product Not Found</h2>
                <p>The product you are looking for does not exist.</p>
                <a href="collection.php">Back to Collection</a>
            </div>
        </body>
        </html>'
    );

}


/* =========================================================
   BUILD PRODUCT DATA
========================================================= */

$product = [
    'id' =>
        (int)$dbProduct['id'],

    'name' =>
        (string)$dbProduct['name'],

    'series' =>
        (string)$dbProduct['series'],

    'category' =>
        (string)$dbProduct['category'],

    'price' =>
        (int)$dbProduct['price'],

    'condition' =>
        (string)$dbProduct['condition_status'],

    'availability' =>
        (string)$dbProduct['availability'],

    'image' =>
        (string)$dbProduct['image'],

    'description' =>
        (string)($dbProduct['description'] ?? ''),

    'stock' =>
        max(0, (int)$dbProduct['stock'])
];


/* =========================================================
   PRODUCT STATE
========================================================= */

$isInStock =
    $product['availability'] === 'In Stock'
    && $product['stock'] > 0;


$isOutOfStock =
    $product['availability'] === 'In Stock'
    && $product['stock'] <= 0;


$isPreOrder =
    $product['availability'] === 'Pre-Order';


$maxQuantity =
    $isInStock
        ? min(20, $product['stock'])
        : 1;


?>


<!doctype html>

<html lang="en">


<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($product['name']) ?>
        | Mimic Haven Collectibles
    </title>


    <meta
        name="description"
        content="<?= e($product['name']) ?> — <?= e($product['series']) ?> available from Mimic Haven Collectibles."
    >


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >


    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- =========================================================
     SAME HEADER
========================================================= -->

<header
    class="site-header"
    id="home"
>

    <div class="header-inner">


        <a
            class="brand"
            href="index.php"
            aria-label="Mimic Haven Collectibles"
        >

            <?php if (asset('logo')): ?>

                <img
                    class="brand-logo"
                    src="<?= asset('logo') ?>"
                    alt="Mimic Haven Collectibles"
                >

            <?php else: ?>

                <div class="brand-fallback">

                    MIMIC HAVEN

                    <span>
                        COLLECTIBLES
                    </span>

                </div>

            <?php endif; ?>

        </a>



        <nav
            class="nav"
            id="main-nav"
            aria-label="Primary navigation"
        >


            <a href="index.php">
                Home
            </a>


            <a href="collection.php">
                Collection
            </a>


            <a href="preorder.php">
                Pre-Orders
            </a>


            <a href="index.php#about">
                About
            </a>


            <a href="index.php#contact">
                Contact
            </a>



            <a
                class="nav-icon"
                href="collection.php#collection-search"
                aria-label="Search"
            >

                <?php if (icon('search')): ?>

                    <img
                        src="<?= icon('search') ?>"
                        alt="Search"
                    >

                <?php endif; ?>

            </a>



            <a
                class="nav-icon cart-button"
                href="cart.php"
                aria-label="Cart"
            >

                <?php if (icon('pre-order')): ?>

                    <img
                        src="<?= icon('pre-order') ?>"
                        alt="Cart"
                    >

                <?php endif; ?>


                <span id="cart-count">
                    0
                </span>

            </a>



            <a
                class="browse-btn"
                href="collection.php"
            >
                Browse Figures
            </a>


        </nav>



        <button
            class="menu-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="main-nav"
        >
            ☰
        </button>


    </div>

</header>



<!-- =========================================================
     PRODUCT PAGE
========================================================= -->

<main class="product-page">


    <!-- =====================================================
         BREADCRUMB
    ====================================================== -->

    <div class="product-breadcrumb">

        <a href="index.php">
            Home
        </a>


        <span>
            ›
        </span>


        <a href="collection.php">
            Collection
        </a>


        <span>
            ›
        </span>


        <strong>
            <?= e($product['name']) ?>
        </strong>

    </div>



    <!-- =====================================================
         PRODUCT DETAIL
    ====================================================== -->

    <section class="product-detail">


        <!-- =================================================
             PRODUCT IMAGE
        ================================================== -->

        <div class="product-gallery">


            <div class="product-main-image">


                <img
                    src="assets/collections/<?= rawurlencode($product['image']) ?>"
                    alt="<?= e($product['name']) ?>"
                >


                <span class="product-condition-badge">

                    <?= e($product['condition']) ?>

                </span>


            </div>



            <div class="product-thumbnail">

                <img
                    src="assets/collections/<?= rawurlencode($product['image']) ?>"
                    alt="<?= e($product['name']) ?>"
                >

            </div>


        </div>



        <!-- =================================================
             PRODUCT INFO
        ================================================== -->

        <div class="product-detail-info">


            <span class="product-detail-series">

                <?= e($product['series']) ?>

            </span>



            <h1>

                <?= e($product['name']) ?>

            </h1>



            <div class="product-detail-price">

                <?= peso($product['price']) ?>

            </div>



            <!-- =================================================
                 AVAILABILITY / STOCK
            ================================================== -->

            <div class="product-detail-status">


                <?php if ($isInStock): ?>

                    <span class="instock">

                        <?= $product['stock'] ?>

                        <?= $product['stock'] === 1 ? 'Available' : 'Available' ?>

                    </span>


                <?php elseif ($isOutOfStock): ?>

                    <span class="out-of-stock">

                        Out of Stock

                    </span>


                <?php else: ?>

                    <span class="preorder">

                        Pre-Order

                    </span>

                <?php endif; ?>


            </div>



            <div class="product-detail-divider"></div>



            <p class="product-description">

                <?= e($product['description']) ?>

            </p>



            <!-- =================================================
                 PRODUCT SPECIFICATIONS
            ================================================== -->

            <div class="product-specs">


                <div class="product-spec">

                    <span>
                        Category
                    </span>

                    <strong>

                        <?= e($product['category']) ?>

                    </strong>

                </div>



                <div class="product-spec">

                    <span>
                        Condition
                    </span>

                    <strong>

                        <?= e($product['condition']) ?>

                    </strong>

                </div>



                <div class="product-spec">

                    <span>
                        Availability
                    </span>

                    <strong>

                        <?= e($product['availability']) ?>

                    </strong>

                </div>



                <?php if ($product['availability'] === 'In Stock'): ?>

                    <div class="product-spec">

                        <span>
                            Stock Available
                        </span>

                        <strong>

                            <?= $product['stock'] ?>

                            <?= $product['stock'] === 1
                                ? 'piece'
                                : 'pieces'
                            ?>

                        </strong>

                    </div>

                <?php endif; ?>


            </div>



            <!-- =================================================
                 QUANTITY / PURCHASE
            ================================================== -->

            <?php if ($isInStock): ?>


                <div class="product-purchase-row">


                    <div class="quantity-control">


                        <button
                            type="button"
                            id="quantityMinus"
                            aria-label="Decrease quantity"
                        >
                            −
                        </button>



                        <input
                            type="number"
                            id="productQuantity"
                            value="1"
                            min="1"
                            max="<?= $maxQuantity ?>"
                        >



                        <button
                            type="button"
                            id="quantityPlus"
                            aria-label="Increase quantity"
                        >
                            +
                        </button>


                    </div>



                    <button
                        type="button"
                        class="product-add-cart"
                        id="addToCart"
                        data-product-id="<?= $product['id'] ?>"
                        data-product-name="<?= e($product['name']) ?>"
                        data-product-price="<?= $product['price'] ?>"
                        data-product-stock="<?= $product['stock'] ?>"
                    >

                        Add to Cart

                    </button>


                </div>


                <small
                    style="
                        display:block;
                        margin-top:10px;
                        color:#aab2ba;
                    "
                >

                    <?= $product['stock'] ?>

                    <?= $product['stock'] === 1
                        ? 'piece'
                        : 'pieces'
                    ?>

                    currently available.

                </small>


            <?php elseif ($isOutOfStock): ?>


                <div class="product-purchase-row">


                    <div class="quantity-control">


                        <button
                            type="button"
                            disabled
                        >
                            −
                        </button>


                        <input
                            type="number"
                            value="0"
                            disabled
                        >


                        <button
                            type="button"
                            disabled
                        >
                            +
                        </button>


                    </div>



                    <button
                        type="button"
                        class="product-add-cart"
                        disabled
                        style="
                            opacity:0.5;
                            cursor:not-allowed;
                        "
                    >

                        Out of Stock

                    </button>


                </div>


                <small
                    style="
                        display:block;
                        margin-top:10px;
                        color:#aab2ba;
                    "
                >

                    This figure is currently unavailable.

                </small>


            <?php endif; ?>



            <!-- =================================================
                 PRE-ORDER
            ================================================== -->

            <?php if ($isPreOrder): ?>

                <a
                    class="product-preorder-button"
                    href="preorder.php"
                >

                    Reserve as Pre-Order
                    →

                </a>


                <small
                    style="
                        display:block;
                        margin-top:10px;
                        color:#aab2ba;
                    "
                >

                    This item is available through pre-order
                    reservation.

                </small>


            <?php endif; ?>



            <!-- =================================================
                 TRUST
            ================================================== -->

            <div class="product-trust">


                <div>

                    <strong>
                        100% Authentic
                    </strong>

                    <span>
                        Original &amp; official products
                    </span>

                </div>



                <div>

                    <strong>
                        Packed with Care
                    </strong>

                    <span>
                        Secure packaging guaranteed
                    </span>

                </div>



                <div>

                    <strong>
                        Collector Focused
                    </strong>

                    <span>
                        Made for collectors
                    </span>

                </div>


            </div>


        </div>

    </section>



    <!-- =====================================================
         PRODUCT INFORMATION
    ====================================================== -->

    <section class="product-information">


        <div class="product-information-tabs">


            <button
                class="product-tab active"
                type="button"
            >
                Product Details
            </button>


            <button
                class="product-tab"
                type="button"
            >
                Condition Guide
            </button>


            <button
                class="product-tab"
                type="button"
            >
                Ordering Information
            </button>


        </div>



        <div class="product-information-content">


            <!-- PRODUCT DETAILS -->

            <div class="product-info-block active">


                <h2>
                    Product Details
                </h2>


                <p>

                    This collectible is part of the Mimic Haven
                    curated collection. Product information shown
                    on this page is intended to help collectors
                    review the item before adding it to their order.

                </p>



                <div class="product-detail-list">


                    <div>

                        <span>
                            Figure
                        </span>

                        <strong>
                            <?= e($product['name']) ?>
                        </strong>

                    </div>



                    <div>

                        <span>
                            Series
                        </span>

                        <strong>
                            <?= e($product['series']) ?>
                        </strong>

                    </div>



                    <div>

                        <span>
                            Category
                        </span>

                        <strong>
                            <?= e($product['category']) ?>
                        </strong>

                    </div>



                    <div>

                        <span>
                            Condition
                        </span>

                        <strong>
                            <?= e($product['condition']) ?>
                        </strong>

                    </div>



                    <div>

                        <span>
                            Availability
                        </span>

                        <strong>
                            <?= e($product['availability']) ?>
                        </strong>

                    </div>



                    <?php if ($product['availability'] === 'In Stock'): ?>

                        <div>

                            <span>
                                Stock Available
                            </span>

                            <strong>

                                <?= $product['stock'] ?>

                                <?= $product['stock'] === 1
                                    ? 'piece'
                                    : 'pieces'
                                ?>

                            </strong>

                        </div>

                    <?php endif; ?>


                </div>


            </div>



            <!-- CONDITION GUIDE -->

            <div class="product-info-block">


                <h2>
                    Condition Guide
                </h2>


                <p>

                    Mimic Haven uses standardized condition labels
                    to make the state of each collectible easier
                    to understand.

                </p>



                <div class="product-condition-list">


                    <div>

                        <strong>
                            MISB
                        </strong>

                        <span>
                            Mint in Sealed Box
                        </span>

                    </div>



                    <div>

                        <strong>
                            MIB
                        </strong>

                        <span>
                            Mint in Box
                        </span>

                    </div>



                    <div>

                        <strong>
                            BIB
                        </strong>

                        <span>
                            Box Opened
                        </span>

                    </div>



                    <div>

                        <strong>
                            LOOSE
                        </strong>

                        <span>
                            No Original Packaging
                        </span>

                    </div>


                </div>


            </div>



            <!-- ORDERING INFORMATION -->

            <div class="product-info-block">


                <h2>
                    Ordering Information
                </h2>


                <p>

                    Please review the product condition,
                    availability, and applicable Mimic Haven
                    terms before completing your order.

                </p>


                <?php if ($isInStock): ?>

                    <p>

                        This figure currently has
                        <strong>
                            <?= $product['stock'] ?>
                        </strong>

                        <?= $product['stock'] === 1
                            ? 'piece'
                            : 'pieces'
                        ?>

                        available for purchase.

                    </p>


                <?php elseif ($isOutOfStock): ?>

                    <p>

                        This figure is currently
                        <strong>
                            out of stock
                        </strong>.

                    </p>


                <?php else: ?>

                    <p>

                        This figure is currently available
                        through the Mimic Haven pre-order system.

                    </p>

                <?php endif; ?>



                <a
                    href="preorder.php"
                    class="product-information-link"
                >
                    View Pre-Order Information →
                </a>


            </div>


        </div>


    </section>



    <!-- =====================================================
         BACK TO COLLECTION
    ====================================================== -->

    <div class="back-to-collection">


        <a
            class="btn outline"
            href="collection.php"
        >

            ← Back to Collection

        </a>


    </div>


</main>



<!-- =========================================================
     SAME FOOTER
========================================================= -->

<footer id="contact">


    <div class="footer-grid">


        <div>


            <?php if (asset('logo')): ?>

                <img
                    class="footer-logo"
                    src="<?= asset('logo') ?>"
                    alt="Mimic Haven Collectibles"
                >

            <?php endif; ?>


            <p>

                A sanctuary for anime collectors, offering authentic
                figures, trusted pre-orders, and a premium collecting
                experience inspired by the journey behind every masterpiece.

            </p>


        </div>



        <div class="footer-links">


            <h4>
                NAVIGATE
            </h4>


            <a href="index.php">
                Home
            </a>


            <a href="collection.php">
                Collection
            </a>


            <a href="preorder.php">
                Pre-Orders
            </a>


            <a href="index.php#about">
                About
            </a>


            <a href="index.php#contact">
                Contact
            </a>


        </div>



        <div class="footer-links">


            <h4>
                CONNECT
            </h4>


            <a href="mailto:mimichvn.collectibles@gmail.com">
                mimichvn.collectibles@gmail.com
            </a>


            <a href="tel:+639657457775">
                +63 965 745 7775
            </a>



            <div class="socials">


                <a
                    href="#"
                    aria-label="Facebook"
                >

                    <?php if (icon('facebook')): ?>

                        <img
                            src="<?= icon('facebook') ?>"
                            alt="Facebook"
                        >

                    <?php endif; ?>

                </a>



                <a
                    href="#"
                    aria-label="Instagram"
                >

                    <?php if (icon('instagram')): ?>

                        <img
                            src="<?= icon('instagram') ?>"
                            alt="Instagram"
                        >

                    <?php endif; ?>

                </a>


            </div>


        </div>


    </div>



    <div class="footer-bottom">


        <span>

            © 2026 Mimic Haven Collectibles.
            All Rights Reserved.

        </span>


        <span>

            Crafted with precision &amp; passion.

        </span>


    </div>


</footer>



<div
    id="toast"
    class="toast"
    role="status"
    aria-live="polite"
></div>



<script src="script.js"></script>



<script>

/* =========================================================
   ACCOUNT-SPECIFIC CART KEY
========================================================= */

const cartStorageKey =
    <?= json_encode($cartStorageKey) ?>;


/* =========================================================
   PRODUCT PAGE - STOCK
========================================================= */

const productStock =
    <?= (int)$product['stock'] ?>;


const productAvailability =
    <?= json_encode($product['availability']) ?>;


const productIsInStock =
    productAvailability === 'In Stock'
    && productStock > 0;


/* =========================================================
   QUANTITY ELEMENTS
========================================================= */

const quantityInput =
    document.getElementById(
        'productQuantity'
    );


const quantityMinus =
    document.getElementById(
        'quantityMinus'
    );


const quantityPlus =
    document.getElementById(
        'quantityPlus'
    );


/* =========================================================
   QUANTITY LIMIT
========================================================= */

function updateProductQuantity() {

    if (!quantityInput || !productIsInStock) {
        return;
    }


    let quantity =
        Number(quantityInput.value);


    if (!Number.isFinite(quantity)) {
        quantity = 1;
    }


    quantity =
        Math.floor(quantity);


    quantity =
        Math.max(
            1,
            Math.min(
                productStock,
                20,
                quantity
            )
        );


    quantityInput.value =
        quantity;

}


quantityMinus?.addEventListener(
    'click',
    () => {

        if (!productIsInStock) {
            return;
        }


        let quantity =
            Number(quantityInput.value) || 1;


        quantity =
            Math.max(
                1,
                quantity - 1
            );


        quantityInput.value =
            quantity;

    }
);


quantityPlus?.addEventListener(
    'click',
    () => {

        if (!productIsInStock) {
            return;
        }


        let quantity =
            Number(quantityInput.value) || 1;


        quantity =
            Math.min(
                productStock,
                20,
                quantity + 1
            );


        quantityInput.value =
            quantity;

    }
);


quantityInput?.addEventListener(
    'input',
    updateProductQuantity
);


quantityInput?.addEventListener(
    'change',
    updateProductQuantity
);


/* =========================================================
   PRODUCT TABS
========================================================= */

const productTabs =
    document.querySelectorAll(
        '.product-tab'
    );


const productBlocks =
    document.querySelectorAll(
        '.product-info-block'
    );


productTabs.forEach(
    (tab, index) => {

        tab.addEventListener(
            'click',
            () => {


                productTabs.forEach(
                    item => {

                        item.classList.remove(
                            'active'
                        );

                    }
                );


                productBlocks.forEach(
                    block => {

                        block.classList.remove(
                            'active'
                        );

                    }
                );


                tab.classList.add(
                    'active'
                );


                productBlocks[index]
                    ?.classList.add(
                        'active'
                    );

            }
        );

    }
);


/* =========================================================
   ADD TO CART
========================================================= */

const addToCart =
    document.getElementById(
        'addToCart'
    );


addToCart?.addEventListener(
    'click',
    () => {


        if (!productIsInStock) {

            return;

        }


        const id =
            addToCart.dataset.productId;


        const name =
            addToCart.dataset.productName;


        const price =
            Number(
                addToCart.dataset.productPrice
            );


        let quantity =
            Number(
                quantityInput?.value
            ) || 1;


        quantity =
            Math.floor(quantity);


        quantity =
            Math.max(
                1,
                Math.min(
                    productStock,
                    20,
                    quantity
                )
            );


        /* -------------------------------------------------
           LOAD ACCOUNT-SPECIFIC CART
        ------------------------------------------------- */

        let cart = [];


        try {

            cart =
                JSON.parse(
                    localStorage.getItem(
                        cartStorageKey
                    ) || '[]'
                );

        } catch (error) {

            cart = [];

        }


        if (!Array.isArray(cart)) {
            cart = [];
        }


        /* -------------------------------------------------
           CHECK EXISTING QUANTITY
        ------------------------------------------------- */

        const existing =
            cart.find(
                item =>
                    String(item.id) ===
                    String(id)
            );


        if (existing) {


            const currentQuantity =
                Number(
                    existing.quantity
                ) || 0;


            if (
                currentQuantity + quantity >
                productStock
            ) {


                const remaining =
                    Math.max(
                        0,
                        productStock -
                        currentQuantity
                    );


                if (typeof showToast === 'function') {

                    showToast(
                        remaining > 0
                            ? `Only ${remaining} more available.`
                            : 'You already have the maximum available quantity in your cart.'
                    );

                }


                return;

            }


            existing.quantity =
                currentQuantity + quantity;


        } else {


            cart.push({

                id: id,

                name: name,

                price: price,

                quantity: quantity,

                series:
                    <?= json_encode($product['series']) ?>,

                condition:
                    <?= json_encode($product['condition']) ?>,

                image:
                    <?= json_encode($product['image']) ?>

            });

        }


        /* -------------------------------------------------
           SAVE ACCOUNT-SPECIFIC CART
        ------------------------------------------------- */

        localStorage.setItem(
            cartStorageKey,
            JSON.stringify(cart)
        );


        /* -------------------------------------------------
           UPDATE CART COUNT
        ------------------------------------------------- */

        const productCartCount =
    document.getElementById(
        'cart-count'
    );

const totalItems =
    cart.reduce(
        (
            total,
            item
        ) =>
            total +
            (
                Number(item.quantity)
                || 0
            ),
        0
    );

if (productCartCount) {

    productCartCount.textContent =
        totalItems;

}

        /* -------------------------------------------------
           BUTTON FEEDBACK
        ------------------------------------------------- */

        addToCart.textContent =
            'Added to Cart ✓';


        setTimeout(
            () => {

                addToCart.textContent =
                    'Add to Cart';

            },
            1800
        );


        if (
            typeof showToast ===
            'function'
        ) {

            showToast(
                `${name} added to your cart.`
            );

        }

    }
);


/* =========================================================
   LOAD ACCOUNT-SPECIFIC CART COUNT
========================================================= */

let savedCart = [];


try {

    savedCart =
        JSON.parse(
            localStorage.getItem(
                cartStorageKey
            ) || '[]'
        );

} catch (error) {

    savedCart = [];

}


if (!Array.isArray(savedCart)) {
    savedCart = [];
}


const savedCartCount =
    savedCart.reduce(
        (
            total,
            item
        ) =>
            total +
            (
                Number(item.quantity)
                || 0
            ),
        0
    );


const pageCartCount =
    document.getElementById(
        'cart-count'
    );


if (pageCartCount) {

    pageCartCount.textContent =
        savedCartCount;

}


/* =========================================================
   INITIAL QUANTITY
========================================================= */

updateProductQuantity();

</script>


</body>

</html>
```

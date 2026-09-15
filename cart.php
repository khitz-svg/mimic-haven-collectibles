<?php

/* =========================================================
   MIMIC HAVEN - CART PAGE
========================================================= */

require_once 'auth.php';


function asset(string $name): string {

    foreach (['png','jpg','jpeg','webp','svg'] as $ext) {

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

    foreach (['png','jpg','jpeg','webp','svg'] as $ext) {

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
        Cart | Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="Review your selected anime figures and proceed to checkout."
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
     HEADER
========================================================= -->

<header
    class="site-header"
    id="home"
>

    <div class="header-inner">


        <!-- LOGO -->

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


        <!-- NAVIGATION -->

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


            <a href="about.php">
                About
            </a>


            <a href="contact.php">
                Contact
            </a>


            <!-- EXPANDABLE SEARCH -->

            <div
                class="header-search"
                id="headerSearch"
            >

                <button
                    type="button"
                    class="header-search-toggle"
                    id="headerSearchToggle"
                    aria-label="Search"
                    aria-expanded="false"
                >

                    <?php if (icon('search')): ?>

                        <img
                            src="<?= icon('search') ?>"
                            alt=""
                        >

                    <?php endif; ?>

                </button>


                <input
                    type="search"
                    class="header-search-input"
                    id="headerSearchInput"
                    placeholder="Search figures..."
                    autocomplete="off"
                    aria-label="Search figures"
                >

            </div>


            <!-- CART -->

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


            <!-- ACCOUNT -->

            <?php if (isLoggedIn()): ?>

                <a
                    class="nav-user"
                    href="account.php"
                >
                    Hi, <?= e(currentFirstName()) ?>
                </a>


                <a
                    class="nav-account"
                    href="logout.php"
                >
                    Logout
                </a>

            <?php else: ?>

                <a
                    class="nav-account"
                    href="login.php"
                >
                    Login
                </a>

            <?php endif; ?>


        </nav>


        <!-- MOBILE MENU -->

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
     CART PAGE
========================================================= -->

<main class="cart-page">


    <!-- BREADCRUMB -->

    <div class="cart-breadcrumb">

        <a href="index.php">
            Home
        </a>

        <span>
            ›
        </span>

        Cart

    </div>



    <!-- PAGE HEADING -->

    <div class="cart-heading">

        <span>
            YOUR COLLECTION
        </span>

        <h1>

            Shopping

            <strong>
                Cart
            </strong>

        </h1>

        <p>
            Review your selected figures before continuing to checkout.
        </p>

    </div>



    <!-- CART LAYOUT -->

    <section class="cart-layout">


        <!-- =================================================
             LEFT SIDE - CART ITEMS
        ================================================= -->

        <div class="cart-items">


            <div
                id="cartItemsContainer"
                class="cart-items-container"
            ></div>


            <!-- EMPTY CART -->

            <div
                id="emptyCart"
                class="empty-cart"
            >

                <div class="empty-cart-icon">
                    🛒
                </div>

                <h2>
                    Your Cart Is Empty
                </h2>

                <p>
                    Your favorite figures are waiting for you.
                </p>

                <a
                    href="collection.php"
                    class="btn primary"
                >
                    Browse Collection →
                </a>

            </div>

        </div>



        <!-- =================================================
             RIGHT SIDE - ORDER SUMMARY
        ================================================= -->

        <aside class="cart-summary">

            <h2>
                Order Summary
            </h2>


            <div class="cart-summary-row">

                <span>
                    Subtotal
                </span>

                <strong id="cartSubtotal">
                    ₱0
                </strong>

            </div>


            <div class="cart-summary-row">

                <span>
                    Shipping
                </span>

                <strong>
                    Calculated at checkout
                </strong>

            </div>


            <div class="cart-summary-divider">
            </div>


            <div class="cart-summary-total">

                <span>
                    Total
                </span>

                <strong id="cartTotal">
                    ₱0
                </strong>

            </div>


            <button
                type="button"
                class="checkout-button"
                id="checkoutButton"
                disabled
            >
                Proceed to Checkout →
            </button>


            <a
                href="collection.php"
                class="continue-shopping"
            >
                ← Continue Shopping
            </a>

        </aside>


    </section>

</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer id="contact">

    <div class="footer-grid">


        <!-- BRAND -->

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



        <!-- NAVIGATION -->

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


            <a href="about.php">
                About
            </a>


            <a href="contact.php">
                Contact
            </a>

        </div>



        <!-- CONNECT -->

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
            © 2026 Mimic Haven Collectibles. All Rights Reserved.
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



<!-- =========================================================
     HEADER SEARCH
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const headerSearch =
            document.getElementById('headerSearch');

        const headerSearchToggle =
            document.getElementById('headerSearchToggle');

        const headerSearchInput =
            document.getElementById('headerSearchInput');


        if (
            !headerSearch ||
            !headerSearchToggle ||
            !headerSearchInput
        ) {
            return;
        }


        headerSearchToggle.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();


                const isOpen =
                    headerSearch.classList.contains('active');


                if (isOpen) {

                    headerSearchInput.focus();

                    return;
                }


                headerSearch.classList.add('active');


                headerSearchToggle.setAttribute(
                    'aria-expanded',
                    'true'
                );


                setTimeout(
                    function () {

                        headerSearchInput.focus();

                    },
                    250
                );

            }
        );


        headerSearchInput.addEventListener(
            'keydown',
            function (event) {

                if (event.key !== 'Enter') {
                    return;
                }


                const search =
                    headerSearchInput.value.trim();


                if (!search) {
                    return;
                }


                window.location.href =
                    'collection.php?search=' +
                    encodeURIComponent(search);

            }
        );


        document.addEventListener(
            'click',
            function (event) {

                if (
                    !headerSearch.contains(event.target)
                ) {

                    headerSearch.classList.remove(
                        'active'
                    );


                    headerSearchToggle.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                }

            }
        );


        headerSearchInput.addEventListener(
            'keydown',
            function (event) {

                if (event.key === 'Escape') {

                    headerSearch.classList.remove(
                        'active'
                    );


                    headerSearchToggle.setAttribute(
                        'aria-expanded',
                        'false'
                    );


                    headerSearchInput.value = '';


                    headerSearchToggle.focus();

                }

            }
        );

    }
);



/* =========================================================
   CART FUNCTIONALITY
========================================================= */

const cartItemsContainer =
    document.getElementById('cartItemsContainer');

const emptyCart =
    document.getElementById('emptyCart');

const cartSubtotal =
    document.getElementById('cartSubtotal');

const cartTotal =
    document.getElementById('cartTotal');

const checkoutButton =
    document.getElementById('checkoutButton');

const pageCartCount =
    document.getElementById('cart-count');


/* =========================================================
   GET CART
========================================================= */

function getCart() {

    return JSON.parse(
        localStorage.getItem('mimicHavenCart') || '[]'
    );

}


/* =========================================================
   SAVE CART
========================================================= */

function saveCart(cart) {

    localStorage.setItem(
        'mimicHavenCart',
        JSON.stringify(cart)
    );

}


/* =========================================================
   FORMAT PRICE
========================================================= */

function formatPeso(value) {

    return '₱' +
        Number(value).toLocaleString('en-PH');

}


/* =========================================================
   ESCAPE HTML
========================================================= */

function escapeHtml(value) {

    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

}


/* =========================================================
   UPDATE CART COUNT
========================================================= */

function updateCartCount(cart) {

    const totalItems =
        cart.reduce(
            (total, item) =>
                total + Number(item.quantity || 0),
            0
        );


    if (pageCartCount) {

        pageCartCount.textContent =
            totalItems;

    }

}


/* =========================================================
   PRODUCT IMAGE
========================================================= */

function getProductImage(image) {

    if (!image) {

        return '';

    }


    let file =
        String(image).trim();


    file = file.replace(
        /^.*?assets[\/\\]collections[\/\\]/i,
        ''
    );


    return `assets/collections/${encodeURI(file)}`;

}


/* =========================================================
   RENDER CART
========================================================= */

function renderCart() {

    const cart =
        getCart();


    cartItemsContainer.innerHTML =
        '';


    updateCartCount(cart);


    if (cart.length === 0) {

        emptyCart.style.display =
            'block';


        cartSubtotal.textContent =
            '₱0';


        cartTotal.textContent =
            '₱0';


        checkoutButton.disabled =
            true;


        return;
    }


    emptyCart.style.display =
        'none';


    checkoutButton.disabled =
        false;


    let subtotal = 0;


    cart.forEach(
        (item, index) => {

            const quantity =
                Number(item.quantity || 1);


            const price =
                Number(item.price || 0);


            const itemTotal =
                price * quantity;


            subtotal +=
                itemTotal;


            const image =
                getProductImage(item.image);


            const itemElement =
                document.createElement('div');


            itemElement.className =
                'cart-item';


            itemElement.innerHTML = `

                <div class="cart-item-image">

                    ${
                        image

                        ? `

                            <img
                                src="${image}"
                                alt="${escapeHtml(item.name)}"
                            >

                          `

                        : `

                            <div class="cart-item-placeholder">
                                FIGURE
                            </div>

                          `
                    }

                </div>


                <div class="cart-item-info">

                    <span class="cart-item-series">

                        ${escapeHtml(
                            item.series || 'Anime Figure'
                        )}

                    </span>


                    <h3>

                        ${escapeHtml(
                            item.name
                        )}

                    </h3>


                    <span class="cart-item-condition">

                        ${escapeHtml(
                            item.condition || 'MISB'
                        )}

                    </span>

                </div>


                <div class="cart-item-price">

                    ${formatPeso(price)}

                </div>


                <div class="cart-quantity">

                    <button
                        type="button"
                        onclick="changeQuantity(${index}, -1)"
                    >
                        −
                    </button>


                    <span>
                        ${quantity}
                    </span>


                    <button
                        type="button"
                        onclick="changeQuantity(${index}, 1)"
                    >
                        +
                    </button>

                </div>


                <div class="cart-item-total">

                    ${formatPeso(itemTotal)}

                </div>


                <button
                    type="button"
                    class="cart-remove"
                    onclick="removeCartItem(${index})"
                    aria-label="Remove item"
                >
                    ×
                </button>

            `;


            cartItemsContainer.appendChild(
                itemElement
            );

        }
    );


    cartSubtotal.textContent =
        formatPeso(subtotal);


    cartTotal.textContent =
        formatPeso(subtotal);

}


/* =========================================================
   CHANGE QUANTITY
========================================================= */

function changeQuantity(index, amount) {

    const cart =
        getCart();


    if (!cart[index]) {

        return;

    }


    cart[index].quantity =
        Number(cart[index].quantity || 1) +
        amount;


    if (cart[index].quantity <= 0) {

        cart.splice(
            index,
            1
        );

    } else {

        cart[index].quantity =
            Math.min(
                10,
                cart[index].quantity
            );

    }


    saveCart(cart);

    renderCart();

}


/* =========================================================
   REMOVE ITEM
========================================================= */

function removeCartItem(index) {

    const cart =
        getCart();


    if (!cart[index]) {

        return;

    }


    const itemName =
        cart[index].name;


    cart.splice(
        index,
        1
    );


    saveCart(cart);

    renderCart();


    if (
        typeof showToast === 'function'
    ) {

        showToast(
            `${itemName} removed from cart.`
        );

    }

}


/* =========================================================
   CHECKOUT
========================================================= */

checkoutButton.addEventListener(
    'click',
    () => {

        const cart =
            getCart();


        if (cart.length === 0) {

            return;

        }


        window.location.href =
            'checkout.php';

    }
);


/* =========================================================
   INITIAL LOAD
========================================================= */

renderCart();

</script>


</body>

</html>
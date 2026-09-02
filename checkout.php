<?php

/* =========================================================
   MIMIC HAVEN - CHECKOUT PAGE
========================================================= */

function asset(string $name): string {
    foreach (['png','jpg','jpeg','webp','svg'] as $ext) {

        $assetFile = __DIR__ . "/assets/{$name}.{$ext}";

        if (file_exists($assetFile)) {
            return "assets/{$name}.{$ext}";
        }

        $rootFile = __DIR__ . "/{$name}.{$ext}";

        if (file_exists($rootFile)) {
            return "{$name}.{$ext}";
        }
    }

    return "";
}

function icon(string $name): string {
    foreach (['png','jpg','jpeg','webp','svg'] as $ext) {

        $file = __DIR__ . "/assets/icons/{$name}.{$ext}";

        if (file_exists($file)) {
            return "assets/icons/{$name}.{$ext}";
        }
    }

    return "";
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
        Checkout | Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="Complete your Mimic Haven Collectibles order."
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">

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

            <a href="about.php">
                About
            </a>

            <a href="contact.php">
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
     CHECKOUT PAGE
========================================================= -->

<main class="checkout-page">


    <!-- =====================================================
         BREADCRUMB
    ====================================================== -->

    <div class="checkout-breadcrumb">

        <a href="index.php">
            Home
        </a>

        <span>›</span>

        <a href="cart.php">
            Cart
        </a>

        <span>›</span>

        Checkout

    </div>



    <!-- =====================================================
         HEADING
    ====================================================== -->

    <div class="checkout-heading">

        <span>
            COMPLETE YOUR ORDER
        </span>

        <h1>
            Check
            <strong>out.</strong>
        </h1>

        <p>
            Enter your details below to complete your order.
        </p>

    </div>



    <!-- =====================================================
         CHECKOUT LAYOUT
    ====================================================== -->

    <section class="checkout-layout">


        <!-- =================================================
             LEFT SIDE - FORM
        ================================================= -->

        <div class="checkout-form-section">


            <!-- CUSTOMER INFORMATION -->

            <div class="checkout-section">

                <div class="checkout-section-heading">

                    <span>
                        01
                    </span>

                    <div>

                        <h2>
                            Customer Information
                        </h2>

                        <p>
                            Tell us where we can reach you.
                        </p>

                    </div>

                </div>


                <div class="checkout-form-row">


                    <div class="checkout-field">

                        <label for="firstName">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="firstName"
                            name="firstName"
                            placeholder="Juan"
                            required
                        >

                    </div>


                    <div class="checkout-field">

                        <label for="lastName">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="lastName"
                            name="lastName"
                            placeholder="Dela Cruz"
                            required
                        >

                    </div>

                </div>


                <div class="checkout-form-row">


                    <div class="checkout-field">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="your@email.com"
                            required
                        >

                    </div>


                    <div class="checkout-field">

                        <label for="phone">
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="+63 9XX XXX XXXX"
                            required
                        >

                    </div>

                </div>

            </div>



            <!-- DELIVERY -->

            <div class="checkout-section">

                <div class="checkout-section-heading">

                    <span>
                        02
                    </span>

                    <div>

                        <h2>
                            Delivery Address
                        </h2>

                        <p>
                            Where should we send your collection?
                        </p>

                    </div>

                </div>


                <div class="checkout-field">

                    <label for="address">
                        Complete Address
                    </label>

                    <input
                        type="text"
                        id="address"
                        name="address"
                        placeholder="House No., Street, Barangay"
                        required
                    >

                </div>


                <div class="checkout-form-row">


                    <div class="checkout-field">

                        <label for="city">
                            City / Municipality
                        </label>

                        <input
                            type="text"
                            id="city"
                            name="city"
                            placeholder="Dumaguete City"
                            required
                        >

                    </div>


                    <div class="checkout-field">

                        <label for="province">
                            Province
                        </label>

                        <input
                            type="text"
                            id="province"
                            name="province"
                            placeholder="Negros Oriental"
                            required
                        >

                    </div>

                </div>


                <div class="checkout-form-row">


                    <div class="checkout-field">

                        <label for="postalCode">
                            Postal Code
                        </label>

                        <input
                            type="text"
                            id="postalCode"
                            name="postalCode"
                            placeholder="6200"
                            required
                        >

                    </div>


                    <div class="checkout-field">

                        <label for="country">
                            Country
                        </label>

                        <select
                            id="country"
                            name="country"
                            required
                        >

                            <option value="Philippines" selected>
                                Philippines
                            </option>

                        </select>

                    </div>

                </div>

            </div>



            <!-- PAYMENT -->

            <div class="checkout-section">

                <div class="checkout-section-heading">

                    <span>
                        03
                    </span>

                    <div>

                        <h2>
                            Payment Method
                        </h2>

                        <p>
                            Choose how you would like to pay.
                        </p>

                    </div>

                </div>


                <div class="payment-options">


                    <label class="payment-option">

                        <input
                            type="radio"
                            name="payment"
                            value="gcash"
                            checked
                        >

                        <span class="payment-box">

                            <strong>
                                GCash
                            </strong>

                            <small>
                                Mobile payment
                            </small>

                        </span>

                    </label>


                    <label class="payment-option">

                        <input
                            type="radio"
                            name="payment"
                            value="bank"
                        >

                        <span class="payment-box">

                            <strong>
                                Bank Transfer
                            </strong>

                            <small>
                                Direct bank payment
                            </small>

                        </span>

                    </label>


                    <label class="payment-option">

                        <input
                            type="radio"
                            name="payment"
                            value="cod"
                        >

                        <span class="payment-box">

                            <strong>
                                Cash on Delivery
                            </strong>

                            <small>
                                Pay upon delivery
                            </small>

                        </span>

                    </label>

                </div>

            </div>



            <!-- NOTES -->

            <div class="checkout-section">

                <div class="checkout-section-heading">

                    <span>
                        04
                    </span>

                    <div>

                        <h2>
                            Order Notes
                        </h2>

                        <p>
                            Optional instructions for your order.
                        </p>

                    </div>

                </div>


                <div class="checkout-field">

                    <label for="notes">
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="5"
                        placeholder="Any special instructions..."
                    ></textarea>

                </div>

            </div>

        </div>



        <!-- =================================================
             RIGHT SIDE - ORDER SUMMARY
        ================================================= -->

        <aside class="checkout-summary">

            <div class="checkout-summary-header">

                <span>
                    YOUR ORDER
                </span>

                <h2>
                    Order
                    <strong>Summary</strong>
                </h2>

            </div>


            <div
                id="checkoutItems"
                class="checkout-items"
            ></div>


            <div class="checkout-summary-divider"></div>


            <div class="checkout-summary-row">

                <span>
                    Subtotal
                </span>

                <strong id="checkoutSubtotal">
                    ₱0
                </strong>

            </div>


            <div class="checkout-summary-row">

                <span>
                    Shipping
                </span>

                <strong>
                    Calculated after order
                </strong>

            </div>


            <div class="checkout-summary-divider"></div>


            <div class="checkout-summary-total">

                <span>
                    Total
                </span>

                <strong id="checkoutTotal">
                    ₱0
                </strong>

            </div>


            <button
                type="button"
                class="checkout-place-order"
                id="placeOrderButton"
            >
                Place Order →
            </button>


            <a
                href="cart.php"
                class="checkout-back"
            >
                ← Back to Cart
            </a>


            <p class="checkout-security">

                Your order information is used only to process
                your purchase.

            </p>

        </aside>

    </section>

</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

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

            <a href="about.php">
                About
            </a>

            <a href="contact.php">
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


<script>

/* =========================================================
   CHECKOUT CART
========================================================= */

const checkoutItems =
    document.getElementById('checkoutItems');

const checkoutSubtotal =
    document.getElementById('checkoutSubtotal');

const checkoutTotal =
    document.getElementById('checkoutTotal');

const placeOrderButton =
    document.getElementById('placeOrderButton');


function getCheckoutCart() {

    try {

        return JSON.parse(
            localStorage.getItem('mimicHavenCart') || '[]'
        );

    } catch (error) {

        console.error(
            'Unable to read cart:',
            error
        );

        return [];

    }

}


function formatCheckoutPrice(value) {

    return '₱' +
        Number(value).toLocaleString('en-PH');

}


function escapeCheckoutHtml(value) {

    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

}


function renderCheckout() {

    const cart =
        getCheckoutCart();

    checkoutItems.innerHTML = '';

    let subtotal = 0;


    if (cart.length === 0) {

        checkoutItems.innerHTML = `

            <div class="checkout-empty">

                <p>
                    Your cart is empty.
                </p>

                <a href="collection.php">
                    Browse Collection →
                </a>

            </div>

        `;

        checkoutSubtotal.textContent =
            '₱0';

        checkoutTotal.textContent =
            '₱0';

        placeOrderButton.disabled =
            true;

        return;

    }


    placeOrderButton.disabled =
        false;


    cart.forEach(item => {

        const quantity =
            Number(item.quantity || 1);

        const price =
            Number(item.price || 0);

        const itemTotal =
            price * quantity;


        subtotal += itemTotal;


        const itemElement =
            document.createElement('div');


        itemElement.className =
            'checkout-item';


        itemElement.innerHTML = `

            <div class="checkout-item-info">

                <strong>
                    ${escapeCheckoutHtml(
                        item.name || 'Anime Figure'
                    )}
                </strong>

                <span>
                    Qty: ${quantity}
                </span>

            </div>

            <strong>
                ${formatCheckoutPrice(itemTotal)}
            </strong>

        `;


        checkoutItems.appendChild(
            itemElement
        );

    });


    checkoutSubtotal.textContent =
        formatCheckoutPrice(subtotal);

    checkoutTotal.textContent =
        formatCheckoutPrice(subtotal);

}


placeOrderButton?.addEventListener(
    'click',
    () => {

        const cart =
            getCheckoutCart();


        if (cart.length === 0) {
            return;
        }


        const requiredFields = [
            'firstName',
            'lastName',
            'email',
            'phone',
            'address',
            'city',
            'province',
            'postalCode'
        ];


        for (const fieldId of requiredFields) {

            const field =
                document.getElementById(fieldId);


            if (!field || !field.value.trim()) {

                field?.focus();

                if (typeof showToast === 'function') {

                    showToast(
                        'Please complete all required fields.'
                    );

                }

                return;

            }

        }


        placeOrderButton.disabled =
            true;

        placeOrderButton.textContent =
            'Order Placed ✓';


        localStorage.removeItem(
            'mimicHavenCart'
        );


        const cartCount =
            document.getElementById('cart-count');


        if (cartCount) {
            cartCount.textContent = '0';
        }


        if (typeof showToast === 'function') {

            showToast(
                'Your order has been placed successfully!'
            );

        }


        setTimeout(() => {

            window.location.href =
                'index.php';

        }, 1800);

    }
);


renderCheckout();

</script>

</body>
</html>

// =========================================================
// MIMIC HAVEN - CART PAGE
// Account-specific cart, quantity, removal and checkout
// =========================================================

(() => {
    'use strict';

    const cartItemsContainer = document.getElementById('cartItemsContainer');
    const emptyCart = document.getElementById('emptyCart');
    const cartSubtotal = document.getElementById('cartSubtotal');
    const cartTotal = document.getElementById('cartTotal');
    const checkoutButton = document.getElementById('checkoutButton');
    const pageCartCount = document.getElementById('cart-count');

    if (
        !cartItemsContainer ||
        !emptyCart ||
        !cartSubtotal ||
        !cartTotal ||
        !checkoutButton
    ) {
        return;
    }

    // =====================================================
    // USE THE SERVER-PROVIDED CART KEY
    // Keep the same key throughout this page session.
    // =====================================================

    function isValidCartKey(key) {
        return (
            key === 'mimicHavenGuestCart' ||
            (typeof key === 'string' &&
                /^mimicHavenCart_\d+$/.test(key))
        );
    }

    function getInitialCartKey() {
        // The key passed directly by CartController through Twig.
        if (isValidCartKey(window.MIMIC_HAVEN_CART_KEY)) {
            return window.MIMIC_HAVEN_CART_KEY;
        }

        // Fallback to the existing global cart-key function.
        if (typeof window.getCartStorageKey === 'function') {
            const key = window.getCartStorageKey();

            if (isValidCartKey(key)) {
                return key;
            }
        }

        // Last fallback: read the cookie.
        try {
            const cookie = document.cookie
                .split(';')
                .map(item => item.trim())
                .find(item =>
                    item.startsWith('mimicHavenCartStorageKey=')
                );

            if (cookie) {
                const key = decodeURIComponent(
                    cookie.substring(
                        'mimicHavenCartStorageKey='.length
                    )
                );

                if (isValidCartKey(key)) {
                    return key;
                }
            }
        } catch (error) {
            console.error('Unable to read cart key:', error);
        }

        return 'mimicHavenGuestCart';
    }

    // Resolve ONCE instead of switching keys during clicks.
    const CART_STORAGE_KEY = getInitialCartKey();

    console.log('Active cart storage:', CART_STORAGE_KEY);

    // =====================================================
    // GET CART
    // =====================================================

    function getCart() {
        try {
            const saved = localStorage.getItem(CART_STORAGE_KEY);
            const cart = JSON.parse(saved || '[]');

            return Array.isArray(cart) ? cart : [];
        } catch (error) {
            console.error('Unable to read cart:', error);
            return [];
        }
    }

    // =====================================================
    // SAVE CART
    // =====================================================

    function saveCart(cart) {
        try {
            localStorage.setItem(
                CART_STORAGE_KEY,
                JSON.stringify(cart)
            );

            return true;
        } catch (error) {
            console.error('Unable to save cart:', error);
            return false;
        }
    }

    // =====================================================
    // FORMAT PRICE
    // =====================================================

    function formatPeso(value) {
        return '₱' + Number(value || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    // =====================================================
    // ESCAPE HTML
    // =====================================================

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[character]);
    }

    // =====================================================
    // PRODUCT IMAGE
    // =====================================================

    function getProductImage(image) {
        if (!image) return '';

        let file = String(image).trim();

        file = file.replace(
            /^.*?assets[\\/]+collections[\\/]+/i,
            ''
        );

        file = file.replace(
            /^collections[\\/]+/i,
            ''
        );

        return '/assets/collections/' + encodeURIComponent(file);
    }

    // =====================================================
    // UPDATE CART COUNT
    // =====================================================

    function updateCartCount(cart) {
        const totalItems = cart.reduce(
            (total, item) => total + (Number(item.quantity) || 0),
            0
        );

        if (pageCartCount) {
            pageCartCount.textContent = String(totalItems);

            pageCartCount.style.display =
                totalItems > 0 ? 'grid' : 'none';
        }
    }

    // =====================================================
    // SHOW MESSAGE
    // =====================================================

    function notify(message) {
        if (typeof window.showToast === 'function') {
            window.showToast(message);
            return;
        }

        const toast = document.getElementById('toast');

        if (toast) {
            toast.textContent = message;
            toast.classList.add('show');

            window.setTimeout(() => {
                toast.classList.remove('show');
            }, 2200);
        } else {
            window.alert(message);
        }
    }

    // =====================================================
    // RENDER CART
    // =====================================================

    function renderCart() {
        const cart = getCart();

        cartItemsContainer.innerHTML = '';
        updateCartCount(cart);

        if (cart.length === 0) {
            emptyCart.style.display = 'block';
            cartSubtotal.textContent = '₱0';
            cartTotal.textContent = '₱0';
            checkoutButton.disabled = true;
            return;
        }

        emptyCart.style.display = 'none';
        checkoutButton.disabled = false;

        let subtotal = 0;

        cart.forEach((item, index) => {
            const quantity = Math.max(
                1,
                Number(item.quantity) || 1
            );

            const price = Math.max(
                0,
                Number(item.price) || 0
            );

            const itemTotal = price * quantity;
            subtotal += itemTotal;

            const image = getProductImage(item.image);

            const imageMarkup = image
                ? `<img src="${escapeHtml(image)}" alt="${escapeHtml(item.name)}">`
                : '<div class="cart-item-placeholder">FIGURE</div>';

            const itemElement = document.createElement('div');
            itemElement.className = 'cart-item';

            itemElement.innerHTML = `
                <div class="cart-item-image">
                    ${imageMarkup}
                </div>

                <div class="cart-item-info">
                    <span class="cart-item-series">
                        ${escapeHtml(item.series || 'Anime Figure')}
                    </span>

                    <h3>
                        ${escapeHtml(item.name || 'Collectible Figure')}
                    </h3>

                    <span class="cart-item-condition">
                        ${escapeHtml(item.condition || 'MISB')}
                    </span>
                </div>

                <div class="cart-item-price">
                    ${formatPeso(price)}
                </div>

                <div class="cart-quantity">
                    <button
                        type="button"
                        data-action="decrease"
                        data-index="${index}"
                        aria-label="Decrease quantity"
                    >−</button>

                    <span>${quantity}</span>

                    <button
                        type="button"
                        data-action="increase"
                        data-index="${index}"
                        aria-label="Increase quantity"
                    >+</button>
                </div>

                <div class="cart-item-total">
                    ${formatPeso(itemTotal)}
                </div>

                <button
                    type="button"
                    class="cart-remove"
                    data-action="remove"
                    data-index="${index}"
                    aria-label="Remove ${escapeHtml(item.name || 'item')}"
                >×</button>
            `;

            cartItemsContainer.appendChild(itemElement);
        });

        cartSubtotal.textContent = formatPeso(subtotal);
        cartTotal.textContent = formatPeso(subtotal);
    }

    // =====================================================
    // PROCESS CART BUTTONS
    // =====================================================

    document.addEventListener('click', event => {
        if (!(event.target instanceof Element)) return;

        const cartButton = event.target.closest(
            '#cartItemsContainer button[data-action]'
        );

        const isCheckout = event.target.closest(
            '#checkoutButton'
        );

        if (!cartButton && !isCheckout) return;

        event.preventDefault();
        event.stopPropagation();

        // CHECKOUT
        if (isCheckout) {
            if (getCart().length === 0) {
                renderCart();
                notify('Your cart is empty.');
                return;
            }

            window.location.href = '/checkout';
            return;
        }

        // QUANTITY AND REMOVE
        const index = Number(cartButton.dataset.index);
        const action = cartButton.dataset.action;

        const cart = getCart();

        if (!Number.isInteger(index) || !cart[index]) return;

        if (action === 'increase') {
            cart[index].quantity = Math.min(
                10,
                (Number(cart[index].quantity) || 1) + 1
            );

        } else if (action === 'decrease') {
            cart[index].quantity =
                (Number(cart[index].quantity) || 1) - 1;

            if (cart[index].quantity <= 0) {
                cart.splice(index, 1);
            }

        } else if (action === 'remove') {
            const itemName = cart[index].name || 'Item';

            cart.splice(index, 1);

            if (!saveCart(cart)) {
                notify('Unable to update your cart.');
                return;
            }

            renderCart();
            notify(`${itemName} removed from cart.`);
            return;

        } else {
            return;
        }

        if (!saveCart(cart)) {
            notify('Unable to update your cart.');
            return;
        }

        renderCart();

    }, true);

    // =====================================================
    // UPDATE WHEN CART CHANGES
    // =====================================================

    window.addEventListener('storage', event => {
        if (
            event.key === CART_STORAGE_KEY ||
            event.key === null
        ) {
            renderCart();
        }
    });

    // =====================================================
    // INITIALIZE
    // =====================================================

    renderCart();

})();

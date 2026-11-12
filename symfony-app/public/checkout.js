
(() => {

    const itemsContainer = document.getElementById('checkoutItems');
    const subtotalElement = document.getElementById('checkoutSubtotal');
    const totalElement = document.getElementById('checkoutTotal');
    const placeOrderButton = document.getElementById('placeOrderButton');

    if (!itemsContainer || !subtotalElement || !totalElement || !placeOrderButton) {
        return;
    }


    /* =====================================================
       CART STORAGE
    ===================================================== */

    const activeCartKey =
        typeof cartStorageKey !== 'undefined'
            ? cartStorageKey
            : 'mimicHavenGuestCart';

    const storageBridge = window.MIMIC_HAVEN_CART_STORAGE;

    const rawGet = key => {

        if (storageBridge) {
            return storageBridge.getRaw(key);
        }

        return localStorage.getItem(key);
    };

    const rawSet = (key, value) => {

        if (storageBridge) {
            return storageBridge.setRaw(key, value);
        }

        return localStorage.setItem(key, value);
    };

    const rawRemove = key => {

        if (storageBridge) {
            return storageBridge.removeRaw(key);
        }

        return localStorage.removeItem(key);
    };


    const parseCart = key => {

        try {

            const saved = rawGet(key);
            const cart = JSON.parse(saved || '[]');

            return Array.isArray(cart) ? cart : [];

        } catch (error) {

            console.error('Unable to read cart:', error);
            return [];

        }
    };


    /* =====================================================
       MERGE GUEST CART IF NECESSARY
    ===================================================== */

    const migrateGuestCart = () => {

        if (!/^mimicHavenCart_\d+$/.test(activeCartKey)) {
            return;
        }

        const guestCart = parseCart('mimicHavenGuestCart');

        if (guestCart.length === 0) {
            return;
        }

        const accountCart = parseCart(activeCartKey);

        const mergedCart = accountCart.map(item => ({ ...item }));

        guestCart.forEach(guestItem => {

            const guestId = String(guestItem.id ?? '');

            const existingItem = guestId
                ? mergedCart.find(item =>
                    String(item.id ?? '') === guestId
                )
                : null;

            if (existingItem) {

                existingItem.quantity =
                    (Number(existingItem.quantity) || 1) +
                    (Number(guestItem.quantity) || 1);

            } else {

                mergedCart.push({ ...guestItem });

            }

        });

        try {

            rawSet(activeCartKey, JSON.stringify(mergedCart));

            const savedCart = parseCart(activeCartKey);

            if (savedCart.length === mergedCart.length) {
                rawRemove('mimicHavenGuestCart');
            }

        } catch (error) {

            console.error('Unable to transfer cart:', error);

        }
    };


    /* =====================================================
       CART
    ===================================================== */

    const getCart = () => {

        migrateGuestCart();

        return parseCart(activeCartKey);

    };


    /* =====================================================
       HELPERS
    ===================================================== */

    const money = amount =>
        '₱' + Number(amount || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });

    const escapeHtml = value =>
        String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[character]);

    const showMessage = message => {

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
            }, 3500);

        } else {

            window.alert(message);

        }
    };

    const fieldValue = id =>
        document.getElementById(id)?.value.trim() || '';


    /* =====================================================
       RENDER CHECKOUT
    ===================================================== */

    const renderCheckout = () => {

        const cart = getCart();

        itemsContainer.innerHTML = '';

        let subtotal = 0;

        if (cart.length === 0) {

            itemsContainer.innerHTML = `
                <div class="checkout-empty">
                    <p>Your cart is empty.</p>
                    <a href="/collection">Browse Collection →</a>
                </div>
            `;

            subtotalElement.textContent = '₱0';
            totalElement.textContent = '₱0';

            placeOrderButton.disabled = true;

            return;
        }

        placeOrderButton.disabled = false;

        cart.forEach(item => {

            const quantity = Math.max(
                1,
                Math.floor(Number(item.quantity) || 1)
            );

            const price = Math.max(
                0,
                Number(item.price) || 0
            );

            const itemTotal = price * quantity;

            subtotal += itemTotal;

            const row = document.createElement('div');

            row.className = 'checkout-item';

            row.innerHTML = `
                <div class="checkout-item-info">
                    <strong>${escapeHtml(item.name || 'Anime Figure')}</strong>
                    <span>Qty: ${quantity}</span>
                </div>
                <strong>${money(itemTotal)}</strong>
            `;

            itemsContainer.appendChild(row);

        });

        subtotalElement.textContent = money(subtotal);
        totalElement.textContent = money(subtotal);

    };


    /* =====================================================
       PLACE ORDER
    ===================================================== */

    placeOrderButton.addEventListener('click', async () => {

        const cart = getCart();

        if (cart.length === 0) {

            showMessage('Your cart is empty. Please add a product first.');

            renderCheckout();

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
            'postalCode',
            'country'
        ];

        for (const id of requiredFields) {

            const field = document.getElementById(id);

            if (
                !field ||
                !field.value.trim() ||
                !field.checkValidity()
            ) {

                field?.focus();
                field?.reportValidity();

                if (field?.value.trim()) {
                    showMessage('Please check the information you entered.');
                } else {
                    showMessage('Please complete all required fields.');
                }

                return;
            }

        }

        const payment = document.querySelector(
            'input[name="payment"]:checked'
        );

        if (!payment) {

            showMessage('Please select a payment method.');

            return;
        }

        const orderData = {

            csrf_token: checkoutCsrfToken,

            firstName: fieldValue('firstName'),
            lastName: fieldValue('lastName'),
            email: fieldValue('email'),
            phone: fieldValue('phone'),

            address: fieldValue('address'),
            city: fieldValue('city'),
            province: fieldValue('province'),
            postalCode: fieldValue('postalCode'),
            country: fieldValue('country'),

            payment: payment.value,
            notes: fieldValue('notes'),

            cart: cart

        };

        placeOrderButton.disabled = true;
        placeOrderButton.textContent = 'Processing...';

        try {

            const response = await fetch('/checkout', {

                method: 'POST',

                credentials: 'same-origin',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },

                body: JSON.stringify(orderData)

            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Unable to place your order.'
                );
            }

            // Clear only the current customer's cart after success.
            rawRemove(activeCartKey);

            // Ensure no old guest cart remains.
            rawRemove('mimicHavenGuestCart');

            const cartCount = document.getElementById('cart-count');

            if (cartCount) {

                cartCount.textContent = '0';
                cartCount.style.display = 'none';

            }

            placeOrderButton.textContent = 'Order Placed ✓';

            showMessage(
                `Order #${result.order_id} placed successfully!`
            );

            // Keep the original redirect.
            window.setTimeout(() => {
                window.location.href = '/account';
            }, 1800);

        } catch (error) {

            console.error('Order submission failed:', error);

            placeOrderButton.disabled = false;
            placeOrderButton.textContent = 'Place Order →';

            showMessage(
                error.message || 'Unable to place your order.'
            );

        }

    });


    /* =====================================================
       INITIALIZE
    ===================================================== */

    renderCheckout();

})();

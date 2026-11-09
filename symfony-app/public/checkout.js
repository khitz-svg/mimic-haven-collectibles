// Checkout page: keeps the existing Mimic Haven cart key and order fields.
(() => {
    const itemsContainer = document.getElementById('checkoutItems');
    const subtotalElement = document.getElementById('checkoutSubtotal');
    const totalElement = document.getElementById('checkoutTotal');
    const placeOrderButton = document.getElementById('placeOrderButton');

    if (!itemsContainer || !subtotalElement || !totalElement || !placeOrderButton) return;

    const money = (amount) => '₱' + Number(amount || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[character]);

    const getCart = () => {
        try {
            const cart = JSON.parse(localStorage.getItem(cartStorageKey) || '[]');
            return Array.isArray(cart) ? cart : [];
        } catch (error) {
            console.error('Unable to read cart:', error);
            return [];
        }
    };

    const showMessage = (message) => {
        if (typeof window.showToast === 'function') {
            window.showToast(message);
            return;
        }
        const toast = document.getElementById('toast');
        if (toast) {
            toast.textContent = message;
            toast.classList.add('show');
            window.setTimeout(() => toast.classList.remove('show'), 3500);
        } else {
            window.alert(message);
        }
    };

    const renderCheckout = () => {
        const cart = getCart();
        itemsContainer.innerHTML = '';
        let subtotal = 0;

        if (cart.length === 0) {
            itemsContainer.innerHTML = '<div class="checkout-empty"><p>Your cart is empty.</p><a href="/collection">Browse Collection →</a></div>';
            subtotalElement.textContent = '₱0';
            totalElement.textContent = '₱0';
            placeOrderButton.disabled = true;
            return;
        }

        placeOrderButton.disabled = false;
        cart.forEach((item) => {
            const quantity = Math.max(1, Math.floor(Number(item.quantity) || 1));
            const price = Math.max(0, Number(item.price) || 0);
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

    const fieldValue = (id) => document.getElementById(id)?.value.trim() || '';

    placeOrderButton.addEventListener('click', async () => {
        const cart = getCart();
        if (cart.length === 0) return;

        const requiredFields = ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'province', 'postalCode', 'country'];
        for (const id of requiredFields) {
            const field = document.getElementById(id);
            if (!field || !field.value.trim() || !field.checkValidity()) {
                field?.focus();
                field?.reportValidity();
                if (field?.value.trim()) showMessage('Please check the information you entered.');
                else showMessage('Please complete all required fields.');
                return;
            }
        }

        const payment = document.querySelector('input[name="payment"]:checked');
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
            cart
        };

        placeOrderButton.disabled = true;
        placeOrderButton.textContent = 'Processing...';

        try {
            const response = await fetch('/checkout', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(orderData)
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Unable to place your order.');
            }

            localStorage.removeItem(cartStorageKey);
            const cartCount = document.getElementById('cart-count');
            if (cartCount) cartCount.textContent = '0';

            placeOrderButton.textContent = 'Order Placed ✓';
            showMessage(`Order #${result.order_id} placed successfully!`);

            // The original checkout sends customers to their account after ordering.
            window.setTimeout(() => { window.location.href = '/account'; }, 1800);
        } catch (error) {
            console.error('Order submission failed:', error);
            placeOrderButton.disabled = false;
            placeOrderButton.textContent = 'Place Order →';
            showMessage(error.message || 'Unable to place your order.');
        }
    });

    renderCheckout();
})();

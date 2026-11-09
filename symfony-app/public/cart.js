// Cart page behavior. Uses the same account-specific localStorage key as the product page.
(() => {
    const cartItemsContainer = document.getElementById('cartItemsContainer');
    const emptyCart = document.getElementById('emptyCart');
    const cartSubtotal = document.getElementById('cartSubtotal');
    const cartTotal = document.getElementById('cartTotal');
    const checkoutButton = document.getElementById('checkoutButton');
    const pageCartCount = document.getElementById('cart-count');

    if (!cartItemsContainer || !emptyCart || !cartSubtotal || !cartTotal || !checkoutButton) {
        return;
    }

    function getCart() {
        try {
            const saved = JSON.parse(localStorage.getItem(getCartStorageKey()) || '[]');
            return Array.isArray(saved) ? saved : [];
        } catch (error) {
            console.error('Unable to read cart:', error);
            return [];
        }
    }

    function saveCart(cart) {
        localStorage.setItem(getCartStorageKey(), JSON.stringify(cart));
    }

    function formatPeso(value) {
        return '₱' + Number(value || 0).toLocaleString('en-PH');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function getProductImage(image) {
        if (!image) return '';
        let file = String(image).trim();
        file = file.replace(/^.*?assets[\\/]collections[\\/]/i, '');
        file = file.replace(/^collections[\\/]/i, '');
        return '/assets/collections/' + encodeURIComponent(file);
    }

    function updateCartCount(cart) {
        const totalItems = cart.reduce((total, item) => total + (Number(item.quantity) || 0), 0);
        if (pageCartCount) pageCartCount.textContent = String(totalItems);
    }

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
            const quantity = Math.max(1, Number(item.quantity) || 1);
            const price = Math.max(0, Number(item.price) || 0);
            const itemTotal = price * quantity;
            subtotal += itemTotal;

            const image = getProductImage(item.image);
            const imageMarkup = image
                ? `<img src="${image}" alt="${escapeHtml(item.name)}">`
                : '<div class="cart-item-placeholder">FIGURE</div>';

            const itemElement = document.createElement('div');
            itemElement.className = 'cart-item';
            itemElement.innerHTML = `
                <div class="cart-item-image">${imageMarkup}</div>
                <div class="cart-item-info">
                    <span class="cart-item-series">${escapeHtml(item.series || 'Anime Figure')}</span>
                    <h3>${escapeHtml(item.name || 'Collectible Figure')}</h3>
                    <span class="cart-item-condition">${escapeHtml(item.condition || 'MISB')}</span>
                </div>
                <div class="cart-item-price">${formatPeso(price)}</div>
                <div class="cart-quantity">
                    <button type="button" data-action="decrease" data-index="${index}" aria-label="Decrease quantity">−</button>
                    <span>${quantity}</span>
                    <button type="button" data-action="increase" data-index="${index}" aria-label="Increase quantity">+</button>
                </div>
                <div class="cart-item-total">${formatPeso(itemTotal)}</div>
                <button type="button" class="cart-remove" data-action="remove" data-index="${index}" aria-label="Remove ${escapeHtml(item.name || 'item')}">×</button>
            `;
            cartItemsContainer.appendChild(itemElement);
        });

        cartSubtotal.textContent = formatPeso(subtotal);
        cartTotal.textContent = formatPeso(subtotal);
    }

    cartItemsContainer.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;

        const index = Number(button.dataset.index);
        const action = button.dataset.action;
        const cart = getCart();
        if (!Number.isInteger(index) || !cart[index]) return;

        if (action === 'increase') {
            cart[index].quantity = Math.min(10, (Number(cart[index].quantity) || 1) + 1);
        } else if (action === 'decrease') {
            cart[index].quantity = (Number(cart[index].quantity) || 1) - 1;
            if (cart[index].quantity <= 0) cart.splice(index, 1);
        } else if (action === 'remove') {
            const itemName = cart[index].name || 'Item';
            cart.splice(index, 1);
            saveCart(cart);
            renderCart();
            if (typeof showToast === 'function') showToast(`${itemName} removed from cart.`);
            return;
        }

        saveCart(cart);
        renderCart();
    });

    checkoutButton.addEventListener('click', () => {
        if (getCart().length === 0) return;
        window.location.href = '/checkout';
    });

    window.addEventListener('storage', (event) => {
        if (event.key === getCartStorageKey()) renderCart();
    });

    renderCart();
})();

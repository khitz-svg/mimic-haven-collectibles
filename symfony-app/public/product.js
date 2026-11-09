// Product page quantity controls, information tabs, and cart behavior.
(() => {
    const quantityInput = document.getElementById('productQuantity');
    const minusButton = document.getElementById('quantityMinus');
    const plusButton = document.getElementById('quantityPlus');
    const addButton = document.getElementById('addToCart');

    const stock = Math.max(0, Number(addButton?.dataset.productStock || quantityInput?.max || 0));
    const maxQuantity = Math.min(20, stock);

    function clampQuantity() {
        if (!quantityInput) return 1;
        const value = Math.floor(Number(quantityInput.value) || 1);
        const quantity = Math.max(1, Math.min(maxQuantity || 1, value));
        quantityInput.value = String(quantity);
        return quantity;
    }

    minusButton?.addEventListener('click', () => {
        if (!quantityInput) return;
        quantityInput.value = String(Math.max(1, clampQuantity() - 1));
    });

    plusButton?.addEventListener('click', () => {
        if (!quantityInput) return;
        quantityInput.value = String(Math.min(maxQuantity || 1, clampQuantity() + 1));
    });

    quantityInput?.addEventListener('input', clampQuantity);
    quantityInput?.addEventListener('change', clampQuantity);

    const tabs = [...document.querySelectorAll('.product-tab')];
    const blocks = [...document.querySelectorAll('.product-info-block')];

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            tabs.forEach((item, itemIndex) => {
                const active = itemIndex === index;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', String(active));
            });
            blocks.forEach((block, blockIndex) => {
                block.classList.toggle('active', blockIndex === index);
            });
        });
    });

    addButton?.addEventListener('click', () => {
        if (!quantityInput || stock < 1) return;

        const quantity = clampQuantity();
        const product = {
            id: String(addButton.dataset.productId),
            name: addButton.dataset.productName,
            price: Number(addButton.dataset.productPrice),
            quantity,
            series: addButton.dataset.productSeries,
            condition: addButton.dataset.productCondition,
            image: addButton.dataset.productImage,
        };

        let cart = [];
        try {
            const saved = JSON.parse(localStorage.getItem(getCartStorageKey()) || '[]');
            cart = Array.isArray(saved) ? saved : [];
        } catch (error) {
            cart = [];
        }

        const existing = cart.find(item => String(item.id) === product.id);
        if (existing) {
            const currentQuantity = Number(existing.quantity) || 0;
            if (currentQuantity + quantity > stock) {
                const remaining = Math.max(0, stock - currentQuantity);
                showToast(remaining > 0
                    ? `Only ${remaining} more available.`
                    : 'You already have the maximum available quantity in your cart.');
                return;
            }
            existing.quantity = currentQuantity + quantity;
        } else {
            cart.push(product);
        }

        localStorage.setItem(getCartStorageKey(), JSON.stringify(cart));
        updateCartCount();

        addButton.textContent = 'Added to Cart ✓';
        window.setTimeout(() => {
            addButton.textContent = 'Add to Cart';
        }, 1800);

        showToast(`${product.name} added to your cart.`);
    });

    if (quantityInput) clampQuantity();
})();

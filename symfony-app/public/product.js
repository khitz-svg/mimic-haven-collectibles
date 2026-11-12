
// Product page quantity controls, information tabs, and cart behavior.
(() => {
    const quantityInput = document.getElementById('productQuantity');
    const minusButton = document.getElementById('quantityMinus');
    const plusButton = document.getElementById('quantityPlus');
    const addButton = document.getElementById('addToCart');

    if (!addButton) return;

    /* =====================================================
       STOCK AND QUANTITY
    ===================================================== */

    const stockValue = addButton.dataset.productStock;
    const inputMax = quantityInput?.getAttribute('max');

    const stockSource =
        stockValue !== undefined && stockValue !== ''
            ? stockValue
            : inputMax;

    const stockKnown =
        stockSource !== null &&
        stockSource !== undefined &&
        stockSource !== '' &&
        Number.isFinite(Number(stockSource));

    const stock = stockKnown
        ? Math.max(0, Number(stockSource))
        : 20;

    const maxQuantity = Math.min(20, stock);

    function clampQuantity() {
        if (!quantityInput) return 1;

        const value = Math.floor(Number(quantityInput.value) || 1);

        const quantity = Math.max(
            1,
            Math.min(maxQuantity || 1, value)
        );

        quantityInput.value = String(quantity);

        return quantity;
    }

    if (stockKnown && stock < 1) {
        addButton.disabled = true;
        addButton.textContent = 'Out of Stock';
    }

    minusButton?.addEventListener('click', (event) => {
        event.preventDefault();

        if (!quantityInput) return;

        quantityInput.value = String(
            Math.max(1, clampQuantity() - 1)
        );
    });

    plusButton?.addEventListener('click', (event) => {
        event.preventDefault();

        if (!quantityInput) return;

        quantityInput.value = String(
            Math.min(maxQuantity || 1, clampQuantity() + 1)
        );
    });

    quantityInput?.addEventListener('input', clampQuantity);
    quantityInput?.addEventListener('change', clampQuantity);


    /* =====================================================
       PRODUCT INFORMATION TABS
    ===================================================== */

    const tabs = [...document.querySelectorAll('.product-tab')];
    const blocks = [...document.querySelectorAll('.product-info-block')];

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', (event) => {
            event.preventDefault();

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


    /* =====================================================
       CART STORAGE KEY
    ===================================================== */

    function getProductCartStorageKey() {
        try {
            const cookies = document.cookie
                .split(';')
                .map(cookie => cookie.trim());

            for (const cookie of cookies) {
                if (cookie.startsWith('mimicHavenCartStorageKey=')) {
                    const value = decodeURIComponent(
                        cookie.substring(
                            'mimicHavenCartStorageKey='.length
                        )
                    );

                    if (
                        value === 'mimicHavenGuestCart' ||
                        /^mimicHavenCart_\d+$/.test(value)
                    ) {
                        return value;
                    }
                }
            }
        } catch (error) {
            console.error('Unable to read cart cookie:', error);
        }

        return 'mimicHavenGuestCart';
    }


    /* =====================================================
       TOAST MESSAGE
    ===================================================== */

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


    /* =====================================================
       UPDATE CART COUNT
    ===================================================== */

    function refreshCartCount(cart) {
        const countElement = document.getElementById('cart-count');

        if (!countElement) return;

        const totalItems = cart.reduce((total, item) => {
            return total + (Number(item.quantity) || 0);
        }, 0);

        countElement.textContent = totalItems;
        countElement.style.display = totalItems > 0 ? 'grid' : 'none';
    }


    /* =====================================================
       ADD TO CART
    ===================================================== */

    addButton.addEventListener('click', (event) => {
        // Prevent accidental form submission or page reload.
        event.preventDefault();

        if (addButton.disabled) return;

        if (!quantityInput) {
            notify('Unable to determine product quantity.');
            return;
        }

        if (stock < 1) {
            notify('This product is currently out of stock.');
            return;
        }

        const quantity = clampQuantity();

        const product = {
            id: String(addButton.dataset.productId || ''),
            name: addButton.dataset.productName || 'Anime Figure',
            price: Number(addButton.dataset.productPrice) || 0,
            quantity,
            series: addButton.dataset.productSeries || '',
            condition: addButton.dataset.productCondition || 'New',
            image: addButton.dataset.productImage || ''
        };

        if (!product.id) {
            console.error('Product ID is missing from the Add to Cart button.');
            notify('Unable to add this product. Please refresh the page.');
            return;
        }

        const storageKey = getProductCartStorageKey();

        let cart = [];

        try {
            const saved = localStorage.getItem(storageKey);
            const parsed = JSON.parse(saved || '[]');

            cart = Array.isArray(parsed) ? parsed : [];

        } catch (error) {
            console.error('Unable to read cart:', error);
            notify('Unable to access your cart. Please refresh the page.');
            return;
        }

        const existing = cart.find(
            item => String(item.id) === product.id
        );

        if (existing) {
            const currentQuantity = Number(existing.quantity) || 0;

            if (currentQuantity + quantity > stock) {
                const remaining = Math.max(
                    0,
                    stock - currentQuantity
                );

                notify(
                    remaining > 0
                        ? `Only ${remaining} more available.`
                        : 'You already have the maximum available quantity in your cart.'
                );

                return;
            }

            existing.quantity = currentQuantity + quantity;

        } else {
            cart.push(product);
        }

        try {
            localStorage.setItem(storageKey, JSON.stringify(cart));
        } catch (error) {
            console.error('Unable to save cart:', error);
            notify('Unable to save your cart. Please try again.');
            return;
        }

        refreshCartCount(cart);

        addButton.textContent = 'Added to Cart ✓';

        window.setTimeout(() => {
            if (!addButton.disabled) {
                addButton.textContent = 'Add to Cart';
            }
        }, 1800);

        notify(`${product.name} added to your cart.`);
    });


    /* =====================================================
       INITIALIZE
    ===================================================== */

    if (quantityInput) {
        clampQuantity();
    }

})();

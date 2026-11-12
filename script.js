
const menuToggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('.nav');
const cartButton = document.querySelector('.cart-button');
const cartCount = document.querySelector('#cart-count');
const toast = document.querySelector('#toast');


/* =========================================================
   ORIGINAL STORAGE METHODS
========================================================= */

const originalStorageGetItem = Storage.prototype.getItem;
const originalStorageSetItem = Storage.prototype.setItem;
const originalStorageRemoveItem = Storage.prototype.removeItem;

// Keep direct access to the actual storage methods.
// This lets us migrate the guest cart without accidentally
// redirecting its contents to the account cart.

window.MIMIC_HAVEN_CART_STORAGE = {
    getRaw(key) {
        return originalStorageGetItem.call(localStorage, key);
    },

    setRaw(key, value) {
        return originalStorageSetItem.call(
            localStorage,
            key,
            value
        );
    },

    removeRaw(key) {
        return originalStorageRemoveItem.call(
            localStorage,
            key
        );
    }
};


/* =========================================================
   MOBILE NAVIGATION
========================================================= */

menuToggle?.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    menuToggle.setAttribute('aria-expanded', String(open));
});

document.querySelectorAll('.nav a').forEach(link => {
    link.addEventListener('click', () => {
        nav.classList.remove('open');
    });
});


/* =========================================================
   CART STORAGE KEY
========================================================= */

function getCartStorageKey() {

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

window.getCartStorageKey = getCartStorageKey;


/* =========================================================
   READ AND SAVE CART DATA
========================================================= */

function readCartFromStorage(key) {

    try {
        const saved =
            window.MIMIC_HAVEN_CART_STORAGE.getRaw(key);

        const cart = JSON.parse(saved || '[]');

        return Array.isArray(cart) ? cart : [];

    } catch (error) {
        console.error('Unable to read cart:', error);
        return [];
    }
}

function saveCartToStorage(key, cart) {

    try {
        window.MIMIC_HAVEN_CART_STORAGE.setRaw(
            key,
            JSON.stringify(cart)
        );

        return true;

    } catch (error) {
        console.error('Unable to save cart:', error);
        return false;
    }
}


/* =========================================================
   MERGE GUEST CART INTO ACCOUNT CART
========================================================= */

function mergeCartItems(accountCart, guestCart) {

    const merged = accountCart.map(item => ({ ...item }));

    guestCart.forEach(guestItem => {

        const guestId = String(guestItem.id ?? '');

        const existingItem = guestId
            ? merged.find(item =>
                String(item.id ?? '') === guestId
            )
            : null;

        if (existingItem) {

            existingItem.quantity =
                (Number(existingItem.quantity) || 1) +
                (Number(guestItem.quantity) || 1);

        } else {

            merged.push({ ...guestItem });

        }
    });

    return merged;
}


/* =========================================================
   HANDLE LOGOUT CART RESET
========================================================= */

function handleGuestCartReset() {

    try {

        const resetRequested = document.cookie
            .split(';')
            .map(cookie => cookie.trim())
            .some(cookie =>
                cookie === 'mimicHavenResetGuestCart=1'
            );

        if (!resetRequested) {
            return;
        }

        // Clear guest data only when Symfony signals logout.
        window.MIMIC_HAVEN_CART_STORAGE.removeRaw(
            'mimicHavenGuestCart'
        );

        document.cookie =
            'mimicHavenResetGuestCart=; Max-Age=0; path=/; SameSite=Lax';

    } catch (error) {
        console.error('Unable to reset guest cart:', error);
    }
}


/* =========================================================
   MIGRATE GUEST CART TO LOGGED-IN ACCOUNT
========================================================= */

function migrateGuestCartToAccount() {

    const activeKey = getCartStorageKey();

    // Guests continue using their own guest cart.
    if (!/^mimicHavenCart_\d+$/.test(activeKey)) {
        return;
    }

    try {

        const guestCart = readCartFromStorage(
            'mimicHavenGuestCart'
        );

        if (guestCart.length === 0) {
            return;
        }

        const accountCart = readCartFromStorage(activeKey);

        const mergedCart = mergeCartItems(
            accountCart,
            guestCart
        );

        // Save first. Only remove the guest cart after saving succeeds.
        if (!saveCartToStorage(activeKey, mergedCart)) {
            return;
        }

        const savedCart = readCartFromStorage(activeKey);

        if (savedCart.length !== mergedCart.length) {
            console.error('Cart migration verification failed.');
            return;
        }

        window.MIMIC_HAVEN_CART_STORAGE.removeRaw(
            'mimicHavenGuestCart'
        );

        console.log(
            'Guest cart successfully transferred to:',
            activeKey
        );

    } catch (error) {
        console.error('Unable to migrate guest cart:', error);
    }
}


// IMPORTANT: Handle logout before attempting migration.
handleGuestCartReset();

// Transfer existing guest items to the current account.
migrateGuestCartToAccount();


/* =========================================================
   LEGACY CART STORAGE COMPATIBILITY
========================================================= */

// Some older Add to Cart functions still use
// the literal key 'mimicHavenGuestCart'.
// Redirect their storage calls to the logged-in account.

function getCorrectStorageKey(storage, key) {

    if (
        storage !== window.localStorage ||
        key !== 'mimicHavenGuestCart'
    ) {
        return key;
    }

    const activeKey = getCartStorageKey();

    if (/^mimicHavenCart_\d+$/.test(activeKey)) {
        return activeKey;
    }

    return key;
}

Storage.prototype.getItem = function (key) {

    const correctKey = getCorrectStorageKey(this, key);

    return originalStorageGetItem.call(
        this,
        correctKey
    );
};

Storage.prototype.setItem = function (key, value) {

    const correctKey = getCorrectStorageKey(this, key);

    return originalStorageSetItem.call(
        this,
        correctKey,
        value
    );
};

Storage.prototype.removeItem = function (key) {

    const correctKey = getCorrectStorageKey(this, key);

    return originalStorageRemoveItem.call(
        this,
        correctKey
    );
};


/* =========================================================
   CART
========================================================= */

function getCart() {

    return readCartFromStorage(getCartStorageKey());

}

window.getCart = getCart;


/* =========================================================
   UPDATE CART COUNT
========================================================= */

function updateCartCount() {

    const cart = getCart();

    const totalItems = cart.reduce(
        (total, item) => {
            return total + (Number(item.quantity) || 0);
        },
        0
    );

    if (cartCount) {

        cartCount.textContent = totalItems;

        cartCount.style.display =
            totalItems > 0
                ? 'grid'
                : 'none';
    }
}

window.updateCartCount = updateCartCount;

updateCartCount();


/* =========================================================
   UPDATE WHEN CART CHANGES
========================================================= */

window.addEventListener('storage', event => {

    if (
        event.key === getCartStorageKey() ||
        event.key === 'mimicHavenGuestCart'
    ) {
        updateCartCount();
    }

});


/* =========================================================
   COLLECTION CARDS
========================================================= */

document.querySelectorAll('.collection-card').forEach(card => {

    const open = () => {

        const title =
            card.dataset.collection || 'Collection';

        showToast(`${title} selected`);

    };

    card.addEventListener('click', open);

    card.addEventListener('keydown', e => {

        if (e.key === 'Enter' || e.key === ' ') {

            e.preventDefault();

            open();
        }

    });

});


/* =========================================================
   TOAST
========================================================= */

function showToast(message) {

    if (!toast) {
        return;
    }

    toast.textContent = message;

    toast.classList.add('show');

    window.clearTimeout(showToast.timer);

    showToast.timer = window.setTimeout(() => {

        toast.classList.remove('show');

    }, 2200);

}

window.showToast = showToast;


/* =========================================================
   PRE-ORDER PAGE
========================================================= */

document.querySelectorAll('.preorder-favorite').forEach(button => {

    button.addEventListener('click', () => {

        button.classList.toggle('favorited');

        button.textContent =
            button.classList.contains('favorited')
                ? '♥'
                : '♡';

    });

});

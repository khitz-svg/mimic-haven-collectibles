const menuToggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('.nav');
const cartButton = document.querySelector('.cart-button');
const cartCount = document.querySelector('#cart-count');
const toast = document.querySelector('#toast');

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

/*
 * The current user's cart key is stored in a cookie
 * by auth.php.
 *
 * Logged-in:
 * mimicHavenCart_1
 * mimicHavenCart_2
 * mimicHavenCart_3
 *
 * Guest:
 * mimicHavenGuestCart
 */

function getCartStorageKey() {

    try {

        const cookies =
            document.cookie
                .split(';')
                .map(cookie => cookie.trim());

        for (const cookie of cookies) {

            if (
                cookie.startsWith(
                    'mimicHavenCartStorageKey='
                )
            ) {

                const value =
                    decodeURIComponent(
                        cookie.substring(
                            'mimicHavenCartStorageKey='.length
                        )
                    );

                if (
                    value === 'mimicHavenGuestCart'
                    ||
                    /^mimicHavenCart_\d+$/.test(value)
                ) {
                    return value;
                }

            }

        }

    } catch (error) {

        console.error(
            'Unable to determine cart storage key:',
            error
        );

    }

    return 'mimicHavenGuestCart';
}


/* =========================================================
   CART
========================================================= */

function getCart() {

    const storageKey =
        getCartStorageKey();

    try {

        const saved =
            localStorage.getItem(storageKey);

        const cart =
            JSON.parse(saved || '[]');

        return Array.isArray(cart)
            ? cart
            : [];

    } catch (error) {

        console.error(
            'Unable to read cart:',
            error
        );

        return [];
    }
}


/* =========================================================
   UPDATE CART COUNT
========================================================= */

function updateCartCount() {

    const cart =
        getCart();

    const totalItems =
        cart.reduce(
            (total, item) => {

                return (
                    total +
                    (Number(item.quantity) || 0)
                );

            },
            0
        );

    if (cartCount) {

        cartCount.textContent =
            totalItems;

        /*
         * Hide the blue badge when the cart is empty.
         * Show it only when there is at least 1 item.
         */

        cartCount.style.display =
            totalItems > 0
                ? 'grid'
                : 'none';
    }
}


/*
 * Load the correct account's cart
 * whenever a page opens.
 */

updateCartCount();


/* =========================================================
   UPDATE WHEN CART CHANGES
========================================================= */

window.addEventListener(
    'storage',
    event => {

        const currentStorageKey =
            getCartStorageKey();

        if (
            event.key === currentStorageKey
        ) {

            updateCartCount();

        }

    }
);


/* =========================================================
   COLLECTION CARDS
========================================================= */

document.querySelectorAll(
    '.collection-card'
).forEach(card => {

    const open = () => {

        const title =
            card.dataset.collection ||
            'Collection';

        showToast(
            `${title} selected`
        );

    };

    card.addEventListener(
        'click',
        open
    );

    card.addEventListener(
        'keydown',
        e => {

            if (
                e.key === 'Enter'
                ||
                e.key === ' '
            ) {

                e.preventDefault();

                open();

            }

        }
    );

});


/* =========================================================
   TOAST
========================================================= */

function showToast(message) {

    if (!toast) {
        return;
    }

    toast.textContent =
        message;

    toast.classList.add('show');

    window.clearTimeout(
        showToast.timer
    );

    showToast.timer =
        window.setTimeout(
            () => {

                toast.classList.remove(
                    'show'
                );

            },
            2200
        );

}


/* =========================================================
   PRE-ORDER PAGE
========================================================= */

document.querySelectorAll(
    '.preorder-favorite'
).forEach(button => {

    button.addEventListener(
        'click',
        () => {

            button.classList.toggle(
                'favorited'
            );

            button.textContent =
                button.classList.contains(
                    'favorited'
                )
                    ? '♥'
                    : '♡';

        }
    );

});
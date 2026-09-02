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
   CART
========================================================= */

function getCart() {
    try {
        return JSON.parse(
            localStorage.getItem('mimicHavenCart') || '[]'
        );
    } catch (error) {
        console.error('Unable to read cart:', error);
        return [];
    }
}

function updateCartCount() {
    const cart = getCart();

    const totalItems = cart.reduce(
        (total, item) => {
            return total + Number(item.quantity || 0);
        },
        0
    );

    if (cartCount) {
        cartCount.textContent = totalItems;
    }
}

/* Load the saved cart count whenever a page opens */
updateCartCount();

/* Update automatically when cart data changes */
window.addEventListener('storage', event => {
    if (event.key === 'mimicHavenCart') {
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

    showToast.timer = window.setTimeout(
        () => {
            toast.classList.remove('show');
        },
        2200
    );

}


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
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const headerSearch = document.getElementById('headerSearch');
    const headerSearchToggle = document.getElementById('headerSearchToggle');
    const headerSearchInput = document.getElementById('headerSearchInput');
    const collectionCards = [...document.querySelectorAll('.collection-product-card')];
    const collectionGrid = document.getElementById('collectionProductGrid');
    const collectionSearch = document.getElementById('collectionSearchInput');
    const collectionSort = document.getElementById('collectionSort');
    const collectionPagination = document.getElementById('collectionPagination');
    const collectionNoResults = document.getElementById('collectionNoResults');
    const resultStart = document.getElementById('collectionResultStart');
    const resultEnd = document.getElementById('collectionResultEnd');
    const resultTotal = document.getElementById('collectionResultTotal');
    const gridView = document.getElementById('collectionGridView');
    const listView = document.getElementById('collectionListView');
    const clearFiltersButton = document.getElementById('clearFilters');
    const perPage = 12;
    let currentPage = 1;
    let filteredCards = [...collectionCards];

    if (headerSearch && headerSearchToggle && headerSearchInput) {
        headerSearchToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = headerSearch.classList.toggle('active');
            headerSearchToggle.setAttribute('aria-expanded', String(open));
            if (open) headerSearchInput.focus();
        });

        headerSearchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && headerSearchInput.value.trim()) {
                window.location.href = '/collection?search=' + encodeURIComponent(headerSearchInput.value.trim());
            }
            if (event.key === 'Escape') {
                headerSearch.classList.remove('active');
                headerSearchToggle.setAttribute('aria-expanded', 'false');
                headerSearchInput.value = '';
                headerSearchToggle.focus();
            }
        });

        document.addEventListener('click', (event) => {
            if (!headerSearch.contains(event.target)) {
                headerSearch.classList.remove('active');
                headerSearchToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    const checkedValues = (selector) => [...document.querySelectorAll(selector + ':checked')].map((input) => input.value);

    function renderPagination() {
        collectionPagination.innerHTML = '';
        const totalPages = Math.ceil(filteredCards.length / perPage);
        if (totalPages <= 1) return;

        for (let page = 1; page <= totalPages; page += 1) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'collection-page-button' + (page === currentPage ? ' active' : '');
            button.textContent = String(page);
            button.addEventListener('click', () => {
                currentPage = page;
                renderCollection();
                window.scrollTo({ top: 400, behavior: 'smooth' });
            });
            collectionPagination.appendChild(button);
        }
    }

    function renderCollection() {
        collectionCards.forEach((card) => { card.style.display = 'none'; });
        const start = (currentPage - 1) * perPage;
        const visibleCards = filteredCards.slice(start, start + perPage);

        visibleCards.forEach((card) => {
            card.style.display = '';
            collectionGrid.appendChild(card);
        });

        const total = filteredCards.length;
        resultTotal.textContent = String(total);
        resultStart.textContent = total === 0 ? '0' : String(start + 1);
        resultEnd.textContent = total === 0 ? '0' : String(Math.min(start + perPage, total));
        collectionNoResults.style.display = total === 0 ? 'block' : 'none';
        renderPagination();
    }

    function sortCollection() {
        const sort = collectionSort.value;
        filteredCards.sort((a, b) => {
            if (sort === 'name') return a.dataset.name.localeCompare(b.dataset.name);
            if (sort === 'price-low') return Number(a.dataset.price) - Number(b.dataset.price);
            if (sort === 'price-high') return Number(b.dataset.price) - Number(a.dataset.price);
            return Number(a.dataset.index) - Number(b.dataset.index);
        });
        renderCollection();
    }

    function filterCollection() {
        const search = collectionSearch.value.trim().toLowerCase();
        const categories = checkedValues('.category-filter');
        const franchises = checkedValues('.franchise-filter');
        const conditions = checkedValues('.condition-filter');
        const availability = checkedValues('.availability-filter');

        filteredCards = collectionCards.filter((card) => {
           
const searchableText = [
    card.dataset.name,
    card.dataset.series,
    card.dataset.category,
    card.dataset.condition,
    card.dataset.availability
].join(' ').toLowerCase();

const matchesSearch = !search || searchableText.includes(search);

            const matchesCategory = categories.length === 0 || categories.includes(card.dataset.category);
            const matchesFranchise = franchises.length === 0 || franchises.includes(card.dataset.series);
            const matchesCondition = conditions.length === 0 || conditions.includes(card.dataset.condition);
            const matchesAvailability = availability.length === 0 || availability.includes(card.dataset.availability);
            return matchesSearch && matchesCategory && matchesFranchise && matchesCondition && matchesAvailability;
        });

        currentPage = 1;
        sortCollection();
    }

    collectionSearch.addEventListener('input', filterCollection);
    collectionSort.addEventListener('change', sortCollection);

    document.querySelectorAll('.collection-sidebar input[type="checkbox"]').forEach((input) => {
        input.addEventListener('change', filterCollection);
    });

    clearFiltersButton.addEventListener('click', () => {
        document.querySelectorAll('.collection-sidebar input[type="checkbox"]').forEach((input) => { input.checked = false; });
        collectionSearch.value = '';
        filterCollection();
    });

    document.querySelectorAll('.collection-favorite').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const favorited = button.classList.toggle('favorited');
            button.textContent = favorited ? '♥' : '♡';
        });
    });

    collectionCards.forEach((card) => {
        const openProduct = () => {
            window.location.href = '/product?id=' + encodeURIComponent(card.dataset.productId);
        };
        card.addEventListener('click', (event) => {
            if (!event.target.closest('.collection-favorite')) openProduct();
        });
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openProduct();
            }
        });
    });

    gridView.addEventListener('click', () => {
        collectionGrid.classList.remove('list-view');
        gridView.classList.add('active');
        listView.classList.remove('active');
    });

    listView.addEventListener('click', () => {
        collectionGrid.classList.add('list-view');
        listView.classList.add('active');
        gridView.classList.remove('active');
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('search')) collectionSearch.value = params.get('search');
    filterCollection();
});

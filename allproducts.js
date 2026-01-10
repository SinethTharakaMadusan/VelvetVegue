function toggleSection(element) {
    const section = element.parentElement;
    section.classList.toggle('collapsed');
}

const categoryData = {
    "menswear": ["Shirts", "T-shirts", "Trousers", "Hoodies"],
    "womenswear": [
        "Dresses - Mini",
        "Dresses - Maxi",
        "Dresses - T-shirt",
        "Tops - Blouses",
        "Tops - Crop",
        "Bottoms - Leggings",
        "Bottoms - Skirts"
    ],
    "shoes": ["Sneakers", "Formal", "Sandals/Heels", "Sports"]
};

function updateSubcategoryFilter() {
    const mainCategory = document.querySelector('input[name="mainCategory"]:checked')?.value || 'all';
    const subcategoryContainer = document.getElementById('subcategoryFilters');

    subcategoryContainer.innerHTML = '<label><input type="radio" name="subcategory" value="all" checked>All</label>';

    if (mainCategory !== 'all' && categoryData[mainCategory]) {
        categoryData[mainCategory].forEach(sub => {
            const label = document.createElement('label');
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'subcategory';
            input.value = sub.toLowerCase();
            input.addEventListener('change', applyFilters);

            label.appendChild(input);
            label.appendChild(document.createTextNode(sub));
            subcategoryContainer.appendChild(label);
        });
    }

    applyFilters();
}

function applyFilters() {

    const selectedMainCategory = document.querySelector('input[name="mainCategory"]:checked')?.value || 'all';
    const selectedSubcategory = document.querySelector('input[name="subcategory"]:checked')?.value || 'all';
    const maxPrice = document.getElementById('price-slider')?.value || 15000;

    const products = document.querySelectorAll('.card-container .card');

    let visibleCount = 0;

    products.forEach(card => {
        const productGender = (card.getAttribute('data-gender') || '').toLowerCase().trim();
        const productMainCategory = (card.getAttribute('data-main-category') || '').toLowerCase().trim();
        const productSubcategory = (card.getAttribute('data-subcategory') || '').toLowerCase().trim();
        const productPrice = parseFloat(card.getAttribute('data-price')) || 0;



        const mainCategoryMatch = selectedMainCategory === 'all' || productMainCategory === selectedMainCategory;

        let subcategoryMatch = selectedSubcategory === 'all';
        if (!subcategoryMatch) {
            const normalize = (str) => str.replace(/[\s\-_]+/g, '').toLowerCase();
            const normProductSub = normalize(productSubcategory);
            const normSelectedSub = normalize(selectedSubcategory);
            subcategoryMatch = normProductSub === normSelectedSub || productSubcategory === selectedSubcategory;
        }

        const priceMatch = productPrice <= maxPrice;

        if (mainCategoryMatch && subcategoryMatch && priceMatch) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    updateResultsMessage(visibleCount, products.length);
}

function updateResultsMessage(visible, total) {
    let messageEl = document.getElementById('filter-results-message');

    if (!messageEl) {
        messageEl = document.createElement('div');
        messageEl.id = 'filter-results-message';
        messageEl.style.cssText = 'text-align: center; padding: 20px; font-size: 16px; color: #666;';
        const container = document.querySelector('.products-grid');
        if (container) {
            container.parentNode.insertBefore(messageEl, container);
        }
    }

    if (visible === 0) {
        messageEl.textContent = 'No products found matching your filters.';
        messageEl.style.display = 'block';
    } else if (visible < total) {
        messageEl.textContent = `Showing ${visible} of ${total} products`;
        messageEl.style.display = 'block';
    } else {
        messageEl.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const filters = document.querySelectorAll('.filter-content input');
    const priceSlider = document.getElementById('price-slider');
    const currentPriceDisplay = document.getElementById('current-price');

    filters.forEach(input => {
        input.addEventListener('change', applyFilters);
    });

    if (priceSlider) {
        priceSlider.addEventListener('input', (e) => {
            const value = parseInt(e.target.value);
            if (currentPriceDisplay) {
                currentPriceDisplay.textContent = `Max: LKR ${value.toLocaleString()}`;
            }
            applyFilters();
        });
    }

    applyFilters();
});

function toggleSidebar() {
    const sidebar = document.getElementById('filterSidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    } else {
        console.error("Sidebar element not found!");
    }
}
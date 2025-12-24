// products.js - popup, previews, validation and detail modal handlers

function openPopup() {
    const p = document.getElementById('popup');
    if (p) p.style.display = 'block';
}

function resetForm() {
    // Reset text inputs
    const textInputs = document.querySelectorAll('input[type="text"]');
    textInputs.forEach(input => input.value = '');

    // Reset file inputs
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => input.value = '');

    // Reset preview images and remove buttons
    const previewImages = document.querySelectorAll('.preview-img');
    previewImages.forEach(img => {
        img.src = '';
        img.style.display = 'none';
    });
    const removeButtons = document.querySelectorAll('.remove-btn');
    removeButtons.forEach(btn => btn.style.display = 'none');

    // Show upload icon/text
    const uploadIcons = document.querySelectorAll('.upload-box i, .upload-box span');
    uploadIcons.forEach(el => el.style.display = 'block');

    // Reset selects and textarea
    const categoryEl = document.getElementById('category');
    if (categoryEl) categoryEl.value = '';
    const descEl = document.querySelector('textarea[name="description"]');
    if (descEl) descEl.value = '';

    // Reset checkboxes
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = false);
}

function closePopup() {
    const p = document.getElementById('popup');
    if (p) p.style.display = 'none';
    resetForm();
}

// Close popup when clicking outside
window.addEventListener('click', function (event) {
    const popup = document.getElementById('popup');
    if (!popup) return;
    if (event.target === popup) closePopup();
});

// Image preview
function previewImg(input, index) {
    const preview = document.getElementById('preview' + index);
    if (!preview) return;

    const removeBtn = input.parentElement.querySelector('.remove-btn');
    const uploadIcon = input.parentElement.querySelector('i');
    const uploadText = input.parentElement.querySelector('span');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (removeBtn) removeBtn.style.display = 'flex';
            if (uploadIcon) uploadIcon.style.display = 'none';
            if (uploadText) uploadText.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImg(index) {
    const preview = document.getElementById('preview' + index);
    if (!preview) return;
    const uploadBox = preview.parentElement;
    const fileInput = uploadBox.querySelector('input[type="file"]');
    const removeBtn = uploadBox.querySelector('.remove-btn');
    const uploadIcon = uploadBox.querySelector('i');
    const uploadText = uploadBox.querySelector('span');

    if (fileInput) fileInput.value = '';
    preview.src = '';
    preview.style.display = 'none';
    if (removeBtn) removeBtn.style.display = 'none';
    if (uploadIcon) uploadIcon.style.display = 'block';
    if (uploadText) uploadText.style.display = 'block';
}

// Validation and handlers after DOM ready
document.addEventListener('DOMContentLoaded', function () {
    // Form validation
    const form = document.getElementById('addProductForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const priceInput = form.querySelector('input[name="price"]');
            if (priceInput) {
                const val = priceInput.value.trim();
                if (val === '' || isNaN(val) || Number(val) < 0) {
                    alert('Please enter a valid non-negative price.');
                    e.preventDefault();
                    return;
                }
            }

            // Validate images: max 4, each <= 4MB
            const fileInputs = form.querySelectorAll('input[type="file"]');
            let count = 0;
            const maxSize = 4 * 1024 * 1024;
            for (const fi of fileInputs) {
                if (fi.files && fi.files[0]) {
                    count++;
                    if (fi.files[0].size > maxSize) {
                        alert('Each image must be 4MB or smaller.');
                        e.preventDefault();
                        return;
                    }
                }
            }
            if (count > 4) {
                alert('Please upload no more than 4 images.');
                e.preventDefault();
                return;
            }
        });
    }

    // Product detail modal
    document.querySelectorAll('.product-card').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function (e) {
            // Ignore clicks on action buttons
            if (e.target.closest('.product-actions')) return;

            const img = card.querySelector('.product-image img');
            const name = card.querySelector('.product-info h3');
            const category = card.querySelector('.product-info .category');
            const price = card.querySelector('.product-stats .price');
            const stock = card.querySelector('.product-stats .stock');
            const desc = card.getAttribute('data-description') || '';

            const modal = document.getElementById('detailModal');
            if (!modal) return;

            const detailImage = document.getElementById('detailImage');
            const detailName = document.getElementById('detailName');
            const detailCategory = document.getElementById('detailCategory');
            const detailPrice = document.getElementById('detailPrice');
            const detailStock = document.getElementById('detailStock');
            const detailDescription = document.getElementById('detailDescription');

            if (detailImage) detailImage.src = (img && img.src) ? img.src : 'image/default.png';
            if (detailName) detailName.textContent = name ? name.textContent : '';
            if (detailCategory) detailCategory.textContent = category ? category.textContent : '';
            if (detailPrice) detailPrice.textContent = price ? price.textContent : '';
            if (detailStock) detailStock.textContent = stock ? stock.textContent : '';
            if (detailDescription) detailDescription.textContent = desc;

            modal.style.display = 'block';
        });
    });
});

// Search Product Function
function searchFunction() {
    const input = document.querySelector('.search-input');
    const filter = input.value.toUpperCase();
    const container = document.querySelector('.products-grid');
    const cards = container.getElementsByClassName('product-card');

    for (let i = 0; i < cards.length; i++) {
        const title = cards[i].querySelector('.product-info h3');
        const idText = cards[i].innerText;

        
        let txtValue = title.textContent || title.innerText;
        let allText = cards[i].textContent || cards[i].innerText;

        if (txtValue.toUpperCase().indexOf(filter) > -1 || allText.toUpperCase().indexOf(filter) > -1) {
            cards[i].style.display = "";
        } else {
            cards[i].style.display = "none";
        }
    }
}

// Add enter key listener for search input
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function (event) {
            if (event.key === 'Enter') {
                searchFunction();
            }
        });
    }
});

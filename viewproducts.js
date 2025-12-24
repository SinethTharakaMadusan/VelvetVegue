document.addEventListener("DOMContentLoaded", function () {
    console.log("viewproduct.js loaded successfully");
    // --- Product Image Gallery ---
    var productImage = document.getElementById("productImage");
    var smallImages = document.getElementsByClassName("small-img");

    if (productImage && smallImages.length > 0) {
        // Use a loop to attach event listeners to all available small images
        Array.from(smallImages).forEach(function (img) {
            img.onclick = function () {
                productImage.src = this.src;
            }
        });
    }

    // --- Quantity Selector ---
    const minusBtn = document.querySelector(".minus-btn");
    const plusBtn = document.querySelector(".plus-btn");
    const qtyBox = document.querySelector(".qty-box");

    if (minusBtn && plusBtn && qtyBox) {
        function updateButtons() {
            let val = parseInt(qtyBox.value);
            if (isNaN(val)) val = 1;
            minusBtn.disabled = val <= 1;
            // Assuming max quantity is 10 as per original logic
            plusBtn.disabled = val >= 10;
        }

        plusBtn.onclick = function () {
            let val = parseInt(qtyBox.value);
            if (isNaN(val)) val = 0;
            if (val < 10) {
                qtyBox.value = val + 1;
                updateButtons();
            }
        };

        minusBtn.onclick = function () {
            let val = parseInt(qtyBox.value);
            if (isNaN(val)) val = 1;
            if (val > 1) {
                qtyBox.value = val - 1;
                updateButtons();
            }
        };

        qtyBox.addEventListener('change', function () {
            let val = parseInt(this.value);
            if (val < 1 || isNaN(val)) {
                this.value = 1;
            } else if (val > 10) {
                this.value = 10;
            }
            updateButtons();
        });

        // Initialize button state
        updateButtons();
    } else {
        console.error("Quantity elements not found!");
    }

    // --- Tabs ---
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    console.log("Found tab buttons:", tabButtons.length);

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Activate clicked button
            button.classList.add('active');

            // Activate corresponding content
            const targetId = button.getAttribute('data-tab');
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
});


// Search Function
function searchFunction() {
    const input = document.querySelector('.Search');
    const filter = input.value.toUpperCase();
    const container = document.querySelector('.card-container');
    const cards = container.getElementsByClassName('card');

    // Elements to hide/show
    const mainProduct = document.querySelector('.single-product');
    const infoRows = document.querySelectorAll('.product-info-row');
    const headers = document.querySelectorAll('h2');

    // Determine visibility state
    const isSearching = filter.length > 0;

    // Toggle Main Product Section
    if (mainProduct) {
        mainProduct.style.display = isSearching ? 'none' : 'block';
    }

    // Toggle Info Rows (Tabs, Shipping)
    infoRows.forEach(row => {
        row.style.display = isSearching ? 'none' : 'flex';
    });

    // Toggle Specific Headers (#Categories, etc if present, or just the More... header)
    headers.forEach(h2 => {
        const text = h2.textContent || h2.innerText;
        // Hide the "More [Gender] Clothing" header when searching if desired, or keep it.
        // Based on "hide under all element search result", we likely want to maximize space.
        // Let's hide the "More [Gender] Clothing" header when searching.
        if (text.includes('Clothing')) {
            h2.style.display = isSearching ? 'none' : 'block';
        }
    });

    // Ensure container is visible
    if (container) {
        container.style.display = 'flex'; // Ensure flex layout is maintained
    }

    // Filter Products
    for (let i = 0; i < cards.length; i++) {
        const titleEl = cards[i].querySelector('.card-content p');
        const category = cards[i].getAttribute('data-category') || '';
        const gender = cards[i].getAttribute('data-gender') || '';

        if (titleEl) {
            const title = titleEl.textContent || titleEl.innerText;
            const combinedText = `${title} ${category} ${gender}`;

            if (combinedText.toUpperCase().indexOf(filter) > -1) {
                // Use 'flex' to maintain card layout, or inherit from CSS class
                cards[i].style.display = "flex";
            } else {
                cards[i].style.display = "none";
            }
        }
    }
}

// Add enter key listener for search input
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('.Search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function (event) {
            if (event.key === 'Enter') {
                searchFunction();
            } else if (searchInput.value === '') {
                // Restore view if input is cleared via backspace
                searchFunction();
            }
        });
    }
});
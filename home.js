const navbarToggle = document.querySelector('.navbar-toggle');
const navbarMenu = document.querySelector('.navbar-menu');

if (navbarToggle && navbarMenu) {
    navbarToggle.addEventListener('click', () => {
        navbarToggle.classList.toggle('active');
        navbarMenu.classList.toggle('active');
    });
}

// Cart Logic
let cart = JSON.parse(localStorage.getItem("cart")) || [];

function addToCart(product) {
    cart.push(product);
    localStorage.setItem("cart", JSON.stringify(cart));
    alert(product.name + " has been added to your cart!");
}

document.querySelectorAll(".card").forEach(card => {
    let btn = card.querySelector(".cart");
    if (btn) {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            let product = {
                name: card.querySelector("p").innerText,
                price: card.querySelector("h3").innerText,
                img: card.querySelector("img").src
            };
            addToCart(product);
        });
    }
});

// Search Function
function searchFunction() {
    const input = document.querySelector('.Search');
    const filter = input.value.toUpperCase();
    const container = document.querySelector('.card-container');
    const cards = container.getElementsByClassName('card');

    // Elements to hide/show
    const categoriesWrapper = document.querySelector('.wrapper');
    const headers = document.querySelectorAll('h2');

    // Determine visibility state
    const isSearching = filter.length > 0;

    // Toggle Categories Section
    if (categoriesWrapper) {
        categoriesWrapper.style.display = isSearching ? 'none' : 'flex';
    }

    // Toggle Specific Headers (#Categories, #Our Best Collections)
    headers.forEach(h2 => {
        const text = h2.textContent || h2.innerText;
        if (text.includes('#Categories') || text.includes('#Our Best Collections')) {
            h2.style.display = isSearching ? 'none' : 'block';
        }
    });

    // Filter Products
    for (let i = 0; i < cards.length; i++) {
        const titleEl = cards[i].querySelector('.card-content p');
        const category = cards[i].getAttribute('data-category') || '';
        const gender = cards[i].getAttribute('data-gender') || '';

        if (titleEl) {
            const title = titleEl.textContent || titleEl.innerText;
            const combinedText = `${title} ${category} ${gender}`;

            if (combinedText.toUpperCase().indexOf(filter) > -1) {
                cards[i].style.display = "";
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

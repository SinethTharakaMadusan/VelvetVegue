document.addEventListener("DOMContentLoaded", function () {
    updateTotal();

    document.body.addEventListener('click', function (e) {
        if (e.target.closest('.plus-btn')) {
            updateQuantity(e.target.closest('.plus-btn'), 1);
        } else if (e.target.closest('.minus-btn')) {
            updateQuantity(e.target.closest('.minus-btn'), -1);
        } else if (e.target.closest('.remove-btn')) {
            removeCartItem(e.target.closest('.remove-btn'));
        }
    });

    document.body.addEventListener('change', function (e) {
        if (e.target.classList.contains('qty-box')) {
            let qty = parseInt(e.target.value);
            if (isNaN(qty) || qty < 1) {
                qty = 1;
            } else if (qty > 10) {
                qty = 10;
            }
            e.target.value = qty;

            // Update minus button state
            const container = e.target.closest('.qty-container');
            const minusBtn = container.querySelector('.minus-btn');
            if (minusBtn) {
                minusBtn.disabled = (qty <= 1);
            }

            updateTotal();
        } else if (e.target.classList.contains('select-item')) {
            updateTotal();
        }
    });

    // Handle checkout form submission
    const checkoutForm = document.getElementById('checkoutForm');

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            
            const checkedItems = document.querySelectorAll('.select-item:checked');

            if (checkedItems.length === 0) {
                e.preventDefault();
                alert('Please select at least one item to checkout.');
                return false;
            }

            
        });
    }
});

function updateQuantity(btn, change) {
    const container = btn.closest('.qty-container');
    const input = container.querySelector('.qty-box');
    let qty = parseInt(input.value);

    if (isNaN(qty)) qty = 1;
    qty += change;

    if (qty < 1) {
        qty = 1;
    }

    if (qty > 10) {
        qty = 10;

    }

    input.value = qty;


    const minusBtn = container.querySelector('.minus-btn');
    if (minusBtn) {
        minusBtn.disabled = (qty <= 1);
    }

    updateTotal();
}

function removeCartItem(btn) {
    const item = btn.closest('.cart-item');
    const cartId = btn.getAttribute('data-id');

    if (item && cartId) {
        if (confirm("Are you sure you want to remove this item?")) {
            fetch('delete_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ cart_id: cartId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        item.remove();
                        updateTotal();
                        location.reload();
                    } else {
                        alert('Error removing item: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing the item.');
                });
        }
    }
}

function updateTotal() {
    let subtotal = 0;

    document.querySelectorAll(".cart-item").forEach(item => {
        const checkbox = item.querySelector(".select-item");
        if (!checkbox || !checkbox.checked) return;

        const priceEl = item.querySelector(".item-price");
        const qtyBox = item.querySelector(".qty-box");

        if (priceEl && qtyBox) {

            const priceText = priceEl.innerText.replace(/[^0-9.]/g, '');
            const price = parseFloat(priceText);
            const qty = parseInt(qtyBox.value);

            if (!isNaN(price) && !isNaN(qty)) {
                subtotal += price * qty;
            }
        }
    });


    const formattedTotal = subtotal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

    const subtotalEl = document.getElementById("subtotal");
    const totalEl = document.getElementById("total");

    if (subtotalEl) subtotalEl.innerText = formattedTotal;
    if (totalEl) totalEl.innerText = formattedTotal;
}
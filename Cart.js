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
        if (e.target.classList.contains('select-item') || e.target.classList.contains('qty-box')) {
            updateTotal();
        }
    });
});

function updateQuantity(btn, change) {
    const container = btn.closest('.qty-container');
    const input = container.querySelector('.qty-box');
    let qty = parseInt(input.value);

    if (isNaN(qty)) qty = 1;
    qty += change;

    if (qty < 1) qty = 1;
   

    input.value = qty;

    
    const minusBtn = container.querySelector('.minus-btn');
    if (minusBtn) {
        minusBtn.disabled = (qty <= 1);
    }

    updateTotal();
}

function removeCartItem(btn) {
    const item = btn.closest('.cart-item');
    if (item) {
        item.remove();
        updateTotal();
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
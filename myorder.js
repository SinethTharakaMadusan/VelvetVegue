// Cancel Order Function
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        // Send AJAX request to cancel the order
        fetch('cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + orderId
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order cancelled successfully!');
                    location.reload(); // Reload to show updated status
                } else {
                    alert('Error: ' + (data.message || 'Failed to cancel order'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cancelling the order');
            });
    }
}

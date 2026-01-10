<?php
include 'Database.php';


$sql = "
SELECT 
    u.users_id,
    u.name,
    u.email,
    u.profile_image,
    u.created_at,
    COUNT(DISTINCT o.order_id) as order_count,
    COALESCE(SUM(o.total_amount), 0) as total_spent
FROM users u
LEFT JOIN orders o ON u.users_id = o.user_id
GROUP BY u.users_id
ORDER BY u.created_at DESC
";

$result = $conn->query($sql);
if (!$result) {
    die('DB error: ' . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Customers</title>
    <link rel="icon" type="image/png" href="image/logo.png">
    <link rel="stylesheet" href="Admin.css">
    <link rel="stylesheet" href="customers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo-container">
                <img class="logo" src="image/logo.png" alt="Logo">
            </div>
            <div class="menu-items">
                <a href="Admin.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-box"></i> Products</a>
                <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="customers.php" class="active"><i class="fas fa-users"></i> Customers</a>
                <a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            </div>
            <div class="user-profile">
                <img src="image/user.png" alt="User" class="user-avatar">
                <div class="user-info">
                    <h4>Admin User</h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>Customer Management</h1>
                <div class="header-actions">
                    
                </div>
            </div>

            
            <div class="customer-filters">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="customer-search" placeholder="Search Email..." class="search-input" onkeyup="searchCustomers()">
                </div>
                <div class="filter-group">
                    <select class="filter-select" id="status-filter">
                        <option value="">Membership Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select class="filter-select" id="sort-filter">
                        <option value="">Sort By</option>
                        <option value="name">Name</option>
                        <option value="orders">Orders</option>
                        <option value="spent">Total Spent</option>
                        <option value="joined">Join Date</option>
                    </select>
                    <button class="filter-btn" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>

            <div class="customers-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($customer = $result->fetch_assoc()): 
                        $joined_date = date('M d, Y', strtotime($customer['created_at']));
                        $total_spent = number_format($customer['total_spent'], 2);
                        $status = $customer['order_count'] > 0 ? 'Active' : 'Inactive';
                        $status_class = $customer['order_count'] > 0 ? 'active' : 'inactive';
                        $profile_image = $customer['profile_image'];
                    ?>
                <div class="customer-card">
                    <div class="customer-header">
                        <?php if (!empty($profile_image)): ?>
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Customer" class="customer-avatar">
                        <?php else: ?>
                            <div class="default-profile-icon customer-avatar-icon">
                                <i class="fas fa-user-circle"></i>
                            </div>
                        <?php endif; ?>
                        <div class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></div>
                    </div>
                    <div class="customer-info">
                        <h3><?php echo htmlspecialchars($customer['name']); ?></h3>
                        <p class="email"><?php echo htmlspecialchars($customer['email']); ?></p>
                        <div class="customer-stats">
                            <div class="stat">
                                <span class="label">Orders</span>
                                <span class="value"><?php echo $customer['order_count']; ?></span>
                            </div>
                            <div class="stat">
                                <span class="label">Spent</span>
                                <span class="value">LKR <?php echo $total_spent; ?></span>
                            </div>
                        </div>
                        <p class="joined-date">Joined: <?php echo $joined_date; ?></p>
                    </div>
                    <div class="customer-actions">
                        <button class="view-btn" onclick="viewCustomerProfile(<?php echo $customer['users_id']; ?>)"><i class="fas fa-eye"></i> View Profile</button>
                        <button class="delete-btn" onclick="deleteCustomer(<?php echo $customer['users_id']; ?>, '<?php echo htmlspecialchars($customer['name']); ?>')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                    <?php endwhile; ?>
                <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <p style="font-size: 18px; color: #666;">No customers found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Customer Profile Popup -->
    <div class="popup-bg" id="customerPopup">
        <div class="popup-content customer-popup">
            <span class="close-btn" onclick="closeCustomerPopup()">&times;</span>
            <h3>Customer Profile</h3>
            <div id="customerDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <script>
        
        function searchCustomers() {
            const searchValue = document.getElementById('customer-search').value.toLowerCase().trim();
            const customerCards = document.querySelectorAll('.customer-card');
            
            customerCards.forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const email = card.querySelector('.email').textContent.toLowerCase();
                
                if (name.includes(searchValue) || email.includes(searchValue)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Delete customer
        function deleteCustomer(userId, customerName) {
            if (!confirm(`Are you sure you want to delete customer "${customerName}"?\n\nThis action cannot be undone.`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('user_id', userId);
            
            fetch('delete_customer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cards = document.querySelectorAll('.customer-card');
                    cards.forEach(card => {
                        const viewBtn = card.querySelector('.view-btn');
                        if (viewBtn && viewBtn.getAttribute('onclick').includes(userId)) {
                            card.remove();
                        }
                    });
                    
                    const countElement = document.querySelector('.header-actions span');
                    if (countElement) {
                        const currentCount = parseInt(countElement.textContent.match(/\d+/)[0]);
                        countElement.innerHTML = `<i class="fas fa-users"></i> Total: ${currentCount - 1} customers`;
                    }
                    
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete customer');
            });
        }

        // Apply filters and sorting
        function applyFilters() {
            const statusFilter = document.getElementById('status-filter').value.toLowerCase();
            const sortFilter = document.getElementById('sort-filter').value;
            const customerCards = Array.from(document.querySelectorAll('.customer-card'));
            
            // Filter by status
            customerCards.forEach(card => {
                const statusBadge = card.querySelector('.status-badge');
                const cardStatus = statusBadge.textContent.toLowerCase();
                
                if (statusFilter === '' || cardStatus === statusFilter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Sort cards
            if (sortFilter !== '') {
                const visibleCards = customerCards.filter(card => card.style.display !== 'none');
                const grid = document.querySelector('.customers-grid');
                
                visibleCards.sort((a, b) => {
                    if (sortFilter === 'name') {
                        const nameA = a.querySelector('h3').textContent.toLowerCase();
                        const nameB = b.querySelector('h3').textContent.toLowerCase();
                        return nameA.localeCompare(nameB);
                    } else if (sortFilter === 'orders') {
                        const ordersA = parseInt(a.querySelector('.stat .value').textContent);
                        const ordersB = parseInt(b.querySelector('.stat .value').textContent);
                        return ordersB - ordersA;
                    } else if (sortFilter === 'spent') {
                        const spentA = parseFloat(a.querySelectorAll('.stat .value')[1].textContent.replace('LKR ', '').replace(',', ''));
                        const spentB = parseFloat(b.querySelectorAll('.stat .value')[1].textContent.replace('LKR ', '').replace(',', ''));
                        return spentB - spentA;
                    } else if (sortFilter === 'joined') {
                        const dateA = new Date(a.querySelector('.joined-date').textContent.replace('Joined: ', ''));
                        const dateB = new Date(b.querySelector('.joined-date').textContent.replace('Joined: ', ''));
                        return dateB - dateA;
                    }
                    return 0;
                });
                
                
                visibleCards.forEach(card => grid.appendChild(card));
            }
        }

        // View Customer Profile Popup
        function viewCustomerProfile(userId) {
            const popup = document.getElementById('customerPopup');
            const content = document.getElementById('customerDetailsContent');
            
            content.innerHTML = '<p style="text-align: center; padding: 20px;">Loading...</p>';
            popup.style.display = 'flex';
            
            fetch('get_customer_details.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const customer = data.customer;
                        const orders = data.recent_orders;
                        
                        let ordersHtml = '';
                        if (orders.length > 0) {
                            orders.forEach(order => {
                                ordersHtml += `
                                    <div class="order-row">
                                        <span class="order-id-small">${order.order_id}</span>
                                        <span class="order-date-small">${order.order_date}</span>
                                        <span class="status ${order.order_status.toLowerCase()}">${order.order_status}</span>
                                        <span class="order-amount">LKR ${order.total_amount}</span>
                                    </div>
                                `;
                            });
                        } else {
                            ordersHtml = '<p style="text-align: center; color: #666;">No orders yet</p>';
                        }
                        
                        
                        const statusClass = customer.account_status === 'locked' ? 'locked' : 'active';
                        const statusText = customer.account_status === 'locked' ? 'Locked' : 'Active';
                        const statusIcon = customer.account_status === 'locked' ? 'fa-lock' : 'fa-check-circle';
                        
                        content.innerHTML = `
                            <div class="admin-actions-bar">
                                <button class="status-toggle-btn ${statusClass}" onclick="toggleAccountStatus(${customer.user_id}, '${customer.account_status}')" id="statusBtn">
                                    <i class="fas ${statusIcon}"></i> ${statusText}
                                </button>
                                <button class="reset-password-btn" onclick="resetCustomerPassword(${customer.user_id})">
                                    <i class="fas fa-key"></i> Reset Password
                                </button>
                            </div>
                            
                            <div class="profile-section">
                                <h4><i class="fas fa-user"></i> Personal Information</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Name:</span>
                                        <span class="info-value">${customer.name}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value">${customer.email}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Phone:</span>
                                        <div id="phoneDisplay">
                                            <span class="info-value">${customer.phone}</span><br>
                                            <button class="edit-icon-btn" onclick="editContactInfo('phone', ${customer.user_id})" title="Edit Phone">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Address:</span>
                                        <div id="addressDisplay">
                                            <span class="info-value">${customer.address}</span><br>
                                            <button class="edit-icon-btn" onclick="editContactInfo('address', ${customer.user_id})" title="Edit Address">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Joined:</span>
                                        <span class="info-value">${customer.joined_date}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="profile-section">
                                <h4><i class="fas fa-chart-line"></i> Order Statistics</h4>
                                <div class="stats-grid">
                                    <div class="stat-box">
                                        <div class="stat-value">${customer.total_orders}</div>
                                        <div class="stat-label">Total Orders</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value">LKR ${customer.total_spent}</div>
                                        <div class="stat-label">Total Spent</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value">${customer.last_order}</div>
                                        <div class="stat-label">Last Order</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="profile-section">
                                <h4><i class="fas fa-shopping-bag"></i> Recent Orders</h4>
                                <div class="orders-list">
                                    ${ordersHtml}
                                </div>
                            </div>
                        `;
                        
                        // Store contact data for editing
                        window.customerContactData = data.contact_raw;
                    } else {
                        content.innerHTML = '<p style="text-align: center; color: red;">Error loading customer details</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = '<p style="text-align: center; color: red;">Error loading customer details</p>';
                });
        }

        // Close Customer Popup
        function closeCustomerPopup() {
            document.getElementById('customerPopup').style.display = 'none';
        }

        // Close popup when clicking outside
        document.getElementById('customerPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCustomerPopup();
            }
        });

        // Toggle Account Status
        function toggleAccountStatus(userId, currentStatus) {
            const newStatus = currentStatus === 'locked' ? 'active' : 'locked';
            
            if (!confirm(`Are you sure you want to ${newStatus === 'locked' ? 'lock' : 'unlock'} this account?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('status', newStatus);
            
            fetch('update_customer_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const btn = document.getElementById('statusBtn');
                    btn.className = 'status-toggle-btn ' + newStatus;
                    btn.innerHTML = `<i class="fas ${newStatus === 'locked' ? 'fa-lock' : 'fa-check-circle'}"></i> ${newStatus === 'locked' ? 'Locked' : 'Active'}`;
                    btn.onclick = function() { toggleAccountStatus(userId, newStatus); };
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update status');
            });
        }

        // Edit Contact Info
        function editContactInfo(field, userId) {
            if (field === 'phone') {
                const currentPhone = window.customerContactData?.phone_number || '';
                const phoneDisplay = document.getElementById('phoneDisplay');
                phoneDisplay.innerHTML = `
                    <input type="text" id="phoneInput" value="${currentPhone}" style="width: 200px; padding: 5px;">
                    <button onclick="saveContactDetails(${userId}, 'phone')" style="margin-left: 5px; padding: 5px 10px; background: #244551; color: white; border: none; border-radius: 4px; cursor: pointer;">Save</button>
                    <button onclick="viewCustomerProfile(${userId})" style="margin-left: 5px; padding: 5px 10px; background: #ccc; color: #333; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                `;
                document.getElementById('phoneInput').focus();
            } else if (field === 'address') {
                const contactData = window.customerContactData || {};
                const addressDisplay = document.getElementById('addressDisplay');
                addressDisplay.innerHTML = `
                    <div style="display: grid; gap: 5px; margin: 10px 0;">
                        <input type="text" id="addressLine1Input" placeholder="Address Line 1" value="${contactData.address_line1 || ''}" style="padding: 5px;">
                        <input type="text" id="cityInput" placeholder="City" value="${contactData.city || ''}" style="padding: 5px;">
                        <input type="text" id="stateInput" placeholder="State" value="${contactData.state || ''}" style="padding: 5px;">
                        <input type="text" id="zipInput" placeholder="Zip Code" value="${contactData.zip_code || ''}" style="padding: 5px;">
                        <div>
                            <button onclick="saveContactDetails(${userId}, 'address')" style="padding: 5px 10px; background: #244551; color: white; border: none; border-radius: 4px; cursor: pointer;">Save</button>
                            <button onclick="viewCustomerProfile(${userId})" style="margin-left: 5px; padding: 5px 10px; background: #ccc; color: #333; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                        </div>
                    </div>
                `;
            }
        }

        // Save Contact Details
        function saveContactDetails(userId, field) {
            const formData = new FormData();
            formData.append('user_id', userId);
            
            if (field === 'phone') {
                const phone = document.getElementById('phoneInput').value;
                formData.append('phone_number', phone);
                formData.append('address_line1', window.customerContactData?.address_line1 || '');
                formData.append('city', window.customerContactData?.city || '');
                formData.append('state', window.customerContactData?.state || '');
                formData.append('zip_code', window.customerContactData?.zip_code || '');
            } else {
                formData.append('phone_number', window.customerContactData?.phone_number || '');
                formData.append('address_line1', document.getElementById('addressLine1Input').value);
                formData.append('city', document.getElementById('cityInput').value);
                formData.append('state', document.getElementById('stateInput').value);
                formData.append('zip_code', document.getElementById('zipInput').value);
            }
            
            fetch('update_customer_details.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    viewCustomerProfile(userId); // Reload to show updated data
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update details');
            });
        }

        // Reset Customer Password
        function resetCustomerPassword(userId) {
            if (!confirm('Are you sure you want to reset this customer\'s password?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('user_id', userId);
            
            fetch('reset_customer_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Password reset successful!\n\nNew Password: ${data.new_password}\n\nPlease provide this to the customer.`);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to reset password');
            });
        }
    </script>
</body>
</html>
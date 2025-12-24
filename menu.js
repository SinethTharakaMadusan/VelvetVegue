document.addEventListener('DOMContentLoaded', function() {
    const navbarToggle = document.querySelector('.navbar-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');

    navbarToggle.addEventListener('click', function() {
        navbarMenu.classList.toggle('active');
        
        // Animate hamburger to X
        const bars = document.querySelectorAll('.bar');
        bars.forEach(bar => bar.classList.toggle('active'));
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!navbarToggle.contains(event.target) && !navbarMenu.contains(event.target)) {
            navbarMenu.classList.remove('active');
            const bars = document.querySelectorAll('.bar');
            bars.forEach(bar => bar.classList.remove('active'));
        }
    });
});
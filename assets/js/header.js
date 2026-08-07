// ============================================================
// HEADER AUTO-HIDE ON SCROLL
// Shows header when scrolling up, hides after 3 seconds of inactivity
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('.top-navbar');
    
    if (!header) return;

    let lastScrollY = window.scrollY;
    let isHeaderHidden = false;
    let hideTimeout = null;
    let isScrolling = false;

    // Function to hide header with delay
    function scheduleHeaderHide() {
        // Clear any existing timeout
        clearTimeout(hideTimeout);
        
        // Set timeout to hide header after 3 seconds of no scrolling
        hideTimeout = setTimeout(function() {
            if (!isHeaderHidden && window.scrollY > 50) {
                header.classList.add('header-hidden');
                isHeaderHidden = true;
            }
        }, 1000); // 3 seconds delay
    }

    // Function to show header immediately
    function showHeader() {
        clearTimeout(hideTimeout);
        if (isHeaderHidden) {
            header.classList.remove('header-hidden');
            isHeaderHidden = false;
        }
    }

    function handleScroll() {
        const currentScrollY = window.scrollY;

        // If at top of page, always show header
        if (currentScrollY === 0) {
            showHeader();
            lastScrollY = currentScrollY;
            return;
        }

        // Scrolling down - hide header immediately
        if (currentScrollY > lastScrollY && currentScrollY > 50) {
            if (!isHeaderHidden) {
                header.classList.add('header-hidden');
                isHeaderHidden = true;
            }
            // Clear any pending hide timeout
            clearTimeout(hideTimeout);
        }

        // Scrolling up - show header immediately
        if (currentScrollY < lastScrollY) {
            showHeader();
            // Schedule hide after 3 seconds of no scrolling
            scheduleHeaderHide();
        }

        lastScrollY = currentScrollY;
    }

    // Throttle scroll events for better performance
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    });

    // Mouse leave from header area - if not scrolling, hide after 3 seconds
    header.addEventListener('mouseleave', function() {
        if (window.scrollY > 50 && !isHeaderHidden) {
            scheduleHeaderHide();
        }
    });

    // Mouse enter on header - show immediately
    header.addEventListener('mouseenter', function() {
        showHeader();
        clearTimeout(hideTimeout);
    });

    // Show header on mouse move near top (user intent to scroll up)
    document.addEventListener('mousemove', function(e) {
        if (e.clientY < 50 && isHeaderHidden) {
            showHeader();
            // Schedule hide after 3 seconds of no scrolling
            scheduleHeaderHide();
        }
    });

    // Show header on touch near top (mobile)
    document.addEventListener('touchmove', function(e) {
        const touchY = e.touches[0].clientY;
        if (touchY < 50 && isHeaderHidden) {
            showHeader();
            // Schedule hide after 3 seconds of no scrolling
            scheduleHeaderHide();
        }
    });

    // Initial state - if not at top, schedule hide after 3 seconds
    if (window.scrollY > 50) {
        scheduleHeaderHide();
    }
});
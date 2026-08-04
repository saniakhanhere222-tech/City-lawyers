<?php
// ============================================================
// PUBLIC FOOTER - Reusable Footer
// ============================================================
// Provides: Logo/branding, navigation links, social icons,
// Bootstrap JS, custom JS, and conditional homepage JS.
//
// Usage: include at bottom of public pages.
// Expected: BASE_URL, SITE_NAME (from config.php)
// Optional: $page_title for conditional JS loading
// ============================================================
?>
    
    </div> <!-- .container -->
</main>

<!-- Footer -->
<footer class="site-footer pt-5 pb-3">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 ">
                <div class="d-flex align-items-center gap-2 mb-3">
        <img src="<?php echo BASE_URL; ?>assets/images/citylawyers_logo.png" alt="Logo" style="width: 45px; height: 45px; object-fit: contain;">
        <h5 class="mb-0" style="font-family: 'Cormorant Garamond', serif;"><?php echo SITE_NAME; ?></h5>
    </div>
                <p >Connecting you with the best legal professionals in your city. Book appointments effortlessly and manage your legal consultations in one place.</p>
                <p class=" small">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>.All rights reserved.</p>
            </div>
            
            <div class="col-md-3 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>" >Home</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>customer/search.php" >Find Lawyers</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>register.php?type=lawyer" >Join as Lawyer</a></li>
                </ul>
            </div>
            
            <div class="col-md-3 mb-4">
                <h5>Legal</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>about.php?tab=privacy" >Privacy Policy</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>about.php?tab=terms" >Terms of Service</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>about.php?tab=contact" >Contact Us</a></li>
                </ul>
            </div>
            
            <div class="col-md-2 mb-4">
                <h5>Follow Us</h5>
                <div class="d-flex gap-3">
                    <a href="#" ><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" ><i class="fab fa-linkedin fa-lg"></i></a>
                    <a href="#" ><i class="fab fa-facebook fa-lg"></i></a>
                </div>
            </div>
        </div>
        <hr class="bg-secondary">
        <div class="text-center small">
           &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
       </div>
    </div>
</footer>

<!-- ============================================================
     BACK TO TOP BUTTON - Floating arrow
============================================================ -->
<button id="backToTop" class="back-to-top" aria-label="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>

<!-- ============================================================
     CONDITIONAL HOMEPAGE JS
============================================================ -->
<?php if (isset($page_title) && $page_title === 'CityLawyers-Hompepage'): ?>
    <script src="<?php echo BASE_URL; ?>assets/js/how-it-works.js"></script>
<?php endif; ?>

<!-- ============================================================
     BACK TO TOP JAVASCRIPT
============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const backToTopBtn = document.getElementById('backToTop');
    
    if (!backToTopBtn) return;

    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    });

    // Smooth scroll to top on click
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        
        // Optional: Pulse animation feedback
        this.classList.add('pulse');
        setTimeout(() => {
            this.classList.remove('pulse');
        }, 2000);
    });
});
</script>

</body>
</html>
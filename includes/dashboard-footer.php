<?php
/**
 * Dashboard Footer – Reusable for all dashboard pages
 * 
 * Usage: include 'includes/dashboard-footer.php';
 * 
 * Expected variables:
 * - $page_js : Optional – path to page‑specific JavaScript file
 * - BASE_URL : Defined in config.php
 */
?>

        </div> <!-- end .main-content -->
    </div> <!-- end .dashboard-wrapper -->

    <!-- Footer -->
    <footer class="dashboard-footer">
        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - All rights reserved.
    </footer>

</main> <!-- end .main-content-wrapper -->


<!-- Bootstrap JS (required for dropdowns, toggles, etc.) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar toggle logic -->
<script>
 document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.getElementById("dashboardSidebar");
    const toggle = document.getElementById("sidebarToggle");
    const wrapper = document.querySelector(".dashboard-wrapper");

    if (!sidebar || !toggle || !wrapper) return;

    toggle.addEventListener("click", function () {

        sidebar.classList.toggle("collapsed");
        wrapper.classList.toggle("sidebar-collapsed");

        const icon = this.querySelector("i");

        if (sidebar.classList.contains("collapsed")) {

            icon.classList.replace("fa-chevron-left","fa-chevron-right");

        } else {

            icon.classList.replace("fa-chevron-right","fa-chevron-left");

        }

    });

});
</script>

<!-- Page-specific JavaScript (optional) -->
<?php if (isset($page_js)): ?>
    <script src="<?php echo BASE_URL . $page_js; ?>"></script>
<?php endif; ?>

</body>
</html>
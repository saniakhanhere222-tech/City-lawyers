<?php
$page_title = 'Contact Us';
require_once 'includes/config.php';
include 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/contact.css">

<div class="about-page">
    <div class="about-container">
        
        <!-- Hero -->
        <div class="about-hero">
            <h1>About LegalFlow</h1>
            <p>LegalFlow represents the intersection of tradition and digital innovation, connecting clients with trusted legal professionals across the nation.</p>
        </div>
        
        <!-- Tabs -->
        <div class="about-tabs">
            <button class="tab-btn active" data-tab="about">About Us</button>
            <button class="tab-btn" data-tab="privacy">Privacy Policy</button>
            <button class="tab-btn" data-tab="terms">Terms of Service</button>
            <button class="tab-btn" data-tab="contact">Contact Us</button>
        </div>
        
        <!-- About Us Tab -->
        <div id="about" class="tab-content active">
            <h2>Who We Are</h2>
            <p>LegalFlow is a premier online platform dedicated to connecting individuals and businesses with highly qualified legal professionals. Founded with the vision of making legal services accessible, transparent, and efficient, we bridge the gap between tradition and digital innovation.</p>
            
            <h3>Our Mission</h3>
            <p>To democratize access to quality legal representation by providing a seamless digital platform where clients can find, evaluate, and book appointments with trusted lawyers across all practice areas.</p>
            
            <h3>What We Offer</h3>
            <ul>
                <li><strong>Verified Lawyers:</strong> Every lawyer on our platform undergoes a thorough verification process.</li>
                <li><strong>Easy Booking:</strong> Schedule consultations with just a few clicks.</li>
                <li><strong>Transparent Pricing:</strong> Clear fee structures with no hidden costs.</li>
                <li><strong>Secure Platform:</strong> Your data and communications are protected.</li>
            </ul>
            
            <h3>Our Values</h3>
            <p><strong>Integrity:</strong> We believe honesty and transparency are the foundations of trust.<br>
            <strong>Excellence:</strong> We connect you with the finest legal minds.<br>
            <strong>Accessibility:</strong> Quality legal advice should be within everyone's reach.</p>
        </div>
        
        <!-- Privacy Policy Tab -->
        <div id="privacy" class="tab-content">
            <h2>Privacy Policy</h2>
            <p>Last updated: <?php echo date('F j, Y'); ?></p>
            
            <h3>Information We Collect</h3>
            <p>We collect information you provide directly to us, such as when you create an account, book an appointment, or contact us. This may include your name, email address, phone number, and payment information.</p>
            
            <h3>How We Use Your Information</h3>
            <ul>
                <li>To facilitate appointments between clients and lawyers</li>
                <li>To communicate with you about your appointments and account</li>
                <li>To improve our services and user experience</li>
                <li>To comply with legal obligations</li>
            </ul>
            
            <h3>Data Security</h3>
            <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
            
            <h3>Sharing Your Information</h3>
            <p>We do not sell your personal information. We share your information only with lawyers you choose to book appointments with, and as required by law.</p>
            
            <h3>Your Rights</h3>
            <p>You have the right to access, correct, or delete your personal information. You may also request a copy of your data or withdraw consent at any time.</p>
            
            <h3>Contact Us</h3>
            <p>If you have questions about this Privacy Policy, please contact us at privacy@legalflow.com</p>
        </div>
        
        <!-- Terms of Service Tab -->
        <div id="terms" class="tab-content">
            <h2>Terms of Service</h2>
            <p>Last updated: <?php echo date('F j, Y'); ?></p>
            
            <h3>Acceptance of Terms</h3>
            <p>By accessing or using LegalFlow, you agree to be bound by these Terms of Service. If you do not agree, please do not use our services.</p>
            
            <h3>User Accounts</h3>
            <p>You are responsible for maintaining the confidentiality of your account credentials. You agree to accept responsibility for all activities that occur under your account.</p>
            
            <h3>Booking Appointments</h3>
            <p>When you book an appointment through LegalFlow, you agree to the lawyer's consultation fees and cancellation policy. Cancellations must be made at least 24 hours in advance.</p>
            
            <h3>Lawyer Verification</h3>
            <p>While we verify all lawyers on our platform, LegalFlow does not guarantee the outcome of any legal matter. The lawyer-client relationship is formed directly between you and the lawyer.</p>
            
            <h3>Payment Terms</h3>
            <p>All fees are displayed in PKR. Payments are processed securely through our payment partners. Refunds are subject to individual lawyer policies.</p>
            
            <h3>Prohibited Conduct</h3>
            <p>You may not use our platform for any unlawful purpose, to harass others, or to interfere with the operation of our services.</p>
            
            <h3>Limitation of Liability</h3>
            <p>LegalFlow is not liable for any indirect, incidental, or consequential damages arising from your use of our services.</p>
        </div>
        
        <!-- Contact Us Tab -->
        <div id="contact" class="tab-content">
            <h2>Contact Us</h2>
            <p>We'd love to hear from you. Reach out with any questions, feedback, or concerns.</p>
            
            <div class="contact-grid">
                <div class="contact-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h4>Visit Us</h4>
                    <p>123 Legal District<br>Karachi, Pakistan</p>
                </div>
                <div class="contact-card">
                    <i class="fas fa-phone"></i>
                    <h4>Call Us</h4>
                    <p>+92 300 1234567<br>Mon-Fri, 9AM-6PM</p>
                </div>
                <div class="contact-card">
                    <i class="fas fa-envelope"></i>
                    <h4>Email Us</h4>
                    <p>support@legalflow.com<br>info@legalflow.com</p>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
// Tab switching functionality
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        
        button.classList.add('active');
        const tabId = button.getAttribute('data-tab');
        document.getElementById(tabId).classList.add('active');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
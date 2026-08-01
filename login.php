<?php
/**
 * Login – Choose your role
 * Fallback page when user type is not known
 */
$page_title = 'Login';
$footer_css = 'dashboard'; // loads specific dashboard-footer.php css (dasboard-footer.css)
require_once 'includes/config.php';
include 'includes/header.php';
?>
<style>
    /* =========================================
   LOGIN CHOICE PAGE
========================================= */

.login-choice-page{
    min-height:calc(100vh - 180px);
    display:flex;
    justify-content:center;
    align-items:center;
    padding:60px 20px;
}

.login-choice-card{

    width:100%;
    max-width:520px;

    background:var(--surface-color);

    border:var(--border-soft);

    box-shadow:var(--shadow-lg);

    padding:50px 45px;

    text-align:center;
    border-radius:14px;

}

.login-choice-card h1{

    margin:0;

    font-family:'Cormorant Garamond',serif;

    font-size:42px;

    color:var(--primary-color);

}

.login-choice-subtitle{

    margin:10px 0 35px;

    color:var(--text-light);

    font-size:14px;

}

/*=====================================
BUTTON STACK
=====================================*/

.login-choice-buttons{

    display:flex;

    flex-direction:column;

    gap:18px;

}

/*=====================================
BUTTON
=====================================*/

.login-choice-btn{

    position:relative;

    overflow:hidden;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:12px;

    height:42px;

    text-decoration:none;

    font-size:12px;

    font-weight:600;

    letter-spacing:2px;

    text-transform:uppercase;

    background:var(--primary-color);

    color:var(--secondary-color);

    border:1px solid transparent;

    transition:
        transform .45s,
        background .45s,
        box-shadow .45s;
        border-radius:10px;

}

/* Shine */

.login-choice-btn::before{

    content:"";

    position:absolute;

    top:-120%;

    left:-70%;

    width:45%;

    height:300%;

    background:linear-gradient(
        90deg,
        transparent,
        rgba(255,255,255,.25),
        transparent
    );

    transform:rotate(25deg);

    transition:.8s;

}

/* Hover */

.login-choice-btn:hover{

    background:var(--accent-color);

    color:var(--secondary-color);

    transform:
        translateY(-3px);

    box-shadow:var(--shadow-lg);

}

.login-choice-btn:hover::before{

    left:150%;

}

/*=====================================
ICONS
=====================================*/

.login-choice-btn i{

    font-size:15px;

}

/*=====================================
Different button colors
=====================================*/

.login-choice-btn.customer{

    background:var(--primary-color);

}

.login-choice-btn.lawyer{

    background:var(--accent-color);

}

.login-choice-btn.admin{

    background:#1b251b;

}

.login-choice-btn.customer:hover,
.login-choice-btn.lawyer:hover,
.login-choice-btn.admin:hover{

    background:#3d6b3d;

}

/*=====================================
Mobile
=====================================*/

@media(max-width:576px){

.login-choice-card{

    padding:35px 25px;

}

.login-choice-card h1{

    font-size:34px;

}

}
    </style>

<div class="login-choice-page">

    <div class="login-choice-card">

        <h1>Welcome Back</h1>

        <p class="login-choice-subtitle">
            Select how you'd like to sign in.
        </p>

        <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
            <div class="alert alert-success">
                You have been logged out successfully.
            </div>
        <?php endif; ?>

        <div class="login-choice-buttons">

            <a href="customer/login.php" class="login-choice-btn customer">
                <i class="fas fa-user"></i>
                <span>Client Login</span>
            </a>

            <a href="lawyer/login.php" class="login-choice-btn lawyer">
                <i class="fas fa-gavel"></i>
                <span>Lawyer Login</span>
            </a>

            <a href="admin/login.php" class="login-choice-btn admin">
                <i class="fas fa-user-shield"></i>
                <span>Administrator</span>
            </a>

        </div>

    </div>

</div>

<?php include 'includes/dashboard-footer.php'; ?>
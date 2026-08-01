/**
 * How It Works – Interactive Step Flow
 * 
 * Displays role-based steps (client/lawyer) with a preview panel.
 * When a step is clicked, the preview updates with the corresponding
 * title, description, and a visual screenshot mock.
 * 
 * Scroll behaviour: steps trigger based on section height segments.
 * Clicking a tab does NOT scroll to top.
 */
document.addEventListener('DOMContentLoaded', function () {

    // =========================================
    // 1. Define all steps for each role
    // =========================================
    var flows = {
        client: [
            {
                icon: 'fa-user-plus',
                title: 'Create your account',
                summary: 'Sign up in a couple of minutes.',
                eyebrow: 'Step 1 — Registration',
                desc: 'Register with your name, email, and city. No fees to browse or compare — you only pay once you book.',
                mock: 'client-form'
            },
            {
                icon: 'fa-magnifying-glass',
                title: 'Search & compare',
                summary: 'Filter by city and practice area.',
                eyebrow: 'Step 2 — Discovery',
                desc: 'Browse verified lawyers by city, practice area, and fees. Compare ratings and profiles side by side before you decide.',
                mock: 'client-grid'
            },
            {
                icon: 'fa-calendar-check',
                title: 'Book an appointment',
                summary: 'Pick a lawyer and a time slot.',
                eyebrow: 'Step 3 — Booking',
                desc: 'Choose an open slot on the lawyer\'s calendar and send your request — no phone calls or back-and-forth emails.',
                mock: 'client-slots'
            },
            {
                icon: 'fa-sliders',
                title: 'Track & manage',
                summary: 'Edit or reschedule anytime.',
                eyebrow: 'Step 4 — Your dashboard',
                desc: 'See every appointment\'s status from your dashboard. Reschedule or cancel with a couple of clicks if plans change.',
                mock: 'client-dashboard'
            },
            {
                icon: 'fa-comments',
                title: 'Meet, chat & review',
                summary: 'Message your lawyer, then rate them.',
                eyebrow: 'Step 5 — After the session',
                desc: 'Message your lawyer directly for quick questions, and leave a review once your appointment is complete.',
                mock: 'client-chat'
            }
        ],
        lawyer: [
            {
                icon: 'fa-user-tie',
                title: 'Create your profile',
                summary: 'Register and get verified.',
                eyebrow: 'Step 1 — Registration',
                desc: 'Set up your profile with your specialization, fees, and credentials. Our team verifies every listing before it goes live.',
                mock: 'lawyer-form'
            },
            {
                icon: 'fa-gauge',
                title: 'Manage your dashboard',
                summary: 'See your schedule at a glance.',
                eyebrow: 'Step 2 — Your dashboard',
                desc: 'Your dashboard shows upcoming appointments, pending requests, and client history in one place.',
                mock: 'lawyer-grid'
            },
            {
                icon: 'fa-bell',
                title: 'Get notified instantly',
                summary: 'A request lands the moment it\'s sent.',
                eyebrow: 'Step 3 — New request',
                desc: 'The moment a client books a slot, you get a notification with their details and the time they\'ve requested.',
                mock: 'lawyer-notif'
            },
            {
                icon: 'fa-circle-check',
                title: 'Approve or decline',
                summary: 'Confirm what fits your schedule.',
                eyebrow: 'Step 4 — Confirmation',
                desc: 'Review the request against your calendar and approve or decline it. Clients are notified right away either way.',
                mock: 'lawyer-approve'
            },
            {
                icon: 'fa-star',
                title: 'Build your reputation',
                summary: 'Chat with clients, collect reviews.',
                eyebrow: 'Step 5 — After the session',
                desc: 'Message clients directly through the platform, and let your completed sessions build up the reviews on your profile.',
                mock: 'lawyer-chat'
            }
        ]
    };

    // =========================================
    // 2. Visual mock templates – with real screenshots
    // =========================================
    var mockTemplates = {
        // ---- CLIENT SCREENSHOTS ----
        'client-form': '<div class="hiw-screen-body"><img src="assets/images/hiw-client-register.png" alt="Client registration" class="hiw-screenshot"></div>',
        'client-grid': '<div class="hiw-screen-body"><img src="assets/images/hiw-client-search.png" alt="Client search" class="hiw-screenshot"></div>',
        'client-slots': '<div class="hiw-screen-body"><img src="assets/images/hiw-client-book.png" alt="Client booking" class="hiw-screenshot"></div>',
        'client-dashboard': '<div class="hiw-screen-body"><img src="assets/images/hiw-client-dashboard.png" alt="Client dashboard" class="hiw-screenshot"></div>',
        'client-chat': '<div class="hiw-screen-body"><img src="assets/images/hiw-client-chat.png" alt="Client chat" class="hiw-screenshot"></div>',

        // ---- LAWYER SCREENSHOTS ----
        'lawyer-form': '<div class="hiw-screen-body"><img src="assets/images/hiw-lawyer-register.png" alt="Lawyer registration" class="hiw-screenshot"></div>',
        'lawyer-grid': '<div class="hiw-screen-body"><img src="assets/images/hiw-lawyer-dashboard.png" alt="Lawyer dashboard" class="hiw-screenshot"></div>',
        'lawyer-notif': '<div class="hiw-screen-body"><img src="assets/images/hiw-lawyer-notif.png" alt="Lawyer notification" class="hiw-screenshot"></div>',
        'lawyer-approve': '<div class="hiw-screen-body"><img src="assets/images/hiw-lawyer-approve.png" alt="Lawyer approval" class="hiw-screenshot"></div>',
        'lawyer-chat': '<div class="hiw-screen-body"><img src="assets/images/hiw-lawyer-chat.png" alt="Lawyer chat" class="hiw-screenshot"></div>'
    };

    // =========================================
    // 3. DOM references
    // =========================================
    var stepsEl = document.getElementById('hiwSteps');
    var previewEl = document.getElementById('hiwPreview');
    var toggleBtns = document.querySelectorAll('.hiw-toggle-btn');
    var sectionEl = document.querySelector('.how-it-works-section');

    var currentRole = 'client';
    var currentStep = 0;

    // =========================================
    // 4. Render the step list (left column)
    // =========================================
    function renderSteps() {
        stepsEl.innerHTML = '';
        flows[currentRole].forEach(function (step, i) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'hiw-step' + (i === currentStep ? ' active' : '');
            btn.innerHTML =
                '<span class="hiw-step-num">' + (i + 1) + '</span>' +
                '<span>' +
                    '<p class="hiw-step-title">' + step.title + '</p>' +
                    '<p class="hiw-step-desc">' + step.summary + '</p>' +
                '</span>';
            btn.addEventListener('click', function () {
                currentStep = i;
                renderSteps();
                renderPreview();
                // ✅ No scroll – stays in place
            });
            stepsEl.appendChild(btn);
        });
    }

    // =========================================
    // 5. Render the preview panel (right column)
    // =========================================
    function renderPreview() {
        var step = flows[currentRole][currentStep];
        previewEl.style.opacity = 0;
        setTimeout(function () {
            previewEl.innerHTML =
                '<p class="hiw-preview-eyebrow">' + step.eyebrow + '</p>' +
                '<h3 class="hiw-preview-title">' + step.title + '</h3>' +
                '<p class="hiw-preview-desc">' + step.desc + '</p>' +
                '<div class="hiw-screen">' +
                    '<div class="hiw-screen-bar">' +
                        '<span class="hiw-screen-dot"></span>' +
                        '<span class="hiw-screen-dot"></span>' +
                        '<span class="hiw-screen-dot"></span>' +
                    '</div>' +
                    mockTemplates[step.mock] +
                '</div>';
            previewEl.style.transition = 'opacity .35s ease';
            previewEl.style.opacity = 1;
        }, 120);
    }

    // =========================================
    // 6. Role toggle (Client / Lawyer)
    // =========================================
    toggleBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            toggleBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentRole = btn.getAttribute('data-role');
            currentStep = 0;
            renderSteps();
            renderPreview();
        });
    });

    // =========================================
    // 7. SCROLL DETECTION – Segments based on section height
    // =========================================
    function handleScroll() {
        if (!sectionEl) return;

        var sectionTop = sectionEl.offsetTop;
        var sectionHeight = sectionEl.offsetHeight;
        var scrollY = window.scrollY;

        var scrollProgress = (scrollY - sectionTop) / sectionHeight;
        scrollProgress = Math.max(0, Math.min(scrollProgress, 1));

        var totalSteps = flows[currentRole].length;
        var newStep = Math.min(Math.floor(scrollProgress * totalSteps), totalSteps - 1);

        if (newStep !== currentStep && scrollY > sectionTop - 50) {
            currentStep = newStep;
            renderSteps();
            renderPreview();
        }
    }

    function throttle(callback, limit) {
        var waiting = false;
        return function () {
            if (!waiting) {
                callback();
                waiting = true;
                setTimeout(function () {
                    waiting = false;
                }, limit);
            }
        };
    }

    if (sectionEl) {
        var throttledScroll = throttle(handleScroll, 150);
        window.addEventListener('scroll', throttledScroll);
        setTimeout(handleScroll, 300);
    }

    // =========================================
    // 8. Initial render
    // =========================================
    renderSteps();
    renderPreview();
});
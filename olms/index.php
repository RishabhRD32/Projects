<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect(is_admin() ? 'admin/dashboard.php' : 'employee/dashboard.php');
$pageTitle = 'Online Leave Management';
require __DIR__ . '/includes/header.php';
?>
<section class="hero-section">
    <div class="hero-copy">
        <span class="eyebrow"><span class="eyebrow-dot"></span> PEOPLE OPERATIONS · MADE SIMPLE</span>
        <h1>Time off should be<br><em>easy to manage.</em></h1>
        <p class="hero-lead">One clear place for employees to request leave and for HR teams to review, plan coverage and keep records accurate.</p>
        <div class="hero-actions"><a class="btn btn-primary btn-lg" href="<?= h(url('register.php')) ?>">Get started <span>→</span></a><a class="btn btn-outline btn-lg" href="<?= h(url('login.php')) ?>">Sign in</a></div>
        <div class="hero-proof"><span class="proof-avatars"><i>R</i><i>A</i><i>M</i></span><span><strong>Clear workflows.</strong> Less back-and-forth.</span></div>
    </div>
    <div class="hero-visual">
        <div class="visual-glow"></div>
        <div class="mock-window">
            <div class="mock-top"><span class="mock-dots"><i></i><i></i><i></i></span><span>Leave overview</span><span class="mock-period">THIS YEAR</span></div>
            <div class="mock-welcome"><div><small>GOOD MORNING</small><h3>Your time, in view.</h3></div><span class="mock-avatar">JD</span></div>
            <div class="mock-stats"><div><small>AVAILABLE</small><strong>18 <small>days</small></strong><span>Across leave types</span></div><div><small>REQUESTS</small><strong>04</strong><span>2 awaiting review</span></div></div>
            <div class="mock-line-title"><strong>Recent requests</strong><span>View all →</span></div>
            <div class="mock-request"><span class="mock-icon">☼</span><div><strong>Casual leave</strong><small>12–13 Dec · 2 days</small></div><b class="mock-status">Pending</b></div>
            <div class="mock-request"><span class="mock-icon mint">✳</span><div><strong>Sick leave</strong><small>04 Nov · 1 day</small></div><b class="mock-approved">Approved</b></div>
        </div>
        <div class="floating-note"><span class="note-check">✓</span><span><strong>Request submitted</strong><small>Your manager has been notified</small></span></div>
    </div>
</section>
<section class="feature-strip"><div><span class="feature-number">01</span><strong>Apply online</strong><p>Submit requests with dates, leave type and reason.</p></div><div><span class="feature-number">02</span><strong>Track every update</strong><p>See pending, approved and rejected requests.</p></div><div><span class="feature-number">03</span><strong>Plan with confidence</strong><p>Spot team overlaps and find backup coverage.</p></div></section>
<section class="home-bottom"><div><span class="eyebrow">A BETTER LEAVE WORKFLOW</span><h2>Clarity for employees.<br><em>Control for administrators.</em></h2></div><div><p>LeaveFlow centralises employee records, leave balances, approvals and reminders in a role-based workspace. Built for small and medium-sized teams.</p><a class="text-link" href="<?= h(url('login.php?admin=1')) ?>">Administrator sign in <span>→</span></a></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>

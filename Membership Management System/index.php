<?php
include('includes/config.php');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error_message = "Email and password are required!";
        } else {
            // SECURITY WARNING: Storing and checking passwords in plain text is highly insecure.
            // This implementation is for demonstration purposes based on the request.
            // For a real-world application, always use password_hash() and password_verify().
            $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
            
            $result = $conn->query($sql);

            if ($result && $result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid email or password!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Welcome to the Membership Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- Google Font: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f4f6f9;
    }
    .hero-section {
      background: linear-gradient(135deg, rgba(0, 123, 255, 0.9), rgba(40, 167, 69, 0.9));
      background-size: cover;
      color: #fff;
      padding: 100px 0;
    }
    .hero-section .display-4 {
        font-weight: 700;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    .login-form-container {
        background: #fff;
        padding: 2.5rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transform: translateY(-50px);
    }
    .section {
        padding: 60px 0;
    }
    .section-title {
        font-weight: 700;
        margin-bottom: 40px;
        position: relative;
        padding-bottom: 15px;
    }
    .section-title::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: #007bff;
        border-radius: 2px;
    }
    .benefit-card {
        background: #fff;
        border: none;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.07);
        padding: 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    .benefit-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    .benefit-card .icon {
        font-size: 40px;
        color: #007bff;
        margin-bottom: 20px;
    }
    .tier-table {
        box-shadow: 0 5px 15px rgba(0,0,0,0.07);
        border-radius: 10px;
        overflow: hidden;
    }
    .tier-table .fas.fa-check-circle { color: #28a745; }
    .tier-table .fas.fa-times-circle { color: #dc3545; }
    .stats-section {
        background-color: #343a40;
        color: #fff;
    }
    .stat-item h3 {
        font-weight: 700;
        font-size: 3rem;
    }
    #testimonial-carousel .carousel-item {
        padding: 40px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.07);
    }
    #testimonial-carousel .testimonial-img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
    }
    .faq-section .card-header {
        cursor: pointer;
    }
    .main-footer {
        background: #343a40;
        color: #f8f9fa;
        padding: 30px 0;
    }
  </style>
</head>
<body>
<div class="wrapper">

  <!-- Hero Section -->
  <section class="hero-section text-center">
    <div class="container">
      <h1 class="display-4">Unlock Your Potential</h1>
      <p class="lead mt-3">Join a thriving community of professionals, innovators, and leaders. Grow your network, enhance your skills, and accelerate your career with us.</p>
    </div>
  </section>

  <div class="container">
    <div class="login-form-container">
      <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <h3 class="font-weight-bold text-primary">Welcome Back, Admin!</h3>
          <p class="text-muted">Log in to access the management dashboard, oversee members, and view system analytics. Your community is waiting for your leadership.</p>
        </div>
        <div class="col-lg-6">
          <h5 class="text-center font-weight-bold">Administrator Login</h5>
          <?php if (isset($error_message)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error_message) ?></div>
          <?php endif; ?>
          <form action="" method="POST">
            <div class="input-group mb-3">
              <input type="email" class="form-control" name="email" placeholder="Email" required>
              <div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>
            </div>
            <div class="input-group mb-3">
              <input type="password" class="form-control" name="password" placeholder="Password" required>
              <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
            </div>
            <button type="submit" name="login" class="btn btn-primary btn-block font-weight-bold">Secure Sign In</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Why Join Section -->
  <section id="benefits" class="section">
    <div class="container">
      <h2 class="section-title text-center">Why You'll Love Being a Member</h2>
      <div class="row mt-5">
        <div class="col-md-6 col-lg-3 mb-4">
          <div class="benefit-card text-center">
            <div class="icon"><i class="fas fa-network-wired"></i></div>
            <h5 class="font-weight-bold">Expert Networking</h5>
            <p>Connect with industry pioneers and build relationships that matter at our curated events and online forums.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
          <div class="benefit-card text-center">
            <div class="icon"><i class="fas fa-lightbulb"></i></div>
            <h5 class="font-weight-bold">Exclusive Content</h5>
            <p>Gain unlimited access to our rich library of resources, including webinars, research papers, and case studies.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
          <div class="benefit-card text-center">
            <div class="icon"><i class="fas fa-chart-line"></i></div>
            <h5 class="font-weight-bold">Career Growth</h5>
            <p>Leverage our members-only job board, mentorship programs, and career development workshops to advance.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
          <div class="benefit-card text-center">
            <div class="icon"><i class="fas fa-tools"></i></div>
            <h5 class="font-weight-bold">Skill Development</h5>
            <p>Sharpen your expertise with hands-on training sessions and certification courses led by industry veterans.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Membership Tiers Section -->
  <section id="tiers" class="section bg-light">
      <div class="container">
          <h2 class="section-title text-center">Find the Perfect Plan for You</h2>
          <div class="tier-table table-responsive mt-5">
              <table class="table table-striped table-bordered text-center">
                  <thead class="thead-dark">
                      <tr>
                          <th scope="col" class="text-left">Feature</th>
                          <th scope="col">Bronze Member</th>
                          <th scope="col">Silver Member</th>
                          <th scope="col">Gold Member</th>
                      </tr>
                  </thead>
                  <tbody>
                      <tr>
                          <th scope="row" class="text-left">Monthly Newsletter</th>
                          <td><i class="fas fa-check-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                      <tr>
                          <th scope="row" class="text-left">Access to Online Forums</th>
                          <td><i class="fas fa-check-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                       <tr>
                          <th scope="row" class="text-left">Digital Membership Badge</th>
                          <td><i class="fas fa-check-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                      <tr>
                          <th scope="row" class="text-left">Entry to Monthly Webinars</th>
                          <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                      <tr>
                          <th scope="row" class="text-left">Full Resource Library Access</th>
                          <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                      <tr>
                          <th scope="row" class="text-left">Members-Only Job Board</th>
                          <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                      <tr>
                          <th scope="row" class="text-left">Exclusive Partner Discounts</th>
                          <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                      <tr>
                          <th scope="row" class="text-left">Priority Event Registration</th>
                           <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                       <tr>
                          <th scope="row" class="text-left">1-on-1 Mentorship Program</th>
                           <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                       <tr>
                          <th scope="row" class="text-left">Voting Rights in Community Polls</th>
                           <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                       <tr>
                          <th scope="row" class="text-left">Feature in Member Spotlight</th>
                           <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-times-circle"></i></td>
                          <td><i class="fas fa-check-circle"></i></td>
                      </tr>
                  </tbody>
              </table>
          </div>
      </div>
  </section>
  
  <!-- Stats Section -->
  <section class="stats-section section">
      <div class="container text-center">
          <div class="row">
              <div class="col-md-4 stat-item">
                  <h3>5,000+</h3>
                  <p>Active Members</p>
              </div>
              <div class="col-md-4 stat-item">
                  <h3>50+</h3>
                  <p>Events Annually</p>
              </div>
              <div class="col-md-4 stat-item">
                  <h3>100+</h3>
                  <p>Exclusive Resources</p>
              </div>
          </div>
      </div>
  </section>
  
  <!-- Testimonials Section -->
  <section id="testimonials" class="section">
    <div class="container">
      <h2 class="section-title text-center">What Our Members Say</h2>
        <div id="testimonial-carousel" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="d-flex flex-column align-items-center text-center">
                        <img src="https://placehold.co/100x100/007BFF/white?text=A.J." alt="Alex Johnson" class="testimonial-img mb-3">
                        <p class="lead">"Joining this community was a career-defining move. The connections I've made and the skills I've learned have been invaluable. I highly recommend it to anyone serious about their professional growth."</p>
                        <footer class="font-weight-bold">- Alex Johnson, Gold Member</footer>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="d-flex flex-column align-items-center text-center">
                        <img src="https://placehold.co/100x100/28A745/white?text=S.L." alt="Samantha Lee" class="testimonial-img mb-3">
                        <p class="lead">"The resource library alone is worth the price of admission. The monthly webinars keep me updated on the latest industry trends, and I've met some fantastic people in the forums."</p>
                        <footer class="font-weight-bold">- Samantha Lee, Silver Member</footer>
                    </div>
                </div>
            </div>
            <a class="carousel-control-prev text-dark" href="#testimonial-carousel" role="button" data-slide="prev">
                <span class="fas fa-chevron-left fa-2x" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next text-dark" href="#testimonial-carousel" role="button" data-slide="next">
                <span class="fas fa-chevron-right fa-2x" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a>
        </div>
    </div>
  </section>
  
  <!-- FAQ Section -->
  <section id="faq" class="section bg-light">
    <div class="container">
        <h2 class="section-title text-center">Frequently Asked Questions</h2>
        <div class="accordion" id="faqAccordion">
            <div class="card">
                <div class="card-header" id="headingOne" data-toggle="collapse" data-target="#collapseOne">
                    <h5 class="mb-0">How do I become a member?</h5>
                </div>
                <div id="collapseOne" class="collapse show" data-parent="#faqAccordion">
                    <div class="card-body">
                        Becoming a member is easy! Simply navigate to our registration page, choose a membership tier that fits your needs, and fill out the application form. Our team will review it and get back to you within 48 hours.
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="headingTwo" data-toggle="collapse" data-target="#collapseTwo">
                    <h5 class="mb-0">Can I upgrade my membership plan later?</h5>
                </div>
                <div id="collapseTwo" class="collapse" data-parent="#faqAccordion">
                    <div class="card-body">
                        Absolutely! You can upgrade your membership at any time through your member dashboard. The new benefits will be available to you immediately after the upgrade is processed.
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="headingThree" data-toggle="collapse" data-target="#collapseThree">
                    <h5 class="mb-0">What is the cancellation policy?</h5>
                </div>
                <div id="collapseThree" class="collapse" data-parent="#faqAccordion">
                    <div class="card-body">
                        We offer a flexible membership model. You can cancel your subscription at any time from your account settings. Your membership will remain active until the end of the current billing cycle.
                    </div>
                </div>
            </div>
        </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="main-footer">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <strong>&copy; <?= date('Y') ?> Membership Management System.</strong> All rights reserved.
        </div>
        <div class="col-md-6 text-md-right">
          <a href="#" class="text-light mx-2"><i class="fab fa-twitter fa-lg"></i></a>
          <a href="#" class="text-light mx-2"><i class="fab fa-facebook-f fa-lg"></i></a>
          <a href="#" class="text-light mx-2"><i class="fab fa-linkedin-in fa-lg"></i></a>
        </div>
      </div>
    </div>
  </footer>

</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>

</body>
</html>


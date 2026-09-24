<?php
// Start the session at the very top of the file to access user data
session_start();

// SECURITY CHECK: If the user is not logged in, redirect them to the login page.
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit(); // Stop the script from executing further
}

// Get the username from the session to display it on the page
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AptitudeHub - Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        a {
            text-decoration: none;
            color: #0066cc;
            transition: color 0.3s;
        }
        
        a:hover {
            color: #004080;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
            margin-right: 10px;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            color: #0066cc;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
        }
        
        .nav-menu li {
            margin-left: 20px;
        }
        
        .nav-menu a {
            font-weight: 500;
            padding: 5px 0;
            position: relative;
        }
        
        .nav-menu a:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #0066cc;
            transition: width 0.3s;
        }
        
        .nav-menu a:hover:after {
            width: 100%;
        }
        
        .auth-buttons {
            display: flex;
            align-items: center;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login {
            background-color: transparent;
            color: #0066cc;
            border: 1px solid #0066cc;
            margin-right: 10px;
        }
        
        .btn-login:hover {
            background-color: #0066cc;
            color: #fff;
        }
        
        .btn-signup {
            background-color: #0066cc;
            color: #fff;
            border: 1px solid #0066cc;
        }
        
        .btn-signup:hover {
            background-color: #004080;
            border-color: #004080;
        }
        
        /* Style for the welcome message */
        .welcome-user {
            font-weight: 500;
            margin-right: 15px;
            color: #333;
        }

        .mobile-menu-btn {
            display: none;
            font-size: 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            color: #0066cc;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(to right, #0066cc, #004080);
            color: #fff;
            padding: 60px 0;
            text-align: center;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .btn-primary {
            background-color: #fff;
            color: #0066cc;
            padding: 12px 24px;
            font-size: 1.1rem;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #f0f0f0;
        }
        
        .btn-outline {
            background-color: transparent;
            color: #fff;
            border: 2px solid #fff;
            padding: 12px 24px;
            font-size: 1.1rem;
        }
        
        .btn-outline:hover {
            background-color: #fff;
            color: #0066cc;
        }
        
        /* Categories Section */
        .categories {
            padding: 60px 0;
        }
        
        .section-heading {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .section-heading h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .section-heading p {
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .category-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 25px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .category-icon {
            font-size: 2.5rem;
            color: #0066cc;
            margin-bottom: 15px;
        }
        
        .category-card h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .category-card p {
            color: #666;
            margin-bottom: 15px;
        }
        
        /* Features Section */
        .features {
            background-color: #f0f5ff;
            padding: 60px 0;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .feature-icon {
            font-size: 2rem;
            color: #0066cc;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        /* Testimonials */
        .testimonials {
            padding: 60px 0;
        }
        
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .testimonial-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 25px;
            position: relative;
        }
        
        .testimonial-card:before {
            content: '"';
            font-size: 4rem;
            color: #e6e6e6;
            position: absolute;
            top: 10px;
            left: 15px;
            line-height: 1;
        }
        
        .testimonial-text {
            margin-bottom: 20px;
            font-style: italic;
            color: #555;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            background-color: #e6e6e6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-weight: bold;
        }
        
        .author-info h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .author-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* CTA Section */
        .cta {
            background: linear-gradient(to right, #0066cc, #004080);
            color: #fff;
            padding: 80px 0;
            text-align: center;
        }
        
        .cta h2 {
            font-size: 2.2rem;
            margin-bottom: 20px;
        }
        
        .cta p {
            max-width: 600px;
            margin: 0 auto 30px;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Footer */
        footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 60px 0 20px;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            color: #fff;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #bdc3c7;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #fff;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: #fff;
            transition: background-color 0.3s;
        }
        
        .social-links a:hover {
            background-color: #0066cc;
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #bdc3c7;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/197b064b-de6d-46ec-b8d2-efa144a75443.png" alt="AptitudeHub Logo">
                    <h1>AptitudeHub</h1>
                </div>
                
                <button class="mobile-menu-btn">☰</button>
                
                <ul class="nav-menu">
                    <li><a href="dashboard.php">Home</a></li>
                    <li><a href="aptitudetests.html">Aptitude Tests</a></li>
                    <li><a href="interviewquestions.html">Interview Questions</a></li>
                    <li><a href="online-tests.html">Online Tests</a></li>
                    <li><a href="resources.html">Resources</a></li>
                </ul>
                
                <div class="auth-buttons">
                    <span class="welcome-user">Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                    <a href="logout.php" class="btn btn-login">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Master Aptitude Tests & Land Your Dream Job</h2>
                <p>Prepare for placement exams, competitive tests, and interviews with our comprehensive collection of practice questions and mock tests.</p>
                <div class="hero-buttons">
                    <button class="btn btn-primary" onclick="window.location.href='practice.html'">Start Practicing Now</button>
                    <button class="btn btn-outline" onclick="window.location.href='free-test.html'">Earn Badges</button>
                </div>
            </div>
        </div>
    </section>

    <section class="categories">
        <div class="container">
            <div class="section-heading">
                <h2>Explore Aptitude Categories</h2>
                <p>Practice questions from all major aptitude categories to boost your test performance</p>
            </div>
            
            <div class="category-grid">
                <div class="category-card">
                    <div class="category-icon">🧮</div>
                    <h3>Quantitative Aptitude</h3>
                    <p>Numbers, calculations, and mathematical problems</p>
                    <a href="quantitative.html">Practice Now →</a>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">💡</div>
                    <h3>Logical Reasoning</h3>
                    <p>Patterns, sequences, and logical puzzles</p>
                    <a href="logical.html">Practice Now →</a>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">🔤</div>
                    <h3>Verbal Ability</h3>
                    <p>Grammar, vocabulary, and comprehension</p>
                    <a href="verbal.html">Practice Now →</a>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">🧩</div>
                    <h3>Puzzles</h3>
                    <p>Brain teasers and problem-solving challenges</p>
                    <a href="puzzles.html">Practice Now →</a>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="section-heading">
                <h2>Why Choose AptitudeHub?</h2>
                <p>We provide the best platform for aptitude test preparation</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Detailed Analytics</h3>
                    <p>Track your performance with in-depth analytics and identify areas for improvement.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h3>Unlimited Practice</h3>
                    <p>Access thousands of questions across all categories with detailed explanations.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">⏱️</div>
                    <h3>Timed Tests</h3>
                    <p>Simulate real exam conditions with our timed test feature to improve your speed.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <div class="container">
            <div class="section-heading">
                <h2>What Our Users Say</h2>
                <p>Join thousands of successful candidates who aced their exams with AptitudeHub</p>
            </div>
            
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <p class="testimonial-text">AptitudeHub helped me tremendously in my campus placements. The practice tests mirrored the actual difficulty level of company tests.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">RK</div>
                        <div class="author-info">
                            <h4>Rahul Kumar</h4>
                            <p>Placed at TCS</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <p class="testimonial-text">The detailed explanations for each question helped me understand concepts better. I improved my score by 40% in just one month!</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">PM</div>
                        <div class="author-info">
                            <h4>Priya Mishra</h4>
                            <p>CAT Aspirant</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <p class="testimonial-text">As a working professional, I needed a flexible way to prepare for competitive exams. AptitudeHub's mobile-friendly interface was perfect.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">AS</div>
                        <div class="author-info">
                            <h4>Ankit Singh</h4>
                            <p>Bank PO Aspirant</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
  
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>AptitudeHub</h3>
                    <p>The premier platform for aptitude test preparation and interview questions.</p>
                    <div class="social-links">
                        <a href="#">FB</a>
                        <a href="#">TW</a>
                        <a href="#">IG</a>
                        <a href="#">LI</a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="dashboard.php">Home</a></li>
                        <li><a href="aptitudetests.html">Aptitude Tests</a></li>
                        <li><a href="interviewquestions.html">Interview Questions</a></li>
                        <li><a href="online-tests.html">Online Tests</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Categories</h3>
                    <ul class="footer-links">
                        <li><a href="quantitative.html">Quantitative Aptitude</a></li>
                        <li><a href="logical.html">Logical Reasoning</a></li>
                        <li><a href="verbal.html">Verbal Ability</a></li>
                        <li><a href="puzzles.html">Puzzles</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>© AptitudeHub. All rights reserved. Created by Priti Yadav.</p>
            </div>
        </div>
    </footer>
    
    </body>
</html>
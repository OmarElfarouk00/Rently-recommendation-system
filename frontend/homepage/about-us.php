<?php
// Start session if needed
session_start();
require_once 'php files/config.php'; // Adjust path as needed

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : null;

// // Fetch company information from database
//     $stmt = $pdo->prepare("SELECT * FROM company_info WHERE id = 1");
//     $stmt->execute();
//     $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no company info exists, use defaults
    // if (!$company) {
    //     $company = [
    //         'name' => 'Real Estate Company',
    //         'founded_year' => '2010',
    //         'mission' => 'To help people find their dream homes and make property transactions seamless.',
    //         'vision' => 'To be the leading real estate platform that connects property owners and seekers worldwide.',
    //         'email' => 'contact@realestate.com',
    //         'phone' => '+1 (555) 123-4567',
    //         'address' => '123 Property Street, Real Estate City, 10001'
    //     ];
    // }
    
$company = [
    'name' => 'Real Estate Company',
    'founded_year' => '2010',
    'mission' => 'To help people find their dream homes and make property transactions seamless.',
    'vision' => 'To be the leading real estate platform that connects property owners and seekers worldwide.',
    'email' => 'contact@realestate.com',
    'phone' => '+1 (555) 123-4567',
    'address' => '123 Property Street, Real Estate City, 10001'
];

$team = [
    ['id' => 1, 'name' => 'John Doe', 'position' => 'CEO & Founder', 'bio' => 'With over 15 years of experience in real estate.', 'image_path' => 'images/team/john.jpg'],
    ['id' => 2, 'name' => 'Jane Smith', 'position' => 'Head of Sales', 'bio' => 'Expert in property valuation and market trends.', 'image_path' => 'images/team/jane.jpg'],
    ['id' => 3, 'name' => 'Michael Brown', 'position' => 'Lead Developer', 'bio' => 'Creates innovative solutions for our platform.', 'image_path' => 'images/team/michael.jpg']
];

$testimonials = [
    ['id' => 1, 'content' => 'Found my dream home through this platform. Excellent service!', 'rating' => 5, 'full_name' => 'Sarah Johnson', 'profile_image' => 'images/clients/sarah.jpg'],
    ['id' => 2, 'content' => 'Very professional team and easy to use website.', 'rating' => 4, 'full_name' => 'Robert Williams', 'profile_image' => 'images/clients/robert.jpg']
];

// Define milestones for the timeline
$milestones = [
    ['year' => $company['founded_year'], 'title' => 'Company Founded', 'description' => 'Our journey began with a vision to transform the real estate industry.'],
    ['year' => '2015', 'title' => 'Expanded Services', 'description' => 'Introduced property management and investment advisory services.'],
    ['year' => '2018', 'title' => 'Digital Transformation', 'description' => 'Launched our online platform to connect property owners and seekers.'],
    ['year' => '2020', 'title' => 'International Expansion', 'description' => 'Extended our services to international markets.'],
    ['year' => date('Y'), 'title' => 'Continuing Growth', 'description' => 'Constantly innovating and improving our services for our clients.']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo htmlspecialchars($company['name']); ?></title>
    <link rel="stylesheet" href="styles.css">
<style>
    /* General Styles */

    .header {
    background: white;
    padding: 0.5rem 14.4px 1.5%;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.user-profile {
    margin-right: 2%;
    margin-left: auto;
}

:root {
    --primary-color: #2c3e50;
    --secondary-color: #e74c3c;
    --accent-color: #3498db;
    --light-color: #f8f9fa;
    --dark-color: #343a40;
    --text-color: #333;
    --text-light: #6c757d;
    --border-color: #dee2e6;
    --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    color: var(--text-color);
    line-height: 1.6;
    overflow-x: hidden;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

h1, h2, h3, h4, h5, h6 {
    font-family: 'Playfair Display', serif;
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 15px;
}

p {
    margin-bottom: 15px;
}

img {
    max-width: 100%;
    height: auto;
    display: block;
}

.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-header h2 {
    font-size: 2.5rem;
    color: var(--primary-color);
    position: relative;
    display: inline-block;
}

.section-divider {
    width: 80px;
    height: 3px;
    background-color: var(--secondary-color);
    margin: 15px auto 20px;
}

section {
    padding: 80px 0;
}

/* Hero Section */
.hero-section {
    position: relative;
    height: 500px;
    background-image: url('../images/about/hero-bg.jpg');
    background-size: cover;
    background-position: center;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(44, 62, 80, 0.7), rgba(44, 62, 80, 0.9));
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 800px;
    padding: 0 20px;
}

.hero-content h1 {
    font-size: 3.5rem;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.hero-content p {
    font-size: 1.2rem;
    margin-bottom: 30px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Company Overview Section */
.company-overview {
    background-color: var(--light-color);
}

.overview-content {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 40px;
}

.overview-image {
    flex: 1;
    min-width: 300px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

.overview-text {
    flex: 1;
    min-width: 300px;
}

.overview-text h3 {
    color: var(--secondary-color);
    font-size: 1.8rem;
    margin-bottom: 20px;
}

.stats-container {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
    padding: 20px;
    flex: 1;
    min-width: 120px;
}

.stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 10px;
}

.stat-label {
    font-size: 1rem;
    color: var(--text-light);
}

/* Mission & Vision Section */
.mission-vision {
    background-color: white;
}

.mission-vision-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
}

.mission-box, .vision-box {
    flex: 1;
    min-width: 300px;
    padding: 40px;
    border-radius: 10px;
    box-shadow: var(--shadow);
    text-align: center;
    transition: var(--transition);
}

.mission-box:hover, .vision-box:hover {
    transform: translateY(-10px);
}

.mission-box {
    background-color: var(--primary-color);
    color: white;
}

.vision-box {
    background-color: var(--secondary-color);
    color: white;
}

.icon-container {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.icon-container i {
    font-size: 2rem;
}

/* Values Section */
.values-section {
    background-color: var(--light-color);
}

.values-container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    justify-content: center;
}

.value-item {
    flex: 1;
    min-width: 250px;
    max-width: 300px;
    padding: 30px;
    background-color: white;
    border-radius: 10px;
    box-shadow: var(--shadow);
    text-align: center;
    transition: var(--transition);
}

.value-item:hover {
    transform: translateY(-10px);
}

.value-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.value-icon i {
    font-size: 1.8rem;
}

.value-item h3 {
    color: var(--primary-color);
    margin-bottom: 15px;
}

/* Timeline Section */
.timeline-section {
    background-color: white;
    position: relative;
}

.timeline {
    position: relative;
    max-width: 1000px;
    margin: 0 auto;
}

.timeline::after {
    content: '';
    position: absolute;
    width: 6px;
    background-color: var(--border-color);
    top: 0;
    bottom: 0;
    left: 50%;
    margin-left: -3px;
}

.timeline-item {
    padding: 10px 40px;
    position: relative;
    width: 50%;
    box-sizing: border-box;
}

.timeline-item::after {
    content: '';
    position: absolute;
    width: 25px;
    height: 25px;
    background-color: white;
    border: 4px solid var(--secondary-color);
    border-radius: 50%;
    top: 15px;
    z-index: 1;
}

.left {
    left: 0;
}

.right {
    left: 50%;
}

.left::after {
    right: -12px;
}

.right::after {
    left: -12px;
}

.timeline-content {
    padding: 20px;
    background-color: white;
    border-radius: 10px;
    box-shadow: var(--shadow);
    position: relative;
}

.timeline-year {
    display: inline-block;
    padding: 5px 15px;
    background-color: var(--secondary-color);
    color: white;
    border-radius: 20px;
    font-weight: 600;
    margin-bottom: 15px;
}

/* Team Section */
.team-section {
    background-color: var(--light-color);
}

.team-container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    justify-content: center;
}

.team-member {
    flex: 1;
    min-width: 250px;
    max-width: 300px;
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.team-member:hover {
    transform: translateY(-10px);
}

.member-image {
    position: relative;
    overflow: hidden;
    height: 300px;
}

.member-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.team-member:hover .member-image img {
    transform: scale(1.1);
}

.member-social {
    position: absolute;
    bottom: -50px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: 10px;
    padding: 15px 0;
    background-color: rgba(44, 62, 80, 0.8);
    transition: var(--transition);
}

.team-member:hover .member-social {
    bottom: 0;
}

.member-social a {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background-color: white;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.member-social a:hover {
    background-color: var(--secondary-color);
    color: white;
}

.member-info {
    padding: 20px;
    text-align: center;
}

.member-position {
    color: var(--secondary-color);
    font-weight: 500;
    margin-bottom: 10px;
}

.member-bio {
    font-size: 0.9rem;
    color: var(--text-light);
}

/* Testimonials Section */
.testimonials-section {
    background-color: white;
    position: relative;
}

.testimonials-slider {
    display: flex;
    overflow: hidden;
    position: relative;
}

.testimonial-item {
    min-width: 100%;
    padding: 20px;
    transition: var(--transition);
}

.testimonial-content {
    background-color: var(--light-color);
    padding: 30px;
    border-radius: 10px;
    position: relative;
    margin-bottom: 30px;
}

.testimonial-content::after {
    content: '';
    position: absolute;
    bottom: -15px;
    left: 50px;
    width: 30px;
    height: 30px;
    background-color: var(--light-color);
    transform: rotate(45deg);
}

.quote-icon {
    font-size: 2rem;
    color: var(--secondary-color);
    opacity: 0.3;
    margin-bottom: 15px;
}

.testimonial-rating {
    margin-top: 15px;
}

.testimonial-rating .fa-star {
    color: #ccc;
    margin-right: 3px;
}

.testimonial-rating .fa-star.active {
    color: #ffc107;
}

.testimonial-author {
    display: flex;
    align-items: center;
}

.author-image {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 15px;
}

.author-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-info h4 {
    margin-bottom: 5px;
}

.author-info p {
    color: var(--text-light);
    font-size: 0.9rem;
    margin: 0;
}

.testimonial-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 30px;
}

.prev-testimonial, .next-testimonial {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.prev-testimonial:hover, .next-testimonial:hover {
    background-color: var(--secondary-color);
}

.testimonial-dots {
    display: flex;
    gap: 8px;
    margin: 0 15px;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: var(--border-color);
    cursor: pointer;
    transition: var(--transition);
}

.dot.active {
    background-color: var(--secondary-color);
}

/* Contact Section */
.contact-section {
    background-color: var(--light-color);
}

.contact-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
}

.contact-info, .contact-form {
    flex: 1;
    min-width: 300px;
}

.contact-details {
    margin: 30px 0;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
}

.contact-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.contact-text h4 {
    margin-bottom: 5px;
}

.social-links {
    display: flex;
    gap: 15px;
}

.social-link {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: var(--transition);
}

.social-link:hover {
    background-color: var(--secondary-color);
    transform: translateY(-5px);
}

.contact-form {
    background-color: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: var(--shadow);
}

.contact-form h3 {
    margin-bottom: 20px;
    color: var(--primary-color);
}

.form-group {
    margin-bottom: 20px;
}

.form-group input, .form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    transition: var(--transition);
}

.form-group input:focus, .form-group textarea:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

.form-group textarea {
    height: 150px;
    resize: vertical;
}

.submit-btn {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: var(--transition);
}

.submit-btn:hover {
    background-color: var(--secondary-color);
}

/* Responsive Styles */
@media (max-width: 992px) {
    .section-header h2 {
        font-size: 2rem;
    }
    
    .hero-content h1 {
        font-size: 2.8rem;
    }
    
    .timeline::after {
        left: 31px;
    }
    
    .timeline-item {
        width: 100%;
        padding-left: 70px;
        padding-right: 25px;
    }
    
    .timeline-item::after {
        left: 18px;
    }
    
    .left::after, .right::after {
        left: 18px;
    }
    
    .right {
        left: 0;
    }
}

@media (max-width: 768px) {
    section {
        padding: 60px 0;
    }
    
    .hero-section {
        height: 400px;
    }
    
    .hero-content h1 {
        font-size: 2.2rem;
    }
    
    .hero-content p {
        font-size: 1rem;
    }
    
    .overview-content, .mission-vision-wrapper, .values-container, .team-container {
        gap: 20px;
    }
    
    .stat-item {
        min-width: 100px;
    }
    
    .stat-number {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .section-header h2 {
        font-size: 1.8rem;
    }
    
    .hero-content h1 {
        font-size: 1.8rem;
    }
    
    .stats-container {
        flex-direction: column;
        gap: 10px;
    }
    
    .stat-item {
        padding: 10px;
    }
}
</style>   
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Add Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Include your header/navigation here -->
        <!-- Header (Same as index.php) -->
        <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-home"></i>
                <a href="index.php" class="logo">RentEstate</a>
            </div>

                            <?php if ($isLoggedIn): ?>
                <!-- User Profile with Active Status -->
                <div class="user-profile">
                    <div class="user-status">
                        <span class="status-indicator"></span>
                        <span class="username"><?php echo $_SESSION['user_name']; ?></span>
                    </div>
                </div>
            <?php endif; ?>


            <div class="user-menu">
                <div class="user-menu-item menu-dropdown">
                    <i class="fas fa-bars" id="menuToggle"></i>
                    <div class="menu-content" id="menuContent">
                        <?php if (!$isLoggedIn): ?>
                            <a href="../login-signup/index.php" class="menu-item">
                                <i class="fas fa-sign-in-alt"></i>
                                Login
                            </a>
                            <a href="../login-signup/signup.php" class="menu-item">
                                <i class="fas fa-user-plus"></i>
                                Sign Up
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!$isLoggedIn): ?>
                            <a href="../login-signup/index.php" class="menu-item">
                                <i class="fas fa-arrow-right"></i>
                                Become a host
                            </a>
                        <?php else: ?>
                            <a href="become-host.php" class="menu-item">
                                <i class="fas fa-arrow-right"></i>
                                Become a host
                            </a>
                        <?php endif; ?>
                        
                        <a href="index.php" class="menu-item" id="dashboard-toggle">
                            <i class="fas fa-home"></i>
                            dashboard
                        </a>
                        
                        <div class="menu-divider"></div>
                        <a href="#" class="menu-item" id="language-toggle">
                            <i class="fas fa-globe"></i>
                            Language
                        </a>
                        <a href="help-center.php" class="menu-item">
                            <i class="fas fa-question-circle"></i>
                            Help Center
                        </a>

                        <div class="language-menu" id="language-menu">
                            <ul>
                                <li><a href="#" data-lang="en">English</a></li>
                                <li><a href="#" data-lang="es">Spanish</a></li>
                                <li><a href="#" data-lang="fr">French</a></li>
                                <li><a href="#" data-lang="de">German</a></li>
                                <li><a href="#" data-lang="zh">Chinese</a></li>
                            </ul>
                        </div>
                        
                        <a href="#" class="menu-item">
                            <i class="fas fa-info-circle"></i>
                            About Us
                        </a>

                        <?php if ($isLoggedIn): ?>
                            <div class="menu-divider"></div>
                            <a href="../login-signup/php files/logout.php" class="menu-item">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>About Us</h1>
            <p>Learn about our journey, our team, and our mission to transform real estate experiences</p>
        </div>
    </section>
    
    <!-- Company Overview Section -->
    <section class="company-overview">
        <div class="container">
            <div class="section-header">
                <h2>Our Story</h2>
                <div class="section-divider"></div>
            </div>
            
            <div class="overview-content">
                <div class="overview-image">
                    <img src="images/about/company-building.jpg" alt="<?php echo htmlspecialchars($company['name']); ?> Building">
                </div>
                <div class="overview-text">
                    <h3>Established in <?php echo htmlspecialchars($company['founded_year']); ?></h3>
                    <p>Welcome to <?php echo htmlspecialchars($company['name']); ?>, where we believe that finding the perfect property should be an exciting journey, not a stressful process.</p>
                    <p>Since our founding in <?php echo htmlspecialchars($company['founded_year']); ?>, we've been dedicated to creating meaningful connections between property owners and those seeking their ideal spaces.</p>
                    <p>Our platform combines cutting-edge technology with personalized service to make property transactions seamless, transparent, and enjoyable for everyone involved.</p>
                    
                    <div class="stats-container">
                        <div class="stat-item">
                            <span class="stat-number" data-count="1000">0</span>
                            <span class="stat-label">Properties Listed</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" data-count="500">0</span>
                            <span class="stat-label">Happy Clients</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" data-count="50">0</span>
                            <span class="stat-label">Team Members</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Mission & Vision Section -->
    <section class="mission-vision">
        <div class="container">
            <div class="mission-vision-wrapper">
                <div class="mission-box">
                    <div class="icon-container">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Our Mission</h3>
                    <p><?php echo htmlspecialchars($company['mission']); ?></p>
                </div>
                
                <div class="vision-box">
                    <div class="icon-container">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p><?php echo htmlspecialchars($company['vision']); ?></p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Values Section -->
    <section class="values-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Core Values</h2>
                <div class="section-divider"></div>
            </div>
            
            <div class="values-container">
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>We conduct our business with honesty, transparency, and ethical standards that build trust with our clients.</p>
                </div>
                
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Excellence</h3>
                    <p>We strive for excellence in every aspect of our service, constantly improving to exceed expectations.</p>
                </div>
                
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Client-Focused</h3>
                    <p>Our clients' needs and satisfaction are at the center of everything we do.</p>
                </div>
                
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>We embrace new technologies and ideas to continuously improve our services and platform.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Timeline Section -->
    <section class="timeline-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Journey</h2>
                <div class="section-divider"></div>
            </div>
            
            <div class="timeline">
                <?php foreach ($milestones as $index => $milestone): ?>
                <div class="timeline-item <?php echo $index % 2 == 0 ? 'left' : 'right'; ?>">
                    <div class="timeline-content">
                        <div class="timeline-year"><?php echo htmlspecialchars($milestone['year']); ?></div>
                        <h3><?php echo htmlspecialchars($milestone['title']); ?></h3>
                        <p><?php echo htmlspecialchars($milestone['description']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="section-header">
                <h2>Meet Our Team</h2>
                <div class="section-divider"></div>
                <p>The passionate professionals behind our success</p>
            </div>
            
            <div class="team-container">
                <?php foreach ($team as $member): ?>
                <div class="team-member">
                    <div class="member-image">
                        <img src="<?php echo htmlspecialchars($member['image_path']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                        <div class="member-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-facebook"></i></a>
                        </div>
                    </div>
                    <div class="member-info">
                        <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                        <p class="member-position"><?php echo htmlspecialchars($member['position']); ?></p>
                        <p class="member-bio"><?php echo htmlspecialchars($member['bio']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="container">
            <div class="section-header">
                <h2>What Our Clients Say</h2>
                <div class="section-divider"></div>
            </div>
            
            <div class="testimonials-slider">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-item">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p><?php echo htmlspecialchars($testimonial['content']); ?></p>
                        <div class="testimonial-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-image">
                            <img src="<?php echo htmlspecialchars($testimonial['profile_image']); ?>" alt="<?php echo htmlspecialchars($testimonial['full_name']); ?>">
                        </div>
                        <div class="author-info">
                            <h4><?php echo htmlspecialchars($testimonial['full_name']); ?></h4>
                            <p>Client</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="testimonial-controls">
                <button class="prev-testimonial"><i class="fas fa-chevron-left"></i></button>
                <div class="testimonial-dots"></div>
                <button class="next-testimonial"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>
    
    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-wrapper">
                <div class="contact-info">
                    <h2>Get In Touch</h2>
                    <div class="section-divider"></div>
                    <p>We'd love to hear from you. Contact us with any questions or inquiries.</p>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-text">
                                <h4>Address</h4>
                                <p><?php echo htmlspecialchars($company['address']); ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-text">
                                <h4>Phone</h4>
                                <p><?php echo htmlspecialchars($company['phone']); ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-text">
                                <h4>Email</h4>
                                <p><?php echo htmlspecialchars($company['email']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="contact-form">
                    <h3>Send us a message</h3>
                    <form id="contact-form" action="php files/submit_contact.php" method="POST">
                        <div class="form-group">
                            <input type="text" name="name" id="name" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" id="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="subject" id="subject" placeholder="Subject">
                        </div>
                        <div class="form-group">
                            <textarea name="message" id="message" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Include your footer here -->
    
    <script src="script.js" defer>
        document.addEventListener('DOMContentLoaded', function() {
    // Animate stats counter
    animateStats();
    
    // Initialize testimonials slider
    initTestimonialsSlider();
    
    // Initialize contact form
    initContactForm();
    
    // Add scroll animations
    addScrollAnimations();
});

// Function to animate stats counter
function animateStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-count'));
        const duration = 2000; // 2 seconds
        const step = target / (duration / 16); // 16ms is approx one frame at 60fps
        let current = 0;
        
        const updateCounter = () => {
            current += step;
            if (current < target) {
                stat.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                stat.textContent = target;
            }
        };
        
        // Start animation when element is in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(stat);
    });
}

// Function to initialize testimonials slider
function initTestimonialsSlider() {
    const slider = document.querySelector('.testimonials-slider');
    const items = document.querySelectorAll('.testimonial-item');
    const prevBtn = document.querySelector('.prev-testimonial');
    const nextBtn = document.querySelector('.next-testimonial');
    const dotsContainer = document.querySelector('.testimonial-dots');
    
    if (!slider || items.length === 0) return;
    
    let currentIndex = 0;
    const totalItems = items.length;
    
    // Create dots
    for (let i = 0; i < totalItems; i++) {
        const dot = document.createElement('div');
        dot.classList.add('dot');
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(i));
        dotsContainer.appendChild(dot);
    }
    
    // Update dots
    function updateDots() {
        const dots = document.querySelectorAll('.dot');
        dots.forEach((dot, index) => {
            if (index === currentIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }
    
    // Go to specific slide
    function goToSlide(index) {
        currentIndex = index;
        slider.style.transform = `translateX(-${currentIndex * 100}%)`;
        updateDots();
    }
    
    // Next slide
    function nextSlide() {
        currentIndex = (currentIndex + 1) % totalItems;
        goToSlide(currentIndex);
    }
    
    // Previous slide
    function prevSlide() {
        currentIndex = (currentIndex - 1 + totalItems) % totalItems;
        goToSlide(currentIndex);
    }
    
    // Add event listeners
    nextBtn.addEventListener('click', nextSlide);
    prevBtn.addEventListener('click', prevSlide);
    
    // Auto slide every 5 seconds
    let slideInterval = setInterval(nextSlide, 5000);
    
    // Pause auto slide on hover
    slider.addEventListener('mouseenter', () => {
        clearInterval(slideInterval);
    });
    
    slider.addEventListener('mouseleave', () => {
        slideInterval = setInterval(nextSlide, 5000);
    });
}

// Function to initialize contact form
function initContactForm() {
    const contactForm = document.getElementById('contact-form');
    
    if (!contactForm) return;
    
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(contactForm);
        
        // Show loading state
        const submitBtn = contactForm.querySelector('.submit-btn');
        const originalBtnText = submitBtn.textContent;
        submitBtn.textContent = 'Sending...';
        submitBtn.disabled = true;
        
        // Send form data using fetch API
        fetch(contactForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('Thank you! Your message has been sent successfully.');
                contactForm.reset();
            } else {
                // Show error message
                alert('Oops! Something went wrong. Please try again later.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Oops! Something went wrong. Please try again later.');
        })
        .finally(() => {
            // Reset button state
            submitBtn.textContent = originalBtnText;
            submitBtn.disabled = false;
        });
    });
}

// Function to add scroll animations
function addScrollAnimations() {
    // Add animation to elements when they come into view
    const animatedElements = document.querySelectorAll(
        '.overview-content, .mission-box, .vision-box, .value-item, .timeline-item, .team-member, .contact-wrapper'
    );
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });
    
    animatedElements.forEach(element => {
        // Set initial styles
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        observer.observe(element);
    });
}
    </script>
</body>
</html>
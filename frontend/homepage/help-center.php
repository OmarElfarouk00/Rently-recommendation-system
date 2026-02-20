<?php
session_start();
require_once 'php files/config.php';

// Get FAQ categories
$stmt = $pdo->query("SELECT * FROM faq_categories ORDER BY display_order");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured FAQs
$stmt = $pdo->query("SELECT * FROM faqs WHERE is_featured = 1 ORDER BY display_order LIMIT 5");
$featuredFaqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Search functionality
$searchResults = [];
$searchQuery = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
    $stmt = $pdo->prepare("
        SELECT f.*, c.name as category_name 
        FROM faqs f
        JOIN faq_categories c ON f.category_id = c.id
        WHERE f.question LIKE :search OR f.answer LIKE :search
        ORDER BY c.display_order, f.display_order
    ");
    $stmt->execute(['search' => '%' . $searchQuery . '%']);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - RentEstate</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
:root {
    --primary-color: #ee7238;
    --text-color: #2c3e50;
    --text-light: #6c757d;
    --light-color: #f8f9fa;
    --dark-color: #343a40;
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
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 15px;
    color: var(--text-color);
}

p {
    margin-bottom: 15px;
}

img {
    max-width: 100%;
    height: auto;
    display: block;
}

a {
    text-decoration: none;
    color: var(--primary-color);
    transition: var(--transition);
}

a:hover {
    color: var(--secondary-color);
}

.section {
    padding: 80px 0;
}

.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-header h2 {
    font-size: 2.5rem;
    color: var(--text-color);
    position: relative;
    display: inline-block;
}

.section-divider {
    width: 80px;
    height: 3px;
    background-color: var(--primary-color);
    margin: 15px auto 20px;
}

.btn-primary {
    display: inline-block;
    background-color: var(--primary-color);
    color: white;
    padding: 12px 25px;
    border-radius: 5px;
    font-weight: 500;
    text-align: center;
    border: none;
    cursor: pointer;
    transition: var(--transition);
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    color: white;
}

.btn-secondary {
    display: inline-block;
    background-color: var(--light-color);
    color: var(--text-color);
    padding: 12px 25px;
    border-radius: 5px;
    font-weight: 500;
    text-align: center;
    border: 1px solid var(--border-color);
    cursor: pointer;
    transition: var(--transition);
}

.btn-secondary:hover {
    background-color: var(--border-color);
}

/* Header Styles */
.header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background-color: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
}


.menu-button {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.menu-button:hover {
    background-color: var(--light-color);
}

.menu-dropdown {
    position: absolute;
    top: 70px;
    right: 20px;
    width: 200px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    padding: 10px 0;
    display: none;
    animation: fadeIn 0.3s ease;
    z-index: 1000;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.menu-dropdown.active {
    display: block;
}

.menu-nav {
    display: flex;
    flex-direction: column;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    color: var(--text-color);
    transition: var(--transition);
}

.menu-item.active {
    color: var(--primary-color);
    background-color: var(--light-color);
}

.menu-item:hover {
    background-color: var(--light-color);
    color: var(--primary-color);
}

.menu-divider {
    height: 1px;
    background-color: var(--border-color);
    margin: 5px 0;
}

.language-dropdown {
    position: relative;
}

.language-button {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
}

.language-options {
    position: absolute;
    top: 0;
    left: 100%;
    width: 150px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    padding: 10px 0;
    display: none;
    z-index: 10;
}

.language-options.active {
    display: block;
}

.language-option {
    display: block;
    padding: 8px 20px;
    color: var(--text-color);
    transition: var(--transition);
}

.language-option:hover {
    background-color: var(--light-color);
    color: var(--primary-color);
}

/* Hero Section */
.hero-section {
    position: relative;
    height: 400px;
    background-image: url('https://images.unsplash.com/photo-1560520653-9e0e4c89eb11?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1920&q=80');
    background-size: cover;
    background-position: center;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    margin-top: 70px;
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
    font-size: 3rem;
    margin-bottom: 30px;
    color: white;
}

.search-container {
    max-width: 600px;
    margin: 0 auto;
    position: relative;
}

.search-container input {
    width: 100%;
    padding: 15px 20px;
    padding-right: 50px;
    border: none;
    border-radius: 30px;
    font-size: 1rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.search-container button {
    position: absolute;
    right: 5px;
    top: 5px;
    background-color: var(--primary-color);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: var(--transition);
}

.search-container button:hover {
    background-color: var(--secondary-color);
}

/* Categories Section */
.categories-section {
    background-color: var(--light-color);
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
}

.category-card {
    background-color: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: var(--shadow);
    text-align: center;
    transition: var(--transition);
    display: block;
    color: var(--text-color);
}

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    color: var(--text-color);
}

.category-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 1.8rem;
}

.category-card h3 {
    margin-bottom: 10px;
}

.category-card p {
    color: var(--text-light);
    font-size: 0.9rem;
}

/* FAQ Section */
.faq-section {
    background-color: white;
}

.faq-list {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    margin-bottom: 20px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
}

.faq-question {
    padding: 20px;
    background-color: var(--light-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: var(--transition);
}

.faq-question:hover {
    background-color: #f0f0f0;
}

.faq-question h3 {
    margin: 0;
    font-size: 1.1rem;
    flex: 1;
}

.faq-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    transition: var(--transition);
}

.faq-item.active .faq-toggle {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item.active .faq-answer {
    padding: 20px;
    max-height: 1000px;
}

.faq-meta {
    margin-top: 15px;
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: var(--text-light);
}

.faq-category {
    background-color: var(--light-color);
    padding: 3px 10px;
    border-radius: 15px;
}

/* Contact Section */
.contact-section {
    background-color: var(--light-color);
}

.contact-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.contact-card {
    background-color: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: var(--shadow);
    text-align: center;
    transition: var(--transition);
}

.contact-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.contact-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 1.8rem;
}

.contact-card h3 {
    margin-bottom: 10px;
}

.contact-card p {
    color: var(--text-light);
    margin-bottom: 20px;
}

.contact-form-container {
    max-width: 800px;
    margin: 0 auto;
    background-color: white;
    border-radius: 10px;
    padding: 40px;
    box-shadow: var(--shadow);
}

.contact-form-container h3 {
    text-align: center;
    margin-bottom: 30px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    flex: 1;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    transition: var(--transition);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(238, 114, 56, 0.2);
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-checkbox input {
    width: auto;
}

.form-checkbox label {
    margin-bottom: 0;
}

/* Resources Section */
.resources-section {
    background-color: white;
}

.resources-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
}

.resource-card {
    background-color: var(--light-color);
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    transition: var(--transition);
    display: block;
    color: var(--text-color);
}

.resource-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow);
    color: var(--text-color);
}

.resource-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: white;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 1.5rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.resource-card h3 {
    margin-bottom: 10px;
}

.resource-card p {
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Search Results Section */
.search-results-section {
    background-color: white;
}

.no-results {
    text-align: center;
    padding: 50px 0;
}

.no-results i {
    font-size: 3rem;
    color: var(--text-light);
    margin-bottom: 20px;
}

.search-back {
    text-align: center;
    margin-top: 30px;
}

/* Chat Widget */
.chat-widget {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 350px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    display: none;
    z-index: 1000;
    transition: var(--transition);
}

.chat-widget.active {
    display: flex;
    flex-direction: column;
    height: 450px;
}

.chat-header {
    background-color: var(--primary-color);
    color: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header h3 {
    margin: 0;
    color: white;
}

.chat-header button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 1.2rem;
}

.chat-messages {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.message {
    display: flex;
    max-width: 80%;
}

.message.user {
    align-self: flex-end;
}

.message.support {
    align-self: flex-start;
}

.message-content {
    padding: 10px 15px;
    border-radius: 15px;
    position: relative;
}

.message.user .message-content {
    background-color: var(--primary-color);
    color: white;
    border-bottom-right-radius: 0;
}

.message.support .message-content {
    background-color: var(--light-color);
    border-bottom-left-radius: 0;
}

.message-time {
    display: block;
    font-size: 0.7rem;
    margin-top: 5px;
    opacity: 0.7;
}

.chat-input {
    display: flex;
    padding: 10px;
    border-top: 1px solid var(--border-color);
}

.chat-input input {
    flex: 1;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    margin-right: 10px;
}

.chat-input button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: var(--transition);
}

.chat-input button:hover {
    background-color: var(--secondary-color);
}

/* Footer */
.footer {
    background-color: var(--text-color);
    color: white;
    padding: 60px 0 20px;
}

.footer-content {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    margin-bottom: 40px;
}

.footer-logo {
    flex: 1;
    min-width: 250px;
}

.footer-logo .logo {
    color: white;
    margin-bottom: 15px;
    display: inline-block;
}

.footer-links {
    flex: 2;
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
}

.footer-column {
    flex: 1;
    min-width: 150px;
}

.footer-column h4 {
    color: white;
    margin-bottom: 20px;
    font-size: 1.2rem;
}

.footer-column ul {
    list-style: none;
}

.footer-column ul li {
    margin-bottom: 10px;
}

.footer-column ul li a {
    color: rgba(255, 255, 255, 0.7);
    transition: var(--transition);
}

.footer-column ul li a:hover {
    color: var(--primary-color);
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    flex-wrap: wrap;
    gap: 20px;
}

.footer-social {
    display: flex;
    gap: 15px;
}

.footer-social a {
    color: rgba(255, 255, 255, 0.7);
    transition: var(--transition);
}

.footer-social a:hover {
    color: var(--primary-color);
}

/* Responsive Styles */
@media (max-width: 992px) {
    .section-header h2 {
        font-size: 2rem;
    }
    
    .hero-content h1 {
        font-size: 2.5rem;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .chat-widget {
        width: 300px;
    }
}

@media (max-width: 768px) {
    .section {
        padding: 60px 0;
    }
    
    .hero-section {
        height: 350px;
    }
    
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .contact-form-container {
        padding: 30px 20px;
    }
    
    .chat-widget {
        bottom: 20px;
        right: 20px;
        width: calc(100% - 40px);
    }
}

@media (max-width: 576px) {
    .section-header h2 {
        font-size: 1.8rem;
    }
    
    .hero-content h1 {
        font-size: 1.8rem;
    }
    
    .search-container input {
        padding: 12px 15px;
        padding-right: 45px;
    }
    
    .search-container button {
        width: 35px;
        height: 35px;
    }
}
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-home"></i>
                <a href="index.php" class="logo">RentEstate</a>
            </div>

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
                        <a href="#" class="menu-item">
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
        <div class="container hero-content">
            <h1>How can we help you?</h1>
            <div class="search-container">
                <form action="help-center.php" method="GET">
                    <input type="text" name="search" placeholder="Search for help topics..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>
    </section>
    
    <?php if (!empty($searchQuery)): ?>
    <!-- Search Results Section -->
    <section class="section search-results-section">
        <div class="container">
            <div class="section-header">
                <h2>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
                <div class="section-divider"></div>
            </div>
            
            <?php if (count($searchResults) > 0): ?>
                <div class="faq-list">
                    <?php foreach ($searchResults as $faq): ?>
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                                <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                            </div>
                            <div class="faq-answer">
                                <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                <div class="faq-meta">
                                    <span class="faq-category"><?php echo htmlspecialchars($faq['category_name']); ?></span>
                                    <?php if (!empty($faq['last_updated'])): ?>
                                        <span class="faq-updated">Updated: <?php echo date('M d, Y', strtotime($faq['last_updated'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No results found</h3>
                    <p>We couldn't find any help articles matching your search. Please try different keywords or browse our help categories below.</p>
                </div>
            <?php endif; ?>
            
            <div class="search-back">
                <a href="help-center.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Help Center</a>
            </div>
        </div>
    </section>
    <?php else: ?>
    
    <!-- Help Categories Section -->
    <section class="section categories-section">
        <div class="container">
            <div class="section-header">
                <h2>Browse Help Topics</h2>
                <div class="section-divider"></div>
            </div>
            
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="help-category.php?id=<?php echo $category['id']; ?>" class="category-card">
                        <div class="category-icon">
                            <i class="<?php echo htmlspecialchars($category['icon_class']); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Popular Questions Section -->
    <section class="section faq-section">
        <div class="container">
            <div class="section-header">
                <h2>Frequently Asked Questions</h2>
                <div class="section-divider"></div>
            </div>
            
            <div class="faq-list">
                <?php foreach ($featuredFaqs as $faq): ?>
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                            <?php if (!empty($faq['last_updated'])): ?>
                                <div class="faq-meta">
                                    <span class="faq-updated">Updated: <?php echo date('M d, Y', strtotime($faq['last_updated'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Contact Support Section -->
    <section class="section contact-section">
        <div class="container">
            <div class="section-header">
                <h2>Still Need Help?</h2>
                <div class="section-divider"></div>
                <p>Our support team is here to assist you with any questions or issues you may have.</p>
            </div>
            
            <div class="contact-options">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email Support</h3>
                    <p>Send us an email and we'll get back to you within 24 hours.</p>
                    <a href="mailto:support@rentestate.com" class="btn-primary">Email Us</a>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3>Phone Support</h3>
                    <p>Call us directly for immediate assistance with urgent matters.</p>
                    <a href="tel:+15551234567" class="btn-primary">Call Now</a>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <h3>Live Chat</h3>
                    <p>Chat with our support team in real-time during business hours.</p>
                    <button id="openChatBtn" class="btn-primary">Start Chat</button>
                </div>
            </div>
            
            <div class="contact-form-container">
                <h3>Send Us a Message</h3>
                <form id="support-form" action="php files/submit_support.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Your Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Help Category</label>
                        <select id="category" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    <div class="form-group form-checkbox">
                        <input type="checkbox" id="priority" name="is_priority" value="1">
                        <label for="priority">This is an urgent matter</label>
                    </div>
                    <button type="submit" class="btn-primary">Submit Request</button>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Help Resources Section -->
    <section class="section resources-section">
        <div class="container">
            <div class="section-header">
                <h2>Additional Resources</h2>
                <div class="section-divider"></div>
            </div>
            
            <div class="resources-grid">
                <a href="#" class="resource-card">
                    <div class="resource-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>User Guide</h3>
                    <p>Complete documentation on how to use our platform</p>
                </a>
                
                <a href="#" class="resource-card">
                    <div class="resource-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <h3>Video Tutorials</h3>
                    <p>Step-by-step video guides for common tasks</p>
                </a>
                
                <a href="#" class="resource-card">
                    <div class="resource-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Legal Documents</h3>
                    <p>Terms of service, privacy policy, and other legal documents</p>
                </a>
                
                <a href="#" class="resource-card">
                    <div class="resource-icon">
                        <i class="fas fa-blog"></i>
                    </div>
                    <h3>Blog</h3>
                    <p>Latest news, tips, and updates from our team</p>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <a href="/" class="logo">RentEstate</a>
                    <p>Finding your perfect property match.</p>
                </div>
                
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Company</h4>
                        <ul>
                            <li><a href="/about-us.html">About Us</a></li>
                            <li><a href="/careers">Careers</a></li>
                            <li><a href="/blog">Blog</a></li>
                            <li><a href="/press">Press</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Services</h4>
                        <ul>
                            <li><a href="/rent">Rent</a></li>
                            <li><a href="/buy">Buy</a></li>
                            <li><a href="/sell">Sell</a></li>
                            <li><a href="/invest">Invest</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Support</h4>
                        <ul>
                            <li><a href="/help-center.php">Help Center</a></li>
                            <li><a href="/contact">Contact Us</a></li>
                            <li><a href="/faq">FAQs</a></li>
                            <li><a href="/privacy">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 RentEstate. All rights reserved.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Chat Widget -->
    <div class="chat-widget" id="chatWidget">
        <div class="chat-header">
            <h3>Live Support</h3>
            <button id="closeChatBtn"><i class="fas fa-times"></i></button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="message support">
                <div class="message-content">
                    <p>Hello! How can I help you today?</p>
                    <span class="message-time">Just now</span>
                </div>
            </div>
        </div>
        <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Type your message...">
            <button id="sendChatBtn"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
    
    <script src="script.js" defer> document.addEventListener('DOMContentLoaded', function() {
    // Menu Toggle
    const menuButton = document.getElementById('menuButton');
    const menuDropdown = document.getElementById('menuDropdown');
    const languageButton = document.getElementById('languageButton');
    const languageOptions = document.getElementById('languageOptions');
    
    // Toggle menu dropdown
    menuButton.addEventListener('click', function() {
        menuDropdown.classList.toggle('active');
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('#menuButton') && !event.target.closest('#menuDropdown')) {
            menuDropdown.classList.remove('active');
        }
    });
    
    // Toggle language options
    languageButton.addEventListener('click', function(event) {
        event.stopPropagation();
        languageOptions.classList.toggle('active');
    });
    
    // Close language options when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('#languageButton')) {
            languageOptions.classList.remove('active');
        }
    });
    
    // FAQ Toggle
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            // Close other open FAQs
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current FAQ
            item.classList.toggle('active');
        });
    });
    
    // Chat Widget
    const openChatBtn = document.getElementById('openChatBtn');
    const closeChatBtn = document.getElementById('closeChatBtn');
    const chatWidget = document.getElementById('chatWidget');
    const chatInput = document.getElementById('chatInput');
    const sendChatBtn = document.getElementById('sendChatBtn');
    const chatMessages = document.getElementById('chatMessages');
    
    // Open chat widget
    if (openChatBtn) {
        openChatBtn.addEventListener('click', function() {
            chatWidget.classList.add('active');
        });
    }
    
    // Close chat widget
    if (closeChatBtn) {
        closeChatBtn.addEventListener('click', function() {
            chatWidget.classList.remove('active');
        });
    }
    
    // Send message
    if (sendChatBtn && chatInput) {
        sendChatBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
    
    function sendMessage() {
        const message = chatInput.value.trim();
        
        if (message) {
            // Add user message
            addMessage('user', message);
            
            // Clear input
            chatInput.value = '';
            
            // Simulate response after a short delay
            setTimeout(() => {
                addMessage('support', 'Thank you for your message. Our support team will get back to you shortly.');
            }, 1000);
        }
    }
    
    function addMessage(type, content) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', type);
        
        const now = new Date();
        const timeString = now.getHours() + ':' + (now.getMinutes() < 10 ? '0' : '') + now.getMinutes();
        
        messageDiv.innerHTML = `
            <div class="message-content">
                <p>${content}</p>
                <span class="message-time">${timeString}</span>
            </div>
        `;
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Support Form Validation
    const supportForm = document.getElementById('support-form');
    
    if (supportForm) {
        supportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            let isValid = true;
            const requiredFields = supportForm.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            // Email validation
            const emailField = document.getElementById('email');
            if (emailField && !validateEmail(emailField.value)) {
                isValid = false;
                emailField.classList.add('error');
            }
            
            if (isValid) {
                // Submit form via AJAX
                const formData = new FormData(supportForm);
                
                // Show loading state
                const submitBtn = supportForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.textContent;
                submitBtn.textContent = 'Submitting...';
                submitBtn.disabled = true;
                
                // Simulate form submission
                setTimeout(() => {
                    // In a real application, you would use fetch or XMLHttpRequest here
                    // to submit the form data to the server
                    
                    // Show success message
                    alert('Thank you! Your support request has been submitted successfully.');
                    supportForm.reset();
                    
                    // Reset button state
                    submitBtn.textContent = originalBtnText;
                    submitBtn.disabled = false;
                }, 1500);
            } else {
                alert('Please fill in all required fields correctly.');
            }
        });
    }
    
    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
});
</script>
</body>
</html>
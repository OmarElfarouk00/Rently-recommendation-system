<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade to VIP - RentEstate</title>
    <link rel="stylesheet" href="../homepage/styles.css">
    <script defer src="../homepage/script.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* VIP Upgrade Page Specific Styles */
        .vip-container {
            margin-top: 80px;
            padding: 2rem 5%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .page-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title h1 {
            font-size: 2.5rem;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .page-title p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Pricing Plans */
        .pricing-plans {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-bottom: 4rem;
            flex-wrap: wrap;
        }

        .plan {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            flex: 1;
            min-width: 280px;
            max-width: 350px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .plan:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .plan.featured {
            border: 2px solid var(--primary-color);
            transform: scale(1.05);
        }

        .plan.featured:hover {
            transform: scale(1.05) translateY(-5px);
        }

        .best-value {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .plan-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .plan-header h2 {
            font-size: 1.8rem;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .plan-price {
            margin-bottom: 0.5rem;
        }

        .price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--text-color);
        }

        .period {
            font-size: 1rem;
            color: #666;
        }

        .savings {
            color: #28a745;
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .plan-features {
            margin-bottom: 2rem;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .feature i {
            color: #28a745;
        }

        .select-plan {
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .select-plan:hover {
            background-color: #d65b1e;
        }

        /* Payment Section */
        .payment-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 4rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: none;
        }

        .payment-section h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text-color);
        }

        .selected-plan-info {
            background: var(--light-gray);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .selected-plan-info p {
            margin: 0.5rem 0;
            color: var(--text-color);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            margin-bottom: 1rem;
            color: var(--text-color);
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .form-input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .card-input-wrapper {
            position: relative;
        }

        .card-icons {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 5px;
        }

        .card-icons i {
            font-size: 1.5rem;
            color: #666;
        }

        .terms-agreement {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .terms-agreement label {
            font-size: 0.9rem;
            color: #666;
        }

        .terms-agreement a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .submit-payment {
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-payment:hover {
            background-color: #d65b1e;
        }

        /* Success Message */
        .success-message {
            background: white;
            border-radius: 15px;
            padding: 3rem 2rem;
            margin-bottom: 4rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            display: none;
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1.5rem;
        }

        .success-message h2 {
            color: var(--text-color);
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .success-message p {
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .return-home {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.8rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .return-home:hover {
            background-color: #d65b1e;
        }

        /* VIP Benefits Section */
        .vip-benefits {
            margin-bottom: 4rem;
        }

        .vip-benefits h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text-color);
            font-size: 2rem;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .benefit-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .benefit-card:hover {
            transform: translateY(-5px);
        }

        .benefit-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .benefit-card h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .benefit-card p {
            color: #666;
        }

        /* Testimonials Section */
        .testimonials {
            margin-bottom: 4rem;
        }

        .testimonials h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text-color);
            font-size: 2rem;
        }

        .testimonial-slider {
            display: flex;
            gap: 2rem;
            overflow-x: auto;
            padding: 1rem 0.5rem;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
        }

        .testimonial-slider::-webkit-scrollbar {
            display: none;
        }

        .testimonial {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            min-width: 300px;
            flex: 1;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            scroll-snap-align: start;
        }

        .testimonial-content {
            margin-bottom: 1.5rem;
            color: #666;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .author-info h4 {
            color: var(--text-color);
            margin-bottom: 0.2rem;
        }

        .author-info p {
            color: #666;
            font-size: 0.9rem;
        }

        /* FAQ Section */
        .faq-section {
            margin-bottom: 4rem;
        }

        .faq-section h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text-color);
            font-size: 2rem;
        }

        .faq-list {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: white;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .faq-question {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .faq-question h3 {
            color: var(--text-color);
            font-size: 1.1rem;
            margin: 0;
        }

        .faq-answer {
            padding: 0 1.5rem 1.5rem;
            color: #666;
            display: none;
        }

        .faq-item.active .faq-answer {
            display: block;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .pricing-plans {
                flex-direction: column;
                align-items: center;
            }

            .plan {
                width: 100%;
                max-width: 100%;
            }

            .plan.featured {
                transform: none;
                order: -1;
            }

            .plan.featured:hover {
                transform: translateY(-5px);
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
            }

            .testimonial-slider {
                flex-direction: column;
            }

            .testimonial {
                min-width: auto;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">RentEstate</a>
            <div class="user-menu">
                <div class="menu-dropdown">
                    <i class="fas fa-bars" id="menuToggle"></i>
                    <div class="menu-content" id="menuContent">
                        <a href="index.php" class="menu-item">
                            <i class="fas fa-home"></i>
                            dashboard
                        </a>
                        <div class="menu-divider"></div>
                        <a href="#" class="menu-item" id="language-toggle">
                            <i class="fas fa-globe"></i>
                            Language
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
                        <a href="#" class="menu-item">
                            <i class="fas fa-question-circle"></i>
                            Help Center
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="vip-container">
        <div class="page-title">
            <h1>Upgrade to VIP</h1>
            <p>Unlock premium features and get the most out of RentEstate</p>
        </div>

        <div class="pricing-plans">

            <div class="plan featured">
                <div class="best-value">Best Value</div>
                <div class="plan-header">
                    <h2>Monthly</h2>
                    <div class="plan-price">
                        <span class="price">1000DA</span>
                        <span class="period">/Unlimited</span>
                    </div>
                </div>
                <div class="plan-features">
                    <!-- <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>Access to premium listings</span>
                    </div> -->
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>Top Listing</span>
                    </div>

                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>Unlimited property photos upload</span>
                    </div>
                    <!-- <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>Priority customer support</span>
                    </div> -->
                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>No ads experience</span>
                    </div>

                    <div class="feature">
                        <i class="fas fa-check"></i>
                        <span>List properties for sell</span>
                    </div>
                </div>
                <button class="select-plan" data-plan="Monthly" data-price="49.99">Select Plan</button>
            </div>

        </div>

        <!-- Payment Form (Initially Hidden) -->
        <div class="payment-section" id="paymentSection">
            <h2>Complete Your VIP Upgrade</h2>
            <div class="selected-plan-info">
                <p>Selected Plan: <span id="selectedPlanName">Unlimited</span></p>
                <p>Price: <span id="selectedPlanPrice">1000DA</span></p>
            </div>

            <form id="paymentForm" action="process-vip-upgrade.php" method="POST">
                <input type="hidden" id="planType" name="planType" value="annual">
                <input type="hidden" id="planPrice" name="planPrice" value="49.99">

                <div class="form-section">
                    <h3>Payment Information</h3>
                    <div class="form-group">
                        <select name="baridi" class="form-input">
                            <option value="">-Select-</option>
                            <option value="baridi">Baridi Mob</option>
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="cardNumber">Card Number</label>
                        <div class="card-input-wrapper">
                            <input type="text" id="cardNumber" name="cardNumber" class="form-input"
                                value="1234 5678 9012 3456" required minlength="16" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="propertyImageUpload">Upload Property Images:</label>
                        <input type="file" id="propertyImageUpload" name="propertyImages[]" accept="image/*" multiple>
                        <small class="form-text text-muted">You can upload multiple images (JPG, PNG, PDF) for your
                            property.</small>
                    </div>
                    <!-- <div class="form-row">
                        <div class="form-group">
                            <label for="expiryDate">Expiry Date</label>
                            <input type="text" id="expiryDate" name="expiryDate" class="form-input" placeholder="MM/YY" required pattern="\d{2}/\d{2}">
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" name="cvv" class="form-input" placeholder="1234" required minlength="4">
                        </div>
                    </div> -->
                </div>


                <div class="terms-agreement">
                    <input type="checkbox" id="termsAgree" name="termsAgree" required>
                    <label for="termsAgree">I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy
                            Policy</a></label>
                </div>

                <button type="submit" class="submit-payment">Complete Upgrade</button>
            </form>
        </div>

        <!-- Success Message (Initially Hidden) -->
        <div class="success-message" id="successMessage">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Congratulations!</h2>
            <p>Your account has been successfully upgraded to VIP status.</p>
            <p>You now have access to all premium features.</p>
            <a href="index.php" class="return-home">Return to Homepage</a>
        </div>

        <!-- VIP Benefits Section -->
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Menu toggle functionality
            const menuToggle = document.getElementById('menuToggle');
            const menuContent = document.getElementById('menuContent');
            const languageToggle = document.getElementById('language-toggle');
            const languageMenu = document.getElementById('language-menu');

            if (menuToggle && menuContent) {
                menuToggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    menuContent.classList.function(e);
                    e.stopPropagation();
                    menuContent.classList.toggle('active');
                });
            }

            if (languageToggle && languageMenu) {
                languageToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    languageMenu.classList.toggle('active');
                });
            }

            // Close menus when clicking outside
            document.addEventListener('click', function (e) {
                if (menuContent && !menuContent.contains(e.target) && !menuToggle.contains(e.target)) {
                    menuContent.classList.remove('active');
                    if (languageMenu) {
                        languageMenu.classList.remove('active');
                    }
                }
            });

            // Plan selection functionality
            const planButtons = document.querySelectorAll('.select-plan');
            const paymentSection = document.getElementById('paymentSection');
            const selectedPlanName = document.getElementById('selectedPlanName');
            const selectedPlanPrice = document.getElementById('selectedPlanPrice');
            const planTypeInput = document.getElementById('planType');
            const planPriceInput = document.getElementById('planPrice');

            planButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const plan = this.getAttribute('data-plan');
                    const price = this.getAttribute('data-price');

                    // Update hidden form fields
                    planTypeInput.value = plan;
                    planPriceInput.value = price;

                    // Update display text
                    selectedPlanName.textContent = plan.charAt(0).toUpperCase() + plan.slice(1);
                    selectedPlanPrice.textContent = `$${price}`;

                    // Show payment section and scroll to it
                    paymentSection.style.display = 'block';
                    paymentSection.scrollIntoView({ behavior: 'smooth' });
                });
            });

            // Payment form validation
            const paymentForm = document.getElementById('paymentForm');
            const successMessage = document.getElementById('successMessage');

            if (paymentForm) {
                paymentForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    // Basic form validation
                    const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                    const expiryDate = document.getElementById('expiryDate').value;
                    const cvv = document.getElementById('cvv').value;

                    let isValid = true;

                    // Validate card number (simple check for length)
                    if (cardNumber.length < 15 || cardNumber.length > 16) {
                        alert('Please enter a valid card number');
                        isValid = false;
                    }

                    // Validate expiry date format (MM/YY)
                    if (!/^\d{2}\/\d{2}$/.test(expiryDate)) {
                        alert('Please enter a valid expiry date in MM/YY format');
                        isValid = false;
                    }

                    // Validate CVV (3-4 digits)
                    if (!/^\d{3,4}$/.test(cvv)) {
                        alert('Please enter a valid CVV');
                        isValid = false;
                    }

                    if (isValid) {
                        // Simulate form submission with AJAX
                        simulatePaymentProcessing();
                    }
                });
            }

            function simulatePaymentProcessing() {
                const submitButton = document.querySelector('.submit-payment');
                submitButton.disabled = true;
                submitButton.textContent = 'Processing...';

                // Simulate API call delay
                setTimeout(function () {
                    // Hide payment form
                    paymentSection.style.display = 'none';

                    // Show success message
                    successMessage.style.display = 'block';
                    successMessage.scrollIntoView({ behavior: 'smooth' });

                    // In a real implementation, you would send the form data to the server here
                }, 2000);
            }

            // Format credit card number with spaces
            const cardNumberInput = document.getElementById('cardNumber');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = '';

                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) {
                            formattedValue += ' ';
                        }
                        formattedValue += value[i];
                    }

                    e.target.value = formattedValue;
                });
            }

            // Format expiry date with slash
            const expiryDateInput = document.getElementById('expiryDate');
            if (expiryDateInput) {
                expiryDateInput.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');

                    if (value.length > 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }

                    e.target.value = value;
                });
            }

            // FAQ accordion functionality
            const faqItems = document.querySelectorAll('.faq-item');

            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');

                question.addEventListener('click', function () {
                    // Close all other items
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                        }
                    });

                    // Toggle current item
                    item.classList.toggle('active');
                });
            });
        });
    </script>
</body>

</html>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.querySelector('.password-toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.textContent = 'Hide';
    } else {
        passwordInput.type = 'password';
        toggleButton.textContent = 'Show';
    }
}

// document.getElementById('loginForm').addEventListener('submit', function(e) {
//     e.preventDefault();
//     const email = document.getElementById('email').value;
//     const password = document.getElementById('password').value;
//     console.log('Login attempt:', { email, password });
// });

// document.getElementById('forgotPassword').addEventListener('click', function(e) {
//     e.preventDefault();
//     console.log('Forgot password clicked');
// });

// document.getElementById('signUp').addEventListener('click', function(e) {
//     e.preventDefault();
//     console.log('Sign up clicked');
// });
//  input.addEventListener('input', function() {
//     if (this.type === 'email') {
//         if (this.value && !isValidEmail(this.value)) {
//             emailError.textContent = 'Please enter a valid email';
//             emailError.style.display = 'block';
//         } else {
//             emailError.style.display = 'none';
//         }
//     }
//     if (this.type === 'password') {
//         if (this.value && this.value.length < 6) {
//             passwordError.textContent = 'Password must be at least 6 characters';
//             passwordError.style.display = 'block';
//         } else {
//             passwordError.style.display = 'none';
//         }
//     }
// });
// // index Social login
// function socialLogin(provider) {
//     console.log(`Logging in with ${provider}`);
//     // Add your social login logic here
// }
// //forget password Reset password
// document.getElementById('resetForm').addEventListener('submit', function(e) {
//     e.preventDefault();
//     const email = document.getElementById('email').value;
    
//     // Here you would typically handle the password reset logic
//     console.log('Password reset requested for:', email);
    
//     // Show success message
//     alert('If an account exists with this email, you will receive a password reset link shortly.');
//     window.location.href = 'login.html';
// });
// // signup
//         function togglePassword() {
//             const passwordInput = document.getElementById('password');
//             const toggleButton = document.querySelector('.password-toggle');
            
//             if (passwordInput.type === 'password') {
//                 passwordInput.type = 'text';
//                 toggleButton.textContent = 'Hide';
//             } else {
//                 passwordInput.type = 'password';
//                 toggleButton.textContent = 'Show';
//             }
//         }

//         document.getElementById('signupForm').addEventListener('submit', function(e) {
//             e.preventDefault();
//             const name = document.getElementById('name').value;
//             const email = document.getElementById('email').value;
//             const password = document.getElementById('password').value;
            
//             // Here you would typically handle the signup logic
//             console.log('Signup attempt:', { name, email, password });
            
//             // Redirect to confirmation page
//             window.location.href = 'confirmation.html';
//         });

//         function socialSignup(provider) {
//             console.log(`Signing up with ${provider}`);
//             // Add your social signup logic here
//         }





document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const successMessage = document.getElementById('successMessage');

    // Ripple effect
    document.querySelector('.ripple').addEventListener('click', function(e) {
        let x = e.clientX - e.target.offsetLeft;
        let y = e.clientY - e.target.offsetTop;

        let ripple = document.createElement('span');
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        this.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 1000);
    });

    // Email validation function
    function isValidEmail(email) {
        // return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        // must end with the following domains: @gmail.com, @yahoo.com, @hotmail.com
         return /^[^\s@]+@[^\s@]+\.(gmail|yahoo|hotmail)\.(com|fr|org)$/.test(email);
    }

    // Add floating label effect and validation
    document.querySelectorAll('.input-group input').forEach(input => {
        // Initial check for pre-filled inputs
        if (input.value) {
            input.nextElementSibling.classList.add('active');
        }

        input.addEventListener('focus', function() {
            this.nextElementSibling.classList.add('active');
        });

        input.addEventListener('blur', function() {
            if (!this.value) {
                this.nextElementSibling.classList.remove('active');
            }
        });

        // Real-time validation
        input.addEventListener('input', function() {
            if (this.type === 'email') {
                if (this.value && !isValidEmail(this.value)) {
                    emailError.textContent = 'Please enter a valid email';
                    emailError.style.display = 'block';
                } else {
                    emailError.style.display = 'none';
                }
            }
            if (this.type === 'password') {
                if (this.value && this.value.length < 8) {
                    passwordError.textContent = 'Password must be at least 8 characters';
                    passwordError.style.display = 'block';
                } else {
                    passwordError.style.display = 'none';
                }
            }
        });
    });

    // Handle form submission with enhanced animations
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        let isValid = true;

        // Reset error messages
        emailError.style.display = 'none';
        passwordError.style.display = 'none';
        successMessage.style.display = 'none';

        // Validate email
        if (!email.value.trim()) {
            emailError.textContent = 'Email is required';
            emailError.style.display = 'block';
            isValid = false;
        } else if (!isValidEmail(email.value)) {
            emailError.textContent = 'Please enter a valid email';
            emailError.style.display = 'block';
            isValid = false;
        }

        // Validate password
        if (!password.value) {
            passwordError.textContent = 'Password is required';
            passwordError.style.display = 'block';
            isValid = false;
        } else if (password.value.length < 6) {
            passwordError.textContent = 'Password must be at least 6 characters';
            passwordError.style.display = 'block';
            isValid = false;
        }

        // If form is valid, show success animation
        if (isValid) {
            const button = document.querySelector('button');
            button.innerHTML = 'Logging in...';
            button.disabled = true;

            const formData = new FormData(form);
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    successMessage.style.display = 'block';
                    setTimeout(() => {
                        window.location.href = 'dashboard.html';
                    }, 1500);
                } else {
                    alert(data.message || 'Login failed');
                    button.innerHTML = 'Login';
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Login error:', error);
                button.innerHTML = 'Login';
                button.disabled = false;
            }
        }
    });
});

        // Add this to your existing JavaScript
        document.addEventListener("DOMContentLoaded", function () {
            const menuToggle = document.getElementById("menuToggle");
            const menuContent = document.getElementById("menuContent");
        
            menuToggle.addEventListener("click", function (event) {
                event.stopPropagation();  // Prevents closing when clicking inside
                menuContent.classList.toggle("active");
            });
        
            // Close the menu when clicking anywhere outside
            document.addEventListener("click", function (event) {
                if (!menuContent.contains(event.target) && event.target !== menuToggle) {
                    menuContent.classList.remove("active");
                }
            });
        });
        

        // // Language selection function
        // document.addEventListener('DOMContentLoaded', function () {
        //     const languageToggle = document.getElementById('language-toggle');
        //     const languageMenu = document.getElementById('language-menu');

            // // Toggle the visibility of the language menu
            // languageToggle.addEventListener('click', function (event) {
            //     event.preventDefault(); // Prevent the default anchor behavior
            //     languageMenu.style.display = languageMenu.style.display === 'block' ? 'none' : 'block';
            // });

            // // Change language on click
            // const languageLinks = document.querySelectorAll('.language-menu a');
            // languageLinks.forEach(link => {
            //     link.addEventListener('click', function (event) {
            //         event.preventDefault(); // Prevent the default anchor behavior
            //         const selectedLanguage = this.getAttribute('data-lang');
            //         changeLanguage(selectedLanguage);
            //         languageMenu.style.display = 'none'; // Hide the menu after selection
            //     });
            // });

            // Function to change the language
            // function changeLanguage(lang) {
            //     // Here you can implement the logic to change the language
            //     // For example, you could load a different language file or update the text on the page
            //     console.log('Language changed to:', lang);
            //     // You can also store the selected language in localStorage or cookies if needed
            // }

            // // Close the menu if clicked outside
            // window.addEventListener('click', function (event) {
            //     if (!languageToggle.contains(event.target) && !languageMenu.contains(event.target)) {
            //         languageMenu.style.display = 'none';
            //     }
            // });
        // });

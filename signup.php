<?php
// signup.php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    
    // Check if email exists
    $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit();
    }
    
    // Insert user
    $sql = "INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $full_name, $email, $password, $phone, $role);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // If business owner, insert into business_owners table
        if ($role == 'business_owner') {
            $business_type = $_POST['business_type'];
            $business_name = $_POST['business_name'];
            
            $biz_sql = "INSERT INTO business_owners (user_id, business_type, business_name) VALUES (?, ?, ?)";
            $biz_stmt = $conn->prepare($biz_sql);
            $biz_stmt->bind_param("iss", $user_id, $business_type, $business_name);
            $biz_stmt->execute();
        }
       
        echo json_encode(['success' => true, 'message' => 'Registration successful! Please login.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - PharmaLocator</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ===== GLOBAL STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== HEADER DESIGN ===== */
        .new-header {
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
        }

        .logo-link {
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            font-size: 24px;
            background: linear-gradient(135deg, #2c7da0, #2a9d8f);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            color: white;
        }

        .logo-text {
            font-weight: 700;
            font-size: 18px;
            color: #1e293b;
        }

        .logo-text span {
            color: #64748b;
            font-weight: 400;
            font-size: 12px;
            display: block;
            line-height: 1.2;
        }

        /* Navigation */
        .nav-center {
            display: flex;
            gap: 32px;
        }

        .nav-center a {
            text-decoration: none;
            color: #475569;
            font-weight: 500;
            font-size: 15px;
            padding: 8px 0;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-center a:hover {
            color: #2c7da0;
            border-bottom-color: #2c7da0;
        }

        .nav-center a.active {
            color: #2c7da0;
            border-bottom-color: #2c7da0;
            font-weight: 600;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
        }

        .btn-signin {
            text-decoration: none;
            padding: 8px 20px;
            background: #f1f5f9;
            color: #334155;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .btn-signin:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .btn-register {
            text-decoration: none;
            padding: 8px 20px;
            background: #2c7da0;
            color: white;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(44,125,160,0.2);
        }

        .btn-register:hover {
            background: #1e5f7a;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(44,125,160,0.3);
        }

        /* ===== MAIN CONTAINER ===== */
        .container {
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

        /* ===== SIGNUP CARD ===== */
        .signup-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            padding: 40px;
            animation: slideUp 0.5s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .signup-header i {
            font-size: 50px;
            color: #2c7da0;
            margin-bottom: 15px;
            background: #f0f9ff;
            padding: 15px;
            border-radius: 60px;
        }

        .signup-header h2 {
            color: #1e293b;
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .signup-header p {
            color: #64748b;
            font-size: 15px;
        }

        /* ===== ROLE SELECTOR (KEEPING YOUR STRUCTURE) ===== */
        .role-selector {
            display: flex;
            gap: 20px;
            margin: 30px 0;
        }
        
        .role-option {
            flex: 1;
            padding: 25px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        .role-option.selected {
            border-color: #2c7da0;
            background: #f0f9ff;
            box-shadow: 0 10px 20px rgba(44,125,160,0.1);
        }
        
        .role-option input {
            display: none;
        }
        
        .role-option .icon {
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
            color: #2c7da0;
        }
        
        .role-option h3 {
            margin: 10px 0;
            color: #1e293b;
            font-size: 18px;
        }
        
        .role-option p {
            color: #64748b;
            font-size: 14px;
        }

        /* ===== FORM ELEMENTS ===== */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group label i {
            color: #2c7da0;
            margin-right: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2c7da0;
            outline: none;
            box-shadow: 0 0 0 4px rgba(44,125,160,0.1);
            background: white;
        }

        .form-group small {
            color: #64748b;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Business Fields */
        #businessFields {
            display: none;
            margin-top: 30px;
            padding: 25px;
            background: #f8fafc;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            animation: slideDown 0.3s;
        }

        #businessFields h3 {
            margin-bottom: 20px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #businessFields h3 i {
            color: #2c7da0;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Alert */
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        .alert-info i {
            font-size: 18px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Button */
        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2c7da0, #1e5f7a);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(44,125,160,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(44,125,160,0.4);
        }

        /* Links */
        .text-center {
            text-align: center;
        }

        .mt-20 {
            margin-top: 20px;
        }

        .login-link {
            color: #64748b;
            font-size: 14px;
        }

        .login-link a {
            color: #2c7da0;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Footer */
        footer {
            background: white;
            padding: 20px 30px;
            margin-top: 40px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .footer-container {
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-copyright {
            color: #64748b;
            font-size: 14px;
        }

        .footer-social {
            display: flex;
            gap: 20px;
        }

        .footer-social a {
            color: #94a3b8;
            font-size: 18px;
            transition: all 0.3s;
        }

        .footer-social a:hover {
            color: #2c7da0;
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .nav-center {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }

            .action-buttons {
                width: 100%;
                justify-content: center;
            }

            .role-selector {
                flex-direction: column;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .signup-card {
                padding: 30px 20px;
            }

            .footer-container {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- ===== NEW HEADER ===== -->
    <header class="new-header">
        <div class="header-container">
            <!-- LEFT: Logo -->
            <div class="logo-section">
                <a href="home.html" class="logo-link">
                    <div class="logo-icon"></div>
                    <div class="logo-text">
                        PharmaLocator
                        <span>Find Care, Fast</span>
                    </div>
                </a>
            </div>

            <!-- CENTER: Navigation (KEEPING YOUR LINKS) -->
            <nav class="nav-center">
                <a href="home.html">Home</a>
                <a href="service.html">Services</a>
                <a href="about.html">About</a>
                <a href="contact.html">Contact</a>
            </nav>

            <!-- RIGHT: Action Buttons (Register is active) -->
            <div class="action-buttons">
                <a href="login.html" class="btn-signin">Sign in</a>
                <a href="signup.php" class="btn-register">Register</a>
            </div>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="container">
        <div class="signup-card">
            <div class="signup-header">
                <i class="fas fa-user-plus"></i>
                <h2>Create Account</h2>
                <p>Join our healthcare network</p>
            </div>

            <!-- Message area (KEEPING YOUR FUNCTIONALITY) -->
            <div id="message"></div>

            <!-- Signup Form (YOUR EXISTING FORM) -->
            <form onsubmit="submitSignup(event)">
                <!-- Role Selector (KEEPING YOUR STRUCTURE) -->
                <div class="role-selector">
                    <label class="role-option" onclick="selectRole('customer')" id="role-customer">
                        <input type="radio" name="role" value="customer" required>
                        <span class="icon"><i class="fas fa-user"></i></span>
                        <h3>Customer</h3>
                        <p>Find pharmacies, hospitals and clinics near you</p>
                    </label>
                    
                    <label class="role-option" onclick="selectRole('business')" id="role-business">
                        <input type="radio" name="role" value="business_owner" required>
                        <span class="icon"><i class="fas fa-store"></i></span>
                        <h3>Business Owner</h3>
                        <p>Register your pharmacy, hospital or clinic</p>
                    </label>
                </div>

                <!-- Personal Information -->
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="full_name" placeholder="Enter your full name" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" placeholder="you@example.com" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone" placeholder="+237 6XX XXX XXX" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Create a password" required>
                    <small>Minimum 8 characters</small>
                </div>

                <!-- Business Fields (KEEPING YOUR STRUCTURE) -->
                <div id="businessFields">
                    <h3><i class="fas fa-building"></i> Business Information</h3>
                    
                    <div class="form-group">
                        <select name="business_type" id="business_type">
                            <option value="">Select Business Type</option>
                            <option value="pharmacy">Pharmacy</option>
                            <option value="hospital">Hospital</option>
                            <option value="clinic">Clinic</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="business_name" placeholder="Business Name">
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="registration_number" placeholder="Registration/License Number">
                    </div>
                    
                    <div class="form-group">
                        <textarea name="business_address" placeholder="Business Address" rows="3"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span><strong>Note:</strong> Business accounts require verification before appearing in search results.</span>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
                
                <p class="text-center mt-20 login-link">
                    Already have an account? <a href="login.html">Sign in here</a>
                </p>
            </form>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="footer-container">
            <div class="footer-copyright">
                &copy; 2026 PharmaLocator. All rights reserved.
            </div>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- YOUR EXISTING JAVASCRIPT - 100% INTACT -->
    <script>
    function selectRole(role) {
        document.getElementById('role-customer').classList.remove('selected');
        document.getElementById('role-business').classList.remove('selected');
        
        if (role === 'customer') {
            document.getElementById('role-customer').classList.add('selected');
            document.getElementById('businessFields').style.display = 'none';
            // Remove required attributes
            document.querySelectorAll('#businessFields input, #businessFields select, #businessFields textarea').forEach(field => {
                field.required = false;
            });
        } else {
            document.getElementById('role-business').classList.add('selected');
            document.getElementById('businessFields').style.display = 'block';
            // Add required attributes
            document.querySelectorAll('#businessFields input, #businessFields select, #businessFields textarea').forEach(field => {
                field.required = true;
            });
        }
    }

    async function submitSignup(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Validate password length
        const password = formData.get('password');
        if (password.length < 8) {
            showMessage('Password must be at least 8 characters', 'danger');
            return;
        }
        
        // Validate business fields if business owner
        const role = formData.get('role');
        if (role === 'business_owner') {
            const businessType = formData.get('business_type');
            const businessName = formData.get('business_name');
            
            if (!businessType || !businessName) {
                showMessage('Please fill all business fields', 'danger');
                return;
            }
        }
        
        try {
            const response = await fetch('signup.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage(data.message, 'success');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                showMessage(data.message, 'danger');
            }
        } catch (error) {
            console.error('Signup error:', error);
            showMessage('Registration failed. Please try again.', 'danger');
        }
    }

    function showMessage(message, type = 'danger') {
        const msgDiv = document.getElementById('message');
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        msgDiv.innerHTML = `<div class="alert ${alertClass}"><i class="fas fa-${icon}"></i> ${message}</div>`;

        // Auto hide after 5 seconds
        setTimeout(() => {
            msgDiv.innerHTML = '';
        }, 5000);
    }

    // Handle optional query param errors (e.g., after redirect)
    window.addEventListener('load', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');

        if (error) {
            if (error === 'email_exists') showMessage('This email is already registered.', 'danger');
            else if (error === 'invalid') showMessage('Invalid details provided.', 'danger');
            else showMessage('Registration error. Please try again.', 'danger');

            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
    </script>
</body>
</html>
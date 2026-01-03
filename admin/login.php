<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Akanyenyeri Magazine</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a202c;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2.5rem;
        }
        
        .brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .brand i {
            font-size: 2.5rem;
            color: #3182ce;
            margin-bottom: 1rem;
        }
        
        .brand h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4a5568;
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background: #3182ce;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary:hover {
            background: #2c5282;
        }
        
        .btn-primary:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: none;
        }
        
        .alert-error {
            background: #fff5f5;
            color: #c53030;
            border: 1px solid #feb2b2;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .remember-me input {
            width: 1rem;
            height: 1rem;
        }
        
        .remember-me label {
            font-size: 0.9rem;
            color: #4a5568;
            user-select: none;
        }
        
        .footer-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .footer-link a {
            color: #3182ce;
            text-decoration: none;
        }
        
        .footer-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="brand">
                <img src="../uploads/akanyenyeri-logo.svg" alt="Akanyenyeri Logo" style="height: 80px; width: auto; margin-bottom: 1rem;">
                <h1>Admin Login</h1>
            </div>
            
            <div class="alert alert-error" id="error-alert"></div>
            
            <form id="login-form">
                <div class="form-group">
                    <label for="username" class="form-label">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                
                <button type="submit" class="btn-primary" id="submit-btn">
                    <span>Sign In</span>
                    <i class="fas fa-spinner fa-spin" style="display: none; margin-left: 0.5rem;"></i>
                </button>
            </form>
            
            <div class="footer-link">
                <a href="../index.php">Back to Website</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submit-btn');
            const errorAlert = document.getElementById('error-alert');
            const spinner = submitBtn.querySelector('.fa-spinner');
            
            // Reset state
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            errorAlert.style.display = 'none';
            errorAlert.textContent = '';
            
            const formData = {
                username: this.username.value,
                password: this.password.value,
                remember: this.remember.checked
            };
            
            try {
                const response = await fetch('php/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = data.redirect || 'dashboard.php';
                } else {
                    errorAlert.textContent = data.message || 'Login failed';
                    errorAlert.style.display = 'block';
                    submitBtn.disabled = false;
                    spinner.style.display = 'none';
                }
            } catch (error) {
                console.error('Error:', error);
                errorAlert.textContent = 'An error occurred. Please try again.';
                errorAlert.style.display = 'block';
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }
        });
    </script>
</body>
</html>

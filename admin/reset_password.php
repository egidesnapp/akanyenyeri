<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Akanyenyeri Magazine</title>

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
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
            position: relative;
            overflow-y: auto;
            padding: 20px;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 226, 0.3) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(10px) rotate(240deg); }
        }

        .container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            padding: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.1) 0%,
                rgba(255, 255, 255, 0.05) 50%,
                rgba(255, 255, 255, 0.1) 100%
            );
            border-radius: 20px;
            pointer-events: none;
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
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .content-wrapper {
            position: relative;
            z-index: 2;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #e2e8f0;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-control:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }

        .password-strength.weak {
            color: #e53e3e;
        }

        .password-strength.medium {
            color: #dd6b20;
        }

        .password-strength.strong {
            color: #38a169;
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

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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

        .password-requirements {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 6px;
            border-left: 4px solid #3182ce;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #4a5568;
        }

        .password-requirements ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.2rem;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="content-wrapper">
                <div class="brand">
                    <img src="../logo/akanyenyeri logo.png" alt="Akanyenyeri Logo" style="height: 80px; width: auto; margin-bottom: 1rem;">
                    <h1>Reset Password</h1>
                </div>

            <div class="alert alert-error" id="error-alert"></div>
            <div class="alert alert-success" id="success-alert"></div>

            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>Contains at least one uppercase letter</li>
                    <li>Contains at least one lowercase letter</li>
                    <li>Contains at least one number</li>
                    <li>Contains at least one special character</li>
                </ul>
            </div>

            <form id="reset-form">
                <div class="form-group">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" required autofocus>
                    <div class="password-strength" id="password-strength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <button type="submit" class="btn-primary" id="submit-btn">
                    <span>Reset Password</span>
                    <i class="fas fa-spinner fa-spin" style="display: none; margin-left: 0.5rem;"></i>
                </button>
            </form>

            <div class="footer-link">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        // Get token from URL
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');

        if (!token) {
            document.getElementById('error-alert').textContent = 'Invalid reset link. Please request a new password reset.';
            document.getElementById('error-alert').style.display = 'block';
            document.getElementById('reset-form').style.display = 'none';
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];

            if (password.length >= 8) strength++;
            else feedback.push('At least 8 characters');

            if (/[a-z]/.test(password)) strength++;
            else feedback.push('Lowercase letter');

            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('Uppercase letter');

            if (/[0-9]/.test(password)) strength++;
            else feedback.push('Number');

            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push('Special character');

            const strengthText = strength < 3 ? 'Weak' : strength < 4 ? 'Medium' : 'Strong';
            const strengthClass = strength < 3 ? 'weak' : strength < 4 ? 'medium' : 'strong';

            return { strength, strengthText, strengthClass, feedback };
        }

        // Password input handler
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const result = checkPasswordStrength(password);
            const strengthElement = document.getElementById('password-strength');

            if (password.length > 0) {
                strengthElement.textContent = `Password strength: ${result.strengthText}`;
                strengthElement.className = `password-strength ${result.strengthClass}`;
            } else {
                strengthElement.textContent = '';
                strengthElement.className = 'password-strength';
            }
        });

        // Form submission
        document.getElementById('reset-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submit-btn');
            const errorAlert = document.getElementById('error-alert');
            const successAlert = document.getElementById('success-alert');
            const spinner = submitBtn.querySelector('.fa-spinner');

            // Reset state
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            errorAlert.style.display = 'none';
            successAlert.style.display = 'none';
            errorAlert.textContent = '';
            successAlert.textContent = '';

            const password = this.password.value;
            const confirmPassword = this.confirm_password.value;

            // Validate passwords match
            if (password !== confirmPassword) {
                errorAlert.textContent = 'Passwords do not match.';
                errorAlert.style.display = 'block';
                submitBtn.disabled = false;
                spinner.style.display = 'none';
                return;
            }

            // Check password strength
            const strengthResult = checkPasswordStrength(password);
            if (strengthResult.strength < 3) {
                errorAlert.textContent = 'Password is too weak. Please choose a stronger password.';
                errorAlert.style.display = 'block';
                submitBtn.disabled = false;
                spinner.style.display = 'none';
                return;
            }

            const formData = {
                token: token,
                password: password,
                confirm_password: confirmPassword
            };

            try {
                const response = await fetch('php/reset_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                let data;
                try {
                    data = await response.json();
                    console.log('Reset password response:', data);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    errorAlert.textContent = 'Server error occurred. Please try again.';
                    errorAlert.style.display = 'block';
                    submitBtn.disabled = false;
                    spinner.style.display = 'none';
                    return;
                }

                if (data.success) {
                    successAlert.textContent = data.message;
                    successAlert.style.display = 'block';

                    // Redirect to login after success
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 3000);
                } else {
                    errorAlert.textContent = data.message || 'Reset failed';
                    errorAlert.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                errorAlert.textContent = 'An error occurred. Please try again.';
                errorAlert.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Akanyenyeri Magazine</title>

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

        .container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .card {
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

        .info-text {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 6px;
            border-left: 4px solid #3182ce;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #4a5568;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="brand">
                <img src="../logo/akanyenyeri logo.png" alt="Akanyenyeri Logo" style="height: 80px; width: auto; margin-bottom: 1rem;">
                <h1>Reset Password</h1>
            </div>

            <div class="alert alert-error" id="error-alert"></div>
            <div class="alert alert-success" id="success-alert"></div>

            <div class="info-text">
                <strong>Note:</strong> If you are an admin, you will receive a password reset link via email. For other users, your request will be sent to the administrator for manual processing.
            </div>

            <form id="forgot-form">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required autofocus placeholder="Enter your email address">
                </div>

                <button type="submit" class="btn-primary" id="submit-btn">
                    <span>Send Reset Request</span>
                    <i class="fas fa-spinner fa-spin" style="display: none; margin-left: 0.5rem;"></i>
                </button>
            </form>

            <div class="footer-link">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('forgot-form').addEventListener('submit', async function(e) {
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

            const formData = {
                email: this.email.value
            };

            try {
                const response = await fetch('php/forgot_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                let data;
                try {
                    data = await response.json();
                    console.log('Forgot password response:', data);
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
                    // Clear form
                    this.email.value = '';
                } else {
                    errorAlert.textContent = data.message || 'Request failed';
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

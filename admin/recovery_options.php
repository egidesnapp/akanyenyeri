<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Recovery - Akanyenyeri Magazine</title>

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
            overflow: hidden;
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
            max-width: 600px;
        }

        .recovery-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            padding: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .recovery-card::before {
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

        .recovery-options {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .recovery-option {
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .recovery-option:hover {
            border-color: #60a5fa;
            background: rgba(96, 165, 250, 0.1);
            transform: translateY(-2px);
        }

        .recovery-option.selected {
            border-color: #60a5fa;
            background: rgba(96, 165, 250, 0.15);
            box-shadow: 0 4px 20px rgba(96, 165, 250, 0.3);
        }

        .option-icon {
            font-size: 2rem;
            color: #60a5fa;
            margin-bottom: 1rem;
        }

        .option-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .option-description {
            color: #e2e8f0;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .recovery-form {
            display: none;
        }

        .recovery-form.active {
            display: block;
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

        .form-control[type="textarea"] {
            resize: vertical;
            min-height: 80px;
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

        .btn-secondary {
            width: 100%;
            padding: 0.75rem;
            background: transparent;
            color: #3182ce;
            border: 2px solid #3182ce;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 1rem;
        }

        .btn-secondary:hover {
            background: #3182ce;
            color: white;
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

        .alert-info {
            background: #d4edda;
            color: #0c5460;
            border: 1px solid #b8daff;
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

        .recovery-status {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 4px solid #3182ce;
        }

        .recovery-status h3 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .recovery-status p {
            color: #646970;
            font-size: 0.9rem;
        }

        .question-list {
            margin-bottom: 1rem;
        }

        .question-item {
            margin-bottom: 0.5rem;
        }

        .question-item label {
            font-weight: 500;
            color: #4a5568;
        }

        .question-item input[type="radio"] {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .recovery-card {
                padding: 1.5rem;
            }

            .recovery-options {
                gap: 1rem;
            }

            .recovery-option {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Recovery Status -->
        <div class="recovery-status" id="recoveryStatus" style="display: none;">
            <h3>Recovery Method Selected</h3>
            <p id="statusMessage">Please complete the form below to recover your account.</p>
        </div>

        <!-- Recovery Options -->
        <div class="recovery-card">
            <div class="content-wrapper">
                <div class="brand">
                    <img src="../logo/akanyenyeri logo.png" alt="Akanyenyeri Logo" style="height: 80px; width: auto; margin-bottom: 1rem;">
                    <h1>Account Recovery</h1>
                </div>

            <div class="alert alert-info">
                <strong>Multiple Recovery Options:</strong> Choose the method that works best for you. If email doesn't work, try security questions or contact an administrator.
                <br><br><strong>Direct Admin Contact:</strong> <span id="adminContactInfo">Loading...</span>
            </div>

            <div class="alert alert-error" id="error-alert"></div>
            <div class="alert alert-success" id="success-alert"></div>

            <!-- Recovery Method Selection -->
            <div class="recovery-options" id="recoveryOptions">
                <div class="recovery-option" data-method="email">
                    <div class="option-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="option-title">Email Recovery</div>
                    <div class="option-description">
                        Receive a password reset link via email. This is the fastest method for admins.
                    </div>
                </div>

                <div class="recovery-option" data-method="questions">
                    <div class="option-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="option-title">Security Questions</div>
                    <div class="option-description">
                        Answer your pre-set security questions to verify your identity and reset your password. <em>(Must be set up first)</em>
                    </div>
                </div>

                <div class="recovery-option" data-method="admin">
                    <div class="option-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="option-title">Contact Administrator</div>
                    <div class="option-description">
                        Send a request to the system administrator who will manually assist with your password reset.
                    </div>
                </div>
            </div>

            <!-- Email Recovery Form -->
            <div class="recovery-form" id="emailForm">
                <h3 style="margin-bottom: 1rem; color: #2d3748;">Email Recovery</h3>
                <form id="emailRecoveryForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email address">
                    </div>
                    <button type="submit" class="btn-primary" id="emailSubmitBtn">
                        <span>Send Reset Link</span>
                        <i class="fas fa-spinner fa-spin" style="display: none; margin-left: 0.5rem;"></i>
                    </button>
                </form>
            </div>

            <!-- Security Questions Form -->
            <div class="recovery-form" id="questionsForm">
                <h3 style="margin-bottom: 1rem; color: #2d3748;">Security Questions</h3>
                <form id="questionsRecoveryForm">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required placeholder="Enter your username">
                    </div>

                    <div class="question-list" id="questionList" style="display: none;">
                        <p style="margin-bottom: 1rem; color: #646970;">Answer your security questions:</p>
                        <!-- Questions will be loaded dynamically -->
                    </div>

                    <div class="form-group" id="newPasswordGroup" style="display: none;">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password">
                        <div class="password-strength" id="password-strength" style="margin-top: 0.5rem; font-size: 0.8rem;"></div>
                    </div>

                    <button type="submit" class="btn-primary" id="questionsSubmitBtn">
                        <span>Continue</span>
                        <i class="fas fa-spinner fa-spin" style="display: none; margin-left: 0.5rem;"></i>
                    </button>
                </form>
            </div>

            <!-- Admin Contact Form -->
            <div class="recovery-form" id="adminForm">
                <h3 style="margin-bottom: 1rem; color: #2d3748;">Contact Administrator</h3>
                <form id="adminRecoveryForm">
                    <div class="form-group">
                        <label for="contact_email" class="form-label">Your Email Address</label>
                        <input type="email" id="contact_email" name="contact_email" class="form-control" required placeholder="Enter your email address">
                    </div>

                    <div class="form-group">
                        <label for="contact_message" class="form-label">Additional Information (Optional)</label>
                        <textarea id="contact_message" name="contact_message" class="form-control" rows="3" placeholder="Provide any additional information that might help identify your account"></textarea>
                    </div>

                    <button type="submit" class="btn-primary" id="adminSubmitBtn">
                        <span>Send Request</span>
                        <i class="fas fa-spinner fa-spin" style="display: none; margin-left: 0.5rem;"></i>
                    </button>
                </form>
            </div>

            <button class="btn-secondary" id="backBtn" style="display: none;">
                <i class="fas fa-arrow-left"></i> Choose Different Method
            </button>

            <div class="footer-link">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        let currentMethod = null;
        let selectedQuestions = [];

        // Load admin contact info on page load
        window.addEventListener('DOMContentLoaded', async function() {
            await loadAdminContactInfo();
        });

        async function loadAdminContactInfo() {
            try {
                const response = await fetch('php/admin_contact.php?action=get_contact_info');
                const data = await response.json();

                if (data.success) {
                    const contactInfo = data.contact_info;
                    document.getElementById('adminContactInfo').textContent =
                        `${contactInfo.name} (${contactInfo.email})`;
                } else {
                    document.getElementById('adminContactInfo').textContent = 'admin@akanyenyeri.com';
                }
            } catch (error) {
                console.error('Error loading admin contact info:', error);
                document.getElementById('adminContactInfo').textContent = 'admin@akanyenyeri.com';
            }
        }

        // Recovery method selection
        document.querySelectorAll('.recovery-option').forEach(option => {
            option.addEventListener('click', function() {
                const method = this.getAttribute('data-method');
                selectRecoveryMethod(method);
            });
        });

        function selectRecoveryMethod(method) {
            // Remove selected class from all options
            document.querySelectorAll('.recovery-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            // Add selected class to clicked option
            document.querySelector(`[data-method="${method}"]`).classList.add('selected');

            // Hide all forms
            document.querySelectorAll('.recovery-form').forEach(form => {
                form.classList.remove('active');
            });

            // Show selected form
            document.getElementById(`${method}Form`).classList.add('active');

            // Update status
            document.getElementById('recoveryStatus').style.display = 'block';
            document.getElementById('recoveryOptions').style.display = 'none';
            document.getElementById('backBtn').style.display = 'block';

            currentMethod = method;

            // Update status message
            const messages = {
                'email': 'Please enter your email address to receive a password reset link.',
                'questions': 'Please enter your username to load your security questions.',
                'admin': 'Please provide your contact information so an administrator can assist you.'
            };
            document.getElementById('statusMessage').textContent = messages[method];
        }

        // Back button
        document.getElementById('backBtn').addEventListener('click', function() {
            document.querySelectorAll('.recovery-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            document.querySelectorAll('.recovery-form').forEach(form => {
                form.classList.remove('active');
            });

            document.getElementById('recoveryStatus').style.display = 'none';
            document.getElementById('recoveryOptions').style.display = 'grid';
            document.getElementById('backBtn').style.display = 'none';
            document.getElementById('questionList').style.display = 'none';
            document.getElementById('newPasswordGroup').style.display = 'none';

            currentMethod = null;
        });

        // Email recovery form
        document.getElementById('emailRecoveryForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('emailSubmitBtn');
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

                const data = await response.json();

                if (data.success) {
                    successAlert.textContent = data.message;
                    successAlert.style.display = 'block';
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

        // Security questions form
        document.getElementById('questionsRecoveryForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('questionsSubmitBtn');
            const errorAlert = document.getElementById('error-alert');
            const successAlert = document.getElementById('success-alert');

            // Reset alerts
            errorAlert.style.display = 'none';
            successAlert.style.display = 'none';

            if (document.getElementById('questionList').style.display === 'none') {
                // First step: Load questions
                await loadSecurityQuestions();
            } else if (document.getElementById('newPasswordGroup').style.display === 'none') {
                // Second step: Verify answers
                await verifySecurityAnswers();
            } else {
                // Third step: Reset password
                await resetPasswordWithQuestions();
            }
        });

        async function loadSecurityQuestions() {
            const username = document.getElementById('username').value;
            const submitBtn = document.getElementById('questionsSubmitBtn');
            const spinner = submitBtn.querySelector('.fa-spinner');

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';

            try {
                const response = await fetch('php/recovery_questions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'load_questions',
                        username: username
                    })
                });

                const data = await response.json();

                if (data.success) {
                    displaySecurityQuestions(data.questions);
                } else {
                    document.getElementById('error-alert').textContent = data.message || 'Failed to load questions';
                    document.getElementById('error-alert').style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('error-alert').textContent = 'An error occurred. Please try again.';
                document.getElementById('error-alert').style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }
        }

        function displaySecurityQuestions(questions) {
            const questionList = document.getElementById('questionList');
            questionList.innerHTML = '<p style="margin-bottom: 1rem; color: #646970;">Answer your security questions:</p>';

            questions.forEach((question, index) => {
                const questionDiv = document.createElement('div');
                questionDiv.className = 'question-item';
                questionDiv.innerHTML = `
                    <label>${question.question}</label>
                    <input type="text" class="form-control" name="answer_${index}" required placeholder="Your answer">
                    <input type="hidden" name="question_${index}" value="${question.question}">
                `;
                questionList.appendChild(questionDiv);
            });

            questionList.style.display = 'block';
            document.getElementById('questionsSubmitBtn').querySelector('span').textContent = 'Verify Answers';
        }

        async function verifySecurityAnswers() {
            const formData = new FormData(document.getElementById('questionsRecoveryForm'));
            const submitBtn = document.getElementById('questionsSubmitBtn');
            const spinner = submitBtn.querySelector('.fa-spinner');

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';

            const answers = [];
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('answer_')) {
                    const index = key.split('_')[1];
                    const question = formData.get(`question_${index}`);
                    answers.push({
                        question: question,
                        answer: value
                    });
                }
            }

            try {
                const response = await fetch('php/recovery_questions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'verify_answers',
                        username: document.getElementById('username').value,
                        answers: answers
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('newPasswordGroup').style.display = 'block';
                    document.getElementById('questionsSubmitBtn').querySelector('span').textContent = 'Reset Password';
                    selectedQuestions = answers;
                } else {
                    document.getElementById('error-alert').textContent = data.message || 'Verification failed';
                    document.getElementById('error-alert').style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('error-alert').textContent = 'An error occurred. Please try again.';
                document.getElementById('error-alert').style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }
        }

        async function resetPasswordWithQuestions() {
            const newPassword = document.getElementById('new_password').value;
            const submitBtn = document.getElementById('questionsSubmitBtn');
            const spinner = submitBtn.querySelector('.fa-spinner');

            // Validate password strength
            if (!checkPasswordStrength(newPassword)) {
                document.getElementById('error-alert').textContent = 'Password does not meet security requirements.';
                document.getElementById('error-alert').style.display = 'block';
                return;
            }

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';

            try {
                const response = await fetch('php/recovery_questions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'reset_password',
                        username: document.getElementById('username').value,
                        new_password: newPassword,
                        answers: selectedQuestions
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('success-alert').textContent = data.message;
                    document.getElementById('success-alert').style.display = 'block';
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 3000);
                } else {
                    document.getElementById('error-alert').textContent = data.message || 'Reset failed';
                    document.getElementById('error-alert').style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('error-alert').textContent = 'An error occurred. Please try again.';
                document.getElementById('error-alert').style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            return password.length >= 8 &&
                   /[a-z]/.test(password) &&
                   /[A-Z]/.test(password) &&
                   /[0-9]/.test(password) &&
                   /[^A-Za-z0-9]/.test(password);
        }

        // Password input handler
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const isStrong = checkPasswordStrength(password);
            const strengthElement = document.getElementById('password-strength');

            if (password.length > 0) {
                strengthElement.textContent = isStrong ?
                    'Password meets security requirements' :
                    'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
                strengthElement.style.color = isStrong ? '#38a169' : '#e53e3e';
            } else {
                strengthElement.textContent = '';
            }
        });

        // Admin contact form
        document.getElementById('adminRecoveryForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('adminSubmitBtn');
            const errorAlert = document.getElementById('error-alert');
            const successAlert = document.getElementById('success-alert');
            const spinner = submitBtn.querySelector('.fa-spinner');

            // Reset state
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            errorAlert.style.display = 'none';
            successAlert.style.display = 'none';

            const formData = {
                email: this.contact_email.value,
                message: this.contact_message.value
            };

            try {
                const response = await fetch('php/admin_contact.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    successAlert.textContent = data.message;
                    successAlert.style.display = 'block';
                    this.contact_email.value = '';
                    this.contact_message.value = '';
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

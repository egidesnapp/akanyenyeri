<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Security Questions - Akanyenyeri Magazine</title>

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

        .info-text {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #60a5fa;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            color: #e2e8f0;
        }

        .question-group {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .form-group {
            margin-bottom: 1rem;
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
            border-radius: 6px;
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

        .content-wrapper {
            position: relative;
            z-index: 2;
        }

        .question-select {
            margin-bottom: 1rem;
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

        .add-question {
            text-align: center;
            margin-bottom: 1rem;
        }

        .add-question button {
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .add-question button:hover {
            background: #cbd5e0;
        }

        .remove-question {
            background: #fed7d7;
            color: #c53030;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .remove-question:hover {
            background: #feb2b2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="content-wrapper">
                <div class="brand">
                    <img src="../logo/akanyenyeri logo.png" alt="Akanyenyeri Logo" style="height: 80px; width: auto; margin-bottom: 1rem;">
                    <h1>Setup Security Questions</h1>
                </div>

            <div class="info-text">
                <strong>Security Questions:</strong> Set up at least 3 security questions and answers. These will help you recover your account if you forget your password. Choose questions with answers that are memorable to you but difficult for others to guess.
            </div>

            <div class="alert alert-error" id="error-alert"></div>
            <div class="alert alert-success" id="success-alert"></div>

            <form id="securityForm">
                <div id="questionsContainer">
                    <!-- Questions will be added dynamically -->
                </div>

                <div class="add-question">
                    <button type="button" onclick="addQuestion()">
                        <i class="fas fa-plus"></i> Add Another Question
                    </button>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">
                    <span>Save Security Questions</span>
                    <i class="fas fa-spinner fa-spin" style="display: none; margin-left: 0.5rem;"></i>
                </button>
            </form>

            <div class="footer-link">
                <a href="dashboard.php">Skip for Now</a> | <a href="profile.php">Go to Profile</a>
            </div>
        </div>
    </div>

    <script>
        let questionCount = 0;
        const defaultQuestions = [
            "What was the name of your first pet?",
            "What is your mother's maiden name?",
            "What was the name of your first school?",
            "What is your favorite childhood memory?",
            "What was your childhood nickname?",
            "What is the name of the city where you were born?",
            "What was your favorite subject in school?",
            "What was the make and model of your first car?",
            "What is your favorite book or movie?",
            "What was the name of your best friend in childhood?"
        ];

        // Initialize with 3 questions
        window.addEventListener('DOMContentLoaded', function() {
            for (let i = 0; i < 3; i++) {
                addQuestion();
            }
        });

        function addQuestion() {
            if (questionCount >= 5) {
                alert('Maximum 5 security questions allowed.');
                return;
            }

            questionCount++;
            const container = document.getElementById('questionsContainer');

            const questionDiv = document.createElement('div');
            questionDiv.className = 'question-group';
            questionDiv.id = `question-${questionCount}`;

            questionDiv.innerHTML = `
                <div class="form-group">
                    <label class="form-label">Security Question ${questionCount}</label>
                    <select class="form-control question-select" name="question_${questionCount}" required>
                        <option value="">Select a question...</option>
                        ${defaultQuestions.map(q => `<option value="${q}">${q}</option>`).join('')}
                        <option value="custom">Write your own question...</option>
                    </select>
                </div>
                <div class="form-group" id="custom-question-${questionCount}" style="display: none;">
                    <input type="text" class="form-control" name="custom_question_${questionCount}"
                           placeholder="Enter your custom question" maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label">Answer</label>
                    <input type="text" class="form-control" name="answer_${questionCount}"
                           placeholder="Your answer (case insensitive)" required>
                </div>
                ${questionCount > 3 ? `<button type="button" class="remove-question" onclick="removeQuestion(${questionCount})">
                    <i class="fas fa-trash"></i> Remove
                </button>` : ''}
            `;

            container.appendChild(questionDiv);

            // Add event listener for custom question selection
            const select = questionDiv.querySelector('.question-select');
            select.addEventListener('change', function() {
                toggleCustomQuestion(questionCount, this.value === 'custom');
            });
        }

        function removeQuestion(id) {
            const questionDiv = document.getElementById(`question-${id}`);
            questionDiv.remove();
            questionCount--;

            // Renumber remaining questions
            let counter = 1;
            document.querySelectorAll('.question-group').forEach(div => {
                const label = div.querySelector('.form-label');
                if (label) {
                    label.textContent = `Security Question ${counter}`;
                }
                counter++;
            });
        }

        function toggleCustomQuestion(id, show) {
            const customDiv = document.getElementById(`custom-question-${id}`);
            const customInput = customDiv.querySelector('input');

            if (show) {
                customDiv.style.display = 'block';
                customInput.required = true;
            } else {
                customDiv.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
            }
        }

        // Form submission
        document.getElementById('securityForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const errorAlert = document.getElementById('error-alert');
            const successAlert = document.getElementById('success-alert');
            const spinner = submitBtn.querySelector('.fa-spinner');

            // Reset alerts
            errorAlert.style.display = 'none';
            successAlert.style.display = 'none';

            // Collect form data
            const formData = new FormData(this);
            const questions = [];

            for (let i = 1; i <= questionCount; i++) {
                const questionSelect = formData.get(`question_${i}`);
                const customQuestion = formData.get(`custom_question_${i}`);
                const answer = formData.get(`answer_${i}`);

                if (!questionSelect || !answer) continue;

                const question = questionSelect === 'custom' ? customQuestion : questionSelect;
                if (!question.trim()) continue;

                questions.push({
                    question: question.trim(),
                    answer: answer.trim()
                });
            }

            if (questions.length < 3) {
                errorAlert.textContent = 'Please set up at least 3 security questions.';
                errorAlert.style.display = 'block';
                return;
            }

            // Check for duplicate questions
            const questionTexts = questions.map(q => q.question.toLowerCase());
            if (new Set(questionTexts).size !== questionTexts.length) {
                errorAlert.textContent = 'Please use different questions for each entry.';
                errorAlert.style.display = 'block';
                return;
            }

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';

            try {
                const response = await fetch('php/setup_security.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        questions: questions
                    })
                });

                const data = await response.json();

                if (data.success) {
                    successAlert.textContent = data.message;
                    successAlert.style.display = 'block';

                    // Redirect after success
                    setTimeout(() => {
                        window.location.href = 'profile.php';
                    }, 2000);
                } else {
                    errorAlert.textContent = data.message || 'Failed to save security questions';
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

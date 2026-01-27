<!-- Footer -->
    <footer class="footer">
        <div class="container">
            <!-- Compact Footer Content -->
            <div class="footer-content">
                <!-- Brand Section -->
                <div class="footer-section">
                    <div class="footer-brand">
                        <img src="<?php echo SITE_URL; ?>logo/akanyenyeri_logo.png" alt="Akanyenyeri Logo" style="height: 25px; width: auto; margin-right: 8px;">
                        <span>Akanyenyeri</span>
                    </div>
                    <p class="footer-text">Your trusted source for comprehensive news coverage.</p>
                </div>

                <!-- Quick Links Section -->
                <div class="footer-section">
                    <h6>Quick Links</h6>
                    <div class="footer-links-horizontal">
                        <a href="<?php echo SITE_URL; ?>public/index.php?v=footer">Home</a>
                        <a href="<?php echo SITE_URL; ?>public/index.php#news">Latest News</a>
                        <a href="<?php echo SITE_URL; ?>public/index.php#categories">Categories</a>
                        <a href="<?php echo SITE_URL; ?>public/about.php">About Us</a>
                        <a href="<?php echo SITE_URL; ?>public/services.php">Services</a>
                        <a href="<?php echo SITE_URL; ?>public/contact.php">Contact</a>
                    </div>
                </div>

                <!-- Categories Section -->
                <div class="footer-section">
                    <h6>Categories</h6>
                    <div class="footer-links-horizontal">
                        <?php
                        // Get categories for footer links
                        if (function_exists('getCategories')) {
                            $footerCategories = getCategories($pdo ?? null);
                            if (!empty($footerCategories)) {
                                foreach (array_slice($footerCategories, 0, 6) as $cat) {
                                    echo '<a href="' . SITE_URL . 'public/category.php?slug=' . htmlspecialchars($cat['slug']) . '">' . htmlspecialchars($cat['name']) . '</a>';
                                }
                            } else {
                                // Fallback static links if function not available
                                echo '<a href="' . SITE_URL . 'public/category.php?slug=politics">Politics</a>';
                                echo '<a href="' . SITE_URL . 'public/category.php?slug=business">Business</a>';
                                echo '<a href="' . SITE_URL . 'public/category.php?slug=technology">Technology</a>';
                                echo '<a href="' . SITE_URL . 'public/category.php?slug=sports">Sports</a>';
                                echo '<a href="' . SITE_URL . 'public/category.php?slug=entertainment">Entertainment</a>';
                                echo '<a href="' . SITE_URL . 'public/category.php?slug=health">Health</a>';
                            }
                        } else {
                            echo '<a href="' . SITE_URL . 'public/category.php?slug=politics">Politics</a>';
                            echo '<a href="' . SITE_URL . 'public/category.php?slug=business">Business</a>';
                            echo '<a href="' . SITE_URL . 'public/category.php?slug=technology">Technology</a>';
                            echo '<a href="' . SITE_URL . 'public/category.php?slug=sports">Sports</a>';
                            echo '<a href="' . SITE_URL . 'public/category.php?slug=entertainment">Entertainment</a>';
                            echo '<a href="' . SITE_URL . 'public/category.php?slug=health">Health</a>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Contact & Social Section -->
                <div class="footer-section">
                    <h6>Connect</h6>
                    <div class="footer-contact-compact">
                        <p><i class="fas fa-envelope me-2"></i>akanyenyeriblog@gmail.com</p>
                    </div>
                    <div class="social-links-compact">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom-compact">
                <p class="mb-0">&copy; 2025 Akanyenyeri. All rights reserved.</p>
                <div class="footer-legal-compact">
                    <a href="<?php echo SITE_URL; ?>public/privacy.php">Privacy</a>
                    <a href="<?php echo SITE_URL; ?>public/terms.php">Terms</a>
                    <a href="<?php echo SITE_URL; ?>public/cookies.php">Cookies</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        // Enhanced Star Loading Animation
        window.addEventListener('load', function() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                // Add fade-out animation after page loads
                setTimeout(() => {
                    loadingOverlay.style.opacity = '0';
                    loadingOverlay.style.visibility = 'hidden';
                    
                    // Add a final star burst effect before hiding
                    const starLoader = loadingOverlay.querySelector('.star-loader');
                    if (starLoader) {
                        starLoader.style.transform = 'scale(1.5)';
                        starLoader.style.opacity = '0';
                    }
                    
                    // Completely remove after animation
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                    }, 500);
                }, 1500);
            }
        });

        // Function to show loading animation for AJAX requests
        function showStarLoader() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
                loadingOverlay.style.opacity = '1';
                loadingOverlay.style.visibility = 'visible';
            }
        }

        // Function to hide loading animation
        function hideStarLoader() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                loadingOverlay.style.visibility = 'hidden';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 500);
            }
        }

        // Newsletter form submission
        document.getElementById('newsletterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;

            // Simulate form submission
            const button = this.querySelector('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subscribing...';
            button.disabled = true;

            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-check me-2"></i>Subscribed!';
                button.classList.remove('btn-custom');
                button.classList.add('btn-success');

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-custom');
                    button.disabled = false;
                    this.reset();
                }, 3000);
            }, 2000);
        });

        // Smooth scrolling for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');

            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(30, 41, 59, 0.98)';
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.background = 'rgba(30, 41, 59, 0.95)';
                navbar.style.boxShadow = 'none';
            }
        });

        // Parallax effect for hero section
        window.addEventListener('scroll', function() {
            const hero = document.querySelector('.hero-section');
            const scrolled = window.pageYOffset;
            hero.style.backgroundPositionY = -(scrolled * 0.5) + 'px';
        });

        // Card hover effects
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Advertisement Rotation Functionality
        let currentAdIndex = 0;
        let adInterval;

        function initAdvertisements() {
            const adSlides = document.querySelectorAll('.hero-ad-slide');
            const adDots = document.querySelectorAll('.hero-ad-dot');

            if (adSlides.length === 0) return;

            // Show first slide
            showAdSlide(0);

            // Auto-rotate every 5 seconds
            adInterval = setInterval(() => {
                currentAdIndex = (currentAdIndex + 1) % adSlides.length;
                showAdSlide(currentAdIndex);
            }, 5000);

            // Dot click handlers
            adDots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    clearInterval(adInterval);
                    showAdSlide(index);
                    currentAdIndex = index;
                    // Restart auto-rotation
                    adInterval = setInterval(() => {
                        currentAdIndex = (currentAdIndex + 1) % adSlides.length;
                        showAdSlide(currentAdIndex);
                    }, 5000);
                });
            });
        }

        function showAdSlide(index) {
            const adSlides = document.querySelectorAll('.hero-ad-slide');
            const adDots = document.querySelectorAll('.hero-ad-dot');

            // Hide all slides
            adSlides.forEach(slide => slide.classList.remove('active'));
            adDots.forEach(dot => dot.classList.remove('active'));

            // Show selected slide
            adSlides[index].classList.add('active');
            if (adDots[index]) {
                adDots[index].classList.add('active');
            }
        }

        // Initialize advertisements when page loads
        document.addEventListener('DOMContentLoaded', initAdvertisements);

        // Enhanced Hero Section Interactions
        function initHeroInteractions() {
            const heroSection = document.querySelector('.hero-section');
            const heroContent = document.querySelector('.hero-content');
            
            if (!heroSection || !heroContent) return;

            // Parallax effect for hero section
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                
                if (heroSection) {
                    heroSection.style.transform = `translateY(${rate}px)`;
                }
            });

            // Enhanced button hover effects
            const heroButton = document.querySelector('.hero-section .btn-custom');
            if (heroButton) {
                heroButton.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px) scale(1.05)';
                    this.style.boxShadow = '0 15px 35px rgba(37, 99, 235, 0.5)';
                });

                heroButton.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '0 10px 25px rgba(37, 99, 235, 0.35)';
                });
            }

            // Add entrance animations
            const heroElements = document.querySelectorAll('.hero-content > *');
            heroElements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    element.style.transition = 'all 0.8s ease-out';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 200);
            });
        }

        // Initialize hero interactions after DOM is loaded
        document.addEventListener('DOMContentLoaded', initHeroInteractions);
    </script>
</body>
</html>

    </main>
    
    <!-- Footer Section -->
    <footer class="bg-dark text-white mt-5 py-5 border-top">
        <div class="container-fluid px-4">
            <div class="row mb-4">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="../uploads/akanyenyeri-logo.svg" alt="Akanyenyeri Logo" style="height: 50px; width: auto;">
                        <h5 class="fw-bold mb-0">Akanyenyeri Magazine</h5>
                    </div>
                    <p class="small">Bringing you the latest news, analysis, and stories from around the world. Dedicated to providing accurate and engaging content.</p>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">Quick Links</h5>
                    <ul class="list-unstyled small">
                        <li><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                        <li><a href="index.php?category=business" class="text-white-50 text-decoration-none">Business</a></li>
                        <li><a href="index.php?category=politics" class="text-white-50 text-decoration-none">Politics</a></li>
                        <li><a href="index.php?category=technology" class="text-white-50 text-decoration-none">Technology</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">Follow Us</h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white text-decoration-none" title="Facebook">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                        <a href="#" class="text-white text-decoration-none" title="Twitter">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                        <a href="#" class="text-white text-decoration-none" title="Instagram">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="text-white text-decoration-none" title="LinkedIn">
                            <i class="fab fa-linkedin fa-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <hr class="bg-white-50">
            
            <div class="text-center small text-white-50">
                <p>&copy; <?php echo date('Y'); ?> Akanyenyeri Magazine. All rights reserved. | 
                <a href="#" class="text-white-50 text-decoration-none">Privacy Policy</a> | 
                <a href="#" class="text-white-50 text-decoration-none">Terms of Service</a></p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Spinner Loading Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const spinner = document.getElementById('spinnerOverlay');
            
            document.addEventListener('click', function(e){
                const a = e.target.closest('a');
                if (!a) return;
                const href = a.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('javascript:')) return;
                if (spinner) spinner.style.display = 'flex';
            }, true);
            
            window.addEventListener('load', function(){
                if (spinner) spinner.style.display = 'none';
            });
        });
    </script>
</body>
</html>

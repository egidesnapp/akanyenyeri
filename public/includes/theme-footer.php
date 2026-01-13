<?php
/**
 * Footer Template
 * Based on Rectified Magazine Theme
 */
?>

    <footer id="colophon" class="site-footer">
        <!-- Top Footer (Widgets Area) -->
        <div class="top-footer">
            <div class="container-inner clearfix">
                <div class="ct-col-4">
                    <section class="widget">
                        <h2 class="widget-title">About Us</h2>
                        <div class="textwidget">
                            <p>Akanyenyeri Magazine brings you the latest news, analysis, and stories from around the world. We are dedicated to providing accurate and engaging content.</p>
                        </div>
                    </section>
                </div>
                
                <div class="ct-col-4">
                    <section class="widget">
                        <h2 class="widget-title">Quick Links</h2>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="admin/login.php">Login</a></li>
                            <li><a href="#">Contact Us</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                        </ul>
                    </section>
                </div>
                
                <div class="ct-col-4">
                    <section class="widget">
                        <h2 class="widget-title">Follow Us</h2>
                        <ul class="rectified-magazine-menu-social" style="display: flex; gap: 10px; list-style: none; padding: 0;">
                            <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                            <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                            <li><a href="#"><i class="fa fa-instagram"></i></a></li>
                            <li><a href="#"><i class="fa fa-youtube"></i></a></li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>

        <!-- Site Info (Copyright) -->
        <div class="site-info">
            <div class="container-inner">
                <span class="copy-right-text">
                    &copy; <?php echo date('Y'); ?> Akanyenyeri Magazine. All rights reserved.
                </span>
                <span class="sep"> | </span>
                <span class="designer-link">
                    Designed inspired by <a href="#" target="_blank">Rectified Magazine</a>
                </span>
            </div>
        </div>
    </footer>

    </div><!-- #page -->

    <!-- Scripts -->
    <script src="<?php echo isset($base) ? $base : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/theme/rectified/assets/framework/slick/slick.min.js"></script>
    <script src="<?php echo isset($base) ? $base : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/theme/rectified/assets/js/rectified-magazine-custom.js"></script>
    
    <!-- AOS Animation Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50
        });

        // Initialize Slick Slider (if needed manually, though theme js might handle it)
        $(document).ready(function(){
            if($('.main-slider').length && $.fn.slick) {
                $('.main-slider').slick({
                    dots: true,
                    infinite: true,
                    speed: 500,
                    fade: true,
                    cssEase: 'linear',
                    autoplay: true,
                    autoplaySpeed: 3000
                });
            }
        });
    </script>
</body>
</html>

<?php include 'includes/head.php'; ?>
<?php include 'includes/nav.php'; ?>

<section class="hero-section" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.9) 0%, rgba(217, 119, 6, 0.9) 100%), url('https://images.unsplash.com/photo-1423666639041-f56000c27a9a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1472&q=80');">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title fade-in">Contact Us</h1>
            <p class="hero-subtitle fade-in" data-aos-delay="200">
                Get in touch with our team. We'd love to hear from you!
            </p>
        </div>
    </div>
</section>

<section class="container my-5">
    <div class="row g-5">
        <div class="col-lg-6">
            <h2 class="section-title mb-4">Get In Touch</h2>
            <p class="mb-4">
                Have a story idea, feedback, or just want to say hello? We'd love to hear from you.
                Reach out to us using any of the methods below.
            </p>

            <div class="row g-4">
                <div class="col-12">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-map-marker-alt fa-2x text-primary me-3 mt-1"></i>
                        <div>
                            <h5>Office Address</h5>
                            <p class="mb-0">KG 123 St, Kigali<br>Rwanda, East Africa</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-phone fa-2x text-success me-3 mt-1"></i>
                        <div>
                            <h5>Phone</h5>
                            <p class="mb-0">+250 123 456 789</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-envelope fa-2x text-warning me-3 mt-1"></i>
                        <div>
                            <h5>Email</h5>
                            <p class="mb-0">info@akanyenyeri.rw<br>editor@akanyenyeri.rw</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-clock fa-2x text-info me-3 mt-1"></i>
                        <div>
                            <h5>Business Hours</h5>
                            <p class="mb-0">Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow" style="background: var(--card-bg);">
                <div class="card-body p-4">
                    <h3 class="card-title mb-4">Send us a Message</h3>
                    <form>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" required>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" required>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="5" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-custom w-100">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

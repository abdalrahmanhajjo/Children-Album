    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-8 mb-lg-0">
                    <h3 class="text-2xl font-bold mb-4 heading-font"><?php echo SITE_NAME; ?></h3>
                    <p class="text-gray-400 mb-4">Create beautiful albums for your children and cherish every moment.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-pink-400 transition-all">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-pink-400 transition-all">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-pink-400 transition-all">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-8 mb-md-0">
                    <h4 class="text-lg font-bold mb-4 heading-font">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="<?php echo SITE_URL; ?>" class="text-gray-400 hover:text-pink-400 transition-all">Home</a></li>
                        <?php if (is_logged_in()): ?>
                            <li><a href="<?php echo SITE_URL; ?>/dashboard.php" class="text-gray-400 hover:text-pink-400 transition-all">Dashboard</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/add-child.php" class="text-gray-400 hover:text-pink-400 transition-all">Add Child</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo SITE_URL; ?>/login.php" class="text-gray-400 hover:text-pink-400 transition-all">Login</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/register.php" class="text-gray-400 hover:text-pink-400 transition-all">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-8 mb-md-0">
                    <h4 class="text-lg font-bold mb-4 heading-font">Features</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition-all">Photo Gallery</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition-all">Milestones</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition-all">Wishes</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition-all">Growth Tracking</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h4 class="text-lg font-bold mb-4 heading-font">Newsletter</h4>
                    <p class="text-gray-400 mb-4">Subscribe to get updates on new features.</p>
                    <form class="flex">
                        <input type="email" placeholder="Your email" class="px-4 py-2 rounded-l-lg focus:outline-none text-gray-900 w-full">
                        <button type="submit" class="bg-pink-600 text-white px-4 py-2 rounded-r-lg hover:bg-pink-700 transition-all">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-10 pt-6 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved. Made with <i class="fas fa-heart text-pink-400"></i> for parents</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" id="back-to-top" class="fixed bottom-8 right-8 w-12 h-12 bg-pink-600 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-pink-700 transition-all opacity-0 invisible">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
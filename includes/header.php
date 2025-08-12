<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'auth.php';
require_once 'i18n.php';

$i18n = new i18n();

// Get user profile picture if logged in
$profilePic = null;
$defaultProfilePic = SITE_URL . '/assets/images/default-profile.png';

if (is_logged_in()) {
    // Check if we already have the profile picture in session
    if (isset($_SESSION['profile_picture'])) {
        $profilePic = !empty($_SESSION['profile_picture']) 
            ? SITE_URL . '/uploads/profile_pics/' . $_SESSION['profile_picture']
            : $defaultProfilePic;
    } else {
        // Fetch from database if not in session
        require_once 'functions.php';
        $userData = get_user_data($_SESSION['user_id']);
        if ($userData && !empty($userData['profile_picture'])) {
            $profilePic = SITE_URL . '/uploads/profile_pics/' . $userData['profile_picture'];
            $_SESSION['profile_picture'] = $userData['profile_picture']; // Store in session
        } else {
            $profilePic = $defaultProfilePic;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $i18n->getLanguage() ?>" <?= $i18n->getLanguage() === 'ar' ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? $site_name ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                        'tajawal': ['Tajawal', 'sans-serif']
                    },
                    colors: {
                        'brand': {
                            50: '#fdf2f8',
                            100: '#fce7f3',
                            500: '#ec4899',
                            600: '#db2777',
                            700: '#be185d'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" 
          integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= SITE_URL; ?>/assets/css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Arabic Font -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        /* Custom smooth animations */
        .dropdown-menu {
            transform: translateY(-8px);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .dropdown-menu.show {
            transform: translateY(0);
        }
        
        .mobile-drawer {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .backdrop {
            transition: opacity 0.3s ease-in-out;
        }
        
        /* Focus styles for accessibility */
        .focus-ring:focus {
            outline: 2px solid #ec4899;
            outline-offset: 2px;
        }
        
        /* Logo hover effect */
        .logo-hover:hover {
            transform: scale(1.02);
            transition: transform 0.2s ease-in-out;
        }
        
        /* Profile picture styles */
        .profile-pic {
            object-fit: cover;
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="bg-gray-50 font-poppins antialiased">

<!-- SKIP LINK for accessibility -->
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-brand-600 text-white px-4 py-2 rounded-md z-[60]">
    Skip to main content
</a>

<!-- NAVBAR -->
<header class="fixed top-0 left-0 w-full z-50 bg-white/90 backdrop-blur-lg shadow-sm border-b border-gray-100" role="banner">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            <!-- Logo -->
            <div class="flex items-center">
                <a href="<?= SITE_URL; ?>/index.php" 
                   class="logo-hover flex items-center text-brand-600 font-bold text-xl focus-ring rounded-lg px-2 py-1 -ml-2"
                   aria-label="<?= SITE_NAME; ?> Home">
                    <i class="fas fa-baby-carriage text-2xl mr-3" aria-hidden="true"></i>
                    <span class="hidden sm:inline"><?= SITE_NAME; ?></span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden lg:flex items-center space-x-8" role="navigation" aria-label="Main navigation">
                <?php if (is_logged_in()): ?>
                    <a href="<?= SITE_URL; ?>/pages/dashboard.php" 
                       class="flex items-center text-gray-700 hover:text-brand-600 focus-ring rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-home mr-2" aria-hidden="true"></i>
                        Dashboard
                    </a>
                    <a href="<?= SITE_URL; ?>/pages/add-child.php" 
                       class="flex items-center text-gray-700 hover:text-brand-600 focus-ring rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-baby mr-2" aria-hidden="true"></i>
                        Add Child
                    </a>

                    <!-- Profile Dropdown with Alpine.js -->
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open"
                                class="flex items-center space-x-3 text-gray-700 hover:text-brand-600 focus-ring rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200"
                                aria-haspopup="true"
                                :aria-expanded="open"
                                aria-label="User menu">
                            <img src="<?= $profilePic ?>" 
                                 alt="Profile picture of <?= htmlspecialchars($_SESSION['username']); ?>" 
                                 class="profile-pic w-8 h-8 rounded-full border-2 border-gray-200 object-cover"
                                 loading="lazy"
                                 onerror="this.src='<?= $defaultProfilePic ?>'">
                            <span class="hidden md:inline font-medium"><?= htmlspecialchars($_SESSION['username']); ?></span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200" 
                               :class="{ 'rotate-180': open }" aria-hidden="true"></i>
                        </button>
                        
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 py-1 z-50"
                             role="menu"
                             aria-orientation="vertical">
                            <a href="<?= SITE_URL; ?>/pages/profile.php" 
                               class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 focus:bg-gray-50 focus-ring"
                               role="menuitem">
                                <i class="fas fa-user mr-3 text-gray-400" aria-hidden="true"></i>
                                View Profile
                            </a>
                           
                            <a href="<?= SITE_URL; ?>/pages/settings.php" 
                               class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 focus:bg-gray-50 focus-ring"
                               role="menuitem">
                                <i class="fas fa-cog mr-3 text-gray-400" aria-hidden="true"></i>
                                Settings
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="<?= SITE_URL; ?>/pages/logout.php" 
                               class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 focus:bg-red-50 focus-ring"
                               role="menuitem">
                                <i class="fas fa-sign-out-alt mr-3 text-red-500" aria-hidden="true"></i>
                                Sign Out
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= SITE_URL; ?>/pages/login.php" 
                       class="flex items-center text-gray-700 hover:text-brand-600 focus-ring rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-sign-in-alt mr-2" aria-hidden="true"></i>
                        Sign In
                    </a>
                    <a href="<?= SITE_URL; ?>/pages/register.php" 
                       class="flex items-center bg-brand-600 text-white hover:bg-brand-700 focus-ring rounded-md px-4 py-2 text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-user-plus mr-2" aria-hidden="true"></i>
                        Get Started
                    </a>
                <?php endif; ?>
            </nav>

            <!-- Mobile menu button -->
            <button id="mobile-menu-button" 
                    class="lg:hidden flex items-center justify-center w-10 h-10 text-brand-600 hover:text-brand-700 focus-ring rounded-md"
                    aria-controls="mobile-menu"
                    aria-expanded="false"
                    aria-label="Toggle navigation menu">
                <i class="fas fa-bars text-xl" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Navigation Overlay -->
<div id="mobile-menu-overlay" 
     class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 opacity-0 invisible transition-all duration-300 lg:hidden"
     aria-hidden="true">
</div>

<!-- Mobile Navigation Drawer -->
<div id="mobile-menu" 
     class="mobile-drawer fixed top-0 left-0 w-80 max-w-sm h-full bg-white shadow-2xl z-50 transform -translate-x-full lg:hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="mobile-menu-title">
     
    <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h2 id="mobile-menu-title" class="font-bold text-xl text-brand-600"><?= SITE_NAME; ?></h2>
        <button id="close-mobile-menu" 
                class="flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 focus-ring rounded-md"
                aria-label="Close navigation menu">
            <i class="fas fa-times text-lg" aria-hidden="true"></i>
        </button>
    </div>
    
    <nav class="flex flex-col p-6 space-y-1" role="navigation" aria-label="Mobile navigation">
        <?php if (is_logged_in()): ?>
            <a href="<?= SITE_URL; ?>/pages/dashboard.php" 
               class="flex items-center text-gray-700 hover:text-brand-600 hover:bg-brand-50 focus-ring rounded-md px-4 py-3 text-base font-medium transition-colors duration-200">
                <i class="fas fa-home mr-3 w-5" aria-hidden="true"></i>
                Dashboard
            </a>
            <a href="<?= SITE_URL; ?>/pages/add-child.php" 
               class="flex items-center text-gray-700 hover:text-brand-600 hover:bg-brand-50 focus-ring rounded-md px-4 py-3 text-base font-medium transition-colors duration-200">
                <i class="fas fa-baby mr-3 w-5" aria-hidden="true"></i>
                Add Child
            </a>
            <a href="<?= SITE_URL; ?>/pages/profile.php" 
               class="flex items-center text-gray-700 hover:text-brand-600 hover:bg-brand-50 focus-ring rounded-md px-4 py-3 text-base font-medium transition-colors duration-200">
                <i class="fas fa-user mr-3 w-5" aria-hidden="true"></i>
                Profile
            </a>
           
            <a href="<?= SITE_URL; ?>/pages/settings.php" 
               class="flex items-center text-gray-700 hover:text-brand-600 hover:bg-brand-50 focus-ring rounded-md px-4 py-3 text-base font-medium transition-colors duration-200">
                <i class="fas fa-cog mr-3 w-5" aria-hidden="true"></i>
                Settings
            </a>
            <div class="border-t border-gray-200 my-4"></div>
            <a href="<?= SITE_URL; ?>/pages/logout.php" 
               class="flex items-center text-red-600 hover:text-red-700 hover:bg-red-50 focus-ring rounded-md px-4 py-3 text-base font-medium transition-colors duration-200">
                <i class="fas fa-sign-out-alt mr-3 w-5" aria-hidden="true"></i>
                Sign Out
            </a>
        <?php else: ?>
            <a href="<?= SITE_URL; ?>/pages/login.php" 
               class="flex items-center text-gray-700 hover:text-brand-600 hover:bg-brand-50 focus-ring rounded-md px-4 py-3 text-base font-medium transition-colors duration-200">
                <i class="fas fa-sign-in-alt mr-3 w-5" aria-hidden="true"></i>
                Sign In
            </a>
            <a href="<?= SITE_URL; ?>/pages/register.php" 
               class="flex items-center bg-brand-600 text-white hover:bg-brand-700 focus-ring rounded-md px-4 py-3 mt-4 text-base font-medium transition-colors duration-200">
                <i class="fas fa-user-plus mr-3 w-5" aria-hidden="true"></i>
                Get Started
            </a>
        <?php endif; ?>
    </nav>
</div>

<!-- Main Content -->
<main id="main-content" class="pt-16">
    <!-- Page content goes here -->
</main>

<!-- Alpine.js for dropdown functionality -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<!-- Enhanced Mobile Menu JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
    const mobileMenu = document.getElementById('mobile-menu');
    const closeMobileMenu = document.getElementById('close-mobile-menu');
    
    let isMenuOpen = false;
    
    function openMenu() {
        isMenuOpen = true;
        document.body.style.overflow = 'hidden'; // Prevent body scroll
        mobileMenuOverlay.classList.remove('invisible', 'opacity-0');
        mobileMenuOverlay.classList.add('visible', 'opacity-100');
        mobileMenu.classList.remove('-translate-x-full');
        mobileMenuButton.setAttribute('aria-expanded', 'true');
        mobileMenuOverlay.setAttribute('aria-hidden', 'false');
        
        // Focus management
        closeMobileMenu.focus();
    }
    
    function closeMenu() {
        isMenuOpen = false;
        document.body.style.overflow = ''; // Restore body scroll
        mobileMenuOverlay.classList.add('invisible', 'opacity-0');
        mobileMenuOverlay.classList.remove('visible', 'opacity-100');
        mobileMenu.classList.add('-translate-x-full');
        mobileMenuButton.setAttribute('aria-expanded', 'false');
        mobileMenuOverlay.setAttribute('aria-hidden', 'true');
        
        // Return focus to button
        mobileMenuButton.focus();
    }
    
    // Event listeners
    mobileMenuButton.addEventListener('click', openMenu);
    closeMobileMenu.addEventListener('click', closeMenu);
    mobileMenuOverlay.addEventListener('click', closeMenu);
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isMenuOpen) {
            closeMenu();
        }
    });
    
    // Close menu when window is resized to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024 && isMenuOpen) {
            closeMenu();
        }
    });
});
</script>
</body>
</html>
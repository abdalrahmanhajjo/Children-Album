<?php
require_once 'includes/config.php';

// Get featured children for showcase (if any)
$featured_children = [];
try {
    $stmt = $pdo->query("
        SELECT c.*, u.username 
        FROM children c 
        JOIN users u ON c.user_id = u.user_id 
        ORDER BY c.created_at DESC 
        LIMIT 6
    ");
    $featured_children = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching featured children: " . $e->getMessage());
}

// Get statistics
$stats = [
    'users' => 0,
    'children' => 0,
    'photos' => 0,
    'milestones' => 0
];

try {
    $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['children'] = (int)$pdo->query("SELECT COUNT(*) FROM children")->fetchColumn();
    $stats['photos'] = (int)$pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
    $stats['milestones'] = (int)$pdo->query("SELECT COUNT(*) FROM milestones")->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
}

// Virtual children data
$virtual_pool = [
    [
        'name' => 'Ava Grace',
        'gender' => 'girl',
        'birth_date' => '2023-04-14',
        'meaning' => 'Radiant light and grace.',
        'username' => 'Community',
        'virtual' => true,
        'cover_image' => 'assets/images/virtual/ava-grace.jpg',
        'profile_image' => 'assets/images/virtual/ava-profile.jpg'
    ],
    [
        'name' => 'Liam Noah',
        'gender' => 'boy',
        'birth_date' => '2022-11-02',
        'meaning' => 'Strong-willed and peaceful.',
        'username' => 'Community',
        'virtual' => true,
        'cover_image' => 'assets/images/virtual/liam-noah.jpg',
        'profile_image' => 'assets/images/virtual/liam-profile.jpg'
    ],
    [
        'name' => 'Mia Rose',
        'gender' => 'girl',
        'birth_date' => '2021-09-07',
        'meaning' => 'Beloved and blossoming.',
        'username' => 'Community',
        'virtual' => true,
        'cover_image' => 'assets/images/virtual/mia-rose.jpg',
        'profile_image' => 'assets/images/virtual/mia-profile.jpg'
    ]
];

// Merge real with virtual to ensure we always have enough showcase items
$max_featured = 6;
$featured_showcase = $featured_children;
if (count($featured_showcase) < $max_featured) {
    $needed = $max_featured - count($featured_showcase);
    $featured_showcase = array_merge($featured_showcase, array_slice($virtual_pool, 0, $needed));
}

$page_title = "Welcome to Children Album - Preserve Precious Memories";
$meta_description = "Create beautiful digital albums for your children, track their milestones, and preserve precious memories forever with Children Album.";
require_once 'includes/header.php';
?>

<style>
/* Modern CSS variables for consistent theming */
:root {
    --primary: #ec4899; /* Pink */
    --secondary: #f59e0b; /* Amber */
    --accent: #3b82f6; /* Blue */
    --dark: #1e293b;
    --light: #f8fafc;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --gray-400: #94a3b8;
    --gray-500: #64748b;
    --gray-600: #475569;
    --gray-700: #334155;
    --gray-800: #1e293b;
    --gray-900: #0f172a;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    --rounded-sm: 0.125rem;
    --rounded: 0.25rem;
    --rounded-md: 0.375rem;
    --rounded-lg: 0.5rem;
    --rounded-xl: 0.75rem;
    --rounded-2xl: 1rem;
    --rounded-3xl: 1.5rem;
    --rounded-full: 9999px;
}

/* Base styles */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    color: var(--gray-800);
    line-height: 1.6;
    background-color: var(--light);
}

.heading-font {
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    font-weight: 700;
}

/* Animation styles */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes blob {
    0%, 100% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(15px, -10px) scale(1.05); }
    66% { transform: translate(-10px, 10px) scale(0.98); }
}

.animate-float { animation: float 6s ease-in-out infinite; }
.animate-blob { animation: blob 10s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
.animation-delay-4000 { animation-delay: 4s; }

/* Utility classes */
.section-padding {
    padding: 5rem 0;
}

.container {
    width: 100%;
    margin: 0 auto;
    padding: 0 1rem;
}

@media (min-width: 640px) {
    .container {
        max-width: 640px;
    }
}

@media (min-width: 768px) {
    .container {
        max-width: 768px;
    }
}

@media (min-width: 1024px) {
    .container {
        max-width: 1024px;
        padding: 0 2rem;
    }
}

@media (min-width: 1280px) {
    .container {
        max-width: 1280px;
    }
}

/* Hero section */
.hero-section {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(236,72,153,0.05) 0%, rgba(245,158,11,0.05) 100%);
}

.hero-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
}

.hero-bg::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 50%, rgba(236,72,153,0.1) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(245,158,11,0.1) 0%, transparent 40%);
}

.hero-bg::after {
    content: '';
    position: absolute;
    bottom: -20%;
    left: -10%;
    width: 60%;
    aspect-ratio: 1/1;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(236,72,153,0.15) 0%, transparent 70%);
    filter: blur(60px);
}

.hero-content {
    position: relative;
    z-index: 10;
    padding-top: 5rem;
    padding-bottom: 5rem;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: var(--rounded-full);
    background-color: white;
    color: var(--primary);
    font-weight: 600;
    font-size: 0.875rem;
    box-shadow: var(--shadow-sm);
    margin-bottom: 1.5rem;
}

.hero-badge i {
    color: var(--primary);
}

.hero-title {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 1.5rem;
    color: var(--gray-900);
}

.hero-subtitle {
    font-size: clamp(1.25rem, 2vw, 1.75rem);
    color: var(--gray-700);
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.hero-description {
    font-size: 1.125rem;
    color: var(--gray-600);
    margin-bottom: 2rem;
    max-width: 600px;
}

.hero-cta-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 3rem;
}

.hero-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border-radius: var(--rounded-full);
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    box-shadow: var(--shadow-md);
}

.hero-button-primary {
    background-color: white;
    color: var(--primary);
}

.hero-button-primary:hover {
    background-color: var(--secondary);
    color: var(--gray-800);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.hero-button-secondary {
    background-color: var(--primary);
    color: white;
    border: 2px solid white;
}

.hero-button-secondary:hover {
    background-color: white;
    color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.hero-testimonial {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.hero-rating {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--secondary);
}

.hero-stats {
    font-weight: 700;
    color: var(--gray-900);
}

.hero-image-container {
    position: relative;
    border-radius: var(--rounded-2xl);
    overflow: hidden;
    box-shadow: var(--shadow-2xl);
    transform: perspective(1000px) rotateY(-5deg) rotateX(2deg);
    transition: transform 0.5s ease;
}

.hero-image-container:hover {
    transform: perspective(1000px) rotateY(0) rotateX(0);
}

.hero-image {
    width: 100%;
    height: auto;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.hero-image-container:hover .hero-image {
    transform: scale(1.02);
}

.hero-image-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
    padding: 1.5rem;
}

.hero-image-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.75rem;
    border-radius: var(--rounded-full);
    background-color: rgba(255,255,255,0.9);
    color: var(--primary);
    font-weight: 600;
    font-size: 0.75rem;
    box-shadow: var(--shadow-sm);
}

/* Stats section */
.stats-section {
    background: linear-gradient(135deg, rgba(236,72,153,0.05) 0%, rgba(245,158,11,0.05) 100%);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.stat-card {
    padding: 1.5rem;
    text-align: center;
    background-color: white;
    border-radius: var(--rounded-xl);
    box-shadow: var(--shadow-sm);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    background: linear-gradient(to right, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Section headers */
.section-header {
    text-align: center;
    margin-bottom: 4rem;
}

.section-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: var(--rounded-full);
    background-color: var(--gray-100);
    color: var(--primary);
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.section-title {
    font-size: clamp(1.75rem, 3vw, 2.5rem);
    font-weight: 800;
    margin-bottom: 1rem;
    color: var(--gray-900);
}

.section-title span {
    background: linear-gradient(to right, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.section-divider {
    width: 80px;
    height: 4px;
    background: linear-gradient(to right, var(--primary), var(--secondary));
    margin: 0 auto 1.5rem;
    border-radius: var(--rounded-full);
}

.section-description {
    font-size: 1.125rem;
    color: var(--gray-600);
    max-width: 700px;
    margin: 0 auto;
}

/* Features section */
.features-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .features-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.feature-card {
    background-color: white;
    border-radius: var(--rounded-xl);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.feature-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: var(--rounded-xl);
    margin: 0 auto 1.5rem;
    font-size: 2rem;
}

.feature-icon-primary {
    background-color: rgba(236,72,153,0.1);
    color: var(--primary);
}

.feature-icon-secondary {
    background-color: rgba(245,158,11,0.1);
    color: var(--secondary);
}

.feature-icon-accent {
    background-color: rgba(59,130,246,0.1);
    color: var(--accent);
}

.feature-content {
    padding: 2rem;
    text-align: center;
}

.feature-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--gray-900);
}

.feature-description {
    color: var(--gray-600);
    margin-bottom: 1.5rem;
}

.feature-list {
    text-align: left;
    margin-top: 1.5rem;
}

.feature-list-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 0.75rem;
    color: var(--gray-600);
}

.feature-list-item i {
    margin-right: 0.5rem;
    margin-top: 0.125rem;
    color: var(--primary);
}

/* How it works section */
.steps-section {
    background: linear-gradient(135deg, rgba(236,72,153,0.03) 0%, rgba(59,130,246,0.03) 100%);
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 2rem;
    position: relative;
}

@media (min-width: 768px) {
    .steps-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.step-card {
    position: relative;
    background-color: white;
    border-radius: var(--rounded-xl);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.step-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.step-number {
    position: absolute;
    top: -1rem;
    left: -1rem;
    width: 3rem;
    height: 3rem;
    border-radius: var(--rounded-full);
    background: linear-gradient(to bottom right, var(--primary), var(--secondary));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
    z-index: 10;
}

.step-icon {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    color: var(--primary);
    text-align: center;
}

.step-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--gray-900);
    text-align: center;
}

.step-description {
    color: var(--gray-600);
    text-align: center;
}

.steps-cta {
    margin-top: 3rem;
    text-align: center;
}

/* Showcase section */
.showcase-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 2rem;
}

@media (min-width: 768px) {
    .showcase-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .showcase-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.showcase-card {
    background-color: white;
    border-radius: var(--rounded-xl);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.showcase-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.showcase-image-container {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.showcase-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.showcase-card:hover .showcase-image {
    transform: scale(1.05);
}

.showcase-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
    padding: 1.5rem;
}

.showcase-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.75rem;
    border-radius: var(--rounded-full);
    background-color: rgba(255,255,255,0.9);
    color: var(--primary);
    font-weight: 600;
    font-size: 0.75rem;
    box-shadow: var(--shadow-sm);
}

.showcase-profile {
    display: flex;
    align-items: center;
}

.showcase-avatar {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: var(--rounded-full);
    border: 3px solid white;
    object-fit: cover;
    margin-right: 1rem;
}

.showcase-profile-info {
    color: white;
}

.showcase-profile-name {
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.showcase-profile-age {
    font-size: 0.875rem;
    opacity: 0.9;
}

.showcase-content {
    padding: 1.5rem;
}

.showcase-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.showcase-gender {
    padding: 0.25rem 0.75rem;
    border-radius: var(--rounded-full);
    font-size: 0.75rem;
    font-weight: 600;
}

.showcase-gender-boy {
    background-color: rgba(59,130,246,0.1);
    color: var(--accent);
}

.showcase-gender-girl {
    background-color: rgba(236,72,153,0.1);
    color: var(--primary);
}

.showcase-birthdate {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.showcase-meaning {
    color: var(--gray-600);
    margin-bottom: 1.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.showcase-link {
    display: inline-flex;
    align-items: center;
    color: var(--primary);
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s ease;
}

.showcase-link:hover {
    color: var(--secondary);
}

.showcase-link i {
    margin-left: 0.5rem;
    transition: transform 0.3s ease;
}

.showcase-link:hover i {
    transform: translateX(3px);
}

.showcase-cta {
    margin-top: 3rem;
    text-align: center;
}

/* Testimonials section */
.testimonials-section {
    background: linear-gradient(135deg, rgba(59,130,246,0.03) 0%, rgba(236,72,153,0.03) 100%);
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 2rem;
}

@media (min-width: 768px) {
    .testimonials-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .testimonials-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.testimonial-card {
    background-color: white;
    border-radius: var(--rounded-xl);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.testimonial-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.testimonial-rating {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.testimonial-rating i {
    color: var(--secondary);
}

.testimonial-date {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin-left: 0.5rem;
}

.testimonial-text {
    font-style: italic;
    color: var(--gray-600);
    margin-bottom: 1.5rem;
}

.testimonial-author {
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.testimonial-role {
    font-size: 0.875rem;
    color: var(--gray-500);
}

/* Pricing section */
.pricing-card {
    background: linear-gradient(to bottom, white 0%, var(--gray-50) 100%);
    border-radius: var(--rounded-2xl);
    overflow: hidden;
    box-shadow: var(--shadow-xl);
    max-width: 800px;
    margin: 0 auto;
    border: 1px solid var(--gray-200);
}

.pricing-header {
    background: linear-gradient(to right, var(--primary), var(--secondary));
    color: white;
    text-align: center;
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.875rem;
}

.pricing-content {
    padding: 2rem;
}

@media (min-width: 768px) {
    .pricing-content {
        padding: 3rem;
    }
}

.pricing-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--gray-900);
}

.pricing-description {
    color: var(--gray-600);
    margin-bottom: 2rem;
}

.pricing-features {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 1.5rem;
    margin-top: 2rem;
}

@media (min-width: 768px) {
    .pricing-features {
        grid-template-columns: repeat(2, 1fr);
    }
}

.pricing-feature-item {
    display: flex;
    align-items: flex-start;
}

.pricing-feature-item i {
    color: var(--success);
    margin-right: 0.75rem;
    margin-top: 0.125rem;
}

.pricing-feature-text {
    color: var(--gray-600);
}

.pricing-cta {
    margin-top: 3rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.pricing-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border-radius: var(--rounded-full);
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
}

.pricing-button-primary {
    background: linear-gradient(to right, var(--primary), var(--secondary));
    color: white;
    box-shadow: var(--shadow-md);
}

.pricing-button-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.pricing-button-secondary {
    background-color: white;
    color: var(--primary);
    border: 1px solid var(--gray-200);
}

.pricing-button-secondary:hover {
    background-color: var(--gray-50);
}

.pricing-note {
    text-align: center;
    margin-top: 2rem;
    color: var(--gray-600);
    font-size: 0.875rem;
}

/* FAQ section */
.faq-section {
    background: linear-gradient(135deg, rgba(236,72,153,0.03) 0%, rgba(59,130,246,0.03) 100%);
}

.faq-container {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    background-color: white;
    border-radius: var(--rounded-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    margin-bottom: 1rem;
    transition: box-shadow 0.3s ease;
}

.faq-item:hover {
    box-shadow: var(--shadow-md);
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    cursor: pointer;
    font-weight: 600;
    color: var(--gray-800);
    transition: color 0.3s ease;
}

.faq-question:hover {
    color: var(--primary);
}

.faq-icon {
    color: var(--primary);
    transition: transform 0.3s ease;
}

.faq-item.active .faq-icon {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 1.5rem;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.faq-item.active .faq-answer {
    padding: 0 1.5rem 1.5rem;
    max-height: 500px;
}

.faq-cta {
    margin-top: 3rem;
    text-align: center;
}

/* CTA section */
.cta-section {
    background: linear-gradient(to right, var(--primary), var(--secondary));
    color: white;
    position: relative;
    overflow: hidden;
}

.cta-container {
    position: relative;
    z-index: 10;
}

.cta-title {
    font-size: clamp(1.75rem, 3vw, 2.5rem);
    font-weight: 800;
    margin-bottom: 1.5rem;
    line-height: 1.3;
}

.cta-description {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.cta-buttons {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
}

.cta-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border-radius: var(--rounded-full);
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    min-width: 180px;
}

.cta-button-primary {
    background-color: white;
    color: var(--primary);
    box-shadow: var(--shadow-md);
}

.cta-button-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.cta-button-secondary {
    background-color: transparent;
    color: white;
    border: 2px solid white;
}

.cta-button-secondary:hover {
    background-color: rgba(255,255,255,0.1);
}

/* Responsive adjustments */
@media (max-width: 767px) {
    .hero-content {
        text-align: center;
    }
    
    .hero-description {
        margin-left: auto;
        margin-right: auto;
    }
    
    .hero-cta-buttons {
        justify-content: center;
    }
    
    .hero-testimonial {
        justify-content: center;
    }
    
    .section-header {
        margin-bottom: 2rem;
    }
    
    .steps-grid::before {
        display: none;
    }
    
    .pricing-cta {
        justify-content: center;
    }
}

/* Animation classes for AOS */
[data-aos] {
    transition: opacity 0.5s ease, transform 0.5s ease;
}

[data-aos="fade-up"] {
    transform: translateY(20px);
    opacity: 0;
}

[data-aos="fade-up"].aos-animate {
    transform: translateY(0);
    opacity: 1;
}

[data-aos="fade-down"] {
    transform: translateY(-20px);
    opacity: 0;
}

[data-aos="fade-down"].aos-animate {
    transform: translateY(0);
    opacity: 1;
}

[data-aos="fade-left"] {
    transform: translateX(20px);
    opacity: 0;
}

[data-aos="fade-left"].aos-animate {
    transform: translateX(0);
    opacity: 1;
}

[data-aos="fade-right"] {
    transform: translateX(-20px);
    opacity: 0;
}

[data-aos="fade-right"].aos-animate {
    transform: translateX(0);
    opacity: 1;
}

[data-aos="zoom-in"] {
    transform: scale(0.9);
    opacity: 0;
}

[data-aos="zoom-in"].aos-animate {
    transform: scale(1);
    opacity: 1;
}

/* Count-up animation */
@keyframes countUp {
    from { background-position: 0% 50%; }
    to { background-position: 100% 50%; }
}

.count-up {
    background: linear-gradient(90deg, var(--primary), var(--secondary), var(--primary));
    background-size: 200% 100%;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    animation: countUp 1s ease-out forwards;
}
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-bg"></div>
    
    <div class="container">
        <div class="flex flex-col lg:flex-row items-center">
            <div class="lg:w-1/2 mb-12 lg:mb-0 lg:pr-8" data-aos="fade-right" data-aos-duration="900">
                <div class="hero-badge">
                    <i class="fas fa-heart"></i>
                    Free for everyone — forever
                </div>
                
                <h1 class="hero-title">
                    Preserve Your Child's <span class="text-yellow-300">Precious Journey</span>
                </h1>
                
                <h2 class="hero-subtitle">Every smile, every step, every milestone</h2>
                
                <p class="hero-description">
                    Create secure, beautiful digital keepsakes of your child's growth. Organize photos, track milestones,
                    and collect heartfelt wishes — all in one ad‑free place. Now with unlimited features, free for all.
                </p>
                
                 <div class="flex flex-wrap justify-center lg:justify-start gap-3 mb-10">
                    <?php if (is_logged_in()): ?>
                        <a href="pages/dashboard.php" class="flex items-center px-6 py-3 bg-white text-pink-600 rounded-full font-semibold shadow-md hover:bg-amber-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-opacity-50">
                            Go to Dashboard <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                        <a href="pages/add_child.php" class="flex items-center px-6 py-3 bg-gradient-to-r from-amber-400 to-pink-500 text-white rounded-full font-semibold shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-opacity-50">
                            Add a Child <i class="fas fa-baby ml-2"></i>
                        </a>
                    <?php else: ?>
                        <a href="pages/register.php" class="flex items-center px-6 py-3 bg-gradient-to-r from-pink-500 to-amber-500 text-white rounded-full font-semibold shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-opacity-50">
                            Get Started Free <i class="fas fa-user-plus ml-2"></i>
                        </a>
                        <a href="pages/login.php" class="flex items-center px-6 py-3 bg-white text-pink-600 rounded-full font-semibold shadow-md hover:bg-gray-50 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-opacity-50">
                            Login <i class="fas fa-sign-in-alt ml-2"></i>
                        </a>
                        <a href="pages/demo.php" class="flex items-center px-6 py-3 bg-blue-600 text-white rounded-full font-semibold shadow-md hover:bg-blue-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                            Try Demo <i class="fas fa-eye ml-2"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="hero-testimonial">
                    <div class="hero-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-700">
                            Trusted by <span class="font-bold hero-stats"><?= number_format($stats['users']) ?>+</span> parents
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="lg:w-1/2" data-aos="fade-left" data-aos-duration="900">
                <div class="relative">
                    <div class="absolute -top-10 -left-10 w-32 h-32 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
                    <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-pink-500 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
                    <div class="absolute top-1/2 left-1/2 w-32 h-32 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>
                    
                    <div class="hero-image-container">
                        <img src="R.jpeg" 
                             alt="Collage of happy children memories"
                             class="hero-image"
                             loading="lazy">
                        <div class="hero-image-overlay">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                </div>
                                <div class="ml-3">
                                    <p class="text-white font-medium">"Children Album turned our son's milestones into a story we'll cherish forever."</p>
                                    <p class="text-white/80 text-sm">- Sarah M.</p>
                                </div>
                            </div>
                        </div>
                        <div class="hero-image-badge">Ad‑Free • Secure</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section py-16">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card" data-aos="fade-up" data-aos-delay="50">
                <div class="stat-number" data-count="<?= (int)$stats['users'] ?>"><?= number_format($stats['users']) ?></div>
                <div class="stat-label">Happy Parents</div>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-number" data-count="<?= (int)$stats['children'] ?>"><?= number_format($stats['children']) ?></div>
                <div class="stat-label">Children</div>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="150">
                <div class="stat-number" data-count="<?= (int)$stats['photos'] ?>"><?= number_format($stats['photos']) ?></div>
                <div class="stat-label">Precious Photos</div>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-number" data-count="<?= (int)$stats['milestones'] ?>"><?= number_format($stats['milestones']) ?></div>
                <div class="stat-label">Milestones</div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="section-padding bg-white">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge">Why Choose Us</span>
            <h2 class="section-title">Amazing <span>Features</span></h2>
            <div class="section-divider"></div>
            <p class="section-description">Everything you need to document your child's precious moments in one beautiful platform</p>
        </div>
        
        <div class="features-grid">
            <!-- Feature 1 -->
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-content">
                    <div class="feature-icon feature-icon-primary">
                        <i class="fas fa-camera-retro"></i>
                    </div>
                    <h3 class="feature-title">Beautiful Photo Galleries</h3>
                    <p class="feature-description">Organize your child's photos in stunning, customizable galleries. Tag photos by age, event, or theme.</p>
                    <ul class="feature-list">
                        <li class="feature-list-item"><i class="fas fa-check-circle"></i> Unlimited photo storage</li>
                        <li class="feature-list-item"><i class="fas fa-check-circle"></i> Automatic date sorting</li>
                        <li class="feature-list-item"><i class="fas fa-check-circle"></i> Private or shared albums</li>
                    </ul>
                </div>
            </div>
            
            
           
            <!-- Feature 5 -->
            <div class="feature-card" data-aos="fade-up" data-aos-delay="500">
                <div class="feature-content">
                    <div class="feature-icon" style="background-color: rgba(168,85,247,0.1); color: #a855f7;">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h3 class="feature-title">Family Sharing</h3>
                    <p class="feature-description">Safely share special moments with selected family members.</p>
                    <ul class="feature-list">
                        <li class="feature-list-item"><i class="fas fa-check-circle"></i> Granular privacy controls</li>
                        <li class="feature-list-item"><i class="fas fa-check-circle"></i> Commenting system</li>
                        <li class="feature-list-item"><i class="fas fa-check-circle"></i> Automatic notifications</li>
                    </ul>
                </div>
            </div>
            
            <!-- Feature 6 -->
            <div class="feature-card" data-aos="fade-up" data-aos-delay="600">
                <div class="feature-content">
                    <div class="feature-icon" style="background-color: rgba(239,68,68,0.1); color: #ef4444;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3 class="feature-title">Bank-Grade Security</h3>
                    <p class="feature-description">Your child's memories are safe with our enterprise-level security.</p>
                    <ul class="feature-list">
                        <li class="feature-list-item"><i class="fas fa-check-circle"></i> End-to-end encryption</li>
                        <li class="feature-list-item"><i class="fas fa-check-circle"></i> Automated backups</li>
                        <li class="feature-list-item"><i class="fas fa-check-circle"></i> Two-factor authentication</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="steps-section section-padding">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge" style="background-color: rgba(59,130,246,0.1); color: var(--accent);">Simple Steps</span>
            <h2 class="section-title">How It <span>Works</span></h2>
            <div class="section-divider" style="background: linear-gradient(to right, var(--secondary), var(--primary));"></div>
            <p class="section-description">Get started in minutes and begin preserving memories that last a lifetime</p>
        </div>
        
        <div class="steps-grid">
            <!-- Step 1 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="100">
                <div class="step-number">1</div>
                <div class="step-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3 class="step-title">Create Your Account</h3>
                <p class="step-description">Sign up for free in just a few seconds. No credit card required to get started.</p>
            </div>
            
            <!-- Step 2 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                <div class="step-number">2</div>
                <div class="step-icon">
                    <i class="fas fa-baby"></i>
                </div>
                <h3 class="step-title">Add Your Child</h3>
                <p class="step-description">Create a profile for your child with their name, birth details, and special meaning.</p>
            </div>
            
            <!-- Step 3 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                <div class="step-number">3</div>
                <div class="step-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h3 class="step-title">Upload Memories</h3>
                <p class="step-description">Add photos, record milestones, and collect wishes from loved ones.</p>
            </div>
        </div>
        
        <div class="steps-cta" data-aos="fade-up" data-aos-delay="400">
            <a href="pages/register.php" class="cta-button cta-button-primary">
                Get Started Now
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Featured Children Section -->
<section class="section-padding bg-white">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge">Get Inspired</span>
            <h2 class="section-title">Create Your Child's <span>Memory Album</span></h2>
            <div class="section-divider"></div>
            <p class="section-description">See examples of how you can preserve your baby's precious moments. Start creating your own album today!</p>
        </div>
        
        <div class="showcase-grid">
            <?php 
            // Virtual baby examples with authentic baby images
            $virtual_babies = [
                [
                    'name' => 'Emma Rose',
                    'gender' => 'girl',
                    'birth_date' => '2023-05-15',
                    'meaning' => 'Our little sunshine who brightens every day with her gummy smiles.',
                    'cover_image' => '1.jpg',
                    'profile_image' => '2.jpeg'
                ],
                [
                    'name' => 'Liam James',
                    'gender' => 'boy', 
                    'birth_date' => '2022-10-22',
                    'meaning' => 'Our curious explorer who finds wonder in everything around him.',
                    'cover_image' => '3.jpg',
                    'profile_image' => '4.jpg'
                ],
                [
                    'name' => 'Ava Grace',
                    'gender' => 'girl',
                    'birth_date' => '2024-01-10',
                    'meaning' => 'Our peaceful angel with the most contagious baby giggles.',
                    'cover_image' => '5.jpeg',
                    'profile_image' => '6.jpg'
                ]
            ];
            
            foreach ($virtual_babies as $index => $baby): 
                $babyName = htmlspecialchars($baby['name']);
                $babyGender = htmlspecialchars($baby['gender']);
                $born = date('F j, Y', strtotime($baby['birth_date']));
                $meaning = htmlspecialchars($baby['meaning']);
            ?>
            <div class="showcase-card" data-aos="fade-up" data-aos-delay="<?= ($index+1)*100 ?>">
                <div class="showcase-image-container">
                    <img src="<?= $baby['cover_image'] ?>" 
                         alt="Cute baby <?= $babyGender === 'boy' ? 'boy' : 'girl' ?> example"
                         class="showcase-image"
                         loading="lazy">
                    <div class="showcase-overlay">
                        <div class="showcase-profile">
                            <img src="<?= $baby['profile_image'] ?>" 
                                 alt="Close-up of baby <?= $babyName ?>"
                                 class="showcase-avatar">
                            <div class="showcase-profile-info">
                                <div class="showcase-profile-name"><?= $babyName ?></div>
                                <div class="showcase-profile-age">Age: <?= date_diff(date_create($baby['birth_date']), date_create('today'))->format('%m mos') ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="showcase-badge">Example</div>
                </div>
                <div class="showcase-content">
                    <div class="showcase-meta">
                        <span class="showcase-gender <?= $babyGender === 'boy' ? 'showcase-gender-boy' : 'showcase-gender-girl' ?>">
                            <?= ($babyGender === 'boy' ? '👦 Baby Boy' : '👧 Baby Girl') ?>
                        </span>
                        <span class="showcase-birthdate">
                            Born <?= $born ?>
                        </span>
                    </div>
                    <p class="showcase-meaning">"<?= $meaning ?>"</p>
                    <div>
                        <a href="pages/register.php" class="showcase-link <?= $babyGender === 'boy' ? 'text-blue-600' : 'text-pink-600' ?>">
                            Start your baby's album <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="showcase-cta" data-aos="fade-up">
            <a href="<?= is_logged_in() ? 'pages/add-child.php' : 'pages/register.php' ?>" class="cta-button cta-button-primary">
                <?php if (is_logged_in()): ?>
                    <i class="fas fa-baby mr-2"></i> Add Your Baby
                <?php else: ?>
                    <i class="fas fa-heart mr-2"></i> Start Preserving Memories
                <?php endif; ?>
            </a>
            <p class="mt-4 text-gray-500 text-sm">Cherish every giggle, milestone, and sleepy smile</p>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section section-padding">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge" style="background-color: rgba(59,130,246,0.1); color: var(--accent);">Testimonials</span>
            <h2 class="section-title">What Parents <span>Say</span></h2>
            <div class="section-divider" style="background: linear-gradient(to right, var(--secondary), var(--primary));"></div>
            <p class="section-description">Hear from parents who are using Children Album to preserve their children's memories</p>
        </div>
        
        <div class="testimonials-grid">
            <!-- Testimonial 1 -->
            <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
                <div class="testimonial-rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <span class="testimonial-date">2 weeks ago</span>
                </div>
                <p class="testimonial-text">"As a first-time mom, I was overwhelmed with photos of my daughter. Children Album helped me organize everything beautifully and even reminded me of milestones I might have forgotten to record."</p>
                <div>
                    <div class="testimonial-author">Sarah J.</div>
                    <div class="testimonial-role">Mother of 1-year-old Emma</div>
                </div>
            </div>
            
            <!-- Testimonial 2 -->
            <div class="testimonial-card" data-aos="fade-up" data-aos-delay="200">
                <div class="testimonial-rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <span class="testimonial-date">1 month ago</span>
                </div>
                <p class="testimonial-text">"Living far from our families, Children Album has been amazing for sharing our son's growth. The wish collection feature lets grandparents send messages he'll treasure when he's older."</p>
                <div>
                    <div class="testimonial-author">Michael T.</div>
                    <div class="testimonial-role">Father of 3-year-old Noah</div>
                </div>
            </div>
            
            <!-- Testimonial 3 -->
            <div class="testimonial-card" data-aos="fade-up" data-aos-delay="300">
                <div class="testimonial-rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                    <span class="testimonial-date">3 months ago</span>
                </div>
                <p class="testimonial-text">"I created memory books for both my children's first five years using Children Album. The automatic timeline feature saved me hours of work, and the results look professionally designed."</p>
                <div>
                    <div class="testimonial-author">Priya K.</div>
                    <div class="testimonial-role">Mother of twins Aarya and Aadi</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section - Free for All -->
<section class="section-padding bg-white">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge" style="background-color: rgba(245,158,11,0.1); color: var(--secondary);">Pricing</span>
            <h2 class="section-title">Simple, Affordable Pricing — Now <span>Free for All</span></h2>
            <div class="section-divider" style="background: linear-gradient(to right, var(--accent), var(--primary));"></div>
            <p class="section-description">All premium features are unlocked for everyone. No credit card required.</p>
        </div>
        
        <div class="pricing-card" data-aos="zoom-in">
            <div class="pricing-header">Free Forever</div>
            <div class="pricing-content">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="mb-6 md:mb-0">
                        <h3 class="pricing-title">Children Album — Free for Everyone</h3>
                        <p class="pricing-description">Enjoy everything without limits.</p>
                    </div>
                    <div class="text-center">
                        <div class="flex items-baseline justify-center md:justify-end gap-2">
                            <span class="text-5xl font-extrabold text-gray-900">$0</span>
                            <span class="text-gray-600">/forever</span>
                        </div>
                        <div class="text-xs text-gray-500 line-through">was $7–$12 per month</div>
                    </div>
                </div>
                
                <div class="pricing-features">
                    <ul class="space-y-3">
                        <li class="pricing-feature-item">
                            <i class="fas fa-check"></i>
                            <span class="pricing-feature-text">Unlimited child profiles</span>
                        </li>
                        <li class="pricing-feature-item">
                            <i class="fas fa-check"></i>
                            <span class="pricing-feature-text">Unlimited photos and videos</span>
                        </li>
                        <li class="pricing-feature-item">
                            <i class="fas fa-check"></i>
                            <span class="pricing-feature-text">Advanced milestones & growth charts</span>
                        </li>
                        <li class="pricing-feature-item">
                            <i class="fas fa-check"></i>
                            <span class="pricing-feature-text">Memory books (digital & printable)</span>
                        </li>
                    </ul>
                    <ul class="space-y-3">
                        <li class="pricing-feature-item">
                            <i class="fas fa-check"></i>
                            <span class="pricing-feature-text">Family sharing with granular privacy</span>
                        </li>
                        <li class="pricing-feature-item">
                            <i class="fas fa-check"></i>
                            <span class="pricing-feature-text">End‑to‑end encryption & 2FA</span>
                        </li>
                        <li class="pricing-feature-item">
                            <i class="fas fa-check"></i>
                            <span class="pricing-feature-text">Automated backups & easy exports</span>
                        </li>
                        <li class="pricing-feature-item">
                            <i class="fas fa-check"></i>
                            <span class="pricing-feature-text">Completely ad‑free experience</span>
                        </li>
                    </ul>
                </div>
                
                <div class="pricing-cta">
                    <a href="pages/register.php" class="pricing-button pricing-button-primary">
                        Create free account
                    </a>
                    <a href="pages/demo.php" class="pricing-button pricing-button-secondary">
                        Explore a live demo
                    </a>
                </div>
            </div>
        </div>
        
        <div class="pricing-note" data-aos="fade-up">
            <p>No hidden fees. Cancel anytime. Your memories are always yours to download.</p>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section section-padding">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge" style="background-color: rgba(59,130,246,0.1); color: var(--accent);">FAQs</span>
            <h2 class="section-title">Frequently Asked <span>Questions</span></h2>
            <div class="section-divider" style="background: linear-gradient(to right, var(--secondary), var(--primary));"></div>
            <p class="section-description">Find answers to common questions about Children Album</p>
        </div>
        
        <div class="faq-container" data-aos="fade-up">
            <div class="space-y-4">
                <!-- FAQ Item 1 -->
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Is my child's data secure and private?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        <p class="text-gray-600">Absolutely. We take privacy and security very seriously. All data is encrypted both in transit and at rest. We never share your information with third parties, and you have complete control over who can see your child's information.</p>
                    </div>
                </div>
                
                <!-- FAQ Item 2 -->
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Can I download my photos and data?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        <p class="text-gray-600">Yes, you can download all your photos and data at any time. We provide easy export options for your entire collection, including photos, milestones, and wishes, in standard formats.</p>
                    </div>
                </div>
                
                <!-- FAQ Item 3 -->
                <div class="faq-item">
                    <button class="faq-question">
                        <span>What happens if I delete my account?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        <p class="text-gray-600">You can request a complete export before deleting your account. Once deleted, we securely purge your data from our systems after a short grace period, except where retention is legally required.</p>
                    </div>
                </div>
                
                <!-- FAQ Item 4 -->
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Can I use Children Album for multiple children?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        <p class="text-gray-600">Yes! You can create unlimited child profiles. Each child gets their own dedicated space with separate albums, milestones, and wish collections.</p>
                    </div>
                </div>
                
                <!-- FAQ Item 5 -->
                <div class="faq-item">
                    <button class="faq-question">
                        <span>How do the memory books work?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        <p class="text-gray-600">Our memory books automatically organize your photos and milestones into beautiful, professionally designed layouts. You can customize them before ordering printed copies or download digital versions.</p>
                    </div>
                </div>
            </div>
            
            <div class="faq-cta" data-aos="fade-up">
                <p class="text-gray-600 mb-6">Still have questions? We're happy to help!</p>
                <a href="pages/contact.php" class="cta-button cta-button-primary">
                    Contact Us
                    <i class="fas fa-envelope ml-2"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section section-padding">
    <div class="container">
        <div class="max-w-4xl mx-auto text-center" data-aos="zoom-in">
            <h2 class="cta-title">Ready to Start Preserving Your Child's Memories?</h2>
            <p class="cta-description">Join thousands of parents documenting their children's precious moments today — free and unlimited.</p>
            <div class="cta-buttons">
                <a href="pages/register.php" class="cta-button cta-button-primary">
                    Get Started Free
                </a>
                <a href="pages/demo.php" class="cta-button cta-button-secondary">
                    Watch Demo
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// FAQ toggle functionality
document.querySelectorAll('.faq-question').forEach(button => {
    button.addEventListener('click', () => {
        const faqItem = button.closest('.faq-item');
        const answer = faqItem.querySelector('.faq-answer');
        const icon = faqItem.querySelector('.faq-icon');
        
        // Close all other FAQs
        document.querySelectorAll('.faq-item').forEach(item => {
            if (item !== faqItem) {
                item.classList.remove('active');
                item.querySelector('.faq-answer').style.maxHeight = '0';
                item.querySelector('.faq-answer').style.padding = '0 1.5rem';
            }
        });
        
        // Toggle current FAQ
        faqItem.classList.toggle('active');
        
        if (faqItem.classList.contains('active')) {
            answer.style.maxHeight = answer.scrollHeight + 'px';
            answer.style.padding = '0 1.5rem 1.5rem';
        } else {
            answer.style.maxHeight = '0';
            answer.style.padding = '0 1.5rem';
        }
    });
});

// Count-up animation for stats when in view
(function() {
    const els = document.querySelectorAll('[data-count]');
    if (!('IntersectionObserver' in window) || els.length === 0) return;

    const animateCountUp = (el) => {
        const target = parseInt(el.getAttribute('data-count'), 10) || 0;
        const duration = 1200;
        const start = performance.now();
        
        // Store original content
        const originalContent = el.textContent;
        
        const step = (now) => {
            const p = Math.min((now - start) / duration, 1);
            const value = Math.round(p * target);
            el.textContent = value.toLocaleString();
            
            if (p < 1) {
                requestAnimationFrame(step);
            } else {
                // Restore original content after animation
                setTimeout(() => {
                    el.textContent = originalContent;
                    el.classList.add('count-up');
                }, 500);
            }
        };
        
        requestAnimationFrame(step);
    };

    const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCountUp(entry.target);
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    els.forEach(el => io.observe(el));
})();

// Initialize AOS (Animate On Scroll) for simple animations
document.addEventListener('DOMContentLoaded', function() {
    const aosElements = document.querySelectorAll('[data-aos]');
    
    const handleScroll = () => {
        aosElements.forEach(el => {
            const rect = el.getBoundingClientRect();
            const isVisible = (rect.top <= window.innerHeight * 0.75) && 
                             (rect.bottom >= window.innerHeight * 0.25);
            
            if (isVisible) {
                el.classList.add('aos-animate');
            }
        });
    };
    
    // Initial check
    handleScroll();
    
    // Throttle scroll events
    let isScrolling;
    window.addEventListener('scroll', () => {
        window.clearTimeout(isScrolling);
        isScrolling = setTimeout(handleScroll, 50);
    }, { passive: true });
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>
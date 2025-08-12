<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';

// Get the token from URL parameter
$token = $_GET['token'] ?? '';

// Validate token exists and is not empty
if (empty($token)) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Check database for valid token
$stmt = $pdo->prepare("
    SELECT c.*, sl.expiry_date, u.full_name as parent_name, sl.child_id
    FROM share_links sl
    JOIN children c ON sl.child_id = c.child_id
    JOIN users u ON c.user_id = u.user_id
    WHERE sl.token = ? AND sl.expiry_date > NOW()
");
$stmt->execute([$token]);
$shared_child = $stmt->fetch();

if (!$shared_child) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Handle wish submission if user is logged in
if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_wish'])) {
    $message = sanitize_input($_POST['message']);
    $relationship = sanitize_input($_POST['relationship']);
    
    if (!empty($message) && !empty($relationship)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO wishes (child_id, sender_name, message, relationship, user_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $shared_child['child_id'],
                $_SESSION['username'],
                $message,
                $relationship,
                $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = "Your wish has been added successfully!";
            header("Location: ?token=$token");
            exit;
        } catch (PDOException $e) {
            $error = "Failed to add your wish. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Track view count (optional)
$pdo->prepare("UPDATE share_links SET view_count = view_count + 1 WHERE token = ?")->execute([$token]);

$page_title = 'Shared Album - ' . htmlspecialchars($shared_child['name']);

// Get child data
$child_id = $shared_child['child_id'];
$age = calculate_age($shared_child['birth_date']);

// Get photos
$photos_stmt = $pdo->prepare("SELECT * FROM gallery WHERE child_id = ? ORDER BY created_at DESC");
$photos_stmt->execute([$child_id]);
$photos = $photos_stmt->fetchAll();

// Get milestones
$milestones_stmt = $pdo->prepare("SELECT * FROM milestones WHERE child_id = ? ORDER BY date_achieved DESC");
$milestones_stmt->execute([$child_id]);
$milestones = $milestones_stmt->fetchAll();

// Get wishes
$wishes_stmt = $pdo->prepare("SELECT * FROM wishes WHERE child_id = ? ORDER BY created_at DESC");
$wishes_stmt->execute([$child_id]);
$wishes = $wishes_stmt->fetchAll();

// Theme setup
$themes = [
    'girl' => [
        'primary' => '#E91E63',  // Pink 500
        'secondary' => '#F8BBD0', // Pink 100
        'accent' => '#C2185B'    // Pink 700
    ],
    'boy' => [
        'primary' => '#2196F3',   // Blue 500
        'secondary' => '#BBDEFB', // Blue 100
        'accent' => '#1976D2'     // Blue 700
    ]
];

$gender = $shared_child['gender'] ?? 'girl';
$theme_colors = $themes[$gender];

require_once 'includes/header.php';
?>

<style>
    /* Dynamic theme colors */
    .theme-primary { color: <?php echo $theme_colors['primary']; ?>; }
    .theme-primary-bg { background-color: <?php echo $theme_colors['primary']; ?>; }
    .theme-primary-border { border-color: <?php echo $theme_colors['primary']; ?>; }
    .theme-primary-hover:hover { background-color: <?php echo $theme_colors['accent']; ?>; }
    
    .theme-secondary { color: <?php echo $theme_colors['secondary']; ?>; }
    .theme-secondary-bg { background-color: <?php echo $theme_colors['secondary']; ?>; }
    
    .hero-section {
        background: linear-gradient(135deg, <?php echo $theme_colors['secondary']; ?> 0%, <?php echo $theme_colors['primary']; ?> 100%);
    }
    
    .btn-primary {
        background-color: <?php echo $theme_colors['primary']; ?>;
        color: white;
    }
    .btn-primary:hover {
        background-color: <?php echo $theme_colors['accent']; ?>;
    }
    
    .floating {
        animation: floating 3s ease-in-out infinite;
    }
    
    @keyframes floating {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-15px); }
        100% { transform: translateY(0px); }
    }
</style>

<!-- Hero Section -->
<section class="hero-section min-h-[400px] flex items-center pt-16 relative">
    <div class="absolute inset-0 bg-black/30"></div>
    <div class="container relative z-10">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white heading-font mb-4"><?php echo htmlspecialchars($shared_child['name']); ?>'s Shared Album</h1>
            <p class="text-xl text-white mb-5">Shared by <?php echo htmlspecialchars($shared_child['parent_name']); ?></p>
            <p class="text-white text-lg mb-6"><?php echo htmlspecialchars($age); ?> old • Born <?php echo date('F j, Y', strtotime($shared_child['birth_date'])); ?></p>
            <p class="text-sm text-white/80 mt-2">
                Link expires: <?php echo date('F j, Y', strtotime($shared_child['expiry_date'])); ?>
            </p>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-16 bg-white">
    <div class="container">
        <div class="flex flex-col md:flex-row items-center gap-8">
            <div class="md:w-1/3">
                <div class="relative">
                    <?php if ($shared_child['profile_picture']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($shared_child['profile_picture']); ?>" 
                             alt="<?php echo htmlspecialchars($shared_child['name']); ?>" 
                             class="w-64 h-64 rounded-full object-cover border-4 border-white shadow-xl floating mx-auto">
                    <?php else: ?>
                        <div class="w-64 h-64 rounded-full bg-white flex items-center justify-center shadow-xl floating mx-auto">
                            <i class="fas fa-child text-6xl theme-primary"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="md:w-2/3 text-center md:text-left">
                <h2 class="text-3xl font-bold text-gray-800 heading-font mb-4">About <span class="theme-primary"><?php echo htmlspecialchars($shared_child['nickname'] ?: $shared_child['name']); ?></span></h2>
                
                <?php if ($shared_child['meaning']): ?>
                    <p class="text-gray-600 mb-6 text-lg"><?php echo nl2br(htmlspecialchars($shared_child['meaning'])); ?></p>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="theme-primary text-2xl mb-2">
                            <i class="fas fa-birthday-cake"></i>
                        </div>
                        <h4 class="font-bold text-gray-800">Birth Date</h4>
                        <p class="text-sm text-gray-600"><?php echo date('F j, Y', strtotime($shared_child['birth_date'])); ?></p>
                    </div>
                    
                    <?php if ($shared_child['birth_weight']): ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="theme-primary text-2xl mb-2">
                                <i class="fas fa-weight"></i>
                            </div>
                            <h4 class="font-bold text-gray-800">Birth Weight</h4>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($shared_child['birth_weight']); ?> kg</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($shared_child['birth_length']): ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="theme-primary text-2xl mb-2">
                                <i class="fas fa-ruler-vertical"></i>
                            </div>
                            <h4 class="font-bold text-gray-800">Birth Length</h4>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($shared_child['birth_length']); ?> cm</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Photo Gallery Section -->
<section id="gallery" class="py-16 bg-gray-50">
    <div class="container">
        <h2 class="text-3xl font-bold theme-primary heading-font text-center mb-12">Photo Gallery</h2>
        
        <?php if (empty($photos)): ?>
            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                <div class="text-5xl theme-primary mb-4">
                    <i class="fas fa-images"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">No Photos Shared</h3>
                <p class="text-gray-600">This album doesn't contain any shared photos yet.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($photos as $photo): ?>
                    <div class="overflow-hidden rounded-xl shadow-md hover:shadow-xl transition-all bg-white">
                        <a href="uploads/<?php echo htmlspecialchars($photo['image_path']); ?>" data-lightbox="gallery" data-title="<?php echo htmlspecialchars($photo['title'] ?? ''); ?>">
                            <img src="uploads/<?php echo htmlspecialchars($photo['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($photo['title'] ?: $shared_child['name']); ?>" 
                                 class="w-full h-64 object-cover transition-transform duration-300 hover:scale-105">
                        </a>
                        
                        <div class="p-4">
                            <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($photo['title'] ?: 'Untitled'); ?></h4>
                            <?php if ($photo['date_taken']): ?>
                                <p class="text-sm text-gray-600"><?php echo date('F j, Y', strtotime($photo['date_taken'])); ?></p>
                            <?php endif; ?>
                            <?php if ($photo['description']): ?>
                                <p class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($photo['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Milestones Section -->
<section id="milestones" class="py-16 bg-white">
    <div class="container">
        <h2 class="text-3xl font-bold theme-primary heading-font text-center mb-12">Milestones</h2>
        
        <?php if (empty($milestones)): ?>
            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                <div class="text-5xl theme-primary mb-4">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">No Milestones Shared</h3>
                <p class="text-gray-600">This album doesn't contain any shared milestones yet.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6 max-w-3xl mx-auto">
                <?php foreach ($milestones as $milestone): ?>
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 theme-secondary-bg theme-primary rounded-full w-12 h-12 flex items-center justify-center">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="ml-4">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($milestone['title']); ?></h3>
                                    <span class="text-sm text-gray-500"><?php echo date('F j, Y', strtotime($milestone['date_achieved'])); ?></span>
                                </div>
                                <?php if ($milestone['description']): ?>
                                    <p class="text-gray-600 mt-2"><?php echo nl2br(htmlspecialchars($milestone['description'])); ?></p>
                                <?php endif; ?>
                                <?php if ($milestone['image_path']): ?>
                                    <div class="mt-4">
                                        <a href="uploads/<?php echo htmlspecialchars($milestone['image_path']); ?>" data-lightbox="milestone-<?php echo $milestone['milestone_id']; ?>">
                                            <img src="uploads/<?php echo htmlspecialchars($milestone['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($milestone['title']); ?>" 
                                                 class="w-full max-w-md rounded-lg shadow-sm">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Wishes Section -->
<section id="wishes" class="py-16 bg-gray-50">
    <div class="container">
        <h2 class="text-3xl font-bold theme-primary heading-font text-center mb-12">Wishes & Messages</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 max-w-3xl mx-auto">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 max-w-3xl mx-auto">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (is_logged_in()): ?>
            <div class="bg-white p-6 rounded-lg shadow-sm mb-8 max-w-3xl mx-auto">
                <h3 class="text-xl font-bold mb-4 theme-primary">Add Your Wish</h3>
                <form method="POST" action="">
                    <input type="hidden" name="add_wish" value="1">
                    <div class="mb-4">
                        <label for="relationship" class="block text-gray-700 font-medium mb-2">Your Relationship</label>
                        <select id="relationship" name="relationship" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                            <option value="">Select relationship</option>
                            <option value="Family">Family</option>
                            <option value="Friend">Friend</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Neighbor">Neighbor</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="message" class="block text-gray-700 font-medium mb-2">Your Message</label>
                        <textarea id="message" name="message" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required></textarea>
                    </div>
                    <button type="submit" class="px-6 py-2 btn-primary rounded-lg font-semibold">
                        Submit Wish <i class="fas fa-paper-plane ml-2"></i>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-white p-6 rounded-lg shadow-sm mb-8 max-w-3xl mx-auto text-center">
                <p class="text-gray-600 mb-4">Want to leave a wish? Please login first.</p>
                <a href="https://children-album.great-site.net/children-album/pages/login.php" class="inline-block px-6 py-2 btn-primary rounded-lg font-semibold">
                    Login to Add Wish <i class="fas fa-sign-in-alt ml-2"></i>
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (empty($wishes)): ?>
            <div class="bg-white rounded-xl shadow-md p-8 text-center max-w-3xl mx-auto">
                <div class="text-5xl theme-primary mb-4">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">No Wishes Yet</h3>
                <p class="text-gray-600">Be the first to send your love and blessings!</p>
            </div>
        <?php else: ?>
            <div class="space-y-4 max-w-3xl mx-auto">
                <?php foreach ($wishes as $wish): ?>
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 theme-secondary-bg theme-primary rounded-full w-10 h-10 flex items-center justify-center">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($wish['sender_name']); ?></h4>
                                <p class="text-gray-600 text-sm mb-2">
                                    <?php echo htmlspecialchars($wish['relationship']); ?> • 
                                    <?php echo date('F j, Y', strtotime($wish['created_at'])); ?>
                                </p>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($wish['message'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Download Section -->
<section class="py-16 bg-white">
    <div class="container text-center">
        <h2 class="text-3xl font-bold theme-primary heading-font mb-6">Want to create your own album?</h2>
        <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
            Preserve your child's precious moments with our beautiful album platform. Create, share, and cherish memories forever.
        </p>
        <a href="https://children-album.great-site.net/children-album/pages/register.php" class="inline-block px-8 py-4 btn-primary rounded-full text-lg font-semibold transition-all">
            Get Started <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>
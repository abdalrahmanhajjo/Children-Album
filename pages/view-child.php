<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$child_id = $_GET['id'];
$child = get_child_details($child_id);

// Verify child belongs to current user
if (!$child || $child['user_id'] != get_current_user_id()) {
    header('Location: dashboard.php');
    exit;
}

// Handle theme update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_theme'])) {
    $theme_primary = sanitize_input($_POST['primary_color']);
    $theme_secondary = sanitize_input($_POST['secondary_color']);
    $theme_accent = sanitize_input($_POST['accent_color']);
    
    try {
        $stmt = $pdo->prepare("UPDATE children SET theme_primary = ?, theme_secondary = ?, theme_accent = ? WHERE child_id = ?");
        $stmt->execute([$theme_primary, $theme_secondary, $theme_accent, $child_id]);
        $_SESSION['success'] = "Theme updated successfully!";
        header("Location: view-child.php?id=$child_id");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to update theme: " . $e->getMessage();
    }
}

// Handle all other form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle wish submission
    if (isset($_POST['add_wish'])) {
        $sender_name = sanitize_input($_POST['sender_name']);
        $message = sanitize_input($_POST['message']);
        $relationship = sanitize_input($_POST['relationship']);
        
        if (!empty($sender_name) && !empty($message)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO wishes (child_id, sender_name, message, relationship) VALUES (?, ?, ?, ?)");
                $stmt->execute([$child_id, $sender_name, $message, $relationship]);
                $_SESSION['success'] = "Your wish has been added!";
                header("Location: view-child.php?id=$child_id#wishes");
                exit;
            } catch (PDOException $e) {
                $error = "Failed to add wish: " . $e->getMessage();
            }
        } else {
            $error = "Please fill in all required fields";
        }
    }
    
    // Handle photo operations
    if (isset($_POST['photo_action'])) {
        $photo_id = (int)$_POST['photo_id'];
        
        if ($_POST['photo_action'] === 'delete') {
            if (delete_photo($photo_id)) {
                $_SESSION['success'] = "Photo deleted successfully";
                header("Location: view-child.php?id=$child_id#gallery");
                exit;
            } else {
                $error = "Failed to delete photo";
            }
        } 
        elseif ($_POST['photo_action'] === 'update') {
            $title = sanitize_input($_POST['title']);
            $description = sanitize_input($_POST['description']);
            $date_taken = sanitize_input($_POST['date_taken']);
            
            try {
                $stmt = $pdo->prepare("UPDATE gallery SET title = ?, description = ?, date_taken = ? WHERE gallery_id = ? AND child_id = ?");
                $stmt->execute([$title, $description, $date_taken, $photo_id, $child_id]);
                $_SESSION['success'] = "Photo updated successfully";
                header("Location: view-child.php?id=$child_id#gallery");
                exit;
            } catch (PDOException $e) {
                $error = "Failed to update photo: " . $e->getMessage();
            }
        }
    }
    
    // Handle milestone operations
    if (isset($_POST['milestone_action'])) {
        $milestone_id = (int)$_POST['milestone_id'];
        
        if ($_POST['milestone_action'] === 'delete') {
            try {
                $stmt = $pdo->prepare("DELETE FROM milestones WHERE milestone_id = ? AND child_id = ?");
                if ($stmt->execute([$milestone_id, $child_id])) {
                    $_SESSION['success'] = "Milestone deleted successfully";
                    header("Location: view-child.php?id=$child_id#milestones");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Failed to delete milestone";
            }
        } 
        elseif ($_POST['milestone_action'] === 'update') {
            $title = sanitize_input($_POST['title']);
            $description = sanitize_input($_POST['description']);
            $date_achieved = sanitize_input($_POST['date_achieved']);
            
            try {
                $stmt = $pdo->prepare("UPDATE milestones SET title = ?, description = ?, date_achieved = ? WHERE milestone_id = ? AND child_id = ?");
                $stmt->execute([$title, $description, $date_achieved, $milestone_id, $child_id]);
                $_SESSION['success'] = "Milestone updated successfully";
                header("Location: view-child.php?id=$child_id#milestones");
                exit;
            } catch (PDOException $e) {
                $error = "Failed to update milestone: " . $e->getMessage();
            }
        }
    }
}

// Get all data
$gallery = get_child_gallery($child_id);
$milestones = get_child_milestones($child_id);
$wishes = get_child_wishes($child_id);

// Define default themes
$themes = [
    'girl' => [
        'primary' => '#E91E63',
        'secondary' => '#F8BBD0',
        'accent' => '#C2185B'
    ],
    'boy' => [
        'primary' => '#2196F3',
        'secondary' => '#BBDEFB',
        'accent' => '#1976D2'
    ]
];

// Use custom theme if set, otherwise default based on gender
$theme_colors = [
    'primary' => $child['theme_primary'] ?? $themes[$child['gender'] ?? 'girl']['primary'],
    'secondary' => $child['theme_secondary'] ?? $themes[$child['gender'] ?? 'girl']['secondary'],
    'accent' => $child['theme_accent'] ?? $themes[$child['gender'] ?? 'girl']['accent']
];

$page_title = $child['name'] . "'s Album";
require_once '../includes/header.php';

// Display success/error messages
if (isset($_SESSION['success'])) {
    echo '<div class="container py-4"><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">' . 
         $_SESSION['success'] . '</div></div>';
    unset($_SESSION['success']);
}

if (isset($error)) {
    echo '<div class="container py-4"><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">' . 
         $error . '</div></div>';
}
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
    
    .btn-outline {
        border-color: <?php echo $theme_colors['primary']; ?>;
        color: <?php echo $theme_colors['primary']; ?>;
    }
    .btn-outline:hover {
        background-color: <?php echo $theme_colors['primary']; ?>;
        color: white;
    }
    
    .floating {
        animation: floating 3s ease-in-out infinite;
    }
    
    @keyframes floating {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-15px); }
        100% { transform: translateY(0px); }
    }
    
    /* Action buttons */
    .action-btn {
        transition: all 0.3s ease;
        opacity: 0;
    }
    .gallery-item:hover .action-btn, 
    .milestone-item:hover .action-btn {
        opacity: 1;
    }
    
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 1000;
        overflow-y: auto;
    }
    
    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .modal-close {
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    
    /* Theme editor styles */
    .theme-editor {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 100;
    }
    
    .theme-toggle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: <?php echo $theme_colors['primary']; ?>;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    
    .theme-panel {
        display: none;
        position: absolute;
        bottom: 60px;
        right: 0;
        width: 250px;
        background: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    
    .theme-panel h4 {
        margin-top: 0;
        margin-bottom: 15px;
    }
    
    .color-picker {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .color-picker label {
        width: 80px;
        display: inline-block;
    }
    
    .color-picker input {
        width: 100px;
    }
</style>

<!-- Theme Editor Floating Button -->
<div class="theme-editor">
    <div class="theme-toggle" id="themeToggle">
        <i class="fas fa-palette"></i>
    </div>
    <div class="theme-panel" id="themePanel">
        <h4>Customize Theme</h4>
        <form method="POST" action="">
            <input type="hidden" name="update_theme" value="1">
            
            <div class="color-picker">
                <label>Primary:</label>
                <input type="color" name="primary_color" value="<?php echo $theme_colors['primary']; ?>">
            </div>
            
            <div class="color-picker">
                <label>Secondary:</label>
                <input type="color" name="secondary_color" value="<?php echo $theme_colors['secondary']; ?>">
            </div>
            
            <div class="color-picker">
                <label>Accent:</label>
                <input type="color" name="accent_color" value="<?php echo $theme_colors['accent']; ?>">
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeThemePanel()" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hero Section -->
<section class="hero-section min-h-[400px] flex items-center pt-16 relative">
    <div class="absolute inset-0 bg-black/30"></div>
    <div class="container relative z-10">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h1 class="text-4xl md:text-5xl font-bold text-white heading-font mb-4"><?php echo htmlspecialchars($child['name']); ?></h1>
                <h2 class="text-2xl text-white mb-5">Our precious little <span class="text-yellow-300">"<?php echo htmlspecialchars($child['nickname'] ?: $child['name']); ?>"</span></h2>
                <p class="text-white text-lg mb-6"><?php echo calculate_age($child['birth_date']); ?> old • Born <?php echo date('F j, Y', strtotime($child['birth_date'])); ?></p>
                <div class="flex flex-wrap gap-3">
                    <a href="#gallery" class="px-6 py-3 bg-white theme-primary hover:bg-yellow-300 transition-all rounded-full font-semibold" style="color: <?php echo $theme_colors['primary']; ?>;">
                        View Photos <i class="fas fa-images ml-2"></i>
                    </a>
                    <a href="#wishes" class="px-6 py-3 border-2 border-white text-white rounded-full font-semibold hover:bg-white transition-all" style="hover-color: <?php echo $theme_colors['primary']; ?>;">
                        Send Wishes <i class="fas fa-heart ml-2"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="relative">
                    <?php if ($child['profile_picture']): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($child['profile_picture']); ?>" 
                             alt="<?php echo htmlspecialchars($child['name']); ?>" 
                             class="w-64 h-64 rounded-full object-cover border-4 border-white shadow-xl floating mx-auto">
                    <?php else: ?>
                        <div class="w-64 h-64 rounded-full bg-white flex items-center justify-center shadow-xl floating mx-auto">
                            <i class="fas fa-child text-6xl theme-primary"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-16 bg-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-8 mb-lg-0">
                <div class="relative">
                    <?php if ($child['cover_picture']): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($child['cover_picture']); ?>" 
                             alt="<?php echo htmlspecialchars($child['name']); ?>" 
                             class="w-full rounded-2xl shadow-lg">
                    <?php else: ?>
                        <div class="w-full h-64 rounded-2xl theme-secondary-bg shadow-lg flex items-center justify-center">
                            <i class="fas fa-child text-6xl theme-primary"></i>
                        </div>
                    <?php endif; ?>
                    <div class="absolute -bottom-5 -left-5 theme-primary-bg text-white px-4 py-3 rounded-lg shadow-lg">
                        <div class="text-2xl font-bold"><?php echo calculate_age($child['birth_date']); ?> old</div>
                        <div class="text-xs">And counting!</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="ps-lg-10">
                    <h3 class="text-3xl font-bold text-gray-800 heading-font mb-4">About <span class="theme-primary"><?php echo htmlspecialchars($child['nickname'] ?: $child['name']); ?></span></h3>
                    
                    <?php if ($child['meaning']): ?>
                        <p class="text-gray-600 mb-4"><?php echo nl2br(htmlspecialchars($child['meaning'])); ?></p>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <div class="theme-primary text-2xl mb-2">
                                <i class="fas fa-birthday-cake"></i>
                            </div>
                            <h4 class="font-bold text-gray-800">Birth Date</h4>
                            <p class="text-sm text-gray-600"><?php echo date('F j, Y', strtotime($child['birth_date'])); ?></p>
                        </div>
                        
                        <?php if ($child['birth_weight']): ?>
                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="theme-primary text-2xl mb-2">
                                    <i class="fas fa-weight"></i>
                                </div>
                                <h4 class="font-bold text-gray-800">Birth Weight</h4>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($child['birth_weight']); ?> kg</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($child['birth_length']): ?>
                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="theme-primary text-2xl mb-2">
                                    <i class="fas fa-ruler-vertical"></i>
                                </div>
                                <h4 class="font-bold text-gray-800">Birth Length</h4>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($child['birth_length']); ?> cm</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-wrap gap-3">
                        <a href="add-photo.php?child_id=<?php echo $child_id; ?>" class="px-6 py-3 btn-primary rounded-full font-semibold transition-all">
                            Add Photo <i class="fas fa-camera ml-2"></i>
                        </a>
                        <a href="add-milestone.php?child_id=<?php echo $child_id; ?>" class="px-6 py-3 btn-outline rounded-full font-semibold transition-all">
                            Add Milestone <i class="fas fa-star ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Gallery Section -->
<section id="gallery" class="py-16 bg-gray-50">
    <div class="container">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold theme-primary heading-font">Photo Gallery</h2>
            <a href="add-photo.php?child_id=<?php echo $child_id; ?>" class="px-4 py-2 btn-primary rounded-lg transition-all">
                <i class="fas fa-plus mr-2"></i> Add Photo
            </a>
        </div>
        
        <?php if (empty($gallery)): ?>
            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                <div class="text-5xl theme-primary mb-4">
                    <i class="fas fa-images"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">No Photos Yet</h3>
                <p class="text-gray-600 mb-4">Start building your child's photo gallery by adding their first photo.</p>
                <a href="add-photo.php?child_id=<?php echo $child_id; ?>" class="inline-block px-6 py-3 btn-primary rounded-lg transition-all">
                    Add First Photo <i class="fas fa-camera ml-2"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($gallery as $photo): ?>
                    <div class="gallery-item overflow-hidden rounded-xl shadow-md hover:shadow-xl transition-all relative">
                        <img src="../uploads/<?php echo htmlspecialchars($photo['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($photo['title'] ?: $child['name']); ?>" 
                             class="w-full h-64 object-cover transition-transform duration-300 hover:scale-105">
                             
                        <!-- Action buttons -->
                        <div class="absolute top-4 right-4 flex gap-2 action-btn">
                            <button onclick="openEditModal('photo', <?php echo $photo['gallery_id']; ?>, '<?php echo htmlspecialchars($photo['title'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($photo['description'] ?? '', ENT_QUOTES); ?>', '<?php echo $photo['date_taken'] ? date('Y-m-d', strtotime($photo['date_taken'])) : ''; ?>')" 
                                    class="p-3 bg-white rounded-full shadow-md hover:bg-gray-100 transition-all">
                                <i class="fas fa-edit theme-primary"></i>
                            </button>
                            
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="photo_action" value="delete">
                                <input type="hidden" name="photo_id" value="<?php echo $photo['gallery_id']; ?>">
                                <button type="submit" class="p-3 bg-white rounded-full shadow-md hover:bg-gray-100 transition-all" onclick="return confirm('Are you sure you want to delete this photo?');">
                                    <i class="fas fa-trash text-red-600"></i>
                                </button>
                            </form>
                        </div>
                        
                        <div class="p-4 bg-white">
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
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold theme-primary heading-font">Milestones</h2>
            <a href="add-milestone.php?child_id=<?php echo $child_id; ?>" class="px-4 py-2 btn-primary rounded-lg transition-all">
                <i class="fas fa-plus mr-2"></i> Add Milestone
            </a>
        </div>
        
        <?php if (empty($milestones)): ?>
            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                <div class="text-5xl theme-primary mb-4">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">No Milestones Yet</h3>
                <p class="text-gray-600 mb-4">Document your child's important achievements and special moments.</p>
                <a href="add-milestone.php?child_id=<?php echo $child_id; ?>" class="inline-block px-6 py-3 btn-primary rounded-lg transition-all">
                    Add First Milestone <i class="fas fa-star ml-2"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($milestones as $milestone): ?>
                    <div class="milestone-item bg-white rounded-xl shadow-md p-6 relative">
                        <!-- Action buttons -->
                        <div class="absolute top-4 right-4 flex gap-2 action-btn">
                            <button onclick="openEditModal('milestone', <?php echo $milestone['milestone_id']; ?>, '<?php echo htmlspecialchars($milestone['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($milestone['description'] ?? '', ENT_QUOTES); ?>', '<?php echo date('Y-m-d', strtotime($milestone['date_achieved'])); ?>')" 
                                    class="p-2 bg-white rounded-full shadow-md hover:bg-gray-100 transition-all">
                                <i class="fas fa-edit theme-primary"></i>
                            </button>
                            
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="milestone_action" value="delete">
                                <input type="hidden" name="milestone_id" value="<?php echo $milestone['milestone_id']; ?>">
                                <button type="submit" class="p-2 bg-white rounded-full shadow-md hover:bg-gray-100 transition-all" onclick="return confirm('Are you sure you want to delete this milestone?');">
                                    <i class="fas fa-trash text-red-600"></i>
                                </button>
                            </form>
                        </div>
                        
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
                                        <img src="../uploads/<?php echo htmlspecialchars($milestone['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($milestone['title']); ?>" 
                                             class="w-full max-w-md rounded-lg shadow-sm">
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
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold theme-primary heading-font">Wishes for <?php echo htmlspecialchars($child['nickname'] ?: $child['name']); ?></h2>
            <div class="w-20 h-1 theme-primary-bg mx-auto mb-6"></div>
            <p class="text-gray-600 max-w-2xl mx-auto">Share your love and blessings for <?php echo htmlspecialchars($child['nickname'] ?: $child['name']); ?></p>
        </div>
        
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-xl shadow-md p-6 mb-10">
                <form method="POST" action="">
                    <input type="hidden" name="add_wish" value="1">
                    <div class="mb-4">
                        <label for="sender_name" class="block text-gray-700 font-medium mb-2">Your Name *</label>
                        <input type="text" id="sender_name" name="sender_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="message" class="block text-gray-700 font-medium mb-2">Your Message *</label>
                        <textarea id="message" name="message" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="relationship" class="block text-gray-700 font-medium mb-2">Your Relationship</label>
                        <select id="relationship" name="relationship" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                            <option value="Family">Family</option>
                            <option value="Friend">Friend</option>
                            <option value="Colleague">Colleague</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 btn-primary rounded-lg font-semibold transition-all">
                        Send Wish <i class="fas fa-paper-plane ml-2"></i>
                    </button>
                </form>
            </div>

            <div id="wishes-container" class="space-y-4">
                <?php if (empty($wishes)): ?>
                    <div class="bg-white rounded-xl shadow-md p-8 text-center">
                        <div class="text-5xl theme-primary mb-4">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">No Wishes Yet</h3>
                        <p class="text-gray-600">Be the first to send your love and blessings to <?php echo htmlspecialchars($child['nickname'] ?: $child['name']); ?>!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($wishes as $wish): ?>
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 theme-secondary-bg theme-primary rounded-full w-10 h-10 flex items-center justify-center">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($wish['sender_name']); ?></h4>
                                    <p class="text-gray-600 text-sm mb-1"><?php echo htmlspecialchars($wish['relationship']); ?> • <?php echo date('F j, Y', strtotime($wish['created_at'])); ?></p>
                                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($wish['message'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle" class="text-xl font-bold">Edit Item</h3>
            <span class="modal-close" onclick="closeModal()">&times;</span>
        </div>
        <form id="editForm" method="POST" action="">
            <input type="hidden" id="itemAction" name="photo_action" value="update">
            <input type="hidden" id="itemId" name="photo_id">
            
            <div class="mb-4">
                <label for="editTitle" class="block text-gray-700 mb-2">Title</label>
                <input type="text" id="editTitle" name="title" class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label for="editDescription" class="block text-gray-700 mb-2">Description</label>
                <textarea id="editDescription" name="description" rows="3" class="w-full px-3 py-2 border rounded"></textarea>
            </div>
            
            <div class="mb-4">
                <label for="editDate" class="block text-gray-700 mb-2">Date</label>
                <input type="date" id="editDate" name="date_taken" class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Theme editor toggle
document.getElementById('themeToggle').addEventListener('click', function() {
    const panel = document.getElementById('themePanel');
    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
});

function closeThemePanel() {
    document.getElementById('themePanel').style.display = 'none';
}

// Edit modal functions
function openEditModal(type, id, title, description, date) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');
    const titleInput = document.getElementById('editTitle');
    const descInput = document.getElementById('editDescription');
    const dateInput = document.getElementById('editDate');
    const idInput = document.getElementById('itemId');
    const actionInput = document.getElementById('itemAction');
    const modalTitle = document.getElementById('modalTitle');
    
    // Set form action based on type
    if (type === 'photo') {
        actionInput.name = 'photo_action';
        actionInput.value = 'update';
        idInput.name = 'photo_id';
        modalTitle.textContent = 'Edit Photo';
        dateInput.name = 'date_taken';
    } else {
        actionInput.name = 'milestone_action';
        actionInput.value = 'update';
        idInput.name = 'milestone_id';
        modalTitle.textContent = 'Edit Milestone';
        dateInput.name = 'date_achieved';
    }
    
    // Set values
    idInput.value = id;
    titleInput.value = title || '';
    descInput.value = description || '';
    dateInput.value = date || '';
    
    // Show modal
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeModal();
    }
    
    const themePanel = document.getElementById('themePanel');
    const themeToggle = document.getElementById('themeToggle');
    if (themePanel.style.display === 'block' && !themeToggle.contains(event.target) && !themePanel.contains(event.target)) {
        closeThemePanel();
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>
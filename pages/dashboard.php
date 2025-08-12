<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

$page_title = 'Dashboard - Children Album';

// Enhanced analytics function
function get_child_analytics($child_id, $pdo) {
    $analytics = [
        'milestones' => [],
        'photos' => [],
        'wishes' => []
    ];

    // Get milestones by year
    $stmt = $pdo->prepare("SELECT 
                          YEAR(date_achieved) as year, 
                          COUNT(*) as count 
                          FROM milestones 
                          WHERE child_id = ? 
                          GROUP BY YEAR(date_achieved)");
    $stmt->execute([$child_id]);
    $analytics['milestones'] = $stmt->fetchAll();

    // Get photos by year
    $stmt = $pdo->prepare("SELECT 
                          YEAR(created_at) as year, 
                          COUNT(*) as count 
                          FROM gallery 
                          WHERE child_id = ? 
                          GROUP BY YEAR(created_at)");
    $stmt->execute([$child_id]);
    $analytics['photos'] = $stmt->fetchAll();

    // Get wishes count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishes WHERE child_id = ?");
    $stmt->execute([$child_id]);
    $analytics['wishes'] = $stmt->fetchColumn();

    return $analytics;
}

// Get user's children with statistics
$children = [];
$stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM milestones WHERE child_id = c.child_id) as milestone_count,
           (SELECT COUNT(*) FROM gallery WHERE child_id = c.child_id) as photo_count,
           (SELECT COUNT(*) FROM wishes WHERE child_id = c.child_id) as wish_count,
           (SELECT MAX(created_at) FROM gallery WHERE child_id = c.child_id) as last_photo_date,
           (SELECT MAX(date_achieved) FROM milestones WHERE child_id = c.child_id) as last_milestone_date,
           (SELECT COUNT(*) FROM share_links WHERE child_id = c.child_id AND expiry_date > NOW()) as active_share_links
    FROM children c 
    WHERE c.user_id = ?
    ORDER BY c.birth_date DESC
");
$stmt->execute([get_current_user_id()]);
$children = $stmt->fetchAll();

// Handle share link creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_share_link'])) {
    $child_id = $_POST['child_id'];
    $expiry_days = (int)$_POST['expiry_days'];
    
    // Verify child belongs to user
    $stmt = $pdo->prepare("SELECT child_id FROM children WHERE child_id = ? AND user_id = ?");
    $stmt->execute([$child_id, get_current_user_id()]);
    
    if ($stmt->fetch()) {
        $token = bin2hex(random_bytes(16));
        $expiry_date = date('Y-m-d H:i:s', strtotime("+$expiry_days days"));
        
        $stmt = $pdo->prepare("INSERT INTO share_links (child_id, token, expiry_date) VALUES (?, ?, ?)");
        $stmt->execute([$child_id, $token, $expiry_date]);
        
        $share_url = SITE_URL . "/shared.php?token=" . $token;
$success_message = "Share link created! URL: <a href='$share_url' target='_blank' class='font-medium'>$share_url</a> (Copy this link to share)";
   }
}


// Handle privacy toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_privacy'])) {
    $child_id = $_POST['child_id'];
    $is_public = (int)$_POST['is_public'];
    
    // Verify child belongs to user
    $stmt = $pdo->prepare("UPDATE children SET is_public = ? WHERE child_id = ? AND user_id = ?");
    $stmt->execute([$is_public, $child_id, get_current_user_id()]);
    
    if ($stmt->rowCount() > 0) {
        $success_message = "Album privacy updated successfully!";
    }
}

require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <!-- Success Message -->
        <?php if (isset($success_message)): ?>
            <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg border border-green-200">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Dashboard Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-6">
            <div>
                <div class="flex items-center mb-2">
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 heading-font">My Children's Albums</h1>
                    <span class="ml-3 px-3 py-1 rounded-full bg-pink-100 text-pink-800 text-sm font-medium">
                        <?php echo count($children); ?> Albums
                    </span>
                </div>
                <p class="text-gray-600">Manage and explore your children's precious memories</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <a href="add-child.php" class="flex items-center justify-center px-5 py-3 bg-gradient-to-r from-pink-600 to-pink-500 text-white rounded-lg hover:from-pink-700 hover:to-pink-600 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <i class="fas fa-plus-circle mr-2"></i> Add Child
                </a>
                <a href="memory-books.php" class="flex items-center justify-center px-5 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all shadow-sm hover:shadow-md">
                    <i class="fas fa-book mr-2"></i> Create Memory Book
                </a>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content Column -->
            <div class="lg:col-span-3">
                <?php if (empty($children)): ?>
                    <!-- Empty State -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-8 sm:p-12 text-center">
                            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-pink-100 mb-6">
                                <i class="fas fa-baby-carriage text-4xl text-pink-500"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">No Children Added Yet</h3>
                            <p class="text-gray-600 max-w-md mx-auto mb-6">
                                Start preserving your child's precious moments by creating their first album.
                            </p>
                            <div class="flex flex-col sm:flex-row justify-center gap-3">
                                <a href="add-child.php" class="px-6 py-3 bg-gradient-to-r from-pink-600 to-pink-500 text-white rounded-lg hover:from-pink-700 hover:to-pink-600 transition-all shadow-md hover:shadow-lg">
                                    <i class="fas fa-plus-circle mr-2"></i> Add Your First Child
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Children Grid -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">All Children</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 p-4">
                            <?php foreach ($children as $child): 
                                $age = calculate_age($child['birth_date']);
                                $birthDate = date('F j, Y', strtotime($child['birth_date']));
                                $lastUpdated = $child['updated_at'] ? date('M j, Y', strtotime($child['updated_at'])) : 'Never';
                                $lastPhoto = $child['last_photo_date'] ? date('M j, Y', strtotime($child['last_photo_date'])) : 'No photos yet';
                                $lastMilestone = $child['last_milestone_date'] ? date('M j, Y', strtotime($child['last_milestone_date'])) : 'No milestones yet';
                                $analytics = get_child_analytics($child['child_id'], $pdo);
                            ?>
                                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md hover:-translate-y-1">
                                    <!-- Cover Image -->
                                    <div class="relative h-48 bg-gradient-to-br from-pink-100 to-blue-100 overflow-hidden group">
                                        <?php if ($child['cover_picture']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($child['cover_picture']); ?>" 
                                                 alt="<?php echo htmlspecialchars($child['name']); ?>'s cover photo" 
                                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                 loading="lazy">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center">
                                                <i class="fas fa-child text-6xl text-pink-400 opacity-30"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                                        <div class="absolute bottom-0 left-0 right-0 p-4">
                                            <h3 class="text-xl font-bold text-white heading-font truncate"><?php echo htmlspecialchars($child['name']); ?></h3>
                                            <p class="text-pink-200 text-sm"><?php echo htmlspecialchars($age); ?> old</p>
                                        </div>
                                        <div class="absolute top-3 right-3 flex flex-col gap-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/90 text-pink-600">
                                                <?php echo htmlspecialchars($child['milestone_count']); ?> milestones
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $child['is_public'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo $child['is_public'] ? 'Public' : 'Private'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Profile Section -->
                                    <div class="p-4">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 -mt-12">
                                                <div class="relative">
                                                    <div class="h-16 w-16 rounded-full border-4 border-white bg-white shadow-sm overflow-hidden">
                                                        <?php if ($child['profile_picture']): ?>
                                                            <img src="../uploads/<?php echo htmlspecialchars($child['profile_picture']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($child['name']); ?>" 
                                                                 class="h-full w-full object-cover">
                                                        <?php else: ?>
                                                            <div class="h-full w-full flex items-center justify-center bg-pink-100 text-pink-500">
                                                                <i class="fas fa-baby text-2xl"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($child['gender']): ?>
                                                        <span class="absolute -bottom-1 -right-1 inline-flex items-center justify-center h-6 w-6 rounded-full bg-<?php echo $child['gender'] === 'boy' ? 'blue' : 'pink'; ?>-500 text-white text-xs border-2 border-white">
                                                            <?php echo $child['gender'] === 'boy' ? '♂' : '♀'; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="ml-4 flex-1 min-w-0">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <h4 class="font-bold text-gray-900 truncate">
                                                            <?php echo htmlspecialchars($child['nickname'] ?: $child['name']); ?>
                                                        </h4>
                                                        <p class="text-sm text-gray-500">Born <?php echo htmlspecialchars($birthDate); ?></p>
                                                    </div>
                                                    <div class="text-xs text-gray-400" title="Last updated">
                                                        <i class="fas fa-clock mr-1"></i> <?php echo htmlspecialchars($lastUpdated); ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- Stats -->
                                                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                                    <div class="p-2 bg-gray-50 rounded-lg">
                                                        <div class="text-xs text-gray-500">Photos</div>
                                                        <div class="font-bold text-pink-600"><?php echo (int)$child['photo_count']; ?></div>
                                                    </div>
                                                    <div class="p-2 bg-gray-50 rounded-lg">
                                                        <div class="text-xs text-gray-500">Milestones</div>
                                                        <div class="font-bold text-blue-600"><?php echo (int)$child['milestone_count']; ?></div>
                                                    </div>
                                                    <div class="p-2 bg-gray-50 rounded-lg">
                                                        <div class="text-xs text-gray-500">Wishes</div>
                                                        <div class="font-bold text-purple-600"><?php echo (int)$child['wish_count']; ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Privacy and Sharing -->
                                        <div class="mt-3 space-y-2">
                                            <!-- Privacy Toggle -->
                                            <form method="POST" class="flex items-center justify-between bg-gray-50 p-2 rounded-lg">
                                                <div class="text-sm text-gray-600">
                                                    <i class="fas fa-<?php echo $child['is_public'] ? 'globe' : 'lock'; ?> mr-1"></i>
                                                    Album is <?php echo $child['is_public'] ? 'public' : 'private'; ?>
                                                </div>
                                                <input type="hidden" name="child_id" value="<?php echo $child['child_id']; ?>">
                                                <input type="hidden" name="is_public" value="<?php echo $child['is_public'] ? '0' : '1'; ?>">
                                                <button type="submit" name="toggle_privacy" class="text-xs px-2 py-1 rounded bg-white border border-gray-200 hover:bg-gray-100">
                                                    Make <?php echo $child['is_public'] ? 'Private' : 'Public'; ?>
                                                </button>
                                            </form>
                                            
                                            <!-- Share Link Creation -->
                                            <div x-data="{ showShareForm: false }" class="bg-gray-50 p-2 rounded-lg">
                                                <div class="flex items-center justify-between">
                                                    <div class="text-sm text-gray-600">
                                                        <i class="fas fa-share-alt mr-1"></i>
                                                        <?php echo (int)$child['active_share_links']; ?> active share link(s)
                                                    </div>
                                                    <button @click="showShareForm = !showShareForm" class="text-xs px-2 py-1 rounded bg-white border border-gray-200 hover:bg-gray-100">
                                                        <i class="fas fa-plus mr-1"></i> Create Link
                                                    </button>
                                                </div>
                                                
                                                <!-- Share Form (hidden by default) -->
                                                <form x-show="showShareForm" method="POST" class="mt-2 space-y-2">
                                                    <input type="hidden" name="child_id" value="<?php echo $child['child_id']; ?>">
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Expires in</label>
                                                        <select name="expiry_days" class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                                                            <option value="1">1 day</option>
                                                            <option value="7" selected>7 days</option>
                                                            <option value="30">30 days</option>
                                                            <option value="90">90 days</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" name="create_share_link" class="w-full text-xs px-2 py-1 rounded bg-pink-100 text-pink-700 hover:bg-pink-200">
                                                        Generate Share Link
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="mt-4 flex justify-between items-center border-t border-gray-100 pt-3">
                                            <a href="view-child.php?id=<?php echo $child['child_id']; ?>" 
                                               class="inline-flex items-center text-pink-600 hover:text-pink-700 font-medium text-sm px-3 py-1.5 rounded-lg hover:bg-pink-50 transition-all">
                                                View Album <i class="fas fa-arrow-right ml-1.5 text-xs"></i>
                                            </a>
                                            <div class="flex items-center space-x-2">
                                                <a href="edit-child.php?id=<?php echo $child['child_id']; ?>" 
                                                   class="text-gray-500 hover:text-blue-600 p-1.5 rounded-full hover:bg-blue-50 transition-all"
                                                   title="Edit">
                                                    <i class="fas fa-pencil-alt text-sm"></i>
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this album?');">
                                                    <input type="hidden" name="delete_child_id" value="<?php echo $child['child_id']; ?>">
                                                    <button type="submit" class="text-gray-500 hover:text-red-600 p-1.5 rounded-full hover:bg-red-50 transition-all"
                                                            title="Delete">
                                                        <i class="fas fa-trash-alt text-sm"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
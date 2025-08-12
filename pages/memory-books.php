<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

$page_title = 'Create Memory Book';
$child_id = $_GET['child_id'] ?? null;

// Get user's children
$stmt = $pdo->prepare("SELECT * FROM children WHERE user_id = ? ORDER BY name");
$stmt->execute([get_current_user_id()]);
$children = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_memory_book'])) {
    $child_id = $_POST['child_id'];
    $title = sanitize_input($_POST['title']);
    $cover_style = $_POST['cover_style'];
    $include_photos = isset($_POST['include_photos']) ? 1 : 0;
    $include_milestones = isset($_POST['include_milestones']) ? 1 : 0;
    $include_wishes = isset($_POST['include_wishes']) ? 1 : 0;
    $theme = $_POST['theme'];
    
    // Validate child belongs to user
    $valid_child = false;
    foreach ($children as $child) {
        if ($child['child_id'] == $child_id) {
            $valid_child = true;
            break;
        }
    }
    
    if ($valid_child) {
        try {
            // Create memory book record
            $stmt = $pdo->prepare("INSERT INTO memory_books 
                                  (user_id, child_id, title, cover_style, include_photos, include_milestones, include_wishes, theme, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                get_current_user_id(),
                $child_id,
                $title,
                $cover_style,
                $include_photos,
                $include_milestones,
                $include_wishes,
                $theme
            ]);
            
            $memory_book_id = $pdo->lastInsertId();
            $_SESSION['success'] = "Memory book created successfully!";
            header("Location: view-memory-book.php?id=$memory_book_id");
            exit;
        } catch (PDOException $e) {
            $error = "Error creating memory book: " . $e->getMessage();
        }
    } else {
        $error = "Invalid child selection";
    }
}

require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Create Memory Book</h1>
                <a href="memory-books.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Books
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <form method="POST" action="">
                    <div class="p-6 space-y-6">
                        <!-- Basic Information -->
                        <div class="border-b border-gray-200 pb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Basic Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="child_id" class="block text-sm font-medium text-gray-700 mb-1">Select Child</label>
                                    <select id="child_id" name="child_id" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- Select Child --</option>
                                        <?php foreach ($children as $child): ?>
                                            <option value="<?php echo $child['child_id']; ?>" 
                                                <?php echo ($child_id == $child['child_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($child['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Book Title</label>
                                    <input type="text" id="title" name="title" required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="e.g. My Baby's First Year">
                                </div>
                            </div>
                        </div>

                        <!-- Cover Style -->
                        <div class="border-b border-gray-200 pb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Cover Style</h2>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <label class="cover-option">
                                    <input type="radio" name="cover_style" value="classic" checked class="hidden peer">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                        <div class="h-32 bg-gray-100 mb-2 flex items-center justify-center">
                                            <i class="fas fa-book text-4xl text-gray-400"></i>
                                        </div>
                                        <span class="font-medium">Classic</span>
                                    </div>
                                </label>
                                <label class="cover-option">
                                    <input type="radio" name="cover_style" value="modern" class="hidden peer">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                        <div class="h-32 bg-gray-800 mb-2 flex items-center justify-center">
                                            <i class="fas fa-book-open text-4xl text-white"></i>
                                        </div>
                                        <span class="font-medium">Modern</span>
                                    </div>
                                </label>
                                <label class="cover-option">
                                    <input type="radio" name="cover_style" value="elegant" class="hidden peer">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                        <div class="h-32 bg-gradient-to-r from-purple-500 to-pink-500 mb-2 flex items-center justify-center">
                                            <i class="fas fa-heart text-4xl text-white"></i>
                                        </div>
                                        <span class="font-medium">Elegant</span>
                                    </div>
                                </label>
                                <label class="cover-option">
                                    <input type="radio" name="cover_style" value="fun" class="hidden peer">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                        <div class="h-32 bg-yellow-400 mb-2 flex items-center justify-center">
                                            <i class="fas fa-star text-4xl text-white"></i>
                                        </div>
                                        <span class="font-medium">Fun</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Content Selection -->
                        <div class="border-b border-gray-200 pb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Content to Include</h2>
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_photos" checked 
                                           class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                                    <span class="ml-2 text-gray-700">Photos (<?php 
                                        if ($child_id) {
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM gallery WHERE child_id = ?");
                                            $stmt->execute([$child_id]);
                                            echo $stmt->fetchColumn();
                                        } else {
                                            echo '0';
                                        }
                                    ?> available)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_milestones" checked 
                                           class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                                    <span class="ml-2 text-gray-700">Milestones (<?php 
                                        if ($child_id) {
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM milestones WHERE child_id = ?");
                                            $stmt->execute([$child_id]);
                                            echo $stmt->fetchColumn();
                                        } else {
                                            echo '0';
                                        }
                                    ?> available)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_wishes" 
                                           class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                                    <span class="ml-2 text-gray-700">Wishes & Messages (<?php 
                                        if ($child_id) {
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishes WHERE child_id = ?");
                                            $stmt->execute([$child_id]);
                                            echo $stmt->fetchColumn();
                                        } else {
                                            echo '0';
                                        }
                                    ?> available)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Theme Selection -->
                        <div class="pb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Book Theme</h2>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="classic" checked class="hidden peer">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                        <div class="h-16 bg-white mb-2 flex items-center justify-center border">
                                            <div class="w-4 h-4 bg-gray-300 mx-1"></div>
                                            <div class="w-4 h-4 bg-gray-100 mx-1"></div>
                                            <div class="w-4 h-4 bg-gray-300 mx-1"></div>
                                        </div>
                                        <span class="font-medium">Classic</span>
                                    </div>
                                </label>
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="pastel" class="hidden peer">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                        <div class="h-16 bg-pink-50 mb-2 flex items-center justify-center">
                                            <div class="w-4 h-4 bg-pink-200 mx-1"></div>
                                            <div class="w-4 h-4 bg-blue-200 mx-1"></div>
                                            <div class="w-4 h-4 bg-yellow-200 mx-1"></div>
                                        </div>
                                        <span class="font-medium">Pastel</span>
                                    </div>
                                </label>
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="vibrant" class="hidden peer">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                        <div class="h-16 bg-gray-900 mb-2 flex items-center justify-center">
                                            <div class="w-4 h-4 bg-red-500 mx-1"></div>
                                            <div class="w-4 h-4 bg-yellow-500 mx-1"></div>
                                            <div class="w-4 h-4 bg-blue-500 mx-1"></div>
                                        </div>
                                        <span class="font-medium">Vibrant</span>
                                    </div>
                                </label>
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="elegant" class="hidden peer">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                        <div class="h-16 bg-gray-800 mb-2 flex items-center justify-center">
                                            <div class="w-4 h-4 bg-gray-600 mx-1"></div>
                                            <div class="w-4 h-4 bg-gray-400 mx-1"></div>
                                            <div class="w-4 h-4 bg-gray-200 mx-1"></div>
                                        </div>
                                        <span class="font-medium">Elegant</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" name="create_memory_book" 
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                <i class="fas fa-book mr-2"></i> Create Memory Book
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('child_id').addEventListener('change', function() {
    const childId = this.value;
    if (!childId) return;
    
    // Fetch counts via AJAX
    fetch(`get-child-counts.php?child_id=${childId}`)
        .then(response => response.json())
        .then(data => {
            document.querySelector('input[name="include_photos"]').nextElementSibling.innerHTML = 
                `Photos (${data.photos} available)`;
            document.querySelector('input[name="include_milestones"]').nextElementSibling.innerHTML = 
                `Milestones (${data.milestones} available)`;
            document.querySelector('input[name="include_wishes"]').nextElementSibling.innerHTML = 
                `Wishes & Messages (${data.wishes} available)`;
        });
});
// Update content counts when child selection changes
document.getElementById('child_id').addEventListener('change', function() {
    const childId = this.value;
    if (!childId) return;
    
    // You would typically fetch these counts via AJAX in a real application
    // For this example, we'll just show the counts that were preloaded in PHP
});
</script>

<?php
require_once '../includes/footer.php';
?>
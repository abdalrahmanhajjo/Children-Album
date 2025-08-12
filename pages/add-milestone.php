<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

if (!isset($_GET['child_id'])) {
    header('Location: dashboard.php');
    exit;
}

$child_id = $_GET['child_id'];
$child = get_child_details($child_id);

// Verify child belongs to current user
if (!$child || $child['user_id'] != get_current_user_id()) {
    header('Location: dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $date_achieved = sanitize_input($_POST['date_achieved']);
    
    // Validate required fields
    if (empty($title)) {
        $error = "Title is required";
    } elseif (empty($date_achieved)) {
        $error = "Date achieved is required";
    } elseif (!validate_date($date_achieved)) {
        $error = "Invalid date format";
    } else {
        $image_path = null;
        
        // Handle file upload if provided
        if (!empty($_FILES['image']['name'])) {
            $result = upload_file($_FILES['image']);
            
            if ($result['success']) {
                $image_path = $result['filename'];
            } else {
                $error = $result['message'];
            }
        }
        
        if (!isset($error)) {
            // Insert milestone record
            $stmt = $pdo->prepare("INSERT INTO milestones (child_id, title, description, date_achieved, image_path) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$child_id, $title, $description, $date_achieved, $image_path])) {
                header("Location: view-child.php?id=$child_id#milestones");
                exit;
            } else {
                $error = "Failed to add milestone. Please try again.";
            }
        }
    }
}

$page_title = 'Add Milestone';
require_once '../includes/header.php';
?>

<div class="container py-12">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-md p-8">
            <h1 class="text-2xl font-bold text-gray-800 heading-font mb-6">Add Milestone for <?php echo htmlspecialchars($child['name']); ?></h1>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="title" class="block text-gray-700 font-medium mb-2">Title *</label>
                    <input type="text" id="title" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-gray-700 font-medium mb-2">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="date_achieved" class="block text-gray-700 font-medium mb-2">Date Achieved *</label>
                    <input type="date" id="date_achieved" name="date_achieved" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                </div>
                
                <div class="mb-6">
                    <label for="image" class="block text-gray-700 font-medium mb-2">Photo (Optional)</label>
                    <input type="file" id="image" name="image" accept="image/*" class="w-full">
                    <p class="text-sm text-gray-500 mt-1">Maximum file size: 5MB</p>
                </div>
                
                <div class="flex justify-between">
                    <a href="view-child.php?id=<?php echo $child_id; ?>" class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-100 transition-all">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-pink-600 text-white rounded-lg font-semibold hover:bg-pink-700 transition-all">
                        Save Milestone <i class="fas fa-save ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
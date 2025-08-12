<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $nickname = sanitize_input($_POST['nickname']);
    $gender = sanitize_input($_POST['gender']);
    $birth_date = sanitize_input($_POST['birth_date']);
    $birth_weight = sanitize_input($_POST['birth_weight']);
    $birth_length = sanitize_input($_POST['birth_length']);
    $meaning = sanitize_input($_POST['meaning']);
    $user_id = get_current_user_id();
    
    // Validate required fields
    if (empty($name)) {
        $error = "Child's name is required";
    } elseif (empty($birth_date)) {
        $error = "Birth date is required";
    } elseif (!validate_date($birth_date)) {
        $error = "Invalid birth date format";
    } else {
        // Handle file uploads
        $profile_picture = null;
        $cover_picture = null;
        
        if (!empty($_FILES['profile_picture']['name'])) {
            $result = upload_file($_FILES['profile_picture']);
            if ($result['success']) {
                $profile_picture = $result['filename'];
            } else {
                $error = $result['message'];
            }
        }
        
        if (!empty($_FILES['cover_picture']['name']) && !isset($error)) {
            $result = upload_file($_FILES['cover_picture']);
            if ($result['success']) {
                $cover_picture = $result['filename'];
            } else {
                $error = $result['message'];
            }
        }
        
        if (!isset($error)) {
            // Insert child record
            $stmt = $pdo->prepare("INSERT INTO children (user_id, name, nickname, gender, birth_date, birth_weight, birth_length, meaning, profile_picture, cover_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$user_id, $name, $nickname, $gender, $birth_date, $birth_weight, $birth_length, $meaning, $profile_picture, $cover_picture])) {
                $child_id = $pdo->lastInsertId();
                header("Location: view-child.php?id=$child_id");
                exit;
            } else {
                $error = "Failed to add child. Please try again.";
            }
        }
    }
}

$page_title = 'Add Child';
require_once '../includes/header.php';
?>

<div class="container py-12">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-md p-8">
            <h1 class="text-3xl font-bold text-gray-800 heading-font mb-6">Add Your Child</h1>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="name" class="block text-gray-700 font-medium mb-2">Full Name *</label>
                        <input type="text" id="name" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                    </div>
                    <div>
                        <label for="nickname" class="block text-gray-700 font-medium mb-2">Nickname</label>
                        <input type="text" id="nickname" name="nickname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="gender" class="block text-gray-700 font-medium mb-2">Gender *</label>
                        <select id="gender" name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                            <option value="girl">Girl</option>
                            <option value="boy">Boy</option>
                        </select>
                    </div>
                    <div>
                        <label for="birth_date" class="block text-gray-700 font-medium mb-2">Birth Date *</label>
                        <input type="date" id="birth_date" name="birth_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="birth_weight" class="block text-gray-700 font-medium mb-2">Birth Weight (kg)</label>
                        <input type="number" step="0.01" id="birth_weight" name="birth_weight" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                    <div>
                        <label for="birth_length" class="block text-gray-700 font-medium mb-2">Birth Length (cm)</label>
                        <input type="number" step="0.1" id="birth_length" name="birth_length" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="meaning" class="block text-gray-700 font-medium mb-2">Name Meaning</label>
                    <textarea id="meaning" name="meaning" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="profile_picture" class="block text-gray-700 font-medium mb-2">Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="w-full">
                        <p class="text-sm text-gray-500 mt-1">Recommended size: 300x300 pixels</p>
                    </div>
                    <div>
                        <label for="cover_picture" class="block text-gray-700 font-medium mb-2">Cover Picture</label>
                        <input type="file" id="cover_picture" name="cover_picture" accept="image/*" class="w-full">
                        <p class="text-sm text-gray-500 mt-1">Recommended size: 1200x400 pixels</p>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-pink-600 text-white rounded-lg font-semibold hover:bg-pink-700 transition-all">
                        Save Child <i class="fas fa-save ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
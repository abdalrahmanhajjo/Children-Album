
<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

$child_id = $_GET['id'];
$child = get_child_details($child_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $nickname = sanitize_input($_POST['nickname']);
    $gender = sanitize_input($_POST['gender']);
    $birth_date = sanitize_input($_POST['birth_date']);
    $birth_weight = sanitize_input($_POST['birth_weight']);
    $birth_length = sanitize_input($_POST['birth_length']);
    $meaning = sanitize_input($_POST['meaning']);
    
    // Validate required fields
    if (empty($name)) {
        $error = "Child's name is required";
    } elseif (empty($birth_date)) {
        $error = "Birth date is required";
    } elseif (!validate_date($birth_date)) {
        $error = "Invalid birth date format";
    } else {
        // Handle file uploads
        $profile_picture = $child['profile_picture'];
        $cover_picture = $child['cover_picture'];
        
        if (!empty($_FILES['profile_picture']['name'])) {
            $result = upload_file($_FILES['profile_picture']);
            if ($result['success']) {
                // Delete old profile picture if exists
                if ($profile_picture && file_exists(UPLOAD_PATH . $profile_picture)) {
                    unlink(UPLOAD_PATH . $profile_picture);
                }
                $profile_picture = $result['filename'];
            } else {
                $error = $result['message'];
            }
        }
        
        if (!empty($_FILES['cover_picture']['name']) && !isset($error)) {
            $result = upload_file($_FILES['cover_picture']);
            if ($result['success']) {
                // Delete old cover picture if exists
                if ($cover_picture && file_exists(UPLOAD_PATH . $cover_picture)) {
                    unlink(UPLOAD_PATH . $cover_picture);
                }
                $cover_picture = $result['filename'];
            } else {
                $error = $result['message'];
            }
        }
        
        if (!isset($error)) {
            // Update child record
            $stmt = $pdo->prepare("UPDATE children SET name = ?, nickname = ?, gender = ?, birth_date = ?, birth_weight = ?, birth_length = ?, meaning = ?, profile_picture = ?, cover_picture = ? WHERE child_id = ?");
            
            if ($stmt->execute([$name, $nickname, $gender, $birth_date, $birth_weight, $birth_length, $meaning, $profile_picture, $cover_picture, $child_id])) {
                header("Location: view-child.php?id=$child_id");
                exit;
            } else {
                $error = "Failed to update child. Please try again.";
            }
        }
    }
}

$page_title = 'Edit ' . $child['name'];
require_once '../includes/header.php';
?>

<div class="container py-12">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-md p-8">
            <h1 class="text-3xl font-bold text-gray-800 heading-font mb-6">Edit <?php echo htmlspecialchars($child['name']); ?></h1>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="name" class="block text-gray-700 font-medium mb-2">Full Name *</label>
                        <input type="text" id="name" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" value="<?php echo htmlspecialchars($child['name']); ?>" required>
                    </div>
                    <div>
                        <label for="nickname" class="block text-gray-700 font-medium mb-2">Nickname</label>
                        <input type="text" id="nickname" name="nickname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" value="<?php echo htmlspecialchars($child['nickname']); ?>">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="gender" class="block text-gray-700 font-medium mb-2">Gender *</label>
                        <select id="gender" name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                            <option value="girl" <?php echo $child['gender'] === 'girl' ? 'selected' : ''; ?>>Girl</option>
                            <option value="boy" <?php echo $child['gender'] === 'boy' ? 'selected' : ''; ?>>Boy</option>
                        </select>
                    </div>
                    <div>
                        <label for="birth_date" class="block text-gray-700 font-medium mb-2">Birth Date *</label>
                        <input type="date" id="birth_date" name="birth_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" value="<?php echo htmlspecialchars($child['birth_date']); ?>" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="birth_weight" class="block text-gray-700 font-medium mb-2">Birth Weight (kg)</label>
                        <input type="number" step="0.01" id="birth_weight" name="birth_weight" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" value="<?php echo htmlspecialchars($child['birth_weight']); ?>">
                    </div>
                    <div>
                        <label for="birth_length" class="block text-gray-700 font-medium mb-2">Birth Length (cm)</label>
                        <input type="number" step="0.1" id="birth_length" name="birth_length" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" value="<?php echo htmlspecialchars($child['birth_length']); ?>">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="meaning" class="block text-gray-700 font-medium mb-2">Name Meaning</label>
                    <textarea id="meaning" name="meaning" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500"><?php echo htmlspecialchars($child['meaning']); ?></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Current Profile Picture</label>
                        <?php if ($child['profile_picture']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($child['profile_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($child['name']); ?>" 
                                 class="w-32 h-32 rounded-full object-cover mb-2">
                        <?php else: ?>
                            <div class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center mb-2">
                                <i class="fas fa-baby text-3xl text-pink-400"></i>
                            </div>
                        <?php endif; ?>
                        <label for="profile_picture" class="block text-gray-700 font-medium mb-2">New Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="w-full">
                        <p class="text-sm text-gray-500 mt-1">Recommended size: 300x300 pixels</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Current Cover Picture</label>
                        <?php if ($child['cover_picture']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($child['cover_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($child['name']); ?>" 
                                 class="w-full h-32 object-cover rounded-lg mb-2">
                        <?php else: ?>
                            <div class="w-full h-32 bg-gradient-to-r from-pink-100 to-blue-100 rounded-lg flex items-center justify-center mb-2">
                                <i class="fas fa-child text-4xl text-pink-400"></i>
                            </div>
                        <?php endif; ?>
                        <label for="cover_picture" class="block text-gray-700 font-medium mb-2">New Cover Picture</label>
                        <input type="file" id="cover_picture" name="cover_picture" accept="image/*" class="w-full">
                        <p class="text-sm text-gray-500 mt-1">Recommended size: 1200x400 pixels</p>
                    </div>
                </div>
                
                <div class="flex justify-between">
                    <a href="view-child.php?id=<?php echo $child_id; ?>" class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-100 transition-all">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-pink-600 text-white rounded-lg font-semibold hover:bg-pink-700 transition-all">
                        Save Changes <i class="fas fa-save ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
<?php
require_once '../includes/config.php';

redirect_if_not_logged_in();

if (!isset($_GET['id'])) {
    header('Location: memory-books.php');
    exit;
}

$book_id = $_GET['id'];

// Get memory book details
$stmt = $pdo->prepare("
    SELECT mb.*, c.name as child_name, c.profile_picture, c.gender 
    FROM memory_books mb
    JOIN children c ON mb.child_id = c.child_id
    WHERE mb.book_id = ? AND mb.user_id = ?
");
$stmt->execute([$book_id, get_current_user_id()]);
$memory_book = $stmt->fetch();

if (!$memory_book) {
    header('Location: memory-books.php');
    exit;
}

// Get content based on selected options
$photos = [];
$milestones = [];
$wishes = [];

if ($memory_book['include_photos']) {
    $stmt = $pdo->prepare("SELECT * FROM gallery WHERE child_id = ? ORDER BY date_taken, created_at");
    $stmt->execute([$memory_book['child_id']]);
    $photos = $stmt->fetchAll();
}

if ($memory_book['include_milestones']) {
    $stmt = $pdo->prepare("SELECT * FROM milestones WHERE child_id = ? ORDER BY date_achieved");
    $stmt->execute([$memory_book['child_id']]);
    $milestones = $stmt->fetchAll();
}

if ($memory_book['include_wishes']) {
    $stmt = $pdo->prepare("SELECT * FROM wishes WHERE child_id = ? ORDER BY created_at");
    $stmt->execute([$memory_book['child_id']]);
    $wishes = $stmt->fetchAll();
}

// Handle PDF generation request
if (isset($_POST['generate_pdf'])) {
   require_once __DIR__.'/../vendor/autoload.php';

    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(SITE_NAME);
    $pdf->SetAuthor($memory_book['child_name'] . "'s Parent");
    $pdf->SetTitle($memory_book['title']);
    $pdf->SetSubject('Memory Book');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, $memory_book['title'], 'Created on ' . date('F j, Y', strtotime($memory_book['created_at'])));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    // Cover page
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 0, $memory_book['title'], 0, 1, 'C');
    $pdf->Ln(10);
    
    if ($memory_book['profile_picture']) {
        $image_file = '../uploads/' . $memory_book['profile_picture'];
        $pdf->Image($image_file, '', '', 100, 0, '', '', 'C', false, 300, '', false, false, 1, false, false, false);
    }
    
    $pdf->SetFont('helvetica', '', 16);
    $pdf->Cell(0, 0, 'For ' . $memory_book['child_name'], 0, 1, 'C');
    $pdf->Ln(20);
    $pdf->Cell(0, 0, 'Created on ' . date('F j, Y', strtotime($memory_book['created_at'])), 0, 1, 'C');
    
    // Add content pages
    if (!empty($photos)) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 0, 'Photo Gallery', 0, 1);
        $pdf->Ln(10);
        
        foreach ($photos as $index => $photo) {
            if ($index > 0 && $index % 2 == 0) {
                $pdf->AddPage();
            }
            
            $image_file = '../uploads/' . $photo['image_path'];
            $pdf->Image($image_file, '', '', 90, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
            
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 0, $photo['title'] ?? 'Untitled', 0, 1);
            if ($photo['date_taken']) {
                $pdf->Cell(0, 0, date('F j, Y', strtotime($photo['date_taken'])), 0, 1);
            }
            $pdf->Ln(10);
        }
    }
    
    // Output PDF to browser
    $pdf->Output($memory_book['title'] . '.pdf', 'D');
    exit;
}

$page_title = $memory_book['title'] . ' - Memory Book';
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($memory_book['title']); ?></h1>
                <div class="flex gap-2">
                    <a href="memory-books.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Books
                    </a>
                    <form method="POST" action="">
                        <button type="submit" name="generate_pdf" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-file-pdf mr-2"></i> Download PDF
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                <!-- Cover Preview -->
                <div class="p-8 text-center border-b border-gray-200">
                    <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($memory_book['title']); ?></h2>
                    <div class="mx-auto w-48 h-64 bg-gray-100 rounded-lg shadow-md mb-4 flex items-center justify-center overflow-hidden">
                        <?php if ($memory_book['profile_picture']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($memory_book['profile_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($memory_book['child_name']); ?>" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-child text-5xl text-gray-400"></i>
                        <?php endif; ?>
                    </div>
                    <p class="text-gray-600">For <?php echo htmlspecialchars($memory_book['child_name']); ?></p>
                    <p class="text-sm text-gray-500">Created on <?php echo date('F j, Y', strtotime($memory_book['created_at'])); ?></p>
                </div>

                <!-- Content Summary -->
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <div class="text-blue-600 text-2xl mb-2">
                            <i class="fas fa-images"></i>
                        </div>
                        <h3 class="font-bold">Photos</h3>
                        <p class="text-gray-600"><?php echo count($photos); ?> included</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <div class="text-green-600 text-2xl mb-2">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="font-bold">Milestones</h3>
                        <p class="text-gray-600"><?php echo count($milestones); ?> included</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                        <div class="text-purple-600 text-2xl mb-2">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3 class="font-bold">Wishes</h3>
                        <p class="text-gray-600"><?php echo count($wishes); ?> included</p>
                    </div>
                </div>
            </div>

            <!-- Preview Sections -->
            <?php if (!empty($photos)): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-2xl font-bold">Photo Gallery</h2>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($photos as $photo): ?>
                            <div class="overflow-hidden rounded-lg shadow-sm border border-gray-200">
                                <img src="../uploads/<?php echo htmlspecialchars($photo['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($photo['title'] ?? 'Photo'); ?>" 
                                     class="w-full h-48 object-cover">
                                <div class="p-4">
                                    <h3 class="font-bold"><?php echo htmlspecialchars($photo['title'] ?? 'Untitled'); ?></h3>
                                    <?php if ($photo['date_taken']): ?>
                                        <p class="text-sm text-gray-600"><?php echo date('F j, Y', strtotime($photo['date_taken'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($milestones)): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-2xl font-bold">Milestones</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <?php foreach ($milestones as $milestone): ?>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-blue-100 text-blue-600 rounded-full w-12 h-12 flex items-center justify-center mr-4">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold"><?php echo htmlspecialchars($milestone['title']); ?></h3>
                                    <p class="text-gray-600"><?php echo date('F j, Y', strtotime($milestone['date_achieved'])); ?></p>
                                    <?php if ($milestone['description']): ?>
                                        <p class="text-gray-700 mt-2"><?php echo nl2br(htmlspecialchars($milestone['description'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($milestone['image_path']): ?>
                                        <div class="mt-4">
                                            <img src="../uploads/<?php echo htmlspecialchars($milestone['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($milestone['title']); ?>" 
                                                 class="max-w-xs rounded-lg shadow-sm">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($wishes)): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-2xl font-bold">Wishes & Messages</h2>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($wishes as $wish): ?>
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <div class="flex items-center mb-4">
                                    <div class="flex-shrink-0 bg-purple-100 text-purple-600 rounded-full w-10 h-10 flex items-center justify-center mr-3">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold"><?php echo htmlspecialchars($wish['sender_name']); ?></h4>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($wish['relationship']); ?></p>
                                    </div>
                                </div>
                                <blockquote class="text-gray-700 italic pl-4 border-l-4 border-purple-200">
                                    "<?php echo nl2br(htmlspecialchars($wish['message'])); ?>"
                                </blockquote>
                                <p class="text-xs text-gray-500 mt-4">
                                    <?php echo date('F j, Y', strtotime($wish['created_at'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
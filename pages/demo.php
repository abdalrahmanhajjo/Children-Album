<?php
// demo.php — Public, safe demo of a child's album (no login required)
require_once '../includes/config.php';

// Set page meta
$page_title = "Live Demo — Children Album";
$meta_description = "Explore a live demo of Children Album. See galleries, milestones, and wishes in a safe, public demo — no account required.";

// Demo mode flag
$is_demo = true;

// Helper: SVG data-URI placeholder generator
function svg_placeholder($w, $h, $label = 'Memory', $from = '#fde68a', $to = '#fca5a5') {
    $w = (int)$w; $h = (int)$h;
    $label_esc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $svg = "
<svg xmlns='http://www.w3.org/2000/svg' width='{$w}' height='{$h}' viewBox='0 0 {$w} {$h}'>
  <defs>
    <linearGradient id='g' x1='0' x2='1' y1='0' y2='1'>
      <stop offset='0%' stop-color='{$from}'/>
      <stop offset='100%' stop-color='{$to}'/>
    </linearGradient>
  </defs>
  <rect width='100%' height='100%' fill='url(#g)'/>
  <g fill='rgba(255,255,255,0.85)' text-anchor='middle'>
    <text x='".($w/2)."' y='".($h/2)."' font-size='".max(18, min(32, $w/12))."' font-family='system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif'>{$label_esc}</text>
  </g>
</svg>";
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

// Demo child
$child = [
    'child_id'       => 0,
    'user_id'        => 0,
    'name'           => 'Ava Grace',
    'nickname'       => 'Avi',
    'gender'         => 'girl',
    'birth_date'     => '2023-04-14',
    'birth_weight'   => '3.2',
    'birth_length'   => '50',
    'meaning'        => "Ava means 'life', and Grace reflects elegance and kindness. A little star lighting up every room.",
    'profile_picture'=> null, // keep null to show themed avatar
    'cover_picture'  => svg_placeholder(1200, 600, "Ava's Story", '#fbcfe8', '#f472b6'),
];

// Themes
$themes = [
    'girl' => [
        'primary'   => '#E91E63',
        'secondary' => '#F8BBD0',
        'accent'    => '#C2185B'
    ],
    'boy' => [
        'primary'   => '#2196F3',
        'secondary' => '#BBDEFB',
        'accent'    => '#1976D2'
    ]
];
$gender = $child['gender'] ?? 'girl';
$theme_colors = $themes[$gender];

// Demo gallery
$gallery = [
    [
        'gallery_id'  => 1,
        'image_path'  => svg_placeholder(800, 600, 'First Smile', '#fbcfe8', '#f472b6'),
        'title'       => 'First Smile',
        'date_taken'  => '2023-06-01',
        'description' => 'A tiny grin that melted everyone’s heart.'
    ],
    [
        'gallery_id'  => 2,
        'image_path'  => svg_placeholder(800, 600, 'Playtime', '#a7f3d0', '#60a5fa'),
        'title'       => 'Playtime Joy',
        'date_taken'  => '2023-08-15',
        'description' => 'Giggles and blocks — a perfect afternoon.'
    ],
    [
        'gallery_id'  => 3,
        'image_path'  => svg_placeholder(800, 600, 'Family Time', '#fde68a', '#fca5a5'),
        'title'       => 'Family Get‑Together',
        'date_taken'  => '2023-12-24',
        'description' => 'Warm hugs and festive lights.'
    ],
    [
        'gallery_id'  => 4,
        'image_path'  => svg_placeholder(800, 600, 'First Steps', '#93c5fd', '#f5d0fe'),
        'title'       => 'Little Steps',
        'date_taken'  => '2024-02-10',
        'description' => 'One step, two steps… and a happy squeal!'
    ],
    [
        'gallery_id'  => 5,
        'image_path'  => svg_placeholder(800, 600, 'Bedtime Story', '#fcd34d', '#34d399'),
        'title'       => 'Bedtime Story',
        'date_taken'  => '2024-03-08',
        'description' => 'Dreams begin with a good story.'
    ],
    [
        'gallery_id'  => 6,
        'image_path'  => svg_placeholder(800, 600, 'Splash Day', '#fca5a5', '#93c5fd'),
        'title'       => 'Splash Day',
        'date_taken'  => '2024-05-20',
        'description' => 'Water, sunshine, and endless laughter.'
    ],
];

// Demo milestones
$milestones = [
    [
        'milestone_id'  => 101,
        'title'         => 'First Word',
        'date_achieved' => '2023-09-22',
        'description'   => 'Said “Mama” for the first time — instant happy tears.',
        'image_path'    => svg_placeholder(800, 450, 'First Word', '#fde68a', '#a7f3d0')
    ],
    [
        'milestone_id'  => 102,
        'title'         => 'First Tooth',
        'date_achieved' => '2023-11-05',
        'description'   => 'A tiny tooth, a big smile.',
        'image_path'    => null
    ],
    [
        'milestone_id'  => 103,
        'title'         => 'First Steps',
        'date_achieved' => '2024-02-10',
        'description'   => 'Wobbly and wonderful!',
        'image_path'    => svg_placeholder(800, 450, 'First Steps', '#fbcfe8', '#93c5fd')
    ],
];

// Demo wishes (persist across refresh within the session during the visit)
if (!isset($_SESSION['demo_wishes'])) {
    $_SESSION['demo_wishes'] = [
        [
            'sender_name' => 'Grandma Rose',
            'relationship'=> 'Family',
            'message'     => 'Grow healthy and happy, little sunshine! We love you to the moon and back.',
            'created_at'  => '2024-01-01',
        ],
        [
            'sender_name' => 'Uncle Max',
            'relationship'=> 'Family',
            'message'     => 'Can’t wait for our next playdate. Proud of you!',
            'created_at'  => '2024-03-15',
        ],
        [
            'sender_name' => 'Aunt Mia',
            'relationship'=> 'Family',
            'message'     => 'Your laughter makes every day brighter.',
            'created_at'  => '2024-05-20',
        ],
    ];
}

// Handle demo form submissions
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add wish (allowed in demo — stored in session only)
    if (isset($_POST['add_wish'])) {
        $sender_name = sanitize_input($_POST['sender_name'] ?? '');
        $message     = sanitize_input($_POST['message'] ?? '');
        $relationship= sanitize_input($_POST['relationship'] ?? 'Friend');

        if ($sender_name && $message) {
            $_SESSION['demo_wishes'][] = [
                'sender_name' => $sender_name,
                'relationship'=> $relationship ?: 'Friend',
                'message'     => $message,
                'created_at'  => date('Y-m-d'),
            ];
            $success = "Your wish has been added (demo). It will persist for this session only.";
        } else {
            $error = "Please fill in all required fields.";
        }
    }

    // Prevent destructive actions in demo
    if (isset($_POST['delete_photo']) || isset($_POST['delete_milestone'])) {
        $error = "This action is disabled in demo mode.";
    }
}

$wishes = $_SESSION['demo_wishes'];

// Header
require_once '../includes/header.php';

// Flash messages
if (!empty($success)) {
    echo '<div class="container py-4"><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">'.$success.'</div></div>';
}
if (!empty($error)) {
    echo '<div class="container py-4"><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">'.$error.'</div></div>';
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
        position: relative;
        overflow: hidden;
    }
    .hero-section::before {
        content:'';
        position:absolute;
        inset:0;
        background-image: url('<?php echo $child['cover_picture']; ?>');
        background-size: cover;
        background-position: center;
        opacity: 0.22;
        mix-blend-mode: overlay;
    }

    .btn-primary {
        background-color: <?php echo $theme_colors['primary']; ?>;
        color: white;
    }
    .btn-primary:hover {
        background-color: <?php echo $theme_colors['accent']; ?>;
    }
    .btn-outline {
        border: 2px solid <?php echo $theme_colors['primary']; ?>;
        color: <?php echo $theme_colors['primary']; ?>;
        background-color: transparent;
    }
    .btn-outline:hover {
        background-color: <?php echo $theme_colors['primary']; ?>;
        color: white;
    }

    .floating { animation: floating 3s ease-in-out infinite; }
    @keyframes floating { 0% { transform: translateY(0px);} 50% { transform: translateY(-15px);} 100% { transform: translateY(0px);} }

    /* Action buttons */
    .action-btn { transition: all 0.3s ease; opacity: 0; }
    .gallery-item:hover .action-btn, .milestone-item:hover .action-btn { opacity: 1; }

    /* Demo badge */
    .demo-badge {
        position: absolute; top: 16px; right: 16px;
        background: rgba(255,255,255,0.9);
        color: <?php echo $theme_colors['primary']; ?>;
        border: 1px solid rgba(0,0,0,0.06);
        padding: 6px 12px; border-radius: 9999px; font-weight: 700; font-size: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
</style>

<!-- Hero Section -->
<section class="hero-section min-h-[420px] flex items-center pt-16 relative">
    <div class="absolute inset-0 bg-black/25"></div>
    <div class="demo-badge">Live Demo</div>
    <div class="container relative z-10">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right">
                <h1 class="text-4xl md:text-5xl font-bold text-white heading-font mb-3">
                    <?php echo htmlspecialchars($child['name']); ?>’s Album
                </h1>
                <h2 class="text-2xl text-white mb-4">
                    Our precious little <span class="text-yellow-300">"<?php echo htmlspecialchars($child['nickname'] ?: $child['name']); ?>"</span>
                </h2>
                <p class="text-white/95 text-lg mb-6">
                    <?php echo calculate_age($child['birth_date']); ?> old • Born <?php echo date('F j, Y', strtotime($child['birth_date'])); ?>
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="#gallery" class="px-6 py-3 bg-white theme-primary rounded-full font-semibold hover:bg-yellow-300 transition-all">
                        View Photos <i class="fas fa-images ml-2"></i>
                    </a>
                    <a href="#wishes" class="px-6 py-3 border-2 border-white text-white rounded-full font-semibold hover:bg-white hover:text-gray-900 transition-all">
                        Send Wishes <i class="fas fa-heart ml-2"></i>
                    </a>
                </div>
                <p class="text-white/90 text-sm mt-4">
                    Tip: This is a demo. Add a wish below and see it appear instantly — no account needed.
                </p>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="relative text-center">
                    <?php if (!empty($child['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($child['profile_picture']); ?>" 
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
            <div class="col-lg-5 mb-8 mb-lg-0" data-aos="fade-up">
                <div class="relative">
                    <?php if ($child['cover_picture']): ?>
                        <img src="<?php echo $child['cover_picture']; ?>" 
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
            <div class="col-lg-7" data-aos="fade-up" data-aos-delay="100">
                <div class="ps-lg-10">
                    <h3 class="text-3xl font-bold text-gray-800 heading-font mb-4">
                        About <span class="theme-primary"><?php echo htmlspecialchars($child['nickname'] ?: $child['name']); ?></span>
                    </h3>
                    
                    <?php if ($child['meaning']): ?>
                        <p class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($child['meaning'])); ?></p>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <div class="theme-primary text-2xl mb-2"><i class="fas fa-birthday-cake"></i></div>
                            <h4 class="font-bold text-gray-800">Birth Date</h4>
                            <p class="text-sm text-gray-600"><?php echo date('F j, Y', strtotime($child['birth_date'])); ?></p>
                        </div>
                        
                        <?php if ($child['birth_weight']): ?>
                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="theme-primary text-2xl mb-2"><i class="fas fa-weight"></i></div>
                                <h4 class="font-bold text-gray-800">Birth Weight</h4>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($child['birth_weight']); ?> kg</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($child['birth_length']): ?>
                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="theme-primary text-2xl mb-2"><i class="fas fa-ruler-vertical"></i></div>
                                <h4 class="font-bold text-gray-800">Birth Length</h4>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($child['birth_length']); ?> cm</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-wrap gap-3">
                        <a href="#" data-demo-disabled class="px-6 py-3 btn-primary rounded-full font-semibold transition-all">
                            Add Photo <i class="fas fa-camera ml-2"></i>
                        </a>
                        <a href="#" data-demo-disabled class="px-6 py-3 btn-outline rounded-full font-semibold transition-all">
                            Add Milestone <i class="fas fa-star ml-2"></i>
                        </a>
                        <a href="../pages/register.php" class="px-6 py-3 btn-outline rounded-full font-semibold transition-all">
                            Create your free album <i class="fas fa-arrow-right ml-2"></i>
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
            <a href="#" data-demo-disabled class="px-4 py-2 btn-primary rounded-lg transition-all">
                <i class="fas fa-plus mr-2"></i> Add Photo
            </a>
        </div>
        
        <?php if (empty($gallery)): ?>
            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                <div class="text-5xl theme-primary mb-4"><i class="fas fa-images"></i></div>
                <h3 class="text-xl font-bold mb-2">No Photos Yet</h3>
                <p class="text-gray-600 mb-4">Start building your child's photo gallery by adding their first photo.</p>
                <a href="#" data-demo-disabled class="inline-block px-6 py-3 btn-primary rounded-lg transition-all">
                    Add First Photo <i class="fas fa-camera ml-2"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($gallery as $photo): 
                    $src = (strpos($photo['image_path'], 'data:') === 0) ? $photo['image_path'] : '../uploads/' . htmlspecialchars($photo['image_path']);
                ?>
                    <div class="gallery-item overflow-hidden rounded-xl shadow-md hover:shadow-xl transition-all relative">
                        <img src="<?php echo $src; ?>" 
                             alt="<?php echo htmlspecialchars($photo['title'] ?: $child['name']); ?>" 
                             class="w-full h-64 object-cover transition-transform duration-300 hover:scale-105">
                             
                        <!-- Edit/Delete buttons (disabled in demo) -->
                        <div class="absolute top-4 right-4 flex gap-2 action-btn">
                            <button type="button" data-demo-disabled class="p-3 bg-white rounded-full shadow-md hover:bg-gray-100 transition-all cursor-not-allowed opacity-80" title="Edit (demo)">
                                <i class="fas fa-edit theme-primary"></i>
                            </button>
                            <button type="button" data-demo-disabled class="p-3 bg-white rounded-full shadow-md hover:bg-gray-100 transition-all cursor-not-allowed opacity-80" title="Delete (demo)">
                                <i class="fas fa-trash text-red-600"></i>
                            </button>
                        </div>
                        
                        <div class="p-4 bg-white">
                            <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($photo['title'] ?: 'Untitled'); ?></h4>
                            <?php if (!empty($photo['date_taken'])): ?>
                                <p class="text-sm text-gray-600"><?php echo date('F j, Y', strtotime($photo['date_taken'])); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($photo['description'])): ?>
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
            <a href="#" data-demo-disabled class="px-4 py-2 btn-primary rounded-lg transition-all">
                <i class="fas fa-plus mr-2"></i> Add Milestone
            </a>
        </div>
        
        <?php if (empty($milestones)): ?>
            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                <div class="text-5xl theme-primary mb-4"><i class="fas fa-star"></i></div>
                <h3 class="text-xl font-bold mb-2">No Milestones Yet</h3>
                <p class="text-gray-600 mb-4">Document your child's important achievements and special moments.</p>
                <a href="#" data-demo-disabled class="inline-block px-6 py-3 btn-primary rounded-lg transition-all">
                    Add First Milestone <i class="fas fa-star ml-2"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($milestones as $milestone):
                    $ms_src = (!empty($milestone['image_path']) && strpos($milestone['image_path'], 'data:') === 0)
                        ? $milestone['image_path'] : (!empty($milestone['image_path']) ? '../uploads/' . htmlspecialchars($milestone['image_path']) : null);
                ?>
                    <div class="milestone-item bg-white rounded-xl shadow-md p-6 relative">
                        <!-- Edit/Delete buttons (disabled in demo) -->
                        <div class="absolute top-4 right-4 flex gap-2 action-btn">
                            <button type="button" data-demo-disabled class="p-2 bg-white rounded-full shadow-md hover:bg-gray-100 transition-all cursor-not-allowed opacity-80" title="Edit (demo)">
                                <i class="fas fa-edit theme-primary"></i>
                            </button>
                            <button type="button" data-demo-disabled class="p-2 bg-white rounded-full shadow-md hover:bg-gray-100 transition-all cursor-not-allowed opacity-80" title="Delete (demo)">
                                <i class="fas fa-trash text-red-600"></i>
                            </button>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0 theme-secondary-bg theme-primary rounded-full w-12 h-12 flex items-center justify-center">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="ml-4 w-full">
                                <div class="flex justify-between items-start flex-wrap gap-2">
                                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($milestone['title']); ?></h3>
                                    <span class="text-sm text-gray-500"><?php echo date('F j, Y', strtotime($milestone['date_achieved'])); ?></span>
                                </div>
                                <?php if (!empty($milestone['description'])): ?>
                                    <p class="text-gray-600 mt-2"><?php echo nl2br(htmlspecialchars($milestone['description'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($ms_src)): ?>
                                    <div class="mt-4">
                                        <img src="<?php echo $ms_src; ?>" 
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
            <p class="text-gray-600 max-w-2xl mx-auto">Share your love and blessings for <?php echo htmlspecialchars($child['nickname'] ?: $child['name']); ?>. In this demo, wishes are stored for your current session only.</p>
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
                        <div class="text-5xl theme-primary mb-4"><i class="fas fa-heart"></i></div>
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
                                    <p class="text-gray-600 text-sm mb-1">
                                        <?php echo htmlspecialchars($wish['relationship'] ?: 'Friend'); ?> • 
                                        <?php echo date('F j, Y', strtotime($wish['created_at'])); ?>
                                    </p>
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

<!-- CTA Section -->
<section class="py-16 bg-gradient-to-r from-pink-500 to-yellow-500 text-white">
    <div class="container">
        <div class="max-w-3xl mx-auto text-center">
            <h3 class="text-3xl font-bold mb-4">Like what you see?</h3>
            <p class="text-lg mb-6">Create your free, private album in minutes. Unlimited features — free for all.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="../pages/register.php" class="px-6 py-3 bg-white text-pink-600 rounded-full font-bold hover:bg-gray-100 transition-all shadow">
                    Create Free Account
                </a>
                <a href="../index.php#features" class="px-6 py-3 border-2 border-white text-white rounded-full font-bold hover:bg-white/20 transition-all">
                    Explore Features
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// Disable destructive/creation actions in demo (except adding wishes)
document.querySelectorAll('[data-demo-disabled]').forEach(el => {
    el.addEventListener('click', (e) => {
        e.preventDefault();
        alert('This action is disabled in demo mode. Create your free account to use it.');
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
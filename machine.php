<?php
/**
 * MACHINE.PHP - Equipment Directory
 * 
 * Displays all available gym machines with links to detailed pages
 * Each machine card shows:
 * - Machine image
 * - Machine name
 * - Brief description of exercises
 * 
 * Security: Public access - No login required
 */

// Set page-specific variables
$page_title = 'Machines — FIT-STOP';

// Custom CSS for this page
$custom_css = <<<CSS
<style>
    /* Page Hero Spacing */
    .page-hero {
        padding-top: 96px;
        padding-bottom: 28px;
    }
    
    /* Machine Card Styling */
    .machine-card {
        background: rgba(255,255,255,0.03);
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.03);
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    /* Hover Effect - Lifts card and adds shadow */
    .machine-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.3);
    }
    
    /* Machine Image */
    .machine-card img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        display: block;
    }
    
    /* Card Body */
    .machine-card .body {
        padding: 16px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
</style>
CSS;

// Include header
include('includes/header.php');

/**
 * MACHINE DATA ARRAY
 * 
 * Organized list of all gym machines
 * Structure:
 * - name: Display name
 * - file: PHP file in machines/ folder
 * - image: Image path
 * - description: Brief exercise description
 */
$machines = [
    [
        'name' => 'Smith Machine',
        'file' => 'SmithMachine.php',
        'image' => 'images/smith-pic.jpg',
        'desc' => 'Smith Squat, Smith Bench Press, Smith Shoulder Press, Smith Row'
    ],
    [
        'name' => 'Lat Pulldown / Seated Cable Row',
        'file' => 'LatPulldownSeatedCableRow.php',
        'image' => 'images/LatPulldown-CableRow.png',
        'desc' => 'Lat Pulldown, Seated Cable Row'
    ],
    [
        'name' => 'Shoulder Press',
        'file' => 'ShoulderPress.php',
        'image' => 'images/ShoulderPress.jpg',
        'desc' => 'Shoulder Press'
    ],
    [
        'name' => 'Seated Chest Press',
        'file' => 'SeatedChestPress.php',
        'image' => 'images/SeatedChestPress.png',
        'desc' => 'Chest Press'
    ],
    [
        'name' => 'Pec Deck Fly / Rear Delt Fly',
        'file' => 'PecDeckFlyRearDelt.php',
        'image' => 'images/PeckDeckFly.png',
        'desc' => 'Pec Deck Fly, Rear Delt Fly'
    ],
    [
        'name' => 'Decline Chest Press',
        'file' => 'DeclineChestPress.php',
        'image' => 'images/DeclineChestPress.jpg',
        'desc' => 'Decline Press'
    ],
    [
        'name' => 'Multi Press Machine',
        'file' => 'MultiPress.php',
        'image' => 'images/MultiPress.png',
        'desc' => 'Shoulder Press, Flat Chest Press, Incline Chest Press'
    ],
    [
        'name' => 'Leg Press / Hack Squat',
        'file' => 'LegPressHackSquat.php',
        'image' => 'images/HackSquat-LegPress.png',
        'desc' => 'Leg Press, Hack Squat'
    ],
    [
        'name' => 'Cable Machine',
        'file' => 'CableMachine.php',
        'image' => 'images/CableMachine-Pullups.png',
        'desc' => 'Cable Crossover, Tricep Pushdown, Cable Bicep Curl, Lateral Raise'
    ],
    [
        'name' => 'Pullup Station',
        'file' => 'PullupStation.php',
        'image' => 'images/PullUpBar.png',
        'desc' => 'Pullups, Chin-ups'
    ],
    [
        'name' => 'Treadmill',
        'file' => 'Treadmill.php',
        'image' => 'images/Treadmill.png',
        'desc' => 'Walking, Jogging, Running, Incline Walking'
    ],
    [
        'name' => 'Leg Extension / Hamstring Curl Machine',
        'file' => 'LegExtension.php',
        'image' => 'images/LegExtension-HamstringCurl.png',
        'desc' => 'Leg Extension, Single-Leg Extension, Seated Leg Curl, Single-Leg Curl'
    ],
    [
        'name' => 'Dips / Leg Raise Station',
        'file' => 'Dips.php',
        'image' => 'images/DIPS.png',
        'desc' => 'Parallel Bar Dips, Vertical Knee Raise, Straight-Leg Raise'
    ],
    [
        'name' => 'Machine Row',
        'file' => 'MachineRow.php',
        'image' => 'images/MachineRow.png',
        'desc' => 'Seated Machine Row, Wide-Grip Row, Close-Grip Row, Single-Arm Row'
    ],
    [
        'name' => 'Preacher Curl',
        'file' => 'PreacherCurl.php',
        'image' => 'images/PreacherCurl.png',
        'desc' => 'Preacher Curl'
    ]
];
?>

    <!-- PAGE HERO -->
    <section class="page-hero">
        <div class="container">
            <div class="row align-items-end">
                <div class="col-md-8">
                    <h1 class="mb-0">Machines</h1>
                    <p class="text-muted">Browse machines and view exercise galleries and instructions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- MAIN CONTENT -->
    <main class="container mb-5">
        <!-- Breadcrumb Navigation -->
        <div class="breadcrumbs mb-4">
            <a href="index.php">Home</a> › 
            <a href="equipment.php">Equipment</a> › 
            Machines
        </div>

        <!-- Machine Grid -->
        <div class="row g-4">
            <?php 
            foreach ($machines as $machine): 
            ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a class="machine-card" href="machines/<?php echo htmlspecialchars($machine['file']); ?>">
                        <img src="<?php echo htmlspecialchars($machine['image']); ?>" 
                             alt="<?php echo htmlspecialchars($machine['name']); ?>">
                        <div class="body">
                            <h5 class="mb-1"><?php echo htmlspecialchars($machine['name']); ?></h5>
                            <div class="text-muted small"><?php echo htmlspecialchars($machine['desc']); ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

<?php
// Include footer
include('includes/footer.php');
?>

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
 * - file: HTML file in machines/ folder
 * - image: Image path
 * - description: Brief exercise description
 */
$machines = [
    [
        'name' => 'Smith Machine',
        'file' => 'SmithMachine.php',
        'image' => 'images/smith-pic.jpg',
        'desc' => 'Bench press, shoulder press, guided bar path'
    ],
    [
        'name' => 'Lat Pulldown/Seated Cable Row',
        'file' => 'LatPulldownSeatedCableRow.php',
        'image' => 'images/LatPulldown-CableRow.png',
        'desc' => 'Back width, lats, rowing movements'
    ],
    [
        'name' => 'Shoulder Press',
        'file' => 'ShoulderPress.php',
        'image' => 'images/ShoulderPress.jpg',
        'desc' => 'Deltoids, overhead pressing'
    ],
    [
        'name' => 'Seated Chest Press',
        'file' => 'SeatedChestPress.php',
        'image' => 'images/SeatedChestPress.png',
        'desc' => 'Chest, pectorals, pressing movements'
    ],
    [
        'name' => 'Pec Deck Fly/Rear Delt Fly',
        'file' => 'PecDeckFlyRearDelt.php',
        'image' => 'images/PeckDeckFly.png',
        'desc' => 'Chest flys, rear deltoid isolation'
    ],
    [
        'name' => 'Decline Chest Press',
        'file' => 'DeclineChestPress.php',
        'image' => 'images/DeclineChestPress.jpg',
        'desc' => 'Lower chest, decline pressing'
    ],
    [
        'name' => 'Multi Press Machine',
        'file' => 'MultiPress.php',
        'image' => 'images/MultiPress.png',
        'desc' => 'Chest & shoulder, multi-angle press'
    ],
    [
        'name' => 'Leg Press/Hack Squat',
        'file' => 'LegPressHackSquat.php',
        'image' => 'images/HackSquat-LegPress.png',
        'desc' => 'Quads, glutes, lower body power'
    ],
    [
        'name' => 'Cable Machine',
        'file' => 'CableMachine.php',
        'image' => 'images/CableMachine-Pullups.png',
        'desc' => 'Multi-function, cables, various exercises'
    ],
    [
        'name' => 'Pullup Station',
        'file' => 'PullupStation.php',
        'image' => 'images/PullUpBar.png',
        'desc' => 'Pull-ups, chin-ups, bodyweight training'
    ],
    [
        'name' => 'Treadmill',
        'file' => 'Treadmill.php',
        'image' => 'images/Treadmill.png',
        'desc' => 'Cardiovascular exercise, running, walking'
    ],
    [
        'name' => 'Leg Extension Station',
        'file' => 'LegExtension.php',
        'image' => 'images/LegExtension-HamstringCurl.png',
        'desc' => 'Leg extensions, hamstring curls, quad isolation'
    ],
    [
        'name' => 'Dips Station',
        'file' => 'Dips.php',
        'image' => 'images/DIPS.png',
        'desc' => 'Triceps, chest, shoulder exercises'
    ],
    [
        'name' => 'Preacher Curl Station',
        'file' => 'PreacherCurl.php',
        'image' => 'images/PreacherCurl.png',
        'desc' => 'Bicep curls, arm isolation'
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

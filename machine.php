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
        'file' => 'SmithMachine.html',
        'image' => 'images/smith-pic.jpg',
        'desc' => 'Bench press, shoulder press, guided bar path'
    ],
    [
        'name' => 'Lat Pulldown/Seated Cable Row',
        'file' => 'LatPulldownSeatedCableRow.html',
        'image' => 'images/Fitstop.png',
        'desc' => 'Back width, lats, rowing movements'
    ],
    [
        'name' => 'Shoulder Press',
        'file' => 'ShoulderPress.html',
        'image' => 'images/Fitstop.png',
        'desc' => 'Deltoids, overhead pressing'
    ],
    [
        'name' => 'Seated Chest Press',
        'file' => 'SeatedChestPress.html',
        'image' => 'images/Fitstop.png',
        'desc' => 'Chest, pectorals, pressing movements'
    ],
    [
        'name' => 'Pec Deck Fly/Rear Delt Fly',
        'file' => 'PecDeckFlyRearDelt.html',
        'image' => 'images/Fitstop.png',
        'desc' => 'Chest flys, rear deltoid isolation'
    ],
    [
        'name' => 'Decline Chest Press',
        'file' => 'DeclineChestPress.html',
        'image' => 'images/Fitstop.png',
        'desc' => 'Lower chest, decline pressing'
    ],
    [
        'name' => 'Multi Press Machine',
        'file' => 'MultiPress.html',
        'image' => 'images/Fitstop.png',
        'desc' => 'Chest & shoulder, multi-angle press'
    ],
    [
        'name' => 'Leg Press/Hack Squat',
        'file' => 'LegPressHackSquat.html',
        'image' => 'images/Fitstop.png',
        'desc' => 'Quads, glutes, lower body power'
    ],
    [
        'name' => 'Cable Machine',
        'file' => 'CableMachine.html',
        'image' => 'images/Fitstop.png',
        'desc' => 'Multi-function, cables, various exercises'
    ],
    [
        'name' => 'Pullup Station',
        'file' => 'PullupStation.html',
        'image' => 'images/Fitstop.png',
        'desc' => 'Pull-ups, chin-ups, bodyweight training'
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
            /**
             * LOOP THROUGH MACHINES
             * 
             * Dynamically generates machine cards from $machines array
             * Benefits:
             * - Easy to add/remove machines
             * - Consistent styling
             * - Maintained in one place
             */
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

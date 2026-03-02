<?php
/**
 * ABOUTUS.PHP - Team & Company Information
 * 
 * Displays:
 * - Company mission/vision
 * - Founder/owner profile
 * - Staff member profiles
 * - Team statistics
 * 
 * Security: Public access - No login required
 */

// Set page-specific variables
$page_title = 'About Us | FIT-STOP';

// Custom CSS for profile images
$custom_css = <<<CSS
<style>
    /* Profile Image Container */
    .profile-img-container {
        width: 120px;
        height: 120px;
        margin: 0 auto 1.5rem;
        position: relative;
    }    
    /* Profile Image Styling */
    .profile-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--hazard-yellow);
        background-color: #222;
    }

    /* Team Card Hover Effect */
    .team-card {
        background-color: var(--bg-card);
        border: 1px solid #333;
        transition: transform 0.3s ease;
    }

    .team-card:hover {
        transform: translateY(-5px);
        border-color: var(--hazard-yellow);
    }
</style>
CSS;

// Include header
include('includes/header.php');

/**
 * TEAM DATA STRUCTURE
 * 
 * Organized array for easy maintenance
 * Each member has: name, icon, description
 */
$founder = [
    'name' => 'ERIC ENRIQUEZ',
    'title' => 'FOUNDER & HEAD COACH',
    'bio' => '"Lorem, ipsum dolor sit amet consectetur adipisicing elit. Odit, quod! Consequatur optio vitae quasi. Velit consequatur at, quod eaque ducimus quibusdam est excepturi iure enim fugiat, consectetur nisi, nostrum maxime!."',
    'socials' => [
        'instagram' => '#',
        'linkedin' => '#'
    ]
];

$staff_members = [
    [
        'name' => 'Jhon Dela Torre',
        'icon' => 'fa-dumbbell',
        'bio' => 'Lorem ipsum dolor sit, amet consectetur adipisicing elit. Nostrum, sint doloribus in impedit sequi neque necessitatibus possimus voluptas odit expedita est quidem ut autem beatae ab dolor eaque dicta repellendus!'
    ],
    [
        'name' => 'Dennsy Vega',
        'icon' => 'fa-heart-pulse',
        'bio' => 'Lorem ipsum dolor sit, amet consectetur adipisicing elit. Nostrum, sint doloribus in impedit sequi neque necessitatibus possimus voluptas odit expedita est quidem ut autem beatae ab dolor eaque dicta repellendus!'
    ],
    [
        'name' => 'Kurt Ramos',
        'icon' => 'fa-clipboard-check',
        'bio' => 'Lorem ipsum dolor sit, amet consectetur adipisicing elit. Nostrum, sint doloribus in impedit sequi neque necessitatibus possimus voluptas odit expedita est quidem ut autem beatae ab dolor eaque dicta repellendus!'
    ],
    [
        'name' => 'Celerio Raymund',
        'icon' => 'fa-nutrition-leaf',
        'bio' => 'Lorem ipsum dolor sit, amet consectetur adipisicing elit. Nostrum, sint doloribus in impedit sequi neque necessitatibus possimus voluptas odit expedita est quidem ut autem beatae ab dolor eaque dicta repellendus!'
    ],
    [
        'name' => 'Charles Carillo',
        'icon' => 'fa-headset',
        'bio' => 'Lorem ipsum dolor sit, amet consectetur adipisicing elit. Nostrum, sint doloribus in impedit sequi neque necessitatibus possimus voluptas odit expedita est quidem ut autem beatae ab dolor eaque dicta repellendus!'
    ]
];
?>

    <!-- HERO SECTION -->
    <section class="hero-section position-relative" style="display: flex; align-items: center; overflow: hidden;">
        <!-- Background Icon (decorative) -->
        <div class="position-absolute w-100 h-100" style="opacity: 0.03; z-index: 0;">
            <i class="fa-solid fa-users position-absolute" style="font-size: 20rem; top: 50%; left: 50%; transform: translate(-50%, -50%);"></i>
        </div>
        
        <div class="container text-center position-relative" style="z-index: 1;">
            <div class="mb-4">
                <i class="fa-solid fa-people-group fa-3x text-hazard mb-3 d-block"></i>
            </div>
            
            <h1 class="display-3 fw-bold mb-3">
                MEET THE <span class="text-hazard">CREW</span>
            </h1>
            
            <!-- Decorative Divider -->
            <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
                <div style="width: 60px; height: 2px; background: var(--hazard-yellow);"></div>
                <i class="fa-solid fa-dumbbell text-hazard"></i>
                <div style="width: 60px; height: 2px; background: var(--hazard-yellow);"></div>
            </div>
            
            <p class="lead text-light mx-auto" style="max-width: 700px; font-size: 1.25rem; line-height: 1.8;">
                We are a <strong class="text-white">small, dedicated team</strong> committed to merging 
                <span class="text-hazard">old-school grit</span> with modern tracking technology.
            </p>
            
            <!-- Team Statistics -->
            <div class="d-flex justify-content-center gap-4 mt-5">
                <div class="text-center">
                    <div class="fs-2 fw-bold text-hazard"><?php echo count($staff_members); ?>+</div>
                    <div class="text-muted small">TEAM MEMBERS</div>
                </div>
                <div class="text-center border-start border-secondary ps-4">
                    <div class="fs-2 fw-bold text-hazard">100%</div>
                    <div class="text-muted small">COMMITTED</div>
                </div>
            </div>
        </div>
    </section>

    <div class="hazard-stripes"></div>

    <!-- TEAM SECTION -->
    <section class="py-5">
        <div class="container py-5">
            <!-- FOUNDER PROFILE -->
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8">
                    <div class="text-center mb-4">
                        <h2 class="text-white mb-2">THE <span class="text-hazard">HEAD</span></h2>
                    </div>
                    
                    <div class="pricing-card p-4">
                        <div class="row align-items-center">
                            <!-- Profile Image -->
                            <div class="col-md-5 text-center">
                                <div class="profile-img-container" style="width: 200px; height: 200px;">
                                    <div class="profile-img d-flex align-items-center justify-content-center bg-black">
                                        <i class="fa-solid fa-user-secret fa-4x text-muted"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Profile Info -->
                            <div class="col-md-7 text-center text-md-start">
                                <h3 class="text-white mb-1"><?php echo htmlspecialchars($founder['name']); ?></h3>
                                <p class="text-hazard fw-bold mb-3"><?php echo htmlspecialchars($founder['title']); ?></p>
                                <p class="text-muted small">
                                    <?php echo htmlspecialchars($founder['bio']); ?>
                                </p>
                                
                                <!-- Social Links -->
                                <div class="d-flex gap-2 justify-content-center justify-content-md-start mt-3">
                                    <a href="<?php echo htmlspecialchars($founder['socials']['instagram']); ?>" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-brands fa-instagram"></i>
                                    </a>
                                    <a href="<?php echo htmlspecialchars($founder['socials']['linkedin']); ?>" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-brands fa-linkedin"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STAFF GRID HEADER -->
            <div class="row mb-5 text-center">
                <div class="col-12">
                    <h3 class="text-white">OUR <span class="text-hazard">STAFF</span></h3>
                </div>
            </div>

            <!-- STAFF MEMBERS GRID -->
            <div class="row g-4 justify-content-center">
                <?php 
                /**
                 * LOOP THROUGH STAFF MEMBERS
                 * 
                 * Dynamically generates staff cards from $staff_members array
                 * Benefits:
                 * - Easy to add/remove members
                 * - Consistent styling
                 * - Single source of truth for team data
                 */
                foreach ($staff_members as $member): 
                ?>
                <div class="col-md-4 col-lg-4">
                    <div class="feature-card text-center h-100 p-4">
                        <!-- Icon Placeholder -->
                        <div class="profile-img-container">
                            <div class="profile-img d-flex align-items-center justify-content-center bg-black">
                                <i class="fa-solid <?php echo htmlspecialchars($member['icon']); ?> fa-2x text-muted"></i>
                            </div>
                        </div>
                        
                        <!-- Member Info -->
                        <h5 class="text-white"><?php echo htmlspecialchars($member['name']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($member['bio']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

<?php
// Include footer
include('includes/footer.php');
?>

<?php
$page_title = 'About Us | FIT-STOP';

$custom_css = <<<CSS
<style>
    .profile-img-container {
        width: 120px;
        height: 120px;
        margin: 0 auto 1.5rem;
        position: relative;
    }    
    .profile-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--hazard-yellow);
        background-color: #222;
    }
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

include('includes/header.php');

$founder = [
    'name'   => 'ERIC ENRIQUEZ',
    'image'  => 'founder.png',
    'title'  => 'FOUNDER & HEAD COACH',
    'socials' => [
        'facebook'  => 'https://web.facebook.com/FitstopMalabon',
        'instagram' => 'https://www.instagram.com/dfit.stop/',
    ]
];

$staff_members = [
    [
        'name'  => 'Jhon Dela Torre',
        'image' => 'jhon_dela_torre.jpg',
        'icon'  => 'fa-dumbbell',
    ],
    [
        'name'  => 'Dennsy Vega',
        'image' => 'dennsy_vega.png',
        'icon'  => 'fa-heart-pulse',
    ],  
    [
        'name'  => 'Celerio Raymund',
        'image' => 'celerio_raymund.jpg',
        'icon'  => 'fa-nutrition-leaf',
    ],
    [
        'name'  => 'Charles Carillo',
        'image' => 'charles_carillo.jpg',
        'icon'  => 'fa-headset',
    ]
];
?>

    <!-- HERO SECTION -->
    <section class="hero-section position-relative" style="display: flex; align-items: center; overflow: hidden;">
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
            
            <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
                <div style="width: 60px; height: 2px; background: var(--hazard-yellow);"></div>
                <i class="fa-solid fa-dumbbell text-hazard"></i>
                <div style="width: 60px; height: 2px; background: var(--hazard-yellow);"></div>
            </div>
            
            <p class="lead text-light mx-auto" style="max-width: 700px; font-size: 1.25rem; line-height: 1.8;">
                We are a <strong class="text-white">small, dedicated team</strong> committed to merging 
                <span class="text-hazard">old-school grit</span> with modern tracking technology.
            </p>
            
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
                            <div class="col-md-5 text-center">
                                <div class="profile-img-container" style="width: 200px; height: 200px;">
                                    <img src="images/<?php echo htmlspecialchars($founder['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($founder['name']); ?>" 
                                         class="profile-img">
                                </div>
                            </div>
                            
                            <div class="col-md-7 text-center text-md-start">
                                <h3 class="text-white mb-1"><?php echo htmlspecialchars($founder['name']); ?></h3>
                                <p class="text-hazard fw-bold mb-3"><?php echo htmlspecialchars($founder['title']); ?></p>
                                
                                <div class="d-flex gap-2 justify-content-center justify-content-md-start mt-3">
                                    <a href="<?php echo htmlspecialchars($founder['socials']['facebook']); ?>" 
                                       class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                                        <i class="fa-brands fa-facebook"></i>
                                    </a>
                                    <a href="<?php echo htmlspecialchars($founder['socials']['instagram']); ?>" 
                                       class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                                        <i class="fa-brands fa-instagram"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STAFF GRID -->
            <div class="row mb-5 text-center">
                <div class="col-12">
                    <h3 class="text-white">OUR <span class="text-hazard">STAFF</span></h3>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <?php foreach ($staff_members as $member): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="feature-card text-center h-100 p-4">
                        <div class="profile-img-container">
                            <img src="images/<?php echo htmlspecialchars($member['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($member['name']); ?>" 
                                 class="profile-img">
                        </div>
                        <h5 class="text-white mb-0"><?php echo htmlspecialchars($member['name']); ?></h5>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

<?php include('includes/footer.php'); ?>
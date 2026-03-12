<?php
/**
 * EQUIPMENT.PHP - Facilities & Equipment Overview
 * 
 * Comprehensive page displaying:
 * - All gym equipment categories
 * - Cardio machines
 * - Facility zones with capacity info
 * - Additional amenities
 * 
 * Security: Public access - No login required
 */

// Set page-specific variables
$page_title = 'FIT-STOP | Equipment & Facilities';

// Include header
include('includes/header.php');

/**
 * EQUIPMENT CATEGORIES DATA
 * 
 * Structured array for easy maintenance
 * Each category includes: icon, status, name, description, and metadata
 */
$strength_equipment = [
    [
        'icon' => 'fa-dumbbell',
        'status' => 'OPERATIONAL',
        'name' => 'FREE WEIGHT ZONE',
        'desc' => 'Complete dumbbell rack (5lb - 100lb), Olympic barbells, weight plates up to 45lb. Benches and squat racks available.',
        'meta' => 'CAPACITY: 8-12 users'
    ],
    [
        'icon' => 'fa-grip',
        'status' => 'OPERATIONAL',
        'name' => 'CABLE MACHINES',
        'desc' => 'Dual-stack cable crossover, lat pulldown stations, seated row machines. Adjustable resistance up to 200lb per stack.',
        'meta' => 'UNITS: 2 stations'
    ],
    [
        'icon' => 'fa-truck-moving',
        'status' => 'OPERATIONAL',
        'name' => 'PLATE-LOADED',
        'desc' => 'Hammer Strength equipment, leg press (1000lb capacity), hack squat, chest press. Built for power lifters.',
        'meta' => 'UNITS: 8 machines'
    ],
    [
        'icon' => 'fa-gears',
        'status' => 'OPERATIONAL',
        'name' => 'SMITH MACHINES',
        'desc' => 'Multi-angle Smith machines with safety catches. Ideal for controlled movements and solo training sessions.',
        'meta' => 'UNITS: 1 machine'
    ],
    [
        'icon' => 'fa-person-running',
        'status' => 'OPERATIONAL',
        'name' => 'SQUAT RACKS',
        'desc' => 'Power racks with adjustable safety bars, pull-up bars, and J-hooks. Olympic platform floors included.',
        'meta' => 'UNITS: 1 rack'
    ],
    [
        'icon' => 'fa-cog',
        'status' => 'OPERATIONAL',
        'name' => 'SPECIALIZED',
        'desc' => 'Preacher curl bench, hyperextension bench, decline/incline benches, dip station, and GHD machine.',
        'meta' => 'UNITS: 8+ pieces'
    ]
];

$cardio_equipment = [
    ['icon' => 'fa-person-running', 'name' => 'TREADMILL', 'count' => '1', 'desc' => 'Digital screens, incline control, preset programs'],
    ['icon' => 'fa-bicycle', 'name' => 'SPIN BIKES', 'count' => '2', 'desc' => 'Adjustable resistance, bottle holders, performance tracking'],
    ['icon' => 'fa-stairs', 'name' => 'ELLIPTICAL', 'count' => '1', 'desc' => 'Low-impact cardio, digital displays, heart rate sensors']
];

$facility_zones = [
    [
        'icon' => 'fa-layer-group',
        'name' => 'MAIN TRAINING FLOOR',
        'code' => 'ZONE-A',
        'desc' => 'The core of our facility. 2,500 sq ft of open training space with rubber flooring, mirrors, and optimal layout for strength training. Air-conditioned and well-ventilated.',
        'area' => '200+ ft',
        'capacity' => '20 users'
    ],
    [
        'icon' => 'fa-fire',
        'name' => 'FUNCTIONAL AREA',
        'code' => 'ZONE-B',
        'desc' => 'Dedicated space for functional training, HIIT, and CrossFit-style workouts. Equipped with battle ropes, kettlebells, medicine balls, plyo boxes, and slam balls.',
        'area' => '20 foot',
        'capacity' => '3 users'
    ],
    [
        'icon' => 'fa-people-group',
        'name' => 'LOCKER AREA',
        'code' => 'FACILITIES',
        'desc' => 'Clean, secure locker facility, changing area, and restroom.',
        'lockers' => '15 units'
    ]
];

$amenities = [
    ['icon' => 'fa-wifi', 'name' => 'FREE WIFI', 'desc' => 'Internet throughout facility'],
    ['icon' => 'fa-music', 'name' => 'SOUND SYSTEM', 'desc' => 'Curated workout playlists'],
    ['icon' => 'fa-shield-halved', 'name' => '24/7 SECURITY', 'desc' => 'CCTV monitoring & secure access']
];
?>

    <!-- HERO SECTION -->
    <section class="hero-section" style="background-image: linear-gradient(rgba(10,10,10,0.85), rgba(10,10,10,0.85)), url('images/Fitstop.png');">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 hero-content">
                    <div class="d-inline-block bg-warning text-black px-2 py-1 mb-3 fw-bold small brand-font">
                        <i class="fa-solid fa-warehouse me-1"></i> FACILITY OVERVIEW
                    </div>
                    <h1>
                        EQUIPMENT <span class="text-hazard">INVENTORY</span>
                    </h1>
                    <p>
                        Industrial-grade machinery. Professional zones. <br>
                        Everything you need to build strength the right way.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="machine.php" class="btn btn-hazard">VIEW MACHINES</a>
                        <a href="#zones" class="btn btn-outline-hazard">VIEW FACILITIES</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="hazard-stripes"></div>

    <!-- STRENGTH EQUIPMENT SECTION -->
    <section id="strength" class="py-5">
        <div class="container py-5">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="text-white">STRENGTH <span class="text-hazard">EQUIPMENT</span></h2>
                    <p class="text-muted">Heavy-duty machines and free weights for serious lifters</p>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($strength_equipment as $equipment): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <i class="fa-solid <?php echo $equipment['icon']; ?> feature-icon mb-0"></i>
                            <span class="badge bg-success"><?php echo htmlspecialchars($equipment['status']); ?></span>
                        </div>
                        <h4 class="text-white mb-2"><?php echo htmlspecialchars($equipment['name']); ?></h4>
                        <p class="text-muted small mb-3">
                            <?php echo htmlspecialchars($equipment['desc']); ?>
                        </p>
                        <div class="border-top border-secondary pt-3">
                            <small class="text-hazard font-monospace"><?php echo htmlspecialchars($equipment['meta']); ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="hazard-stripes"></div>

    <!-- CARDIO SYSTEMS SECTION -->
    <section id="cardio" class="py-5 bg-black">
        <div class="container py-5">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="text-white">CARDIO <span class="text-hazard">SYSTEMS</span></h2>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($cardio_equipment as $cardio): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card text-center">
                        <i class="fa-solid <?php echo $cardio['icon']; ?> feature-icon"></i>
                        <h5 class="text-white"><?php echo htmlspecialchars($cardio['name']); ?></h5>
                        <div class="display-6 text-hazard fw-bold my-3"><?php echo htmlspecialchars($cardio['count']); ?></div>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($cardio['desc']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="hazard-stripes"></div>

    <!-- FACILITY ZONES SECTION -->
    <section id="zones" class="py-5">
        <div class="container py-5">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="text-white">FACILITY <span class="text-hazard">ZONES</span></h2>
                    <p class="text-muted">Purpose-built spaces for every training style</p>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($facility_zones as $zone): ?>
                <div class="col-lg-4">
                    <div class="feature-card" style="height: 100%;">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fa-solid <?php echo $zone['icon']; ?> text-hazard me-3" style="font-size: 2rem;"></i>
                            <div>
                                <h4 class="text-white mb-0"><?php echo htmlspecialchars($zone['name']); ?></h4>
                                <small class="text-muted font-monospace"><?php echo htmlspecialchars($zone['code']); ?></small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">
                            <?php echo htmlspecialchars($zone['desc']); ?>
                        </p>
                        <div class="row g-2">
                            <?php if (isset($zone['area'])): ?>
                            <div class="col-6">
                                <div class="bg-dark p-2 rounded border border-secondary text-center">
                                    <small class="text-muted d-block" style="font-size: 0.65rem;">AREA</small>
                                    <span class="text-white fw-bold"><?php echo htmlspecialchars($zone['area']); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-dark p-2 rounded border border-secondary text-center">
                                    <small class="text-muted d-block" style="font-size: 0.65rem;">CAPACITY</small>
                                    <span class="text-hazard fw-bold"><?php echo htmlspecialchars($zone['capacity']); ?></span>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="col-12">
                                <div class="bg-dark p-2 rounded border border-secondary text-center">
                                    <small class="text-muted d-block" style="font-size: 0.65rem;">LOCKERS</small>
                                    <span class="text-white fw-bold"><?php echo htmlspecialchars($zone['lockers']); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="hazard-stripes"></div>

    <!-- AMENITIES SECTION -->
    <section class="py-5 bg-black">
        <div class="container py-5">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="text-white">ADDITIONAL <span class="text-hazard">AMENITIES</span></h2>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <?php foreach ($amenities as $amenity): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="text-center">
                        <i class="fa-solid <?php echo $amenity['icon']; ?> feature-icon"></i>
                        <h6 class="text-white mt-3 mb-2"><?php echo htmlspecialchars($amenity['name']); ?></h6>
                        <p class="text-muted small"><?php echo htmlspecialchars($amenity['desc']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-5">
                <a href="index.php#pricing" class="btn btn-hazard btn-lg">
                    <i class="fa-solid fa-rocket me-2"></i> START YOUR MEMBERSHIP
                </a>
            </div>
        </div>
    </section>

<?php
// Include footer
include('includes/footer.php');
?>

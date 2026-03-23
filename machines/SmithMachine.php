<?php
// Define base path for includes (we're in machines/ folder, one level deep)
$base_path = '../';

$page_title = 'Smith Machine | FIT-STOP Equipment';
$custom_css = '<link rel="stylesheet" href="../machines/machine-pages.css">';
include('../includes/header.php');
?>

<div class="container-inner">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <a href="<?php echo $base_path; ?>index.php">Home</a> › 
        <a href="<?php echo $base_path; ?>equipment.php">Equipment</a> › 
        <a href="<?php echo $base_path; ?>machine.php">Machines</a> › 
        Smith Machine
    </div>
    
    <h1 class="mb-4">Smith Machine</h1>
    
    <div class="row g-3">
        <!-- Left Column: Gallery & Exercises -->
        <div class="col-12 col-lg-8">
            <div class="card">
                <!-- Gallery Header -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
                    <strong>Usage Gallery</strong>
                    <small style="color:#9fb1c7">Photos & videos of machine in use</small>
                </div>
                
                <!-- Main Media Display -->
                <div id="mediaMain" class="media-main">
                    <img src="../images/smith-pic.jpg" alt="Smith Machine">
                </div>
                
                <!-- Thumbnails -->
                <div class="thumbs" id="thumbs">
                    <button type="button" 
                            data-src="../images/smith-pic.jpg" 
                            data-type="image" 
                            aria-label="View photo 1">
                        <img src="../images/smith-pic.jpg" alt="Smith Machine Photo" loading="lazy" decoding="async">
                    </button>
                        
                    <button type="button" 
                            data-src="../images/Smith-Bench.mp4" 
                            data-type="video" 
                            aria-label="Play video 1">
                        <img src="../images/smith-press-thumbnail.png" alt="Smith Bench Video" loading="lazy" decoding="async">
                    </button>
                </div>
                
                <!-- Exercises Section -->
                <section style="margin-top:24px">
                    <strong>Exercises You Can Do On This Machine</strong>
                    <div class="row row-cols-1 row-cols-md-2 g-3 mt-1">
                        <!-- Exercise 1: Smith Squat -->
                        <div class="col">
                            <article class="ex-card">
                                <div>
                                    <div style="font-weight:600">Smith Squat</div>
                                    <div style="font-size:13px; color:#9fb1c7">
                                        Primary muscle • Quads, Glutes
                                    </div>
                                </div>
                            </article>
                        </div>
                        
                        <!-- Exercise 2: Smith Bench Press -->
                        <div class="col">
                            <article class="ex-card">
                                <div>
                                    <div style="font-weight:600">Smith Bench Press</div>
                                    <div style="font-size:13px; color:#9fb1c7">
                                        Primary muscle • Chest
                                    </div>
                                </div>
                            </article>
                        </div>
                        
                        <!-- Exercise 3: Smith Shoulder Press -->
                        <div class="col">
                            <article class="ex-card">
                                <div>
                                    <div style="font-weight:600">Smith Shoulder Press</div>
                                    <div style="font-size:13px; color:#9fb1c7">
                                        Primary muscle • Shoulders
                                    </div>
                                </div>
                            </article>
                        </div>
                        
                        <!-- Exercise 4: Smith Row -->
                        <div class="col">
                            <article class="ex-card">
                                <div>
                                    <div style="font-weight:600">Smith Row</div>
                                    <div style="font-size:13px; color:#9fb1c7">
                                        Primary muscle • Back
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        
        <!-- Right Column: Details, Tips & Feedback -->
        <div class="col-12 col-lg-4">
            <!-- Machine Details Card -->
            <div class="card">
                <strong>Machine Details</strong>
                <ul class="meta-list" style="margin-top:10px">
                    <li><strong>Equipment:</strong> Smith Machine</li>
                    <li><strong>Type:</strong> Guided Barbell / Multi-Function</li>
                    <li><strong>Difficulty:</strong> Beginner — Advanced</li>
                    <li><strong>Focus Muscles:</strong> Full Body (varies by exercise)</li>
                    <li><strong>Auxiliary Muscles:</strong> Stabilizers, Core</li>
                </ul>
                <div class="cta">
                    <button type="button" 
                            class="btn btn-outline-light w-100" 
                            data-bs-toggle="modal" 
                            data-bs-target="#galleryModal">
                        Open full gallery
                    </button>
                </div>
            </div>
            
            <!-- Quick Tips Card -->
            <div class="card">
                <strong>Quick Tips</strong>
                <ol style="margin-top:8px; color:#c9d6e1; font-size:14px">
                    <li>Bar moves on fixed vertical path.</li>
                    <li>Use safety hooks for secure training.</li>
                    <li>Great for learning proper form safely.</li>
                </ol>
            </div>
            
            <!-- Feedback Card -->
            <div class="card">
                <strong>Machine Feedback</strong>
                
                <?php if (empty($_SESSION['username'])): ?>
                <p style="font-size:13px; color:#9fb1c7; margin-top:8px; margin-bottom:0">
                    <i class="bi bi-info-circle me-1"></i>
                    Visitor feedback - help us improve our equipment!
                </p>
                <?php endif; ?>
                
                <div id="feedbackMessage" style="margin-top:12px"></div>
                
                <form style="margin-top:12px" action="../Database/submit_feedback.php" method="POST">
                    <input type="hidden" name="machine" value="Smith Machine">
                    <?php echo fitstop_csrf_input(); ?>
                    
                    <?php if (empty($_SESSION['username'])): ?>
                    <div class="mb-3">
                        <label for="guestName" class="form-label" style="color:#c9d6e1; font-size:13px">
                            Your Name (Optional)
                        </label>
                        <input type="text" 
                               class="form-control bg-dark text-light border-secondary" 
                               id="guestName" 
                               name="guest_name" 
                               placeholder="Anonymous" 
                               maxlength="100" 
                               style="font-size:13px">
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="feedback" class="form-label" style="color:#c9d6e1; font-size:13px">
                            Concerns / Comments on Performance
                        </label>
                        <textarea class="form-control bg-dark text-light border-secondary" 
                                  id="feedback" 
                                  name="feedback" 
                                  rows="4" 
                                  placeholder="Share your feedback about this machine..." 
                                  style="font-size:13px" 
                                  required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-hazard w-100" style="font-size:13px">
                        Submit Feedback
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Gallery Modal -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-light">
            <!-- Modal Header -->
            <div class="modal-header border-0">
                <nav aria-label="breadcrumb" class="m-0">
                    <ol class="breadcrumb m-0" style="background:transparent; padding:0">
                        <li class="breadcrumb-item active" aria-current="page">Gallery</li>
                    </ol>
                </nav>
                <button type="button" 
                        class="btn-close btn-close-white" 
                        data-bs-dismiss="modal" 
                        aria-label="Close"></button>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body py-2 px-3">
                <!-- Gallery Grid View -->
                <div id="galleryGrid" class="row row-cols-5 g-2"></div>
                
                <!-- Gallery Viewer (Hidden by default) -->
                <div id="galleryViewer" class="d-none d-flex flex-column align-items-center gap-3 mt-2">
                    <div class="w-100 d-flex justify-content-start">
                        <button id="galleryBack" type="button" class="btn btn-sm btn-secondary">
                            Back
                        </button>
                    </div>
                    <div class="w-100 d-flex justify-content-center align-items-center">
                        <img id="galleryViewerImg" 
                             src="" 
                             alt="" 
                             class="img-fluid rounded" 
                             style="max-height:70vh; object-fit:contain;" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Media Gallery Script -->
<script>
    const mediaMain = document.getElementById('mediaMain');
    const thumbs = document.getElementById('thumbs');
    
    /**
     * Set main media display (image or video)
     */
    function setMainMedia(src, isVideo) {
        // Clean up any existing video or iframe
        const prevVideo = mediaMain.querySelector('video');
        if (prevVideo) {
            try { prevVideo.pause(); } catch(e) {}
            prevVideo.removeAttribute('src');
            prevVideo.load && prevVideo.load();
        }
        const prevIframe = mediaMain.querySelector('iframe');
        if (prevIframe) prevIframe.removeAttribute('src');
        
        mediaMain.innerHTML = '';
        if (!src) return;
        
        if (isVideo) {
            if (src.startsWith('http')) {
                // Embed URL (Google Drive, YouTube, etc.) — use iframe
                const f = document.createElement('iframe');
                f.src = src;
                f.allowFullscreen = true;
                f.style.width = '100%';
                f.style.height = '100%';
                f.style.border = 'none';
                f.style.minHeight = '340px';
                mediaMain.appendChild(f);
            } else {
                // Local video file — use video element
                const v = document.createElement('video');
                v.src = src;
                v.controls = true;
                v.playsInline = true;
                v.style.maxWidth = '100%';
                v.style.maxHeight = '100%';
                mediaMain.appendChild(v);
            }
        } else {
            // Create image element
            const i = document.createElement('img');
            i.src = src;
            i.alt = '';
            i.style.maxWidth = '100%';
            i.style.maxHeight = '100%';
            mediaMain.appendChild(i);
        }
    }
    
    /**
     * Handle thumbnail clicks
     */
    thumbs.addEventListener('click', e => {
        const btn = e.target.closest('button');
        if (!btn || !thumbs.contains(btn)) return;
        e.preventDefault();
        setMainMedia(btn.dataset.src, btn.dataset.type === 'video');
    });
    
    // Gallery Modal Elements
    const galleryModalEl = document.getElementById('galleryModal');
    const galleryGrid = document.getElementById('galleryGrid');
    const galleryViewer = document.getElementById('galleryViewer');
    const galleryViewerImg = document.getElementById('galleryViewerImg');
    const galleryBack = document.getElementById('galleryBack');
    
    /**
     * Build gallery grid from thumbnails
     */
    function buildGallery() {
        galleryGrid.innerHTML = '';
        galleryViewer.classList.add('d-none');
        galleryGrid.classList.remove('d-none');
        
        // Get image buttons (exclude videos)
        const buttons = Array.from(thumbs.querySelectorAll('button'))
            .filter(b => b.dataset.type !== 'video');
        const imgs = buttons.map(b => b.dataset.src).slice(0, 25);
        
        // Create gallery grid items
        imgs.forEach(src => {
            const col = document.createElement('div');
            col.className = 'col';
            
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn p-0 w-100';
            
            const img = document.createElement('img');
            img.src = src;
            img.alt = '';
            img.className = 'img-fluid rounded';
            
            b.appendChild(img);
            b.addEventListener('click', () => openViewer(src));
            col.appendChild(b);
            galleryGrid.appendChild(col);
        });
        
        // Add placeholder cells
        const fill = 25 - imgs.length;
        for (let i = 0; i < fill; i++) {
            const ph = document.createElement('div');
            ph.className = 'col thumb-placeholder';
            galleryGrid.appendChild(ph);
        }
    }
    
    /**
     * Open image viewer for specific image
     */
    function openViewer(src) {
        galleryViewerImg.src = src;
        galleryGrid.classList.add('d-none');
        galleryViewer.classList.remove('d-none');
    }
    
    /**
     * Back button handler
     */
    galleryBack.addEventListener('click', () => {
        galleryViewerImg.src = '';
        galleryViewer.classList.add('d-none');
        galleryGrid.classList.remove('d-none');
    });
    
    /**
     * Modal event listeners
     */
    if (galleryModalEl) {
        galleryModalEl.addEventListener('show.bs.modal', buildGallery);
        galleryModalEl.addEventListener('hidden.bs.modal', () => {
            galleryViewerImg.src = '';
            galleryViewer.style.display = 'none';
            galleryGrid.style.display = 'grid';
        });
    }
    
    /**
     * Handle feedback submission results
     */
    const urlParams = new URLSearchParams(window.location.search);
    const feedbackMessage = document.getElementById('feedbackMessage');
    
    if (urlParams.has('success') && urlParams.get('success') === 'feedback_submitted') {
        feedbackMessage.innerHTML = `
            <div class="alert alert-success" role="alert" style="font-size:13px">
                <i class="bi bi-check-circle-fill me-2"></i>
                Thank you! Your feedback has been submitted.
            </div>
        `;
        setTimeout(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
            feedbackMessage.innerHTML = '';
        }, 5000);
    } else if (urlParams.has('error')) {
        let errorMsg = 'An error occurred. Please try again.';
        
        switch(urlParams.get('error')) {
            case 'empty_feedback':
                errorMsg = 'Please enter your feedback before submitting.';
                break;
            case 'feedback_too_long':
                errorMsg = 'Feedback is too long. Maximum 1000 characters.';
                break;
            case 'invalid_machine':
                errorMsg = 'Invalid machine selection.';
                break;
            case 'database':
                errorMsg = 'Database error. Please try again later.';
                break;
        }
        
        feedbackMessage.innerHTML = `
            <div class="alert alert-danger" role="alert" style="font-size:13px">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                ${errorMsg}
            </div>
        `;
        setTimeout(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
            feedbackMessage.innerHTML = '';
        }, 5000);
    }
</script>


<!-- Feedback Form Handler -->
<script src="feedback.js"></script>

<?php include('../includes/footer.php'); ?>


<?php
/**
 * CABLE MACHINE - Equipment Detail Page
 * 
 * TO CUSTOMIZE THIS PAGE:
 * 1. Update $page_title
 * 2. Change machine name in breadcrumbs
 * 3. Replace placeholder images with actual media
 * 4. Update exercises list
 * 5. Modify machine details and tips
 */

// Set page title
$page_title = 'Cable Machine | FIT-STOP Equipment';

// Custom CSS for machine pages
$custom_css = '
<style>
    .container-inner {max-width:1100px;margin:88px auto 28px;padding:0 16px}
    .breadcrumbs {color:#aab4bf;font-size:13px;margin-bottom:12px}
    .breadcrumbs a {color:#aab4bf;text-decoration:none}
    .breadcrumbs a:hover {color:#fff}
    .card {background:rgba(255,255,255,0.03);padding:18px;border-radius:8px;margin-bottom:16px}
    .media-main {background:#0b1320;border-radius:8px;height:420px;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .media-main img, .media-main video {max-width:100%;max-height:100%;display:block}
    .thumbs {display:flex;gap:12px;margin-top:16px;flex-wrap:wrap}
    .thumbs button{border:2px solid rgba(255,255,255,0.1);padding:0;background:transparent;cursor:pointer;border-radius:6px;transition:border-color 0.2s}
    .thumbs button:hover{border-color:rgba(255,255,255,0.3)}
    .thumbs img{width:84px;height:56px;object-fit:cover;border-radius:4px;display:block}
    .ex-card{display:flex;gap:10px;align-items:center;padding:10px;background:#07101a;border-radius:8px}
    .ex-card img{width:64px;height:48px;object-fit:cover;border-radius:6px;flex-shrink:0}
    .meta-list{list-style:none;padding:0;margin:0;display:grid;gap:6px}
    .meta-list li{font-size:14px;color:#c9d6e1}
    .cta{margin-top:10px}
    .thumb-placeholder{min-height:0}
    @media (max-width: 991px) {
        .container-inner {margin-top:70px}
    }
    @media (max-width: 768px) {
        .media-main {height:280px}
        .thumbs img{width:70px;height:46px}
        .breadcrumbs {font-size:12px}
    }
    @media (max-width: 576px) {
        .container-inner {margin-top:64px}
        .media-main {height:220px}
        .ex-card img{width:56px;height:42px}
    }
</style>
';

// Include header
include('../includes/header.php');
?>

<!-- MACHINE CONTENT -->
<div class="container-inner">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <a href="../index.php">Home</a> › 
        <a href="../equipment.php">Equipment</a> › 
        <a href="../machine.php">Machines</a> › 
        Cable Machine
    </div>
    
    <h1 class="mb-4">Cable Machine</h1>

    <div class="row g-3">
        <!-- LEFT COLUMN: Media Gallery & Exercises -->
        <div class="col-12 col-lg-8">
            <div class="card">
                <!-- Gallery Header -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <strong>Usage Gallery</strong>
                    <small style="color:#9fb1c7">Photos & videos of machine in use</small>
                </div>

                <!-- Main Media Display -->
                <div id="mediaMain" class="media-main">
                    <img src="../images/Fitstop.png" alt="Cable Machine">
                </div>

                <!-- Media Thumbnails - REPLACE WITH YOUR IMAGES/VIDEOS -->
                <div class="thumbs" id="thumbs">
                    <button type="button" data-src="../images/Fitstop.png" data-type="image" aria-label="View photo 1">
                        <img src="../images/Fitstop.png" alt="Photo 1">
                    </button>
                    <!-- Add more thumbnails here:
                    <button type="button" data-src="path/to/image2.jpg" data-type="image" aria-label="View photo 2">
                        <img src="path/to/image2.jpg" alt="Photo 2">
                    </button>
                    <button type="button" data-src="path/to/video.mp4" data-type="video" aria-label="View video">
                        <img src="path/to/video-thumbnail.jpg" alt="Video">
                    </button>
                    -->
                </div>

                <!-- Exercises Section -->
                <section style="margin-top:24px">
                    <strong>Exercises You Can Do On This Machine</strong>
                    <div class="row row-cols-1 row-cols-md-2 g-3 mt-1">
                        <!-- Exercise 1 - CUSTOMIZE BELOW -->
                        <div class="col">
                            <article class="ex-card">
                                <img src="../images/Fitstop.png" alt="Cable Crossover">
                                <div>
                                    <div style="font-weight:600">Cable Crossover</div>
                                    <div style="font-size:13px;color:#9fb1c7">Primary muscle • Chest</div>
                                </div>
                            </article>
                        </div>

                        <!-- Exercise 2 -->
                        <div class="col">
                            <article class="ex-card">
                                <img src="../images/Fitstop.png" alt="Tricep Pushdown">
                                <div>
                                    <div style="font-weight:600">Tricep Pushdown</div>
                                    <div style="font-size:13px;color:#9fb1c7">Primary muscle • Triceps</div>
                                </div>
                            </article>
                        </div>

                        <!-- Exercise 3 -->
                        <div class="col">
                            <article class="ex-card">
                                <img src="../images/Fitstop.png" alt="Cable Bicep Curl">
                                <div>
                                    <div style="font-weight:600">Cable Bicep Curl</div>
                                    <div style="font-size:13px;color:#9fb1c7">Primary muscle • Biceps</div>
                                </div>
                            </article>
                        </div>

                        <!-- Exercise 4 -->
                        <div class="col">
                            <article class="ex-card">
                                <img src="../images/Fitstop.png" alt="Lateral Raise">
                                <div>
                                    <div style="font-weight:600">Lateral Raise</div>
                                    <div style="font-size:13px;color:#9fb1c7">Primary muscle • Shoulders</div>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- RIGHT COLUMN: Machine Details, Tips & Feedback -->
        <div class="col-12 col-lg-4">
            <!-- Machine Details - CUSTOMIZE BELOW -->
            <div class="card">
                <strong>Machine Details</strong>
                <ul class="meta-list" style="margin-top:10px">
                    <li><strong>Equipment:</strong> Adjustable Height Cable Machine </li>
                    <li><strong>Type:</strong> Cable / Multi-Function</li>
                    <li><strong>Difficulty:</strong> Beginner — Advanced</li>
                </ul>
                <div class="cta">
                    <button type="button" class="btn btn-outline-light w-100" data-bs-toggle="modal" data-bs-target="#galleryModal">
                        Open full gallery
                    </button>
                </div>
            </div>

            <!-- Quick Tips - CUSTOMIZE BELOW -->
            <div class="card">
                <strong>Quick Tips</strong>
                <ol style="margin-top:8px;color:#c9d6e1;font-size:14px">
                    <li>Adjust cable height to target different muscle groups.</li>
                    <li>Use various attachments for different exercises.</li>
                    <li>Maintain constant tension throughout the movement.</li>
                </ol>
            </div>

            <!-- Machine Feedback Form -->
            <div class="card">
                <strong>Machine Feedback</strong>
                <div id="feedbackMessage" style="margin-top:12px"></div>
                <form style="margin-top:12px" action="../Database/submit_feedback.php" method="POST">
                    <input type="hidden" name="machine" value="Cable Machine">
                    <div class="mb-3">
                        <label for="feedbackCable" class="form-label" style="color:#c9d6e1;font-size:13px">
                            Concerns / Comments on Performance
                        </label>
                        <textarea class="form-control bg-dark text-light border-secondary" 
                                  id="feedbackCable" 
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
            <div class="modal-header border-0">
                <nav aria-label="breadcrumb" class="m-0">
                    <ol class="breadcrumb m-0" style="background:transparent;padding:0">
                        <li class="breadcrumb-item active" aria-current="page">Gallery</li>
                    </ol>
                </nav>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-2 px-3">
                <div id="galleryGrid" class="row row-cols-5 g-2"></div>
                <div id="galleryViewer" class="d-none d-flex flex-column align-items-center gap-3 mt-2">
                    <div class="w-100 d-flex justify-content-start">
                        <button id="galleryBack" type="button" class="btn btn-sm btn-secondary">Back</button>
                    </div>
                    <div class="w-100 d-flex justify-content-center align-items-center">
                        <img id="galleryViewerImg" src="" alt="" class="img-fluid rounded" style="max-height:70vh;object-fit:contain;" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript for Media Gallery -->
<script>
    const mediaMain = document.getElementById('mediaMain');
    const thumbs = document.getElementById('thumbs');

    // Set main media (image or video)
    function setMainMedia(src, isVideo){
        const prevVideo = mediaMain.querySelector('video');
        if (prevVideo) {
            try { prevVideo.pause(); } catch(e){}
            prevVideo.removeAttribute('src');
            prevVideo.load && prevVideo.load();
        }

        mediaMain.innerHTML = '';
        if (!src) return;

        if (isVideo) {
            const v = document.createElement('video');
            v.src = src; v.controls = true; v.autoplay = true; v.playsInline = true;
            v.style.maxWidth = '100%'; v.style.maxHeight = '100%';
            mediaMain.appendChild(v);
            v.play().catch(()=>{});
        } else {
            const i = document.createElement('img');
            i.src = src; i.alt = ''; i.style.maxWidth = '100%'; i.style.maxHeight = '100%';
            mediaMain.appendChild(i);
        }
    }

    // Handle thumbnail clicks
    thumbs.addEventListener('click', e => {
        const btn = e.target.closest('button');
        if (!btn || !thumbs.contains(btn)) return;
        e.preventDefault();
        const src = btn.dataset.src;
        const type = btn.dataset.type;
        setMainMedia(src, type === 'video');
    });

    // Gallery modal elements
    const galleryModalEl = document.getElementById('galleryModal');
    const galleryGrid = document.getElementById('galleryGrid');
    const galleryViewer = document.getElementById('galleryViewer');
    const galleryViewerImg = document.getElementById('galleryViewerImg');
    const galleryBack = document.getElementById('galleryBack');

    // Build the 5x5 gallery grid
    function buildGallery(){
        galleryGrid.innerHTML = '';
        galleryViewer.classList.add('d-none');
        galleryGrid.classList.remove('d-none');

        const buttons = Array.from(thumbs.querySelectorAll('button')).filter(b => b.dataset.type !== 'video');
        const imgs = buttons.map(b => b.dataset.src).slice(0,25);
        imgs.forEach(src => {
            const col = document.createElement('div'); col.className = 'col';
            const b = document.createElement('button'); b.type = 'button'; b.className = 'btn p-0 w-100';
            const img = document.createElement('img'); img.src = src; img.alt = ''; img.className = 'img-fluid rounded';
            b.appendChild(img);
            b.addEventListener('click', () => openViewer(src));
            col.appendChild(b);
            galleryGrid.appendChild(col);
        });

        const fill = 25 - imgs.length;
        for (let i=0;i<fill;i++){
            const ph = document.createElement('div'); ph.className = 'col thumb-placeholder'; galleryGrid.appendChild(ph);
        }
    }

    function openViewer(src){
        galleryViewerImg.src = src;
        galleryGrid.classList.add('d-none');
        galleryViewer.classList.remove('d-none');
    }

    galleryBack.addEventListener('click', () => {
        galleryViewerImg.src = '';
        galleryViewer.classList.add('d-none');
        galleryGrid.classList.remove('d-none');
    });

    if (galleryModalEl) {
        galleryModalEl.addEventListener('show.bs.modal', buildGallery);
        galleryModalEl.addEventListener('hidden.bs.modal', () => {
            galleryViewerImg.src = '';
            galleryViewer.style.display = 'none';
            galleryGrid.style.display = 'grid';
        });
    }

    // Handle feedback submission messages
    const urlParams = new URLSearchParams(window.location.search);
    const feedbackMessage = document.getElementById('feedbackMessage');
    
    if (urlParams.has('success') && urlParams.get('success') === 'feedback_submitted') {
        feedbackMessage.innerHTML = '<div class="alert alert-success" role="alert" style="font-size:13px"><i class="bi bi-check-circle-fill me-2"></i>Thank you! Your feedback has been submitted.</div>';
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
        feedbackMessage.innerHTML = '<div class="alert alert-danger" role="alert" style="font-size:13px"><i class="bi bi-exclamation-triangle-fill me-2"></i>' + errorMsg + '</div>';
        setTimeout(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
            feedbackMessage.innerHTML = '';
        }, 5000);
    }
</script>

<?php
// Include footer
include('../includes/footer.php');
?>

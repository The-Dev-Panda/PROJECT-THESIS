<?php
// BACKEND: adjust these paths/includes if your folder structure changes.
$base_path = '../';
$custom_css = '<link rel="stylesheet" href="../machines/machine-pages.css">';

// MACHINE INFO: update all values in this block for each new machine page.
$machine_name = 'Machine Name';
$page_title = $machine_name . ' | FIT-STOP Equipment';
$machine_type = 'Machine Type';
$difficulty = 'Beginner - Advanced';
$focus_muscles = 'Primary Focus Muscles';
$auxiliary_muscles = 'Auxiliary Muscles';
$quick_tips = [
    'Tip 1',
    'Tip 2',
    'Tip 3',
];
$main_media_src = '../images/placeholder-main.jpg';
$main_media_alt = $machine_name;
$gallery_items = [
    ['type' => 'image', 'src' => '../images/placeholder-main.jpg', 'thumb' => '../images/placeholder-main.jpg', 'alt' => $machine_name . ' Photo 1'],
    ['type' => 'video', 'src' => '../images/placeholder-video.mp4', 'thumb' => '../images/placeholder-video-thumb.jpg', 'alt' => $machine_name . ' Video 1'],
];
$exercises = [
    ['name' => 'Exercise 1', 'muscle' => 'Primary muscle • Muscle Group', 'image' => '../images/placeholder-main.jpg', 'alt' => 'Exercise 1'],
    ['name' => 'Exercise 2', 'muscle' => 'Primary muscle • Muscle Group', 'image' => '../images/placeholder-main.jpg', 'alt' => 'Exercise 2'],
    ['name' => 'Exercise 3', 'muscle' => 'Primary muscle • Muscle Group', 'image' => '../images/placeholder-main.jpg', 'alt' => 'Exercise 3'],
    ['name' => 'Exercise 4', 'muscle' => 'Primary muscle • Muscle Group', 'image' => '../images/placeholder-main.jpg', 'alt' => 'Exercise 4'],
];

// BACKEND: keep this include if your shared header sets session/bootstrap assets.
include('../includes/header.php');
?>

<div class="container-inner">
    <div class="breadcrumbs">
        <a href="<?php echo htmlspecialchars($base_path, ENT_QUOTES, 'UTF-8'); ?>index.php">Home</a> ›
        <a href="<?php echo htmlspecialchars($base_path, ENT_QUOTES, 'UTF-8'); ?>equipment.php">Equipment</a> ›
        <a href="<?php echo htmlspecialchars($base_path, ENT_QUOTES, 'UTF-8'); ?>machine.php">Machines</a> ›
        <?php echo htmlspecialchars($machine_name, ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <h1 class="mb-4"><?php echo htmlspecialchars($machine_name, ENT_QUOTES, 'UTF-8'); ?></h1>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
                    <strong>Usage Gallery</strong>
                    <small style="color:#9fb1c7">Photos & videos of machine in use</small>
                </div>

                <div id="mediaMain" class="media-main">
                    <img src="<?php echo htmlspecialchars($main_media_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($main_media_alt, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="thumbs" id="thumbs">
                    <?php foreach ($gallery_items as $index => $item): ?>
                    <button type="button"
                            data-src="<?php echo htmlspecialchars($item['src'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-type="<?php echo htmlspecialchars($item['type'], ENT_QUOTES, 'UTF-8'); ?>"
                            aria-label="<?php echo ($item['type'] === 'video' ? 'Play' : 'View') . ' media ' . ($index + 1); ?>">
                        <img src="<?php echo htmlspecialchars($item['thumb'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['alt'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async">
                    </button>
                    <?php endforeach; ?>
                </div>

                <section style="margin-top:24px">
                    <strong>Exercises You Can Do On This Machine</strong>
                    <div class="row row-cols-1 row-cols-md-2 g-3 mt-1">
                        <?php foreach ($exercises as $exercise): ?>
                        <div class="col">
                            <article class="ex-card">
                                <div>
                                    <div style="font-weight:600"><?php echo htmlspecialchars($exercise['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div style="font-size:13px; color:#9fb1c7"><?php echo htmlspecialchars($exercise['muscle'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            </article>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card">
                <strong>Machine Details</strong>
                <ul class="meta-list" style="margin-top:10px">
                    <li><strong>Equipment:</strong> <?php echo htmlspecialchars($machine_name, ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><strong>Type:</strong> <?php echo htmlspecialchars($machine_type, ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><strong>Difficulty:</strong> <?php echo htmlspecialchars($difficulty, ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><strong>Focus Muscles:</strong> <?php echo htmlspecialchars($focus_muscles, ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><strong>Auxiliary Muscles:</strong> <?php echo htmlspecialchars($auxiliary_muscles, ENT_QUOTES, 'UTF-8'); ?></li>
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

            <div class="card">
                <strong>Quick Tips</strong>
                <ol style="margin-top:8px; color:#c9d6e1; font-size:14px">
                    <?php foreach ($quick_tips as $tip): ?>
                    <li><?php echo htmlspecialchars($tip, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>

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
                    <input type="hidden" name="machine" value="<?php echo htmlspecialchars($machine_name, ENT_QUOTES, 'UTF-8'); ?>">

                    <?php if (empty($_SESSION['username'])): ?>
                    <div class="mb-3">
                        <label for="guestName" class="form-label" style="color:#c9d6e1; font-size:13px">Your Name (Optional)</label>
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
                        <label for="feedback" class="form-label" style="color:#c9d6e1; font-size:13px">Concerns / Comments on Performance</label>
                        <textarea class="form-control bg-dark text-light border-secondary"
                                  id="feedback"
                                  name="feedback"
                                  rows="4"
                                  placeholder="Share your feedback about this machine..."
                                  style="font-size:13px"
                                  required></textarea>
                    </div>

                    <button type="submit" class="btn btn-hazard w-100" style="font-size:13px">Submit Feedback</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-light">
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

            <div class="modal-body py-2 px-3">
                <div id="galleryGrid" class="row row-cols-5 g-2"></div>
                <div id="galleryViewer" class="d-none d-flex flex-column align-items-center gap-3 mt-2">
                    <div class="w-100 d-flex justify-content-start">
                        <button id="galleryBack" type="button" class="btn btn-sm btn-secondary">Back</button>
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

<script>
    const mediaMain = document.getElementById('mediaMain');
    const thumbs = document.getElementById('thumbs');

    function setMainMedia(src, isVideo) {
        const prevVideo = mediaMain.querySelector('video');
        if (prevVideo) {
            try {
                prevVideo.pause();
            } catch (e) {}
            prevVideo.removeAttribute('src');
            prevVideo.load && prevVideo.load();
        }

        mediaMain.innerHTML = '';
        if (!src) return;

        if (isVideo) {
            const v = document.createElement('video');
            v.src = src;
            v.controls = true;
            v.autoplay = true;
            v.playsInline = true;
            v.style.maxWidth = '100%';
            v.style.maxHeight = '100%';
            mediaMain.appendChild(v);
            v.play().catch(() => {});
        } else {
            const i = document.createElement('img');
            i.src = src;
            i.alt = '';
            i.style.maxWidth = '100%';
            i.style.maxHeight = '100%';
            mediaMain.appendChild(i);
        }
    }

    thumbs.addEventListener('click', e => {
        const btn = e.target.closest('button');
        if (!btn || !thumbs.contains(btn)) return;
        e.preventDefault();
        setMainMedia(btn.dataset.src, btn.dataset.type === 'video');
    });

    const galleryModalEl = document.getElementById('galleryModal');
    const galleryGrid = document.getElementById('galleryGrid');
    const galleryViewer = document.getElementById('galleryViewer');
    const galleryViewerImg = document.getElementById('galleryViewerImg');
    const galleryBack = document.getElementById('galleryBack');

    function buildGallery() {
        galleryGrid.innerHTML = '';
        galleryViewer.classList.add('d-none');
        galleryGrid.classList.remove('d-none');

        const buttons = Array.from(thumbs.querySelectorAll('button')).filter(b => b.dataset.type !== 'video');
        const imgs = buttons.map(b => b.dataset.src).slice(0, 25);

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

        const fill = 25 - imgs.length;
        for (let i = 0; i < fill; i++) {
            const ph = document.createElement('div');
            ph.className = 'col thumb-placeholder';
            galleryGrid.appendChild(ph);
        }
    }

    function openViewer(src) {
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

        switch (urlParams.get('error')) {
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

<!-- BACKEND: keep or update script path if you move this template file. -->
<script src="feedback.js"></script>

<!-- BACKEND: keep this include if your shared footer is required. -->
<?php include('../includes/footer.php'); ?>

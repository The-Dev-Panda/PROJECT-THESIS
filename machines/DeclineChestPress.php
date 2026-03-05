<?php
/**
 * DECLINE CHEST PRESS - Equipment Detail Page
 * 
 * TO CUSTOMIZE THIS PAGE:
 * 1. Update images/videos in the media gallery section
 * 2. Modify exercises list with actual images and names
 * 3. Update machine details, tips, and specifications
 */

$page_title = 'Decline Chest Press | FIT-STOP Equipment';

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
    @media (max-width: 991px) {.container-inner {margin-top:70px}}
    @media (max-width: 768px) {.media-main {height:280px}.thumbs img{width:70px;height:46px}}
    @media (max-width: 576px) {.container-inner {margin-top:64px}.media-main {height:220px}.ex-card img{width:56px;height:42px}}
</style>
';

include('../includes/header.php');
?>

<div class="container-inner">
    <div class="breadcrumbs">
        <a href="../index.php">Home</a> › <a href="../equipment.php">Equipment</a> › <a href="../machine.php">Machines</a> › Decline Chest Press
    </div>
    
    <h1 class="mb-4">Decline Chest Press</h1>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <strong>Usage Gallery</strong>
                    <small style="color:#9fb1c7">Photos & videos of machine in use</small>
                </div>

                <div id="mediaMain" class="media-main">
                    <img src="../images/Fitstop.png" alt="Decline Chest Press">
                </div>

                <div class="thumbs" id="thumbs">
                    <button type="button" data-src="../images/Fitstop.png" data-type="image" aria-label="View photo 1">
                        <img src="../images/Fitstop.png" alt="Photo 1">
                    </button>
                </div>

                <section style="margin-top:24px">
                    <strong>Exercises You Can Do On This Machine</strong>
                    <div class="row row-cols-1 row-cols-md-2 g-3 mt-1">
                        <div class="col">
                            <article class="ex-card">
                                <img src="../images/Fitstop.png" alt="Decline Press">
                                <div>
                                    <div style="font-weight:600">Decline Press</div>
                                    <div style="font-size:13px;color:#9fb1c7">Primary muscle • Lower Chest</div>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card">
                <strong>Machine Details</strong>
                <ul class="meta-list" style="margin-top:10px">
                    <li><strong>Equipment:</strong> Decline Chest Press</li>
                    <li><strong>Type:</strong> Plate-Loaded</li>
                    <li><strong>Difficulty:</strong> Intermediate</li>
                    <li><strong>Focus Muscles:</strong> Lower Chest</li>
                    <li><strong>Auxiliary Muscles:</strong> Triceps, Shoulders</li>
                </ul>
                <div class="cta">
                    <button type="button" class="btn btn-outline-light w-100" data-bs-toggle="modal" data-bs-target="#galleryModal">Open full gallery</button>
                </div>
            </div>

            <div class="card">
                <strong>Quick Tips</strong>
                <ol style="margin-top:8px;color:#c9d6e1;font-size:14px">
                    <li>Adjust seat to align handles with lower chest.</li>
                    <li>Press up and away in a controlled motion.</li>
                    <li>Keep elbows at 45-degree angle to body.</li>
                </ol>
            </div>

            <div class="card">
                <strong>Machine Feedback</strong>
                <div id="feedbackMessage" style="margin-top:12px"></div>
                <form style="margin-top:12px" action="../Database/submit_feedback.php" method="POST">
                    <input type="hidden" name="machine" value="Decline Chest Press">
                    <div class="mb-3">
                        <label for="feedback" class="form-label" style="color:#c9d6e1;font-size:13px">Concerns / Comments on Performance</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="feedback" name="feedback" rows="4" placeholder="Share your feedback about this machine..." style="font-size:13px" required></textarea>
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
                <nav aria-label="breadcrumb" class="m-0"><ol class="breadcrumb m-0" style="background:transparent;padding:0"><li class="breadcrumb-item active" aria-current="page">Gallery</li></ol></nav>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-2 px-3">
                <div id="galleryGrid" class="row row-cols-5 g-2"></div>
                <div id="galleryViewer" class="d-none d-flex flex-column align-items-center gap-3 mt-2">
                    <div class="w-100 d-flex justify-content-start"><button id="galleryBack" type="button" class="btn btn-sm btn-secondary">Back</button></div>
                    <div class="w-100 d-flex justify-content-center align-items-center"><img id="galleryViewerImg" src="" alt="" class="img-fluid rounded" style="max-height:70vh;object-fit:contain;" /></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const mediaMain=document.getElementById('mediaMain'),thumbs=document.getElementById('thumbs');
    function setMainMedia(src,isVideo){const prevVideo=mediaMain.querySelector('video');if(prevVideo){try{prevVideo.pause()}catch(e){}prevVideo.removeAttribute('src');prevVideo.load&&prevVideo.load()}mediaMain.innerHTML='';if(!src)return;if(isVideo){const v=document.createElement('video');v.src=src;v.controls=true;v.autoplay=true;v.playsInline=true;v.style.maxWidth='100%';v.style.maxHeight='100%';mediaMain.appendChild(v);v.play().catch(()=>{})}else{const i=document.createElement('img');i.src=src;i.alt='';i.style.maxWidth='100%';i.style.maxHeight='100%';mediaMain.appendChild(i)}}
    thumbs.addEventListener('click',e=>{const btn=e.target.closest('button');if(!btn||!thumbs.contains(btn))return;e.preventDefault();setMainMedia(btn.dataset.src,btn.dataset.type==='video')});
    const galleryModalEl=document.getElementById('galleryModal'),galleryGrid=document.getElementById('galleryGrid'),galleryViewer=document.getElementById('galleryViewer'),galleryViewerImg=document.getElementById('galleryViewerImg'),galleryBack=document.getElementById('galleryBack');
    function buildGallery(){galleryGrid.innerHTML='';galleryViewer.classList.add('d-none');galleryGrid.classList.remove('d-none');const buttons=Array.from(thumbs.querySelectorAll('button')).filter(b=>b.dataset.type!=='video'),imgs=buttons.map(b=>b.dataset.src).slice(0,25);imgs.forEach(src=>{const col=document.createElement('div');col.className='col';const b=document.createElement('button');b.type='button';b.className='btn p-0 w-100';const img=document.createElement('img');img.src=src;img.alt='';img.className='img-fluid rounded';b.appendChild(img);b.addEventListener('click',()=>openViewer(src));col.appendChild(b);galleryGrid.appendChild(col)});const fill=25-imgs.length;for(let i=0;i<fill;i++){const ph=document.createElement('div');ph.className='col thumb-placeholder';galleryGrid.appendChild(ph)}}
    function openViewer(src){galleryViewerImg.src=src;galleryGrid.classList.add('d-none');galleryViewer.classList.remove('d-none')}
    galleryBack.addEventListener('click',()=>{galleryViewerImg.src='';galleryViewer.classList.add('d-none');galleryGrid.classList.remove('d-none')});
    if(galleryModalEl){galleryModalEl.addEventListener('show.bs.modal',buildGallery);galleryModalEl.addEventListener('hidden.bs.modal',()=>{galleryViewerImg.src='';galleryViewer.style.display='none';galleryGrid.style.display='grid'})}
    const urlParams=new URLSearchParams(window.location.search),feedbackMessage=document.getElementById('feedbackMessage');
    if(urlParams.has('success')&&urlParams.get('success')==='feedback_submitted'){feedbackMessage.innerHTML='<div class="alert alert-success" role="alert" style="font-size:13px"><i class="bi bi-check-circle-fill me-2"></i>Thank you! Your feedback has been submitted.</div>';setTimeout(()=>{window.history.replaceState({},document.title,window.location.pathname);feedbackMessage.innerHTML=''},5000)}else if(urlParams.has('error')){let errorMsg='An error occurred. Please try again.';switch(urlParams.get('error')){case 'empty_feedback':errorMsg='Please enter your feedback before submitting.';break;case 'feedback_too_long':errorMsg='Feedback is too long. Maximum 1000 characters.';break;case 'invalid_machine':errorMsg='Invalid machine selection.';break;case 'database':errorMsg='Database error. Please try again later.';break}feedbackMessage.innerHTML='<div class="alert alert-danger" role="alert" style="font-size:13px"><i class="bi bi-exclamation-triangle-fill me-2"></i>'+errorMsg+'</div>';setTimeout(()=>{window.history.replaceState({},document.title,window.location.pathname);feedbackMessage.innerHTML=''},5000)}
</script>

<?php include('../includes/footer.php'); ?>

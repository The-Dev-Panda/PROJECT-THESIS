# Machine Pages - Customization Guide

## 📁 Files Created

All machine HTML files have been converted to clean PHP files:

1. ✅ CableMachine.php
2. ✅ DeclineChestPress.php
3. ✅ LatPulldownSeatedCableRow.php
4. ✅ LegPressHackSquat.php
5. ✅ MultiPress.php
6. ✅ PecDeckFlyRearDelt.php
7. ✅ PullupStation.php
8. ✅ SeatedChestPress.php
9. ✅ ShoulderPress.php
10. ✅ SmithMachine.php

## 🎨 What's Changed

### Before (Old HTML files):
- ❌ Duplicated navigation code in every file
- ❌ Hardcoded headers and footers
- ❌ Difficult to maintain consistency
- ❌ 300+ lines per file with lots of boilerplate

### After (New PHP files):
- ✅ Uses centralized header and footer includes
- ✅ Clean, organized structure
- ✅ Easy to customize content
- ✅ Consistent navigation across all pages
- ✅ Session management built-in

## 📝 How to Customize Each Machine Page

### 1. **Replace Images/Videos**

Find the thumbs section (around line 70) and replace placeholder images:

```php
<div class="thumbs" id="thumbs">
    <!-- Replace these with your actual images -->
    <button type="button" data-src="../images/your-image-1.jpg" data-type="image" aria-label="View photo 1">
        <img src="../images/your-image-1.jpg" alt="Photo 1">
    </button>
    
    <!-- Add more images -->
    <button type="button" data-src="../images/your-image-2.jpg" data-type="image" aria-label="View photo 2">
        <img src="../images/your-image-2.jpg" alt="Photo 2">
    </button>
    
    <!-- To add a video -->
    <button type="button" data-src="../videos/your-video.mp4" data-type="video" aria-label="View video">
        <img src="../images/video-thumbnail.jpg" alt="Video">
    </button>
</div>
```

### 2. **Update Exercises**

Find the exercises section (around line 90) and modify:

```php
<div class="col">
    <article class="ex-card">
        <img src="../images/your-exercise-image.jpg" alt="Exercise Name">
        <div>
            <div style="font-weight:600">Exercise Name</div>
            <div style="font-size:13px;color:#9fb1c7">Primary muscle • Muscle Group</div>
        </div>
    </article>
</div>
```

### 3. **Modify Machine Details**

Find the meta-list section (around line 130):

```php
<ul class="meta-list" style="margin-top:10px">
    <li><strong>Equipment:</strong> Your Machine Name</li>
    <li><strong>Type:</strong> Machine Type</li>
    <li><strong>Difficulty:</strong> Beginner/Intermediate/Advanced</li>
    <li><strong>Focus Muscles:</strong> Primary muscles</li>
    <li><strong>Auxiliary Muscles:</strong> Secondary muscles</li>
</ul>
```

### 4. **Update Tips**

Find the Quick Tips section (around line 150):

```php
<ol style="margin-top:8px;color:#c9d6e1;font-size:14px">
    <li>Your first tip here.</li>
    <li>Your second tip here.</li>
    <li>Your third tip here.</li>
</ol>
```

### 5. **Change Page Title**

At the top of each file:

```php
$page_title = 'Your Machine Name | FIT-STOP Equipment';
```

## 🚀 Quick Start

1. **Open any machine PHP file** (e.g., CableMachine.php)
2. **Find the sections marked with comments** (Usage Gallery, Exercises, Machine Details, Quick Tips)
3. **Replace the placeholder content** with your actual images, text, and info
4. **Save the file** and refresh your browser

## 📸 Image Guidelines

- **Main gallery images**: JPG or PNG, 1200x800px recommended
- **Exercise thumbnails**: 300x225px recommended
- **Videos**: MP4 format, H.264 codec
- Store images in: `../images/`
- Store videos in: `../videos/`

## ✨ Features

All machine pages include:
- ✅ Interactive media gallery with image/video switching
- ✅ Full-screen gallery modal (shows up to 25 images in 5x5 grid)
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Feedback form submission with error handling
- ✅ Breadcrumb navigation
- ✅ Consistent header/footer with session management

## 🔄 Benefits of New Structure

1. **Easy Updates**: Change header/footer once, affects all pages
2. **Session Support**: Logged-in users see personalized navigation
3. **Clean Code**: Easy to read and modify
4. **Maintainable**: No code duplication
5. **Professional**: Uses PHP includes like real web applications

## 📌 Important Notes

- Keep the old HTML files as backup (you can delete them later)
- The PHP files use `../includes/header.php` and `../includes/footer.php`
- Make sure your web server supports PHP
- Test each page after making changes

## 💡 Example: Customizing Cable Machine

Open `CableMachine.php` and:

1. Line 11: Change title if needed
2. Line 70-80: Add your images/videos to thumbs section
3. Line 90-140: Update exercise cards with your images
4. Line 150-160: Modify machine details
5. Line 165-170: Update tips

That's it! The page will automatically use your includes and maintain consistency.

---

**Need help?** Refer to `CableMachine.php` as the main template - it has detailed comments throughout.

## FIT-STOP Design System Documentation

Based on analysis of index.html, machine.html, equipment.html, aboutus.html, and styles.css:

---

## 🎨 **COLOR PALETTE**

### Primary Colors
- **Void Black**: `#0a0a0a` (Background)
- **Hazard Yellow**: `#FFCC00` (Brand/Accent)
- **Pure White**: `#ffffff` (Primary text)

### Surface Colors
- **Surface**: `#141414` (Secondary background)
- **Card**: `#1a1a1a` (Component backgrounds)
- **Border**: `#333` (Dividers/borders)

### Text Colors
- **Primary**: `#ffffff`
- **Muted**: `#a0a0a0` / `#777` (Secondary text)
- **Success Badge**: `bg-success` (Bootstrap green)

### Additional Colors
- **Dark Overlay**: `rgba(10,10,10,0.95)`
- **Card Hover**: `#555` (Border on hover)
- **Highlight Hover**: `#e6b800` (Yellow hover state)

---

## 📝 **TYPOGRAPHY**

### Font Families
```css
/* Headers & Brand */
font-family: 'Chakra Petch', sans-serif;
- Weights: 400, 600, 700

/* Body Text */
font-family: 'Inter', sans-serif;
- Weights: 300, 400, 600
```

### Font Sizing
- **Display/Hero**: `4rem` (h1), `display-6`, `display-3`
- **Headings**: h2-h5 (default Bootstrap sizes)
- **Body**: `1.25rem` (lead/hero paragraph)
- **Small Text**: `.small`, `0.6rem`, `0.65rem`, `0.7rem`, `0.8rem`, `13px`
- **Monospace**: `.font-monospace` (for technical labels)

### Text Styles
- **Transform**: `text-transform: uppercase` (All headers + brand)
- **Letter Spacing**: `letter-spacing: 1px` (Headers)
- **Line Height**: `line-height: 1` (h1), `1.8` (paragraphs)

---

## 🔘 **BUTTONS**

### Primary Hazard Button
```css
.btn-hazard {
    background-color: #FFCC00;
    color: #000;
    font-weight: 700;
    text-transform: uppercase;
    padding: 0.6rem 1.5rem;
    font-family: 'Chakra Petch', sans-serif;
    /* Clipped corners */
    clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
}

/* Hover State */
background-color: #e6b800;
transform: translateY(-2px);
box-shadow: 0 0 15px rgba(255, 204, 0, 0.4);
```

### Outline Button
```css
.btn-outline-hazard {
    background: transparent;
    border: 1px solid #FFCC00;
    color: #FFCC00;
    /* Same clip-path and hover effects */
}

/* Hover */
background: rgba(255, 204, 0, 0.1);
color: #fff;
border-color: #fff;
```

### Size Variants
- **Default**: `0.6rem 1.5rem` padding
- **Large**: `.btn-lg`
- **Small**: `.btn-sm`

---

## 🧩 **COMPONENTS**

### Navigation Bar
```css
.navbar {
    background: rgba(10, 10, 10, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid #333;
    padding: 1rem 0;
    position: fixed-top;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    color: yellow;
    border: 2px solid #FFCC00;
    padding: 0.2rem 0.8rem;
    text: "[FIT-STOP]"
}
```

### Feature Cards
```css
.feature-card {
    background-color: #1a1a1a;
    border: 1px solid #333;
    padding: 2rem;
    height: 100%;
    transition: all 0.3s ease;
}

/* Left accent bar (appears on hover) */
::before {
    width: 4px;
    background-color: #FFCC00;
    opacity: 0;
}

/* Hover State */
transform: translateY(-5px);
border-color: #555;
::before { opacity: 1; }
```

### Pricing Cards
```css
.pricing-card {
    background: #141414;
    border: 1px solid #333;
}

.pricing-header {
    background: #000;
    padding: 2rem;
    border-bottom: 1px dashed #333;
}

.pricing-body {
    padding: 2rem;
}

/* Featured variant */
.pricing-card.featured {
    border: 1px solid #FFCC00;
    /* "RECOMMENDED" badge on top */
}
```

### Badges & Labels
```css
/* Status Badge */
<span class="badge bg-success">OPERATIONAL</span>

/* Info Badge */
<div class="bg-warning text-black px-2 py-1 fw-bold small brand-font">
    <i class="fa-solid fa-bolt me-1"></i> LIVE SYSTEM ACTIVE
</div>
```

### Machine/Equipment Cards
```css
.machine-card {
    background: rgba(255,255,255,0.03);
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.03);
}

.machine-card img {
    height: 150px;
    object-fit: cover;
}

.machine-card .body {
    padding: 12px;
}
```

---

## 🎭 **DECORATIVE ELEMENTS**

### Hazard Stripes (Section Divider)
```css
.hazard-stripes {
    height: 10px;
    background: repeating-linear-gradient(
        45deg,
        #FFCC00,
        #FFCC00 10px,
        #000 10px,
        #000 20px
    );
}
```

### Profile Images
```css
.profile-img-container {
    width: 120px; /* Standard: 200px for owner */
    height: 120px;
    margin: 0 auto 1.5rem;
}

.profile-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #FFCC00;
    background-color: #222;
}
```

---

## 📐 **LAYOUT PATTERNS**

### Hero Section
```css
.hero-section {
    min-height: 90vh;
    display: flex;
    align-items: center;
    background-image: linear-gradient(rgba(10,10,10,0.8), rgba(10,10,10,0.8)), url(...);
    background-size: cover;
    background-position: center;
    border-bottom: 2px solid #FFCC00;
}
```

### Content Sections
- Padding: `py-5` (top/bottom)
- Inner Container: `py-5` (additional padding)
- Grid: Bootstrap grid system (`.row`, `.col-md-*`, `.col-lg-*`)
- Gaps: `gap-3`, `gap-4`, `g-4`

### App Interface Mockup
```css
.app-interface {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 20px;
    padding: 15px;
    box-shadow: 0 0 30px rgba(0,0,0,0.8), 0 0 10px rgba(255, 204, 0, 0.1);
    max-width: 350px;
}

.app-screen {
    background: #000;
    border-radius: 12px;
    border: 1px solid #333;
}
```

---

## 🎬 **ANIMATIONS**

### Float Animation
```css
@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
    100% { transform: translateY(0px); }
}

.phone-mockup-container {
    animation: float 6s ease-in-out infinite;
}
```

### Hover Transitions
```css
transition: all 0.3s ease; /* Standard for cards */
transition: 0.2s; /* For links */
```

---

## 🔤 **ICONS**

**Library**: Font Awesome 6.4.0
```html
<!-- Common Icons -->
<i class="fa-solid fa-lock"></i>
<i class="fa-solid fa-bolt"></i>
<i class="fa-solid fa-dumbbell"></i>
<i class="fa-solid fa-brain"></i>
<i class="fa-solid fa-warehouse"></i>
<i class="fa-solid fa-people-group"></i>
<i class="fa-solid fa-check"></i>
<i class="fa-solid fa-xmark"></i>
```

**Icon Sizing**:
- Feature Icons: `font-size: 2rem`, `fa-3x`, `fa-4x`
- Small Icons: Default size with margin (e.g., `me-1`, `me-2`)

---

## 🌐 **UTILITY CLASSES**

### Spacing
- Margins: `mb-3`, `mt-4`, `me-4` (Bootstrap standard)
- Padding: `p-3`, `p-4`, `px-2`, `py-1`

### Borders
- Standard: `border border-secondary`
- Custom: `border-bottom: 1px solid #333`
- Dashed: `border-bottom: 1px dashed #333`

### Text Utilities
- `.text-hazard` - Yellow accent color
- `.text-muted` - Gray secondary text
- `.text-white` - White text
- `.fw-bold` - Bold weight
- `.small` - Smaller font size

### Background
- `.bg-dark` - Bootstrap dark
- `.bg-black` - Pure black

---

## 📱 **RESPONSIVE BREAKPOINTS**

Uses Bootstrap 5.3.0 breakpoints:
- **xs**: < 576px
- **sm**: ≥ 576px
- **md**: ≥ 768px
- **lg**: ≥ 992px
- **xl**: ≥ 1200px

Common patterns:
```html
<div class="col-md-6 col-lg-4">  <!-- 2 cols on tablet, 3 on desktop -->
<div class="col-md-4 col-lg-3">  <!-- 3 cols on tablet, 4 on desktop -->
```

---

## 🎯 **DESIGN PRINCIPLES**

1. **Industrial Tech Aesthetic**: Dark backgrounds, yellow hazard accents, clipped button corners
2. **High Contrast**: Black backgrounds with white text and yellow highlights
3. **Uppercase Typography**: All headings and buttons use uppercase for bold impact
4. **Hover Feedback**: Subtle lift (`translateY(-5px)`) and border color changes
5. **Consistent Spacing**: 2rem padding for cards, 1rem for navbar
6. **Monospace Labels**: Technical/data labels use monospace font
7. **Icon-First Design**: Every feature/zone uses an icon for visual hierarchy

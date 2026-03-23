<?php 
// sidebar.php - reusable sidebar + offcanvas for user pages

$activePage = $activePage ?? '';

// TOP LINKS (scrollable)
$topLinks = [
  ['href'=>'user.php','icon'=>'bi-grid-1x2','label'=>'Dashboard','key'=>'dashboard'],
  ['href'=>'bmi.php','icon'=>'bi-heart-pulse','label'=>'BMI Tracker','key'=>'bmi'],
  ['href'=>'myplan.php','icon'=>'bi-clipboard-check','label'=>'My Plan','key'=>'myplan'],
  ['href'=>'history.php','icon'=>'bi-clock-history','label'=>'History','key'=>'history'],
  ['href'=>'payments.php','icon'=>'bi-credit-card','label'=>'Payments','key'=>'payments'],
  ['href'=>'AI_ADVISOR.php','icon'=>'bi-robot','label'=>'AI Advisor','key'=>'ai'],
  ['href'=>'profile.php','icon'=>'bi-person','label'=>'Profile','key'=>'profile'],
];

// BOTTOM LINKS (fixed)
$bottomLinks = [
  ['href'=>'settings.php','icon'=>'bi-gear','label'=>'Settings','key'=>'settings'],
  ['href'=>'logout.php','icon'=>'bi-box-arrow-right','label'=>'Logout','key'=>'logout'],
];

function renderSidebarLinks($links, $activePage) {
  foreach ($links as $link) {
    $active = $link['key'] === $activePage ? 'active' : '';
    echo '<li class="' . $active . '">
            <a href="' . htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') . '">
              <i class="bi ' . htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') . '"></i>
              <span>' . htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') . '</span>
            </a>
          </li>';
  }
}

function renderSidebarStatic($activePage, $topLinks, $bottomLinks) {
  ob_start();
  ?>
  <aside class="sidebar sidebar-static">
    <div class="sidebar-header">
      <img src="userimage/FIT-STOP LOGO.png" alt="Fit-Stop Logo" class="logo-img" />
      <span class="logo-text">Fit-Stop</span>
    </div>

    <div class="menu-container">
      <ul class="menu top-menu">
        <?php renderSidebarLinks($topLinks, $activePage); ?>
      </ul>

      <ul class="menu bottom-menu">
        <?php renderSidebarLinks($bottomLinks, $activePage); ?>
      </ul>
    </div>
  </aside>
  <?php
  return ob_get_clean();
}

function renderSidebarOffcanvas($activePage, $topLinks, $bottomLinks) {
  ob_start();
  ?>
  <div class="sidebar-offcanvas">
    <div class="sidebar-header">
      <img src="userimage/FIT-STOP LOGO.png" alt="Fit-Stop Logo" class="logo-img" />
      <span class="logo-text">Fit-Stop</span>
    </div>

    <div class="menu-container">
      <ul class="menu top-menu">
        <?php renderSidebarLinks($topLinks, $activePage); ?>
      </ul>

      <ul class="menu bottom-menu">
        <?php renderSidebarLinks($bottomLinks, $activePage); ?>
      </ul>
    </div>
  </div>
  <?php
  return ob_get_clean();
}

echo renderSidebarStatic($activePage, $topLinks, $bottomLinks);
?>
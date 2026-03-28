<?php 
// sidebar.php - reusable sidebar + offcanvas for user pages

$activePage = $activePage ?? '';

function getSidebarLinks() {
    return [
        'top' => [
            ['href' => 'user.php', 'icon' => 'bi-grid-1x2', 'label' => 'Dashboard', 'key' => 'dashboard'],
            ['href' => 'bmi.php', 'icon' => 'bi-heart-pulse', 'label' => 'BMI Tracker', 'key' => 'bmi'],
            ['href' => 'myplan.php', 'icon' => 'bi-clipboard-check', 'label' => 'My Plan', 'key' => 'myplan'],
            ['href' => 'history.php', 'icon' => 'bi-clock-history', 'label' => 'History', 'key' => 'history'],
            ['href' => 'payments.php', 'icon' => 'bi-credit-card', 'label' => 'Payments', 'key' => 'payments'],
            ['href' => 'AI_ADVISOR.php', 'icon' => 'bi-robot', 'label' => 'AI Advisor', 'key' => 'ai'],
            ['href' => 'profile.php', 'icon' => 'bi-person', 'label' => 'Profile', 'key' => 'profile'],
        ],
        'bottom' => [
            ['href' => 'settings.php', 'icon' => 'bi-gear', 'label' => 'Settings', 'key' => 'settings'],
            ['href' => 'logout.php', 'icon' => 'bi-box-arrow-right', 'label' => 'Logout', 'key' => 'logout'],
        ],
    ];
}

function renderSidebarLinks($links, $activePage) {
    foreach ($links as $link) {
        $activeClass = ($link['key'] === $activePage) ? 'active' : '';

        if ($link['key'] === 'logout') {
            echo '<li class="' . $activeClass . '">';
            echo '<form action="../Login/logout.php" method="POST" style="display: none;">' . fitstop_csrf_input() . '</form>';
            echo '<a href="#" onclick="event.preventDefault(); this.previousElementSibling.submit();">';
            echo '<i class="bi ' . htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') . '"></i>';
            echo '<span>' . htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') . '</span>';
            echo '</a></li>';
        } else {
            echo '<li class="' . $activeClass . '">';
            echo '<a href="' . htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') . '">';
            echo '<i class="bi ' . htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') . '"></i>';
            echo '<span>' . htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') . '</span>';
            echo '</a></li>';
        }
    }
}

function renderSidebarStatic($activePage) {
    $links = getSidebarLinks();

    ob_start();
    ?>
    <aside class="sidebar sidebar-static">
        <div class="sidebar-header">
            <img src="userimage/FIT-STOP LOGO.png" alt="Fit-Stop Logo" class="logo-img" />
            <a href=""></a>
            <span class="logo-text">Fit-Stop</span>
        </div>

        <div class="menu-container">
            <ul class="menu top-menu">
                <?php renderSidebarLinks($links['top'], $activePage); ?>
            </ul>

            <ul class="menu bottom-menu">
                <?php renderSidebarLinks($links['bottom'], $activePage); ?>
            </ul>
        </div>
    </aside>
    <?php
    return ob_get_clean();
}

function renderSidebarOffcanvas($activePage) {
    $links = getSidebarLinks();

    ob_start();
    ?>
    <div class="sidebar-offcanvas">
        <div class="sidebar-header">
            <img src="userimage/FIT-STOP LOGO.png" alt="Fit-Stop Logo" class="logo-img" />
            <span class="logo-text">Fit-Stop</span>
        </div>

        <div class="menu-container">
            <ul class="menu top-menu">
                <?php renderSidebarLinks($links['top'], $activePage); ?>
            </ul>

            <ul class="menu bottom-menu">
                <?php renderSidebarLinks($links['bottom'], $activePage); ?>
            </ul>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function renderMobileTopbar() {
    ob_start();
    ?>
    <div class="mobile-topbar">
        <button id="hamburgerBtn" class="hamburger-btn" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
        <div class="topbar-title">FITSTOP</div>

    </div>
    <?php
    return ob_get_clean();
}

// Render mobile topbar and sidebar.
echo renderMobileTopbar();
echo renderSidebarStatic($activePage);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const hamburger = document.getElementById('hamburgerBtn');
  const sidebar = document.querySelector('.sidebar');

  if (hamburger && sidebar) {
    hamburger.addEventListener('click', function() {
      sidebar.classList.toggle('mobile-active');
    });
  }
});
</script>

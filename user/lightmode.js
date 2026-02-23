(function () {
  /* --- Apply saved theme immediately on page load --- */
  function applyTheme() {
    const saved = localStorage.getItem("fitstop-theme"); // 'dark' | 'light'
    if (saved === "light") {
      document.body.classList.add("light-mode");
    } else {
      document.body.classList.remove("light-mode");
    }
  }

  /* --- Save and broadcast theme change --- */
  function setTheme(mode) {
    localStorage.setItem("fitstop-theme", mode);
    applyTheme();
  }

  /* --- Sync the toggle in settings.html if it exists --- */
  function syncToggle() {
    const toggle = document.getElementById("darkModeToggle");
    if (!toggle) return;

    const saved = localStorage.getItem("fitstop-theme");

    // Toggle is ON  = dark mode (default)
    // Toggle is OFF = light mode
    toggle.checked = saved !== "light";

    toggle.addEventListener("change", function () {
      setTheme(this.checked ? "dark" : "light");
    });
  }

  /* --- Run on every page load --- */
  document.addEventListener("DOMContentLoaded", function () {
    applyTheme();
    syncToggle();
  });

  /* --- Also apply immediately (before DOMContentLoaded) to avoid flash --- */
  applyTheme();
})();

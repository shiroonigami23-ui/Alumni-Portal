(function () {
  var THEME_KEY = "alumni_portal_theme";
  var THEMES = ["ocean", "dark", "light"];

  function normalizeTheme(value) {
    return THEMES.indexOf(value) >= 0 ? value : "ocean";
  }

  function applyTheme(theme) {
    var actual = normalizeTheme(theme);
    var root = document.documentElement;
    if (actual === "ocean") {
      root.removeAttribute("data-theme");
    } else {
      root.setAttribute("data-theme", actual);
    }
    localStorage.setItem(THEME_KEY, actual);
    updateToggleLabel(actual);
  }

  function getNextTheme(current) {
    var idx = THEMES.indexOf(current);
    if (idx < 0) return THEMES[0];
    return THEMES[(idx + 1) % THEMES.length];
  }

  function updateToggleLabel(theme) {
    var btn = document.getElementById("themeModeBtn");
    if (!btn) return;
    var labels = {
      ocean: "Ocean",
      dark: "Dark",
      light: "Light",
    };
    btn.setAttribute("title", "Theme: " + labels[theme] + " (click to cycle)");
    btn.setAttribute("aria-label", "Theme: " + labels[theme]);
  }

  function bindThemeToggle() {
    var btn = document.getElementById("themeModeBtn");
    if (!btn || btn.dataset.bound === "1") return;
    btn.dataset.bound = "1";

    btn.addEventListener("click", function () {
      var current = normalizeTheme(localStorage.getItem(THEME_KEY) || "ocean");
      applyTheme(getNextTheme(current));
    });
  }

  function revealAnimation() {
    var targets = document.querySelectorAll(
      ".bg-white, .rounded-xl, .post-card, .alumni-card, section"
    );
    var list = Array.prototype.slice.call(targets).slice(0, 48);
    list.forEach(function (el, idx) {
      if (el.classList.contains("vu-reveal")) return;
      el.classList.add("vu-reveal");
      setTimeout(function () {
        el.classList.add("vu-in");
      }, Math.min(600, idx * 35));
    });
  }

  function init() {
    var saved = normalizeTheme(localStorage.getItem(THEME_KEY) || "ocean");
    applyTheme(saved);
    bindThemeToggle();
    revealAnimation();
  }

  document.addEventListener("DOMContentLoaded", init);
})();

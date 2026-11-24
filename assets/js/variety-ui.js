(function () {
  var THEME_KEY = "alumni_portal_theme";
  var THEMES = ["dark", "light", "ocean"];

  function normalizeTheme(value) {
    return THEMES.indexOf(value) >= 0 ? value : "dark";
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

  function addSkipLink() {
    if (document.querySelector(".vu-skip")) return;
    var main = document.querySelector("main, [role='main'], #main-content, .main-content");
    if (!main) return;
    if (!main.id) main.id = "main-content";
    var a = document.createElement("a");
    a.href = "#" + main.id;
    a.className = "vu-skip";
    a.textContent = "Skip to content";
    document.body.insertBefore(a, document.body.firstChild);
  }


  /* ---------------------------------------------------------------- toasts */
  function toast(message, type) {
    type = type || "info";
    var host = document.getElementById("vu-toasts");
    if (!host) {
      host = document.createElement("div");
      host.id = "vu-toasts";
      host.setAttribute("role", "status");
      host.setAttribute("aria-live", "polite");
      document.body.appendChild(host);
    }
    var icons = { success: "✓", error: "!", warning: "!", info: "i" };
    var el = document.createElement("div");
    el.className = "vu-toast vu-toast-" + type;
    var ic = document.createElement("span");
    ic.className = "vu-toast-icon";
    ic.textContent = icons[type] || "i";
    var tx = document.createElement("span");
    tx.textContent = String(message || "");
    var x = document.createElement("button");
    x.type = "button";
    x.className = "vu-toast-x";
    x.setAttribute("aria-label", "Dismiss");
    x.textContent = "×";
    el.appendChild(ic); el.appendChild(tx); el.appendChild(x);
    host.appendChild(el);
    requestAnimationFrame(function () { el.classList.add("vu-in"); });
    var close = function () { el.classList.remove("vu-in"); setTimeout(function () { el.remove(); }, 250); };
    x.addEventListener("click", close);
    setTimeout(close, type === "error" ? 6000 : 3800);
  }
  window.toast = toast;

  /* ------------------------------------------------------- command palette */
  function prefix() {
    return (window.PORTAL_BASE_PREFIX || (location.pathname.indexOf("/admin/") >= 0 ? "../" : ""));
  }
  function userRole() {
    try { return (JSON.parse(localStorage.getItem("user_data") || "{}").role) || ""; } catch (e) { return ""; }
  }
  function paletteItems() {
    var p = prefix();
    var items = [
      ["Dashboard", "dashboard.php", "home overview"], ["Feed", "feed.php", "posts timeline"],
      ["Find alumni", "discovery.php", "directory search people"], ["Alumni Atlas", "atlas.php", "map cities companies batches"],
      ["Referral board", "referrals.php", "jobs refer referral"], ["My referral requests", "referrals.php#requests", "status"],
      ["Jobs", "jobs.php", "careers openings"], ["Events", "events.php", "meetups rsvp"],
      ["Mentorship", "mentorship.php", "mentor mentee"], ["Messages", "messages.php", "chat inbox dm"],
      ["My profile", "profile.php", "me account"], ["Settings", "settings.php", "privacy password account"],
      ["Edit profile", "settings.php#profile", "bio photo"], ["Help centre", "help.php", "faq support"],
      ["Code of conduct", "conduct.php", "rules"], ["Privacy policy", "policy.php", "data"]
    ].map(function (i) { return { label: i[0], href: p + i[1], keys: i[2], group: "Go to" }; });
    if (userRole() === "admin") {
      [["Admin · Insights & control", "admin/insights.php", "stats security banner broadcast export"],
       ["Admin · Dashboard", "admin/dashboard.php", "panel"], ["Admin · Manage users", "admin/users.php", "ban approve"],
       ["Admin · Reports", "admin/reports.php", "flags"], ["Admin · Invite tokens", "admin/tokens.php", "codes"],
       ["Moderation queue", "moderation.php", "review posts"]].forEach(function (i) {
        items.push({ label: i[0], href: p + i[1], keys: i[2], group: "Admin" });
      });
    }
    items.push({ label: "Switch theme", action: function () { var b = document.getElementById("themeModeBtn"); if (b) b.click(); else applyTheme(getNextTheme(normalizeTheme(localStorage.getItem(THEME_KEY)))); }, keys: "dark light mode", group: "Actions" });
    if (typeof window.logout === "function") items.push({ label: "Sign out", action: function () { window.logout(); }, keys: "logout exit", group: "Actions" });
    return items;
  }

  var palette = null, paletteIdx = 0, paletteList = [];
  function buildPalette() {
    palette = document.createElement("div");
    palette.id = "vu-palette";
    palette.setAttribute("role", "dialog");
    palette.setAttribute("aria-modal", "true");
    palette.setAttribute("aria-label", "Quick search");
    palette.innerHTML = '<div class="vu-pal-box"><div class="vu-pal-head"><span aria-hidden="true">⌕</span>' +
      '<input id="vu-pal-input" type="text" autocomplete="off" spellcheck="false" placeholder="Search pages, people, actions…" aria-controls="vu-pal-list">' +
      '<kbd>Esc</kbd></div><ul id="vu-pal-list" role="listbox"></ul><div class="vu-pal-foot"><span><kbd>↑</kbd><kbd>↓</kbd> move</span><span><kbd>Enter</kbd> open</span><span><kbd>Ctrl</kbd><kbd>K</kbd> toggle</span></div></div>';
    document.body.appendChild(palette);
    palette.addEventListener("click", function (e) { if (e.target === palette) closePalette(); });
    var input = palette.querySelector("input");
    input.addEventListener("input", renderPalette);
    input.addEventListener("keydown", function (e) {
      if (e.key === "ArrowDown") { paletteIdx = Math.min(paletteList.length - 1, paletteIdx + 1); highlight(); e.preventDefault(); }
      else if (e.key === "ArrowUp") { paletteIdx = Math.max(0, paletteIdx - 1); highlight(); e.preventDefault(); }
      else if (e.key === "Enter") { runItem(paletteList[paletteIdx]); e.preventDefault(); }
      else if (e.key === "Escape") { closePalette(); }
    });
    palette.querySelector("ul").addEventListener("click", function (e) {
      var li = e.target.closest("li[data-i]");
      if (li) runItem(paletteList[+li.dataset.i]);
    });
  }
  function renderPalette() {
    var q = palette.querySelector("input").value.trim().toLowerCase();
    var items = paletteItems().filter(function (i) {
      return !q || (i.label + " " + i.keys).toLowerCase().indexOf(q) >= 0;
    });
    if (q.length >= 2) {
      var p = prefix(), enc = encodeURIComponent(q);
      items.push({ label: 'Search alumni for “' + q + '”', href: p + "discovery.php?search=" + enc, group: "Search" });
      items.push({ label: 'Referral offers matching “' + q + '”', href: p + "referrals.php?q=" + enc, group: "Search" });
      items.push({ label: 'Alumni at “' + q + '” on the Atlas', href: p + "atlas.php?company=" + enc, group: "Search" });
    }
    paletteList = items;
    paletteIdx = 0;
    var ul = palette.querySelector("ul"), html = "", last = "";
    items.forEach(function (i, n) {
      if (i.group !== last) { html += '<li class="vu-pal-group" role="presentation">' + i.group + "</li>"; last = i.group; }
      var safe = document.createElement("span"); safe.textContent = i.label;
      html += '<li role="option" data-i="' + n + '">' + safe.innerHTML + "</li>";
    });
    ul.innerHTML = html || '<li class="vu-pal-empty">No matches</li>';
    highlight();
  }
  function highlight() {
    palette.querySelectorAll("li[data-i]").forEach(function (li) {
      var on = +li.dataset.i === paletteIdx;
      li.classList.toggle("vu-active", on);
      li.setAttribute("aria-selected", on ? "true" : "false");
      if (on) li.scrollIntoView({ block: "nearest" });
    });
  }
  function runItem(i) {
    if (!i) return;
    closePalette();
    if (i.action) i.action(); else if (i.href) window.location.href = i.href;
  }
  function openPalette() {
    if (!palette) buildPalette();
    palette.classList.add("vu-open");
    var input = palette.querySelector("input");
    input.value = "";
    renderPalette();
    setTimeout(function () { input.focus(); }, 10);
  }
  function closePalette() { if (palette) palette.classList.remove("vu-open"); }
  window.openCommandPalette = openPalette;

  document.addEventListener("keydown", function (e) {
    var typing = /input|textarea|select/i.test((e.target && e.target.tagName) || "") || (e.target && e.target.isContentEditable);
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k") { e.preventDefault(); palette && palette.classList.contains("vu-open") ? closePalette() : openPalette(); }
    else if (e.key === "/" && !typing) { e.preventDefault(); openPalette(); }
  });

  /* -------------------------------------------------------------- banner */
  function loadBanner() {
    if (!window.fetch || document.getElementById("vu-banner")) return;
    var base = (typeof window.getApiBase === "function") ? window.getApiBase() : prefix() + "api";
    fetch(base.replace(/\/$/, "") + "/site_banner.php", { credentials: "same-origin" })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (res) {
        var b = res && res.data;
        if (!b || !b.message || localStorage.getItem("vu_banner_dismissed") === b.id) return;
        var bar = document.createElement("div");
        bar.id = "vu-banner";
        bar.className = "vu-banner vu-banner-" + (b.tone || "info");
        bar.setAttribute("role", "region");
        bar.setAttribute("aria-label", "Announcement");
        var msg = document.createElement("span"); msg.textContent = b.message; bar.appendChild(msg);
        if (b.link && /^(https:\/\/|\/|[a-z0-9_-]+\.php)/i.test(b.link)) {
          var a = document.createElement("a");
          a.href = /^https:|^\//i.test(b.link) ? b.link : prefix() + b.link;
          a.textContent = (b.link_text || "Learn more") + " →";
          if (/^https:/i.test(b.link)) { a.target = "_blank"; a.rel = "noopener noreferrer"; }
          bar.appendChild(a);
        }
        var x = document.createElement("button");
        x.type = "button"; x.setAttribute("aria-label", "Dismiss announcement"); x.textContent = "×";
        x.addEventListener("click", function () { localStorage.setItem("vu_banner_dismissed", b.id); bar.remove(); });
        bar.appendChild(x);
        document.body.insertBefore(bar, document.body.firstChild);
      })
      .catch(function () {});
  }

  /* ------------------------------------------------- palette trigger chip */
  function addPaletteHint() {
    if (document.getElementById("vu-kbar-btn") || !localStorage.getItem("jwt_token")) return;
    var b = document.createElement("button");
    b.id = "vu-kbar-btn"; b.type = "button";
    b.setAttribute("aria-label", "Open quick search (Ctrl+K)");
    b.innerHTML = '<span aria-hidden="true">⌕</span><span class="vu-kbar-text">Quick search</span><kbd>Ctrl K</kbd>';
    b.addEventListener("click", openPalette);
    document.body.appendChild(b);
  }

  function init() {
    addSkipLink();
    loadBanner();
    addPaletteHint();
    var saved = normalizeTheme(localStorage.getItem(THEME_KEY) || "dark");
    applyTheme(saved);
    bindThemeToggle();
    revealAnimation();
  }

  document.addEventListener("DOMContentLoaded", init);
})();

/*
 * motion.js — tasteful motion for the whole portal.
 *   • count-up numbers         window.countUp(el, value)  + auto for [data-countup]
 *   • staggered entrance       new cards fade/slide in as they are rendered
 *   • button ripple            any button / .btn
 *   • confetti                 window.celebrate()  (fires on success toasts that contain 🎉)
 * Everything is skipped when the user prefers reduced motion.
 */
(function () {
  "use strict";
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------------------------------------------------------- count-up */
  function parseNum(text) {
    var m = String(text || "").replace(/,/g, "").match(/-?\d+(\.\d+)?/);
    return m ? parseFloat(m[0]) : null;
  }
  function countUp(el, to, opts) {
    if (!el) return;
    opts = opts || {};
    var target = typeof to === "number" ? to : parseNum(to);
    if (target === null || reduce) { if (to !== undefined) el.textContent = String(to); return; }
    var suffix = opts.suffix || "";
    var from = typeof opts.from === "number" ? opts.from : 0;
    var dur = opts.duration || Math.min(1400, 500 + Math.log10(Math.abs(target) + 1) * 350);
    var start = performance.now();
    var fmt = function (n) { return Math.round(n).toLocaleString("en-IN") + suffix; };
    function tick(now) {
      var t = Math.min(1, (now - start) / dur);
      var eased = 1 - Math.pow(1 - t, 3);
      el.textContent = fmt(from + (target - from) * eased);
      if (t < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
  window.countUp = countUp;

  // Auto count-up: any element marked data-countup animates whenever a number is written into it.
  var counting = new WeakSet();
  function watchCounters(root) {
    (root || document).querySelectorAll("[data-countup]").forEach(function (el) {
      if (el.__vuWatched) return;
      el.__vuWatched = true;
      new MutationObserver(function () {
        if (counting.has(el)) return;
        var n = parseNum(el.textContent);
        if (n === null) return;
        counting.add(el);
        var suffix = el.textContent.replace(/[\d,.\s-]/g, "");
        countUp(el, n, { suffix: suffix });
        setTimeout(function () { counting.delete(el); }, 1600);
      }).observe(el, { childList: true, characterData: true, subtree: true });
    });
  }

  /* ---------------------------------------------------- staggered entrance */
  var ENTER = ".job-card, .ins-kpi, .atlas-stat, article, .post-card, .alumni-card, [data-enter]";
  function enter(nodes) {
    if (reduce) return;
    var i = 0;
    nodes.forEach(function (n) {
      if (n.__vuEntered || !n.matches || !n.matches(ENTER)) return;
      n.__vuEntered = true;
      n.classList.add("vu-enter");
      n.style.animationDelay = Math.min(i++ * 45, 540) + "ms";
    });
  }
  function observeEntrances() {
    enter(Array.prototype.slice.call(document.querySelectorAll(ENTER)));
    new MutationObserver(function (muts) {
      var added = [];
      muts.forEach(function (m) {
        m.addedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return;
          if (n.matches(ENTER)) added.push(n);
          n.querySelectorAll && n.querySelectorAll(ENTER).forEach(function (c) { added.push(c); });
          if (n.querySelectorAll) watchCounters(n);
        });
      });
      if (added.length) enter(added);
    }).observe(document.body, { childList: true, subtree: true });
  }

  /* ---------------------------------------------------------------- ripple */
  function ripple(e) {
    if (reduce) return;
    var b = e.target.closest("button, .btn, a.bg-blue-600");
    if (!b || b.disabled) return;
    var r = b.getBoundingClientRect();
    var s = document.createElement("span");
    var d = Math.max(r.width, r.height) * 1.6;
    s.className = "vu-ripple";
    s.style.width = s.style.height = d + "px";
    s.style.left = (e.clientX - r.left - d / 2) + "px";
    s.style.top = (e.clientY - r.top - d / 2) + "px";
    if (getComputedStyle(b).position === "static") b.style.position = "relative";
    b.style.overflow = "hidden";
    b.appendChild(s);
    setTimeout(function () { s.remove(); }, 650);
  }

  /* -------------------------------------------------------------- confetti */
  function celebrate(opts) {
    if (reduce) return;
    opts = opts || {};
    var c = document.createElement("canvas");
    c.className = "vu-confetti";
    c.width = innerWidth * devicePixelRatio;
    c.height = innerHeight * devicePixelRatio;
    document.body.appendChild(c);
    var ctx = c.getContext("2d");
    ctx.scale(devicePixelRatio, devicePixelRatio);
    var colors = ["#2563eb", "#7c3aed", "#22c55e", "#f59e0b", "#ec4899", "#38bdf8"];
    var x0 = opts.x || innerWidth / 2, y0 = opts.y || innerHeight / 3;
    var parts = [];
    for (var i = 0; i < 140; i++) {
      var a = Math.random() * Math.PI * 2, v = 4 + Math.random() * 7;
      parts.push({ x: x0, y: y0, vx: Math.cos(a) * v, vy: Math.sin(a) * v - 5, r: 3 + Math.random() * 4,
        c: colors[i % colors.length], rot: Math.random() * 6, vr: (Math.random() - 0.5) * 0.4, life: 0 });
    }
    var start = performance.now();
    (function frame(now) {
      ctx.clearRect(0, 0, innerWidth, innerHeight);
      parts.forEach(function (p) {
        p.vy += 0.22; p.vx *= 0.99; p.x += p.vx; p.y += p.vy; p.rot += p.vr;
        ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rot); ctx.fillStyle = p.c;
        ctx.globalAlpha = Math.max(0, 1 - (now - start) / 2200);
        ctx.fillRect(-p.r, -p.r / 2, p.r * 2, p.r); ctx.restore();
      });
      if (now - start < 2200) requestAnimationFrame(frame); else c.remove();
    })(start);
  }
  window.celebrate = celebrate;

  // Celebrate success toasts that carry a party emoji (referred, offer live, …)
  function hookToasts() {
    var orig = window.toast;
    if (typeof orig !== "function" || orig.__vuHooked) return;
    var wrapped = function (msg, type) {
      orig(msg, type);
      if ((type || "success") === "success" && /🎉|referred|is live|posted successfully/i.test(String(msg))) celebrate();
    };
    wrapped.__vuHooked = true;
    window.toast = wrapped;
  }

  function init() {
    document.documentElement.classList.add("vu-motion");
    observeEntrances();
    watchCounters();
    document.addEventListener("pointerdown", ripple, { passive: true });
    hookToasts();
    setTimeout(hookToasts, 500);
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init); else init();
})();

/*
 * feed-extras.js — polls and scheduled posts for the feed composer.
 * Hooks used by pages/feed.php:
 *   feedExtras.beforeCreate()        → true when it handled the submit (scheduled post)
 *   feedExtras.afterPostCreated(id)  → attaches a poll to the new post
 *   feedExtras.decoratePost(el,post) → renders the poll widget on a post card
 */
(function () {
  "use strict";
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const notify = (m, t = "success") => (window.toast ? window.toast(m, t) : window.appAlert && window.appAlert(m));
  const $ = (id) => document.getElementById(id);

  /* ------------------------------------------------------------ composer UI */
  function injectComposer() {
    const toolbar = $("openGifPickerBtn")?.parentElement;
    const form = $("postContent")?.closest("form");
    if (!toolbar || !form || $("fxPollBtn")) return;

    toolbar.insertAdjacentHTML("beforeend", `
      <button id="fxPollBtn" type="button" class="p-2 rounded-full hover:bg-emerald-50 text-emerald-600" title="Add a poll" aria-pressed="false"><i data-lucide="bar-chart-3" class="h-4 w-4"></i></button>
      <button id="fxScheduleBtn" type="button" class="p-2 rounded-full hover:bg-amber-50 text-amber-600" title="Schedule for later" aria-pressed="false"><i data-lucide="calendar-clock" class="h-4 w-4"></i></button>`);

    const panel = document.createElement("div");
    panel.innerHTML = `
      <div id="fxPollPanel" class="hidden mt-3 rounded-xl border border-emerald-200 p-3 space-y-2">
        <div class="flex items-center justify-between"><span class="text-sm font-semibold text-gray-800">📊 Poll</span><button type="button" id="fxPollRemove" class="text-xs text-gray-500 hover:text-red-500">Remove poll</button></div>
        <input id="fxPollQ" maxlength="280" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Ask a question…">
        <div id="fxPollOpts" class="space-y-2"></div>
        <div class="flex items-center justify-between gap-2">
          <button type="button" id="fxPollAdd" class="text-xs font-medium text-emerald-700">+ Add option</button>
          <select id="fxPollDur" class="text-xs border border-gray-300 rounded-md px-2 py-1"><option value="">No end date</option><option value="24">1 day</option><option value="72">3 days</option><option value="168" selected>1 week</option></select>
        </div>
      </div>
      <div id="fxSchedPanel" class="hidden mt-3 rounded-xl border border-amber-200 p-3 flex flex-wrap items-center gap-2">
        <span class="text-sm font-semibold text-gray-800">🗓️ Publish on</span>
        <input id="fxSchedAt" type="datetime-local" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
        <span class="text-xs text-gray-500">Your local time. Text, GIF and poll are scheduled; photos/files must be posted now.</span>
        <button type="button" id="fxSchedOff" class="ml-auto text-xs text-gray-500 hover:text-red-500">Post now instead</button>
      </div>`;
    const actionsRow = $("submitPostBtn")?.closest(".mt-3");
    (actionsRow || form).before(panel);

    // Scheduled list below the composer
    const card = form.closest(".bg-white") || form.parentElement;
    card.insertAdjacentHTML("afterend", `
      <div id="fxScheduled" class="hidden bg-white rounded-xl shadow-sm p-4 mb-6">
        <div class="flex items-center justify-between mb-2"><h3 class="font-semibold text-gray-900 text-sm">🗓️ Your scheduled posts</h3><button type="button" id="fxSchedRefresh" class="text-xs text-gray-500">Refresh</button></div>
        <ul id="fxSchedList" class="divide-y divide-gray-100"></ul>
      </div>`);

    const addOpt = (val = "") => {
      const n = $("fxPollOpts").children.length;
      if (n >= 6) return;
      $("fxPollOpts").insertAdjacentHTML("beforeend", `<input maxlength="120" class="fx-opt w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Option ${n + 1}" value="${esc(val)}">`);
    };
    const togglePoll = (on) => {
      $("fxPollPanel").classList.toggle("hidden", !on);
      $("fxPollBtn").setAttribute("aria-pressed", String(on));
      if (on && !$("fxPollOpts").children.length) { addOpt(); addOpt(); }
      if (on) $("fxPollQ").focus();
    };
    const toggleSched = (on) => {
      $("fxSchedPanel").classList.toggle("hidden", !on);
      $("fxScheduleBtn").setAttribute("aria-pressed", String(on));
      const btn = $("submitPostBtn");
      if (btn) btn.textContent = on ? "Schedule" : "Post";
      if (on && !$("fxSchedAt").value) {
        const d = new Date(Date.now() + 60 * 60 * 1000);
        d.setMinutes(0, 0, 0);
        $("fxSchedAt").value = new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
      }
    };
    $("fxPollBtn").addEventListener("click", () => togglePoll($("fxPollPanel").classList.contains("hidden")));
    $("fxPollRemove").addEventListener("click", () => { togglePoll(false); $("fxPollQ").value = ""; $("fxPollOpts").innerHTML = ""; });
    $("fxPollAdd").addEventListener("click", () => addOpt());
    $("fxScheduleBtn").addEventListener("click", () => toggleSched($("fxSchedPanel").classList.contains("hidden")));
    $("fxSchedOff").addEventListener("click", () => toggleSched(false));
    $("fxSchedRefresh").addEventListener("click", loadScheduled);
    $("fxSchedList").addEventListener("click", onScheduledAction);
    window.lucide && window.lucide.createIcons();
    feedExtras._togglePoll = togglePoll;
    feedExtras._toggleSched = toggleSched;
    loadScheduled();
  }

  function pollDraft() {
    if ($("fxPollPanel")?.classList.contains("hidden")) return null;
    const q = $("fxPollQ").value.trim();
    const options = [...document.querySelectorAll(".fx-opt")].map((i) => i.value.trim()).filter(Boolean);
    const dur = $("fxPollDur").value;
    return { question: q, options, duration_hours: dur ? Number(dur) : null };
  }

  function validPoll(p) {
    if (!p) return true;
    if (p.question.length < 3) { notify("Write a poll question.", "error"); return false; }
    if (p.options.length < 2) { notify("Add at least two poll options.", "error"); return false; }
    return true;
  }

  /* ------------------------------------------------------------ scheduling */
  async function beforeCreate() {
    const poll = pollDraft();
    if (!validPoll(poll)) return true; // stop the normal submit
    if ($("fxSchedPanel")?.classList.contains("hidden")) return false;

    const at = $("fxSchedAt").value;
    if (!at) { notify("Pick a date and time.", "error"); return true; }
    if ($("postImages")?.files.length || $("postFiles")?.files.length) {
      notify("Photos and files can't be scheduled yet — post them now or remove them.", "error");
      return true;
    }
    const res = await window.makeApiCall("schedule_post.php", "POST", {
      content: $("postContent").value.trim(),
      gif_url: $("postGifUrl")?.value.trim() || "",
      visibility_scope: $("postVisibilityScope")?.value || "all",
      allow_comments: $("allowComments")?.checked ? "1" : "0",
      publish_at: new Date(at).toISOString(),
      poll,
    });
    if (res && res.success) {
      const when = new Date(res.publish_at).toLocaleString("en-IN", { dateStyle: "medium", timeStyle: "short" });
      notify(`Scheduled for ${when} 🗓️`, "success");
      if (typeof window.clearPostForm === "function") window.clearPostForm("Post scheduled. Draft cleared.");
      feedExtras._togglePoll(false); $("fxPollQ").value = ""; $("fxPollOpts").innerHTML = "";
      feedExtras._toggleSched(false); $("fxSchedAt").value = "";
      loadScheduled();
    } else {
      notify((res && res.message) || "Could not schedule the post.", "error");
    }
    return true;
  }

  async function afterPostCreated(postId) {
    const poll = pollDraft();
    if (!poll || !postId) return;
    const res = await window.makeApiCall("create_poll.php", "POST", { post_id: postId, ...poll });
    if (!res || !res.success) notify((res && res.message) || "Post published, but the poll could not be added.", "error");
    feedExtras._togglePoll(false); $("fxPollQ").value = ""; $("fxPollOpts").innerHTML = "";
  }

  async function loadScheduled() {
    const res = await window.makeApiCall("scheduled_posts.php");
    const rows = (res && res.success && res.data) || [];
    const show = rows.filter((r) => r.status === "pending" || r.status === "failed").slice(0, 10);
    $("fxScheduled")?.classList.toggle("hidden", show.length === 0);
    const fmt = (d) => { const t = window.parsePortalDate ? window.parsePortalDate(d) : new Date(String(d).replace(" ", "T") + "Z"); return t.toLocaleString("en-IN", { dateStyle: "medium", timeStyle: "short" }); };
    $("fxSchedList").innerHTML = show.map((r) => `
      <li class="py-2 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-xs font-semibold px-2 py-0.5 rounded-full ${r.status === "failed" ? "bg-red-100 text-red-700" : "bg-amber-100 text-amber-700"}">${r.status === "failed" ? "Failed" : fmt(r.publish_at)}</span>
        <span class="flex-1 min-w-0 truncate text-gray-700">${esc(r.preview || "(GIF)")}${r.poll_question ? ` · 📊 ${esc(r.poll_question)}` : ""}</span>
        ${r.status === "pending" ? `<button class="text-xs text-blue-600" data-fx="reschedule" data-id="${r.scheduled_id}">Reschedule</button>
        <button class="text-xs text-red-500" data-fx="cancel" data-id="${r.scheduled_id}">Cancel</button>` : `<span class="text-xs text-red-600">${esc(r.error || "")}</span>`}
      </li>`).join("");
  }

  async function onScheduledAction(e) {
    const b = e.target.closest("[data-fx]");
    if (!b) return;
    const id = Number(b.dataset.id);
    if (b.dataset.fx === "cancel") {
      if (!(await window.appConfirm("Cancel this scheduled post?", { title: "Cancel scheduled post", confirmText: "Cancel post" }))) return;
      const r = await window.makeApiCall("scheduled_posts.php", "POST", { action: "cancel", scheduled_id: id });
      notify((r && r.message) || "Done", r && r.success ? "success" : "error");
    } else {
      const v = await window.appPrompt("New date and time (e.g. 2026-12-01 18:30)", { title: "Reschedule", inputLabel: "When", confirmText: "Save" });
      if (!v) return;
      const d = new Date(String(v).replace(" ", "T"));
      if (isNaN(d)) { notify("That date doesn't look right.", "error"); return; }
      const r = await window.makeApiCall("scheduled_posts.php", "POST", { action: "reschedule", scheduled_id: id, publish_at: d.toISOString() });
      notify((r && r.message) || "Done", r && r.success ? "success" : "error");
    }
    loadScheduled();
  }

  /* ---------------------------------------------------------------- polls */
  function pollHtml(p) {
    const total = p.total_votes || 0;
    const showResults = p.my_vote || !p.is_open;
    const endsAt = p.expires_at ? (window.parsePortalDate ? window.parsePortalDate(p.expires_at) : new Date(p.expires_at)) : null;
    const status = !p.is_open ? "Final results" : endsAt ? `Ends ${endsAt.toLocaleString("en-IN", { dateStyle: "medium", timeStyle: "short" })}` : "Open";
    return `
      <div class="fx-poll mt-3 rounded-xl border border-gray-200 p-3" data-poll-id="${p.poll_id}">
        <p class="font-semibold text-gray-900 text-sm mb-2">📊 ${esc(p.question)}</p>
        <div class="space-y-2">
          ${p.options.map((o) => {
            const pct = total ? Math.round((o.votes / total) * 100) : 0;
            const mine = p.my_vote === o.option_id;
            return showResults
              ? `<div class="relative overflow-hidden rounded-lg border ${mine ? "border-blue-400" : "border-gray-200"} px-3 py-2 text-sm">
                   <div class="absolute inset-y-0 left-0 bg-blue-500/15 fx-poll-bar" style="width:${pct}%"></div>
                   <div class="relative flex justify-between gap-2"><span class="text-gray-800">${mine ? "✓ " : ""}${esc(o.text)}</span><span class="font-semibold text-gray-700">${pct}%</span></div>
                 </div>`
              : `<button type="button" class="fx-vote w-full text-left rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 hover:border-blue-500 hover:bg-blue-50" data-poll="${p.poll_id}" data-option="${o.option_id}">${esc(o.text)}</button>`;
          }).join("")}
        </div>
        <p class="text-xs text-gray-500 mt-2">${total} vote${total === 1 ? "" : "s"} · ${status}</p>
      </div>`;
  }

  function decoratePost(el, post) {
    if (!el || !post || !post.poll || el.querySelector(".fx-poll")) return;
    const wrap = document.createElement("div");
    wrap.innerHTML = pollHtml(post.poll);
    const block = el.querySelector("[data-post-content-block]");
    if (block) {
      block.appendChild(wrap.firstElementChild); // right under the post text, above the like/comment bar
    } else {
      const bar = el.querySelector(".like-btn")?.parentElement;
      bar ? bar.before(wrap.firstElementChild) : el.appendChild(wrap.firstElementChild);
    }
  }

  document.addEventListener("click", async (e) => {
    const v = e.target.closest(".fx-vote");
    if (!v) return;
    const box = v.closest(".fx-poll");
    box.querySelectorAll(".fx-vote").forEach((b) => (b.disabled = true));
    const r = await window.makeApiCall("vote_poll.php", "POST", { poll_id: Number(v.dataset.poll), option_id: Number(v.dataset.option) });
    if (r && r.success && r.poll) {
      box.outerHTML = pollHtml(r.poll);
    } else {
      notify((r && r.message) || "Could not record your vote.", "error");
      box.querySelectorAll(".fx-vote").forEach((b) => (b.disabled = false));
    }
  });

  const feedExtras = (window.feedExtras = { beforeCreate, afterPostCreated, decoratePost, loadScheduled });
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", injectComposer);
  else injectComposer();
})();

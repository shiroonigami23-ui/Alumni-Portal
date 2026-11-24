/* Output-safety helpers shared by every page. Loaded synchronously in <head>. */
window.escapeHtml = window.escapeHtml || function (v) {
    return String(v == null ? '' : v).replace(/[&<>"'`]/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '`': '&#96;' }[c];
    });
};
/* Only http(s), mailto, tel, relative and data:image URLs survive; anything else (javascript:, data:text…) becomes "#". */
window.safeUrl = window.safeUrl || function (u) {
    var s = String(u == null ? '' : u).trim();
    if (!s) return '';
    // Browsers ignore tabs/newlines/control chars inside a URL ("java\tscript:"), so test without them.
    var probe = s.replace(/[\u0000-\u0020\u007f-\u009f]/g, '').toLowerCase();
    if (/^(https?:|mailto:|tel:)/.test(probe) || /^data:image\/(png|jpe?g|gif|webp|svg\+xml)[;,]/.test(probe)) return s;
    if (/^[a-z][a-z0-9+.\-]*:/.test(probe) || probe.indexOf('//') === 0 || probe.indexOf('\\\\') === 0) return '#';
    return s;
};
var escapeHtml = window.escapeHtml, safeUrl = window.safeUrl;
/*
 * Turn @handles and http(s) links into links. Input MUST already be escaped with escapeHtml();
 * handles are [a-z0-9_] only and URLs stop at whitespace/quotes, so no markup can be introduced.
 */
window.linkifyText = window.linkifyText || function (escaped) {
    var s = String(escaped == null ? '' : escaped);
    s = s.replace(/\bhttps?:\/\/(?:(?!&(?:quot|lt|gt|#39|#96);)[^\s<>"'])+/gi, function (url) {
        var trail = '';
        var m = url.match(/[.,!?;:)]+$/);           // keep sentence punctuation outside the link
        if (m) { trail = m[0]; url = url.slice(0, -trail.length); }
        return '<a href="' + url + '" target="_blank" rel="noopener noreferrer nofollow" class="text-blue-600 hover:underline break-all">' + url + '</a>' + trail;
    });
    s = s.replace(/(^|[^\w@\/&#])@([a-zA-Z][a-zA-Z0-9_]{2,19})\b/g, function (all, pre, handle) {
        return pre + '<a href="profile.php?u=' + handle.toLowerCase() + '" class="mention text-blue-600 hover:underline">@' + handle + '</a>';
    });
    return s;
};
var linkifyText = window.linkifyText;

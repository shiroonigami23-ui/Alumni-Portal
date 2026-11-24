#!/usr/bin/env python3
"""
Front-end contract — runs in CI, no browser needed.

Every user-visible value inside an HTML template string must go through the shared helpers in
assets/js/safe.js (escapeHtml / safeUrl / linkifyText) or a page's own esc(); numbers may use Number().
Flags:
  1. ${obj.field} inside HTML markup with no escaping      (stored XSS — what hit 7 pages in 2.3)
  2. href="${…}" / src="${…}" without safeUrl()           (javascript: links)
  3. onclick="fn('${…}')" with a non-numeric value         (escaping can't protect JS strings in attributes)

Not every ${…} holds user data. A value that is known to be safe (static labels, pre-built HTML snippets)
can be marked on its line with   /* safe-html: reason */   and is then skipped — the reason is the review.

    python3 tests/static/frontend_contract.py
"""
import re, sys, glob, json, os
ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
FILES = sorted(set(glob.glob(f'{ROOT}/pages/*.php') + glob.glob(f'{ROOT}/admin/*.php') + glob.glob(f'{ROOT}/includes/*.php')
                   + [f'{ROOT}/index.php'] + glob.glob(f'{ROOT}/assets/js/*.js')))
ESCAPERS = re.compile(r'^\s*(escapeHtml|esc|escapeHTML|safeUrl|linkifyText|Number|parseInt|encodeURIComponent|formatDate|formatTime|timeAgo|escapeJs)\s*\(')
MEMBER = re.compile(r"^\s*[A-Za-z_$][\w$]*(\??\.[A-Za-z_$][\w$]*)+(\s*\|\|\s*('[^'`]*'|\"[^\"`]*\"|[A-Za-z_$][\w$.]*))?\s*$")
SAFE_FIELD = re.compile(r"\.(id|\w+_id|\w+Id|\w*_count|\w*Count|count|length|total|status|role|type|icon|tone|color|kind|provider|visibility|rsvp_status|post_type|severity|percent|level|year|graduation_year|joining_year)$")
HTMLISH = re.compile(r'<[a-zA-Z/!]|[\w-]="[^"]*\$\{|>\s*\$\{')

problems = []
for f in FILES:
    rel = os.path.relpath(f, ROOT)
    for n, line in enumerate(open(f, encoding='utf-8', errors='ignore'), 1):
        if '${' not in line or 'safe-html:' in line or not HTMLISH.search(line):
            continue
        if 'fetch(' in line or 'makeApiCall(' in line or 'console.' in line:
            continue
        for m in re.finditer(r'\$\{([^{}`]*)\}', line):
            expr = m.group(1)
            before = line[max(0, m.start() - 12):m.start()]
            if ESCAPERS.match(expr):
                # href/src must still be safeUrl'd (or be a page-local link builder)
                if re.search(r'(href|src)="$', before) and 'safeUrl' not in expr and not re.match(r"\s*(Number|encodeURIComponent)", expr):
                    if re.match(r'\s*(escapeHtml|esc)\(\s*[\w.?]+(url|Url|URL|link|href|src)\b', expr):
                        problems.append((rel, n, 'url without safeUrl()', expr.strip()))
                continue
            if not MEMBER.match(expr):
                continue                       # ternaries, function calls, nested templates: reviewed by hand
            first = expr.split('||')[0].strip()
            if SAFE_FIELD.search(first):
                continue                       # numbers / enums from our own API
            inside_js_attr = re.search(r'on\w+="[^"]*$', line[:m.start()]) is not None
            problems.append((rel, n, 'JS-string in attribute' if inside_js_attr else 'unescaped value in HTML', expr.strip()))

for rel, n, kind, expr in problems:
    print(f'  ✗ {rel}:{n}  {kind}: ${{{expr}}}')
out = os.environ.get('FRONTEND_OUT', f'{ROOT}/tests/results/frontend-contract.json')
os.makedirs(os.path.dirname(out), exist_ok=True)
json.dump({'files': len(FILES), 'problems': [dict(file=r, line=n, kind=k, expr=e) for r, n, k, e in problems]}, open(out, 'w'), indent=2)
print(f'{len(FILES)} front-end files, {len(problems)} unescaped values')
sys.exit(1 if problems else 0)

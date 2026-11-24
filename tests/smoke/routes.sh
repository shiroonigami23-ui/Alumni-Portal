#!/usr/bin/env bash
# Route + security smoke test. Starts PHP's built-in server with router.php and
# checks that every public URL resolves, aliases redirect, and internals stay hidden.
# Usage: bash tests/smoke/routes.sh            (needs php on PATH; no database required)
set -uo pipefail
cd "$(dirname "$0")/../.."
PORT=${PORT:-8765}
php -S 127.0.0.1:$PORT router.php >/tmp/ap-smoke.log 2>&1 &
PID=$!; trap 'kill $PID 2>/dev/null' EXIT; sleep 1
fail=0
check() { # path expected_code [expected_location_suffix]
  local out code loc
  out=$(curl -s -o /dev/null -w "%{http_code} %{redirect_url}" "http://127.0.0.1:$PORT$1")
  code=${out%% *}; loc=${out#* }
  if [[ "$code" != "$2" ]] || [[ -n "${3:-}" && "$loc" != *"$3" ]]; then
    printf '  \e[31mFAIL\e[0m %-34s got %s %s (want %s %s)\n' "$1" "$code" "$loc" "$2" "${3:-}"; fail=1
  else
    printf '  \e[32mok\e[0m   %-34s %s %s\n' "$1" "$code" "${3:-}"
  fi
}
echo "Pages (legacy .php + clean URLs)"
for p in "" index.php dashboard feed profile settings messages discovery mentorship jobs events moderation help live \
         login register forgot-password reset terms policy conduct; do
  check "/$p" 200; [[ -n "$p" && "$p" != *.php ]] && check "/$p.php" 200
done
echo "Redirects"
check /edit-profile.php 301 "/settings.php#profile"
check /signup 301 /register.php
check /privacy 301 /policy.php
check /admin 301 /admin/dashboard.php
check /feed/ 301 /feed
check /pages/feed.php 301 /feed.php
check /alumni_portal/feed.php 301 /feed.php
echo "Hidden internals"
for p in config/Database.php bootstrap/routes.php helpers/Logger.php includes/header.php api/_feed_schema.php \
         api/test.php storage/log.txt storage/verification/x.pdf deployment/db.sql nope.php; do check "/$p" 404; done
echo "Static assets"
for p in assets/css/variety-ui.css sw.js manifest.webmanifest offline.html assets/icons/app-icon-192.png healthz; do check "/$p" 200; done
echo "Security"
cors=$(curl -sI -H "Origin: https://evil.example" "http://127.0.0.1:$PORT/api/me.php" | grep -ci "access-control-allow-origin")
[[ "$cors" == "0" ]] && echo "  ok   hostile origin gets no CORS header" || { echo "  FAIL CORS leaked"; fail=1; }
csp=$(curl -sI "http://127.0.0.1:$PORT/feed.php" | grep -ci "content-security-policy")
[[ "$csp" -ge 1 ]] && echo "  ok   CSP header present" || { echo "  FAIL no CSP"; fail=1; }
if grep -q "PHP Fatal\|PHP Warning" /tmp/ap-smoke.log; then echo "  FAIL PHP warnings:"; grep "PHP Fatal\|PHP Warning" /tmp/ap-smoke.log | head; fail=1; fi
[[ $fail == 0 ]] && echo -e "\n\e[32mAll route checks passed.\e[0m" || echo -e "\n\e[31mSome checks failed.\e[0m"
exit $fail

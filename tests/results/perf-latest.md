# Performance (2026-11-24)

## 2.3.3 — merged queries (this release)

"Before" is 2.3.2 (`388156f`), "after" is 2.3.3. Same method as below: database behind
`tests/perf/latency_proxy.py` at 0.5 ms each way, OPcache on, requests interleaved, median of 150.

| Endpoint | 2.3.2 | 2.3.3 | Change | Round trips |
|---|---:|---:|---:|---|
| `get_user_profile.php?user_id=5` | 37.1 ms | 15.9 ms | **−57 %** | 11 → 4 (profile + counters + follow + block in one query; skills, badges, pins as JSON in one) |
| `success_stories.php?action=list` | 30.1 ms | 11.6 ms | **−62 %** | 8 → 2 (story texts batch-loaded) |
| `master_search.php?q=ana` | 20.6 ms | 12.0 ms | −42 % | 5 → 2 (jobs, events, referrals as JSON columns of one query) |
| `get_feed.php?sort=for_you` | 31.8 ms | 19.2 ms | −40 % | 8 → 4 (total via `COUNT(*) OVER ()`, viewer prefs inline, polls + options + my vote in one) |
| `mentorship.php?action=get_status` | 29.5 ms | 23.3 ms | −21 % | 8 → 6 |
| `search_directory.php?search=sh` | 19.5 ms | 16.5 ms | −15 % | 5 → 4 |
| `me.php`, notifications, conversations, events, jobs | — | — | ±2 % | already 1–3 |
| **All, median** | 19.4 ms | 12.2 ms | **−37 %** | |
| **All, p95** | 37.2 ms | 23.3 ms | **−37 %** | |

Also: 22 endpoints no longer look up the caller's role with their own query — they all ask
`api_user_role()` in `api/_kernel.php`, which answers from the sign-in query the kernel already ran.

## 2.3.2 — API kernel


Same database, same machine, requests interleaved between the two versions. "Before" is `0d7745c` (2.3.0),
"after" is 2.3.2. Database reached through `tests/perf/latency_proxy.py` at 0.5 ms each way (a typical
same-region cloud database); OPcache on, as in the Docker image. Median of 150 requests per endpoint.

| Endpoint | Before | After | Change |
|---|---:|---:|---:|
| `me.php` | 22.4 ms | 10.6 ms | −52 % |
| `get_feed.php?sort=for_you` | 106.3 ms | 31.1 ms | **−71 %** |
| `get_notifications.php` | 19.4 ms | 7.9 ms | −59 % |
| `get_unread_messages_count.php` | 18.6 ms | 7.4 ms | −60 % |
| `get_conversations.php` | 23.9 ms | 12.4 ms | −48 % |
| `search_directory.php?search=sh` | 30.4 ms | 19.0 ms | −38 % |
| `get_user_profile.php?user_id=5` | 73.4 ms | 36.6 ms | **−50 %** |
| `get_jobs.php` | 22.9 ms | 11.3 ms | −51 % |
| `master_search.php?q=ana` | 32.7 ms | 21.1 ms | −36 % |
| **All, median** | 24.3 ms | 12.6 ms | **−48 %** |
| **All, p95** | 106.9 ms | 37.0 ms | **−65 %** |

Where the time went (database round trips before any endpoint code): session, account status, shadow ban,
device ban, role, time zone = 6 → **1** (one joined query; device bans cached; time zone set when the
connection opens). Feed 29 → 8 queries (post texts loaded in one batch). Profile 19 → 11.

With PostgreSQL on the same machine (no added latency) both versions are within ±2 % of each other:
the kernel's own PHP cost is about 0.05–0.1 ms per request.

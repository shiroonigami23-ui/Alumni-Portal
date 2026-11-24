# Performance — before vs after the API kernel (2026-11-24)

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

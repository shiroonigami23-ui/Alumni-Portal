# UI smoke test results

**78 / 78 passed** · 2026-11-24 10:03 · Chrome via Playwright, signed in as each role.

Each page is opened in a real browser; a check fails on any JavaScript error, failed same-origin request, or blank page.

| | Role | Page | Load | Problems |
|---|---|---|---|---|
| ✅ | guest | `/` | 1549 ms |  |
| ✅ | guest | `/login.php` | 1362 ms |  |
| ✅ | guest | `/register.php` | 1384 ms |  |
| ✅ | guest | `/forgot-password.php` | 1283 ms |  |
| ✅ | guest | `/terms.php` | 1400 ms |  |
| ✅ | guest | `/policy.php` | 1417 ms |  |
| ✅ | guest | `/conduct.php` | 1379 ms |  |
| ✅ | guest | `/old-link-404` | 1269 ms |  |
| ✅ | admin | `login via UI` |  |  |
| ✅ | admin | `/dashboard.php` | 1622 ms |  |
| ✅ | admin | `/feed.php` | 1592 ms |  |
| ✅ | admin | `/profile.php` | 1587 ms |  |
| ✅ | admin | `/settings.php` | 1485 ms |  |
| ✅ | admin | `/messages.php` | 1410 ms |  |
| ✅ | admin | `/discovery.php` | 1424 ms |  |
| ✅ | admin | `/atlas.php` | 1601 ms |  |
| ✅ | admin | `/referrals.php` | 1475 ms |  |
| ✅ | admin | `/jobs.php` | 1418 ms |  |
| ✅ | admin | `/events.php` | 1394 ms |  |
| ✅ | admin | `/mentorship.php` | 1569 ms |  |
| ✅ | admin | `/live.php` | 1400 ms |  |
| ✅ | admin | `/library.php` | 1411 ms |  |
| ✅ | admin | `/help.php` | 1331 ms |  |
| ✅ | admin | `/admin/insights.php` | 1550 ms |  |
| ✅ | admin | `/admin/dashboard.php` | 1427 ms |  |
| ✅ | admin | `/admin/users.php` | 1433 ms |  |
| ✅ | admin | `/admin/reports.php` | 1402 ms |  |
| ✅ | admin | `/admin/tokens.php` | 1391 ms |  |
| ✅ | admin | `/admin/system-settings.php` | 1434 ms |  |
| ✅ | admin | `/moderation.php` | 1421 ms |  |
| ✅ | alumni | `login via UI` |  |  |
| ✅ | alumni | `/dashboard.php` | 1549 ms |  |
| ✅ | alumni | `/feed.php` | 1606 ms |  |
| ✅ | alumni | `/profile.php` | 1520 ms |  |
| ✅ | alumni | `/settings.php` | 1547 ms |  |
| ✅ | alumni | `/messages.php` | 1429 ms |  |
| ✅ | alumni | `/discovery.php` | 1460 ms |  |
| ✅ | alumni | `/atlas.php` | 1852 ms |  |
| ✅ | alumni | `/referrals.php` | 1452 ms |  |
| ✅ | alumni | `/jobs.php` | 1433 ms |  |
| ✅ | alumni | `/events.php` | 1488 ms |  |
| ✅ | alumni | `/mentorship.php` | 1496 ms |  |
| ✅ | alumni | `/live.php` | 1426 ms |  |
| ✅ | alumni | `/library.php` | 1402 ms |  |
| ✅ | alumni | `/help.php` | 1407 ms |  |
| ✅ | student | `login via UI` |  |  |
| ✅ | student | `/dashboard.php` | 1626 ms |  |
| ✅ | student | `/feed.php` | 1592 ms |  |
| ✅ | student | `/profile.php` | 1555 ms |  |
| ✅ | student | `/settings.php` | 1476 ms |  |
| ✅ | student | `/messages.php` | 1420 ms |  |
| ✅ | student | `/discovery.php` | 1430 ms |  |
| ✅ | student | `/atlas.php` | 1818 ms |  |
| ✅ | student | `/referrals.php` | 1392 ms |  |
| ✅ | student | `/jobs.php` | 1438 ms |  |
| ✅ | student | `/events.php` | 1402 ms |  |
| ✅ | student | `/mentorship.php` | 1537 ms |  |
| ✅ | student | `/live.php` | 1394 ms |  |
| ✅ | student | `/library.php` | 1457 ms |  |
| ✅ | student | `/help.php` | 1327 ms |  |
| ✅ | flow | `Feed draft survives a reload` |  |  |
| ✅ | flow | `Discard draft clears the composer` |  |  |
| ✅ | flow | `Create a post with a poll from the composer` |  |  |
| ✅ | flow | `Schedule mode switches the button to “Schedule”` |  |  |
| ✅ | flow | `Scheduled post appears in “Your scheduled posts”` |  |  |
| ✅ | flow | `Ctrl+K palette navigates` |  |  |
| ✅ | flow | `No JavaScript errors during admin flows` |  |  |
| ✅ | flow | `Vote on a poll and see results` |  |  |
| ✅ | flow | `Handle field rejects reserved names live` |  |  |
| ✅ | flow | `Handle field shows a free handle as available` |  |  |
| ✅ | flow | `Privacy page lists visibility toggles` |  |  |
| ✅ | flow | `/@handle opens the right profile` |  |  |
| ✅ | flow | `Private profile shows a locked card to non-followers` |  |  |
| ✅ | flow | `Follow button reads "Request to follow"` |  |  |
| ✅ | flow | `Sending a request switches the button to "Requested"` |  |  |
| ✅ | flow | `Owner approves the request from Settings` |  |  |
| ✅ | flow | `Approved follower sees the full profile` |  |  |
| ✅ | flow | `No JavaScript errors in privacy / handle flows` |  |  |

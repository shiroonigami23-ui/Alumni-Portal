# UI smoke test results

**78 / 78 passed** · 2026-11-24 14:33 · Chrome via Playwright, signed in as each role.

Each page is opened in a real browser; a check fails on any JavaScript error, failed same-origin request, or blank page.

| | Role | Page | Load | Problems |
|---|---|---|---|---|
| ✅ | guest | `/` | 1958 ms |  |
| ✅ | guest | `/login.php` | 1399 ms |  |
| ✅ | guest | `/register.php` | 1393 ms |  |
| ✅ | guest | `/forgot-password.php` | 1296 ms |  |
| ✅ | guest | `/terms.php` | 1414 ms |  |
| ✅ | guest | `/policy.php` | 1441 ms |  |
| ✅ | guest | `/conduct.php` | 1408 ms |  |
| ✅ | guest | `/old-link-404` | 1290 ms |  |
| ✅ | admin | `login via UI` |  |  |
| ✅ | admin | `/dashboard.php` | 1834 ms |  |
| ✅ | admin | `/feed.php` | 1676 ms |  |
| ✅ | admin | `/profile.php` | 1645 ms |  |
| ✅ | admin | `/settings.php` | 1593 ms |  |
| ✅ | admin | `/messages.php` | 1498 ms |  |
| ✅ | admin | `/discovery.php` | 1469 ms |  |
| ✅ | admin | `/atlas.php` | 1946 ms |  |
| ✅ | admin | `/referrals.php` | 1473 ms |  |
| ✅ | admin | `/jobs.php` | 1476 ms |  |
| ✅ | admin | `/events.php` | 1431 ms |  |
| ✅ | admin | `/mentorship.php` | 1581 ms |  |
| ✅ | admin | `/live.php` | 1464 ms |  |
| ✅ | admin | `/library.php` | 1495 ms |  |
| ✅ | admin | `/help.php` | 1351 ms |  |
| ✅ | admin | `/admin/insights.php` | 1573 ms |  |
| ✅ | admin | `/admin/dashboard.php` | 1433 ms |  |
| ✅ | admin | `/admin/users.php` | 1478 ms |  |
| ✅ | admin | `/admin/reports.php` | 1385 ms |  |
| ✅ | admin | `/admin/tokens.php` | 1437 ms |  |
| ✅ | admin | `/admin/system-settings.php` | 1440 ms |  |
| ✅ | admin | `/moderation.php` | 1413 ms |  |
| ✅ | alumni | `login via UI` |  |  |
| ✅ | alumni | `/dashboard.php` | 1755 ms |  |
| ✅ | alumni | `/feed.php` | 1583 ms |  |
| ✅ | alumni | `/profile.php` | 1667 ms |  |
| ✅ | alumni | `/settings.php` | 1506 ms |  |
| ✅ | alumni | `/messages.php` | 1444 ms |  |
| ✅ | alumni | `/discovery.php` | 1601 ms |  |
| ✅ | alumni | `/atlas.php` | 1860 ms |  |
| ✅ | alumni | `/referrals.php` | 1468 ms |  |
| ✅ | alumni | `/jobs.php` | 1501 ms |  |
| ✅ | alumni | `/events.php` | 1513 ms |  |
| ✅ | alumni | `/mentorship.php` | 1604 ms |  |
| ✅ | alumni | `/live.php` | 1429 ms |  |
| ✅ | alumni | `/library.php` | 1479 ms |  |
| ✅ | alumni | `/help.php` | 1345 ms |  |
| ✅ | student | `login via UI` |  |  |
| ✅ | student | `/dashboard.php` | 1634 ms |  |
| ✅ | student | `/feed.php` | 1667 ms |  |
| ✅ | student | `/profile.php` | 1598 ms |  |
| ✅ | student | `/settings.php` | 1561 ms |  |
| ✅ | student | `/messages.php` | 1433 ms |  |
| ✅ | student | `/discovery.php` | 1584 ms |  |
| ✅ | student | `/atlas.php` | 1911 ms |  |
| ✅ | student | `/referrals.php` | 1419 ms |  |
| ✅ | student | `/jobs.php` | 1562 ms |  |
| ✅ | student | `/events.php` | 1412 ms |  |
| ✅ | student | `/mentorship.php` | 1546 ms |  |
| ✅ | student | `/live.php` | 1504 ms |  |
| ✅ | student | `/library.php` | 1412 ms |  |
| ✅ | student | `/help.php` | 1401 ms |  |
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

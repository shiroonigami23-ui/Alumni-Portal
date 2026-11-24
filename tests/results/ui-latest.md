# UI smoke test results

**82 / 82 passed** · 2026-11-24 15:27 · Chrome via Playwright, signed in as each role.

Each page is opened in a real browser; a check fails on any JavaScript error, failed same-origin request, or blank page.

| | Role | Page | Load | Problems |
|---|---|---|---|---|
| ✅ | guest | `/` | 1595 ms |  |
| ✅ | guest | `/login.php` | 1390 ms |  |
| ✅ | guest | `/register.php` | 1380 ms |  |
| ✅ | guest | `/forgot-password.php` | 1325 ms |  |
| ✅ | guest | `/terms.php` | 1397 ms |  |
| ✅ | guest | `/policy.php` | 1522 ms |  |
| ✅ | guest | `/conduct.php` | 1403 ms |  |
| ✅ | guest | `/old-link-404` | 1295 ms |  |
| ✅ | admin | `login via UI` |  |  |
| ✅ | admin | `/dashboard.php` | 1797 ms |  |
| ✅ | admin | `/feed.php` | 1571 ms |  |
| ✅ | admin | `/profile.php` | 1752 ms |  |
| ✅ | admin | `/settings.php` | 1571 ms |  |
| ✅ | admin | `/messages.php` | 1475 ms |  |
| ✅ | admin | `/discovery.php` | 1506 ms |  |
| ✅ | admin | `/atlas.php` | 1906 ms |  |
| ✅ | admin | `/referrals.php` | 1532 ms |  |
| ✅ | admin | `/jobs.php` | 1507 ms |  |
| ✅ | admin | `/events.php` | 1432 ms |  |
| ✅ | admin | `/mentorship.php` | 1549 ms |  |
| ✅ | admin | `/live.php` | 1436 ms |  |
| ✅ | admin | `/library.php` | 1469 ms |  |
| ✅ | admin | `/help.php` | 1358 ms |  |
| ✅ | admin | `/admin/insights.php` | 1503 ms |  |
| ✅ | admin | `/admin/dashboard.php` | 1540 ms |  |
| ✅ | admin | `/admin/users.php` | 1687 ms |  |
| ✅ | admin | `/admin/reports.php` | 1534 ms |  |
| ✅ | admin | `/admin/tokens.php` | 1455 ms |  |
| ✅ | admin | `/admin/system-settings.php` | 1461 ms |  |
| ✅ | admin | `/moderation.php` | 1525 ms |  |
| ✅ | alumni | `login via UI` |  |  |
| ✅ | alumni | `/dashboard.php` | 1678 ms |  |
| ✅ | alumni | `/feed.php` | 1619 ms |  |
| ✅ | alumni | `/profile.php` | 1607 ms |  |
| ✅ | alumni | `/settings.php` | 1577 ms |  |
| ✅ | alumni | `/messages.php` | 1447 ms |  |
| ✅ | alumni | `/discovery.php` | 1485 ms |  |
| ✅ | alumni | `/atlas.php` | 1852 ms |  |
| ✅ | alumni | `/referrals.php` | 1454 ms |  |
| ✅ | alumni | `/jobs.php` | 1551 ms |  |
| ✅ | alumni | `/events.php` | 1465 ms |  |
| ✅ | alumni | `/mentorship.php` | 1631 ms |  |
| ✅ | alumni | `/live.php` | 1478 ms |  |
| ✅ | alumni | `/library.php` | 1462 ms |  |
| ✅ | alumni | `/help.php` | 1370 ms |  |
| ✅ | student | `login via UI` |  |  |
| ✅ | student | `/dashboard.php` | 1620 ms |  |
| ✅ | student | `/feed.php` | 1679 ms |  |
| ✅ | student | `/profile.php` | 1552 ms |  |
| ✅ | student | `/settings.php` | 1542 ms |  |
| ✅ | student | `/messages.php` | 1505 ms |  |
| ✅ | student | `/discovery.php` | 1516 ms |  |
| ✅ | student | `/atlas.php` | 1842 ms |  |
| ✅ | student | `/referrals.php` | 1513 ms |  |
| ✅ | student | `/jobs.php` | 1494 ms |  |
| ✅ | student | `/events.php` | 1462 ms |  |
| ✅ | student | `/mentorship.php` | 1564 ms |  |
| ✅ | student | `/live.php` | 1437 ms |  |
| ✅ | student | `/library.php` | 1442 ms |  |
| ✅ | student | `/help.php` | 1387 ms |  |
| ✅ | flow | `Feed draft survives a reload` |  |  |
| ✅ | flow | `Discard draft clears the composer` |  |  |
| ✅ | flow | `Create a post with a poll from the composer` |  |  |
| ✅ | flow | `Schedule mode switches the button to “Schedule”` |  |  |
| ✅ | flow | `Scheduled post appears in “Your scheduled posts”` |  |  |
| ✅ | flow | `Ctrl+K palette navigates` |  |  |
| ✅ | flow | `No JavaScript errors during admin flows` |  |  |
| ✅ | flow | `Vote on a poll and see results` |  |  |
| ✅ | flow | `Only the poll's author sees “End poll”` |  |  |
| ✅ | flow | `Ctrl+K shows people with their @handle` |  |  |
| ✅ | flow | `Enter opens that person’s profile` |  |  |
| ✅ | flow | `Author ends the poll; results become final` |  |  |
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

# UI smoke test results

**94 / 94 passed** · 2026-11-24 16:02 · Chrome via Playwright, signed in as each role.

Each page is opened in a real browser; a check fails on any JavaScript error, failed same-origin request, or blank page.

| | Role | Page | Load | Problems |
|---|---|---|---|---|
| ✅ | guest | `/` | 1679 ms |  |
| ✅ | guest | `/login.php` | 1415 ms |  |
| ✅ | guest | `/register.php` | 1367 ms |  |
| ✅ | guest | `/forgot-password.php` | 1298 ms |  |
| ✅ | guest | `/terms.php` | 1416 ms |  |
| ✅ | guest | `/policy.php` | 1431 ms |  |
| ✅ | guest | `/conduct.php` | 1374 ms |  |
| ✅ | guest | `/old-link-404` | 1283 ms |  |
| ✅ | admin | `login via UI` |  |  |
| ✅ | admin | `/dashboard.php` | 1778 ms |  |
| ✅ | admin | `/feed.php` | 1701 ms |  |
| ✅ | admin | `/profile.php` | 1767 ms |  |
| ✅ | admin | `/settings.php` | 1537 ms |  |
| ✅ | admin | `/messages.php` | 1518 ms |  |
| ✅ | admin | `/discovery.php` | 1631 ms |  |
| ✅ | admin | `/atlas.php` | 2000 ms |  |
| ✅ | admin | `/referrals.php` | 1457 ms |  |
| ✅ | admin | `/jobs.php` | 1460 ms |  |
| ✅ | admin | `/events.php` | 1539 ms |  |
| ✅ | admin | `/mentorship.php` | 1566 ms |  |
| ✅ | admin | `/live.php` | 1441 ms |  |
| ✅ | admin | `/library.php` | 1451 ms |  |
| ✅ | admin | `/help.php` | 1393 ms |  |
| ✅ | admin | `/admin/insights.php` | 1510 ms |  |
| ✅ | admin | `/admin/dashboard.php` | 1633 ms |  |
| ✅ | admin | `/admin/users.php` | 1571 ms |  |
| ✅ | admin | `/admin/reports.php` | 1556 ms |  |
| ✅ | admin | `/admin/tokens.php` | 1464 ms |  |
| ✅ | admin | `/admin/system-settings.php` | 1439 ms |  |
| ✅ | admin | `/moderation.php` | 1493 ms |  |
| ✅ | alumni | `login via UI` |  |  |
| ✅ | alumni | `/dashboard.php` | 1690 ms |  |
| ✅ | alumni | `/feed.php` | 1744 ms |  |
| ✅ | alumni | `/profile.php` | 1569 ms |  |
| ✅ | alumni | `/settings.php` | 1655 ms |  |
| ✅ | alumni | `/messages.php` | 1485 ms |  |
| ✅ | alumni | `/discovery.php` | 1616 ms |  |
| ✅ | alumni | `/atlas.php` | 2036 ms |  |
| ✅ | alumni | `/referrals.php` | 1492 ms |  |
| ✅ | alumni | `/jobs.php` | 1575 ms |  |
| ✅ | alumni | `/events.php` | 1489 ms |  |
| ✅ | alumni | `/mentorship.php` | 1656 ms |  |
| ✅ | alumni | `/live.php` | 1561 ms |  |
| ✅ | alumni | `/library.php` | 1607 ms |  |
| ✅ | alumni | `/help.php` | 1399 ms |  |
| ✅ | student | `login via UI` |  |  |
| ✅ | student | `/dashboard.php` | 1787 ms |  |
| ✅ | student | `/feed.php` | 1686 ms |  |
| ✅ | student | `/profile.php` | 1681 ms |  |
| ✅ | student | `/settings.php` | 1563 ms |  |
| ✅ | student | `/messages.php` | 1525 ms |  |
| ✅ | student | `/discovery.php` | 1527 ms |  |
| ✅ | student | `/atlas.php` | 2013 ms |  |
| ✅ | student | `/referrals.php` | 1478 ms |  |
| ✅ | student | `/jobs.php` | 1565 ms |  |
| ✅ | student | `/events.php` | 1509 ms |  |
| ✅ | student | `/mentorship.php` | 1591 ms |  |
| ✅ | student | `/live.php` | 1530 ms |  |
| ✅ | student | `/library.php` | 1461 ms |  |
| ✅ | student | `/help.php` | 1378 ms |  |
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
| ✅ | flow | `Alumni see "Propose an event"` |  |  |
| ✅ | flow | `Proposal is "sent for approval"` |  |  |
| ✅ | flow | `My events shows it as "Awaiting approval"` |  |  |
| ✅ | flow | `Alumni have no Pending approval tab` |  |  |
| ✅ | flow | `Staff see the Pending approval tab with a count` |  |  |
| ✅ | flow | `Admin approves it from the queue` |  |  |
| ✅ | flow | `Approved event appears in Upcoming` |  |  |
| ✅ | flow | `Users table offers Suspend… and Change email…` |  |  |
| ✅ | flow | `Selecting rows shows the bulk bar` |  |  |
| ✅ | flow | `Admins have no selection checkbox` |  |  |
| ✅ | flow | `Reports page shows reporter history` |  |  |
| ✅ | flow | `No JavaScript errors in events / admin flows` |  |  |

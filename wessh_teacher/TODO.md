# Teacher Screens Fixes

## Issues Identified

- Inconsistent session checks (some commented out in dashboard.php and reports.php)
- Incorrect CSS/JS paths pointing to timestamped folder
- Hardcoded empty DB credentials in dashboard.php
- Duplicated DB setup code instead of using db.php include

## Plan

- Enable session checks across all files
- Fix paths to use correct startbootstrap folder (../startbootstrap-sb-admin-2-gh-pages/)
- Use include 'db.php' consistently instead of hardcoded DB setup
- Standardize DB credentials via db.php

## Files to Edit

- [x] dashboard.php: Uncomment session check, remove hardcoded DB, use include, fix paths
- [x] review.php: Use include 'db.php', fix paths
- [x] notifications.php: Use include 'db.php', fix paths
- [x] reports.php: Uncomment session check, use include 'db.php', fix paths
- [ ] logout.php: No changes needed
- [ ] db.php: No changes needed

## Database Setup

- [x] Created wessh_db database
- [x] Imported schema (with duplicate index warning, but tables created)

=== Nexura Database Cleaner & Optimizer ===
Contributors: prokashsarker2026, freemius
Tags: database cleaner, database optimizer, woocommerce cleaner, action scheduler, speed up wordpress
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Safely clean, defragment, and optimize your WordPress database with health audits, orphan detection, cleanup previews, and MySQL table tools.

== Description ==

**Nexura Database Cleaner & Optimizer** is a modern, safety-first WordPress database optimization and cleanup plugin built to speed up your website, reduce server load (TTFB), and reclaim gigabytes of bloated storage.

🔗 **Quick Links:**
* **Official Website:** [nexurasecurity.com/database-cleaner-optimizer](https://nexurasecurity.com/database-cleaner-optimizer/)
* **GitHub Repository:** [github.com/nexurasecurity/nexura-database-cleaner](https://github.com/nexurasecurity/nexura-database-cleaner)
* **Pricing & Pro Version:** [View Pricing & Plans](https://nexurasecurity.com/database-cleaner-optimizer/#pricing)

---

### Why Clean and Optimize Your WordPress Database?

Over time, every active WordPress and WooCommerce site accumulates gigabytes of unseen bloat:
* **Old Post Revisions & Auto-Drafts** accumulating over years of editing.
* **Orphaned Metadata** left behind by uninstalled plugins and deleted posts, users, or terms.
* **Massive Autoloaded Options** in `wp_options` that load on every single page view, slowing down TTFB and PHP memory.
* **Bloated WooCommerce Action Scheduler Tables** with hundreds of thousands of completed or failed task logs.
* **Expired Transient Caches & Spam Comments** creating database query bottlenecks.

This clutter increases database file size, slows down WP-Admin queries, causes slow WooCommerce checkouts, and increases server backup time.

**Nexura Database Cleaner** follows a strict safety principle: **Scan first. Preview what was found. Clean only what you approve.**

---

### Key Features at a Glance

* **Database Health Audit & Transparent Score**: Instant 0–100 health assessment with fully transparent calculation deductions—never arbitrary or misleading.
* **Safe Dry Run & Preview Mode**: Inspect matched records and estimated disk space before deleting a single row.
* **Risk Level Classification**: Every item is transparently classified by risk (Safe, Low Risk, Medium Risk, High Risk).
* **Batch Processing Engine**: Removes clutter in asynchronous, controlled AJAX batches to prevent server timeouts and 504 gateway errors.
* **Pre-Cleanup Backup Safety Prompt**: Displays safety recommendations before bulk deletion operations.
* **Full Audit Trail & History**: Complete records of every cleanup operation, including rows removed, bytes reclaimed, and execution timestamps.
* **Automated Scheduled Maintenance**: Set recurring background cleanups (Daily, Weekly, or Monthly) via WordPress Cron for hands-free optimization.
* **InnoDB Storage Awareness**: Correctly distinguishes normal 2 MB MySQL InnoDB extent reservations from true table fragmentation.
* **Multisite Compatible**: Safely distinguishes network-wide tables and subsite prefixes.

---

### Comprehensive Cleanup Modules

#### 1. Posts & Content Cleaners
* **Post Revisions**: Remove excessive past revisions while preserving published posts.
* **Auto-Drafts**: Clean abandoned auto-drafts created by the WordPress block editor.
* **Trashed Posts**: Empty discarded posts and pages remaining in the trash bin.

#### 2. Comments Clutter Cleaners
* **Spam Comments**: Purge spam comments marked by moderators or antispam tools.
* **Trashed Comments**: Permanently empty deleted comments.
* **Pingbacks & Trackbacks**: Remove legacy blog notifications stored in the comments table.

#### 3. Metadata & Relationships Cleaners
* **Orphaned Post Meta**: Clean metadata tied to deleted post IDs.
* **Orphaned User Meta**: Remove metadata referencing removed user accounts.
* **Orphaned Comment Meta**: Clean metadata belonging to deleted comments.
* **Orphaned Term Meta**: Remove metadata referencing deleted categories or tags.
* **Orphaned Term Relationships**: Clean relationships pointing to missing post entries.
* **Duplicated Post Meta**: Deduplicate redundant postmeta rows sharing identical keys and values.
* **Duplicated User Meta**: Remove redundant duplicate usermeta records.
* **Duplicated Comment Meta**: Deduplicate identical commentmeta entries.

#### 4. Transients & Caches
* **Expired Transients**: Purge expired temporary cached options from the options table.
* **Expired Site Transients**: Clean expired network-wide and multisite transient caches.
* **oEmbed Caches**: Clean temporary embedded media HTML caches from postmeta.

#### 5. WooCommerce & Action Scheduler Cleaners
* **Action Scheduler: Completed Actions**: Purge completed background jobs older than 30 days.
* **Action Scheduler: Failed Actions**: Remove failed background actions that will not retry.
* **Action Scheduler: Canceled Actions**: Delete canceled scheduled actions.
* **Action Scheduler: Orphaned Logs**: Clean orphaned log rows whose parent actions were deleted.
* **Customer Sessions (Pro)**: Clean expired WooCommerce customer session tokens.
* **Orphaned Order Items & Meta (Pro)**: Identify and clean leftover order items and metadata from deleted orders.
* **Webhook Delivery Logs (Pro)**: Purge accumulated historical WooCommerce webhook delivery logs.

#### 6. Database Table Optimization & Defragmentation
* **Table Inventory**: View table names, database engines (InnoDB/MyISAM), row counts, data size, index size, and overhead.
* **Single & Bulk Optimization**: Defragment tables and rebuild index trees with one click.
* **InnoDB Extent Baseline Recognition**: Understands MySQL extent reservations and marks baseline tables as Optimal.
* **Table Analysis**: Execute MySQL `ANALYZE TABLE` to refresh index statistics for the query optimizer.

#### 7. Options & Autoload Diagnostics
* **Autoload Size Monitoring**: Real-time calculation of total autoloaded bytes with a warning if exceeding 800 KB.
* **Large Options Detection**: Identifies the heaviest individual options in your database (> 50 KB).
* **1-Click Autoload Toggle**: Turn autoload ON or OFF for any option without executing manual SQL queries.

#### 8. Cron Jobs Analyzer
* **Scheduled Task Overview**: Inspect all active WP-Cron hooks and next run timestamps.
* **Orphaned Cron Detection**: Identify cron events with no valid callback registered by active plugins or themes.
* **1-Click Unschedule**: Safely remove orphaned scheduled tasks.

---

### Free vs Pro Feature Comparison

While our **Free version** gives you everything you need to safely clean clutter, optimize tables, and speed up standard WordPress sites, **Pro** is designed for agencies, WooCommerce stores, and high-traffic websites that need deep identification of uninstalled plugin tables via our built-in signature library, automated safety backups, and granular scheduled maintenance.

👉 **[Check Out Pro Pricing & Plans](https://nexurasecurity.com/database-cleaner-optimizer/#pricing)**

| Feature | Free Version | Pro Version |
| :--- | :---: | :---: |
| Database Health Audit Score (0–100) | Yes | Yes |
| Post Revisions, Auto-Drafts & Trash Cleaners | Yes | Yes |
| Spam & Trash Comments, Pingbacks/Trackbacks | Yes | Yes |
| Expired Transients & Site Transients | Yes | Yes |
| Orphaned Metadata Cleaners (Post, User, Comment, Term) | Yes | Yes |
| Orphaned Term Relationships | Yes | Yes |
| Duplicated Metadata Cleaners (Post, User, Comment) | Yes | Yes |
| oEmbed HTML Caches Cleanup | Yes | Yes |
| Table Optimization & Defragmentation (InnoDB & MyISAM) | Yes | Yes |
| InnoDB 2 MB Extent Reserve Recognition | Yes | Yes |
| Autoload Options Size Monitor & Top Options Inspector | Yes | Yes |
| 1-Click Autoload Toggle (On/Off) | Yes | Yes |
| Cron Task Analyzer & Unschedule | Yes | Yes |
| Interactive Dry Run & Safety Preview | Yes | Yes |
| Asynchronous Batch Processing (No Timeout) | Yes | Yes |
| Audit Trail History & Local Execution Log | Yes | Yes |
| Basic Action Scheduler Cleanup (Failed & Canceled) | Yes | Yes |
| Basic Scheduled Cleanup (Daily, Weekly, Monthly) | Yes | Yes |
| **Plugin Signature Library (popular plugins, stored locally)** | View only | **Yes, with table and option cleanup** |
| **Abandoned Tables Detector & Cleaner (Uninstalled Plugins)** | No | **Yes** |
| **Abandoned Options & Metadata Purger (Deleted Plugins)** | No | **Yes** |
| **WooCommerce Deep Clean Suite (Sessions, Variations, Order Items & Meta, Webhooks, Analytics)** | No | **Yes** |
| **Granular Schedule Engine (Custom intervals per cleaner)** | No | **Yes** |
| **Scheduled Cleanup Email Summary Reports** | No | **Yes** |
| **1-Click Pre-Cleanup SQL Table Backup** | Warning Modal | **Yes (1-Click SQL)** |
| **Database Table Engine Converter (MyISAM to InnoDB)** | Engine View | **Yes (1-Click)** |
| **Raw Table Data, Columns & Index Browser** | No | **Yes** |
| **Deep Search & Filter inside Options / Postmeta** | No | **Yes** |
| **WordPress Multisite (Network Super-Admin Clean)** | Single Site | **Yes (Full Network)** |
| **Support & Updates** | Community (wp.org) | **VIP Dedicated 1-on-1** |

---

### Privacy & Data Protection

* **100% Local Processing**: All scans, queries, and cleanup operations run locally on your server.
* **No Database Transmission**: Your database contents, post text, user credentials, and customer data are never uploaded or transmitted to any external server.
* **GDPR Compliant**: Keeps your user and website data strictly private on your hosting infrastructure.

### 3rd Party Services (Freemius)
This plugin uses Freemius for optional usage tracking, licensing, and upgrades. No data is collected unless you explicitly opt-in. Freemius is a secure third-party service specifically built for WordPress plugins. 
By opting in or upgrading, you agree to Freemius' terms and policies:
* [Freemius Privacy Policy](https://freemius.com/privacy/)
* [Freemius Terms of Service](https://freemius.com/terms/)

== Installation ==

1. Log in to your WordPress dashboard.
2. Go to **Plugins > Add New** and search for `Nexura Database Cleaner & Optimizer`.
3. Click **Install Now**, then click **Activate**.
4. Navigate to **Database Cleaner** in your WordPress admin menu to run your first database health audit.

### Manual Installation
1. Download the plugin ZIP archive or clone from our [GitHub repository](https://github.com/nexurasecurity/nexura-database-cleaner).
2. Upload the `nexura-database-cleaner` folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the **Plugins** screen in WordPress.

== Frequently Asked Questions ==

= How does database cleanup speed up my WordPress website? =
Every request WordPress processes requires querying the database. When tables are bloated with hundreds of thousands of revisions, expired transients, and huge autoloaded options, MySQL takes longer to execute queries and uses more RAM. Cleaning bloat and defragmenting tables reduces query execution times, lowers server response time (TTFB), and speeds up your entire website.

= How do I clean WooCommerce Action Scheduler tables safely? =
Under the WooCommerce module in Nexura Database Cleaner, you can safely purge completed actions older than 30 days, failed actions, canceled actions, and orphaned action logs. This frees up massive space in `wp_actionscheduler_actions` and `wp_actionscheduler_logs` without interrupting any running store orders or pending background tasks.

= How to reduce autoload options size in WordPress (wp_options)? =
WordPress loads every option marked with `autoload = yes` into PHP memory on every single page load. If total autoloaded size exceeds 800 KB, page load times increase. Nexura monitors your autoload memory, identifies the heaviest individual options, and lets you toggle autoload off with one click without writing SQL queries.

= Why does my InnoDB table still show 2 MB under Free Overhead after optimizing? =
This is standard MySQL and MariaDB engine behavior. In InnoDB with `innodb_file_per_table`, MySQL allocates storage in fixed-size chunks called "extents" (typically 2 MB). Even after an `OPTIMIZE TABLE` operation rebuilds the table to its smallest possible footprint, MySQL maintains a 2 MB extent reserve for future write operations. This 2 MB is not fragmentation—it is normal baseline operating space. Our plugin intelligently recognizes this and displays `Optimal (2 MB reserve)` instead of an alarming warning.

= What is a Dry Run and why is it recommended? =
A Dry Run inspects and counts the exact rows matching your cleanup criteria without executing any `DELETE` query. It allows you to verify what will be cleaned and how much space will be reclaimed before making permanent changes.

= Does the plugin delete database data automatically upon activation? =
No. The plugin will never delete any data automatically upon activation or when running a scan. You retain complete control over what to clean, when to clean, and whether to run a safe Dry Run first.

= Will deleting post revisions or auto-drafts break my published posts? =
No. Revisions and auto-drafts are historical backups of earlier drafts. Cleaning them does not modify or delete your currently published posts, pages, or custom post types.

= Does the plugin create a backup before cleanup? =
In the Free version, the plugin presents a pre-cleanup backup safety recommendation dialog reminding you to create a backup before bulk operations. In the **Pro version**, 1-click automatic SQL table export is included directly inside the plugin.

= Why should I upgrade to Nexura Database Cleaner & Optimizer Pro? =
Upgrading to **Pro** unlocks:
1. Built-in signature library to identify and remove leftover tables and options from uninstalled plugins.
2. Advanced WooCommerce cleaners (customer sessions, orphaned variations, order items & metadata, webhooks).
3. 1-click automatic SQL table backup before cleaning.
4. Granular scheduled cleanup intervals with automated email reports.
5. WordPress Multisite network-wide cleanup for super-admins.
[View Pro Pricing & Plans](https://nexurasecurity.com/database-cleaner-optimizer/#pricing).

= Is Nexura Database Cleaner open source and hosted on GitHub? =
Yes! You can view the code, contribute, or track releases on our [official GitHub repository](https://github.com/nexurasecurity/nexura-database-cleaner).

== Screenshots ==

1. **Dashboard & Health Audit**: Real-time database metrics, health score, total database size, and quick action overview.
2. **Batch Cleanup & Preview**: Itemized clutter list with risk levels, counts, space estimates, and Dry Run inspection.
3. **Table Optimization & Extents**: Detailed table statistics, overhead defragmentation, and intelligent InnoDB extent reserve handling.
4. **Options & Autoload Diagnostics**: Total autoloaded memory monitor, large options table, and 1-click autoload toggling.
5. **Automated Scheduled Cleanup**: Configure recurring background maintenance via WordPress Cron.
6. **Audit History & Verification**: Transparent history log documenting rows cleaned, freed bytes, and timestamps.

== Changelog ==

= 1.0.0 =
* Initial public release with full database health audit, cleanup modules, InnoDB extent optimization, autoload analyzer, Action Scheduler cleaners, automated scheduler, and complete audit trail.
* Pro license unlocks the signature library cleanup, WooCommerce deep clean, per-item schedule with email reports, SQL export, MyISAM conversion, schema browser, meta search, and Multisite network cleanup.

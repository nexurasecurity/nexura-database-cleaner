# Nexura Database Cleaner & Optimizer

<p align="center">
  <a href="https://wordpress.org/plugins/nexura-database-cleaner/" target="_blank">
    <img src="https://img.shields.io/badge/WordPress.org-Download%20Free-blue?style=flat-square&logo=wordpress" alt="WordPress.org">
  </a>
  <a href="https://wordpress.org/plugins/nexura-database-cleaner/" target="_blank">
    <img src="https://img.shields.io/badge/Requires%20WP-5.8%2B-informational?style=flat-square" alt="Requires WP 5.8+">
  </a>
  <a href="https://wordpress.org/plugins/nexura-database-cleaner/" target="_blank">
    <img src="https://img.shields.io/badge/Requires%20PHP-7.4%2B-informational?style=flat-square" alt="Requires PHP 7.4+">
  </a>
  <a href="https://wordpress.org/plugins/nexura-database-cleaner/" target="_blank">
    <img src="https://img.shields.io/badge/Tested%20up%20to-7.1-brightgreen?style=flat-square" alt="Tested up to 7.1">
  </a>
  <a href="LICENSE" target="_blank">
    <img src="https://img.shields.io/badge/License-GPLv2-blue?style=flat-square" alt="License GPLv2">
  </a>
  <a href="https://github.com/nexurasecurity/nexura-database-cleaner/actions" target="_blank">
    <img src="https://img.shields.io/github/actions/workflow/status/nexurasecurity/nexura-database-cleaner/ci.yml?label=CI&style=flat-square" alt="CI Status">
  </a>
  <a href="https://github.com/nexurasecurity/nexura-database-cleaner/stargazers">
    <img src="https://img.shields.io/github/stars/nexurasecurity/nexura-database-cleaner?style=flat-square" alt="GitHub Stars">
  </a>
  <a href="https://github.com/nexurasecurity/nexura-database-cleaner/network/members">
    <img src="https://img.shields.io/github/forks/nexurasecurity/nexura-database-cleaner?style=flat-square" alt="GitHub Forks">
  </a>
  <a href="https://github.com/nexurasecurity/nexura-database-cleaner/issues">
    <img src="https://img.shields.io/github/issues/nexurasecurity/nexura-database-cleaner?style=flat-square" alt="Open Issues">
  </a>
</p>

<p align="center">
  <strong>Database-safe Cleanup + Orphan Detection + Table Optimization + Autoload Diagnostics in one ultra-fast, native WordPress plugin.</strong>
</p>

---

## 🚀 Overview

**Nexura Database Cleaner & Optimizer** is a modern, safety-first WordPress database optimization plugin built to speed up your website, reduce server load (TTFB), and reclaim gigabytes of bloated storage — without risking your data.

🔗 **Quick Links:**
- 🌐 **Official Website:** [nexurasecurity.com/database-cleaner-optimizer](https://nexurasecurity.com/database-cleaner-optimizer/)
- 📦 **WordPress.org Plugin:** [wordpress.org/plugins/nexura-database-cleaner](https://wordpress.org/plugins/nexura-database-cleaner/)
- 💰 **Pro Pricing & Plans:** [View Pricing](https://nexurasecurity.com/database-cleaner-optimizer/#pricing)
- 🐛 **Report an Issue:** [GitHub Issues](https://github.com/nexurasecurity/nexura-database-cleaner/issues)

---

## ✨ Key Features

- 📊 **Database Health Audit Score (0–100)** — Transparent calculation with zero arbitrary deductions
- 🔍 **Safe Dry Run & Preview Mode** — Inspect matched rows before deleting a single record
- ⚡ **Asynchronous Batch Processing** — No timeouts, no 504 errors, no server overload
- 🗂️ **Full Cleanup Audit Trail** — Rows removed, bytes reclaimed, and timestamps logged for every operation
- ⏱️ **Automated Scheduled Maintenance** — Daily, Weekly, or Monthly background cleanups via WP-Cron
- 🔒 **Risk Level Classification** — Every item is classified by risk: Safe / Low / Medium / High
- 🏗️ **InnoDB Extent Awareness** — Correctly identifies 2 MB MySQL extent baselines vs. real fragmentation
- 🌐 **WordPress Multisite Compatible** — Safely handles network-wide tables and subsite prefixes

---

## 🧹 Cleanup Modules

### 1. Posts & Content
| Module | Description |
|:---|:---|
| Post Revisions | Remove excessive past revisions, keep published content safe |
| Auto-Drafts | Clean abandoned block-editor auto-saves |
| Trashed Posts | Empty the post trash permanently |

### 2. Comments Clutter
| Module | Description |
|:---|:---|
| Spam Comments | Purge antispam-marked comments |
| Trashed Comments | Empty deleted comments bin |
| Pingbacks & Trackbacks | Remove legacy blog notification entries |

### 3. Orphaned & Duplicated Metadata
| Module | Description |
|:---|:---|
| Orphaned Post Meta | Clean metadata linked to deleted post IDs |
| Orphaned User Meta | Remove metadata from deleted user accounts |
| Orphaned Comment Meta | Clean metadata from deleted comments |
| Orphaned Term Meta | Remove metadata from deleted categories/tags |
| Orphaned Term Relationships | Clean relationships to missing post entries |
| Duplicated Post / User / Comment Meta | Deduplicate identical metadata rows |

### 4. Transients & Caches
| Module | Description |
|:---|:---|
| Expired Transients | Purge expired temporary cache options |
| Expired Site Transients | Clean expired network-wide transients |
| oEmbed Caches | Remove embedded media HTML caches from postmeta |

### 5. WooCommerce & Action Scheduler
| Module | Free | Pro |
|:---|:---:|:---:|
| Action Scheduler: Completed Actions (30+ days) | ✅ | ✅ |
| Action Scheduler: Failed & Canceled Actions | ✅ | ✅ |
| Action Scheduler: Orphaned Logs | ✅ | ✅ |
| Customer Sessions Cleaner | ❌ | ✅ |
| Orphaned Order Items & Meta | ❌ | ✅ |
| Webhook Delivery Logs | ❌ | ✅ |

### 6. Table Optimization & Defragmentation
- View engine type (InnoDB/MyISAM), row count, data size, index size, and overhead
- Single & bulk `OPTIMIZE TABLE` with one click
- `ANALYZE TABLE` for index statistics refresh
- InnoDB 2 MB extent reserve recognition

### 7. Options & Autoload Diagnostics
- Real-time total autoloaded bytes monitor (⚠️ warns if > 800 KB)
- Top largest individual options inspector (> 50 KB)
- 1-click autoload ON/OFF toggle — no SQL needed

### 8. Cron Jobs Analyzer
- Full active WP-Cron hooks overview with next-run timestamps
- Orphaned cron detection for callbacks with no active plugin
- 1-click unschedule for stale tasks

---

## 📊 Free vs Pro Comparison

| Feature | Free | Pro |
|:---|:---:|:---:|
| Database Health Audit Score (0–100) | ✅ | ✅ |
| Post, Draft & Trash Cleaners | ✅ | ✅ |
| Comment Spam & Trash Cleaners | ✅ | ✅ |
| Expired Transient & Site Transient Cleanup | ✅ | ✅ |
| Orphaned Metadata Cleaners (Post/User/Comment/Term) | ✅ | ✅ |
| Duplicated Metadata Cleaners | ✅ | ✅ |
| oEmbed Cache Cleanup | ✅ | ✅ |
| Table Optimization & Defragmentation | ✅ | ✅ |
| Autoload Monitor & 1-Click Toggle | ✅ | ✅ |
| Cron Task Analyzer & Unschedule | ✅ | ✅ |
| Interactive Dry Run & Safety Preview | ✅ | ✅ |
| Async Batch Processing (No Timeout) | ✅ | ✅ |
| Audit Trail History & Execution Log | ✅ | ✅ |
| Action Scheduler Cleanup (Failed & Canceled) | ✅ | ✅ |
| Basic Scheduled Cleanup (Daily/Weekly/Monthly) | ✅ | ✅ |
| **Plugin Signature Library (popular plugins)** | View only | ✅ |
| **Abandoned Table Detector & Cleaner** | ❌ | ✅ |
| **Abandoned Options & Meta Purger** | ❌ | ✅ |
| **WooCommerce Deep Clean Suite** | ❌ | ✅ |
| **Granular Schedule Engine (per-item intervals)** | ❌ | ✅ |
| **Scheduled Cleanup Email Summary Reports** | ❌ | ✅ |
| **1-Click SQL Table Backup** | Warning only | ✅ |
| **Table Engine Converter (MyISAM → InnoDB)** | Engine view | ✅ |
| **Raw Table Data, Columns & Index Browser** | ❌ | ✅ |
| **Deep Search & Filter in Options / Postmeta** | ❌ | ✅ |
| **WordPress Multisite Network Cleanup** | Single site | ✅ |
| **Support & Updates** | Community (wp.org) | VIP 1-on-1 |

👉 **[Compare Plans & Upgrade to Pro](https://nexurasecurity.com/database-cleaner-optimizer/#pricing)**

---

## 🔧 Installation

### Via WordPress Admin (Recommended)
1. Log in to your **WordPress dashboard**
2. Go to **Plugins → Add New** and search for `Nexura Database Cleaner`
3. Click **Install Now**, then click **Activate**
4. Navigate to **Database Cleaner** in your WP admin menu to run your first audit

### Manual Installation
1. Download the plugin ZIP or clone this repository:
   ```bash
   git clone https://github.com/nexurasecurity/nexura-database-cleaner.git
   ```
2. Upload the `nexura-database-cleaner` folder to `/wp-content/plugins/`
3. Activate via **Plugins** screen in WordPress admin

---

## 📋 Requirements

| Requirement | Minimum |
|:---|:---|
| WordPress | 5.8+ |
| PHP | 7.4+ |
| MySQL / MariaDB | 5.6+ |
| Tested Up To | WordPress 7.1 |

---

## 🔒 Privacy & Data Protection

- ✅ **100% Local Processing** — All scans and cleanup operations run on your own server
- ✅ **No Data Transmission** — Your database contents and user data are never uploaded externally
- ✅ **GDPR Compliant** — Keeps all data strictly on your hosting infrastructure

### Third-Party Services (Freemius)
This plugin uses [Freemius](https://freemius.com) for optional usage tracking, licensing, and upgrades.  
**No data is collected unless you explicitly opt-in.**

- [Freemius Privacy Policy](https://freemius.com/privacy/)
- [Freemius Terms of Service](https://freemius.com/terms/)

---

## 📄 Changelog

### 1.0.0 — Initial Release
- Full Database Health Audit with transparent 0–100 score
- Complete cleanup module suite: posts, comments, metadata, transients, WooCommerce action scheduler
- InnoDB extent optimization with intelligent 2 MB baseline recognition
- Autoload analyzer with 1-click toggle
- Automated WP-Cron scheduler (Daily / Weekly / Monthly)
- Full audit trail history with rows, bytes, and timestamps
- **Pro:** Plugin signature library, WooCommerce deep clean, per-item schedule with email reports, SQL backup, MyISAM conversion, schema browser, meta search, and Multisite network cleanup

---

## 🤝 Contributing

Contributions, bug reports, and feature suggestions are welcome!

1. Fork this repository
2. Create a feature branch: `git checkout -b feature/your-feature-name`
3. Commit your changes: `git commit -m 'feat: add your feature'`
4. Push to your fork: `git push origin feature/your-feature-name`
5. Open a Pull Request

---

## 📜 License

This project is licensed under the **GNU General Public License v2.0 or later**.  
See the [LICENSE](LICENSE) file for details.

---

<p align="center">
  Made with ❤️ by <a href="https://nexurasecurity.com" target="_blank">Nexura Security</a>
</p>

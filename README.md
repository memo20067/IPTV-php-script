# IPTV BOX – A Cross-Platform IPTV Management Script

## 📦 Overview

**M3U Streamer Portal** is a complete PHP-based web application for IPTV management. It enables easy installation on any PHP/MySQL web server, similar to platforms like vBulletin or WordPress.

This script serves as a centralized IPTV platform, supporting import/playback of M3U/M3U8 playlists, user subscription management, and seamless integration with Android, iOS, and Windows apps via a robust API system.

---

## ⚙️ Installation

- **One-Click Installer:** Fast and simple setup.
- **Guided Setup Wizard:** Prompts for database credentials.
- **Automatic Table Creation:** All required tables and config setup automatically.
- **No advanced technical knowledge required.**

---

## 🎯 Core Features

### ✅ Cross-Platform Support

- **Web-based Admin Panel:** Built in PHP.
- **REST API Integration:** For Android APK, iOS App (App Store ready), Windows Desktop App (.NET or Electron).

### 🎬 Main Application Features

1. **Downloaded Playlists Tab**
   - Displays M3U/M3U8 playlists gathered from the internet.
   - Built-in video player auto-plays first channel.
   - Sliding panel reveals all channels in playlist.
   - Channel switcher, playlist selector, search/filter options.

2. **All Channels Tab**
   - Aggregates all channels from every playlist.
   - Search and filter functionality.

3. **Local File Playback Tab**
   - Users can upload and play their own M3U/M3U8 files.

### 🔐 Login and Free Trial

- Users log in with credentials stored in database.
- **Free Trial:** 1-hour trial available without subscription.
- **After trial expiration:**
  - Access blocked.
  - Device fingerprinting (hardware ID, browser, user-agent) prevents repeat abuse.
  - Full access resumes only after subscription payment.

---

## 👨‍💼 Admin Control Panel

Admins can:
- Manage users (edit, ban)
- Upload M3U/M3U8 files
- Manage playlists and channels
- Control pricing/subscription durations
- Send public messages (ticker)
- Enable/disable ads
- View logs and stats

---

## 💸 Subscription System

- Free user registration.
- 1-hour free trial.
- Subscription unlocks full features.
- Admin sets durations (1 month, 1 year, etc.) and pricing tiers.
- **Secure Payment Integration:** Stripe, PayPal, etc.
- Full access granted upon payment confirmation.

---

## 📢 Extra Features

- Optional ad display system
- **Admin Ticker:** Real-time messages scroll across bottom of screen, hidden when inactive
- Fully responsive, modern UI/UX

---

## 🔐 Device Lock & Security

- Device locked after trial ends (hardware ID/fingerprint)
- Prevents abuse via reinstalling/registering new accounts
- Unlock only with valid paid subscription

---

## 📌 Notes

- Modular & easily extendable design
- API-first architecture for future compatibility
- More features/customizations can be added later

---

## ✅ Sample Project Title

**Cross-Platform IPTV Web App & API – Built with PHP (M3U/M3U8 Player + Subscription Management)**
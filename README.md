# 📞 Asterisk Dialplan & Click2Call Script

This repository contains two key components for managing VoIP call routing and triggering outbound calls:

1. **Universal Asterisk Dialplan (`dialplan-example.conf`)**
2. **Secure PHP-based Click2Call Script (`click2call.php`)**

---

## 📂 Files

| File                  | Description                                                  |
|------------------------|--------------------------------------------------------------|
| `dialplan-example.conf` | A customizable Asterisk dialplan for dynamic outbound routing |
| `click2call.php`         | PHP script to initiate calls via Asterisk AMI                 |

---

## 🛠 Features Overview

### 📟 Dialplan Highlights

- **Extension Range Filtering** — Only specific extensions can use the dialplan
- **Number Normalization** — Strips `+`/`00`, adapts to local format
- **Mobile Prefix Detection** — Flags calls to mobile numbers
- **Call Counter** — Balances calls between trunks (e.g. every 25th call)
- **Trunk Switching** — Routes between `GSM` and `TDM` trunks
- **Recording** — Auto-records calls with dynamic filenames
- **Invalid Number Handling** — Drops and logs invalid call attempts

📌 Fully adaptable: just replace placeholders like `EXT_START`, `EXT_END`, `+XX`, `GSM_TRUNK`, `TDM_TRUNK` with your own values.

---

### 🖥️ Click2Call Script (`click2call.php`)

This script allows triggering a call between two parties from a web interface or API:

- ✅ Connects to Asterisk via **AMI**
- ✅ Accepts `leg_a` (internal) and `leg_b` (external) via GET parameters
- ✅ Normalizes `leg_b` by stripping `00` or `+`
- ✅ Validates both numbers
- ✅ Authenticates with AMI and sends `Originate` action
- ✅ Logs the entire action to a file

Example request:
GET /click2call.php?leg_a=1001&leg_b=00442012345678&route_number=gateway1


🧱 Requires:
- Asterisk with enabled AMI
- PJSIP/SIP extension configured
- Web server with PHP support (Apache, Nginx+PHP-FPM)

---

## 🔐 Security Recommendations

- Move AMI credentials to a separate `.env` or `config.php`
- Use HTTPS and IP whitelisting (`$allowed_ips`) for the script
- Ensure correct file and directory permissions on log paths
- Rotate logs periodically (`logrotate` for `/var/log/click2call.log`)

---

## 🧩 Usage

### ☎️ Asterisk Dialplan

1. Place `dialplan-example.conf` into `/etc/asterisk/extensions_custom.conf` or similar.
2. Replace all placeholders like:
   - `EXT_START`, `EXT_END`
   - `+XX`, `GSM_TRUNK`, `TDM_TRUNK`
3. Reload the dialplan:
```bash
asterisk -rx "dialplan reload"

🌐 Click2Call Script
Copy click2call.php to your web server (e.g., /var/www/html/)

Set correct ownership and permissions:

chown www-data:www-data click2call.php
chmod 640 click2call.php

Test the script via browser or API tools like Postman or curl.

🔁 Additional Dialplan Hooks

📋 [macro-dialout-trunk-predial-hook]
Used to log the hangup cause into the CDR record for analysis and reporting (e.g., via Grafana or Asternic).

Automatically stores ${HANGUPCAUSE} in the CDR field hangupcause

Hooked into outbound trunk logic via FreePBX

📋 [set-static-callerid]
Handles Click2Call calls by setting consistent SIP headers and recording the call:

Ensures that a valid CallerID is set

Removes conflicting SIP headers (From, PAI, RPID)

Adds new standardized headers for SIP identity

Records call audio to a dated .wav file

Captures QoS (RTP statistics) and hangup cause at the end of the call into the CDR

📌 This context should be used as the Context in your Originate AMI action (see click2call.php).

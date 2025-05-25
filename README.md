# 📞 Asterisk Dialplan: Smart Outbound Routing Example

This repository contains a universal example of a custom Asterisk dialplan that implements dynamic outbound call routing based on:

- **Extension range authorization**
- **Number normalization**
- **Mobile number detection**
- **Call counter logic**
- **Routing between GSM and TDM trunks**
- **Call recording**
- **Invalid number handling and logging**

---

## 🛠 Features

### ✅ Extension Range Check
Only allows outbound calls from internal extensions within a specific range.  
Customize it by changing the values of `EXT_START` and `EXT_END`.

### 🔢 Number Normalization
Cleans up outbound numbers, including:
- Removing `+` signs
- Adjusting to a local/national format (customize as needed)

### 📱 Mobile Number Detection
Flags numbers as mobile if they start with configurable prefixes, e.g., `79`, `89`, etc.

### 🔄 Call Counter Logic
Keeps track of how many mobile calls have been made. Every Nth call (e.g., every 25th) is routed through an alternate trunk (e.g., TDM), while others go through the default (e.g., GSM).

### 🔀 Trunk Routing
Dynamically decides which trunk to use (`TDM_TRUNK` or `GSM_TRUNK`) based on logic.

### 🎧 Call Recording
Each call is recorded with a unique filename structure:  
`out-DST-CALLERID-TIMESTAMP.wav`

### 🚫 Invalid Call Handling
If a call does not meet routing criteria (e.g., invalid format for TDM), it's logged and dropped.

---

## 🧩 Structure Overview

```asterisk
[from-internal-custom]
  → Checks if caller is authorized to route

[custom-routing]
  → Normalizes number
  → Detects mobile numbers
  → Increments call counter
  → Chooses route based on counter
  → Jumps to [tdm], [gsm] or [invalid]

[tdm]
  → Validates and routes through TDM trunk
  → Records the call

[gsm]
  → Routes through GSM trunk
  → Records the call

[invalid]
  → Logs the attempt and ends the call

| Parameter        | Description                                               |
| ---------------- | --------------------------------------------------------- |
| `EXT_START/END`  | Extension range allowed to use the dialplan               |
| `+XX`, `X`       | Adjust to your local numbering plan                       |
| `GSM_TRUNK`      | Name of the default SIP or PJSIP trunk                    |
| `TDM_TRUNK`      | Name of the alternate fallback trunk                      |
| `% 25`           | Defines how often to switch to TDM (e.g. every 25th call) |
| `callcounter` DB | Asterisk internal DB key used to store the counter        |

📌 Note
This dialplan is intended as a flexible base template. Adapt the logic to your own telecom environment and regulatory dialing format.

🧑‍💻 Author
Made with Ingvar by a VoIP infrastructure engineer optimizing SIP routing and automation.

# Airtable Hybrid Cacher 🧩

### Smart Caching + Attachment Proxy Layer for Airtable APIs  
*(Partly based on [hubgit/cache-proxy](https://github.com/hubgit/cache-proxy) by [@hubgit](https://github.com/hubgit))*

The **Airtable Hybrid Cacher** is an advanced caching and prefetching layer for Airtable’s REST API.  
It builds on the lightweight caching concepts from **hubgit/cache-proxy**, extending them into a full-featured hybrid system with **bound cache compilation**, **attachment proxying**, and a **frontend UI**.

This system converts Airtable’s paginated API into stable, local JSON files and cached images for ultra-fast, API-free front-end use.

---

## 🚀 Features

✅ **Hybrid Fetching System**
- Functions as both a transparent proxy and an offline-ready API generator.
- Supports **on-demand caching** (`?url=`) and **forced regeneration** (`?regenerate=`).

✅ **Bound Cache Compilation**
- Fetches *all* Airtable pages, merges them, and stores as a single `.json` file.
- Caches all attachments for stable local or CDN delivery.
- Displays compile progress and duration in real-time.

✅ **Attachment Touching**
- Traverses nested attachment paths like `Attachments[0].thumbnails.full.url`.
- Downloads or proxies all image URLs through your cache system.
- Ensures no Airtable CDN URLs ever expire on your front-end.

✅ **Frontend Compiler UI**
- `frontend-compiler.html` lets you:
  - Start new full cache compilations.
  - Refresh attachments only.
  - View, open, and delete caches.
  - Track progress via progress bars and live timers.

✅ **CORS-Friendly API**
- All endpoints include `Access-Control-Allow-Origin: *` for local and remote consumption.
- Ready to plug into Vue, Quasar, React, or other browser apps.

---

## 🧠 Core Concepts

| Term | Description |
|------|--------------|
| **On-Demand Cache** | Direct `index.php?url=...` requests are cached for future reuse. |
| **Bound Cache** | Full Airtable table compiled into one JSON file with all records and attachments. |
| **Attachment Toucher** | Helper that touches (downloads/proxies) attachments for caching. |
| **Regenerate Mode** | Forces cache rebuild with `?regenerate=` — useful when Airtable updates occur. |

---

## 🧩 Architecture Overview

```

Airtable-Hybrid-Cacher/
├── index.php                # Core caching proxy (extends hubgit/cache-proxy)
├── bound-cache.php          # CRUD API for bound cache management
├── frontend-compiler.html   # Web UI for compiling and refreshing caches
├── CurlClient.php           # Enhanced curl handler with gzip + retry support
├── OAuthClient.php          # Optional OAuth handler
├── helpers.php              # Utility functions for paths, configs, headers
├── /cache/                  # Stores gzipped responses and bound JSON files
└── /config/                 # Optional per-host API key / header config

````

This project retains **hubgit/cache-proxy’s** efficient caching core while adding:
- Pagination merging logic for Airtable.
- Real-time compiler dashboard.
- Attachment traversal and proxy logic.
- Bound cache metadata and duration tracking.

---

## ⚙️ Setup

1. **Clone this repo**
   ```bash
   git clone https://github.com/yourname/Airtable-Hybrid-Cacher.git
   cd Airtable-Hybrid-Cacher
````

2. **Ensure PHP 8+ with `curl` + `zlib` extensions**

3. **Make the cache folder writable**

   ```bash
   chmod -R 777 cache
   ```

4. **Run a local server**

   ```bash
   php -S localhost:8000
   ```

5. **Open the UI**
   [http://localhost:8000/frontend-compiler.html](http://localhost:8000/frontend-compiler.html)

---

## 💡 Usage

### 1️⃣ Compile a Bound Cache

In the UI, enter your Airtable API endpoint:

```
https://api.airtable.com/v0/appXXXXXXXX/TableName?view=Grid%20view
```

Optional — specify your attachment path:

```
Attachments[0].thumbnails.full.url
```

Click **Start Compilation**.
You’ll see progress bars for:

* Record fetching (per page)
* Attachment caching (per record)
* Total elapsed time

A single JSON file (e.g. `bound-<hash>.json`) will be saved under `/cache/`.

---

### 2️⃣ Consume a Bound Cache

Example from a Vue / Quasar app:

```js
const url = 'https://yourdomain.com/Airtable-Hybrid-Cacher/bound-cache.php?action=get&url=' +
            encodeURIComponent('https://api.airtable.com/v0/appXXXXXXXX/TableName?view=Grid%20view');

const res = await fetch(url);
const data = await res.json();
console.log('Cached records:', data.records);
```

You can now do **client-side filtering** and **pagination** entirely offline.

---

### 3️⃣ Refresh Attachments Only

If the dataset hasn’t changed but images should be refreshed:

* Re-run compilation in the UI with the same URL + attachment path.
* The system detects the bound cache and only re-touches attachments.

---

## 🧹 Cache Management

| Action          | Endpoint Example                                        |
| --------------- | ------------------------------------------------------- |
| List caches     | `/bound-cache.php?action=list`                          |
| Delete cache    | `/bound-cache.php?action=delete&file=bound-<hash>.json` |
| Force recompile | `/index.php?regenerate=https://api.airtable.com/...`    |

---

## 🔒 Security Notes

* For development and build servers.
* No API keys are exposed client-side — define them in `/config/` as headers.
* Add authentication if deploying on production servers.

---

## 🛠️ Future Enhancements

* 🧵 Parallel attachment downloads for faster compilation.
* 🕒 Scheduled automatic cache regeneration.
* 🧱 Config-driven attachment path mapping.
* 🧩 CLI tool for automated bound cache builds.

---

## 🧑‍💻 Author

**Ivan Copeland**
Full-Stack Developer (Vue / Laravel / Quasar)
📧 [your.email@example.com](mailto:your.email@example.com)

---

## 📜 License

MIT License

Originally inspired by and partly based on
**[hubgit/cache-proxy](https://github.com/hubgit/cache-proxy)** © [Hubgit](https://github.com/hubgit)

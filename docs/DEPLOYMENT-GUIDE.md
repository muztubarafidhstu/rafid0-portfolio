# rafid0.com — Deployment Guide (for the developer)

- **Project:** Portfolio website + admin panel (CMS) for **MD. Mujtuba Rafid (rafid0)**, R&D Engineer
- **Target URL:** `https://rafid0.com`
- **Package:** `portfolio-website.zip` (website), `nginx.conf.example` (only if the host uses Nginx)
- **Stack:** Plain PHP 8 + SQLite. No framework, no Composer, no Node/npm, no build step, no MySQL.

> Goal: upload the site, connect the domain, enable HTTPS, create the owner's admin account
> **with the owner present**, then hand the admin login to the owner.

---

## 1. Requirements (check before uploading)

| Requirement | Needed | How to check |
|---|---|---|
| PHP | **8.0+** (8.1–8.3 recommended) | Hosting panel → PHP version |
| PHP extensions | `pdo_sqlite`, `sqlite3`, `mbstring`, `fileinfo`, `gd` (gd optional: resizes large photos) | Panel → PHP extensions, or the test script in §10 |
| Web server | Apache or LiteSpeed (uses `.htaccess`). Nginx works with `nginx.conf.example` | Ask host / check panel |
| SSL | Free Let's Encrypt / AutoSSL | Panel → SSL |
| Disk | 10 GB+ recommended (photos, PDFs, videos) | Plan details |
| Writable folders | `data/` and `uploads/` (755 dirs, 644 files) | File Manager → Permissions |
| `mail()` | Optional (contact-form email alerts). Messages are always saved in the admin panel | — |

Hosting chosen by the owner: **Spaceship — Singapore Web Hosting** (cheapest plan). Domain: **rafid0.com** (Spaceship).

---

## 2. Folder structure

```
public_html/                 ← web root (upload the CONTENTS of portfolio-website/ here)
├── index.php                Home
├── projects.php             Project list           project.php?slug=…        Project detail
├── research.php             Research list          research-item.php?slug=…  Research detail
├── gallery.php  downloads.php  download.php (counts downloads, then redirects)
├── about.php  cv.php  contact.php  search.php  404.php
├── sitemap.php  robots.php  favicon.php   (rewritten from /sitemap.xml, /robots.txt by .htaccess)
├── .htaccess                Apache rules (security, caching, upload limits, optional HTTPS redirect)
├── .user.ini                PHP upload limits for PHP-FPM hosts (256 MB)
├── admin/                   Admin panel (login, dashboard, projects, research, gallery, downloads,
│                            messages, profile/CV, settings)
├── includes/                PHP code — BLOCKED from web (.htaccess)
│   ├── bootstrap.php  db.php  functions.php  icons.php  partials.php
│   ├── header.php  footer.php
│   └── seed.php             Starter content, inserted ONCE on first run
├── data/                    SQLite database — BLOCKED from web. Created automatically.
│   └── config.php           Created on first run; holds the random DB file name
├── uploads/                 Files uploaded from the admin (images/, videos/, documents/)
│                            Script execution disabled (.htaccess)
├── assets/                  css/ js/ img/demo/ (sample illustrations)
└── README.md                Owner's user guide
```

---

## 3. Deploy steps

### 3.1 Upload
1. Hosting panel → **File Manager** → open **`public_html`** (the root folder for rafid0.com).
2. Remove the host's placeholder files (`index.html`, `default.php`, etc.).
3. Upload **`portfolio-website.zip`** → **Extract**.
4. Move everything **inside** `portfolio-website/` up into `public_html/` so that `public_html/index.php` exists. Delete the empty folder and the zip.
5. Make sure the **hidden files** `.htaccess` and `.user.ini` were extracted (enable "Show hidden files").
6. Permissions: `data/` and `uploads/` (and subfolders) → **755**. Files → **644**.

### 3.2 Domain / DNS
- If the domain and hosting are both on **Spaceship**, the domain is usually linked automatically. Otherwise:
  - **Option A (nameservers):** Spaceship → Domain Manager → rafid0.com → Nameservers → Custom → enter the hosting nameservers.
  - **Option B (records):** keep Spaceship DNS, add `A @ → <hosting IP>` and `CNAME www → rafid0.com` (or `A www → <hosting IP>`).
- Propagation: minutes to 24 h. Check with `nslookup rafid0.com`.
- If **Cloudflare** is placed in front later: SSL/TLS mode **Full (strict)** or **Full**, never *Flexible*. The site already reads the real visitor IP from `CF-Connecting-IP`, but only when the request comes from Cloudflare's IP ranges.

### 3.3 SSL / HTTPS
1. Panel → SSL → issue the free certificate for `rafid0.com` and `www.rafid0.com`.
2. Open `https://rafid0.com` and confirm the padlock.
3. Then edit `public_html/.htaccess`, uncomment the three lines under *"Uncomment the 3 lines below AFTER SSL (https) works"*:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteCond %{HTTP:X-Forwarded-Proto} !https
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```
4. Optional: redirect `www` → bare domain (add above the HTTPS rule):
   ```apache
   RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
   RewriteRule ^ https://%1%{REQUEST_URI} [L,R=301]
   ```

### 3.4 First run & admin account ⚠️
- The **first visit** to any page creates the database in `data/` and inserts the starter content.
- The **first person to open `https://rafid0.com/admin`** creates the admin account (username + password).
  **Do this immediately after upload, together with the owner** (or create it and hand the credentials over, then have the owner change the password in *Site Settings → Account & Password*).
- Don't leave the site live with no admin account created.

### 3.5 PHP settings
- Set PHP to **8.1+** in the panel.
- Upload limits: `.user.ini` (PHP-FPM) and `.htaccess` `<IfModule mod_php…>` (mod_php) request 256 MB. If the host caps it lower, raise `upload_max_filesize` / `post_max_size` in the panel's PHP options if possible. The admin shows the current limit and warns when a file is too big.
- If `.user.ini` changes don't apply immediately: it is cached for about 5 minutes (`user_ini.cache_ttl`).

---

## 4. Post-deploy checklist

**Public site**
- [ ] `https://rafid0.com` loads with a padlock; `http://` and `www.` redirect to `https://rafid0.com`
- [ ] Every page in the top menu loads; open one project page and one research page
- [ ] Light/dark toggle, mobile menu (phone), search (🔍 → "robot")
- [ ] Gallery: clicking a photo opens the full-screen viewer
- [ ] Contact form: send a test → it appears in **Admin → Messages**
- [ ] `https://rafid0.com/robots.txt` and `https://rafid0.com/sitemap.xml` show text/XML

**Security** (each of these must return **403 or 404**, never the file)
- [ ] `https://rafid0.com/data/config.php` (blank page or 403 is OK; it must not show content)
- [ ] `https://rafid0.com/data/` and `https://rafid0.com/includes/functions.php`
- [ ] `https://rafid0.com/README.md`
- [ ] `https://rafid0.com/uploads/` (no file listing)

**Admin**
- [ ] Admin account created; owner can log in at `/admin`
- [ ] Upload a test photo to a project → it shows on the project page and in the Gallery
- [ ] Profile → upload a CV PDF → the home page **Download CV** button downloads it, and it appears on the Downloads page
- [ ] Downloads → upload a PDF → it shows on the Downloads page and downloads
- [ ] Delete the test items afterwards
- [ ] Site Settings → Contact: set the owner's email; tick "Email me when someone sends a message"; send a contact test and check the inbox and spam folder

---

## 5. SEO setup (goal: rank #1 for "rafid0")

Already in the code: page titles "MD. Mujtuba Rafid (rafid0) — R&D Engineer", meta descriptions, Open Graph tags, canonical URLs, a `Person` + `WebSite` JSON-LD block on the home page (name, `alternateName: rafid0`, `sameAs` = social links), `sitemap.xml` and `robots.txt`.

Steps:
1. **Google Search Console** → add property → **Domain** `rafid0.com` → verify with the **TXT record** in Spaceship DNS (or Cloudflare DNS).
2. Search Console → Sitemaps → submit `https://rafid0.com/sitemap.xml`.
3. URL Inspection → `https://rafid0.com/` → **Request indexing**.
4. Optional: Bing Webmaster Tools → import from Search Console.
5. Ask the owner to fill in **Site Settings → Contact & Social** (LinkedIn, GitHub, etc.); these feed `sameAs`.

---

## 6. Email (optional)

- **Forwarding** `me@rafid0.com` → owner's Gmail: use Spaceship's email forwarding, or Cloudflare Email Routing if DNS is on Cloudflare.
- Contact-form alerts use PHP `mail()` from `no-reply@rafid0.com`. If they land in spam, add an SPF record for the host (from the hosting docs), or create a real mailbox on the host.

---

## 7. Backups & updates

- **All content** = `data/` (database) + `uploads/` (files). Back up both folders together (File Manager → Compress → Download) monthly, or enable the host's automatic backups.
- **Restore** = put both folders back. The database file name is in `data/config.php`; keep that file with the `.sqlite` file.
- **Code updates:** replace everything **except** `data/` and `uploads/`.
- **Moving from a local XAMPP copy:** upload the local `data/` and `uploads/` over the empty ones. Content and admin login come along.

---

## 8. Free hosting (InfinityFree or similar)

The owner may use a **free PHP host** instead of paid hosting. The site works the same way; check these points with the host's current limits:
- **SQLite:** confirm `pdo_sqlite` with the `check.php` test in §10 before anything else.
- **File size limit** (often about 10 MB per file): big videos go on **YouTube**, big files on **Google Drive**. The admin supports pasting links for both (Downloads → "paste a link instead"; Project → "Document / file links" and "Video links").
- **`mail()` is often disabled:** contact messages are still saved in Admin → Messages.
- **`.user.ini` / `php_value` may be ignored:** upload limits are whatever the host allows. If `.htaccess` causes a 500 error, remove the `<IfModule mod_php…>` blocks.
- **DNS:** point rafid0.com to the host's nameservers in Spaceship. Enable the host's free SSL, then turn on the HTTPS redirect (§3.3).
- **Inode/file-count limits:** keep uploads reasonable. Photos are resized automatically on upload.

## 9. Nginx hosts only

If the host runs Nginx without Apache, `.htaccess` is ignored. Apply the rules in `nginx.conf.example` (blocks `data/`, `includes/`, dotfiles; disables PHP in `uploads/`; robots/sitemap rewrites).
Even without those rules, the database has a random 24-character file name and uploads only accept whitelisted extensions (no `.php`, `.html`, `.svg`, `.js`). Apply the rules anyway.

---

## 10. Troubleshooting

| Problem | Fix |
|---|---|
| **500 error** right after upload | Remove the `<IfModule mod_php…>` blocks from `.htaccess` (some hosts forbid `php_value`); check that PHP ≥ 8.0 |
| "The /data folder is not writable" | Set `data/` to 755 (or 775 if the host requires) |
| Pages without styling | `.htaccess`/assets not uploaded, or the site was put in a sub-folder; it must be in `public_html/` root |
| Uploads fail / "too large" | Raise `upload_max_filesize` and `post_max_size`; or upload fewer files at once; use YouTube links for large videos |
| Redirect loop after enabling HTTPS | Cloudflare set to *Flexible*: change it to *Full*. Or the host terminates SSL: the rule already checks `X-Forwarded-Proto` |
| Admin locked out ("Too many failed attempts") | Wait 15 minutes (5 failures per IP / 15 min) |
| Forgot admin password | See §11 |
| PHP errors | Logged to `data/error.log` (not public). Display is off |

**Quick environment test:** create `public_html/check.php` with
`<?php echo PHP_VERSION, ' sqlite:', extension_loaded('pdo_sqlite') ? 'yes' : 'NO', ' gd:', extension_loaded('gd') ? 'yes' : 'no';`
Open it in the browser, then **delete it**.

---

## 11. Admin password reset (developer only)

Create `public_html/reset.php` temporarily:
```php
<?php
require __DIR__ . '/includes/bootstrap.php';
set_setting('admin_pass_hash', password_hash('TEMPORARY-Passw0rd!', PASSWORD_DEFAULT));
echo 'Password reset. Username: ' . e(setting('admin_user')) . ' — DELETE THIS FILE NOW.';
```
Open it once, **delete it immediately**, log in, and change the password in *Site Settings → Account & Password*.
(Alternatively delete the `admin_pass_hash` row in the settings table: the next visit to `/admin` shows the "create account" screen.)

---

## 12. Technical reference

- **Routing:** one PHP file per page; no URL rewriting needed. `BASE_URL` is auto-detected, so the site also works in a sub-folder (but the `ErrorDocument 404 /404.php` line in `.htaccess` assumes the domain root).
- **Database:** SQLite, file `data/site-<random>.sqlite`. Tables: `settings` (key/value: all site texts, profile, CV path, admin credentials), `projects`, `project_media` (image/video/document per project), `research`, `gallery`, `downloads`, `messages`, `login_attempts`. Schema in `includes/db.php` (`PRAGMA user_version`).
- **Security:** `password_hash` (bcrypt/argon), session cookies HttpOnly + SameSite=Lax (+ Secure on HTTPS), session ID regenerated on login, CSRF token on every form, login rate-limit, prepared statements everywhere, all output escaped, uploads checked by extension whitelist plus `getimagesize` for images, random file names, no script execution in `uploads/`, admin pages `noindex` + `X-Frame-Options`.
- **Content model for owners:** multi-line text fields use simple formats: `Name: Value` (specs), `Value | Label` (results, stats), `Period | Role | Org | Description` (experience). Documented in the admin form hints and in README.md.
- **Starter content:** 7 projects, 3 research items and sample SVG illustrations (`assets/img/demo/`). Job history, education and awards are **placeholders** the owner must replace.

---

## 13. Handover to the owner

Give the owner:
1. The admin URL `https://rafid0.com/admin` plus the username and password (in person or by private message, never in a public channel).
2. Confirmation that HTTPS, the contact form and CV download work.
3. Search Console access (add the owner's Gmail as an **Owner** in Search Console → Settings → Users).
4. Hosting backups: confirm they are on, or show the owner how to download `data/` + `uploads/`.

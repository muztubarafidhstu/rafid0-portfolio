# Portfolio Website + Admin Panel — Launch Guide

Your complete portfolio website. You update everything (projects, research, photos,
videos, PDFs, CV, contact details) from the **Admin Panel**, and the website changes
instantly. No coding needed.

---

## 1. What to buy

| Item | What to choose | Approx. price |
|---|---|---|
| **Domain** | e.g. `yourname.com` (Namecheap, Hostinger, Porkbun…) | $10–15 / year |
| **Hosting** | Any **shared hosting with cPanel or hPanel and PHP 8** — e.g. Hostinger "Premium", Namecheap "Stellar", or a local BD host with cPanel | $2–5 / month |

✅ Requirements (every normal plan has these): **PHP 8.0 or newer**, **SQLite** (`pdo_sqlite`), Apache or LiteSpeed.
❌ You do **not** need MySQL, WordPress, Node.js or a VPS.

Tip: buy the domain and hosting from the same company — it then connects automatically.

---

## 2. Upload the website (about 10 minutes)

1. Log in to your hosting control panel (cPanel / hPanel).
2. Make sure your domain has **free SSL (https)** turned on — usually under *SSL* → *Let's Encrypt*, often automatic.
3. Open **File Manager** → go into the folder **`public_html`**.
4. Delete the default files in it (like `default.php` or `index.html`), if any.
5. Click **Upload** → upload `portfolio-website.zip`.
6. Right-click the zip → **Extract**. Make sure the files (`index.php`, `admin`, `assets`…) end up **directly inside `public_html`**, not in a sub-folder.
   (If they went into `public_html/portfolio-website/`, open that folder, select all, and **Move** them to `public_html`.)
7. Check folder permissions: `data` and `uploads` should be **755** (usually already correct).
8. In the hosting panel, set **PHP version 8.1 or newer** (cPanel: *Select PHP Version* / hPanel: *PHP Configuration*).

---

## 3. Create your admin account (do this right away!)

1. Open **`https://yourdomain.com/admin`**
2. The first time, it asks you to **create a username and password**. Choose a strong password and save it.
   ⚠️ Do this immediately after uploading — the first person who opens `/admin` creates the account.
3. You are now in the Admin Panel. 🎉

---

## 4. Make it yours (Admin Panel)

Follow the **"Getting started checklist"** on the Dashboard:

| Where | What to do |
|---|---|
| **Site Settings → General** | Your name, job title, description for Google, accent colour, logo |
| **Site Settings → Home page** | Headline, intro text, hero image, statistics (35+ Projects…), expertise cards |
| **Site Settings → Contact & Social** | Email, phone, LinkedIn, GitHub, YouTube, Google Scholar… |
| **Profile, About & CV** | Photo, biography, experience, education, skills, awards, **upload CV PDF** |
| **Projects** | Edit the sample projects (replace sample pictures with your real photos) or add new ones. Drag & drop images, videos, PDFs, ZIP files. |
| **Research** | Papers, patents, ongoing work (with PDF and publisher link) |
| **Gallery** | Upload photos/videos of lab work, field tests, events |
| **Downloads** | Datasets, source code, manuals |
| **Messages** | Messages from your Contact page (also emailed to you) |

**Writing tips (in the text boxes):**
- Lists: one item per line.
- Specifications: `Name: Value` per line — e.g. `Accuracy: > 95%`
- Results: `Value | Label` per line — e.g. `95.6% | Sorting Accuracy`
- Experience: `Period | Job title | Company | Description`
- Blank line = new paragraph, `**bold**` = **bold**.
- Big videos: upload them to YouTube and paste the link (faster website, no size limit).
- Big files (datasets, CAD models, ZIPs): put them on **Google Drive** (Share → "Anyone with the link" → Copy link) and paste the link in **Downloads → "…or paste a link instead"**, or in a project's **"Document / file links"** box as `Title | link`.

★ **Featured** projects (first 4) and research (first 3) are shown on the home page.

---

## 5. After launch

- **Google:** add your site to [Google Search Console](https://search.google.com/search-console) and submit `https://yourdomain.com/sitemap.php`.
- **Force https:** open `.htaccess` in File Manager and remove the `#` from the 3 lines under "Uncomment the 3 lines below AFTER SSL works".
- **Contact emails:** messages are always saved in Admin → Messages. Email alerts use your host's mail; if they land in spam, create an email account `you@yourdomain.com` in your hosting and use it as the contact email.
- **Backup:** once a month, in File Manager, download (compress) the **`data`** and **`uploads`** folders. That is your whole website content.
- **Forgot your password?** In File Manager, open `data/`, delete the `.sqlite` file **only if you want to start over**. Better: ask your hosting support, or contact the developer.

---

## Upload limits

The site asks the server for up to 256 MB per upload (`.user.ini`). Some hosts cap it lower
(often 64–128 MB). If an upload fails, upload fewer files at once, or use a YouTube link for video.
Admin → Projects shows a warning if a file is too big.

---

## Website pages

`/` Home · `/projects.php` · `/project.php?slug=…` · `/research.php` · `/gallery.php` ·
`/downloads.php` · `/about.php` · `/cv.php` · `/contact.php` · `/search.php` · `/admin`

## Technical notes (for developers)

- Plain PHP 8 + SQLite, no framework, no build step, no external dependencies.
- Database file is created automatically in `data/` with a random name (see `data/config.php`).
- `data/` and `includes/` are blocked from the web; `uploads/` cannot execute scripts.
- Security: password hashing, CSRF tokens, login rate-limiting, safe upload validation,
  escaped output, honeypot + rate limit on the contact form.
- Sample content is in `includes/seed.php` and is only inserted on first run.

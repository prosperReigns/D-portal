# Examcenter Official Website

The official public website for **Examcenter**, built with Flask and server-rendered templates.

The website is intentionally separate from the examination application. Its job is to help people **discover, download, learn, license, and get support for Examcenter**.

## Website responsibilities

- **Product information:** explain what Examcenter is, who it serves, the problems it solves, and its offline-first architecture.
- **Downloads:** provide the official Windows installer and track successful downloads.
- **Documentation:** installation, setup, examination workflow, results, licensing and troubleshooting guidance.
- **Pricing and licensing:** explain the Free/Core examination experience and Pro supporting services, then hand licensing actions to the configured license portal.
- **Support:** provide troubleshooting guidance and a contact channel for installation, usage, technical and licensing questions.
- **Company/product presence:** explain the product, KTS, partners, school/community stories and ways to contact the team.

## Important boundary

The website **does not conduct examinations** and should not become a runtime dependency for exam day.

The intended architecture is:

`Examcenter Website` → information, downloads, documentation, pricing, support, licensing entry point

`License Server` → customers, payments, licenses, activation and entitlement validation

`Local Examcenter Application` → actual examination process, operating offline

This separation is deliberate. A school should be able to conduct an examination in its local Examcenter environment without needing the public website to be online.

## Free/Core vs Pro

The public website communicates a simple licensing principle:

> **The features necessary to conduct an examination remain Core/free. Pro is for supporting capabilities around the examination process.**

The configured pricing page currently supports:

- Core — Free
- 6 Months Pro — ₦150,000
- 1 Year Pro — ₦250,000
- 2 Years Pro — ₦450,000

Prices are environment-configurable. The website is not the license authority; `LICENSE_PORTAL_URL` can point Pro customers to the separate licensing service.

## Stack

- Frontend: Flask/Jinja templates with server-rendered HTML/CSS
- Backend: Flask + Flask-SQLAlchemy
- Database: PostgreSQL in production, SQLite fallback for local preview
- Installer: configurable Windows `.exe` upload/download

## Run locally

```powershell
cd backend
python -m venv .venv
.\\.venv\\Scripts\\Activate.ps1
pip install -r requirements.txt
Copy-Item .env.example .env
python app.py
```

The site runs at `http://localhost:5000` by default.

## Main public routes

- `/` — product overview
- `/download-app` — official Windows download
- `/guides` — documentation and getting started
- `/pricing` — Core/Pro plans
- `/license` — licensing portal hand-off
- `/support` — support hub
- `/about` — company and product story
- `/clients` — public school/community directory when configured
- `/partners` — partner information
- `/testimonials` — community stories
- `/contact` — contact/support form

## Installer administration

The owner-only installer upload page is available at `/admin/installer`. Registration is controlled by `ALLOW_ADMIN_REGISTRATION`.

Do not commit a real `.env` file or credentials. In production use HTTPS, a strong `SECRET_KEY`, a production database, secure cookies and restricted access to the installer administration area.

## Deployment boundary

The website may be deployed publicly (for example on a cloud platform), while the Examcenter desktop application remains the offline examination environment. The license server is a separate service and should remain the authority for license validation and entitlement state.

# KTS & Examcenter Public Website

The public website is the company and product information hub for **KTS (Kingstech Software)** and its product **Examcenter**. It is built with Flask and server-rendered Jinja templates.

## Brand and route structure

- `/` — KTS corporate homepage: company positioning, product family and company contact.
- `/products/examcenter` — Examcenter product homepage and feature overview.
- `/download-app` — official Windows installer page.
- `/guides` — product documentation and getting started.
- `/pricing` — Core/Pro plans.
- `/license` — hand-off to the configured licensing portal.
- `/support` — troubleshooting and support hub.
- `/about`, `/clients`, `/partners`, `/testimonials`, `/contact` — product/company information and contact.
- `/admin/installer` — owner-only installer upload page.

The intended public domain structure is one KTS domain, with Examcenter presented as a product under `/products/examcenter`. If a dedicated product subdomain is used later, it can point to the same product experience without changing the product's role in the KTS brand family. The licensing service remains a separate service and hostname (for example, `license.kingstechsoftwares.com`).

## Responsibilities and boundaries

`KTS website` → company identity, product discovery and future product family.

`Examcenter website pages` → product information, downloads, documentation, pricing, support and licensing hand-off.

`License server` → customers, payments, licenses, activation and entitlement validation. It remains the authority for licensing; the public website is not.

`Local Examcenter application` → actual examination process, including local/offline operation.

The website must not conduct examinations or become a runtime dependency on exam day. A school must be able to conduct examinations in its local Examcenter environment even when the public website is unavailable.

## Core and Pro

The public website communicates this licensing principle: **everything necessary to conduct an examination remains Core/free; Pro covers supporting capabilities around the examination process.**

Current configurable plans:

- Core — Free
- 6 Months Pro — ₦150,000
- 1 Year Pro — ₦250,000
- 2 Years Pro — ₦450,000

Prices are environment-configurable. `LICENSE_PORTAL_URL` points to the separate licensing service.

## Architecture

- Flask application factory and route blueprints
- Jinja templates, shared product layout and a separate corporate KTS layout
- Shared base styles plus `corporate.css` for the corporate homepage
- Flask-SQLAlchemy
- PostgreSQL in production; SQLite fallback for local preview
- Configurable Windows `.exe` upload/download

The company homepage is rendered by `backend/templates/index.html`. The existing Examcenter product landing page is preserved at `backend/templates/examcenter.html`, served by `/products/examcenter`. Product pages use the shared product layout and navigation; the KTS homepage uses `base_corporate.html`.

## Run locally

```powershell
cd backend
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
Copy-Item .env.example .env
python app.py
```

Open `http://localhost:5000`.

## Installer administration and production

Registration is controlled by `ALLOW_ADMIN_REGISTRATION`. Do not commit a real `.env` file or credentials. In production use HTTPS, a strong `SECRET_KEY`, a production database, secure cookies and restricted access to installer administration.

The website may be deployed publicly while the Examcenter desktop application remains the offline examination environment. The license server is a separate service and should remain the authority for license validation and entitlement state.

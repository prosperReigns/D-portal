# Kings CBT Website

A full-stack marketing and download site for the Kings CBT Windows application, rendered server-side with Flask templates.

## Stack

- **Frontend:** Flask/Jinja templates with server-rendered HTML and CSS
- **Backend:** Flask + Flask-SQLAlchemy
- **Database:** PostgreSQL (Docker Compose), with SQLite fallback for local preview

## Run the website

```powershell
cd backend
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
Copy-Item .env.example .env
python app.py
```

The website runs at `http://localhost:5000` by default. Update `backend/.env` for the database URL, host, port, public contact details, video IDs, installer filename, and upload limits. Add the real Windows installer at `backend/uploads/<INSTALLER_FILENAME>`. The download button records a download event before serving the file.

Public school listings are runtime content. Set `SCHOOLS_JSON` to a JSON list or point `SCHOOLS_FILE` at a JSON file:

```json
[{"name":"Example Secondary School","location":"City, Country","students":"1,200+"}]
```

## Private installer upload

The owner-only upload page is available at `/admin/installer`. The `/admin/register` route creates the initial administrator account only while `ALLOW_ADMIN_REGISTRATION=true`; after the account exists, registration closes and admins sign in with the stored password hash.

Do not commit a real `.env` file or credentials. In production, serve the site over HTTPS, set `APP_ENV=production`, set a long random `SECRET_KEY`, set `DATABASE_URL`, and enable secure cookies with `SESSION_COOKIE_SECURE=true`.

The upload accepts only the configured installer filename/extension and replaces the public installer atomically. Consider restricting `/admin/installer` by VPN, IP allowlist, or provider-level access controls.

## PostgreSQL

Copy `backend/.env.example` to `backend/.env`, set `POSTGRES_PASSWORD`, then start PostgreSQL with `docker compose up -d db`. The same file provides `DATABASE_URL` for Flask. The database stores download events, contact messages, and admin users.

Initialize tables explicitly when `AUTO_CREATE_DATABASE=false`:

```bash
cd backend
flask --app app init-db
```

## Production

Use a WSGI server instead of Flask's development server:

```bash
cd backend
APP_ENV=production gunicorn "app:app"
```

For production, set `AUTO_CREATE_DATABASE=false` after your database tables have been created, set `TRUST_PROXY_HEADERS=true` only behind a trusted reverse proxy, and enable `HSTS_ENABLED=true` only when HTTPS is fully configured. The app will fail fast in production if `SECRET_KEY`, `DATABASE_URL`, secure cookies, or debug settings are unsafe.

Docker Compose can run both the web app and PostgreSQL:

```bash
Copy-Item backend/.env.example backend/.env
docker compose up --build
```

Before the first public deployment, temporarily set `ALLOW_ADMIN_REGISTRATION=true`, create the admin account at `/admin/register`, then set it back to `false` and redeploy.

## Video content

The two guide cards are ready for the setup and usage videos. Replace the card interaction with the final YouTube/Vimeo URLs, or wire in hosted MP4 files when the recordings are available.

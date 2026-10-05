# examcenter-website — Project Journal

Persistence anchor for this workspace's agent memory. The agent maintains this file:
append notable decisions, changes, and session notes so they survive across chats and
sessions. Newest entries on top. `get_project_briefing` reads the sections below.

## About

Server-rendered Flask marketing and download site for the Kings CBT Windows application. The backend serves Jinja templates, tracks installer downloads/contact messages with SQLAlchemy, and includes admin-only installer upload management.

## Recent Changes

- 2026-08-23: Refreshed the shared `styles.css` UI system with a more professional visual treatment across public, contact, download, and admin pages.
- 2026-08-22: Rewired templates to load the updated `styles.css` bundle from `base.html`.
- 2026-08-22: Split template stylesheet loading so `base.html` loads shared CSS and each template loads a matching page-specific CSS entrypoint.
- 2026-08-22: Hardened production readiness with fail-fast config validation, CSRF-protected admin/contact forms, env/file-backed school data, Docker/Gunicorn web service setup, readiness health check, and upload route reliability fixes.
- 2026-08-22: Added centralized environment-driven Flask config, production secret validation, proxy/security header support, configurable installer/contact/video values, env-backed Docker Compose settings, and production README guidance.
- 2026-08-20: Fixed stale template homepage endpoint references from `site_pages.home` to `home.home`; added direct `SQLAlchemy` and `Werkzeug` dependencies to `backend/requirements.txt`.

## Session Memory

- 2026-08-23: UI refresh stayed centralized in `backend/static/styles.css`; local Flask render/server check is blocked until a Windows-usable environment with Flask is available.
- 2026-08-22: Removed literal Markdown fence lines from HTML templates; static endpoint audit still reports no missing endpoints.
- 2026-08-20: Static endpoint audit checks Jinja/Python `url_for(...)` calls against route blueprint endpoints; current audit reports no missing endpoints.

"""Small security helpers shared by form routes and templates."""

import secrets
from hmac import compare_digest

from flask import request, session


CSRF_SESSION_KEY = "_csrf_token"


def csrf_token():
    """Return the current session's CSRF token, creating one if needed."""
    token = session.get(CSRF_SESSION_KEY)
    if not token:
        token = secrets.token_urlsafe(32)
        session[CSRF_SESSION_KEY] = token
    return token


def csrf_token_is_valid():
    """Validate a submitted CSRF token from a form field or request header."""
    expected = session.get(CSRF_SESSION_KEY)
    submitted = request.form.get("csrf_token") or request.headers.get("X-CSRF-Token")
    return bool(
        expected
        and submitted
        and compare_digest(str(expected), str(submitted))
    )

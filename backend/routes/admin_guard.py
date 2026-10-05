"""Shared session guard for private administrator endpoints."""

from functools import wraps

from flask import redirect, request, session, url_for

from extensions import db
from models import AdminUser


def admin_required(view):
    """Redirect anonymous visitors to login before executing an admin view."""
    @wraps(view)
    def wrapped_view(*args, **kwargs):
        admin_user_id = session.get("admin_user_id")
        if not admin_user_id or db.session.get(AdminUser, admin_user_id) is None:
            session.clear()
            return redirect(url_for("admin_auth.login", next=request.full_path))
        return view(*args, **kwargs)

    return wrapped_view

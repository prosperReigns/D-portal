"""Registration and session-based login routes for Kings CBT administrators."""

import re

from flask import Blueprint, abort, current_app, flash, redirect, render_template, request, session, url_for
from sqlalchemy.exc import IntegrityError

from extensions import db
from models import AdminUser
from security import csrf_token_is_valid

admin_auth_bp = Blueprint("admin_auth", __name__)
_USERNAME_PATTERN = re.compile(r"^[A-Za-z0-9][A-Za-z0-9_.-]{2,79}$")


def _valid_credentials(username, password):
    """Validate account fields before hashing or querying the database."""
    return bool(
        _USERNAME_PATTERN.fullmatch(username or "")
        and isinstance(password, str)
        and 12 <= len(password) <= 256
    )


@admin_auth_bp.route("/admin/register", methods=["GET", "POST"])
def register():
    """Create the first admin account; close public registration afterward."""
    if AdminUser.query.first() is not None or not current_app.config["ALLOW_ADMIN_REGISTRATION"]:
        flash("Administrator registration is closed. Sign in with the existing account.", "info")
        return redirect(url_for("admin_auth.login"))

    if request.method == "POST":
        if not csrf_token_is_valid():
            abort(400, description="Invalid CSRF token.")

        username = request.form.get("username", "").strip()
        password = request.form.get("password", "")
        confirmation = request.form.get("password_confirmation", "")
        if not _valid_credentials(username, password):
            flash("Use a username with 3–80 letters, numbers, dots, dashes, or underscores and a password of at least 12 characters.", "error")
        elif password != confirmation:
            flash("The passwords do not match.", "error")
        else:
            admin = AdminUser(username=username)
            admin.set_password(password)
            db.session.add(admin)
            try:
                db.session.commit()
            except IntegrityError:
                db.session.rollback()
                flash("An administrator already exists. Please sign in.", "info")
                return redirect(url_for("admin_auth.login"))
            session.clear()
            session.permanent = True
            session["admin_user_id"] = admin.id
            session["admin_username"] = admin.username
            flash("Your administrator account is ready.", "success")
            return redirect(url_for("installer_upload.installer_upload"))

    return render_template("admin_register.html")


@admin_auth_bp.route("/admin/login", methods=["GET", "POST"])
def login():
    """Authenticate an administrator with a password hash and establish a session."""
    if session.get("admin_user_id"):
        return redirect(url_for("installer_upload.installer_upload"))

    if request.method == "POST":
        if not csrf_token_is_valid():
            abort(400, description="Invalid CSRF token.")

        username = request.form.get("username", "").strip()
        password = request.form.get("password", "")
        admin = AdminUser.query.filter_by(username=username).first()
        if not admin or not admin.check_password(password):
            flash("Invalid username or password.", "error")
        else:
            session.clear()
            session.permanent = True
            session["admin_user_id"] = admin.id
            session["admin_username"] = admin.username
            return redirect(url_for("installer_upload.installer_upload"))

    return render_template("admin_login.html")


@admin_auth_bp.post("/admin/logout")
def logout():
    """End the current administrator session."""
    if not csrf_token_is_valid():
        abort(400, description="Invalid CSRF token.")

    session.clear()
    flash("You have been signed out.", "success")
    return redirect(url_for("admin_auth.login"))

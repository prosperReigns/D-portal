"""HTML contact-form submission endpoint."""

from flask import Blueprint, abort, flash, redirect, request, url_for

from extensions import db
from models.contact_message import ContactMessage
from security import csrf_token_is_valid

contact_page_bp = Blueprint("contact_page", __name__)


@contact_page_bp.post("/contact")
def contact_page():
    if not csrf_token_is_valid():
        abort(400, description="Invalid CSRF token.")

    name = request.form.get("name", "").strip()
    email = request.form.get("email", "").strip()
    message = request.form.get("message", "").strip()
    if not name or not email or not message:
        flash("Name, email, and message are required.", "error")
        return redirect(url_for("site_pages.contact"))

    db.session.add(ContactMessage(name=name, email=email, message=message))
    db.session.commit()
    flash("Message sent. We'll be in touch soon.", "success")
    return redirect(url_for("site_pages.contact"))

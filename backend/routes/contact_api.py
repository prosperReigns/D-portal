"""JSON contact-form submission endpoint."""

from flask import Blueprint, current_app, jsonify, request
from sqlalchemy.exc import SQLAlchemyError

from extensions import db
from models.contact_message import ContactMessage

contact_api_bp = Blueprint("contact_api", __name__)


@contact_api_bp.post("/api/contact")
def contact():
    data = request.get_json(silent=True) or {}
    name = data.get("name", "").strip()
    email = data.get("email", "").strip()
    message = data.get("message", "").strip()
    if not name or not email or not message:
        return jsonify({"error": "Name, email, and message are required."}), 400

    try:
        db.session.add(ContactMessage(name=name, email=email, message=message))
        db.session.commit()
    except SQLAlchemyError:
        db.session.rollback()
        current_app.logger.exception("Failed to save contact message.")
        return jsonify({"error": "Your message could not be saved right now."}), 503

    company_name = current_app.config["COMPANY_NAME"]
    return jsonify({"message": f"Thanks - your message is on its way to {company_name}."}), 201

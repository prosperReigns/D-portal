"""Health-check endpoint."""

from flask import Blueprint, current_app, jsonify
from sqlalchemy import text

from extensions import db

health_bp = Blueprint("health", __name__)


@health_bp.get("/api/health")
def health():
    return jsonify({"status": "ok", "service": current_app.config["SERVICE_NAME"]})


@health_bp.get("/api/ready")
def ready():
    db.session.execute(text("SELECT 1"))
    return jsonify({"status": "ready", "service": current_app.config["SERVICE_NAME"]})

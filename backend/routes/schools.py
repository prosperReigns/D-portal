"""Schools directory endpoint."""

from flask import Blueprint, current_app, jsonify

schools_bp = Blueprint("schools", __name__)


@schools_bp.get("/api/schools")
def schools():
    return jsonify(current_app.config["SCHOOLS"])

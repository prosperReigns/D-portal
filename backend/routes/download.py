"""Installer download endpoint with download-event tracking."""

from pathlib import Path

from flask import Blueprint, current_app, jsonify, send_from_directory
from sqlalchemy.exc import SQLAlchemyError

from extensions import db
from models.download_event import DownloadEvent

download_bp = Blueprint("download", __name__)


@download_bp.get("/download")
@download_bp.get("/api/download")
def download():
    installer_dir = Path(current_app.config["INSTALLER_PATH"])
    installer = current_app.config["INSTALLER_FILENAME"]
    file_path = installer_dir / installer
    if not file_path.exists():
        return jsonify({"error": "The installer is not available yet."}), 404

    try:
        db.session.add(DownloadEvent())
        db.session.commit()
    except SQLAlchemyError:
        db.session.rollback()
        current_app.logger.exception("Failed to record download event.")
    return send_from_directory(installer_dir, installer, as_attachment=True)

"""Public application statistics endpoint."""

from flask import Blueprint, current_app, jsonify

from models.download_event import DownloadEvent

stats_bp = Blueprint("stats", __name__)


@stats_bp.get("/api/stats")
def stats():
    return jsonify(
        {
            "downloads": DownloadEvent.query.count(),
            "schools": len(current_app.config["SCHOOLS"]),
        }
    )

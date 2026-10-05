"""Homepage endpoint for the server-rendered website."""

from flask import Blueprint, current_app, render_template

from models.download_event import DownloadEvent

home_bp = Blueprint("home", __name__)


@home_bp.get("/")
def home():
    return render_template(
        "index.html",
        schools=current_app.config["SCHOOLS"],
        downloads=DownloadEvent.query.count(),
    )

"""Homepage and product landing routes for the public KTS website."""

from flask import Blueprint, current_app, render_template

from models.download_event import DownloadEvent

home_bp = Blueprint("home", __name__)


@home_bp.get("/")
def home():
    """Render the KTS parent-company homepage."""
    return render_template("index.html")


@home_bp.get("/products/examcenter")
def examcenter_product():
    """Render the Examcenter product landing page under the KTS brand."""
    return render_template(
        "examcenter.html",
        schools=current_app.config["SCHOOLS"],
        downloads=DownloadEvent.query.count(),
    )

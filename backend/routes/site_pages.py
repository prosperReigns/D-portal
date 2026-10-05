"""Focused public content pages for the Kings CBT website."""

from flask import Blueprint, current_app, render_template

from models.download_event import DownloadEvent

site_pages_bp = Blueprint("site_pages", __name__)


@site_pages_bp.get("/about")
def about():
    """Explain Kings Tech Software and the product's human-centered mission."""
    return render_template("about.html", downloads=DownloadEvent.query.count())


@site_pages_bp.get("/testimonials")
def testimonials():
    """Show educator stories and outcomes from Kings CBT users."""
    return render_template("testimonials.html", downloads=DownloadEvent.query.count())


@site_pages_bp.get("/partners")
def partners():
    """Present the organizations supporting the CBT ecosystem."""
    return render_template("partners.html")


@site_pages_bp.get("/clients")
def clients():
    """List schools and locations currently running Kings CBT."""
    return render_template("clients.html", schools=current_app.config["SCHOOLS"])


@site_pages_bp.get("/contact")
def contact():
    """Render the public contact page and its message form."""
    return render_template("contact.html")


@site_pages_bp.get("/download-app")
def download_page():
    """Render the dedicated Windows download page with live download count."""
    return render_template("download.html", downloads=DownloadEvent.query.count())

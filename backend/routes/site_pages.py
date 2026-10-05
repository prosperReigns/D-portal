"""Public information pages for the Examcenter website."""

from flask import Blueprint, current_app, redirect, render_template

from models.download_event import DownloadEvent

site_pages_bp = Blueprint("site_pages", __name__)


def _page_context():
    return {"downloads": DownloadEvent.query.count()}


@site_pages_bp.get("/about")
def about():
    """Explain KTS and why Examcenter exists."""
    return render_template("about.html", **_page_context())


@site_pages_bp.get("/testimonials")
def testimonials():
    """Show educator stories and outcomes."""
    return render_template("testimonials.html", **_page_context())


@site_pages_bp.get("/partners")
def partners():
    """Present useful deployment and education partnerships."""
    return render_template("partners.html")


@site_pages_bp.get("/clients")
def clients():
    """Show schools using Examcenter when public directory data is configured."""
    return render_template("clients.html", schools=current_app.config["SCHOOLS"])


@site_pages_bp.get("/contact")
def contact():
    """Render the public contact and support form."""
    return render_template("contact.html")


@site_pages_bp.get("/download-app")
def download_page():
    """Render the official Windows download page."""
    return render_template("download.html", **_page_context())


@site_pages_bp.get("/guides")
def guides():
    """Central documentation and getting-started hub."""
    return render_template("guides.html")


@site_pages_bp.get("/support")
def support():
    """Central support and troubleshooting hub."""
    return render_template("support.html")


@site_pages_bp.get("/pricing")
def pricing():
    """Explain the Free/Core and Pro licensing model."""
    return render_template(
        "pricing.html",
        plans=current_app.config["PRICING_PLANS"],
        license_portal_url=current_app.config["LICENSE_PORTAL_URL"],
    )


@site_pages_bp.get("/license")
def license():
    """Send customers to the configured license portal."""
    target = current_app.config["LICENSE_PORTAL_URL"]
    if target:
        return redirect(target)
    return redirect("/pricing")

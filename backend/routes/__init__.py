"""HTTP endpoint blueprints."""

from routes.admin_auth import admin_auth_bp
from routes.contact_api import contact_api_bp
from routes.contact_page import contact_page_bp
from routes.download import download_bp
from routes.health import health_bp
from routes.home import home_bp
from routes.installer_upload import installer_upload_bp
from routes.schools import schools_bp
from routes.stats import stats_bp
from routes.site_pages import site_pages_bp

ALL_BLUEPRINTS = (
    home_bp,
    admin_auth_bp,
    health_bp,
    stats_bp,
    schools_bp,
    contact_page_bp,
    contact_api_bp,
    download_bp,
    installer_upload_bp,
    site_pages_bp,
)

__all__ = ["ALL_BLUEPRINTS"]

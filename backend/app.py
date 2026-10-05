"""Application factory and development entrypoint."""

from flask import Flask
from werkzeug.middleware.proxy_fix import ProxyFix

from config import Config
from extensions import db
from models import ContactMessage, DownloadEvent  # noqa: F401 - register models with SQLAlchemy
from routes import ALL_BLUEPRINTS
from security import csrf_token


def create_app():
    """Create and configure the Flask application and register all endpoints."""
    app = Flask(__name__, template_folder="templates", static_folder="static")
    app.config.from_object(Config)

    if app.config["TRUST_PROXY_HEADERS"]:
        app.wsgi_app = ProxyFix(app.wsgi_app, x_for=1, x_proto=1, x_host=1, x_prefix=1)

    db.init_app(app)
    for blueprint in ALL_BLUEPRINTS:
        app.register_blueprint(blueprint)

    register_template_globals(app)
    register_security_headers(app)
    register_cli_commands(app)

    if app.config["AUTO_CREATE_DATABASE"]:
        with app.app_context():
            db.create_all()

    return app


def register_cli_commands(app):
    """Register operational commands for deployment tasks."""

    @app.cli.command("init-db")
    def init_db():
        """Create database tables for the configured database."""
        db.create_all()
        print("Database tables are ready.")


def register_template_globals(app):
    """Expose public configuration to every template."""

    @app.context_processor
    def inject_public_settings():
        return {
            "app_settings": {
                "product_name": app.config["PRODUCT_NAME"],
                "company_name": app.config["COMPANY_NAME"],
                "contact_email": app.config["CONTACT_EMAIL"],
                "support_hours": app.config["SUPPORT_HOURS"],
                "youtube_embed_base_url": app.config["YOUTUBE_EMBED_BASE_URL"],
                "copyright_year": app.config["COPYRIGHT_YEAR"],
                "product_name_upper": app.config["PRODUCT_NAME"].upper(),
                "product_initial": app.config["PRODUCT_NAME"][:1].upper(),
                "allow_admin_registration": app.config["ALLOW_ADMIN_REGISTRATION"],
            },
            "setup_video_id": app.config["SETUP_VIDEO_ID"],
            "usage_video_id": app.config["USAGE_VIDEO_ID"],
            "csrf_token": csrf_token,
        }


def register_security_headers(app):
    """Add conservative headers that are safe for this template-based app."""

    @app.after_request
    def add_security_headers(response):
        response.headers.setdefault("X-Content-Type-Options", "nosniff")
        response.headers.setdefault("X-Frame-Options", "SAMEORIGIN")
        response.headers.setdefault("Referrer-Policy", "strict-origin-when-cross-origin")
        response.headers.setdefault(
            "Permissions-Policy",
            "camera=(), microphone=(), geolocation=()",
        )
        if app.config["CONTENT_SECURITY_POLICY"]:
            response.headers.setdefault(
                "Content-Security-Policy",
                app.config["CONTENT_SECURITY_POLICY"],
            )
        if app.config["HSTS_ENABLED"]:
            response.headers.setdefault(
                "Strict-Transport-Security",
                f"max-age={app.config['HSTS_MAX_AGE']}; includeSubDomains",
            )
        return response


app = create_app()


if __name__ == "__main__":
    app.run(
        host=app.config["SERVER_HOST"],
        port=app.config["PORT"],
        debug=app.config["DEBUG"],
    )

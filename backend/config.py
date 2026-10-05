"""Environment-driven application configuration."""

import os
import re
from datetime import datetime, timedelta
from pathlib import Path

from dotenv import load_dotenv

from data import load_schools

BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / ".env")

TRUE_VALUES = {"1", "true", "yes", "on"}
VALID_APP_ENVS = {"development", "testing", "production"}
VALID_SAMESITE_VALUES = {"Lax", "Strict", "None"}
SAFE_FILENAME_PATTERN = re.compile(r"^[A-Za-z0-9_.-]+$")


def env(name, default=""):
    """Read an environment variable with optional whitespace cleanup."""
    value = os.getenv(name)
    if value is None:
        return default
    return value.strip()


def env_bool(name, default=False):
    value = os.getenv(name)
    if value is None:
        return default
    return value.strip().lower() in TRUE_VALUES


def env_int(name, default):
    value = os.getenv(name)
    if value is None or not value.strip():
        return default
    try:
        return int(value)
    except ValueError as exc:
        raise RuntimeError(f"{name} must be an integer.") from exc


def env_path(name, default):
    value = Path(env(name, default))
    if value.is_absolute():
        return str(value)
    return str(BASE_DIR / value)


class Config:
    """Runtime settings loaded from environment variables."""

    APP_ENV = env("APP_ENV", env("FLASK_ENV", "development")).lower()
    DEBUG = env_bool("FLASK_DEBUG", APP_ENV == "development")
    TESTING = env_bool("TESTING", False)

    SECRET_KEY = env("SECRET_KEY", "" if APP_ENV == "production" else "dev-only-secret-key")
    if APP_ENV == "production" and not SECRET_KEY:
        raise RuntimeError("SECRET_KEY must be set when APP_ENV=production.")

    raw_database_url = env("DATABASE_URL")
    if not raw_database_url:
        raw_database_url = f"sqlite:///{BASE_DIR / 'kings_cbt.db'}"
    SQLALCHEMY_DATABASE_URI = raw_database_url.replace("postgres://", "postgresql://", 1)
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    AUTO_CREATE_DATABASE = env_bool("AUTO_CREATE_DATABASE", APP_ENV != "production")

    INSTALLER_PATH = env_path("INSTALLER_PATH", "uploads")
    INSTALLER_FILENAME = env("INSTALLER_FILENAME", "examcenter-setup.exe")
    INSTALLER_EXTENSION = env("INSTALLER_EXTENSION", ".exe")
    INSTALLER_ACCEPT = env(
        "INSTALLER_ACCEPT",
        ".exe,application/vnd.microsoft.portable-executable",
    )
    UPLOAD_MAX_BYTES = env_int("UPLOAD_MAX_BYTES", 500 * 1024 * 1024)
    MAX_CONTENT_LENGTH = UPLOAD_MAX_BYTES

    PRODUCT_NAME = env("PRODUCT_NAME", "Examcenter")
    COMPANY_NAME = env("COMPANY_NAME", "Kings Tech Softwares")
    CONTACT_EMAIL = env("CONTACT_EMAIL", "hello@kingstechsoftware.com")
    SUPPORT_HOURS = env("SUPPORT_HOURS", "Mon-Fri, 8am-5pm WAT")
    SERVICE_NAME = env("SERVICE_NAME", f"{PRODUCT_NAME} API")
    COPYRIGHT_YEAR = env_int("COPYRIGHT_YEAR", datetime.now().year)
    SCHOOLS_FILE = env_path("SCHOOLS_FILE", "") if env("SCHOOLS_FILE") else ""
    SCHOOLS = load_schools(env("SCHOOLS_JSON"), SCHOOLS_FILE)

    SETUP_VIDEO_ID = env("SETUP_VIDEO_ID")
    USAGE_VIDEO_ID = env("USAGE_VIDEO_ID")
    YOUTUBE_EMBED_BASE_URL = env("YOUTUBE_EMBED_BASE_URL", "https://www.youtube.com/embed")

    SERVER_HOST = env("SERVER_HOST", "127.0.0.1")
    PORT = env_int("PORT", 5000)

    TRUST_PROXY_HEADERS = env_bool("TRUST_PROXY_HEADERS", APP_ENV == "production")
    SESSION_COOKIE_HTTPONLY = True
    SESSION_COOKIE_SAMESITE = env("SESSION_COOKIE_SAMESITE", "Lax")
    SESSION_COOKIE_SECURE = env_bool("SESSION_COOKIE_SECURE", APP_ENV == "production")
    SESSION_COOKIE_NAME = env("SESSION_COOKIE_NAME", "__Host-examcenter-session" if SESSION_COOKIE_SECURE else "examcenter-session")
    PERMANENT_SESSION_LIFETIME = timedelta(seconds=env_int("SESSION_LIFETIME_SECONDS", 8 * 60 * 60))
    PREFERRED_URL_SCHEME = "https" if SESSION_COOKIE_SECURE else "http"
    TEMPLATES_AUTO_RELOAD = DEBUG
    ALLOW_ADMIN_REGISTRATION = env_bool("ALLOW_ADMIN_REGISTRATION", APP_ENV != "production")

    HSTS_ENABLED = env_bool("HSTS_ENABLED", APP_ENV == "production")
    HSTS_MAX_AGE = env_int("HSTS_MAX_AGE", 31536000)
    CONTENT_SECURITY_POLICY = env(
        "CONTENT_SECURITY_POLICY",
        "default-src 'self'; "
        "base-uri 'self'; "
        "form-action 'self'; "
        "frame-ancestors 'self'; "
        "object-src 'none'; "
        "img-src 'self' data:; "
        "style-src 'self' 'unsafe-inline'; "
        "script-src 'self' 'unsafe-inline'; "
        "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com",
    )


def validate_config(config):
    """Fail fast when a deployment is configured with unsafe settings."""
    if config.APP_ENV not in VALID_APP_ENVS:
        raise RuntimeError(f"APP_ENV must be one of: {', '.join(sorted(VALID_APP_ENVS))}.")
    if config.SESSION_COOKIE_SAMESITE not in VALID_SAMESITE_VALUES:
        raise RuntimeError("SESSION_COOKIE_SAMESITE must be Lax, Strict, or None.")
    if config.SESSION_COOKIE_SAMESITE == "None" and not config.SESSION_COOKIE_SECURE:
        raise RuntimeError("SESSION_COOKIE_SECURE must be true when SESSION_COOKIE_SAMESITE=None.")
    if config.UPLOAD_MAX_BYTES <= 0:
        raise RuntimeError("UPLOAD_MAX_BYTES must be greater than zero.")
    if Path(config.INSTALLER_FILENAME).name != config.INSTALLER_FILENAME:
        raise RuntimeError("INSTALLER_FILENAME must be a filename, not a path.")
    if not SAFE_FILENAME_PATTERN.fullmatch(config.INSTALLER_FILENAME):
        raise RuntimeError("INSTALLER_FILENAME may contain only letters, numbers, dots, dashes, and underscores.")
    if not config.INSTALLER_EXTENSION.startswith("."):
        raise RuntimeError("INSTALLER_EXTENSION must start with a dot.")

    if config.APP_ENV == "production":
        if config.DEBUG:
            raise RuntimeError("FLASK_DEBUG must be false when APP_ENV=production.")
        if not env("DATABASE_URL"):
            raise RuntimeError("DATABASE_URL must be set when APP_ENV=production.")
        if config.SQLALCHEMY_DATABASE_URI.startswith("sqlite:"):
            raise RuntimeError("SQLite is not supported when APP_ENV=production.")
        if len(config.SECRET_KEY) < 32:
            raise RuntimeError("SECRET_KEY must be at least 32 characters when APP_ENV=production.")
        if not config.SESSION_COOKIE_SECURE:
            raise RuntimeError("SESSION_COOKIE_SECURE must be true when APP_ENV=production.")


validate_config(Config)

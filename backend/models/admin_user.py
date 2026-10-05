"""Database model for administrator accounts used by the private upload area."""

from datetime import datetime, timezone

from werkzeug.security import check_password_hash, generate_password_hash

from extensions import db


class AdminUser(db.Model):
    """Stores a uniquely named admin with a salted password hash, never plaintext credentials."""

    __tablename__ = "admin_users"

    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False, index=True)
    password_hash = db.Column(db.String(255), nullable=False)
    created_at = db.Column(db.DateTime(timezone=True), default=lambda: datetime.now(timezone.utc), nullable=False)

    def set_password(self, password):
        """Hash a new administrator password before persistence."""
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        """Compare a submitted password with the stored password hash."""
        return check_password_hash(self.password_hash, password)

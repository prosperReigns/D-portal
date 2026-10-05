"""Database model for successful installer downloads."""

from datetime import datetime

from extensions import db


class DownloadEvent(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False)

"""Session-protected installer upload page for authenticated administrators."""

import os
import tempfile
from pathlib import Path

from flask import Blueprint, abort, current_app, flash, redirect, render_template, request, url_for
from werkzeug.utils import secure_filename

from routes.admin_guard import admin_required
from security import csrf_token_is_valid

installer_upload_bp = Blueprint("installer_upload", __name__)


@installer_upload_bp.route("/admin/installer", methods=["GET", "POST"])
@admin_required
def installer_upload():
    """Render the private upload form and atomically replace the public installer."""
    if request.method == "POST":
        if not csrf_token_is_valid():
            abort(400, description="Invalid CSRF token.")

        uploaded_file = request.files.get("installer")
        if not uploaded_file or not uploaded_file.filename:
            flash("Choose a Windows .exe installer to upload.", "error")
            return redirect(url_for("installer_upload.installer_upload"))

        filename = secure_filename(uploaded_file.filename)
        configured_name = secure_filename(current_app.config["INSTALLER_FILENAME"])
        installer_extension = current_app.config["INSTALLER_EXTENSION"]
        if not filename.lower().endswith(installer_extension.lower()):
            flash(f"Only {installer_extension} files are accepted.", "error")
            return redirect(url_for("installer_upload.installer_upload"))
        if filename != configured_name:
            flash(f"The file must be named {configured_name}.", "error")
            return redirect(url_for("installer_upload.installer_upload"))

        installer_dir = Path(current_app.config["INSTALLER_PATH"])
        installer_dir.mkdir(parents=True, exist_ok=True)
        destination = installer_dir / configured_name
        temp_path = None
        try:
            with tempfile.NamedTemporaryFile(
                dir=installer_dir, prefix=".installer-", suffix=".tmp", delete=False
            ) as temporary_file:
                temp_path = Path(temporary_file.name)
                uploaded_file.save(temporary_file)
            if temp_path.stat().st_size > current_app.config["UPLOAD_MAX_BYTES"]:
                flash("The installer is larger than the configured upload limit.", "error")
            else:
                os.replace(temp_path, destination)
                temp_path = None
                flash("The installer was uploaded successfully.", "success")
        except OSError:
            flash("The installer could not be saved. Check the upload directory.", "error")
        finally:
            if temp_path and temp_path.exists():
                temp_path.unlink(missing_ok=True)

        return redirect(url_for("installer_upload.installer_upload"))

    installer_name = current_app.config["INSTALLER_FILENAME"]
    installer_path = Path(current_app.config["INSTALLER_PATH"]) / installer_name
    return render_template(
        "installer_upload.html",
        installer_name=installer_name,
        installer_exists=installer_path.is_file(),
        installer_size=installer_path.stat().st_size if installer_path.is_file() else None,
        upload_max_bytes=current_app.config["UPLOAD_MAX_BYTES"],
        installer_extension=current_app.config["INSTALLER_EXTENSION"],
        installer_accept=current_app.config["INSTALLER_ACCEPT"],
    )

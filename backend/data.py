"""Runtime-loadable public content used by the website."""

import json
from pathlib import Path


def load_schools(schools_json="", schools_file=""):
    """Load school directory content from JSON text or a JSON file."""
    raw_content = ""
    if schools_file:
        raw_content = Path(schools_file).read_text(encoding="utf-8").strip()
    elif schools_json:
        raw_content = schools_json.strip()

    if not raw_content:
        return []

    try:
        schools = json.loads(raw_content)
    except json.JSONDecodeError as exc:
        raise RuntimeError("SCHOOLS_JSON or SCHOOLS_FILE must contain valid JSON.") from exc

    if not isinstance(schools, list):
        raise RuntimeError("School data must be a JSON list.")

    normalized = []
    for school in schools:
        if not isinstance(school, dict):
            raise RuntimeError("Each school entry must be a JSON object.")
        name = str(school.get("name", "")).strip()
        location = str(school.get("location", "")).strip()
        students = str(school.get("students", "")).strip()
        if not name or not location:
            raise RuntimeError("Each school entry needs at least name and location.")
        normalized.append({"name": name, "location": location, "students": students})

    return normalized

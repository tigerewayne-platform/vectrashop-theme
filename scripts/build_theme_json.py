"""Build theme.json from the Vectra brand tokens.

The brand repo (vectrabrandOS) owns colours, fonts, spacing and radius. This theme never sets them by hand.

Usage:
  python3 scripts/build_theme_json.py                     # uses scripts/brand-tokens.json
  python3 scripts/build_theme_json.py ../vectrabrandOS/brand/tokens.json   # refresh from the brand repo

The tokens file is copied into scripts/brand-tokens.json so the build is reproducible from this repo alone.
"""
import json
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LOCAL = ROOT / "scripts" / "brand-tokens.json"

if len(sys.argv) > 1:
    shutil.copyfile(sys.argv[1], LOCAL)

tokens = json.loads(LOCAL.read_text())
base = json.loads((ROOT / "scripts" / "theme.base.json").read_text())
light = tokens["color"]["themes"][0]["id"]


def light_value(v):
    return v.get(light) if isinstance(v, dict) else v


palette = [
    {"slug": t["name"], "name": t["name"].replace("-", " ").title(), "color": light_value(t["value"])}
    for t in tokens["color"]["tokens"]
]

# One variable file serves both roles; the face is declared once, on display.
FONT_FILES = {
    "display": ("Geist", "Geist-Variable.woff2", "100 900"),
}
families = []
for slug, stack in tokens["type"]["families"].items():
    fam = {"slug": slug, "name": slug.title(), "fontFamily": stack}
    if slug in FONT_FILES:
        name, file, weight = FONT_FILES[slug]
        fam["fontFace"] = [{
            "fontFamily": name, "fontStyle": "normal", "fontWeight": weight,
            "fontDisplay": "swap", "src": [f"file:./assets/fonts/{file}"],
        }]
    families.append(fam)

spacing = [{"slug": t["name"], "name": t["name"], "size": t["value"]} for t in tokens["spacing"]["tokens"]]
radius = {t["name"].replace("radius-", ""): t["value"] for t in tokens["radius"]["tokens"]}

theme = base
theme["settings"]["color"]["palette"] = palette
theme["settings"]["typography"]["fontFamilies"] = families
theme["settings"]["spacing"]["spacingSizes"] = spacing
theme["settings"]["custom"] = {"radius": radius, "brandVersion": tokens.get("version", 1)}

(ROOT / "theme.json").write_text(json.dumps(theme, indent=2) + "\n")
print(f"theme.json built: {len(palette)} colours, {len(families)} font families, {len(spacing)} spacing steps")

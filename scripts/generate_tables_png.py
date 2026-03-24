from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(r"C:\xampp\htdocs\alumni_portal")
TABLES_FILE = ROOT / "tmp_tables.txt"
OUT_DIR = ROOT / "storage" / "tables"
OUT_PNG = OUT_DIR / "alumni_portal_tables.png"


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)

    lines = [
        ln.strip()
        for ln in TABLES_FILE.read_text(encoding="utf-8", errors="ignore").splitlines()
        if ln.strip()
    ]
    if not lines:
        lines = ["(no tables found)"]

    header = f"Alumni Portal PostgreSQL Tables ({len(lines)})"
    rows = [f"{i + 1:02d}. {name}" for i, name in enumerate(lines)]

    font = ImageFont.load_default()
    line_height = 22
    pad = 24

    max_chars = max(len(header), *(len(r) for r in rows))
    width = max(760, min(1900, pad * 2 + max_chars * 8))
    height = pad * 2 + 64 + len(rows) * line_height

    image = Image.new("RGB", (width, height), "#f8fafc")
    draw = ImageDraw.Draw(image)

    draw.rectangle([0, 0, width, 64], fill="#1e3a8a")
    draw.text((pad, 22), header, fill="white", font=font)

    y = 80
    for row in rows:
        draw.text((pad, y), row, fill="#111827", font=font)
        y += line_height

    image.save(OUT_PNG)
    print(str(OUT_PNG))


if __name__ == "__main__":
    main()

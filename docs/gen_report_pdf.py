from pathlib import Path

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas

src = Path("docs/ALUMNI_PORTAL_FULL_TECHNICAL_WRITEUP.md")
out = Path("docs/ALUMNI_PORTAL_FULL_TECHNICAL_WRITEUP.pdf")

text = src.read_text(encoding="utf-8", errors="ignore").replace("\t", "    ")

try:
    pdfmetrics.registerFont(TTFont("DejaVu", r"C:/Windows/Fonts/DejaVuSans.ttf"))
    body_font = "DejaVu"
except Exception:
    body_font = "Helvetica"

c = canvas.Canvas(str(out), pagesize=A4)
width, height = A4
left = 1.7 * cm
right = width - 1.7 * cm
y = height - 1.8 * cm
line_h = 13


def new_page() -> None:
    global y
    c.showPage()
    y = height - 1.8 * cm


def draw_wrapped(line: str, bold: bool = False) -> None:
    global y
    if y < 2.0 * cm:
        new_page()

    font_name = "Helvetica-Bold" if bold else body_font
    font_size = 11 if bold else 10
    c.setFont(font_name, font_size)

    max_w = right - left
    words = line.split(" ")
    current = ""

    for word in words:
        candidate = (current + " " + word).strip()
        if c.stringWidth(candidate, font_name, font_size) <= max_w:
            current = candidate
            continue

        if current:
            c.drawString(left, y, current)
            y -= line_h
            if y < 2.0 * cm:
                new_page()
        current = word

    if current:
        c.drawString(left, y, current)
        y -= line_h


for raw in text.splitlines():
    line = raw.rstrip()
    if not line:
        y -= 6
        continue
    if line.startswith("# "):
        y -= 4
        draw_wrapped(line[2:], bold=True)
        y -= 2
    elif line.startswith("## "):
        y -= 3
        draw_wrapped(line[3:], bold=True)
    elif line.startswith("### "):
        draw_wrapped(line[4:], bold=True)
    elif line.startswith("- "):
        draw_wrapped("* " + line[2:])
    else:
        draw_wrapped(line)

c.save()
print(f"Wrote {out}")

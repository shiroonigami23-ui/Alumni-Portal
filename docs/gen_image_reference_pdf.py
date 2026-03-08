from pathlib import Path

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas

OUT = Path("docs/IMAGE_REFERENCE_BASE.pdf")

FIGURES = [
    (
        "F01",
        "assets/images/featured_alumni/alumni_visits_shalini1.jpeg",
        "Alumni Interaction Visit: Ms. Shalini Dubey with RJIT faculty and students",
    ),
    (
        "F02",
        "assets/images/featured_alumni/alumni_visits_saurabh1.jpeg",
        "Industry-Alumni Connect Session: Mr. Saurabh Sikarwar interaction at campus",
    ),
    (
        "F03",
        "assets/images/featured_alumni/alumni_visits_sandhya1.jpeg",
        "Digital Mentorship Event Announcement: Ms. Sandhya Singh (AU: 2003-07)",
    ),
    (
        "F04",
        "assets/images/rjit_updates/anjuman_1.jpeg",
        "Campus Alumni Meet visual from official RJIT updates",
    ),
    (
        "F05",
        "assets/images/rjit_updates/anjuman_2.jpeg",
        "Group photograph from RJIT Alumni Meet event (Anjuman 2025)",
    ),
    (
        "F06",
        "assets/images/featured_alumni/alumni_visits_ayush1.jpeg",
        "Career Guidance Interaction: Mr. Ayush Chandel (Automobile alumnus)",
    ),
]


def wrap_line(c: canvas.Canvas, text: str, x: float, y: float, max_w: float, font: str, size: int) -> float:
    c.setFont(font, size)
    words = text.split()
    line = ""
    step = 12
    for w in words:
        cand = (line + " " + w).strip()
        if c.stringWidth(cand, font, size) <= max_w:
            line = cand
        else:
            c.drawString(x, y, line)
            y -= step
            line = w
    if line:
        c.drawString(x, y, line)
        y -= step
    return y


def main() -> None:
    try:
        pdfmetrics.registerFont(TTFont("DejaVu", r"C:/Windows/Fonts/DejaVuSans.ttf"))
        body_font = "DejaVu"
    except Exception:
        body_font = "Helvetica"

    c = canvas.Canvas(str(OUT), pagesize=A4)
    width, height = A4
    margin = 1.6 * cm
    thumb_w = 8.2 * cm
    thumb_h = 5.2 * cm
    text_x = margin + thumb_w + 0.8 * cm
    text_w = width - margin - text_x

    c.setTitle("Image Reference Base")
    c.setFont("Helvetica-Bold", 16)
    c.drawString(margin, height - margin, "Image Reference Base")
    c.setFont(body_font, 10)
    c.drawString(margin, height - margin - 14, "CAMS College Alumni Management System")
    c.drawString(margin, height - margin - 28, "Use this PDF with the Claude prompt for figure-level clarity.")

    y = height - margin - 48
    for fig_id, rel_path, caption in FIGURES:
        img_path = Path(rel_path)
        if y < margin + thumb_h + 40:
            c.showPage()
            y = height - margin

        c.setStrokeColorRGB(0.8, 0.8, 0.8)
        c.rect(margin - 2, y - thumb_h - 2, thumb_w + 4, thumb_h + 4, stroke=1, fill=0)

        if img_path.exists():
            image = ImageReader(str(img_path))
            iw, ih = image.getSize()
            scale = min(thumb_w / iw, thumb_h / ih)
            draw_w = iw * scale
            draw_h = ih * scale
            dx = margin + (thumb_w - draw_w) / 2
            dy = y - draw_h - (thumb_h - draw_h) / 2
            c.drawImage(image, dx, dy, width=draw_w, height=draw_h, preserveAspectRatio=True, mask="auto")
        else:
            c.setFont("Helvetica", 9)
            c.drawString(margin + 8, y - 20, "Image not found:")
            c.drawString(margin + 8, y - 34, rel_path)

        c.setFont("Helvetica-Bold", 11)
        c.drawString(text_x, y - 2, f"{fig_id}")
        c.setFont(body_font, 9)
        y_text = wrap_line(c, f"File: {rel_path}", text_x, y - 18, text_w, body_font, 9)
        y_text = wrap_line(c, f"Caption: {caption}", text_x, y_text - 2, text_w, body_font, 9)
        y_text = wrap_line(c, "Suggested use: Report + PPT module evidence", text_x, y_text - 2, text_w, body_font, 9)

        y -= max(thumb_h + 18, (y - y_text) + 8)

    c.save()
    print(f"Wrote {OUT}")


if __name__ == "__main__":
    main()


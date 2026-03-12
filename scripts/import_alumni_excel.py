import argparse
import csv
import re
from pathlib import Path

import pandas as pd
import psycopg2
from psycopg2.extras import execute_values


EMAIL_RE = re.compile(r"^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$")


def norm_str(v):
    if pd.isna(v):
        return ""
    s = str(v).strip()
    # DB is WIN1252 encoded locally; drop unsupported Unicode chars safely.
    return s.encode("cp1252", errors="ignore").decode("cp1252")


def norm_email(v):
    e = norm_str(v).lower()
    return e


def norm_year(v):
    s = norm_str(v)
    if not s:
        return ""
    try:
        y = int(float(s))
        if 1900 <= y <= 2100:
            return str(y)
    except Exception:
        pass
    return ""


def norm_phone(v):
    s = norm_str(v)
    if not s:
        return ""
    s = re.sub(r"[^0-9+]", "", s)
    return s[:20]


def norm_linkedin(v):
    s = norm_str(v)
    if not s:
        return ""
    low = s.lower()
    if "not linkedin" in low or "no linkedin" in low:
        return ""
    if "linkedin.com" in low and not low.startswith("http"):
        return "https://" + s
    if low.startswith("http://") or low.startswith("https://"):
        return s
    return ""


def department_from_branch(branch):
    b = (branch or "").upper().strip()
    m = {
        "CSE": "Department of Computer Science & Engineering",
        "IT": "Department of Information Technology",
        "ECE": "Department of Electronics & Communication Engineering",
        "EE": "Department of Electrical Engineering",
        "ME": "Department of Mechanical Engineering",
        "AU": "Department of Automobile Engineering",
        "MCA": "Department of Computer Applications",
    }
    return m.get(b, "Alumni")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--excel", required=True)
    parser.add_argument("--project-root", default=".")
    parser.add_argument("--db-host", default="127.0.0.1")
    parser.add_argument("--db-port", default="5432")
    parser.add_argument("--db-name", default="alumni_portal")
    parser.add_argument("--db-user", default="postgres")
    parser.add_argument("--db-password", default="")
    parser.add_argument("--banner-path", default="storage/covers/alumni_banner_default.jpg")
    parser.add_argument("--sheets", default="", help="Comma-separated sheet names to import, e.g. ME,CSE. Empty = all sheets.")
    args = parser.parse_args()

    project_root = Path(args.project_root).resolve()
    excel_path = Path(args.excel).resolve()
    if not excel_path.exists():
        raise FileNotFoundError(f"Excel not found: {excel_path}")

    xl = pd.ExcelFile(excel_path)
    records = {}

    selected_sheets = [s.strip() for s in args.sheets.split(",") if s.strip()]
    if selected_sheets:
        valid = set(xl.sheet_names)
        selected_sheets = [s for s in selected_sheets if s in valid]
    else:
        selected_sheets = list(xl.sheet_names)

    for sheet in selected_sheets:
        df = pd.read_excel(excel_path, sheet_name=sheet)
        for _, row in df.iterrows():
            email = norm_email(row.get("Email :"))
            if not email or not EMAIL_RE.match(email):
                continue
            t = norm_str(row.get("\nTime"))
            rec = {
                "email": email,
                "full_name": norm_str(row.get("Name:")),
                "branch": norm_str(row.get("BRANCH")).upper(),
                "graduation_year": norm_year(row.get("Passout Year")),
                "current_company": norm_str(row.get("Current Organisation:")),
                "job_role": norm_str(row.get("Current Designation:")),
                "location_city": norm_str(row.get("Current Job Location:")),
                "contact_number": norm_phone(row.get("Mobile No. (Whatsapp No.)")),
                "linkedin_url": norm_linkedin(row.get("LinkedIn ( Profile Link )")),
                "course": "B.Tech",
                "department": department_from_branch(norm_str(row.get("BRANCH"))),
                "event_time": t,
            }
            prev = records.get(email)
            if prev is None or rec["event_time"] > prev["event_time"]:
                records[email] = rec

    import_dir = project_root / "storage" / "imports"
    import_dir.mkdir(parents=True, exist_ok=True)
    csv_path = import_dir / "alumni_import_cleaned.csv"

    fieldnames = [
        "email",
        "full_name",
        "branch",
        "graduation_year",
        "current_company",
        "job_role",
        "location_city",
        "contact_number",
        "linkedin_url",
        "course",
        "department",
    ]
    with csv_path.open("w", newline="", encoding="utf-8") as f:
        w = csv.DictWriter(f, fieldnames=fieldnames)
        w.writeheader()
        for email in sorted(records.keys()):
            row = records[email]
            w.writerow({k: row.get(k, "") for k in fieldnames})

    # bcrypt hash for "alumni123" generated via PHP password_hash with cost 12.
    pwd_hash = "$2y$12$hKTvYCeGhdjr1klhSERHi.QPRf24K55HZoCF/RUznyHO12d3l9sWG"

    rows = []
    for email in sorted(records.keys()):
        r = records[email]
        y = int(r["graduation_year"]) if r["graduation_year"] else None
        rows.append(
            (
                r["email"],
                r["full_name"],
                r["branch"],
                y,
                r["current_company"],
                r["job_role"],
                r["location_city"],
                r["contact_number"],
                r["linkedin_url"],
                r["course"],
                r["department"],
            )
        )

    conn = psycopg2.connect(
        host=args.db_host,
        port=args.db_port,
        dbname=args.db_name,
        user=args.db_user,
        password=args.db_password,
    )
    conn.set_client_encoding("UTF8")
    conn.autocommit = False
    cur = conn.cursor()
    try:
        cur.execute(
            """
            CREATE TEMP TABLE tmp_alumni_import (
                email TEXT,
                full_name TEXT,
                branch TEXT,
                graduation_year INTEGER,
                current_company TEXT,
                job_role TEXT,
                location_city TEXT,
                contact_number TEXT,
                linkedin_url TEXT,
                course TEXT,
                department TEXT
            ) ON COMMIT DROP;
            """
        )
        execute_values(
            cur,
            """
            INSERT INTO tmp_alumni_import (
                email, full_name, branch, graduation_year, current_company, job_role,
                location_city, contact_number, linkedin_url, course, department
            ) VALUES %s
            """,
            rows,
            page_size=500,
        )
        cur.execute(
            """
            INSERT INTO users (email, password_hash, role, status, email_verified, can_post, token_used)
            SELECT i.email, %s, 'alumni', 'active', true, true, true
            FROM tmp_alumni_import i
            LEFT JOIN users u ON lower(u.email) = lower(i.email)
            WHERE u.user_id IS NULL;
            """,
            (pwd_hash,),
        )
        inserted_users = cur.rowcount
        cur.execute(
            """
            UPDATE users u
            SET password_hash = %s,
                status = 'active',
                email_verified = true,
                can_post = true,
                token_used = true,
                updated_at = CURRENT_TIMESTAMP
            FROM tmp_alumni_import i
            WHERE lower(u.email) = lower(i.email)
              AND u.role = 'alumni';
            """,
            (pwd_hash,),
        )
        updated_users = cur.rowcount
        cur.execute(
            """
            INSERT INTO profiles (
                user_id, full_name, graduation_year, course, branch, current_company, job_role,
                department, location_city, contact_number, linkedin_url, is_private, show_email,
                show_contact, cover_photo_url
            )
            SELECT
                u.user_id,
                COALESCE(NULLIF(i.full_name, ''), split_part(i.email, '@', 1)),
                i.graduation_year,
                NULLIF(i.course, ''),
                NULLIF(i.branch, ''),
                NULLIF(i.current_company, ''),
                NULLIF(i.job_role, ''),
                NULLIF(i.department, ''),
                NULLIF(i.location_city, ''),
                NULLIF(i.contact_number, ''),
                NULLIF(i.linkedin_url, ''),
                false,
                true,
                true,
                %s
            FROM tmp_alumni_import i
            JOIN users u ON lower(u.email) = lower(i.email)
            WHERE u.role = 'alumni'
            ON CONFLICT (user_id) DO UPDATE
            SET full_name = EXCLUDED.full_name,
                graduation_year = EXCLUDED.graduation_year,
                course = COALESCE(EXCLUDED.course, profiles.course),
                branch = COALESCE(EXCLUDED.branch, profiles.branch),
                current_company = COALESCE(EXCLUDED.current_company, profiles.current_company),
                job_role = COALESCE(EXCLUDED.job_role, profiles.job_role),
                department = COALESCE(EXCLUDED.department, profiles.department),
                location_city = COALESCE(EXCLUDED.location_city, profiles.location_city),
                contact_number = COALESCE(EXCLUDED.contact_number, profiles.contact_number),
                linkedin_url = COALESCE(EXCLUDED.linkedin_url, profiles.linkedin_url),
                is_private = false,
                show_email = true,
                show_contact = true,
                cover_photo_url = COALESCE(profiles.cover_photo_url, EXCLUDED.cover_photo_url),
                updated_at = CURRENT_TIMESTAMP;
            """,
            (args.banner_path,),
        )
        profile_upserts = cur.rowcount
        cur.execute("SELECT count(*) FROM users WHERE role='alumni';")
        alumni_total = cur.fetchone()[0]
        cur.execute("SELECT count(*) FROM users WHERE role='alumni' AND status='active';")
        alumni_active = cur.fetchone()[0]
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        cur.close()
        conn.close()

    print("IMPORT_OK")
    print(f"sheets={','.join(selected_sheets)}")
    print(f"rows_cleaned={len(records)}")
    print(f"csv={csv_path}")
    print(f"inserted_users={inserted_users}")
    print(f"updated_users={updated_users}")
    print(f"profile_upserts={profile_upserts}")
    print(f"alumni_users_total={alumni_total}")
    print(f"alumni_active_total={alumni_active}")


if __name__ == "__main__":
    main()

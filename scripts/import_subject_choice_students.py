import argparse
import re
from pathlib import Path

import pandas as pd
import psycopg2
from psycopg2.extras import execute_values

# bcrypt for plain password "student123" (same hash used in deployment/sql seed)
STUDENT123_BCRYPT = "$2y$12$qKyHMoSUog6TSSu7bi9hievpQGmeB8OMaKdT2nNu5HAHNz3IA4b/y"

BRANCH_MAP = {
    "CS": ("CSE", "Department of Computer Science & Engineering"),
    "IT": ("IT", "Department of Information Technology"),
    "EC": ("ECE", "Department of Electronics & Communication Engineering"),
    "EE": ("EE", "Department of Electrical Engineering"),
    "ME": ("ME", "Department of Mechanical Engineering"),
    "AU": ("AU", "Department of Automobile Engineering"),
    "CE": ("CE", "Department of Civil Engineering"),
}


def clean(v: object) -> str:
    if pd.isna(v):
        return ""
    return str(v).strip()


def clean_roll(v: object) -> str:
    s = clean(v).upper().replace(" ", "")
    return re.sub(r"[^A-Z0-9]", "", s)


def derive_roll_metadata(roll: str):
    m = re.match(r"^(\d{4})([A-Z]{2,4})(\d{2})(\d+)$", roll)
    if not m:
        return "CSE", "Department of Computer Science & Engineering", None
    code = m.group(2)
    join_year = 2000 + int(m.group(3))
    grad_year = join_year + 4
    branch, department = BRANCH_MAP.get(code, (code, "RJIT"))
    return branch, department, grad_year


def parse_args():
    p = argparse.ArgumentParser(description="Pre-register students from Subject Choice Excel")
    p.add_argument("--excel", required=True, help="Path to Excel file")
    p.add_argument("--db-host", default="127.0.0.1")
    p.add_argument("--db-port", default="5432")
    p.add_argument("--db-name", default="alumni_portal")
    p.add_argument("--db-user", default="postgres")
    p.add_argument("--db-password", default="")
    return p.parse_args()


def main():
    args = parse_args()
    excel_path = Path(args.excel).resolve()
    if not excel_path.exists():
        raise FileNotFoundError(f"Excel not found: {excel_path}")

    xl = pd.ExcelFile(excel_path)
    by_email = {}

    for sheet in xl.sheet_names:
        df = pd.read_excel(excel_path, sheet_name=sheet, dtype=str)
        for _, row in df.iterrows():
            roll = clean_roll(row.get("Roll No."))
            if not roll:
                continue

            email = f"{roll.lower()}@rjit.ac.in"
            full_name = clean(row.get("Name Of Student")) or f"Student {roll}"
            branch, department, graduation_year = derive_roll_metadata(roll)

            by_email[email] = (
                email,
                STUDENT123_BCRYPT,
                full_name,
                roll,
                "B.Tech",
                branch,
                graduation_year,
                department,
            )

    rows = list(by_email.values())
    if not rows:
        raise RuntimeError("No valid student rows parsed from Excel")

    conn = psycopg2.connect(
        host=args.db_host,
        port=args.db_port,
        dbname=args.db_name,
        user=args.db_user,
        password=args.db_password,
    )
    conn.autocommit = False
    cur = conn.cursor()

    try:
        cur.execute(
            """
            CREATE TEMP TABLE tmp_core_students (
                email TEXT,
                password_hash TEXT,
                full_name TEXT,
                roll_number TEXT,
                course TEXT,
                branch TEXT,
                graduation_year INTEGER,
                department TEXT
            ) ON COMMIT DROP;
            """
        )

        execute_values(
            cur,
            """
            INSERT INTO tmp_core_students
            (email, password_hash, full_name, roll_number, course, branch, graduation_year, department)
            VALUES %s
            """,
            rows,
            page_size=500,
        )

        cur.execute(
            """
            INSERT INTO users (email, password_hash, role, status, email_verified, can_post, token_used)
            SELECT email, password_hash, 'student', 'active', true, true, false
            FROM tmp_core_students
            ON CONFLICT (email) DO UPDATE
            SET password_hash = EXCLUDED.password_hash,
                role = 'student',
                status = 'active',
                email_verified = true,
                can_post = true,
                updated_at = CURRENT_TIMESTAMP;
            """
        )
        users_upserted = cur.rowcount

        cur.execute(
            """
            INSERT INTO profiles (
                user_id, full_name, roll_number, course, branch, graduation_year, department,
                is_private, show_email, show_contact
            )
            SELECT
                u.user_id,
                t.full_name,
                t.roll_number,
                t.course,
                t.branch,
                t.graduation_year,
                t.department,
                false,
                true,
                false
            FROM tmp_core_students t
            JOIN users u ON u.email = t.email
            ON CONFLICT (user_id) DO UPDATE
            SET full_name = EXCLUDED.full_name,
                roll_number = EXCLUDED.roll_number,
                course = EXCLUDED.course,
                branch = EXCLUDED.branch,
                graduation_year = EXCLUDED.graduation_year,
                department = EXCLUDED.department,
                current_company = NULL,
                job_role = NULL,
                location_city = NULL,
                contact_number = NULL,
                linkedin_url = NULL,
                skills = NULL,
                is_private = false,
                show_email = true,
                show_contact = false,
                updated_at = CURRENT_TIMESTAMP;
            """
        )
        profiles_upserted = cur.rowcount

        cur.execute("SELECT count(*) FROM tmp_core_students")
        rows_processed = cur.fetchone()[0]

        conn.commit()

        print("PREREG_OK")
        print(f"rows_processed={rows_processed}")
        print(f"users_upserted={users_upserted}")
        print(f"profiles_upserted={profiles_upserted}")
    except Exception:
        conn.rollback()
        raise
    finally:
        cur.close()
        conn.close()


if __name__ == "__main__":
    main()

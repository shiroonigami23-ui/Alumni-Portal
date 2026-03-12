import argparse
import hashlib
import urllib.error
import urllib.request

import psycopg2


def gravatar_url(email: str, size: int = 512) -> str:
    md5 = hashlib.md5(email.strip().lower().encode("utf-8")).hexdigest()
    return f"https://www.gravatar.com/avatar/{md5}?d=404&s={size}"


def has_gravatar(email: str, timeout: float = 1.5) -> str:
    url = gravatar_url(email)
    req = urllib.request.Request(url, method="GET", headers={"User-Agent": "RJIT-Alumni-Portal/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            if resp.status == 200:
                return url
    except urllib.error.HTTPError as e:
        if e.code == 404:
            return ""
    except Exception:
        return ""
    return ""


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--db-host", default="127.0.0.1")
    parser.add_argument("--db-port", default="5432")
    parser.add_argument("--db-name", default="alumni_portal")
    parser.add_argument("--db-user", default="postgres")
    parser.add_argument("--db-password", default="")
    parser.add_argument("--limit", type=int, default=0, help="Optional max number of alumni emails to scan (0 = all)")
    args = parser.parse_args()

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
        base_sql = """
            SELECT u.user_id, lower(u.email) AS email
            FROM users u
            JOIN profiles p ON p.user_id = u.user_id
            WHERE u.role = 'alumni'
              AND coalesce(p.profile_picture_url, '') = ''
              AND coalesce(u.email, '') <> ''
            ORDER BY u.user_id
        """
        if args.limit and args.limit > 0:
            cur.execute(base_sql + " LIMIT %s", (args.limit,))
        else:
            cur.execute(base_sql)
        rows = cur.fetchall()

        checked = 0
        updated = 0
        for user_id, email in rows:
            checked += 1
            pic = has_gravatar(email)
            if not pic:
                continue
            cur.execute(
                """
                UPDATE profiles
                SET profile_picture_url = %s,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = %s
                  AND coalesce(profile_picture_url, '') = ''
                """,
                (pic, user_id),
            )
            if cur.rowcount > 0:
                updated += 1

        conn.commit()
        print("ENRICH_OK")
        print(f"checked={checked}")
        print(f"updated={updated}")
    except Exception:
        conn.rollback()
        raise
    finally:
        cur.close()
        conn.close()


if __name__ == "__main__":
    main()

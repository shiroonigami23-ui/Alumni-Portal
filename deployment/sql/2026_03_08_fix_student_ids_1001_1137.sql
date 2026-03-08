BEGIN;

/*
Fix student IDs/emails from old style:
  0902cs230001@rjit.ac.in ... 0902cs230137@rjit.ac.in
to required style:
  0902cs231001@rjit.ac.in ... 0902cs231137@rjit.ac.in

Also updates profiles.roll_number and profiles.full_name ("Student 0902CS23xxxx")
for the same student users.
*/

WITH old_rows AS (
    SELECT
        u.user_id,
        u.email AS old_email,
        lower('0902cs23' || lpad(((substring(u.email from '0902cs23([0-9]{4})')::int + 1000))::text, 4, '0') || '@rjit.ac.in') AS new_email,
        '0902CS23' || lpad(((substring(u.email from '0902cs23([0-9]{4})')::int + 1000))::text, 4, '0') AS new_roll
    FROM users u
    WHERE u.role = 'student'
      AND u.email ~ '^0902cs23(0[0-1][0-9]{2}|0137)@rjit\.ac\.in$'
),
safe_map AS (
    SELECT o.*
    FROM old_rows o
    LEFT JOIN users clash
      ON clash.email = o.new_email
     AND clash.user_id <> o.user_id
    WHERE clash.user_id IS NULL
),
upd_users AS (
    UPDATE users u
    SET email = s.new_email,
        updated_at = CURRENT_TIMESTAMP
    FROM safe_map s
    WHERE u.user_id = s.user_id
    RETURNING u.user_id
)
UPDATE profiles p
SET roll_number = s.new_roll,
    full_name = CASE
        WHEN p.full_name ~* '^Student 0902CS23[0-9]{4}$' THEN 'Student ' || s.new_roll
        ELSE p.full_name
    END,
    updated_at = CURRENT_TIMESTAMP
FROM safe_map s
WHERE p.user_id = s.user_id;

COMMIT;

-- Validation
SELECT
  COUNT(*) FILTER (WHERE role='student')                                                  AS total_students,
  COUNT(*) FILTER (WHERE role='student' AND email ~ '^0902cs23(0[0-1][0-9]{2}|0137)@rjit\.ac\.in$') AS still_old_style,
  COUNT(*) FILTER (WHERE role='student' AND email ~ '^0902cs23(10[0-9]{2}|11[0-3][0-9]|1137)@rjit\.ac\.in$') AS new_style_range
FROM users;


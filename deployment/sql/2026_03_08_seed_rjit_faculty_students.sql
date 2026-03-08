BEGIN;

-- Default bcrypt hashes:
-- faculty123 => $2y$12$c5tdu/O7pGCzVX/T/NIVgOWBJNWm9ABKtgCheEG0DXawLA8K85yJ2
-- student123 => $2y$12$qKyHMoSUog6TSSu7bi9hievpQGmeB8OMaKdT2nNu5HAHNz3IA4b/y

WITH faculty_seed(full_name, designation, department, email, contact_number) AS (
    VALUES
    ('Dr. Prashant Kumar Jain','Professor','Department of Mechanical Engineering','pkjain@rjit.ac.in','9340841848'),
    ('Prof. Abhishek Chakraborty','Assistant Professor','Department of Automobile Engineering','achakraborty@rjit.ac.in','9407211667'),
    ('Dr. Mainak Pal','Assistant Professor','Department of Automobile Engineering','mainakpal@rjit.ac.in','9988607469'),
    ('Prof. Anand Baghel','Assistant Professor','Department of Automobile Engineering','anandbaghel@rjit.ac.in','9039583563'),
    ('Dr. Gaurav Saxena','Assistant Professor','Department of Automobile Engineering','gauravsaxena@rjit.ac.in','9826824648'),
    ('Prof. Trapti Sharma','Assistant Professor','Department of Automobile Engineering','traptisharma@rjit.ac.in','9755583200'),
    ('Dr. Jagdish Makhijani','Assistant Professor','Department of Computer Science & Engineering','drjagdish@rjit.ac.in','9425360281'),
    ('Prof Yograj Sharma','Assistant Professor','Department of Computer Science & Engineering','yograjsharma@rjit.ac.in','9609375551'),
    ('Prof. Vivek Gupta','Assistant Professor','Department of Computer Science & Engineering','vivekguptachp@gmail.com','9425813639'),
    ('Prof. Aishwarya Sharma','Assistant Professor','Department of Computer Science & Engineering','aishwarya@rjit.ac.in','7067480927'),
    ('Prof. Samiksha Khule','Assistant Professor','Department of Computer Science & Engineering','samiksha@rjit.ac.in','8989020090'),
    ('Muskan Sihare','Assistant Professor','Department of Computer Science & Engineering','muskan@rjit.ac.in','8871820963'),
    ('Prof. Madhukar Dubey','Assistant Professor','Department of Computer Science & Engineering','madhukardubey@rjit.ac.in','8120444411'),
    ('Dr. Neeraj Shrivastava','Associate Professor','Department of Electronics & Communications','dr.neerajshrivastava@rjit.ac.in','9425754197'),
    ('Dr. Anjana Goen','Associate Professor','Department of Electronics & Communications','dr.anjanagoen@rjit.ac.in','9425755052'),
    ('Dr. Chetan Pathak','Assistant Professor','Department of Electronics & Communications','cp_rjit@yahoo.co.in','9425483027'),
    ('Dr. Mahendra Kumar Pandey','Assistant Professor','Department of Electronics & Communications','mahendrapandey@rjit.ac.in','9425122359'),
    ('Prof. Sandeep Agrawal','Assistant Professor','Department of Electronics & Communications','sandeepagrawal@rjit.ac.in','9406586068'),
    ('Dr. Devendra Kumar Raghav','Assistant Professor','Department of Electronics & Communications','devendrakumar@rjit.ac.in','9926882527'),
    ('Prof. Gaurav Bhardwaj','Assistant Professor','Department of Electronics & Communications','gauravbhardwaj@rjit.ac.in','9977469922'),
    ('Prof. Dhananjay Bhadoriya','Assistant Professor','Department of Electrical Engineering','dhananjay.bhadoria@gmail.com','9425113244'),
    ('Krishna Kant Gautam','Assistant Professor','Department of Electrical Engineering','krishankantgautam@rjit.ac.in','9479547630'),
    ('Prof. Ashish Gupta','Assistant Professor','Department of Electrical Engineering','ashishgupta@rjit.ac.in','8962384106'),
    ('Prof. Arun Kumar Singh Tomar','Assistant Professor','Department of Electrical Engineering','aruntomar@rjit.ac.in','9399354050'),
    ('Prof. Anand Jha','Assistant Professor','Department of Information Technology','anandjha@rjit.org','9893892193'),
    ('Prof. Sanjay Patsariya','Assistant Professor','Department of Information Technology','sanjaypatsariya@gmail.com','9827549919'),
    ('Prof. Janki Sharan Pahareeya','Assistant Professor','Department of Information Technology','jspahareeya@rjit.ac.in','9753430660'),
    ('Prof. Aradhana Saxena','Assistant Professor','Department of Information Technology','aradhana@rjit.ac.in',NULL),
    ('Dr. Arvind Sharma','Assistant Professor','Department of Information Technology','arvinddevansh@rediffmail.com','9827334389'),
    ('Prof. Rajan Sharma','Assistant Professor','Department of Mechanical Engineering','rajansharma@rjit.ac.in','9303843888'),
    ('Prof. Mugdha Shrivastava','Assistant Professor','Department of Mechanical Engineering','mugdhashrivastava@rjit.ac.in','9977703213'),
    ('Prof. Ajay Bangar','Assistant Professor','Department of Mechanical Engineering','ajaybanger@rjit.ac.in','9926211039'),
    ('Prof. Manish Jain','Assistant Professor','Department of Mechanical Engineering','manishjain@rjit.ac.in','8964863202'),
    ('Dr. Manoj Kumar Niranjan','Assistant Professor','Department of Computer Applications','manoj_niranjan2000@yahoo.co.in','9425122579'),
    ('Prof. Suhel Ahmed Khan','Assistant Professor','Department of Computer Applications','suhel@rjit.ac.in','9425116486'),
    ('Prof. Dharmendra Kumar Tripathi','Assistant Professor','Department of Computer Applications','dharmendratripathi@rjit.ac.in','9425051589'),
    ('Prof. Abhay Tiwari','Assistant Professor','Department of Civil Engineering','abhaytiwari@rjit.ac.in','7859895938'),
    ('Ambika Priyadarshini Mishra','Assistant Professor','Department of Civil Engineering','ambikapmishra@rjit.ac.in','8847851033'),
    ('Dr. Manoj Sharma','Associate Professor','Department of Mathematics','dr.manojsharma@rjit.ac.in','0942548291'),
    ('Dr. Sanjay Kumar Gupta','Associate Professor','Department of Mathematics','drsanjaygupta@rjit.ac.in','9425778442'),
    ('Dr. Hakim Singh Jat','Assistant Professor','Department of Mathematics','dr.hsjat@rjit.ac.in','9425754854'),
    ('Dr. D V S Kushwah','Eminent Faculty','Department of Mathematics','dvskushwah@rjit.ac.in','9826067025'),
    ('Dr. Rachna Gupta','Assistant Professor','Department of Mathematics','dr.rachna@rjit.ac.in','9958711005'),
    ('Dr. Uma Shankar Sharma','Assistant Professor','Department of Physics','dr.u.sharma@rjit.ac.in','9425483084'),
    ('Dr. Rakesh Sohal','Assistant Professor','Department of Physics','dr.rakeshsohal@rjit.ac.in','9406978630'),
    ('Prof. Ayushi Sharma','Assistant Professor','Department of Physics','ayushi@rjit.ac.in','8770539392'),
    ('Dr. Rashmi Shah','Associate Professor','Department of Chemistry','dr.rashmishah14@gmail.com','9425360239'),
    ('Dr. Namita Saxena','Assistant Professor','Department of Chemistry','nam_saxena26@yahoo.com','9425341421'),
    ('Dr. Vishwajeet Singh Yadav','Assistant Professor','Department of Chemistry','dr.vishwajeet@rjit.ac.in','9826488151'),
    ('Lieut. Dr. Yogita Verma','Assistant Professor','Department of Humanities','dr.yogitaverma@rjit.ac.in','9981182661'),
    ('Bhavesh Rohira','Assistant Professor','Department of Humanities','bhavesh@rjit.ac.in','9516964903')
),
faculty_users AS (
    INSERT INTO users (email, password_hash, role, status, email_verified, can_post, token_used)
    SELECT
        lower(email),
        '$2y$12$c5tdu/O7pGCzVX/T/NIVgOWBJNWm9ABKtgCheEG0DXawLA8K85yJ2',
        'faculty',
        'active',
        true,
        true,
        false
    FROM faculty_seed
    ON CONFLICT (email) DO UPDATE
      SET password_hash = EXCLUDED.password_hash,
          role = 'faculty',
          status = 'active',
          email_verified = true,
          can_post = true,
          updated_at = CURRENT_TIMESTAMP
    RETURNING user_id, email
)
INSERT INTO profiles (user_id, full_name, department, designation, contact_number, is_private, show_email, show_contact)
SELECT
    u.user_id,
    f.full_name,
    f.department,
    f.designation,
    f.contact_number,
    false,
    true,
    true
FROM faculty_seed f
JOIN users u ON u.email = lower(f.email)
ON CONFLICT (user_id) DO UPDATE
  SET full_name = EXCLUDED.full_name,
      department = EXCLUDED.department,
      designation = EXCLUDED.designation,
      contact_number = EXCLUDED.contact_number,
      is_private = EXCLUDED.is_private,
      show_email = EXCLUDED.show_email,
      show_contact = EXCLUDED.show_contact,
      updated_at = CURRENT_TIMESTAMP;

WITH student_seed AS (
    SELECT
        gs AS seq,
        ('0902CS23' || lpad(gs::text, 4, '0'))::varchar(50) AS roll_number
    FROM generate_series(1001, 1137) AS gs
),
student_users AS (
    INSERT INTO users (email, password_hash, role, status, email_verified, can_post, token_used)
    SELECT
        lower(roll_number) || '@rjit.ac.in',
        '$2y$12$qKyHMoSUog6TSSu7bi9hievpQGmeB8OMaKdT2nNu5HAHNz3IA4b/y',
        'student',
        'active',
        true,
        true,
        false
    FROM student_seed
    ON CONFLICT (email) DO UPDATE
      SET password_hash = EXCLUDED.password_hash,
          role = 'student',
          status = 'active',
          email_verified = true,
          can_post = true,
          updated_at = CURRENT_TIMESTAMP
    RETURNING user_id, email
)
INSERT INTO profiles (user_id, full_name, roll_number, course, branch, graduation_year, department, is_private, show_email, show_contact)
SELECT
    u.user_id,
    'Student ' || s.roll_number,
    s.roll_number,
    'B.Tech',
    'Computer Science',
    2027,
    'Department of Computer Science & Engineering',
    false,
    true,
    false
FROM student_seed s
JOIN users u ON u.email = lower(s.roll_number) || '@rjit.ac.in'
ON CONFLICT (user_id) DO UPDATE
  SET full_name = EXCLUDED.full_name,
      roll_number = EXCLUDED.roll_number,
      course = EXCLUDED.course,
      branch = EXCLUDED.branch,
      graduation_year = EXCLUDED.graduation_year,
      department = EXCLUDED.department,
      is_private = EXCLUDED.is_private,
      show_email = EXCLUDED.show_email,
      show_contact = EXCLUDED.show_contact,
      updated_at = CURRENT_TIMESTAMP;

COMMIT;

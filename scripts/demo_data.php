<?php
/**
 * Demo dataset for local development, screenshots and tests.
 * Fictional people only; every demo account uses an @demo.rjit.local email and
 * the password  Demo@12345 . Never run on production.
 */
function seed_demo_data(PDO $db): int
{
    $first = ['Aarav', 'Ananya', 'Vivaan', 'Diya', 'Kabir', 'Ishita', 'Reyansh', 'Meera', 'Arjun', 'Sana', 'Vihaan', 'Kavya', 'Rohan', 'Tara',
        'Aditya', 'Nisha', 'Kunal', 'Pooja', 'Yash', 'Riya', 'Siddharth', 'Neha', 'Dev', 'Aisha', 'Harsh', 'Simran', 'Manav', 'Priya', 'Varun', 'Tanvi',
        'Om', 'Shreya', 'Kartik', 'Anjali', 'Nikhil', 'Zoya'];
    $last = ['Sharma', 'Verma', 'Tomar', 'Bhadoria', 'Jain', 'Gupta', 'Kushwah', 'Rajput', 'Saxena', 'Mishra', 'Chauhan', 'Agrawal', 'Singh', 'Yadav', 'Dubey', 'Patel'];
    $cities = ['Bengaluru', 'Bengaluru', 'Bengaluru', 'Pune', 'Pune', 'Hyderabad', 'Hyderabad', 'Gurugram', 'Noida', 'Delhi', 'Mumbai', 'Mumbai', 'Chennai',
        'Indore', 'Bhopal', 'Gwalior', 'Gwalior', 'Kolkata', 'Ahmedabad', 'Jaipur', 'Singapore', 'Dubai', 'London', 'San Francisco', 'Seattle', 'Toronto'];
    $companies = [['TCS', 'Systems Engineer'], ['Infosys', 'Senior Software Engineer'], ['Wipro', 'Project Engineer'], ['Accenture', 'Consultant'],
        ['Microsoft', 'Software Engineer II'], ['Google', 'SWE III'], ['Amazon', 'SDE-2'], ['Flipkart', 'SDE-2'], ['Zomato', 'Product Manager'],
        ['Paytm', 'Backend Engineer'], ['Deloitte', 'Analyst'], ['L&T', 'Site Engineer'], ['Tata Motors', 'Design Engineer'], ['Cognizant', 'Associate'],
        ['HCLTech', 'Lead Engineer'], ['Razorpay', 'Data Engineer'], ['Swiggy', 'SDE-1'], ['ISRO', 'Scientist C'], ['NTPC', 'Assistant Manager'], ['Adobe', 'MTS-2']];
    $branches = ['Computer Science & Engineering', 'Information Technology', 'Electronics & Communication', 'Mechanical Engineering', 'Civil Engineering', 'Electrical Engineering'];
    $hash = password_hash('Demo@12345', PASSWORD_DEFAULT);

    $insUser = $db->prepare("INSERT INTO users (email, password_hash, role, status, last_login, created_at) VALUES (:e, :h, :r, 'active', NOW() - (:ll || ' days')::interval, NOW() - (:ca || ' days')::interval)
                             ON CONFLICT (email) DO UPDATE SET status = 'active' RETURNING user_id");
    $insProfile = $db->prepare("INSERT INTO profiles (user_id, full_name, bio, branch, graduation_year, joining_year, current_company, job_role, location_city, location_country, linkedin_url, skills)
                                VALUES (:u, :n, :b, :br, :gy, :jy, :c, :jr, :city, :country, :li, :sk)
                                ON CONFLICT (user_id) DO UPDATE SET full_name = EXCLUDED.full_name, current_company = EXCLUDED.current_company, location_city = EXCLUDED.location_city");
    $alumni = [];
    $count = 0;
    foreach ($first as $i => $fn) {
        $ln = $last[$i % count($last)];
        $name = "$fn $ln";
        [$co, $role] = $companies[$i % count($companies)];
        $city = $cities[($i * 7) % count($cities)];
        $gy = 2010 + ($i * 3) % 15;
        $insUser->execute(['e' => strtolower("$fn.$ln.$i@demo.rjit.local"), 'h' => $hash, 'r' => 'alumni', 'll' => (string)($i % 9), 'ca' => (string)(3 + ($i * 5) % 80)]);
        $uid = (int)$insUser->fetchColumn();
        $international = in_array($city, ['Singapore', 'Dubai', 'London', 'San Francisco', 'Seattle', 'Toronto'], true);
        $insProfile->execute([
            'u' => $uid, 'n' => $name, 'b' => "$role at $co. Happy to help juniors with interviews and referrals.",
            'br' => $branches[$i % count($branches)], 'gy' => $gy, 'jy' => $gy - 4, 'c' => $co, 'jr' => $role, 'city' => $city,
            'country' => $international ? null : 'India', 'li' => 'https://www.linkedin.com/in/demo-' . $i, 'sk' => 'Problem solving, Communication',
        ]);
        $alumni[] = ['id' => $uid, 'name' => $name, 'company' => $co, 'role' => $role, 'city' => $city];
        $count++;
    }
    foreach (['Ayaan Tomar', 'Kiara Jain', 'Ritvik Sharma', 'Mahi Gupta', 'Parth Rajput', 'Sneha Saxena'] as $i => $name) {
        $insUser->execute(['e' => strtolower(str_replace(' ', '.', $name)) . "@demo.rjit.local", 'h' => $hash, 'r' => 'student', 'll' => '0', 'ca' => (string)(2 + $i)]);
        $uid = (int)$insUser->fetchColumn();
        $insProfile->execute(['u' => $uid, 'n' => $name, 'b' => 'Final-year student at RJIT', 'br' => $branches[$i % 3], 'gy' => 2027, 'jy' => 2023,
            'c' => null, 'jr' => null, 'city' => 'Gwalior', 'country' => 'India', 'li' => null, 'sk' => 'DSA, Web development']);
    }

    // Posts
    $posts = [
        "🎉 Just completed 5 years at %s! Grateful to RJIT for the foundation. Juniors — DM me for interview prep.",
        "Our team at %s is hiring freshers this quarter. I'll be posting a referral offer on the Referral board 🙌",
        "Throwback to the RJIT annual fest — miss those days. Who's coming to the Alumni Meet this year?",
        "Tip for final-years: build one solid project end-to-end and be able to explain every design choice.",
        "Visited campus last week and spoke to 3rd-year CSE students about careers in %s. Brilliant questions!",
    ];
    $insPost = $db->prepare("INSERT INTO posts (user_id, content_file_path, post_type, status, reaction_count, comment_count, view_count, created_at)
                             VALUES (:u, :p, 'text', 'published', :r, 0, :v, NOW() - (:h || ' hours')::interval)");
    foreach (array_slice($alumni, 0, 10) as $i => $a) {
        $text = sprintf($posts[$i % count($posts)], $a['company']);
        $ptr = store_content_payload($db, $a['id'], ['content' => $text, 'attachments' => []], 'post');
        $insPost->execute(['u' => $a['id'], 'p' => $ptr, 'r' => 4 + ($i * 7) % 40, 'v' => 50 + $i * 31, 'h' => (string)(2 + $i * 9)]);
    }

    // Jobs
    $insJob = $db->prepare("INSERT INTO jobs (poster_id, company_name, job_title, location, salary_range, job_type, application_url, created_at)
                            VALUES (:p, :c, :t, :l, :s, :ty, :url, NOW() - (:d || ' days')::interval)");
    foreach ([[0, 'SDE-1 (Freshers)', '8–12 LPA', 'full-time'], [4, 'Software Engineering Intern', '50k/month', 'internship'], [7, 'Data Analyst', '6–9 LPA', 'full-time'], [15, 'Graduate Engineer Trainee', '7 LPA', 'full-time']] as $k => [$ai, $title, $sal, $type]) {
        $a = $alumni[$ai];
        $insJob->execute(['p' => $a['id'], 'c' => $a['company'], 't' => $title, 'l' => $a['city'], 's' => $sal, 'ty' => $type, 'url' => 'https://careers.example.com/' . ($k + 1), 'd' => (string)($k * 3)]);
    }

    // Events
    $adminId = (int)$db->query("SELECT user_id FROM users WHERE role = 'admin' ORDER BY user_id LIMIT 1")->fetchColumn();
    $insEvent = $db->prepare("INSERT INTO events (creator_user_id, title, description, start_datetime, end_datetime, location_type, location_address, visibility, status, rsvp_limit)
                              VALUES (:u, :t, :d, NOW() + (:s || ' days')::interval, NOW() + (:s || ' days')::interval + interval '3 hours', 'physical', :l, 'public', 'approved', 300)");
    foreach ([['Alumni Meet 2026', 'Annual reunion with batch photos, dinner and a panel on careers.', 12, 'RJIT Auditorium, Gwalior'],
              ['Mock Interview Marathon', 'Alumni from product companies run 30-minute mock interviews.', 5, 'Online · Jitsi'],
              ['Startup Founders Panel', 'RJIT alumni founders share their journeys.', 21, 'Seminar Hall 2']] as [$t, $d, $s, $l]) {
        $insEvent->execute(['u' => $adminId ?: $alumni[0]['id'], 't' => $t, 'd' => $d, 's' => (string)$s, 'l' => $l]);
    }

    // Referral offers
    ensure_referral_schema($db);
    $insRef = $db->prepare("INSERT INTO referral_offers (owner_id, company, roles, locations, experience, slots, notes) VALUES (:o, :c, :r, :l, :e, :s, :n)");
    foreach ([[5, 'SDE-1, SDE Intern', 'fresher', 3], [6, 'SDE-2, Support Engineer', '2-5', 2], [4, 'Software Engineer, Data Scientist', 'fresher', 4],
              [16, 'SDE-1', 'fresher', 5], [15, 'Data Engineer Intern', 'intern', 2], [19, 'Member of Technical Staff', '0-2', 2]] as [$ai, $roles, $exp, $slots]) {
        $a = $alumni[$ai];
        $insRef->execute(['o' => $a['id'], 'c' => $a['company'], 'r' => $roles, 'l' => $a['city'], 'e' => $exp, 's' => $slots,
            'n' => 'Share the job link + your strongest project. CGPA 7+ preferred.']);
    }
    return $count;
}

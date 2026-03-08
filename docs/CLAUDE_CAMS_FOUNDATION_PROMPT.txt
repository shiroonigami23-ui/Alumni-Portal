# Claude Prompt: CAMS College Alumni Management System (Foundation + Expansion)

You are preparing a **final, publication-grade technical project document** for our college submission.
Use the factual foundation below and expand it into a polished, in-depth report suitable for faculty review.

## 1) Project Identity
- Project Name: **CAMS College Alumni Management System**
- Working product name in UI: **RJIT Alumni Portal**
- Project type: Full-stack web platform for alumni-student-faculty networking, engagement, and administration.
- Target deployment modes:
  1. Local development (XAMPP + PostgreSQL)
  2. Cloud production (AWS ECS + RDS + S3 + ALB + CloudWatch via Terraform)

## 2) Team and Responsibilities (must be shown clearly)
Include this in a dedicated section and in acknowledgements.

- **Aryan Singh Chandel** - Main Lead; core development and coding; architecture coordination.
- **Kishan Kumar** - Testing, QA workflow, and SRS alignment.
- **Nikhil B.** - Database design, data consistency, migration support.
- **Abhinandan Yadav** - Documentation, reporting, and marketing/presentation coordination.

## 3) Verified Technical Foundation (do not contradict)
Use these as hard facts:

- Backend language: **PHP**
- Frontend: PHP-rendered pages + JavaScript + CSS/Tailwind-style utility classes
- Database: **PostgreSQL**
- Local runtime: **XAMPP Apache + PostgreSQL**
- Cloud readiness: Terraform infrastructure and Docker-based deployment scripts exist
- API folder currently contains ~**100+ endpoints** (latest count observed: 103 files in `api/`)
- Core modules include:
  - Authentication and registration (student/alumni/faculty/admin)
  - Profile management and privacy controls
  - Discovery/directory search with filters
  - Social feed (posts, comments, reactions, moderation)
  - Messaging/inbox
  - Events and RSVP
  - Jobs and applications
  - Mentorship request lifecycle
  - Resources and success stories
  - Notifications
  - Live stream toggle + active streams
  - Admin moderation/management utilities
- Verification evidence available in docs:
  - `docs/feature_matrix_report.json`
  - `docs/verification_latest.json`
  - `verify_feature_matrix.ps1`
  - `verify_local.ps1`
- Deployment docs available:
  - `README.md`
  - `POSTGRES_SETUP.md`
  - `AWS_DEPLOYMENT.md`
  - `QUICKSTART_AWS.md`

## 4) Main User-Facing Pages (mention in module mapping)
Top-level pages include:
- `index.php` (landing)
- `login.php`, `register.php`
- `dashboard.php`
- `feed.php`
- `discovery.php`
- `events.php`
- `jobs.php`
- `mentorship.php`
- `messages.php`
- `profile.php`, `settings.php`
- `help.php`, `policy.php`, `terms.php`, `conduct.php`

## 5) Functional Narrative to Emphasize
Explain clearly how this system solves real college needs:
- Alumni-student connection gap
- Opportunity access gap (jobs, mentorship, networking)
- Institutional relationship continuity
- Verified, role-based, moderated platform over ad-hoc social media groups

## 6) Security and Governance Topics to Cover
Include practical, implementation-oriented coverage:
- Authentication + bearer token patterns
- Role-based authorization (student/alumni/faculty/admin)
- Profile privacy handling
- Content reporting and moderation flow
- Input validation and endpoint protection
- Production secret handling (env vars / AWS secret practices)

## 7) Testing and Quality Section Requirements
Create a strong QA section:
- Explain automated verification via PowerShell scripts
- Mention DB connectivity checks and API runtime sweep
- Mention feature matrix testing covering post/message/event/job/mentorship flows
- Include interpretation of sample results from JSON reports
- Add a concise bug-fix log style subsection

## 8) Deployment and DevOps Section Requirements
Describe both local and AWS lifecycle:
- Local setup (DB env vars, Apache, localhost routing)
- AWS deployment path (Terraform infra, Docker image, ECS service, RDS migration)
- Observability with CloudWatch
- Common failure cases + mitigation

## 9) Data and Schema Discussion
Write a practical data model discussion:
- Core entities (users, profiles, posts, comments, messages, events, jobs, mentorship, reports, notifications)
- Relationship examples and lifecycle examples
- Data consistency and migration notes
- Why PostgreSQL is suitable for this use case

## 10) Screenshots Requirement (must include a few)
Use the following repository images as figures in the report with captions:
- `assets/images/featured_alumni/alumni_visits_shalini1.jpeg`
- `assets/images/featured_alumni/alumni_visits_saurabh1.jpeg`
- `assets/images/featured_alumni/alumni_visits_sandhya1.jpeg`
- `assets/images/rjit_updates/anjuman_1.jpeg`
- `assets/images/rjit_updates/anjuman_2.jpeg`

For each image, provide:
- Figure number
- What module/page it supports
- Why it matters in the narrative

Before writing the report/PPT blueprint, first read:
- `docs/IMAGE_REFERENCE_BASE.md`
- `docs/IMAGE_REFERENCE_BASE.pdf`

Treat these files as the official interpretation map for figure meaning and placement.

## 11) Mandatory Document Structure
Generate the final report in this order:
1. Title Page
2. Certificate-style statement (project ownership)
3. Abstract
4. Problem Statement
5. Objectives
6. Scope and Constraints
7. Stakeholders and User Roles
8. System Architecture (logical + deployment)
9. Module-by-Module Functional Specification
10. Database Design and Data Flow
11. API Layer Design and Endpoint Strategy
12. Security and Access Control
13. Testing Strategy and Validation Evidence
14. Performance, Scalability, and Reliability Notes
15. Deployment Strategy (Local + AWS)
16. UI/UX Overview with Figure Captions
17. Team Contribution Matrix
18. Challenges Faced and Engineering Decisions
19. Future Enhancements Roadmap
20. Conclusion
21. References / Appendix (scripts, docs, files)

## 12) Writing Quality Requirements
- Formal but readable academic tone.
- Not generic. Use concrete implementation terms.
- Include at least 4 tables:
  - Role vs Permissions
  - Module vs Key Features
  - Testing Matrix Summary
  - Team Contribution Matrix
- Include at least 2 architecture diagrams in text form (Mermaid or structured ASCII).
- Add short "Key Takeaways" at the end of each major section.
- Avoid fake metrics. If unknown, mark as "observed" or "to be measured".

## 13) PPTX Requirement (Mandatory)
In addition to the report, create a **beautiful, faculty-ready PPTX plan** (15-20 slides) that can be directly built in PowerPoint.

PPT expectations:
- Total slides: 15 to 20
- Theme: professional academic + modern product showcase
- Style: clean hierarchy, high contrast, readable from classroom distance
- Include strong visual rhythm: title slides, architecture slides, feature slides, testing evidence slides, roadmap slide
- Use minimal text per slide and highly readable structure
- Include where to place screenshots from the provided image paths

Mandatory slide flow:
1. Title Slide (project + team)
2. Problem Statement
3. Objectives
4. Existing System vs Proposed System
5. Stakeholders and User Roles
6. End-to-End System Architecture
7. Tech Stack
8. Core Modules Overview
9. Authentication, Roles, and Security
10. Discovery, Feed, Messaging, Mentorship
11. Events and Jobs Workflows
12. Database Design and Key Entities
13. API Layer and Integration Strategy
14. Testing Strategy + Evidence Snapshot
15. Deployment Model (Local + AWS)
16. UI Highlights with Screenshot Captions
17. Team Contribution Matrix
18. Challenges and Engineering Decisions
19. Future Enhancements
20. Conclusion + Thank You

For every slide, provide:
- Slide title
- 3-6 concise bullets
- Visual recommendation (diagram/table/screenshot/icon layout)

Also include:
- A single \"Presentation Design System\" block specifying:
  - Color palette (hex values)
  - Font pairing
  - Heading/body sizes
  - Icon style guidance
  - Transition/animation restraint rules

## 14) Output Format You Must Return
Return in four blocks:
1. **Final Report (Markdown, long-form, PDF-ready)**
2. **Short Executive Summary (1 page equivalent)**
3. **Viva/Presentation Questions & Answers (20 likely questions with model answers)**
4. **PPTX Blueprint (15-20 slides, slide-by-slide, visual directions only; no speaker notes)**

## 15) Critical Accuracy Rule
Do not invent technologies not present in the foundation.
If uncertain, write assumptions explicitly as: "Assumption:".

Now generate the complete, expanded report.

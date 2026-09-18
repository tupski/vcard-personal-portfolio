# PLAN.md

Project:
Artupski Portfolio CMS

Base:
vCard Personal Portfolio

Stack:
Laravel 13 + Turbo

---

# PHASE 1
Project Setup

- Laravel 13 install
- Tailwind
- Turbo
- Stimulus
- Authentication
- Admin layout

Deliverable:

✓ App runs
✓ Login works

---

# PHASE 2
Convert Static Template

Extract:

- sidebar
- navbar
- sections
- cards

Create:

resources/views/components/

Deliverable:

✓ identical UI

---

# PHASE 3
Database

Tables:

users
profiles
services
experiences
educations
skills
projects
project_categories
blog_posts
blog_categories
testimonials
social_links
contact_messages
settings

Deliverable:

✓ migration complete

---

# PHASE 4
Admin Panel

Features:

Dashboard

CRUD:

- profile
- skills
- experience
- education
- projects
- blogs
- settings

Deliverable:

✓ CMS works

---

# PHASE 5
Media

Upload:

- avatar
- projects
- blogs

Generate:

- webp
- thumbnails

Deliverable:

✓ media optimized

---

# PHASE 6
SEO

Features:

- sitemap
- robots
- og image
- meta manager

Deliverable:

✓ SEO complete

---

# PHASE 7
Contact

Features:

- contact form
- email
- inbox admin

Deliverable:

✓ messages stored

---

# PHASE 8
Blog

Features:

- categories
- tags
- search

Deliverable:

✓ mini CMS

---

# PHASE 9
Optimization

- cache
- image optimization
- eager loading

Deliverable:

✓ Lighthouse >95

---

# PHASE 10
Deployment

Support:

✓ Shared Hosting
✓ cPanel
✓ aaPanel
✓ VPS Docker

---

# File Structure

app/
resources/
routes/
database/
public/
storage/

resources/views:

frontend/
admin/
components/

---

# Route Example

/
/resume
/portfolio
/blog
/blog/{slug}
/contact

/admin
/admin/projects
/admin/blogs
/admin/settings

---

# Turbo Pattern

Links:

data-turbo="true"

Forms:

Turbo Stream

Delete:

Turbo Confirm

Modal:

Turbo Frame

---

# Acceptance Criteria

Frontend:

✓ same as original
✓ responsive
✓ fast

Admin:

✓ full CMS
✓ upload image
✓ SEO

System:

✓ easy deploy
✓ easy backup
✓ VPS friendly

---

# Nice To Have

Future:

- multilingual
- RSS
- analytics
- dark mode
- guestbook
- public API
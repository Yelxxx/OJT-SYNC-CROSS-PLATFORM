<div align="center">

# 🎓 OJT SYNC

### *Modern On-the-Job Training & Internship Management Ecosystem*

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=black)](https://alpinejs.dev)
[![Vite](https://img.shields.io/badge/Vite-6.x-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev)
[![License: MIT](https://img.shields.io/badge/License-MIT-33954B?style=for-the-badge)](LICENSE)

<br/>

**OJT Sync** streamlines and bridges the internship workflow across students, university coordinators, and partner company HR teams. From real-time daily time tracking (DTR) and e-journals to token-secured supervisor evaluations, everything is consolidated in one responsive platform.

[Key Features](#-core-capabilities) • [Role Architecture](#-role-architecture) • [Quickstart](#-quick-start) • [Demo Accounts](#-demo-credentials) • [Deployment](#-deployment)

---

</div>

## 📌 Workflow Overview

```mermaid
flowchart LR
    %% Actors
    subgraph Actors ["Actors"]
        direction TB
        Student["Student"]
        HR["Company HR"]
        Coord["Coordinator"]
        Super["Supervisor"]
    end

    %% Platform Modules
    subgraph Platform ["OJT Sync Platform"]
        direction TB
        Jobs["Job Matching & Applications"]
        DTR["Daily Time Records (DTR)"]
        Journal["Weekly E-Journals"]
        Eval["Tokenized Evaluations"]
    end

    %% Specific, accurate connections to modules
    Student -->|Apply with Resume| Jobs
    Student -->|Check-in / Check-out| DTR
    Student -->|Submit Weekly Reflection| Journal

    HR -->|Post Openings & Screen Applicants| Jobs
    HR -->|Verify Daily Hours| DTR
    HR -->|Generate Evaluation Request| Eval

    Coord -->|Monitor Cohort Progress & Hours| DTR
    Coord -->|Review Task Submissions| Journal
    Coord -->|Audit Final Scores| Eval

    Super -->|Submit Scores via Secure Link| Eval

    %% Styling
    style Actors fill:#1e293b,stroke:#475569,stroke-width:2px,color:#f8fafc
    style Platform fill:#0f172a,stroke:#3b82f6,stroke-width:2px,color:#f8fafc
```

---

## ⚡ Core Capabilities

<table>
<tr>
<td width="50%" valign="top">

### 🎓 For Students
- **Smart Job Board:** Filter and apply to approved partner company internships with PDF resume attachments.
- **One-Tap DTR:** Check-in and check-out with automatic hours calculations toward the required total (default: 300 hrs).
- **Weekly E-Journals:** Record key learnings, tasks accomplished, and reflections for coordinator review.
- **Progress Tracking:** Live visual progress rings showing accredited hours versus graduation requirements.

</td>
<td width="50%" valign="top">

### 🏢 For Partner Companies (HR)
- **Vacancy Management:** Publish, update, and manage company internship listings and slot caps.
- **Applicant Pipeline:** Track candidate status across `Pending` ➔ `Shortlisted` ➔ `Interview` ➔ `Hired / Rejected` with internal interview notes.
- **DTR Verification:** One-click approval / rejection for logged intern daily time records.
- **Intern Checklists:** Track onboarding, document requirements, and deliverables for each hired intern.

</td>
</tr>
<tr>
<td width="50%" valign="top">

### 📋 For OJT Coordinators
- **Cohort Dashboard:** Real-time metrics on all registered trainees and their hours rendered.
- **Audit Logs:** Deep-dive into time logs, attached tasks, and submitted e-journal entries.
- **Company Assignment:** Direct student-to-company assignments and official status management.
- **Performance Overview:** Instant access to consolidated supervisor scoring rubrics and remarks.

</td>
<td width="50%" valign="top">

### ⭐ Token-Protected Evaluations
- **Zero-Friction Grading:** No login or supervisor account needed.
- **Single-Use Tokens:** Cryptographically signed, disposable evaluation URLs.
- **Automated Score Aggregation:** Submissions instantly compile into HR and Coordinator gradebooks.
- **Rate-Limited & Secure:** Built-in safeguards against double submissions and scraping.

</td>
</tr>
</table>

---

## 🏛 Role Architecture

```
                    ┌────────────────────────┐
                    │      System Admin      │
                                             │
                    └───────────┬────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        ▼                       ▼                       ▼
 ┌──────────────┐        ┌──────────────┐        ┌──────────────┐
 │   Student    │        │  Company HR  │        │ Coordinator  │
 ├──────────────┤        ├──────────────┤        ├──────────────┤
 │ • Browse Jobs│        │ • Post Jobs  │        │ • Cohort DTR │
 │ • Submit App │───────►│ • Review App │        │ • E-Journals │
 │ • Time Clock │◄───────│ • Verify DTR │◄───────│ • Status     │
 │ • E-Journals │        │ • Request Eval──────┐ │ • Analytics  │
 └──────────────┘        └──────────────┘      │ └──────────────┘
                                               ▼
                                     ┌──────────────────┐
                                     │    Supervisor    │
                                     │ (Token Eval Link)│
                                     └──────────────────┘
```

---

## 🚀 Quick Start

### Prerequisites
- **PHP 8.3+** with SQLite / PostgreSQL extension enabled
- **Composer** (v2+)
- **Node.js** (v18+) & **npm**

### Installation

```bash
# 1. Clone repository
git clone https://github.com/your-username/ojt-sync.git
cd ojt-sync

# 2. Install dependencies & configure environment
composer install
cp .env.example .env
php artisan key:generate

# 3. Setup SQLite database & seed demo accounts
touch database/database.sqlite
php artisan migrate --seed

# 4. Install & bundle frontend assets
npm install
npm run build
```

### Run Locally

Launch the complete development environment (Web Server + Vite HMR + Queue Worker):

```bash
composer run dev
```

Visit [`http://localhost:8000`](http://localhost:8000) in your browser.

---

## 🔑 Demo Credentials

All seeded test accounts use the default password: `password`

| Role | Email Address | Access Scope |
| :--- | :--- | :--- |
| **🎓 Student** | `peter.parker@cvsu.edu.ph` | Intern portal, DTR clocking, journals, applications |
| **🏢 Company HR** | `hr@technologia.test` | Job postings, applicant tracker, DTR approval, evals |
| **📋 Coordinator** | `coordinator@cvsu.edu.ph` | Class overview, log auditing, company assignments |
| **🛡 Admin** | `admin@cvsu.edu.ph` | Company registration approvals & system governance |

---

## 🧪 Testing

OJT Sync includes an automated feature and unit test suite covering auth guards, applications, evaluations, and DTR calculations:

```bash
composer test
```

---

## 🌐 Deployment

The project is pre-configured with a zero-cost production template (**Render Web Service** + **Neon PostgreSQL**):

- Blueprint file: [`render.yaml`](render.yaml)
- Fully containerized via production-ready [`Dockerfile`](Dockerfile)
- Progressive Web App (PWA) ready with built-in offline service worker support

---

<div align="center">

Crafted with ❤️ for smoother college internship programs.

</div>

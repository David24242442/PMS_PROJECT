# PMS Project - Complete Analysis

## 1. Project Overview

The **PMS (Performance Management System)** is a full-stack web application built for the **Melcom Group** (a Ghanaian retail conglomerate with 10 companies and 179 branches). It serves two primary functions:

1. **HR Employee Management** - Complete employee lifecycle from onboarding through record management
2. **Performance Management** - Goal setting, appraisals, reviews, assessments, and HR reporting

The project consists of **three main codebases** in one repository:

| Component | Path | Tech Stack |
|-----------|------|------------|
| Backend API | `PMS_BACKEND_LARAVEL/` | Laravel 8, PHP 7.3+, MySQL, Sanctum |
| PMS Frontend | `PMS_FRONTEND_VUE/` | Vue 3, Vite, PrimeVue, Tailwind CSS |
| HR Onboarding Portal | `HR Onboarding/hrproject Vue/hrproject/` | Vue 3, Vite, PrimeVue, Tailwind CSS |

---

## 2. Backend Architecture (Laravel 8)

### 2.1 Authentication

Token-based authentication using **Laravel Sanctum**. Login endpoint (`POST /api/login`) validates username/password and returns a `plainTextToken`. All other API routes are protected by `auth:sanctum` middleware. Users have an `admin` boolean field for basic role differentiation.

### 2.2 API Route Structure

**Public:**
- `POST /api/login`

**Protected (auth:sanctum):**

| Prefix | Endpoints | Purpose |
|--------|-----------|---------|
| `/api/` | fetchusers, adduser, updateuser, updatepassword | User management |
| `/api/pms/` | dashboard | PMS statistics |
| `/api/pms/` | goals (apiResource) + goals/upload-attachment | SMART goal CRUD |
| `/api/pms/` | appraisals, appraisals/all, appraisals/{id}/review | Appraisal workflow |
| `/api/pms/` | assessments | Manager assessments |
| `/api/pms/` | reviews | Periodic reviews |
| `/api/pms/` | reports (apiResource) | HR report generation |
| `/api/pms/` | drafts | Form autosave |
| `/api/pms/` | employee-master, my-team | Employee hierarchy |

### 2.3 Controllers (22 Total)

**PMS Module Controllers:**

- **GoalController** - Full CRUD for SMART goals with title, description, purposes, challenges, category, weight, target/actual tracking, rating (1-5), quarterly tracking JSON, and file attachment uploads (PDF/DOC/XLS/CSV/images up to 10MB).

- **AppraisalController** - Annual appraisal form with goals_data and competencies_data (JSON snapshots), self-assessment, achievements, improvements, development plan, and digital signatures. Status flow: draft -> pending -> reviewed -> approved/rejected.

- **ReviewController** - Periodic review forms capturing goal achievement, initiatives, next steps, improvements, and manager feedback (all stored as JSON arrays).

- **AssessmentController** - Manager-driven evaluations with 5 rating dimensions (attendance, task completion, quality, teamwork, initiative), each rated 1-5, plus strengths/improvements/action items text.

- **DraftController** - Generic autosave mechanism storing any form state as JSON, keyed by user_id + type string.

- **HrReportController** - CRUD for HR reports by type (performance_summary, department_metrics, appraisal_status, goal_completion). Report generation logic is a stub.

- **PMSDashboardController** - Aggregate statistics: total employees/goals/completed goals/pending appraisals, completion rate, weekly progress.

- **EmployeeMasterController** - Employee hierarchy management (line_manager_id, employee_code, location, department). `myTeam` returns direct reports.

**HR/Employee Module Controllers:**

- **EmployeeController** (~1,450 lines, largest controller) - Complete employee lifecycle:
  - Paginated list with complex filtering (operators: is, isnot, gt, lt, contains; AND/OR conditions)
  - Full employee creation in one transaction (personal info, education, emergency contacts, job details, children, wives, references, work experience, union, work permit, driver license, nominee)
  - 12+ document upload endpoints (appointment letters, CV, SSNIT, NHIS, birth cert, police clearance, etc.)
  - Bank/social security and IRR guarantor management with witnesses
  - Record status workflow with history tracking
  - CSV export for data dumps and document checklists

- **DashboardController** - HR analytics: employee counts by gender, company, citizenship, department, location; age group distribution; monthly hiring trends.

- **Supporting Controllers** (10): PresentJobController, EmergencyContactController, SocialContactController, EducationController, WorkExperienceController, ReferenceController, ChildrenController, GuarantorController, WifeController, UnionController, NomineeController, DriverlicenseController, WorkpermitController, BankSocialController, IrrGuaranteeController, IrrGuarWitnessController, HistoryController.

### 2.4 Models & Relationships (15+ Models)

**Core Models:**

- **User** - id, name, email, username, password, position_id, admin, manager_name, department, line_manager_id, employee_code, location. Uses HasApiTokens (Sanctum).

- **Employee** - Central HR entity with 40+ fields (personal info, Ghana Card, address, DOB, marital status, joining info, company, gender, citizenship, social security, etc.). Relationships:
  - hasOne: BankSocial, IrrGuarantee, PresentJob, Nominee, Union, Workpermit, Driverlicense, SocialContact
  - hasMany: Education, EmergencyContact, Guarantor, Children, Wife, Reference, WorkExperience
  - Polymorphic: Upload (12+ document types), History (audit trail)
  - Record status: 0=Draft, 1=Submitted, 2=Verified, 3=Approved

- **Goal** - belongsTo User (owner) and User (creator). Fields: title, description, purposes, challenges, category, weight, target/actual, rating, status, due_date, smart_criteria (JSON), quarterly_tracking (JSON), year.

- **Appraisal** - belongsTo User (employee), User (reviewer), User (approver). Stores goals_data and competencies_data as JSON snapshots. Status: draft -> pending -> reviewed -> approved/rejected.

- **Review** - belongsTo User. JSON arrays for goal_achievement, initiatives, next_steps, improvements, manager_feedback.

- **Assessment** (table: user_assessments) - belongsTo User (employee) and User (assessor). Five 1-5 rating dimensions, checklist JSON.

- **Department** - Self-referential hierarchy (parent_id), manager (User), name, code, is_active.

- **Upload** - Polymorphic (uploadable_type/id), stores type string and file path.

- **History** - Polymorphic audit trail (histos_type/id), tracks user_id, recordstatus, details.

### 2.5 Database

MySQL database named `hr` with **55+ migration files** spanning from August 2024 to February 2026. Key tables: employees, users, goals, appraisals, reviews, user_assessments, hr_reports, departments, drafts, uploads, histories, plus ~15 supporting tables for employee sub-records.

### 2.6 Configuration

- **CORS**: Allows `localhost:5173` and `192.168.0.20:5173` (Vite dev servers) with credentials
- **Dependencies**: doctrine/dbal (migration column changes), Guzzle, Sanctum, fruitcake/laravel-cors

---

## 3. PMS Frontend (PMS_FRONTEND_VUE)

### 3.1 Tech Stack

- Vue 3.4 with Composition API (`<script setup>`)
- Vite 5.3 with dev proxy to Laravel at localhost:8000
- Pinia 2.1 for state management
- PrimeVue 4.0 (Nora theme, auto-imported)
- Tailwind CSS 3.4
- ApexCharts + Chart.js for data visualization
- vue3-signature for digital signatures
- @tato30/vue-pdf for PDF viewing
- SweetAlert2 + vue3-toastify for notifications
- Axios for HTTP with Bearer token interceptor

### 3.2 Routes & Pages

| Route | View | Description |
|-------|------|-------------|
| `/` | LoginView | Glassmorphism login with Melcom branding |
| `/dashboard` | DashboardView | HR analytics dashboard (~1800 lines) |
| `/app/pms/dashboard` | PMS DashboardView | PMS KPIs, trends, leaderboard |
| `/app/pms/goals` | GoalsView | SMART goal CRUD with quarterly tracking |
| `/app/pms/appraisal` | AppraisalView | 2-step appraisal with competency ratings |
| `/app/pms/review` | ReviewView | End-of-year review (5 sections) |
| `/onboarding` | OnboardingView | 4-tab employee onboarding (~5048 lines) |
| `/employees` | EmployeesView | Paginated employee table with filtering |
| `/employee/:empid` | EmployeeView | Read-only employee detail |
| `/app/hr/submissions` | SubmissionsView | HR/IT heads review appraisals |
| `/app/hr/reports` | ReportsView | Report generation with templates |
| `/users` | UsersView | User management (admin) |
| `/profile` | ProfileView | User profile |

### 3.3 Key Views in Detail

**HR Dashboard** - Summary stat cards (total employees, departments, locations, companies). Multiple ApexCharts: citizenship bar, gender donut, company donut, age groups bar, department bars, monthly onboarding, regional distribution with drill-down to branches. Includes a PDF viewer for company handbook.

**PMS Dashboard** - KPI cards (completion rate, happiness score, active/completed goals). Area chart for performance trends, bar chart for activity, radial chart for completion. Recent goals table and employee leaderboard.

**GoalsView** - Multi-step goal creation: Step 1 for goal details (dynamic fields, SMART criteria checklist), Step 2 for quarterly tracking (Q1-Q4 with dates, target measures, evidence, file attachments). Stats cards for total/completed/in-progress/pending.

**AppraisalView** - Two-step performance assessment. Step 1: four weighted competencies (Performance 20%, Customer Service 30%, Execution 30%, Continuous Improvement 20%) with self and manager ratings (1-5 scale). Step 2: computed overall weighted score, performance/potential matrix, reflection textareas, digital signature fields. Supports draft saving.

**ReviewView** - End-of-year review with 5 sections: A-Goal Achievement vs Target, B-Additional Initiatives, C-Next Steps, D-Improvements, E-Manager Feedback. Dynamic add/remove items per section.

**OnboardingView** (~5,048 lines, largest file) - Massive 4-tab form: Employee Info (4 steps), Bank/Social Security, Irrevocable Guarantee (3 steps), Checklist. Three digital signature pads. Base64 uploads for 15+ document types. Record status workflow with route leave guards.

### 3.4 Shared Utilities

- **helpers/essential.js** - Date formatting, toast/alert wrappers, calculateAge, numberToWords (Ghana "peswas" currency), formatMoney, HTML-to-PDF conversion via jsPDF.
- **data/masterdata.js** - All static lookups: 10 companies, 48 departments, 16 regions, 179 branches, 23 banks, countries, genders, marital statuses, contract types, qualification types, ID types, record statuses.

### 3.5 Authentication Flow

Login posts username/password, stores the Sanctum token and user object in both localStorage and Pinia store. Axios request interceptor injects Bearer token; response interceptor on 401 clears store and redirects to login. Route guards check localStorage for authentication.

---

## 4. HR Onboarding Portal (HR Onboarding/hrproject Vue/hrproject)

### 4.1 Purpose

A **unified HR portal** that combines employee onboarding with PMS features. Unlike PMS_FRONTEND_VUE (which is PMS-focused), this project started as an HR onboarding tool and was extended to include PMS.

### 4.2 Tech Stack

Identical to PMS_FRONTEND_VUE: Vue 3.4, Vite 5, Pinia, PrimeVue 4 (Nora), Tailwind CSS 3, ApexCharts, Chart.js, vue3-signature, vue-pdf, Quill (rich text editor), SweetAlert2, Axios.

### 4.3 Role-Based Access (4 Roles)

| Role | Access |
|------|--------|
| Role 2 | Onboarding, Employees |
| Role 3 | PMS Dashboard, Goals, Review, Appraisal |
| Role 4 (HR Head) | Full access: all HR + PMS + Admin |

### 4.4 Routes

HR routes: `/dashboard`, `/onboarding`, `/employees`, `/employee/:empid`, `/users`, `/profile`
PMS routes: `/pms/dashboard`, `/pms/goals`, `/pms/review`, `/pms/appraisal`, `/pms/employee-master`, `/hr/submissions`

### 4.5 Key Difference

Uses the same Laravel backend API. Auth state stored as `hrproject_user` and `hrproject_user_token` in localStorage. Has a dedicated `pms_axios.js` helper with separate Axios instance for PMS endpoints. Image URLs point to `/pms_backend/api/file/`.

---

## 5. PMS Workflow (End-to-End)

### 5.1 Goal Setting Phase
1. Employee logs in and navigates to PMS Dashboard
2. Creates SMART goals with title, description, purposes, challenges, category, weight, and target
3. Sets quarterly tracking milestones (Q1-Q4) with dates, measures, and evidence uploads
4. Goals have status: not_started -> in_progress -> completed/on_hold

### 5.2 Review Phase
1. Employee completes end-of-year Review form
2. Fills 5 sections: Goal Achievement vs Target, Additional Initiatives, Next Steps, Improvements
3. Manager adds feedback to each section
4. Review is saved with employee info snapshot

### 5.3 Appraisal Phase
1. Employee starts annual Appraisal (can save as draft)
2. Rates themselves on 4 weighted competencies (Performance 20%, Customer Service 30%, Execution 30%, Continuous Improvement 20%)
3. System computes overall weighted score
4. Employee adds reflections and digital signature
5. Manager adds their ratings and signature
6. Appraisal status: draft -> pending (submitted) -> reviewed (by manager) -> approved/rejected (by HR)

### 5.4 HR Review & Reporting
1. HR heads access Submissions page to review all appraisals
2. Can filter by status, department, search
3. Approve/reject with performance score (1-5) and remarks
4. Generate HR reports (performance summary, department metrics, appraisal status, goal completion)

---

## 6. HR Employee Management Workflow

### 6.1 Onboarding
1. HR creates employee record via 4-tab form
2. Tab 1 - Employee Info (4 steps): personal details, education, emergency contacts, job details, children, wives, references, work experience
3. Tab 2 - Bank/Social Security: bank account, social security, SSNIT details
4. Tab 3 - Irrevocable Guarantee: guarantor details with witnesses, signatures, ID cards
5. Tab 4 - Checklist: document verification (Ghana Card, SSNIT, NHIS, birth cert, CV, police clearance, etc.)

### 6.2 Document Management
15+ document types uploaded as base64: profile picture, Ghana Card, employee signature, guarantor signature, appointment letters, CV, SSNIT card, NHIS card, birth certificate, police clearance form, Petra Trust, probation confirmation.

### 6.3 Record Status Workflow
- 0 = Draft (initial entry)
- 1 = Submitted (sent for verification)
- 2 = Verified (checked by supervisor)
- 3 = Approved (final approval)
- Can move back: Approved -> Recheck (1) or Draft (0)

### 6.4 Audit Trail
All changes tracked via polymorphic History model with user attribution, timestamp, and status details.

---

## 7. Technical Observations

### Strengths
- Comprehensive domain model covering full HR and PMS lifecycle
- Polymorphic Upload and History models provide clean document management and audit trail
- Complex filtering with AND/OR conditions and multiple operators
- Digital signature capture integrated into forms
- Rich data visualization with ApexCharts and Chart.js
- Ghana-specific features (SSNIT, Ghana Card, ECOWAS, 23 banks, 16 regions)

### Areas for Improvement
- No centralized API service layer (each view calls Axios directly)
- No TypeScript (plain JavaScript throughout)
- No unit or integration tests
- Several views exceed 1,000 lines (OnboardingView at 5,048 lines)
- EmployeeController has no routes registered in api.php (may be called differently)
- HrReportController report generation is a stub (creates record but doesn't compute data)
- Authentication relies entirely on localStorage (no refresh token mechanism)
- Two separate frontends with significant code duplication
- No input validation library on frontend (manual validation only)
- README files are default boilerplate with no project-specific documentation

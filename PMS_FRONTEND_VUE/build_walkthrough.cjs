const pptxgen = require("pptxgenjs");

const pres = new pptxgen();
pres.layout = "LAYOUT_WIDE"; // 13.33" x 7.5"
pres.author = "Melcom HR IT Team";
pres.subject = "HR Portal Technical Walkthrough";
pres.title = "Melcom HR Portal – Developer Walkthrough";

// ── Color Palette ── Midnight Executive
const C = {
  navy: "1E2761",
  ice: "CADCFC",
  white: "FFFFFF",
  dark: "0F172A",
  slate: "334155",
  muted: "64748B",
  accent: "6366F1",   // indigo
  accent2: "8B5CF6",  // violet
  emerald: "10B981",
  amber: "F59E0B",
  red: "EF4444",
  bg: "F8FAFC",
  cardBg: "F1F5F9",
};

// ── Helper Functions ──
function titleSlide(slide, title, subtitle) {
  slide.background = { color: C.navy };
  slide.addText("HR PORTAL", {
    x: 0.7, y: 0.5, w: 3, h: 0.5,
    fontSize: 14, fontFace: "Calibri", bold: true, color: C.ice,
    charSpacing: 3,
  });
  slide.addText(title, {
    x: 0.7, y: 2.0, w: 11, h: 1.5,
    fontSize: 44, fontFace: "Calibri", bold: true, color: C.white,
    lineSpacingMultiple: 1.1,
  });
  slide.addText(subtitle, {
    x: 0.7, y: 3.6, w: 10, h: 0.8,
    fontSize: 18, fontFace: "Calibri", color: C.ice,
  });
  slide.addText("Melcom Group of Companies  |  IT Software Development Team", {
    x: 0.7, y: 6.5, w: 10, h: 0.4,
    fontSize: 11, fontFace: "Calibri", color: C.muted,
  });
}

function sectionSlide(slide, number, title, subtitle) {
  slide.background = { color: C.dark };
  slide.addShape(pres.shapes.RECTANGLE, {
    x: 0.7, y: 2.8, w: 0.8, h: 0.06, fill: { color: C.accent },
  });
  slide.addText(number, {
    x: 0.7, y: 1.5, w: 2, h: 1.0,
    fontSize: 60, fontFace: "Calibri", bold: true, color: C.accent, margin: 0,
  });
  slide.addText(title, {
    x: 0.7, y: 3.1, w: 11, h: 1.0,
    fontSize: 40, fontFace: "Calibri", bold: true, color: C.white,
  });
  slide.addText(subtitle, {
    x: 0.7, y: 4.2, w: 10, h: 0.6,
    fontSize: 16, fontFace: "Calibri", color: C.muted,
  });
}

function contentHeader(slide, title) {
  slide.background = { color: C.white };
  slide.addText(title, {
    x: 0.7, y: 0.4, w: 11, h: 0.6,
    fontSize: 28, fontFace: "Calibri", bold: true, color: C.dark, margin: 0,
  });
  slide.addShape(pres.shapes.RECTANGLE, {
    x: 0.7, y: 1.05, w: 1.5, h: 0.04, fill: { color: C.accent },
  });
}

function addCard(slide, x, y, w, h, bgColor) {
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x, y, w, h,
    fill: { color: bgColor || C.cardBg },
    rectRadius: 0.1,
    shadow: { type: "outer", blur: 6, offset: 2, angle: 90, color: "000000", opacity: 0.08 },
  });
}

function iconCircle(slide, x, y, color, letter) {
  slide.addShape(pres.shapes.OVAL, {
    x, y, w: 0.45, h: 0.45,
    fill: { color: color },
  });
  slide.addText(letter, {
    x, y, w: 0.45, h: 0.45,
    fontSize: 16, fontFace: "Calibri", bold: true, color: C.white,
    align: "center", valign: "middle", margin: 0,
  });
}

// ═══════════════════════════════════════════════════════════════
// SLIDE 1 — Title
// ═══════════════════════════════════════════════════════════════
let slide = pres.addSlide();
titleSlide(slide, "Melcom HR Portal\nDeveloper Walkthrough", "Complete technical guide to every module, page, and feature");
slide.addNotes("Title slide. Comprehensive walkthrough of the Melcom HR Portal for the development/technical team.");

// SLIDE 2 — Table of Contents
slide = pres.addSlide();
contentHeader(slide, "Table of Contents");
const tocItems = [
  { num: "01", title: "Tech Stack & Architecture", desc: "Vue 3, Laravel, deployment pipeline" },
  { num: "02", title: "Authentication & Login", desc: "Login flow, role-based redirects, token management" },
  { num: "03", title: "App Shell & Navigation", desc: "Sidebar, routing, permissions, dark mode" },
  { num: "04", title: "Onboarding Dashboard", desc: "KPI stats, charts, regional data, employee drilldown" },
  { num: "05", title: "Employee Onboarding", desc: "Multi-section form, data capture, OTP verification" },
  { num: "06", title: "Employees List", desc: "Column picker, CSV export, search, filtering" },
  { num: "07", title: "PMS Dashboard", desc: "Performance KPIs, leaderboard widget, weekly progress" },
  { num: "08", title: "Goals Management", desc: "Multi-step goal creation, SMART criteria, quarterly tracking" },
  { num: "09", title: "Appraisal", desc: "8 competency ratings, weighted scoring, signatures" },
  { num: "10", title: "Performance Review", desc: "4-step review wizard, manager ratings, PDF dossier" },
  { num: "11", title: "Leaderboard", desc: "Ranking by rating/completion, employee drilldown modal" },
  { num: "12", title: "HR Admin Module", desc: "User management, Line Manager Console, PMS Submissions" },
  { num: "13", title: "Profile & Settings", desc: "Password change, user info, dark mode toggle" },
  { num: "14", title: "Deployment Pipeline", desc: "Build, deploy scripts, route cache clearing" },
];
const colX = [0.7, 6.8];
const startY = 1.4;
tocItems.forEach((item, i) => {
  const col = i < 7 ? 0 : 1;
  const row = i < 7 ? i : i - 7;
  const x = colX[col];
  const y = startY + row * 0.78;
  slide.addText(item.num, { x, y, w: 0.5, h: 0.35, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
  slide.addText(item.title, { x: x + 0.55, y, w: 4.5, h: 0.35, fontSize: 13, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText(item.desc, { x: x + 0.55, y: y + 0.3, w: 4.5, h: 0.35, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });
});

// SLIDE 3 — Section: Tech Stack
slide = pres.addSlide();
sectionSlide(slide, "01", "Tech Stack & Architecture", "Frontend, backend, deployment infrastructure");

// SLIDE 4 — Tech Stack Details
slide = pres.addSlide();
contentHeader(slide, "Technology Stack");
addCard(slide, 0.7, 1.4, 5.7, 2.8, C.bg);
iconCircle(slide, 1.0, 1.6, C.accent, "F");
slide.addText("Frontend", { x: 1.6, y: 1.6, w: 4, h: 0.4, fontSize: 18, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText([
  { text: "Vue 3", options: { bold: true, color: C.dark } }, { text: " + Composition API (script setup)\n", options: { color: C.slate } },
  { text: "Vite 5", options: { bold: true, color: C.dark } }, { text: " — Build tool & dev server\n", options: { color: C.slate } },
  { text: "PrimeVue 4", options: { bold: true, color: C.dark } }, { text: " (Nora theme) — UI components\n", options: { color: C.slate } },
  { text: "Tailwind CSS 3", options: { bold: true, color: C.dark } }, { text: " — Utility-first styling\n", options: { color: C.slate } },
  { text: "Pinia", options: { bold: true, color: C.dark } }, { text: " — State management\n", options: { color: C.slate } },
  { text: "ApexCharts", options: { bold: true, color: C.dark } }, { text: " — Dashboard visualizations\n", options: { color: C.slate } },
  { text: "html2pdf.js", options: { bold: true, color: C.dark } }, { text: " — PDF generation from DOM", options: { color: C.slate } },
], { x: 1.0, y: 2.15, w: 5.0, h: 2.0, fontSize: 11, fontFace: "Calibri", lineSpacingMultiple: 1.4, margin: 0, valign: "top" });

addCard(slide, 6.9, 1.4, 5.7, 2.8, C.bg);
iconCircle(slide, 7.2, 1.6, C.emerald, "B");
slide.addText("Backend", { x: 7.8, y: 1.6, w: 4, h: 0.4, fontSize: 18, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText([
  { text: "Laravel 10", options: { bold: true, color: C.dark } }, { text: " — PHP framework (REST API)\n", options: { color: C.slate } },
  { text: "MySQL", options: { bold: true, color: C.dark } }, { text: " — Primary database\n", options: { color: C.slate } },
  { text: "XAMPP", options: { bold: true, color: C.dark } }, { text: " — Server stack on Windows\n", options: { color: C.slate } },
  { text: "Sanctum", options: { bold: true, color: C.dark } }, { text: " — API token authentication\n", options: { color: C.slate } },
  { text: "Eloquent ORM", options: { bold: true, color: C.dark } }, { text: " — Database models\n", options: { color: C.slate } },
  { text: "Production Server:", options: { bold: true, color: C.dark } }, { text: " 192.168.0.20:5050\n", options: { color: C.slate } },
  { text: "API Base:", options: { bold: true, color: C.dark } }, { text: " /pms_backend/api", options: { color: C.slate } },
], { x: 7.2, y: 2.15, w: 5.0, h: 2.0, fontSize: 11, fontFace: "Calibri", lineSpacingMultiple: 1.4, margin: 0, valign: "top" });

addCard(slide, 0.7, 4.5, 11.9, 2.3, C.bg);
iconCircle(slide, 1.0, 4.7, C.amber, "I");
slide.addText("Infrastructure & Deployment", { x: 1.6, y: 4.7, w: 5, h: 0.4, fontSize: 18, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText([
  { text: "Server 20 (192.168.0.20)", options: { bold: true, color: C.dark } },
  { text: " — Windows + XAMPP, port 5050. Frontend at C:\\xampp\\htdocs\\PMS\\pms_frontend\\, Backend at C:\\xampp\\htdocs\\PMS\\pms_backend\\\n", options: { color: C.slate } },
  { text: "Deployment Pipeline:", options: { bold: true, color: C.dark } },
  { text: " Local dev machine > Share folder (\\\\192.168.0.24) > robocopy to Server 20\n", options: { color: C.slate } },
  { text: "Frontend Build:", options: { bold: true, color: C.dark } },
  { text: " npm run build > deploy_pms_frontend.bat > robocopy dist to server\n", options: { color: C.slate } },
  { text: "Route Cache:", options: { bold: true, color: C.dark } },
  { text: " Hit /api/run-route-clear after backend deploy to clear Laravel route cache", options: { color: C.slate } },
], { x: 1.0, y: 5.25, w: 11.2, h: 1.5, fontSize: 11, fontFace: "Calibri", lineSpacingMultiple: 1.35, margin: 0, valign: "top" });

// SLIDE 5 — Section: Authentication
slide = pres.addSlide();
sectionSlide(slide, "02", "Authentication & Login", "Credentials, token storage, role-based routing");

// SLIDE 6 — Login Page Details
slide = pres.addSlide();
contentHeader(slide, "Login Page");
addCard(slide, 0.7, 1.4, 6.0, 5.4, C.bg);
slide.addText("Route & Component", { x: 1.0, y: 1.6, w: 5, h: 0.35, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addText([
  { text: "Path: ", options: { bold: true } }, { text: "/  (root)\n" },
  { text: "File: ", options: { bold: true } }, { text: "views/LoginView.vue\n" },
  { text: "Auth Required: ", options: { bold: true } }, { text: "No" },
], { x: 1.0, y: 2.05, w: 5.2, h: 0.9, fontSize: 11, fontFace: "Calibri", color: C.slate, lineSpacingMultiple: 1.35, margin: 0 });
slide.addText("Features", { x: 1.0, y: 3.0, w: 5, h: 0.35, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
const loginFeatures = [
  "Full-screen split layout with Melcom branding image on left, login form on right",
  "Username + password fields with show/hide toggle",
  "Error state display on invalid credentials",
  "Loading spinner during authentication",
  "POST /login receives user object + bearer token",
  "Stores user data and token in localStorage",
  "Dynamic redirect after login based on role",
  "Already logged-in users auto-redirect away from login page",
];
loginFeatures.forEach((feat, i) => {
  slide.addText(feat, { x: 1.0, y: 3.45 + i * 0.35, w: 5.2, h: 0.33, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, bullet: true, paraSpaceAfter: 2 });
});

addCard(slide, 7.1, 1.4, 5.5, 3.2, C.bg);
slide.addText("Role-Based Redirect Logic", { x: 7.4, y: 1.6, w: 4.5, h: 0.35, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addTable([
  ["Role", "position_id", "Redirect"],
  ["HR Head", "4", "/pms/goals"],
  ["Line Manager", "3", "/pms/goals"],
  ["HR Officer", "2", "First permitted route"],
  ["Standard User", "1", "/profile"],
], { x: 7.4, y: 2.1, w: 5.0, fontSize: 10, fontFace: "Calibri", colW: [1.8, 1.2, 2.0], border: { type: "solid", pt: 0.5, color: "E2E8F0" }, rowH: 0.35, autoPage: false, headerRow: true, color: C.slate });

addCard(slide, 7.1, 4.9, 5.5, 1.9, C.bg);
slide.addText("Token Management", { x: 7.4, y: 5.1, w: 4.5, h: 0.35, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addText([
  { text: "pms_axios.js", options: { bold: true, color: C.dark } }, { text: " creates a dedicated Axios instance\n" },
  { text: "Request interceptor attaches Bearer token from localStorage\n" },
  { text: "Response interceptor catches 401 > clears storage > redirects to /\n" },
  { text: "Base URL from ", options: {} }, { text: "VITE_API_BASE_URL", options: { bold: true, color: C.dark } }, { text: " env variable", options: {} },
], { x: 7.4, y: 5.55, w: 5.0, h: 1.1, fontSize: 10, fontFace: "Calibri", color: C.slate, lineSpacingMultiple: 1.35, margin: 0 });

// SLIDE 7 — Section: App Shell
slide = pres.addSlide();
sectionSlide(slide, "03", "App Shell & Navigation", "Sidebar structure, permission-based menus, dark mode");

// SLIDE 8 — Navigation & Sidebar
slide = pres.addSlide();
contentHeader(slide, "Sidebar Navigation & Routing");
addCard(slide, 0.7, 1.4, 6.0, 5.5, C.bg);
slide.addText("Sidebar Menu Structure (Nav.vue)", { x: 1.0, y: 1.55, w: 5.5, h: 0.35, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
const navItems = [
  { indent: 0, text: "Dashboard  >  /dashboard" }, { indent: 0, text: "Onboarding (dropdown)" },
  { indent: 1, text: "Onboarding  >  /onboarding" }, { indent: 1, text: "Employees  >  /employees" },
  { indent: 0, text: "Performance (dropdown)" }, { indent: 1, text: "Dashboard  >  /pms/dashboard" },
  { indent: 1, text: "Goals  >  /pms/goals" }, { indent: 1, text: "Appraisal  >  /pms/appraisal" },
  { indent: 1, text: "Review  >  /pms/review" }, { indent: 0, text: "HR Admin (dropdown)" },
  { indent: 1, text: "Manage Users  >  /users" }, { indent: 1, text: "Line Manager Console  >  /pms/employee-master" },
  { indent: 1, text: "PMS Submissions  >  /hr/submissions" }, { indent: 0, text: "Profile  >  /profile" },
  { indent: 0, text: "Logout" },
];
navItems.forEach((item, i) => {
  slide.addText(item.text, {
    x: item.indent === 1 ? 1.5 : 1.0, y: 2.0 + i * 0.32, w: 5.2, h: 0.3,
    fontSize: 10, fontFace: "Calibri", margin: 0, bold: item.indent === 0, color: item.indent === 0 ? C.dark : C.slate,
  });
});

addCard(slide, 7.1, 1.4, 5.5, 5.5, C.bg);
slide.addText("Navigation Features", { x: 7.4, y: 1.55, w: 4.5, h: 0.35, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
const navFeatures = [
  { title: "Collapsible Sidebar", desc: "260px expanded to 80px collapsed. State persisted in localStorage." },
  { title: "Permission-Based Visibility", desc: "Each menu item checks loguser.permissions array. Only permitted routes shown." },
  { title: "Dark Mode Toggle", desc: "Adds body.dark-mode class. CSS variables handle all color switches." },
  { title: "Active Route Indicator", desc: "Highlight bar on the active router-link via .active-indicator element." },
  { title: "Dropdown Submenus", desc: "Onboarding, Performance, HR Admin groups with chevron toggle." },
  { title: "Appraisal Nav Guard", desc: "Appraisal link disabled (opacity-50) when no goals pending appraisal." },
  { title: "Route Guard", desc: "router.beforeEach checks requiresAuth + permissions. HR Head bypasses all." },
  { title: "NProgress Bar", desc: "Top loading bar on every route transition." },
];
navFeatures.forEach((feat, i) => {
  slide.addText(feat.title, { x: 7.4, y: 2.05 + i * 0.62, w: 5.0, h: 0.25, fontSize: 11, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText(feat.desc, { x: 7.4, y: 2.3 + i * 0.62, w: 5.0, h: 0.32, fontSize: 9, fontFace: "Calibri", color: C.muted, margin: 0 });
});

// SLIDE 9 — Section: Onboarding Dashboard
slide = pres.addSlide();
sectionSlide(slide, "04", "Onboarding Dashboard", "HR metrics, regional distribution, department breakdowns");

// SLIDE 10 — Onboarding Dashboard Details
slide = pres.addSlide();
contentHeader(slide, "Onboarding Dashboard");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/dashboard   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/DashboardView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "HR Head (role 4)" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

const kpiCards = [
  { label: "Total Employees", icon: "E", color: C.accent },
  { label: "Active Onboarding", icon: "A", color: C.emerald },
  { label: "Departments", icon: "D", color: C.amber },
  { label: "Regions", icon: "R", color: C.accent2 },
];
kpiCards.forEach((kpi, i) => {
  const x = 0.7 + i * 3.05;
  addCard(slide, x, 1.7, 2.8, 1.1, C.bg);
  iconCircle(slide, x + 0.15, 1.85, kpi.color, kpi.icon);
  slide.addText(kpi.label, { x: x + 0.7, y: 1.85, w: 1.9, h: 0.4, fontSize: 12, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText("Dynamic from API", { x: x + 0.7, y: 2.25, w: 1.9, h: 0.3, fontSize: 9, fontFace: "Calibri", color: C.muted, margin: 0 });
});

addCard(slide, 0.7, 3.1, 5.8, 3.8, C.bg);
slide.addText("Melcom Departments (Treemap)", { x: 1.0, y: 3.3, w: 5.0, h: 0.3, fontSize: 13, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText("ApexCharts treemap showing employee count per department. Click any block to drill down into a searchable employee popup dialog with light theme.", { x: 1.0, y: 3.7, w: 5.2, h: 0.8, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.3 });
slide.addText("Also includes:\n- Hospitality Departments (bar chart)\n- Regional Distribution (bar chart) for 8 Ghana regions\n- Gender Breakdown\n- Employee Status Distribution", { x: 1.0, y: 4.6, w: 5.2, h: 1.2, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.3 });

addCard(slide, 6.9, 3.1, 5.7, 3.8, C.bg);
slide.addText("Interactive Features", { x: 7.2, y: 3.3, w: 5.0, h: 0.3, fontSize: 13, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
["Clickable chart segments open employee list popup","Employee popup with search, profile photos, and details","Light-themed PrimeVue Dialog (custom .emp-dialog-light styles)","Region aggregation from branch-to-region mapping arrays","Master data lookup helpers (finddept, findbranch, findcompany)","Loading overlay via Pinia store (setIsLoading)","Dark mode support with CSS variable switching"].forEach((f, i) => {
  slide.addText(f, { x: 7.2, y: 3.75 + i * 0.38, w: 5.0, h: 0.35, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, bullet: true, paraSpaceAfter: 2 });
});

// SLIDE 11 — Section: Employee Onboarding
slide = pres.addSlide();
sectionSlide(slide, "05", "Employee Onboarding", "Comprehensive multi-section data capture form");

// SLIDE 12 — Onboarding Form Details
slide = pres.addSlide();
contentHeader(slide, "Employee Onboarding Form");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/onboarding   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/OnbaordingView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "HR Officer (2), HR Head (4)" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

const formSections = [
  { title: "Personal Information", desc: "Name, DOB (age validation: 18-80), gender, citizenship, marital status, Ghana Card, contacts, addresses" },
  { title: "Position & Employment", desc: "Joining company, department, branch/location, position, contract type, joining date, employee ID" },
  { title: "Bank & Social Security", desc: "Bank name, account type, account number, Social Security number" },
  { title: "Family Details", desc: "Spouse information (multiple wives support), children records, next of kin/nominee" },
  { title: "Emergency Contacts", desc: "Multiple emergency contact entries with name, phone, relationship, address" },
  { title: "Education & Qualifications", desc: "Multiple education records with institution, qualification type, year, certificate" },
  { title: "Work Experience", desc: "Previous employer entries with position, dates, reason for leaving" },
  { title: "References & Guarantors", desc: "Reference entries + guarantor details with witness section" },
  { title: "Documents & Uploads", desc: "File attachments for certificates, ID copies, passport photos" },
  { title: "OTP Verification & Submit", desc: "Terms acceptance checkbox, OTP request/verification, final form submission" },
];
formSections.forEach((section, i) => {
  const col = i < 5 ? 0 : 1;
  const row = i < 5 ? i : i - 5;
  const x = col === 0 ? 0.7 : 6.9;
  const y = 1.7 + row * 1.1;
  addCard(slide, x, y, 5.8, 0.95, C.bg);
  slide.addText(`${String(i + 1).padStart(2, '0')}  ${section.title}`, { x: x + 0.2, y: y + 0.08, w: 5.3, h: 0.3, fontSize: 12, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText(section.desc, { x: x + 0.2, y: y + 0.4, w: 5.3, h: 0.48, fontSize: 9, fontFace: "Calibri", color: C.muted, margin: 0, lineSpacingMultiple: 1.2 });
});

// SLIDE 13 — Section: Employees List
slide = pres.addSlide();
sectionSlide(slide, "06", "Employees List", "Searchable table with dynamic columns and CSV export");

// SLIDE 14 — Employees List Details
slide = pres.addSlide();
contentHeader(slide, "Employees List Page");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/employees   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/EmployeesView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "HR Officer (2), HR Head (4)" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

addCard(slide, 0.7, 1.7, 7.5, 5.2, C.bg);
slide.addText("Features & Functionality", { x: 1.0, y: 1.85, w: 6.5, h: 0.35, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
[
  { title: "Dynamic Column Picker", desc: "Toggle 20+ optional columns (Position Details, Personal Info). Default 7 columns always visible." },
  { title: "Search & Filtering", desc: "Real-time text search across employee ID, name, email, mobile. Multi-criteria filter sidebar." },
  { title: "CSV Export", desc: "Export currently visible columns + data to CSV file. Respects active column selection and filters." },
  { title: "Employee Detail Navigation", desc: "Click any row to navigate to /employee/:empid for full employee profile view." },
  { title: "Status Display", desc: "Color-coded status badges using findstatus() lookup. Shows onboarding progress state." },
  { title: "Master Data Lookups", desc: "All coded values resolved to display names via masterdata helpers." },
  { title: "Print Support", desc: "Print-friendly styling. Nav and header hidden via @media print CSS." },
].forEach((feat, i) => {
  slide.addText(feat.title, { x: 1.0, y: 2.35 + i * 0.62, w: 6.8, h: 0.25, fontSize: 11, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText(feat.desc, { x: 1.0, y: 2.6 + i * 0.62, w: 6.8, h: 0.32, fontSize: 9, fontFace: "Calibri", color: C.muted, margin: 0 });
});

addCard(slide, 8.5, 1.7, 4.1, 5.2, C.bg);
slide.addText("Column Groups", { x: 8.7, y: 1.85, w: 3.5, h: 0.35, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
[
  { group: "Default (locked)", cols: "Employee ID, First Name, Surname, Email, Mobile No, Status, Created By" },
  { group: "Position Details", cols: "Company, Contract Category, Location, Department, Position, Joining Date" },
  { group: "Personal Info", cols: "Middle Name, Citizenship, GH Card, Address, Hometown, Alt Phone, Gender, DOB, SSN, Marital Status" },
].forEach((g, i) => {
  slide.addText(g.group, { x: 8.7, y: 2.35 + i * 1.5, w: 3.7, h: 0.3, fontSize: 11, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText(g.cols, { x: 8.7, y: 2.7 + i * 1.5, w: 3.7, h: 1.0, fontSize: 9, fontFace: "Calibri", color: C.muted, margin: 0, lineSpacingMultiple: 1.3 });
});

// SLIDE 15 — Section: PMS Dashboard
slide = pres.addSlide();
sectionSlide(slide, "07", "PMS Dashboard", "Performance metrics, leaderboard, weekly progress chart");

// SLIDE 16 — PMS Dashboard Details
slide = pres.addSlide();
contentHeader(slide, "Performance Management Dashboard");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/pms/dashboard   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/pms/DashboardView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "Line Manager (3), HR Head (4)" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

[
  { label: "Completion Rate", desc: "% of users with goals. Shows X of Y goals completed.", color: C.accent },
  { label: "Avg Rating", desc: "Computed from top employees' avg_rating values.", color: C.emerald },
  { label: "Active Goals", desc: "Total goals count + pending appraisals count.", color: C.amber },
  { label: "Goals Completed", desc: "Completed goals + approved appraisals.", color: C.accent2 },
].forEach((kpi, i) => {
  const x = 0.7 + i * 3.05;
  addCard(slide, x, 1.65, 2.8, 1.0, C.bg);
  slide.addText(kpi.label, { x: x + 0.15, y: 1.75, w: 2.5, h: 0.3, fontSize: 12, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText(kpi.desc, { x: x + 0.15, y: 2.1, w: 2.5, h: 0.4, fontSize: 9, fontFace: "Calibri", color: C.muted, margin: 0 });
});

addCard(slide, 0.7, 2.95, 5.8, 4.0, C.bg);
slide.addText("Live Data Indicators", { x: 1.0, y: 3.1, w: 5.0, h: 0.3, fontSize: 13, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText([
  { text: "Dynamic date display: ", options: { bold: true, color: C.dark } },
  { text: "Shows current date with green pulsing dot and 'Live' badge\n\n" },
  { text: "Weekly Progress Chart: ", options: { bold: true, color: C.dark } },
  { text: "ApexCharts area chart showing goals created over the last 7 days\n\n" },
  { text: "Recent Goals: ", options: { bold: true, color: C.dark } },
  { text: "Last 5 goals with status badges, time progress bar, and user avatars\n\n" },
  { text: "Performance Distribution: ", options: { bold: true, color: C.dark } },
  { text: "Donut chart showing goal status breakdown\n\n" },
  { text: "API Endpoint: ", options: { bold: true, color: C.dark } },
  { text: "GET /pms/dashboard" },
], { x: 1.0, y: 3.5, w: 5.2, h: 3.2, fontSize: 10, fontFace: "Calibri", color: C.slate, lineSpacingMultiple: 1.2, margin: 0, valign: "top" });

addCard(slide, 6.9, 2.95, 5.7, 4.0, C.bg);
slide.addText("Top Performers Widget", { x: 7.2, y: 3.1, w: 5.0, h: 0.3, fontSize: 13, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText([
  { text: "Shows top 5 employees ranked by real avg_rating from appraisal_data\n\n" },
  { text: "Each entry shows: avatar initial, name + job title, star rating, numeric score\n\n" },
  { text: "Click an employee to open modal with their goal details\n\n" },
  { text: "Data source: ", options: { bold: true, color: C.dark } },
  { text: "buildLeaderboard(5) in PMSDashboardController extracts managerRating from appraisal_data competencies, deduplicates by normalized candidate_name" },
], { x: 7.2, y: 3.5, w: 5.2, h: 3.2, fontSize: 10, fontFace: "Calibri", color: C.slate, lineSpacingMultiple: 1.2, margin: 0, valign: "top" });

// SLIDE 17 — Section: Goals
slide = pres.addSlide();
sectionSlide(slide, "08", "Goals Management", "Create, track, and manage performance goals");

// SLIDE 18 — Goals Page Details
slide = pres.addSlide();
contentHeader(slide, "Goals Management Page");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/pms/goals   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/pms/GoalsView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "Line Manager (3), HR Head (4)" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

addCard(slide, 0.7, 1.65, 5.8, 5.3, C.bg);
slide.addText("List View (Default)", { x: 1.0, y: 1.8, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
["Year selector dropdown (current year - 5 years)","Goal cards: Record ID, candidate name, title, status badge, time progress","Status badges: Draft, In Progress, Submitted, Completed, Approved","Click any goal card for detail modal with full info","Detail modal: descriptions, purposes, challenges, SMART, quarterly, appraisal","Create New Goal button switches to creation wizard","Print support with PDF dossier (MELCOM HR PMS watermark)"].forEach((f, i) => {
  slide.addText(f, { x: 1.0, y: 2.2 + i * 0.43, w: 5.2, h: 0.4, fontSize: 9.5, fontFace: "Calibri", color: C.slate, margin: 0, bullet: true, paraSpaceAfter: 2 });
});

addCard(slide, 6.9, 1.65, 5.7, 5.3, C.bg);
slide.addText("Goal Creation Wizard (2 Steps)", { x: 7.2, y: 1.8, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addText("Step 1 - Goal Details", { x: 7.2, y: 2.25, w: 5.0, h: 0.25, fontSize: 11, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText("Employee selection via AutoComplete from HR DB. Auto-fills: name, code, dept, job title, location, manager. Goal title, category, target, dates. Dynamic list fields: descriptions, purposes, challenges. SMART Criteria checkboxes (color-coded S-M-A-R-T badges).", { x: 7.2, y: 2.55, w: 5.2, h: 1.6, fontSize: 9.5, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.3, valign: "top" });
slide.addText("Step 2 - Quarterly Tracking", { x: 7.2, y: 4.3, w: 5.0, h: 0.25, fontSize: 11, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText("4 quarters (Q1-Q4) with auto-populated date ranges. Each quarter: start/end dates, target measures (dynamic list), evidence notes. Color-coded headers: Q1=red, Q2=purple, Q3=green, Q4=amber. Draft auto-save support. API: POST /pms/goals, PUT /pms/goals/{id}.", { x: 7.2, y: 4.6, w: 5.2, h: 1.8, fontSize: 9.5, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.3, valign: "top" });

// SLIDE 19 — Section: Appraisal
slide = pres.addSlide();
sectionSlide(slide, "09", "Performance Appraisal", "Competency-based rating with weighted scoring");

// SLIDE 20 — Appraisal Details
slide = pres.addSlide();
contentHeader(slide, "Appraisal Page");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/pms/appraisal   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/pms/AppraisalView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "Line Manager (3), HR Head (4)" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

addCard(slide, 0.7, 1.65, 7.5, 4.0, C.bg);
slide.addText("8 Performance Key Competencies", { x: 1.0, y: 1.8, w: 6.5, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addTable([
  ["#", "Competency", "Weight", "Self Rating", "Manager Rating"],
  ["1", "Quality of Work", "15%", "1-5 scale", "1-5 scale"],
  ["2", "Quantity of Work", "15%", "1-5 scale", "1-5 scale"],
  ["3", "Job Knowledge", "15%", "1-5 scale", "1-5 scale"],
  ["4", "Dependability", "15%", "1-5 scale", "1-5 scale"],
  ["5", "Cooperation", "10%", "1-5 scale", "1-5 scale"],
  ["6", "Adaptability", "10%", "1-5 scale", "1-5 scale"],
  ["7", "Initiative", "10%", "1-5 scale", "1-5 scale"],
  ["8", "Judgment", "10%", "1-5 scale", "1-5 scale"],
  ["", "Total", "100%", "", ""],
], { x: 1.0, y: 2.2, w: 7.0, fontSize: 9, fontFace: "Calibri", colW: [0.4, 2.2, 0.8, 1.5, 1.5], border: { type: "solid", pt: 0.5, color: "E2E8F0" }, rowH: 0.3, autoPage: false, headerRow: true, color: C.slate });

addCard(slide, 8.5, 1.65, 4.1, 2.5, C.bg);
slide.addText("Rating Scale", { x: 8.7, y: 1.8, w: 3.5, h: 0.3, fontSize: 13, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
["5 - Outstanding / Exceptional","4 - Exceeding Expectations","3 - Meeting Expectations","2 - Partly Meeting Expectations","1 - Below Expectations"].forEach((r, i) => {
  slide.addText(r, { x: 8.7, y: 2.2 + i * 0.33, w: 3.7, h: 0.3, fontSize: 9, fontFace: "Calibri", color: C.slate, margin: 0 });
});

addCard(slide, 8.5, 4.35, 4.1, 2.5, C.bg);
slide.addText("Additional Fields", { x: 8.7, y: 4.5, w: 3.5, h: 0.3, fontSize: 13, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText("Performance & Potential ratings. 'Impressed Most' / 'Impressed Least' text areas. HOD comments section. Three signature fields: Candidate, Manager, HOD. Signature date auto-set to today. Read-only mode after review_completed. 2-step wizard (competencies then signatures).", { x: 8.7, y: 4.9, w: 3.7, h: 1.8, fontSize: 9, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.3 });

// SLIDE 21 — Section: Review
slide = pres.addSlide();
sectionSlide(slide, "10", "Performance Review", "4-step review wizard with PDF dossier generation");

// SLIDE 22 — Review Details
slide = pres.addSlide();
contentHeader(slide, "Performance Review Page");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/pms/review   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/pms/ReviewView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "Line Manager (3), HR Head (4)" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

[
  { num: "Step 1", title: "Goal Summary", desc: "View the submitted goal with all details: title, descriptions, purposes, challenges, SMART criteria badges (color-coded S-M-A-R-T), quarterly tracking tables with date ranges and target measures." },
  { num: "Step 2", title: "Appraisal Review", desc: "Review all 8 competency ratings side-by-side (Self vs Manager). Weighted score calculation displayed. Performance rating mapped to potential rating." },
  { num: "Step 3", title: "Manager Assessment", desc: "Manager enters overall performance rating (1-5), potential rating auto-mapped, performance and potential comments. HOD comments section." },
  { num: "Step 4", title: "Signatures & Submit", desc: "Digital signature collection: Candidate, Manager, HOD names with date. Submit button finalizes (status becomes review_completed)." },
].forEach((step, i) => {
  const x = 0.7 + i * 3.05;
  addCard(slide, x, 1.65, 2.8, 4.8, C.bg);
  slide.addText(step.num, { x: x + 0.15, y: 1.8, w: 2.5, h: 0.3, fontSize: 11, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
  slide.addText(step.title, { x: x + 0.15, y: 2.1, w: 2.5, h: 0.35, fontSize: 14, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText(step.desc, { x: x + 0.15, y: 2.55, w: 2.5, h: 3.5, fontSize: 9.5, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.35, valign: "top" });
});
slide.addText([
  { text: "PDF Dossier: ", options: { bold: true, color: C.dark } },
  { text: "Print generates multi-page A4 PDF with MELCOM HR PMS watermark, goal summary, SMART criteria, quarterly tracking, competency ratings, signatures. Uses html2pdf.js.", options: { color: C.slate } },
], { x: 0.7, y: 6.65, w: 12, h: 0.5, fontSize: 10, fontFace: "Calibri", margin: 0 });

// SLIDE 23 — Section: Leaderboard
slide = pres.addSlide();
sectionSlide(slide, "11", "Employee Leaderboard", "Rankings by rating and goal completion");

// SLIDE 24 — Leaderboard Details
slide = pres.addSlide();
contentHeader(slide, "Leaderboard Page");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/pms/leaderboard   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/pms/LeaderboardView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "Line Manager (3), HR Head (4)" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

addCard(slide, 0.7, 1.65, 6.0, 5.3, C.bg);
slide.addText("UI Features", { x: 1.0, y: 1.8, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
[
  { title: "Dual Ranking Tabs", desc: "Toggle between 'By Rating' and 'By Completion'. Dynamic sorting." },
  { title: "Trophy Badges", desc: "Top 3 get gold/silver/bronze trophy icons. Others show standard user icon." },
  { title: "Employee Cards", desc: "Avatar initial, name, department, job title, avg rating stars, completion % bar." },
  { title: "Search", desc: "Filter by name or department in real-time." },
  { title: "Employee Modal", desc: "Click any card for modal with all goals, statuses, ratings, dates." },
  { title: "Status Pills", desc: "Color-coded: completed=green, approved=blue, submitted=amber, draft=gray." },
].forEach((f, i) => {
  slide.addText(f.title, { x: 1.0, y: 2.25 + i * 0.75, w: 5.5, h: 0.25, fontSize: 11, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText(f.desc, { x: 1.0, y: 2.5 + i * 0.75, w: 5.5, h: 0.4, fontSize: 9.5, fontFace: "Calibri", color: C.muted, margin: 0 });
});

addCard(slide, 7.1, 1.65, 5.5, 5.3, C.bg);
slide.addText("Backend: buildLeaderboard()", { x: 7.4, y: 1.8, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addText([
  { text: "API: ", options: { bold: true, color: C.dark } }, { text: "GET /pms/leaderboard\n\n" },
  { text: "Controller: ", options: { bold: true, color: C.dark } }, { text: "PMSDashboardController@leaderboard\n\n" },
  { text: "Algorithm:\n1. Fetch all Goals with user relationship\n2. Group by normalized candidate_name (strtolower + trim)\n3. For each employee: count goals, extract managerRating from appraisal_data.competencies, compute avg_rating\n4. Sort: highest avg_rating first, then completion_pct\n5. Return: user_id, name, department, job_title, avg_rating, completion_pct, goals[]\n\n" },
  { text: "Shared helper: ", options: { bold: true, color: C.dark } },
  { text: "buildLeaderboard($limit) used by /pms/dashboard (top 5) and /pms/leaderboard (all)" },
], { x: 7.4, y: 2.2, w: 5.0, h: 4.5, fontSize: 9.5, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.3, valign: "top" });

// SLIDE 25 — Section: HR Admin
slide = pres.addSlide();
sectionSlide(slide, "12", "HR Admin Module", "User management, line manager assignments, submissions overview");

// SLIDE 26 — Manage Users
slide = pres.addSlide();
contentHeader(slide, "HR Admin: Manage Users");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/users   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/UsersView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "HR Head (4) only" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

addCard(slide, 0.7, 1.65, 5.8, 5.3, C.bg);
slide.addText("User Management Features", { x: 1.0, y: 1.8, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
["List all system users with name, username, role, permissions","Create new user from existing users table","Default password: 'Password'","Role assignment: 1=Standard, 2=HR Officer, 3=Line Manager, 4=HR Head","Permission-based page access control (category checkboxes)","Edit existing user details and permissions","Permission categories: Onboarding, Performance, HR Admin","Each category has specific page toggles"].forEach((f, i) => {
  slide.addText(f, { x: 1.0, y: 2.25 + i * 0.42, w: 5.2, h: 0.38, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, bullet: true, paraSpaceAfter: 2 });
});

addCard(slide, 6.9, 1.65, 5.7, 5.3, C.bg);
slide.addText("Permission Pages Matrix", { x: 7.2, y: 1.8, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addTable([
  ["Category", "Page", "Route Path"],
  ["Onboarding", "Dashboard", "/dashboard"],
  ["Onboarding", "Onboarding", "/onboarding"],
  ["Onboarding", "Employees", "/employees"],
  ["Performance", "PMS Dashboard", "/pms/dashboard"],
  ["Performance", "Goals", "/pms/goals"],
  ["Performance", "Appraisal", "/pms/appraisal"],
  ["Performance", "Review", "/pms/review"],
  ["HR Admin", "PMS Submissions", "/hr/submissions"],
  ["HR Admin", "Manage Users", "/users"],
  ["HR Admin", "Line Manager Console", "/pms/employee-master"],
], { x: 7.2, y: 2.2, w: 5.3, fontSize: 9, fontFace: "Calibri", colW: [1.3, 2.0, 2.0], border: { type: "solid", pt: 0.5, color: "E2E8F0" }, rowH: 0.3, autoPage: false, headerRow: true, color: C.slate });

// SLIDE 27 — Line Manager Console
slide = pres.addSlide();
contentHeader(slide, "HR Admin: Line Manager Console");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/pms/employee-master   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/pms/EmployeeMasterView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "HR Head (4) only" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

addCard(slide, 0.7, 1.65, 11.9, 5.2, C.bg);
slide.addText("Purpose & Features", { x: 1.0, y: 1.8, w: 10.5, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addText("Manages the mapping between Line Managers and their team members. Determines which employees a manager can create goals for and appraise.", { x: 1.0, y: 2.2, w: 11.0, h: 0.5, fontSize: 11, fontFace: "Calibri", color: C.slate, margin: 0 });
[
  { title: "Manager List Table", desc: "Shows all Line Manager users. Search by name. Each row: manager name, team count, actions." },
  { title: "Assign Team Members", desc: "Edit modal with AutoComplete + MultiSelect. Search master employees from HR database." },
  { title: "View Team Modal", desc: "Click a manager to see their full team list with employee codes and search." },
  { title: "Create New Manager User", desc: "Create system user as Line Manager. AutoComplete for candidate selection." },
  { title: "API Endpoints", desc: "GET /pms/employee-master, PUT /pms/employee-master/{id}, GET /pms/get-employees" },
].forEach((f, i) => {
  slide.addText(f.title, { x: 1.0, y: 2.85 + i * 0.72, w: 11.0, h: 0.25, fontSize: 12, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText(f.desc, { x: 1.0, y: 3.12 + i * 0.72, w: 11.0, h: 0.4, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });
});

// SLIDE 28 — PMS Submissions
slide = pres.addSlide();
contentHeader(slide, "HR Admin: PMS Submissions");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/hr/submissions   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/hr/SubmissionsView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "HR Head (4) only" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

addCard(slide, 0.7, 1.65, 5.8, 5.3, C.bg);
slide.addText("Page Purpose (Read-Only)", { x: 1.0, y: 1.8, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addText("View-only and print page. No approve/reject/delete actions. All submissions show status 'Submitted' (green badge). For HR Head to review and print appraisals.", { x: 1.0, y: 2.2, w: 5.2, h: 0.9, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.3 });
slide.addText("Stat Cards (3-column)", { x: 1.0, y: 3.2, w: 5.0, h: 0.3, fontSize: 12, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
["Total Submissions count","Rated submissions with avg rating > 0","Avg Rating across all rated submissions"].forEach((s, i) => {
  slide.addText(s, { x: 1.0, y: 3.6 + i * 0.35, w: 5.2, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, bullet: true, paraSpaceAfter: 2 });
});
slide.addText("Table Columns", { x: 1.0, y: 4.8, w: 5.0, h: 0.3, fontSize: 12, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText("Record ID, Employee, Department, Job Title, Avg Rating (stars), Status ('Submitted'), View button", { x: 1.0, y: 5.15, w: 5.2, h: 0.6, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.3 });

addCard(slide, 6.9, 1.65, 5.7, 5.3, C.bg);
slide.addText("Preview Sidebar & Print", { x: 7.2, y: 1.8, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
["Click View opens right sidebar with full appraisal preview","Preview: employee info, goal title, SMART badges, descriptions, quarterly tracking","Competency ratings table with self vs manager columns + weighted scores","Performance and potential summary","Comments sections (impressed most/least, HOD comments)","Print button generates multi-page A4 PDF dossier","PDF includes MELCOM HR PMS watermark, A4 sizing (210x297mm)","Clone-based printing (clones DOM, applies print styles)","Record count display replaces old filter tabs","Search by employee name or department"].forEach((f, i) => {
  slide.addText(f, { x: 7.2, y: 2.2 + i * 0.42, w: 5.0, h: 0.38, fontSize: 9.5, fontFace: "Calibri", color: C.slate, margin: 0, bullet: true, paraSpaceAfter: 2 });
});

// SLIDE 29 — Section: Profile
slide = pres.addSlide();
sectionSlide(slide, "13", "Profile & Settings", "User profile, password management, theme preferences");

// SLIDE 30 — Profile Details
slide = pres.addSlide();
contentHeader(slide, "Profile Page");
slide.addText([
  { text: "Route: ", options: { bold: true } }, { text: "/profile   |   " },
  { text: "File: ", options: { bold: true } }, { text: "views/app/ProfileView.vue   |   " },
  { text: "Access: ", options: { bold: true } }, { text: "All roles (1, 2, 3, 4)" },
], { x: 0.7, y: 1.2, w: 12, h: 0.3, fontSize: 10, fontFace: "Calibri", color: C.muted, margin: 0 });

addCard(slide, 0.7, 1.65, 5.8, 3.5, C.bg);
slide.addText("Profile Display", { x: 1.0, y: 1.8, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addText("User avatar (initial letter from name). Full name display. Role badge via findposition helper. Username and email fields. Account details from Pinia store (loguser). Read-only profile information.", { x: 1.0, y: 2.2, w: 5.2, h: 2.5, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.4 });

addCard(slide, 6.9, 1.65, 5.7, 3.5, C.bg);
slide.addText("Password Change", { x: 7.2, y: 1.8, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addText("Old password, new password, confirm password fields. Show/hide toggle for each. Client-side validation (required fields, password match). Invalid field highlighting (red border). API: POST /updatepassword. Error handling for incorrect old password. Toast notifications for feedback.", { x: 7.2, y: 2.2, w: 5.2, h: 2.5, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.4 });

addCard(slide, 0.7, 5.4, 11.9, 1.5, C.bg);
slide.addText("Logout & Theme", { x: 1.0, y: 5.55, w: 3, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addText([
  { text: "Logout: ", options: { bold: true, color: C.dark } },
  { text: "Clears localStorage, resets Pinia store, redirects to /login.   " },
  { text: "Dark Mode: ", options: { bold: true, color: C.dark } },
  { text: "Toggle in sidebar adds body.dark-mode class. CSS variables for all colors. Stored in localStorage." },
], { x: 1.0, y: 5.95, w: 11.2, h: 0.7, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.3 });

// SLIDE 31 — Section: Deployment
slide = pres.addSlide();
sectionSlide(slide, "14", "Deployment Pipeline", "Build, deploy, and route cache management");

// SLIDE 32 — Deployment Details
slide = pres.addSlide();
contentHeader(slide, "Deployment Pipeline");
addCard(slide, 0.7, 1.4, 11.9, 2.5, C.bg);
slide.addText("Deployment Flow", { x: 1.0, y: 1.55, w: 5, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
[
  { label: "Local Dev\nMachine", x: 1.0, color: C.accent },
  { label: "Share Folder\n\\\\192.168.0.24", x: 4.3, color: C.accent2 },
  { label: "Server 20\n192.168.0.20", x: 7.6, color: C.emerald },
  { label: "Route Cache\nClear", x: 10.5, color: C.amber },
].forEach((step) => {
  addCard(slide, step.x, 2.15, 2.5, 1.0, step.color);
  slide.addText(step.label, { x: step.x, y: 2.15, w: 2.5, h: 1.0, fontSize: 12, fontFace: "Calibri", bold: true, color: C.white, align: "center", valign: "middle", margin: 0 });
});
[3.5, 6.8, 10.1].forEach((x) => {
  slide.addText(">", { x, y: 2.3, w: 0.8, h: 0.7, fontSize: 28, fontFace: "Calibri", color: C.muted, align: "center", valign: "middle", margin: 0 });
});

addCard(slide, 0.7, 4.2, 5.8, 3.0, C.bg);
slide.addText("Frontend Deployment", { x: 1.0, y: 4.35, w: 5.0, h: 0.3, fontSize: 13, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText("1. npm run build (Vite production build)\n2. deploy_pms_frontend.bat (robocopy dist to share)\n3. On Server 20: robocopy from share to C:\\xampp\\htdocs\\PMS\\pms_frontend\\\n\nFlags: /MIR /MT /R:2 /W:5 /NP\nOutput: Static files served by Apache at :5050/pms_frontend/", { x: 1.0, y: 4.75, w: 5.2, h: 2.2, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.35 });

addCard(slide, 6.9, 4.2, 5.7, 3.0, C.bg);
slide.addText("Backend Deployment", { x: 7.2, y: 4.35, w: 5.0, h: 0.3, fontSize: 13, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
slide.addText([
  { text: "1. deploy_pms_test.bat (robocopy to share)\n   Excludes: vendor, node_modules, .git, storage\n2. On Server 20: robocopy from share to pms_backend\n3. Route cache clear: GET /api/run-route-clear\n\n" },
  { text: "CRITICAL: ", options: { bold: true, color: C.red } },
  { text: "Production backend is HR Onboarding/hr/hr/ NOT PMS_BACKEND_LARAVEL/ (dev-only)" },
], { x: 7.2, y: 4.75, w: 5.2, h: 2.2, fontSize: 10, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.35 });

// SLIDE 33 — App Flow Summary
slide = pres.addSlide();
contentHeader(slide, "Complete Application Flow");
[
  { num: "1", title: "Login", desc: "Authenticate > token stored > role-based redirect", y: 1.5 },
  { num: "2", title: "Dashboard", desc: "Onboarding KPIs + PMS metrics overview", y: 2.1 },
  { num: "3", title: "Create Goal", desc: "Manager selects employee > fills SMART goals > quarterly tracking", y: 2.7 },
  { num: "4", title: "Submit Goal", desc: "Goal saved > status 'submitted' > appears in appraisal queue", y: 3.3 },
  { num: "5", title: "Appraise", desc: "Manager rates 8 competencies > self + manager scores > signatures", y: 3.9 },
  { num: "6", title: "Review", desc: "4-step wizard > performance/potential assessment > HOD comments", y: 4.5 },
  { num: "7", title: "Finalize", desc: "Review completed > shows on leaderboard + submissions page", y: 5.1 },
  { num: "8", title: "HR Admin", desc: "View/print submissions, manage users, assign teams", y: 5.7 },
].forEach((step) => {
  slide.addShape(pres.shapes.OVAL, { x: 0.9, y: step.y, w: 0.45, h: 0.45, fill: { color: C.accent } });
  slide.addText(step.num, { x: 0.9, y: step.y, w: 0.45, h: 0.45, fontSize: 16, fontFace: "Calibri", bold: true, color: C.white, align: "center", valign: "middle", margin: 0 });
  slide.addText(step.title, { x: 1.6, y: step.y + 0.02, w: 2.5, h: 0.4, fontSize: 14, fontFace: "Calibri", bold: true, color: C.dark, margin: 0 });
  slide.addText(step.desc, { x: 4.2, y: step.y + 0.05, w: 8.5, h: 0.35, fontSize: 11, fontFace: "Calibri", color: C.slate, margin: 0 });
});

// SLIDE 34 — File Structure
slide = pres.addSlide();
contentHeader(slide, "Key File Structure");
addCard(slide, 0.7, 1.4, 5.8, 5.5, C.bg);
slide.addText("Frontend (Vue 3)", { x: 1.0, y: 1.55, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
["src/views/LoginView.vue","src/views/AppInterface.vue","src/views/app/DashboardView.vue","src/views/app/OnbaordingView.vue","src/views/app/EmployeesView.vue","src/views/app/EmployeeView.vue","src/views/app/UsersView.vue","src/views/app/ProfileView.vue","src/views/app/pms/DashboardView.vue","src/views/app/pms/GoalsView.vue","src/views/app/pms/AppraisalView.vue","src/views/app/pms/ReviewView.vue","src/views/app/pms/LeaderboardView.vue","src/views/app/pms/EmployeeMasterView.vue","src/views/app/hr/SubmissionsView.vue","src/components/Nav.vue","src/helpers/pms_axios.js","src/stores/user.js (Pinia)","src/router/index.js","src/data/masterdata.js"].forEach((f, i) => {
  slide.addText(f, { x: 1.0, y: 1.95 + i * 0.24, w: 5.3, h: 0.22, fontSize: 8.5, fontFace: "Courier New", color: C.slate, margin: 0 });
});

addCard(slide, 6.9, 1.4, 5.7, 3.5, C.bg);
slide.addText("Backend (Laravel)", { x: 7.2, y: 1.55, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
["HR Onboarding/hr/hr/  <- PRODUCTION","  app/Http/Controllers/PMSDashboardController.php","  app/Models/Goal.php","  app/Models/User.php","  routes/api.php","  .env (production config)","","PMS_BACKEND_LARAVEL/  <- DEV ONLY","","Key API Routes:","  POST /login","  GET /pms/dashboard","  GET /pms/goals | POST | PUT /{id}","  GET /pms/leaderboard","  GET /pms/employee-master | PUT /{id}","  GET /pms/get-employees","  GET /hr/submissions","  GET /users | POST | PUT /{id}","  POST /updatepassword","  GET /run-route-clear"].forEach((f, i) => {
  const isHeader = f.includes("PRODUCTION") || f.includes("DEV ONLY") || f.startsWith("Key");
  slide.addText(f, { x: 7.2, y: 1.95 + i * 0.22, w: 5.3, h: 0.2, fontSize: 8.5, fontFace: isHeader ? "Calibri" : "Courier New", color: isHeader ? C.dark : C.slate, bold: isHeader, margin: 0 });
});

addCard(slide, 6.9, 5.1, 5.7, 1.8, C.bg);
slide.addText("Deployment Scripts", { x: 7.2, y: 5.25, w: 5.0, h: 0.3, fontSize: 14, fontFace: "Calibri", bold: true, color: C.accent, margin: 0 });
slide.addText("deploy_pms_frontend.bat - Build + robocopy frontend\ndeploy_pms_test.bat - Robocopy backend (excludes vendor)\n.env.production - VITE_API_BASE_URL=http://192.168.0.20:5050/pms_backend/api", { x: 7.2, y: 5.65, w: 5.2, h: 1.0, fontSize: 9, fontFace: "Calibri", color: C.slate, margin: 0, lineSpacingMultiple: 1.4 });

// SLIDE 35 — Closing
slide = pres.addSlide();
slide.background = { color: C.navy };
slide.addText("HR PORTAL", { x: 0.7, y: 0.5, w: 3, h: 0.5, fontSize: 14, fontFace: "Calibri", bold: true, color: C.ice });
slide.addText("Thank You", { x: 0.7, y: 2.5, w: 11, h: 1.2, fontSize: 48, fontFace: "Calibri", bold: true, color: C.white });
slide.addText("Melcom HR Portal - Complete Developer Walkthrough", { x: 0.7, y: 3.8, w: 10, h: 0.5, fontSize: 18, fontFace: "Calibri", color: C.ice });
slide.addText("For questions or updates, contact the IT Software Development Team", { x: 0.7, y: 4.6, w: 10, h: 0.4, fontSize: 14, fontFace: "Calibri", color: C.muted });
slide.addText("Version 1.0  |  July 2026", { x: 0.7, y: 6.5, w: 10, h: 0.4, fontSize: 11, fontFace: "Calibri", color: C.muted });

// ── Save ──
const path = require("path");
const outPath = process.argv[2] || path.join(__dirname, "HR_Portal_Developer_Walkthrough.pptx");
pres.writeFile({ fileName: outPath })
  .then(() => console.log("Created: " + outPath))
  .catch(err => console.error("Error:", err));

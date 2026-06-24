# PMS Project Enhancements - Changelog

This document summarizes the comprehensive UI/UX enhancements and feature updates implemented in the Performance Management System (PMS) project.

## 1. Dashboard Redesign (`DashboardView.vue`)

A complete overhaul of the Admin Dashboard to achieve a modern, premium aesthetic.

- **Modern UI**: Implemented a clean, card-based layout with ample whitespace and subtle shadows.
- **KPI Cards**:
  - Designed new cards for "Completion Rate", "Happiness Score", "Active Goals", and "Completed Goals".
  - Added visual trend indicators (e.g., "vs last period").
- **Performance Chart**:
  - Replaced the legacy chart with a sleek **Gradient Area Chart** using `VueApexCharts`.
  - Styled with a purple gradient theme to match the new branding.
- **New Widgets**:
  - **Employee Leaderboard**: Displays top-performing employees with avatars, roles, and scores.
  - **Activity Widgets**: "Most Active Days" (Bar Chart) and "Completion Rate" (Radial Bar Chart).
- **Contrast Improvements**: Enhanced text legibility and contrast ratios throughout the dashboard.

## 2. Goals Management (`GoalsView.vue`)

Refined the Goals Creation and Management flow for better usability and professional presentation.

- **Step 2 Redesign**:
  - **Goal Summary Card**: Created a visually rich summary card featuring a large background icon, clear goal title, description, and SMART criteria badges.
  - **Quarterly Tracking Attachments**:
    - Users can now upload **Attachments** (PDF, Excel, Images) for each quarterly milestone.
    - Added instant file upload handling and viewable links for uploaded evidence.
  - **Quarterly Tracking**: Transformed the tracking table into **Responsive Cards** for each quarter (Q1-Q4). This improves readability and data entry on all device sizes.
  - **Input Enhancements**:
    - Fixed text visibility issues by enforcing `text-gray-900` on all input fields.
    - Added consistent styling (rounded corners, focus states) to all form inputs.
- **Layout Optimization**:
  - Updated the main grid layout to a **4-column system** (3 columns for content, 1 column for the sidebar), providing more breathing room for the main form.
- - **Editable Signatures**:
    - Converted "Candidate Name" and "Line Manager Name" from static text to editable input fields, defaulting to the logged-in user and their manager.
    - **Dynamic Fields**:
    - Enhanced "Purposes", "Challenges", and **"Description (Objectives)"** sections to support **multiple dynamic entries**.
    - Added "Add (+)" and "Remove (Trash)" buttons for easy list management.
    - Implemented frontend serialization to store these lists compatibly with the backend.

## 3. User Management (`UsersManageView.vue`)

Enhanced the User Administration interface to align with the new professional design language.

- **Professional Tabs**:
  - Replaced standard text links with **Pill-Shaped Buttons**.
  - **Active State**: Solid dark background (gray-900) with **white text** for clear visibility.
  - **Inactive State**: Clean white background with a subtle border.
- **Load More Button**:
  - Redesigned the "Load More" button to be a full-width, rounded container with an expansion icon.
- **Consistent Headers**:
  - Unified the styling of card headers across "Employee Details", "Records Verification", and "Assessment" sections, adding consistent iconography and spacing.

## 4. Sidebar Navigation (`Nav.vue`)

Updated the application branding.

- **Logo Update**: Removed the legacy image logo.
- **New Branding**:
  - Stylized "PMS" text to be **Red**, large, and bold (`Arial Black` font).
  - Implemented a compact "P" logo for the collapsed sidebar state.

## 5. General Improvements

- **Lint Fixes**: Resolved various CSS linting warnings (redundant `block` on `flex` items).
- **Code Quality**: Refactored grid layouts and component structures for better maintainability.

## 6. Appraisal View & System (`AppraisalView.vue`)

- **Drafts System**:
  - Implemented a dedicated **Drafts Table** in the database to separate incomplete forms from official records.

  - **Auto-Save/Resume**: Users can now "Save Draft" at any time. The system automatically restores their progress when they return to the page.
  - **Submission Flow**: Submitting an appraisal now finalizes the record and clears the temporary draft.

- **Signatures Update**:
  - Replaced static signature placeholders with **Interactive Text Inputs** for Candidate and Line Manager names.
  - Added **Read-Only Date Fields** that automatically populate with the current date to ensure accuracy.
- **Competency Ratings**:
  - Enabled the **Line Manager Rating** column, allowing managers to input their scores (1-5) directly in the table.
  - **Percentage-Based Scoring**: Updated the calculation logic to normalize scores to a **0-100% scale**.
    - Formula: `(Rating / 5) * Weight`.
    - Example: A 5/5 rating on a 20% weight item now yields result of "20%" (max score) instead of "1.0".
  - The "Line Manager's Weighted Score" now dynamically recalculates based on the manager's input.
- **UI Cleanup**:
  - Removed the redundant "1-5 Rating Reference Guide" table to declutter the view.

## 7. Review Page Refactor (`ReviewView.vue`)

- **Dynamic Sections**:
  - Converted static input fields (Sections B-E) into **Dynamic Lists**. Users can now add (+) or remove (-) items as needed.
- **Goal Achievement Table**:
  - Refactored "Goal Achievement vs. Target" (Section A) into a structured **Table Layout** with dedicated columns for "Target" and "Achievement".
- **Backend Integration**:
  - Created persistent storage for reviews using JSON columns to support flexible data structures.

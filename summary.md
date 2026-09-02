# SharePlate - Development Summary

This document serves as a log of all major tasks and features completed in the SharePlate project during this development session.

## 1. Donor Dashboard
- **`dashboard.php` & `dashboard.css`**: Created a responsive donor dashboard tailored for food donors (restaurants, grocers).
- **Features included**:
  - Custom sidebar navigation (Overview, Donations, Inventory, Impact, Community).
  - Key performance indicators (Total Donated, People Helped).
  - Dynamic "Impact Hero" card with call-to-action buttons.
  - "Recent Community Activity" feed with colored status badges (Completed, In Transit, Expired).
  - Interactive "Nearby Needs" map widget and real-time notification alerts.
  - Dark mode support built-in.

## 2. Signup Tab Fixes
- **`signup.php` & `script.js`**: Resolved an issue where the "I want to Donate" and "I need Food" tabs were unresponsive.
- **Fixes applied**: Added `type="button"` to prevent unintended browser defaults and added `e.preventDefault()` to the JavaScript event listeners to guarantee smooth state toggling and hidden input updates.

## 3. Account Email Verification Flow
- **`process_signup.php`, `verify.php`, `verify_code.php`**: Built a secure, multi-step account creation process.
- **Features included**:
  - **PHPMailer Integration**: Set up SMTP securely using Composer to dispatch emails.
  - **Custom HTML Emails**: Built a branded email template that sends a dynamically generated 6-digit verification code.
  - **Secure Sessions**: Instead of writing unverified users to the database immediately, data is held securely in `$_SESSION` until the verification code is successfully validated.
  - **Database Integration**: Validated codes trigger a secure insertion into the `share_plate.users` table with a hashed password.

## 4. Forgot Password & Recovery Flow
- **`forgot_password.php`, `verify_reset.php`, `reset_password.php` (and process scripts)**: Implemented a robust 3-step password recovery system.
- **Features included**:
  - Linked the "FORGOT PASSWORD?" button on the login page to the recovery flow.
  - Form to accept the user's email, verify it against the database, and send a custom 6-digit recovery code via PHPMailer.
  - Form to capture and validate the recovery code using session-backed security.
  - Form to securely capture, confirm, and hash a new password, updating the existing record in the database.

## 5. Global Password Visibility (Eye Button) Fixes
- **Site-wide Fix**: Corrected logic in `script.js` to ensure clicking the eye icon successfully toggles the HTML input type between `password` and `text`, updating the icon class simultaneously.
- **`reset_password.php` Fix**: Removed redundant inline scripts that were conflicting with the global `script.js` logic, allowing the new password form to toggle visibility smoothly.

## 6. Food Donation Posting & Database Architecture
- **Database Expansion**: Created the `admin` and `food_listings` tables to meet core project criteria.
- **`post_food.php` & `process_post_food.php`**: Built a comprehensive and secure food listing submission flow handling text, metadata, and image uploads.
- **Enhanced UX**: Implemented real-time JavaScript image preview for the dropzone, giving instant visual feedback to users.
- **Data Completeness**: Added highly relevant `pickup_location` and `contact_info` fields to the form and database for better donation logistics.

## 7. Dynamic Dashboard & Freshness Engine
- **Data Integration**: Refactored `dashboard.php` to fetch actual database entries rather than displaying static HTML cards.
- **Freshness Algorithm**: Built custom logic to automatically calculate and assign visual status colors (Green, Yellow, Red) based on the food's specific category, time elapsed since creation, and hours until expiry.
- **Notification Upgrade**: Replaced the static notification center with a dynamic, toggleable popup triggered by the bell icon.

## 8. Active Listings & History Views
- **`active_listings.php`**: Created a dedicated page to filter and display only `Available` donations, using a clean, centralized layout (`.post-food-content`).
- **`history.php`**: Created a comprehensive log of all past donations, utilizing CSS filters to visually fade out items that are no longer available (e.g., claimed or expired).
- **Navigation Sync**: Updated sidebar and header links site-wide to ensure seamless navigation between the Overview, Active Listings, History, and Post Food interfaces.

## 9. Sidebar Centralization & Profile Updates
- **`sidebar.php`**: Extracted the sidebar navigation into a modular PHP include, ensuring consistent layout and active-state tracking across all dashboard pages.
- **`profile.php`**: Redesigned the user profile page from a basic layout to a highly visual, premium card-based layout without unnecessary icons, matching user preferences.

## 10. Messaging Infrastructure
- **Database Expansion**: Created `conversations` and `messages` tables to handle peer-to-peer user communication.
- **`messages.php`**: Built a comprehensive real-time chat UI featuring a conversation list on the left and a detailed chat window on the right. 
- **Features**: Includes read/unread statuses, dynamic timestamps, and smooth message rendering.

## 11. Settings, Analytics & Flowcharts
- **`settings.php`**: Built a comprehensive settings management page including Profile edits, Password changing, and a fully functional Dark Mode toggle linked to the global navigation state. Added `phone_number` and `organization_name` to the user schema.
- **`analytics.php`**: Implemented a data-rich analytics view featuring KPI cards and chart placeholders, stripped of basic icons for a cleaner, data-first look.
- **`how_it_works.php`**: Engineered a pure-CSS UML Activity Diagram wrapped in a premium background card. Refined the flexbox/grid layout and connection arrows to ensure flawless node alignment and responsive scaling without overlap.

## 12. Public & Private Food Marketplace
- **`marketplace.php`**: Engineered a sophisticated "dual-layout engine" that serves as the main food browsing hub.
  - **Logged-Out View**: Renders the public `index.php` navbar and footer seamlessly, pushing the 4-column CSS grid below the fixed header for anonymous visitors.
  - **Logged-In View**: Automatically detects the session and wraps the content in the secure Dashboard sidebar layout, gracefully dropping to a 3-column CSS grid.
- **UI Enhancements**: 
  - Horizontal pill-based category filters.
  - Dynamic expiration tags (Very Fresh, Expiring Soon, Urgent) calculated precisely via PHP `DateTime` diffs.
  - Premium cards with side-by-side "Message" and "View Details" action buttons, thoroughly tested against Dark Mode styles.
- **`logout.php` Security Fix**: Replaced the static HTML logout link with a robust server-side script that explicitly unsets session arrays, destroys the session, and forcefully expires the `PHPSESSID` browser cookie to guarantee state integrity.

## 13. Marketplace UI Refinements
- **`marketplace_content.php`**: Redesigned the food card UI for a minimalistic, dashboard widget vibe. Grouped location and post date on a single subtle line, and moved quantity and expiration into a structured 2-column grid.
- **Advanced Filtering**: Upgraded from simple pill buttons to a dual-dropdown filtering system (Category & Status). Implemented Javascript logic to dynamically show/hide a "Discover More Items" button based on visible results.
- **Layout Stability**: Fixed layout shifts by enforcing `width: 100%` on the marketplace wrapper and sticking the filter dropdowns firmly to the right edge (`justify-content: flex-end`), preventing horizontal jumping when filtering results. Fixed PHP timezone configurations to ensure expiration timers are accurate to `Asia/Dhaka`.

## 14. Secure Food Claiming Workflow
- **Database Expansion**: Engineered the `food_claims` table to track food_id, receiver_id, statuses, and custom messages, establishing the foundation for receiver history tracking.
- **`food_details.php` UI**: Stripped out legacy serif fonts to maintain the site's modern sans-serif aesthetic. Built a beautifully animated, hidden modal form that allows receivers to add an optional message when claiming food.
- **`process_claim.php`**: Developed a secure backend script to handle form submissions. It validates the food's availability, blocks users from claiming their own food, checks for duplicate claims, securely inserts the claim via database transactions, increments the listing's claim count, and dispatches a real-time notification to the donor.

## 15. Advanced Claim & Notification Workflows
- **Request Management**: Built the `incoming_requests.php` page allowing donors to view and manage pending claims. Claims are processed via `process_request.php` supporting both 'Approve' and 'Reject' actions.
- **Dynamic Routing & UI Fixes**: Fixed the donor's top widget card to strictly show 'Pending' listings, hiding approved/completed items. Extended the receiver's claim form to require and store phone number, address, and reason for claiming.
- **Notification Engine Improvements**: Fixed timestamp accuracy issues in `notifications.php` (converting PHP DateTime differences correctly into human-readable strings). Set up bi-directional notification dispatches so receivers are alerted when their claims are approved.

## 16. System Admin Portal & Analytics
- **Role-Based Access Control**: Implemented comprehensive logic to handle `role_id = 3` (System Admin) across the platform, including dual-table auth checks in `login.php` and `process_forgot_password.php`.
- **User Management System**: Engineered `admin_users.php`, `admin_view_user.php`, and `admin_user_actions.php`. Admins can view detailed user metrics, safely toggle account statuses (Active/Suspended), or perform secure, cascading deletions of users and all their associated data.
- **System-Wide Dashboard & Analytics**: Transformed the admin dashboard and `admin_analytics.php` from donor copies into true platform overviews. Added system-level KPIs (Total Users, Total Donors) and built dynamic Chart.js area charts plotting 'New Signups', 'New Listings', and 'Claims Processed' over 30-day windows.
- **Profile Enhancements**: Enabled users to add and manage their `organization_name` via the `settings.php` UI, keeping data consistent with the Admin's detailed view.

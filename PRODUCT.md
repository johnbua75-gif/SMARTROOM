# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- Primary users: students, faculty, staff, and administrators in a campus or academic environment.
- Students use the system to view enrolled courses, room schedules, attendance records, and check in for class sessions.
- Faculty use it to manage teaching schedules, room reservations, attendance sessions, AI-assisted scheduling guidance, and room access verification.
- Staff and administrators manage room access, user onboarding, and operational records.

## Product Purpose

SmartRoom is a campus operations and access platform for managing classroom scheduling, room reservations, attendance tracking, and secure access control for academic spaces. The product exists to give institutions a single workflow for handling room usage, user access, and attendance in a way that supports both teaching operations and physical security.

Success means that room bookings, attendance sessions, and access checks are reliably recorded, visible to the correct roles, and protected by role-based authorization and temporary credential onboarding.

## Positioning

SmartRoom is not a generic room-booking tool or a general LMS. Its differentiator is the combination of academic scheduling, attendance workflows, secure room access, and RFID/fingerprint logging in a single institution-focused system. It is positioned around operational control for classroom environments and the enforcement of access policy in those spaces.

## Operating Context

- The application runs as a Laravel web application with a PostgreSQL-backed data model.
- It is designed for a university or campus environment where different roles interact with the same academic infrastructure.
- Room and schedule operations are built around semester or course-based workflows.
- Access control is integrated with ESP32/Arduino smart-lock devices and RFID/fingerprint event logging.
- User onboarding includes admin-created accounts with temporary passwords and forced password changes before access is granted.

## Capabilities and Constraints

Confirmed capabilities include:

- Student course enrollment and schedule viewing
- Faculty schedule management and reservation handling
- Attendance tracking with QR-based or card-triggered check-in flows
- Room and classroom reporting/export features
- Admin user creation with temporary credentials and email delivery
- RFID and fingerprint access logging for physical space access
- ESP32 smart-lock integration via API endpoints
- Role-based access control for students, faculty, staff, and administrators

Confirmed constraints and operational requirements include:

- The app uses Laravel authentication and role middleware; protected pages require password-change completion.
- The database is configured for PostgreSQL/Supabase, with a `public` schema and SSL requirement.
- The system supports API access for smart lock hardware and expects authentication tokens for device requests.
- The product is expected to preserve institutional workflows and role separation rather than a consumer-facing general-use pattern.

## Brand Commitments

- Product name: SmartRoom
- The system presents itself as a practical institutional operations platform rather than a consumer product.
- Existing language in the codebase and README frames the product around campus access, attendance, and room management.

## Evidence on Hand

- Project README at `README.md`
- Route definitions in `routes/web.php`
- Laravel application structure and dependencies in `composer.json`
- Supporting project documentation files such as `ENROLLMENT_API.md`, `FEATURE_ADD_STUDENT_EMAIL.md`, and ESP32 integration documents in the repository root

## Product Principles

1. Secure access is a first-class requirement, not an afterthought.
2. Role clarity must define what each user can see and do.
3. Academic operations should be visible, traceable, and exportable.
4. Physical-space access and digital scheduling are linked parts of the same workflow.
5. Institution-specific workflows should remain explicit and dependable across student, faculty, and staff actions.

## Accessibility & Inclusion

- The app includes standard auth and password-change flows intended to support onboarding and account compliance.
- No separate product-specific accessibility requirement is currently documented beyond the app’s general role-based and operational design goals.

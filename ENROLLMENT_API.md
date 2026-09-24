# Student Enrollment API Documentation

## Overview
The enrollment API allows admins to manually enroll students in courses without relying on block_section matching.

## Base URL
```
/api/v1/enrollments
```

## Endpoints

### 1. Single Student Enrollment
**Endpoint:** `POST /api/v1/enrollments`

**Description:** Enroll a single student in a course.

**Request Body:**
```json
{
  "student_id": 2,
  "course_id": 5,
  "status": "active"
}
```

**Parameters:**
- `student_id` (required): The ID of the student
- `course_id` (required): The ID of the course
- `status` (optional): Enrollment status - `active`, `inactive`, or `suspended` (default: `active`)

**Response (Success - 200):**
```json
{
  "data": {
    "id": 1,
    "student_id": 2,
    "course_id": 5,
    "enrolled_at": "2026-05-09T12:34:56.000000Z",
    "status": "active",
    "student": {
      "id": 2,
      "name": "Lebron James",
      "student_id": "STU-54321",
      "email": "lebron@example.com",
      "block_section": null
    },
    "course": {
      "id": 5,
      "code": "CS101",
      "title": "Systems Analysis",
      "instructor_user_id": 1
    },
    "created_at": "2026-05-09T12:34:56.000000Z",
    "updated_at": "2026-05-09T12:34:56.000000Z"
  }
}
```

**Response (Conflict - 409 - Already Enrolled):**
```json
{
  "message": "Student already enrolled in this course",
  "enrollment": { ... }
}
```

---

### 2. Bulk Enroll Multiple Students
**Endpoint:** `POST /api/v1/enrollments/bulk`

**Description:** Enroll multiple students in the same course.

**Request Body:**
```json
{
  "student_ids": [2, 3, 4, 5],
  "course_id": 5
}
```

**Parameters:**
- `student_ids` (required): Array of student IDs to enroll
- `course_id` (required): The ID of the course to enroll them in

**Response (Success - 200):**
```json
{
  "message": "Bulk enrollment completed",
  "results": {
    "enrolled": [
      {
        "student_id": 2,
        "student_name": "Lebron James",
        "enrollment_id": 1
      },
      {
        "student_id": 3,
        "student_name": "Stephen Curry",
        "enrollment_id": 2
      }
    ],
    "failed": [
      {
        "student_id": 999,
        "reason": "Student not found"
      }
    ],
    "already_enrolled": [
      {
        "student_id": 4,
        "student_name": "Kevin Durant",
        "enrollment_id": 10
      }
    ]
  },
  "summary": {
    "total_students": 4,
    "enrolled": 2,
    "failed": 1,
    "already_enrolled": 1
  }
}
```

---

### 3. Remove Student Enrollment
**Endpoint:** `DELETE /api/v1/enrollments/{enrollment_id}`

**Description:** Unenroll a student from a course.

**Parameters:**
- `enrollment_id` (required): The ID of the enrollment record to delete

**Response (Success - 200):**
```json
{
  "message": "Student unenrolled successfully"
}
```

---

## Usage Examples

### Example 1: Enroll One Student
```bash
curl -X POST http://localhost:8000/api/v1/enrollments \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 2,
    "course_id": 5
  }'
```

### Example 2: Bulk Enroll Multiple Students
```bash
curl -X POST http://localhost:8000/api/v1/enrollments/bulk \
  -H "Content-Type: application/json" \
  -d '{
    "student_ids": [2, 3, 4, 5, 6],
    "course_id": 5
  }'
```

### Example 3: Unenroll a Student
```bash
curl -X DELETE http://localhost:8000/api/v1/enrollments/1
```

---

## Command-Line Alternative

You can also use the Artisan command for manual enrollment:

```bash
# Auto-enroll by block_section matching
php artisan enroll:auto-by-block

# With dry-run to preview changes
php artisan enroll:auto-by-block --dry-run
```

---

## How It Works

1. **Student Visibility**: Once enrolled, students will only see the courses they're enrolled in on their schedule page
2. **Schedule Display**: The student's schedule page automatically fetches all schedules for their enrolled courses
3. **No Block Matching Required**: Unlike the block_section method, this API enrolls students directly regardless of their block_section value

---

## Status Code Reference

| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Bad Request (missing required fields) |
| 404 | Not Found (student or course doesn't exist) |
| 409 | Conflict (student already enrolled) |
| 422 | Unprocessable Entity (validation error) |

---

## Implementation in Admin Panel

To add this to your admin dashboard:

1. **Single Enrollment Form:**
   - Select Student dropdown
   - Select Course dropdown
   - Enroll button → POST to `/api/v1/enrollments`

2. **Bulk Enrollment Form:**
   - Multi-select Students
   - Select Course
   - Bulk Enroll button → POST to `/api/v1/enrollments/bulk`

3. **Current Enrollments:**
   - List student enrollments
   - Delete button → DELETE to `/api/v1/enrollments/{id}`

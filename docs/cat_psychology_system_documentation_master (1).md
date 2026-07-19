# CAT Psychology Examination System Documentation

## Technology Stack

- Laravel 12
- PHP 8.2
- MySQL / MariaDB
- Blade Template Engine
- Alpine.js
- Livewire (Admin Dashboard Only)
- AJAX / Fetch API
- Shared Hosting Compatible

---

# 01 — Project Overview

## Project Name

Psychology Online Examination System

## Description

Web-based psychology examination system similar to CAT (Computer Assisted Test) for psychological assessments.

The system is designed for:

- Doctors/Admins
- Participants

Participants answer psychology questions using:

- YES
- NO

The system only stores and displays participant answers.

The system DOES NOT:

- calculate score
- perform psychological interpretation
- generate diagnosis
- generate AI analysis

## Participant Account System

Participants have dedicated accounts.

Participant accounts are used to store:

- National Identity Number (NIK)
- Full name
- Birth date
- Gender
- Additional participant profile data for future needs

Participants authenticate using their account credentials.

Exam access itself uses session token validation.

---

## Main Goals

- Stable exam system
- Lightweight architecture
- Shared hosting compatible
- One-time participant attempt
- Autosave answer system
- Session-based examination
- Minimalist UI focused on examination

## Main Features

### Frontend Architecture

### Participant Examination Interface

Participant exam pages use:

- Blade
- Alpine.js
- Fetch API AJAX

Reason:

- lightweight
- predictable state handling
- stable autosave behavior
- shared hosting friendly
- easier AI code generation

### Doctor/Admin Dashboard

Doctor dashboard may use Livewire for:

- CRUD tables
- filtering
- searching
- monitoring interface

Livewire must NOT be used for participant exam engine.

---

## Doctor Features

- Manage sessions
- Manage participants
- Manage questions
- Monitor participant progress
- View participant answers
- Export result data

### Participant Features

- Login using participant account
- Access exam session using token
- Start exam
- Answer YES/NO questions
- Automatic answer saving
- Resume exam session
- Submit exam

---

# 02 — Business Rules

## Core Rules

0. Participant must have account before accessing exam system.

1. Participant can only have one attempt.
2. Participant cannot access another session.
3. Submitted attempt cannot be reopened.
4. Answers are autosaved using AJAX.
5. Timer continues even if browser closes.
6. Session validity is based on server time.
7. Participant may continue unfinished attempt.
8. One participant can only access one active session.
9. Doctor can only view results.
10. System does not calculate score.
11. Question order is fixed unless randomization is enabled.
12. Autosave must be idempotent.
13. Submitted answers become immutable.
14. Session access expires after session end time.
15. Participant answers must preserve question snapshot.

## Exam Rules

1. Participant must login using participant account.
2. Participant must enter valid exam token.
2. Participant can only access assigned session.
3. Participant cannot create multiple attempts.
4. Attempt starts when participant enters exam page.
5. Exam automatically ends after duration expires.
6. Participant may navigate between questions.
7. Autosave occurs immediately after answer selection.
8. Submit action permanently locks exam.

## Security Rules

1. All exam routes must use ExamGuard middleware.
2. CSRF protection must be enabled.
3. Duplicate submit must be prevented.
4. Session token must be validated.
5. Session expiration must use server-side validation.
6. Participant cannot access another participant data.

---

# 03 — User Roles

## Doctor / Admin

### Permissions

- Create session
- Edit session
- Delete session
- Create participant
- Edit participant
- Manage questions
- View participant answers
- Monitor active participants
- Export result data

## Participant

### Permissions

- Login
- Start exam
- Answer questions
- Resume active exam
- Submit exam

### Livewire Restrictions

- Do not use Livewire for exam answering page.
- Do not use Livewire realtime binding for autosave.
- Do not use Livewire for timer synchronization.
- Exam autosave must use manual AJAX.
- Exam timer must use frontend JavaScript.

## Restrictions

- Cannot access admin area
- Cannot reopen submitted exam
- Cannot access another session
- Cannot create second attempt

---

# 04 — Authentication Flow

## Participant Authentication Flow

1. Participant logs into participant account.
2. Participant enters exam token.
3. System validates exam token.
4. System validates assigned session.
5. System validates active session time.
6. System checks existing attempt.
7. If no attempt exists:
   - create attempt
8. If active attempt exists:
   - resume existing attempt
9. If attempt already submitted:
   - deny access
10. System generates session token.
11. Redirect participant to exam page.

## Doctor Authentication

Doctor authentication uses Laravel Breeze.

---

# 05 — Session Management

## Session Rules

- Exam session access uses token validation.
- Participant authentication uses participant account.
- Token only grants access to assigned exam session.

- Only one active attempt per participant.
- Browser reconnect is allowed.
- Session resume is allowed.
- Attempt is locked after submit.
- Session expires after session end time.
- Timer continues even if browser closes.
- Session uses server-side validation.

## Resume Rules

Participant may continue exam if:

- attempt not submitted
- session still active
- exam duration not expired

## Session Lock Rules

Session is locked using:

- session token
- browser session
- participant ID
- active attempt validation

---

# 06 — Exam Flow

## Full Exam Flow

```text
Login
→ Validate participant
→ Validate session
→ Create/Resume attempt
→ Enter exam page
→ Answer questions
→ AJAX autosave
→ Submit exam
→ Lock attempt
→ Exam completed
```

## Submit Flow

1. Participant clicks submit.
2. System validates session.
3. System validates attempt.
4. System locks attempt.
5. System records finished_at.
6. Participant redirected to completion page.

---

# 07 — Autosave System

## Autosave Rules

- Save occurs immediately after answer selection.
- Save uses AJAX.
- Backend uses updateOrCreate.
- Failed save must display warning.
- Submit does not save answers again.
- Autosave must be idempotent.

## Autosave API Request Example

```json
{
  "question_id": 15,
  "answer": true
}
```

## Autosave API Response Example

```json
{
  "success": true
}
```

## Frontend Behavior

Frontend should display:

- Saving...
- Saved
- Failed to save

## Recommended Strategy

- Use Fetch API for autosave requests.
- Avoid Livewire autosave lifecycle.
- Save on answer selection
- Avoid save on next page
- Avoid batching answers

---

# 08 — Database Schema

## Table: users

| Column | Type |
|---|---|
| id | bigint |
| name | string |
| email | string |
| password | string |
| timestamps | timestamps |

---

## Table: exam_sessions

| Column | Type |
|---|---|
| id | bigint |
| title | string |
| start_time | timestamp |
| end_time | timestamp |
| duration_minutes | integer |
| status | boolean |
| timestamps | timestamps |

---

## Table: participants

| Column | Type |
|---|---|
| id | bigint |
| session_id | bigint |
| nik | string |
| username | string |
| password | string |
| code | string |
| name | string |
| birth_date | date |
| gender | enum |
| phone | string nullable |
| address | text nullable |
| login_token | string nullable |
| is_finished | boolean |
| timestamps | timestamps |

---

## Table: questions

| Column | Type |
|---|---|
| id | bigint |
| category | string |
| question | text |
| order_no | integer |
| is_active | boolean |
| timestamps | timestamps |

---

## Table: exam_attempts

| Column | Type |
|---|---|
| id | bigint |
| participant_id | bigint |
| session_id | bigint |
| started_at | timestamp |
| finished_at | timestamp |
| current_question | integer |
| is_submitted | boolean |
| session_token | string |
| ip_address | string |
| user_agent | text |
| timestamps | timestamps |

---

## Table: participant_answers

| Column | Type |
|---|---|
| id | bigint |
| attempt_id | bigint |
| question_id | bigint |
| question_snapshot | text |
| answer | boolean |
| answered_at | timestamp |
| timestamps | timestamps |

---

## Recommended Indexes

### participant_answers

```sql
INDEX idx_attempt_question (
    attempt_id,
    question_id
)
```

### exam_attempts

```sql
INDEX idx_participant_session (
    participant_id,
    session_id
)
```

---

# 09 — API Specification

## POST /exam/save-answer

### Request

```json
{
  "question_id": 1,
  "answer": true
}
```

### Response

```json
{
  "success": true
}
```

---

## POST /exam/submit

### Response

```json
{
  "success": true,
  "message": "Exam submitted successfully"
}
```

---

# 10 — Security Rules

## Security Policies

- All exam routes require ExamGuard middleware.
- CSRF protection enabled.
- Submitted attempts are immutable.
- Duplicate submit prevented.
- Session validated on every request.
- Session expiration validated server-side.

## Anti-Cheat Strategy

### Enabled

- Session validation
- Autosave
- One attempt only
- Session locking
- Timer validation

### Optional

- Fullscreen mode
- Multiple tab detection
- Browser focus warning

---

# 11 — Folder Architecture

```text
app/
├── Actions/
├── DTOs/
├── Repositories/
├── Services/
├── Policies/
├── Models/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Responses/
```

---

# 12 — Route Structure

## Public Routes

```php
Route::get('/login');
Route::post('/login');
```

## Exam Routes

```php
Route::prefix('exam')
    ->middleware('exam.guard')
    ->group(function () {

    Route::get('/');
    Route::post('/save-answer');
    Route::post('/submit');
});
```

## Doctor Routes

```php
Route::middleware('auth')
    ->prefix('admin')
    ->group(function () {

    Route::resource('sessions');
    Route::resource('participants');
    Route::resource('questions');
});
```

---

# 13 — Service Architecture

## Recommended Services

### Exam Services

- StartExamService
- ResumeAttemptService
- SaveAnswerService
- SubmitExamService
- ValidateSessionService

### Doctor Services

- CreateSessionService
- ImportQuestionService
- ExportResultService

---

# 14 — UI Guidelines

## Frontend Technology Rules

### Participant Area

Use:

- Blade
- Alpine.js
- Fetch API

Avoid:

- Livewire exam component
- Reactive realtime synchronization
- Heavy frontend framework

### Admin Area

Allowed:

- Livewire
- Blade
- Alpine.js

---

## General UI Rules

- Minimalist CAT style
- Large typography
- Large answer buttons
- Minimal animation
- Full focus on question
- Responsive layout

## Participant UI Rules

- One question focus
- High readability
- Visible timer
- Clear progress indicator
- Accessible button spacing

## Doctor Dashboard Rules

- Data table focused
- Fast navigation
- Lightweight interface
- Minimal chart usage

---

# 15 — Deployment Guidelines

## Hosting Target

Shared hosting compatible.

## Requirements

- PHP 8.2
- MySQL / MariaDB
- Composer support
- Cron support

## Recommended Optimizations

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Avoid

- Redis dependency
- Websocket dependency
- Docker requirement
- Heavy realtime processing

---

# 16 — AI Coding Rules

## General Rules

- Use Laravel 12.
- Use PHP 8.2 syntax only.
- Use Blade + Alpine.js for participant exam pages.
- Livewire may be used only for admin dashboard pages.
- Use MySQL compatible queries.
- Use Eloquent ORM.
- Use FormRequest validation.
- Use Service Pattern.
- Use Repository Pattern when needed.
- Shared hosting compatible only.

## Restrictions

- Do not use Livewire.
- Do not use Inertia.
- Do not use Vue SPA.
- Do not use React SPA.
- Do not use Redis as mandatory dependency.
- Do not use websocket as mandatory dependency.
- Do not overengineer architecture.

## Coding Style

- Use strict typing.
- Use single responsibility principle.
- Use clear naming convention.
- Use RESTful routes.
- Use dependency injection.

---

# 17 — Naming Convention

## Controller

- Example: ExamController

## Service

- Example: SaveAnswerService

## Repository

- Example: ParticipantRepository

## Action

- Example: StartExamAction

## DTO

- Example: SaveAnswerDTO

---

# 18 — Recommended Development Workflow

## Phase 1

Generate:

- migrations
- models
- relationships

## Phase 2

Generate:

- authentication
- middleware
- session management

## Phase 3

Generate:

- exam flow
- autosave system
- submit logic

## Phase 4

Generate:

- doctor dashboard
- monitoring
- export

## Phase 5

Generate:

- UI improvements
- optimization
- testing

---

# 19 — Recommended Future Features

## Optional Features

- Excel import
- PDF export
- Randomized questions
- Fullscreen mode
- Advanced monitoring
- Statistics dashboard
- Psychological scoring engine
- AI analysis system

---

# 20 — Important Notes

## Most Critical Components

1. Business rules
2. Session management
3. Autosave system
4. Middleware protection
5. Attempt locking

## Recommended MVP Scope

Focus only on:

- participant login
- exam session
- question answering
- autosave
- submit
- doctor result view

Avoid adding advanced features too early.


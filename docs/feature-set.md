# Feature Set Documentation

## Overview

PRF Core is a Laravel API and admin platform for planning, executing and reporting ministry work across schools, cohorts and fellowship events.

This document summarizes the feature capabilities currently represented by:

- API routes in `routes/api/v1.php` and `routes/api/v2.php`
- API controllers in `app/Http/Controllers/API/`
- Filament resources in `app/Filament/Resources/`

## Core Domain Features

### 1) Identity and Access

- Member and student authentication (login, register, social login)
- Profile lifecycle for authenticated users:
  - View self profile
  - Update profile
  - Delete student profile
- Sanctum-protected API access for authenticated operations
- API throttling on sensitive endpoints such as auth and webhooks

### 2) Mission Planning and Execution

- Full mission lifecycle management:
  - Create, update, delete missions
  - Approve, reject, cancel and complete missions
- Mission operations and automations:
  - Export mission schedule
  - Notify school
  - Request feedback
  - Queue WhatsApp group notifications
  - Generate mission summary
  - Upload mission assets to cloud drive
  - Auto-create zero requisition
- Mission participants and logistics:
  - Mission subscriptions
  - Offline mission members
  - Mission sessions and transcripts
  - Mission FAQs and FAQ categories
  - Mission questions
  - Mission ground suggestions

### 3) School and Outreach Data Management

- School CRUD management
- School contact management
- Contact type catalog management
- School term management
- Mission type management
- Institutional and demographic catalogs:
  - Churches
  - Marital statuses
  - Professions
  - Spiritual years

### 4) Member, Group and Discipleship Management

- Member management and engagement tracking
- Group and group-member management
- Class groups
- Membership records
- Cohort management and cohort-mission linking
- Letters and cohort letters
- Learning and discipleship workflows:
  - Courses
  - Course modules
  - Course members
  - Course groups
  - Lessons
  - Lesson modules
  - Lesson members
  - Member modules

### 5) Events and Participation

- PRF event management
- Event subscriptions
- Event speakers
- PRF event handlers and participants
- Event media uploads and retrieval

### 6) Prayer, Souls and Student Follow-up

- Soul records and updates
- Prayer prompts
- Prayer requests
- Prayer responses
- Student enquiries and replies
- Announcements feed

### 7) Finance and Accountability

- Accounting event management
- Budget estimates and budget estimate entries
- Requisition management:
  - Create and update requisitions
  - Approval workflow (request review, approve, reject, recall)
- Requisition item management
- Allocation entry management (including token assignment)
- Payment management and payment status checking
- Payment type management
- Payment instruction management
- Refund recording
- Expense category management

### 8) Media and File Attachments

- Media attachments for missions, mission sessions, events and members
- Media support in v2 for:
  - Mission media upload and delete
  - Allocation entry media upload and delete
  - Event, member and mission session media upload

## Platform and Integration Features

### 1) External Integrations

- Paystack webhook intake and signature verification for payment notifications
- Firebase Cloud Messaging (FCM) push notifications for mission and event flows
- WhatsApp notification workflows for mission teams
- Configurable application settings for organization-specific integrations

### 2) Automation and Background Processing

- Queue-backed jobs for mission notifications, summary generation and related workflows
- Observer-driven side effects on important model lifecycle changes

### 3) Security and Reliability Controls

- Request-signature validation middleware support
- Webhook signature middleware for payment callbacks
- Rate limiting for auth and webhook surfaces
- Structured API versioning (`v1`, `v2`)

### 4) Admin and Operations

- Filament admin resources for all major domain entities
- Runtime app settings management via admin resources
- API client management resource
- Operational observability support through Laravel Pulse and Telescope providers

## Filament Admin Coverage (Resource Domains)

The admin panel currently includes resource domains for:

- API clients, users and app settings
- Missions, mission types, mission FAQs, mission questions and mission ground suggestions
- Schools, school terms, class groups, cohorts and student enquiries
- Members, memberships, groups and professions
- Courses, lessons, modules and related learning entities
- PRF events, announcements, speakers and prayer resources
- Accounting events, requisitions, payments, payment types, expense categories and transfer rates

## Notes on Scope

- This is a feature inventory, not a per-endpoint API reference.
- Some integration scaffolding exists in code but may be gated by environment settings or route exposure.
- For implementation details and access constraints, review policies, middleware, request validation rules and route definitions.

## Suggested Companion Documentation

- API endpoint reference by version (`v1` and `v2`)
- Role and permission matrix
- Data model and ERD documentation
- Runbooks for payment, mission and notification operations
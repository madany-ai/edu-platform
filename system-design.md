# System Design - LMS Platform

Version: 1.0

Architecture: Modular Monolith

Backend: Laravel 13

Frontend: Next.js

Admin Panel: Filament

Database: PostgreSQL

Cache: Redis

Storage: Cloudflare R2 / MinIO

Video Hosting: Bunny Stream

Search: Meilisearch

Realtime: Laravel Reverb

Queue: Laravel Horizon

Deployment: Docker Compose

---

# High Level Architecture

```
                        Student
                           │
                           │
                     Next.js Frontend
                           │
                    REST API (Laravel)
                           │
        ┌───────────────────────────────────────┐
        │            Laravel Backend            │
        │                                       │
        │ Identity Module                       │
        │ Student Module                        │
        │ Course Module                         │
        │ Lecture Module                        │
        │ Assessment Module                     │
        │ Assignment Module                     │
        │ Commerce Module                       │
        │ Payment Module                        │
        │ Media Module                          │
        │ Notification Module                   │
        │ Analytics Module                      │
        │ Q&A Module                            │
        └───────────────────────────────────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
   PostgreSQL           Redis            Horizon
        │                  │                  │
        └──────────────┬───┘                  │
                       │                      │
                  Laravel Queue              │
                       │                      │
        ┌──────────────┼──────────────────────┐
        │              │                      │
   Bunny Stream      Cloudflare R2        Mailpit
        │
   Student Streaming


------------------------------------------------------


                   Instructor
                        │
                        │
              Laravel Filament Admin
                        │
                 Same Laravel Backend
```

---

# Frontend

## Student Application

Technology

- Next.js
- React
- TailwindCSS

Responsibilities

- Login
- Register
- Browse Courses
- Watch Videos
- Open PDFs
- Take Exams
- Submit Assignments
- Buy Courses
- Student Dashboard

Communication

↓

REST API

---

# Admin Panel

Technology

- Filament

Responsibilities

- Manage Courses
- Manage Students
- Upload Videos
- Upload PDFs
- Manage Exams
- Manage Assignments
- View Analytics
- View Sales
- Notifications

Uses

↓

Laravel Services

Never contains business logic.

---

# Backend

Technology

Laravel

Architecture

Modular MVC

```
Modules

Identity

Students

Courses

Lectures

Assessment

Assignments

Commerce

Payments

Media

Notifications

Analytics

QA
```

Each Module contains

```
Controllers

Models

Services

Requests

Policies

Resources

Routes
```

---

# Business Layer

Business Logic lives only inside Services.

Example

```
CreateCourseService

PurchaseCourseService

UploadVideoService

SubmitExamService

GenerateCertificateService

CreateOrderService

GrantEntitlementService
```

Controllers

↓

Services

↓

Models

---

# Database

Primary Database

PostgreSQL

Contains

Users

Students

Courses

Lectures

Videos

Files

Orders

Payments

Subscriptions

Enrollments

Exams

Questions

Answers

Assignments

Analytics

Notifications

---

# Cache Layer

Redis

Used for

Cache

Sessions

Rate Limiting

Queues

Realtime

---

# Queue

Laravel Horizon

Jobs

Send Email

Upload Video

Generate Report

Generate Certificate

Notification

Video Processing

---

# Realtime

Laravel Reverb

Features

Notifications

Live Q&A

Progress Updates

---

# Search

Meilisearch

Search

Courses

Students

Lectures

---

# Object Storage

Cloudflare R2

(Local Development → MinIO)

Stores

Images

PDFs

Assignments

Certificates

---

# Video Service

Bunny Stream

Stores

Videos

Streaming

Signed URLs

HLS

---

# Authentication

Laravel Sanctum

Used by

Next.js

API

Filament uses Session Authentication.

---

# External Services

Paymob

Email Provider

Firebase (Future)

Cloudflare CDN

Bunny Stream

Cloudflare R2

---

# Deployment

Docker Compose

Services

- Nginx

- PHP-FPM

- PostgreSQL

- Redis

- Horizon

- Queue

- Scheduler

- Reverb

- Mailpit

- MinIO

- Meilisearch

- Next.js

---

# Request Flow

Student

↓

Next.js

↓

Laravel API

↓

Controller

↓

Service

↓

Model

↓

PostgreSQL

↓

JSON Response

↓

Next.js

---

# Admin Flow

Instructor

↓

Filament

↓

Service

↓

Model

↓

Database

---

# File Upload Flow

Instructor

↓

Filament

↓

Media Service

↓

Cloudflare R2

↓

Database stores URL

---

# Video Upload Flow

Instructor

↓

Filament

↓

Bunny Stream API

↓

Video Processing

↓

Database stores Video ID

---

# Purchase Flow

Student

↓

Checkout

↓

Paymob

↓

Webhook

↓

Payment Service

↓

Create Order

↓

Grant Entitlement

↓

Student gets access

---

# Analytics Flow

Student Activity

↓

Activity Log

↓

Analytics Service

↓

Dashboard

---

# Core Principles

- Modular Monolith

- Thin Controllers

- Fat Services

- Shared Business Logic

- API First

- Filament for Admin

- Next.js for Students

- PostgreSQL as Source of Truth

- Redis for Performance

- Queue for Heavy Tasks

- Docker Everywhere

- One Laravel Project

- One Next.js Project

- Production Ready

- Scalable
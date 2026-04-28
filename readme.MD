# ServiceDesk API

Un sistema backend sicuro, RESTful per il management di utenti ed interventi tecnici. Costruito con PHP 8, MySQL e autenticazione JWT

## Contenuti

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Architettura](#architettura)
- [Getting Started](#getting-started)
- [Environment Variables](#environment-variables)
- [Database Setup](#database-setup)
- [API Endpoints](#api-endpoints)
- [Request & Response Examples](#request--response-examples)
- [Filtering & Pagination](#filtering--pagination)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [Optional Features](#optional-features)

---

## Overview

ServiceDesk API è un sistema backend che espone endpoint HTTP sicuri per:
- **Gestione utenti** — registrazione, accesso, operazioni CRUD sul profilo
- **Monitoraggio degli interventi** — creazione e gestione dei ticket tecnici con relativo ciclo di vita degli stati (`aperto` → `in corso` → `chiuso`)
Tutti gli endpoint non pubblici sono protetti tramite **middleware JWT**.

---

## Tech Stack

| Layer           | Tecnologia |
|-----------------|--------------------------------|
| Language        | PHP >= 8.0                     |
| Database        | MySQL 8                        |
| DB Access       | PDO (prepared statements)      |
| Authentication  | JWT (JSON Web Token)           |
| Local Server    | PHP built-in / Apache / Nginx  |
| Testing         | Postman / Thunder Client       |
| (Optional)      | Docker                         |

---

## Architettura

```
/
├── app/
│   ├── Controllers/        # handler richieste HTTP
│   ├── Services/           # Logica
│   ├── Repositories/       # PDO
│   └── Models/             # Rappresentazione dati
├── middleware/             # middlewere con JWT
├── routes/                 # definizione dei routes
├── config/                 # configurazione DB e .env
├── public/                 # entry point
└── docs/                   # Postman collection
```
---

## Getting Started

### Prerequisiti

- PHP >= 8.0
- MySQL >= 8.0
- Composer (optional, if using packages)

### Installazione

```bash
# 1. Clona la repository
git clone https://github.com/shahharis040-star/service-desk-api.git
cd service-desk-api

# 2. Copia il file .env 
cp .env.example .env

# 3. Setuppa il database (vedi setup Database più in fondo)

# 4. Avvia il server locale
php -S localhost:8000 -t public
```

L'API sarà disponibile a  `http://localhost:8000`.

## Environment Variables

Create a `.env` file in the project root (never commit this file):

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=service_desk
DB_USER=root
DB_PASS=your_password

JWT_SECRET=your_super_secret_key
JWT_EXPIRATION=3600
```

> ⚠️ `.env` is listed in `.gitignore`. Use `.env.example` as a template.

---

## Database Setup

Run the following SQL to create the required tables:

```sql
CREATE DATABASE IF NOT EXISTS service_desk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE service_desk;

CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE interventions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    status      VARCHAR(20) NOT NULL DEFAULT 'open',
    user_id     INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## API Endpoints

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/auth/register` | ❌ | Register a new user |
| `POST` | `/auth/login` | ❌ | Login and receive a JWT token |
| `GET` | `/auth/me` | ✅ | Get the current authenticated user |

### Users

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/users` | ✅ | List all users |
| `GET` | `/users/{id}` | ✅ | Get a single user |
| `PUT` | `/users/{id}` | ✅ | Update user data |
| `DELETE` | `/users/{id}` | ✅ | Delete a user |

### Interventions

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/interventions` | ✅ | List interventions (supports filters) |
| `POST` | `/interventions` | ✅ | Create a new intervention |
| `GET` | `/interventions/{id}` | ✅ | Get a single intervention |
| `PUT` | `/interventions/{id}` | ✅ | Update an intervention |
| `DELETE` | `/interventions/{id}` | ✅ | Delete an intervention |

> ✅ = Requires `Authorization: Bearer <token>` header

---

## Request & Response Examples

### Register

```http
POST /auth/register
Content-Type: application/json

{
  "email": "mario@example.com",
  "password": "secret123"
}
```

```json
// 201 Created
{
  "message": "User registered successfully",
  "user_id": 1
}
```

### Login

```http
POST /auth/login
Content-Type: application/json

{
  "email": "mario@example.com",
  "password": "secret123"
}
```

```json
// 200 OK
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 3600
}
```

### Create Intervention

```http
POST /interventions
Authorization: Bearer <token>
Content-Type: application/json

{
  "title": "Server not responding",
  "description": "Production server unreachable since 14:00",
  "status": "open"
}
```

```json
// 201 Created
{
  "id": 5,
  "title": "Server not responding",
  "description": "Production server unreachable since 14:00",
  "status": "open",
  "user_id": 1,
  "created_at": "2025-04-28T14:30:00Z"
}
```

### Update Intervention Status

```http
PUT /interventions/5
Authorization: Bearer <token>
Content-Type: application/json

{
  "status": "in_progress"
}
```

```json
// 200 OK
{
  "message": "Intervention updated successfully"
}
```

---

## Filtering & Pagination

The `GET /interventions` endpoint supports the following query parameters:

```bash
# Filter by status
GET /interventions?status=open

# Filter by assigned user
GET /interventions?user_id=1

# Search by keyword in title or description
GET /interventions?search=server

# Pagination
GET /interventions?page=1&limit=10

# Sorting
GET /interventions?sort=created_at&order=desc

# Combined
GET /interventions?status=open&sort=created_at&order=desc&page=1&limit=5
```

---

## Error Handling

All errors return a consistent JSON structure:

```json
{
  "error": "Descriptive error message"
}
```

| Status Code | Meaning |
|-------------|---------|
| `200` | OK |
| `201` | Resource created |
| `400` | Bad request / validation error |
| `401` | Unauthorized — missing or invalid token |
| `404` | Resource not found |

---

## Testing

A Postman collection covering all endpoints is available in `/docs/postman_collection.json`.

**Import it:**
1. Open Postman → Import → select the file
2. Set the `base_url` variable to `http://localhost:8000`
3. Run the **Auth** folder first to get a token
4. The token is automatically set for subsequent requests

---

## Optional Features

- [x] Filtering by status, user, keyword
- [x] Pagination (`?page` & `?limit`)
- [x] Sorting (`?sort` & `?order`)
- [ ] Error logging to file
- [ ] Docker support
- [ ] Role-based access control middleware

---

## Security

- Passwords hashed with **bcrypt** via `password_hash()`
- All DB queries use **PDO prepared statements** (no SQL injection)
- JWT tokens have configurable **expiration**
- Error responses never expose internal stack traces or system details

---

## License

MIT — feel free to use, modify, and distribute.
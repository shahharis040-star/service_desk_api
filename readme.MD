# ServiceDesk API

Un sistema backend sicuro, RESTful per il management di utenti ed interventi tecnici. Costruito con PHP 8, MySQL e autenticazione JWT

## Contenuti

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Architettura](#architettura)
- [Getting Started](#getting-started)
- [Variabili Ambiente](#variabili-ambiente)
- [Setup Database](#setup-database)
- [API Endpoints](#api-endpoints)
- [Esempi Request & Response](#esempi-request--response)
- [Filtri & Paginazione](#filtri--paginazione)
- [Gestione errori](#gestione-errori)
- [Testing](#testing)

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

## Variabili Ambiente

Crea un file `.env` nel root del progetto :

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

## Setup Database

Esegui le seguenti query per creare le tabelle necessarie:

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

### Autenticazione

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/auth/register` | ❌ | Registra un nuovo utente |
| `POST` | `/auth/login` | ❌ | Accedi e ottini un token JWT |
| `GET` | `/auth/me` | ✅ | Ottieni l'utente attualmente autenticato |

### Utenti

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/users` | ✅ | Lista di tutti gli utenti |
| `GET` | `/users/{id}` | ✅ | Ottieni un singolo utente |
| `PUT` | `/users/{id}` | ✅ | Aggionra i dati di un utente |
| `DELETE` | `/users/{id}` | ✅ | Elimina un utente |

### Interventi

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/interventions` | ✅ | Lista degli interventi |
| `POST` | `/interventions` | ✅ | Crea nuovo intervento |
| `GET` | `/interventions/{id}` | ✅ | Ottieni un singolo intervento |
| `PUT` | `/interventions/{id}` | ✅ | Aggiorna i dati di un intervento |
| `DELETE` | `/interventions/{id}` | ✅ | Elimina un intervento |

> ✅ = Richiede `Authorization: Bearer <token>` nell'header

---

## Esempi Request & Response

### Registrazione

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
  "message": "Utente registrato correttamente",
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

### Creare Intervento

```http
POST /interventions
Authorization: Bearer <token>
Content-Type: application/json

{
  "title": "Server non risponde",
  "description": "Server non raggiungibile dalle 14:00",
  "status": "open"
}
```

```json
// 201 Created
{
  "id": 5,
  "title": "Server non risponde",
  "description": "Server non raggiungibile dalle 14:00",
  "status": "open",
  "user_id": 1,
  "created_at": "2025-04-28T14:30:00Z"
}
```

### Aggiornare stato degli interventi

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
  "message": "Intervento aggiornato correttamente"
}
```

---

## Filtri & Paginazione

Il `GET /interventions` endpoint supporta i seguenti parametri query:

```bash
# Filtra per stato
GET /interventions?status=open

# Filtra per utente di assegnazione
GET /interventions?user_id=1

# Ricerca per parola chiave in titolo o descrizione
GET /interventions?search=server

# Paginazione
GET /interventions?page=1&limit=10

# Sorting
GET /interventions?sort=created_at&order=desc

# Combinati
GET /interventions?status=open&sort=created_at&order=desc&page=1&limit=5
```

---

## Gestione errori

TUTTI gli errori ritornato la stessa struttura JSON:

```json
{
  "error": "Messaggio di errore descrittivo"
}
```

| Status Code | Meaning |
|-------------|---------|
| `200` | OK |
| `201` | Risorsa creata |
| `400` | Richiesta malformata / errore di validazione |
| `401` | Inautorizzato - token mancante o invalido |
| `404` | Risorsa non trovata |

---

## Testing

E' disponibile una collection Postman per testare tutti gli endpoint in: `/docs/postman_collection.json`.

**Import it:**
1. Apri Postman → Import → seleziona in file
2. Imposta il `base_url` come `http://localhost:8000`
3. Esegui prima la cartella **Auth** per ricevere il token
4. Il token viene impostato automaticamente per le richieste successive


---

## Sicurezza

- Password sottoposte a hash con **bcrypt** tramite `password_hash()`
- Tutte le query utilizzano **PDO prepared statements** (nessun rischio di SQL injection)
- I token JWT hanno una **scadenza** configurabile
- Le risposte di errore non rivelano mai tracce dello stack interne né dettagli di sistema

---

## Licenza

MIT

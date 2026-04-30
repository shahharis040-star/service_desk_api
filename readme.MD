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
- **Monitoraggio degli interventi** — creazione e gestione dei ticket tecnici con relativo ciclo di vita degli stati (`open` → `in_progress` → `closed`)
Tutti gli endpoint non pubblici sono protetti tramite **middleware JWT**.

---

## Tech Stack

| Layer           | Tecnologia |
|-----------------|--------------------------------|
| Linguaggio      | PHP >= 8.0                     |
| Database        | MySQL 8                        |
| Accesso DB      | PDO (prepared statements)      |
| Autenticazione  | JWT (JSON Web Token)           |
| Server locale   | PHP built-in |
| Testing         | Postman        |

---

## Architettura

```
/
├── app/
│   ├── Controllers/        # Gestione richieste HTTP
│   ├── Services/           # Logica core
│   ├── Repositories/       # Accesso DB con PDO
├── middleware/             # Autenticazione con JWT
├── routes/                 # Definizione Routes
├── config/                 # configurazione DB e .env
├── public/                 # Entry point (index.php)
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
git clone https://github.com/tuo-nome-utente/service-desk-api.git
cd service-desk-api

# 2. Copia il file .env 
cp .env.example .env

# 3. Setuppa il database 
Esegui le query SQL presenti dentro il file setup.sql

# 4. Avvia il server locale
php -S localhost:8000 -t public
```

L'API sarà disponibile a  `http://localhost:8000`.

---


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
  "email": "mario@esempio.com",
  "password": "password123"
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
  "email": "mario@esempio.com",
  "password": "password123"
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

| Status Code | Significato |
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
4. Andare su "Authorization" -> "Auth Type" -> "Bearer Token" ed inserire il token ritornato dall'API di login, il token verrà salvato per le API successive

---

## Sicurezza

- Password sottoposte a hash con **bcrypt** tramite `password_hash()`
- Tutte le query utilizzano **PDO prepared statements** (nessun rischio di SQL injection)
- I token JWT hanno una **scadenza** configurabile
- Le risposte di errore non rivelano mai tracce dello stack interne né dettagli di sistema

---

## License

MIT — feel free to use, modify, and distribute.
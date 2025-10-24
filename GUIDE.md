# Country & Currency API: A Comprehensive Implementation Guide

## Introduction and Architectural Overview

This API provides a robust caching and transformation layer over volatile external data sources. It ingests, processes, and persists data from third-party services, shielding clients from external instability and exposing a consistent, enriched dataset via a RESTful interface.

Architecture components:
- **Data Ingestion & Transformation**: Fetches raw country data and exchange rates, merges datasets, handles edge cases, and computes `estimated_gdp`.
- **Caching & Persistence**: Relational database (MySQL or SQLite) acts as the persistent cache and single source of truth. Updates occur only via an atomic refresh mechanism.
- **RESTful API Exposure**: Standardized endpoints for CRUD, status, and media serving.

---

## 1.0 Foundational Layer: Database Schema Design

The schema below supports case-insensitive upserts, efficient filtering, and per-record auditing.

| Field Name         | Data Type        | Constraint                                | Notes |
|--------------------|------------------|-------------------------------------------|-------|
| id                 | INT              | Primary Key, Auto-Increment               | Auto-generated unique identifier |
| name               | VARCHAR(255)     | NOT NULL, UNIQUE                          | Common name; used for case-insensitive upsert |
| capital            | VARCHAR(255)     | NULL                                      | Optional |
| region             | VARCHAR(255)     | NULL                                      | Optional; indexed for filtering |
| population         | BIGINT           | NOT NULL                                  | Required for GDP calculation |
| currency_code      | CHAR(3)          | NULL                                      | Nullable to handle missing currency data |
| exchange_rate      | DECIMAL(10,4)    | NULL                                      | Nullable if rate not found |
| estimated_gdp      | DECIMAL(20,2)    | NULL                                      | Computed from population and exchange rate |
| flag_url           | VARCHAR(512)     | NULL                                      | URL to flag image |
| last_refreshed_at  | DATETIME         | NOT NULL, auto-updated on insert/update   | Per-record audit timestamp |

Notes:
- UNIQUE on `name` is critical for case-insensitive upsert logic.
- Index `region` and `currency_code` for performant GET /countries filtering.

### MySQL vs SQLite: Key Considerations

| Key Consideration | MySQL | SQLite |
|-------------------|-------|--------|
| Setup Complexity  | Server-based; requires install/config | Serverless; file-based; simpler setup |
| Concurrency       | High concurrency support | Limited concurrency; OK with single controlled write (/refresh) |
| Transactions      | Full ACID support | Full ACID support |
| Scaling           | Suitable for production/high traffic | Best for single-service or demo use |

---

## 2.0 Core Logic: The Data Ingestion and Caching Service

All writes occur via `POST /countries/refresh`. The refresh must be atomic, transactional, and fail-fast on external dependency failure.

### 2.1 Step 1: Fetching and Validating External Data

External APIs:
- Countries API: `https://restcountries.com/v2/all?fields=name,capital,region,population,flag,currencies`
- Exchange Rates API: `https://open.er-api.com/v6/latest/USD`

Error handling:
- If either external call fails (network, timeout, non-2xx), abort refresh and return HTTP 503:
    ```json
    { "error": "External data source unavailable", "details": "Could not fetch data from [API name]" }
    ```
- Fail-fast to avoid partial updates.

### 2.2 Step 2: Data Processing within a Database Transaction

Wrap the entire processing and DB updates in a single transaction. For each country record from the countries API:

1. Extract currency code:
     - Use only the first object in the `currencies` array.
     - If missing/empty, set `currency_code` and `exchange_rate` to `NULL` and `estimated_gdp` to `0`.
2. Match exchange rate:
     - Look up the currency code in the exchange rates payload.
     - If not found, set `exchange_rate` and `estimated_gdp` to `NULL`.
3. Calculate `estimated_gdp`:
     - Use: `estimated_gdp = population × random(1000–2000) ÷ exchange_rate`
     - Generate a new random integer between 1000 and 2000 for each country during each refresh.
4. Upsert logic:
     - Case-insensitive search by `name`.
     - If found, update all fields and recalculate `estimated_gdp` with a new random multiplier.
     - If not found, insert a new record.

Ensure the transaction commits only if all records are processed successfully; otherwise rollback.

### 2.3 Step 3: Post-Transaction Tasks and Image Generation

After successful commit:
- Update a global `last_refreshed_at` timestamp (served by `/status`).
- Generate a summary image at `cache/summary.png` including:
    - Total number of countries stored.
    - Top 5 countries by `estimated_gdp` (descending).
    - Timestamp of last successful refresh.
- Save the image to `cache/summary.png` for the image serving endpoint.

---

## 3.0 API Endpoint Implementation

### 3.1 Data Management Endpoints
- `POST /countries/refresh`  
    Triggers the fetch → process → persist flow (see Section 2.0).
- `GET /countries`  
    Returns list; supports optional query params: `region`, `currency`, and `?sort=gdp_desc`. Build queries safely and conditionally add WHERE/ORDER BY clauses.
- `GET /countries/:name`  
    Retrieve a single country by case-insensitive name match.
- `DELETE /countries/:name`  
    Delete a country by name.

### 3.2 Status and Media Endpoints
- `GET /status`  
    Returns JSON with total countries count and `last_refreshed_at`.
    Example:
    ```json
    { "total_countries": 195, "last_refreshed_at": "2025-10-24T12:34:56Z" }
    ```
- `GET /countries/image`  
    Serve `cache/summary.png` with `Content-Type: image/png`. If not present, return a JSON error.

---

## 4.0 System-Wide Validation and Error Handling

Standardized JSON error responses:

- 400 Bad Request — validation failure:
    ```json
    {
        "error": "Validation failed",
        "details": { "currency_code": "is required" }
    }
    ```
    Triggered when required fields (`name`, `population`, `currency_code`) fail validation before persistence.

- 404 Not Found:
    ```json
    { "error": "Country not found" }
    ```
    When a requested resource does not exist.

- 500 Internal Server Error:
    ```json
    { "error": "Internal server error" }
    ```
    For unexpected server-side failures.

- 503 Service Unavailable:
    ```json
    { "error": "External data source unavailable", "details": "Could not fetch data from [API name]" }
    ```

Implement consistent error middleware to produce these JSON responses and appropriate HTTP status codes.

---

## 5.0 Project Setup, Deployment, and Submission

- Use a `.env` file to manage configuration (DB credentials, APP_PORT, etc.).
- Provide a comprehensive `README.md` in the project root with:
    - Setup instructions
    - Dependency list and installation steps
    - How to run locally
- Hosting: Vercel is forbidden. Use Railway, Heroku, AWS, or similar.
- Submission: Use Slack bot command `/stage-two-backend` with live API base URL and public GitHub repo link.

File artifact:
- Summary image path: `cache/summary.png` (served by `GET /countries/image`).

This guide defines the schema, transactional refresh process, endpoints, and operational expectations needed to implement the Country & Currency API.

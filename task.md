This task outlines the requirements for building a **RESTful API** that serves cached, processed **country and currency data**. The primary objective is to fetch data from two external APIs, combine and transform this data with a calculated field (estimated GDP), store it in a MySQL database as a cache, and expose several API endpoints for CRUD operations, filtering, sorting, status checks, and image serving.

The core challenge lies in data aggregation, transformation, caching logic (update/insert on refresh), error handling for external APIs and validation, and dynamic image generation.

***

## 🎯 Task Breakdown and Objectives

The task is fundamentally about **Data Caching and Exposure**.

### 1. Data Ingestion and Transformation
* Fetch country details (name, capital, region, population, flag, currencies) from the `restcountries.com` API.
* Extract the primary currency code for each country.
* Fetch exchange rates (relative to USD) from the `open.er-api.com` API.
* **Data Matching**: Associate each country's currency code with its corresponding exchange rate.
* **Data Transformation/Computation**: Calculate the `estimated_gdp` using the formula:
    $$\text{estimated\_gdp} = \text{population} \times \text{random}(1000-2000) \div \text{exchange\_rate}$$
* Handle edge cases: multiple currencies, missing currencies, missing exchange rates.

### 2. Caching and Persistence
* Use a **MySQL database** to store the processed data.
* Implement a crucial **refresh logic** (`POST /countries/refresh`):
    * Match existing records by **name** (case-insensitive).
    * **Update** existing records (with a new random GDP multiplier).
    * **Insert** new records.
    * Update a global `last_refreshed_at` timestamp.
* Ensure atomicity: Do not modify the database if the external API fetch fails.

### 3. API Endpoints (Functionality)
* **`/countries/refresh` (POST)**: Triggers the entire data fetching, processing, and caching routine.
* **`/countries` (GET)**: Retrieve all cached country data, supporting filters (`region`, `currency`) and sorting (`gdp_desc`).
* **`/countries/:name` (GET)**: Retrieve a single country record by name.
* **`/countries/:name` (DELETE)**: Delete a country record.
* **`/status` (GET)**: Provide metadata (total countries, last refresh time).
* **`/countries/image` (GET)**: Serve a dynamically generated summary image.

### 4. Image Generation
* **On successful refresh**: Generate an image summarizing total countries, top 5 by GDP, and refresh time.
* Serve this image via `GET /countries/image`.

### 5. Robust Error Handling
* Handle **External API failures** with a `503 Service Unavailable` response.
* Implement **Validation** (required fields) with a `400 Bad Request` response.
* Handle **Not Found** (`404`) and general **Internal Server Errors** (`500`).

***

## ⚙️ Step-by-Step Guide (PHP Algorithm & Structure)

The implementation can be logically divided into **Database Schema**, **Data Ingestion/Caching Logic**, **Endpoint Handling (Routing & Controllers)**, and **Image Generation**.

### 1. Database Schema (SQLite)

Create a single table, e.g., `countries`, with the required fields:

| Field Name | Data Type | Constraint | Notes |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | Primary Key, Auto-Increment | Auto-generated ID. |
| `name` | `VARCHAR(255)` | NOT NULL, UNIQUE | Country name, used for update/insert logic. |
| `capital` | `VARCHAR(255)` | NULL | Optional field. |
| `region` | `VARCHAR(255)` | NULL | Optional field. |
| `population` | `BIGINT` | NOT NULL | Required field. |
| `currency_code` | `CHAR(3)` | NULL | Currency code (e.g., NGN). |
| `exchange_rate` | `DECIMAL(10, 4)` | NULL | Rate against USD. |
| `estimated_gdp` | `DECIMAL(20, 2)` | NULL | Computed value. |
| `flag_url` | `VARCHAR(512)` | NULL | URL to the flag image. |
| `last_refreshed_at` | `DATETIME` | NOT NULL, Auto-update | Timestamp of last record update. |

A separate table or a single configuration entry might be needed to store the **global last refresh timestamp** and **total country count** for the `/status` endpoint, though this data could also be computed on demand.

### 2. Data Ingestion and Caching Logic (`POST /countries/refresh`)

This is the most complex part and should be implemented in a dedicated **Service/Controller method**.

#### A. Fetch and Validate External Data
1.  **Fetch Country Data**: Call `https://restcountries.com/v2/all?fields=...`.
2.  **Fetch Exchange Rates**: Call `https://open.er-api.com/v6/latest/USD`.
3.  **External API Error Handling**: Use `try-catch` or check HTTP status codes. If either fails, immediately return **503 Service Unavailable** and stop the process.

#### B. Data Processing Loop (Transaction)
Execute the following inside a **database transaction** to ensure all updates succeed or fail together.

For each country from the API:
1.  **Extract Currency Code**: Get the first currency code from the `currencies` array. If empty, set `currency_code`, `exchange_rate` to `NULL`, and `estimated_gdp` to `0`. Skip rate fetching for this country.
2.  **Find Exchange Rate**: Look up the country's `currency_code` in the fetched exchange rates.
    * If found, use the rate.
    * If **not found**, set `exchange_rate` and `estimated_gdp` to `NULL`.
3.  **Calculate Estimated GDP**:
    * Generate a **random multiplier** $R$ where $1000 \le R \le 2000$.
    * If rate is available: $$\text{estimated\_gdp} = \text{population} \times R \div \text{exchange\_rate}$$
4.  **Database Operation (Upsert Logic)**:
    * **Search**: Query the DB for a country matching the new country's `name` (case-insensitive).
    * **Update**: If found, update the existing record with all new data (including the newly computed GDP and fresh `last_refreshed_at`).
    * **Insert**: If not found, insert a new record.

#### C. Post-Transaction and Image Generation
1.  **Commit Transaction**: If the loop completes successfully, commit the changes.
2.  **Update Global Status**: Store the successful refresh timestamp and the total count.
3.  **Generate Summary Image**:
    * Fetch the total country count.
    * Query the DB for the **Top 5 countries by `estimated_gdp`** (descending).
    * Use a PHP **Image Processing library** (e.g., GD or Imagick) to draw the text and data onto an image canvas.
    * Save the image to the specified path: `cache/summary.png`.
4.  **Response**: Return a success status (e.g., 200/201).

### 3. API Endpoint Implementation (Routing & Controllers)

The PHP framework (e.g., Laravel, Symfony, or a custom router) will handle routing the HTTP requests to the appropriate controller methods.

#### A. `POST /countries/refresh`
* Route to the service method implementing **Section 2**.

#### B. `GET /countries`
1.  **Parse Query Parameters**: Extract `region`, `currency`, and `sort` parameters.
2.  **Build Dynamic Query**: Construct a MySQL `SELECT` query with `WHERE` clauses for filtering and an `ORDER BY` clause for sorting.
    * Example: `SELECT * FROM countries WHERE region = ? ORDER BY estimated_gdp DESC`.
3.  **Execute and Respond**: Fetch data and return the results as a **200 OK** JSON array.

#### C. `GET /countries/:name`
1.  **Extract Name**: Get the name from the URL path.
2.  **Query**: Search the DB by name (case-insensitive).
3.  **Error Handling**: If not found, return **404 Not Found**.
4.  **Respond**: Return the single record as a **200 OK** JSON object.

#### D. `DELETE /countries/:name`
1.  **Extract Name**: Get the name from the URL path.
2.  **Query**: Find and delete the record by name.
3.  **Error Handling**: If the record wasn't found before deletion, return **404 Not Found**.
4.  **Respond**: Return a success status (e.g., **204 No Content** or **200 OK** with a success message).

#### E. `GET /status`
1.  **Query Global Status**: Fetch the total count and `last_refreshed_at` timestamp from the database/config.
2.  **Respond**: Return the data as a **200 OK** JSON object.

#### F. `GET /countries/image`
1.  **Check File Existence**: Check if `cache/summary.png` exists.
2.  **Error Handling**: If it doesn't exist, return a **404 Not Found** JSON response.
3.  **Serve Image**: Set the appropriate HTTP headers (`Content-Type: image/png`) and serve the raw image file content.

### 4. Validation and General Error Handling

* **Request Validation (e.g., for future POST/PUT)**: Use a PHP Validator component to ensure required fields (`name`, `population`, `currency_code`) are present and valid. On failure, return **400 Bad Request**.
* **Catch-All Error Handler**: Implement a global exception handler to catch uncaught errors (e.g., DB connection failure, unhandled runtime errors) and map them to a **500 Internal Server Error** JSON response.
# Country & Currency Data Caching API

A RESTful API that fetches, processes, caches, and serves country and currency data with calculated GDP estimates. Built with vanilla PHP and SQLite.

## 🎯 Features

- **Data Aggregation**: Fetches data from RestCountries and Open Exchange Rates APIs
- **Smart Caching**: SQLite database with atomic transactions and case-insensitive upsert logic
- **GDP Calculation**: Estimates GDP using population and exchange rates with random multipliers
- **Filtering & Sorting**: Query countries by region, currency, and sort by GDP
- **Image Generation**: Auto-generates summary images with top countries using PHP GD
- **RESTful Design**: Clean API endpoints with proper HTTP status codes
- **Error Handling**: Comprehensive exception handling with meaningful error messages

## 📋 Prerequisites

- **PHP 7.4+** (with extensions: `pdo_sqlite`, `gd`, `curl`, `json`, `mbstring`)
- **Composer** 2.x
- **SQLite3**
- **Git**

### Check PHP Extensions

```bash
php -m | grep -E "pdo_sqlite|gd|curl|json|mbstring"
```

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/baydre/country-currency-and-exchange-rate-API.git
cd country-currency-and-exchange-rate-API
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` if needed (defaults should work):

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

RESTCOUNTRIES_API=https://restcountries.com/v2/all
EXCHANGERATE_API=https://open.er-api.com/v6/latest/USD

APP_DEBUG=true
APP_PORT=8000
CACHE_DIR=cache
```

### 4. Create Database

```bash
# Create database file
touch database/database.sqlite
chmod 664 database/database.sqlite

# Run migrations
sqlite3 database/database.sqlite < database/schema.sql
```

### 5. Set Permissions

```bash
chmod 755 cache database
```

### 6. Start Development Server

```bash
php -S localhost:8000 -t public
```

The API will be available at: `http://localhost:8000`

## � API Documentation

### Interactive OpenAPI Documentation

Access the interactive Swagger UI documentation at:

**http://localhost:8000/docs**

This provides:
- ✅ Complete API specification (OpenAPI 3.0)
- ✅ Try-it-out functionality for all endpoints
- ✅ Request/response examples
- ✅ Schema definitions
- ✅ HTTP status code details

### OpenAPI Specification File

The raw OpenAPI YAML specification is available at:
- **File**: `openapi.yaml`
- **Format**: OpenAPI 3.0.3

You can import this into:
- Postman
- Insomnia
- Swagger Editor
- Any OpenAPI-compatible tool

## �📡 API Endpoints

### 1. Refresh Countries Data

**POST** `/countries/refresh`

Fetches data from external APIs, processes it, and updates the database.

**Response:**
```json
{
    "message": "Countries data refreshed successfully",
    "processed": 250,
    "errors": 0,
    "total_countries": 250,
    "timestamp": "2025-10-24 19:18:49"
}
```

**Status Codes:**
- `200 OK` - Success
- `503 Service Unavailable` - External API failure

**Example:**
```bash
curl -X POST http://localhost:8000/countries/refresh
```

---

### 2. Get All Countries

**GET** `/countries`

Retrieves all countries with optional filtering and sorting.

**Query Parameters:**
- `region` (optional) - Filter by region (e.g., `Africa`, `Europe`, `Asia`)
- `currency` (optional) - Filter by currency code (e.g., `USD`, `EUR`)
- `sort` (optional) - Sort order. Allowed values: `gdp_desc`

**Response:**
```json
{
    "data": [
        {
            "id": "240",
            "name": "United States of America",
            "capital": "Washington, D.C.",
            "region": "Americas",
            "population": "329484123",
            "currency_code": "USD",
            "exchange_rate": "1.0",
            "estimated_gdp": "484671144933.0",
            "flag_url": "https://flagcdn.com/us.svg",
            "last_refreshed_at": "2025-10-24 19:18:49"
        }
    ],
    "count": 250
}
```

**Examples:**
```bash
# Get all countries
curl http://localhost:8000/countries

# Filter by region
curl http://localhost:8000/countries?region=Africa

# Filter by currency
curl http://localhost:8000/countries?currency=USD

# Sort by GDP (descending)
curl http://localhost:8000/countries?sort=gdp_desc

# Combine filters
curl "http://localhost:8000/countries?region=Europe&sort=gdp_desc"
```

---

### 3. Get Single Country

**GET** `/countries/{name}`

Retrieves a specific country by name (case-insensitive).

**Response:**
```json
{
    "data": {
        "id": "163",
        "name": "Nigeria",
        "capital": "Abuja",
        "region": "Africa",
        "population": "206139587",
        "currency_code": "NGN",
        "exchange_rate": "1461.543789",
        "estimated_gdp": "212550838.33",
        "flag_url": "https://flagcdn.com/ng.svg"
    }
}
```

**Status Codes:**
- `200 OK` - Country found
- `404 Not Found` - Country doesn't exist

**Examples:**
```bash
# Case-insensitive lookup
curl http://localhost:8000/countries/nigeria
curl http://localhost:8000/countries/NIGERIA
curl http://localhost:8000/countries/Nigeria
```

---

### 4. Delete Country

**DELETE** `/countries/{name}`

Deletes a country from the database (case-insensitive).

**Response:**
```json
{
    "message": "Country 'Nigeria' deleted successfully"
}
```

**Status Codes:**
- `200 OK` - Country deleted
- `404 Not Found` - Country doesn't exist

**Example:**
```bash
curl -X DELETE http://localhost:8000/countries/Nigeria
```

---

### 5. Get API Status

**GET** `/status`

Returns metadata about the cached data.

**Response:**
```json
{
    "total_countries": 250,
    "last_refreshed_at": "2025-10-24 19:18:49"
}
```

**Example:**
```bash
curl http://localhost:8000/status
```

---

### 6. Get Summary Image

**GET** `/countries/image`

Serves a dynamically generated PNG image showing:
- Total country count
- Top 5 countries by estimated GDP
- Last refresh timestamp

**Response:** PNG image (800x600 pixels)

**Status Codes:**
- `200 OK` - Image served
- `404 Not Found` - Image not generated (run refresh first)

**Example:**
```bash
# View in browser
open http://localhost:8000/countries/image

# Download image
curl http://localhost:8000/countries/image -o summary.png
```

---

## 🏗️ Project Structure

```
country-currency-and-exchange-rate-API/
├── public/
│   └── index.php              # Entry point
├── src/
│   ├── Controllers/
│   │   ├── CountryController.php
│   │   ├── ImageController.php
│   │   └── StatusController.php
│   ├── Services/
│   │   ├── ExternalApiService.php
│   │   ├── CountryService.php
│   │   └── ImageGeneratorService.php
│   ├── Database/
│   │   └── Database.php       # PDO wrapper with transactions
│   ├── Models/
│   │   └── Country.php        # Country model
│   ├── Exceptions/
│   │   ├── ServiceUnavailableException.php
│   │   ├── NotFoundException.php
│   │   └── ValidationException.php
│   ├── Router.php             # Request router
│   └── helpers.php            # Helper functions
├── database/
│   ├── database.sqlite        # SQLite database
│   └── schema.sql             # Database schema
├── cache/
│   └── summary.png            # Generated image
├── vendor/                    # Composer dependencies
├── .env                       # Environment config
├── .env.example               # Example config
├── composer.json              # Dependencies
└── README.md                  # This file
```

## 🧪 Testing

### Manual Testing Checklist

```bash
# 1. Check status (should be empty)
curl http://localhost:8000/status

# 2. Refresh data
curl -X POST http://localhost:8000/countries/refresh

# 3. Check status again
curl http://localhost:8000/status

# 4. Get all countries
curl http://localhost:8000/countries

# 5. Filter by region
curl "http://localhost:8000/countries?region=Africa"

# 6. Sort by GDP
curl "http://localhost:8000/countries?sort=gdp_desc"

# 7. Get single country (case-insensitive)
curl http://localhost:8000/countries/nigeria

# 8. Get image
curl http://localhost:8000/countries/image -o test.png

# 9. Delete country
curl -X DELETE http://localhost:8000/countries/testcountry

# 10. Verify 404 for non-existent country
curl http://localhost:8000/countries/nonexistent
```

### Run Automated Tests (Optional)

```bash
./vendor/bin/phpunit tests/
```

## 🔒 Security Features

- ✅ **SQL Injection Prevention**: All queries use prepared statements
- ✅ **Input Validation**: Query parameters and route params validated
- ✅ **Error Handling**: No stack traces exposed in production
- ✅ **CORS Configured**: Allows cross-origin requests
- ✅ **Case-Insensitive Lookups**: Prevents duplicate entries

## 🧮 GDP Calculation Formula

```
estimated_gdp = (population × random_multiplier) ÷ exchange_rate

where:
  - population: Country's population from RestCountries API
  - random_multiplier: Random integer between 1000 and 2000
  - exchange_rate: Currency exchange rate vs USD from Exchange Rate API
```

**Note**: This is a simplified estimation for demonstration purposes.

## 🗄️ Database Schema

### `countries` Table

| Column | Type | Constraints |
|--------|------|-------------|
| id | INTEGER | PRIMARY KEY, AUTOINCREMENT |
| name | TEXT | NOT NULL, UNIQUE (case-insensitive) |
| capital | TEXT | NULL |
| region | TEXT | NULL |
| population | INTEGER | NOT NULL |
| currency_code | TEXT | NULL |
| exchange_rate | REAL | NULL |
| estimated_gdp | REAL | NULL |
| flag_url | TEXT | NULL |
| last_refreshed_at | TEXT | NOT NULL |
| created_at | TEXT | NOT NULL |
| updated_at | TEXT | NOT NULL |

**Indexes**: `name`, `region`, `currency_code`, `estimated_gdp DESC`

### `api_status` Table

| Column | Type | Constraints |
|--------|------|-------------|
| id | INTEGER | PRIMARY KEY (always 1) |
| total_countries | INTEGER | DEFAULT 0 |
| last_refreshed_at | TEXT | NULL |
| updated_at | TEXT | NOT NULL |

## 🔧 Troubleshooting

### Port Already in Use

```bash
# Use different port
php -S localhost:8080 -t public
```

### Permission Denied

```bash
chmod 755 cache database
chmod 664 database/database.sqlite
```

### GD Extension Not Found

```bash
# Ubuntu/Debian
sudo apt install php-gd

# Verify
php -m | grep gd
```

### Database Locked

```bash
# Remove database and recreate
rm database/database.sqlite
touch database/database.sqlite
sqlite3 database/database.sqlite < database/schema.sql
```

## 📚 External APIs Used

- **RestCountries API**: https://restcountries.com/v2/all
  - Provides country data (name, capital, population, currencies, flags)
  
- **Open Exchange Rates API**: https://open.er-api.com/v6/latest/USD
  - Provides currency exchange rates relative to USD

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 👤 Author

**Baydre Africa**
- GitHub: [@baydre](https://github.com/baydre)

## 🙏 Acknowledgments

- RestCountries API for comprehensive country data
- Open Exchange Rates for currency data
- PHP GD Library for image generation

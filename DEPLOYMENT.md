# Deployment Guide for PXXL App

This guide will help you deploy the Country Currency and Exchange Rate API to PXXL App.

## Prerequisites

- Git installed on your local machine
- PXXL App CLI installed (or Heroku CLI if using Heroku)
- A PXXL App account

## Files Overview

### 1. **Dockerfile**
- Containerizes the PHP application with Apache
- Installs PHP 8.1 and required extensions (SQLite, PDO, etc.)
- Configures Apache to serve from `public/` directory
- Sets up database and cache directories
- Initializes SQLite database on build

### 2. **Procfile**
- Tells PXXL App how to run the application
- Uses `heroku-php-apache2` to serve from `public/` directory

### 3. **public/.htaccess**
- Enables URL rewriting for clean URLs
- Routes all requests through `index.php`

### 4. **.dockerignore**
- Excludes unnecessary files from Docker builds
- Reduces image size and improves build speed

### 5. **docker-compose.yml**
- For local testing with Docker
- Maps port 8080 to container's port 80

## Deployment Steps

### Option 1: Deploy with Docker

1. **Build the Docker image:**
   ```bash
   docker build -t country-currency-api .
   ```

2. **Test locally:**
   ```bash
   docker-compose up
   ```
   Your app will be available at http://localhost:8080

3. **Push to container registry (if needed):**
   ```bash
   docker tag country-currency-api registry.pxxl.app/your-username/country-currency-api
   docker push registry.pxxl.app/your-username/country-currency-api
   ```

### Option 2: Deploy to Heroku/PXXL App (Git-based)

1. **Login to your platform:**
   ```bash
   heroku login
   # or
   pxxl login
   ```

2. **Create a new app:**
   ```bash
   heroku create your-app-name
   # or
   pxxl apps:create your-app-name
   ```

3. **Set environment variables:**
   ```bash
   heroku config:set APP_DEBUG=false
   heroku config:set DB_CONNECTION=sqlite
   heroku config:set DB_DATABASE=database/database.sqlite
   heroku config:set RESTCOUNTRIES_API=https://restcountries.com/v2/all
   heroku config:set EXCHANGERATE_API=https://open.er-api.com/v6/latest/USD
   heroku config:set CACHE_DIR=cache
   ```

4. **Deploy the application:**
   ```bash
   git add .
   git commit -m "Setup deployment files"
   git push heroku main
   # or
   git push pxxl main
   ```

5. **Open your app:**
   ```bash
   heroku open
   # or
   pxxl apps:open
   ```

## Environment Variables

Make sure to set these environment variables in your PXXL App dashboard:

| Variable | Default Value | Description |
|----------|---------------|-------------|
| `APP_DEBUG` | `false` | Enable/disable debug mode (set to false in production) |
| `DB_CONNECTION` | `sqlite` | Database driver |
| `DB_DATABASE` | `database/database.sqlite` | Database file path |
| `RESTCOUNTRIES_API` | `https://restcountries.com/v2/all` | REST Countries API endpoint |
| `EXCHANGERATE_API` | `https://open.er-api.com/v6/latest/USD` | Exchange Rate API endpoint |
| `CACHE_DIR` | `cache` | Cache directory path |

## Testing the Deployment

Once deployed, test your API endpoints:

```bash
# Health check
curl https://your-app.pxxl.app/status

# Get all countries
curl https://your-app.pxxl.app/countries

# Get specific country
curl https://your-app.pxxl.app/countries/Nigeria
```

## Troubleshooting

### Database Issues
- Ensure SQLite is enabled in your PHP installation
- Check write permissions for `database/` and `cache/` directories
- The database will be initialized automatically on first deploy

### Apache Configuration
- The app serves from the `public/` directory
- `.htaccess` handles URL rewriting
- If URLs don't work, ensure `mod_rewrite` is enabled

### Environment Variables
- Make sure all required environment variables are set
- Check variable names (they're case-sensitive)
- Use `heroku config` or `pxxl config` to verify settings

## Performance Optimization

For production deployments:

1. **Enable OPcache:**
   Add to your Dockerfile before the CMD:
   ```dockerfile
   RUN docker-php-ext-install opcache
   ```

2. **Set production PHP settings:**
   Create `php.ini` and copy it in Dockerfile:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   ```

3. **Use CDN for static assets** (if applicable)

4. **Monitor logs:**
   ```bash
   heroku logs --tail
   # or
   pxxl logs --tail
   ```

## Support

For issues or questions:
- Check PXXL App documentation
- Review application logs
- Ensure all dependencies are installed via composer

## Notes

- The SQLite database is created inside the container
- For persistent data, consider using a managed database service
- Cache directory is writable for storing API responses
- The application automatically refreshes country data from external APIs

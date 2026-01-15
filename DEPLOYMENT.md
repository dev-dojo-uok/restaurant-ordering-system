# Food Ordering System - Docker Deployment Guide

## Quick Start

### 1. Prerequisites
- Docker Engine 20.10+
- Docker Compose 2.0+

### 2. Initial Setup

Clone the repository and navigate to the project directory:
```bash
cd food-order-app
```

Create a `.env` file (if not exists):
```bash
cp env_example .env
```

### 3. Build and Run

Build and start all services:
```bash
docker-compose up -d --build
```

This will:
- Build the React POS application
- Create a PHP/Apache container with the built app
- Start a PostgreSQL database
- Run database migrations automatically
- Set up networking between services

### 4. Access the Application

- **Main Site**: http://localhost:8080
- **POS System**: http://localhost:8080/pos
- **Admin Panel**: http://localhost:8080/admin/dashboard.php
- **Database**: localhost:5432

### 5. Default Credentials

Create an admin user by accessing:
```bash
http://localhost:8080/register.php
```

Then update the role in the database or use existing credentials if already set up.

## Docker Commands

### View Logs
```bash
# All services
docker-compose logs -f

# Web server only
docker-compose logs -f web

# Database only
docker-compose logs -f db
```

### Stop Services
```bash
docker-compose down
```

### Stop and Remove Volumes (Fresh Start)
```bash
docker-compose down -v
```

### Rebuild After Code Changes
```bash
docker-compose up -d --build web
```

### Run Database Migrations
```bash
docker-compose exec web php /var/www/html/sql/run_migration.php
```

### Access Database Shell
```bash
docker-compose exec db psql -U postgres -d food_app
```

### Access Web Container Shell
```bash
docker-compose exec web bash
```

## Production Deployment

### 1. Update Environment Variables

Edit `.env` with production values:
```env
DB_HOST=db
DB_PORT=5432
DB_NAME=food_app_prod
DB_USER=foodapp_user
DB_PASS=strong_secure_password_here
```

### 2. Update docker-compose.yml

For production, consider:
- Using specific image versions
- Setting resource limits
- Enabling SSL/TLS
- Using secrets management
- Setting up backups

### 3. Deploy
```bash
docker-compose -f docker-compose.yml up -d --build
```

## Architecture

```
┌─────────────────────────────────────────┐
│          Internet / Users               │
└─────────────────┬───────────────────────┘
                  │ Port 8080
┌─────────────────▼───────────────────────┐
│         food-app-web (Apache)           │
│  ┌────────────────────────────────┐     │
│  │  PHP Backend (Laravel-style)   │     │
│  │  - API Routes                  │     │
│  │  - Controllers                 │     │
│  │  - Models                      │     │
│  └────────────────────────────────┘     │
│  ┌────────────────────────────────┐     │
│  │  React POS (/pos)              │     │
│  │  - Built at build time         │     │
│  │  - Served as static files      │     │
│  └────────────────────────────────┘     │
└─────────────────┬───────────────────────┘
                  │ Internal Network
┌─────────────────▼───────────────────────┐
│      food-app-db (PostgreSQL)           │
│  - Port 5432                            │
│  - Persistent Volume                    │
└─────────────────────────────────────────┘
```

## Troubleshooting

### Database Connection Issues
```bash
# Check if database is ready
docker-compose exec db pg_isready -U postgres

# View database logs
docker-compose logs db
```

### Web Server Issues
```bash
# Check Apache error logs
docker-compose exec web tail -f /var/log/apache2/error.log

# Check Apache access logs
docker-compose exec web tail -f /var/log/apache2/access.log
```

### POS Not Loading
```bash
# Check if POS files are built
docker-compose exec web ls -la /var/www/html/public/pos/

# Rebuild the image
docker-compose up -d --build --force-recreate web
```

### Permission Issues
```bash
# Fix uploads directory permissions
docker-compose exec web chown -R www-data:www-data /var/www/html/public/uploads
docker-compose exec web chmod -R 755 /var/www/html/public/uploads
```

## Backup and Restore

### Backup Database
```bash
docker-compose exec db pg_dump -U postgres food_app > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore Database
```bash
cat backup.sql | docker-compose exec -T db psql -U postgres food_app
```

### Backup Uploads
```bash
tar -czf uploads_backup_$(date +%Y%m%d_%H%M%S).tar.gz public/uploads/
```

## Health Checks

Both services have health checks configured:

- **Database**: Checks PostgreSQL readiness every 10 seconds
- **Web Server**: Checks API endpoint every 30 seconds

View health status:
```bash
docker-compose ps
```

## Scaling (Future)

To scale the web service:
```bash
docker-compose up -d --scale web=3
```

Note: This requires a load balancer and shared storage for uploads.

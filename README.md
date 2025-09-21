# 🚀 Inventory AI - Management System

Sistem manajemen inventory dengan integrasi AI menggunakan PHP 8.4 + MongoDB + Ollama.

## 🛠️ Tech Stack

- **PHP 8.4.12** dengan strict types dan type declarations ✅
- **MongoDB 8.0** sebagai database utama ✅  
- **Ollama** untuk integrasi AI (Phi3, DeepSeek) ⏳
- **Nginx** sebagai web server ✅
- **Composer** untuk dependency management ✅

## 📁 Project Structure
src/  
├── Config/ # Konfigurasi database dan environment ✅  
├── Repository/ # Interface dan implementasi data access ✅  
├── Service/ # Business logic dan service layer ⏳  
├── Controller/ # HTTP controllers dan routing ⏳  
├── Model/ # Data entities dan domain objects ✅  
├── Middleware/ # Authentication dan validation middleware ✅  
└── Utility/ # Helper functions dan utilities ✅


## ✅ Status Development

### Phase 1: Foundation Setup - COMPLETED ✅
- ✅ PHP 8.4 + MongoDB environment setup ✅
- ✅ Database connection adapter (MongoDBManager) ✅
- ✅ Core interfaces (IRepository, IService)  ✅
- ✅ Logger service & error handling middleware ✅
- ✅ Routing system dengan base path support ✅
- ✅ Comprehensive test suite (23 tests, 69 assertions) ✅
- ✅ Automated index creation ✅

### Phase 2: Core Features - IN PROGRESS ⏳
- ⏳ Authentication System (JWT)
- ⏳ User Management Service
- ⏳ Inventory Management Service
- ⏳ AI Integration

### Phase 3: Advanced Features - PLANNED 📅
- 📅 Category Management
- 📅 Supplier Management  
- 📅 Reporting System
- 📅 Deployment Preparation

## 🚀 Quick Start

### 1. **Prerequisites**
```
bash
# PHP 8.4 + extensions
sudo apt install php8.4 php8.4-fpm php8.4-mongodb php8.4-bcmath php8.4-json php8.4-mbstring php8.4-xml php8.4-zip

# MongoDB
sudo apt install mongodb-server

# Nginx  
sudo apt install nginx

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```
### 2\. **Setup Project**
```
bash
# Clone dan install dependencies
git clone <repository>
cd inventory-ai
composer install

# Setup environment
cp .env.example .env
# Edit .env file dengan konfigurasi MongoDB dan JWT

# Create database indexes
php scripts/create-indexes.php
```

### 3\. **Nginx Configuration** (`/etc/nginx/sites-available/default`)
```
server {
    listen 80;
    server_name localhost;
    root /var/www/html/inventory-ai/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```
### 4\. **Start Services**
```
bash
# Start MongoDB
sudo systemctl start mongodb

# Start PHP-FPM
sudo systemctl start php8.4-fpm

# Start Nginx
sudo systemctl start nginx

# Atau gunakan script startService.sh
sudo ./startService.sh
```

### 5\. **Test API**
```
bash
# Health check
curl http://localhost/inventory-ai/health

# API status
curl http://localhost/inventory-ai/
```

## 📋 API Endpoints

### Available Now ✅

- `GET /` - Health check dan status API
    
- `GET /health` - Service health monitoring
    

### Coming Soon ⏳

- `POST /api/auth/register` - User registration
    
- `POST /api/auth/login` - User login
    
- `GET /api/users` - Get users list
    
- `POST /api/inventory` - Create inventory item
    
- `GET /api/inventory` - Get inventory items
    

## 🧪 Testing

### Run Test Suite
```
bash
# All tests
composer test

# Specific test suites
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Integration/ 
./vendor/bin/phpunit tests/Functional/

# With coverage (requires xdebug/pcov)
./vendor/bin/phpunit --coverage-html tests/coverage
```
### Test Results

- **Unit Tests**: 19 tests, 56 assertions ✅
    
- **Integration Tests**: 4 tests, 13 assertions ✅
    
- **Functional Tests**: 2 tests (skipped when server not running) ✅
    
- **Total**: 23 tests, 69 assertions ✅
    

## 🔧 Development

### Coding Standards

- PSR-12 coding style ✅
    
- Strict types declaration (`declare(strict_types=1)`) ✅
    
- Interface-based design ✅
    
- Dependency injection ✅
    
- Comprehensive testing ✅
    

### Code Quality
```
bash
# Run tests
composer test

# Code style check (coming soon)
composer lint

# Static analysis (coming soon) 
composer analyze
```
## 📊 Database Schema

### Users Collection ✅
```
javascript
{
  _id: ObjectId,
  username: string (unique),
  email: string (unique),
  passwordHash: string,
  role: string['admin', 'manager', 'staff'],
  createdAt: DateTime,
  updatedAt: DateTime
}
```
### Inventory Collection ⏳
```
javascript
{
  _id: ObjectId,
  name: string,
  description: string, 
  quantity: integer,
  price: float,
  category: string,
  supplier: string,
  minStock: integer,
  createdAt: DateTime,
  updatedAt: DateTime
}
```

## 🤝 Contributing

1. Follow PSR-12 coding standards ✅
    
2. Write tests for new features ✅
    
3. Update documentation ✅
    
4. Use conventional commit messages ✅
    
5. Ensure all tests pass before submitting ✅
    

## 📝 License

MIT License - lihat file [LICENSE](https://license/) untuk detail lengkap.

---

**Status**: Phase 1 Completed ✅, Phase 2 In Progress ⏳  
**Last Updated**: {{current\_date}}  
**Test Coverage**: 23 tests, 69 assertions ✅  
**PHP Version**: 8.4.12 ✅  
**MongoDB**: Connected ✅

```

## 🎯 **PERUBAHAN PENTING:**

1. **✅ PHP Version**: Diupdate dari 8.3 → 8.4.12 (sesuai actual environment)
2. **✅ Status Development**: Clear progress indicators (✅⏳📅)
3. **✅ Test Results**: Added actual test metrics (23 tests, 69 assertions)
4. **✅ Nginx Config**: Updated untuk PHP 8.4
5. **✅ API Endpoints**: Dibedakan yang available vs coming soon
6. **✅ Database Schema**: Status completion untuk tiap collection
7. **✅ Tech Stack**: Actual versions yang terverified

```

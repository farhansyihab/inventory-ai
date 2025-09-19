# 📋 Inventory AI PHP - Project Plan & Documentation

## 🎯 Project Overview

**Inventory Management System dengan AI Integration** menggunakan PHP + MongoDB + Ollama

---

## 📊 SOLUSI: UML sebagai Dokumentasi + PHP Code Generator

### 🔄 Dual Representation Strategy

#### 1\. **UML Diagram** (Visual Documentation)

- **Purpose**: High-level design dan relationship visualization
    
- **Tools**: Mermaid.js dalam Markdown
    
- **Maintenance**: Update manual, fokus pada struktur bukan detail implementasi
    

#### 2\. **PHP Code** (Actual Implementation)

- **PHP 8.0+** dengan strict types dan type declarations
    
- **Full type hints** untuk parameters dan return values
    
- **Interface-based design** dengan dependency injection
    

### 📝 Contoh Mapping UML ↔ PHP

**UML Declaration:**
```
mermaid
classDiagram
    class IRepository {
        <<interface>>
        +findById(string id)
        +find(array filter = [])
        +create(array data)
        +update(string id, array data)
        +delete(string id)
    }
```
PHP Implementation:
```
php
<?php
declare(strict_types=1);

namespace App\Repository;

interface IRepository
{
    public function findById(string $id): ?array;
    public function find(array $filter = []): array;
    public function create(array $data): string;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
}
```
🎨 UML sebagai Living Documentation

Pertahankan UML untuk:

    Architecture overview

    Class relationships

    Dependency visualization

    Team communication

Gunakan PHP code untuk:

    Actual implementation

    Type safety

    IDE support

    Runtime execution

### 🖥️ Rekomendasi Setup Pengembangan
### 📋 Hardware Configuration
Laptop 1 (Asus VivoBook - Primary Development)

    RAM: 12GB ✅

    Storage: SSD ✅

    Role: Full stack development + AI processing

    Services:

        PHP 8.3+

        Nginx

        MongoDB

        Ollama (Phi3 + DeepSeek)

Laptop 2 (Acer Aspire - Optional Testing)

    RAM: 4GB ✅

    Storage: SATA ✅

    Role: Dedicated web server testing

    Services: Nginx + PHP-FPM + MongoDB

🌐 Network Configuration
```
bash
# Development Mode (Single Laptop)
Web Server: localhost:80
MongoDB: localhost:27017
Ollama: localhost:11434

# Testing Mode (Dual Laptop)
Laptop 1 (Asus): 192.168.1.100 - Ollama AI
Laptop 2 (Acer): 192.168.1.101 - Web + DB
```
### 🔧 Software Stack

#### Core Dependencies
```
bash
# PHP Extensions
sudo apt install php8.1 php8.1-fpm php8.1-mongodb \
php8.1-bcmath php8.1-json php8.1-mbstring php8.1-xml php8.1-zip

# MongoDB
sudo apt install mongodb-server

# Web Server
sudo apt install nginx

# Ollama (already installed)
```
### PHP Dependencies via Composer
```
json
{
  "require": {
    "mongodb/mongodb": "^1.15",
    "firebase/php-jwt": "^6.8",
    "vlucas/phpdotenv": "^5.5"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.6",
    "squizlabs/php_codesniffer": "^3.7"
  }
}
```
### ⚙️ Development Environment Setup
```
bash
# 1. Install software stack
sudo apt update
sudo apt install nginx php8.3-fpm php8.3-mongodb mongodb-server

# 2. Configure PHP
sudo nano /etc/php/8.3/fpm/php.ini
# Set: memory_limit = 512M, opcache.enable=1

# 3. Configure MongoDB memory limit
sudo nano /etc/mongod.conf
# Set: storage.wiredTiger.engineConfig.cacheSizeGB = 1.0

# 4. Install Composer dependencies
composer install

# 5. Start services
sudo systemctl start nginx php8.3-fpm mongodb
```
## 🚀 Action Plan & Roadmap

### 📅 Phase 1: Foundation Setup (Week 1)

#### Day 1-2: Environment Setup

- Install PHP 8.1 + extensions **✅**
    
- Install MongoDB dan configure memory limit **✅**
    
- Setup Nginx virtual host **✅**
    
- Initialize Composer project **✅**
    

#### Day 3-4: Core Architecture

- Create base directory structure **✅**
    
- Implement Database connection adapter  **✅**
    
- Create core interfaces (IRepository, IService) 
    
- Setup environment configuration
    

#### Day 5-7: Basic Infrastructure

- Implement Logger service
    
- Create validation utilities
    
- Setup error handling middleware
    
- Implement basic routing
    

### 📅 Phase 2: Core Features (Week 2)

#### Day 1-3: Authentication System

- Implement UserRepository
    
- Create JWT Token service
    
- Implement Auth middleware
    
- Create login/register endpoints
    

#### Day 4-5: Inventory Management

- Implement InventoryRepository
    
- Create InventoryService
    
- Implement CRUD operations
    
- Add validation rules
    

#### Day 6-7: AI Integration

- Create AI Strategy interface
    
- Implement Ollama connector
    
- Integrate AI with inventory service
    
- Create analysis endpoints
    

### 📅 Phase 3: Advanced Features (Week 3)

#### Day 1-2: Additional Modules

- Implement Category management
    
- Create Supplier management
    
- Add audit logging system
    
- Implement reporting service
    

#### Day 3-4: Testing & Quality

- Write PHPUnit tests
    
- Implement CI/CD pipeline
    
- Code quality checks (PHPCS)
    
- Performance optimization
    

#### Day 5-7: Deployment Prep

- Environment configuration
    
- Database migration scripts
    
- Deployment documentation
    
- Final testing
    

---

## 📝 Catatan Penting Proyek

### 🔐 Security Considerations
```php
// Always use prepared statements for MongoDB
$filter = ['_id' => new ObjectId($id)];
// Never concatenate user input directly!

// Password hashing
password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// JWT Configuration
$token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
```
### 🗃️ Database Design Notes
```
javascript
// User Collection
{
  _id: ObjectId,
  username: string (unique),
  passwordHash: string,
  email: string,
  role: string['admin', 'manager', 'staff'],
  createdAt: ISODate,
  updatedAt: ISODate
}

// Inventory Collection  
{
  _id: ObjectId,
  name: string,
  description: string,
  quantity: integer,
  price: float,
  categoryId: ObjectId,
  supplierId: ObjectId,
  minStock: integer,
  createdAt: ISODate,
  updatedAt: ISODate
}
```
### ⚡ Performance Optimizations
```
php
// MongoDB Indexes
db.users.createIndex({ "username": 1 }, { unique: true });
db.inventory.createIndex({ "categoryId": 1 });
db.inventory.createIndex({ "quantity": 1 });
db.inventory.createIndex({ "supplierId": 1 });

// PHP OPcache configuration
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```
### 🔧 Development Best Practices

    Always use strict types: declare(strict_types=1)

    Follow PSR-12 coding standards

    Write tests for all business logic

    Use interface-based design

    Implement proper error handling

    Document all public methods

    Use environment variables for configuration

### 🐛 Debugging Tips
```
bash
# Enable XDebug for development
sudo apt install php8.1-xdebug

# MongoDB query debugging
db.setProfilingLevel(1, 100); // Log queries > 100ms

# PHP error logging
ini_set('display_errors', '1');
ini_set('error_log', '/var/log/php_errors.log');
```
### 📈 Monitoring & Maintenance
```
bash
# System monitoring
htop
glances

# Log monitoring
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log  
tail -f /var/log/mongodb/mongod.log

# Performance testing
ab -n 1000 -c 100 http://localhost/api/health
```

## 🎯 Success Metrics
    catatan :
    ✅ = sudah
    ❌ = belum
### Phase 1 Completion

    PHP environment setup complete ✅

    MongoDB running with proper configuration ✅

    Basic routing and structure in place ❓

    Core interfaces defined ❓

### Phase 2 Completion

    Authentication system working ❌

    Inventory CRUD operations implemented ❌

    AI integration functional ❌

    Basic frontend connectivity ❌

### Phase 3 Completion

    All planned features implemented ❌

    Test coverage > 80% ❌

    Performance benchmarks met ❌

    Documentation complete ❌




### 📞 Support & Troubleshooting
### Common Issues

    MongoDB connection refused: Check if service is running ✅ solved

    PHP extensions not loaded: Restart PHP-FPM service ✅ solved

    Ollama not responding: Check if model is loaded ✅ solved

    Permission issues: Check Nginx and PHP file permissions ✅ solved

### Useful Commands
```
bash
# Service management
sudo systemctl status nginx
sudo systemctl restart php8.1-fpm

# Log viewing
sudo tail -f /var/log/nginx/error.log
journalctl -u mongod -f

# Debugging
php -m | grep mongodb  # Check if extension loaded
curl http://localhost:11434/api/tags # Test Ollama
```

## UML Diagram (disesuaikan dengan mermaid rule):

1. **Menghapus tipe parameter** - Mermaid tidak mendukung deklarasi tipe parameter yang detail
    
2. **Menggunakan tipe return sederhana** - String, Boolean, Array
```
mermaid
classDiagram
    %% ========== Interfaces / Abstractions ==========
    class IRepository {
        <<interface>>
        +findById(id)
        +find(filter)
        +create(data)
        +update(id, data)
        +delete(id)
    }

    class IUserRepository {
        <<interface>>
        +findByUsername(username)
    }

    class IInventoryRepository {
        <<interface>>
        +findLowStock(threshold)
        +aggregate(pipeline)
    }

    class ICategoryRepository {
        <<interface>>
    }

    class ISupplierRepository {
        <<interface>>
    }

    class ITokenRepository {
        <<interface>>
        +storeRefreshToken(tokenHash, userId, expires)
        +revoke(tokenHash)
        +find(tokenHash)
    }

    class IHashService {
        <<interface>>
        +hash(password) String
        +verify(password, hash) Boolean
    }

    class ITokenService {
        <<interface>>
        +generateAccessToken(user) String
        +generateRefreshToken(user) String
        +verifyAccessToken(token) Array~Boolean~
        +revokeRefreshToken(refreshToken)
    }

    class AIStrategy {
        <<interface>>
        +generate(prompt) Array
    }

    %% ========== Concrete Implementations ==========
    class MongoUserRepository
    class MongoInventoryRepository
    class MongoCategoryRepository
    class MongoSupplierRepository
    class MongoTokenRepository

    class BcryptHashService
    class JwtTokenService

    class Phi3Strategy
    class DeepSeekStrategy
    class OllamaStrategy

    IUserRepository <|.. MongoUserRepository
    IInventoryRepository <|.. MongoInventoryRepository
    ICategoryRepository <|.. MongoCategoryRepository
    ISupplierRepository <|.. MongoSupplierRepository
    ITokenRepository <|.. MongoTokenRepository
    IHashService <|.. BcryptHashService
    ITokenService <|.. JwtTokenService
    AIStrategy <|.. Phi3Strategy
    AIStrategy <|.. DeepSeekStrategy
    AIStrategy <|.. OllamaStrategy

    %% ========== Services ==========
    class UserService {
        +register(dto) Array
        +login(dto) Array
        +getProfile(id) Array
    }

    class AuthService {
        +login(credentials)
        +refresh(refreshToken)
        +logout(refreshToken)
    }

    class InventoryService {
        +createItem(dto)
        +updateItem(id, dto)
        +deleteItem(id)
        +getItem(id)
        +listItems(filter)
        +getLowStock(threshold)
        +generateReport(type)
    }

    class CategoryService {
        +addCategory(dto)
        +listCategories() Array
    }

    class SupplierService {
        +addSupplier(dto)
        +listSuppliers() Array
    }

    class AuditLogService {
        +record(userId, action, meta)
        +listLogs(filter) Array
    }

    class AIService {
        -strategies Map
        +registerStrategy(name, strategy)
        +setStrategy(name)
        +analyzeInventory(items)
    }

    %% Dependencies
    UserService --> IUserRepository
    UserService --> IHashService
    UserService --> ITokenService
    AuthService --> IUserRepository
    AuthService --> ITokenService
    AuthService --> ITokenRepository
    InventoryService --> IInventoryRepository
    InventoryService --> ICategoryRepository
    InventoryService --> ISupplierRepository
    InventoryService --> AIService
    InventoryService --> AuditLogService
    AIService --> AIStrategy

    %% Controllers
    class UserController {
        +register(req, res)
        +login(req, res)
        +profile(req, res)
    }

    class InventoryController {
        +create(req, res)
        +update(req, res)
        +delete(req, res)
        +list(req, res)
    }

    class CategoryController {
        +create(req, res)
        +list(req, res)
    }

    class SupplierController {
        +create(req, res)
        +list(req, res)
    }

    class AuthController {
        +login(req, res)
        +refresh(req, res)
        +logout(req, res)
    }

    UserController --> UserService
    InventoryController --> InventoryService
    CategoryController --> CategoryService
    SupplierController --> SupplierService
    AuthController --> AuthService

    %% Middleware / Utilities
    class AuthMiddleware {
        +verifyAccessToken(req, res, next)
    }

    class RoleMiddleware {
        +requireRole(role)
    }

    class Validator {
        +validate(schema, data)
    }

    class Logger
    class Config
    class MongoClientAdapter {
        +getDB() Database
        +startSession()
    }

    InventoryService --> MongoClientAdapter
    AuthService --> MongoClientAdapter

    %% ========== Domain Entities ==========
    class User {
        +_id String
        +username String
        +passwordHash String
        +role String
        +createdAt DateTime
        +updatedAt DateTime
    }

    class InventoryItem {
        +_id String
        +name String
        +description String
        +quantity Number
        +price Number
        +categoryId String
        +supplierId String
        +minStock Number
        +createdAt DateTime
        +updatedAt DateTime
    }

    class Category {
        +_id String
        +name String
        +createdAt DateTime
        +updatedAt DateTime
    }

    class Supplier {
        +_id String
        +name String
        +contactInfo String
        +createdAt DateTime
        +updatedAt DateTime
    }

    class AuditLog {
        +_id String
        +userId String
        +action String
        +timestamp DateTime
        +meta String
    }

    InventoryItem --> Category
    InventoryItem --> Supplier
    AuditLog --> User

    %% ========== App Entrypoint ==========
    class Application {
        +initialize()
        +start()
        +shutdown()
    }
    Application --> Config
    Application --> Logger
    Application --> MongoClientAdapter
    Application --> UserController
    Application --> InventoryController
    Application --> CategoryController
    Application --> SupplierController
    Application --> AuthController
```

### UML Diagram asli  (masih dengan deklarasi PHP)
masih dengan deklarasi tipe parameter yang detail
tipe return sesuai PHP

```
mermaid
classDiagram
    %% ========== Interfaces / Abstractions ==========
    class IRepository {
        <<interface>>
        +findById(string id)
        +find(array filter = [])
        +create(array data)
        +update(string id, array data)
        +delete(string id)
    }

    class IUserRepository {
        <<interface>>
        +findByUsername(string username)
    }

    class IInventoryRepository {
        <<interface>>
        +findLowStock(int threshold)
        +aggregate(array pipeline)
    }

    class ICategoryRepository {
        <<interface>>
    }

    class ISupplierRepository {
        <<interface>>
    }

    class ITokenRepository {
        <<interface>>
        +storeRefreshToken(string tokenHash, string userId, DateTime expires)
        +revoke(string tokenHash)
        +find(string tokenHash)
    }

    class IHashService {
        <<interface>>
        +hash(string password): string
        +verify(string password, string hash): bool
    }

    class ITokenService {
        <<interface>>
        +generateAccessToken(array user): string
        +generateRefreshToken(array user): string
        +verifyAccessToken(string token): array|false
        +revokeRefreshToken(string refreshToken)
    }

    class AIStrategy {
        <<interface>>
        +generate(string prompt): array
    }

    %% ========== Concrete Implementations ==========
    class MongoUserRepository
    class MongoInventoryRepository
    class MongoCategoryRepository
    class MongoSupplierRepository
    class MongoTokenRepository

    class BcryptHashService
    class JwtTokenService

    class Phi3Strategy
    class DeepSeekStrategy
    class OllamaStrategy

    IUserRepository <|.. MongoUserRepository
    IInventoryRepository <|.. MongoInventoryRepository
    ICategoryRepository <|.. MongoCategoryRepository
    ISupplierRepository <|.. MongoSupplierRepository
    ITokenRepository <|.. MongoTokenRepository
    IHashService <|.. BcryptHashService
    ITokenService <|.. JwtTokenService
    AIStrategy <|.. Phi3Strategy
    AIStrategy <|.. DeepSeekStrategy
    AIStrategy <|.. OllamaStrategy

    %% ========== Services ==========
    class UserService {
        +register(array dto): array
        +login(array dto): array
        +getProfile(string id): array
    }

    class AuthService {
        +login(array credentials)
        +refresh(string refreshToken)
        +logout(string refreshToken)
    }

    class InventoryService {
        +createItem(array dto)
        +updateItem(string id, array dto)
        +deleteItem(string id)
        +getItem(string id)
        +listItems(array filter)
        +getLowStock(int threshold)
        +generateReport(string type)
    }

    class CategoryService
    class SupplierService
    class AuditLogService
    class AIService {
        -map<string, AIStrategy> strategies
        +registerStrategy(string name, AIStrategy s)
        +setStrategy(string name)
        +analyzeInventory(array items)
    }

    %% Dependencies
    UserService --> IUserRepository
    UserService --> IHashService
    UserService --> ITokenService
    AuthService --> IUserRepository
    AuthService --> ITokenService
    AuthService --> ITokenRepository
    InventoryService --> IInventoryRepository
    InventoryService --> ICategoryRepository
    InventoryService --> ISupplierRepository
    InventoryService --> AIService
    InventoryService --> AuditLogService
    AIService --> AIStrategy

    %% Controllers
    class UserController
    class InventoryController
    class CategoryController
    class SupplierController
    class AuthController

    UserController --> UserService
    InventoryController --> InventoryService
    CategoryController --> CategoryService
    SupplierController --> SupplierService
    AuthController --> AuthService

    %% Middleware / Utilities
    class AuthMiddleware
    class RoleMiddleware
    class Validator
    class Logger
    class Config
    class MongoClientAdapter {
        +getDB(): MongoDB\\Database
        +startSession()
    }

    InventoryService --> MongoClientAdapter
    AuthService --> MongoClientAdapter

    %% Entities
    class User {
        +string _id
        +string username
        +string passwordHash
        +string role
        +DateTime createdAt
        +DateTime updatedAt
    }

    class InventoryItem {
        +string _id
        +string name
        +string description
        +int quantity
        +float price
        +string categoryId
        +string supplierId
        +int minStock
        +DateTime createdAt
        +DateTime updatedAt
    }

    class Category
    class Supplier
    class AuditLog

    InventoryItem --> Category
    InventoryItem --> Supplier
    AuditLog --> User

    %% Application entry
    class Application
    Application --> Config
    Application --> Logger
    Application --> MongoClientAdapter
    Application --> UserController
    Application --> InventoryController
    Application --> AuthController
```

**Terakhir Diupdate**: {{current\_date}}  
**Status**: Planning Phase 🟡  
**Tim**: Farhan (Developer) + AI Assistant
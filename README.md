# 🚀 Inventory AI - Management System

Sistem manajemen inventory dengan integrasi AI menggunakan PHP 8.3 + MongoDB + Ollama.

## 🛠️ Tech Stack

- **PHP 8.3** dengan strict types dan type declarations
- **MongoDB 8.0** sebagai database utama
- **Ollama** untuk integrasi AI (Phi3, DeepSeek)
- **Nginx** sebagai web server
- **Composer** untuk dependency management

## 📁 Project Structure
```
text
src/  
├── Config/ # Konfigurasi database dan environment  
├── Repository/ # Interface dan implementasi data access  
├── Service/ # Business logic dan service layer  
├── Controller/ # HTTP controllers dan routing  
├── Model/ # Data entities dan domain objects  
├── Middleware/ # Authentication dan validation middleware  
└── Utility/ # Helper functions dan utilities
```

## 🚀 Quick Start

1. **Clone dan install dependencies:**
   ```
   bash
   composer install
   ```

2. **Setup environment::**
```
bash
cp .env.example .env
# Edit .env file dengan konfigurasi MongoDB dan JWT
```
bash
3. **Start services:**
```
sudo ./startService.sh
```
4. **Test API:**
```
bash
curl http://localhost/inventory-ai/public/
```
## 📋 API Endpoints

- `GET /` - Health check dan status API
    
- `POST /api/auth/register` - User registration
    
- `POST /api/auth/login` - User login
    
- `GET /api/inventory` - Get inventory items
    
- `POST /api/inventory` - Create new inventory item
    

## 🔧 Development

### Coding Standards

- PSR-12 coding style
    
- Strict types declaration (`declare(strict_types=1)`)
    
- Interface-based design
    
- Dependency injection
    

### Testing
```
bash
# Run PHPUnit tests
composer test

# Code style check
composer lint
```

## 📊 Database Schema

### Users Collection
```
javascript
{
  _id: ObjectId,
  username: string (unique),
  email: string,
  passwordHash: string,
  role: string['admin', 'manager', 'staff'],
  createdAt: DateTime,
  updatedAt: DateTime
}
```
## 🤝 Contributing

1. Follow PSR-12 coding standards
    
2. Write tests for new features
    
3. Update documentation
    
4. Use conventional commit messages
    

## 📝 License

MIT License - lihat file [LICENSE](https://license/) untuk detail lengkap.

---

**Status**: Development Phase 1 ✅  
**Last Updated**: {{current\_date}}


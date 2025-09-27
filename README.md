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
- ✅ Core interfaces (IRepository, IService) ✅
- ✅ Logger service & error handling middleware ✅
- ✅ Routing system dengan base path support ✅
- ✅ Comprehensive test suite (23 tests, 69 assertions) ✅
- ✅ Automated index creation ✅
    

## ✅ **PHASE 2 PROGRESS!**
### 📊 **Yang Sudah Selesai di Phase 2:**
#### 🔐 **Authentication & User Management** (100%)
- ✅ UserService dengan business logic lengkap  ✅ 
- ✅ AuthService dengan JWT authentication ✅ 
- ✅ UserController & AuthController ✅ 
- ✅ Comprehensive testing (14 tests, 36 assertions) ✅ 
    

#### 📦 **Inventory Management** (100%)
- ✅ Inventory Model dengan validation robust (11 tests, 53 assertions) ✅ 
- ✅ Inventory Repository dengan MongoDB implementation (8 tests, 24 assertions) ✅ 
- ✅ Inventory Service dengan business logic lengkap (14 tests, 36 assertions) ✅ 
- ✅ Inventory Controller dengan RESTful API endpoints ✅ 
- ✅ Full CRUD operations + advanced features ✅ 
    

#### 🧪 **Testing Excellence**
- ✅ **Total Tests:** 47 tests, 149 assertions
- ✅ **Test Coverage:** 100% untuk core functionality
- ✅ **No Regression:** Semua test passing

 **AI Integration** (100%) ✅
    - AI Strategy interface ✅
    - Ollama connector ✅
    - AI integration dengan inventory ✅
    - Analysis endpoints ✅

### Phase 3: Advanced Features - Running  ⏳
  - ✅ Category Management ✅
  - ⏳ Dashboard Service & Metrics ⏳   
  - 📅 Supplier Management 
  - 📅 Reporting System
  - 📅 Deployment Preparation
---
## Class Diagram

```mermaid
classDiagram
    note for MongoDBManager "Singleton pattern untuk MongoDB connection"
    
    %% ========== Interfaces / Abstractions ==========
    class IRepository {
        <<interface>>
        +findById(string id) array|null
        +find(array filter = [], array options = []) array
        +create(array data) string
        +update(string id, array data) bool
        +delete(string id) bool
        +count(array filter = []) int
        +findOne(array filter = []) array|null
    }

    class ICategoryRepository {
        <<interface>>
        +findBySlug(string slug) array|null
        +findActive() array
        +findByParentId(string parentId) array
        +findRootCategories() array
        +getCategoryTree() array
        +findByDepth(int depth) array
        +updatePath(string categoryId, array path, int depth) bool
        +slugExists(string slug, string excludeId = null) bool
        +getCategoriesWithCounts() array
        +bulkUpdateStatus(array categoryIds, bool active) bool
    }

    class IService {
        <<interface>>
        +findById(string id) array|null
        +find(array filter = [], array options = []) array
        +create(array data) array
        +update(string id, array data) bool
        +delete(string id) bool
        +count(array filter = []) int
        +validate(array data) bool
        +findOne(array filter = []) array|null
    }

    %% ========== Concrete Implementations (UPDATED) ==========
    class Category {
        -id string|null
        -name string
        -slug string
        -description string
        -active bool
        -parentId string|null
        -depth int
        -path array
        -createdAt DateTime
        -updatedAt DateTime
        +__construct(string name, string slug, string description = '', bool active = true, string parentId = null, string id = null, DateTime createdAt = null, DateTime updatedAt = null)
        +getId() string|null
        +getName() string
        +getSlug() string
        +getDescription() string
        +isActive() bool
        +getParentId() string|null
        +getDepth() int
        +getPath() array
        +getCreatedAt() DateTime
        +getUpdatedAt() DateTime
        +setName(string name) void
        +setSlug(string slug) void
        +setDescription(string description) void
        +setActive(bool active) void
        +setParentId(string parentId) void
        +setDepth(int depth) void
        +setPath(array path) void
        +setUpdatedAt(DateTime updatedAt) void
        +validate() void
        +toDocument() array
        +fromDocument(array document) Category
        +toArray() array
        +__toString() string
        +isRoot() bool
        +hasChildren() bool
        +getFullPath() string
        -parseDate(mixed dateValue) DateTime
    }

    class MongoCategoryRepository {
        -collection Collection
        -logger Logger
        +__construct(Logger logger = null)
        +createIndexes() array
        +findById(string id) array|null
        +find(array filter = [], array options = []) array
        +findOne(array filter = []) array|null
        +create(array data) string
        +update(string id, array data) bool
        +delete(string id) bool
        +count(array filter = []) int
        +findBySlug(string slug) array|null
        +findActive() array
        +findByParentId(string parentId) array
        +findRootCategories() array
        +getCategoryTree() array
        +findByDepth(int depth) array
        +updatePath(string categoryId, array path, int depth) bool
        +slugExists(string slug, string excludeId = null) bool
        +getCategoriesWithCounts() array
        +bulkUpdateStatus(array categoryIds, bool active) bool
        -documentToArray(mixed document) array
        -normalizeToUTCDateTime(mixed value) UTCDateTime
    }

    class CategoryService {
        -categoryRepo ICategoryRepository
        -logger Logger
        +__construct(ICategoryRepository categoryRepo, Logger logger)
        +findById(string id) array|null
        +find(array filter = [], array options = []) array
        +create(array data) array
        +update(string id, array data) bool
        +delete(string id) bool
        +count(array filter = []) int
        +validate(array data) bool
        +findOne(array filter = []) array|null
        +getCategoryTree() array
        +getSubcategories(string parentId) array
        +getRootCategories() array
        +getCategoryPath(string categoryId) array
        +moveCategory(string categoryId, string newParentId) array
        +validateCategoryData(array data, bool isUpdate = false) array
        +bulkUpdateStatus(array categoryIds, bool active) array
        +getCategoryStatistics() array
        +categoryExists(string id) bool
        +slugExists(string slug, string excludeId = null) bool
        +findBySlug(string slug) array|null
        -updateCategoryTree(string categoryId) void
    }

    class BaseController {
        #logger Logger
        #requestData array
        #testMode bool
        #lastResponse array|null
        +__construct(Logger logger = null)
        +enableTestMode() void
        +setRequestData(array data) void
        #parseRequestData() void
        #getRequestValue(string key, mixed default) mixed
        #getRequestData() array
        #jsonResponse(array data, int statusCode = 200) array|null
        #successResponse(array data = [], string message = 'Success', int statusCode = 200) array|null
        #errorResponse(string message, array errors = [], int statusCode = 400) array|null
        #notFoundResponse(string message = 'Resource not found') array|null
        #unauthorizedResponse(string message = 'Unauthorized') array|null
        #validationErrorResponse(array errors, string message = 'Validation failed') array|null
        #getAuthUserId() string|null
        #isAuthenticated() bool
        #validateRequiredFields(array fields) array
        #logAction(string action, array context = []) void
        #getPaginationParams() array
        #getSortingParams() array
        +buildTestResponse(array data = [], int statusCode = 200, bool success = true, string message = '') array
        +getLastResponse() array
    }

    class CategoryController {
        -categoryService CategoryService
        +__construct(CategoryService categoryService, Logger logger)
        +listCategories() void
        +getCategory(string id) void
        +getCategoryBySlug(string slug) void
        +createCategory() void
        +updateCategory(string id) void
        +deleteCategory(string id) void
        +getCategoryTree() void
        +getSubcategories(string id) void
        +getRootCategories() void
        +getCategoryPath(string id) void
        +moveCategory(string id) void
        +bulkUpdateStatus() void
        +getStatistics() void
        +searchCategories() void
        -validateCategoryId(string id) void
        -validateCreateCategoryData(array data) array
        -validateUpdateCategoryData(array data) array
        -buildFilterFromRequest() array
        -logAction(string action, array context = []) void
    }

    class MongoDBManager {
        -static Client client
        -static Database database
        -static LoggerInterface logger
        +initialize(LoggerInterface logger = null) void
        +getClient() Client
        +getDatabase() Database
        +getCollection(string name) Collection
        +ping() bool
        +startSession() Session|null
        +getConnectionInfo() array
        +createIndexes(string collectionName, array indexes) array
        +collectionExists(string collectionName) bool
        +getStats() array
        +getCollectionStats(string collectionName) array
        +dropCollection(string collectionName) array
        +getServerInfo() array
        +getServerVersion() array
        +reset() void
        +getLogger() LoggerInterface
        +setLogger(LoggerInterface logger) void
    }

    class Logger {
        -logFile string
        -defaultLevel string
        +__construct(string logFile = null, string defaultLevel = 'INFO')
        +log(mixed level, string|Stringable message, array context = []) void
        +debug(string|Stringable message, array context = []) void
        +info(string|Stringable message, array context = []) void
        +error(string|Stringable message, array context = []) void
        +warning(string|Stringable message, array context = []) void
        +getLogFile() string
    }

    %% ========== NEWLY IMPLEMENTED COMPONENTS ==========
    class Router {
        -routes array
        -routeGroups array
        -notFoundHandler callable
        -currentGroupPrefix string
        -testMode bool
        +__construct()
        +enableTestMode() void
        +get(string path, mixed handler) self
        +post(string path, mixed handler) self
        +put(string path, mixed handler) self
        +delete(string path, mixed handler) self
        +patch(string path, mixed handler) self
        +options(string path, mixed handler) self
        +any(string path, mixed handler) self
        +addRoute(string method, string path, mixed handler) self
        +group(string prefix, callable callback) self
        +setNotFoundHandler(callable handler) self
        +dispatch(string method, string path) mixed
        -executeHandler(mixed handler, array params = []) mixed
        -matchRoute(string routePath, string requestPath, array& params) bool
        -handleNotFound() mixed
        -normalizePath(string path) string
        +getRoutes() array
        +clearRoutes() void
    }

    %% ========== Relationships (UPDATED) ==========
    IRepository <|.. MongoCategoryRepository
    ICategoryRepository <|.. MongoCategoryRepository
    IService <|.. CategoryService
    
    MongoCategoryRepository --> MongoDBManager : uses
    CategoryService --> ICategoryRepository : depends on
    CategoryService --> Logger : depends on
    CategoryController --> CategoryService : depends on
    CategoryController --> BaseController : extends
    BaseController --> Logger : depends on

    %% ========== Planned Components (Tetap Dipertahankan) ==========
    class IInventoryRepository {
        <<interface>>
        +find(array filter = [], array options = []) array
        +findById(string id) array|null
        +findOne(array filter = []) array|null
        +create(array data) string
        +update(string id, array data) bool
        +delete(string id) bool
        +count(array filter = []) int
        +findLowStock(int threshold = 0) array
        +findOutOfStock() array
        +updateQuantity(string id, int quantityChange) bool
        +getStats() array
        +aggregate(array pipeline) array
    }

    class IAuthService {
        <<interface>>
        +register(array userData) array
        +login(string username, string password) array
        +refreshToken(string refreshToken) array
        +logout(string refreshToken) bool
        +verifyCredentials(string username, string password) array|false
        +changePassword(string userId, string currentPassword, string newPassword) bool
    }

    class ITokenService {
        <<interface>>
        +generateAccessToken(array user) string
        +generateRefreshToken(array user) string
        +verifyAccessToken(string token) array|false
        +verifyRefreshToken(string token) array|false
        +revokeRefreshToken(string token) bool
        +isRefreshTokenRevoked(string token) bool
        +getAccessTokenExpiry() int
        +getRefreshTokenExpiry() int
    }

    class IInventoryService {
        <<interface>>
        +getItem(string id) array|null
        +listItems(array filter = [], array options = []) array
        +createItem(array data) array
        +updateItem(string id, array data) array
        +deleteItem(string id) bool
        +getLowStockItems(int threshold = 0) array
        +getOutOfStockItems() array
        +updateItemQuantity(string id, int quantityChange) array
        +getInventoryStats() array
        +searchItems(string query, array options = []) array
        +validateItemData(array data, bool isCreate = true) array
        +getItemsByCategory(string categoryId, array options = []) array
        +getItemsBySupplier(string supplierId, array options = []) array
    }

    class IAIService {
        <<interface>>
        +analyzeInventory(array inventoryData, string analysisType) array
        +generateReport(array inventoryData, string reportType) array
        +predictStockNeeds(array items, int forecastDays) array
        +detectAnomalies(array inventoryData) array
        +setStrategy(string strategyName) bool
        +getAvailableStrategies() array
        +isAvailable() bool
    }

    class IAdvancedAIService {
        <<interface>>
        +analyzeSalesTrends(array salesData, int periodDays) array
        +predictInventoryTurnover(array items) array
        +optimizeStockLevels(array inventoryData) array
        +generatePurchaseRecommendations(array supplierData) array
        +calculateSafetyStock(array itemHistory) array
        +analyzeInventoryWithStats(array inventoryData, string analysisType) array
    }

    class IInventoryAnalysisService {
        <<interface>>
        +getComprehensiveAnalysis(array options) array
        +generateWeeklyReport() array
        +monitorCriticalItems() array
        +predictInventoryNeeds(int forecastDays) array
        +optimizeInventory() array
    }

    class AIStrategy {
        <<interface>>
        +analyze(array data, string analysisType) array
        +generate(array data, string reportType) array
        +isAvailable() bool
    }

    class ITokenRepository {
        <<interface>>
        +storeRefreshToken(string tokenHash, string userId, DateTime expiresAt) bool
        +revokeRefreshToken(string tokenHash) bool
        +isRefreshTokenRevoked(string tokenHash) bool
        +findRefreshToken(string tokenHash) array|null
        +cleanupExpiredTokens() int
    }

    %% ========== Future Concrete Implementations ==========
    class UserRepository {
        +__construct(Collection collection = null, LoggerInterface logger = null)
        +findUserById(string id) User|null
        +findUserByUsername(string username) User|null
        +findUserByEmail(string email) User|null
        +saveUser(User user) string
        +deleteUser(User user) bool
        +usernameExists(string username) bool
        +emailExists(string email) bool
        +createIndexes() array
        -documentToArray(mixed document) array
        -normalizeToUTCDateTime(mixed value) UTCDateTime
        -parseDuplicateError(string mongoMessage) string
    }

    class InventoryRepository {
        +__construct(LoggerInterface logger = null)
        +createIndexes() array
        -documentToArray(mixed document) array
        -normalizeToUTCDateTime(mixed value) UTCDateTime
    }

    class MongoTokenRepository {
        +__construct(Logger logger = null)
        +storeRefreshToken(string tokenHash, string userId, DateTime expiresAt) bool
        +revokeRefreshToken(string tokenHash) bool
        +isRefreshTokenRevoked(string tokenHash) bool
        +findRefreshToken(string tokenHash) array|null
        +cleanupExpiredTokens() int
    }

    class UserService {
        +__construct(UserRepository userRepository, Logger logger)
        +findByUsername(string username) array|null
        +findByEmail(string email) array|null
        -convertToArray(array userData) array
    }

    class InventoryService {
        +__construct(IInventoryRepository inventoryRepo, Logger logger)
        +getItemsByCategory(string categoryId, array options = []) array
        +getItemsBySupplier(string supplierId, array options = []) array
        -calculateInventoryHealth(array stats) string
    }

    class AuthService {
        +__construct(UserService userService, ITokenService tokenService, Logger logger)
        +validatePasswordStrength(string password) array
        -generateTokens(array user) array
    }

    class JwtTokenService {
        +__construct(string secretKey, string algorithm, int accessTokenExpiry, int refreshTokenExpiry, Logger logger, ITokenRepository tokenRepository)
        +generateAccessToken(array user) string
        +generateRefreshToken(array user) string
        +verifyAccessToken(string token) array|false
        +verifyRefreshToken(string token) array|false
        +revokeRefreshToken(string token) bool
        +isRefreshTokenRevoked(string token) bool
        +getAccessTokenExpiry() int
        +getRefreshTokenExpiry() int
    }

    class AIService {
        -strategies array
        -activeStrategy AIStrategy|null
        -logger Logger
        -enabled bool
        +__construct(Logger logger, bool enabled = true)
        +registerStrategy(string name, AIStrategy strategy) void
        +setStrategy(string strategyName) bool
        +analyzeInventory(array inventoryData, string analysisType) array
        +generateReport(array inventoryData, string reportType) array
        +predictStockNeeds(array items, int forecastDays) array
        +detectAnomalies(array inventoryData) array
        +getAvailableStrategies() array
        +isAvailable() bool
        +analyzeSalesTrends(array salesData, int periodDays) array
        +predictInventoryTurnover(array items) array
        +optimizeStockLevels(array inventoryData) array
        +generatePurchaseRecommendations(array supplierData) array
        +calculateSafetyStock(array itemHistory) array
        +analyzeInventoryWithStats(array inventoryData, string analysisType) array
        -validateInventoryData(array data) void
        -getFallbackAnalysis(array inventoryData, string analysisType) array
        -getFallbackReport(array inventoryData, string reportType) array
        -validateSalesData(array salesData) void
        -validateInventoryItems(array items) void
        -validateStockOptimizationData(array inventoryData) void
        -validateSupplierData(array supplierData) void
        -validateItemHistory(array itemHistory) void
        -getFallbackSalesTrends(array salesData, int periodDays) array
        -getFallbackTurnoverPrediction(array items) array
        -getFallbackStockOptimization(array inventoryData) array
        -getFallbackPurchaseRecommendations(array supplierData) array
        -getFallbackSafetyStock(array itemHistory) array
        -getFallbackInventoryAnalysis(array inventoryData, string analysisType) array
    }

    class OllamaStrategy {
        -httpClient HttpClient
        -logger Logger
        -baseUrl string
        -model string
        -defaultOptions array
        +__construct(HttpClient httpClient, Logger logger, string baseUrl, string model)
        +analyze(array data, string analysisType) array
        +generate(array data, string reportType) array
        +isAvailable() bool
        -callOllamaAPI(string prompt) array
        -buildAnalysisPrompt(array data, string analysisType) string
        -buildReportPrompt(array data, string reportType) string
        -parseAnalysisResponse(array response, string analysisType) array
        -parseReportResponse(array response, string reportType) array
        -validateData(array data) void
        -performBasicAnalysis(array data, string analysisType) array
        -analyzeSalesTrends(array data) array
        -analyzeInventoryTurnover(array data) array
        -optimizeStockLevels(array data) array
        -generatePurchaseRecommendations(array data) array
        -calculateSafetyStock(array data) array
        -buildSalesTrendsPrompt(array data) string
        -buildInventoryTurnoverPrompt(array data) string
        -buildStockOptimizationPrompt(array data) string
        -buildPurchaseRecommendationsPrompt(array data) string
        -buildSafetyStockPrompt(array data) string
        -parseSalesTrendsResponse(array response) array
        -parseInventoryTurnoverResponse(array response) array
        -parseStockOptimizationResponse(array response) array
        -parsePurchaseRecommendationsResponse(array response) array
        -parseSafetyStockResponse(array response) array
    }

    class AdvancedAnalysisStrategy {
        -logger Logger
        -mlModels array
        -mlEnabled bool
        +__construct(Logger logger, bool mlEnabled = true)
        +analyze(array data, string analysisType) array
        +generate(array data, string reportType) array
        +isAvailable() bool
        -analyzeSalesTrendsWithML(array data) array
        -predictTurnoverWithML(array data) array
        -optimizeStockWithML(array data) array
        -linearRegressionAnalysis(array salesData) array
        -randomForestTurnoverPrediction(array item) float
        -geneticAlgorithmOptimization(array item) array
        -initializeMLModels() array
        -validateData(array data) void
        -calculateConfidence(array data) float
        -basicSalesTrendAnalysis(array salesData, int periodDays) array
        -basicTurnoverPrediction(array items) array
        -basicStockOptimization(array inventoryData) array
        -fallbackAnalysis(array data, string analysisType) array
        -fallbackReport(array data, string reportType) array
    }

    class InventoryAnalysisService {
        -aiService AIService
        -inventoryService InventoryService
        -logger Logger
        -cache array
        -cacheTtl int
        +__construct(AIService aiService, InventoryService inventoryService, Logger logger, int cacheTtl = 300)
        +getComprehensiveAnalysis(array options) array
        +generateWeeklyReport() array
        +monitorCriticalItems() array
        +predictInventoryNeeds(int forecastDays) array
        +optimizeInventory() array
        -executeParallel(array operations) array
        -parallelExecution(array operations) array
        -optimizedAIAnalysis(array data) array
        -batchStockOptimization(array items) array
        -optimizedSalesTrendsAnalysis(array items) array
        -stratifiedSample(array items, int sampleSize) array
        -generateCacheKey(string type, array params) string
        -getFromCache(string key) array|null
        -setCache(string key, array data) void
        -calculatePerformanceMetrics(float startTime) array
        -calculateOverallRisk(array recommendations) string
        -processIncrementalWeeklyData() array
        -generateIncrementalInsights(DateTime startDate) array
        -calculateIncrementalMetrics(DateTime startDate) array
        -analyzeSalesTrends(array items) array
        -prepareOptimizationData(array items) array
        -estimateDailyUsage(array item) float
        -calculateUrgencyLevel(array item) string
        -calculateWeeklyMetrics(array stats, array recentItems) array
        -calculateTurnoverRatio(array stats) float
        -calculateOutOfStockPercentage(array stats) float
        -generatePurchaseRecommendations(array items) array
        -prepareSupplierData(array items) array
        -calculateSavingsPotential(array currentItems, array optimizationResult) array
        -generateImplementationPlan(array optimizationResult) array
        -getFallbackAnalysis() array
        -getFallbackWeeklyReport() array
        -getFallbackMonitoring() array
        -getFallbackPrediction(int days) array
        -getFallbackOptimization() array
    }

    class AnalysisResult {
        -analysisType string
        -findings array
        -recommendations array
        -confidenceScore float
        -supportingData array
        -isFallback bool
        -errorMessage string|null
        +__construct(string analysisType, array findings, array recommendations, float confidenceScore, array supportingData, bool isFallback, string errorMessage)
        +getAnalysisType() string
        +getFindings() array
        +getRecommendations() array
        +getConfidenceScore() float
        +getSupportingData() array
        +isFallback() bool
        +getErrorMessage() string|null
        +toArray() array
        +isValid() bool
        +getSummary() string
    }

    class AuthController {
        -authService AuthService
        -userService UserService
        +__construct(AuthService authService = null, UserService userService = null, Logger logger = null)
        +register() void
        +login() void
        +refreshToken() void
        +logout() void
        +profile() void
        +changePassword() void
    }

    class UserController {
        -userService UserService
        +__construct(UserService userService = null, Logger logger = null)
        +listUsers() void
        +getUser(string id) void
        +createUser() void
        +updateUser(string id) void
        +deleteUser(string id) void
    }

    class InventoryController {
        -inventoryService InventoryService
        +__construct(InventoryService inventoryService = null, Logger logger = null)
        +listItems() void
        +getItem(string id) void
        +createItem() void
        +updateItem(string id) void
        +deleteItem(string id) void
        +getLowStock() void
        +getOutOfStock() void
        +getStats() void
        +searchItems() void
        +updateQuantity(string id) void
    }

    class AIAnalysisController {
        -analysisService InventoryAnalysisService
        -aiService AIService
        +__construct(InventoryAnalysisService analysisService = null, AIService aiService = null, Logger logger = null)
        +getComprehensiveAnalysis() array|null
        +generateWeeklyReport() array|null
        +monitorCriticalItems() array|null
        +predictInventoryNeeds(int days = null) array|null
        +optimizeInventory() array|null
        +analyzeSalesTrends() array|null
        +getAIStatus() array|null
        -createMockAnalysisService() InventoryAnalysisService
    }

    class ErrorHandler {
        -logger LoggerInterface
        -displayErrors bool
        +__construct(LoggerInterface logger = null, bool displayErrors = false)
        +register() void
        +handleError(int errno, string errstr, string errfile, int errline) bool
        +handleException(Throwable exception) void
        +handleShutdown() void
        -sendErrorResponse(Throwable exception) void
        -getErrorType(int errno) string
        +setDisplayErrors(bool displayErrors) void
    }

    class HttpClient {
        -defaultOptions array
        +__construct(array defaultOptions = [])
        +get(string url, array options = []) array
        +post(string url, mixed data = null, array options = []) array
        +put(string url, mixed data = null, array options = []) array
        +delete(string url, array options = []) array
        +isUrlReachable(string url, float timeout = 5) bool
        +setDefaultOptions(array options) void
        +getDefaultOptions() array
        -request(string method, string url, mixed data, array options) array
        -createStreamContext(string method, mixed data, array options) resource
        -executeRequest(string url, resource context, array options) array
    }

    class PerformanceBenchmark {
        -static array benchmarks
        -static bool enabled
        +enable() void
        +disable() void
        +measure(callable fn, string operation, array context = []) mixed
        +measureBatch(array operations, bool parallel = false) array
        +getResults() array
        +getLatestResult() array|null
        +clear() void
        +generateReport() array
        +meetsThreshold(string operation, float maxDuration, int maxMemory) bool
        -recordBenchmark(string operation, float duration, int memoryUsed, array context = []) void
        -measureParallel(array operations) array
    }

    class User {
        -id string|null
        -username string
        -email string
        -passwordHash string
        -role string
        -createdAt DateTime
        -updatedAt DateTime
        +ROLE_ADMIN = 'admin'
        +ROLE_MANAGER = 'manager'
        +ROLE_STAFF = 'staff'
        +VALID_ROLES = [ROLE_ADMIN, ROLE_MANAGER, ROLE_STAFF]
        +__construct(string username, string email, string passwordHash, string role = 'staff', string id = null, DateTime createdAt = null, DateTime updatedAt = null)
        +getId() string|null
        +getUsername() string
        +getEmail() string
        +getPasswordHash() string
        +getRole() string
        +getCreatedAt() DateTime
        +getUpdatedAt() DateTime
        +setUsername(string username) void
        +setEmail(string email) void
        +setPasswordHash(string hash) void
        +setRole(string role) void
        +setUpdatedAt(DateTime updatedAt) void
        +toDocument() array
        +fromDocument(mixed document) User
        -parseDate(mixed dateValue) DateTime
        +validate() void
        +isAdmin() bool
        +isManager() bool
        +isStaff() bool
        +toArray() array
        +__toString() string
    }

    class Inventory {
        -id string|null
        -name string
        -description string
        -quantity int
        -price float
        -categoryId string|null
        -supplierId string|null
        -minStockLevel int
        -createdAt DateTime
        -updatedAt DateTime
        +__construct(string name, string description, int quantity, float price, string categoryId = null, string supplierId = null, int minStockLevel = 0, string id = null, DateTime createdAt = null, DateTime updatedAt = null)
        +getId() string|null
        +getName() string
        +getDescription() string
        +getQuantity() int
        +getPrice() float
        +getCategoryId() string|null
        +getSupplierId() string|null
        +getMinStockLevel() int
        +getCreatedAt() DateTime
        +getUpdatedAt() DateTime
        +setName(string name) void
        +setDescription(string description) void
        +setQuantity(int quantity) void
        +setPrice(float price) void
        +setCategoryId(string categoryId) void
        +setSupplierId(string supplierId) void
        +setMinStockLevel(int minStockLevel) void
        +setUpdatedAt(DateTime updatedAt) void
        +isLowStock() bool
        +isOutOfStock() bool
        +toDocument() array
        +fromDocument(mixed document) Inventory
        -parseDate(mixed dateValue) DateTime
        +validate() void
        +getTotalValue() float
        +__toString() string
        +toArray() array
    }

    %% ========== Planned Components (Belum Diimplementasi) ==========
    class ISupplierRepository {
        <<interface>>
        +findByStatus(string status) array
        +getSupplierStats() array
    }

    class IHashService {
        <<interface>>
        +hash(string password) string
        +verify(string password, string hash) bool
    }

    class SupplierService {
        +__construct(ISupplierRepository supplierRepo, Logger logger)
        +createSupplier(array data) array
        +updateSupplier(string id, array data) array
        +getSupplierPerformance(string supplierId) array
    }

    class AuditLogService {
        +__construct(AuditLogRepository auditRepo, Logger logger)
        +logAction(string userId, string action, string resource, string resourceId, array oldData, array newData) bool
        +getAuditTrail(string resourceType, string resourceId, DateTime from, DateTime to) array
    }

    class SupplierController {
        -supplierService SupplierService
        +__construct(SupplierService supplierService, Logger logger)
        +listSuppliers() void
        +getSupplier(string id) void
        +createSupplier() void
        +updateSupplier(string id) void
        +deleteSupplier(string id) void
    }

    class ReportController {
        -aiService AIService
        -inventoryService InventoryService
        +__construct(AIService aiService, InventoryService inventoryService, Logger logger)
        +generateInventoryReport() void
        +generateSalesReport() void
        +generateStockPrediction() void
    }

    class AuthMiddleware {
        -tokenService ITokenService
        +__construct(ITokenService tokenService)
        +verifyAccessToken(Request request, Response response, callable next) mixed
        +requireAuthentication() mixed
    }
    
    class RoleMiddleware {
        +__construct(array allowedRoles)
        +requireRole(string role) mixed
        +requireAnyRole(array roles) mixed
    }
    
    class Validator {
        +validate(array schema, array data) array
        +sanitize(array data) array
        +validateEmail(string email) bool
        +validatePassword(string password) array
    }

    class Supplier {
        +string id
        +string name
        +string contactEmail
        +string phone
        +string address
        +string status
        +DateTime createdAt
        +DateTime updatedAt
    }

    class AuditLog {
        +string id
        +string userId
        +string action
        +string resource
        +string resourceId
        +array oldData
        +array newData
        +DateTime timestamp
        +string ipAddress
    }

    %% ========== Future Relationships ==========
    IRepository <|.. UserRepository
    IRepository <|.. InventoryRepository
    IInventoryRepository <|.. InventoryRepository
    IAuthService <|.. AuthService
    IInventoryService <|.. InventoryService
    ITokenService <|.. JwtTokenService
    ITokenRepository <|.. MongoTokenRepository
    IAIService <|.. AIService
    IAdvancedAIService <|.. AIService
    IInventoryAnalysisService <|.. InventoryAnalysisService
    AIStrategy <|.. OllamaStrategy
    AIStrategy <|.. AdvancedAnalysisStrategy
    
    UserRepository --> MongoDBManager : uses
    InventoryRepository --> MongoDBManager : uses
    MongoTokenRepository --> MongoDBManager : uses
    
    UserService --> UserRepository : depends on
    UserService --> Logger : depends on
    
    InventoryService --> IInventoryRepository : depends on
    InventoryService --> Logger : depends on
    
    AuthService --> UserService : depends on
    AuthService --> ITokenService : depends on
    AuthService --> Logger : depends on
    
    JwtTokenService --> ITokenRepository : depends on
    JwtTokenService --> Logger : depends on
    
    AIService --> AIStrategy : depends on
    AIService --> Logger : depends on
    
    OllamaStrategy --> HttpClient : depends on
    OllamaStrategy --> Logger : depends on
    
    AdvancedAnalysisStrategy --> Logger : depends on
    
    InventoryAnalysisService --> AIService : depends on
    InventoryAnalysisService --> InventoryService : depends on
    InventoryAnalysisService --> Logger : depends on
    
    AuthController --> AuthService : depends on
    AuthController --> UserService : depends on
    AuthController --> BaseController : extends
    
    UserController --> UserService : depends on
    UserController --> BaseController : extends
    
    InventoryController --> InventoryService : depends on
    InventoryController --> BaseController : extends
    
    AIAnalysisController --> InventoryAnalysisService : depends on
    AIAnalysisController --> AIService : depends on
    AIAnalysisController --> BaseController : extends
    
    ErrorHandler --> LoggerInterface : depends on

    ISupplierRepository <|.. MongoSupplierRepository
    IHashService <|.. BcryptHashService
    AIStrategy <|.. Phi3Strategy
    AIStrategy <|.. DeepSeekStrategy

    SupplierController --> SupplierService
    ReportController --> AIService
    ReportController --> InventoryService

    SupplierService --> ISupplierRepository
    AuditLogService --> AuditLogRepository

    Inventory --> Category
    Inventory --> Supplier
    AuditLog --> User

    MongoSupplierRepository --> MongoDBManager
    AuditLogRepository --> MongoDBManager

    SupplierService --> Logger
    AuditLogService --> Logger

    AuthMiddleware --> ITokenService
    RoleMiddleware --> AuthMiddleware
```

---
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
### 4\. **Environment Configuration (.env)**
```
ini
APP_ENV=development
MONGODB_URI=mongodb://localhost:27017
MONGODB_DB=inventory_ai
JWT_SECRET=your-super-secret-jwt-key-change-this-in-production
JWT_ALGORITHM=HS256
JWT_ACCESS_EXPIRY=3600
JWT_REFRESH_EXPIRY=2592000
```

### 5\. **Start Services**
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

### 6\. **Test API**
```
bash
# Health check
curl http://localhost/inventory-ai/health

# API status
curl http://localhost/inventory-ai/
```

## 📋 API Endpoints

### Authentication ✅

- `POST /auth/register` - User registration
    
- `POST /auth/login` - User login
    
- `POST /auth/refresh` - Refresh access token
    
- `POST /auth/logout` - User logout
    
- `GET /auth/profile` - Get user profile
    
- `POST /auth/change-password` - Change password
    

### User Management ✅

- `GET /users` - Get users list (with pagination)
    
- `GET /users/{id}` - Get user by ID
    
- `POST /users` - Create new user (admin only)
    
- `PUT /users/{id}` - Update user
    
- `DELETE /users/{id}` - Delete user (admin only)
    

### System ✅

- `GET /` - API status dan information
    
- `GET /health` - Service health monitoring
    

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
### Test Results ✅

- **Total Tests**: 23 tests, 69 assertions
    
- **Unit Tests**: 19 tests, 56 assertions ✅
    
- **Integration Tests**: 4 tests, 13 assertions ✅
    
- **Functional Tests**: 2 tests ✅
    
- **Success Rate**: 100% ✅
    

## 📊 Database Schema

### Users Collection ✅
```
javascript
{
  _id: ObjectId,
  username: string (unique, min: 3 chars),
  email: string (unique, valid format),
  passwordHash: string (bcrypt),
  role: string['admin', 'manager', 'staff'],
  createdAt: DateTime,
  updatedAt: DateTime,
  indexes: [
    { username: 1, unique: true },
    { email: 1, unique: true },
    { role: 1 },
    { createdAt: 1 }
  ]
}
``` 
### Refresh Tokens Collection ✅
```
javascript
{
  _id: ObjectId,
  tokenHash: string (sha256),
  userId: ObjectId,
  expiresAt: DateTime,
  revoked: boolean,
  createdAt: DateTime,
  revokedAt: DateTime
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
## 🎯 Key Features Implemented

### ✅ Authentication System

- JWT token-based authentication
    
- Access & refresh tokens
    
- Password hashing dengan bcrypt
    
- Token revocation system
    
- Role-based authorization
    

### ✅ User Management

- User CRUD operations
    
- Password validation & strength checking
    
- Email and username uniqueness validation
    
- Pagination and filtering
    

### ✅ Error Handling

- Global error handling middleware
    
- Structured error responses
    
- Comprehensive logging
    
- Validation error handling
    

### ✅ Testing

- Unit tests untuk services
    
- Integration tests untuk database
    
- Functional tests untuk API
    
- 100% test coverage untuk core functionality

## 🤝 Contributing

1. Follow PSR-12 coding standards ✅
    
2. Write tests for new features ✅
    
3. Update documentation ✅
    
4. Use conventional commit messages ✅
    
5. Ensure all tests pass before submitting ✅
    

## 📝 License

MIT License - lihat file [LICENSE](https://license/) untuk detail lengkap.

---

**Status**: Phase 1 Completed ✅, Phase 2 Completed ✅, Phase 3 In Progress ⏳  
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

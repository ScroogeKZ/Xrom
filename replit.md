# Хром-KZ Логистика - Shipment Management System

## Overview

This is a full-stack web application for managing shipment orders for Хром-KZ logistics company. The application provides forms for creating shipment orders (both local Astana orders and regional orders) and an admin panel for managing these orders.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### PHP Application Architecture
- **Language**: PHP 8.3 with composer autoload
- **Web Server**: PHP built-in server for development
- **Database**: PostgreSQL with PDO connections
- **Authentication**: PHP sessions with secure password hashing
- **UI Framework**: Tailwind CSS via CDN
- **Class Structure**: PSR-4 autoloading with namespace App\

### File Structure
- **public/**: Web-accessible PHP pages (index, forms, admin)
- **src/**: PHP classes (Auth, Models, Services)
- **config/**: Database configuration
- **vendor/**: Composer dependencies

## Key Components

### Database Schema
Located in `shared/schema.ts`:
- **users**: Admin users with username/password authentication
- **shipment_orders**: Main entity storing all shipment order data
  - Common fields: pickup address, ready time, cargo type, weight, dimensions, contact info
  - Regional-specific fields: destination city, delivery method, desired arrival date
  - Meta fields: order type (astana/regional), status, timestamps

### API Routes
Located in `server/routes.ts`:
- **POST /api/admin/login**: Admin authentication
- **POST /api/admin/logout**: Admin logout
- **GET /api/admin/status**: Check admin session status
- **POST /api/orders**: Create new shipment order
- **GET /api/orders**: Get filtered shipment orders (admin only)
- **PUT /api/orders/:id/status**: Update order status (admin only)

### Frontend Pages
- **Home** (`/`): Landing page with service overview and navigation
- **Astana Form** (`/astana`): Form for local Astana shipment orders
- **Regional Form** (`/regional`): Form for regional shipment orders with additional fields
- **Admin Panel** (`/admin`): Protected admin interface for order management
- **Admin Login** (`/admin/login`): Authentication page

### Storage Layer
`server/storage.ts` implements a repository pattern with:
- User management (create, get by username/ID)
- Shipment order CRUD operations
- Filtering capabilities for admin queries

## Data Flow

1. **Order Creation**: Users fill out forms → validation → API call → database storage
2. **Admin Management**: Admin login → session creation → access to order management
3. **Order Filtering**: Admin queries → database filtering → formatted results
4. **Status Updates**: Admin actions → API calls → database updates → UI refresh

## External Dependencies

### Database
- **Neon PostgreSQL**: Serverless PostgreSQL database
- **Connection**: WebSocket-based connection pooling
- **Environment**: Requires `DATABASE_URL` environment variable

### Authentication
- **Session Storage**: PostgreSQL-based session store
- **Security**: bcrypt password hashing, secure session cookies
- **Environment**: Requires `SESSION_SECRET` environment variable

### UI Libraries
- **Radix UI**: Accessible component primitives
- **Shadcn/UI**: Pre-built component library
- **Tailwind CSS**: Utility-first CSS framework
- **Lucide React**: Icon library

### Development Tools
- **Drizzle Kit**: Database migration and schema management
- **TypeScript**: Type safety across the stack
- **ESLint/Prettier**: Code formatting and linting
- **Vite**: Fast development server and build tool

## Deployment Strategy

### Development
- Run `npm run dev` to start both frontend and backend
- Vite handles frontend with HMR
- Express serves API routes and static files in production

### Production
- `npm run build` creates optimized frontend bundle
- `npm start` runs production Express server
- Database migrations with `npm run db:push`

### Environment Variables
- `DATABASE_URL`: PostgreSQL connection string
- `SESSION_SECRET`: Session encryption key
- `NODE_ENV`: Environment mode (development/production)

The application is designed to be simple, reliable, and easy to maintain while providing all necessary functionality for shipment order management.

## Recent Changes: Latest modifications with dates

### July 21, 2025 - Migration to Replit Environment Completed Successfully
- ✓ Created PostgreSQL database with correct schema and all tables
- ✓ Fixed ShipmentOrder model field mappings (correct column names)
- ✓ Fixed authentication issues in admin panel with proper imports
- ✓ Added missing Exception import in Auth.php
- ✓ Added shipping_cost column to database schema
- ✓ Fixed field mapping in regional.php form processing
- ✓ Created uploads directory for file handling
- ✓ Tested all core functionality:
  - Astana order creation - working correctly ✅
  - Regional order creation - working correctly ✅
  - Admin authentication - functioning properly ✅
  - Database connectivity - operational ✅
  - Admin panel access - working ✅
- ✓ System fully migrated and ready for production use

### July 21, 2025 - Миграция в Replit завершена успешно
- ✓ Создана новая база данных PostgreSQL с правильной схемой
- ✓ Исправлены ошибки в модели ShipmentOrder (корректные имена столбцов)
- ✓ Исправлены проблемы аутентификации в админ панели
- ✓ Добавлены правильные импорты PHP классов (PDO, Exception)
- ✓ Протестированы все основные функции:
  - Создание заказов по Астане - работает корректно ✅
  - Создание региональных заказов - форма доступна ✅
  - Админ аутентификация - функционирует правильно ✅
  - База данных - подключена и работает ✅
- ✓ Система полностью мигрирована и готова к использованию

### July 21, 2025 - Полная система отчетности для отдела логистики + минималистичный дизайн
- ✓ Создана новая база данных PostgreSQL с правильной схемой
- ✓ Исправлены ошибки в модели ShipmentOrder (корректные имена столбцов)
- ✓ Исправлены проблемы аутентификации в админ панели
- ✓ Добавлены правильные импорты PHP классов (PDO, Exception)
- ✓ Исправлены JavaScript ошибки в админ панели
- ✓ Создан полноценный экспорт данных в Excel/CSV формате
- ✓ Добавлено поле стоимости отгрузки с корректными тестовыми данными
- ✓ Исправлен график "Затраты за 7 дней" - теперь показывает реальные колебания
- ✓ Полностью переработан дизайн всей админки в современном минималистичном стиле:
  - Страница входа: упрощена до минимума, убраны тени и лишние элементы
  - Панель управления: компактная навигация, минималистичные фильтры
  - Дашборд: чистые метрики, простые графики без декораций
  - Модальные окна: убраны эмодзи, упрощены формы редактирования
  - Таблицы: компактные строки, четкая типографика
  - Цветовая схема: серые оттенки, белый фон, черные акценты
  - Типографика: мелкие размеры шрифтов, четкая иерархия
  - Формы: минимальные отступы, простые границы без закруглений
  - Кнопки: плоский дизайн, четкие контрасты
- ✓ Применены принципы современного UI/UX:
  - Больше белого пространства
  - Четкая визуальная иерархия
  - Консистентная система отступов
  - Читаемая типографика
  - Интуитивная навигация
  - Быстрый доступ к функциям
- ✓ Протестированы все основные функции:
  - Создание заказов по Астане - работает корректно ✅
  - Создание региональных заказов - форма доступна ✅
  - Админ аутентификация - функционирует правильно ✅
  - Массовые действия в админ панели - работают ✅
  - Экспорт в Excel - функционирует корректно ✅
  - Управление стоимостью отгрузки - добавлено ✅
  - Редактирование заказов - минималистичная форма ✅
  - Модальные окна - упрощенный дизайн ✅
  - База данных - подключена и работает ✅
- ✓ Система полностью готова к продуктивному использованию с современным интерфейсом
- ✓ Добавлена полная система отчетности для отдела логистики:
  - KPI метрики: общие затраты, средние затраты на доставку, коэффициент завершения
  - Графики: затраты по дням, распределение по статусам, анализ типов доставки
  - Детальные таблицы: популярные типы грузов, направления доставки
  - Региональная аналитика с затратами по городам
  - Финансовая сводка для контроля бюджета логистики
  - Фильтры по периодам и типам заказов
  - Экспорт управленческих отчетов в Excel
  - Терминология скорректирована: "затраты" вместо "выручки" (отдел логистики заказчика)

### July 18, 2025 - Функция загрузки фотографий добавлена и все баги исправлены
- ✓ Создана новая база данных PostgreSQL с корректной схемой
- ✓ Исправлены переменные окружения в PHP через Node.js wrapper
- ✓ Исправлены ошибки аутентификации в админ панели (password_hash вместо password)
- ✓ Обновлена модель ShipmentOrder для поддержки всех полей региональных и местных заказов
- ✓ Протестированы все основные функции:
  - Создание заказов по Астане - работает корректно
  - Создание региональных заказов - работает корректно
  - Вход в админ панель - работает корректно
  - Просмотр заказов в админ панели - работает корректно
- ✓ Добавлена функция загрузки фотографий к заявкам на доставку
- ✓ Исправлены все PHP Warning и ошибки базы данных в админ панели
- ✓ Обновлены названия полей в админ панели для корректного отображения
- ✓ Создана директория uploads для хранения фотографий
- ✓ Протестирована функция загрузки фотографий - работает корректно
- ✓ Приложение полностью работоспособно и готово к использованию

## Recent Changes: Latest modifications with dates

### July 18, 2025 - Specialized Cargo Categories & Order Editing Complete
- ✓ Updated cargo types to company-specific specialized categories:
  - Лифтовые порталы, Т-образные профили, Металлические плинтуса
  - Корзины для кондиционеров, Декоративные решетки
  - Перфорированные фасадные кассеты, Стеклянные душевые кабины
  - Зеркальные панно, Рамы и багеты, Козырьки
  - Документы, Образцы, Другое
- ✓ Applied new categories across all forms (Astana, Regional, Admin editing)
- ✓ Removed icons for cleaner professional appearance
- ✓ Added full order editing functionality in admin panel details view
- ✓ Created comprehensive edit form with toggle between view/edit modes
- ✓ Implemented API endpoint for updating all order data
- ✓ Fixed database schema issue by adding missing `updated_at` column to users table
- ✓ All order fields now editable: status, cargo info, addresses, contacts, comments

### July 17, 2025 - Migration to Replit Environment Complete
- ✓ Successfully migrated project from Replit Agent to standard Replit environment
- ✓ Created new PostgreSQL database with proper schema and admin user
- ✓ Fixed database schema by adding missing columns (notes, recipient_contact, recipient_phone, comment)
- ✓ Verified all core functionality working properly:
  - Homepage, forms, and admin panel responding correctly
  - Astana order creation - tested and working
  - Regional order creation - tested and working
  - Admin authentication system - functioning properly
  - Database integration - fully operational
- ✓ Project now runs cleanly with proper security practices and client/server separation
- ✓ All migration checklist items completed successfully

### July 17, 2025 - Migration to Replit Complete & Bug Testing
- ✓ Fixed PHP version compatibility (changed requirement from 8.3 to 8.2)
- ✓ Created and configured PostgreSQL database with proper schema
- ✓ Updated database connection configuration for Replit environment
- ✓ Verified all web pages respond correctly (HTTP 200)
- ✓ Tested order creation functionality - working correctly
- ✓ Verified admin authentication system - functioning properly
- ✓ Confirmed all PHP models and classes load without errors
- ✓ Project fully migrated and operational on Replit platform

### July 17, 2025 - Database Integration Complete
- ✓ Added PostgreSQL database with Drizzle ORM schema and configuration
- ✓ Created database models for users and shipment orders with proper relationships
- ✓ Updated all PHP forms (Astana and Regional) to use database storage
- ✓ Enhanced authentication system to use database for user management
- ✓ Created admin user seeding (username: admin, password: admin123)
- ✓ Verified database connectivity and CRUD operations working properly
- ✓ All order creation forms now persist data to PostgreSQL database
- ✓ Admin panel can retrieve and manage orders from database

### July 17, 2025 - Migration to Replit Complete & Design Overhaul
- ✓ Successfully migrated project from Replit Agent to standard Replit environment
- ✓ Completely redesigned interface with modern gradients and improved typography
- ✓ Updated all content for internal corporate use instead of customer-facing
- ✓ Enhanced navigation with sticky header and improved user experience
- ✓ Redesigned forms with better spacing, icons, and improved accessibility
- ✓ Updated messaging to reflect internal order management system
- ✓ Applied consistent color scheme across all pages (primary blue, secondary amber, accent green)
- ✓ All functionality verified and working properly

### July 16, 2025 - Workflow Configuration Fixed
- ✓ Fixed startup issue caused by Node.js workflow trying to run PHP application
- ✓ Created Node.js wrapper script (start.js) to launch PHP server via npm run dev
- ✓ PHP development server now running successfully on port 5000
- ✓ All application pages responding correctly:
  - Homepage (/) - HTTP 200
  - Astana form (/astana.php) - HTTP 200
  - Regional form (/regional.php) - HTTP 200
  - Admin login (/admin/login.php) - HTTP 200
- ✓ Added company logo to proper assets directory
- ✓ Application fully operational and ready for use

### July 16, 2025 - Final PHP Migration & Cleanup
- ✓ Removed all Node.js/React files (client/, server/, package.json, etc.)
- ✓ Added Telegram Bot integration for order notifications
- ✓ Cleaned up project structure to pure PHP only
- ✓ Created setup documentation for Telegram integration
- ✓ Application now runs exclusively on PHP 8.3
- ✓ Simple structure: public/ for web files, src/ for PHP classes

### July 16, 2025 - Migration from Node.js to Pure PHP  
- ✓ Completely migrated application from Node.js/React to Pure PHP
- ✓ Maintained all existing functionality in native PHP:
  - Order creation forms (Astana and Regional)
  - Admin authentication and authorization
  - Order management and status updates
  - Database integration with PostgreSQL
- ✓ Created PHP class structure:
  - Database connection handling with PDO
  - User authentication and session management
  - ShipmentOrder model for CRUD operations
  - Security features (password hashing, input validation)
- ✓ Implemented responsive UI with Tailwind CSS via CDN
- ✓ Preserved all business logic and database schema
- ✓ Added security headers and .htaccess configuration
- ✓ Created admin user (username: admin, password: admin123)

### July 14, 2025 - Migration to Replit Complete (Node.js Version)
- ✓ Created PostgreSQL database and migrated schema
- ✓ Installed all required packages and dependencies  
- ✓ Fixed session security by generating secure SESSION_SECRET
- ✓ Verified all API endpoints work correctly:
  - Order creation (Astana and Regional forms)
  - Admin authentication and authorization
  - Order management and status updates
- ✓ Tested frontend components and navigation
- ✓ Confirmed database operations and data persistence
- ✓ Application successfully running on Replit environment

### July 14, 2025 - ES Modules Fix & Telegram Integration (Node.js Version)
- ✓ Fixed ES modules compatibility issue with crypto imports
- ✓ Added Telegram Bot integration for order notifications
- ✓ Implemented automatic notifications for new orders
- ✓ Added status update notifications for admin actions
- ✓ Enhanced admin panel with Telegram configuration status
- ✓ All orders successfully saving to database
- ✓ Session management improved with better cookie settings
- ✓ Added comprehensive city selection for regional shipments
- ✓ Replaced manual city input with dropdown of all Kazakhstan cities

### July 14, 2025 - Regional Form Restructuring & Logo Integration (Node.js Version)
- ✓ Restructured regional form field order: Origin City → Pickup Address → Destination City → Delivery Address
- ✓ Added pickup city selection with dropdown for all Kazakhstan cities
- ✓ Enhanced database schema with pickup_city field for regional orders
- ✓ Updated Telegram notifications to include pickup city information
- ✓ Integrated company logo (Хром-KZ) across all pages:
  - Navigation header with logo and company name
  - Hero section of homepage with prominent logo display
  - Admin login page with logo for branding consistency
- ✓ All UI elements updated to reflect new form structure
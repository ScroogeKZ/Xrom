# Информация о базе данных Хром-KZ

## Статус базы данных
- ✅ **PostgreSQL**: Работает на Neon.tech
- ✅ **Подключение**: Доступно через DATABASE_URL
- ✅ **Таблицы**: 3 таблицы созданы и работают

## Структура таблиц

### 1. users - Пользователи системы
```sql
- id (SERIAL PRIMARY KEY)
- username (VARCHAR UNIQUE) 
- password (VARCHAR - хешированный bcrypt)
```
**Данные**: 1 админ пользователь (admin/admin123)

### 2. shipment_orders - Заказы на перевозку
```sql
- id (SERIAL PRIMARY KEY)
- pickup_address, delivery_address (TEXT)
- ready_time (VARCHAR)
- cargo_type, weight, dimensions (VARCHAR)
- contact_person, phone (VARCHAR)
- recipient_contact, recipient_phone (VARCHAR)
- comment (TEXT)
- pickup_city, destination_city (VARCHAR) - для региональных
- delivery_method (VARCHAR) - для региональных  
- desired_arrival_date (DATE) - для региональных
- order_type (astana/regional)
- status (processing/completed)
- created_at, updated_at (TIMESTAMP)
```
**Данные**: 2 тестовых заказа (1 Астана, 1 региональный)

### 3. session - PHP сессии
```sql
- sid (VARCHAR PRIMARY KEY)
- sess (JSON)
- expire (TIMESTAMP)
```

## Переменные окружения
- `DATABASE_URL`: ✅ Настроен для Neon PostgreSQL
- `PGHOST`, `PGUSER`, `PGPASSWORD`, `PGPORT`, `PGDATABASE`: ✅ Доступны

## Бэкапы
- `database_backup.sql`: Полный SQL дамп с данными
- Создан: 16 июля 2025 г.

## Восстановление
Для восстановления из бэкапа:
```bash
psql $DATABASE_URL < database_backup.sql
```

## Подключение в PHP
```php
$pdo = new PDO($_ENV['DATABASE_URL']);
```

База данных полностью настроена и готова к работе!
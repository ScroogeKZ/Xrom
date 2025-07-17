-- Хром-KZ Логистика - Бэкап базы данных
-- Создано: 16 июля 2025 г.
-- Описание: Полный бэкап PostgreSQL базы данных с данными

-- Создание таблицы пользователей
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Создание таблицы заказов
CREATE TABLE IF NOT EXISTS shipment_orders (
    id SERIAL PRIMARY KEY,
    pickup_address TEXT NOT NULL,
    delivery_address TEXT NOT NULL,
    ready_time VARCHAR(10) NOT NULL,
    cargo_type VARCHAR(255) NOT NULL,
    weight VARCHAR(50) NOT NULL,
    dimensions VARCHAR(100) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    recipient_contact VARCHAR(255) NOT NULL,
    recipient_phone VARCHAR(20) NOT NULL,
    comment TEXT,
    pickup_city VARCHAR(255),
    destination_city VARCHAR(255),
    delivery_method VARCHAR(100),
    desired_arrival_date DATE,
    order_type VARCHAR(20) NOT NULL CHECK (order_type IN ('astana', 'regional')),
    status VARCHAR(20) DEFAULT 'processing' CHECK (status IN ('processing', 'completed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Создание таблицы сессий
CREATE TABLE IF NOT EXISTS session (
    sid VARCHAR NOT NULL PRIMARY KEY,
    sess JSON NOT NULL,
    expire TIMESTAMP(6) NOT NULL
);

-- Вставка данных пользователей
INSERT INTO users (id, username, password) VALUES 
(1, 'admin', '$2b$10$FG6WspJ8/xALF9CSXTS2g.SN9.QNUtmZ3TgHFfB0y1EgIxAIOwA6a');

-- Вставка тестовых заказов
INSERT INTO shipment_orders (
    id, pickup_address, delivery_address, ready_time, cargo_type, weight, 
    dimensions, contact_person, phone, recipient_contact, recipient_phone, 
    comment, pickup_city, destination_city, delivery_method, desired_arrival_date, 
    order_type, status, created_at, updated_at
) VALUES 
(2, 'ул. Кенесары 42', 'пр. Назарбаева 123', '14:00', 'документы', '2', 
 '20x15x5', 'Тест Тестович', '+77001234567', 'Получатель Тестов', '+77007654321', 
 'Тестовый заказ', NULL, NULL, NULL, NULL, 'astana', 'processing', 
 '2025-07-16 05:49:18.53106', '2025-07-16 05:49:18.53106'),

(3, 'ул. Сарыарка 15', 'пр. Абая 78', '10:00', 'посылка', '5', 
 '30x20x15', 'Региональный Тест', '+77002345678', 'Алматинский Получатель', '+77008765432', 
 'Региональный тестовый заказ', 'Астана', 'Алматы', 'до_двери', '2025-07-19', 
 'regional', 'processing', '2025-07-16 05:49:18.986668', '2025-07-16 05:49:18.986668');

-- Обновление последовательностей
SELECT setval('users_id_seq', 1, true);
SELECT setval('shipment_orders_id_seq', 3, true);
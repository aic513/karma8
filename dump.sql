-- Таблица пользователей
CREATE TABLE users
(
    username  VARCHAR(255),
    email     VARCHAR(255),
    validts   INT,
    confirmed TINYINT,
    checked   TINYINT,
    valid     TINYINT,
    PRIMARY KEY (email)
);

-- Таблица очереди задач
CREATE TABLE task_queue
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255),
    username   VARCHAR(255),
    validts    INT,
    status     ENUM ('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Таблица для отслеживания отправленных уведомлений
CREATE TABLE notifications
(
    email             VARCHAR(255),
    notification_date DATE,
    PRIMARY KEY (email, notification_date)
);

-- Индекс для ускорения выборки пользователей
CREATE INDEX idx_validts_confirmed_valid ON users (validts, confirmed, checked, valid);


CREATE DATABASE IF NOT EXISTS service_desk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE service_desk;

CREATE TABLE IF NOT EXISTS users (
    id          INT             AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(255)    NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS interventions (
    id          INT             AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255)    NOT NULL,
    description TEXT,
    status      ENUM(
                    'open',
                    'in_progress',
                    'closed'
                )               NOT NULL DEFAULT 'open',
    user_id     INT             NOT NULL,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_interventions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_interventions_status
    ON interventions(status);

CREATE INDEX IF NOT EXISTS idx_interventions_user_id
    ON interventions(user_id);

CREATE INDEX IF NOT EXISTS idx_interventions_created_at
    ON interventions(created_at);

SELECT 'Database setuppato corettamente.' AS message;
SHOW TABLES;
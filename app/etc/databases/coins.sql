/* -------------------------
        COINS CONTEXT
---------------------------- */

-- Generic tables

CREATE TABLE mutations
(
    id                 BIGINT AUTO_INCREMENT PRIMARY KEY,
    table_name         VARCHAR(255) NOT NULL,
    operation          ENUM ('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_value          JSON NULL,
    new_value          JSON NULL,
    mutation_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Aggregates tables

CREATE TABLE coins
(
    id       CHAR(36)         NOT NULL,
    value    DOUBLE PRECISION NOT NULL,
    quantity INT              NOT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

CREATE TRIGGER after_coins_insert
    AFTER INSERT
    ON coins
    FOR EACH ROW
BEGIN
    INSERT INTO mutations (table_name, operation, new_value, mutation_timestamp)
    VALUES ('coins', 'INSERT', JSON_OBJECT('id', new.id, 'value', new.value, 'quantity', new.quantity), NOW());
END;

CREATE TRIGGER after_coins_update
    AFTER UPDATE
    ON coins
    FOR EACH ROW
BEGIN
    INSERT INTO mutations (table_name, operation, old_value, new_value, mutation_timestamp)
    VALUES ('coins',
            'UPDATE',
            JSON_OBJECT('id', old.id, 'value', old.value, 'quantity', old.quantity),
            JSON_OBJECT('id', new.id, 'value', new.value, 'quantity', new.quantity),
            NOW());
END;

CREATE TRIGGER after_coins_delete
    AFTER DELETE
    ON coins
    FOR EACH ROW
BEGIN
    INSERT INTO mutations (table_name, operation, old_value, mutation_timestamp)
    VALUES ('coins', 'DELETE', JSON_OBJECT('id', old.id, 'value', old.value, 'quantity', old.quantity), NOW());
END;

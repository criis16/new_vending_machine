/* -------------------------
        ITEMS CONTEXT
---------------------------- */

CREATE TABLE IF NOT EXISTS items
(
    id       CHAR(36)         NOT NULL,
    name     VARCHAR(255)     NOT NULL,
    quantity INT              NOT NULL,
    price    DOUBLE PRECISION NOT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

DELIMITER $$

CREATE TRIGGER after_items_insert
    AFTER INSERT
    ON items
    FOR EACH ROW
BEGIN
    INSERT INTO mutations (table_name, operation, new_value, mutation_timestamp)
    VALUES ('items', 'INSERT',
            JSON_OBJECT('id', new.id, 'name', new.name, 'quantity', new.quantity, 'price', new.price), NOW());
END$$

CREATE TRIGGER after_items_update
    AFTER UPDATE
    ON items
    FOR EACH ROW
BEGIN
    INSERT INTO mutations (table_name, operation, old_value, new_value, mutation_timestamp)
    VALUES ('items',
            'UPDATE',
            JSON_OBJECT('id', old.id, 'name', old.name, 'quantity', old.quantity, 'price', old.price),
            JSON_OBJECT('id', new.id, 'name', new.name, 'quantity', new.quantity, 'price', new.price),
            NOW());
END$$

CREATE TRIGGER after_items_delete
    AFTER DELETE
    ON items
    FOR EACH ROW
BEGIN
    INSERT INTO mutations (table_name, operation, old_value, mutation_timestamp)
    VALUES ('items', 'DELETE',
            JSON_OBJECT('id', old.id, 'name', old.name, 'quantity', old.quantity, 'price', old.price), NOW());
END$$

DELIMITER ;

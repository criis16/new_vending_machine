/* -------------------------
        MACHINE STATUS CONTEXT
---------------------------- */

CREATE TABLE IF NOT EXISTS machine_status
(
    id      CHAR(36)         NOT NULL,
    balance DOUBLE PRECISION NOT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

DELIMITER $$

CREATE TRIGGER after_machine_status_insert
    AFTER INSERT
    ON machine_status
    FOR EACH ROW
BEGIN
    INSERT INTO mutations (table_name, operation, new_value, mutation_timestamp)
    VALUES ('machine_status', 'INSERT',
            JSON_OBJECT('id', new.id, 'balance', new.balance), NOW());
END$$

CREATE TRIGGER after_machine_status_update
    AFTER UPDATE
    ON machine_status
    FOR EACH ROW
BEGIN
    INSERT INTO mutations (table_name, operation, old_value, new_value, mutation_timestamp)
    VALUES ('machine_status',
            'UPDATE',
            JSON_OBJECT('id', old.id, 'balance', old.balance),
            JSON_OBJECT('id', new.id, 'balance', new.balance),
            NOW());
END$$

CREATE TRIGGER after_machine_status_delete
    AFTER DELETE
    ON machine_status
    FOR EACH ROW
BEGIN
    INSERT INTO mutations (table_name, operation, old_value, mutation_timestamp)
    VALUES ('machine_status', 'DELETE',
            JSON_OBJECT('id', old.id, 'balance', old.balance), NOW());
END$$

DELIMITER ;

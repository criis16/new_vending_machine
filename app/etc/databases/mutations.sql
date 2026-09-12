/* -------------------------
        MUTATIONS CONTEXT
---------------------------- */

CREATE TABLE IF NOT EXISTS mutations
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

--
--  Database access
--  ---------------
CREATE TABLE access (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_id INT UNSIGNED NOT NULL,
    source_id INT UNSIGNED NOT NULL,
    effect ENUM('allow', 'deny') NOT NULL DEFAULT 'allow',

    PRIMARY KEY (id),
    UNIQUE KEY uq_access_role_source (role_id, source_id),

    CONSTRAINT fk_access_role
        FOREIGN KEY (role_id)
            REFERENCES roles(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE,

    CONSTRAINT fk_access_source
        FOREIGN KEY (source_id)
            REFERENCES source(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

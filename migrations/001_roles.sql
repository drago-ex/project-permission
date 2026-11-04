--
--  Custom roles
-- -------------
-- Only the roles added in the administration; the base roles (guest, user, admin) live in the ACL neon files.
-- The name is the key used by user_roles of drago-ex/project-auth and by role_privileges, so it is never changed.
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    CONSTRAINT uq_roles_name UNIQUE (name)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

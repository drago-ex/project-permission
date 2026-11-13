--
--  Custom roles
-- -------------
-- Only the roles added in the administration; the base roles (guest, user, admin) live in the ACL neon files.
-- The name is the key used by user_roles of drago-ex/project-auth and by role_privileges, so it is never changed.
-- The title is what the administration shows; the privileges of a role that is not active do not apply.
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    icon VARCHAR(30) NOT NULL DEFAULT 'bi-person',
    active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT uq_roles_name UNIQUE (name)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

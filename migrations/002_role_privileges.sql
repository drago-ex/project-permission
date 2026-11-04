--
--  Privileges of the roles
-- ------------------------
-- What a role may do on top of the ACL neon files, by role name: a custom role or a base one (user, guest).
-- The resource is a presenter name, the privilege an action or a signal of it ("default", "edit", "delete").
CREATE TABLE role_privileges (
    role VARCHAR(50) NOT NULL,
    resource VARCHAR(100) NOT NULL,
    privilege VARCHAR(100) NOT NULL,
    PRIMARY KEY (role, resource, privilege)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

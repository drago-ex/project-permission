--
--  Seed resources
-- ----------------
INSERT INTO resources (resource, privilege, description) VALUES
    ('Backend:Permission', 'roles-read', 'View roles'),
    ('Backend:Permission', 'roles-write', 'Roles management'),
    ('Backend:Permission', 'users-read', 'View user roles'),
    ('Backend:Permission', 'users-write', 'Assign roles to users'),
    ('Backend:Permission', 'permissions-read', 'View permissions'),
    ('Backend:Permission', 'permissions-write', 'Manage permissions');

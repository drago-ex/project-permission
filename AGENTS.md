# Agent Guide

`drago-ex/project-permission` (type drago-tools-resource, PHP >=8.3 <9, `migration-priority` 20 after
project-auth): the administration of custom roles, role privileges and user roles, a database layer on top of
the static ACL of drago-ex/permission. The workspace rules are in `../AGENTS.md`; dependencies are in `composer.json`.

## Layout
- `migrations/`: `roles(id, name UNIQUE VARCHAR(50), description)` (custom roles only; id only for the datagrid)
  and `role_privileges(role, resource, privilege)`.
- `resources/app/Presentation/Backend/Permission`: `PermissionPresenter`, `RoleRules`, `PrivilegeList`,
  `conf.neon`, `3.menu.neon`, `acl.neon`, `Translate/cs.neon`; `Component/Users`, `Component/Roles` and
  `Component/Authorization` (the permissions page) each with its control and repository.
- `resources/assets/naja/permission-toggle.js` + `.scss`: `PermissionToggle`.
- `tests/RoleRules.phpt`.

## How it works
- The base is the static ACL of drago-ex/permission (`role.acl.neon` + the `acl.neon` of every presenter
  directory). `conf.neon` registers `RoleRules`, `PrivilegeList`, `PrivilegeCatalog` and a `decorator` of
  `Nette\Security\Authorizator` calling `RoleRules::apply()`. It must decorate `Authorizator`, not `Permission`:
  SecurityExtension registers the ACL with the type Authorizator, so a Permission decorator is never applied
  (regression test in `RoleRules.phpt`); `apply()` skips other implementations.
- `RoleRules::apply()` adds the custom roles (parent `Role::User`) and the `allow` rules of `role_privileges`,
  skipping unknown roles and resources; cached (key `rules`), `clean()` after every change in the administration.
  A `DriverException` (no tables yet) logs a warning and applies nothing.
- Everything is keyed by role NAME: `role_privileges` also holds rules of the base roles user/guest, user roles are
  `user_roles` of project-auth (`UserRoleEntity`). A custom role's name is immutable (readonly in the edit form,
  kept from the record on save); `RolesRepository::deleteRole()` removes the role, its privileges and its user
  rows in one transaction. A new role the ACL already knows is refused. Packages ship no permission seeds.
- `PrivilegeList` = `PrivilegeCatalog::getPrivileges()` per ACL resource, cached with `Cache::Files` of all
  `*.php`/`*.latte` of the presenter directory. A toggle is validated against that list and the ACL roles (not
  admin) before `allow()`/`deny()` and applied to the live ACL too, so the redrawn snippet is right.
- Permissions page states: `own` (a row of `role_privileges`, toggle), `inherited` (allowed by the ACL files or a
  parent role, locked), denied; admin is all locked. The users page lists every user
  (`COALESCE(GROUP_CONCAT(role), 'user')` in a derived table, so the grid filters and sorts `roles`); the form only
  edits roles, no role means `user`; delete takes all roles.
- Role descriptions and user names are data, never translated (`Html::el()->setText()`); base role labels are
  translated (Guest, User, Administrator).
- `PermissionPresenter::checkRequirements()` redirects `default` (the menu item) to `users`, or to `roles` without
  the `users` privilege; there is no `default` page.

## Permissions
Privileges of `Backend:Permission`: pages `users`, `roles`, `permissions`; `edit` (forms with `#[Access('edit')]`
on their factories, toggles, row edit); `delete` (dialog, `delete` form, row delete).
`BaseControl::handleOpenModal/OpenOffcanvas` and `RolesControl::handlePermissions` are `#[Access]` (page enough).
`BaseControl` uses `AccessCheck`; `$writeAllowed` = `isAllowed('edit')`, grid actions
`setVisible($this->isAllowed(...))`.

## Commands
```bash
composer phpstan
composer tester     # RoleRules.phpt: a readonly subclass overriding loadRules(), MemoryStorage;
                    # tests/bootstrap.php loads App\ from resources/app, then .dev-resource/app (drago-install --dev)
```

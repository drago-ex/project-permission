# Agent Guide

Use this file as package-specific guidance when working in this repository. Keep changes aligned with the existing public API, project conventions, and supported PHP range.

## Package Overview
- **Package**: `drago-ex/project-permission`
- **Type**: drago-tools-resource
- **Purpose**: Component for managing user permissions in a Drago project.
- **PHP**: >=8.3 <9
- **Runtime dependencies**: nette/di (^3.1), nette/security (^3.1), nette/application (^3.1), drago-ex/component (^3.0), drago-ex/database (^5.0), drago-ex/utils (^2.0), drago-ex/application (^4.0), drago-ex/form (^3.0), drago-ex/migration (^1.0), drago-ex/permission (^2.0), drago-ex/translator (^4.0.1), drago-ex/project-auth (^2.0), drago-ex/project-backend (^1.0), drago-ex/datagrid (^1.0), nette/caching (^3.3), nette/utils (^4.0)
- **License**: MIT

## Repository Layout
- `migrations/`: database migrations.
- `resources/`: application resources contributed to the consuming Drago project.
- `.github/`: CI workflows; keep their PHP versions and commands consistent with Composer and the package scripts.
- `composer.json`: package metadata, dependency constraints, autoloading, and available Composer scripts.

## Design (2026-11-03, the ACL-in-neon rework, user's decisions)
- The static ACL of drago-ex/permission (^2.0: `role.acl.neon` + the `acl.neon` of every presenter directory) is the base; this package only adds the database layer. No PermissionFactory/Provider any more: `conf.neon` registers `RoleRules`, `PrivilegeList`, `PrivilegeCatalog` and a `decorator` of `Nette\Security\Authorizator` (NOT `Permission`: SecurityExtension registers the ACL with the type Authorizator, so a Permission decorator was never applied, found by the netis integration test 2026-11-04; `apply(Authorizator)` skips other implementations; regression test in `RoleRules.phpt`) calling `RoleRules::apply()`, which adds the custom roles (parent `Role::User`) and the `allow` rules of `role_privileges`, skipping unknown roles/resources; cached (`Cache`, key `rules`), `clean()` after every change in the administration; a `DriverException` (no tables yet) logs a warning and applies nothing.
- Everything is keyed by role NAME: `roles(id, name UNIQUE VARCHAR(50), description)` holds only custom roles (id only for the datagrid), `role_privileges(role, resource, privilege)` also for the base roles user/guest, the users' roles are `user_roles` of project-auth (its `UserRoleEntity`). A custom role's name is immutable (readonly in the edit form, kept from the record on save); `RolesRepository::deleteRole()` removes the role, its privileges and its user rows in one transaction. A new role that the ACL already knows (base or neon role) is refused. No `resources`/`authorization`/`users_roles` tables and no seeds; packages ship no `migrations-permission/` any more.
- Privileges offered on the permissions page come from the code: `PrivilegeList` = `PrivilegeCatalog::getPrivileges()` per ACL resource, cached with `Cache::Files` of all *.php/*.latte of the presenter directory (user asked for caching that works in dev and production). A toggle is validated against that list and the ACL roles (not admin) before `allow()`/`deny()`, also applied to the live ACL (`allow`/`removeAllow`) so the redrawn snippet is right.
- Permissions page states: `own` (a row of role_privileges, toggle), `inherited` (allowed by the ACL files or a parent role without an own row, locked), denied; admin all locked. Users page lists every user (`COALESCE(GROUP_CONCAT(role), 'user')` in a derived table, so the grid filters/sorts `roles`); the form only edits roles (no add), no role = default `user`; delete takes all roles.
- Own privileges of `Backend:Permission`: pages `users`, `roles`, `permissions`; `edit` (forms with `#[Access('edit')]` on their factories, toggles, row edit) and `delete` (delete dialog + `delete` form, row delete). `BaseControl::handleOpenModal/OpenOffcanvas` and `RolesControl::handlePermissions` are `#[Access]` (page enough). `BaseControl` uses `AccessCheck`; `$writeAllowed` = `isAllowed('edit')`, grid actions `setVisible($this->isAllowed(...))`.
- Role descriptions and user names are data, never translated (`Html::el()->setText()`); base role labels are translated (Guest, User, Administrator).
- `PermissionPresenter::checkRequirements()` redirects `default` (the menu item) to `users`, or to `roles` without the `users` privilege (Nette runs it before the onStartup ACL check; there is no `default` page).
## Dependencies

Composer constraints describe the package contract. Before changing integration behavior, inspect the source and the actual call sites in this package; do not infer runtime usage solely from dependency declarations.
Development dependencies include nette/tester (^2.5), phpstan/phpstan-nette (^2), drago-ex/project-tools (^1.0).

## Essential Commands

```bash
composer phpstan                # phpstan analyse
composer tester                 # tester tests -s -C
```

## Tests

Nette Tester tests are in `tests/` (`RoleRules.phpt`: a readonly subclass overriding `loadRules()`, MemoryStorage); run them with `composer tester`. `tests/bootstrap.php` autoloads `App\` classes from `resources/app`, then from `.dev-resource/app`, where `drago-install --dev` copies the resources of the dependencies (e.g. `drago-ex/project-auth`).

## Installation Hooks

Composer install and update hooks invoke Drago project setup/cleanup commands. Inspect those scripts and their effects before invoking Composer operations that trigger them; they may modify generated project files.

## Conventions & Rules

- Preserve backward compatibility unless the task explicitly permits a breaking change. Check callers and public signatures before changing behavior.
- Follow the existing PHP style: strict types, explicit parameter and return types, tabs for indentation, and single-quoted strings where practical. Match nearby code when a convention is unclear.
- Keep package-specific configuration, resources, and migrations in their established directories. Do not edit generated or consuming-project files without confirming they are part of this repository’s intended output.
- Add or update focused tests for behavioral changes when this package has a runnable test setup. Do not invent test commands; use only the scripts and configuration present here.
- Update README examples and Composer metadata when a public API or supported requirement changes.
- Menu: `Backend/Permission/3.menu.neon` adds the item "Permissions" to project-backend-ui (user, 2026-11-04, reverses the 2026-10-27 rule that project-preset ships the menu neons): the sidebar parameter is unused without project-backend-ui, which hides items the user may not open.

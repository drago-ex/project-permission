# Agent Guide

`drago-ex/project-permission` (type drago-tools-resource, PHP >=8.3 <9, `migration-priority` 20 after
project-auth): the administration of custom roles, role privileges and user roles, a database layer on top of
the static ACL of drago-ex/permission. The workspace rules are in `../AGENTS.md`; dependencies are in `composer.json`.

## Layout
- `migrations/`: `roles(id, name UNIQUE VARCHAR(50), title, description NULL, active)` (custom roles only; id only for the datagrid; the name is the slug and the key, the title is what the administration shows)
  and `role_privileges(role, resource, privilege)`.
- `resources/app/Presentation/Backend/Permission`: `PermissionPresenter`, `RoleRules`, `PrivilegeList`,
  `conf.neon`, `3.menu.neon`, `acl.neon`, `Translate/cs.neon`; `Component/Users`, `Component/Roles` and
  `Component/Authorization` (the permissions of a role, an offcanvas of the roles page) each with its control and repository.
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
- `PrivilegeList` caches per ACL resource `PrivilegeCatalog::getSources()` (privileges with the places of the code
  by kind Page/Signal/Form) and the titles of the page templates (`{block title}{_'…'}{/block}`, read by a regex),
  with `Cache::Files` of all `*.php`/`*.latte` of the presenter directory. `get()` = the privilege names;
  `getLabels()` = the parameter `permissionLabels` (merged by Nette from the `acl.neon` files of the packages,
  `[]` in `conf.neon`, passed as `labels: %permissionLabels%`) over the template titles, untranslated keys.
  A toggle is validated against `get()` and the ACL roles (not admin) before `allow()`/`deny()` and applied to the
  live ACL too, so the redrawn snippet is right.
- Permissions of a role (`AuthorizationControl`, `Authorization.latte`): an offcanvas, not a page (user's choice: the roles grid shows by a
  shield that a role has permissions and opens them in place; the page with its select of roles is gone). The control is the child
  `authorization` of `RolesControl` (`createComponentAuthorization()`, rendered by `{control authorization}` in `Roles.latte`). The grid column
  "Permissions" (`formatPermissions()`, shown with the privilege `permissions`) is a `btn btn-xs btn-icon btn-subtle theme-primary` with
  `bi-shield` linking `authorization:open!`; the base roles, which are not in the table, open from the dropdown "Base roles" of the heading
  (`AuthorizationControl::BaseRoles`). Nothing is persistent: `handleOpen(role)`, `handleResource(role, resource)` (both
  `#[Access('permissions')]`) and `handleTogglePermission(role, resource, privilege)` (`#[Access('edit')]`, POST) get the role and the presenter
  with every signal, `setRole()` refuses a role the ACL does not know, and each redraws only the snippet `permissions` inside the offcanvas
  (the closed offcanvas renders empty). Two columns: presenters on the left (`nav-list`, `allowed/total` from `resourceAllowed`, `col-md-4 col-lg-3`), privileges
  of the selected one on the right (the first one when unknown). Each privilege is one line: its name, the translated description and its labels
  (`$kinds` maps the PrivilegeCatalog kinds to "Page", "Action", "Form"), then the toggle. The labels have no tooltip with the names of the methods: they mean nothing
  to an administrator who sets roles.
- `conf.neon` registers `Drago\Permission\Tracy\RoleSwitchPanel` in `tracy: bar:` (debug mode only), so every project
  with this package can try its custom roles; hence `tracy/tracy` in `require`.
- Descriptions in a package's `acl.neon` must quote a text with parentheses: NEON reads `text (…)` as an entity
  and the whole DI container fails.
- Roles grid: Role (the title as plain text), Slug (`name`), Permissions (the shield), Description and State. A role has no icon and the
  grid no count of users, so it stays short. The state is `setState('activate')` → `handleActivate()` (own privilege `activate`): a role that is
  not active stays a role of the ACL (its users keep what `user` may do), but `RoleRules::loadRules()` leaves its privileges out.
- States of a privilege in the offcanvas: `own` (a row of `role_privileges`, toggle), `inherited` (allowed by the ACL files or a
  parent role, locked), denied; admin is all locked. The users page lists every user
  (`COALESCE(GROUP_CONCAT(role), 'user')` in a derived table, so the grid filters and sorts `roles`); the form only
  edits roles, no role means `user`; delete takes all roles.
- Role titles and descriptions and user names are data, never translated (`Html::el()->setText()`); base role labels are
  translated (Guest, User, Administrator).
- `PermissionPresenter::checkRequirements()` redirects `default` (the menu item) to `users`, or to `roles` without
  the `users` privilege; there is no `default` page.

## Permissions
Privileges of `Backend:Permission`: pages `users`, `roles`; `permissions` (opening the permissions of a role) and `activate` (the state of a role), both described in `acl.neon`; `edit` (forms with `#[Access('edit')]`
on their factories, toggles, the Edit of the row menu); `delete` (dialog, `delete` form, the Delete of the row menu and of the bar);
`export` (CSV of roles and users).
`BaseControl::handleOpenModal/OpenOffcanvas` are `#[Access]` (page enough).
`BaseControl` uses `AccessCheck`; `$writeAllowed` = `isAllowed('edit')`, grid actions
`setVisible($this->isAllowed(...))`.

Every grid has inline filters and no row click that opens the form: a row has a menu of three dots (row actions of drago-ex/datagrid, `addActionEdit()` and `addActionDelete()`, the latter calls `handleDelete([$id])`), the bar under the table has the bulk actions (roles and users: Export, Delete), a click anywhere on a row chooses it (`setRowClickAction('select')`), states are badges that are only read (`setBadges()`). The name of a row or bulk action is its privilege, and the control has a `handle<Name>()` method the callback calls, so `PrivilegeCatalog` finds the privilege; `export` comes from `DataGrid::handleExport()`. `BaseControl::handleDelete(array $ids)` opens one confirmation for all chosen items (`$deleteTitles`, the hidden field `ids` of the delete form) and `deleteItem()` runs for each.

## Commands
```bash
composer phpstan
composer tester     # RoleRules.phpt: a readonly subclass overriding loadRules(), MemoryStorage;
                    # tests/bootstrap.php loads App\ from resources/app, then .dev-resource/app (drago-install --dev)
```

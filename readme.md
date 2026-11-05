# Drago Project Permission

Administration of roles and permissions for Drago Project: custom roles, their privileges on the pages and
buttons of the administration, and the roles of users, on top of the ACL of the packages.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-permission.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-permission)
[![Coding Style](https://github.com/drago-ex/project-permission/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-permission/actions/workflows/coding-style.yml)

## Requirements

- PHP >= 8.3, < 9
- [Drago Project](https://github.com/drago-ex/project)

## Installation

```bash
composer require drago-ex/project-permission
```

[Project Tools](https://github.com/drago-ex/project-tools) copies the files of the package into the project,
where you adapt them.

## Database

The migrations create the tables `roles` (custom roles with a description) and `role_privileges` (role, resource,
privilege); the roles of users are in `user_roles` of [drago-ex/project-auth](https://github.com/drago-ex/project-auth),
so the migrations run after it. Run them with `bin/project-setup` of Drago Project or
`php vendor/bin/migration db:migrate --packages`. Without the tables only the ACL files apply.

## Assets

Register the Naja extension that saves a privilege right after its switch is changed in `assets/admin.js`:

```js
import PermissionToggle from './naja/permission-toggle.js';

registerNajaExtensions(
	// ...
	PermissionToggle
);
```

and its styles in `assets/admin.scss`:

```scss
@import 'naja/permission-toggle';
```

The lists of users and roles come from [drago-ex/datagrid](https://github.com/drago-ex/datagrid), set up in
`admin.js` and `admin.scss`.

## How It Works

The base is the ACL of [drago-ex/permission](https://github.com/drago-ex/permission): the roles guest, user and
admin and the `acl.neon` files of the packages. The administration adds:

- **custom roles** (e.g. `moderator`), which inherit `user`,
- **privileges** of a role on a presenter, also for the base roles `user` and `guest`,
- **roles of users**; a user may have more roles, a user without one is `user`.

The privileges offered are read from the code of the presenters: their pages (`default`, `edit`, …) and signals
(`delete`, `approveComment`, …), so a new page or button of any package shows up by itself. What the ACL files or
a parent role already allow is shown as inherited and locked; the administrator is allowed everything.

```latte
{link :Backend:Permission:}       {* the menu item: roles of users, or roles without that privilege *}
{link :Backend:Permission:users}  {* roles of users *}
{link :Backend:Permission:roles}  {* custom roles and their permissions *}
```

The menu item comes from `Backend/Permission/3.menu.neon` with
[Backend UI](https://github.com/drago-ex/project-backend-ui).

## Permissions

- The pages are checked by the privileges `users`, `roles` and `permissions` of `Backend:Permission`, the changes
  by `edit` and `delete`.
- Whoever may edit the roles of users can give any role, including `admin`; give `edit` only to administrators.
- The base roles come from the ACL files and cannot be changed here. The name of a custom role is its key, so
  only its description can be changed.

# Drago Project Permission

Administration of roles and permissions on top of the static ACL of a Drago / Nette project.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-permission.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-permission)
[![Coding Style](https://github.com/drago-ex/project-permission/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-permission/actions/workflows/coding-style.yml)

## Requirements

- PHP >= 8.3, < 9
- [Drago Project](https://github.com/drago-ex/project)

## Installation

```bash
composer require drago-ex/project-permission
```

## Files copied into the project

The files are copied into the project by [drago-ex/project-tools](https://github.com/drago-ex/project-tools),
as set in `extra.drago-tools` of this package's `composer.json`.

## Database migration

```bash
php vendor/bin/migration db:migrate --packages
```

The package has `migration-priority` 20, so it runs after `drago-ex/project-auth`, whose `users` and `user_roles`
tables it uses.

## Assets

Register the Naja extension, which saves a privilege of a role right after its switch is changed, in `assets/admin.js`:

```js
import PermissionToggle from './naja/permission-toggle.js';

registerNajaExtensions(
	// ...
	PermissionToggle
);
```

and include its styles in `assets/admin.scss`:

```scss
@import 'naja/permission-toggle';
```

The lists of users and roles come from [drago-ex/datagrid](https://github.com/drago-ex/datagrid); set up its assets in
`admin.js` and `admin.scss` as its README shows.

## How it works

The base of the permissions is the static ACL of [drago-ex/permission](https://github.com/drago-ex/permission): the roles
guest, user and admin and the `acl.neon` files of the packages. This package adds what is set in the administration
on top of it, in `app/Presentation/Backend/Permission/conf.neon`:

- **custom roles** (e.g. `moderator`), which inherit `user`, so their users keep what every signed-in user may,
- **privileges** of a role on a presenter, also for the base roles `user` and `guest`,
- **roles of the users**, stored in `user_roles` of [drago-ex/project-auth](https://github.com/drago-ex/project-auth);
  a user may have more roles.

The privileges offered are read from the code of the presenters by `PrivilegeCatalog` of drago-ex/permission: their pages
(`default`, `edit`, …) and signals (`delete`, `approveComment`, …). A new page or button of any package shows up without
any list kept by hand. The list is cached until a file of the presenter directory changes; the roles and privileges
of the database are cached until the administration changes them.

The administration has three pages:

```latte
{link :Backend:Permission:}       {* for the menu: the roles of users, or the roles without that privilege *}
{link :Backend:Permission:users}  {* roles of the users *}
{link :Backend:Permission:roles}  {* custom roles, a link to their permissions *}
```

On the permissions page of a role, what the ACL files or a parent role already allow is shown as inherited and locked;
the administrator is allowed everything. With [drago-ex/project-backend-ui](https://github.com/drago-ex/project-backend-ui),
the pages get their menu item from `Backend/Permission/3.menu.neon`.

## Security

- The base roles guest, user and admin come from the ACL files and cannot be changed here; the name of a custom role
  is the key of its privileges and of the user roles, so only its description can be changed.
- The pages are checked by the privileges `users`, `roles` and `permissions` of `Backend:Permission`, the changes
  by `edit` and `delete`.
- Whoever may edit the roles of users can give any role, including `admin`; give `edit` only to administrators.
- Without the tables (e.g. before the migrations) only the ACL files apply.

## Database

- `roles`: the custom roles (name, description)
- `role_privileges`: role, resource (a presenter) and privilege
- `user_roles` of drago-ex/project-auth: the roles of the users

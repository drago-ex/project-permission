# Drago Project Permission

English | [Čeština](readme.cs.md)

Component for ACL and permission management in a Drago / Nette project.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-permission.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-permission)
[![Coding Style](https://github.com/drago-ex/project-permission/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-permission/actions/workflows/coding-style.yml)

## Requirements

- PHP >= 8.3, < 9
- Nette Framework
- Composer
- Bootstrap
- Naja
- Node.js
- Drago Project core packages

## Installation

```bash
composer require drago-ex/project-permission
```

## Project files

File copying is handled automatically by [drago-ex/project-tools](https://github.com/drago-ex/project-tools),
which must be installed in your project. Without it, copy the files manually according to the `copy` section
in this package's `composer.json`. To skip this package, set `"skip": true` under
`extra.drago-tools.packages.<package-name>` in your root `composer.json`.

After installation, run the package migrations, load the provided service configuration from
`app/Presentation/Backend/Permission/conf.neon`, register the Naja extension from `assets/naja/permission-toggle.js`
and include styles from `assets/naja/permission-toggle.scss`.

Example:

```js
import naja from 'naja';
import PermissionToggle from './naja/permission-toggle.js';
import './naja/permission-toggle.scss';

naja.registerExtension(new PermissionToggle());
```

## What it does

- manages **roles**
- assigns **multiple roles to users**
- manages **resource + privilege** records
- allows role permissions to be toggled in the admin UI
- builds a Nette `Permission` ACL from registered providers and database data

The package ships with an admin section for:

- **Users** - assign roles to existing users
- **Roles** - create, edit and delete custom roles
- **Permissions** - allow or deny access for a selected role

The backend module is installed under `App\Presentation\Backend\Permission` and uses the `Backend:Permission`
ACL resource. Module permissions are registered by `PermissionProvider` classes found in the presentation layer.
With [drago-ex/project-backend-ui](https://github.com/drago-ex/project-backend-ui), the item "Permissions" appears
in the sidebar (`Backend/Permission/3.menu.neon`).

System roles such as `admin`, `user` and `guest` are handled as protected base roles.

## Security

- System roles (`admin`, `user`, `guest`) cannot be renamed or deleted.
- Signals and forms are checked by the ACL (`{component}-read` / `{component}-write` on `Backend:Permission`).
- Whoever may assign roles can assign any role, including `admin`; give `users-write` only to administrators.
- Without the permission tables, only the rules of the `PermissionProvider` classes apply.

## Database

The package works with these tables:

- `roles`
- `users_roles`
- `resources`
- `authorization`

Seed migrations also add default roles and backend permission resources.

## Roles of users

The roles assigned here are loaded at sign-in by `getRolesByUser()` of
[drago-ex/project-auth](https://github.com/drago-ex/project-auth#permissions); its README shows the code.

## Privileges of other packages

Packages with their own administration (e.g. project-blog, project-static-page) ship their privileges in
`migrations-permission/`. Import them by hand, so roles can be given them here:

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/project-blog/migrations-permission
```

The add, edit and delete buttons follow the `{component}-write` privilege by the `WriteAccess` trait of
[drago-ex/permission](https://github.com/drago-ex/permission#write-buttons).

## Database migration

```bash
php vendor/bin/migration db:migrate --packages
```

The package has `migration-priority` 20, so it runs after `drago-ex/project-auth`, whose `users` table it uses.

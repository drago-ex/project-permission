# Drago Project Permission

Component for ACL and permission management in a Drago / Nette project.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/project-permission/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-permission.svg)](https://badge.fury.io/ph/drago-ex/project-permission)

## Requirements
- PHP >= 8.3
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

System roles such as `admin`, `user` and `guest` are handled as protected base roles.

## Database

The package works with these tables:

- `roles`
- `users_roles`
- `resources`
- `authorization`

Seed migrations also add default roles and backend permission resources.

## Integration with project-auth

To load roles from this package, implement the prepared `getRolesByUser()` method in
[`UserRepository`](https://github.com/drago-ex/project-auth/blob/main/resources/app/Presentation/Sign/User/UserRepository.php):

```php
public function getRolesByUser(int $userId): array
{
	$roles = $this->getConnection()
		->select('r.*')->from(RolesEntity::Table)->as('r')
		->innerJoin(UsersRolesEntity::Table)->as('ur')->on('ur.role_id = r.id')
		->where('ur.%n = ?', UsersRolesEntity::ColumnUserId, $userId)
		->fetchPairs(value: RolesEntity::ColumnName);

	$roles = array_values($roles);
	return $roles ?: [\Drago\Permission\Role::RoleUser];
}
```

## Database migration
```bash
php vendor/bin/migration db:migrate vendor/drago-ex/project-permission/migrations
```

## Automated setup

This package exposes setup commands in `composer.json` under `extra.drago-tools.commands`.
If [drago-ex/project-tools](https://github.com/drago-ex/project-tools) is installed, you can run them from the project root:

```bash
php vendor/bin/drago-setup
```

The migrations depend on the `users` table. Without `drago-setup`, run the
`drago-ex/project-auth` migrations first.

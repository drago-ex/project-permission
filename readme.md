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
File copying is handled automatically by [drago-ex/project-installer](https://github.com/drago-ex/project-installer),
which must be installed in your project. Without it, copy the files manually according to the `copy` section
in this package's `composer.json`. To skip this package, set `"skip": true` under
`extra.drago-project.packages.<package-name>` in your root `composer.json`.

After installation, run the package migrations, load the provided service configuration, register the Naja extension from `assets/permission-toggle.js` and include styles from `assets/permission-togle.scss`.

Example:

```js
import naja from 'naja';
import PermissionToggle from 'drago-ex/project-permission/assets/permission-toggle';
import 'drago-ex/project-permission/assets/permission-toggle.scss';

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

System roles such as `admin`, `user` and `guest` are handled as protected base roles.

## Database

The package works with these tables:

- `roles`
- `users_roles`
- `resources`
- `authorization`

Seed migrations also add default roles and AccessControl permissions for the backend module.

## Integration with project-auth

[`UserRepository`](https://github.com/drago-ex/project-auth/blob/main/resources/app/UI/Backend/Sign/User/UserRepository.php#L58)
fill in the body of the prepared `getRolesByUser()` method:

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
- https://github.com/drago-ex/migration
```bash
php vendor/bin/migration db:migrate vendor/drago-ex/project-permission/migrations
```

**Important Note on Migrations:**
The migrations in this package depend on the `users` table. If you are not using the automated `package-setup` tool, ensure that you run the migrations from **`drago-ex/project-auth`** first to create the necessary foreign key targets.

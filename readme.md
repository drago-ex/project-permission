# Drago Project Permission

Component for ACL and permission management in a Drago / Nette project.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://raw.githubusercontent.com/drago-ex/project-permission/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-permission.svg)](https://badge.fury.io/ph/drago-ex/project-permission)

## Requirements
- PHP >= 8.3
- Nette Framework
- Composer
- Bootstrap
- Naja
- Node.js
- Drago Project core packages

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

## Installation

```bash
composer require drago-ex/project-permission
```

After installation, run the package migrations, load the provided service configuration, register the Naja extension from `assets/permission-toggle.js` and include styles from `assets/permission-togle.scss`.

Example:

```js
import naja from 'naja';
import PermissionToggle from 'drago-ex/project-permission/assets/permission-toggle';

naja.registerExtension(new PermissionToggle());
```

## Database

The package works with these tables:

- `roles`
- `users_roles`
- `resources`
- `authorization`

Seed migrations also add default roles and AccessControl permissions for the backend module.

## Integration with project-auth

In [`UserAuthenticator`](https://github.com/drago-ex/project-auth/blob/main/resources/app/UI/Backend/Sign/User/UserAuthenticator.php)
add a `getRolesByUser()` method (or SQL query) that fetches the user's roles, then pass them into `SimpleIdentity` in both `authenticate()` and `wakeupIdentity()`:

```php
/**
 * Finds the roles of a user.
 */
public function getRolesByUser(int $userId): array
{
    return $this->userRepository->getConnection()
        ->select('r.*')->from(RolesEntity::Table)->as('r')
        ->innerJoin(UsersRolesEntity::Table)->as('ur')->on('ur.role_id = r.id')
        ->where('ur.%n = ?', UsersRolesEntity::ColumnUserId, $userId)
        ->fetchPairs(RolesEntity::PrimaryKey, RolesEntity::ColumnName);
}
```

```php
// in authenticate() and wakeupIdentity()
$roles = $this->getRolesByUser($user->id);
return new SimpleIdentity(id: $user->id, roles: $roles, data: $user);
```

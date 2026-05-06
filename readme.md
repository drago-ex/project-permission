# Drago Project Permission

Component for ACL and permission management in a Drago / Nette project.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://raw.githubusercontent.com/drago-ex/project-permission/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-permission.svg)](https://badge.fury.io/ph/drago-ex/project-permission)

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

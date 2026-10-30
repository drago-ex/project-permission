# Agent Guide

Use this file as package-specific guidance when working in this repository. Keep changes aligned with the existing public API, project conventions, and supported PHP range.

## Package Overview
- **Package**: `drago-ex/project-permission`
- **Type**: drago-tools-resource
- **Purpose**: Component for managing user permissions in a Drago project.
- **PHP**: >=8.3 <9
- **Runtime dependencies**: nette/di (^3.1), nette/security (^3.1), nette/application (^3.1), drago-ex/component (^3.0), drago-ex/database (^4.1), drago-ex/utils (^2.0), drago-ex/application (^4.0), drago-ex/form (^3.0), drago-ex/migration (^1.0), drago-ex/permission (^1.0), drago-ex/translator (^4.0.1), drago-ex/project-auth (^1.0), drago-ex/project-backend (^1.0), drago-ex/datagrid (^1.0)
- **License**: MIT

## Repository Layout
- `migrations/`: database migrations.
- `resources/`: application resources contributed to the consuming Drago project.
- `.github/`: CI workflows; keep their PHP versions and commands consistent with Composer and the package scripts.
- `composer.json`: package metadata, dependency constraints, autoloading, and available Composer scripts.

## Buttons and permissions
- Roles of users are loaded at sign-in by `getRolesByUser()` of project-auth (code in its README, project-preset-permission overrides it). Privileges of other packages come from their `migrations-permission/`, imported by hand (README "Privileges of other packages"), never automatically.
- `Component\BaseControl` uses the `WriteAccess` trait of drago-ex/permission (^1.0): `isWriteAllowed()` is the `"{component}-write"` ACL check of the add/edit/delete buttons. Grid actions get `->setVisible($writeAllowed)`; templates get `$writeAllowed`. Other packages (project-blog, project-static-page, ...) have no permission code; their README shows how to add the trait by hand, project-preset is meant to ship it pre-done.
- Role descriptions and user names are data and are never translated (form items wrapped in `Html::el()->setText()`, so the translator does not report them); privilege descriptions of `resources` come from packages and are translated (`cs.neon`).
- `PermissionFactory` adds the roles of the `roles` table that are not built in with `Role::RoleUser` as parent, so a custom role keeps the signed-in basics (Sign, front, account); without it a role like "moderator" got 403 even on sign-out.

## Key Parts
- The users and roles forms keep their edit mode after a server error (`setEditMode()` from `onError` when the form holds an id: locked user select, readonly name of a system role, caption "Edit roles"); before, the redrawn form looked like a new one while the save still used the edited id (2026-10-25, code review).
- Permissions page: a privilege a parent role allows and the role itself does not is "Allowed (inherited)" and locked; the rule lives only in `AuthorizationControl::getInheritedPrivileges()` (the counters and the template read its result). A resources row with privilege `all` means every privilege of the resource, through `PermissionFactory::privilege()` for both the ACL and the inherited check.
- `PermissionPresenter` has no default page: `checkRequirements()` redirects `default` (the single menu item) to `users`, or to `roles` without `users-read`; the users page links to the roles (blog-style button), the roles page back. Not in `startup()`: Nette calls the `onStartup` handlers (where the Authorization trait checks the ACL, and there is no `default-read`) before `startup()`, but `checkRequirements()` before them; the roles page checks the access itself (2026-10-24, tested with a roles-read-only role).

## Dependencies

Composer constraints describe the package contract. Before changing integration behavior, inspect the source and the actual call sites in this package; do not infer runtime usage solely from dependency declarations.
Development dependencies include nette/tester (^2.5), phpstan/phpstan-nette (^2), drago-ex/project-tools (^1.0).

## Essential Commands

```bash
composer phpstan                # phpstan analyse
composer tester                 # tester tests -s -C
```

## Tests

Nette Tester tests are in `tests/`; run them with `composer tester`. `tests/bootstrap.php` autoloads `App\` classes from `resources/app`, then from `.dev-resource/app`, where `drago-install --dev` copies the resources of the dependencies (e.g. `drago-ex/project-auth`).

## Installation Hooks

Composer install and update hooks invoke Drago project setup/cleanup commands. Inspect those scripts and their effects before invoking Composer operations that trigger them; they may modify generated project files.

## Conventions & Rules

- Preserve backward compatibility unless the task explicitly permits a breaking change. Check callers and public signatures before changing behavior.
- Follow the existing PHP style: strict types, explicit parameter and return types, tabs for indentation, and single-quoted strings where practical. Match nearby code when a convention is unclear.
- Keep package-specific configuration, resources, and migrations in their established directories. Do not edit generated or consuming-project files without confirming they are part of this repository’s intended output.
- Add or update focused tests for behavioral changes when this package has a runnable test setup. Do not invent test commands; use only the scripts and configuration present here.
- Update README examples and Composer metadata when a public API or supported requirement changes.

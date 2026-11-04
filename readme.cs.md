# Drago Project Permission

Správa rolí a oprávnění nad statickým ACL projektu Drago / Nette.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-permission.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-permission)
[![Coding Style](https://github.com/drago-ex/project-permission/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-permission/actions/workflows/coding-style.yml)

## Požadavky

- PHP >= 8.3, < 9
- [Drago Project](https://github.com/drago-ex/project)

## Instalace

```bash
composer require drago-ex/project-permission
```

## Soubory kopírované do projektu

Soubory do projektu kopíruje [drago-ex/project-tools](https://github.com/drago-ex/project-tools)
podle nastavení v `extra.drago-tools` v `composer.json` tohoto balíčku.

## Migrace databáze

```bash
php vendor/bin/migration db:migrate --packages
```

Balíček má `migration-priority` 20, takže běží po `drago-ex/project-auth`, jehož tabulky `users` a `user_roles`
používá.

## Assety

Zaregistrujte rozšíření Naja, které uloží privilegium role hned po přepnutí jeho přepínače, v `assets/admin.js`:

```js
import PermissionToggle from './naja/permission-toggle.js';

registerNajaExtensions(
	// ...
	PermissionToggle
);
```

a vložte jeho styly do `assets/admin.scss`:

```scss
@import 'naja/permission-toggle';
```

Seznamy uživatelů a rolí pocházejí z [drago-ex/datagrid](https://github.com/drago-ex/datagrid); nastavte jeho assety
v `admin.js` a `admin.scss` podle jeho README.

## Jak to funguje

Základem oprávnění je statické ACL z [drago-ex/permission](https://github.com/drago-ex/permission): role
guest, user a admin a soubory `acl.neon` balíčků. Tento balíček k nim přidává to, co se nastaví v administraci,
v `app/Presentation/Backend/Permission/conf.neon`:

- **vlastní role** (např. `moderator`), které dědí z `user`, takže jejich uživatelé smí vše, co každý přihlášený,
- **privilegia** role nad presenterem, i pro základní role `user` a `guest`,
- **role uživatelů**, uložené v `user_roles` z [drago-ex/project-auth](https://github.com/drago-ex/project-auth);
  uživatel může mít víc rolí.

Nabízená privilegia se čtou z kódu presenterů pomocí `PrivilegeCatalog` z drago-ex/permission: jejich stránky
(`default`, `edit`, …) a signály (`delete`, `approveComment`, …). Nová stránka nebo tlačítko kteréhokoli balíčku se
objeví bez seznamu udržovaného ručně. Seznam je v cache, dokud se nezmění soubor ve složce presenteru; role
a privilegia z databáze jsou v cache, dokud je administrace nezmění.

Administrace má tři stránky:

```latte
{link :Backend:Permission:}       {* do menu: role uživatelů, bez tohoto privilegia role *}
{link :Backend:Permission:users}  {* role uživatelů *}
{link :Backend:Permission:roles}  {* vlastní role, odkaz na jejich oprávnění *}
```

Na stránce oprávnění role je to, co už povolují soubory ACL nebo nadřazená role, zobrazené jako zděděné a zamčené;
administrátor smí všechno. S [drago-ex/project-backend-ui](https://github.com/drago-ex/project-backend-ui)
dostanou stránky položku menu z `Backend/Permission/3.menu.neon`.

## Bezpečnost

- Základní role guest, user a admin pocházejí ze souborů ACL a tady je změnit nejde; název vlastní role je klíčem
  jejích privilegií i rolí uživatelů, takže změnit jde jen její popis.
- Stránky se kontrolují privilegii `users`, `roles` a `permissions` nad `Backend:Permission`, změny
  privilegii `edit` a `delete`.
- Kdo smí upravovat role uživatelů, může přidělit jakoukoli roli včetně `admin`; `edit` dávejte jen administrátorům.
- Bez tabulek (např. před migracemi) platí jen soubory ACL.

## Databáze

- `roles`: vlastní role (název, popis)
- `role_privileges`: role, zdroj (presenter) a privilegium
- `user_roles` z drago-ex/project-auth: role uživatelů

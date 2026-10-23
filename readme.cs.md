# Drago Project Permission

[English](readme.md) | Čeština

Komponenta pro správu ACL a oprávnění v projektu Drago / Nette.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-permission.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-permission)
[![Coding Style](https://github.com/drago-ex/project-permission/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-permission/actions/workflows/coding-style.yml)

## Požadavky

- PHP >= 8.3, < 9
- Nette Framework
- Composer
- Bootstrap
- Naja
- Node.js
- Základní balíčky Drago Project

## Instalace

```bash
composer require drago-ex/project-permission
```

## Soubory projektu

Kopírování souborů obstarává automaticky [drago-ex/project-tools](https://github.com/drago-ex/project-tools),
který musí být v projektu nainstalovaný. Bez něj zkopírujte soubory ručně podle sekce `copy`
v `composer.json` tohoto balíčku. Pro přeskočení balíčku nastavte `"skip": true` v
`extra.drago-tools.packages.<package-name>` v kořenovém `composer.json`.

Po instalaci spusťte migrace balíčku, načtěte dodanou konfiguraci služeb z
`app/Presentation/Backend/Permission/conf.neon`, zaregistrujte rozšíření Naja z `assets/naja/permission-toggle.js`
a připojte styly z `assets/naja/permission-toggle.scss`.

Konfigurace nahrazuje konfiguraci drago-ex/permission (`src/Permission/conf.neon`): pokud jste ji připojili pro statická
oprávnění, toto připojení odeberte, jinak se zaregistrují dva autorizátory.

Role se berou z databáze, proto prvnímu administrátorovi přidělte roli `admin` (webový instalátor z
drago-ex/project-install to udělá za vás):

```sql
INSERT INTO users_roles (user_id, role_id) SELECT 1, id FROM roles WHERE name = 'admin';
```

Příklad:

```js
import naja from 'naja';
import PermissionToggle from './naja/permission-toggle.js';
import './naja/permission-toggle.scss';

naja.registerExtension(new PermissionToggle());
```

## Co balíček dělá

- spravuje **role**
- přiřazuje uživatelům **více rolí**
- spravuje záznamy **zdroj + privilegium**
- umožňuje v administraci přepínat oprávnění rolí
- sestavuje Nette ACL `Permission` z registrovaných providerů a dat v databázi

Balíček obsahuje sekci administrace pro:

- **Uživatele** - přiřazení rolí existujícím uživatelům
- **Role** - vytváření, úpravy a mazání vlastních rolí
- **Oprávnění** - povolení nebo zakázání přístupu vybrané roli

Modul administrace se instaluje do `App\Presentation\Backend\Permission` a používá ACL zdroj
`Backend:Permission`. Oprávnění modulů registrují třídy `PermissionProvider` nalezené v prezentační vrstvě.
S [drago-ex/project-backend-ui](https://github.com/drago-ex/project-backend-ui) se v postranním menu objeví
položka „Oprávnění“ (`Backend/Permission/3.menu.neon`).

Systémové role jako `admin`, `user` a `guest` jsou chráněné základní role.
Vlastní role dědí z `user`, takže se jejich uživatelé vždy odhlásí a vidí to, co přihlášený uživatel.

## Bezpečnost

- Systémové role (`admin`, `user`, `guest`) nejde přejmenovat ani smazat.
- Signály a formuláře kontroluje ACL (`{component}-read` / `{component}-write` nad `Backend:Permission`).
- Kdo smí přiřazovat role, může přiřadit jakoukoli roli včetně `admin`; `users-write` dávejte jen administrátorům.
- Bez tabulek oprávnění platí jen pravidla tříd `PermissionProvider`.

## Databáze

Balíček pracuje s těmito tabulkami:

- `roles`
- `users_roles`
- `resources`
- `authorization`

Výchozí migrace přidají také základní role a zdroje oprávnění administrace.

## Role uživatelů

Zde přiřazené role se při přihlášení načítají přes `getRolesByUser()` z
[drago-ex/project-auth](https://github.com/drago-ex/project-auth#permissions); jeho README ukazuje kód.

## Privilegia ostatních balíčků

Balíčky s vlastní administrací (např. project-blog, project-static-page) dodávají svá privilegia v
`migrations-permission/`. Naimportujte je ručně, aby je bylo možné zde přidělovat rolím:

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/project-blog/migrations-permission
```

Tlačítka pro přidání, úpravu a mazání se řídí privilegiem `{component}-write` přes trait `WriteAccess` z
[drago-ex/permission](https://github.com/drago-ex/permission#write-buttons).

## Migrace databáze

```bash
php vendor/bin/migration db:migrate --packages
```

Balíček má `migration-priority` 20, takže běží po `drago-ex/project-auth`, jehož tabulku `users` používá.

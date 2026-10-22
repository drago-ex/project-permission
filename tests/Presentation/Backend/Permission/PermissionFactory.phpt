<?php

/**
 * Test: App\Presentation\Backend\Permission\PermissionFactory
 */

declare(strict_types=1);

use App\Presentation\Backend\Permission\PermissionFactory;
use App\Presentation\Backend\Permission\PermissionProvider;
use Dibi\Connection;
use Drago\Permission\Role;
use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../../../bootstrap.php';


test('Without the permission tables only the provider rules apply, and the error is logged', function () {
	$logDir = __DIR__ . '/../../../tmp/log-' . getmypid();
	@mkdir($logDir, 0o777, true);
	Tester\Helpers::purge($logDir);
	Debugger::$logDirectory = $logDir;

	// Port 1 refuses connections, so loading the database rules fails.
	$connection = new Connection(['driver' => 'mysqli', 'host' => '127.0.0.1', 'port' => 1, 'lazy' => true]);
	$acl = (new PermissionFactory($connection, [new PermissionProvider]))->create();

	try {
		Assert::true($acl->isAllowed(Role::RoleAdmin, 'Backend:Permission', 'users-write'));
		Assert::false($acl->isAllowed(Role::RoleUser, 'Backend:Permission', 'users-read'));
		Assert::false($acl->isAllowed(Role::RoleGuest, 'Backend:Permission', 'users-read'));
		Assert::true(is_file($logDir . '/warning.log'));
	} finally {
		Tester\Helpers::purge($logDir);
		rmdir($logDir);
	}
});

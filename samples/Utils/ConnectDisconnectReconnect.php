<?php

use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$withStrategy = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: true);

tryConnectDisconnectReconnect($withStrategy);

$withoutStrategy = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: false);

tryConnectDisconnectReconnect($withoutStrategy);

/**
 * @param Connection|MySQLiConnection $context
 * @return void
 */
function tryConnectDisconnectReconnect(Connection|MySQLiConnection $context): void
{
    $status = 'Connection Status: %s';

    // Connectar

    try {
        $context->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

    var_dump(sprintf($status, $context->isConnected() ? 'Connected' : 'Disconnected'));

    // Desconnectar

    try {
        $context->disconnect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

    var_dump(sprintf($status, $context->isConnected() ? 'Connected' : 'Disconnected'));

    // Reconnectar

    try {
        $context->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

    var_dump(sprintf($status, $context->isConnected() ? 'Connected' : 'Disconnected'));
}

<?php
require_once './functions.php';
require_once './autoload.php';
require_once "./i18n.php";

$lang = filter_input(INPUT_GET, 'language') ?? 'en';
$country = filter_input(INPUT_GET, 'country') ?? 'US';
$i18n = new i18n("", $lang, $country); // @phpstan-ignore-line

define('PROJECT_PATH', __DIR__);
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

if (!load_env_file(ROOT_PATH . '/.env')) {
    throw new Exception($i18n->getLabel("env_not_found")); // @phpstan-ignore-line
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Engines</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/index.css" rel="stylesheet">
</head>

<body>
    <div class="container py-5">
        <h2 class="text-center mb-4">Database Engines</h2>
        <ul class="nav nav-tabs" id="engineTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="native-tab" data-bs-toggle="tab" data-bs-target="#native"
                    type="button" role="tab">Native</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pdo-tab" data-bs-toggle="tab" data-bs-target="#pdo" type="button"
                    role="tab">PDO</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="odbc-tab" data-bs-toggle="tab" data-bs-target="#odbc" type="button"
                    role="tab">ODBC</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pdo-odbc-tab" data-bs-toggle="tab" data-bs-target="#pdo-odbc" type="button"
                    role="tab">PDO + ODBC</button>
            </li>
        </ul>

        <div class="tab-content p-4 border border-top-0 rounded-bottom" id="engineTabContent">

            <!-- Native Tab -->
            <div class="tab-pane fade show active" id="native" role="tabpanel">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="pill"
                            data-bs-target="#native-mysql">MySQL</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill"
                            data-bs-target="#native-postgresql">PostgreSQL</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#native-sqlserver">SQL
                            Server</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#native-oracle">Oracle</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill"
                            data-bs-target="#native-firebird">Firebird</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#native-sqlite">SQLite</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <?php
                    Autoloader::loadFromArray([
                        ['path' => 'native', 'files' => ['native'], 'vars' => ['i18n' => $i18n]],
                    ]);
                    ?>
                </div>
            </div>


            <!-- PDO Tab -->
            <div class="tab-pane fade" id="pdo" role="tabpanel">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pdo-mysql">MySQL</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill"
                            data-bs-target="#pdo-postgresql">PostgreSQL</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pdo-sqlserver">SQL
                            Server</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pdo-oracle">Oracle</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pdo-firebird">Firebird</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pdo-sqlite">SQLite</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <?php
                    Autoloader::loadFromArray([
                        ['path' => 'pdo', 'files' => ['pdo'], 'vars' => ['i18n' => $i18n]],
                    ]);
                    ?>
                </div>
            </div>

            <!-- ODBC Tab -->
            <div class="tab-pane fade" id="odbc" role="tabpanel">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="pill"
                            data-bs-target="#odbc-mysql">MySQL</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill"
                            data-bs-target="#odbc-postgresql">PostgreSQL</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#odbc-sqlserver">SQL
                            Server</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#odbc-oracle">Oracle</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#odbc-firebird">Firebird</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#odbc-sqlite">SQLite</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#odbc-access">Access</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <?php
                    Autoloader::loadFromArray([
                        ['path' => 'odbc', 'files' => ['odbc'], 'vars' => ['i18n' => $i18n]],
                    ]);
                    ?>
                </div>
            </div>

            <!-- PDO + ODBC Tab -->
            <div class="tab-pane fade" id="pdo-odbc" role="tabpanel">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="pill"
                            data-bs-target="#pdo-odbc-mysql">MySQL</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill"
                            data-bs-target="#pdo-odbc-postgresql">PostgreSQL</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pdo-odbc-sqlserver">SQL
                            Server</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pdo-odbc-oracle">Oracle</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill"
                            data-bs-target="#pdo-odbc-firebird">Firebird</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pdo-odbc-sqlite">SQLite</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pdo-odbc-access">Access</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <?php
                    Autoloader::loadFromArray([
                        ['path' => 'pdo_odbc', 'files' => ['pdo_odbc'], 'vars' => ['i18n' => $i18n]]
                    ]);
                    ?>
                </div>
            </div>

        </div>

    </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

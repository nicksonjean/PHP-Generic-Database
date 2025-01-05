<?php
require_once './functions.php';
require_once './autoload.php';
require_once "./i18n.php";

$lang = filter_input(INPUT_GET, 'language') ?? 'en';
$country = filter_input(INPUT_GET, 'country') ?? 'US';
$i18n = new i18n("", $lang, $country);

define('PROJECT_PATH', __DIR__);
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

if (!load_env_file(ROOT_PATH . '/.env')) {
    throw new Exception($i18n->getLabel("env_not_found"));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $i18n->getLabel("title"); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/index.css" rel="stylesheet">
</head>

<body>
    <div class="container py-5">
        <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
            <symbol id="check-circle-fill" viewBox="0 0 16 16">
                <path
                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
            </symbol>
            <symbol id="info-fill" viewBox="0 0 16 16">
                <path
                    d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" />
            </symbol>
            <symbol id="exclamation-triangle-fill" viewBox="0 0 16 16">
                <path
                    d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
            </symbol>
        </svg>

        <h2 class="text-center mb-4"><?php echo $i18n->getLabel("title"); ?></h2>
        <ul class="nav nav-tabs" id="engineTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="native-tab" data-bs-toggle="tab" data-bs-target="#native"
                    type="button" role="tab"><?php echo $i18n->getLabel("native"); ?></button>
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
<?php
require_once './functions.php';
require_once './autoload.php';
require_once "./i18n.php";

$lang = filter_input(INPUT_GET, 'language') ?? 'en';
$country = filter_input(INPUT_GET, 'country') ?? 'US';
$debug = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);

$i18n = new i18n("", $lang, $country);

define('PROJECT_PATH', __DIR__);
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

if (!load_env_file(ROOT_PATH . '/.env')) {
    throw new Exception($i18n->getLabel("env_not_found"));
}

$isEnglish = $lang === 'en';
$translateText = $isEnglish ? 'Traduzir para Português' : 'Translate to English';
$targetLanguage = $isEnglish ? 'pt' : 'en';
$targetCountry = $isEnglish ? 'BR' : 'US';
$targetFlag = strtolower($targetCountry);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $i18n->getLabel("title"); ?></title>
    <link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon" />
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

        <div class="d-flex justify-content-between align-items-center border-bottom">

            <ul class="nav nav-tabs" id="engineTabs" role="tablist" style="margin-bottom: -1px;">
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
                    <button class="nav-link" id="pdo-odbc-tab" data-bs-toggle="tab" data-bs-target="#pdo-odbc"
                        type="button" role="tab">PDO + ODBC</button>
                </li>
            </ul>

            <div class="dropdown ms-3">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-gear" viewBox="0 0 16 16">
                        <path
                            d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z" />
                        <path
                            d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z" />
                    </svg>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item"
                            href="<?php echo buildUrl(['language' => $targetLanguage, 'country' => $targetCountry]); ?>">
                            <img src="https://flagcdn.com/24x18/<?php echo $targetFlag; ?>.png"
                                alt="<?php echo $targetCountry; ?>" width="24" height="18" class="me-2">
                            <?php echo $translateText; ?>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item <?php echo $debug ? 'active' : ''; ?>"
                            href="<?php echo buildUrl(['debug' => !$debug]); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-bug me-2" viewBox="0 0 16 16">
                                <path
                                    d="M4.355.522a.5.5 0 0 1 .623.333l.291.956A4.979 4.979 0 0 1 8 1c1.007 0 1.946.298 2.731.811l.29-.956a.5.5 0 1 1 .957.29l-.41 1.352A4.985 4.985 0 0 1 13 6h.5a.5.5 0 0 0 .5-.5V5a.5.5 0 0 1 1 0v.5A1.5 1.5 0 0 1 13.5 7H13v1h1.5a.5.5 0 0 1 0 1H13v1h.5a1.5 1.5 0 0 1 1.5 1.5v.5a.5.5 0 1 1-1 0v-.5a.5.5 0 0 0-.5-.5H13a5 5 0 0 1-10 0h-.5a.5.5 0 0 0-.5.5v.5a.5.5 0 1 1-1 0v-.5A1.5 1.5 0 0 1 2.5 10H3V9H1.5a.5.5 0 0 1 0-1H3V7h-.5A1.5 1.5 0 0 1 1 5.5V5a.5.5 0 0 1 1 0v.5a.5.5 0 0 0 .5.5H3c0-1.364.547-2.601 1.432-3.503l-.41-1.352a.5.5 0 0 1 .333-.623zM4 7v4a4 4 0 0 0 3.5 3.97V7H4zm4.5 0v7.97A4 4 0 0 0 12 11V7H8.5zM12 6a3.989 3.989 0 0 0-1.334-2.982A3.983 3.983 0 0 0 8 2a3.983 3.983 0 0 0-2.667 1.018A3.989 3.989 0 0 0 4 6h8z" />
                            </svg>
                            <?php echo $i18n->getLabel("debug"); ?>
                        </a>
                    </li>
                </ul>
            </div>

        </div>

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

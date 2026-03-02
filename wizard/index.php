<?php

/**
 * Connection Wizard - Professional interface with i18n and templates
 */
require_once __DIR__ . '/includes/Functions.php';
require_once __DIR__ . '/includes/Autoload.php';
require_once __DIR__ . '/includes/Translator.php';
require_once __DIR__ . '/includes/Template.php';

$lang = filter_input(INPUT_GET, 'lang') ?? 'en';
$lang = in_array($lang, ['en', 'pt']) ? $lang : 'en';

define('PROJECT_PATH', dirname(__DIR__));
define('ROOT_PATH', dirname(__DIR__));

// ── Wizard constants ───────────────────────────────────────────────────────────
// WIZARD_DATA_DIR and WIZARD_SETTINGS_FILE are defined explicitly so that static
// analysers can resolve them. All other constants are derived from settings.toml.
define('WIZARD_DATA_DIR', __DIR__ . '/data');
define('WIZARD_SETTINGS_FILE', WIZARD_DATA_DIR . '/settings.toml');

// Read TOML once — raw values stored in $_wizSettings for $initialConfig,
// constants derived by prepending WIZARD_DATA_DIR to path values (starting with /).
$_wizSettings = read_toml_file(WIZARD_SETTINGS_FILE);
foreach ($_wizSettings as $_wizKey => $_wizVal) {
    define(
        'WIZARD_' . strtoupper($_wizKey),
        is_string($_wizVal) && str_starts_with($_wizVal, '/')
            ? WIZARD_DATA_DIR . $_wizVal
            : $_wizVal
    );
}
unset($_wizKey, $_wizVal);
// ── End Wizard constants ──────────────────────────────────────────────────────

load_env_file(ROOT_PATH . '/.env');

// Raw TOML values (pagination + paths) — consumed by the Settings modal.
$initialConfig = ['active_connection' => null, 'connections' => [], 'settings' => $_wizSettings];

if (file_exists(WIZARD_ACTIVE_PROFILE_FILE)) {
    $config = json_decode(file_get_contents(WIZARD_ACTIVE_PROFILE_FILE), true);
    $initialConfig['active_connection'] = $config['active_connection'] ?? null;
}

if (file_exists(WIZARD_PROFILES_FILE)) {
    $connections = json_decode(file_get_contents(WIZARD_PROFILES_FILE), true);
    $initialConfig['connections'] = is_array($connections) ? $connections : [];
}

$t = new \Translator($lang);
$template = new \Template(__DIR__ . '/templates');
echo $template->assign('t', $t)->assign('initialConfig', $initialConfig)->render('index.php');

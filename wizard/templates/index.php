<?php

/** @var \Translator $t */
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($t->getLocaleForHtml()) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t->t('wizard_title')) ?> - PHP Generic Database</title>
    <link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/wizard.css" rel="stylesheet">
    <script>
        window.WIZARD_INITIAL_CONFIG = <?= json_encode($initialConfig ?? ['active_connection' => null, 'connections' => []]) ?>;
    </script>
</head>

<body>
    <div id="wizard-loading-overlay" class="wizard-loading-overlay" aria-live="polite" aria-busy="true">
        <div class="wizard-loading-spinner">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden"><?= htmlspecialchars($t->t('loading')) ?></span></div>
            <p class="mt-2 mb-0 text-muted small"><?= htmlspecialchars($t->t('loading_config')) ?></p>
        </div>
    </div>
    <div class="wizard-container">
        <nav aria-label="breadcrumb" class="connection-breadcrumb mb-3" id="connection-breadcrumb">
            <div class="d-flex align-items-center justify-content-between py-2 px-3 rounded bg-light border">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active d-flex align-items-center gap-2" aria-current="page">
                        <i class="bi bi-plug-fill text-muted"></i>
                        <span id="breadcrumb-prefix"><?= htmlspecialchars($t->t('active_connection')) ?>:</span>
                        <span id="breadcrumb-active-label"><?= htmlspecialchars($t->t('no_active_connection')) ?></span>
                    </li>
                </ol>
                <button type="button" class="btn btn-outline-danger btn-sm d-none" id="btn-clear-connection" title="<?= htmlspecialchars($t->t('clear_connection')) ?>">
                    <i class="bi bi-trash"></i> <?= htmlspecialchars($t->t('clear_connection')) ?>
                </button>
            </div>
        </nav>
        <div class="wizard-header">
            <div class="wizard-header-content">
                <div class="wizard-header-icon" aria-hidden="true">
                    <i class="bi bi-database-gear"></i>
                </div>
                <div class="wizard-header-text">
                    <h1 class="wizard-title"><?= htmlspecialchars($t->t('wizard_title')) ?></h1>
                    <p class="wizard-subtitle"><?= htmlspecialchars($t->t('wizard_subtitle')) ?></p>
                </div>
            </div>
            <div class="dropdown wizard-header-options">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuOptions" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear"></i> <?= htmlspecialchars($t->t('options')) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuOptions">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 w-100" id="btn-manage-connections">
                            <i class="bi bi-collection"></i> <?= htmlspecialchars($t->t('manage_connections')) ?>
                        </button>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 w-100" id="btn-settings">
                            <i class="bi bi-sliders"></i> <?= htmlspecialchars($t->t('settings')) ?>
                        </button>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <?php $langUrl = '?lang=' . ($t->getLocale() === 'en' ? 'pt' : 'en');
                    if (isset($_GET['debug']) && $_GET['debug'] === '1') $langUrl .= '&debug=1'; ?>
                    <li><a class="dropdown-item" href="<?= htmlspecialchars($langUrl) ?>">
                            <i class="bi bi-translate"></i> <?= htmlspecialchars($t->t('lang_switch')) ?>
                        </a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <label class="dropdown-item d-flex align-items-center gap-2 cursor-pointer mb-0">
                            <input type="checkbox" id="debug-mode-toggle" <?= isset($_GET['debug']) && $_GET['debug'] === '1' ? 'checked' : '' ?>>
                            <i class="bi bi-bug"></i> <?= htmlspecialchars($t->t('debug_mode')) ?>
                        </label>
                    </li>
                </ul>
            </div>
        </div>

        <div class="wizard-steps">
            <div class="step active" data-step="1">
                <div class="step-icon"><i class="bi bi-database-fill-gear"></i></div>
                <div class="step-label"><?= htmlspecialchars($t->t('step_connection')) ?></div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="2">
                <div class="step-icon"><i class="bi bi-terminal-fill"></i></div>
                <div class="step-label"><?= htmlspecialchars($t->t('step_query')) ?></div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="3">
                <div class="step-icon"><i class="bi bi-tools"></i></div>
                <div class="step-label"><?= htmlspecialchars($t->t('step_builder')) ?></div>
            </div>
        </div>

        <div id="wizard-step-1">
            <div class="mode-selector mb-4">
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="wizard_mode" id="mode_preset" value="preset" checked>
                    <label class="btn btn-outline-primary" for="mode_preset">
                        <i class="bi bi-plug"></i> <?= htmlspecialchars($t->t('mode_preset')) ?>
                    </label>
                    <input type="radio" class="btn-check" name="wizard_mode" id="mode_new" value="new">
                    <label class="btn btn-outline-primary" for="mode_new">
                        <i class="bi bi-plus-circle"></i> <?= htmlspecialchars($t->t('mode_new')) ?>
                    </label>
                </div>
            </div>

            <div class="wizard-panels row g-4">
                <div class="col-12 wizard-panel d-block" id="panel-preset">
                    <div class="wizard-panel-card card h-100 shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-plug"></i> <?= htmlspecialchars($t->t('panel_preset_title')) ?></h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small"><?= htmlspecialchars($t->t('panel_preset_desc')) ?></p>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('module')) ?></label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="module_preset" id="module_chainable" value="Chainable" checked>
                                            <label class="form-check-label" for="module_chainable"><?= htmlspecialchars($t->t('chainable')) ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="module_preset" id="module_fluent" value="Fluent">
                                            <label class="form-check-label" for="module_fluent"><?= htmlspecialchars($t->t('fluent')) ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="module_preset" id="module_staticargs" value="StaticArgs">
                                            <label class="form-check-label" for="module_staticargs"><?= htmlspecialchars($t->t('staticargs')) ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="module_preset" id="module_staticarray" value="StaticArray">
                                            <label class="form-check-label" for="module_staticarray"><?= htmlspecialchars($t->t('staticarray')) ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12" id="engine-section-preset" style="display: none;">
                                    <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('engine')) ?></label>
                                    <div class="engine-cards">
                                        <div class="engine-card" data-engine="native">
                                            <i class="bi bi-cpu"></i>
                                            <span><?= htmlspecialchars($t->t('native')) ?></span>
                                            <small><?= htmlspecialchars($t->t('native_drivers')) ?></small>
                                        </div>
                                        <div class="engine-card" data-engine="pdo">
                                            <i class="bi bi-database"></i>
                                            <span><?= htmlspecialchars($t->t('pdo')) ?></span>
                                            <small><?= htmlspecialchars($t->t('pdo_drivers')) ?></small>
                                        </div>
                                        <div class="engine-card" data-engine="odbc">
                                            <i class="bi bi-hdd-network"></i>
                                            <span><?= htmlspecialchars($t->t('odbc')) ?></span>
                                            <small><?= htmlspecialchars($t->t('odbc_extra')) ?></small>
                                        </div>
                                        <div class="engine-card" data-engine="pdo_odbc">
                                            <i class="bi bi-diagram-3"></i>
                                            <span><?= htmlspecialchars($t->t('pdo_odbc')) ?></span>
                                            <small><?= htmlspecialchars($t->t('odbc_same')) ?></small>
                                        </div>
                                        <div class="engine-card" data-engine="flat_files">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span><?= htmlspecialchars($t->t('flat_files')) ?></span>
                                            <small><?= htmlspecialchars($t->t('flat_files_drivers')) ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12" id="driver-section-preset" style="display: none;">
                                    <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('driver')) ?></label>
                                    <div class="driver-cards" id="driver-cards"></div>
                                </div>
                                <div class="col-12" id="instance-type-section-preset" style="display: none;">
                                    <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('instance_type')) ?></label>
                                    <div class="d-flex gap-3 instance-type-options">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="mechanism_preset" id="mechanism_strategy" value="strategy">
                                            <label class="form-check-label" for="mechanism_strategy"><?= htmlspecialchars($t->t('instance_strategy')) ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="mechanism_preset" id="mechanism_specific" value="specific" checked>
                                            <label class="form-check-label" for="mechanism_specific"><?= htmlspecialchars($t->t('instance_specific')) ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex flex-wrap gap-2 justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-preset">
                                        <i class="bi bi-arrow-counterclockwise"></i> <?= htmlspecialchars($t->t('reset')) ?>
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-test-preset">
                                            <i class="bi bi-plug-fill"></i> <?= htmlspecialchars($t->t('test')) ?>
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm" id="btn-confirm-preset">
                                            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($t->t('confirm')) ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 wizard-panel d-none" id="panel-new">
                    <div class="wizard-panel-card card h-100 shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0" id="panel-new-title"><i class="bi bi-plus-circle"></i> <?= htmlspecialchars($t->t('panel_new_title')) ?></h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small"><?= htmlspecialchars($t->t('panel_new_desc')) ?></p>
                            <form id="connection-form">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('module')) ?></label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="module" id="module_chainable_new" value="Chainable" checked>
                                                <label class="form-check-label" for="module_chainable_new"><?= htmlspecialchars($t->t('chainable')) ?></label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="module" id="module_fluent_new" value="Fluent">
                                                <label class="form-check-label" for="module_fluent_new"><?= htmlspecialchars($t->t('fluent')) ?></label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="module" id="module_staticargs_new" value="StaticArgs">
                                                <label class="form-check-label" for="module_staticargs_new"><?= htmlspecialchars($t->t('staticargs')) ?></label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="module" id="module_staticarray_new" value="StaticArray">
                                                <label class="form-check-label" for="module_staticarray_new"><?= htmlspecialchars($t->t('staticarray')) ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12" id="engine-section-new" style="display: none;">
                                        <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('engine')) ?></label>
                                        <div class="engine-cards" id="engine-cards-new">
                                            <div class="engine-card" data-engine="native">
                                                <i class="bi bi-cpu"></i>
                                                <span><?= htmlspecialchars($t->t('native')) ?></span>
                                                <small><?= htmlspecialchars($t->t('native_drivers')) ?></small>
                                            </div>
                                            <div class="engine-card" data-engine="pdo">
                                                <i class="bi bi-database"></i>
                                                <span><?= htmlspecialchars($t->t('pdo')) ?></span>
                                                <small><?= htmlspecialchars($t->t('pdo_drivers')) ?></small>
                                            </div>
                                            <div class="engine-card" data-engine="odbc">
                                                <i class="bi bi-hdd-network"></i>
                                                <span><?= htmlspecialchars($t->t('odbc')) ?></span>
                                                <small><?= htmlspecialchars($t->t('odbc_extra')) ?></small>
                                            </div>
                                            <div class="engine-card" data-engine="pdo_odbc">
                                                <i class="bi bi-diagram-3"></i>
                                                <span><?= htmlspecialchars($t->t('pdo_odbc')) ?></span>
                                                <small><?= htmlspecialchars($t->t('odbc_same')) ?></small>
                                            </div>
                                            <div class="engine-card" data-engine="flat_files">
                                                <i class="bi bi-file-earmark-text"></i>
                                                <span><?= htmlspecialchars($t->t('flat_files')) ?></span>
                                                <small><?= htmlspecialchars($t->t('flat_files_drivers')) ?></small>
                                            </div>
                                        </div>
                                        <input type="hidden" id="form-engine" name="engine" value="">
                                    </div>
                                    <div class="col-12" id="driver-section-new" style="display: none;">
                                        <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('driver')) ?></label>
                                        <div class="driver-cards" id="driver-cards-new"></div>
                                        <input type="hidden" id="form-driver" name="driver" value="">
                                    </div>
                                    <div class="col-12" id="instance-type-section-new" style="display: none;">
                                        <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('instance_type')) ?></label>
                                        <div class="d-flex gap-3 instance-type-options">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="instance_type" id="instance_strategy_new" value="strategy">
                                                <label class="form-check-label" for="instance_strategy_new"><?= htmlspecialchars($t->t('instance_strategy')) ?></label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="instance_type" id="instance_specific_new" value="specific" checked>
                                                <label class="form-check-label" for="instance_specific_new"><?= htmlspecialchars($t->t('instance_specific')) ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12" id="connection-fields-section" style="display: none;">
                                        <div class="row g-3" id="connection-fields"></div>
                                    </div>
                                    <div class="col-12" id="connection-name-options-section" style="display: none;">
                                        <label class="form-label fw-semibold">
                                            <?= htmlspecialchars($t->t('connection_options')) ?>
                                            <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" title="<?= htmlspecialchars($t->t('connection_options_hint')) ?>"></i>
                                        </label>
                                        <textarea class="form-control font-monospace" id="connection-options" name="options" rows="4"></textarea>
                                    </div>
                                    <div class="col-12 d-flex flex-wrap gap-2 justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-new">
                                            <i class="bi bi-arrow-counterclockwise"></i> <?= htmlspecialchars($t->t('reset')) ?>
                                        </button>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-test-connection">
                                                <i class="bi bi-plug-fill"></i> <?= htmlspecialchars($t->t('test')) ?>
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm" id="btn-confirm-connection">
                                                <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($t->t('confirm')) ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end wizard-step-1 -->

        <div id="wizard-step-2" class="d-none">
            <!-- No active connection warning -->
            <div id="query-no-connection" class="alert alert-warning d-flex align-items-center gap-2 mb-4 d-none">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= htmlspecialchars($t->t('query_no_connection')) ?></span>
            </div>

            <!-- Query Form Card -->
            <div class="card shadow mb-4" id="query-form-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-terminal"></i> <?= htmlspecialchars($t->t('query_tab_title')) ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3"><?= htmlspecialchars($t->t('query_tab_desc')) ?></p>
                    <div class="row g-3">
                        <!-- Query Type -->
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('query_type')) ?></label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="query_type" id="query_type_raw" value="raw" checked>
                                    <label class="form-check-label" for="query_type_raw"><?= htmlspecialchars($t->t('query_raw')) ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="query_type" id="query_type_prepared" value="prepared">
                                    <label class="form-check-label" for="query_type_prepared"><?= htmlspecialchars($t->t('query_prepared')) ?></label>
                                </div>
                            </div>
                        </div>
                        <!-- SQL Textarea -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="query-sql"><?= htmlspecialchars($t->t('query_sql')) ?></label>
                            <textarea class="form-control font-monospace" id="query-sql" rows="6" placeholder="<?= htmlspecialchars($t->t('query_sql_placeholder')) ?>"></textarea>
                        </div>
                        <!-- Placeholder type (prepared only) -->
                        <div class="col-12" id="placeholder-type-section" style="display:none;">
                            <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('query_placeholder_type')) ?></label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="placeholder_type" id="ph_question" value="question" checked>
                                    <label class="form-check-label" for="ph_question"><?= htmlspecialchars($t->t('query_placeholder_question')) ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="placeholder_type" id="ph_numbered" value="numbered">
                                    <label class="form-check-label" for="ph_numbered"><?= htmlspecialchars($t->t('query_placeholder_numbered')) ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="placeholder_type" id="ph_named" value="named">
                                    <label class="form-check-label" for="ph_named"><?= htmlspecialchars($t->t('query_placeholder_named')) ?></label>
                                </div>
                            </div>
                        </div>
                        <!-- Dynamic parameter fields (prepared only) -->
                        <div class="col-12" id="query-params-section" style="display:none;">
                            <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('query_params')) ?></label>
                            <div id="query-param-fields" class="row g-2"></div>
                        </div>
                        <!-- Action buttons -->
                        <div class="col-12 d-flex flex-wrap gap-2 justify-content-between">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-query-reset">
                                    <i class="bi bi-arrow-counterclockwise"></i> <?= htmlspecialchars($t->t('form_reset')) ?>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-query-save-draft">
                                    <i class="bi bi-pencil-square"></i> <?= htmlspecialchars($t->t('save_draft')) ?>
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-query-save">
                                    <i class="bi bi-floppy"></i> <?= htmlspecialchars($t->t('query_save')) ?>
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" id="btn-query-execute">
                                    <i class="bi bi-play-fill"></i> <?= htmlspecialchars($t->t('query_execute')) ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results card (hidden until query runs) -->
            <div class="card shadow mb-4 d-none" id="query-results-card">
                <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-table"></i> <?= htmlspecialchars($t->t('query_results')) ?></h5>
                    <span id="query-results-meta" class="badge bg-light text-dark"></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" id="query-results-table-wrap" style="max-height:400px;overflow:auto;"></div>
                </div>
            </div>

            <!-- Saved queries + History side by side -->
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-bookmark-star"></i> <?= htmlspecialchars($t->t('query_saved_list')) ?></h5>
                        </div>
                        <div class="card-body p-0">
                            <div id="saved-queries-list" class="list-group list-group-flush"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> <?= htmlspecialchars($t->t('query_history')) ?></h5>
                        </div>
                        <div class="card-body p-0">
                            <div id="query-history-list" class="list-group list-group-flush"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end wizard-step-2 -->

        <div id="wizard-step-3" class="d-none">
            <!-- No active connection warning -->
            <div id="builder-no-connection" class="alert alert-warning d-flex align-items-center gap-2 mb-4 d-none">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= htmlspecialchars($t->t('builder_no_connection')) ?></span>
            </div>

            <!-- QB Form Card -->
            <div class="card shadow mb-4" id="qb-form-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-tools"></i> <?= htmlspecialchars($t->t('builder_tab_title')) ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3"><?= htmlspecialchars($t->t('builder_tab_desc')) ?></p>
                    <div class="row g-3">
                        <!-- SQL Input -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="qb-sql"><?= htmlspecialchars($t->t('builder_sql_label')) ?></label>
                            <textarea class="form-control font-monospace" id="qb-sql" rows="5" placeholder="<?= htmlspecialchars($t->t('builder_sql_placeholder')) ?>"></textarea>
                        </div>
                        <!-- QB Code Output -->
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= htmlspecialchars($t->t('builder_output_label')) ?></label>
                            <div class="position-relative">
                                <pre class="bg-light border rounded p-3 mb-0" style="min-height:80px;max-height:300px;overflow:auto;"><code id="qb-output" class="font-monospace small text-break">--</code></pre>
                                <span id="qb-converting" class="position-absolute top-0 end-0 m-2 badge bg-secondary d-none"><?= htmlspecialchars($t->t('builder_converting')) ?></span>
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="col-12 d-flex flex-wrap gap-2 justify-content-between">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-qb-reset">
                                    <i class="bi bi-arrow-counterclockwise"></i> <?= htmlspecialchars($t->t('form_reset')) ?>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-qb-save-draft">
                                    <i class="bi bi-pencil-square"></i> <?= htmlspecialchars($t->t('save_draft')) ?>
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-qb-save">
                                    <i class="bi bi-floppy"></i> <?= htmlspecialchars($t->t('builder_save')) ?>
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" id="btn-qb-execute">
                                    <i class="bi bi-play-fill"></i> <?= htmlspecialchars($t->t('builder_execute')) ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results card (hidden until executed) -->
            <div class="card shadow mb-4 d-none" id="qb-results-card">
                <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-table"></i> <?= htmlspecialchars($t->t('builder_results')) ?></h5>
                    <span id="qb-results-meta" class="badge bg-light text-dark"></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" id="qb-results-table-wrap" style="max-height:400px;overflow:auto;"></div>
                </div>
            </div>

            <!-- Saved Builders + Builder History side by side -->
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-bookmark-star"></i> <?= htmlspecialchars($t->t('builder_saved_list')) ?></h5>
                        </div>
                        <div class="card-body p-0">
                            <div id="saved-builders-list" class="list-group list-group-flush"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> <?= htmlspecialchars($t->t('builder_history')) ?></h5>
                        </div>
                        <div class="card-body p-0">
                            <div id="builder-history-list" class="list-group list-group-flush"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end wizard-step-3 -->

        <div id="alert-container" class="mt-3"></div>
    </div>

    <!-- Manage Connections Modal -->
    <div class="modal fade" id="manageConnectionsModal" tabindex="-1" aria-labelledby="manageConnectionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageConnectionsModalLabel">
                        <i class="bi bi-collection"></i> <?= htmlspecialchars($t->t('manage_connections')) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0" id="connections-table">
                            <thead>
                                <tr>
                                    <th><?= htmlspecialchars($t->t('connection_name')) ?></th>
                                    <th><?= htmlspecialchars($t->t('type')) ?></th>
                                    <th><?= htmlspecialchars($t->t('module')) ?></th>
                                    <th><?= htmlspecialchars($t->t('engine')) ?></th>
                                    <th><?= htmlspecialchars($t->t('driver')) ?></th>
                                    <th class="text-center" style="width: 180px;"><?= htmlspecialchars($t->t('actions')) ?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clear Active Connection Confirmation Modal -->
    <div class="modal fade" id="clearConfirmModal" tabindex="-1" aria-labelledby="clearConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clearConfirmModalLabel">
                        <i class="bi bi-trash text-danger"></i> <?= htmlspecialchars($t->t('clear_confirm_title')) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0"><?= htmlspecialchars($t->t('clear_confirm')) ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($t->t('cancel')) ?></button>
                    <button type="button" class="btn btn-danger" id="btn-clear-confirm">
                        <i class="bi bi-trash"></i> <?= htmlspecialchars($t->t('clear_connection')) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">
                        <i class="bi bi-exclamation-triangle text-warning"></i> <?= htmlspecialchars($t->t('delete')) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0"><?= htmlspecialchars($t->t('delete_confirm')) ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($t->t('cancel')) ?></button>
                    <button type="button" class="btn btn-danger" id="btn-delete-confirm">
                        <i class="bi bi-trash"></i> <?= htmlspecialchars($t->t('delete')) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Connection Name Modal -->
    <div class="modal fade" id="connectionNameModal" tabindex="-1" aria-labelledby="connectionNameModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="connectionNameModalLabel">
                        <i class="bi bi-tag"></i> <?= htmlspecialchars($t->t('connection_name')) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3"><?= htmlspecialchars($t->t('connection_name_modal_desc')) ?></p>
                    <input type="text" class="form-control form-control-lg" id="modal-connection-name" placeholder="<?= htmlspecialchars($t->t('name_placeholder')) ?>" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($t->t('cancel')) ?></button>
                    <button type="button" class="btn btn-primary" id="btn-save-connection">
                        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($t->t('save')) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Connection Config Modal -->
    <div class="modal fade" id="activeConfigModal" tabindex="-1" aria-labelledby="activeConfigModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activeConfigModalLabel">
                        <i class="bi bi-file-earmark-code"></i> <?= htmlspecialchars($t->t('active_connection')) ?> - active_profile.json
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="active-config-content" class="bg-dark text-light p-3 rounded overflow-auto mb-0" style="max-height: 60vh; font-size: 0.8rem;"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug Modal -->
    <div class="modal fade" id="debugModal" tabindex="-1" aria-labelledby="debugModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="debugModalLabel"><i class="bi bi-bug"></i> Debug - Connection Object</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="debug-modal-content" class="bg-dark text-light p-3 rounded overflow-auto" style="max-height: 70vh; font-size: 0.8rem;"></pre>
                </div>
            </div>
        </div>
    </div>
    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="settingsModalLabel">
                        <i class="bi bi-sliders"></i> <?= htmlspecialchars($t->t('settings')) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3"><?= htmlspecialchars($t->t('settings_desc')) ?></p>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0" id="settings-table">
                            <thead>
                                <tr>
                                    <th><?= htmlspecialchars($t->t('setting_name')) ?></th>
                                    <th style="width: 120px;"><?= htmlspecialchars($t->t('setting_value')) ?></th>
                                    <th class="text-center" style="width: 90px;"><?= htmlspecialchars($t->t('actions')) ?></th>
                                </tr>
                            </thead>
                            <tbody id="settings-table-body"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($t->t('cancel')) ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Setting Modal -->
    <div class="modal fade" id="editSettingModal" tabindex="-1" aria-labelledby="editSettingModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSettingModalLabel">
                        <i class="bi bi-pencil-square"></i> <span id="edit-setting-label"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2" id="edit-setting-description"></p>
                    <!-- Number input: pagination settings -->
                    <input type="number" class="form-control" id="edit-setting-value-number" min="1" max="100" autocomplete="off">
                    <div class="form-text text-muted mt-1" id="edit-hint-number"><?= htmlspecialchars($t->t('setting_edit_hint')) ?></div>
                    <!-- Text input: path settings -->
                    <input type="text" class="form-control font-monospace d-none" id="edit-setting-value-text" autocomplete="off" spellcheck="false">
                    <div class="form-text text-muted mt-1 d-none" id="edit-hint-text"><?= htmlspecialchars($t->t('setting_edit_path_hint')) ?></div>
                    <input type="hidden" id="edit-setting-key">
                    <input type="hidden" id="edit-setting-type">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"><?= htmlspecialchars($t->t('cancel')) ?></button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-save-setting">
                        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($t->t('save')) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Draft Modal -->
    <div class="modal fade" id="saveDraftModal" tabindex="-1" aria-labelledby="saveDraftModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="saveDraftModalLabel">
                        <i class="bi bi-pencil-square"></i> <?= htmlspecialchars($t->t('save_draft')) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3"><?= htmlspecialchars($t->t('draft_alias')) ?></p>
                    <input type="text" class="form-control form-control-lg" id="modal-draft-alias" placeholder="<?= htmlspecialchars($t->t('draft_alias_placeholder')) ?>" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($t->t('cancel')) ?></button>
                    <button type="button" class="btn btn-primary" id="btn-save-draft-confirm">
                        <i class="bi bi-pencil-square"></i> <?= htmlspecialchars($t->t('save')) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.WIZARD_LOCALE = '<?= addslashes($t->getLocale()) ?>';
        window.WIZARD_LABELS = <?= json_encode([
                                    'confirm_select_first' => $t->t('confirm_select_first'),
                                    'connection_confirmed' => $t->t('connection_confirmed'),
                                    'fill_required' => $t->t('fill_required'),
                                    'connection_created' => $t->t('connection_created'),
                                    'error_test' => $t->t('error_test'),
                                    'error_create' => $t->t('error_create'),
                                    'error_load_fields' => $t->t('error_load_fields'),
                                    'error_network' => $t->t('error_network'),
                                    'select' => $t->t('select'),
                                    'select_module_first' => $t->t('select_module_first'),
                                    'select_engine_first' => $t->t('select_engine_first'),
                                    'test' => $t->t('test'),
                                    'confirm' => $t->t('confirm'),
                                    'testing' => $t->t('testing'),
                                    'creating' => $t->t('creating'),
                                    'field_host' => $t->t('field_host'),
                                    'field_port' => $t->t('field_port'),
                                    'field_database' => $t->t('field_database'),
                                    'field_username' => $t->t('field_username'),
                                    'field_password' => $t->t('field_password'),
                                    'field_charset' => $t->t('field_charset'),
                                    'manage_connections' => $t->t('manage_connections'),
                                    'no_active_connection' => $t->t('no_active_connection'),
                                    'active_connection' => $t->t('active_connection'),
                                    'actions' => $t->t('actions'),
                                    'make_active' => $t->t('make_active'),
                                    'edit' => $t->t('edit'),
                                    'delete' => $t->t('delete'),
                                    'panel_edit_title' => $t->t('panel_edit_title'),
                                    'delete_confirm' => $t->t('delete_confirm'),
                                    'connection_deleted' => $t->t('connection_deleted'),
                                    'connection_updated' => $t->t('connection_updated'),
                                    'panel_new_title' => $t->t('panel_new_title'),
                                    'no_connections_found' => $t->t('no_connections_found'),
                                    'type' => $t->t('type'),
                                    'clear_connection' => $t->t('clear_connection'),
                                    'clear_confirm' => $t->t('clear_confirm'),
                                    'clear_confirm_title' => $t->t('clear_confirm_title'),
                                    'connection_cleared' => $t->t('connection_cleared'),
                                    'connection_activated' => $t->t('connection_activated'),
                                    // Query tab
                                    'step_query' => $t->t('step_query'),
                                    'query_no_connection' => $t->t('query_no_connection'),
                                    'query_type' => $t->t('query_type'),
                                    'query_raw' => $t->t('query_raw'),
                                    'query_prepared' => $t->t('query_prepared'),
                                    'query_sql' => $t->t('query_sql'),
                                    'query_placeholder_type' => $t->t('query_placeholder_type'),
                                    'query_placeholder_question' => $t->t('query_placeholder_question'),
                                    'query_placeholder_numbered' => $t->t('query_placeholder_numbered'),
                                    'query_placeholder_named' => $t->t('query_placeholder_named'),
                                    'query_params' => $t->t('query_params'),
                                    'query_execute' => $t->t('query_execute'),
                                    'query_save' => $t->t('query_save'),
                                    'query_results' => $t->t('query_results'),
                                    'query_no_rows' => $t->t('query_no_rows'),
                                    'query_affected_rows' => $t->t('query_affected_rows'),
                                    'query_saved_list' => $t->t('query_saved_list'),
                                    'query_history' => $t->t('query_history'),
                                    'query_history_empty' => $t->t('query_history_empty'),
                                    'query_saved_empty' => $t->t('query_saved_empty'),
                                    'query_name' => $t->t('query_name'),
                                    'query_name_placeholder' => $t->t('query_name_placeholder'),
                                    'query_saved' => $t->t('query_saved'),
                                    'query_deleted' => $t->t('query_deleted'),
                                    'query_loaded' => $t->t('query_loaded'),
                                    'query_error' => $t->t('query_error'),
                                    'query_save_name_required' => $t->t('query_save_name_required'),
                                    'query_executing' => $t->t('query_executing'),
                                    'query_saving' => $t->t('query_saving'),
                                    'query_load' => $t->t('query_load'),
                                    'query_run_again' => $t->t('query_run_again'),
                                    'query_rows' => $t->t('query_rows'),
                                    'query_success' => $t->t('query_success'),
                                    'form_reset' => $t->t('form_reset'),
                                    // Draft
                                    'save_draft' => $t->t('save_draft'),
                                    'draft_alias' => $t->t('draft_alias'),
                                    'draft_alias_placeholder' => $t->t('draft_alias_placeholder'),
                                    'draft_saved' => $t->t('draft_saved'),
                                    'draft_deleted' => $t->t('draft_deleted'),
                                    'draft_badge' => $t->t('draft_badge'),
                                    'draft_loaded' => $t->t('draft_loaded'),
                                    // Builder tab
                                    'step_builder' => $t->t('step_builder'),
                                    'builder_tab_title' => $t->t('builder_tab_title'),
                                    'builder_tab_desc' => $t->t('builder_tab_desc'),
                                    'builder_no_connection' => $t->t('builder_no_connection'),
                                    'builder_sql_label' => $t->t('builder_sql_label'),
                                    'builder_sql_placeholder' => $t->t('builder_sql_placeholder'),
                                    'builder_output_label' => $t->t('builder_output_label'),
                                    'builder_save' => $t->t('builder_save'),
                                    'builder_execute' => $t->t('builder_execute'),
                                    'builder_saved_list' => $t->t('builder_saved_list'),
                                    'builder_history' => $t->t('builder_history'),
                                    'builder_saved_empty' => $t->t('builder_saved_empty'),
                                    'builder_history_empty' => $t->t('builder_history_empty'),
                                    'builder_saved' => $t->t('builder_saved'),
                                    'builder_deleted' => $t->t('builder_deleted'),
                                    'builder_executed' => $t->t('builder_executed'),
                                    'builder_converting' => $t->t('builder_converting'),
                                    'builder_no_sql' => $t->t('builder_no_sql'),
                                    'builder_error' => $t->t('builder_error'),
                                    'builder_loaded' => $t->t('builder_loaded'),
                                    'builder_load' => $t->t('builder_load'),
                                    'builder_rows' => $t->t('builder_rows'),
                                    'builder_no_rows' => $t->t('builder_no_rows'),
                                    'builder_affected_rows' => $t->t('builder_affected_rows'),
                                    'builder_results' => $t->t('builder_results'),
                                    'builder_executing' => $t->t('builder_executing'),
                                    'builder_saving' => $t->t('builder_saving'),
                                    // Settings
                                    'settings' => $t->t('settings'),
                                    'settings_desc' => $t->t('settings_desc'),
                                    'setting_name' => $t->t('setting_name'),
                                    'setting_value' => $t->t('setting_value'),
                                    'setting_edit_title' => $t->t('setting_edit_title'),
                                    'setting_edit_hint' => $t->t('setting_edit_hint'),
                                    'settings_saved' => $t->t('settings_saved'),
                                    'settings_error' => $t->t('settings_error'),
                                    'queries_page_size' => $t->t('queries_page_size'),
                                    'history_page_size' => $t->t('history_page_size'),
                                    'connections_page_size' => $t->t('connections_page_size'),
                                    'builders_page_size' => $t->t('builders_page_size'),
                                    'builder_history_page_size' => $t->t('builder_history_page_size'),
                                    // Path settings
                                    'profiles_dir' => $t->t('profiles_dir'),
                                    'active_profile_file' => $t->t('active_profile_file'),
                                    'profiles_file' => $t->t('profiles_file'),
                                    'queries_file' => $t->t('queries_file'),
                                    'query_history_file' => $t->t('query_history_file'),
                                    'builders_file' => $t->t('builders_file'),
                                    'builder_history_file' => $t->t('builder_history_file'),
                                    'draft_file' => $t->t('draft_file'),
                                    'setting_edit_path_hint' => $t->t('setting_edit_path_hint'),
                                    'settings_group_pagination' => $t->t('settings_group_pagination'),
                                    'settings_group_paths' => $t->t('settings_group_paths'),
                                ]) ?>;
        window.WIZARD_SETTINGS = <?= json_encode($initialConfig['settings'] ?? [
                                        'queries_page_size' => 10,
                                        'history_page_size' => 20,
                                        'connections_page_size' => 10,
                                        'builders_page_size' => 10,
                                        'builder_history_page_size' => 20,
                                    ]) ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/wizard.js"></script>
</body>

</html>

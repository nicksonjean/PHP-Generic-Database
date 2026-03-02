/**
 * Wizard - Assistente de Conexão
 * Lógica dinâmica para configuração de conexões
 */

(function () {
    'use strict';

    const CONFIG = {
        engines: {
            native: { label: 'Native', drivers: ['mysql', 'pgsql', 'sqlsrv', 'oci', 'firebird', 'sqlite'] },
            pdo: { label: 'PDO', drivers: ['mysql', 'pgsql', 'sqlsrv', 'oci', 'firebird', 'sqlite'] },
            odbc: { label: 'ODBC', drivers: ['mysql', 'pgsql', 'sqlsrv', 'oci', 'firebird', 'sqlite', 'access', 'excel', 'text'] },
            pdo_odbc: { label: 'PDO + ODBC', drivers: ['mysql', 'pgsql', 'sqlsrv', 'oci', 'firebird', 'sqlite', 'access', 'excel', 'text'] },
            flat_files: { label: 'Flat Files', drivers: ['csv', 'ini', 'json', 'neon', 'xml', 'yaml'] },
        },
        driverLabels: {
            mysql: { native: 'MySQLi', default: 'MySQL' },
            pgsql: 'PostgreSQL',
            sqlsrv: 'SQL Server',
            oci: 'Oracle',
            firebird: 'Firebird',
            sqlite: 'SQLite',
            access: 'Access',
            excel: 'Excel',
            text: 'Text',
            csv: 'CSV',
            ini: 'INI',
            json: 'JSON',
            neon: 'NEON',
            xml: 'XML',
            yaml: 'YAML',
        },
        // Driver icons - Simple Icons via jsDelivr, local SVG for Firebird/Text
        driverIcons: {
            mysql: 'assets/icons/mysql.svg',
            pgsql: 'assets/icons/postgresql.svg',
            sqlsrv: 'assets/icons/microsoftsqlserver.svg',
            oci: 'assets/icons/oracle.svg',
            firebird: 'assets/icons/firebird.svg',
            sqlite: 'assets/icons/sqlite.svg',
            access: 'assets/icons/microsoftaccess.svg',
            excel: 'assets/icons/microsoftexcel.svg',
            text: 'assets/icons/text.svg',
            csv: 'assets/icons/csv.svg',
            ini: 'assets/icons/ini.svg',
            json: 'assets/icons/json.svg',
            neon: 'assets/icons/neon.svg',
            xml: 'assets/icons/xml.svg',
            yaml: 'assets/icons/yaml.svg',
        },
    };

    const L = window.WIZARD_LABELS || {};

    function getDriverLabel(driverId, engine) {
        const lbl = CONFIG.driverLabels[driverId];
        if (typeof lbl === 'object') {
            return engine === 'native' ? lbl.native : lbl.default;
        }
        return lbl || driverId;
    }

    function getEngineLabel(engineId) {
        const eng = (engineId || '').toLowerCase();
        return CONFIG.engines[eng]?.label || eng || engineId;
    }

    /** Normalize engine label/value to internal id (e.g. "Native" -> "native", "PDO + ODBC" -> "pdo_odbc") */
    function normalizeEngineId(val) {
        const v = (val || '').toLowerCase().replace(/\s+/g, '');
        const map = { native: 'native', pdo: 'pdo', odbc: 'odbc', 'pdo+odbc': 'pdo_odbc', flatfiles: 'flat_files' };
        return map[v] || v.replace(/\+/g, '_');
    }

    /** Normalize driver label/value to internal id (e.g. "MySQLi" -> "mysql", "PostgreSQL" -> "pgsql") */
    function normalizeDriverId(val, engine) {
        const v = (val || '').toLowerCase().trim();
        if (v === 'mysqli') return 'mysql';
        const map = { mysql: 'mysql', postgresql: 'pgsql', 'sql server': 'sqlsrv', oracle: 'oci', firebird: 'firebird', sqlite: 'sqlite', access: 'access', excel: 'excel', text: 'text', csv: 'csv', ini: 'ini', json: 'json', neon: 'neon', xml: 'xml', yaml: 'yaml' };
        return map[v] || v.replace(/\s+/g, '_');
    }

    /** Normalize instance type label to id (e.g. "Specific" -> "specific", "Strategy" -> "strategy") */
    function normalizeInstanceTypeId(val) {
        return (val || '').toLowerCase().trim() === 'strategy' ? 'strategy' : 'specific';
    }

    /** Format option value for PHP literal display (e.g. true, false, 5, "utf8") */
    function formatOptionValue(v) {
        if (typeof v === 'boolean') return v ? 'true' : 'false';
        if (typeof v === 'number') return String(v);
        return String(v);
    }

    let state = { mode: 'preset' };
    let statePreset = {
        module: 'Chainable',
        engine: null,
        driver: null,
        mechanism: 'specific',
    };
    let stateNew = {
        module: 'Chainable',
        engine: null,
        driver: null,
        mechanism: 'specific',
    };
    let pendingRestoreData = null;
    let editingConnectionName = null;
    let loadedConnectionName = null;

    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

    function isDebugMode() {
        const url = new URL(window.location.href);
        if (url.searchParams.get('debug') === '1') return true;
        const cb = $('#debug-mode-toggle');
        return cb && cb.checked;
    }

    function initDebugMode() {
        const cb = $('#debug-mode-toggle');
        if (cb) {
            cb.addEventListener('change', () => {
                const url = new URL(window.location.href);
                if (cb.checked) url.searchParams.set('debug', '1');
                else url.searchParams.delete('debug');
                window.history.replaceState({}, '', url);
            });
        }
    }

    function syncUrlDebugParam() {
        const url = new URL(window.location.href);
        const cb = $('#debug-mode-toggle');
        if (cb && url.searchParams.get('debug') === '1') cb.checked = true;
    }

    function showDebugModal(content) {
        const pre = $('#debug-modal-content');
        const modal = document.getElementById('debugModal');
        if (pre) pre.textContent = content;
        if (modal) {
            const m = new bootstrap.Modal(modal);
            m.show();
        }
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    /** Pagination keys — validated as integers 1–100. */
    const SETTINGS_PAGINATION_KEYS = [
        'queries_page_size',
        'history_page_size',
        'connections_page_size',
        'builders_page_size',
        'builder_history_page_size',
    ];

    /** Path keys — validated as strings starting with /. */
    const SETTINGS_PATH_KEYS_ARR = [
        'profiles_dir',
        'active_profile_file',
        'profiles_file',
        'queries_file',
        'query_history_file',
        'builders_file',
        'builder_history_file',
        'draft_file',
    ];

    const SETTINGS_PATH_KEYS = new Set(SETTINGS_PATH_KEYS_ARR);

    function openSettingsModal() {
        const modal = document.getElementById('settingsModal');
        if (!modal) return;
        renderSettingsTable();
        new bootstrap.Modal(modal).show();
    }

    function renderSettingsTable() {
        const tbody = document.getElementById('settings-table-body');
        if (!tbody) return;
        const settings = window.WIZARD_SETTINGS || {};
        tbody.innerHTML = '';

        const addSectionHeader = (label) => {
            const tr = document.createElement('tr');
            tr.className = 'table-light';
            tr.innerHTML = `<th colspan="3" class="text-uppercase small text-muted fw-semibold py-1 px-2">${escapeHtml(label)}</th>`;
            tbody.appendChild(tr);
        };

        const addRow = (key, badgeClass) => {
            const label = L[key] || key;
            const value = settings[key] ?? '—';
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="align-middle">${escapeHtml(label)}</td>
                <td class="align-middle"><span class="badge ${badgeClass} px-2 py-1 font-monospace" id="setting-val-${escapeHtml(key)}">${escapeHtml(String(value))}</span></td>
                <td class="text-center align-middle">
                    <button class="btn btn-outline-primary btn-sm" data-setting-key="${escapeHtml(key)}" title="${escapeHtml(L.edit || 'Edit')}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </td>`;
            tr.querySelector('[data-setting-key]').addEventListener('click', () => openEditSetting(key, label, value));
            tbody.appendChild(tr);
        };

        addSectionHeader(L.settings_group_pagination || 'Pagination');
        SETTINGS_PAGINATION_KEYS.forEach((key) => addRow(key, 'bg-secondary'));

        addSectionHeader(L.settings_group_paths || 'Data File Paths');
        SETTINGS_PATH_KEYS_ARR.forEach((key) => addRow(key, 'bg-info text-dark'));
    }

    function openEditSetting(key, label, currentValue) {
        const editModal = document.getElementById('editSettingModal');
        if (!editModal) return;

        document.getElementById('edit-setting-label').textContent = label;
        document.getElementById('edit-setting-description').textContent = label;
        document.getElementById('edit-setting-key').value = key;

        const isPath = SETTINGS_PATH_KEYS.has(key);
        document.getElementById('edit-setting-type').value = isPath ? 'text' : 'number';

        const numInput  = document.getElementById('edit-setting-value-number');
        const txtInput  = document.getElementById('edit-setting-value-text');
        const numHint   = document.getElementById('edit-hint-number');
        const txtHint   = document.getElementById('edit-hint-text');

        if (isPath) {
            numInput.classList.add('d-none');
            numHint.classList.add('d-none');
            txtInput.classList.remove('d-none');
            txtHint.classList.remove('d-none');
            txtInput.value = currentValue;
        } else {
            txtInput.classList.add('d-none');
            txtHint.classList.add('d-none');
            numInput.classList.remove('d-none');
            numHint.classList.remove('d-none');
            numInput.value = currentValue;
        }

        // Close settings modal, open edit modal
        bootstrap.Modal.getInstance(document.getElementById('settingsModal'))?.hide();
        const m = new bootstrap.Modal(editModal);
        m.show();
        editModal.addEventListener('shown.bs.modal', () => {
            const active = isPath ? txtInput : numInput;
            active?.focus();
            active?.select();
        }, { once: true });

        // Re-open settings modal when edit modal closes
        editModal.addEventListener('hidden.bs.modal', () => {
            new bootstrap.Modal(document.getElementById('settingsModal')).show();
        }, { once: true });
    }

    function saveSetting() {
        const key    = document.getElementById('edit-setting-key')?.value;
        const type   = document.getElementById('edit-setting-type')?.value;
        const isPath = (type === 'text');
        let val;

        if (isPath) {
            val = document.getElementById('edit-setting-value-text')?.value?.trim();
            if (!val || !val.startsWith('/') || val.includes('..')) {
                showAlert('warning', L.setting_edit_path_hint || 'Must start with / and must not contain ..');
                return;
            }
        } else {
            val = parseInt(document.getElementById('edit-setting-value-number')?.value, 10);
            if (!key || isNaN(val) || val < 1 || val > 100) {
                showAlert('warning', L.setting_edit_hint || 'Enter a value between 1 and 100.');
                return;
            }
        }

        const fd = new FormData();
        fd.append('action', 'update_settings');
        fd.append(key, val);
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    window.WIZARD_SETTINGS = res.settings;
                    showAlert('success', L.settings_saved || 'Settings saved.');
                    bootstrap.Modal.getInstance(document.getElementById('editSettingModal'))?.hide();
                    // Force page reload so all global constants apply immediately.
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    showAlert('danger', res.error || L.settings_error || 'Error saving settings.');
                }
            })
            .catch(() => showAlert('danger', L.error_network || 'Network error.'));
    }

    function bindSettings() {
        const btnSettings = document.getElementById('btn-settings');
        if (btnSettings) btnSettings.addEventListener('click', openSettingsModal);

        const btnSave = document.getElementById('btn-save-setting');
        if (btnSave) btnSave.addEventListener('click', saveSetting);

        document.getElementById('edit-setting-value-number')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); saveSetting(); }
        });
        document.getElementById('edit-setting-value-text')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); saveSetting(); }
        });
    }

    // ── End Settings ──────────────────────────────────────────────────────────

    function init() {
        bindModeSelector();
        bindPresetSection();
        bindFormSection();
        bindButtons();
        bindManageConnections();
        bindClearConnection();
        bindSettings();
        bindSteps();
        initTooltips();
        initDebugMode();
        syncUrlDebugParam();
        initPresetVisibility();
        loadConfigAndRestore();
    }

    function loadConfigAndRestore() {
        const initial = window.WIZARD_INITIAL_CONFIG || {};
        const ac = initial.active_connection;
        const connections = initial.connections || [];

        if (ac) {
            updateBreadcrumb(ac);
            if (ac.source === 'Custom' && ac.name) {
                loadedConnectionName = ac.name;
            }
        } else {
            loadedConnectionName = null;
        }

        if (!ac) {
            hideLoadingOverlay();
            return;
        }

        if (ac.source === 'Preset') {
            restoreFromConfig({ active_connection: ac });
            hideLoadingOverlay();
            return;
        }

        const conn = connections.find((c) => (c.name || '') === ac.name);
        if (!conn) {
            hideLoadingOverlay();
            return;
        }

        const engine = normalizeEngineId(ac.engine);
        const driver = normalizeDriverId(ac.driver, engine);
        const url = `api.php?action=get_connection_fields&driver=${driver}&engine=${engine || 'pdo'}&connection_name=${encodeURIComponent(ac.name)}`;
        fetch(url)
            .then((r) => r.json())
            .then((data) => {
                const connData = data.connData || {};
                const config = {
                    active_connection: ac,
                    connection_data: {
                        ...conn,
                        connection_data: connData,
                    },
                };
                restoreFromConfig(config, data);
            })
            .catch(() => {
                restoreFromConfig({ active_connection: ac, connection_data: conn });
            })
            .finally(hideLoadingOverlay);
    }

    function hideLoadingOverlay() {
        const overlay = document.getElementById('wizard-loading-overlay');
        if (overlay) {
            overlay.classList.add('hidden');
        }
    }

    function updateBreadcrumb(activeConnection) {
        // Keep WIZARD_INITIAL_CONFIG in sync so step-2 can always check the current active connection
        if (!window.WIZARD_INITIAL_CONFIG) window.WIZARD_INITIAL_CONFIG = {};
        window.WIZARD_INITIAL_CONFIG.active_connection = activeConnection || null;

        const prefixEl = $('#breadcrumb-prefix');
        const labelEl = $('#breadcrumb-active-label');
        const clearBtn = document.getElementById('btn-clear-connection');
        if (!labelEl) return;
        const item = labelEl.closest('.breadcrumb-item');
        if (!activeConnection || !activeConnection.name) {
            if (prefixEl) prefixEl.style.display = 'none';
            labelEl.innerHTML = L.no_active_connection || 'No active connection';
            labelEl.onclick = null;
            labelEl.classList.remove('breadcrumb-link', 'text-primary');
            item?.classList.remove('fw-semibold');
            if (clearBtn) clearBtn.classList.add('d-none');
            // Refresh query tab if visible (will show "no connection" banner and clear lists)
            const step2El = document.getElementById('wizard-step-2');
            if (step2El && !step2El.classList.contains('d-none')) {
                initQueryTab();
                const resultsCard = document.getElementById('query-results-card');
                if (resultsCard) resultsCard.classList.add('d-none');
            }
            // Refresh builder tab if visible
            const step3El = document.getElementById('wizard-step-3');
            if (step3El && !step3El.classList.contains('d-none')) {
                initBuilderTab();
                const qbResultsCard = document.getElementById('qb-results-card');
                if (qbResultsCard) qbResultsCard.classList.add('d-none');
            }
            return;
        }
        if (prefixEl) prefixEl.style.display = 'inline';
        if (clearBtn) clearBtn.classList.remove('d-none');
        const engine = normalizeEngineId(activeConnection.engine);
        const driver = normalizeDriverId(activeConnection.driver, engine);
        const engineLabel = getEngineLabel(engine);
        const driverLabel = getDriverLabel(driver, engine);
        const engineDriverLabel = engineLabel + '/' + driverLabel;

        const connName = escapeHtml(activeConnection.name || '');
        if (activeConnection.source === 'Preset') {
            labelEl.innerHTML = `<a href="#" class="breadcrumb-link text-primary text-decoration-none">Preset/${connName}</a>`;
        } else {
            labelEl.innerHTML = `<a href="#" class="breadcrumb-link text-primary text-decoration-none">Custom/${connName}</a>`;
        }
        labelEl.querySelector('.breadcrumb-link')?.addEventListener('click', (e) => {
            e.preventDefault();
            showActiveConfigModal();
        });
        item?.classList.add('fw-semibold');

        // If query tab is currently visible, refresh its data for the new active connection
        const step2El = document.getElementById('wizard-step-2');
        if (step2El && !step2El.classList.contains('d-none')) {
            initQueryTab();
            const resultsCard = document.getElementById('query-results-card');
            if (resultsCard) resultsCard.classList.add('d-none');
        }
        // If builder tab is currently visible, refresh its data for the new active connection
        const step3El = document.getElementById('wizard-step-3');
        if (step3El && !step3El.classList.contains('d-none')) {
            initBuilderTab();
            const qbResultsCard = document.getElementById('qb-results-card');
            if (qbResultsCard) qbResultsCard.classList.add('d-none');
        }
    }

    function showActiveConfigModal() {
        fetch('api.php?action=get_config')
            .then((r) => r.json())
            .then((config) => {
                const pre = $('#active-config-content');
                if (pre) pre.textContent = JSON.stringify(config, null, 2);
                const modal = new bootstrap.Modal(document.getElementById('activeConfigModal'));
                modal.show();
            })
            .catch(() => showAlert('danger', L.error_network || 'Error'));
    }

    function restoreFromConfig(config, preloadedFieldsData) {
        const ac = config.active_connection;
        if (!ac || !ac.source) return;

        const modeRadioPreset = document.getElementById('mode_preset');
        const modeRadioNew = document.getElementById('mode_new');

        if (ac.source === 'Preset') {
            const engine = normalizeEngineId(ac.engine);
            const driver = normalizeDriverId(ac.driver, engine);
            const mechanism = normalizeInstanceTypeId(ac.type);

            statePreset.module = ac.module || 'Chainable';
            statePreset.engine = engine;
            statePreset.driver = driver;
            statePreset.mechanism = mechanism;

            // Clear custom form selections — preset is now active
            stateNew.engine = null;
            stateNew.driver = null;
            $$('#engine-cards-new .engine-card').forEach((c) => c.classList.remove('selected'));
            const dcNew = document.getElementById('driver-cards-new');
            if (dcNew) dcNew.innerHTML = '';
            $('#driver-section-new').style.display = 'none';
            $('#instance-type-section-new').style.display = 'none';
            $('#connection-fields-section').style.display = 'none';
            $('#connection-name-options-section').style.display = 'none';

            const moduleRadio = document.querySelector(`input[name="module_preset"][value="${ac.module || 'Chainable'}"]`);
            if (moduleRadio) moduleRadio.checked = true;

            $('#engine-section-preset').style.display = 'block';
            $$('#engine-section-preset .engine-card').forEach((c) => {
                c.classList.toggle('selected', c.dataset.engine === engine);
            });

            renderDriverCards();
            $('#driver-section-preset').style.display = 'block';
            $$('#driver-cards .driver-card').forEach((c) => {
                c.classList.toggle('selected', c.dataset.driver === driver);
            });
            $('#instance-type-section-preset').style.display = 'block';

            const mechRadio = document.getElementById('mechanism_' + mechanism);
            if (mechRadio) mechRadio.checked = true;

            if (modeRadioPreset) {
                modeRadioPreset.checked = true;
                modeRadioPreset.dispatchEvent(new Event('change', { bubbles: true }));
            }
        } else if ((ac.source === 'Custom') && config.connection_data) {
            const cd = config.connection_data;

            // Clear preset panel selections — custom is now active
            statePreset.engine = null;
            statePreset.driver = null;
            $$('#engine-section-preset .engine-card').forEach((c) => c.classList.remove('selected'));
            const dcPreset = document.getElementById('driver-cards');
            if (dcPreset) dcPreset.innerHTML = '';
            $('#driver-section-preset').style.display = 'none';
            $('#instance-type-section-preset').style.display = 'none';

            stateNew.module = cd.module || ac.module || 'Chainable';
            stateNew.engine = normalizeEngineId(cd.engine || ac.engine);
            stateNew.driver = normalizeDriverId(cd.driver || ac.driver, stateNew.engine);

            const moduleRadio = document.querySelector(`#connection-form input[name="module"][value="${stateNew.module}"]`);
            if (moduleRadio) moduleRadio.checked = true;

            pendingRestoreData = cd;

            if (modeRadioNew) {
                modeRadioNew.checked = true;
                modeRadioNew.dispatchEvent(new Event('change', { bubbles: true }));
            }

            const formEngine = $('#form-engine');
            const formDriver = $('#form-driver');
            if (formEngine && formDriver) {
                formEngine.value = stateNew.engine;
                formDriver.value = stateNew.driver;
                $$('#engine-cards-new .engine-card').forEach((c) => {
                    c.classList.toggle('selected', c.dataset.engine === stateNew.engine);
                });
                renderDriverCardsNew();
                $('#driver-section-new').style.display = 'block';
                $$('#driver-cards-new .driver-card').forEach((c) => {
                    c.classList.toggle('selected', c.dataset.driver === stateNew.driver);
                });
                $('#instance-type-section-new').style.display = 'block';
                $('#connection-fields-section').style.display = 'block';
                loadConnectionFields(pendingRestoreData, preloadedFieldsData);
                pendingRestoreData = null;
                setTimeout(() => {
                    $('#connection-name-options-section').style.display = 'block';
                    const modalConnName = $('#modal-connection-name');
                    if (modalConnName) modalConnName.value = (cd.name || ac.name || '').replace(/^custom_/, '');
                    const instType = normalizeInstanceTypeId(ac.type || cd.type);
                    const instRadio = document.querySelector(`#connection-form input[name="instance_type"][value="${instType}"]`);
                    if (instRadio) instRadio.checked = true;
                    const connData = cd.connection_data;
                    const optsData = connData ? (Array.isArray(connData) ? connData[0]?.options : connData.options) : null;
                    if (optsData) {
                        const obj = Array.isArray(optsData) ? optsData[0] : optsData;
                        if (obj && typeof obj === 'object') {
                            const lines = [];
                            Object.entries(obj).forEach(([k, v]) => {
                                const valStr = formatOptionValue(v);
                                lines.push(`${k} => ${valStr}`);
                            });
                            const optsEl = $('#connection-options');
                            if (optsEl) optsEl.value = lines.join('\n');
                        }
                    }
                }, 300);
            }
        }
    }

    function initPresetVisibility() {
        const moduleChecked = document.querySelector('input[name="module_preset"]:checked');
        if (moduleChecked) {
            $('#engine-section-preset').style.display = 'block';
        }
    }

    function bindModeSelector() {
        const panelPreset = document.getElementById('panel-preset');
        const panelNew = document.getElementById('panel-new');

        function switchPanel(mode) {
            state.mode = mode || document.querySelector('input[name="wizard_mode"]:checked')?.value || 'preset';
            if (panelPreset) {
                panelPreset.classList.toggle('d-none', state.mode !== 'preset');
                panelPreset.classList.toggle('d-block', state.mode === 'preset');
            }
            if (panelNew) {
                panelNew.classList.toggle('d-none', state.mode !== 'new');
                panelNew.classList.toggle('d-block', state.mode === 'new');
                if (state.mode === 'new') syncNewFormModule();
            }
        }

        $$('input[name="wizard_mode"]').forEach((el) => {
            el.addEventListener('change', () => switchPanel());
        });
        document.querySelectorAll('.mode-selector label[for^="mode_"]').forEach((label) => {
            label.addEventListener('click', () => setTimeout(() => switchPanel(), 50));
        });
    }

    function bindPresetSection() {
        $$('input[name="module_preset"]').forEach((el) => {
            el.addEventListener('change', (e) => {
                statePreset.module = e.target.value;
                statePreset.engine = null;
                statePreset.driver = null;
                $$('.engine-card').forEach((c) => c.classList.remove('selected'));
                $('#engine-section-preset').style.display = 'block';
                $('#driver-section-preset').style.display = 'none';
                $('#instance-type-section-preset').style.display = 'none';
                const dc = document.getElementById('driver-cards');
                if (dc) dc.innerHTML = '';
                $('#mechanism_specific').checked = true;
            });
        });

        $$('.engine-card').forEach((card) => {
            card.addEventListener('click', () => {
                $$('.engine-card').forEach((c) => c.classList.remove('selected'));
                card.classList.add('selected');
                statePreset.engine = card.dataset.engine;
                statePreset.driver = null;
                renderDriverCards();
                $('#driver-section-preset').style.display = 'block';
                $('#instance-type-section-preset').style.display = 'none';
            });
        });

        $$('input[name="mechanism_preset"]').forEach((el) => {
            el.addEventListener('change', (e) => {
                statePreset.mechanism = e.target.value;
            });
        });

        const btnResetPreset = $('#btn-reset-preset');
        const btnConfirmPreset = $('#btn-confirm-preset');
        const btnTestPreset = $('#btn-test-preset');
        if (btnResetPreset) btnResetPreset.addEventListener('click', resetPreset);
        if (btnConfirmPreset) btnConfirmPreset.addEventListener('click', confirmPresetConnection);
        if (btnTestPreset) btnTestPreset.addEventListener('click', testPresetConnection);
    }

    function renderDriverCards() {
        const container = document.getElementById('driver-cards');
        if (!container) return;
        container.innerHTML = '';
        if (!statePreset.engine || !CONFIG.engines[statePreset.engine]) return;

        const drivers = CONFIG.engines[statePreset.engine].drivers;
        drivers.forEach((driverId) => {
            const card = document.createElement('div');
            card.className = 'driver-card' + (statePreset.driver === driverId ? ' selected' : '');
            card.dataset.driver = driverId;
            const iconRef = CONFIG.driverIcons[driverId];
            const label = escapeHtml(getDriverLabel(driverId, statePreset.engine));
            if (iconRef) {
                const wrap = document.createElement('div');
                wrap.className = 'driver-icon-wrap';
                if (iconRef.startsWith('bi-')) {
                    const i = document.createElement('i');
                    i.className = 'bi ' + iconRef + ' driver-icon driver-icon-bi';
                    wrap.appendChild(i);
                } else {
                    const img = document.createElement('img');
                    img.src = iconRef;
                    img.alt = '';
                    img.className = 'driver-icon';
                    img.onerror = () => {
                        const fallback = document.createElement('i');
                        fallback.className = 'bi bi-database driver-icon driver-icon-bi';
                        img.replaceWith(fallback);
                    };
                    wrap.appendChild(img);
                }
                card.appendChild(wrap);
            }
            const span = document.createElement('span');
            span.textContent = getDriverLabel(driverId, statePreset.engine);
            card.appendChild(span);
            card.addEventListener('click', () => {
                statePreset.driver = driverId;
                container.querySelectorAll('.driver-card').forEach((c) => c.classList.remove('selected'));
                card.classList.add('selected');
                $('#instance-type-section-preset').style.display = 'block';
            });
            container.appendChild(card);
        });
    }

    function testPresetConnection() {
        if (!statePreset.engine || !statePreset.driver) {
            showAlert('warning', L.confirm_select_first || 'Select an Engine and a Driver before confirming.');
            return;
        }
        const btn = $('#btn-test-preset');
        if (btn) {
            btn.classList.add('loading');
            btn.disabled = true;
        }
        const formData = new FormData();
        formData.append('action', 'test_connection');
        formData.append('module', statePreset.module);
        formData.append('engine', statePreset.engine);
        formData.append('driver', statePreset.driver);
        formData.append('instance_type', statePreset.mechanism);
        if (isDebugMode()) formData.append('debug', '1');
        fetch('api.php', { method: 'POST', body: formData })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    showAlert('success', L.connection_confirmed || 'Connection confirmed!');
                    if (res.debug) showDebugModal(res.debug);
                } else {
                    showAlert('danger', res.error || (L.error_test || 'Error testing connection.'));
                }
            })
            .catch((err) => showAlert('danger', (L.error_network || 'Error') + ': ' + err.message))
            .finally(() => {
                if (btn) {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                }
            });
    }

    function confirmPresetConnection() {
        if (!statePreset.engine || !statePreset.driver) {
            showAlert('warning', L.confirm_select_first || 'Select an Engine and a Driver before confirming.');
            return;
        }
        const btn = $('#btn-confirm-preset');
        if (btn) {
            btn.classList.add('loading');
            btn.disabled = true;
        }
        const formData = new FormData();
        formData.append('action', 'confirm_preset');
        formData.append('module', statePreset.module);
        formData.append('engine', statePreset.engine);
        formData.append('driver', statePreset.driver);
        formData.append('instance_type', statePreset.mechanism);
        if (isDebugMode()) formData.append('debug', '1');
        fetch('api.php', { method: 'POST', body: formData })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    showAlert('success', L.connection_confirmed || 'Connection confirmed!');
                    if (res.debug) showDebugModal(res.debug);
                    const cfg = res.config || {};
                    updateBreadcrumb(cfg.active_connection);
                } else {
                    showAlert('danger', res.error || (L.error_test || 'Error testing connection.'));
                }
            })
            .catch((err) => showAlert('danger', (L.error_network || 'Error') + ': ' + err.message))
            .finally(() => {
                if (btn) {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                }
            });
    }

    function bindFormSection() {
        const formEngine = $('#form-engine');
        const formDriver = $('#form-driver');
        if (!formEngine || !formDriver) return;

        const moduleRadios = $$('#connection-form input[name="module"]');
        moduleRadios.forEach((radio) => {
            radio.addEventListener('change', (e) => {
                stateNew.module = e.target.value;
                stateNew.engine = null;
                stateNew.driver = null;
                $('#engine-section-new').style.display = 'block';
                formEngine.value = '';
                formDriver.value = '';
                $$('#engine-cards-new .engine-card').forEach((c) => c.classList.remove('selected'));
                $('#driver-section-new').style.display = 'none';
                $('#instance-type-section-new').style.display = 'none';
                $('#connection-fields-section').style.display = 'none';
                $('#connection-name-options-section').style.display = 'none';
                $('#driver-cards-new').innerHTML = '';
                const connFields = $('#connection-fields');
                if (connFields) connFields.innerHTML = '';
            });
        });

        $$('#engine-cards-new .engine-card').forEach((card) => {
            card.addEventListener('click', () => {
                $$('#engine-cards-new .engine-card').forEach((c) => c.classList.remove('selected'));
                card.classList.add('selected');
                stateNew.engine = card.dataset.engine;
                stateNew.driver = null;
                formEngine.value = stateNew.engine;
                formDriver.value = '';
                renderDriverCardsNew();
                $('#driver-section-new').style.display = 'block';
                $('#instance-type-section-new').style.display = 'none';
                $('#connection-fields-section').style.display = 'none';
                $('#connection-name-options-section').style.display = 'none';
                const connFields = $('#connection-fields');
                if (connFields) connFields.innerHTML = '';
            });
        });

        document.getElementById('driver-cards-new')?.addEventListener('click', (e) => {
            const card = e.target.closest('.driver-card');
            if (!card) return;
            $$('#driver-cards-new .driver-card').forEach((c) => c.classList.remove('selected'));
            card.classList.add('selected');
            stateNew.driver = card.dataset.driver;
            formDriver.value = stateNew.driver;
            $('#instance-type-section-new').style.display = 'block';
            $('#connection-fields-section').style.display = 'block';
            $('#connection-name-options-section').style.display = 'none';
            loadConnectionFields(pendingRestoreData);
            pendingRestoreData = null;
        });

        $$('#connection-form input[name="instance_type"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                $('#connection-name-options-section').style.display = 'block';
            });
        });
    }

    function renderDriverCardsNew() {
        const container = document.getElementById('driver-cards-new');
        if (!container) return;
        container.innerHTML = '';
        if (!stateNew.engine || !CONFIG.engines[stateNew.engine]) return;

        const drivers = CONFIG.engines[stateNew.engine].drivers;
        drivers.forEach((driverId) => {
            const card = document.createElement('div');
            card.className = 'driver-card' + (stateNew.driver === driverId ? ' selected' : '');
            card.dataset.driver = driverId;
            const iconRef = CONFIG.driverIcons[driverId];
            const label = escapeHtml(getDriverLabel(driverId, stateNew.engine));
            if (iconRef) {
                const wrap = document.createElement('div');
                wrap.className = 'driver-icon-wrap';
                if (iconRef.startsWith('bi-')) {
                    const i = document.createElement('i');
                    i.className = 'bi ' + iconRef + ' driver-icon driver-icon-bi';
                    wrap.appendChild(i);
                } else {
                    const img = document.createElement('img');
                    img.src = iconRef;
                    img.alt = '';
                    img.className = 'driver-icon';
                    img.onerror = () => {
                        const fallback = document.createElement('i');
                        fallback.className = 'bi bi-database driver-icon driver-icon-bi';
                        img.replaceWith(fallback);
                    };
                    wrap.appendChild(img);
                }
                card.appendChild(wrap);
            }
            const span = document.createElement('span');
            span.textContent = getDriverLabel(driverId, stateNew.engine);
            card.appendChild(span);
            container.appendChild(card);
        });
    }

    function showConnectionNameOptionsIfInstanceTypeSelected() {
        const checked = document.querySelector('#connection-form input[name="instance_type"]:checked');
        if (checked) {
            $('#connection-name-options-section').style.display = 'block';
        }
    }

    function syncNewFormModule() {
        const checked = document.querySelector('#connection-form input[name="module"]:checked');
        if (checked) {
            stateNew.module = checked.value;
            $('#engine-section-new').style.display = 'block';
            if (stateNew.engine) {
                $$('#engine-cards-new .engine-card').forEach((c) => {
                    c.classList.toggle('selected', c.dataset.engine === stateNew.engine);
                });
                renderDriverCardsNew();
                $('#driver-section-new').style.display = 'block';
                if (stateNew.driver) {
                    $$('#driver-cards-new .driver-card').forEach((c) => {
                        c.classList.toggle('selected', c.dataset.driver === stateNew.driver);
                    });
                }
            }
        }
    }

    function loadConnectionFields(prefillData, preloadedData) {
        if (!stateNew.driver) return;

        const applyFieldsData = (data) => {
                const container = $('#connection-fields');
                container.innerHTML = '';
                const connData = data.connData || (() => {
                    const raw = prefillData?.connection_data;
                    return raw ? (Array.isArray(raw) ? raw[0] : raw) : {};
                })();
                const colMap = { host: 'col-md-5', port: 'col-md-2', database: 'col-md-5', username: 'col-md-4', password: 'col-md-4', charset: 'col-md-4' };
                (data.fields || []).forEach((f) => {
                    let val = f.value ?? '';
                    if (val === '' && connData) {
                        if (connData[f.env_key]) val = connData[f.env_key];
                        else if (connData[f.name]) val = connData[f.name];
                        else if (f.name === 'username' && connData['user']) val = connData['user'];
                    }
                    const label = L['field_' + f.name] || f.label || f.name;
                    const col = colMap[f.name] || 'col-md-6';
                    const div = document.createElement('div');
                    div.className = col + ' col-12';
                    div.innerHTML = `
                        <label class="form-label fw-semibold">${escapeHtml(label)}</label>
                        <input type="${f.type || 'text'}" class="form-control" name="${escapeHtml(f.name)}"
                            id="field_${escapeHtml(f.name)}" value="${escapeHtml(String(val))}"
                            placeholder="${escapeHtml(label)}">
                    `;
                    container.appendChild(div);
                });
                if (connData && connData.options && typeof connData.options === 'object') {
                    const optsEl = $('#connection-options');
                    if (optsEl) {
                        const lines = [];
                        Object.entries(connData.options).forEach(([k, v]) => {
                            lines.push(`${k} => ${formatOptionValue(v)}`);
                        });
                        optsEl.value = lines.join('\n');
                    }
                }
                showConnectionNameOptionsIfInstanceTypeSelected();
            };

        if (preloadedData && preloadedData.fields) {
            applyFieldsData(preloadedData);
            return;
        }

        const connNameForApi = prefillData?.name || '';
        const url = `api.php?action=get_connection_fields&driver=${stateNew.driver}&engine=${stateNew.engine || 'pdo'}` +
            (connNameForApi ? `&connection_name=${encodeURIComponent(connNameForApi)}` : '');
        fetch(url)
            .then((r) => r.json())
            .then(applyFieldsData)
            .catch((err) => showAlert('danger', (L.error_load_fields || 'Error loading fields') + ': ' + err.message));
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function bindButtons() {
        const btnTest = $('#btn-test-connection');
        const btnResetNew = $('#btn-reset-new');
        const btnConfirm = $('#btn-confirm-connection');
        const btnSaveConnection = $('#btn-save-connection');
        const connectionNameModal = document.getElementById('connectionNameModal');
        if (btnTest) btnTest.addEventListener('click', testConnection);
        if (btnConfirm) {
            btnConfirm.addEventListener('click', () => {
                const data = getFormData();
                if (!data.module || !data.engine || !data.driver) {
                    showAlert('warning', L.fill_required || 'Fill in Module, Engine and Driver.');
                    return;
                }
                const modal = new bootstrap.Modal(connectionNameModal);
                const modalInput = $('#modal-connection-name');
                if (modalInput) {
                    modalInput.value = (editingConnectionName || loadedConnectionName || '').replace(/^custom_/, '');
                }
                modal.show();
                connectionNameModal.addEventListener('shown.bs.modal', function focusInput() {
                    $('#modal-connection-name')?.focus();
                    connectionNameModal.removeEventListener('shown.bs.modal', focusInput);
                }, { once: true });
            });
        }
        if (btnSaveConnection && connectionNameModal) {
            const doSaveConnection = () => {
                const connName = $('#modal-connection-name')?.value?.trim();
                if (!connName) {
                    showAlert('warning', L.fill_required || 'Fill in the connection name.');
                    return;
                }
                bootstrap.Modal.getInstance(connectionNameModal)?.hide();
                completeConnection(connName, editingConnectionName);
            };
            btnSaveConnection.addEventListener('click', doSaveConnection);
            $('#modal-connection-name')?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    doSaveConnection();
                }
            });
        }
        if (btnResetNew) btnResetNew.addEventListener('click', resetNew);
    }

    function bindClearConnection() {
        const btn = document.getElementById('btn-clear-connection');
        if (!btn) return;
        btn.addEventListener('click', () => {
            const clearModal = new bootstrap.Modal(document.getElementById('clearConfirmModal'));
            clearModal.show();
        });
        document.getElementById('btn-clear-confirm')?.addEventListener('click', () => {
            bootstrap.Modal.getInstance(document.getElementById('clearConfirmModal'))?.hide();
            clearActiveConnection();
        });
    }

    function clearActiveConnection() {
        const fd = new FormData();
        fd.append('action', 'clear_active_connection');
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    showAlert('success', L.connection_cleared || 'Active connection cleared.');
                    loadedConnectionName = null;
                    updateBreadcrumb(null);
                    const modalInput = document.getElementById('modal-connection-name');
                    if (modalInput) modalInput.value = '';
                } else {
                    showAlert('danger', res.error || 'Error');
                }
            })
            .catch(() => showAlert('danger', L.error_network || 'Error'));
    }

    function bindManageConnections() {
        const btn = $('#btn-manage-connections');
        const modal = document.getElementById('manageConnectionsModal');
        if (!btn || !modal) return;
        btn.addEventListener('click', () => {
            openManageConnectionsModal();
        });
    }

    function connPageSize() { return (window.WIZARD_SETTINGS?.connections_page_size || 10); }

    function buildPaginationNav(total, pageSize, currentPage, onPageChange) {
        const totalPages = Math.ceil(total / pageSize) || 1;
        if (totalPages <= 1) return null;
        const nav = document.createElement('nav');
        nav.className = 'pagination-wrap d-flex justify-content-center';
        let html = '<ul class="pagination mb-0">';
        html += `<li class="page-item${currentPage <= 1 ? ' disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a></li>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<li class="page-item${i === currentPage ? ' active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        html += `<li class="page-item${currentPage >= totalPages ? ' disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a></li>`;
        html += '</ul>';
        nav.innerHTML = html;
        nav.querySelectorAll('[data-page]').forEach((a) => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                const p = parseInt(a.dataset.page, 10);
                if (p >= 1 && p <= totalPages && p !== currentPage) onPageChange(p);
            });
        });
        return nav;
    }

    let connectionsCurrentPage = 1;
    let connectionsData = [];

    function openManageConnectionsModal() {
        const modalEl = document.getElementById('manageConnectionsModal');
        let modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(modalEl);
        }
        modalInstance.show();
        fetchConnectionsAndRender();
    }

    function fetchConnectionsAndRender(activeName) {
        fetch('api.php?action=get_connections')
            .then((r) => r.json())
            .then((res) => {
                connectionsData = res.connections || [];
                connectionsCurrentPage = 1;
                renderConnectionsTable(activeName ?? res.active_name);
                return res;
            })
            .catch(() => showAlert('danger', (L.error_network || 'Error') + ' loading connections.'));
    }

    function refreshConnectionsInModalAndCloseIfEmpty() {
        fetch('api.php?action=get_connections')
            .then((r) => r.json())
            .then((res) => {
                connectionsData = res.connections || [];
                connectionsCurrentPage = 1;
                renderConnectionsTable(res.active_name);
                if (connectionsData.length === 0) {
                    const modalEl = document.getElementById('manageConnectionsModal');
                    const instance = bootstrap.Modal.getInstance(modalEl);
                    if (instance) {
                        modalEl.addEventListener('hidden.bs.modal', function cleanup() {
                            modalEl.removeEventListener('hidden.bs.modal', cleanup);
                            removeOrphanedBackdrops();
                        }, { once: true });
                        instance.hide();
                    }
                }
            });
    }

    function removeOrphanedBackdrops() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach((b) => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    function renderConnectionsTable(activeName) {
        const tbody = document.querySelector('#connections-table tbody');
        if (!tbody) return;
        tbody.innerHTML = '';
        const total = connectionsData.length;
        if (total === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">' + (L.no_connections_found || 'No connections found') + '</td></tr>';
            return;
        }
        const start = (connectionsCurrentPage - 1) * connPageSize();
        const end = Math.min(start + connPageSize(), total);
        const pageData = connectionsData.slice(start, end);

        pageData.forEach((c) => {
            const name = c.name || '';
            const isActive = name === activeName;
            const isPreset = c.source === 'Preset';
            const engine = normalizeEngineId(c.engine);
            const driver = normalizeDriverId(c.driver, engine);
            const engineLabel = getEngineLabel(engine);
            const driverLabel = getDriverLabel(driver, engine);
            const displayName = name;
            const tr = document.createElement('tr');
            const editDeleteBtns = isPreset ? '' : `
                        <button type="button" class="btn btn-outline-primary" data-action="edit" data-name="${escapeHtml(name)}" title="${L.edit || 'Edit'}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-action="delete" data-name="${escapeHtml(name)}" title="${L.delete || 'Delete'}">
                            <i class="bi bi-trash"></i>
                        </button>`;
            tr.innerHTML = `
                <td><strong>${escapeHtml(displayName)}</strong></td>
                <td>${escapeHtml(c.source || '')}</td>
                <td>${escapeHtml(c.module || '')}</td>
                <td>${escapeHtml(engineLabel)}</td>
                <td>${escapeHtml(driverLabel)}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-${isActive ? 'success' : 'secondary'}" data-action="toggle" data-name="${escapeHtml(name)}" title="${L.make_active || 'Make active'}">
                            <i class="bi bi-${isActive ? 'check-circle-fill' : 'circle'}"></i>
                        </button>${editDeleteBtns}
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        tbody.closest('.modal-body').querySelector('.pagination-wrap')?.remove();
        const nav = buildPaginationNav(total, connPageSize(), connectionsCurrentPage, (p) => {
            connectionsCurrentPage = p;
            fetch('api.php?action=get_connections').then((r) => r.json()).then((res) => {
                connectionsData = res.connections || [];
                renderConnectionsTable(res.active_name || '');
            });
        });
        if (nav) tbody.closest('.modal-body').appendChild(nav);

    }

    let pendingDeleteConnectionName = null;

    document.getElementById('manageConnectionsModal')?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;
        const name = btn.dataset.name;
        if (action === 'toggle' && !btn.classList.contains('btn-outline-success')) {
            setActiveConnection(name);
        } else if (action === 'edit') {
            bootstrap.Modal.getInstance(document.getElementById('manageConnectionsModal'))?.hide();
            editConnection(name);
        } else if (action === 'delete') {
            pendingDeleteConnectionName = name;
            const confirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            confirmModal.show();
        }
    });

    document.getElementById('btn-delete-confirm')?.addEventListener('click', () => {
        const name = pendingDeleteConnectionName;
        bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'))?.hide();
        pendingDeleteConnectionName = null;
        if (name) deleteConnection(name);
    });

    function setActiveConnection(name) {
        const fd = new FormData();
        fd.append('action', 'set_active_connection');
        fd.append('connection_name', name);
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    showAlert('success', L.connection_activated || 'Connection set as active.');
                    const cfg = res.config || {};
                    if (cfg.active_connection?.source === 'Custom' && cfg.active_connection?.name) {
                        loadedConnectionName = cfg.active_connection.name;
                    } else {
                        loadedConnectionName = null;
                    }
                    updateBreadcrumb(cfg.active_connection);
                    restoreFromConfig(cfg);
                    renderConnectionsTable(cfg.active_connection?.name || '');
                } else {
                    showAlert('danger', res.error || 'Error');
                }
            })
            .catch(() => showAlert('danger', L.error_network || 'Error'));
    }

    function deleteConnection(name) {
        const fd = new FormData();
        fd.append('action', 'delete_connection');
        fd.append('connection_name', name);
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    showAlert('success', L.connection_deleted || 'Connection deleted.');
                    const cfg = res.config || {};
                    updateBreadcrumb(cfg.active_connection);
                    if (editingConnectionName === name) {
                        clearEditMode();
                    }
                    refreshConnectionsInModalAndCloseIfEmpty();
                } else {
                    showAlert('danger', res.error || 'Error');
                }
            })
            .catch(() => showAlert('danger', L.error_network || 'Error'));
    }

    function editConnection(name) {
        editingConnectionName = name;
        const conn = connectionsData.find((c) => (c.name || '') === name);
        if (!conn) return;
        const panelTitle = $('#panel-new-title');
        if (panelTitle) {
            panelTitle.innerHTML = `<i class="bi bi-pencil"></i> ${L.panel_edit_title || 'Edit Connection'}`;
        }
        document.getElementById('mode_new').checked = true;
        document.getElementById('mode_new').dispatchEvent(new Event('change', { bubbles: true }));
        pendingRestoreData = conn;
        stateNew.module = conn.module || 'Chainable';
        stateNew.engine = normalizeEngineId(conn.engine);
        stateNew.driver = normalizeDriverId(conn.driver, stateNew.engine);
        const formEngine = $('#form-engine');
        const formDriver = $('#form-driver');
        if (formEngine && formDriver) {
            formEngine.value = stateNew.engine;
            formDriver.value = stateNew.driver;
            $$('#engine-cards-new .engine-card').forEach((c) => c.classList.toggle('selected', c.dataset.engine === stateNew.engine));
            renderDriverCardsNew();
            $('#driver-section-new').style.display = 'block';
            $$('#driver-cards-new .driver-card').forEach((c) => c.classList.toggle('selected', c.dataset.driver === stateNew.driver));
            $('#instance-type-section-new').style.display = 'block';
            $('#connection-fields-section').style.display = 'block';
            loadConnectionFields(pendingRestoreData);
            pendingRestoreData = null;
            setTimeout(() => {
                $('#connection-name-options-section').style.display = 'block';
                $('#modal-connection-name').value = (conn.name || name).replace(/^custom_/, '');
                const instType = normalizeInstanceTypeId(conn.type);
                const instRadio = document.querySelector(`#connection-form input[name="instance_type"][value="${instType}"]`);
                if (instRadio) instRadio.checked = true;
                const connDataRaw = conn.connection_data;
                const optsData = connDataRaw ? (Array.isArray(connDataRaw) ? connDataRaw[0]?.options : connDataRaw.options) : null;
                if (optsData) {
                    const obj = Array.isArray(optsData) ? optsData[0] : optsData;
                    if (obj && typeof obj === 'object') {
                        const lines = [];
                        Object.entries(obj).forEach(([k, v]) => {
                            lines.push(`${k} => ${formatOptionValue(v)}`);
                        });
                        $('#connection-options').value = lines.join('\n');
                    }
                }
            }, 300);
        }
    }

    function clearEditMode() {
        editingConnectionName = null;
        const panelTitle = $('#panel-new-title');
        if (panelTitle) {
            panelTitle.innerHTML = '<i class="bi bi-plus-circle"></i> ' + (L.panel_new_title || 'Create New Connection');
        }
    }

    function getFormData() {
        const form = $('#connection-form');
        const data = new FormData(form);
        const obj = {};
        data.forEach((v, k) => obj[k] = v);
        return obj;
    }

    function testConnection() {
        const data = getFormData();
        if (!data.module || !data.engine || !data.driver) {
            showAlert('warning', L.fill_required || 'Fill in Module, Engine and Driver.');
            return;
        }

        const btn = $('#btn-test-connection');
        btn.classList.add('loading');
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${L.testing || 'Testing...'}`;

        const formData = new FormData();
        Object.entries(data).forEach(([k, v]) => formData.append(k, v));
        formData.append('action', 'test_connection');
        if (isDebugMode()) formData.append('debug', '1');

        const testLabel = L.test || 'Test';

        fetch('api.php', { method: 'POST', body: formData })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    showAlert('success', L.connection_confirmed || 'Connection confirmed!');
                    if (res.debug) showDebugModal(res.debug);
                } else {
                    showAlert('danger', res.error || (L.error_test || 'Error testing connection.'));
                }
            })
            .catch((err) => showAlert('danger', (L.error_network || 'Error') + ': ' + err.message))
            .finally(() => {
                btn.classList.remove('loading');
                btn.disabled = false;
                btn.innerHTML = `<i class="bi bi-plug-fill"></i> ${testLabel}`;
            });
    }

    function completeConnection(connectionName, editOriginalName) {
        const data = getFormData();
        if (!data.module || !data.engine || !data.driver) {
            showAlert('warning', L.fill_required || 'Fill in all required fields.');
            return;
        }
        if (!connectionName || !connectionName.trim()) {
            showAlert('warning', L.fill_required || 'Fill in the connection name.');
            return;
        }

        const btn = $('#btn-confirm-connection');
        if (btn) {
            btn.classList.add('loading');
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${L.creating || 'Creating...'}`;
        }

        const formData = new FormData();
        Object.entries(data).forEach(([k, v]) => formData.append(k, v));
        const connName = connectionName.trim();
        formData.append('connection_name', connName);
        const optionsEl = $('#connection-options');
        if (optionsEl) formData.set('options', optionsEl.value);
        const effectiveEditName = editOriginalName || (loadedConnectionName && 'custom_' + connName === loadedConnectionName ? loadedConnectionName : null);
        if (effectiveEditName) {
            formData.append('edit_connection_name', effectiveEditName);
            formData.append('action', 'update_connection');
        } else {
            formData.append('action', 'complete_connection');
        }

        fetch('api.php', { method: 'POST', body: formData })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    showAlert('success', effectiveEditName ? (L.connection_updated || 'Connection updated!') : (L.connection_created || 'Connection created!'));
                    if (effectiveEditName) clearEditMode();
                    const cfg = res.config || {};
                    if (cfg.active_connection?.source === 'Custom' && cfg.active_connection?.name) {
                        loadedConnectionName = cfg.active_connection.name;
                    }
                    updateBreadcrumb(cfg.active_connection);
                } else {
                    showAlert('danger', res.error || (L.error_create || 'Error creating connection.'));
                }
            })
            .catch((err) => showAlert('danger', (L.error_network || 'Error') + ': ' + err.message))
            .finally(() => {
                if (btn) {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    btn.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${L.confirm || 'Confirm'}`;
                }
            });
    }

    function resetPreset() {
        statePreset.engine = null;
        statePreset.driver = null;
        statePreset.mechanism = 'specific';
        $$('.engine-card').forEach((c) => c.classList.remove('selected'));
        $('#engine-section-preset').style.display = 'none';
        $('#driver-section-preset').style.display = 'none';
        $('#instance-type-section-preset').style.display = 'none';
        const dc = document.getElementById('driver-cards');
        if (dc) dc.innerHTML = '';
        const moduleChainable = $('#module_chainable');
        if (moduleChainable) {
            moduleChainable.checked = true;
            moduleChainable.dispatchEvent(new Event('change', { bubbles: true }));
        }
        $('#mechanism_specific').checked = true;
    }

    function resetNew() {
        clearEditMode();
        loadedConnectionName = null;
        stateNew.module = 'Chainable';
        stateNew.engine = null;
        stateNew.driver = null;
        const formEngine = $('#form-engine');
        const formDriver = $('#form-driver');
        const moduleChainable = $('#module_chainable_new');
        if (moduleChainable) moduleChainable.checked = true;
        if (formEngine) formEngine.value = '';
        if (formDriver) formDriver.value = '';
        $$('#engine-cards-new .engine-card').forEach((c) => c.classList.remove('selected'));
        const dcNew = document.getElementById('driver-cards-new');
        if (dcNew) dcNew.innerHTML = '';
        $('#modal-connection-name').value = '';
        $('#connection-options').value = '';
        $('#connection-fields').innerHTML = '';
        const instanceSpecific = $('#instance_specific_new');
        if (instanceSpecific) instanceSpecific.checked = true;
        $('#engine-section-new').style.display = 'block';
        $('#driver-section-new').style.display = 'none';
        $('#instance-type-section-new').style.display = 'none';
        $('#connection-fields-section').style.display = 'none';
        $('#connection-name-options-section').style.display = 'none';
    }

    function showAlert(type, message) {
        document.getElementById('wizard-toast-overlay')?.remove();
        const iconMap = {
            success: 'bi-check-circle-fill',
            danger: 'bi-x-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill',
        };
        const colorMap = {
            success: 'text-success',
            danger: 'text-danger',
            warning: 'text-warning',
            info: 'text-info',
        };
        const overlay = document.createElement('div');
        overlay.id = 'wizard-toast-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.35);';
        const box = document.createElement('div');
        box.style.cssText = 'background:#fff;border-radius:0.75rem;padding:1.75rem 2.25rem;box-shadow:0 8px 32px rgba(0,0,0,0.2);max-width:90vw;min-width:260px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:0.75rem;';
        box.innerHTML = `<i class="bi ${iconMap[type] || 'bi-info-circle-fill'} ${colorMap[type] || 'text-info'}" style="font-size:2.25rem;"></i><span style="font-size:1rem;font-weight:500;color:#212529;">${escapeHtml(message)}</span>`;
        overlay.appendChild(box);
        document.body.appendChild(overlay);
        overlay.addEventListener('click', () => overlay.remove());
        setTimeout(() => overlay.remove(), 3500);
    }

    function initTooltips() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltips].forEach((el) => new bootstrap.Tooltip(el));
    }

    // ── Step Switching ──────────────────────────────────────────────────────

    function bindSteps() {
        $$('.wizard-steps .step').forEach((stepEl) => {
            stepEl.style.cursor = 'pointer';
            stepEl.addEventListener('click', () => {
                const step = parseInt(stepEl.dataset.step, 10);
                if (isNaN(step) || step < 1 || step > 3) return;
                if (step === 2 || step === 3) {
                    const cfg = window.WIZARD_INITIAL_CONFIG || {};
                    if (!cfg.active_connection) {
                        showAlert('warning', L.query_no_connection || 'No active connection. Configure one in Step 1 first.');
                        return;
                    }
                }
                switchWizardStep(step);
            });
        });
    }

    function switchWizardStep(step) {
        $$('.wizard-steps .step').forEach((el) => {
            el.classList.toggle('active', parseInt(el.dataset.step, 10) === step);
        });
        const step1 = document.getElementById('wizard-step-1');
        const step2 = document.getElementById('wizard-step-2');
        const step3 = document.getElementById('wizard-step-3');
        if (step1) step1.classList.toggle('d-none', step !== 1);
        if (step2) step2.classList.toggle('d-none', step !== 2);
        if (step3) step3.classList.toggle('d-none', step !== 3);
        if (step === 2) initQueryTab();
        if (step === 3) initBuilderTab();
    }

    // ── Query Tab ───────────────────────────────────────────────────────────

    let queryTabInitialized = false;

    function initQueryTab() {
        const cfg = window.WIZARD_INITIAL_CONFIG || {};
        const hasConnection = !!cfg.active_connection;
        const noConnEl = document.getElementById('query-no-connection');
        const formCard = document.getElementById('query-form-card');

        if (noConnEl) noConnEl.classList.toggle('d-none', hasConnection);
        if (formCard) formCard.classList.toggle('d-none', !hasConnection);

        loadSavedQueries();
        loadQueryHistory();

        if (queryTabInitialized || !hasConnection) return;
        queryTabInitialized = true;

        $$('[name="query_type"]').forEach((radio) => radio.addEventListener('change', onQueryTypeChange));
        $$('[name="placeholder_type"]').forEach((radio) => radio.addEventListener('change', () => onSqlInput(false)));

        const sqlEl = document.getElementById('query-sql');
        if (sqlEl) sqlEl.addEventListener('input', () => onSqlInput(true));

        const btnReset = document.getElementById('btn-query-reset');
        if (btnReset) btnReset.addEventListener('click', resetQueryForm);

        const btnExecute = document.getElementById('btn-query-execute');
        if (btnExecute) btnExecute.addEventListener('click', executeQuery);

        const btnSave = document.getElementById('btn-query-save');
        if (btnSave) btnSave.addEventListener('click', saveQuery);

        const btnSaveDraft = document.getElementById('btn-query-save-draft');
        if (btnSaveDraft) btnSaveDraft.addEventListener('click', () => openSaveDraftModal('query'));

        const btnSaveDraftConfirm = document.getElementById('btn-save-draft-confirm');
        if (btnSaveDraftConfirm && !btnSaveDraftConfirm._draftBound) {
            btnSaveDraftConfirm._draftBound = true;
            btnSaveDraftConfirm.addEventListener('click', saveDraft);
        }

        const modalDraftAliasInput = document.getElementById('modal-draft-alias');
        if (modalDraftAliasInput && !modalDraftAliasInput._draftBound) {
            modalDraftAliasInput._draftBound = true;
            modalDraftAliasInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') saveDraft();
            });
        }
    }

    function resetQueryForm() {
        const sqlEl = document.getElementById('query-sql');
        if (sqlEl) sqlEl.value = '';
        const rawRadio = document.querySelector('[name="query_type"][value="raw"]');
        if (rawRadio) { rawRadio.checked = true; onQueryTypeChange(); }
        const resultsCard = document.getElementById('query-results-card');
        if (resultsCard) resultsCard.classList.add('d-none');
    }

    function onQueryTypeChange() {
        const isPrepared = document.querySelector('[name="query_type"]:checked')?.value === 'prepared';
        const phSection = document.getElementById('placeholder-type-section');
        const paramsSection = document.getElementById('query-params-section');
        if (phSection) phSection.style.display = isPrepared ? '' : 'none';
        if (isPrepared) {
            onSqlInput(true);
        } else {
            const paramsFields = document.getElementById('query-param-fields');
            if (paramsFields) paramsFields.innerHTML = '';
            if (paramsSection) paramsSection.style.display = 'none';
        }
    }

    function detectPlaceholderType(sql) {
        if (/:([a-zA-Z_][a-zA-Z0-9_]*)/.test(sql)) return 'named';
        if (/\$\d+/.test(sql)) return 'numbered';
        if (/\?/.test(sql)) return 'question';
        return null;
    }

    function onSqlInput(autoDetect = false) {
        const isPrepared = document.querySelector('[name="query_type"]:checked')?.value === 'prepared';
        if (!isPrepared) return;
        const sql = document.getElementById('query-sql')?.value || '';
        if (autoDetect) {
            const detected = detectPlaceholderType(sql);
            if (detected) {
                const phInput = document.querySelector(`[name="placeholder_type"][value="${detected}"]`);
                if (phInput && !phInput.checked) phInput.checked = true;
            }
        }
        const phType = document.querySelector('[name="placeholder_type"]:checked')?.value || 'question';
        renderParamFields(parsePlaceholders(sql, phType));
    }

    function parsePlaceholders(sql, phType) {
        if (phType === 'question') {
            const result = [];
            let inSingle = false, inDouble = false, count = 0;
            for (let i = 0; i < sql.length; i++) {
                const c = sql[i];
                if (c === "'" && !inDouble) inSingle = !inSingle;
                else if (c === '"' && !inSingle) inDouble = !inDouble;
                else if (c === '?' && !inSingle && !inDouble) {
                    count++;
                    result.push({ key: `param${count}`, label: `?${count}` });
                }
            }
            return result;
        }
        if (phType === 'numbered') {
            const nums = new Set();
            let m;
            const re = /\$(\d+)/g;
            while ((m = re.exec(sql)) !== null) nums.add(parseInt(m[1], 10));
            return [...nums].sort((a, b) => a - b).map((n) => ({ key: `$${n}`, label: `$${n}` }));
        }
        if (phType === 'named') {
            const names = new Set();
            let m;
            const re = /:([a-zA-Z_][a-zA-Z0-9_]*)/g;
            while ((m = re.exec(sql)) !== null) names.add(m[1]);
            return [...names].map((n) => ({ key: `:${n}`, label: `:${n}` }));
        }
        return [];
    }

    function renderParamFields(placeholders) {
        const container = document.getElementById('query-param-fields');
        const section = document.getElementById('query-params-section');
        if (!container) return;
        container.innerHTML = '';
        if (!placeholders.length) {
            if (section) section.style.display = 'none';
            return;
        }
        if (section) section.style.display = '';
        placeholders.forEach((ph) => {
            const col = document.createElement('div');
            col.className = 'col-12 col-sm-6 col-md-4';
            const label = document.createElement('label');
            label.className = 'form-label small fw-semibold';
            label.textContent = ph.label;
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.dataset.phKey = ph.key;
            input.placeholder = `Value for ${ph.label}`;
            col.appendChild(label);
            col.appendChild(input);
            container.appendChild(col);
        });
    }

    function getQueryParams() {
        return $$('#query-param-fields input').map((inp) => inp.value);
    }

    function executeQuery() {
        const sql = (document.getElementById('query-sql')?.value || '').trim();
        if (!sql) {
            showAlert('warning', L.fill_required || 'Enter a SQL query.');
            return;
        }
        const queryType = document.querySelector('[name="query_type"]:checked')?.value || 'raw';
        const params = queryType === 'prepared' ? getQueryParams() : [];

        const btn = document.getElementById('btn-query-execute');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${L.query_executing || 'Executing...'}`;
        }

        const phType = document.querySelector('[name="placeholder_type"]:checked')?.value || 'question';
        const fd = new FormData();
        fd.append('action', 'execute_query');
        fd.append('sql', sql);
        fd.append('query_type', queryType);
        fd.append('placeholder_type', phType);
        params.forEach((v, i) => fd.append(`params[${i}]`, v));

        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    renderQueryResults(res);
                    loadQueryHistory();
                    showAlert('success', L.query_success || 'Query executed successfully.');
                } else {
                    showAlert('danger', res.error || (L.query_error || 'Query error.'));
                }
            })
            .catch(() => showAlert('danger', L.error_network || 'Network error.'))
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="bi bi-play-fill"></i> ${L.query_execute || 'Execute'}`;
                }
            });
    }

    function renderQueryResults(res) {
        const card = document.getElementById('query-results-card');
        const wrap = document.getElementById('query-results-table-wrap');
        const meta = document.getElementById('query-results-meta');
        if (!card || !wrap) return;
        card.classList.remove('d-none');

        if (res.rows !== undefined) {
            const count = res.count || 0;
            if (meta) meta.textContent = `${count} ${L.query_rows || 'rows'} (${res.duration_ms}ms)`;
            if (count === 0) {
                wrap.innerHTML = `<p class="text-muted p-3 mb-0">${escapeHtml(L.query_no_rows || 'No rows returned.')}</p>`;
                return;
            }
            const cols = res.columns || [];
            let html = '<table class="table table-sm table-bordered table-hover table-striped mb-0"><thead><tr>';
            cols.forEach((c) => { html += `<th>${escapeHtml(c)}</th>`; });
            html += '</tr></thead><tbody>';
            res.rows.forEach((row) => {
                html += '<tr>';
                cols.forEach((c) => { html += `<td>${escapeHtml(String(row[c] ?? ''))}</td>`; });
                html += '</tr>';
            });
            html += '</tbody></table>';
            wrap.innerHTML = html;
        } else {
            const affected = res.affected_rows ?? 0;
            if (meta) meta.textContent = `${affected} ${L.query_affected_rows || 'affected rows'} (${res.duration_ms}ms)`;
            wrap.innerHTML = `<p class="text-muted p-3 mb-0">${escapeHtml(L.query_affected_rows || 'Affected rows')}: <strong>${affected}</strong></p>`;
        }
    }

    function saveQuery() {
        const sql = (document.getElementById('query-sql')?.value || '').trim();
        if (!sql) {
            showAlert('warning', L.fill_required || 'Enter a SQL query first.');
            return;
        }
        const autoTitle = 'save_' + Date.now();
        const queryType = document.querySelector('[name="query_type"]:checked')?.value || 'raw';
        const phType = document.querySelector('[name="placeholder_type"]:checked')?.value || 'question';
        const params = queryType === 'prepared' ? getQueryParams() : [];

        const fd = new FormData();
        fd.append('action', 'save_query');
        fd.append('name', autoTitle);
        fd.append('sql', sql);
        fd.append('query_type', queryType);
        fd.append('placeholder_type', phType);
        params.forEach((v, i) => fd.append(`params[${i}]`, v));

        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    showAlert('success', L.query_saved || 'Query saved.');
                    loadSavedQueries();
                } else {
                    showAlert('danger', res.error || 'Error saving query.');
                }
            })
            .catch(() => showAlert('danger', L.error_network || 'Network error.'));
    }

    let draftSource = 'query';

    function openSaveDraftModal(source) {
        draftSource = source || 'query';
        const sqlElId = draftSource === 'builder' ? 'qb-sql' : 'query-sql';
        const sql = (document.getElementById(sqlElId)?.value || '').trim();
        if (!sql) {
            showAlert('warning', L.fill_required || 'Enter a SQL query first.');
            return;
        }
        const modalEl = document.getElementById('saveDraftModal');
        if (!modalEl) return;
        const aliasInput = document.getElementById('modal-draft-alias');
        if (aliasInput) aliasInput.value = '';
        new bootstrap.Modal(modalEl).show();
        setTimeout(() => aliasInput?.focus(), 350);
    }

    function saveDraft() {
        const sqlElId = draftSource === 'builder' ? 'qb-sql' : 'query-sql';
        const sql = (document.getElementById(sqlElId)?.value || '').trim();
        if (!sql) {
            showAlert('warning', L.fill_required || 'Enter a SQL query first.');
            return;
        }
        const alias = (document.getElementById('modal-draft-alias')?.value || '').trim();

        const fd = new FormData();
        fd.append('action', 'save_draft');
        fd.append('alias', alias);
        fd.append('sql', sql);
        fd.append('source', draftSource);

        if (draftSource === 'builder') {
            const qbCode = (document.getElementById('qb-output')?.textContent || '').trim();
            fd.append('qb_code', qbCode === '--' ? '' : qbCode);
        } else {
            const queryType = document.querySelector('[name="query_type"]:checked')?.value || 'raw';
            const phType = document.querySelector('[name="placeholder_type"]:checked')?.value || 'question';
            const params = queryType === 'prepared' ? getQueryParams() : [];
            fd.append('query_type', queryType);
            fd.append('placeholder_type', phType);
            params.forEach((v, i) => fd.append(`params[${i}]`, v));
        }

        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    const modalEl = document.getElementById('saveDraftModal');
                    const instance = bootstrap.Modal.getInstance(modalEl);
                    if (instance) instance.hide();
                    showAlert('success', L.draft_saved || 'Draft saved.');
                    if (draftSource === 'builder') {
                        loadSavedBuilders();
                    } else {
                        loadSavedQueries();
                    }
                } else {
                    showAlert('danger', res.error || 'Error saving draft.');
                }
            })
            .catch(() => showAlert('danger', L.error_network || 'Network error.'));
    }

    let queriesAllItems = [];
    let queriesPage = 1;

    function loadSavedQueries() {
        Promise.all([
            fetch('api.php?action=get_queries').then((r) => r.json()).catch(() => ({ queries: [] })),
            fetch('api.php?action=get_drafts&source=query').then((r) => r.json()).catch(() => ({ drafts: [] })),
        ]).then(([qRes, dRes]) => {
            const queries = (qRes.queries || []).map((q) => ({ ...q, _isDraft: false }));
            const drafts = (dRes.drafts || []).map((d) => ({ ...d, _isDraft: true }));
            queriesAllItems = [...queries, ...drafts].sort((a, b) => {
                const ta = a.updated_at || a.created_at || '';
                const tb = b.updated_at || b.created_at || '';
                return tb.localeCompare(ta);
            });
            queriesPage = 1;
            renderSavedQueries();
        });
    }

    function renderSavedQueries() {
        const container = document.getElementById('saved-queries-list');
        if (!container) return;
        container.innerHTML = '';
        document.getElementById('saved-queries-paginator')?.remove();
        if (!queriesAllItems.length) {
            container.innerHTML = `<p class="text-muted small p-3 mb-0">${escapeHtml(L.query_saved_empty || 'No saved queries.')}</p>`;
            return;
        }
        const pageSize = window.WIZARD_SETTINGS?.queries_page_size || 10;
        const total = queriesAllItems.length;
        const start = (queriesPage - 1) * pageSize;
        const pageItems = queriesAllItems.slice(start, Math.min(start + pageSize, total));
        pageItems.forEach((q) => {
            const isDraft = q._isDraft === true;
            const displayName = isDraft ? (q.alias || q.name || '') : (q.title || q.name || '');
            const badgeHtml = isDraft
                ? `<span class="badge bg-warning text-dark me-1">${escapeHtml(L.draft_badge || 'Draft')}</span>`
                : '';
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex align-items-center justify-content-between gap-2 py-2';
            item.innerHTML = `
                <div class="text-truncate" style="min-width:0;">
                    <span class="fw-semibold">${badgeHtml}${escapeHtml(displayName)}</span>
                    <small class="text-muted d-block text-truncate font-monospace" style="max-width:100%;">${escapeHtml(q.sql)}</small>
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                    <button class="btn btn-outline-primary btn-sm" data-load-id="${escapeHtml(q.id)}" title="${escapeHtml(L.query_load || 'Load')}"><i class="bi bi-arrow-up-circle"></i></button>
                    <button class="btn btn-outline-danger btn-sm" data-del-id="${escapeHtml(q.id)}" title="${escapeHtml(L.delete || 'Delete')}"><i class="bi bi-trash"></i></button>
                </div>`;
            item.querySelector('[data-load-id]').addEventListener('click', () => isDraft ? loadDraft(q) : loadSavedQuery(q));
            item.querySelector('[data-del-id]').addEventListener('click', () => isDraft ? deleteDraft(q.id) : deleteSavedQuery(q.id));
            container.appendChild(item);
        });
        const nav = buildPaginationNav(total, pageSize, queriesPage, (p) => { queriesPage = p; renderSavedQueries(); });
        if (nav) { nav.id = 'saved-queries-paginator'; container.parentElement.appendChild(nav); }
    }

    function fillParamValues(params) {
        if (!params || !params.length) return;
        const inputs = $$('#query-param-fields input');
        inputs.forEach((inp, i) => {
            if (params[i] !== undefined) inp.value = params[i];
        });
    }

    function loadSavedQuery(q) {
        const sqlEl = document.getElementById('query-sql');
        if (sqlEl) sqlEl.value = q.sql;
        const typeInput = document.querySelector(`[name="query_type"][value="${q.query_type || 'raw'}"]`);
        if (typeInput) { typeInput.checked = true; onQueryTypeChange(); }
        if (q.query_type === 'prepared') {
            if (q.placeholder_type) {
                const phInput = document.querySelector(`[name="placeholder_type"][value="${q.placeholder_type}"]`);
                if (phInput) phInput.checked = true;
                onSqlInput(false);
            } else {
                onSqlInput(true);
            }
            fillParamValues(q.params);
        }
        showAlert('success', L.query_loaded || 'Query loaded.');
    }

    function deleteSavedQuery(id) {
        const fd = new FormData();
        fd.append('action', 'delete_query');
        fd.append('query_id', id);
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    showAlert('success', L.query_deleted || 'Query deleted.');
                    loadSavedQueries();
                } else {
                    showAlert('danger', res.error || 'Error deleting query.');
                }
            })
            .catch(() => showAlert('danger', L.error_network || 'Network error.'));
    }

    function loadDraft(d) {
        const sqlEl = document.getElementById('query-sql');
        if (sqlEl) sqlEl.value = d.sql;
        const typeInput = document.querySelector(`[name="query_type"][value="${d.query_type || 'raw'}"]`);
        if (typeInput) { typeInput.checked = true; onQueryTypeChange(); }
        if (d.query_type === 'prepared') {
            if (d.placeholder_type) {
                const phInput = document.querySelector(`[name="placeholder_type"][value="${d.placeholder_type}"]`);
                if (phInput) phInput.checked = true;
                onSqlInput(false);
            } else {
                onSqlInput(true);
            }
            fillParamValues(d.params);
        }
        showAlert('info', L.draft_loaded || 'Draft loaded.');
    }

    function deleteDraft(id) {
        const fd = new FormData();
        fd.append('action', 'delete_draft');
        fd.append('draft_id', id);
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    showAlert('success', L.draft_deleted || 'Draft deleted.');
                    loadSavedQueries();
                } else {
                    showAlert('danger', res.error || 'Error deleting draft.');
                }
            })
            .catch(() => showAlert('danger', L.error_network || 'Network error.'));
    }

    let historyAllItems = [];
    let historyPage = 1;

    function loadQueryHistory() {
        fetch('api.php?action=get_query_history')
            .then((r) => r.json())
            .then((res) => {
                historyAllItems = res.history || [];
                historyPage = 1;
                renderQueryHistory();
            })
            .catch(() => {});
    }

    function renderQueryHistory() {
        const container = document.getElementById('query-history-list');
        if (!container) return;
        container.innerHTML = '';
        document.getElementById('query-history-paginator')?.remove();
        if (!historyAllItems.length) {
            container.innerHTML = `<p class="text-muted small p-3 mb-0">${escapeHtml(L.query_history_empty || 'No history.')}</p>`;
            return;
        }
        const pageSize = window.WIZARD_SETTINGS?.history_page_size || 20;
        const total = historyAllItems.length;
        const start = (historyPage - 1) * pageSize;
        const pageItems = historyAllItems.slice(start, Math.min(start + pageSize, total));
        pageItems.forEach((h) => {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action py-2';
            item.style.cursor = 'pointer';
            item.innerHTML = `
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <span class="font-monospace text-truncate small">${escapeHtml(h.sql)}</span>
                    <span class="badge bg-secondary flex-shrink-0">${h.duration_ms}ms</span>
                </div>
                <small class="text-muted">${escapeHtml(h.executed_at)} &middot; ${h.row_count} ${escapeHtml(L.query_rows || 'rows')}</small>`;
            item.addEventListener('click', () => {
                const sqlEl = document.getElementById('query-sql');
                if (sqlEl) sqlEl.value = h.sql;
                if (h.query_type) {
                    const typeInput = document.querySelector(`[name="query_type"][value="${h.query_type}"]`);
                    if (typeInput) { typeInput.checked = true; onQueryTypeChange(); }
                }
                if (h.query_type === 'prepared') {
                    if (h.placeholder_type) {
                        const phInput = document.querySelector(`[name="placeholder_type"][value="${h.placeholder_type}"]`);
                        if (phInput) phInput.checked = true;
                        onSqlInput(false);
                    } else {
                        onSqlInput(true);
                    }
                    fillParamValues(h.params);
                }
                showAlert('info', L.query_loaded || 'Query loaded.');
            });
            container.appendChild(item);
        });
        const nav = buildPaginationNav(total, pageSize, historyPage, (p) => { historyPage = p; renderQueryHistory(); });
        if (nav) { nav.id = 'query-history-paginator'; container.parentElement.appendChild(nav); }
    }

    // ── Builder Tab (Step 3) ────────────────────────────────────────────────

    let builderTabInitialized = false;
    let buildersAllItems = [];
    let buildersPage = 1;
    let builderHistoryAllItems = [];
    let builderHistoryPage = 1;
    let qbDebounceTimer = null;

    function initBuilderTab() {
        const cfg = window.WIZARD_INITIAL_CONFIG || {};
        const hasConnection = !!cfg.active_connection;
        const noConnEl = document.getElementById('builder-no-connection');
        const formCard = document.getElementById('qb-form-card');
        if (noConnEl) noConnEl.classList.toggle('d-none', hasConnection);
        if (formCard) formCard.classList.toggle('d-none', !hasConnection);

        loadSavedBuilders();
        loadBuilderHistory();

        if (builderTabInitialized) return;
        builderTabInitialized = true;

        const qbSql = document.getElementById('qb-sql');
        if (qbSql) {
            qbSql.addEventListener('input', () => {
                clearTimeout(qbDebounceTimer);
                qbDebounceTimer = setTimeout(convertSqlToQb, 400);
            });
        }
        const btnQbReset = document.getElementById('btn-qb-reset');
        if (btnQbReset) btnQbReset.addEventListener('click', resetBuilderForm);

        const btnQbSaveDraft = document.getElementById('btn-qb-save-draft');
        if (btnQbSaveDraft) btnQbSaveDraft.addEventListener('click', () => openSaveDraftModal('builder'));

        const btnExecute = document.getElementById('btn-qb-execute');
        if (btnExecute) btnExecute.addEventListener('click', executeBuilder);
        const btnSave = document.getElementById('btn-qb-save');
        if (btnSave) btnSave.addEventListener('click', saveBuilder);

        const btnSaveDraftConfirm = document.getElementById('btn-save-draft-confirm');
        if (btnSaveDraftConfirm && !btnSaveDraftConfirm._draftBound) {
            btnSaveDraftConfirm._draftBound = true;
            btnSaveDraftConfirm.addEventListener('click', saveDraft);
        }

        const modalDraftAliasInput = document.getElementById('modal-draft-alias');
        if (modalDraftAliasInput && !modalDraftAliasInput._draftBound) {
            modalDraftAliasInput._draftBound = true;
            modalDraftAliasInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') saveDraft();
            });
        }
    }

    function resetBuilderForm() {
        const sqlEl = document.getElementById('qb-sql');
        if (sqlEl) sqlEl.value = '';
        const outputEl = document.getElementById('qb-output');
        if (outputEl) outputEl.textContent = '--';
        const resultsCard = document.getElementById('qb-results-card');
        if (resultsCard) resultsCard.classList.add('d-none');
    }

    function convertSqlToQb() {
        const sql = (document.getElementById('qb-sql')?.value || '').trim();
        const outputEl = document.getElementById('qb-output');
        const spinner = document.getElementById('qb-converting');
        if (!sql) { if (outputEl) outputEl.textContent = '--'; return; }
        if (spinner) spinner.classList.remove('d-none');
        const fd = new FormData();
        fd.append('action', 'convert_to_qb');
        fd.append('sql', sql);
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => { if (outputEl) outputEl.textContent = res.qb_code || '--'; })
            .catch(() => { if (outputEl) outputEl.textContent = '--'; })
            .finally(() => { if (spinner) spinner.classList.add('d-none'); });
    }

    function executeBuilder() {
        const sql = (document.getElementById('qb-sql')?.value || '').trim();
        const qbCode = (document.getElementById('qb-output')?.textContent || '').trim();
        if (!sql) { showAlert('warning', L.builder_no_sql || 'Enter SQL to convert.'); return; }
        const btnEl = document.getElementById('btn-qb-execute');
        if (btnEl) { btnEl.disabled = true; btnEl.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${escapeHtml(L.builder_executing || 'Executing...')}`; }
        const fd = new FormData();
        fd.append('action', 'execute_builder');
        fd.append('sql', sql);
        fd.append('qb_code', qbCode !== '--' ? qbCode : '');
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    renderBuilderResults(res);
                    loadBuilderHistory();
                    showAlert('success', L.builder_executed || 'Builder executed.');
                } else {
                    showAlert('danger', res.error || L.builder_error || 'Builder error.');
                }
            })
            .catch(() => showAlert('danger', L.error_network || 'Network error.'))
            .finally(() => {
                if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = `<i class="bi bi-play-fill"></i> ${escapeHtml(L.builder_execute || 'Execute')}`; }
            });
    }

    function renderBuilderResults(res) {
        const card = document.getElementById('qb-results-card');
        const wrap = document.getElementById('qb-results-table-wrap');
        const meta = document.getElementById('qb-results-meta');
        if (!card || !wrap) return;
        card.classList.remove('d-none');
        if (res.rows) {
            if (meta) meta.textContent = `${res.count} ${L.builder_rows || 'rows'} — ${res.duration_ms}ms`;
            if (!res.rows.length) { wrap.innerHTML = `<p class="text-muted p-3 mb-0">${escapeHtml(L.builder_no_rows || 'No rows returned.')}</p>`; return; }
            let tbl = '<table class="table table-sm table-bordered table-hover table-striped mb-0"><thead><tr>';
            res.columns.forEach((c) => { tbl += `<th>${escapeHtml(c)}</th>`; });
            tbl += '</tr></thead><tbody>';
            res.rows.forEach((r) => { tbl += '<tr>'; res.columns.forEach((c) => { tbl += `<td>${escapeHtml(String(r[c] ?? ''))}</td>`; }); tbl += '</tr>'; });
            tbl += '</tbody></table>';
            wrap.innerHTML = tbl;
        } else {
            if (meta) meta.textContent = `${res.duration_ms}ms`;
            wrap.innerHTML = `<p class="text-muted p-3 mb-0">${escapeHtml(L.builder_affected_rows || 'Affected rows')}: <strong>${res.affected_rows}</strong></p>`;
        }
    }

    function saveBuilder() {
        const sql = (document.getElementById('qb-sql')?.value || '').trim();
        const qbCode = (document.getElementById('qb-output')?.textContent || '').trim();
        if (!sql) { showAlert('warning', L.builder_no_sql || 'Enter SQL to convert.'); return; }
        const fd = new FormData();
        fd.append('action', 'save_builder');
        fd.append('sql', sql);
        fd.append('qb_code', qbCode !== '--' ? qbCode : '');
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) { showAlert('success', L.builder_saved || 'Builder saved.'); loadSavedBuilders(); }
                else { showAlert('danger', res.error || L.builder_error || 'Builder error.'); }
            })
            .catch(() => showAlert('danger', L.error_network || 'Network error.'));
    }

    function loadSavedBuilders() {
        Promise.all([
            fetch('api.php?action=get_builders').then((r) => r.json()).catch(() => ({ builders: [] })),
            fetch('api.php?action=get_drafts&source=builder').then((r) => r.json()).catch(() => ({ drafts: [] })),
        ]).then(([bRes, dRes]) => {
            const builders = (bRes.builders || []).map((b) => ({ ...b, _isDraft: false }));
            const drafts = (dRes.drafts || []).map((d) => ({ ...d, _isDraft: true }));
            buildersAllItems = [...builders, ...drafts].sort((a, b) => {
                const ta = a.updated_at || a.created_at || '';
                const tb = b.updated_at || b.created_at || '';
                return tb.localeCompare(ta);
            });
            buildersPage = 1;
            renderSavedBuilders();
        });
    }

    function renderSavedBuilders() {
        const container = document.getElementById('saved-builders-list');
        if (!container) return;
        container.innerHTML = '';
        document.getElementById('saved-builders-paginator')?.remove();
        if (!buildersAllItems.length) {
            container.innerHTML = `<p class="text-muted small p-3 mb-0">${escapeHtml(L.builder_saved_empty || 'No saved builders.')}</p>`;
            return;
        }
        const pageSize = window.WIZARD_SETTINGS?.builders_page_size || 10;
        const total = buildersAllItems.length;
        const start = (buildersPage - 1) * pageSize;
        const pageItems = buildersAllItems.slice(start, Math.min(start + pageSize, total));
        pageItems.forEach((b) => {
            const isDraft = b._isDraft === true;
            const displayName = isDraft ? (b.alias || b.name || '') : (b.title || b.name || '');
            const badgeHtml = isDraft
                ? `<span class="badge bg-warning text-dark me-1">${escapeHtml(L.draft_badge || 'Draft')}</span>`
                : '';
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex align-items-center justify-content-between gap-2 py-2';
            item.innerHTML = `
                <div class="text-truncate" style="min-width:0;">
                    <span class="fw-semibold">${badgeHtml}${escapeHtml(displayName)}</span>
                    <small class="text-muted d-block text-truncate font-monospace" style="max-width:100%;">${escapeHtml(b.sql)}</small>
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                    <button class="btn btn-outline-primary btn-sm" data-load-id="${escapeHtml(b.id)}" title="${escapeHtml(L.builder_load || 'Load')}"><i class="bi bi-arrow-up-circle"></i></button>
                    <button class="btn btn-outline-danger btn-sm" data-del-id="${escapeHtml(b.id)}" title="${escapeHtml(L.delete || 'Delete')}"><i class="bi bi-trash"></i></button>
                </div>`;
            item.querySelector('[data-load-id]').addEventListener('click', () => isDraft ? loadBuilderDraft(b) : loadSavedBuilder(b));
            item.querySelector('[data-del-id]').addEventListener('click', () => isDraft ? deleteBuilderDraft(b.id) : deleteBuilder(b.id));
            container.appendChild(item);
        });
        const nav = buildPaginationNav(total, pageSize, buildersPage, (p) => { buildersPage = p; renderSavedBuilders(); });
        if (nav) { nav.id = 'saved-builders-paginator'; container.parentElement.appendChild(nav); }
    }

    function loadSavedBuilder(b) {
        const sqlEl = document.getElementById('qb-sql');
        if (sqlEl) { sqlEl.value = b.sql; convertSqlToQb(); }
        showAlert('info', L.builder_loaded || 'Builder loaded.');
    }

    function loadBuilderDraft(d) {
        const sqlEl = document.getElementById('qb-sql');
        if (sqlEl) { sqlEl.value = d.sql; convertSqlToQb(); }
        showAlert('info', L.draft_loaded || 'Draft loaded.');
    }

    function deleteBuilderDraft(id) {
        const fd = new FormData();
        fd.append('action', 'delete_draft');
        fd.append('draft_id', id);
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) { showAlert('success', L.draft_deleted || 'Draft deleted.'); loadSavedBuilders(); }
                else { showAlert('danger', res.error || 'Error deleting draft.'); }
            })
            .catch(() => showAlert('danger', L.error_network || 'Network error.'));
    }

    function deleteBuilder(id) {
        const fd = new FormData();
        fd.append('action', 'delete_builder');
        fd.append('builder_id', id);
        fetch('api.php', { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) { showAlert('success', L.builder_deleted || 'Builder deleted.'); loadSavedBuilders(); }
            })
            .catch(() => showAlert('danger', L.error_network || 'Network error.'));
    }

    function loadBuilderHistory() {
        fetch('api.php?action=get_builder_history')
            .then((r) => r.json())
            .then((res) => { builderHistoryAllItems = res.history || []; builderHistoryPage = 1; renderBuilderHistory(); })
            .catch(() => {});
    }

    function renderBuilderHistory() {
        const container = document.getElementById('builder-history-list');
        if (!container) return;
        container.innerHTML = '';
        document.getElementById('builder-history-paginator')?.remove();
        if (!builderHistoryAllItems.length) {
            container.innerHTML = `<p class="text-muted small p-3 mb-0">${escapeHtml(L.builder_history_empty || 'No builder history.')}</p>`;
            return;
        }
        const pageSize = window.WIZARD_SETTINGS?.builder_history_page_size || 20;
        const total = builderHistoryAllItems.length;
        const start = (builderHistoryPage - 1) * pageSize;
        const pageItems = builderHistoryAllItems.slice(start, Math.min(start + pageSize, total));
        pageItems.forEach((h) => {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action py-2';
            item.style.cursor = 'pointer';
            item.innerHTML = `
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <span class="font-monospace text-truncate small">${escapeHtml(h.sql)}</span>
                    <span class="badge bg-secondary flex-shrink-0">${h.duration_ms}ms</span>
                </div>
                <small class="text-muted">${escapeHtml(h.executed_at)} &middot; ${h.row_count} ${escapeHtml(L.builder_rows || 'rows')}</small>`;
            item.addEventListener('click', () => {
                const sqlEl = document.getElementById('qb-sql');
                if (sqlEl) { sqlEl.value = h.sql; convertSqlToQb(); }
                showAlert('info', L.builder_loaded || 'Builder loaded.');
            });
            container.appendChild(item);
        });
        const nav = buildPaginationNav(total, pageSize, builderHistoryPage, (p) => { builderHistoryPage = p; renderBuilderHistory(); });
        if (nav) { nav.id = 'builder-history-paginator'; container.parentElement.appendChild(nav); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

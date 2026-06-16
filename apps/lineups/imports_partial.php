<?php
// ─── imports_partial.php ──────────────────────────────────────────────────────
// HTML partial for the Imports tab panel in festivals.php.
// Included inside <div class="tab-panel" id="panel-imports">.
// Assumes $festivals is already populated by festivals.php.
// All AJAX posts target festivals.php (the parent page).
// ─────────────────────────────────────────────────────────────────────────────
?>
<style>
    /* ── Import tab: scoped variables & resets ── */
    #panel-imports {
        --imp-surface:    #161920;
        --imp-surface-2:  #1e2130;
        --imp-border:     #2a2f45;
        --imp-accent:     #f4a01c;
        --imp-accent-dim: #7a5010;
        --imp-danger:     #e8475f;
        --imp-success:    #2ecc8a;
        --imp-text:       #e8eaf2;
        --imp-text-dim:   #7880a0;
        font-family: 'DM Sans', sans-serif;
        font-size: 15px;
    }

    /* ── Card ── */
    #panel-imports .imp-card {
        background: var(--imp-surface);
        border: 1px solid var(--imp-border);
        border-radius: 8px;
        padding: 28px 32px;
        margin-bottom: 24px;
    }
    #panel-imports .imp-card-title {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 18px;
        letter-spacing: 2px;
        color: var(--imp-text-dim);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    #panel-imports .imp-card-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--imp-border);
    }

    /* ── Form controls ── */
    #panel-imports label {
        display: block;
        font-size: 12px;
        font-family: 'DM Mono', monospace;
        color: var(--imp-text-dim);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }
    #panel-imports select {
        width: 100%;
        max-width: 420px;
        background: var(--imp-surface-2);
        color: var(--imp-text);
        border: 1px solid var(--imp-border);
        border-radius: 6px;
        padding: 10px 14px;
        font-family: 'DM Sans', sans-serif;
        font-size: 15px;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%237880a0' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        cursor: pointer;
        transition: border-color .2s;
    }
    #panel-imports select:focus { outline: none; border-color: var(--imp-accent); }

    /* ── Drop zone ── */
    #panel-imports .imp-drop-zone {
        border: 2px dashed var(--imp-border);
        border-radius: 8px;
        padding: 48px 32px;
        text-align: center;
        cursor: pointer;
        transition: border-color .2s, background .2s;
        position: relative;
    }
    #panel-imports .imp-drop-zone:hover,
    #panel-imports .imp-drop-zone.dragover {
        border-color: var(--imp-accent);
        background: rgba(244,160,28,.04);
    }
    #panel-imports .imp-drop-zone.has-file {
        border-color: var(--imp-success);
        background: rgba(46,204,138,.04);
    }
    #panel-imports .imp-drop-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    #panel-imports .imp-drop-icon {
        font-size: 36px;
        margin-bottom: 12px;
        display: block;
    }
    #panel-imports .imp-drop-zone p { color: var(--imp-text-dim); font-size: 14px; }
    #panel-imports .imp-file-name {
        color: var(--imp-success);
        font-family: 'DM Mono', monospace;
        font-size: 14px;
        font-weight: 500;
        margin-top: 8px;
    }

    /* ── Buttons ── */
    #panel-imports .imp-btn-row { display: flex; gap: 12px; margin-top: 24px; }
    #panel-imports .imp-btn {
        padding: 11px 28px;
        border-radius: 6px;
        border: none;
        font-family: 'Bebas Neue', sans-serif;
        font-size: 17px;
        letter-spacing: 2px;
        cursor: pointer;
        transition: opacity .2s, transform .1s;
    }
    #panel-imports .imp-btn:active { transform: scale(.97); }
    #panel-imports .imp-btn:disabled { opacity: .35; cursor: not-allowed; transform: none; }
    #panel-imports .imp-btn-preview {
        background: var(--imp-surface-2);
        color: var(--imp-accent);
        border: 1px solid var(--imp-accent-dim);
    }
    #panel-imports .imp-btn-preview:hover:not(:disabled) { background: rgba(244,160,28,.1); }
    #panel-imports .imp-btn-import { background: var(--imp-accent); color: #0d0f14; }
    #panel-imports .imp-btn-import:hover:not(:disabled) { opacity: .88; }
    #panel-imports .imp-btn-reset {
        background: transparent;
        color: var(--imp-text-dim);
        border: 1px solid var(--imp-border);
        font-family: 'DM Sans', sans-serif;
        font-size: 14px;
        letter-spacing: 0;
    }
    #panel-imports .imp-btn-reset:hover { color: var(--imp-text); border-color: var(--imp-text-dim); }

    /* ── Alerts ── */
    #panel-imports .imp-alert {
        border-radius: 6px;
        padding: 14px 18px;
        font-size: 14px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    #panel-imports .imp-alert-danger  { background: rgba(232,71,95,.12);  border: 1px solid rgba(232,71,95,.3);  color: #f08090; }
    #panel-imports .imp-alert-success { background: rgba(46,204,138,.12); border: 1px solid rgba(46,204,138,.3); color: var(--imp-success); }
    #panel-imports .imp-alert-info    { background: rgba(244,160,28,.10); border: 1px solid rgba(244,160,28,.25); color: var(--imp-accent); }

    /* ── Summary bar ── */
    #panel-imports .imp-summary-bar {
        display: flex;
        gap: 32px;
        padding: 16px 24px;
        background: var(--imp-surface-2);
        border: 1px solid var(--imp-border);
        border-radius: 6px;
        margin-bottom: 20px;
    }
    #panel-imports .imp-summary-stat { text-align: center; }
    #panel-imports .imp-summary-stat .num {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 32px;
        color: var(--imp-accent);
        line-height: 1;
    }
    #panel-imports .imp-summary-stat .lbl {
        font-size: 11px;
        font-family: 'DM Mono', monospace;
        color: var(--imp-text-dim);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 4px;
    }
    #panel-imports .imp-summary-stat.danger .num { color: var(--imp-danger); }
    #panel-imports .imp-summary-stat.ok .num    { color: var(--imp-success); }

    /* ── Error list ── */
    #panel-imports .imp-error-list {
        background: rgba(232,71,95,.07);
        border: 1px solid rgba(232,71,95,.2);
        border-radius: 6px;
        padding: 16px 20px;
        margin-bottom: 20px;
    }
    #panel-imports .imp-error-list h4 {
        color: var(--imp-danger);
        font-size: 13px;
        font-family: 'DM Mono', monospace;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }
    #panel-imports .imp-error-list ul { list-style: none; }
    #panel-imports .imp-error-list li {
        font-size: 13px;
        color: #f08090;
        padding: 3px 0;
        border-bottom: 1px solid rgba(232,71,95,.1);
    }
    #panel-imports .imp-error-list li:last-child { border-bottom: none; }
    #panel-imports .imp-error-list li::before { content: '✕  '; }

    /* ── Preview table ── */
    #panel-imports .imp-table-wrap {
        overflow-x: auto;
        border-radius: 6px;
        border: 1px solid var(--imp-border);
    }
    #panel-imports table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    #panel-imports thead tr { background: var(--imp-surface-2); }
    #panel-imports thead th {
        padding: 10px 14px;
        text-align: left;
        font-family: 'DM Mono', monospace;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--imp-text-dim);
        white-space: nowrap;
        border-bottom: 1px solid var(--imp-border);
    }
    #panel-imports tbody tr {
        border-bottom: 1px solid rgba(42,47,69,.6);
        transition: background .15s;
    }
    #panel-imports tbody tr:hover { background: var(--imp-surface-2); }
    #panel-imports tbody tr.row-error { background: rgba(232,71,95,.08); }
    #panel-imports tbody tr.row-error:hover { background: rgba(232,71,95,.13); }
    #panel-imports tbody td { padding: 9px 14px; color: var(--imp-text); white-space: nowrap; }
    #panel-imports tbody td.mono { font-family: 'DM Mono', monospace; color: var(--imp-text-dim); }

    #panel-imports .imp-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-family: 'DM Mono', monospace;
        font-weight: 500;
    }
    #panel-imports .badge-want { background: rgba(244,160,28,.2); color: var(--imp-accent); }
    #panel-imports .badge-need { background: rgba(46,204,138,.2); color: var(--imp-success); }
    #panel-imports .badge-err  { background: rgba(232,71,95,.2);  color: var(--imp-danger);  }

    #panel-imports .imp-stage-pill {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-family: 'DM Mono', monospace;
        background: var(--imp-surface-2);
        border: 1px solid var(--imp-border);
        color: var(--imp-text-dim);
    }

    /* ── Spinner / loader ── */
    #imp-loader {
        position: fixed;
        inset: 0;
        background: rgba(13,15,20,.75);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999;
        backdrop-filter: blur(2px);
    }
    #imp-loader .loader-box {
        background: var(--card-bg, #161920);
        border: 1px solid var(--border, #2a2f45);
        border-radius: 10px;
        padding: 32px 48px;
        text-align: center;
    }
    #imp-loader .big-spin {
        width: 40px; height: 40px;
        border: 3px solid rgba(244,160,28,.2);
        border-top-color: #f4a01c;
        border-radius: 50%;
        animation: imp-spin .8s linear infinite;
        margin: 0 auto 16px;
    }
    #imp-loader .loader-box p {
        font-family: 'DM Mono', monospace;
        font-size: 13px;
        color: #7880a0;
    }
    @keyframes imp-spin { to { transform: rotate(360deg); } }

    /* ── Confirm modal ── */
    #imp-confirm-modal {
        position: fixed;
        inset: 0;
        background: rgba(13,15,20,.80);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(3px);
    }
    #imp-confirm-modal .modal-box {
        background: var(--card-bg, #161920);
        border: 1px solid var(--border, #2a2f45);
        border-radius: 10px;
        padding: 36px 40px;
        max-width: 480px;
        width: 90%;
    }
    #imp-confirm-modal .modal-box h3 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 22px;
        letter-spacing: 2px;
        color: #e8475f;
        margin-bottom: 14px;
    }
    #imp-confirm-modal .modal-box p {
        font-size: 14px;
        color: #7880a0;
        line-height: 1.6;
        margin-bottom: 8px;
    }
    #imp-confirm-modal .modal-box p strong { color: #e8eaf2; }
    #imp-confirm-modal .modal-divider { border: none; border-top: 1px solid var(--border, #2a2f45); margin: 24px 0; }
    #imp-confirm-modal .modal-btn-row { display: flex; gap: 12px; justify-content: flex-end; }

    #panel-imports .imp-hidden { display: none !important; }

    /* ── Bottom panel toggle bar ── */
    #panel-imports .imp-panel-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 0;
        align-items: center;
    }
    #panel-imports .imp-panel-btn {
        padding: 10px 24px;
        border-radius: 6px 6px 0 0;
        border: 1px solid var(--imp-border);
        border-bottom: none;
        background: var(--imp-surface);
        color: var(--imp-text-dim);
        font-family: 'Bebas Neue', sans-serif;
        font-size: 16px;
        letter-spacing: 2px;
        cursor: pointer;
        transition: background .2s, color .2s, border-color .2s;
        position: relative;
        top: 1px;
    }
    #panel-imports .imp-panel-btn:hover:not(:disabled) {
        background: var(--imp-surface-2);
        color: var(--imp-text);
    }
    #panel-imports .imp-panel-btn.active {
        background: var(--imp-surface-2);
        color: var(--imp-accent);
        border-color: var(--imp-border);
        border-bottom-color: var(--imp-surface-2);
    }
    #panel-imports .imp-panel-btn:disabled {
        opacity: .35;
        cursor: not-allowed;
    }
    #panel-imports .imp-panel-btn-reset {
        margin-left: auto;
        border-radius: 6px;
        border-bottom: 1px solid var(--imp-border);
        top: 0;
        font-family: 'DM Sans', sans-serif;
        font-size: 14px;
        letter-spacing: 0;
        color: var(--imp-text-dim);
    }
    #panel-imports .imp-panel-btn-reset:hover {
        color: var(--imp-text);
        border-color: var(--imp-text-dim);
    }
    #panel-imports #imp-bottom-panel {
        border: 1px solid var(--imp-border);
        border-radius: 0 6px 6px 6px;
        background: var(--imp-surface-2);
        min-height: 0;
    }
    #panel-imports #imp-bottom-panel > .imp-card,
    #panel-imports #imp-bottom-panel > #imp-preview-section {
        margin-bottom: 0;
        border: none;
        border-radius: 0;
        background: transparent;
    }

    /* ── Import Logic section ── */
    #imp-logic-card .imp-logic-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    @media (max-width: 700px) {
        #imp-logic-card .imp-logic-grid { grid-template-columns: 1fr; }
    }
    #imp-logic-card .imp-logic-full { grid-column: 1 / -1; }

    /* Column map table */
    #imp-logic-card .imp-col-map-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    #imp-logic-card .imp-col-map-table th {
        font-family: 'DM Mono', monospace;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--imp-text-dim);
        padding: 0 0 10px 0;
        text-align: left;
        border-bottom: 1px solid var(--imp-border);
    }
    #imp-logic-card .imp-col-map-table td {
        padding: 8px 8px 0 0;
        vertical-align: middle;
    }
    #imp-logic-card .imp-col-letter {
        font-family: 'DM Mono', monospace;
        font-size: 13px;
        color: var(--imp-accent);
        background: rgba(244,160,28,.08);
        border: 1px solid rgba(244,160,28,.2);
        border-radius: 4px;
        padding: 6px 10px;
        width: 42px;
        text-align: center;
        display: inline-block;
    }
    #imp-logic-card .imp-col-map-table select,
    #imp-logic-card .imp-col-map-table input[type="text"] {
        width: 100%;
        background: var(--imp-surface-2);
        color: var(--imp-text);
        border: 1px solid var(--imp-border);
        border-radius: 5px;
        padding: 7px 10px;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        box-sizing: border-box;
        max-width: none;
    }
    #imp-logic-card .imp-col-map-table select:focus,
    #imp-logic-card .imp-col-map-table input[type="text"]:focus {
        outline: none;
        border-color: var(--imp-accent);
    }
    #imp-logic-card .imp-col-map-table select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%237880a0' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
    }

    /* Tag inputs */
    #imp-logic-card .imp-tag-input-wrap {
        background: var(--imp-surface-2);
        border: 1px solid var(--imp-border);
        border-radius: 6px;
        padding: 8px 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
        cursor: text;
        min-height: 44px;
        transition: border-color .2s;
    }
    #imp-logic-card .imp-tag-input-wrap:focus-within { border-color: var(--imp-accent); }
    #imp-logic-card .imp-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(244,160,28,.12);
        border: 1px solid rgba(244,160,28,.25);
        color: var(--imp-accent);
        border-radius: 4px;
        padding: 3px 8px;
        font-family: 'DM Mono', monospace;
        font-size: 12px;
    }
    #imp-logic-card .imp-tag .imp-tag-x {
        cursor: pointer;
        color: var(--imp-text-dim);
        font-size: 14px;
        line-height: 1;
        padding: 0 1px;
    }
    #imp-logic-card .imp-tag .imp-tag-x:hover { color: var(--imp-danger); }
    #imp-logic-card .imp-tag-input-wrap input[type="text"] {
        background: transparent;
        border: none;
        outline: none;
        color: var(--imp-text);
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        min-width: 100px;
        flex: 1;
        padding: 2px 4px;
        max-width: none;
    }

    /* ── Stage format rows ── */
    #imp-logic-card .imp-stage-rows { display: flex; flex-direction: column; gap: 10px; }

    #imp-logic-card .imp-stage-row {
        background: var(--imp-surface-2);
        border: 1px solid var(--imp-border);
        border-radius: 6px;
        padding: 14px 16px;
    }

    /* Main row: all fields side by side + delete btn */
    #imp-logic-card .imp-stage-row-grid {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        flex-wrap: nowrap;
    }

    /* Each labeled field cell */
    #imp-logic-card .imp-stage-field {
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-width: 0;
    }
    #imp-logic-card .imp-stage-field.sf-name  { flex: 2 1 140px; }
    #imp-logic-card .imp-stage-field.sf-order { flex: 0 0 58px; }
    #imp-logic-card .imp-stage-field.sf-hex   { flex: 1.5 1 130px; }
    #imp-logic-card .imp-stage-field.sf-dim   { flex: 2 1 180px; }
    #imp-logic-card .imp-stage-field.sf-glow  { flex: 2 1 180px; }

    #imp-logic-card .imp-stage-field-label {
        font-family: 'DM Mono', monospace;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--imp-text-dim);
        white-space: nowrap;
    }

    /* Stage name text */
    #imp-logic-card .imp-stage-name-display {
        background: rgba(244,160,28,.08);
        border: 1px solid rgba(244,160,28,.25);
        border-radius: 5px;
        padding: 7px 10px;
        font-family: 'DM Mono', monospace;
        font-size: 13px;
        color: var(--imp-accent);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        height: 36px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
    }

    /* Order number input */
    #imp-logic-card .imp-stage-row input[type="number"] {
        background: var(--imp-surface);
        border: 1px solid var(--imp-border);
        border-radius: 5px;
        padding: 7px 6px;
        font-family: 'DM Mono', monospace;
        font-size: 13px;
        color: var(--imp-text);
        width: 100%;
        height: 36px;
        box-sizing: border-box;
        text-align: center;
    }
    #imp-logic-card .imp-stage-row input[type="number"]:focus { outline: none; border-color: var(--imp-accent); }

    /* ── Hex field: color swatch + text value ── */
    #imp-logic-card .imp-sf-hex-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--imp-surface);
        border: 1px solid var(--imp-border);
        border-radius: 5px;
        padding: 0 10px;
        height: 36px;
        box-sizing: border-box;
        transition: border-color .2s;
        cursor: pointer;
    }
    #imp-logic-card .imp-sf-hex-wrap:focus-within { border-color: var(--imp-accent); }
    #imp-logic-card .imp-sf-hex-wrap input[type="color"] {
        width: 20px;
        height: 20px;
        border: none;
        border-radius: 3px;
        background: none;
        cursor: pointer;
        padding: 0;
        flex-shrink: 0;
    }
    #imp-logic-card .imp-sf-hex-text {
        font-family: 'DM Mono', monospace;
        font-size: 12px;
        color: var(--imp-text-dim);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
        flex: 1;
    }

    /* ── Rgba field: color swatch + full rgba text (Option C) ── */
    #imp-logic-card .imp-sf-rgba-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--imp-surface);
        border: 1px solid var(--imp-border);
        border-radius: 5px;
        padding: 0 10px;
        height: 36px;
        box-sizing: border-box;
        transition: border-color .2s;
        min-width: 0;
    }
    #imp-logic-card .imp-sf-rgba-swatch {
        width: 20px;
        height: 20px;
        border-radius: 3px;
        border: 1px solid rgba(255,255,255,0.1);
        flex-shrink: 0;
    }
    #imp-logic-card .imp-sf-rgba-text {
        font-family: 'DM Mono', monospace;
        font-size: 12px;
        color: var(--imp-text-dim);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
        flex: 1;
    }
    /* Alpha row sits below the pill */
    #imp-logic-card .imp-sf-alpha-row {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 5px;
    }
    #imp-logic-card .imp-sf-alpha-label {
        font-family: 'DM Mono', monospace;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: var(--imp-text-dim);
        flex-shrink: 0;
    }
    #imp-logic-card .imp-sf-alpha-input {
        width: 46px;
        background: var(--imp-surface-2);
        border: 1px solid var(--imp-border);
        border-radius: 4px;
        padding: 2px 5px;
        font-family: 'DM Mono', monospace;
        font-size: 11px;
        color: var(--imp-text);
        text-align: center;
        outline: none;
        box-sizing: border-box;
        height: 22px;
        transition: border-color .2s;
    }
    #imp-logic-card .imp-sf-alpha-input:focus { border-color: var(--imp-accent); }

    /* Delete button */
    #imp-logic-card .imp-stage-del-btn {
        background: transparent;
        border: 1px solid rgba(232,71,95,.3);
        color: var(--imp-danger);
        border-radius: 5px;
        width: 32px;
        height: 32px;
        font-size: 16px;
        cursor: pointer;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .15s;
        flex-shrink: 0;
    }
    #imp-logic-card .imp-stage-del-btn:hover { background: rgba(232,71,95,.12); }
    #imp-logic-card .imp-stage-select-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
    }
    #imp-logic-card .imp-stage-select-wrap select {
        max-width: 240px;
    }

    /* Logic section hint text */
    #imp-logic-card .imp-hint {
        font-size: 12px;
        color: var(--imp-text-dim);
        margin-top: 6px;
        font-family: 'DM Mono', monospace;
    }

    /* Logic save button */
    #imp-logic-card .imp-btn-save-logic {
        background: var(--imp-success);
        color: #0d0f14;
    }
    #imp-logic-card .imp-btn-save-logic:hover:not(:disabled) { opacity: .88; }
    #imp-logic-card .imp-btn-load-logic {
        background: var(--imp-surface-2);
        color: var(--imp-text);
        border: 1px solid var(--imp-border);
    }
    #imp-logic-card .imp-btn-load-logic:hover:not(:disabled) {
        border-color: var(--imp-text-dim);
        color: var(--imp-text);
    }

    /* col map add/remove */
    #imp-logic-card .imp-col-map-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
    }
    #imp-logic-card .imp-add-col-btn {
        background: transparent;
        border: 1px dashed var(--imp-border);
        color: var(--imp-text-dim);
        border-radius: 6px;
        padding: 7px 14px;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        cursor: pointer;
        transition: border-color .2s, color .2s;
    }
    #imp-logic-card .imp-add-col-btn:hover { border-color: var(--imp-accent); color: var(--imp-accent); }
    #imp-logic-card .imp-refresh-col-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        background: transparent;
        border: 1px solid rgba(244,160,28,.35);
        color: var(--imp-accent);
        border-radius: 6px;
        padding: 7px 13px;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        cursor: pointer;
        transition: background .2s, border-color .2s;
    }
    #imp-logic-card .imp-refresh-col-btn:hover { background: rgba(244,160,28,.08); border-color: var(--imp-accent); }
    #imp-logic-card .imp-refresh-col-btn svg {
        width: 13px; height: 13px;
        transition: transform .5s ease;
        flex-shrink: 0;
    }
    #imp-logic-card .imp-refresh-col-btn.spinning svg { animation: imp-refresh-spin .7s linear infinite; }
    @keyframes imp-refresh-spin { to { transform: rotate(360deg); } }
    #imp-logic-card .imp-refresh-toast {
        font-family: 'DM Mono', monospace;
        font-size: 12px;
        color: var(--imp-success);
        opacity: 0;
        transition: opacity .3s;
        white-space: nowrap;
    }
    #imp-logic-card .imp-refresh-toast.visible { opacity: 1; }
    #imp-logic-card .imp-col-del-btn {
        background: transparent;
        border: none;
        color: var(--imp-text-dim);
        font-size: 16px;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 4px;
        line-height: 1;
    }
    #imp-logic-card .imp-col-del-btn:hover { color: var(--imp-danger); background: rgba(232,71,95,.1); }

    #imp-logic-status {
        font-size: 13px;
        font-family: 'DM Mono', monospace;
        padding: 4px 0;
    }
</style>

<!-- ── Loader overlay ── -->
<div id="imp-loader" class="imp-hidden">
    <div class="loader-box">
        <div class="big-spin"></div>
        <p id="imp-loader-msg">Processing...</p>
    </div>
</div>

<!-- ── Existing data confirmation modal ── -->
<div id="imp-confirm-modal" class="imp-hidden">
    <div class="modal-box">
        <h3>⚠ Existing Data Detected</h3>
        <p>There are already <strong id="imp-modal-count"></strong> set times recorded for <strong id="imp-modal-festival-name"></strong>.</p>
        <p>Proceeding will <strong style="color:#e8475f;">permanently delete</strong> all existing set times and viewer preferences for this festival before importing the new data.</p>
        <hr class="modal-divider">
        <div class="modal-btn-row">
            <button class="imp-btn imp-btn-reset" id="imp-modal-cancel">Cancel</button>
            <button class="imp-btn imp-btn-import" id="imp-modal-proceed">Proceed with Import</button>
        </div>
    </div>
</div>

<!-- ── Step 1: Select festival & file ── -->
<div class="imp-card" id="imp-step-upload">
    <div class="imp-card-title">01 — Configure Import</div>

    <div>
        <label>Import File (.xlsx)</label>
        <div class="imp-drop-zone" id="imp-drop-zone">
            <input type="file" id="imp-file-input" accept=".xlsx">
            <span class="imp-drop-icon">📂</span>
            <p>Drag & drop your <strong>.xlsx</strong> file here, or click to browse</p>
            <div class="imp-file-name imp-hidden" id="imp-file-name-display"></div>
        </div>
    </div>

</div>

<!-- ── Bottom panel toggle bar ── -->
<div class="imp-panel-bar" id="imp-panel-bar">
    <button class="imp-panel-btn" id="imp-btn-load-logic" disabled data-target="logic">Import Specs</button>
    <button class="imp-panel-btn" id="imp-btn-preview"    disabled data-target="preview">Preview Import</button>
    <button class="imp-panel-btn imp-panel-btn-reset" id="imp-btn-reset">Reset</button>
</div>

<!-- ── Bottom panel container ── -->
<div id="imp-bottom-panel">

<!-- ── 02: Import Logic editor ── -->
<div class="imp-card imp-hidden" id="imp-logic-card">
    <div class="imp-card-title">02 — Import Logic</div>

    <div class="imp-logic-grid">

        <!-- Column Map -->
        <div class="imp-logic-full">
            <label>Column Mapping</label>
            <table class="imp-col-map-table">
                <thead>
                    <tr>
                        <th style="width:50px;">Col</th>
                        <th style="width:160px;">Field</th>
                        <th>Label</th>
                        <th style="width:36px;"></th>
                    </tr>
                </thead>
                <tbody id="imp-col-map-body"></tbody>
            </table>
            <div class="imp-col-map-actions">
                <button class="imp-add-col-btn" id="imp-add-col-btn">+ Add Column</button>
                <button class="imp-refresh-col-btn" id="imp-refresh-col-btn" title="Read file data and populate Days, Stages, and Attendees from the current column mapping">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13.5 2.5A6.5 6.5 0 1 1 4 3.8"/>
                        <polyline points="1,1 4,4 7,1"/>
                    </svg>
                    Refresh from File
                </button>
                <span class="imp-refresh-toast" id="imp-refresh-toast"></span>
            </div>
        </div>

        <!-- Valid Days -->
        <div>
            <label>Valid Days</label>
            <div class="imp-tag-input-wrap" id="imp-days-wrap">
                <input type="text" id="imp-days-input" placeholder="Type a day, press Enter…">
            </div>
            <p class="imp-hint">e.g. friday, saturday, sunday</p>
        </div>

        <!-- Valid Stages -->
        <div>
            <label>Valid Stages</label>
            <div class="imp-tag-input-wrap" id="imp-stages-wrap">
                <input type="text" id="imp-stages-input" placeholder="Type a stage, press Enter…">
            </div>
            <p class="imp-hint">Must match stage_format keys (lowercase)</p>
        </div>

        <!-- Attendees -->
        <div>
            <label>Attendees</label>
            <div class="imp-tag-input-wrap" id="imp-attendees-wrap">
                <input type="text" id="imp-attendees-input" placeholder="Type a name, press Enter…">
            </div>
            <p class="imp-hint">Viewer names as they appear in the spreadsheet</p>
        </div>

        <!-- Stage Format -->
        <div class="imp-logic-full">
            <label>Stage Format</label>
            <div class="imp-stage-rows" id="imp-stage-rows"></div>
            <div class="imp-stage-select-wrap">
                <select id="imp-stage-select">
                    <option value="">+ Add Stage…</option>
                </select>
                <span class="imp-hint">Populated from Valid Stages</span>
            </div>
        </div>

    </div>

    <div class="imp-btn-row" style="margin-top: 28px; align-items: center;">
        <button class="imp-btn imp-btn-save-logic" id="imp-btn-save-logic">Save Logic</button>
        <span id="imp-logic-status" class="imp-hidden"></span>
    </div>
</div>

<!-- ── Step 2: Preview results ── -->
<div id="imp-preview-section" class="imp-hidden">

    <div class="imp-summary-bar" id="imp-summary-bar"></div>

    <div id="imp-error-block" class="imp-error-list imp-hidden">
        <h4>Errors Detected — Fix before importing</h4>
        <ul id="imp-error-list"></ul>
    </div>

    <div id="imp-clean-block" class="imp-hidden">
        <div class="imp-alert imp-alert-success">
            ✓ &nbsp;No errors detected. Ready to import.
        </div>
        <div class="imp-btn-row" style="margin-top:0; margin-bottom:20px;">
            <button class="imp-btn imp-btn-import" id="imp-btn-import">Import to Database</button>
        </div>
    </div>

    <div id="imp-import-result" class="imp-hidden"></div>

    <div class="imp-card" style="padding: 0; overflow:hidden;">
        <div style="padding: 20px 24px 16px; border-bottom: 1px solid var(--imp-border);">
            <div class="imp-card-title" style="margin-bottom:0;">02 — Preview Data</div>
        </div>
        <div class="imp-table-wrap" style="border:none; border-radius:0;">
            <table id="imp-preview-table">
                <thead id="imp-preview-thead"></thead>
                <tbody id="imp-preview-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

</div><!-- /#imp-bottom-panel -->

<script>
(function () {
    // ── Use the global festival dropdown from festivals.php ──
    // We create a proxy object that reads from window.selectedFestival
    // so the rest of the code can use festivalSelect.value / .options[].dataset
    // without changes.
    const festivalSelect = {
        get value() { return window.selectedFestival ? window.selectedFestival.id : ''; },
        get options() {
            return [{
                value: window.selectedFestival ? window.selectedFestival.id : '',
                dataset: {
                    name: window.selectedFestival ? window.selectedFestival.name : '',
                    year: window.selectedFestival ? window.selectedFestival.year : ''
                }
            }];
        },
        get selectedIndex() { return 0; }
    };

    const btnLoadLogic   = document.getElementById('imp-btn-load-logic');
    const logicCard      = document.getElementById('imp-logic-card');
    const logicStatus    = document.getElementById('imp-logic-status');
    const btnSaveLogic   = document.getElementById('imp-btn-save-logic');

    // ── Known DB fields for column mapping ──
    const KNOWN_FIELDS = [
        { value: '',           label: '— select field —' },
        { value: 'day',        label: 'day' },
        { value: 'start_Time', label: 'start_Time' },
        { value: 'end_Time',   label: 'end_Time' },
        { value: 'performer',  label: 'performer' },
        { value: 'stage',      label: 'stage' },
        { value: 'attendee',   label: 'attendee' },
    ];

    // ── Column letters helper ──
    function indexToLetter(i) {
        let s = '';
        i++;
        while (i > 0) { let r = (i - 1) % 26; s = String.fromCharCode(65 + r) + s; i = Math.floor((i - 1) / 26); }
        return s;
    }

    // ── Build a field dropdown ──
    function makeFieldSelect(selectedVal) {
        const sel = document.createElement('select');
        KNOWN_FIELDS.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.value;
            opt.textContent = f.label;
            if (f.value === selectedVal) opt.selected = true;
            sel.appendChild(opt);
        });
        return sel;
    }

    // ── Render column map rows ──
    function renderColMapRows(colMap) {
        // colMap: { "A": {table, field, label}, ... }
        const tbody = document.getElementById('imp-col-map-body');
        tbody.innerHTML = '';
        const entries = Object.entries(colMap).sort(([a],[b]) => a.localeCompare(b));
        entries.forEach(([letter, mapping]) => addColMapRow(letter, mapping.field || '', mapping.label || ''));
    }

    function addColMapRow(letter, field, label) {
        const tbody = document.getElementById('imp-col-map-body');
        const tr = document.createElement('tr');
        tr.dataset.letter = letter;

        // Letter cell
        const tdLetter = document.createElement('td');
        const letterInput = document.createElement('input');
        letterInput.type = 'text';
        letterInput.className = 'imp-col-letter';
        letterInput.value = letter;
        letterInput.maxLength = 3;
        letterInput.style.cssText = 'width:48px;text-align:center;font-family:\'DM Mono\',monospace;font-size:13px;background:rgba(244,160,28,.08);border:1px solid rgba(244,160,28,.2);color:var(--imp-accent);border-radius:4px;padding:6px 8px;box-sizing:border-box;';
        letterInput.addEventListener('input', () => { tr.dataset.letter = letterInput.value.toUpperCase(); letterInput.value = letterInput.value.toUpperCase(); });
        tdLetter.appendChild(letterInput);

        // Field dropdown cell
        const tdField = document.createElement('td');
        const sel = makeFieldSelect(field);
        tdField.appendChild(sel);

        // Label input cell
        const tdLabel = document.createElement('td');
        const labelInput = document.createElement('input');
        labelInput.type = 'text';
        labelInput.value = label;
        labelInput.placeholder = 'Display label…';
        tdLabel.appendChild(labelInput);

        // Delete cell
        const tdDel = document.createElement('td');
        const delBtn = document.createElement('button');
        delBtn.className = 'imp-col-del-btn';
        delBtn.textContent = '×';
        delBtn.title = 'Remove row';
        delBtn.addEventListener('click', () => tr.remove());
        tdDel.appendChild(delBtn);

        tr.appendChild(tdLetter);
        tr.appendChild(tdField);
        tr.appendChild(tdLabel);
        tr.appendChild(tdDel);
        tbody.appendChild(tr);
    }

    document.getElementById('imp-add-col-btn').addEventListener('click', () => {
        const rows = document.getElementById('imp-col-map-body').rows;
        const nextIdx = rows.length;
        addColMapRow(indexToLetter(nextIdx), '', '');
    });

    // ── Refresh from File ──
    // Reads the selected xlsx client-side, scans columns mapped to 'day', 'stage',
    // and 'attendee', de-dupes the values, and populates the corresponding tag inputs.
    document.getElementById('imp-refresh-col-btn').addEventListener('click', async () => {
        if (!selectedFile) {
            showAlert('Select a file first, then click Refresh.', 'danger');
            return;
        }

        const btn   = document.getElementById('imp-refresh-col-btn');
        const toast = document.getElementById('imp-refresh-toast');
        btn.classList.add('spinning');
        toast.textContent = '';
        toast.classList.remove('visible');

        try {
            // Build a letter→field+label map from the current column map rows
            const letterToField   = {};
            const attendeeLabels  = []; // Labels of rows where field = 'attendee'

            document.getElementById('imp-col-map-body').querySelectorAll('tr').forEach(tr => {
                const inputs = tr.querySelectorAll('input[type="text"]');
                const letter = inputs[0].value.trim().toUpperCase();
                const field  = tr.querySelector('select').value;
                const label  = inputs[1].value.trim();
                if (letter && field) {
                    letterToField[letter] = field;
                    if (field === 'attendee' && label) attendeeLabels.push(label);
                }
            });

            // Parse the xlsx file in-browser
            const colData = await readXlsxColumns(selectedFile, letterToField);

            // Merge de-duped values into each tag input (add only new ones)
            let added = { days: 0, stages: 0, attendees: 0 };

            if (colData.day && colData.day.length) {
                const existing = new Set(daysTagInput.getTags());
                const fresh = colData.day.filter(v => !existing.has(v));
                if (fresh.length) {
                    daysTagInput.setTags([...existing, ...fresh]);
                    added.days = fresh.length;
                }
            }

            if (colData.stage && colData.stage.length) {
                const existing = new Set(stagesTagInput.getTags());
                const fresh = colData.stage.filter(v => !existing.has(v));
                if (fresh.length) {
                    stagesTagInput.setTags([...existing, ...fresh]);
                    added.stages = fresh.length;
                }
            }

            if (attendeeLabels.length) {
                const existing = new Set(attendeesTagInput.getTags());
                const fresh = attendeeLabels.filter(v => !existing.has(v));
                if (fresh.length) {
                    attendeesTagInput.setTags([...existing, ...fresh]);
                    added.attendees = fresh.length;
                }
            }

            btn.classList.remove('spinning');

            const parts = [];
            if (added.days)      parts.push(`${added.days} day${added.days !== 1 ? 's' : ''}`);
            if (added.stages)    parts.push(`${added.stages} stage${added.stages !== 1 ? 's' : ''}`);
            if (added.attendees) parts.push(`${added.attendees} attendee${added.attendees !== 1 ? 's' : ''}`);

            toast.textContent = parts.length ? `✓ Added: ${parts.join(', ')}` : '✓ No new values found';
            toast.classList.add('visible');
            setTimeout(() => toast.classList.remove('visible'), 4000);

        } catch (e) {
            btn.classList.remove('spinning');
            showAlert('Could not read file: ' + e.message, 'danger');
        }
    });

    // ── Parse xlsx in-browser and extract unique values per mapped field ──
    // Returns { day: ['friday','saturday',...], stage: [...], attendee: [...] }
    async function readXlsxColumns(file, letterToField) {
        const buffer = await file.arrayBuffer();
        const uint8  = new Uint8Array(buffer);
        const zip    = await parseZipAsync(uint8);

        // Read shared strings — strip default xmlns so getElementsByTagName works across all browsers
        const sharedStrings = [];
        const ssEntry = zip['xl/sharedStrings.xml'];
        if (ssEntry) {
            const ssText = decodeUtf8(ssEntry).replace(/xmlns="[^"]*"/g, '');
            const xml = new DOMParser().parseFromString(ssText, 'text/xml');
            for (const si of xml.getElementsByTagName('si')) {
                let text = '';
                for (const t of si.getElementsByTagName('t')) text += t.textContent;
                sharedStrings.push(text);
            }
        }

        // Read sheet1 — also strip xmlns for consistent tag lookup
        const sheetEntry = zip['xl/worksheets/sheet1.xml'];
        if (!sheetEntry) throw new Error('sheet1.xml not found in file');
        const sheetText = decodeUtf8(sheetEntry).replace(/xmlns="[^"]*"/g, '');
        const sheetXml = new DOMParser().parseFromString(sheetText, 'text/xml');

        // Which column letters map to target fields (day and stage only — attendees come from Labels)
        const fieldForLetter = {};
        for (const [letter, field] of Object.entries(letterToField)) {
            if (['day', 'stage'].includes(field)) {
                fieldForLetter[letter.toUpperCase()] = field;
            }
        }
        if (Object.keys(fieldForLetter).length === 0) return {};

        const result = {};
        let isFirstRow = true;

        for (const row of sheetXml.getElementsByTagName('row')) {
            if (isFirstRow) { isFirstRow = false; continue; } // skip header row
            for (const c of row.getElementsByTagName('c')) {
                const colLetter = (c.getAttribute('r') || '').replace(/[0-9]/g, '').toUpperCase();
                const field = fieldForLetter[colLetter];
                if (!field) continue;
                const t   = c.getAttribute('t');
                const vEl = c.getElementsByTagName('v')[0];
                if (!vEl) continue;
                let val = vEl.textContent.trim();
                if (t === 's') val = (sharedStrings[parseInt(val, 10)] ?? '').trim();
                if (!val) continue;
                if (!result[field]) result[field] = new Set();
                result[field].add(val);
            }
        }

        const out = {};
        for (const [field, set] of Object.entries(result)) {
            out[field] = [...set].sort();
        }
        return out;
    }

    // ── Async zip parser using DecompressionStream for deflate entries ──
    async function parseZipAsync(data) {
        const files = {};
        let i = 0;
        const len = data.length;
        while (i < len - 4) {
            if (data[i]===0x50 && data[i+1]===0x4B && data[i+2]===0x03 && data[i+3]===0x04) {
                const compMethod = data[i+8]  | (data[i+9]  << 8);
                const compSize   = data[i+18] | (data[i+19] << 8) | (data[i+20] << 16) | (data[i+21] << 24);
                const fnLen      = data[i+26] | (data[i+27] << 8);
                const extraLen   = data[i+28] | (data[i+29] << 8);
                const name       = new TextDecoder().decode(data.slice(i+30, i+30+fnLen));
                const dataStart  = i + 30 + fnLen + extraLen;
                const compData   = data.slice(dataStart, dataStart + compSize);

                // Only decompress xml files we actually need
                if (name.endsWith('.xml')) {
                    if (compMethod === 0) {
                        files[name] = compData;
                    } else if (compMethod === 8) {
                        try {
                            const ds     = new DecompressionStream('deflate-raw');
                            const writer = ds.writable.getWriter();
                            const reader = ds.readable.getReader();
                            writer.write(compData);
                            writer.close();
                            const chunks = [];
                            while (true) {
                                const { done, value } = await reader.read();
                                if (done) break;
                                chunks.push(value);
                            }
                            const total  = chunks.reduce((n, c) => n + c.length, 0);
                            const result = new Uint8Array(total);
                            let offset   = 0;
                            for (const chunk of chunks) { result.set(chunk, offset); offset += chunk.length; }
                            files[name] = result;
                        } catch(e) { /* skip unreadable entries */ }
                    }
                }
                i = dataStart + compSize;
            } else {
                i++;
            }
        }
        return files;
    }

    function decodeUtf8(uint8) {
        return new TextDecoder('utf-8').decode(uint8);
    }

    // ── Tag input helper ──
    function initTagInput(wrapId, inputId, initialValues) {
        const wrap  = document.getElementById(wrapId);
        const input = document.getElementById(inputId);
        const tags  = [...(initialValues || [])];

        function renderTags() {
            // Remove existing tag spans
            wrap.querySelectorAll('.imp-tag').forEach(t => t.remove());
            tags.forEach((val, idx) => {
                const span = document.createElement('span');
                span.className = 'imp-tag';
                span.innerHTML = `${escHtml(val)} <span class="imp-tag-x" data-idx="${idx}" title="Remove">×</span>`;
                span.querySelector('.imp-tag-x').addEventListener('click', () => {
                    tags.splice(idx, 1);
                    renderTags();
                });
                wrap.insertBefore(span, input);
            });
        }

        input.addEventListener('keydown', e => {
            if ((e.key === 'Enter' || e.key === ',') && input.value.trim()) {
                e.preventDefault();
                const v = input.value.trim().replace(/,$/, '');
                if (v && !tags.includes(v)) { tags.push(v); renderTags(); }
                input.value = '';
            } else if (e.key === 'Backspace' && input.value === '' && tags.length) {
                tags.pop();
                renderTags();
            }
        });

        wrap.addEventListener('click', () => input.focus());
        renderTags();

        return { getTags: () => [...tags], setTags: (arr) => { tags.length = 0; arr.forEach(v => tags.push(v)); renderTags(); } };
    }

    // Init tag inputs (empty until logic is loaded)
    const daysTagInput      = initTagInput('imp-days-wrap',      'imp-days-input',      []);
    const stagesTagInput    = initTagInput('imp-stages-wrap',    'imp-stages-input',    []);
    const attendeesTagInput = initTagInput('imp-attendees-wrap', 'imp-attendees-input', []);

    // ── Stage format rows ──

    // Rebuild the stage dropdown options based on what's already in rows
    function refreshStageDropdown() {
        const select = document.getElementById('imp-stage-select');
        const usedNames = new Set(
            [...document.getElementById('imp-stage-rows').querySelectorAll('.imp-stage-row')]
                .map(r => r.dataset.stageName)
        );
        const allStages = stagesTagInput.getTags();
        // Preserve current selection if still valid
        const prev = select.value;
        select.innerHTML = '<option value="">+ Add Stage…</option>';
        allStages.forEach(s => {
            if (!usedNames.has(s)) {
                const opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                select.appendChild(opt);
            }
        });
        if (prev && !usedNames.has(prev)) select.value = prev;
    }

    function renderStageRows(stageFormat) {
        const container = document.getElementById('imp-stage-rows');
        container.innerHTML = '';
        if (!stageFormat) { refreshStageDropdown(); return; }
        const entries = Object.entries(stageFormat).sort(([,a],[,b]) => (a.order||0) - (b.order||0));
        entries.forEach(([name, cfg]) => addStageRow(name, cfg));
        refreshStageDropdown();
    }

    function addStageRow(name, cfg) {
        const container = document.getElementById('imp-stage-rows');
        const row = document.createElement('div');
        row.className = 'imp-stage-row';
        row.dataset.stageName = name;

        const hexVal  = cfg.hex || '#ffffff';

        // Parse existing rgba or fall back to defaults
        function parseRgbaAlpha(val, defaultAlpha) {
            const m = val && val.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
            if (m) return parseFloat(m[4] !== undefined ? m[4] : defaultAlpha);
            return defaultAlpha;
        }

        const dimAlpha  = parseRgbaAlpha(cfg['hex-dim'],  0.12);
        const glowAlpha = parseRgbaAlpha(cfg['hex-glow'], 0.35);

        // Derive initial rgba text from hex + alpha
        function makeRgba(hex, alpha) {
            const rgb = hexToRgba(hex);
            return `rgba(${rgb},${alpha})`;
        }

        const dimRgba  = makeRgba(hexVal, dimAlpha);
        const glowRgba = makeRgba(hexVal, glowAlpha);

        row.innerHTML = `
            <div class="imp-stage-row-grid">

                <div class="imp-stage-field sf-name">
                    <span class="imp-stage-field-label">Stage Name</span>
                    <div class="imp-stage-name-display">${escHtml(name)}</div>
                </div>

                <div class="imp-stage-field sf-order">
                    <span class="imp-stage-field-label">Order</span>
                    <input type="number" class="sf-order-input" value="${escHtml(String(cfg.order || ''))}" placeholder="#" min="1" max="99">
                </div>

                <div class="imp-stage-field sf-hex">
                    <span class="imp-stage-field-label">Hex</span>
                    <div class="imp-sf-hex-wrap">
                        <input type="color" class="sf-color-hex" value="${escHtml(hexVal)}">
                        <span class="sf-hex-text imp-sf-hex-text">${escHtml(hexVal)}</span>
                    </div>
                </div>

                <div class="imp-stage-field sf-dim">
                    <span class="imp-stage-field-label">Hex-Dim</span>
                    <div class="imp-sf-rgba-wrap">
                        <div class="imp-sf-rgba-swatch sf-dim-swatch" style="background:rgba(${hexToRgba(hexVal)},${Math.min(dimAlpha+0.25,1)});"></div>
                        <span class="sf-dim-text imp-sf-rgba-text">${escHtml(dimRgba)}</span>
                    </div>
                    <div class="imp-sf-alpha-row">
                        <span class="imp-sf-alpha-label">Alpha</span>
                        <input type="number" class="sf-dim-alpha imp-sf-alpha-input" value="${dimAlpha}" min="0" max="1" step="0.01" title="Dim alpha">
                    </div>
                </div>

                <div class="imp-stage-field sf-glow">
                    <span class="imp-stage-field-label">Hex-Glow</span>
                    <div class="imp-sf-rgba-wrap">
                        <div class="imp-sf-rgba-swatch sf-glow-swatch" style="background:rgba(${hexToRgba(hexVal)},${Math.min(glowAlpha+0.25,1)});"></div>
                        <span class="sf-glow-text imp-sf-rgba-text">${escHtml(glowRgba)}</span>
                    </div>
                    <div class="imp-sf-alpha-row">
                        <span class="imp-sf-alpha-label">Alpha</span>
                        <input type="number" class="sf-glow-alpha imp-sf-alpha-input" value="${glowAlpha}" min="0" max="1" step="0.01" title="Glow alpha">
                    </div>
                </div>

                <button class="imp-stage-del-btn" title="Remove stage" style="align-self:flex-start;margin-top:19px;">×</button>
            </div>
        `;

        // ── Helper: rebuild dim/glow from current hex + alpha ──
        function updateRgbaFields() {
            const hex   = row.querySelector('.sf-color-hex').value;
            const rgb   = hexToRgba(hex);
            const dAlpha = parseFloat(row.querySelector('.sf-dim-alpha').value)  || 0;
            const gAlpha = parseFloat(row.querySelector('.sf-glow-alpha').value) || 0;

            const dimVal  = `rgba(${rgb},${dAlpha})`;
            const glowVal = `rgba(${rgb},${gAlpha})`;

            row.querySelector('.sf-dim-text').textContent  = dimVal;
            row.querySelector('.sf-glow-text').textContent = glowVal;
            row.querySelector('.sf-dim-swatch').style.background  = `rgba(${rgb},${Math.min(dAlpha + 0.25, 1)})`;
            row.querySelector('.sf-glow-swatch').style.background = `rgba(${rgb},${Math.min(gAlpha + 0.25, 1)})`;
        }

        // Hex picker change → update hex text + re-derive dim/glow rgba
        row.querySelector('.sf-color-hex').addEventListener('input', function() {
            row.querySelector('.sf-hex-text').textContent = this.value;
            updateRgbaFields();
        });

        // Alpha input changes → just recalculate rgba text
        row.querySelector('.sf-dim-alpha').addEventListener('input', updateRgbaFields);
        row.querySelector('.sf-glow-alpha').addEventListener('input', updateRgbaFields);

        row.querySelector('.imp-stage-del-btn').addEventListener('click', () => {
            row.remove();
            refreshStageDropdown();
        });

        container.appendChild(row);
    }

    // Stage dropdown: add selected stage as a row
    document.getElementById('imp-stage-select').addEventListener('change', function() {
        const name = this.value;
        if (!name) return;
        addStageRow(name, {
            order: document.getElementById('imp-stage-rows').children.length + 1,
            hex: '#ffffff',
            'hex-dim':  'rgba(255,255,255,0.12)',
            'hex-glow': 'rgba(255,255,255,0.35)',
        });
        refreshStageDropdown();
        this.value = '';
    });

    // Refresh dropdown whenever valid stages change (patch setTags after it's defined)
    const _origStagesSetTags = stagesTagInput.setTags.bind(stagesTagInput);
    stagesTagInput.setTags = function(arr) {
        _origStagesSetTags(arr);
        refreshStageDropdown();
    };

    // ── Read stage format from DOM ──
    function collectStageFormat() {
        const result = {};
        document.getElementById('imp-stage-rows').querySelectorAll('.imp-stage-row').forEach(row => {
            const name = row.dataset.stageName;
            if (!name) return;
            const hex      = row.querySelector('.sf-color-hex').value;
            const hexRgb   = hexToRgba(hex);
            const dimText  = row.querySelector('.sf-dim-text').textContent.trim();
            const glowText = row.querySelector('.sf-glow-text').textContent.trim();
            result[name] = {
                order:      row.querySelector('.sf-order-input').value.trim() || '1',
                hex:        hex,
                'hex-dim':  dimText  || `rgba(${hexRgb},0.12)`,
                'hex-glow': glowText || `rgba(${hexRgb},0.35)`,
            };
        });
        return result;
    }

    function hexToRgba(hex) {
        const r = parseInt(hex.slice(1,3),16);
        const g = parseInt(hex.slice(3,5),16);
        const b = parseInt(hex.slice(5,7),16);
        return `${r},${g},${b}`;
    }

    // ── Read column map from DOM ──
    function collectColMap() {
        const result = {};
        document.getElementById('imp-col-map-body').querySelectorAll('tr').forEach(tr => {
            const inputs = tr.querySelectorAll('input[type="text"]');
            const letter = inputs[0].value.trim().toUpperCase();
            const field  = tr.querySelector('select').value;
            const label  = inputs[1].value.trim();
            if (letter && field) {
                result[letter] = { table: 'festival_transactions', field, label };
            }
        });
        return result;
    }

    // ── Show logic status message ──
    function setLogicStatus(msg, type) {
        logicStatus.textContent = msg;
        logicStatus.className   = '';
        logicStatus.style.color = type === 'success' ? 'var(--imp-success)' : type === 'danger' ? 'var(--imp-danger)' : 'var(--imp-text-dim)';
        logicStatus.classList.remove('imp-hidden');
        if (type !== 'danger') setTimeout(() => logicStatus.classList.add('imp-hidden'), 4000);
    }

    // ── Load Import Logic (called by showPanel when opening the logic panel) ──
    async function loadImportLogic() {
        const festivalId = festivalSelect.value;
        if (!festivalId) { showAlert('No festival selected.', 'danger'); return; }

        showLoader('Loading import logic…');
        try {
            const fd = new FormData();
            fd.append('action', 'load_import_logic');
            fd.append('festival_id', festivalId);
            if (selectedFile) fd.append('import_file', selectedFile);

            const res  = await fetch('index.php', { method: 'POST', body: fd });
            const data = await res.json();
            hideLoader();

            if (!data.success) {
                showAlert(data.message || 'Could not load import logic.', 'danger');
                return;
            }

            if (data.found) {
                renderColMapRows(data.column_map   || {});
                daysTagInput.setTags(data.valid_days   || []);
                stagesTagInput.setTags(data.valid_stages || []);
                attendeesTagInput.setTags(data.attendees || []);
                renderStageRows(data.stage_format  || {});
                setLogicStatus('✓ Existing logic loaded', 'success');
            } else {
                renderColMapRows(data.column_map_from_file || {});
                daysTagInput.setTags([]);
                stagesTagInput.setTags([]);
                attendeesTagInput.setTags([]);
                renderStageRows({});
                setLogicStatus('No logic found — populated from spreadsheet headers. Fill in the details and save.', 'info');
                logicStatus.style.color = 'var(--imp-accent)';
                logicStatus.classList.remove('imp-hidden');
            }
        } catch(e) {
            hideLoader();
            showAlert('Unexpected error: ' + e.message, 'danger');
        }
    }

    // ── Save Import Logic ──
    btnSaveLogic.addEventListener('click', async () => {
        const festivalId = festivalSelect.value;
        if (!festivalId) { showAlert('No festival selected.', 'danger'); return; }

        const payload = {
            column_map:   collectColMap(),
            valid_days:   daysTagInput.getTags(),
            valid_stages: stagesTagInput.getTags(),
            attendees:    attendeesTagInput.getTags(),
            stage_format: collectStageFormat(),
        };

        showLoader('Saving import logic…');
        try {
            const fd = new FormData();
            fd.append('action', 'save_import_logic');
            fd.append('festival_id', festivalId);
            fd.append('logic_data', JSON.stringify(payload));

            const res  = await fetch('index.php', { method: 'POST', body: fd });
            const data = await res.json();
            hideLoader();

            if (data.success) {
                setLogicStatus('✓ Logic saved successfully', 'success');
                checkReady(); // re-evaluate; Preview should now be available
            } else {
                setLogicStatus('✕ ' + (data.message || 'Save failed'), 'danger');
            }
        } catch(e) {
            hideLoader();
            showAlert('Unexpected error: ' + e.message, 'danger');
        }
    });

    const fileInput      = document.getElementById('imp-file-input');
    const dropZone       = document.getElementById('imp-drop-zone');
    const fileNameDisp   = document.getElementById('imp-file-name-display');
    const btnPreview     = document.getElementById('imp-btn-preview');
    const btnReset       = document.getElementById('imp-btn-reset');
    const btnImport      = document.getElementById('imp-btn-import');
    const previewSection = document.getElementById('imp-preview-section');
    const bottomPanel    = document.getElementById('imp-bottom-panel');
    const loader         = document.getElementById('imp-loader');
    const loaderMsg      = document.getElementById('imp-loader-msg');

    let selectedFile   = null;
    let previewRows    = null;
    let previewViewers = [];
    let importing      = false;

    // ── Bottom panel toggle ──
    // activePanel: null | 'logic' | 'preview'
    let activePanel = null;

    function showPanel(name) {
        // Toggle off if same panel clicked again
        if (activePanel === name) {
            activePanel = null;
            logicCard.classList.add('imp-hidden');
            previewSection.classList.add('imp-hidden');
            document.querySelectorAll('.imp-panel-btn[data-target]').forEach(b => b.classList.remove('active'));
            return;
        }
        activePanel = name;
        logicCard.classList.toggle('imp-hidden', name !== 'logic');
        previewSection.classList.toggle('imp-hidden', name !== 'preview');
        document.querySelectorAll('.imp-panel-btn[data-target]').forEach(b => {
            b.classList.toggle('active', b.dataset.target === name);
        });
        if (name === 'logic') loadImportLogic();
    }

    // ── File selection ──
    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) setFile(fileInput.files[0]);
    });

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const f = e.dataTransfer.files[0];
        if (f && f.name.endsWith('.xlsx')) {
            setFile(f);
        } else {
            showAlert('Only .xlsx files are accepted.', 'danger');
        }
    });

    function setFile(f) {
        selectedFile = f;
        dropZone.classList.add('has-file');
        fileNameDisp.textContent = '📄 ' + f.name;
        fileNameDisp.classList.remove('imp-hidden');
        checkReady();
    }

    // Re-check ready state whenever the global festival changes
    document.addEventListener('festivalChanged', checkReady);

    // Tracks whether the current festival has saved import logic
    let festivalHasLogic = false;

    async function checkReady() {
        const festivalId = festivalSelect.value;

        // Import Specs requires: file selected
        btnLoadLogic.disabled = !(festivalId && selectedFile);

        // Preview always disabled until we confirm logic exists
        btnPreview.disabled = true;

        if (!festivalId || !selectedFile) {
            festivalHasLogic = false;
            // If bottom panel was open but no longer valid, close it
            if (activePanel) {
                activePanel = null;
                logicCard.classList.add('imp-hidden');
                previewSection.classList.add('imp-hidden');
                document.querySelectorAll('.imp-panel-btn[data-target]').forEach(b => b.classList.remove('active'));
            }
            return;
        }

        // Ask the server whether import logic exists for this festival
        try {
            const fd = new FormData();
            fd.append('action', 'check_import_logic');
            fd.append('festival_id', festivalId);
            const res  = await fetch('index.php', { method: 'POST', body: fd });
            const data = await res.json();
            festivalHasLogic = !!(data.success && data.found);
        } catch (e) {
            festivalHasLogic = false;
        }

        // Only enable Preview if logic is confirmed to exist in DB
        btnPreview.disabled = !festivalHasLogic;
    }

    // ── Panel button clicks ──
    btnLoadLogic.addEventListener('click', () => showPanel('logic'));
    // (Preview button click is handled further below — it also triggers the fetch)

    // ── Reset ──
    btnReset.addEventListener('click', () => {
        selectedFile = null;
        fileInput.value = '';
        fileNameDisp.classList.add('imp-hidden');
        fileNameDisp.textContent = '';
        dropZone.classList.remove('has-file', 'dragover');
        activePanel = null;
        previewSection.classList.add('imp-hidden');
        logicCard.classList.add('imp-hidden');
        document.querySelectorAll('.imp-panel-btn[data-target]').forEach(b => b.classList.remove('active'));
        previewRows    = null;
        previewViewers = [];
        importing      = false;
        btnLoadLogic.disabled = true;
        btnPreview.disabled   = true;
        document.getElementById('imp-import-result').classList.add('imp-hidden');
    });

    // ── Preview ──
    btnPreview.addEventListener('click', async () => {
        // If preview is already open, close it and stop
        if (activePanel === 'preview') {
            showPanel('preview'); // toggles off
            return;
        }

        const festivalId   = festivalSelect.value;
        const festivalName = festivalSelect.options[festivalSelect.selectedIndex].dataset.name;

        const fd = new FormData();
        fd.append('action', 'preview');
        fd.append('festival_id', festivalId);
        fd.append('festival_name', festivalName);
        fd.append('import_file', selectedFile);

        showLoader('Parsing spreadsheet...');
        try {
            const res  = await fetch('index.php', { method: 'POST', body: fd });
            const data = await res.json();
            hideLoader();
            renderPreview(data);
            showPanel('preview');
        } catch(e) {
            hideLoader();
            showAlert('Unexpected error during preview: ' + e.message, 'danger');
        }
    });

    // ── Import ──
    btnImport.addEventListener('click', async () => {
        if (!previewRows) return;

        const festivalId   = festivalSelect.value;
        const festivalName = festivalSelect.options[festivalSelect.selectedIndex].dataset.name;

        showLoader('Checking for existing data...');
        try {
            const checkFd = new FormData();
            checkFd.append('action', 'check_existing');
            checkFd.append('festival_id', festivalId);

            const checkRes  = await fetch('index.php', { method: 'POST', body: checkFd });
            const checkData = await checkRes.json();
            hideLoader();

            if (checkData.exists) {
                document.getElementById('imp-modal-count').textContent        = checkData.count + ' set time' + (checkData.count !== 1 ? 's' : '');
                document.getElementById('imp-modal-festival-name').textContent = festivalName;
                document.getElementById('imp-confirm-modal').classList.remove('imp-hidden');
            } else {
                await runImport(festivalId, festivalName);
            }
        } catch(e) {
            hideLoader();
            showAlert('Unexpected error during check: ' + e.message, 'danger');
        }
    });

    // ── Modal buttons ──
    document.getElementById('imp-modal-cancel').addEventListener('click', () => {
        document.getElementById('imp-confirm-modal').classList.add('imp-hidden');
    });

    document.getElementById('imp-modal-proceed').addEventListener('click', async () => {
        document.getElementById('imp-confirm-modal').classList.add('imp-hidden');
        const festivalId   = festivalSelect.value;
        const festivalName = festivalSelect.options[festivalSelect.selectedIndex].dataset.name;
        await runImport(festivalId, festivalName);
    });

    // ── Shared import logic ──
    async function runImport(festivalId, festivalName) {
        if (importing) return;
        importing = true;
        btnImport.disabled = true;

        const fd = new FormData();
        fd.append('action', 'import');
        fd.append('festival_id', festivalId);
        fd.append('festival_name', festivalName);
        fd.append('rows', JSON.stringify(previewRows));

        showLoader('Importing to database...');
        try {
            const res  = await fetch('index.php', { method: 'POST', body: fd });
            const data = await res.json();
            hideLoader();

            const resultDiv = document.getElementById('imp-import-result');
            resultDiv.classList.remove('imp-hidden');

            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="imp-alert imp-alert-success">
                        ✓ &nbsp;<strong>Import complete!</strong>
                        &nbsp;${data.trans_count} set times imported,
                        ${data.pref_count} viewer preferences recorded.
                    </div>`;
                btnImport.disabled = true;
                document.getElementById('imp-clean-block').classList.add('imp-hidden');
            } else {
                importing = false;
                btnImport.disabled = false;
                resultDiv.innerHTML = `<div class="imp-alert imp-alert-danger">✕ &nbsp;${escHtml(data.message)}</div>`;
            }
        } catch(e) {
            importing = false;
            btnImport.disabled = false;
            hideLoader();
            showAlert('Unexpected error during import: ' + e.message, 'danger');
        }
    }

    // ── Render preview ──
    function renderPreview(data) {
        // Note: previewSection visibility is controlled by showPanel()
        document.getElementById('imp-import-result').classList.add('imp-hidden');

        previewRows    = data.rows         || [];
        previewViewers = data.viewer_names || [];

        const errorCount = (data.errors || []).length;
        const errorRows  = previewRows.filter(r => r.has_error).length;
        const prefCount  = previewRows.reduce((s, r) => s + r.preferences.length, 0);

        document.getElementById('imp-summary-bar').innerHTML = `
            <div class="imp-summary-stat"><div class="num">${previewRows.length}</div><div class="lbl">Set Times</div></div>
            <div class="imp-summary-stat"><div class="num">${prefCount}</div><div class="lbl">Viewer Prefs</div></div>
            <div class="imp-summary-stat"><div class="num">${previewViewers.length}</div><div class="lbl">Viewers</div></div>
            <div class="imp-summary-stat ${errorRows > 0 ? 'danger' : 'ok'}">
                <div class="num">${errorRows}</div><div class="lbl">Errors</div>
            </div>`;

        const errorBlock = document.getElementById('imp-error-block');
        const errorList  = document.getElementById('imp-error-list');
        const cleanBlock = document.getElementById('imp-clean-block');

        if (errorCount > 0) {
            errorBlock.classList.remove('imp-hidden');
            cleanBlock.classList.add('imp-hidden');
            errorList.innerHTML = (data.errors || []).map(e => `<li>${escHtml(e)}</li>`).join('');
            btnImport.disabled = true;
        } else {
            errorBlock.classList.add('imp-hidden');
            cleanBlock.classList.remove('imp-hidden');
            btnImport.disabled = false;
        }

        const thead = document.getElementById('imp-preview-thead');
        const tbody = document.getElementById('imp-preview-tbody');

        const viewerCols = previewViewers.map(v => `<th>${escHtml(v)}</th>`).join('');
        thead.innerHTML = `<tr>
            <th>#</th><th>Day</th><th>Start</th><th>End</th>
            <th>Performer</th><th>Stage</th>${viewerCols}<th>Status</th>
        </tr>`;

        tbody.innerHTML = previewRows.map(row => {
            const viewerCells = previewViewers.map(v => {
                const pref = row.preferences.find(p => p.viewer === v);
                if (!pref) return '<td>—</td>';
                const badge = pref.want
                    ? `<span class="imp-badge badge-want">W</span>`
                    : `<span class="imp-badge badge-need">N</span>`;
                return `<td>${badge}</td>`;
            }).join('');

            const status = row.has_error
                ? `<span class="imp-badge badge-err">Error</span>`
                : `<span style="color:#2ecc8a;font-size:13px;">✓</span>`;

            return `<tr class="${row.has_error ? 'row-error' : ''}">
                <td class="mono">${row.row_num}</td>
                <td>${escHtml(row.day)}</td>
                <td class="mono">${escHtml(row.start_Time)}</td>
                <td class="mono">${escHtml(row.end_Time)}</td>
                <td>${escHtml(row.performer)}</td>
                <td><span class="imp-stage-pill">${escHtml(row.stage)}</span></td>
                ${viewerCells}
                <td>${status}</td>
            </tr>`;
        }).join('');
    }

    // ── Utilities ──
    function showLoader(msg) {
        loaderMsg.textContent = msg || 'Processing...';
        loader.classList.remove('imp-hidden');
    }
    function hideLoader() {
        loader.classList.add('imp-hidden');
    }
    function showAlert(msg, type) {
        const div = document.createElement('div');
        div.className = `imp-alert imp-alert-${type}`;
        div.textContent = msg;
        document.getElementById('panel-imports').prepend(div);
        setTimeout(() => div.remove(), 6000);
    }
    function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
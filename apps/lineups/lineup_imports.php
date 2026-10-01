<?php
// ─── lineup_imports.php ───────────────────────────────────────────────────────
// Replacement for imports_partial.php in the Imports tab.
// Included inside whatever tab-panel wrapper the Index page uses for this tab.
// Not a standalone page — no <html>/<body>.
//
// NOTE: this file is self-scoped under .lu-imports-root (not a parent panel ID)
// so it renders correctly no matter what wrapper div/id the Index page uses for
// this tab. Does not depend on matching #panel-imports.
//
// Layout: a Partial/Complete mode toggle at the top of the page, then a
// left-hand nav list (File / Import Specs / Preview Data) + a fixed-size right
// "module" container that swaps content based on the selected nav item.
// The container's size never changes — only its contents scroll internally.
//
// AJAX actions used by this file (handled server-side in imports_logic.php,
// posted to index.php exactly like imports_partial.php did):
//   - load_import_logic  (festival_id, [import_file])
//   - save_import_logic  (festival_id, logic_data JSON incl. "complete")
//
// STATUS:
//   - "File"         wired up (drop zone / file picker)
//   - "Import Specs" wired up (column map, valid days/stages, attendees,
//                     stage format, load/save, partial/complete mode)
//   - "Preview Data" still a placeholder
//
// REQUIRES A BACKEND CHANGE — see accompanying notes:
//   1. New column on lineups_import_logic: `complete` TINYINT(1) NOT NULL DEFAULT 0
//   2. sp_lineups_save_import_logic needs a 7th param for `complete`
//   3. imports_logic.php's save_import_logic / load_import_logic handlers need
//      to pass/return that field (see patch notes provided separately).
// ─────────────────────────────────────────────────────────────────────────────
?>
<style>
    /* ── Scoped variables (matches imports_partial.php palette) ── */
    .lu-imports-root {
        --lu-surface:    #161920;
        --lu-surface-2:  #1e2130;
        --lu-border:     #2a2f45;
        --lu-accent:     #f4a01c;
        --lu-accent-dim: #7a5010;
        --lu-danger:     #e8475f;
        --lu-success:    #2ecc8a;
        --lu-text:       #e8eaf2;
        --lu-text-dim:   #7880a0;
        font-family: 'DM Sans', sans-serif;
        font-size: 15px;
    }

    /* ── Mode toggle (top of page) ── */
    .lu-imports-root .lu-mode-bar {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 20px;
        padding: 14px 20px;
        background: var(--lu-surface);
        border: 1px solid var(--lu-border);
        border-radius: 8px;
    }
    .lu-imports-root .lu-mode-label {
        font-family: 'DM Mono', monospace;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--lu-text-dim);
        white-space: nowrap;
    }
    .lu-imports-root .lu-mode-switch {
        display: inline-flex;
        border: 1px solid var(--lu-border);
        border-radius: 6px;
        overflow: hidden;
    }
    .lu-imports-root .lu-mode-btn {
        padding: 8px 20px;
        border: none;
        background: var(--lu-surface-2);
        color: var(--lu-text-dim);
        font-family: 'Bebas Neue', sans-serif;
        font-size: 15px;
        letter-spacing: 1.5px;
        cursor: pointer;
        transition: background .15s, color .15s;
    }
    .lu-imports-root .lu-mode-btn + .lu-mode-btn { border-left: 1px solid var(--lu-border); }
    .lu-imports-root .lu-mode-btn.active.mode-partial { background: rgba(244,160,28,.16); color: var(--lu-accent); }
    .lu-imports-root .lu-mode-btn.active.mode-complete { background: rgba(46,204,138,.16); color: var(--lu-success); }
    .lu-imports-root .lu-mode-btn:not(.active):hover { color: var(--lu-text); }
    .lu-imports-root .lu-mode-hint {
        font-size: 13px;
        color: var(--lu-text-dim);
        flex: 1;
    }

    /* ── Overall layout ── */
    .lu-imports-root .lu-layout {
        display: flex;
        align-items: flex-start;
        gap: 24px;
    }

    /* ── Left nav ── */
    .lu-imports-root .lu-sidebar {
        flex: 0 0 220px;
        width: 220px;
        background: var(--lu-surface);
        border: 1px solid var(--lu-border);
        border-radius: 8px;
        overflow: hidden;
    }
    .lu-imports-root .lu-nav-item {
        padding: 16px 20px;
        font-family: 'Bebas Neue', sans-serif;
        font-size: 17px;
        letter-spacing: 1.5px;
        color: var(--lu-text-dim);
        cursor: pointer;
        border-bottom: 1px solid var(--lu-border);
        transition: background .15s, color .15s;
        user-select: none;
    }
    .lu-imports-root .lu-nav-item:last-child { border-bottom: none; }
    .lu-imports-root .lu-nav-item:hover {
        background: var(--lu-surface-2);
        color: var(--lu-text);
    }
    .lu-imports-root .lu-nav-item.active {
        background: var(--lu-surface-2);
        color: var(--lu-accent);
        box-shadow: inset 3px 0 0 var(--lu-accent);
    }
    .lu-imports-root .lu-nav-item.lu-disabled {
        cursor: default;
    }

    /* ── Right module container (fixed size, never resizes) ── */
    .lu-imports-root .lu-module-container {
        flex: 1 1 auto;
        height: 560px;           /* fixed height regardless of module content */
        background: var(--lu-surface);
        border: 1px solid var(--lu-border);
        border-radius: 8px;
        padding: 28px 32px;
        overflow-y: auto;        /* internal scroll instead of resizing */
        box-sizing: border-box;
    }
    .lu-imports-root .lu-module { display: none; }
    .lu-imports-root .lu-module.active { display: block; }

    .lu-imports-root .lu-module-title {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 18px;
        letter-spacing: 2px;
        color: var(--lu-text-dim);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .lu-imports-root .lu-module-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--lu-border);
    }

    .lu-imports-root label {
        display: block;
        font-size: 12px;
        font-family: 'DM Mono', monospace;
        color: var(--lu-text-dim);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    /* ── Drop zone (ported from imports_partial.php) ── */
    .lu-imports-root .lu-drop-zone {
        border: 2px dashed var(--lu-border);
        border-radius: 8px;
        padding: 48px 32px;
        text-align: center;
        cursor: pointer;
        transition: border-color .2s, background .2s;
        position: relative;
    }
    .lu-imports-root .lu-drop-zone:hover,
    .lu-imports-root .lu-drop-zone.dragover {
        border-color: var(--lu-accent);
        background: rgba(244,160,28,.04);
    }
    .lu-imports-root .lu-drop-zone.has-file {
        border-color: var(--lu-success);
        background: rgba(46,204,138,.04);
    }
    .lu-imports-root .lu-drop-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    .lu-imports-root .lu-drop-icon {
        font-size: 36px;
        margin-bottom: 12px;
        display: block;
    }
    .lu-imports-root .lu-drop-zone p { color: var(--lu-text-dim); font-size: 14px; }
    .lu-imports-root .lu-file-name {
        color: var(--lu-success);
        font-family: 'DM Mono', monospace;
        font-size: 14px;
        font-weight: 500;
        margin-top: 8px;
    }

    .lu-imports-root .lu-hidden { display: none !important; }

    .lu-imports-root .lu-alert {
        border-radius: 6px;
        padding: 14px 18px;
        font-size: 14px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .lu-imports-root .lu-alert-danger  { background: rgba(232,71,95,.12);  border: 1px solid rgba(232,71,95,.3);  color: #f08090; }
    .lu-imports-root .lu-alert-info    { background: rgba(244,160,28,.10); border: 1px solid rgba(244,160,28,.25); color: var(--lu-accent); }
    .lu-imports-root .lu-alert-success { background: rgba(46,204,138,.12); border: 1px solid rgba(46,204,138,.3); color: var(--lu-success); }

    /* ── Placeholder module ── */
    .lu-imports-root .lu-placeholder {
        color: var(--lu-text-dim);
        font-size: 14px;
        line-height: 1.6;
    }

    /* ── Buttons ── */
    .lu-imports-root .lu-btn {
        padding: 10px 24px;
        border-radius: 6px;
        border: none;
        font-family: 'Bebas Neue', sans-serif;
        font-size: 16px;
        letter-spacing: 1.5px;
        cursor: pointer;
        transition: opacity .2s, transform .1s;
    }
    .lu-imports-root .lu-btn:active { transform: scale(.97); }
    .lu-imports-root .lu-btn-save { background: var(--lu-accent); color: #0d0f14; }
    .lu-imports-root .lu-btn-save:hover { opacity: .88; }

    /* ── Import Specs: layout grid ── */
    .lu-imports-root .lu-logic-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    .lu-imports-root .lu-logic-full { grid-column: 1 / -1; }
    .lu-imports-root .lu-hint {
        font-size: 12px;
        color: var(--lu-text-dim);
        margin-top: 6px;
    }

    /* ── Column map table ── */
    .lu-imports-root .lu-col-map-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .lu-imports-root .lu-col-map-table th {
        font-family: 'DM Mono', monospace;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--lu-text-dim);
        padding: 0 0 10px 0;
        text-align: left;
        border-bottom: 1px solid var(--lu-border);
    }
    .lu-imports-root .lu-col-map-table td { padding: 8px 8px 0 0; vertical-align: middle; }
    .lu-imports-root .lu-col-map-table select,
    .lu-imports-root .lu-col-map-table input[type="text"] {
        width: 100%;
        background: var(--lu-surface-2);
        color: var(--lu-text);
        border: 1px solid var(--lu-border);
        border-radius: 5px;
        padding: 7px 10px;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        box-sizing: border-box;
    }
    .lu-imports-root .lu-col-map-table select:focus,
    .lu-imports-root .lu-col-map-table input[type="text"]:focus { outline: none; border-color: var(--lu-accent); }
    .lu-imports-root .lu-col-letter {
        width: 48px; text-align: center; font-family: 'DM Mono', monospace; font-size: 13px;
        background: rgba(244,160,28,.08); border: 1px solid rgba(244,160,28,.2); color: var(--lu-accent);
        border-radius: 4px; padding: 6px 8px; box-sizing: border-box;
    }
    .lu-imports-root .lu-col-del-btn {
        background: none; border: none; color: var(--lu-text-dim);
        font-size: 18px; cursor: pointer; line-height: 1; padding: 4px 6px;
    }
    .lu-imports-root .lu-col-del-btn:hover { color: var(--lu-danger); }
    .lu-imports-root .lu-col-map-actions { display: flex; align-items: center; gap: 12px; margin-top: 12px; }
    .lu-imports-root .lu-add-col-btn,
    .lu-imports-root .lu-refresh-col-btn {
        background: var(--lu-surface-2);
        color: var(--lu-text-dim);
        border: 1px solid var(--lu-border);
        border-radius: 5px;
        padding: 7px 14px;
        font-size: 12px;
        font-family: 'DM Mono', monospace;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: color .15s, border-color .15s;
    }
    .lu-imports-root .lu-add-col-btn:hover,
    .lu-imports-root .lu-refresh-col-btn:hover { color: var(--lu-accent); border-color: var(--lu-accent-dim); }
    .lu-imports-root .lu-refresh-col-btn svg { width: 13px; height: 13px; }
    .lu-imports-root .lu-refresh-col-btn.spinning svg { animation: lu-spin .8s linear infinite; }
    @keyframes lu-spin { to { transform: rotate(360deg); } }
    .lu-imports-root .lu-refresh-toast {
        font-size: 12px; color: var(--lu-success); opacity: 0; transition: opacity .2s;
    }
    .lu-imports-root .lu-refresh-toast.visible { opacity: 1; }

    /* ── Tag inputs ── */
    .lu-imports-root .lu-tag-input-wrap {
        background: var(--lu-surface-2);
        border: 1px solid var(--lu-border);
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
    .lu-imports-root .lu-tag-input-wrap:focus-within { border-color: var(--lu-accent); }
    .lu-imports-root .lu-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(244,160,28,.12);
        border: 1px solid rgba(244,160,28,.25);
        color: var(--lu-accent);
        border-radius: 4px;
        padding: 3px 8px;
        font-family: 'DM Mono', monospace;
        font-size: 12px;
    }
    .lu-imports-root .lu-tag .lu-tag-x {
        cursor: pointer; color: var(--lu-text-dim); font-size: 14px; line-height: 1; padding: 0 1px;
    }
    .lu-imports-root .lu-tag .lu-tag-x:hover { color: var(--lu-danger); }
    .lu-imports-root .lu-tag-input-wrap input[type="text"] {
        background: transparent; border: none; outline: none; color: var(--lu-text);
        font-family: 'DM Sans', sans-serif; font-size: 13px; min-width: 100px; flex: 1; padding: 2px 4px;
    }

    /* ── Stage format rows ── */
    .lu-imports-root .lu-stage-rows { display: flex; flex-direction: column; gap: 10px; }
    .lu-imports-root .lu-stage-row {
        background: var(--lu-surface-2); border: 1px solid var(--lu-border); border-radius: 6px; padding: 14px 16px;
    }
    .lu-imports-root .lu-stage-row-grid { display: flex; align-items: flex-start; gap: 10px; flex-wrap: nowrap; }
    .lu-imports-root .lu-stage-field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
    .lu-imports-root .lu-stage-field.sf-name  { flex: 2 1 140px; }
    .lu-imports-root .lu-stage-field.sf-order { flex: 0 0 58px; }
    .lu-imports-root .lu-stage-field.sf-hex   { flex: 1.5 1 130px; }
    .lu-imports-root .lu-stage-field.sf-dim   { flex: 2 1 180px; }
    .lu-imports-root .lu-stage-field.sf-glow  { flex: 2 1 180px; }
    .lu-imports-root .lu-stage-field-label {
        font-family: 'DM Mono', monospace; font-size: 10px; text-transform: uppercase;
        letter-spacing: 1px; color: var(--lu-text-dim); white-space: nowrap;
    }
    .lu-imports-root .lu-stage-name-display {
        background: rgba(244,160,28,.08); border: 1px solid rgba(244,160,28,.25); border-radius: 5px;
        padding: 7px 10px; font-family: 'DM Mono', monospace; font-size: 13px; color: var(--lu-accent);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; height: 36px; box-sizing: border-box;
        display: flex; align-items: center;
    }
    .lu-imports-root .lu-stage-row input[type="number"] {
        background: var(--lu-surface); border: 1px solid var(--lu-border); border-radius: 5px; padding: 7px 6px;
        font-family: 'DM Mono', monospace; font-size: 13px; color: var(--lu-text); width: 100%; height: 36px;
        box-sizing: border-box; text-align: center;
    }
    .lu-imports-root .lu-stage-row input[type="number"]:focus { outline: none; border-color: var(--lu-accent); }
    .lu-imports-root .lu-sf-hex-wrap {
        display: flex; align-items: center; gap: 8px; background: var(--lu-surface); border: 1px solid var(--lu-border);
        border-radius: 5px; padding: 0 10px; height: 36px; box-sizing: border-box; cursor: pointer;
    }
    .lu-imports-root .lu-sf-hex-wrap input[type="color"] {
        width: 20px; height: 20px; border: none; border-radius: 3px; background: none; cursor: pointer; padding: 0; flex-shrink: 0;
    }
    .lu-imports-root .lu-sf-hex-text {
        font-family: 'DM Mono', monospace; font-size: 12px; color: var(--lu-text-dim);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex: 1;
    }
    .lu-imports-root .lu-sf-rgba-wrap {
        display: flex; align-items: center; gap: 8px; background: var(--lu-surface); border: 1px solid var(--lu-border);
        border-radius: 5px; padding: 0 10px; height: 36px; box-sizing: border-box; min-width: 0;
    }
    .lu-imports-root .lu-sf-rgba-swatch { width: 20px; height: 20px; border-radius: 3px; border: 1px solid rgba(255,255,255,0.1); flex-shrink: 0; }
    .lu-imports-root .lu-sf-rgba-text {
        font-family: 'DM Mono', monospace; font-size: 12px; color: var(--lu-text-dim);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex: 1;
    }
    .lu-imports-root .lu-sf-alpha-row { display: flex; align-items: center; gap: 5px; margin-top: 5px; }
    .lu-imports-root .lu-sf-alpha-label {
        font-family: 'DM Mono', monospace; font-size: 9px; text-transform: uppercase; letter-spacing: .8px;
        color: var(--lu-text-dim); flex-shrink: 0;
    }
    .lu-imports-root .lu-sf-alpha-input {
        width: 46px; background: var(--lu-surface-2); border: 1px solid var(--lu-border); border-radius: 4px;
        padding: 2px 5px; font-family: 'DM Mono', monospace; font-size: 11px; color: var(--lu-text);
        text-align: center; outline: none; box-sizing: border-box; height: 22px;
    }
    .lu-imports-root .lu-sf-alpha-input:focus { border-color: var(--lu-accent); }
    .lu-imports-root .lu-stage-del-btn {
        background: none; border: none; color: var(--lu-text-dim); font-size: 18px; cursor: pointer; line-height: 1; padding: 4px 6px;
    }
    .lu-imports-root .lu-stage-del-btn:hover { color: var(--lu-danger); }
    .lu-imports-root .lu-stage-select-wrap { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
    .lu-imports-root .lu-stage-select-wrap select {
        background: var(--lu-surface-2); color: var(--lu-text); border: 1px solid var(--lu-border);
        border-radius: 5px; padding: 7px 10px; font-size: 13px;
    }

    .lu-imports-root #lu-logic-status { font-size: 13px; font-family: 'DM Mono', monospace; }

    /* ── Preview Data ── */
    .lu-imports-root .lu-btn-run {
        background: var(--lu-surface-2);
        color: var(--lu-accent);
        border: 1px solid var(--lu-accent-dim);
    }
    .lu-imports-root .lu-btn-run:hover:not(:disabled) { background: rgba(244,160,28,.1); }
    .lu-imports-root .lu-btn-import { background: var(--lu-accent); color: #0d0f14; }
    .lu-imports-root .lu-btn-import:hover:not(:disabled) { opacity: .88; }
    .lu-imports-root .lu-btn:disabled { opacity: .35; cursor: not-allowed; }

    .lu-imports-root .lu-mode-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-family: 'DM Mono', monospace;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-left: 10px;
        vertical-align: middle;
    }
    .lu-imports-root .lu-mode-badge.badge-partial  { background: rgba(244,160,28,.15); color: var(--lu-accent); border: 1px solid rgba(244,160,28,.3); }
    .lu-imports-root .lu-mode-badge.badge-complete { background: rgba(46,204,138,.15); color: var(--lu-success); border: 1px solid rgba(46,204,138,.3); }
    .lu-imports-root .lu-mode-badge.badge-db       { background: rgba(120,128,160,.15); color: var(--lu-text-dim); border: 1px solid rgba(120,128,160,.3); }

    .lu-imports-root .lu-btn-run + .lu-btn-run { margin-left: 10px; }

    .lu-imports-root .lu-summary-bar {
        display: flex;
        gap: 32px;
        padding: 16px 24px;
        background: var(--lu-surface-2);
        border: 1px solid var(--lu-border);
        border-radius: 6px;
        margin-bottom: 20px;
    }
    .lu-imports-root .lu-summary-stat { text-align: center; }
    .lu-imports-root .lu-summary-stat .num {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 32px;
        color: var(--lu-accent);
        line-height: 1;
    }
    .lu-imports-root .lu-summary-stat .lbl {
        font-size: 11px;
        font-family: 'DM Mono', monospace;
        color: var(--lu-text-dim);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 4px;
    }
    .lu-imports-root .lu-summary-stat.danger .num { color: var(--lu-danger); }
    .lu-imports-root .lu-summary-stat.ok .num    { color: var(--lu-success); }

    .lu-imports-root .lu-error-list {
        background: rgba(232,71,95,.07);
        border: 1px solid rgba(232,71,95,.2);
        border-radius: 6px;
        padding: 16px 20px;
        margin-bottom: 20px;
    }
    .lu-imports-root .lu-error-list h4 {
        color: var(--lu-danger);
        font-size: 13px;
        font-family: 'DM Mono', monospace;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }
    .lu-imports-root .lu-error-list ul { list-style: none; }
    .lu-imports-root .lu-error-list li {
        font-size: 13px;
        color: #f08090;
        padding: 3px 0;
        border-bottom: 1px solid rgba(232,71,95,.1);
    }
    .lu-imports-root .lu-error-list li:last-child { border-bottom: none; }
    .lu-imports-root .lu-error-list li::before { content: '✕  '; }

    .lu-imports-root .lu-table-wrap {
        overflow-x: auto;
        border-radius: 6px;
        border: 1px solid var(--lu-border);
    }
    .lu-imports-root table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .lu-imports-root thead tr { background: var(--lu-surface-2); }
    .lu-imports-root thead th {
        padding: 10px 14px;
        text-align: left;
        font-family: 'DM Mono', monospace;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--lu-text-dim);
        white-space: nowrap;
        border-bottom: 1px solid var(--lu-border);
    }
    .lu-imports-root tbody tr { border-bottom: 1px solid rgba(42,47,69,.6); transition: background .15s; }
    .lu-imports-root tbody tr:hover { background: var(--lu-surface-2); }
    .lu-imports-root tbody tr.row-error { background: rgba(232,71,95,.08); }
    .lu-imports-root tbody tr.row-error:hover { background: rgba(232,71,95,.13); }
    .lu-imports-root tbody td { padding: 9px 14px; color: var(--lu-text); white-space: nowrap; }
    .lu-imports-root tbody td.mono { font-family: 'DM Mono', monospace; color: var(--lu-text-dim); }

    .lu-imports-root .lu-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-family: 'DM Mono', monospace;
        font-weight: 500;
    }
    .lu-imports-root .badge-want { background: rgba(244,160,28,.2); color: var(--lu-accent); }
    .lu-imports-root .badge-need { background: rgba(46,204,138,.2); color: var(--lu-success); }
    .lu-imports-root .badge-err  { background: rgba(232,71,95,.2);  color: var(--lu-danger);  }

    .lu-imports-root .lu-stage-pill {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-family: 'DM Mono', monospace;
        background: var(--lu-surface-2);
        border: 1px solid var(--lu-border);
        color: var(--lu-text-dim);
    }

    /* ── Loader overlay ── */
    .lu-imports-root #lu-loader {
        position: fixed; inset: 0; background: rgba(13,15,20,.75);
        display: flex; align-items: center; justify-content: center;
        z-index: 999; backdrop-filter: blur(2px);
    }
    .lu-imports-root #lu-loader .loader-box {
        background: var(--lu-surface); border: 1px solid var(--lu-border);
        border-radius: 10px; padding: 32px 48px; text-align: center;
    }
    .lu-imports-root #lu-loader .big-spin {
        width: 40px; height: 40px;
        border: 3px solid rgba(244,160,28,.2);
        border-top-color: var(--lu-accent);
        border-radius: 50%;
        animation: lu-spin .8s linear infinite;
        margin: 0 auto 16px;
    }
    .lu-imports-root #lu-loader .loader-box p { font-family: 'DM Mono', monospace; font-size: 13px; color: var(--lu-text-dim); }

    /* ── Confirm modal ── */
    .lu-imports-root #lu-confirm-modal {
        position: fixed; inset: 0; background: rgba(13,15,20,.80);
        display: flex; align-items: center; justify-content: center;
        z-index: 1000; backdrop-filter: blur(3px);
    }
    .lu-imports-root #lu-confirm-modal .modal-box {
        background: var(--lu-surface); border: 1px solid var(--lu-border);
        border-radius: 10px; padding: 36px 40px; max-width: 480px; width: 90%;
    }
    .lu-imports-root #lu-confirm-modal .modal-box h3 {
        font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 2px;
        color: var(--lu-danger); margin-bottom: 14px;
    }
    .lu-imports-root #lu-confirm-modal .modal-box p { font-size: 14px; color: var(--lu-text-dim); line-height: 1.6; margin-bottom: 8px; }
    .lu-imports-root #lu-confirm-modal .modal-box p strong { color: var(--lu-text); }
    .lu-imports-root #lu-confirm-modal .modal-divider { border: none; border-top: 1px solid var(--lu-border); margin: 24px 0; }
    .lu-imports-root #lu-confirm-modal .modal-btn-row { display: flex; gap: 12px; justify-content: flex-end; }
    .lu-imports-root .lu-btn-cancel {
        background: transparent; color: var(--lu-text-dim); border: 1px solid var(--lu-border);
        font-family: 'DM Sans', sans-serif; font-size: 14px; letter-spacing: 0; padding: 10px 24px; border-radius: 6px; cursor: pointer;
    }
    .lu-imports-root .lu-btn-cancel:hover { color: var(--lu-text); border-color: var(--lu-text-dim); }
</style>

<div class="lu-imports-root">

    <div id="lu-loader" class="lu-hidden">
        <div class="loader-box">
            <div class="big-spin"></div>
            <p id="lu-loader-msg">Processing...</p>
        </div>
    </div>

    <div id="lu-confirm-modal" class="lu-hidden">
        <div class="modal-box">
            <h3>⚠ Existing Data Detected</h3>
            <p>There are already <strong id="lu-modal-count"></strong> set times recorded for <strong id="lu-modal-festival-name"></strong>.</p>
            <p>Proceeding will <strong style="color:#e8475f;">permanently delete</strong> all existing set times and viewer preferences for this festival before importing the new data.</p>
            <hr class="modal-divider">
            <div class="modal-btn-row">
                <button class="lu-btn-cancel" id="lu-modal-cancel">Cancel</button>
                <button class="lu-btn lu-btn-import" id="lu-modal-proceed">Proceed with Import</button>
            </div>
        </div>
    </div>

    <!-- ── Partial / Complete mode toggle (top of the whole Import page) ── -->
    <div class="lu-mode-bar" id="lu-mode-bar">
        <span class="lu-mode-label">This Upload Is:</span>
        <div class="lu-mode-switch">
            <button type="button" class="lu-mode-btn mode-partial active" id="lu-mode-partial" data-mode="partial">Partial List</button>
            <button type="button" class="lu-mode-btn mode-complete" id="lu-mode-complete" data-mode="complete">Complete List</button>
        </div>
        <span class="lu-mode-hint" id="lu-mode-hint">Partial: some columns may be missing entirely — that's expected. Complete: the full data set.</span>
    </div>

    <div class="lu-layout">

        <!-- ── Left nav ── -->
        <div class="lu-sidebar" id="lu-sidebar">
            <div class="lu-nav-item active" data-module="file">File</div>
            <div class="lu-nav-item" data-module="specs">Import Specs</div>
            <div class="lu-nav-item" data-module="preview">Preview Data</div>
        </div>

        <!-- ── Right module container (fixed size) ── -->
        <div class="lu-module-container" id="lu-module-container">

            <!-- ── File module ── -->
            <div class="lu-module active" id="lu-module-file">
                <div class="lu-module-title">Import File</div>

                <label>Import File (.xlsx)</label>
                <div class="lu-drop-zone" id="lu-drop-zone">
                    <input type="file" id="lu-file-input" accept=".xlsx">
                    <span class="lu-drop-icon">📂</span>
                    <p>Drag &amp; drop your <strong>.xlsx</strong> file here, or click to browse</p>
                    <div class="lu-file-name lu-hidden" id="lu-file-name-display"></div>
                </div>
            </div>

            <!-- ── Import Specs module ── -->
            <div class="lu-module" id="lu-module-specs">
                <div class="lu-module-title">Import Specs</div>

                <div id="lu-logic-alert-slot"></div>

                <div class="lu-logic-grid">

                    <!-- Column Map -->
                    <div class="lu-logic-full">
                        <label>Column Mapping</label>
                        <table class="lu-col-map-table">
                            <thead>
                                <tr>
                                    <th style="width:50px;">Col</th>
                                    <th style="width:160px;">Field</th>
                                    <th>Label</th>
                                    <th style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="lu-col-map-body"></tbody>
                        </table>
                        <div class="lu-col-map-actions">
                            <button class="lu-add-col-btn" id="lu-add-col-btn">+ Add Column</button>
                            <button class="lu-refresh-col-btn" id="lu-refresh-col-btn" title="Read file data and populate Days, Stages, and Attendees from the current column mapping">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M13.5 2.5A6.5 6.5 0 1 1 4 3.8"/>
                                    <polyline points="1,1 4,4 7,1"/>
                                </svg>
                                Refresh from File
                            </button>
                            <span class="lu-refresh-toast" id="lu-refresh-toast"></span>
                        </div>
                        <p class="lu-hint">Only map the columns present in this upload. In Partial mode it's fine to leave fields out entirely.</p>
                    </div>

                    <!-- Valid Days -->
                    <div>
                        <label>Valid Days</label>
                        <div class="lu-tag-input-wrap" id="lu-days-wrap">
                            <input type="text" id="lu-days-input" placeholder="Type a day, press Enter…">
                        </div>
                        <p class="lu-hint">e.g. friday, saturday, sunday</p>
                    </div>

                    <!-- Valid Stages -->
                    <div>
                        <label>Valid Stages</label>
                        <div class="lu-tag-input-wrap" id="lu-stages-wrap">
                            <input type="text" id="lu-stages-input" placeholder="Type a stage, press Enter…">
                        </div>
                        <p class="lu-hint">Must match stage_format keys (lowercase)</p>
                    </div>

                    <!-- Attendees -->
                    <div>
                        <label>Attendees</label>
                        <div class="lu-tag-input-wrap" id="lu-attendees-wrap">
                            <input type="text" id="lu-attendees-input" placeholder="Type a name, press Enter…">
                        </div>
                        <p class="lu-hint">Viewer names as they appear in the spreadsheet</p>
                    </div>

                    <!-- Stage Format -->
                    <div class="lu-logic-full">
                        <label>Stage Format</label>
                        <div class="lu-stage-rows" id="lu-stage-rows"></div>
                        <div class="lu-stage-select-wrap">
                            <select id="lu-stage-select">
                                <option value="">+ Add Stage…</option>
                            </select>
                            <span class="lu-hint">Populated from Valid Stages</span>
                        </div>
                    </div>

                </div>

                <div style="margin-top: 28px; display:flex; gap:12px; align-items:center;">
                    <button class="lu-btn lu-btn-save" id="lu-btn-save-logic">Save Logic</button>
                    <span id="lu-logic-status" class="lu-hidden"></span>
                </div>
            </div>

            <!-- ── Preview Data module ── -->
            <div class="lu-module" id="lu-module-preview">
                <div class="lu-module-title">
                    Preview Data
                    <span id="lu-preview-mode-badge" class="lu-mode-badge lu-hidden"></span>
                </div>

                <div id="lu-preview-alert-slot"></div>

                <div style="margin-bottom: 20px;">
                    <button class="lu-btn lu-btn-run" id="lu-btn-preview-file">From File</button>
                    <button class="lu-btn lu-btn-run" id="lu-btn-preview-db">From DB</button>
                </div>

                <div id="lu-preview-results" class="lu-hidden">

                    <div class="lu-summary-bar" id="lu-summary-bar"></div>

                    <div id="lu-error-block" class="lu-error-list lu-hidden">
                        <h4>Errors Detected — Fix before importing</h4>
                        <ul id="lu-error-list"></ul>
                    </div>

                    <div id="lu-clean-block" class="lu-hidden">
                        <div class="lu-alert lu-alert-success">
                            ✓ &nbsp;No errors detected. Ready to import.
                        </div>
                        <div style="margin-bottom:20px;">
                            <button class="lu-btn lu-btn-import" id="lu-btn-import">Import to Database</button>
                        </div>
                    </div>

                    <div id="lu-import-result" class="lu-hidden"></div>

                    <div class="lu-table-wrap">
                        <table id="lu-preview-table">
                            <thead id="lu-preview-thead"></thead>
                            <tbody id="lu-preview-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div><!-- /.lu-imports-root -->

<script>
(function () {
    // ── Use the global festival dropdown from the parent page ──
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

    // ── Left nav → right module switching ──
    const navItems = document.querySelectorAll('#lu-sidebar .lu-nav-item');
    const modules   = document.querySelectorAll('.lu-module-container .lu-module');

    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const target = item.dataset.module;

            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            modules.forEach(m => m.classList.remove('active'));
            const targetModule = document.getElementById('lu-module-' + target);
            if (targetModule) targetModule.classList.add('active');

            if (target === 'specs') loadImportLogic();
        });
    });

    // ── Partial / Complete mode toggle ──
    let importMode = 'partial'; // 'partial' | 'complete'
    const modeBtns = document.querySelectorAll('#lu-mode-bar .lu-mode-btn');
    const modeHint = document.getElementById('lu-mode-hint');

    function setMode(mode, opts) {
        opts = opts || {};
        importMode = mode;
        modeBtns.forEach(b => b.classList.toggle('active', b.dataset.mode === mode));
        modeHint.textContent = mode === 'complete'
            ? 'Complete: this file has the full data set — every field is expected to be present.'
            : "Partial: some columns may be missing entirely — that's expected.";
    }

    modeBtns.forEach(btn => {
        btn.addEventListener('click', () => setMode(btn.dataset.mode));
    });

    // ── File drop zone (ported from imports_partial.php) ──
    const fileInput   = document.getElementById('lu-file-input');
    const dropZone    = document.getElementById('lu-drop-zone');
    const fileNameDisp = document.getElementById('lu-file-name-display');

    let selectedFile = null;
    window.lineupImportSelectedFile = null;

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
        window.lineupImportSelectedFile = f;
        dropZone.classList.add('has-file');
        fileNameDisp.textContent = '📄 ' + f.name;
        fileNameDisp.classList.remove('lu-hidden');
    }

    function showAlert(msg, type, slotId) {
        const div = document.createElement('div');
        div.className = `lu-alert lu-alert-${type}`;
        div.textContent = msg;
        const slot = document.getElementById(slotId || 'lu-module-file');
        slot.prepend(div);
        setTimeout(() => div.remove(), 6000);
    }

    function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ── Import Specs ──
    // ═══════════════════════════════════════════════════════════════════════

    const btnSaveLogic = document.getElementById('lu-btn-save-logic');
    const logicStatus  = document.getElementById('lu-logic-status');

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

    function indexToLetter(i) {
        let s = '';
        i++;
        while (i > 0) { let r = (i - 1) % 26; s = String.fromCharCode(65 + r) + s; i = Math.floor((i - 1) / 26); }
        return s;
    }

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

    function renderColMapRows(colMap) {
        const tbody = document.getElementById('lu-col-map-body');
        tbody.innerHTML = '';
        const entries = Object.entries(colMap).sort(([a],[b]) => a.localeCompare(b));
        entries.forEach(([letter, mapping]) => addColMapRow(letter, mapping.field || '', mapping.label || ''));
    }

    function addColMapRow(letter, field, label) {
        const tbody = document.getElementById('lu-col-map-body');
        const tr = document.createElement('tr');
        tr.dataset.letter = letter;

        const tdLetter = document.createElement('td');
        const letterInput = document.createElement('input');
        letterInput.type = 'text';
        letterInput.className = 'lu-col-letter';
        letterInput.value = letter;
        letterInput.maxLength = 3;
        letterInput.addEventListener('input', () => { tr.dataset.letter = letterInput.value.toUpperCase(); letterInput.value = letterInput.value.toUpperCase(); });
        tdLetter.appendChild(letterInput);

        const tdField = document.createElement('td');
        const sel = makeFieldSelect(field);
        tdField.appendChild(sel);

        const tdLabel = document.createElement('td');
        const labelInput = document.createElement('input');
        labelInput.type = 'text';
        labelInput.value = label;
        labelInput.placeholder = 'Display label…';
        tdLabel.appendChild(labelInput);

        const tdDel = document.createElement('td');
        const delBtn = document.createElement('button');
        delBtn.className = 'lu-col-del-btn';
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

    document.getElementById('lu-add-col-btn').addEventListener('click', () => {
        const rows = document.getElementById('lu-col-map-body').rows;
        addColMapRow(indexToLetter(rows.length), '', '');
    });

    // ── Refresh from File ──
    document.getElementById('lu-refresh-col-btn').addEventListener('click', async () => {
        if (!selectedFile) {
            showAlert('Select a file first (in the File section), then click Refresh.', 'danger', 'lu-logic-alert-slot');
            return;
        }

        const btn   = document.getElementById('lu-refresh-col-btn');
        const toast = document.getElementById('lu-refresh-toast');
        btn.classList.add('spinning');
        toast.textContent = '';
        toast.classList.remove('visible');

        try {
            const letterToField  = {};
            const attendeeLabels = [];

            document.getElementById('lu-col-map-body').querySelectorAll('tr').forEach(tr => {
                const inputs = tr.querySelectorAll('input[type="text"]');
                const letter = inputs[0].value.trim().toUpperCase();
                const field  = tr.querySelector('select').value;
                const label  = inputs[1].value.trim();
                if (letter && field) {
                    letterToField[letter] = field;
                    if (field === 'attendee' && label) attendeeLabels.push(label);
                }
            });

            const colData = await readXlsxColumns(selectedFile, letterToField);

            let added = { days: 0, stages: 0, attendees: 0 };

            if (colData.day && colData.day.length) {
                const existing = new Set(daysTagInput.getTags());
                const fresh = colData.day.filter(v => !existing.has(v));
                if (fresh.length) { daysTagInput.setTags([...existing, ...fresh]); added.days = fresh.length; }
            }
            if (colData.stage && colData.stage.length) {
                const existing = new Set(stagesTagInput.getTags());
                const fresh = colData.stage.filter(v => !existing.has(v));
                if (fresh.length) { stagesTagInput.setTags([...existing, ...fresh]); added.stages = fresh.length; }
            }
            if (attendeeLabels.length) {
                const existing = new Set(attendeesTagInput.getTags());
                const fresh = attendeeLabels.filter(v => !existing.has(v));
                if (fresh.length) { attendeesTagInput.setTags([...existing, ...fresh]); added.attendees = fresh.length; }
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
            showAlert('Could not read file: ' + e.message, 'danger', 'lu-logic-alert-slot');
        }
    });

    // ── Parse xlsx in-browser and extract unique values per mapped field ──
    async function readXlsxColumns(file, letterToField) {
        const buffer = await file.arrayBuffer();
        const uint8  = new Uint8Array(buffer);
        const zip    = await parseZipAsync(uint8);

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

        const sheetEntry = zip['xl/worksheets/sheet1.xml'];
        if (!sheetEntry) throw new Error('sheet1.xml not found in file');
        const sheetText = decodeUtf8(sheetEntry).replace(/xmlns="[^"]*"/g, '');
        const sheetXml = new DOMParser().parseFromString(sheetText, 'text/xml');

        const fieldForLetter = {};
        for (const [letter, field] of Object.entries(letterToField)) {
            if (['day', 'stage'].includes(field)) fieldForLetter[letter.toUpperCase()] = field;
        }
        if (Object.keys(fieldForLetter).length === 0) return {};

        const result = {};
        let isFirstRow = true;

        for (const row of sheetXml.getElementsByTagName('row')) {
            if (isFirstRow) { isFirstRow = false; continue; }
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
        for (const [field, set] of Object.entries(result)) out[field] = [...set].sort();
        return out;
    }

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
            wrap.querySelectorAll('.lu-tag').forEach(t => t.remove());
            tags.forEach((val, idx) => {
                const span = document.createElement('span');
                span.className = 'lu-tag';
                span.innerHTML = `${escHtml(val)} <span class="lu-tag-x" data-idx="${idx}" title="Remove">×</span>`;
                span.querySelector('.lu-tag-x').addEventListener('click', () => {
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

    const daysTagInput      = initTagInput('lu-days-wrap',      'lu-days-input',      []);
    const stagesTagInputRaw = initTagInput('lu-stages-wrap',    'lu-stages-input',    []);
    const attendeesTagInput = initTagInput('lu-attendees-wrap', 'lu-attendees-input', []);

    // ── Stage format rows ──
    function refreshStageDropdown() {
        const select = document.getElementById('lu-stage-select');
        const usedNames = new Set(
            [...document.getElementById('lu-stage-rows').querySelectorAll('.lu-stage-row')]
                .map(r => r.dataset.stageName)
        );
        const allStages = stagesTagInput.getTags();
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

    // Wrap setTags so the stage dropdown stays in sync
    const stagesTagInput = {
        getTags: stagesTagInputRaw.getTags,
        setTags: (arr) => { stagesTagInputRaw.setTags(arr); refreshStageDropdown(); }
    };

    function renderStageRows(stageFormat) {
        const container = document.getElementById('lu-stage-rows');
        container.innerHTML = '';
        if (!stageFormat) { refreshStageDropdown(); return; }
        const entries = Object.entries(stageFormat).sort(([,a],[,b]) => (a.order||0) - (b.order||0));
        entries.forEach(([name, cfg]) => addStageRow(name, cfg));
        refreshStageDropdown();
    }

    function addStageRow(name, cfg) {
        const container = document.getElementById('lu-stage-rows');
        const row = document.createElement('div');
        row.className = 'lu-stage-row';
        row.dataset.stageName = name;

        const hexVal = cfg.hex || '#ffffff';

        function parseRgbaAlpha(val, defaultAlpha) {
            const m = val && val.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
            if (m) return parseFloat(m[4] !== undefined ? m[4] : defaultAlpha);
            return defaultAlpha;
        }

        const dimAlpha  = parseRgbaAlpha(cfg['hex-dim'],  0.12);
        const glowAlpha = parseRgbaAlpha(cfg['hex-glow'], 0.35);

        function makeRgba(hex, alpha) { return `rgba(${hexToRgba(hex)},${alpha})`; }

        const dimRgba  = makeRgba(hexVal, dimAlpha);
        const glowRgba = makeRgba(hexVal, glowAlpha);

        row.innerHTML = `
            <div class="lu-stage-row-grid">
                <div class="lu-stage-field sf-name">
                    <span class="lu-stage-field-label">Stage Name</span>
                    <div class="lu-stage-name-display">${escHtml(name)}</div>
                </div>
                <div class="lu-stage-field sf-order">
                    <span class="lu-stage-field-label">Order</span>
                    <input type="number" class="sf-order-input" value="${escHtml(String(cfg.order || ''))}" placeholder="#" min="1" max="99">
                </div>
                <div class="lu-stage-field sf-hex">
                    <span class="lu-stage-field-label">Hex</span>
                    <div class="lu-sf-hex-wrap">
                        <input type="color" class="sf-color-hex" value="${escHtml(hexVal)}">
                        <span class="sf-hex-text lu-sf-hex-text">${escHtml(hexVal)}</span>
                    </div>
                </div>
                <div class="lu-stage-field sf-dim">
                    <span class="lu-stage-field-label">Hex-Dim</span>
                    <div class="lu-sf-rgba-wrap">
                        <div class="lu-sf-rgba-swatch sf-dim-swatch" style="background:rgba(${hexToRgba(hexVal)},${Math.min(dimAlpha+0.25,1)});"></div>
                        <span class="sf-dim-text lu-sf-rgba-text">${escHtml(dimRgba)}</span>
                    </div>
                    <div class="lu-sf-alpha-row">
                        <span class="lu-sf-alpha-label">Alpha</span>
                        <input type="number" class="sf-dim-alpha lu-sf-alpha-input" value="${dimAlpha}" min="0" max="1" step="0.01" title="Dim alpha">
                    </div>
                </div>
                <div class="lu-stage-field sf-glow">
                    <span class="lu-stage-field-label">Hex-Glow</span>
                    <div class="lu-sf-rgba-wrap">
                        <div class="lu-sf-rgba-swatch sf-glow-swatch" style="background:rgba(${hexToRgba(hexVal)},${Math.min(glowAlpha+0.25,1)});"></div>
                        <span class="sf-glow-text lu-sf-rgba-text">${escHtml(glowRgba)}</span>
                    </div>
                    <div class="lu-sf-alpha-row">
                        <span class="lu-sf-alpha-label">Alpha</span>
                        <input type="number" class="sf-glow-alpha lu-sf-alpha-input" value="${glowAlpha}" min="0" max="1" step="0.01" title="Glow alpha">
                    </div>
                </div>
                <button class="lu-stage-del-btn" title="Remove stage" style="align-self:flex-start;margin-top:19px;">×</button>
            </div>
        `;

        function updateRgbaFields() {
            const hex   = row.querySelector('.sf-color-hex').value;
            const rgb   = hexToRgba(hex);
            const dAlpha = parseFloat(row.querySelector('.sf-dim-alpha').value)  || 0;
            const gAlpha = parseFloat(row.querySelector('.sf-glow-alpha').value) || 0;

            row.querySelector('.sf-dim-text').textContent  = `rgba(${rgb},${dAlpha})`;
            row.querySelector('.sf-glow-text').textContent = `rgba(${rgb},${gAlpha})`;
            row.querySelector('.sf-dim-swatch').style.background  = `rgba(${rgb},${Math.min(dAlpha + 0.25, 1)})`;
            row.querySelector('.sf-glow-swatch').style.background = `rgba(${rgb},${Math.min(gAlpha + 0.25, 1)})`;
        }

        row.querySelector('.sf-color-hex').addEventListener('input', function() {
            row.querySelector('.sf-hex-text').textContent = this.value;
            updateRgbaFields();
        });
        row.querySelector('.sf-dim-alpha').addEventListener('input', updateRgbaFields);
        row.querySelector('.sf-glow-alpha').addEventListener('input', updateRgbaFields);
        row.querySelector('.lu-stage-del-btn').addEventListener('click', () => {
            row.remove();
            refreshStageDropdown();
        });

        container.appendChild(row);
    }

    document.getElementById('lu-stage-select').addEventListener('change', function() {
        const name = this.value;
        if (!name) return;
        addStageRow(name, {
            order: document.getElementById('lu-stage-rows').children.length + 1,
            hex: '#ffffff',
            'hex-dim':  'rgba(255,255,255,0.12)',
            'hex-glow': 'rgba(255,255,255,0.35)',
        });
        refreshStageDropdown();
        this.value = '';
    });

    function collectStageFormat() {
        const result = {};
        document.getElementById('lu-stage-rows').querySelectorAll('.lu-stage-row').forEach(row => {
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

    function collectColMap() {
        const result = {};
        document.getElementById('lu-col-map-body').querySelectorAll('tr').forEach(tr => {
            const inputs = tr.querySelectorAll('input[type="text"]');
            const letter = inputs[0].value.trim().toUpperCase();
            const field  = tr.querySelector('select').value;
            const label  = inputs[1].value.trim();
            if (letter && field) result[letter] = { table: 'festival_transactions', field, label };
        });
        return result;
    }

    function setLogicStatus(msg, type) {
        logicStatus.textContent = msg;
        logicStatus.style.color = type === 'success' ? 'var(--lu-success)' : type === 'danger' ? 'var(--lu-danger)' : 'var(--lu-text-dim)';
        logicStatus.classList.remove('lu-hidden');
        if (type !== 'danger') setTimeout(() => logicStatus.classList.add('lu-hidden'), 4000);
    }

    // ── Load Import Logic (called when the Import Specs module is opened) ──
    let logicLoading = false;
    async function loadImportLogic() {
        if (logicLoading) return;
        const festivalId = festivalSelect.value;
        if (!festivalId) { showAlert('No festival selected.', 'danger', 'lu-logic-alert-slot'); return; }

        logicLoading = true;
        try {
            const fd = new FormData();
            fd.append('action', 'load_import_logic');
            fd.append('festival_id', festivalId);
            if (selectedFile) fd.append('import_file', selectedFile);

            const res  = await fetch('index.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (!data.success) {
                showAlert(data.message || 'Could not load import logic.', 'danger', 'lu-logic-alert-slot');
                return;
            }

            if (data.found) {
                renderColMapRows(data.column_map || {});
                daysTagInput.setTags(data.valid_days || []);
                stagesTagInput.setTags(data.valid_stages || []);
                attendeesTagInput.setTags(data.attendees || []);
                renderStageRows(data.stage_format || {});
                // `complete` comes back as 0/1 (or "0"/"1") from the DB
                setMode((data.complete == 1) ? 'complete' : 'partial');
                setLogicStatus('✓ Existing logic loaded', 'success');
            } else {
                renderColMapRows(data.column_map_from_file || {});
                daysTagInput.setTags([]);
                stagesTagInput.setTags([]);
                attendeesTagInput.setTags([]);
                renderStageRows({});
                setMode('partial');
                setLogicStatus('No logic found — populated from spreadsheet headers. Fill in the details and save.', 'info');
                logicStatus.style.color = 'var(--lu-accent)';
                logicStatus.classList.remove('lu-hidden');
            }
        } catch(e) {
            showAlert('Unexpected error: ' + e.message, 'danger', 'lu-logic-alert-slot');
        } finally {
            logicLoading = false;
        }
    }

    // ── Save Import Logic ──
    btnSaveLogic.addEventListener('click', async () => {
        const festivalId = festivalSelect.value;
        if (!festivalId) { showAlert('No festival selected.', 'danger', 'lu-logic-alert-slot'); return; }

        const payload = {
            column_map:   collectColMap(),
            valid_days:   daysTagInput.getTags(),
            valid_stages: stagesTagInput.getTags(),
            attendees:    attendeesTagInput.getTags(),
            stage_format: collectStageFormat(),
            complete:     importMode === 'complete' ? 1 : 0,
        };

        setLogicStatus('Saving…', 'info');
        try {
            const fd = new FormData();
            fd.append('action', 'save_import_logic');
            fd.append('festival_id', festivalId);
            fd.append('logic_data', JSON.stringify(payload));

            const res  = await fetch('index.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                setLogicStatus('✓ Logic saved successfully (' + (importMode === 'complete' ? 'Complete' : 'Partial') + ')', 'success');
            } else {
                setLogicStatus('✕ ' + (data.message || 'Save failed'), 'danger');
            }
        } catch(e) {
            setLogicStatus('✕ Unexpected error: ' + e.message, 'danger');
        }
    });

    // ═══════════════════════════════════════════════════════════════════════
    // ── Preview Data ──
    // ═══════════════════════════════════════════════════════════════════════

    const loader          = document.getElementById('lu-loader');
    const loaderMsg       = document.getElementById('lu-loader-msg');
    const btnPreviewFile  = document.getElementById('lu-btn-preview-file');
    const btnPreviewDb    = document.getElementById('lu-btn-preview-db');
    const previewResults  = document.getElementById('lu-preview-results');
    const btnImport       = document.getElementById('lu-btn-import');
    const previewModeBadge = document.getElementById('lu-preview-mode-badge');

    let previewRows    = null;
    let previewViewers = [];
    let importing      = false;

    function showLoader(msg) {
        loaderMsg.textContent = msg || 'Processing...';
        loader.classList.remove('lu-hidden');
    }
    function hideLoader() {
        loader.classList.add('lu-hidden');
    }

    btnPreviewFile.addEventListener('click', async () => {
        if (!selectedFile) {
            showAlert('Select a file first (in the File section).', 'danger', 'lu-preview-alert-slot');
            return;
        }
        const festivalId = festivalSelect.value;
        if (!festivalId) {
            showAlert('No festival selected.', 'danger', 'lu-preview-alert-slot');
            return;
        }
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

            if (!data.rows) {
                // Top-level failure — no logic configured, bad file, etc.
                showAlert((data.errors || ['Preview failed.']).join(' '), 'danger', 'lu-preview-alert-slot');
                previewResults.classList.add('lu-hidden');
                return;
            }

            renderPreview(data, 'file');
        } catch(e) {
            hideLoader();
            showAlert('Unexpected error during preview: ' + e.message, 'danger', 'lu-preview-alert-slot');
        }
    });

    btnPreviewDb.addEventListener('click', async () => {
        const festivalId = festivalSelect.value;
        if (!festivalId) {
            showAlert('No festival selected.', 'danger', 'lu-preview-alert-slot');
            return;
        }
        const festivalName = festivalSelect.options[festivalSelect.selectedIndex].dataset.name;

        const fd = new FormData();
        fd.append('action', 'preview_db');
        fd.append('festival_id', festivalId);
        fd.append('festival_name', festivalName);

        showLoader('Loading from database...');
        try {
            const res  = await fetch('index.php', { method: 'POST', body: fd });
            const data = await res.json();
            hideLoader();

            if (!data.success) {
                showAlert((data.errors || ['Could not load data from the database.']).join(' '), 'danger', 'lu-preview-alert-slot');
                previewResults.classList.add('lu-hidden');
                return;
            }

            if (!data.rows || data.rows.length === 0) {
                showAlert('No set times found in the database for this festival.', 'info', 'lu-preview-alert-slot');
                previewResults.classList.add('lu-hidden');
                return;
            }

            renderPreview(data, 'db');
        } catch(e) {
            hideLoader();
            showAlert('Unexpected error loading from database: ' + e.message, 'danger', 'lu-preview-alert-slot');
        }
    });

    function renderPreview(data, source) {
        source = source || 'file';
        document.getElementById('lu-import-result').classList.add('lu-hidden');

        previewRows    = data.rows         || [];
        previewViewers = data.viewer_names || [];

        previewResults.classList.remove('lu-hidden');

        if (source === 'db') {
            previewModeBadge.textContent = 'From Database';
            previewModeBadge.className   = 'lu-mode-badge badge-db';
            previewModeBadge.classList.remove('lu-hidden');
        } else if (typeof data.complete !== 'undefined') {
            const isComplete = data.complete == 1;
            previewModeBadge.textContent = isComplete ? 'Complete List' : 'Partial List';
            previewModeBadge.className   = 'lu-mode-badge ' + (isComplete ? 'badge-complete' : 'badge-partial');
            previewModeBadge.classList.remove('lu-hidden');
        } else {
            previewModeBadge.classList.add('lu-hidden');
        }

        const errorCount = source === 'db' ? 0 : (data.errors || []).length;
        const errorRows  = source === 'db' ? 0 : previewRows.filter(r => r.has_error).length;
        const prefCount  = previewRows.reduce((s, r) => s + r.preferences.length, 0);

        document.getElementById('lu-summary-bar').innerHTML = `
            <div class="lu-summary-stat"><div class="num">${previewRows.length}</div><div class="lbl">Set Times</div></div>
            <div class="lu-summary-stat"><div class="num">${prefCount}</div><div class="lbl">Viewer Prefs</div></div>
            <div class="lu-summary-stat"><div class="num">${previewViewers.length}</div><div class="lbl">Viewers</div></div>
            <div class="lu-summary-stat ${errorRows > 0 ? 'danger' : 'ok'}">
                <div class="num">${errorRows}</div><div class="lbl">Errors</div>
            </div>`;

        const errorBlock = document.getElementById('lu-error-block');
        const errorList  = document.getElementById('lu-error-list');
        const cleanBlock = document.getElementById('lu-clean-block');

        if (source === 'db') {
            // Read-only DB view — no error/clean/import UI at all.
            errorBlock.classList.add('lu-hidden');
            cleanBlock.classList.add('lu-hidden');
            btnImport.disabled = true;
        } else if (errorCount > 0) {
            errorBlock.classList.remove('lu-hidden');
            cleanBlock.classList.add('lu-hidden');
            errorList.innerHTML = (data.errors || []).map(e => `<li>${escHtml(e)}</li>`).join('');
            btnImport.disabled = true;
        } else {
            errorBlock.classList.add('lu-hidden');
            cleanBlock.classList.remove('lu-hidden');
            btnImport.disabled = false;
        }

        const thead = document.getElementById('lu-preview-thead');
        const tbody = document.getElementById('lu-preview-tbody');

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
                    ? `<span class="lu-badge badge-want">W</span>`
                    : `<span class="lu-badge badge-need">N</span>`;
                return `<td>${badge}</td>`;
            }).join('');

            const status = row.has_error
                ? `<span class="lu-badge badge-err">Error</span>`
                : `<span style="color:var(--lu-success);font-size:13px;">✓</span>`;

            return `<tr class="${row.has_error ? 'row-error' : ''}">
                <td class="mono">${row.row_num}</td>
                <td>${escHtml(row.day)}</td>
                <td class="mono">${escHtml(row.start_Time)}</td>
                <td class="mono">${escHtml(row.end_Time)}</td>
                <td>${escHtml(row.performer)}</td>
                <td><span class="lu-stage-pill">${escHtml(row.stage)}</span></td>
                ${viewerCells}
                <td>${status}</td>
            </tr>`;
        }).join('');
    }

    // ── Import (with existing-data confirmation) ──
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
                document.getElementById('lu-modal-count').textContent         = checkData.count + ' set time' + (checkData.count !== 1 ? 's' : '');
                document.getElementById('lu-modal-festival-name').textContent = festivalName;
                document.getElementById('lu-confirm-modal').classList.remove('lu-hidden');
            } else {
                await runImport(festivalId, festivalName);
            }
        } catch(e) {
            hideLoader();
            showAlert('Unexpected error during check: ' + e.message, 'danger', 'lu-preview-alert-slot');
        }
    });

    document.getElementById('lu-modal-cancel').addEventListener('click', () => {
        document.getElementById('lu-confirm-modal').classList.add('lu-hidden');
    });

    document.getElementById('lu-modal-proceed').addEventListener('click', async () => {
        document.getElementById('lu-confirm-modal').classList.add('lu-hidden');
        const festivalId   = festivalSelect.value;
        const festivalName = festivalSelect.options[festivalSelect.selectedIndex].dataset.name;
        await runImport(festivalId, festivalName);
    });

    // ── Shared import logic — always deletes existing data and imports fresh ──
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

            const resultDiv = document.getElementById('lu-import-result');
            resultDiv.classList.remove('lu-hidden');

            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="lu-alert lu-alert-success">
                        ✓ &nbsp;<strong>Import complete!</strong>
                        &nbsp;${data.trans_count} set times imported,
                        ${data.pref_count} viewer preferences recorded.
                    </div>`;
                btnImport.disabled = true;
            } else {
                importing = false;
                btnImport.disabled = false;
                resultDiv.innerHTML = `<div class="lu-alert lu-alert-danger">✕ &nbsp;${escHtml(data.message)}</div>`;
            }
        } catch(e) {
            importing = false;
            btnImport.disabled = false;
            hideLoader();
            showAlert('Unexpected error during import: ' + e.message, 'danger', 'lu-preview-alert-slot');
        }
    }

})();
</script>
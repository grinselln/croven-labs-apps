<?php
// ─── LineupViewer.php ─────────────────────────────────────────────────────
// HTML partial for the Lineup tab panel. Included by index.php (#panel-lineup).
// No PHP data fetching here: data is loaded client-side from
//   index.php?action=lineup_data&festival_id=N
// Listens for the 'festivalChanged' event dispatched by index.php.
// Read-only: shows each attendee's want / need per performer.
// ─────────────────────────────────────────────────────────────────────────
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@400;600;700&family=Barlow:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ── Lineup panel: all rules scoped to #panel-lineup ─────────────────────── */
#panel-lineup {
    --lu-bg:       #0a0a0f;
    --lu-surface:  #111118;
    --lu-surface2: #18181f;
    --lu-border:   #2a2a38;
    --lu-text:     #e8e8f0;
    --lu-text-dim: #7a7a99;
    --lu-want:     #69f0ae;
    --lu-need:     #ff5252;

    font-family: 'Barlow', sans-serif;
    color: var(--lu-text);
    background: var(--lu-bg);
    border-radius: 12px;
    overflow: hidden;
    min-height: 240px;
}

/* ── Loading / error ── */
#lu-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 220px;
    color: var(--lu-text-dim);
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 15px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
#lu-loading svg { animation: lu-spin 1s linear infinite; }
@keyframes lu-spin { to { transform: rotate(360deg); } }

#lu-error {
    max-width: 540px;
    margin: 48px auto;
    text-align: center;
    padding: 32px;
    background: var(--lu-surface);
    border: 1px solid var(--lu-border);
    border-radius: 12px;
    display: none;
}
#lu-error h2 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 24px;
    letter-spacing: 0.1em;
    color: var(--lu-need);
    margin-bottom: 8px;
}
#lu-error p { font-size: 14px; color: var(--lu-text-dim); }

/* ── Header ── */
#lu-header {
    text-align: center;
    padding: 32px 24px 20px;
    border-bottom: 1px solid var(--lu-border);
    background: linear-gradient(180deg, #0d0d1a 0%, var(--lu-bg) 100%);
    display: none;
}
#lu-festival-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(28px, 5vw, 56px);
    letter-spacing: 0.04em;
    line-height: 0.95;
    background: linear-gradient(135deg, #fff 0%, var(--apex, #00e5ff) 40%, #fff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
#lu-festival-sub {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.3em;
    color: var(--lu-text-dim);
    margin-top: 6px;
    text-transform: uppercase;
}

/* ── Controls ── */
#lu-controls {
    background: rgba(10,10,15,0.92);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--lu-border);
    padding: 0 24px;
    display: none;
}
#lu-controls-inner {
    max-width: 1600px;
    margin: 0 auto;
    display: flex;
    align-items: stretch;
}
#lu-day-tabs { display: flex; flex: 1; overflow-x: auto; }

.lu-day-tab {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 20px;
    letter-spacing: 0.08em;
    padding: 16px 28px;
    border: none;
    background: none;
    color: var(--lu-text-dim);
    cursor: pointer;
    position: relative;
    transition: color 0.2s;
    white-space: nowrap;
}
.lu-day-tab::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2px;
    background: var(--apex, #00e5ff);
    transform: scaleX(0);
    transition: transform 0.25s ease;
}
.lu-day-tab:hover { color: var(--lu-text); }
.lu-day-tab.active { color: #fff; }
.lu-day-tab.active::after { transform: scaleX(1); }
.lu-day-tab:focus-visible { outline: 2px solid var(--apex, #00e5ff); outline-offset: -2px; }

#lu-search-filter {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-left: 1px solid var(--lu-border);
    padding-left: 20px;
    margin-left: 8px;
}
#lu-search-wrap { position: relative; }
#lu-search-wrap svg {
    position: absolute;
    left: 10px; top: 50%;
    transform: translateY(-50%);
    color: var(--lu-text-dim);
    pointer-events: none;
}
#lu-search-input {
    background: var(--lu-surface2);
    border: 1px solid var(--lu-border);
    border-radius: 6px;
    color: var(--lu-text);
    font-family: 'Barlow', sans-serif;
    font-size: 13px;
    padding: 8px 12px 8px 34px;
    width: 200px;
    outline: none;
    transition: border-color 0.2s;
}
#lu-search-input::placeholder { color: var(--lu-text-dim); }
#lu-search-input:focus { border-color: var(--apex, #00e5ff); }

/* ── Filter row (stage + viewer) ── */
#lu-filter-row {
    max-width: 1600px;
    margin: 0 auto;
    display: flex;
    flex-wrap: wrap;
    gap: 8px 28px;
    padding: 10px 0 12px;
    border-top: 1px solid var(--lu-border);
}
.lu-filter-group {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}
.lu-filter-group:empty { display: none; }
.lu-filter-label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.06em;
    color: var(--lu-text-dim);
    margin-right: 2px;
}
.lu-filter-btn {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 7px 13px;
    border-radius: 6px;
    border: 1px solid var(--lu-border);
    background: transparent;
    color: var(--lu-text-dim);
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.lu-filter-btn:hover { border-color: var(--lu-text-dim); color: var(--lu-text); }
.lu-filter-btn.active { background: var(--lu-text-dim); border-color: var(--lu-text-dim); color: #000; }
.lu-filter-btn:focus-visible { outline: 2px solid var(--apex, #00e5ff); outline-offset: 2px; }

/* ── Lineup table ── */
#lu-lineup {
    max-width: 1600px;
    margin: 0 auto;
    padding: 24px 24px 40px;
    display: none;
}
.lu-day-panel { display: none; }
.lu-day-panel.active { display: block; }

.lu-day-count {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.06em;
    color: var(--lu-text-dim);
    margin-bottom: 10px;
}

.lu-table-wrap {
    overflow-x: auto;
    border: 1px solid var(--lu-border);
    border-radius: 8px;
    background: var(--lu-surface);
}
.lu-table {
    width: 100%;
    border-collapse: collapse;
}
.lu-table th {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 17px;
    font-weight: 400;
    letter-spacing: 0.1em;
    text-align: left;
    color: var(--lu-text-dim);
    background: var(--lu-surface2);
    padding: 12px 16px;
    border-bottom: 1px solid var(--lu-border);
    white-space: nowrap;
}
.lu-table th.lu-col-pref,
.lu-table td.lu-col-pref { text-align: center; width: 110px; }

.lu-table td {
    padding: 10px 16px;
    border-bottom: 1px solid var(--lu-border);
    vertical-align: middle;
}
.lu-table tbody tr:last-child td { border-bottom: none; }
.lu-table tbody tr:hover td { background: rgba(255,255,255,0.03); }

.lu-performer {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 17px;
    font-weight: 700;
    letter-spacing: 0.03em;
}

.lu-stage-badge {
    display: inline-block;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 15px;
    letter-spacing: 0.1em;
    padding: 3px 10px;
    border-radius: 5px;
    border: 1px solid var(--lu-border);
    color: var(--lu-text-dim);
    white-space: nowrap;
}

.lu-pref {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.06em;
}
.lu-pref.want { color: var(--lu-want); }
.lu-pref.need { color: var(--lu-need); }

.lu-row.dimmed { display: none; }

.lu-no-results {
    display: none;
    text-align: center;
    padding: 60px 20px;
    color: var(--lu-text-dim);
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 18px;
    letter-spacing: 0.1em;
}

/* ── Footer ── */
#lu-footer {
    text-align: center;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.2em;
    color: var(--lu-text-dim);
    text-transform: uppercase;
    padding: 16px;
    border-top: 1px solid var(--lu-border);
    display: none;
}

@media (max-width: 640px) {
    #lu-controls-inner { flex-direction: column; align-items: stretch; }
    #lu-search-filter  { border-left: none; border-top: 1px solid var(--lu-border); padding-left: 0; margin-left: 0; }
    #lu-search-input   { width: 100%; }
    .lu-day-tab        { padding: 14px 16px; font-size: 17px; }
    #lu-lineup         { padding: 16px 12px 32px; }
    .lu-table th, .lu-table td { padding: 8px 10px; }
}
@media (prefers-reduced-motion: reduce) {
    #lu-loading svg { animation: none; }
    .lu-day-tab::after { transition: none; }
}
</style>

<!-- ── Lineup shell ─────────────────────────────────────────────────────── -->
<div id="lu-loading">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
    </svg>
    <span>Select a festival to load the lineup</span>
</div>

<div id="lu-error"><h2>Error</h2><p id="lu-error-msg"></p></div>

<div id="lu-header">
    <div id="lu-festival-title"></div>
    <div id="lu-festival-sub"></div>
</div>

<div id="lu-controls">
    <div id="lu-controls-inner">
        <nav id="lu-day-tabs"></nav>
        <div id="lu-search-filter">
            <div id="lu-search-wrap">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input id="lu-search-input" type="text" placeholder="Search artists…">
            </div>
        </div>
    </div>
    <div id="lu-filter-row">
        <div class="lu-filter-group" id="lu-stage-filters"></div>
        <div class="lu-filter-group" id="lu-viewer-filters"></div>
    </div>
</div>

<div id="lu-lineup"></div>
<div id="lu-footer"></div>

<script>
(function () {
    let currentFestId = null;
    let currentDays   = [];
    let stageFormat   = {};
    let activeStages  = new Set(['all']);
    let activeViewers = new Set(['all']);

    const elLoading  = document.getElementById('lu-loading');
    const elError    = document.getElementById('lu-error');
    const elErrMsg   = document.getElementById('lu-error-msg');
    const elHeader   = document.getElementById('lu-header');
    const elTitle    = document.getElementById('lu-festival-title');
    const elSub      = document.getElementById('lu-festival-sub');
    const elControls = document.getElementById('lu-controls');
    const elDayTabs  = document.getElementById('lu-day-tabs');
    const elStages   = document.getElementById('lu-stage-filters');
    const elViewers  = document.getElementById('lu-viewer-filters');
    const elSearch   = document.getElementById('lu-search-input');
    const elLineup   = document.getElementById('lu-lineup');
    const elFooter   = document.getElementById('lu-footer');

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showOnly(which) {
        elLoading.style.display  = which === 'loading' ? 'flex'  : 'none';
        elError.style.display    = which === 'error'   ? 'block' : 'none';
        elHeader.style.display   = which === 'data'    ? 'block' : 'none';
        elControls.style.display = which === 'data'    ? 'block' : 'none';
        elLineup.style.display   = which === 'data'    ? 'block' : 'none';
        elFooter.style.display   = which === 'data'    ? 'block' : 'none';
    }

    function stageBadge(stage) {
        const m = stageFormat[stage];
        const style = (m && m.hex)
            ? ` style="color:${esc(m.hex)};background:${esc(m['hex-dim'] || 'transparent')};border-color:${esc(m.hex)};"`
            : '';
        return `<span class="lu-stage-badge"${style}>${esc(stage || '—')}</span>`;
    }

    // Wire up a toggle-button group (with an "All" button) to a Set of active values.
    function buildFilterGroup(el, label, allLabel, values, activeSet) {
        if (!values.length) { el.innerHTML = ''; return; }
        el.innerHTML =
            `<span class="lu-filter-label">${esc(label)}</span>` +
            `<button class="lu-filter-btn active" data-val="all">${esc(allLabel)}</button>` +
            values.map(v => `<button class="lu-filter-btn" data-val="${esc(v)}">${esc(v)}</button>`).join('');
        el.querySelectorAll('.lu-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const v = btn.dataset.val;
                if (v === 'all') { activeSet.clear(); activeSet.add('all'); }
                else {
                    if (activeSet.has(v)) activeSet.delete(v);
                    else { activeSet.delete('all'); activeSet.add(v); }
                    if (activeSet.size === 0) activeSet.add('all');
                }
                el.querySelectorAll('.lu-filter-btn').forEach(b =>
                    b.classList.toggle('active', activeSet.has(b.dataset.val))
                );
                applyFilters();
            });
        });
    }

    function render(data) {
        const { festival_name, days, stage_format, viewers, lineup, transaction_count, festival_id } = data;
        currentDays   = days;
        stageFormat   = stage_format || {};
        activeStages  = new Set(['all']);
        activeViewers = new Set(['all']);

        // Stage list: configured stages (in order) plus any stage found in the data but not configured
        const stageNames = Object.keys(stageFormat);
        days.forEach(day => (lineup[day] || []).forEach(r => {
            if (r.stage && !stageNames.includes(r.stage)) stageNames.push(r.stage);
        }));

        elTitle.textContent = festival_name;
        elSub.textContent   = `LINEUP  ·  ${days.length} DAYS  ·  ${stageNames.length} STAGES`;

        // Day tabs (always opens on the first day)
        elDayTabs.innerHTML = days.map((day, i) =>
            `<button class="lu-day-tab${i===0?' active':''}" data-day="${esc(day)}">${esc(day)}</button>`
        ).join('');
        elDayTabs.querySelectorAll('.lu-day-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                elDayTabs.querySelectorAll('.lu-day-tab').forEach(t => t.classList.remove('active'));
                elLineup.querySelectorAll('.lu-day-panel').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                const panel = document.getElementById('lu-panel-' + tab.dataset.day);
                if (panel) panel.classList.add('active');
                applyFilters();
            });
        });

        // Filters
        buildFilterGroup(elStages,  'Stage',  'All stages', stageNames, activeStages);
        buildFilterGroup(elViewers, 'Viewer', 'All',        viewers,    activeViewers);

        // Day panels
        elLineup.innerHTML = '';
        days.forEach((day, dayIdx) => {
            const rows = lineup[day] || [];
            const panel = document.createElement('section');
            panel.className = 'lu-day-panel' + (dayIdx === 0 ? ' active' : '');
            panel.id = 'lu-panel-' + day;

            const head =
                `<tr><th>Performer</th><th>Stage</th>` +
                viewers.map(v => `<th class="lu-col-pref">${esc(v)}</th>`).join('') +
                `</tr>`;

            const body = rows.map(r => {
                const prefs = r.prefs || {};
                const prefCells = viewers.map(v => {
                    const t = prefs[v];
                    return `<td class="lu-col-pref">` +
                        (t ? `<span class="lu-pref ${esc(t)}">${t === 'need' ? 'Need' : 'Want'}</span>` : '') +
                        `</td>`;
                }).join('');
                const viewerKeys = viewers.filter(v => prefs[v]).map(v => v.toLowerCase()).join('|');
                return `<tr class="lu-row" data-name="${esc((r.performer || '').toLowerCase())}" data-stage="${esc(r.stage)}" data-viewers="${esc(viewerKeys)}">` +
                    `<td class="lu-performer">${esc(r.performer || 'Unknown Artist')}</td>` +
                    `<td>${stageBadge(r.stage)}</td>` +
                    prefCells + `</tr>`;
            }).join('');

            panel.innerHTML =
                `<div class="lu-day-count" id="lu-count-${esc(day)}"></div>` +
                `<div class="lu-table-wrap"><table class="lu-table"><thead>${head}</thead><tbody>${body}</tbody></table></div>` +
                `<div class="lu-no-results" id="lu-no-results-${esc(day)}">No artists match your filters.</div>`;
            elLineup.appendChild(panel);
        });

        elFooter.textContent = `FESTIVAL ID: ${esc(String(festival_id))}  ·  ${transaction_count} SETS LOADED`;

        showOnly('data');
        elSearch.value = '';
        applyFilters();
    }

    function applyFilters() {
        const query      = elSearch.value.toLowerCase().trim();
        const allStages  = activeStages.has('all');
        const allViewers = activeViewers.has('all');

        currentDays.forEach(day => {
            const panel = document.getElementById('lu-panel-' + day);
            if (!panel) return;
            const rows = [...panel.querySelectorAll('.lu-row')];
            let shown = 0;

            rows.forEach(r => {
                const nameOk  = !query || (r.dataset.name || '').includes(query);
                const stageOk = allStages || activeStages.has(r.dataset.stage);
                let viewerOk  = allViewers;
                if (!allViewers) {
                    const rv = (r.dataset.viewers || '').split('|').filter(Boolean);
                    viewerOk = [...activeViewers].some(v => rv.includes(v.toLowerCase()));
                }
                const visible = nameOk && stageOk && viewerOk;
                r.classList.toggle('dimmed', !visible);
                if (visible) shown++;
            });

            const wrap = panel.querySelector('.lu-table-wrap');
            if (wrap) wrap.style.display = shown ? '' : 'none';
            const nr = document.getElementById('lu-no-results-' + day);
            if (nr) nr.style.display = shown ? 'none' : 'block';
            const cnt = document.getElementById('lu-count-' + day);
            if (cnt) cnt.textContent = shown === rows.length
                ? `${rows.length} performer${rows.length === 1 ? '' : 's'}`
                : `${shown} of ${rows.length} performers`;
        });
    }

    elSearch.addEventListener('input', applyFilters);

    async function loadFestival(festivalId) {
        if (!festivalId) {
            currentDays   = [];
            currentFestId = null;
            showOnly('loading');
            return;
        }
        if (festivalId === currentFestId) return;
        currentFestId = festivalId;
        showOnly('loading');

        try {
            const res  = await fetch(`index.php?action=lineup_data&festival_id=${encodeURIComponent(festivalId)}`);
            const data = await res.json();
            if (!res.ok || data.error) {
                elErrMsg.textContent = data.error || 'Failed to load lineup.';
                showOnly('error');
                currentFestId = null;
            } else {
                render(data);
            }
        } catch (e) {
            elErrMsg.textContent = 'Network error: ' + e.message;
            showOnly('error');
            currentFestId = null;
        }
    }

    // Festival selection from the parent page
    document.addEventListener('festivalChanged', e => {
        loadFestival(e.detail.id || null);
    });

    // In case a festival was already selected before this script ran
    if (window.selectedFestival && window.selectedFestival.id) {
        loadFestival(window.selectedFestival.id);
    }
})();
</script>

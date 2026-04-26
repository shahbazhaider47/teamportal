/**
 * printing.js  —  RCM Centric export engine
 * Handles: Excel (.xls), CSV, Print (browser dialog)
 *
 * ──────────────────────────────────────────────────────────────
 * HOW TO SKIP COLUMNS
 *   On any <th> add one of:
 *     data-export="false"          — skip in ALL exports
 *     data-export-excel="false"    — skip in Excel only
 *     data-export-csv="false"      — skip in CSV only
 *     data-export-print="false"    — skip in Print only
 *   Or add class  no-export  to the <th> (skips everywhere).
 *
 * Auto-skipped always:
 *   - Columns whose header text is "action" / "actions" / "#" / ""
 *   - Columns that contain only checkboxes (first data cell is an <input>)
 *
 * HOW TO CLEAN A CELL'S TEXT
 *   Add  data-export-value="clean text"  on any <td> and that
 *   value will be used instead of the cell's visible text.
 *   Useful for badge cells, status cells with icons, etc.
 *
 * PRINT CONTEXT  (set before this file loads, or anytime before click)
 *   window.APP_PRINT_CONTEXT = {
 *     pageTitle   : 'AR Report',        // required — falls back to <title>
 *     companyName : 'RCM Centric',
 *     companyLogo : '/path/to/logo.png',
 *     printedBy   : 'Shahbaz',
 *     printedAt   : '2025-04-15 10:30', // auto-generated if omitted
 *   };
 * ──────────────────────────────────────────────────────────────
 */

(function () {
    'use strict';

    // ── Guard against double-bind ──────────────────────────────
    if (window.__RCM_EXPORT_BOUND__) return;
    window.__RCM_EXPORT_BOUND__ = true;

    /* ==========================================================
     * 1. TABLE DISCOVERY
     * ========================================================== */

    /**
     * Walk up from the clicked button and find the nearest <table>.
     * Search order: same card/panel → same section → first on page.
     */
    function findTable(btn) {
        const scopes = [
            btn.closest('.card'),
            btn.closest('.table-responsive'),
            btn.closest('[data-export-scope]'),
            btn.closest('.container-fluid'),
            btn.closest('.content'),
            document.body,
        ];

        for (const scope of scopes) {
            if (!scope) continue;
            const t = scope.querySelector('table');
            if (t) return t;
        }
        return null;
    }

    /* ==========================================================
     * 2. COLUMN ANALYSIS
     * ========================================================== */

    const AUTO_SKIP_HEADERS = new Set(['action', 'actions', '#', '']);

    /**
     * Returns a Set of column indexes that should be skipped
     * for the given export type ('excel' | 'csv' | 'print').
     */
    function getSkipIndexes(table, exportType) {
        const skip = new Set();
        const headers = Array.from(table.querySelectorAll('thead th, thead td'));

        headers.forEach((th, idx) => {
            const headerText = th.textContent.trim().toLowerCase();

            // 1. Auto-skip by header text
            if (AUTO_SKIP_HEADERS.has(headerText)) {
                skip.add(idx);
                return;
            }

            // 2. Class-based skip
            if (th.classList.contains('no-export')) {
                skip.add(idx);
                return;
            }

            // 3. data-export="false" — skip everywhere
            if (th.dataset.export === 'false') {
                skip.add(idx);
                return;
            }

            // 4. Type-specific skip
            const typeAttr = th.dataset['export' + capitalize(exportType)];
            if (typeAttr === 'false') {
                skip.add(idx);
                return;
            }
        });

        // 5. Auto-skip checkbox columns (first data cell contains only <input>)
        const firstRow = table.querySelector('tbody tr');
        if (firstRow) {
            Array.from(firstRow.querySelectorAll('td')).forEach((td, idx) => {
                if (skip.has(idx)) return;
                const text = td.textContent.trim();
                const hasOnlyInput = text === '' && td.querySelector('input[type="checkbox"]');
                if (hasOnlyInput) skip.add(idx);
            });
        }

        return skip;
    }

    function capitalize(s) {
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    /* ==========================================================
     * 3. CELL TEXT EXTRACTION
     * ========================================================== */

    /**
     * Get clean text from a cell:
     *   1. data-export-value attribute wins
     *   2. Strip all child elements, return only text nodes
     *   3. Collapse whitespace
     */
    function cellText(cell) {
        // Explicit override on the TD
        if (cell.dataset && cell.dataset.exportValue !== undefined) {
            return cell.dataset.exportValue.trim();
        }

        // Walk text nodes only — ignores buttons, badges, icons
        let text = '';
        cell.childNodes.forEach(node => {
            if (node.nodeType === Node.TEXT_NODE) {
                text += node.textContent;
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                // Include text from elements that are NOT interactive/icon noise
                const tag = node.tagName.toLowerCase();
                const skipTags = new Set(['button', 'a', 'i', 'svg', 'img', 'input', 'select', 'textarea', 'script', 'style']);
                if (!skipTags.has(tag)) {
                    // Span/badge — grab its text but strip nested icons
                    text += extractTextSkippingIcons(node);
                }
            }
        });

        return text.replace(/\s+/g, ' ').trim();
    }

    function extractTextSkippingIcons(el) {
        let out = '';
        el.childNodes.forEach(node => {
            if (node.nodeType === Node.TEXT_NODE) {
                out += node.textContent;
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                const tag = node.tagName.toLowerCase();
                if (tag === 'i' || tag === 'svg' || tag === 'img') return;
                out += extractTextSkippingIcons(node);
            }
        });
        return out;
    }

    /* ==========================================================
     * 4. SHARED DATA EXTRACTION
     * ========================================================== */

    /**
     * Returns { headers: string[], rows: string[][] }
     * ready for Excel or CSV serialisation.
     */
    function extractData(table, exportType) {
        const skip = getSkipIndexes(table, exportType);

        // Headers
        const headerCells = Array.from(table.querySelectorAll('thead th, thead td'));
        const headers = headerCells
            .filter((_, i) => !skip.has(i))
            .map(th => cellText(th) || th.textContent.trim());

        // Rows — only visible rows (respects table filters)
        const rows = [];
        table.querySelectorAll('tbody tr').forEach(tr => {
            if (tr.style.display === 'none') return; // respect active filters
            const cells = Array.from(tr.querySelectorAll('td'));
            const row = cells
                .filter((_, i) => !skip.has(i))
                .map(td => cellText(td));
            if (row.some(v => v !== '')) rows.push(row); // skip fully empty rows
        });

        return { headers, rows };
    }

    /* ==========================================================
     * 5. FILENAME HELPER
     * ========================================================== */

    function getFilename(btn, ext) {
        const base = (btn.getAttribute('data-export-filename') || 'export')
            .replace(/[^a-z0-9_\-]/gi, '_')
            .replace(/_+/g, '_')
            .toLowerCase();
        const date = new Date().toISOString().slice(0, 10);
        return `${base}_${date}.${ext}`;
    }

    /* ==========================================================
     * 6. DOWNLOAD HELPER
     * ========================================================== */

    function triggerDownload(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        setTimeout(() => {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 200);
    }

    /* ==========================================================
     * 7. EXCEL EXPORT
     *    Uses HTML-in-XLS trick but with clean plain-text cells
     *    (no HTML tags, no buttons, no icons leaking in).
     * ========================================================== */

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-export-table');
        if (!btn) return;

        const table = findTable(btn);
        if (!table) { console.warn('[export] No table found.'); return; }

        const { headers, rows } = extractData(table, 'excel');

        // Build a clean HTML table (no classes, no inline styles from source)
        let tableHtml = '<table><thead><tr>';
        headers.forEach(h => {
            tableHtml += `<th>${escHtml(h)}</th>`;
        });
        tableHtml += '</tr></thead><tbody>';
        rows.forEach(row => {
            tableHtml += '<tr>';
            row.forEach(cell => {
                tableHtml += `<td>${escHtml(cell)}</td>`;
            });
            tableHtml += '</tr>';
        });
        tableHtml += '</tbody></table>';

        const html = [
            '<html xmlns:o="urn:schemas-microsoft-com:office:office"',
            '      xmlns:x="urn:schemas-microsoft-com:office:excel"',
            '      xmlns="http://www.w3.org/TR/REC-html40">',
            '<head><meta charset="UTF-8">',
            '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>',
            '<x:ExcelWorksheet><x:Name>Sheet1</x:Name>',
            '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>',
            '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->',
            '</head><body>',
            tableHtml,
            '</body></html>',
        ].join('\n');

        const blob = new Blob(['\uFEFF' + html], { type: 'application/vnd.ms-excel;charset=utf-8' });
        triggerDownload(blob, getFilename(btn, 'xls'));
    });

    /* ==========================================================
     * 8. CSV EXPORT
     * ========================================================== */

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-csv-table');
        if (!btn) return;

        const table = findTable(btn);
        if (!table) { console.warn('[export] No table found.'); return; }

        const { headers, rows } = extractData(table, 'csv');

        const lines = [headers, ...rows].map(row =>
            row.map(v => `"${v.replace(/"/g, '""')}"`).join(',')
        );
        const csv = lines.join('\r\n');

        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        triggerDownload(blob, getFilename(btn, 'csv'));
    });

    /* ==========================================================
     * 9. PRINT / PDF EXPORT
     * ========================================================== */

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-pdf-table');
        if (!btn) return;

        const table = findTable(btn);
        if (!table) { console.warn('[export] No table found.'); return; }

        const { headers, rows } = extractData(table, 'print');
        const ctx = Object.assign({}, window.APP_PRINT_CONTEXT || {});

        // Auto-fill missing context from the page
        if (!ctx.pageTitle)    ctx.pageTitle   = document.title || 'Report';
        if (!ctx.printedAt)    ctx.printedAt   = formatDateTime(new Date());
        if (!ctx.companyName)  ctx.companyName = '';
        if (!ctx.companyLogo)  ctx.companyLogo = '';
        if (!ctx.printedBy)    ctx.printedBy   = '';

        openPrintWindow(headers, rows, ctx);
    });

    function openPrintWindow(headers, rows, ctx) {
        const win = window.open('', '_blank', 'width=1000,height=700');
        if (!win) {
            alert('Pop-up blocked. Please allow pop-ups for this site to use Print.');
            return;
        }

        // Build table markup
        let thead = '<tr>' + headers.map(h => `<th>${escHtml(h)}</th>`).join('') + '</tr>';
        let tbody = rows.map(row =>
            '<tr>' + row.map(cell => `<td>${escHtml(cell)}</td>`).join('') + '</tr>'
        ).join('\n');

        const logoHtml = ctx.companyLogo
            ? `<img src="${escHtml(ctx.companyLogo)}" class="company-logo" alt="Logo">`
            : '';

        const metaRight = [
            ctx.printedBy  ? `<div><span>Printed by:</span> ${escHtml(ctx.printedBy)}</div>`  : '',
            ctx.printedAt  ? `<div><span>Printed on:</span> ${escHtml(ctx.printedAt)}</div>`  : '',
        ].filter(Boolean).join('');

        win.document.write(`<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>${escHtml(ctx.pageTitle)}</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11pt;
    color: #1a1a2e;
    background: #fff;
    padding: 16mm 14mm 14mm;
  }

  /* ── Header ── */
  .print-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 10px;
    margin-bottom: 14px;
    border-bottom: 2px solid #1a73e8;
  }
  .header-left {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .company-logo {
    height: 40px;
    width: auto;
    object-fit: contain;
  }
  .company-name {
    font-size: 13pt;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.2;
  }
  .header-right {
    text-align: right;
    font-size: 8.5pt;
    color: #555;
    line-height: 1.7;
  }
  .header-right span {
    font-weight: 600;
    color: #333;
  }

  /* ── Report title ── */
  .report-title {
    font-size: 13pt;
    font-weight: 700;
    margin-bottom: 12px;
    color: #1a1a2e;
  }

  /* ── Table ── */
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
    table-layout: auto;
  }
  thead th {
    background: #1a73e8;
    color: #fff;
    font-weight: 600;
    text-align: left;
    padding: 7px 8px;
    border: 1px solid #1558b0;
    white-space: nowrap;
  }
  tbody tr:nth-child(even) td {
    background: #f4f7fe;
  }
  tbody tr:nth-child(odd) td {
    background: #fff;
  }
  tbody td {
    padding: 6px 8px;
    border: 1px solid #dde3ed;
    vertical-align: top;
    word-break: break-word;
  }
  tfoot td {
    font-weight: 700;
    background: #eaf1fb;
    border: 1px solid #dde3ed;
    padding: 6px 8px;
  }

  /* ── Footer ── */
  .print-footer {
    margin-top: 14px;
    font-size: 8pt;
    color: #888;
    text-align: center;
    border-top: 1px solid #e0e0e0;
    padding-top: 6px;
  }

  /* ── Print media ── */
  @media print {
    body { padding: 0; }
    @page { margin: 10mm 8mm; size: landscape; }
    thead { display: table-header-group; }
    tbody tr { page-break-inside: avoid; }
    .no-print { display: none !important; }
  }

  /* ── Print button (screen only) ── */
  .btn-print {
    display: inline-block;
    margin-bottom: 14px;
    padding: 7px 18px;
    background: #1a73e8;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 10pt;
    cursor: pointer;
  }
  .btn-print:hover { background: #1558b0; }
</style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">&#128438; Print / Save as PDF</button>

<div class="print-header">
  <div class="header-left">
    ${logoHtml}
    ${ctx.companyName ? `<div class="company-name">${escHtml(ctx.companyName)}</div>` : ''}
  </div>
  ${metaRight ? `<div class="header-right">${metaRight}</div>` : ''}
</div>

<div class="report-title">${escHtml(ctx.pageTitle)}</div>

<table>
  <thead><tr>${headers.map(h => `<th>${escHtml(h)}</th>`).join('')}</tr></thead>
  <tbody>
    ${rows.map(row =>
        '<tr>' + row.map(cell => `<td>${escHtml(cell)}</td>`).join('') + '</tr>'
    ).join('\n    ')}
  </tbody>
</table>

<div class="print-footer">
  ${escHtml(ctx.pageTitle)} &mdash; Generated ${escHtml(ctx.printedAt)}
  ${ctx.companyName ? ' &mdash; ' + escHtml(ctx.companyName) : ''}
</div>

</body>
</html>`);

        win.document.close();
    }

    /* ==========================================================
     * 10. UTILITIES
     * ========================================================== */

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatDateTime(d) {
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ` +
               `${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

})();
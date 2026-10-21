(function () {
    const COLLAPSE_AFTER_ROWS = 12;
    let scanQueued = false;

    function addStylesheet() {
        if (document.querySelector('link[data-collapsible-lists-css]')) return;
        const stylesheet = document.createElement('link');
        stylesheet.rel = 'stylesheet';
        stylesheet.href = 'assets/css/collapsible-lists.css?v=20261021-1';
        stylesheet.dataset.collapsibleListsCss = 'true';
        document.head.appendChild(stylesheet);
    }

    function dataRows(table) {
        return Array.from(table.tBodies).flatMap(body => Array.from(body.rows)).filter(row => {
            if (row.hidden || row.cells.length < 2) return false;
            if (row.cells.length === 1 && row.cells[0].hasAttribute('colspan')) return false;
            return true;
        });
    }

    function getListTitle(wrapper, table) {
        if (wrapper.dataset.listTitle) return cleanText(wrapper.dataset.listTitle);
        const caption = table.querySelector('caption');
        if (caption && caption.textContent.trim()) return cleanText(caption.textContent);

        const panel = wrapper.closest('.rpt-card, .card, .content-box, section');
        if (panel) {
            const heading = panel.querySelector('.rpt-card-head h2, .rpt-card-head h3, .card-header h2, .card-header h3, h2, h3');
            if (heading && heading.textContent.trim()) return cleanText(heading.textContent);
        }

        const pageHeading = document.querySelector('.page-header h1, .inventory-hero h1, .header h2, main h1');
        return pageHeading && pageHeading.textContent.trim() ? cleanText(pageHeading.textContent) : 'Records';
    }

    function cleanText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim().slice(0, 70);
    }

    function countLabel(count) {
        return `${count.toLocaleString()} ${count === 1 ? 'row' : 'rows'}`;
    }

    function updateFolder(folder) {
        const table = folder.querySelector('table');
        const counter = folder.querySelector('.table-list-folder-count');
        if (!table || !counter) return;
        const count = dataRows(table).length;
        const label = countLabel(count);
        if (counter.textContent !== label) counter.textContent = label;
        const summary = folder.querySelector(':scope > summary');
        const title = folder.dataset.listTitle || 'Records';
        const accessibleLabel = `${title}, ${label}. Open or close this list.`;
        if (summary && summary.getAttribute('aria-label') !== accessibleLabel) summary.setAttribute('aria-label', accessibleLabel);
    }

    function makeFolder(wrapper) {
        const existingFolder = wrapper.closest('.table-list-folder');
        if (existingFolder) {
            updateFolder(existingFolder);
            return;
        }
        if (wrapper.closest('details, .modal, .pos-container, [data-no-collapse]')) return;

        const table = wrapper.querySelector('table');
        if (!table || table.hasAttribute('data-no-collapse')) return;
        const rows = dataRows(table);
        const forceFold = wrapper.dataset.collapsibleList === 'always';
        if (rows.length === 0 || (!forceFold && rows.length <= COLLAPSE_AFTER_ROWS)) return;

        const title = getListTitle(wrapper, table);
        const folder = document.createElement('details');
        folder.className = 'table-list-folder';
        folder.dataset.listTitle = title;

        const summary = document.createElement('summary');
        summary.className = 'table-list-folder-summary';
        summary.setAttribute('aria-label', `${title}, ${countLabel(rows.length)}. Open or close this list.`);

        const icon = document.createElement('span');
        icon.className = 'table-list-folder-icon';
        icon.innerHTML = '<i class="fas fa-table-list" aria-hidden="true"></i>';

        const name = document.createElement('span');
        name.className = 'table-list-folder-name';
        name.textContent = title;

        const counter = document.createElement('span');
        counter.className = 'table-list-folder-count';
        counter.textContent = countLabel(rows.length);

        const chevron = document.createElement('i');
        chevron.className = 'fas fa-chevron-down table-list-folder-chevron';
        chevron.setAttribute('aria-hidden', 'true');

        summary.append(icon, name, counter, chevron);
        wrapper.parentNode.insertBefore(folder, wrapper);
        folder.append(summary, wrapper);
    }

    function scanLists() {
        scanQueued = false;
        document.querySelectorAll('.table-responsive, .table-container, .shift-table-wrap, .dashboard-table-wrap').forEach(makeFolder);
    }

    function scheduleScan() {
        if (scanQueued) return;
        scanQueued = true;
        window.requestAnimationFrame(scanLists);
    }

    function isListMutation(mutation) {
        const target = mutation.target instanceof Element ? mutation.target : mutation.target.parentElement;
        const listWrapperSelector = '.table-responsive, .table-container, .shift-table-wrap, .dashboard-table-wrap, .table-list-folder';
        if (target && target.closest(listWrapperSelector)) return true;
        return Array.from(mutation.addedNodes).some(node => {
            if (!(node instanceof Element)) return false;
            return node.matches(listWrapperSelector) || Boolean(node.querySelector(listWrapperSelector));
        });
    }

    function openFilteredList(event) {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (!target.matches('.table-controls input, .table-controls select, .search-box, .sales-filter-panel input, .sales-filter-panel select')) return;

        const panel = target.closest('.content-box, .card, .rpt-card, .sales-page, .page-content');
        if (!panel) return;
        const folder = panel.querySelector('.table-list-folder');
        if (folder) folder.open = true;
    }

    function expandForPrinting() {
        document.querySelectorAll('.table-list-folder').forEach(folder => {
            folder.dataset.wasOpenBeforePrint = folder.open ? 'true' : 'false';
            folder.open = true;
        });
    }

    function restoreAfterPrinting() {
        document.querySelectorAll('.table-list-folder[data-was-open-before-print]').forEach(folder => {
            folder.open = folder.dataset.wasOpenBeforePrint === 'true';
            delete folder.dataset.wasOpenBeforePrint;
        });
    }

    function init() {
        addStylesheet();
        scanLists();

        const observer = new MutationObserver(mutations => {
            if (mutations.some(isListMutation)) scheduleScan();
        });
        observer.observe(document.body, {childList: true, subtree: true});
        document.addEventListener('input', openFilteredList);
        document.addEventListener('change', openFilteredList);
        window.addEventListener('beforeprint', expandForPrinting);
        window.addEventListener('afterprint', restoreAfterPrinting);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, {once: true});
    } else {
        init();
    }
})();

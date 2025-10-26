(function () {
    const ajaxKey = 'crawler_process_status';
    const ajaxUrl = TYPO3.settings?.ajaxUrls?.[ajaxKey];

    async function fetchStatus(id) {
        if (!ajaxUrl) {
            console.error('Missing TYPO3 AJAX URL for crawler_process_status');
            return;
        }
        try {
            const resp = await fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({id})
            });
            if (!resp.ok) {
                throw new Error(`HTTP error ${resp.status}`);
            }
            const data = await resp.json();
            updateProgress(id, data);
        } catch (err) {
            console.error('Error fetching status', err);
        }
    }

    function updateProgress(id, data) {
        const bar = document.getElementById(id);
        let status = `${data.status}%`;
        bar.style.width = status;
        bar.innerHTML = status;
        updateTableCellByClass(id, 'processedItems', `${data.processedItems}`);
        updateTableCellByClass(id, 'runtime', `${data.runtime}`);

        if (Number(data.status) >= 100) {
            bar.classList.remove('crawlerprocessprogress-bar');
            // Trigger a refresh of the page to show updated status
            document.querySelector('a[title="Refresh"]').click();
        }
    }

    function updateTableCellByClass(elementId, cellClass, newValue) {
        const el = document.getElementById(elementId);
        if (!el) return;

        const row = el.closest('tr');
        if (!row) return;

        const cell = row.querySelector(`td.${cellClass}`);
        if (cell) {
            cell.textContent = newValue;
        }
    }

    async function getElementsToUpdate() {
        const progressBars = document.getElementsByClassName('crawlerprocessprogress-bar');
        const promises = Array.from(progressBars).map(bar => fetchStatus(bar.id));
        await Promise.all(promises);
    }
    setInterval(getElementsToUpdate, 3000);
})();

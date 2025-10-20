(function () {
    const ajaxKey = 'crawler_process_status';
    const ajaxUrl = TYPO3.settings?.ajaxUrls?.[ajaxKey];

    async function fetchStatus(id) {
        try {
            const resp = await fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({id})
            });
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

        if(`${data.status}` >= 100) {
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
        let progressBars = document.getElementsByClassName('crawlerprocessprogress-bar');
        for (let i = 0; i < progressBars.length; i++) {
            await fetchStatus(progressBars[i].id);
        }
    }
    setInterval(getElementsToUpdate, 3000);
})();

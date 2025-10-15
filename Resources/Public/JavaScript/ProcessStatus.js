import AjaxRequest from "@typo3/core/ajax/ajax-request.js";

new AjaxRequest(TYPO3.settings.ajaxUrls.crawler_process_status)
    .get()
    .then(async function (response) {
        const resolved = await response.resolve();
        console.log(resolved.result);
    });

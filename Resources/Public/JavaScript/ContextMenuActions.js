/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Module:  TYPO3/CMS/Crawler/ContextMenuActions
 *
 * JavaScript to handle Version actions from context menu
 * @exports  TYPO3/CMS/Crawler/ContextMenuActions
 */
define(['jquery'], function ($) {
    'use strict';

    /**
     * @exports TYPO3/CMS/Crawler/ContextMenuActions
     */
    var ContextMenuActions = {};

    ContextMenuActions.crawlerAddPageToQueue = function (table, uid) {
        if ('pages' !== table) {
            return;
        }
        var url = TYPO3.settings.ajaxUrls['crawler_add_page_to_queue'];

        $.ajax({
            type: "get",
            url: url,
            data: {
                uid: uid
            }
        });
    };

    return ContextMenuActions;
});

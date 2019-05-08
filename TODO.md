#crawler

Found **144** matches in **1.49s** when
checking for changes and deprecations in **TYPO3 9**

 strong |  weak |  DEPRECATION |  BREAKING | 
 --- |  --- |  --- |  --- | 
 53.5% |  46.5% |  43.8% |  56.3% | 
## Classes/Backend/BackendModule.php
### Access to array key "extConf" *(weak)*
192 `$this->extensionSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler']);`

- [Deprecation: #82254 - Deprecate $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-82254-DeprecateGLOBALSTYPO3_CONF_VARSEXTextConf.html)

### Call to method "incLocalLang()" *(weak)*
207 `$this->incLocalLang();`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #80511 - AbstractFunctionModule->incLocalLang and $thisPath](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.7/Deprecation-80511-AbstractFunctionModule-incLocalLangAndThisPath.html)

### Call to method "section()" *(weak)*
274 `$theOutput = $this->pObj->doc->section($LANG->getLL('title'), $h_func, false, true);`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #72859 - Deprecate methods of DocumentTemplate](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.2/Deprecation-72859-DeprecateMethodsOfDocumentTemplate.html)

### Call to method "section()" *(weak)*
282 `$theOutput .= $this->pObj->doc->section('', $this->drawURLs(), false, true);`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #72859 - Deprecate methods of DocumentTemplate](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.2/Deprecation-72859-DeprecateMethodsOfDocumentTemplate.html)

### Call to method "section()" *(weak)*
289 `$theOutput .= $this->pObj->doc->section('', $this->drawLog(), false, true);`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #72859 - Deprecate methods of DocumentTemplate](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.2/Deprecation-72859-DeprecateMethodsOfDocumentTemplate.html)

### Call to method "section()" *(weak)*
294 `$theOutput .= $this->pObj->doc->section('', $this->drawCLIstatus(), false, true);`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #72859 - Deprecate methods of DocumentTemplate](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.2/Deprecation-72859-DeprecateMethodsOfDocumentTemplate.html)

### Call to method "section()" *(weak)*
297 `$theOutput .= $this->pObj->doc->section('', $this->drawProcessOverviewAction(), false, true);`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #72859 - Deprecate methods of DocumentTemplate](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.2/Deprecation-72859-DeprecateMethodsOfDocumentTemplate.html)

### Use of static class method call "TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj()" *(strong)*
1234 `$hookObj = &GeneralUtility::getUserObj($objRef);`

- [Deprecation: #80993 - GeneralUtility::getUserObj](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-80993-GeneralUtilitygetUserObj.html)

### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()" *(strong)*
1255 `return BackendUtility::getModuleUrl(GeneralUtility::_GP('M'), $urlParameters);`

- [Deprecation: #85113 - Legacy Backend Module Routing methods](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.3/Deprecation-85113-LegacyBackendModuleRoutingMethods.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
210 `if (empty($this->pObj->MOD_SETTINGS['processListMode'])) {`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
211 `$this->pObj->MOD_SETTINGS['processListMode'] = 'simple';`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "content" *(weak)*
215 `$this->pObj->content = str_replace('/*###POSTCSSMARKER###*/', '`

- [Deprecation: #84195 - Protected methods and properties in EditDocumentController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.html)

### Fetch of property "content" *(weak)*
217 `', $this->pObj->content);`

- [Deprecation: #84195 - Protected methods and properties in EditDocumentController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.html)

### Fetch of property "content" *(weak)*
219 `$this->pObj->content .= '<style type="text/css"><!--`

- [Deprecation: #84195 - Protected methods and properties in EditDocumentController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
241 `$this->pObj->MOD_SETTINGS['crawlaction'],`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_MENU" *(weak)*
242 `$this->pObj->MOD_MENU['crawlaction'],`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
247 `if ($this->pObj->MOD_SETTINGS['crawlaction'] === 'log') {`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
251 `$this->pObj->MOD_SETTINGS['depth'],`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_MENU" *(weak)*
252 `$this->pObj->MOD_MENU['depth'],`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
261 `$GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.display') . ': ' . BackendUtility::getFuncMenu($this->pObj->id, 'SET[log_display]', $this->pObj->MOD_SETTINGS['log_display'], $this->pObj->MOD_MENU['log_display'], 'index.php', '&setID=' . $setId) . ' - ' .`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_MENU" *(weak)*
261 `$GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.display') . ': ' . BackendUtility::getFuncMenu($this->pObj->id, 'SET[log_display]', $this->pObj->MOD_SETTINGS['log_display'], $this->pObj->MOD_MENU['log_display'], 'index.php', '&setID=' . $setId) . ' - ' .`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
262 `$GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.showresultlog') . ': ' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[log_resultLog]', $this->pObj->MOD_SETTINGS['log_resultLog'], 'index.php', '&setID=' . $setId . $quiPart) . ' - ' .`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
263 `$GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.showfevars') . ': ' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[log_feVars]', $this->pObj->MOD_SETTINGS['log_feVars'], 'index.php', '&setID=' . $setId . $quiPart) . ' - ' .`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
268 `$this->pObj->MOD_SETTINGS['itemsPerPage'],`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_MENU" *(weak)*
269 `$this->pObj->MOD_MENU['itemsPerPage'],`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "doc" *(weak)*
274 `$theOutput = $this->pObj->doc->section($LANG->getLL('title'), $h_func, false, true);`

- [Deprecation: #84334 - Protected methods and properties in ReplaceFileController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84334-ProtectedMethodsAndPropertiesInReplaceFileController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
277 `switch ((string)$this->pObj->MOD_SETTINGS['crawlaction']) {`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "doc" *(weak)*
282 `$theOutput .= $this->pObj->doc->section('', $this->drawURLs(), false, true);`

- [Deprecation: #84334 - Protected methods and properties in ReplaceFileController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84334-ProtectedMethodsAndPropertiesInReplaceFileController.html)

### Fetch of property "doc" *(weak)*
289 `$theOutput .= $this->pObj->doc->section('', $this->drawLog(), false, true);`

- [Deprecation: #84334 - Protected methods and properties in ReplaceFileController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84334-ProtectedMethodsAndPropertiesInReplaceFileController.html)

### Fetch of property "doc" *(weak)*
294 `$theOutput .= $this->pObj->doc->section('', $this->drawCLIstatus(), false, true);`

- [Deprecation: #84334 - Protected methods and properties in ReplaceFileController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84334-ProtectedMethodsAndPropertiesInReplaceFileController.html)

### Fetch of property "doc" *(weak)*
297 `$theOutput .= $this->pObj->doc->section('', $this->drawProcessOverviewAction(), false, true);`

- [Deprecation: #84334 - Protected methods and properties in ReplaceFileController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84334-ProtectedMethodsAndPropertiesInReplaceFileController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
378 `$this->pObj->MOD_SETTINGS['depth'],`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
452 `$this->pObj->MOD_SETTINGS['depth'],`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
455 `$availableConfigurations = $this->crawlerController->getConfigurationsForBranch($this->pObj->id, $this->pObj->MOD_SETTINGS['depth'] ? $this->pObj->MOD_SETTINGS['depth'] : 0);`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
455 `$availableConfigurations = $this->crawlerController->getConfigurationsForBranch($this->pObj->id, $this->pObj->MOD_SETTINGS['depth'] ? $this->pObj->MOD_SETTINGS['depth'] : 0);`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
567 `if (!$this->pObj->MOD_SETTINGS['log_resultLog']) {`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "pageinfo" *(weak)*
593 `$HTML = IconUtility::getIconForRecord('pages', $this->pObj->pageinfo);`

- [Deprecation: #84195 - Protected methods and properties in EditDocumentController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.html)

### Fetch of property "pageinfo" *(weak)*
595 `'row' => $this->pObj->pageinfo,`

- [Deprecation: #84195 - Protected methods and properties in EditDocumentController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
600 `if ($this->pObj->MOD_SETTINGS['depth']) {`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
601 `$tree->getTree($this->pObj->id, $this->pObj->MOD_SETTINGS['depth'], '');`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
611 `intval($this->pObj->MOD_SETTINGS['itemsPerPage'])`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
755 `$res = $this->crawlerController->getLogEntriesForPageId($pageRow_setId['uid'], $this->pObj->MOD_SETTINGS['log_display'], $doFlush, $doFullFlush, intval($itemsPerPage));`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
757 `$res = $this->crawlerController->getLogEntriesForSetId($pageRow_setId, $this->pObj->MOD_SETTINGS['log_display'], $doFlush, $doFullFlush, intval($itemsPerPage));`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
762 `+ ($this->pObj->MOD_SETTINGS['log_resultLog'] ? -1 : 0)`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
763 `+ ($this->pObj->MOD_SETTINGS['log_feVars'] ? 3 : 0);`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
789 `if ($this->pObj->MOD_SETTINGS['log_resultLog']) {`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
801 `if ($this->pObj->MOD_SETTINGS['log_feVars']) {`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
882 `($this->pObj->MOD_SETTINGS['log_resultLog'] ? '`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
891 `($this->pObj->MOD_SETTINGS['log_feVars'] ? '`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "MOD_SETTINGS" *(weak)*
975 `$mode = $this->pObj->MOD_SETTINGS['processListMode'];`

- [Deprecation: #85196 - Protect SetupModuleController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Deprecation-85196-ProtectSetupModuleController.html)

### Fetch of property "content" *(weak)*
215 `$this->pObj->content = str_replace('/*###POSTCSSMARKER###*/', '`

- [Breaking: #55298 - Decoupled sys_history functionality](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-55298-DecoupledHistoryFunctionality.html)
- [Deprecation: #84289 - Use ServerRequestInterface in File/CreateFolderController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84289-UseServerRequestInterfaceInFileCreateFolderController.html)

### Fetch of property "content" *(weak)*
217 `', $this->pObj->content);`

- [Breaking: #55298 - Decoupled sys_history functionality](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-55298-DecoupledHistoryFunctionality.html)
- [Deprecation: #84289 - Use ServerRequestInterface in File/CreateFolderController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84289-UseServerRequestInterfaceInFileCreateFolderController.html)

### Fetch of property "content" *(weak)*
219 `$this->pObj->content .= '<style type="text/css"><!--`

- [Breaking: #55298 - Decoupled sys_history functionality](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-55298-DecoupledHistoryFunctionality.html)
- [Deprecation: #84289 - Use ServerRequestInterface in File/CreateFolderController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84289-UseServerRequestInterfaceInFileCreateFolderController.html)

### Fetch of property "doc" *(weak)*
274 `$theOutput = $this->pObj->doc->section($LANG->getLL('title'), $h_func, false, true);`

- [Breaking: #55298 - Decoupled sys_history functionality](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-55298-DecoupledHistoryFunctionality.html)
- [Deprecation: #84295 - Use ServerRequestInterface in File/EditFileController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84295-UseServerRequestInterfaceInFileEditFileController.html)

### Fetch of property "doc" *(weak)*
282 `$theOutput .= $this->pObj->doc->section('', $this->drawURLs(), false, true);`

- [Breaking: #55298 - Decoupled sys_history functionality](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-55298-DecoupledHistoryFunctionality.html)
- [Deprecation: #84295 - Use ServerRequestInterface in File/EditFileController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84295-UseServerRequestInterfaceInFileEditFileController.html)

### Fetch of property "doc" *(weak)*
289 `$theOutput .= $this->pObj->doc->section('', $this->drawLog(), false, true);`

- [Breaking: #55298 - Decoupled sys_history functionality](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-55298-DecoupledHistoryFunctionality.html)
- [Deprecation: #84295 - Use ServerRequestInterface in File/EditFileController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84295-UseServerRequestInterfaceInFileEditFileController.html)

### Fetch of property "doc" *(weak)*
294 `$theOutput .= $this->pObj->doc->section('', $this->drawCLIstatus(), false, true);`

- [Breaking: #55298 - Decoupled sys_history functionality](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-55298-DecoupledHistoryFunctionality.html)
- [Deprecation: #84295 - Use ServerRequestInterface in File/EditFileController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84295-UseServerRequestInterfaceInFileEditFileController.html)

### Fetch of property "doc" *(weak)*
297 `$theOutput .= $this->pObj->doc->section('', $this->drawProcessOverviewAction(), false, true);`

- [Breaking: #55298 - Decoupled sys_history functionality](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-55298-DecoupledHistoryFunctionality.html)
- [Deprecation: #84295 - Use ServerRequestInterface in File/EditFileController](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Deprecation-84295-UseServerRequestInterfaceInFileEditFileController.html)

## Classes/Backend/View/ProcessListView.php
### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()" *(strong)*
367 `'window.location=\'' . BackendUtility::getModuleUrl('web_info') . '&SET[crawlaction]=multiprocess&id=' . $this->pageId . '\';'`

- [Deprecation: #85113 - Legacy Backend Module Routing methods](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.3/Deprecation-85113-LegacyBackendModuleRoutingMethods.html)

### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()" *(strong)*
383 `'window.location=\'' . BackendUtility::getModuleUrl('web_info') . '&action=stopCrawling\';'`

- [Deprecation: #85113 - Legacy Backend Module Routing methods](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.3/Deprecation-85113-LegacyBackendModuleRoutingMethods.html)

### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()" *(strong)*
390 `'window.location=\'' . BackendUtility::getModuleUrl('web_info') . '&action=resumeCrawling\';'`

- [Deprecation: #85113 - Legacy Backend Module Routing methods](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.3/Deprecation-85113-LegacyBackendModuleRoutingMethods.html)

### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()" *(strong)*
408 `'window.location=\'' . BackendUtility::getModuleUrl('web_info') . '&SET[processListMode]=simple\';'`

- [Deprecation: #85113 - Legacy Backend Module Routing methods](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.3/Deprecation-85113-LegacyBackendModuleRoutingMethods.html)

### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()" *(strong)*
414 `'window.location=\'' . BackendUtility::getModuleUrl('web_info') . '&SET[processListMode]=detail\';'`

- [Deprecation: #85113 - Legacy Backend Module Routing methods](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.3/Deprecation-85113-LegacyBackendModuleRoutingMethods.html)

### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()" *(strong)*
432 `'window.location=\'' . BackendUtility::getModuleUrl('web_info') . '&action=addProcess\';'`

- [Deprecation: #85113 - Legacy Backend Module Routing methods](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.3/Deprecation-85113-LegacyBackendModuleRoutingMethods.html)

## Classes/Controller/CrawlerController.php
### Access to array key "extConf" *(weak)*
280 `$settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler']);`

- [Deprecation: #82254 - Deprecate $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-82254-DeprecateGLOBALSTYPO3_CONF_VARSEXTextConf.html)

### Access to global array "TYPO3_DB" *(strong)*
276 `$this->db = $GLOBALS['TYPO3_DB'];`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
803 `$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
812 `while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
815 `$GLOBALS['TYPO3_DB']->sql_free_result($res);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
940 `$andWhereLanguage = ' AND ' . $GLOBALS['TYPO3_DB']->quoteStr($transOrigPointerField, $subpartParams['_TABLE']) . ' <= 0 ';`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
943 `$where = $GLOBALS['TYPO3_DB']->quoteStr($pidField, $subpartParams['_TABLE']) . '=' . intval($lookUpPid) . ' ' .`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
946 `$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1060 `return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1099 `return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1121 `$groups = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('DISTINCT set_id', 'tx_crawler_queue', $realWhere);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1124 `EventDispatcher::getInstance()->post('queueEntryFlush', $group['set_id'], $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, set_id', 'tx_crawler_queue', $realWhere . ' AND set_id="' . $group['set_id'] . '"'));`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1129 `$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_crawler_queue', $realWhere);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1159 `$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_crawler_queue', $fieldArray);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1234 `$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_crawler_queue', $fieldArray);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1235 `$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1280 `$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1287 `' AND parameters_hash = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($fieldArray['parameters_hash'], 'tx_crawler_queue')`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1330 `list($queueRec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1364 `$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue', 'qid=' . intval($queueId), $field_array);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1395 `$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue', 'qid=' . intval($queueId), $field_array);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1416 `$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_crawler_queue', $field_array);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1417 `$queueId = $field_array['qid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1430 `$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue', 'qid=' . intval($queueId), $field_array);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1752 `list($queueRec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tx_crawler_queue', 'qid=' . intval($queueId));`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
1836 `$mountpage = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'pages', 'uid = ' . $data['row']['uid']);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2273 `$del = $GLOBALS['TYPO3_DB']->exec_DELETEquery(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2284 `$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2305 `$GLOBALS['TYPO3_DB']->sql_query('BEGIN');`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2307 `$GLOBALS['TYPO3_DB']->exec_UPDATEquery(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2317 `$numberOfAffectedRows = $GLOBALS['TYPO3_DB']->sql_affected_rows();`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2318 `$GLOBALS['TYPO3_DB']->exec_UPDATEquery(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2327 `$GLOBALS['TYPO3_DB']->sql_query('COMMIT');`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2329 `$GLOBALS['TYPO3_DB']->sql_query('ROLLBACK');`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2408 `$GLOBALS['TYPO3_DB']->sql_query('BEGIN');`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2410 `$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2418 `while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2431 `$GLOBALS['TYPO3_DB']->exec_INSERTquery(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2448 `$GLOBALS['TYPO3_DB']->sql_query('COMMIT');`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2471 `$GLOBALS['TYPO3_DB']->sql_query('BEGIN');`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2478 `$GLOBALS['TYPO3_DB']->exec_UPDATEquery(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2486 `$GLOBALS['TYPO3_DB']->exec_UPDATEquery(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2500 `$GLOBALS['TYPO3_DB']->exec_UPDATEquery(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2507 `$GLOBALS['TYPO3_DB']->exec_UPDATEquery(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2517 `$GLOBALS['TYPO3_DB']->sql_query('COMMIT');`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
2533 `$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_crawler_process', 'deleted = 1');`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Call to class constant "TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING" *(strong)*
1347 `GeneralUtility::SYSLOG_SEVERITY_WARNING`

- [Deprecation: #52694 - Deprecated GeneralUtility::devLog()](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-52694-DeprecatedGeneralUtilitydevLog.html)

### Usage of class "TYPO3\CMS\Core\TimeTracker\NullTimeTracker" *(strong)*
2669 `$GLOBALS['TT'] = new NullTimeTracker();`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #73185 - Deprecate NullTimeTracker](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.0/Deprecation-73185-DeprecateNullTimeTracker.html)

### Call to global constant "TYPO3_DLOG" *(strong)*
1490 `if (TYPO3_DLOG) {`

- [Breaking: #82162 - Global error constants removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-82162-GlobalErrorConstantsRemoved.html)

### Call to global constant "TYPO3_DLOG" *(strong)*
1497 `if (TYPO3_DLOG) {`

- [Breaking: #82162 - Global error constants removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-82162-GlobalErrorConstantsRemoved.html)

### Call to global constant "TYPO3_DLOG" *(strong)*
1533 `if (TYPO3_DLOG) {`

- [Breaking: #82162 - Global error constants removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-82162-GlobalErrorConstantsRemoved.html)

### Call to global constant "TYPO3_DLOG" *(strong)*
1563 `if (TYPO3_DLOG) {`

- [Breaking: #82162 - Global error constants removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-82162-GlobalErrorConstantsRemoved.html)

### Call to method "connectToDB()" *(weak)*
2676 `$GLOBALS['TSFE']->connectToDB();`

- [Deprecation: #84965 - Various TypoScriptFrontendController methods](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.3/Deprecation-84965-VariousTypoScriptFrontendControllerMethods.html)

### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField()" *(strong)*
662 `$configurationRecordsForCurrentPage = BackendUtility::getRecordsByField(`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #79122 - Deprecate method getRecordsByField](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.7/Deprecation-79122-DeprecateBackendUtilitygetRecordsByField.html)

### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause()" *(strong)*
666 `BackendUtility::BEenableFields('tx_crawler_configuration') . BackendUtility::deleteClause('tx_crawler_configuration')`

- [Deprecation: #83118 - DeleteClause methods deprecated](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-83118-DeleteClauseMethods.html)

### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause()" *(strong)*
808 `BackendUtility::deleteClause('tx_crawler_configuration') . ' ' .`

- [Deprecation: #83118 - DeleteClause methods deprecated](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-83118-DeleteClauseMethods.html)

### Use of static class method call "TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause()" *(strong)*
949 `$where . BackendUtility::deleteClause($subpartParams['_TABLE']),`

- [Deprecation: #83118 - DeleteClause methods deprecated](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-83118-DeleteClauseMethods.html)

### Use of static class method call "TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj()" *(strong)*
1449 `$callBackObj = &GeneralUtility::getUserObj($objRef);`

- [Deprecation: #80993 - GeneralUtility::getUserObj](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-80993-GeneralUtilitygetUserObj.html)

### Use of static class method call "TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj()" *(strong)*
2380 `$hookObj = &GeneralUtility::getUserObj($objRef);`

- [Deprecation: #80993 - GeneralUtility::getUserObj](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-80993-GeneralUtilitygetUserObj.html)

### Use of static class method call "TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit()" *(strong)*
2682 `PageGenerator::pagegenInit();`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #79858 - TSFE-related properties and methods](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.7/Deprecation-79858-TSFE-relatedPropertiesAndMethods.html)

## Classes/Api/CrawlerApi.php
### Access to global array "TYPO3_DB" *(strong)*
284 `$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
377 `$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($query, 'tx_crawler_queue', $where, '', 'page_id, scheduled');`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
393 `$rs = $GLOBALS['TYPO3_DB']->exec_SELECTquery($query, 'tx_crawler_queue', $where);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
394 `$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rs);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

### Access to global array "TYPO3_DB" *(strong)*
443 `$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $where);`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

## Classes/Command/CrawlerCommandController.php
### Access to array key "extConf" *(weak)*
219 `$settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler']);`

- [Deprecation: #82254 - Deprecate $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-82254-DeprecateGLOBALSTYPO3_CONF_VARSEXTextConf.html)

### Access to global array "TYPO3_DB" *(strong)*
237 `$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_crawler_process', 'assigned_items_count = 0');`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

## Classes/Command/FlushCommandLineController.php
### Usage of class "TYPO3\CMS\Core\Controller\CommandLineController" *(strong)*
38 `class FlushCommandLineController extends CommandLineController`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #79265 - CommandLineController and Cleaner Command](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.6/Deprecation-79265-CommandLineControllerAndCleanerCommand.html)

## Classes/Command/CrawlerCommandLineController.php
### Usage of class "TYPO3\CMS\Core\Controller\CommandLineController" *(strong)*
38 `class CrawlerCommandLineController extends CommandLineController`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #79265 - CommandLineController and Cleaner Command](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.6/Deprecation-79265-CommandLineControllerAndCleanerCommand.html)

## Classes/Command/QueueCommandLineController.php
### Usage of class "TYPO3\CMS\Core\Controller\CommandLineController" *(strong)*
38 `class QueueCommandLineController extends CommandLineController`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #79265 - CommandLineController and Cleaner Command](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.6/Deprecation-79265-CommandLineControllerAndCleanerCommand.html)

## Classes/Domain/Repository/AbstractRepository.php
### Access to global array "TYPO3_DB" *(strong)*
104 `return $GLOBALS['TYPO3_DB'];`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

## Classes/Domain/Repository/ProcessRepository.php
### Access to global array "TYPO3_DB" *(strong)*
248 `return $GLOBALS['TYPO3_DB'];`

- [Breaking: #80929 - TYPO3_DB moved to extension](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)

## Classes/Utility/ExtensionSettingUtility.php
### Access to array key "extConf" *(weak)*
46 `$extensionSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler']);`

- [Deprecation: #82254 - Deprecate $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-82254-DeprecateGLOBALSTYPO3_CONF_VARSEXTextConf.html)

## Tests/Unit/Controller/CrawlerControllerTest.php
### Access to array key "extConf" *(weak)*
55 `$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler'] = 'a:19:{s:9:"sleepTime";s:4:"1000";s:16:"sleepAfterFinish";s:2:"10";s:11:"countInARun";s:3:"100";s:14:"purgeQueueDays";s:2:"14";s:12:"processLimit";s:1:"1";s:17:"processMaxRunTime";s:3:"300";s:14:"maxCompileUrls";s:5:"10000";s:12:"processDebug";s:1:"0";s:14:"processVerbose";s:1:"0";s:16:"crawlHiddenPages";s:1:"0";s:7:"phpPath";s:12:"/usr/bin/php";s:14:"enableTimeslot";s:1:"1";s:11:"logFileName";s:0:"";s:9:"follow30x";s:1:"0";s:18:"makeDirectRequests";s:1:"0";s:16:"frontendBasePath";s:1:"/";s:22:"cleanUpOldQueueEntries";s:1:"1";s:19:"cleanUpProcessedAge";s:1:"2";s:19:"cleanUpScheduledAge";s:1:"7";}';`

- [Deprecation: #82254 - Deprecate $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-82254-DeprecateGLOBALSTYPO3_CONF_VARSEXTextConf.html)

## Tests/Functional/Api/CrawlerApiTest.php
### Access to array key "extConf" *(weak)*
83 `$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler'] = 'a:19:{s:9:"sleepTime";s:4:"1000";s:16:"sleepAfterFinish";s:2:"10";s:11:"countInARun";s:3:"100";s:14:"purgeQueueDays";s:2:"14";s:12:"processLimit";s:1:"1";s:17:"processMaxRunTime";s:3:"300";s:14:"maxCompileUrls";s:5:"10000";s:12:"processDebug";s:1:"0";s:14:"processVerbose";s:1:"0";s:16:"crawlHiddenPages";s:1:"0";s:7:"phpPath";s:12:"/usr/bin/php";s:14:"enableTimeslot";s:1:"1";s:11:"logFileName";s:0:"";s:9:"follow30x";s:1:"0";s:18:"makeDirectRequests";s:1:"0";s:16:"frontendBasePath";s:1:"/";s:22:"cleanUpOldQueueEntries";s:1:"1";s:19:"cleanUpProcessedAge";s:1:"2";s:19:"cleanUpScheduledAge";s:1:"7";}';`

- [Deprecation: #82254 - Deprecate $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-82254-DeprecateGLOBALSTYPO3_CONF_VARSEXTextConf.html)

## Tests/Functional/Domain/Repository/ProcessRepositoryTest.php
### Access to array key "extConf" *(weak)*
67 `$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler'] = 'a:19:{s:9:"sleepTime";s:4:"1000";s:16:"sleepAfterFinish";s:2:"10";s:11:"countInARun";s:3:"100";s:14:"purgeQueueDays";s:2:"14";s:12:"processLimit";s:1:"1";s:17:"processMaxRunTime";s:3:"300";s:14:"maxCompileUrls";s:5:"10000";s:12:"processDebug";s:1:"0";s:14:"processVerbose";s:1:"0";s:16:"crawlHiddenPages";s:1:"0";s:7:"phpPath";s:12:"/usr/bin/php";s:14:"enableTimeslot";s:1:"1";s:11:"logFileName";s:0:"";s:9:"follow30x";s:1:"0";s:18:"makeDirectRequests";s:1:"0";s:16:"frontendBasePath";s:1:"/";s:22:"cleanUpOldQueueEntries";s:1:"1";s:19:"cleanUpProcessedAge";s:1:"2";s:19:"cleanUpScheduledAge";s:1:"7";}';`

- [Deprecation: #82254 - Deprecate $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Deprecation-82254-DeprecateGLOBALSTYPO3_CONF_VARSEXTextConf.html)

## ext_localconf.php
### Access to array key "cliKeys" *(weak)*
4 `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler'] = ['EXT:crawler/cli/crawler_cli.php', '_CLI_crawler'];`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #80468 - Command Line Interface: cliKeys and cli_dispatch.phpsh](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.7/Deprecation-80468-CommandLineInterfaceCliKeysAndCli_dispatchphpsh.html)

### Access to array key "cliKeys" *(weak)*
5 `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_im'] = ['EXT:crawler/cli/crawler_im.php', '_CLI_crawler'];`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #80468 - Command Line Interface: cliKeys and cli_dispatch.phpsh](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.7/Deprecation-80468-CommandLineInterfaceCliKeysAndCli_dispatchphpsh.html)

### Access to array key "cliKeys" *(weak)*
6 `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_flush'] = ['EXT:crawler/cli/crawler_flush.php', '_CLI_crawler'];`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #80468 - Command Line Interface: cliKeys and cli_dispatch.phpsh](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.7/Deprecation-80468-CommandLineInterfaceCliKeysAndCli_dispatchphpsh.html)

### Access to array key "cliKeys" *(weak)*
7 `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_multiprocess'] = ['EXT:crawler/cli/crawler_multiprocess.php', '_CLI_crawler'];`

- [Breaking: #80700 - Deprecated functionality removed](https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-80700-DeprecatedFunctionalityRemoved.html)
- [Deprecation: #80468 - Command Line Interface: cliKeys and cli_dispatch.phpsh](https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.7/Deprecation-80468-CommandLineInterfaceCliKeysAndCli_dispatchphpsh.html)


<?php
defined('TYPO3') or die();

\AOE\Crawler\Utility\BackendUtility::registerInfoModuleFunction();
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_crawler_configuration');

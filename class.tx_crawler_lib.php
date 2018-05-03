<?php

/**
 * Add fallback for TYPO3 CMS 7.6 which still expects this file to exist.
 * see https://github.com/AOEpeople/crawler/issues/262
 */
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler', 'Migrations/Code/LegacyClassesForIde.php'));

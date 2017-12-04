<?php
defined('TYPO3_MODE') or die();

// Compatibility with 6.2
if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(\TYPO3\CMS\Core\Utility\VersionNumberUtility::getNumericTypo3Version()) < 7000000) {
    $GLOBALS['TCA']['tx_crawler_configuration']['columns']['processing_instruction_filter']['config']['renderMode'] = 'checkbox';
}

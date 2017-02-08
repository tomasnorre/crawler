<?php
defined('TYPO3_MODE') or die();

if ('BE' === TYPO3_MODE) {
    \AOE\Crawler\Utility\BackendUtility::registerClickMenuItem();
}
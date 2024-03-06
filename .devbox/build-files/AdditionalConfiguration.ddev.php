<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'crawler-devbox';
$GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'] = 'normal';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] = 'db';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] = 'db';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] = 'db';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] = 'db';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'] = '3306';
$GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] = '$argon2i$v=19$m=65536,t=16,p=1$aVdpSkRCR1NVOE9abTFHaQ$Hy/cm62jADhVJXRqKE8Hd5Hbk8e22fb01Kkk/zzjT3I'; /* joh316 */
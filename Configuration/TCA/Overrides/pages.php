<?php

/*
 * This file is part of the package bk2k/bootstrap-package.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

defined('TYPO3_MODE') || die();

/***************
 * Add content element group to seletor list
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'pages',
    'module',
    [
        'LLL:EXT:auto404/Resources/Private/Language/locallang_tca.xlf:pages.module.I.auto404',
        'http404',
        'ext-auto404-icon'
    ]
);


$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-http404'] = 'apps-pagetree-folder-http404';

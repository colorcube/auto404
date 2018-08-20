<?php
defined('TYPO3_MODE') or die();

$boot = function () {

    if (TYPO3_MODE === 'BE') {

        $icons = [
            'ext-auto404-icon' => 'contains-auto404.svg',
            'apps-pagetree-folder-http404' => 'apps-pagetree-page-http404.svg',
        ];
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        foreach ($icons as $identifier => $path) {
            $iconRegistry->registerIcon(
                $identifier,
                \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
                ['source' => 'EXT:auto404/Resources/Public/Icons/' . $path]
            );
        }
    }

};

$boot();
unset($boot);
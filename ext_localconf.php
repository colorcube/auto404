<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = 'USER_FUNCTION:EXT:'.$_EXTKEY.'/Classes/Hooks/FrontendHook.php:Colorcube\\Auto404\\Hooks\\FrontendHook->pageErrorHandler';


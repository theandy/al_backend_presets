<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    "@import 'EXT:al_backend_presets/Configuration/TsConfig/BackendUser/Editor.tsconfig'"
);
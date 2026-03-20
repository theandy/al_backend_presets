<?php

defined('TYPO3') or die();

call_user_func(function () {
    $servicesFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
        'EXT:al_backend_presets/Configuration/Services.yaml'
    );

    if (file_exists($servicesFile)) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['di']['services'][] = $servicesFile;
    }
});
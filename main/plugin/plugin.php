<?php

require_once '../inc/global.inc.php';

if (!empty($_GET['add_page_plugin'])) {
    header('location: ' . api_get_path(WEB_PLUGIN_PATH) . 'add_external_pages/src/showPage.php?id=' . $_GET['id']);
}

if (!empty($_GET['external_page_ngl_plugin'])) {
    header('location: ' . api_get_path(WEB_PLUGIN_PATH) . 'external_page_ngl/src/showPage.php');
}
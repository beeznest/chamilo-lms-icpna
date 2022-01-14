<?php
/* For licensing terms, see /license.txt */

$plexConfigPlugin = IcpnaPlexConfigPlugin::create();

if (api_is_platform_admin() && !$plexConfigPlugin->isEnabled()) {
    $plexConfigPlugin->createDbTables();

    HookQuizEnd::create()->attach(
        IcpnaPlexConfigQuizEndHook::create()
    );
}

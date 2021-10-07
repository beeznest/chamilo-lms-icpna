<?php
/* For licensing terms, see /license.txt */

if (api_is_platform_admin()) {
    IcpnaPlexConfigPlugin::create()->createDbTables();

    HookQuizEnd::create()->attach(
        IcpnaPlexConfigQuizEndHook::create()
    );
}

<?php
/* For licensing terms, see /license.txt */

if (api_is_platform_admin()) {
    HookQuizEnd::create()->detach(
        IcpnaPlexConfigQuizEndHook::create()
    );

    IcpnaPlexConfigPlugin::create()->dropDbTables();
}

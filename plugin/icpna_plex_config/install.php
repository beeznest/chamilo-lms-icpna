<?php
/* For licensing terms, see /license.txt */

if (api_is_platform_admin()) {
    HookQuizEnd::create()->attach(
        IcpnaPlexConfigQuizEndHook::create()
    );
}

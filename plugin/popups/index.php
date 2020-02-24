<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\Popups\Popup;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

$popupsPlugin = PopupsPlugin::create();
$pluginIsEnabled = 'true' === $popupsPlugin->get(PopupsPlugin::SETTING_ENABLED);

if ($pluginIsEnabled) {
    $request = HttpRequest::createFromGlobals();

    $popups = Database::getManager()
        ->getRepository('ChamiloPluginBundle:Popups\Popup')
        ->findBy(['shownIn' => $request->getBaseUrl(), 'visible' => true]);

    $userStatus = api_get_user_status();

    $popups = array_filter(
        $popups,
        function (Popup $popup) use ($userStatus) {
            return in_array($userStatus, $popup->getVisibleFor());
        }
    );

    /** @var Popup|null $popup */
    $popup = current($popups);

    if ($popup) {
        $modal = '<div id="plugin-popup-modal" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="'.get_lang('Close').'">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title">'.$popup->getTitle().'</h4>
                        </div>
                        <div class="modal-body">
                            '.Security::remove_XSS($popup->getContent()).'
                        </div>
                    </div>
                </div>
            </div>
            <script>
            $(document).ready(function () {
                $(\'#plugin-popup-modal\').modal({show: true});
            });
            </script>';

        echo $modal;
    }
}

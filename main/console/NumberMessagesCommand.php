<?php

require_once 'main/inc/global.inc.php';
require_once api_get_path(CONFIG_SYS_ROOT_PATH) . 'vendor/autoload.php';

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NumberMessagesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('chamilo:getNumberMessages')
            ->setDescription('get number of messages per user')
            ->addArgument('username', InputArgument::REQUIRED)
            ->addArgument('branch_id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $branchId = $input->getArgument('branch_id');
        $sessionId = $input->getArgument('session_id');
        $objBranch = new Branch();
        $programUid = $objBranch->getUidProgram($sessionId);

        /*
        $userInfo = api_get_user_info_from_username($username);

        if (empty($userInfo)) {
            $output->writeln("User does '$username' not exits");
            return 0;
        }*/

        if (isset($_configuration['ws_icpna_message_viewer_count']) &&
            !empty($_configuration['ws_icpna_message_viewer_count'])
        ) {
            ini_set("soap.wsdl_cache_enabled", "0");
            try {
                $wsUrl = $_configuration['ws_icpna_message_viewer_count'];

                $soapClient = new SoapClient(
                    $wsUrl,
                    array('cache_wsdl' => WSDL_CACHE_NONE, 'exceptions' => 1)
                );
                $params = array(
                    'intidsede' => $branchId,
                    //'vcodigorrhh' => $username,
                    'uidIdPrograma' => $programUid,
                );

                $wsResponse = $soapClient->ObtenerNroMensajes($params);
                $countMessages = 0;
                if ($wsResponse) {
                    $countMessages = $wsResponse->ObtenerNroMensajesResult;
                }
                var_dump($countMessages);

            } catch (\Exception $e) {
                //var_dump($e->getMessage());
            }
        }

    }
}

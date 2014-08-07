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
        /*
        $userInfo = api_get_user_info_from_username($username);

        if (empty($userInfo)) {
            $output->writeln("User does '$username' not exits");
            return 0;
        }*/

        $wsUrl = "https://www2.icpna.edu.pe/wsprueba/service.asmx?wsdl";
        $soapClient = new SoapClient($wsUrl);
        $params = array(
            'branch_id' => $branchId,
            'username' => $username
        );

        $wsResponse = $soapClient->ObtenerNroMensajes($params);

        if ($wsResponse) {
            var_dump($wsResponse);
        }
    }
}

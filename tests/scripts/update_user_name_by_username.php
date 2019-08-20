<?php
/* For licensing terms, see /license.txt */

die();

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$userInfos = [
    //'Jon', 'Doe', 'jdoe',
];

$em = Database::getManager();
$userRepo = UserManager::getRepository();

foreach ($userInfos as $userInfo) {
    list($firstName, $lastName, $username) = $userInfo;

    /** @var \Chamilo\UserBundle\Entity\User $user */
    $user = $userRepo->findOneBy(['username' => $username]);

    if (!$user) {
        continue;
    }

    $user
        ->setFirstname($firstName)
        ->setLastname($lastName);

    echo "Updating $username to:".PHP_EOL
        ."\tFirst name -> {$user->getFirstname()} Last name -> {$user->getLastname()}".PHP_EOL;

    $em->persist($user);
}

$em->flush();

echo 'Done'.PHP_EOL;

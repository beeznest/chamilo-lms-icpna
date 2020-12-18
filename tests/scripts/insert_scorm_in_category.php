<?php

/* For licensing terms, see /license.txt */

exit("Script disabled".PHP_EOL);

if (PHP_SAPI !== 'cli') {
    exit('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

// params --->

$originalScormPackage = '/home/aquiroz/Standarized-PPTs.zip';

$courses = [
    'I01',
    'I01AM',
    'I01AT',
    'I01MW',
    'I01SA',
    'I01SI',
    'I01TT',
    'I02',
    'I02AM',
    'I02AT',
    'I02MW',
    'I02SA',
    'I02SI',
    'I02TT',
    'I03',
    'I03AM',
    'I03AT',
    'I03MW',
    'I03SA',
    'I03SI',
    'I03TT',
    'I04',
    'I04AM',
    'I04AT',
    'I04MW',
    'I04SA',
    'I04SI',
    'I04TT',
    'I05',
    'I05AM',
    'I05AT',
    'I05BP',
    'I05LF',
    'I05MW',
    'I05SA',
    'I05SI',
    'I05TT',
    'I06',
    'I06AM',
    'I06AT',
    'I06BP',
    'I06LF',
    'I06MW',
    'I06SA',
    'I06SI',
    'I06TT',
    'I07',
    'I07AM',
    'I07AT',
    'I07BP',
    'I07LF',
    'I07MW',
    'I07SA',
    'I07SI',
    'I07TT',
    'I07VL',
    'I08',
    'I08AM',
    'I08AT',
    'I08BP',
    'I08LF',
    'I08MW',
    'I08SA',
    'I08SI',
    'I08TT',
    'I09',
    'I09AM',
    'I09AT',
    'I09MW',
    'I09SA',
    'I09SI',
    'I09TT',
    'I10',
    'I10AM',
    'I10AT',
    'I10MW',
    'I10SA',
    'I10SI',
    'I10TT',
    'I11',
    'I11AM',
    'I11AT',
    'I11MW',
    'I11SA',
    'I11SI',
    'I11TT',
    'I12',
    'I12AM',
    'I12AT',
    'I12MW',
    'I12SA',
    'I12SI',
    'I12TT',
];

$userAdmin = 642048;

$lpCategoryName = 'DISTANCE LEARNING MATERIAL';

// code --->

$originalScormName = basename($originalScormPackage);
$originalScormPackageSize = filesize($originalScormPackage);

$tblLpCategory = Database::get_course_table(TABLE_LP_CATEGORY);

$currentWorkDirectory = getcwd();

foreach ($courses as $courseCode) {
    echo '['.time()."] Replicating in $courseCode".PHP_EOL;

    $courseInfo = api_get_course_info($courseCode);

    $lpCategoryInfo = Database::select(
        'iid',
        $tblLpCategory,
        [
            'where' => [
                'name = ? AND c_id = ?' => [$lpCategoryName, $courseInfo['real_id']],
            ],
        ],
        'first'
    );

    if (empty($lpCategoryInfo)) {
        $lpCategoryInfo['iid'] = learnpath::createCategory(
            ['name' => $lpCategoryName, 'c_id' => $courseInfo['real_id']]
        );
        echo '['.time()."] \tCreated new category".PHP_EOL;
    }

    $oScorm = new scorm($courseInfo['code'], null, $userAdmin);
    $manifest = $oScorm->import_package(
        [
            'error' => 0,
            'size' => $originalScormPackageSize,
            'name' => $originalScormName,
            'tmp_name' => $originalScormPackage,
            'type' => 'application/zip',
        ],
        '',
        $courseInfo
    );
    if (!empty($manifest)) {
        $oScorm->parse_manifest($manifest);
        $oScorm->import_manifest($courseInfo['code']);
    }
    $oScorm->set_proximity('local');
    $oScorm->set_maker('Scorm');
    $oScorm->set_jslib('scorm_api.php');
    $oScorm->setCategoryId($lpCategoryInfo['iid']);

    $scormId = $oScorm->get_id();

    echo '['.time()."] \tImported scorm ($scormId)".PHP_EOL;

    api_item_property_update($courseInfo, TOOL_LEARNPATH, $scormId, 'invisible', $userAdmin);

    echo '['.time()."] \tScorm ($scormId) no visible".PHP_EOL;

    chdir($currentWorkDirectory);
}

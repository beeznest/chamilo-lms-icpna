<?php
/* For licensing terms, see /license.txt */

/**
 * Generate a table with the course list having the extrafield "desactivado" set = 1.
 */

if ('cli' !== PHP_SAPI) {
    exit;
}

require __DIR__.'/../../main/inc/global.inc.php';

$sql = "SELECT c.id, c.code, c.title
    FROM course c
    INNER JOIN extra_field_values efv ON c.id = efv.item_id
    INNER JOIN extra_field ef ON efv.field_id = ef.id
    WHERE ef.extra_field_type = 2 AND ef.variable = 'mostrar' AND efv.value = '1'";

$result = Database::query($sql);

$headers = [];
$headers['Id'] = 6;
$headers['Code'] = 35;
$headers['Title'] = 65;
$headers['Frequency'] = 20;

echo PHP_EOL."Cursos con 'desactivado' = 1".PHP_EOL;

foreach ($headers as $header => $length) {
    printf("%{$length}s", $header);
}

echo PHP_EOL;

foreach ($headers as $header => $length) {
    echo str_repeat('-', $length);
}

echo PHP_EOL;

$lengths = array_values($headers);
$i = 0;

while ($courseInfo = Database::fetch_assoc($result)) {
    $sql2 = "SELECT efo.display_text
        FROM extra_field_options efo
        INNER JOIN extra_field_values efv ON efo.option_value = efv.value
        INNER JOIN extra_field ef ON (efv.field_id = ef.id AND efo.field_id = ef.id)
        WHERE efv.item_id = {$courseInfo['id']} AND ef.variable = 'frecuencia' AND ef.extra_field_type = 2";

    $result2 = Database::query($sql2);
    $frecuency = Database::fetch_assoc($result2);

    printf("%{$lengths[0]}s", $courseInfo['id']);
    printf("%{$lengths[1]}s", $courseInfo['code']);
    printf("%{$lengths[2]}s", $courseInfo['title']);
    printf("%{$lengths[3]}s", $frecuency ? $frecuency['display_text'] : '');

    echo PHP_EOL;
}

<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Tag;

require_once __DIR__.'/../global.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : '';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

switch ($action) {
    case 'get_second_select_options':
        $field_id = isset($_REQUEST['field_id']) ? $_REQUEST['field_id'] : null;
        $option_value_id = isset($_REQUEST['option_value_id']) ? $_REQUEST['option_value_id'] : null;

        if (!empty($type) && !empty($field_id) && !empty($option_value_id)) {
            $field_options = new ExtraFieldOption($type);
            echo $field_options->get_second_select_field_options_by_field(
                $option_value_id,
                true
            );
        }
        break;
    case 'search_tags':
        header('Content-Type: application/json');

        $fieldId = isset($_REQUEST['field_id']) ? $_REQUEST['field_id'] : null;
        $tag = isset($_REQUEST['q']) ? $_REQUEST['q'] : null;
        $result = [];

        if (empty($tag)) {
            echo json_encode(['items' => $result]);
            exit;
        }

        $extraFieldOption = new ExtraFieldOption($type);

        $tags = Database::getManager()
            ->getRepository('ChamiloCoreBundle:Tag')
            ->createQueryBuilder('t')
            ->where("t.tag LIKE :tag")
            ->andWhere('t.fieldId = :field')
            ->setParameter('field', $fieldId)
            ->setParameter('tag', "$tag%")
            ->getQuery()
            ->getResult();

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $result[] = [
                'id' => $tag->getTag(),
                'text' => $tag->getTag()
            ];
        }

        echo json_encode(['items' => $result]);
        break;
    default:
        exit;
        break;
    case 'filter_select_options':
        $filter = isset($_REQUEST['filter_by']) ? $_REQUEST['filter_by'] : null;
        $fieldVariable = isset($_REQUEST['field_variable']) ? $_REQUEST['field_variable'] : null;

        if (!$fieldVariable || !$type) {
            break;
        }

        $efvOption = new ExtraFieldOption('user');
        $options = $efvOption->filterSelectFieldOptions($fieldVariable, $filter);

        echo json_encode($options);
        break;
}
exit;

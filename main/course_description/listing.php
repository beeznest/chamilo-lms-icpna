<?php
/* For licensing terms, see /license.txt */

/**
* Template (view in MVC pattern) used for listing course descriptions
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.course_description
*/

// protect a course script
api_protect_course_script(true);

// display messages
$add = isset($messages['add']) ? $messages['add'] : null;
$edit = isset($messages['edit']) ? $messages['edit'] : null;
$destroy = isset($messages['destroy']) ? $messages['destroy'] : null;
if ($edit || $add) {
	Display :: display_confirmation_message(get_lang('CourseDescriptionUpdated'));
} else if ($destroy) {
	Display :: display_confirmation_message(get_lang('CourseDescriptionDeleted'));
}

// display actions menu
if (api_is_allowed_to_edit(null,true)) {
    $categories = array ();
    foreach ($default_description_titles as $id => $title) {
        $categories[$id] = $title;
    }
    $categories[ADD_BLOCK] = get_lang('NewBloc');

    $i=1;

    if (api_is_platform_admin()) {
        echo '<div class="actions" style="margin-bottom:30px">';
        ksort($categories);
        foreach ($categories as $id => $title) {
            if ($i==ADD_BLOCK) {
                echo '<a href="index.php?'.api_get_cidreq().'&action=add">'.
                    Display::return_icon($default_description_icon[$id], $title,'',ICON_SIZE_MEDIUM).'</a>';
                break;
            } else {
                echo '<a href="index.php?action=edit&'.api_get_cidreq().'&description_type='.$id.'">'.
                    Display::return_icon($default_description_icon[$id], $title,'',ICON_SIZE_MEDIUM).'</a>';
                $i++;
            }
        }
        echo '</div>';
    }
}

$history = isset($history) ? $history : null;
// display course description list
if ($history) {
	echo '<div><table width="100%"><tr><td><h3>'.get_lang('ThematicAdvanceHistory').'</h3></td><td align="right"><a href="index.php?action=listing">'.Display::return_icon('info.png',get_lang('BackToCourseDesriptionList'),array('style'=>'vertical-align:middle;'),ICON_SIZE_SMALL).' '.get_lang('BackToCourseDesriptionList').'</a></td></tr></table></div>';
}
$user_info = api_get_user_info();
$catCount = 1;

$tabsData = array();

foreach ($default_description_titles as $titles) {
    if (isset($descriptions) && count($descriptions) > 0) {
        foreach ($descriptions as $id => $description) {
            if (
                $catCount == $description['description_type'] ||
                ($catCount == 8 && intval($description['description_type']) >= 8)
            ) {
                $tabsData[] = array(
                    'id' => $description['id'],
                    'title' => $description['title'],
                    'content' => $description['content'],
                    'is_editable' => api_is_platform_admin() &&
                        api_is_allowed_to_edit(null, true) &&
                        !$history &&
                        api_get_session_id() == $description['session_id'],
                    'session_id' => $description['session_id'],
                    'type' => $description['description_type']
                );
            }
        }
    }
    $catCount++;
}

$firstTab = $tabsData[0];

echo '<ul class="nav nav-tabs" id="course-description-tabs">';

foreach ($tabsData as $tab) {
    if ($tab['is_editable']) {
        $tab['title'] = $tab['title'] . ' ' . api_get_session_image(api_get_session_id(), $user_info['status']);
    }

    echo '<li class="' . ($firstTab['id'] == $tab['id'] ? 'active' : '') . '">';
    echo '<a href="#tab-' . $tab['id'] . '">' . $tab['title'] . '</a>';
    echo '</li>';
}

echo '</ul>';
echo '<div class="tab-content">';

foreach ($tabsData as $tab) {
    echo '<div class="tab-pane ' . ($firstTab['id'] == $tab['id'] ? 'active' : '') . '" id="tab-' . $tab['id'] . '">';
    echo '<div class="pull-right">';

    echo Display::url(
        Display::return_icon(
            'print.png',
            get_lang('Print'),
            array(),
            ICON_SIZE_SMALL
        ),
        '#',
        array(
            'role' => 'button',
            'class' => 'btn-to-print',
            'data-id' => $tab['id']
        )
    );

    if ($tab['is_editable']) {
        //edit
        echo Display::url(
            Display::return_icon(
                'edit.png',
                get_lang('Edit'),
                array(),
                ICON_SIZE_SMALL
            ),
            api_get_self() . '?' . http_build_query(array(
                'id' => $tab['id'],
                'cidReq' => api_get_course_id(),
                'id_session' => $tab['session_id'],
                'action' => 'edit',
                'description_type' => $tab['type']
            ))
        );

        //delete
        echo Display::url(
            Display::return_icon(
                'delete.png',
                get_lang('Delete'),
                array(),
                ICON_SIZE_SMALL
            ),
            api_get_self() . '?' . http_build_query(array(
                'id' => $tab['id'],
                'cidReq' => api_get_course_id(),
                'id_session' => $tab['session_id'],
                'action' => 'delete',
                'description_type' => $tab['type'],
            )),
            array('onclick' => "javascript:if(!confirm('" . addslashes(api_htmlentities(
                get_lang('ConfirmYourChoice'),
                ENT_QUOTES, isset($charset) ? $charset : null)) . "')) return false;"
            )
        );
    }

    echo '</div>';

    if ($tab['is_editable']) {
        echo '<br><br>';
    }

    echo '<div class="clearfix">';
    echo $tab['content'];
    echo '</div>';
    echo '</div>';
}

echo '</div>';

echo "
    <script>
    $(document).on('ready', function () {
        $('#course-description-tabs a').click(function (e) {
            e.preventDefault();

            $(this).tab('show');
        });

        $('.btn-to-print').on('click', function (e) {
            e.preventDefault();

            var self = $(this),
                descriptionId = self.data('id');

            if (!descriptionId) {
                return;
            }

            var printWindow = window.open(
                '" . api_get_path(WEB_CODE_PATH) . "course_description/print.php?description=' + descriptionId,
                '',
                'toolbar=no,menubar=no,location=no,status=no'
            );
            printWindow.onload = function () {
                var descriptionContent = printWindow.document.getElementById('description-content');
                descriptionContent.innerHTML += '<h2>' + $('#course-description-tabs li.active a').text().trim() + '</h2>';
                descriptionContent.innerHTML += $('#course-description-tabs').next().find('.tab-pane.active .clearfix').html();

                printWindow.focus();
                printWindow.print();
            };
        });
    });
    </script>
";

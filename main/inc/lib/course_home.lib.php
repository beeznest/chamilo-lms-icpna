<?php
/* For licensing terms, see /license.txt */

class CourseHome {

    /**
     * Gets the html content to show in the 3 column view
     */
    public static function show_tool_3column($cat) {
        global $_user;
        $TBL_ACCUEIL = Database :: get_course_table(TABLE_TOOL_LIST);
        $TABLE_TOOLS = Database :: get_main_table(TABLE_MAIN_COURSE_MODULE);

        $numcols = 3;
        $table = new HTML_Table('width="100%"');
        $all_tools = array();

        $course_id = api_get_course_int_id();

        switch ($cat) {
            case 'Basic' :
                $condition_display_tools = ' WHERE a.c_id = '.$course_id.' AND  a.link=t.link AND t.position="basic" ';
                if ((api_is_coach() || api_is_course_tutor()) && $_SESSION['studentview'] != 'studentview') {
                    $condition_display_tools = ' WHERE a.c_id = '.$course_id.' AND a.link=t.link AND (t.position="basic" OR a.name = "'.TOOL_TRACKING.'") ';
                }

                $sql = "SELECT a.*, t.image img, t.row_module, t.column_module  FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                        $condition_display_tools ORDER BY t.row_module, t.column_module";
                break;
            case 'External' :
                if (api_is_allowed_to_edit()) {
                    $sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                            WHERE a.c_id = $course_id AND ((a.link=t.link AND t.position='external')
                            OR (a.visibility <= 1 AND (a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image))
                            ORDER BY a.id";
                } else {
                    $sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                            WHERE a.c_id = $course_id AND (a.visibility = 1 AND ((a.link=t.link AND t.position='external')
                            OR ((a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image)))
                            ORDER BY a.id";
                }
                break;
            case 'courseAdmin' :
                $sql = "SELECT a.*, t.image img, t.row_module, t.column_module  FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                        WHERE a.c_id = $course_id AND admin=1 AND a.link=t.link ORDER BY t.row_module, t.column_module";
                break;

            case 'platformAdmin' :
               $sql = "SELECT *, image img FROM $TBL_ACCUEIL WHERE c_id = $course_id AND visibility = 2 ORDER BY id";
        }
        $result = Database::query($sql);

        // Grabbing all the tools from $course_tool_table
        while ($tool = Database::fetch_array($result)) {
            $all_tools[] = $tool;
        }

        $course_id = api_get_course_int_id();


        // Grabbing all the links that have the property on_homepage set to 1
        if ($cat == 'External') {
            $tbl_link = Database :: get_course_table(TABLE_LINK);
            $tbl_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
            if (api_is_allowed_to_edit(null, true)) {
                $sql_links = "SELECT tl.*, tip.visibility
								FROM $tbl_link tl
                                LEFT JOIN $tbl_item_property tip ON tip.tool='link' AND tip.ref=tl.id
                                WHERE 	tl.c_id = $course_id AND
                                		tip.c_id = $course_id AND
                						tl.on_homepage='1' AND
                						tip.visibility != 2";
            } else {
                $sql_links = "SELECT tl.*, tip.visibility
                                    FROM $tbl_link tl
                                    LEFT JOIN $tbl_item_property tip ON tip.tool='link' AND tip.ref=tl.id
                                    WHERE 	tl.c_id = $course_id AND
                                			tip.c_id = $course_id AND
                							tl.on_homepage='1' AND
                							tip.visibility = 1";
            }
            $result_links = Database::query($sql_links);
            while ($links_row = Database::fetch_array($result_links)) {
                $properties = array();
                $properties['name'] = $links_row['title'];
                $properties['link'] = $links_row['url'];
                $properties['visibility'] = $links_row['visibility'];
                $properties['img'] = 'external.gif';
                $properties['adminlink'] = api_get_path(WEB_CODE_PATH).'link/link.php?action=editlink&amp;id='.$links_row['id'];
                $all_tools[] = $properties;
            }
        }

        $cell_number = 0;
        // Draw line between basic and external, only if there are entries in External
        if ($cat == 'External' && count($all_tools)) {
            $table->setCellContents(0, 0, '<hr noshade="noshade" size="1"/>');
            $table->updateCellAttributes(0, 0, 'colspan="3"');
            $cell_number += $numcols;
        }

        foreach ($all_tools as & $tool) {
            if ($tool['image'] == 'scormbuilder.gif') {
                // display links to lp only for current session
                /*if (api_get_session_id() != $tool['session_id']) {
                    continue;
                }*/
                // check if the published learnpath is visible for student
                $published_lp_id = self::get_published_lp_id_from_link($tool['link']);
                if (!api_is_allowed_to_edit(null, true) && !learnpath::is_lp_visible_for_student($published_lp_id, api_get_user_id())) {
                    continue;
                }
            }

            if (api_get_session_id() != 0 && in_array($tool['name'], array('course_maintenance', 'course_setting'))) {
                continue;
            }

            $cell_content = '';
            // The name of the tool
            $tool_name = self::translate_tool_name($tool);

            $link_annex = '';
            // The url of the tool
            if ($tool['img'] != 'external.gif') {
                $tool['link'] = api_get_path(WEB_CODE_PATH).$tool['link'];
                $qm_or_amp = strpos($tool['link'], '?') === false ? '?' : '&amp;';
                $link_annex = $qm_or_amp.api_get_cidreq();
            } else {
                // If an external link ends with 'login=', add the actual login...
                $pos = strpos($tool['link'], '?login=');
                $pos2 = strpos($tool['link'], '&amp;login=');
                if ($pos !== false or $pos2 !== false) {
                    $link_annex = $_user['username'];
                }
            }

            // Setting the actual image url
            $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img'];

            // VISIBLE
            if (($tool['visibility'] || ((api_is_coach() || api_is_course_tutor()) && $tool['name'] == TOOL_TRACKING)) || $cat == 'courseAdmin' || $cat == 'platformAdmin') {
                if (strpos($tool['name'], 'visio_') !== false) {
                    $cell_content .= '<a  href="javascript: void(0);" onclick="javascript: window.open(\'' . $tool['link'].$link_annex . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                } elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
                    $cell_content .= '<a href="javascript: void(0);" onclick="javascript: window.open(\'' .$tool['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                    // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                } else {
                    $cell_content .= '<a href="'.$tool['link'].$link_annex.'" target="'.$tool['target'].'"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                    // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                }
            } else {
                // INVISIBLE
                if (api_is_allowed_to_edit(null, true)) {
                    if (strpos($tool['name'], 'visio_') !== false) {
                        $cell_content .= '<a  href="javascript: void(0);" onclick="window.open(\'' . $tool['link'].$link_annex . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '"><img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                    } elseif (strpos($tool['name'],'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
                        $cell_content .= '<a href="javascript: void(0);" onclick="javascript: window.open(\'' .$tool['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                        // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                    } else {
                        $cell_content .= '<a href="'.$tool['link'].$link_annex.'" target="'.$tool['target'].'" class="invisible">
                                            <img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                        // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                    }
                } else {
                    $cell_content .= '<img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">';
                    // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                    $cell_content .= '<span class="invisible">'.$tool_name.'</span>';
                }
            }

            $lnk = array();
            if (api_is_allowed_to_edit(null, true) && $cat != "courseAdmin" && !strpos($tool['link'], 'learnpath_handler.php?learnpath_id') && !api_is_coach()) {
                if ($tool['visibility']) {
                    $link['name'] = Display::return_icon('remove.gif', get_lang('Deactivate'), array('style' => 'vertical-align: middle;'));
                    $link['cmd'] = "hide=yes";
                    $lnk[] = $link;
                } else {
                    $link['name'] = Display::return_icon('add.gif', get_lang('Activate'), array('style' => 'vertical-align: middle;'));
                    $link['cmd'] = "restore=yes";
                    $lnk[] = $link;
                }
                if (is_array($lnk)) {
                    foreach ($lnk as & $this_lnk) {
                        if ($tool['adminlink']) {
                            $cell_content .= '<a href="'.$properties['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
                        } else {
                            $cell_content .= '<a href="'.api_get_self().'?id='.$tool['id'].'&amp;'.$this_lnk['cmd'].'">'.$this_lnk['name'].'</a>';
                        }
                    }
                }
            }
            $table->setCellContents($cell_number / $numcols, ($cell_number) % $numcols, $cell_content);
            $table->updateCellAttributes($cell_number / $numcols, ($cell_number) % $numcols, 'width="32%" height="42"');
            $cell_number ++;
        }
        return $table->toHtml();
    } // end


    /**
     * Displays the tools of a certain category.
     *
     * @return void
     * @param string $course_tool_category	contains the category of tools to display:
     * "Public", "PublicButHide", "courseAdmin", "claroAdmin"
     */
    public static function show_tool_2column($course_tool_category) {
        $html = '';
        $web_code_path = api_get_path(WEB_CODE_PATH);
        $course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);

        $course_id = api_get_course_int_id();

        switch ($course_tool_category) {
            case TOOL_PUBLIC:
                    $condition_display_tools = ' WHERE c_id = '.$course_id.' AND visibility = 1 ';
                    if ((api_is_coach() || api_is_course_tutor()) && $_SESSION['studentview'] != 'studentview') {
                        $condition_display_tools = ' WHERE c_id = '.$course_id.' AND (visibility = 1 OR (visibility = 0 AND name = "'.TOOL_TRACKING.'")) ';
                    }
                    $result = Database::query("SELECT * FROM $course_tool_table $condition_display_tools ORDER BY id");
                    $col_link ="##003399";
                    break;
            case TOOL_PUBLIC_BUT_HIDDEN:
                    $result = Database::query("SELECT * FROM $course_tool_table WHERE c_id = $course_id AND visibility=0 AND admin=0 ORDER BY id");
                    $col_link ="##808080";
                    break;
            case TOOL_COURSE_ADMIN:
                    $result = Database::query("SELECT * FROM $course_tool_table WHERE c_id = $course_id AND admin=1 AND visibility != 2 ORDER BY id");
                    $col_link ="##003399";
                    break;
            case TOOL_PLATFORM_ADMIN:
                    $result = Database::query("SELECT * FROM $course_tool_table WHERE c_id = $course_id AND visibility = 2  ORDER BY id");
                    $col_link ="##003399";
        }
        $i = 0;

        // Grabbing all the tools from $course_tool_table
        while ($temp_row = Database::fetch_array($result)) {
            if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN && $temp_row['image'] != 'scormbuilder.gif') {
                $temp_row['image'] = str_replace('.gif', '_na.gif', $temp_row['image']);
            }
            $all_tools_list[] = $temp_row;
        }

        // Grabbing all the links that have the property on_homepage set to 1
        $course_link_table 			= Database::get_course_table(TABLE_LINK);
        $course_item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);

        switch ($course_tool_category)  {
            case TOOL_PUBLIC:
                $sql_links="SELECT tl.*, tip.visibility
                        FROM $course_link_table tl
                        LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tl.c_id = tip.c_id AND tl.c_id = $course_id AND tip.ref=tl.id
                        WHERE tl.on_homepage='1' AND tip.visibility = 1";
                break;
            case TOOL_PUBLIC_BUT_HIDDEN:
                $sql_links="SELECT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tl.c_id = tip.c_id AND tl.c_id = $course_id AND tip.ref=tl.id
                    WHERE tl.on_homepage='1' AND tip.visibility = 0";

                break;
            default:
                $sql_links = null;
                break;
        }
        if ($sql_links != null) {
            $properties = array();
            $result_links = Database::query($sql_links);
            while ($links_row = Database::fetch_array($result_links)) {
                unset($properties);
                $properties['name'] = $links_row['title'];
                $properties['link'] = $links_row['url'];
                $properties['visibility'] = $links_row['visibility'];
                $properties['image'] = $course_tool_category == TOOL_PUBLIC_BUT_HIDDEN ? 'external_na.gif' : 'external.gif';
                $properties['adminlink'] = api_get_path(WEB_CODE_PATH).'link/link.php?action=editlink&id='.$links_row['id'];
                $all_tools_list[] = $properties;
            }
        }
        if (isset($all_tools_list)) {
            $lnk = array();
            foreach ($all_tools_list as & $tool) {

                if ($tool['image'] == 'scormbuilder.gif') {
                    // display links to lp only for current session
                    /*if (api_get_session_id() != $tool['session_id']) {
                        continue;
                    }*/
                    // check if the published learnpath is visible for student
                    $published_lp_id = self::get_published_lp_id_from_link($tool['link']);

                    if (!api_is_allowed_to_edit(null, true) && !learnpath::is_lp_visible_for_student($published_lp_id,api_get_user_id())) {
                        continue;
                    }
                }

                if (api_get_session_id() != 0 && in_array($tool['name'], array('course_maintenance', 'course_setting'))) {
                    continue;
                }

                if (!($i % 2)) {
                    $html .= "<tr valign=\"top\">";
                }

                // NOTE : Table contains only the image file name, not full path
                if (stripos($tool['link'], 'http://') === false && stripos($tool['link'], 'https://') === false && stripos($tool['link'], 'ftp://') === false) {
                    $tool['link'] = $web_code_path.$tool['link'];
                }
                if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN) {
                    $class = 'class="invisible"';
                }
                $qm_or_amp = strpos($tool['link'], '?') === false ? '?' : '&amp;';

                $tool['link'] = $tool['link'];
                $html .=  '<td width="50%" height="30">';

                if (strpos($tool['name'], 'visio_') !== false) {
                    $html .=  '<a  '.$class.' href="javascript: void(0);" onclick="javascript: window.open(\'' . htmlspecialchars($tool['link']).(($tool['image'] == 'external.gif' || $tool['image'] == 'external_na.gif') ? '' : $qm_or_amp.api_get_cidreq()) . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '">';
                } elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
                    $html .=  '<a href="javascript: void(0);" onclick="javascript: window.open(\'' . htmlspecialchars($tool['link']).$qm_or_amp.api_get_cidreq() . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '"'.$class.'>';
                } else {
                    $html .=  '<a href="'.htmlspecialchars($tool['link']).(($tool['image'] == 'external.gif' || $tool['image'] == 'external_na.gif') ? '' : $qm_or_amp.api_get_cidreq()).'" target="'.$tool['target'].'" '.$class.'>';
                }

                $tool_name = self::translate_tool_name($tool);
                $html .=  Display::return_icon($tool['image'], $tool_name, array(), null, ICON_SIZE_MEDIUM).'&nbsp;'.$tool_name.'</a>';

                // This part displays the links to hide or remove a tool.
                // These links are only visible by the course manager.
                unset($lnk);
                if (api_is_allowed_to_edit(null, true) && !api_is_coach()) {

                    if ($tool['visibility'] == '1' || $tool['name'] == TOOL_TRACKING) {
                        $link['name'] = Display::return_icon('remove.gif', get_lang('Deactivate'));
                        $link['cmd'] = 'hide=yes';
                        $lnk[] = $link;
                    }

                    if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN) {
                        $link['name'] = Display::return_icon('add.gif', get_lang('Activate'));
                        $link['cmd']  = 'restore=yes';
                        $lnk[] = $link;

                        if ($tool['added_tool'] == 1) {
                            $link['name'] = Display::return_icon('delete.gif', get_lang('Remove'));
                            $link['cmd']  = 'remove=yes';
                            $lnk[] = $link;
                        }
                    }
                    if ($tool['adminlink']) {
                        $html .=  '<a href="'.$tool['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
                    }

                }
                if (api_is_platform_admin() && !api_is_coach()) {
                    if ($tool['visibility'] == 2) {
                        $link['name'] = Display::return_icon('undelete.gif', get_lang('Activate'));

                        $link['cmd']  = 'hide=yes';
                        $lnk[] = $link;

                        if ($tool['added_tool'] == 1) {
                            $link['name'] = get_lang('Delete');
                            $link['cmd'] = 'askDelete=yes';
                            $lnk[] = $link;
                        }
                    }
                    if ($tool['visibility'] == 0  && $tool['added_tool'] == 0) {
                        $link['name'] = Display::return_icon('delete.gif', get_lang('Remove'));
                        $link['cmd'] = 'remove=yes';
                        $lnk[] = $link;
                    }
                }
                if (is_array($lnk)) {
                    foreach ($lnk as & $this_link) {
                        if (!$tool['adminlink']) {
                            $html .=  '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;id='.$tool['id'].'&amp;'.$this_link['cmd'].'">'.$this_link['name'].'</a>';
                        }
                    }
                }
                $html .=  "</td>";

                if ($i % 2) {
                    $html .=  "</tr>";
                }

                $i++;
            }
        }

        if ($i % 2) {
            $html .=  "<td width=\"50%\">&nbsp;</td></tr>";
        }
        return $html;
    }

    /**
     * Gets the tools of a certain category. Returns an array expected
     * by show_tools_category()
     * @param string $course_tool_category	contains the category of tools to
     * display: "toolauthoring", "toolinteraction", "tooladmin", "tooladminplatform", "toolplugin"
     * @return array
     */

    public static function get_tools_category($course_tool_category) {
        $course_tool_table  = Database::get_course_table(TABLE_TOOL_LIST);
        $is_platform_admin  = api_is_platform_admin();
        $all_tools_list = array();

        // Condition for the session
        $session_id 			= api_get_session_id();
        $course_id              = api_get_course_int_id();
        $condition_session 		= api_get_session_condition($session_id, true, true);
        $visibilityCondition = "visibility = 1 ";
        if (api_is_platform_admin() || api_is_course_coach()) {
            $visibilityCondition = "visibility IN (1, 2) ";
        }

        switch ($course_tool_category) {
            case TOOL_STUDENT_VIEW:
                    $condition_display_tools = ' WHERE ' . $visibilityCondition . ' AND (category = "authoring" OR category = "interaction" OR category = "plugin") ';
                    if ((api_is_coach() || api_is_course_tutor()) && $_SESSION['studentview'] != 'studentview') {
                        $condition_display_tools = ' WHERE ( ' . $visibilityCondition . ' AND (category = "authoring" OR category = "interaction" OR category = "plugin") OR (name = "'.TOOL_TRACKING.'") )   ';
                    }
                    $sql = "SELECT * FROM $course_tool_table  $condition_display_tools AND c_id = $course_id $condition_session ORDER BY id";
                    $result = Database::query($sql);
                    $col_link ="##003399";
                    break;
            case TOOL_AUTHORING:
                    $sql = "SELECT * FROM $course_tool_table WHERE category = 'authoring' AND c_id = $course_id $condition_session ORDER BY id";
                    $result = Database::query($sql);
                    $col_link ="##003399";
                    break;
            case TOOL_INTERACTION:
                    $sql = "SELECT * FROM $course_tool_table WHERE category = 'interaction' AND c_id = $course_id $condition_session ORDER BY id";
                    $result = Database::query($sql);
                    $col_link ="##003399";
                    break;
            case TOOL_ADMIN_VISIBLE:
                    $sql = "SELECT * FROM $course_tool_table WHERE category = 'admin' AND ' . $visibilityCondition . ' AND c_id = $course_id $condition_session ORDER BY id";
                    $result = Database::query($sql);
                    $col_link ="##003399";
                    break;
            case TOOL_ADMIN_PLATFORM:
                    $sql = "SELECT * FROM $course_tool_table WHERE category = 'admin' AND c_id = $course_id $condition_session ORDER BY id";
                    $result = Database::query($sql);
                    $col_link ="##003399";
                    break;
            case TOOL_COURSE_PLUGIN:
                    //Other queries recover id, name, link, image, visibility, admin, address, added_tool, target, category and session_id
                    // but plugins are not present in the tool table, only globally and inside the course_settings table once configured
                    $sql = "SELECT * FROM $course_tool_table WHERE category = 'plugin' AND c_id = $course_id $condition_session ORDER BY id";
                    $result = Database::query($sql);
                    break;
        }

        //Get the list of hidden tools - this might imply performance slowdowns
        // if the course homepage is loaded many times, so the list of hidden
        // tools might benefit from a shared memory storage later on
        $list = api_get_settings('Tools','list', api_get_current_access_url_id());
        $hide_list = array();
        $check = false;

        foreach ($list as $line) {
            //Admin can see all tools even if the course_hide_tools configuration is set
            if ($is_platform_admin) {
                continue;
            }
            if ($line['variable'] == 'course_hide_tools' and $line['selected_value'] == 'true') {
                $hide_list[] = $line['subkey'];
                $check = true;
            }
        }

        while ($temp_row = Database::fetch_assoc($result)) {
            if ($check) {
                if (!in_array($temp_row['name'], $hide_list)) {
                    $all_tools_list[] = $temp_row;
                }
            } else {
                $all_tools_list[] = $temp_row;
            }
        }

        $i = 0;
        // Grabbing all the links that have the property on_homepage set to 1
        $course_link_table 			= Database::get_course_table(TABLE_LINK);
        $course_item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);


        switch ($course_tool_category) {
            case TOOL_AUTHORING:
                $sql_links = "SELECT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
                    WHERE 	tl.c_id = $course_id AND
                            tip.c_id = $course_id AND
                            tl.on_homepage='1' $condition_session";
                break;
            case TOOL_INTERACTION:
                $sql_links = null;
                /*
                $sql_links = "SELECT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
                        WHERE tl.on_homepage='1' ";
                */
                break;
            case TOOL_STUDENT_VIEW:
                $sql_links = "SELECT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
                        WHERE 	tl.c_id 		= $course_id AND
                                tip.c_id 		= $course_id AND
                                tl.on_homepage	='1' $condition_session";
                break;
            case TOOL_ADMIN:
                $sql_links = "SELECT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
                    WHERE 	tl.c_id = $course_id AND
                            tip.c_id = $course_id AND
                            tl.on_homepage='1' $condition_session";
                break;
            default:
                $sql_links = null;
                break;
        }

        // Edited by Kevin Van Den Haute (kevin@develop-it.be) for integrating Smartblogs
        if ($sql_links != null) {
            $result_links = Database::query($sql_links);

            if (Database::num_rows($result_links) > 0) {
                while ($links_row = Database::fetch_array($result_links, 'ASSOC')) {
                    $properties = array();
                    $properties['name']         = $links_row['title'];
                    $properties['session_id']   = $links_row['session_id'];
                    $properties['link']         = $links_row['url'];
                    $properties['visibility']   = $links_row['visibility'];
                    $properties['image']        = ($links_row['visibility'] == '0') ? 'file_html.gif' : 'file_html.gif';
                    $properties['adminlink']    = api_get_path(WEB_CODE_PATH).'link/link.php?action=editlink&id='.$links_row['id'];
                    $properties['target']       = $links_row['target'];
                    $tmp_all_tools_list[]       = $properties;
                }
            }
        }

        if (isset($tmp_all_tools_list)) {
            foreach ($tmp_all_tools_list as $tool) {
                if ($tool['image'] == 'blog.gif') {
                    // Init
                    $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);

                    // Get blog id
                    $blog_id = substr($tool['link'], strrpos($tool['link'], '=') + 1, strlen($tool['link']));

                    // Get blog members
                    if ($is_platform_admin) {
                        $sql_blogs = "SELECT * FROM $tbl_blogs_rel_user blogs_rel_user WHERE blog_id =" . $blog_id;
                    } else {
                        $sql_blogs = "SELECT * FROM $tbl_blogs_rel_user blogs_rel_user WHERE blog_id =" . $blog_id . " AND user_id = " . api_get_user_id();
                    }
                    $result_blogs = Database::query($sql_blogs);

                    if (Database::num_rows($result_blogs) > 0) {
                        $all_tools_list[] = $tool;
                    }
                } else {
                    $all_tools_list[] = $tool;
                }
            }
        }

        $all_tools_list = CourseHome::filterExternalPagesPlugin($all_tools_list, $course_tool_category);

        return $all_tools_list;
    }

    /**
    * filter for view icons only show if patronKey is = :teacher
    * example dataIcons[i]['name']: parameter titleIcons1:teacher || titleIcons2 || titleIcons3:teacher
    * @param array $dataIcons array reference to icons
    * @return array
    */
    private static function filterExternalPagesPlugin($dataIcons, $course_tool_category)
    {
        $patronKey = ':teacher';
        if ($course_tool_category == TOOL_STUDENT_VIEW) {
            //Fix only coach can see external pages - see #8236 - icpna
            if (api_is_coach()) {
                foreach ($dataIcons as $indice => $array) {
                    if (isset($array['name'])) {
                        $dataIcons[$indice]['name'] = str_replace($patronKey, '', $array['name']);
                    }
                }
                
                return $dataIcons;
            }
            $flagOrder = false;
            foreach ($dataIcons as $indice => $array) {
                if (isset($array['name'])) {
                    $pos = strpos($array['name'], $patronKey);
                    if ($pos !== false) {
                        unset($dataIcons[$indice]);
                        $flagOrder = true;
                    }
                }
            }

            if ($flagOrder) {
                $dataIcons = array_values($dataIcons);
            }
        } else {
            // clean patronKey of name icons
            foreach ($dataIcons as $indice => $array) {
                if (isset($array['name'])) {
                    $dataIcons[$indice]['name'] = str_replace($patronKey, '', $array['name']);
                }
            }
        }

        return $dataIcons;
    }


    /**
     * Displays the tools of a certain category.
     * @param array List of tools as returned by get_tools_category()
     * @param   int rows
     * @return void
     */
    public static function show_tools_category($all_tools_list, $rows = false) {
        global $_user;
        $theme = api_get_setting('homepage_view');
        if ($theme == 'vertical_activity') {
            //ordering by get_lang name
            $order_tool_list = array();
            if (is_array($all_tools_list) && count($all_tools_list)>0) {
                foreach($all_tools_list as $key=>$new_tool) {
                    $tool_name = self::translate_tool_name($new_tool);
                    $order_tool_list [$key]= $tool_name;
                }
                natsort($order_tool_list);
                $my_temp_tool_array = array();
                foreach($order_tool_list as $key=>$new_tool) {
                    $my_temp_tool_array[] = $all_tools_list[$key];
                }
                $all_tools_list = $my_temp_tool_array;
            } else {
                $all_tools_list = array();
            }
        }
        $web_code_path      = api_get_path(WEB_CODE_PATH);
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
        $is_platform_admin  = api_is_platform_admin();

        $session_id = api_get_session_id();

        $i = 0;
        $items = array();
        $app_plugin = new AppPlugin();

        if (isset($all_tools_list)) {
            $lnk = '';
            foreach ($all_tools_list as & $tool) {
                $item = array();

                $tool['original_link'] = $tool['link'];

                if ($tool['image'] == 'scormbuilder.gif') {
                    // display links to lp only for current session
                    /*if ($session_id != $tool['session_id']) {
                        continue;
                    }*/
                    // check if the published learnpath is visible for student
                    $published_lp_id = self::get_published_lp_id_from_link($tool['link']);
                    if (!api_is_allowed_to_edit(null, true) && !learnpath::is_lp_visible_for_student($published_lp_id,api_get_user_id())) {
                        continue;
                    }
                }

                if ($session_id != 0 && in_array(
                        $tool['name'],
                        array('course_setting')
                    )
                ) {
                    continue;
                }

                // This part displays the links to hide or remove a tool.
                // These links are only visible by the course manager.
                unset($lnk);

                $item['extra'] = null;
                if ($is_allowed_to_edit && !api_is_coach()) {

                    if (empty($session_id)) {
                        if ($tool['visibility'] == '1' && $tool['admin'] != '1') {
                            $link['name'] = Display::return_icon('visible.gif', get_lang('Deactivate'), array('id' => 'linktool_'.$tool['id']), ICON_SIZE_MEDIUM, false);
                            $link['cmd'] = 'hide=yes';
                            $lnk[] = $link;
                        }
                        if ($tool['visibility'] == '0' && $tool['admin'] != '1') {
                            $link['name'] = Display::return_icon('invisible.gif', get_lang('Activate'), array('id' => 'linktool_'.$tool['id']), ICON_SIZE_MEDIUM, false);
                            $link['cmd'] = 'restore=yes';
                            $lnk[] = $link;
                        }
                    }

                    if (!empty($tool['adminlink'])) {
                        $item['extra'] = '<a href="'.$tool['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
                    }
                }

                // Both checks are necessary as is_platform_admin doesn't take student view into account
                if ($is_platform_admin && $is_allowed_to_edit) {
                     if ($tool['admin'] != '1') {
                        $link['cmd'] = 'hide=yes';
                    }
                }

                $item['visibility'] = null;

                if (isset($lnk) && is_array($lnk)) {
                    foreach ($lnk as $this_link) {
                        if (empty($tool['adminlink'])) {
                            $item['visibility'] .=  '<a class="make_visible_and_invisible" href="'.api_get_self().'?'.api_get_cidreq().'&amp;id='.$tool['id'].'&amp;'.$this_link['cmd'].'">'.$this_link['name'].'</a>';
                        }
                    }
                } else {
                    $item['visibility'] .=  '&nbsp;&nbsp;&nbsp;&nbsp;';
                }

                // NOTE : Table contains only the image file name, not full path
                if (stripos($tool['link'], 'http://') === false && stripos($tool['link'], 'https://') === false && stripos($tool['link'], 'ftp://') === false) {
                    $tool['link'] = $web_code_path.$tool['link'];
                }
                if ($tool['visibility'] == '0' && $tool['admin'] != '1') {
                      $class = 'invisible';
                      $info = pathinfo($tool['image']);
                      $basename = basename($tool['image'], '.'.$info['extension']); // $file is set to "index"
                    $tool['image'] = $basename.'_na.'.$info['extension'];
                } else {
                    $class = '';
                }

                $qm_or_amp = strpos($tool['link'], '?') === false ? '?' : '&';
                // If it's a link, we don't add the cidReq
                if ($tool['image'] == 'file_html.gif' || $tool['image'] == 'file_html_na.gif') {
                    $tool['link'] = $tool['link'].$qm_or_amp;
                } else {
                    $tool['link'] = $tool['link'].$qm_or_amp.api_get_cidreq();
                }

                $tool_link_params = array();

                //$tool['link'] = htmlspecialchars($tool['link']) ;
                //@todo this visio stuff should be removed
                if (strpos($tool['name'],'visio_') !== false) {
                    $tool_link_params = array('id'      => 'tooldesc_'.$tool["id"],
                                              'href'    => '"javascript: void(0);"',
                                              'class'   => $class,
                                              'onclick' => 'javascript: window.open(\'' . $tool['link'] . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')',
                                              'target'  =>  $tool['target']);
                } elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
                    $tool_link_params = array('id'      => 'tooldesc_'.$tool["id"],
                                              'class'   => $class,
                                              'href'    => 'javascript: void(0);',
                                              'onclick' => 'javascript: window.open(\'' . $tool['link'] . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')',
                                              'target'  =>  $tool['target']);
                } else {
                    if (count(explode('type=classroom',$tool['link'])) == 2 || count(explode('type=conference', $tool['link'])) == 2) {
                        $tool_link_params = array('id'      => 'tooldesc_'.$tool["id"],
                                                  'href'    => $tool['link'],
                                                  'class'   => $class,
                                                  'target'  =>  '_blank');


                    } else {
                        $tool_link_params = array('id'      => 'tooldesc_'.$tool["id"],
                                                  'href'    => $tool['link'],
                                                  'class'   => $class,
                                                  'target'  => $tool['target']);
                    }
                }

                $tool_name = self::translate_tool_name($tool);

                // Including Courses Plugins
                // Creating title and the link

                if (isset($tool['category']) &&  $tool['category'] == 'plugin') {
                    $plugin_info = $app_plugin->get_plugin_info($tool['name']);
                    if (isset($plugin_info) && isset($plugin_info['title'])) {
                        $tool_name = $plugin_info['title'];
                    }
                    $tool_link_params['href'] = api_get_path(WEB_PLUGIN_PATH).$tool['original_link'].'?'.api_get_cidreq();
                }

                $icon = Display::return_icon($tool['image'], $tool_name, array('class' => 'tool-icon', 'id' => 'toolimage_'.$tool['id']), ICON_SIZE_BIG, false);

                // Validation when belongs to a session
                $session_img = api_get_session_image($tool['session_id'], $_user['status']);
                $item['url_params'] = $tool_link_params;
                $item['icon']       = Display::url($icon, $tool_link_params['href'], $tool_link_params);
                $item['tool']       = $tool;
                $item['name']       = $tool_name;

                $tool_link_params['id'] = 'is'.$tool_link_params['id'];
                $item['link']       = Display::url($tool_name.$session_img, $tool_link_params['href'], $tool_link_params);

                $items[] = $item;

                $i++;
            } // end of foreach
        }

        $i = 0;

        $html = '';

        if (!empty($items)) {
            foreach ($items as $item) {
                switch ($theme) {
                    case 'activity_big':
                        $data = '';
                        $html .=  '<div class="span4 course-tool">';
                        $image = (substr($item['tool']['image'], 0, strpos($item['tool']['image'], '.'))).'.png';

                        $original_image = Display::return_icon($image, $item['name'], array('id'=>'toolimage_'.$item['tool']['id']), ICON_SIZE_BIG, false);

                        switch ($image) {
                            case 'scormbuilder.png':
                                if (api_is_allowed_to_edit(null, true)) {
                                    $item['url_params']['href'] .= '&isStudentView=true';
                                }
                                $image = $original_image;
                                $lp_id = self::get_published_lp_id_from_link($item['link']);
                                if ($lp_id) {
                                    $lp = new learnpath(api_get_course_id(), $lp_id, api_get_user_id());
                                    $path = $lp->get_preview_image_path(64);
                                    if ($path) {
                                        $image = '<img src="'.$path.'">';
                                    }
                                }
                                break;
                            default:
                                $image = $original_image;
                        }

                        $data .= Display::url($image , $item['url_params']['href'], $item['url_params']);
                        $html .= Display::div($data, array('class'=>'big_icon')); //box-image reflection
                        $html .= Display::div('<h4>'.$item['visibility'].$item['extra'].$item['link'].'</h4>', array('class'=>'content'));
                        $html .=  '</div>';
                        break;
                    case 'activity':
                        $html .=  '<div class="offset2 span4 course-tool">';
                            $html .=  $item['extra'];
                            $html .=  $item['visibility'];
                            $html .=  $item['icon'];
                            $html .=  $item['link'];
                        $html .=  '</div>';
                        break;
                    case 'vertical_activity':
                        if ($i == 0) {
                            $html .=  '<ul>';
                        }
                        $html .=  '<li class="course-tool">';
                            $html .=  $item['extra'];
                            $html .=  $item['visibility'];
                            $html .=  $item['icon'];
                            $html .=  $item['link'];
                        $html .=  '</li>';

                        if ($i == count($items) -1) {
                            $html .=  '</ul>';
                        }
                        break;
                }
                $i++;
            }
        }
        return $html;
    }

    /**
     * Shows the general data for a particular meeting
     *
     * @param id	session id
     * @return string	session data
     */
    public static function show_session_data($id_session) {

        if ($id_session != strval(intval($id_session))) {
            return '';
        } else {
            $id_session = intval($id_session);
        }

        $session_info = api_get_session_info($id_session);
        $session_category = SessionManager::get_session_category($session_info['session_category_id']);

        $session_category_name = null;
        if (!empty($session_category)) {
            $session_category_name = $session_category['name'];
        }
        $user_info = api_get_user_info($session_info['id_coach']);
        $general_coach = null;
        if (!empty($user_info)) {
            $general_coach = $user_info['complete_name'].' ('.$user_info['username'].')';
        }
        $msg_date = SessionManager::parse_session_dates($session_info);

        $output  = '';
        if (!empty($session_category)) {
            $output .= '<tr><td>'. get_lang('SessionCategory') . ': ' . '<b>' . $session_category_name .'</b></td></tr>';
        }
        $output .= '<tr>
                        <td style="width:50%">'. get_lang('SessionName') . ': ' . '<b>' . $session_info['name'] .'</b></td>
                        <td>'. get_lang('GeneralCoach') . ': ' . '<b>' .$general_coach.'</b></td></tr>';
        $output .= '<tr><td>'. get_lang('SessionIdentifier') . ': '. Display::return_icon('star.png', ' ', array('align' => 'absmiddle')) .'</td>
                        <td>'. get_lang('Date') . ': ' . '<b>' . $msg_date .'</b></td></tr>';

        return $output;
    }

    /**
     * Retrieves the name-field within a tool-record and translates it on necessity.
     * @param array $tool		The input record.
     * @return string			Returns the name of the corresponding tool.
     */
    public static function translate_tool_name(& $tool) {
        static $already_translated_icons = array(
            'file_html.gif',
            'file_html_na.gif',
            'scormbuilder.gif',
            'scormbuilder_na.gif',
            'blog.gif',
            'blog_na.gif',
            'external.gif',
            'external_na.gif'
        );

        if (in_array($tool['image'], $already_translated_icons)) {
            $tool_name = Security::remove_XSS(stripslashes($tool['name']));
        } else {
            /*
            // The following (slow) code was made in the past for transitional purposes.
            // We assume that the new language variables Tool* have been already translated.
            $variable = 'Tool'.api_underscore_to_camel_case($tool['name']); // The newly opened language variables.
            $variable_old = ucfirst($tool['name']);         // The old language variables as a second chance.
            if (api_is_translated($variable)) {
                $tool_name = get_lang($variable);
            } elseif (api_is_translated($variable_old)) {
                $tool_name = get_lang($variable_old);
            } else {
                $tool_name = get_lang($variable);
            }
            */
            if (strpos($tool['link'], 'add_page_plugin') !== false) {
                $tool_name = trim(api_underscore_to_camel_case($tool['name']));
            } else {
                $tool_name = get_lang('Tool'.api_underscore_to_camel_case($tool['name']));
            }
        }

        return $tool_name;
    }

    /**
     * Get published learning path id from link inside course home
     * @param 	string	Link to published lp
     * @return	int		Learning path id
     */
    public static function get_published_lp_id_from_link($published_lp_link) {
        $lp_id = 0;
        $param_lp_id = strstr($published_lp_link, 'lp_id=');
        if (!empty($param_lp_id)) {
            $a_param_lp_id = explode('=',$param_lp_id);
            if (isset($a_param_lp_id[1])) {
                $lp_id = intval($a_param_lp_id[1]);
            }
        }
        return $lp_id;
    }

    static function get_navigation_items($include_admin_tools = false) {
        $navigation_items = array();
        $course_id = api_get_course_int_id();

        if (!empty($course_id)) {

            $course_tools_table = Database :: get_course_table(TABLE_TOOL_LIST);

            /*	Link to the Course homepage */

            $navigation_items['home']['image'] = 'home.gif';
            $navigation_items['home']['link'] = api_get_path(REL_COURSE_PATH).Security::remove_XSS($_SESSION['_course']['path']).'/index.php';
            $navigation_items['home']['name'] = get_lang('CourseHomepageLink');

            $sql_menu_query = "SELECT * FROM $course_tools_table WHERE c_id = $course_id AND visibility='1' and admin='0' ORDER BY id ASC";
            $sql_result = Database::query($sql_menu_query);
            while ($row = Database::fetch_array($sql_result)) {
                $navigation_items[$row['id']] = $row;
                if (stripos($row['link'], 'http://') === false && stripos($row['link'], 'https://') === false) {
                    $navigation_items[$row['id']]['link'] = api_get_path(REL_CODE_PATH).$row['link'];
                    $navigation_items[$row['id']]['name'] = CourseHome::translate_tool_name($row);
                }
            }

            /*	Admin (edit rights) only links
                - Course settings (course admin only)
                - Course rights (roles & rights overview) */

            if ($include_admin_tools) {
                $course_settings_sql = "SELECT name,image FROM $course_tools_table
                                        WHERE c_id = $course_id  AND link='course_info/infocours.php'";
                $sql_result = Database::query($course_settings_sql);
                $course_setting_info = Database::fetch_array($sql_result);
                $course_setting_visual_name = CourseHome::translate_tool_name($course_setting_info);
                if (api_get_session_id() == 0) {
                    // course settings item
                    $navigation_items['course_settings']['image'] = $course_setting_info['image'];
                    $navigation_items['course_settings']['link'] = api_get_path(REL_CODE_PATH).'course_info/infocours.php';
                    $navigation_items['course_settings']['name'] = $course_setting_visual_name;
                }
            }
        }

        foreach ($navigation_items as $key => $navigation_item) {
            if (strstr($navigation_item['link'], '?')) {
                //link already contains a parameter, add course id parameter with &
                $parameter_separator = '&amp;';
            } else {
                //link doesn't contain a parameter yet, add course id parameter with ?
                $parameter_separator = '?';
            }
            //$navigation_items[$key]['link'] .= $parameter_separator.api_get_cidreq();
            $navigation_items[$key]['link'] .= $parameter_separator.'cidReq='.api_get_course_id().'&gidReq=0&id_session='.api_get_session_id();
        }

        return $navigation_items;
    }

    /**
    * Show a navigation menu
    */
    static function show_navigation_menu() {
        $navigation_items = self::get_navigation_items(true);
        $course_id = api_get_course_id();

        $html = '<div id="toolnav"> <!-- start of #toolnav -->';
        if (api_get_setting('show_navigation_menu') == 'icons') {
            $html .= self::show_navigation_tool_shortcuts($orientation = SHORTCUTS_VERTICAL);
        } else {
            $html .= '<div id="toolnavbox">';
            $html .= '<div id="toolnavlist"><dl>';
            foreach ($navigation_items as $key => $navigation_item) {
                //students can't see the course settings option
                if (!api_is_allowed_to_edit() && $key == 'course_settings') {
                    continue;
                }
                $html .= '<dd>';
                $url_item = parse_url($navigation_item['link']);
                $url_current = parse_url($_SERVER['REQUEST_URI']);

                if (strpos($navigation_item['link'], 'chat') !== false && api_get_course_setting('allow_open_chat_window', $course_id)) {
                    $html .= '<a href="javascript: void(0);" onclick="javascript: window.open(\''.$navigation_item['link'].'\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $navigation_item['target'] . '"';
                } else {
                    $html .= '<a href="'.$navigation_item['link'].'" target="_top" ';
                }

                if (stristr($url_item['path'], $url_current['path'])) {
                    if (!isset($_GET['learnpath_id']) || strpos($url_item['query'],'learnpath_id='.$_GET['learnpath_id']) === 0) {
                        $html .= ' id="here"';
                    }
                }
                $html .= ' title="'.$navigation_item['name'].'">';
                if (api_get_setting('show_navigation_menu') != 'text') {
                    $html .= '<div align="left"><img src="'.api_get_path(WEB_IMG_PATH).$navigation_item['image'].'" alt="'.$navigation_item['name'].'"/></div>';
                }
                if (api_get_setting('show_navigation_menu') != 'icons') {
                    $html .= $navigation_item['name'];
                }
                $html .= '</a>';
                $html .= '</dd>';
            }
            $html .= '</dl></div></div>';
        }
        $html .= '</div><!-- end "#toolnav" -->';
        return $html;
    }

    /**
    * Show a toolbar with shortcuts to the course tool
    */
    static function show_navigation_tool_shortcuts($orientation = SHORTCUTS_HORIZONTAL) {
        $navigation_items = self::get_navigation_items(false);
        $html = '';
        if (!empty($navigation_items)) {
            if ($orientation == SHORTCUTS_HORIZONTAL)
                $style_id = "toolshortcuts_horizontal";
            else {
                $style_id = "toolshortcuts_vertical";
            }
            $html .= '<div id="'.$style_id.'">';

            foreach ($navigation_items as $key => $navigation_item) {
                if (strpos($navigation_item['link'],'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
                    $html .= '<a href="javascript: void(0);" onclick="javascript: window.open(\''.$navigation_item['link'].'\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $navigation_item['target'] . '"';
                } else {
                    $html .= '<a href="'.$navigation_item['link'].'"';
                }
                if (strpos(api_get_self(), $navigation_item['link']) !== false) {
                    $html .= ' id="here"';
                }
                $html .= ' target="_top" title="'.$navigation_item['name'].'">';
                $html .= '<img src="'.api_get_path(WEB_IMG_PATH).$navigation_item['image'].'" alt="'.$navigation_item['name'].'"/>';
                $html .= '</a> ';
                if ($orientation == SHORTCUTS_VERTICAL) {
                    $html .= '<br />';
                }
            }
            $html .= '</div>';
        }
        return $html;
    }
}

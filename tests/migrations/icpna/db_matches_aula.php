<?php

/**
 * This script defines as closely as possible the matches between
 * original tables and destination (chamilo) tables and fields.
 * See db_matches.dist.php for formatting documentation
 */

//intIdTipoEmpleado = 2  = Profesor
// This is an array that matches objects (for Chamilo)
$matches = array(
  
    //Sessions
    /* Debería ser 63759 con la última data del servidor de prueba */
    array(
        'orig_table' => 'ProgramaAcademico',
        'query' => 'SELECT %s, SUBSTRING(s.vchNombreSede,14,12) + \' - \' + chrPeriodo + \' - \' +vchNombreCurso + \' \' + chrHoraInicial + \' \' + chrHoraFinal +  \' \' + a.vchIdentificadorFisico as session_name '.
                   ' FROM ProgramaAcademico p '.
                   ' INNER JOIN Curso c ON p.uidIdCurso = c.uidIdCurso '.
                   ' INNER JOIN Horario h ON h.uidIdHorario = p.uidIdHorario '.
                   ' LEFT JOIN Aula a ON a.uidIdAula = p.uidIdAula '.
                   ' INNER JOIN Sede s ON (s.uidIdSede = p.uidIdSede AND s.intIdSede = '.$branch.') '.
                   ' LEFT JOIN Empleado e ON (e.uidIdEmpleado = p.uidIdProfesor) '.
                   '',
        'dest_table' => 'session',
        'dest_func' => 'MigrationCustom::createSession',
        'order' => 'ORDER BY chrPeriodo',
        'extra_fields' => array(            
            /*'uidIdPrograma' => array(                
                'field_display_text' => 'uidIdPrograma',
                'field_variable' => 'uidIdPrograma',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_TEXT,                
            ),*/
            /*
            'aula' => array(                
                'field_display_text' => 'Aula',
                'field_variable' => 'aula',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_TEXT,                
            ),*/            
            'aula' => array(        
                'field_display_text' => 'Aula',
                'field_variable' => 'aula',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'options' => array(
                    'orig_table' => 'Aula',
                    'query' => 'SELECT %s  FROM Aula ',
                    'fields_match' => array(
                        array(
                            'orig' => 'vchIdentificadorFisico',
                            'sql_alter' => null,
                            'dest' => 'option_display_text',
                            'func' => '',
                        ),        
                        array(
                            'orig' => 'uidIdAula',
                            'sql_alter' => 'sqlAlterUnhash50',
                            'dest' => 'option_value',
                            'func' => null,
                        ),     
                    )
                ),
            ),
        ),
        'fields_match' => array(         
            array(                
                'orig' => 'chrPeriodo',
                'sql_alter' => '',
                'dest' => 'name',
                'func' => 'cleanSessionName',
            ),
            array(
                'orig' => 'sdtFechaInicioClases',
                'sql_alter' => '',
                'dest' => 'display_start_date',
                'func' => 'none',
            ),
            array(
                'orig' => 'sdtFechaFinClases',
                'sql_alter' => '',
                'dest' => 'display_end_date',
                'func' => 'none',
            ),
            array(
                'orig' => 'sdtFechaInicioClases',
                'sql_alter' => '',
                'dest' => 'access_start_date',
                'func' => 'none',
            ),
            array(
                'orig' => 'sdtFechaFinClases',
                'sql_alter' => '',
                'dest' => 'access_end_date',
                'func' => 'none',
            ),
            //Getting info from chamilo
            array(
                'orig' => 'p.uidIdCurso',
                'sql_alter' => 'sqlAlterUnhash50',
                'dest' => 'course_code',
                'func' => 'getRealCourseCode',
            ),
            array(
                'orig' => 'uidIdPersona',
                'sql_alter' => 'sqlAlterUnhash50',
                'dest' => 'id_coach',
                'func' => 'getRealTeacherID',
            ),
            //Extra fields
            array(
                'orig' => 'uidIdPrograma',
                'sql_alter' => 'sqlAlterUnhash50',
                'dest' => 'extra_field_uidIdPrograma',
                'func' => null,
            ),
            array (
                'orig' => 'p.uidIdAula',
                'sql_alter' => 'sqlAlterUnhash50',
                'dest' => 'extra_field_aula',
                'func' => null,
            ),           
        ),
    ),
);

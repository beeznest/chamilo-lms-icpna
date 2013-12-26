<?php
/**
 * This script defines as closely as possible the matches between
 * original tables and destination (chamilo) tables and fields.
 * See db_matches.dist.php for formatting documentation
 */
//intIdTipoEmpleado = 2  = Profesor
// This is an array that matches objects (for Chamilo)
$matches = array(
   array(
        'orig_table' => 'Empleado',
/* Debería ser lo siguiente y devolver 1343 docentes:
SELECT DISTINCT e.vchcodigorrhh FROM empleado e INNER JOIN empleadotipo et ON et.uididtipoempleado = e.uididtipoempleado WHERE et.intidtipoempleado = 2 and e.vchcodigorrhh is not null
*/
        'query' => 'SELECT %s '.
                   'FROM Empleado as e '.
                   'INNER JOIN EmpleadoTipo et ON et.uidIdTipoEmpleado = e.uidIdTipoEmpleado '.
                   'INNER JOIN Persona as p ON e.uidIdPersona = p.uidIdPersona '.
                   ' WHERE et.intidTipoEmpleado = 2 and e.vchCodigoRRHH is not null '.
                   'ORDER BY vchPrimerNombre, vchSegundoNombre, vchPaterno, vchMaterno',
        'dest_func' => 'MigrationCustom::create_user',
        'dest_table' => 'user',
        'extra_fields' => array(
            'uidIdPersona' => array(                
                'field_display_text' => 'uidIdPersona',
                'field_variable' => 'uidIdPersona',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_TEXT,                
            ),
        ),
        'fields_match' => array(        
            array(
                'orig' => 'e.uidIdEmpleado',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'uidIdEmpleado',
                'func' => '',
            ),
            array(
                'orig' => 'e.uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'uidIdPersona',
                'func' => '',
            ),    
            array(
                'orig' => 'vchPaterno',
                'sql_alter' => '',
                'dest' => 'lastname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchMaterno',
                'sql_alter' => '',
                'dest' => 'lastname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchPrimerNombre',
                'sql_alter' => '',
                'dest' => 'firstname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchSegundoNombre',
                'sql_alter' => '',
                'dest' => 'firstname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchTelefonoPersona',
                'sql_alter' => '',
                'dest' => 'phone',
                'func' => null,
            ),
            array(
                'orig' => 'vchEmailPersona',
                'sql_alter' => '',
                'dest' => 'email',
                'func' => null,
            ),
            array(
                'orig' => 'chrPasswordT',
                'sql_alter' => '',
                'dest' => 'password',
                'func' => 'make_sha1',
            ),
            /* el codigorrhh tiene duplicados! */
            array(
                'orig' => 'e.vchCodigoRRHH',
                'sql_alter' => '',
                'dest' => 'username',
                'func' => null,
            ),
            array(
                'orig' => 'e.uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_uidIdPersona',
                'func' => 'none',
            ),
            array(
                'orig' => 'e.bitVigencia',
                'sql_alter' => '',
                'dest' => 'active',
                'func' => null,
            ),        
        ),
    ),    
    // Profesores especiales: profesores que eran profesores pero ahora son administradores (empleados) de ICPNA
    // Ya no tienen registros en la base de datos de profesores, pero sí existen
   array(
        'orig_table' => 'Empleado',
        'query' => 'SELECT %s '.
                   ' FROM Programaacademico pa '.
                   ' INNER JOIN Sede s on s.uidIdSede = pa.uidIdSede AND (s.intIdSede = '.$branch.') '.
                   ' INNER JOIN Empleado e on e.uidIdEmpleado = pa.uidIdProfesor '.
                   ' INNER JOIN Persona p on p.uidIdPersona = e.uidIdPersona '.
                   ' INNER JOIN EmpleadoTipo et ON et.uidIdTipoEmpleado = e.uidIdTipoEmpleado '.
                   ' INNER JOIN Aula au ON au.uidIdAula = pa.uidIdAula '.
                   ' INNER JOIN Curso c on c.uidIdCurso = pa.uidIdCurso '.
                   " WHERE et.vchDescripcionTipo <> 'Profesor' ",
        'dest_func' => 'MigrationCustom::create_user',
        'dest_table' => 'user',
        'extra_fields' => array(
            'uidIdPersona' => array(                
                'field_display_text' => 'uidIdPersona',
                'field_variable' => 'uidIdPersona',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_TEXT,                
            ),
        ),
        'fields_match' => array(
            array(
                'orig' => 'e.uidIdEmpleado',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'uidIdEmpleado',
                'func' => '',
            ),
            array(
                'orig' => 'e.uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'uidIdPersona',
                'func' => '',
            ),
            array(
                'orig' => 'vchPaterno',
                'sql_alter' => '',
                'dest' => 'lastname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchMaterno',
                'sql_alter' => '',
                'dest' => 'lastname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchPrimerNombre',
                'sql_alter' => '',
                'dest' => 'firstname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchSegundoNombre',
                'sql_alter' => '',
                'dest' => 'firstname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchTelefonoPersona',
                'sql_alter' => '',
                'dest' => 'phone',
                'func' => null,
            ),
            array(
                'orig' => 'vchEmailPersona',
                'sql_alter' => '',
                'dest' => 'email',
                'func' => null,
            ),            
            array(
                'orig' => 'chrPasswordT',
                'sql_alter' => '',
                'dest' => 'password',
                'func' => 'make_sha1',
            ),
            /* el codigorrhh tiene duplicados! */
            array(
                'orig' => 'e.vchCodigoRRHH',
                'sql_alter' => '',
                'dest' => 'username',
                'func' => null,
            ),
            array(
                'orig' => 'e.uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_uidIdPersona',
                'func' => 'none',
            ),          
            array(
                'orig' => 'e.bitVigencia',
                'sql_alter' => '',
                'dest' => 'active',
                'func' => null,
            ),        
        ),
    ),    
    // Alumnos
    array(
        /* Deberian ser 578447 alumnos */
        'orig_table' => 'Alumno',    
        'query' => 'SELECT %s '.
                   'FROM Alumno as a '.
                   'INNER JOIN Persona as p ON (a.uidIdPersona = p.uidIdPersona) '.
                   'ORDER BY vchPrimerNombre, vchSegundoNombre, vchPaterno, vchMaterno',
        'dest_func' => 'MigrationCustom::create_user',
        'dest_table' => 'user',
        'extra_fields' => array(
            'uidIdPersona' => array(                
                'field_display_text' => 'uidIdPersona',
                'field_variable' => 'uidIdPersona',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_TEXT,                
            ),
        ),
        'fields_match' => array(            
            array(
                'orig' => 'a.uidIdAlumno',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'uidIdAlumno',
                'func' => '',
            ),
            array(
                'orig' => 'a.uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'uidIdPersona',
                'func' => '',
            ),    
            array(
                'orig' => 'vchPaterno',
                'sql_alter' => '',
                'dest' => 'lastname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchMaterno',
                'sql_alter' => '',
                'dest' => 'lastname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchPrimerNombre',
                'sql_alter' => '',
                'dest' => 'firstname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchSegundoNombre',
                'sql_alter' => '',
                'dest' => 'firstname',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'vchTelefonoPersona',
                'sql_alter' => '',
                'dest' => 'phone',
                'func' => null,
            ),
            array(
                'orig' => 'vchEmailPersona',
                'sql_alter' => '',
                'dest' => 'email',
                'func' => null,
            ),
            array(
                'orig' => 'chrPasswordT',
                'sql_alter' => '',
                'dest' => 'password',
                'func' => 'make_sha1',
            ),
            array(
                'orig' => 'a.uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_uidIdPersona',
                'func' => 'none',
            ),
            array(
                'orig' => 'vchCodal',
                'sql_alter' => 'none',
                'dest' => 'username',
                'func' => 'none',
            ),
            array(
                'orig' => 'a.bitVigencia',
                'sql_alter' => '',
                'dest' => 'active',
                'func' => null,
            ),        
        )
    ),   
    array(
        'orig_table' => 'Curso',
        /* Deberian haber 1119 desde el servidor de prueba */
        'query' => 'SELECT %s '.
                   'FROM Curso '.
                   'ORDER BY chrCursoCodigo',
        'dest_table' => 'course',
        'show_in_error_log' => false,
        'dest_func' => 'MigrationCustom::create_course',
        'extra_fields' => array(
            'fase' => array(   
                'field_display_text' => 'Fase',
                'field_variable' => 'fase',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'options' => array(
                    'orig_table' => 'Fase',
                    'fields_match' => array(
                        array(
                            'orig' => 'vchNombreFase',
                            'sql_alter' => null,
                            'dest' => 'option_display_text',
                            //'func' => 'add_meses_label_to_extra_field_fase',
                            'func' => 'none',
                        ),
                        array(
                            'orig' => 'uidIdFase',
                            'sql_alter' => 'sql_alter_unhash_50',
                            'dest' => 'option_value',
                            'func' => 'none',
                        ),
//                        array(
//                            'orig' => 'chrOrdenFase',
//                            'sql_alter' => 'sql_alter_unhash_50',
//                            'dest' => 'option_value',
//                            'func' => 'none',
//                        ),
                    ),
                ),
            ),
/*
            'meses' => array(               
                'field_display_text' => 'Meses',
                'field_variable' => 'meses',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'options' => array(
                    'orig_table' => 'Fase',
                    'fields_match' => array(
                        array(
                            'orig' => 'ChrOrdenFase',
                            'sql_alter' => null,
                            'dest' => 'option_display_text',
                            'func' => 'clean_utf8',
                        ),
                        array(
                            'orig' => 'uidIdFase',
                            'sql_alter' => 'sql_alter_unhash_50',
                            'dest' => 'option_value',
                            'func' => 'none',
                        ),
                    )
                ),
            ),
*/
            'frecuencia' => array(               
                'field_display_text' => 'Frecuencia',
                'field_variable' => 'frecuencia',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'options' => array(
                    'orig_table' => 'Frecuencia',
                    'fields_match' => array(
                        array(
                            'orig' => 'vchFrecuenciaNombre',
                            'sql_alter' => null,
                            'dest' => 'option_display_text',
                            'func' => 'clean_utf8',
                        ),
                        array(
                            'orig' => 'uidIdFrecuencia',
                            'sql_alter' => 'sql_alter_unhash_50',
                            'dest' => 'option_value',
                            'func' => 'none',
                        ),
                    )
                ),
            ),
            'intensidad' => array(
                'field_display_text' => 'Intensidad',
                'field_variable' => 'intensidad',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'options' => array(
                    'orig_table' => 'Intensidad',
                    'fields_match' => array(
                        array(
                            'orig' => 'vchIntensidadNombre',
                            'sql_alter' => null,
                            'dest' => 'option_display_text',
                            'func' => 'clean_utf8',
                        ),
                        array(
                            'orig' => 'uidIdIntensidad',
                            'sql_alter' => 'sql_alter_unhash_50',
                            'dest' => 'option_value',
                            'func' => 'none',
                        ),                   
                    )
               ),
            ),
            'uidIdCurso' => array(
                'field_display_text' => 'uidIdCurso',
                'field_variable' => 'uidIdCurso',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_TEXT
            ),
        ),
        'fields_match' => array(
            array(
                'orig' => 'uidIdCurso',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'uidIdCurso',
                'func' => '',
            ),
            array(
                'orig' => 'vchNombreCurso',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'title',
                'func' => 'clean_utf8',
            ),
            array(
                'orig' => 'chrCursoCodigo',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'wanted_code',
                'func' => 'none',
            ),
            //Extra fields
            array(
                'orig' => 'uidIdFrecuencia',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_frecuencia',
                'func' => 'none',
            ),
            array(
                'orig' => 'uidIdIntensidad',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_intensidad',
                'func' => 'none',
            ),
            array(
                'orig' => 'uidIdFase',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_fase',
                'func' => 'none',
            ),
/*            array(
                'orig' => 'uidIdFase',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_meses',
                'func' => 'none',
            ),
*/
            array(
                'orig' => 'uidIdCurso',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_uidIdCurso',
                'func' => null,
            ),
        ),
    ),
    //Sessions
    /* Debería ser 63759 con la última data del servidor de prueba */
    array(
        'orig_table' => 'ProgramaAcademico',
        'query' => 'SELECT %s, SUBSTRING(s.vchNombreSede,14,12) + \' - \' + chrPeriodo + \' - \' +vchNombreCurso + \' (\' + chrIdHorario + \') \' + chrHoraInicial + \' \' + chrHoraFinal +  \' \' + a.vchIdentificadorFisico as session_name '.
                   ' FROM ProgramaAcademico p '.
                   ' INNER JOIN Curso c ON p.uidIdCurso = c.uidIdCurso '.
                   ' INNER JOIN Horario h ON h.uidIdHorario = p.uidIdHorario '.
                   ' LEFT JOIN Aula a ON a.uidIdAula = p.uidIdAula '.
                   ' INNER JOIN Sede s ON (s.uidIdSede = p.uidIdSede AND s.intIdSede = '.$branch.') '.
                   ' LEFT JOIN Empleado e ON (e.uidIdEmpleado = p.uidIdProfesor) '.
                   '',
        'dest_table' => 'session',
        'dest_func' => 'MigrationCustom::create_session',
        'order' => 'ORDER BY chrPeriodo',
        'extra_fields' => array(            
            'uidIdPrograma' => array(                
                'field_display_text' => 'uidIdPrograma',
                'field_variable' => 'uidIdPrograma',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_TEXT,                
            ),
            'estado' => array(                
                'field_display_text' => 'Estado',
                'field_variable' => 'estado',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
            ),
            'sede' => array( 
                'field_display_text' => 'Sede',
                'field_variable' => 'sede',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'options' => array(
                    'orig_table' => 'Sede',
                    'query' => 'SELECT %s '.
                               'FROM Sede '.
                               'WHERE s.intIdSede = '.$branch.' ',
                    'fields_match' => array(
                        array(
                            'orig' => 'vchNombreSede',
                            'sql_alter' => null,
                            'dest' => 'option_display_text',
                            'func' => 'clean_utf8',
                        ),
                        array(
                            'orig' => 'uidIdSede',
                            'sql_alter' => 'sql_alter_unhash_50',
                            'dest' => 'option_value',
                            'func' => null,
                        ),
                    ),
                ),
            ),
            'horario' => array(
                'field_display_text' => 'Horario',
                'field_variable' => 'horario',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'options' => array(
                    'orig_table' => 'Horario',
                    'query' => 'SELECT %s  
                                FROM Horario '.
                                ' ORDER BY chrIdHorario, chrHoraInicial, chrHoraFinal',
                    'fields_match' => array(
                        array(
                            'orig' => 'chrHoraInicial',
                            'sql_alter' => null,
                            'dest' => 'option_display_text',
                            'func' => 'join_horario',
                        ),
                        array(
                            'orig' => 'chrHoraFinal',
                            'sql_alter' => null,
                            'dest' => 'chrHoraFinal',
                            'func' => '',
                        ),
                        array(
                            'orig' => 'uidIdHorario',
                            'sql_alter' => 'sql_alter_unhash_50',
                            'dest' => 'option_value',
                            'func' => null,
                        ),                  
                        array(
                            'orig' => 'chrIdHorario',
                            'sql_alter' => null,
                            'dest' => 'chrIdHorario',
                            'func' => null,
                        ),                        
                    )
                ),
            ),            
            'periodo' => array(         
                'field_display_text' => 'Periodo',
                'field_variable' => 'periodo',
                'field_visible' => '1',
                'field_type' => ExtraField::FIELD_TYPE_TEXT,                
            ),
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
                            'sql_alter' => 'sql_alter_unhash_50',
                            'dest' => 'option_value',
                            'func' => null,
                        ),     
                    ),
                ),
            ),
        ),
        'fields_match' => array(         
            array(
                'orig' => 'chrPeriodo',
                'sql_alter' => '',
                'dest' => 'name',
                'func' => 'clean_session_name',
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
            array(
                'orig' => 'sdtFechaInicioClases',
                'sql_alter' => '',
                'dest' => 'coach_access_start_date',
                'func' => 'none',
            ),
            array(
                'orig' => 'sdtFechaFinClases',
                'sql_alter' => '',
                'dest' => 'coach_access_end_date',
                'func' => 'none',
            ),
            //getting uididprograma preventively to check session pre-existence
            array(
                'orig' => 'uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'uididprograma',
                'func' => 'none',
            ),
            //Getting info from chamilo
            array(
                'orig' => 'p.uidIdCurso',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'course_code',
                'func' => 'get_real_course_code',
            ),
            array(
                'orig' => 'uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'id_coach',
                'func' => 'get_real_teacher_id',
            ),
            //Extra fields
            array(
                'orig' => 'uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_uidIdPrograma',
                'func' => null,
            ),
            array (
                'orig' => 'p.uidIdHorario',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_horario',
                'func' => null,
            ),
            array (
                'orig' => 'p.uidIdSede',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_sede',
                'func' => null,
            ),            
            array (
                'orig' => 'p.tinEstado',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_estado',
                'func' => null,
            ),
            array (
                'orig' => 'chrPeriodo',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_periodo',
                'func' => null,
            ),
            array (
                'orig' => 'p.uidIdAula',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'extra_field_aula',
                'func' => null,
            ),           
        ),
    ),
    array(
        'orig_table' => 'Matricula',
        /* Updated to remove bitVigencia(s) and add Sede=2 */
        'query' => "SELECT %s " .
                   " FROM Matricula m " .
                   " INNER JOIN ProgramaAcademico p ON p.uidIdPrograma = m.uidIdPrograma " .
                   " INNER JOIN Alumno a ON a.uidIdAlumno = m.uidIdAlumno " .
                   " INNER JOIN Persona pe ON pe.uidIdPersona = a.uidIdPersona " .
                   " INNER JOIN sede s ON s.uidIdSede = p.uidIdSede " .
                   "       AND s.intIdSede = $branch " .
                   " WHERE m.tinEstado = 1 ",        
        'dest_table' => 'add_user_to_session',
        'dest_func' => 'MigrationCustom::add_user_to_session',
        'fields_match' => array(
            array(
                'orig' => 'm.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'uidIdPrograma',
                'func' => '',
            ),
            array(
                'orig' => 'a.uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'uidIdPersona',
                'func' => '',
            )           
        ),
    ),
    //Asistencia - put in comment for now
/*
    array(
        'orig_table' => 'ProgramaAcademico',
        'query' => 'SELECT %s
                    FROM ProgramaAcademico as p 
                    INNER JOIN le_AlumnoAsistencia a ON (a.codigoPrograma = p.uidIdPrograma AND p.bitVigencia = 1 )
                    INNER JOIN Alumno al ON (al.uidIdAlumno = a.codigoAlumno AND al.bitVigencia = 1)
                    WHERE 1=1 ',
        'dest_func' => 'MigrationCustom::create_attendance',
        'dest_table' => 'session',
        'fields_match' => array(        
            array(
                'orig' => 'al.uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'user_id',
                'func' => 'get_user_id_by_persona_id',
            ),
            array(
                'orig' => 'p.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'session_id',
                'func' => 'get_session_id_by_programa_id',
            ),
            array(
                'orig' => 'a.fecha',
                'sql_alter' => 'clean_date_time',
                'dest' => 'fecha',
                'func' => '',
            ),
        )
    ),
*/
    //Course advance
/*
    array(
        'orig_table' => 'ProgramaAcademico',
        //ORDER BY p.uidIdPrograma, a.Unidad, un.Descripcion
        'query' => 'SELECT DISTINCT %s
                    FROM ProgramaAcademico as p 
                    INNER JOIN le_AvanceCursoPrograma a ON (a.Programa = p.uidIdPrograma AND p.bitVigencia = 1)
                    INNER JOIN le_Unidad un ON (un.CodigoUnidad = a.Unidad)                    
                    ',
        'dest_func' => 'MigrationCustom::create_thematic',
        'dest_table' => 'session',
        'fields_match' => array(         
            array(
                'orig' => 'p.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'session_id',
                'func' => 'get_session_id_by_programa_id',
            ),
            array(
                'orig' => 'un.Descripcion',
                'sql_alter' => '',
                'dest' => 'thematic_plan',
                'func' => '',
            ),
            array(
                'orig' => 'a.Unidad',
                'sql_alter' => '',
                'dest' => 'thematic',
                'func' => '',
            ),
        )
    ),    
*/

    //Gradebook - create gradebook evaluations types
/*
    array(
        'orig_table' => 'gradebook_evaluation_type',
        'query' => 'SELECT %s '.
                   ' FROM le_ConceptoCalificatorioTipo '.
                   ' WHERE 1 = 1 ',
        'dest_func' => 'MigrationCustom::add_evaluation_type',
        'dest_table' => 'session',
        'fields_match' => array(
            array(
                'orig' => 'Descripcion',
                'sql_alter' => '',
                'dest' => 'name',
                'func' => '',
            ),
            array(
                'orig' => 'CodigoTipo',
                'sql_alter' => '',
                'dest' => 'external_id',
                'func' => '',
            )            
        )
    ),    
*/
    //Gradebook - create gradebook evaluations
    /*
    array(
        'orig_table' => 'ProgramaAcademico',
        'query' => 'SELECT DISTINCT %s
                    FROM ProgramaAcademico as p 
                    INNER JOIN le_Nota n ON (n.codigoPrograma = p.uidIdPrograma AND p.bitVigencia = 1 )
                    INNER JOIN le_ConceptoCalificatorio cc ON (cc.CodigoConcepto = n.CodigoConcepto)           
                    WHERE 1 = 1 ',
        'dest_func' => 'MigrationCustom::create_gradebook_evaluation',
        'dest_table' => 'session',
        'fields_match' => array(         
            array(
                'orig' => 'p.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'session_id',
                'func' => 'get_session_id_by_programa_id',
            ),
            array(
                'orig' => 'cc.CodigoTipo',
                'sql_alter' => '',
                'dest' => 'gradebook_evaluation_type_id',
                'func' => 'get_evaluation_type',
            ),
            array(
                'orig' => 'cc.Descripcion',
                'sql_alter' => '',
                'dest' => 'gradebook_description',
                'func' => '',
            ),
        )
    ),
    */
    //Nota <= 2009
/*
    array(
        'orig_table' => 'ProgramaAcademico',
        'query' => 'SELECT DISTINCT %s '.
                   'FROM ProgramaAcademico as p '.
                   'INNER JOIN Nota n ON (n.uidIdPrograma = p.uidIdPrograma AND p.bitVigencia = 1 ) '.
                   'INNER JOIN Alumno a ON (a.uidIdAlumno = n.uidIdAlumno) '.
                   '',
        'dest_func' => 'MigrationCustom::add_gradebook_result_with_evaluation',
        'dest_table' => 'session',
        'fields_match' => array(         
            array(
                'orig' => 'p.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'session_id',
                'func' => 'get_session_id_by_programa_id',
            ),
            array(
                'orig' => 'uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'user_id',
                'func' => 'get_user_id_by_persona_id',
            ),
            array(
                'orig' => 'tinNota',
                'sql_alter' => '',
                'dest' => 'nota',
                'func' => '',
            ),            
            array(
                'orig' => 'n.sdtFechaModificacion',
                'sql_alter' => '',
                'dest' => 'fecha',
                'func' => '',
            ),
        )
    ),
*/
    'web_service_calls' =>  array(       
       'url' => "http://***/***/***?wsdl",
       'filename' => 'migration.custom.class.php',
       'class' => 'MigrationCustom',       
    ),
    //le_Nota
    //>= 2010',
    /*
    array(
        'orig_table' => 'ProgramaAcademico',
        'query' => 'SELECT DISTINCT %s
                    FROM ProgramaAcademico as p 
                    INNER JOIN le_Nota n ON (n.codigoPrograma = p.uidIdPrograma AND p.bitVigencia = 1 )
                    INNER JOIN Alumno a ON (n.CodigoAlumno = a.uidIdAlumno)
                    WHERE YEAR(UltimaFechaModif) >= 2010',
        'dest_func' => 'MigrationCustom::add_gradebook_result',
        'dest_table' => 'session',
        'fields_match' => array(         
            array(
                'orig' => 'p.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'session_id',
                'func' => 'get_session_id_by_programa_id',
            ),
            array(
                'orig' => 'uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'user_id',
                'func' => 'get_user_id_by_persona_id',
            ),
            array(
                'orig' => 'Nota',
                'sql_alter' => '',
                'dest' => 'nota',
                'func' => '',
            ),            
            array(
                'orig' => 'UltimaFechaModif',
                'sql_alter' => '',
                'dest' => 'fecha',
                'func' => 'clean_date_time',
            ),
        )
    ),  

     */
    /*
    'transactions' => array(
        //Usuarios + profesores + administradores
            //añadir usuario: usuario_agregar UID
            'usuario_agregar' => array(),        
            //eliminar usuario usuario_eliminar UID
            'usuario_eliminar' => array(),        
            //editar detalles de usuario (nombre/correo/contraseña) usuario_editar UID
            'usuario_editar' => array(),
            //cambiar usuario de progr. académ. (de A a B, de A a nada, de nada a A) (como estudiante o profesor) usuario_matricula UID ORIG DEST
            'usuario_matricula' => array(),
        //Cursos
            //añadir curso curso_agregar CID
            'curso_agregar' => array(),
            //eliminar curso curso_eliminar CID
            'curso_eliminar' => array(),
            //editar detalles de curso curso_editar CID
            'curso_editar' => array(),
            //cambiar curso de progr. académ. (de nada a A) curso_matricula CID ORIG DEST
            'curso_matricula' => array(),
            //cambiar intensidad pa_cambiar_fase_intensidad CID ORIG DEST (id de "intensidadFase")
            'pa_cambiar_fase_intensidad' => array(),
        //Programas académicos
            //añadir p.a. pa_agregar PID
            //eliminar p.a. pa_eliminar PID
            //editar detalles de p.a. pa_editar PID
            //cambiar aula pa_cambiar_aula PID ORIG DEST
            //cambiar horario pa_cambiar_horario PID ORIG DEST
            //cambiar sede pa_cambiar_sede PID ORIG DEST

//        Horario
//            añadir horario_agregar HID
            'horario_agregar' => array(),
//            eliminar horario_eliminar HID
            'horario_eliminar' => array(),
//            editar horario_editar HID
            'horario_editar' => array(),
//        Aula
//            añadir aula_agregar AID
            'aula_agregar' => array(),
//            eliminar aula_eliminar AID
            'aula_eliminar' => array(),
//            editar aula_editor AID
            'aula_editor' => array(),
//        Sede
//            añadir aula_agregar SID
            'aula_agregar' => array(),
//            eliminar aula_eliminar SID
            'aula_eliminar' => array(),
//            editar aula_editar SID
            'aula_editar' => array(),
//
//        Frecuencia
//            añadir aula_agregar FID
            'aula_agregar' => array(),
//            eliminar aula_eliminar FID
            'aula_eliminar' => array(),
//            editar aula_editar FID
            'aula_editar' => array(),
//
//        Intensidad/Fase
//            añadir intfase_agregar IID
            'intfase_agregar' => array(),
//            eliminar intfase_eliminar IID
            'intfase_eliminar' => array(),
//            editar intfase_editar IID
            'intfase_editar' => array()     
    )*/
);

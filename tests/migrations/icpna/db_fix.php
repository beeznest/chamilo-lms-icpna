<?php
/**
 * This script defines as closely as possible the matches between
 * original tables and destination (chamilo) tables and fields.
 * See db_matches.dist.php for formatting documentation
 */
$e = ExtraField::FIELD_TYPE_TEXT;
//intIdTipoEmpleado = 2  = Profesor
// This is an array that matches objects (for Chamilo)
$matches = array(
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
);

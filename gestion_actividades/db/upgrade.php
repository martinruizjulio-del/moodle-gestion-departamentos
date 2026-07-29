<?php
defined('MOODLE_INTERNAL') || die();


function local_gestion_actividades_add_index_if_possible($dbman, string $tablename, string $indexname, array $fields): void {
    $table = new xmldb_table($tablename);
    if (!$dbman->table_exists($table)) {
        return;
    }

    foreach ($fields as $fieldname) {
        $field = new xmldb_field($fieldname);
        if (!$dbman->field_exists($table, $field)) {
            return;
        }
    }

    $index = new xmldb_index($indexname, XMLDB_INDEX_NOTUNIQUE, $fields);
    if ($dbman->index_exists($table, $index)) {
        return;
    }

    try {
        $dbman->add_index($table, $index);
    } catch (Throwable $e) {
        // Defensivo: una instalación puede tener ya un índice equivalente con otro nombre.
        if (function_exists('debugging')) {
            debugging('No se ha podido crear el índice ' . $indexname . ' en ' . $tablename . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}

function xmldb_local_gestion_actividades_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026071013) {
        $table = new xmldb_table('local_ga_workshops');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('code', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('allowrepeat', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('coursecode', XMLDB_INDEX_UNIQUE, ['courseid', 'code']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_ga_workshop_editions');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('workshopid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('activityid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('editioncode', XMLDB_TYPE_CHAR, '80', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sessiondate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('enrolenddate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('places', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('attendancecmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('certificatecmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'open');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('workshopid', XMLDB_INDEX_NOTUNIQUE, ['workshopid']);
            $table->add_index('activityid', XMLDB_INDEX_NOTUNIQUE, ['activityid']);
            $table->add_index('groupid', XMLDB_INDEX_NOTUNIQUE, ['groupid']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_ga_edition_teachers');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('editionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('editionuserid', XMLDB_INDEX_UNIQUE, ['editionid', 'userid']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_ga_edition_enrolments');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('editionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('workshopid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'enrolled');
            $table->add_field('reason', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('editionuser', XMLDB_INDEX_UNIQUE, ['editionid', 'userid']);
            $table->add_index('workshopuser', XMLDB_INDEX_NOTUNIQUE, ['workshopid', 'userid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026071013, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071019) {
        $table = new xmldb_table('local_ga_workshop_editions');

        $field = new xmldb_field('requiredcmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'certificatecmid');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('requiredmodname', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'requiredcmid');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026071019, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071021) {
        $table = new xmldb_table('local_ga_workshop_editions');

        $fields = [
            new xmldb_field('activitycreationtype', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'requiredmodname'),
            new xmldb_field('tasknumericgrade', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'activitycreationtype'),
            new xmldb_field('quizgradingmode', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'tasknumericgrade'),
            new xmldb_field('archived', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'quizgradingmode'),
            new xmldb_field('timearchived', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'archived'),
        ];

        if ($dbman->table_exists($table)) {
            foreach ($fields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026071021, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071022) {
        $table = new xmldb_table('local_ga_workshops');

        $field = new xmldb_field('hours', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'allowrepeat');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026071022, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071023) {
        $table = new xmldb_table('local_ga_hour_history');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('workshopid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('editionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('workshopcode', XMLDB_TYPE_CHAR, '40', null, null, null, null);
            $table->add_field('workshopname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('editionname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('hours', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('certificatecmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('certificatestatus', XMLDB_TYPE_CHAR, '40', null, null, null, 'pending');
            $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('useridx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $table->add_index('editionuseridx', XMLDB_INDEX_UNIQUE, ['editionid', 'userid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026071023, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071024) {
        $table = new xmldb_table('local_ga_workshops');

        $field = new xmldb_field('sectionnum', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'hours');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026071024, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071027) {
        // No database structural change. Robust save and non-blocking section creation.
        upgrade_plugin_savepoint(true, 2026071027, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071028) {
        // Defensive save layer: write only existing DB fields and expose clearer errors.
        upgrade_plugin_savepoint(true, 2026071028, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071029) {
        // Safe save mode: minimal writes only; automatic side effects disabled.
        upgrade_plugin_savepoint(true, 2026071029, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071030) {
        // Course visual structure: workshop entries under TALLERES TIPO A.
        upgrade_plugin_savepoint(true, 2026071030, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071031) {
        // Add workshop deletion flow from workshop list.
        upgrade_plugin_savepoint(true, 2026071031, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071032) {
        // Course section summary display for workshop list.
        upgrade_plugin_savepoint(true, 2026071032, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071033) {
        // Visible Page resources for workshop entries in TALLERES TIPO A.
        upgrade_plugin_savepoint(true, 2026071033, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071034) {
        // Single visible course section: TALLERES TIPO A with visible workshop URL entries.
        upgrade_plugin_savepoint(true, 2026071034, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071035) {
        // Disable automatic course resource creation to avoid Moodle course API errors.
        upgrade_plugin_savepoint(true, 2026071035, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071036) {
        // Create visible URL resources for workshops using low-level Moodle course module writes.
        upgrade_plugin_savepoint(true, 2026071036, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071037) {
        // Generate visible labels in TALLERES TIPO A with defensive error handling.
        upgrade_plugin_savepoint(true, 2026071037, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071038) {
        // Improved course generation using standard course module helper functions and diagnostics.
        upgrade_plugin_savepoint(true, 2026071038, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071039) {
        // Restore missing helper methods for course section names.
        upgrade_plugin_savepoint(true, 2026071039, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071040) {
        // Workshop landing page and student self-enrolment flow.
        upgrade_plugin_savepoint(true, 2026071040, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071041) {
        // Public workshop entry: update existing labels to point to workshop_view.php instead of internal edition pages.
        upgrade_plugin_savepoint(true, 2026071041, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071042) {
        // Public workshop view cleanup and course-front enrol/status endpoint.
        upgrade_plugin_savepoint(true, 2026071042, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071043) {
        $table = new xmldb_table('local_ga_authorized');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('addedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('userid_uix', XMLDB_INDEX_UNIQUE, ['userid']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_ga_materials');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('workshopid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('editionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('url', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('workshopid_idx', XMLDB_INDEX_NOTUNIQUE, ['workshopid']);
            $table->add_index('editionid_idx', XMLDB_INDEX_NOTUNIQUE, ['editionid']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_ga_workshop_editions');
        $fields = [
            new xmldb_field('completed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0'),
            new xmldb_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
            new xmldb_field('completedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
        ];
        foreach ($fields as $field) {
            if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2026071043, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071044) {
        // UI fixes: authorized user selector button, student-safe teacher view, enrolled workshop list.
        upgrade_plugin_savepoint(true, 2026071044, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071045) {
        // Restore teacher/admin access button in public workshop view while keeping it hidden from students.
        upgrade_plugin_savepoint(true, 2026071045, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071046) {
        // Course generated entries cleanup tool.
        upgrade_plugin_savepoint(true, 2026071046, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071047) {
        $table = new xmldb_table('local_ga_materials');
        $field = new xmldb_field('fileitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026071047, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071048) {
        // Restore can_manage_workshop helper in simulation branch.
        upgrade_plugin_savepoint(true, 2026071048, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071049) {
        // Safe enrolled/attendance list view.
        upgrade_plugin_savepoint(true, 2026071049, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071050) {
        // Remove unavailable draft file dependency and harden attendance list.
        upgrade_plugin_savepoint(true, 2026071050, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071051) {
        // Safe embedded attendance list and clearer task/quiz configured state.
        upgrade_plugin_savepoint(true, 2026071051, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071052) {
        $table = new xmldb_table('local_ga_edition_enrolments');
        if ($dbman->table_exists($table)) {
            $fields = [
                new xmldb_field('attended', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0'),
                new xmldb_field('timeattended', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
                new xmldb_field('attendedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
            ];
            foreach ($fields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026071052, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071053) {
        $table = new xmldb_table('local_ga_edition_enrolments');
        if ($dbman->table_exists($table)) {
            $fields = [
                new xmldb_field('attended', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0'),
                new xmldb_field('timeattended', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
                new xmldb_field('attendedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
            ];
            foreach ($fields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026071053, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071054) {
        // Attendance fallback using status field and task creation signature compatibility.
        upgrade_plugin_savepoint(true, 2026071054, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071055) {
        // Default required activity to assignment when previous configuration is ambiguous.
        upgrade_plugin_savepoint(true, 2026071055, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071056) {
        // Safer task/quiz creation: use Moodle native modedit form instead of direct DB/module creation.
        upgrade_plugin_savepoint(true, 2026071056, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071057) {
        // Link already-created task/quiz from workshop view; safer native creation flow.
        upgrade_plugin_savepoint(true, 2026071057, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071058) {
        // Auto-link existing required activity and hide it from course front page.
        upgrade_plugin_savepoint(true, 2026071058, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071059) {
        // Cleanup now hides linked/candidate Moodle activities from the course page.
        upgrade_plugin_savepoint(true, 2026071059, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071060) {
        // Required activity is restricted to the workshop edition group.
        upgrade_plugin_savepoint(true, 2026071060, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071061) {
        // Edition group is created automatically and required activity is bound to it.
        upgrade_plugin_savepoint(true, 2026071061, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071062) {
        // Fix all unqualified core_text references.
        upgrade_plugin_savepoint(true, 2026071062, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071063) {
        // Workshop back links, student attendance status, and archive-on-finish helpers.
        upgrade_plugin_savepoint(true, 2026071063, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071064) {
        // Better course-card aesthetics and real archive/hide of finished workshop cards.
        upgrade_plugin_savepoint(true, 2026071064, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071065) {
        // Hard archive: remove workshop/task cards from visible course section sequences.
        upgrade_plugin_savepoint(true, 2026071065, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071066) {
        // Remove workshop assignment/quiz activities from visible course sequence.
        upgrade_plugin_savepoint(true, 2026071066, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071067) {
        $table = new xmldb_table('local_ga_certificates');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('workshopid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('editionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('certcode', XMLDB_TYPE_CHAR, '80', null, XMLDB_NOTNULL, null, null);
            $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'generated');
            $table->add_field('timeissued', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('editionuser', XMLDB_INDEX_UNIQUE, ['editionid', 'userid']);
            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $dbman->create_table($table);
        }

        if (get_config('local_gestion_actividades', 'certificatetemplatehtml') === false) {
            set_config('certificatetemplatehtml',
                '<p>Se certifica que <strong>{alumno}</strong> ha participado y completado satisfactoriamente el taller <strong>{taller}</strong>, realizado el día <strong>{fecha}</strong>, con una duración de <strong>{horas}</strong> horas, dentro del programa de <strong>Talleres Tipo A</strong>.</p>',
                'local_gestion_actividades'
            );
        }

        upgrade_plugin_savepoint(true, 2026071067, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071068) {
        // Make certificate actions visible in workshop and teacher view.
        upgrade_plugin_savepoint(true, 2026071068, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071069) {
        // Fix certificate download: send real PDF, not preview/icon.
        upgrade_plugin_savepoint(true, 2026071069, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071084) {
        $table = new xmldb_table('local_ga_typeb_certs');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('activityname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('activitydate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('hours', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('authorizedconfirm', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'pending');
            $table->add_field('reviewcomment', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('reviewedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timereviewed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
            $table->add_index('activitydate', XMLDB_INDEX_NOTUNIQUE, ['activitydate']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026071084, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071086) {
        $table = new xmldb_table('local_ga_grade_log');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('activityid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('activitykey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('academicyear', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
            $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
            $table->add_field('importid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('activitykeyuser', XMLDB_INDEX_NOTUNIQUE, ['activitykey', 'userid']);
            $table->add_index('activityid', XMLDB_INDEX_NOTUNIQUE, ['activityid']);
            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $table->add_index('importid', XMLDB_INDEX_NOTUNIQUE, ['importid']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026071086, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071092) {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        // Remove old student shortcuts wrongly published as course activities. Student access
        // must live in block_gestion_hee, not inside the visible course section.
        $oldnames = [
            'Mi portafolio HEE',
            'Mi portafolio HEE: horas y certificados',
            'Mis certificados',
            'Mis horas',
            'Ver mis horas',
        ];

        $targets = [];
        foreach (['url', 'label', 'page'] as $modname) {
            if (!$DB->record_exists('modules', ['name' => $modname]) || !$dbman->table_exists(new xmldb_table($modname))) {
                continue;
            }
            foreach ($oldnames as $oldname) {
                $alias = 'x';
                $sql = "SELECT cm.id AS cmid
                          FROM {course_modules} cm
                          JOIN {modules} m ON m.id = cm.module
                          JOIN {{$modname}} $alias ON $alias.id = cm.instance
                         WHERE m.name = :modname
                           AND " . $DB->sql_like("$alias.name", ':oldname', false);
                $records = $DB->get_records_sql($sql, [
                    'modname' => $modname,
                    'oldname' => $DB->sql_like_escape($oldname) . '%',
                ]);
                foreach ($records as $record) {
                    $targets[(int)$record->cmid] = true;
                }
            }
        }

        if ($DB->record_exists('modules', ['name' => 'url']) && $dbman->table_exists(new xmldb_table('url'))) {
            $pathfragments = [
                '/local/gestion_actividades/portfolio.php',
                '/local/gestion_actividades/mycertificates.php',
                '/local/gestion_actividades/myhours.php',
                '/local/gestion_actividades/typeb_upload.php',
            ];
            foreach ($pathfragments as $fragment) {
                $sql = "SELECT cm.id AS cmid
                          FROM {course_modules} cm
                          JOIN {modules} m ON m.id = cm.module
                          JOIN {url} u ON u.id = cm.instance
                         WHERE m.name = 'url'
                           AND " . $DB->sql_like('u.externalurl', ':fragment', false);
                $records = $DB->get_records_sql($sql, ['fragment' => '%' . $DB->sql_like_escape($fragment) . '%']);
                foreach ($records as $record) {
                    $targets[(int)$record->cmid] = true;
                }
            }
        }

        foreach (array_keys($targets) as $cmid) {
            try {
                course_delete_module((int)$cmid);
            } catch (Throwable $e) {
                // Continue. A missing/half-deleted module must not block plugin upgrade.
            }
        }

        // If the block plugin is installed, add it automatically to every course that has
        // Gestión HEE workshops, unless it already exists there.
        if ($DB->record_exists('block', ['name' => 'gestion_hee']) && $dbman->table_exists(new xmldb_table('local_ga_workshops'))) {
            $courseids = $DB->get_records_sql("SELECT DISTINCT courseid AS id FROM {local_ga_workshops} WHERE courseid > 1");
            foreach ($courseids as $courseidrow) {
                $courseid = (int)$courseidrow->id;
                $context = context_course::instance($courseid, IGNORE_MISSING);
                if (!$context) {
                    continue;
                }
                $exists = $DB->record_exists_select('block_instances',
                    'blockname = :blockname AND parentcontextid = :parentcontextid',
                    ['blockname' => 'gestion_hee', 'parentcontextid' => $context->id]
                );
                if ($exists) {
                    continue;
                }
                $block = (object)[
                    'blockname' => 'gestion_hee',
                    'parentcontextid' => $context->id,
                    'showinsubcontexts' => 0,
                    'pagetypepattern' => 'course-view-*',
                    'subpagepattern' => null,
                    'defaultregion' => 'side-pre',
                    'defaultweight' => 0,
                    'configdata' => '',
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
                $columns = $DB->get_columns('block_instances');
                foreach (array_keys((array)$block) as $field) {
                    if (!isset($columns[$field])) {
                        unset($block->$field);
                    }
                }
                $DB->insert_record('block_instances', $block);
                rebuild_course_cache($courseid, true);
            }
        }

        upgrade_plugin_savepoint(true, 2026071092, 'local', 'gestion_actividades');
    }



    if ($oldversion < 2026071113) {
        $table = new xmldb_table('local_ga_workshop_editions');

        $field = new xmldb_field('requiredassigncmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'requiredcmid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('requiredquizcmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'requiredassigncmid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026071113, 'local', 'gestion_actividades');
    }



    if ($oldversion < 2026071115) {
        $table = new xmldb_table('local_ga_workshop_editions');

        $fields = [
            new xmldb_field('taskdescription', XMLDB_TYPE_TEXT, null, null, null, null, null, 'tasknumericgrade'),
            new xmldb_field('taskurl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'taskdescription'),
            new xmldb_field('taskfileitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'taskurl'),
            new xmldb_field('taskduedate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'taskfileitemid'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $table = new xmldb_table('local_ga_task_submissions');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('editionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('fileitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null);
            $table->add_field('gradedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timegraded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'submitted');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('edition_user', XMLDB_INDEX_UNIQUE, ['editionid', 'userid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026071115, 'local', 'gestion_actividades');
    }



    if ($oldversion < 2026071123) {
        $table = new xmldb_table('local_ga_typeb_certs');
        $field = new xmldb_field('activitydescription', XMLDB_TYPE_TEXT, null, null, null, null, null, 'hours');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026071123, 'local', 'gestion_actividades');
    }



    if ($oldversion < 2026071125) {
        // Índices usados por block_gestion_hee para cálculos cacheados de horas.
        // Se crean aquí porque las tablas pertenecen a local_gestion_actividades.
        local_gestion_actividades_add_index_if_possible($dbman, 'local_ga_certificates', 'userid', ['userid']);
        local_gestion_actividades_add_index_if_possible($dbman, 'local_ga_hour_history', 'useridx', ['userid']);
        local_gestion_actividades_add_index_if_possible($dbman, 'local_ga_typeb_certs', 'userid', ['userid']);
        local_gestion_actividades_add_index_if_possible($dbman, 'local_ga_typeb_certs', 'userstatus', ['userid', 'status']);

        upgrade_plugin_savepoint(true, 2026071125, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071128) {
        $table = new xmldb_table('local_ga_institutional_hours');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('courselevel', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $table->add_field('groupname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_field('typeahours', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('typebhours', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('source', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'Reconocimiento institucional');
            $table->add_field('importid', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $table->add_field('originalrow', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('rawdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('userid', XMLDB_INDEX_UNIQUE, ['userid']);
            $table->add_index('email', XMLDB_INDEX_NOTUNIQUE, ['email']);
            $dbman->create_table($table);
        } else {
            $fields = [
                new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id'),
                new xmldb_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '', 'userid'),
                new xmldb_field('fullname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'email'),
                new xmldb_field('courselevel', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'fullname'),
                new xmldb_field('groupname', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'courselevel'),
                new xmldb_field('typeahours', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0', 'groupname'),
                new xmldb_field('typebhours', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0', 'typeahours'),
                new xmldb_field('source', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'Reconocimiento institucional', 'typebhours'),
                new xmldb_field('importid', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'source'),
                new xmldb_field('originalrow', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'importid'),
                new xmldb_field('rawdata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'originalrow'),
                new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'rawdata'),
                new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated'),
                new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified'),
            ];
            foreach ($fields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
            $index = new xmldb_index('userid', XMLDB_INDEX_UNIQUE, ['userid']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
            $index = new xmldb_index('email', XMLDB_INDEX_NOTUNIQUE, ['email']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
        upgrade_plugin_savepoint(true, 2026071128, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071132) {
        $table = new xmldb_table('local_ga_task_submissions');
        if ($dbman->table_exists($table)) {
            $fields = [
                new xmldb_field('grade', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'fileitemid'),
                new xmldb_field('gradedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'grade'),
                new xmldb_field('timegraded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'gradedby'),
            ];
            foreach ($fields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026071132, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071134) {
        $table = new xmldb_table('local_ga_institutional_hours');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('taskgrade', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'typebhours');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2026071134, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071135) {
        $table = new xmldb_table('local_ga_workshops');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('workshoptype', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'typea', 'hours');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $table = new xmldb_table('local_ga_certificates');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('certificatetype', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'typea', 'editionid');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $index = new xmldb_index('certificatetype', XMLDB_INDEX_NOTUNIQUE, ['certificatetype']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        $table = new xmldb_table('local_ga_typeb_reflections');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('editionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('reflectiontext', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('editionuser', XMLDB_INDEX_UNIQUE, ['editionid', 'userid']);
            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026071135, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071137) {
        $table = new xmldb_table('local_ga_typeb_transfers');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('certificateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('workshopid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('editionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('hours', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('reflectiontext', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'active');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('certificateid', XMLDB_INDEX_UNIQUE, ['certificateid']);
            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $table->add_index('userstatus', XMLDB_INDEX_NOTUNIQUE, ['userid', 'status']);
            $table->add_index('editionid', XMLDB_INDEX_NOTUNIQUE, ['editionid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026071137, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071400) {
        $table = new xmldb_table('local_ga_course_settings');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('selfassessmentcmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('courseid', XMLDB_INDEX_UNIQUE, ['courseid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026071400, 'local', 'gestion_actividades');
    }

    if ($oldversion < 2026071401) {
        $table = new xmldb_table('local_ga_institutional_hours');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('typebreflection', XMLDB_TYPE_TEXT, null, null, null, null, null, 'taskgrade');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('typebreflectionmodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'typebreflection');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Desde esta versión todos los talleres Tipo A requieren una tarea.
        $editiontable = new xmldb_table('local_ga_workshop_editions');
        $workshoptable = new xmldb_table('local_ga_workshops');
        if ($dbman->table_exists($editiontable) && $dbman->table_exists($workshoptable)) {
            $DB->execute("UPDATE {local_ga_workshop_editions}
                            SET requiredmodname = 'assign', activitycreationtype = 'assign'
                          WHERE workshopid IN (
                                SELECT id
                                  FROM {local_ga_workshops}
                                 WHERE workshoptype = 'typea' OR workshoptype IS NULL OR workshoptype = ''
                          )");
            $DB->execute("UPDATE {local_ga_workshop_editions}
                            SET requiredmodname = '', activitycreationtype = '', requiredcmid = 0,
                                requiredassigncmid = 0, requiredquizcmid = 0
                          WHERE workshopid IN (
                                SELECT id
                                  FROM {local_ga_workshops}
                                 WHERE workshoptype = 'typeb'
                          )");
        }

        upgrade_plugin_savepoint(true, 2026071401, 'local', 'gestion_actividades');
    }

    if ($oldversion < 2026071402) {
        // Create the hidden 54-hour grade item, populate it in bulk and apply the
        // access restriction to any self-assessment quiz already selected.
        try {
            $courses = \local_gestion_actividades\local\grade_manager::get_managed_courses();
            foreach ($courses as $course) {
                $courseid = (int)$course->id;
                \local_gestion_actividades\local\grade_manager::sync_course_safely($courseid);
                \local_gestion_actividades\local\grade_manager::ensure_selfassessment_availability($courseid);
            }
        } catch (\Throwable $e) {
            if (function_exists('debugging')) {
                debugging('La migración del criterio de autoevaluación HEE continuará de forma diferida: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        upgrade_plugin_savepoint(true, 2026071402, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071404) {
        // Reapply the 54-hour restriction using the Moodle section API so the
        // containing section is completely hidden and course caches are purged correctly.
        try {
            $courses = \local_gestion_actividades\local\grade_manager::get_managed_courses();
            foreach ($courses as $course) {
                \local_gestion_actividades\local\grade_manager::ensure_selfassessment_availability((int)$course->id);
            }
        } catch (\Throwable $e) {
            if (function_exists('debugging')) {
                debugging('La restricción de sección de autoevaluación HEE se reintentará al guardar el selector: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        upgrade_plugin_savepoint(true, 2026071404, 'local', 'gestion_actividades');
    }

    if ($oldversion < 2026071405) {
        // Repair course-card section sequences and reapply the self-assessment section condition.
        try {
            \local_gestion_actividades\local\manager::ensure_all_workshop_course_visuals();
            \local_gestion_actividades\local\grade_manager::repair_configured_selfassessment_availability();
        } catch (\Throwable $e) {
            if (function_exists('debugging')) {
                debugging('La reparación visual HEE puede repetirse desde el panel: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        upgrade_plugin_savepoint(true, 2026071405, 'local', 'gestion_actividades');
    }

    if ($oldversion < 2026071411) {
        // Rebuild the canonical hour summaries and force Autoevaluación to the final course position.
        try {
            \local_gestion_actividades\local\manager::ensure_all_workshop_course_visuals();
            \local_gestion_actividades\local\grade_manager::repair_configured_selfassessment_availability();
        } catch (\Throwable $e) {
            if (function_exists('debugging')) {
                debugging('La normalización de horas y orden de secciones HEE puede repetirse desde el panel: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        upgrade_plugin_savepoint(true, 2026071411, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071420) {
        // Rebuild course cards so past workshops show the closed state even
        // before the per-user AMD updater runs.
        try {
            \local_gestion_actividades\local\manager::ensure_all_workshop_course_visuals();
        } catch (\Throwable $e) {
            debugging('No se pudieron reconstruir las tarjetas HEE: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        upgrade_plugin_savepoint(true, 2026071420, 'local', 'gestion_actividades');
    }

    if ($oldversion < 2026071421) {
        // Rebuild cards after installing the theme-independent status loader.
        try {
            \local_gestion_actividades\local\manager::ensure_all_workshop_course_visuals();
        } catch (\Throwable $e) {
            debugging('No se pudieron reconstruir las tarjetas HEE: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        upgrade_plugin_savepoint(true, 2026071421, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071422) {
        // Rebuild the shared cards so expired editions have the orange closed
        // state immediately; the per-user AMD module then paints enrolled users.
        try {
            \local_gestion_actividades\local\manager::ensure_all_workshop_course_visuals();
        } catch (\Throwable $e) {
            debugging('No se pudieron reconstruir las tarjetas HEE: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        upgrade_plugin_savepoint(true, 2026071422, 'local', 'gestion_actividades');
    }


    if ($oldversion < 2026071423) {
        // Rebuild cards with server-rendered, per-user enrolment status frames.
        try {
            \local_gestion_actividades\local\manager::ensure_all_workshop_course_visuals();
        } catch (\Throwable $e) {
            debugging('No se han podido reconstruir todas las tarjetas HEE: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        upgrade_plugin_savepoint(true, 2026071423, 'local', 'gestion_actividades');
    }

    if ($oldversion < 2026071424) {
        // Replace iframe/legacy controls with a Moodle-safe enrolment placeholder
        // and rebuild every Type A/Type B section so the AMD updater can find it.
        try {
            \local_gestion_actividades\local\manager::ensure_all_workshop_course_visuals();
        } catch (\Throwable $e) {
            debugging('No se han podido reconstruir las tarjetas de inscripción HEE: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        upgrade_plugin_savepoint(true, 2026071424, 'local', 'gestion_actividades');
    }

    if ($oldversion < 2026071429) {
        // Rebuild every course summary with stable edition markers. Per-user
        // states are painted from data resolved during course navigation.
        try {
            \local_gestion_actividades\local\manager::ensure_all_workshop_course_visuals();
        } catch (\Throwable $e) {
            debugging('No se pudieron reconstruir las tarjetas HEE v1.5.86: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        upgrade_plugin_savepoint(true, 2026071429, 'local', 'gestion_actividades');
    }

    return true;
}

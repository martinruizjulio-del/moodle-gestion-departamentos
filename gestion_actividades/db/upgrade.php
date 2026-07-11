<?php
defined('MOODLE_INTERNAL') || die();

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

    return true;
}

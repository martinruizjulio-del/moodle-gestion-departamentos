<?php
namespace local_gestion_actividades\local;

defined('MOODLE_INTERNAL') || die();

class portfolio_typeb {
    public static function ensure_table(): void {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('local_ga_typeb_certs');
        if ($dbman->table_exists($table)) {
            return;
        }

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
        $dbman->create_table($table);
    }

    public static function create_upload(int $userid, string $activityname, int $activitydate, float $hours, string $filename, string $tmpfilepath): int {
        global $DB;
        self::ensure_table();
        $now = time();
        $record = (object)[
            'userid' => $userid,
            'activityname' => trim($activityname),
            'activitydate' => $activitydate,
            'hours' => max(0, $hours),
            'authorizedconfirm' => 1,
            'filename' => clean_filename($filename),
            'status' => 'pending',
            'reviewcomment' => '',
            'reviewedby' => 0,
            'timereviewed' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $id = $DB->insert_record('local_ga_typeb_certs', $record);

        $fs = get_file_storage();
        $context = \context_system::instance();
        $fs->delete_area_files($context->id, 'local_gestion_actividades', 'typeb_certificate', $id);
        $fs->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => 'local_gestion_actividades',
            'filearea' => 'typeb_certificate',
            'itemid' => $id,
            'filepath' => '/',
            'filename' => clean_filename($filename),
            'mimetype' => 'application/pdf',
        ], $tmpfilepath);

        return $id;
    }

    public static function get(int $id): \stdClass {
        global $DB;
        self::ensure_table();
        return $DB->get_record('local_ga_typeb_certs', ['id' => $id], '*', MUST_EXIST);
    }

    public static function list_for_user(int $userid): array {
        global $DB;
        self::ensure_table();
        return $DB->get_records('local_ga_typeb_certs', ['userid' => $userid], 'activitydate DESC, timecreated DESC');
    }

    public static function list_all(int $userid = 0, string $status = ''): array {
        global $DB;
        self::ensure_table();
        $params = [];
        $where = [];
        if ($userid > 0) {
            $where[] = 'c.userid = :userid';
            $params['userid'] = $userid;
        }
        if ($status !== '') {
            $where[] = 'c.status = :status';
            $params['status'] = $status;
        }
        $wheresql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT c.*, u.firstname, u.lastname, u.email
                  FROM {local_ga_typeb_certs} c
                  JOIN {user} u ON u.id = c.userid
                 $wheresql
              ORDER BY c.timecreated DESC";
        return $DB->get_records_sql($sql, $params);
    }

    public static function set_status(int $id, string $status, string $comment, int $reviewerid): bool {
        global $DB;
        self::ensure_table();
        if (!in_array($status, ['pending', 'validated', 'rejected'], true)) {
            return false;
        }
        $record = self::get($id);
        $record->status = $status;
        $record->reviewcomment = $comment;
        $record->reviewedby = $reviewerid;
        $record->timereviewed = time();
        $record->timemodified = time();
        $DB->update_record('local_ga_typeb_certs', $record);
        return true;
    }

    public static function total_validated_hours(int $userid): float {
        global $DB;
        self::ensure_table();
        $total = $DB->get_field_sql("SELECT COALESCE(SUM(hours), 0) FROM {local_ga_typeb_certs} WHERE userid = :userid AND status = 'validated'", ['userid' => $userid]);
        return (float)$total;
    }

    public static function total_uploaded_hours(int $userid): float {
        global $DB;
        self::ensure_table();
        $total = $DB->get_field_sql("SELECT COALESCE(SUM(hours), 0) FROM {local_ga_typeb_certs} WHERE userid = :userid", ['userid' => $userid]);
        return (float)$total;
    }
}

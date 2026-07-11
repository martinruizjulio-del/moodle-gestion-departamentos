<?php
namespace local_gestion_actividades\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/gradelib.php');

class manager {
    public static function get_activity(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_ga_activities', ['id' => $id], '*', MUST_EXIST);
    }

    public static function save_activity(\stdClass $data): int {
        global $DB, $USER;
        $now = time();
        $record = (object)[
            'courseid' => (int)$data->courseid,
            'activitykey' => trim($data->activitykey),
            'name' => trim($data->name),
            'description' => $data->description ?? '',
            'teacherid' => !empty($data->teacherid) ? (int)$data->teacherid : null,
            'places' => max(1, (int)$data->places),
            'idfield' => in_array($data->idfield, ['email', 'username', 'idnumber'], true) ? $data->idfield : 'email',
            'timemodified' => $now,
        ];
        if (!empty($data->id)) {
            $record->id = (int)$data->id;
            $DB->update_record('local_ga_activities', $record);
            return $record->id;
        }
        $record->timecreated = $now;
        return $DB->insert_record('local_ga_activities', $record);
    }

    public static function process_csv(\stdClass $activity, string $filepath, string $filename, string $gradecolumn, bool $creategroup, bool $createmissingusers = false, string $academicyear = '', bool $savegradehistory = true, bool $updategradebook = false, string $gradeitemname = ''): \stdClass {
        global $DB, $USER;

        $now = time();
        if ($academicyear === '') {
            $year = (int)date('Y');
            $academicyear = $year . '/' . ($year + 1);
        }
        $import = (object)[
            'activityid' => $activity->id,
            'filename' => $filename,
            'userid' => $USER->id,
            'timecreated' => $now,
        ];
        $importid = $DB->insert_record('local_ga_imports', $import);

        // Keep the latest import clean for this activity.
        $DB->delete_records('local_ga_candidates', ['activityid' => $activity->id]);
        $DB->delete_records('local_ga_participants', ['activityid' => $activity->id]);

        $rows = self::read_csv($filepath);
        if (empty($rows)) {
            throw new \moodle_exception('errorcsvempty', 'local_gestion_actividades');
        }

        $headers = array_map([self::class, 'normalise_header'], array_shift($rows));
        $idfield = $activity->idfield;
        $idindex = array_search(self::normalise_header($idfield), $headers, true);
        $gradeindex = array_search(self::normalise_header($gradecolumn), $headers, true);
        if ($gradeindex === false && self::normalise_header($gradecolumn) !== 'grade') {
            $gradeindex = array_search('grade', $headers, true);
        }
        if ($gradeindex === false && self::normalise_header($gradecolumn) !== 'nota') {
            $gradeindex = array_search('nota', $headers, true);
        }
        if ($idindex === false) {
            throw new \moodle_exception('errormissingidcolumn', 'local_gestion_actividades', '', $idfield);
        }
        if ($gradeindex === false) {
            throw new \moodle_exception('errormissinggradecolumn', 'local_gestion_actividades');
        }

        $firstnameindex = array_search('firstname', $headers, true);
        if ($firstnameindex === false) { $firstnameindex = array_search('nombre', $headers, true); }
        $lastnameindex = array_search('lastname', $headers, true);
        if ($lastnameindex === false) { $lastnameindex = array_search('apellidos', $headers, true); }
        $emailindex = array_search('email', $headers, true);
        $usernameindex = array_search('username', $headers, true);
        $idnumberindex = array_search('idnumber', $headers, true);

        $seenusers = [];
        $candidates = [];
        $gradebookgrades = [];
        foreach ($rows as $row) {
            $identifier = self::cell($row, $idindex);
            $gradevalue = str_replace(',', '.', self::cell($row, $gradeindex));
            $candidate = (object)[
                'activityid' => $activity->id,
                'importid' => $importid,
                'userid' => null,
                'identifier' => $identifier,
                'firstname' => self::cell($row, $firstnameindex),
                'lastname' => self::cell($row, $lastnameindex),
                'email' => self::cell($row, $emailindex),
                'username' => self::cell($row, $usernameindex),
                'idnumber' => self::cell($row, $idnumberindex),
                'grade' => null,
                'rank' => null,
                'status' => 'candidate',
                'reason' => '',
                'timecreated' => $now,
            ];

            if ($identifier === '') {
                $candidate->status = 'invalid';
                $candidate->reason = 'Identificador vacío.';
                $candidate->id = $DB->insert_record('local_ga_candidates', $candidate);
                $candidates[] = $candidate;
                continue;
            }
            if ($gradevalue === '') {
                $candidate->status = 'nograde';
                $candidate->reason = 'Sin nota de expediente. No participa en el ranking de esta convocatoria.';
                $candidate->id = $DB->insert_record('local_ga_candidates', $candidate);
                $candidates[] = $candidate;
                continue;
            }
            if (!is_numeric($gradevalue)) {
                $candidate->status = 'invalid';
                $candidate->reason = 'Nota no numérica.';
                $candidate->id = $DB->insert_record('local_ga_candidates', $candidate);
                $candidates[] = $candidate;
                continue;
            }
            $candidate->grade = (float)$gradevalue;

            $users = self::find_users_by_field($idfield, $identifier);
            if (count($users) === 0) {
                if ($createmissingusers) {
                    $createduser = self::create_missing_user_from_candidate($candidate, $idfield, $identifier);
                    if ($createduser) {
                        $users = [$createduser];
                        $candidate->reason = 'Usuario creado automáticamente desde el CSV.';
                    } else {
                        $candidate->status = 'notfound';
                        $candidate->reason = 'No se ha encontrado usuario Moodle con ' . $idfield . ' = ' . $identifier . '. No se ha podido crear porque faltan email, nombre o apellidos.';
                        $candidate->id = $DB->insert_record('local_ga_candidates', $candidate);
                        $candidates[] = $candidate;
                        continue;
                    }
                } else {
                    $candidate->status = 'notfound';
                    $candidate->reason = 'No se ha encontrado usuario Moodle con ' . $idfield . ' = ' . $identifier;
                    $candidate->id = $DB->insert_record('local_ga_candidates', $candidate);
                    $candidates[] = $candidate;
                    continue;
                }
            }
            if (count($users) > 1) {
                $candidate->status = 'duplicate';
                $candidate->reason = 'Más de un usuario Moodle coincide con ese identificador.';
                $candidate->id = $DB->insert_record('local_ga_candidates', $candidate);
                $candidates[] = $candidate;
                continue;
            }

            $user = reset($users);
            $candidate->userid = $user->id;
            $candidate->email = $user->email;
            $candidate->username = $user->username;
            $candidate->idnumber = $user->idnumber;
            $candidate->firstname = $user->firstname;
            $candidate->lastname = $user->lastname;

            if ($savegradehistory && $candidate->grade !== null) {
                self::save_grade_history($activity, (int)$user->id, (float)$candidate->grade, $academicyear, $importid);
            }

            if (isset($seenusers[$user->id])) {
                $candidate->status = 'duplicate';
                $candidate->reason = 'El mismo usuario aparece más de una vez en el CSV.';
                $candidate->id = $DB->insert_record('local_ga_candidates', $candidate);
                $candidates[] = $candidate;
                continue;
            }
            $seenusers[$user->id] = true;

            if ($updategradebook && $candidate->grade !== null) {
                $gradebookgrades[(int)$user->id] = (float)$candidate->grade;
            }

            if (self::has_completed_activity($activity->activitykey, (int)$user->id)) {
                $candidate->status = 'completed';
                $candidate->reason = 'El alumno ya consta como realizado/certificado para esta clave de actividad.';
            }

            $candidate->id = $DB->insert_record('local_ga_candidates', $candidate);
            $candidates[] = $candidate;
        }

        if ($updategradebook && !empty($gradebookgrades)) {
            self::update_course_gradebook($activity, $gradebookgrades, $gradeitemname, $academicyear);
        }

        self::rank_candidates($activity);

        $groupid = null;
        if ($creategroup) {
            $groupid = self::create_group_for_selected($activity);
        } else {
            self::save_selected_participants($activity, null);
        }

        return self::summary($activity->id, $groupid);
    }

    private static function rank_candidates(\stdClass $activity): void {
        global $DB;
        $candidates = $DB->get_records('local_ga_candidates', ['activityid' => $activity->id]);
        $valid = [];
        foreach ($candidates as $candidate) {
            if ($candidate->status === 'candidate' && !empty($candidate->userid)) {
                $valid[] = $candidate;
            }
        }
        usort($valid, function($a, $b) {
            if ((float)$a->grade === (float)$b->grade) {
                return strcmp((string)$a->lastname, (string)$b->lastname);
            }
            return ((float)$a->grade < (float)$b->grade) ? 1 : -1;
        });

        $rank = 1;
        foreach ($valid as $candidate) {
            $candidate->rank = $rank;
            if ($rank <= (int)$activity->places) {
                $candidate->status = 'selected';
                $candidate->reason = 'Admitido por ranking.';
            } else {
                $candidate->status = 'reserve';
                $candidate->reason = 'Reserva por nota.';
            }
            $DB->update_record('local_ga_candidates', $candidate);
            $rank++;
        }
    }

    public static function create_group_for_selected(\stdClass $activity): ?int {
        global $DB;
        $groupname = shorten_text($activity->name, 60) . ' - Convocatoria ' . $activity->id . ' - Admitidos';
        $data = (object)[
            'courseid' => $activity->courseid,
            'name' => $groupname,
            'description' => 'Grupo creado automáticamente por Gestion_actividades.',
            'descriptionformat' => FORMAT_PLAIN,
        ];
        $groupid = groups_create_group($data);
        $selected = $DB->get_records('local_ga_candidates', ['activityid' => $activity->id, 'status' => 'selected']);
        foreach ($selected as $candidate) {
            groups_add_member($groupid, $candidate->userid);
        }
        self::save_selected_participants($activity, $groupid);
        return $groupid;
    }

    private static function save_selected_participants(\stdClass $activity, ?int $groupid): void {
        global $DB;
        $selected = $DB->get_records('local_ga_candidates', ['activityid' => $activity->id, 'status' => 'selected']);
        foreach ($selected as $candidate) {
            $participant = (object)[
                'activityid' => $activity->id,
                'candidateid' => $candidate->id,
                'userid' => $candidate->userid,
                'grade' => $candidate->grade,
                'rank' => $candidate->rank,
                'groupid' => $groupid,
                'status' => 'selected',
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $DB->insert_record('local_ga_participants', $participant);
        }
    }

    public static function mark_selected_as_completed(int $activityid): int {
        global $DB;
        $activity = self::get_activity($activityid);
        $participants = $DB->get_records('local_ga_participants', ['activityid' => $activityid, 'status' => 'selected']);
        $count = 0;
        foreach ($participants as $participant) {
            if (!$DB->record_exists('local_ga_completions', ['activitykey' => $activity->activitykey, 'userid' => $participant->userid])) {
                $DB->insert_record('local_ga_completions', (object)[
                    'activitykey' => $activity->activitykey,
                    'activityid' => $activityid,
                    'userid' => $participant->userid,
                    'status' => 'completed',
                    'source' => 'manual_v01',
                    'timecompleted' => time(),
                ]);
                $count++;
            }
            $participant->status = 'completed';
            $participant->timemodified = time();
            $DB->update_record('local_ga_participants', $participant);
        }
        return $count;
    }

    public static function has_completed_activity(string $activitykey, int $userid): bool {
        global $DB;
        return $DB->record_exists_select('local_ga_completions', 'activitykey = :activitykey AND userid = :userid AND status IN (\'completed\', \'certified\', \'attended\')', [
            'activitykey' => $activitykey,
            'userid' => $userid,
        ]);
    }



    public static function get_workshop_overview_rows(): array {
        global $DB;

        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_workshop_editions'))) {
            return [];
        }

        $sql = "SELECT e.id,
                       w.courseid,
                       w.code AS workshopcode,
                       w.name AS workshopname,
                       w.hours AS workshophours,
                       e.name AS editionname,
                       e.editioncode,
                       e.sessiondate,
                       e.enrolenddate,
                       e.places,
                       e.groupid,
                       e.status,
                       e.attendancecmid,
                       e.certificatecmid,
                       e.requiredcmid,
                       e.requiredmodname,
                       e.activitycreationtype,
                       e.tasknumericgrade,
                       e.quizgradingmode,
                       e.archived,
                       e.timearchived
                  FROM {local_ga_workshops} w
                  JOIN {local_ga_workshop_editions} e ON e.workshopid = w.id
              ORDER BY e.sessiondate DESC, e.id DESC";
        $rows = $DB->get_records_sql($sql);

        foreach ($rows as $row) {
            $row->enrolledcount = 0;
            if (!empty($row->groupid) && $DB->record_exists('groups', ['id' => $row->groupid])) {
                $row->enrolledcount = $DB->count_records('groups_members', ['groupid' => $row->groupid]);
                $group = $DB->get_record('groups', ['id' => $row->groupid], 'id,name');
                $row->groupname = $group ? $group->name : '';
            } else {
                $row->groupname = '';
            }

            $teachers = self::get_edition_teachers($row->id);
            $names = [];
            foreach ($teachers as $teacher) {
                $names[] = fullname($teacher);
            }
            $row->teachers = implode(', ', $names);

            $now = time();
            if (!empty($row->archived) || $row->status === 'archived') {
                $row->computedstatus = 'archived';
            } else if ($row->status === 'closed_full') {
                $row->computedstatus = 'closed_full';
            } else if (!empty($row->sessiondate) && $row->sessiondate < $now) {
                $row->computedstatus = 'past';
            } else if (!empty($row->enrolenddate) && $row->enrolenddate < $now) {
                $row->computedstatus = 'closed_date';
            } else {
                $row->computedstatus = 'open';
            }
        }

        return $rows;
    }



    public static function get_or_create_course_section(int $courseid, string $sectionname): int {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $sectionname = trim($sectionname);
        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');

        foreach ($sections as $section) {
            if (trim($section->name ?? '') === $sectionname) {
                return (int)$section->section;
            }
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $lastsection = 0;
        foreach ($sections as $section) {
            $lastsection = max($lastsection, (int)$section->section);
        }

        $newsectionnum = $lastsection + 1;
        course_create_sections_if_missing($course, $newsectionnum);

        $sectionrecord = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $newsectionnum], '*', MUST_EXIST);
        $sectionrecord->name = $sectionname;
        $sectionrecord->visible = 1;
        $sectionrecord->timemodified = time();
        $DB->update_record('course_sections', $sectionrecord);

        rebuild_course_cache($courseid, true);
        return $newsectionnum;
    }


    public static function get_main_workshop_section_name(): string {
        return 'TALLERES TIPO A';
    }

    public static function get_archive_workshop_section_name(): string {
        return 'TALLERES TIPO A - ARCHIVO';
    }

    public static function ensure_workshop_sections(int $courseid): \stdClass {
        return (object)[
            'main' => self::get_or_create_course_section($courseid, self::get_main_workshop_section_name()),
            'archive' => 0,
        ];
    }

    public static function get_workshop_section_name(\stdClass $workshop): string {
        return trim($workshop->code . ' - ' . $workshop->name);
    }

    public static function get_or_create_workshop_section(\stdClass $workshop): int {
        return self::get_or_create_course_section((int)$workshop->courseid, self::get_main_workshop_section_name());
    }



    public static function create_required_activity_for_edition(int $editionid, ?int $userid = null): \stdClass {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');

        $result = (object)['success' => false, 'message' => '', 'cmid' => 0];

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop((int)$edition->workshopid);
        $course = $DB->get_record('course', ['id' => (int)$workshop->courseid], '*', MUST_EXIST);

        if (!empty($edition->requiredcmid) && $DB->record_exists('course_modules', ['id' => (int)$edition->requiredcmid])) {
            $result->success = true;
            $result->cmid = (int)$edition->requiredcmid;
            $result->message = get_string('requiredactivityalreadycreated', 'local_gestion_actividades');
            return $result;
        }

        $type = self::detect_required_activity_type($edition);
        if ($type === '') {
            $result->message = get_string('requiredtypenotselected', 'local_gestion_actividades');
            return $result;
        }

        if (!$DB->record_exists('modules', ['name' => $type])) {
            $result->message = get_string('requiredmodnotavailable', 'local_gestion_actividades') . ': ' . $type;
            return $result;
        }

        $sectionnum = self::get_or_create_course_section((int)$course->id, self::get_main_workshop_section_name());

        $name = $type === 'quiz'
            ? get_string('quizforworkshop', 'local_gestion_actividades', $workshop->name)
            : get_string('assignmentforworkshop', 'local_gestion_actividades', $workshop->name);

        $moduleinfo = new \stdClass();
        $moduleinfo->course = (int)$course->id;
        $moduleinfo->section = $sectionnum;
        $moduleinfo->module = (int)$DB->get_field('modules', 'id', ['name' => $type], MUST_EXIST);
        $moduleinfo->modulename = $type;
        $moduleinfo->name = $name;
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->groupmode = 0;
        $moduleinfo->groupingid = 0;
        $moduleinfo->completion = 1;
        $moduleinfo->intro = get_string('requiredactivityintro', 'local_gestion_actividades', $workshop->name);
        $moduleinfo->introformat = FORMAT_HTML;

        // Minimal required fields for compatibility.
        if ($type === 'assign') {
            $moduleinfo->alwaysshowdescription = 1;
            $moduleinfo->submissiondrafts = 0;
            $moduleinfo->requiresubmissionstatement = 0;
            $moduleinfo->sendnotifications = 0;
            $moduleinfo->sendlatenotifications = 0;
            $moduleinfo->sendstudentnotifications = 0;
            $moduleinfo->duedate = 0;
            $moduleinfo->allowsubmissionsfromdate = 0;
            $moduleinfo->grade = 100;
            $moduleinfo->teamsubmission = 0;
            $moduleinfo->requireallteammemberssubmit = 0;
            $moduleinfo->blindmarking = 0;
            $moduleinfo->attemptreopenmethod = 'none';
            $moduleinfo->maxattempts = -1;
        } else if ($type === 'quiz') {
            $moduleinfo->grade = 100;
            $moduleinfo->sumgrades = 0;
            $moduleinfo->attempts = 0;
            $moduleinfo->questionsperpage = 1;
            $moduleinfo->preferredbehaviour = 'deferredfeedback';
            $moduleinfo->timeopen = 0;
            $moduleinfo->timeclose = 0;
        }

        try {
            $created = add_moduleinfo($moduleinfo, $course);
            $cmid = 0;
            if (!empty($created->coursemodule)) {
                $cmid = (int)$created->coursemodule;
            } else if (!empty($created->coursemoduleid)) {
                $cmid = (int)$created->coursemoduleid;
            } else if (!empty($created->cmid)) {
                $cmid = (int)$created->cmid;
            }

            if ($cmid > 0) {
                self::update_edition_required_cmid($editionid, $cmid);
                $result->success = true;
                $result->cmid = $cmid;
                $result->message = get_string('requiredactivitycreated', 'local_gestion_actividades');
                rebuild_course_cache((int)$course->id, true);
                return $result;
            }

            $result->message = get_string('requiredactivitycreatefailed', 'local_gestion_actividades');
            return $result;
        } catch (\Throwable $e) {
            $result->message = get_string('requiredactivitycreatefailed', 'local_gestion_actividades') . ': ' . $e->getMessage();
            return $result;
        }
    }

    public static function archive_due_workshop_editions(int $courseid = 0): int {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        $now = time();
        $params = ['now' => $now];
        $coursesql = '';
        if ($courseid > 0) {
            $coursesql = ' AND w.courseid = :courseid';
            $params['courseid'] = $courseid;
        }
        $sql = "SELECT e.*, w.courseid FROM {local_ga_workshop_editions} e JOIN {local_ga_workshops} w ON w.id = e.workshopid WHERE e.archived = 0 AND e.sessiondate > 0 AND e.sessiondate < :now $coursesql";
        $editions = $DB->get_records_sql($sql, $params);
        $count = 0;
        foreach ($editions as $edition) {
            $DB->set_field('local_ga_workshop_editions', 'archived', 1, ['id' => $edition->id]);
            $DB->set_field('local_ga_workshop_editions', 'timearchived', $now, ['id' => $edition->id]);
            $DB->set_field('local_ga_workshop_editions', 'status', 'archived', ['id' => $edition->id]);
            foreach (['requiredcmid', 'attendancecmid', 'certificatecmid'] as $field) {
                if (!empty($edition->$field) && $DB->record_exists('course_modules', ['id' => $edition->$field])) {
                    set_coursemodule_visible((int)$edition->$field, 0);
                }
            }
            $count++;
        }
        return $count;
    }

    public static function get_user_grade_for_cmid(int $userid, int $cmid): ?float {
        global $DB;
        if ($cmid <= 0) { return null; }
        $cm = $DB->get_record('course_modules', ['id' => $cmid], 'id,course,instance,module');
        if (!$cm) { return null; }
        $module = $DB->get_record('modules', ['id' => $cm->module], 'id,name');
        if (!$module) { return null; }
        $item = $DB->get_record('grade_items', ['courseid' => $cm->course, 'itemmodule' => $module->name, 'iteminstance' => $cm->instance], 'id');
        if (!$item) { return null; }
        $grade = $DB->get_record('grade_grades', ['itemid' => $item->id, 'userid' => $userid], 'finalgrade');
        if (!$grade || $grade->finalgrade === null) { return null; }
        return (float)$grade->finalgrade;
    }


    public static function ensure_workshop_sections_safely(int $workshopid): bool {
        global $DB;
        try {
            $workshop = $DB->get_record('local_ga_workshops', ['id' => $workshopid], '*', MUST_EXIST);
            self::ensure_workshop_sections((int)$workshop->courseid);
            self::get_or_create_workshop_section($workshop);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }


    public static function ensure_workshop_course_visuals_safely(int $workshopid): bool {
        $result = self::ensure_workshop_course_visual_with_message($workshopid);
        return !empty($result->success);
    }

    public static function ensure_all_workshop_course_visuals(int $courseid = 0): \stdClass {
        global $DB;

        $params = [];
        $where = '';
        if ($courseid > 0) {
            $where = 'WHERE courseid = :courseid';
            $params['courseid'] = $courseid;
        }

        $workshops = $DB->get_records_sql("SELECT * FROM {local_ga_workshops} $where ORDER BY courseid ASC, code ASC, id ASC", $params);
        $summary = (object)[
            'total' => count($workshops),
            'created' => 0,
            'failed' => 0,
            'messages' => [],
        ];

        foreach ($workshops as $workshop) {
            $result = self::ensure_workshop_course_visual_with_message((int)$workshop->id);
            if (!empty($result->success)) {
                $summary->created++;
            } else {
                $summary->failed++;
            }
            $summary->messages[] = $result->message;
        }

        return $summary;
    }

    public static function ensure_workshop_entry_in_main_section(\stdClass $workshop): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');

        if (!$DB->record_exists('modules', ['name' => 'label'])) {
            return false;
        }

        $mainsection = self::get_or_create_course_section((int)$workshop->courseid, self::get_main_workshop_section_name());
        $labelname = self::get_workshop_course_entry_name($workshop);

        // Avoid duplicates.
        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {label} l ON l.id = cm.instance
                 WHERE cm.course = :courseid
                   AND m.name = 'label'
                   AND l.name = :name";
        if ($DB->record_exists_sql($sql, ['courseid' => (int)$workshop->courseid, 'name' => $labelname])) {
            return true;
        }

        $course = $DB->get_record('course', ['id' => (int)$workshop->courseid], '*', MUST_EXIST);
        $module = $DB->get_record('modules', ['name' => 'label'], '*', MUST_EXIST);

        $url = new \moodle_url('/local/gestion_actividades/editions.php', ['workshopid' => $workshop->id]);
        $hours = isset($workshop->hours) && $workshop->hours !== null ? ' — ' . round((float)$workshop->hours, 2) . ' h' : '';
        $intro = '<div class="local-ga-workshop-entry"><strong>' . s($workshop->code . ' - ' . $workshop->name) . '</strong>' .
            s($hours) . '<br><a href="' . $url->out(false) . '">' .
            get_string('openworkshopeditions', 'local_gestion_actividades') . '</a></div>';

        $moduleinfo = new \stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->module = $module->id;
        $moduleinfo->modulename = 'label';
        $moduleinfo->section = $mainsection;
        $moduleinfo->visible = 1;
        $moduleinfo->name = $labelname;
        $moduleinfo->intro = $intro;
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->showdescription = 0;
        $moduleinfo->cmidnumber = '';
        $moduleinfo->groupmode = 0;
        $moduleinfo->groupingid = 0;
        $moduleinfo->completion = 0;
        $moduleinfo->availability = null;

        add_moduleinfo($moduleinfo, $course);
        rebuild_course_cache((int)$workshop->courseid, true);
        return true;
    }

    public static function get_workshop_course_entry_name(\stdClass $workshop): string {
        return '[Taller Tipo A] ' . trim($workshop->code . ' - ' . $workshop->name);
    }




    public static function ensure_workshop_url_in_main_section(\stdClass $workshop): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');

        if (!$DB->record_exists('modules', ['name' => 'url']) || !$DB->get_manager()->table_exists(new \xmldb_table('url'))) {
            return false;
        }

        $mainsection = self::get_or_create_course_section((int)$workshop->courseid, self::get_main_workshop_section_name());
        $entryname = self::get_workshop_course_entry_name($workshop);

        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {url} u ON u.id = cm.instance
                 WHERE cm.course = :courseid
                   AND m.name = 'url'
                   AND u.name = :name";
        if ($DB->record_exists_sql($sql, ['courseid' => (int)$workshop->courseid, 'name' => $entryname])) {
            return true;
        }

        $course = $DB->get_record('course', ['id' => (int)$workshop->courseid], '*', MUST_EXIST);
        $module = $DB->get_record('modules', ['name' => 'url'], '*', MUST_EXIST);
        $targeturl = new \moodle_url('/local/gestion_actividades/editions.php', ['workshopid' => $workshop->id]);

        $hours = isset($workshop->hours) && $workshop->hours !== null && $workshop->hours !== ''
            ? ' — ' . round((float)$workshop->hours, 2) . ' h'
            : '';

        $moduleinfo = new \stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->module = $module->id;
        $moduleinfo->modulename = 'url';
        $moduleinfo->section = $mainsection;
        $moduleinfo->visible = 1;
        $moduleinfo->name = $entryname . $hours;
        $moduleinfo->intro = get_string('workshopcourseentryintro', 'local_gestion_actividades');
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->externalurl = $targeturl->out(false);
        $moduleinfo->display = 0;
        $moduleinfo->displayoptions = serialize([]);
        $moduleinfo->showdescription = 0;
        $moduleinfo->cmidnumber = '';
        $moduleinfo->groupmode = 0;
        $moduleinfo->groupingid = 0;
        $moduleinfo->completion = 0;
        $moduleinfo->availability = null;

        add_moduleinfo($moduleinfo, $course);
        rebuild_course_cache((int)$workshop->courseid, true);
        return true;
    }

    public static function filter_object_to_columns(string $tablename, \stdClass $object): \stdClass {
        global $DB;
        $columns = $DB->get_columns($tablename);
        $out = new \stdClass();
        foreach ((array)$object as $key => $value) {
            if (isset($columns[$key])) {
                $out->$key = $value;
            }
        }
        return $out;
    }

    
    public static function get_primary_workshop_edition(int $workshopid): ?\stdClass {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_workshop_editions'))) {
            return null;
        }

        $now = time();
        $sql = "SELECT *
                  FROM {local_ga_workshop_editions}
                 WHERE workshopid = :workshopid
                   AND (archived = 0 OR archived IS NULL)
              ORDER BY CASE WHEN sessiondate >= :now THEN 0 ELSE 1 END,
                       sessiondate ASC,
                       id ASC";
        $records = $DB->get_records_sql($sql, ['workshopid' => $workshopid, 'now' => $now], 0, 1);
        if (!$records) {
            return null;
        }
        return reset($records);
    }

    
    public static function get_edition_remaining_places(\stdClass $edition): ?int {
        $places = (int)($edition->places ?? 0);
        if ($places <= 0) {
            return null;
        }
        return max(0, $places - self::get_edition_enrolment_count((int)$edition->id));
    }



    public static function get_edition_enrolment(int $editionid, int $userid): ?\stdClass {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_edition_enrolments'))) {
            return null;
        }
        $record = $DB->get_record('local_ga_edition_enrolments', ['editionid' => $editionid, 'userid' => $userid]);
        return $record ?: null;
    }

    public static function get_edition_enrolment_count(int $editionid): int {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_edition_enrolments'))) {
            return 0;
        }
        return (int)$DB->count_records('local_ga_edition_enrolments', ['editionid' => $editionid, 'status' => 'enrolled']);
    }

    public static function enrol_user_in_edition(int $editionid, int $userid, string $source = 'self'): \stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop((int)$edition->workshopid);
        $now = time();

        $result = (object)['success' => false, 'message' => ''];

        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_edition_enrolments'))) {
            $result->message = get_string('enroltablemissing', 'local_gestion_actividades');
            return $result;
        }

        if (!empty($edition->enrolenddate) && $now > (int)$edition->enrolenddate) {
            $result->message = get_string('enrolclosed', 'local_gestion_actividades');
            return $result;
        }

        $existing = self::get_edition_enrolment($editionid, $userid);
        if ($existing && $existing->status === 'enrolled') {
            $result->success = true;
            $result->message = get_string('alreadyenrolled', 'local_gestion_actividades');
            return $result;
        }

        $places = (int)($edition->places ?? 0);
        if ($places > 0 && self::get_edition_enrolment_count($editionid) >= $places) {
            $result->message = get_string('editionfull', 'local_gestion_actividades');
            return $result;
        }

        if (!empty($edition->groupid) && $DB->record_exists('groups', ['id' => $edition->groupid])) {
            try {
                groups_add_member((int)$edition->groupid, $userid);
            } catch (\Throwable $e) {
                // Do not block internal enrolment if group add fails.
            }
        }

        $record = (object)[
            'editionid' => $editionid,
            'userid' => $userid,
            'status' => 'enrolled',
            'source' => $source,
            'reason' => '',
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record = self::filter_record_to_existing_fields('local_ga_edition_enrolments', $record);

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_ga_edition_enrolments', $record);
        } else {
            $DB->insert_record('local_ga_edition_enrolments', $record);
        }

        $result->success = true;
        $result->message = get_string('enrolledok', 'local_gestion_actividades');
        return $result;
    }

    public static function format_workshop_date(?int $timestamp): string {
        if (empty($timestamp)) {
            return '-';
        }
        return userdate($timestamp, '%A %d/%m/%Y');
    }

    public static function get_cm_url_or_empty(int $cmid): string {
        global $DB;
        if (empty($cmid) || !$DB->record_exists('course_modules', ['id' => $cmid])) {
            return '';
        }
        return (new \moodle_url('/mod/' . self::get_modname_from_cmid($cmid) . '/view.php', ['id' => $cmid]))->out(false);
    }

    public static function get_modname_from_cmid(int $cmid): string {
        global $DB;
        $sql = "SELECT m.name
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.id = :cmid";
        return (string)$DB->get_field_sql($sql, ['cmid' => $cmid]);
    }



    public static function ensure_workshop_course_visual_with_message(int $workshopid): \stdClass {
        global $DB, $CFG;

        $result = (object)[
            'success' => false,
            'message' => '',
        ];

        try {
            require_once($CFG->dirroot . '/course/lib.php');

            $workshop = $DB->get_record('local_ga_workshops', ['id' => $workshopid], '*', MUST_EXIST);
            $edition = self::get_primary_workshop_edition($workshopid);
            $courseid = (int)$workshop->courseid;

            if (!$DB->record_exists('modules', ['name' => 'label'])) {
                $result->message = 'No existe o no está instalado el módulo label/etiqueta.';
                return $result;
            }
            if (!$DB->get_manager()->table_exists(new \xmldb_table('label'))) {
                $result->message = 'No existe la tabla label del módulo etiqueta.';
                return $result;
            }

            $sectionnum = self::get_or_create_course_section($courseid, self::get_main_workshop_section_name());
            $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum], '*', MUST_EXIST);
            $module = $DB->get_record('modules', ['name' => 'label'], '*', MUST_EXIST);

            $entrybase = self::get_workshop_course_entry_name($workshop);
            $hours = isset($workshop->hours) && $workshop->hours !== null && $workshop->hours !== ''
                ? round((float)$workshop->hours, 2) . ' h'
                : '-';
            $entryname = trim($workshop->code . ' - ' . $workshop->name);

            $viewurl = new \moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $workshop->id]);
            $enrolurl = $edition ? new \moodle_url('/local/gestion_actividades/enrol.php', ['id' => $edition->id, 'sesskey' => sesskey()]) : $viewurl;
            $date = $edition ? self::format_workshop_date((int)$edition->sessiondate) : '-';
            $description = trim((string)($workshop->description ?? ''));

            $remainingtext = '-';
            if ($edition) {
                $remaining = self::get_edition_remaining_places($edition);
                $remainingtext = $remaining === null ? get_string('unlimitedplaces', 'local_gestion_actividades') : (string)$remaining;
            }

            $intro = '<div class="local-ga-course-workshop" style="padding:.8rem 1rem;border-left:4px solid #0f6cbf;background:#f8f9fa;margin:.4rem 0;">';
            $intro .= '<div style="font-weight:700;font-size:1.08rem;">' . s($entryname) . '</div>';
            if ($description !== '') {
                $intro .= '<div style="margin-top:.25rem;">' . s($description) . '</div>';
            }
            $intro .= '<div style="margin-top:.4rem;">';
            $intro .= '<strong>' . get_string('date') . ':</strong> ' . s($date);
            $intro .= ' · <strong>' . get_string('workshophours', 'local_gestion_actividades') . ':</strong> ' . s($hours);
            $intro .= ' · <strong>' . get_string('remainingplaces', 'local_gestion_actividades') . ':</strong> ' . s($remainingtext);
            $intro .= '</div>';
            $intro .= '<div style="margin-top:.45rem;">';
            $intro .= '<a class="btn btn-primary" href="' . $enrolurl->out(false) . '">' . get_string('enrolme', 'local_gestion_actividades') . '</a> ';
            $intro .= '<a class="btn btn-secondary" href="' . $viewurl->out(false) . '">' . get_string('viewworkshop', 'local_gestion_actividades') . '</a>';
            $intro .= '<div style="margin-top:.35rem;color:#555;">' . get_string('frontstatusnote', 'local_gestion_actividades') . '</div>';
            $intro .= '</div>';
            $intro .= '</div>';

            $sql = "SELECT l.id AS labelid, cm.id AS cmid
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                      JOIN {label} l ON l.id = cm.instance
                     WHERE cm.course = :courseid
                       AND m.name = 'label'
                       AND (l.name LIKE :oldname OR l.name LIKE :newname)";
            $existing = $DB->get_records_sql($sql, [
                'courseid' => $courseid,
                'oldname' => $DB->sql_like_escape($entrybase) . '%',
                'newname' => $DB->sql_like_escape($entryname) . '%',
            ]);

            if ($existing) {
                foreach ($existing as $ex) {
                    $label = $DB->get_record('label', ['id' => $ex->labelid], '*', MUST_EXIST);
                    $label->name = $entryname;
                    $label->intro = $intro;
                    $label->introformat = FORMAT_HTML;
                    $label->timemodified = time();
                    $label = self::filter_object_to_columns('label', $label);
                    $DB->update_record('label', $label);
                }
                rebuild_course_cache($courseid, true);
                $result->success = true;
                $result->message = $entryname . ': actualizado para alumnos.';
                return $result;
            }

            $label = new \stdClass();
            $label->course = $courseid;
            $label->name = $entryname;
            $label->intro = $intro;
            $label->introformat = FORMAT_HTML;
            $label->timemodified = time();
            $label = self::filter_object_to_columns('label', $label);
            $instanceid = $DB->insert_record('label', $label);

            if (function_exists('add_course_module') && function_exists('course_add_cm_to_section')) {
                $cm = new \stdClass();
                $cm->course = $courseid;
                $cm->module = (int)$module->id;
                $cm->instance = (int)$instanceid;
                $cm->section = (int)$section->id;
                $cm->visible = 1;
                $cm->visibleoncoursepage = 1;
                $cm->visibleold = 1;
                $cm->groupmode = 0;
                $cm->groupingid = 0;
                $cm->completion = 0;
                $cm->showdescription = 0;
                $cm->availability = null;
                $cmid = add_course_module($cm);
                course_add_cm_to_section($courseid, $cmid, $sectionnum);
            } else {
                $cm = new \stdClass();
                $cm->course = $courseid;
                $cm->module = (int)$module->id;
                $cm->instance = (int)$instanceid;
                $cm->section = (int)$section->id;
                $cm->idnumber = '';
                $cm->added = time();
                $cm->score = 0;
                $cm->indent = 0;
                $cm->visible = 1;
                $cm->visibleoncoursepage = 1;
                $cm->visibleold = 1;
                $cm->groupmode = 0;
                $cm->groupingid = 0;
                $cm->completion = 0;
                $cm->completiongradeitemnumber = null;
                $cm->completionview = 0;
                $cm->completionexpected = 0;
                $cm->showdescription = 0;
                $cm->availability = null;
                $cm->deletioninprogress = 0;
                $cm = self::filter_object_to_columns('course_modules', $cm);
                $cmid = $DB->insert_record('course_modules', $cm);

                $sequence = trim((string)($section->sequence ?? ''));
                $items = $sequence === '' ? [] : array_filter(array_map('trim', explode(',', $sequence)), 'strlen');
                if (!in_array((string)$cmid, $items, true)) {
                    $items[] = (string)$cmid;
                    $section->sequence = implode(',', $items);
                    $section->timemodified = time();
                    $DB->update_record('course_sections', $section);
                }
            }

            rebuild_course_cache($courseid, true);
            $result->success = true;
            $result->message = $entryname . ': creado para alumnos.';
            return $result;

        } catch (\Throwable $e) {
            $result->message = 'Error generando taller ID ' . $workshopid . ': ' . $e->getMessage();
            return $result;
        }
    }

    public static function get_course_required_activity_options(int $courseid): array {
        global $DB;

        $options = [0 => get_string('norequiredactivity', 'local_gestion_actividades')];

        $sql = "SELECT cm.id,
                       m.name AS modname,
                       cm.instance,
                       cm.completion,
                       cm.visible
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.course = :courseid
                   AND m.name IN ('assign', 'quiz')
              ORDER BY m.name ASC, cm.id ASC";
        $cms = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        foreach ($cms as $cm) {
            $name = '';
            if ($cm->modname === 'assign' && $DB->record_exists('assign', ['id' => $cm->instance])) {
                $name = $DB->get_field('assign', 'name', ['id' => $cm->instance]);
            } else if ($cm->modname === 'quiz' && $DB->record_exists('quiz', ['id' => $cm->instance])) {
                $name = $DB->get_field('quiz', 'name', ['id' => $cm->instance]);
            }
            if ($name !== '') {
                $label = format_string($name) . ' — ' . get_string('modulename', $cm->modname) . ' — CMID ' . $cm->id;
                if (empty($cm->completion)) {
                    $label .= ' — ' . get_string('completionnotenabled', 'local_gestion_actividades');
                }
                $options[$cm->id] = $label;
            }
        }

        return $options;
    }

    public static function get_module_name_from_cmid(int $cmid): string {
        global $DB;
        if ($cmid <= 0) {
            return '';
        }
        $sql = "SELECT m.name
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.id = :cmid";
        return (string)$DB->get_field_sql($sql, ['cmid' => $cmid]);
    }

    public static function search_course_users(int $courseid, string $query, int $limit = 30): array {
        $context = \context_course::instance($courseid);
        $users = get_enrolled_users($context, '', 0,
            'u.id, u.firstname, u.lastname, u.email, u.username, u.idnumber',
            'u.lastname ASC, u.firstname ASC', 0, $limit);

        $query = \core_text::strtolower(trim($query));
        if ($query === '') {
            return $users;
        }

        $filtered = [];
        foreach ($users as $user) {
            $haystack = \core_text::strtolower(
                fullname($user) . ' ' . $user->email . ' ' . $user->username . ' ' . $user->idnumber
            );
            if (strpos($haystack, $query) !== false) {
                $filtered[$user->id] = $user;
            }
        }
        return $filtered;
    }

    public static function manually_add_student_to_edition(int $editionid, int $userid, bool $force = false): \stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop($edition->workshopid);

        if (empty($edition->groupid)) {
            self::create_group_for_edition($editionid);
            $edition = self::get_workshop_edition($editionid);
        }

        $summary = (object)[
            'added' => 0,
            'blockedrepeat' => 0,
            'overplaces' => 0,
            'alreadyingroup' => 0,
            'message' => '',
        ];

        $alreadydone = $DB->record_exists('local_ga_completions', [
            'activitykey' => $workshop->code,
            'userid' => $userid,
        ]);

        if ($alreadydone && !$force) {
            $summary->blockedrepeat = 1;
            $summary->message = get_string('alreadycompletedworkshop', 'local_gestion_actividades');
            return $summary;
        }

        $currentmembers = $DB->count_records('groups_members', ['groupid' => $edition->groupid]);
        if ((int)$edition->places > 0 && $currentmembers >= (int)$edition->places && !$force) {
            $summary->overplaces = 1;
            $summary->message = get_string('editionfull', 'local_gestion_actividades');
            return $summary;
        }

        if (groups_is_member($edition->groupid, $userid)) {
            $summary->alreadyingroup = 1;
        } else {
            groups_add_member($edition->groupid, $userid);
        }

        $existing = $DB->get_record('local_ga_edition_enrolments', ['editionid' => $editionid, 'userid' => $userid]);
        if ($existing) {
            $existing->status = 'manual';
            $existing->reason = get_string('manualexception', 'local_gestion_actividades');
            $existing->timemodified = time();
            $DB->update_record('local_ga_edition_enrolments', $existing);
        } else {
            $DB->insert_record('local_ga_edition_enrolments', (object)[
                'editionid' => $editionid,
                'workshopid' => $edition->workshopid,
                'userid' => $userid,
                'groupid' => $edition->groupid,
                'status' => 'manual',
                'reason' => get_string('manualexception', 'local_gestion_actividades'),
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $summary->added = 1;
        $summary->message = get_string('studentmanualadded', 'local_gestion_actividades');
        return $summary;
    }

    public static function user_completed_required_activity(int $userid, int $cmid): bool {
        global $DB;
        if ($cmid <= 0) {
            return true;
        }
        $completion = $DB->get_record('course_modules_completion', ['coursemoduleid' => $cmid, 'userid' => $userid]);
        if (!$completion) {
            return false;
        }
        return ((int)$completion->completionstate > 0);
    }

    public static function get_edition_students_status(int $editionid): array {
        global $DB;
        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop($edition->workshopid);

        $sql = "SELECT ee.*, u.firstname, u.lastname, u.email, u.username, u.idnumber
                  FROM {local_ga_edition_enrolments} ee
                  JOIN {user} u ON u.id = ee.userid
                 WHERE ee.editionid = :editionid
              ORDER BY u.lastname, u.firstname";
        $rows = $DB->get_records_sql($sql, ['editionid' => $editionid]);

        foreach ($rows as $row) {
            $row->attended = $DB->record_exists('local_ga_completions', [
                'activitykey' => $workshop->code,
                'userid' => $row->userid,
            ]) ? 1 : 0;
            $row->requiredcompleted = self::user_completed_required_activity((int)$row->userid, (int)$edition->requiredcmid) ? 1 : 0;
            $row->activitygrade = self::get_user_grade_for_cmid((int)$row->userid, (int)$edition->requiredcmid);
            if (($edition->requiredmodname ?? '') === 'quiz' && ($edition->quizgradingmode ?? 'completion') === 'points') {
                $row->requiredcompleted = ($row->activitygrade !== null) ? 1 : 0;
            }
            $row->certificateeligible = ($row->attended && $row->requiredcompleted) ? 1 : 0;
            $row->certificatependingstore = $row->certificateeligible;
        }

        return $rows;
    }



    public static function format_action_icon(string $url, string $pix, string $label, string $btnclass = 'btn btn-secondary btn-sm'): string {
        global $OUTPUT;
        return html_writer::link(
            $url,
            $OUTPUT->pix_icon($pix, $label),
            ['class' => $btnclass, 'title' => $label, 'aria-label' => $label]
        );
    }

    public static function get_authorized_managers(): array {
        $context = \context_system::instance();
        return get_users_by_capability(
            $context,
            'local/gestion_actividades:manage',
            'u.id, u.firstname, u.lastname, u.email, u.username',
            'u.lastname ASC, u.firstname ASC'
        );
    }

    public static function get_student_hour_history(int $userid): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_hour_history'))) {
            return [];
        }
        return $DB->get_records('local_ga_hour_history', ['userid' => $userid], 'timecompleted DESC, id DESC');
    }

    public static function get_student_total_hours(int $userid): float {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_hour_history'))) {
            return 0.0;
        }
        $total = $DB->get_field_sql(
            "SELECT COALESCE(SUM(hours), 0) FROM {local_ga_hour_history} WHERE userid = :userid",
            ['userid' => $userid]
        );
        return (float)$total;
    }

    public static function get_hours_summary_by_student(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_hour_history'))) {
            return [];
        }

        $sql = "SELECT u.id,
                       u.firstname,
                       u.lastname,
                       u.email,
                       COUNT(h.id) AS completedworkshops,
                       COALESCE(SUM(h.hours), 0) AS totalhours
                  FROM {local_ga_hour_history} h
                  JOIN {user} u ON u.id = h.userid
              GROUP BY u.id, u.firstname, u.lastname, u.email
              ORDER BY u.lastname ASC, u.firstname ASC";
        return $DB->get_records_sql($sql);
    }

    public static function store_completed_hour_record(int $editionid, int $userid): bool {
        global $DB;

        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_hour_history'))) {
            return false;
        }

        if ($DB->record_exists('local_ga_hour_history', ['editionid' => $editionid, 'userid' => $userid])) {
            return false;
        }

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop($edition->workshopid);
        $hours = isset($workshop->hours) && $workshop->hours !== null ? (float)$workshop->hours : 0.0;

        $record = (object)[
            'userid' => $userid,
            'courseid' => (int)$workshop->courseid,
            'workshopid' => (int)$workshop->id,
            'editionid' => (int)$editionid,
            'workshopcode' => $workshop->code,
            'workshopname' => $workshop->name,
            'editionname' => $edition->name,
            'hours' => $hours,
            'certificatecmid' => (int)($edition->certificatecmid ?? 0),
            'certificatestatus' => 'pending',
            'timecompleted' => time(),
            'timecreated' => time(),
        ];

        $DB->insert_record('local_ga_hour_history', $record);
        return true;
    }

    public static function refresh_completed_hours_for_edition(int $editionid): int {
        $rows = self::get_edition_students_status($editionid);
        $created = 0;
        foreach ($rows as $row) {
            if (!empty($row->certificateeligible)) {
                if (self::store_completed_hour_record($editionid, (int)$row->userid)) {
                    $created++;
                }
            }
        }
        return $created;
    }




    public static function delete_workshop(int $workshopid): \stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/course/lib.php');

        $summary = (object)[
            'workshopid' => $workshopid,
            'editionsdeleted' => 0,
            'groupsdeleted' => 0,
            'labelsdeleted' => 0,
            'workshopdeleted' => 0,
        ];

        $workshop = self::get_workshop($workshopid);
        $summary->labelsdeleted = self::delete_workshop_course_entries($workshop);

        if ($DB->get_manager()->table_exists(new \xmldb_table('local_ga_workshop_editions'))) {
            $editions = $DB->get_records('local_ga_workshop_editions', ['workshopid' => $workshopid]);
            foreach ($editions as $edition) {
                if ($DB->get_manager()->table_exists(new \xmldb_table('local_ga_edition_teachers'))) {
                    $DB->delete_records('local_ga_edition_teachers', ['editionid' => $edition->id]);
                }
                if ($DB->get_manager()->table_exists(new \xmldb_table('local_ga_edition_enrolments'))) {
                    $DB->delete_records('local_ga_edition_enrolments', ['editionid' => $edition->id]);
                }
                if (!empty($edition->groupid) && $DB->record_exists('groups', ['id' => $edition->groupid])) {
                    try {
                        groups_delete_group($edition->groupid);
                        $summary->groupsdeleted++;
                    } catch (\Throwable $e) {
                        // Continue.
                    }
                }
                $DB->delete_records('local_ga_workshop_editions', ['id' => $edition->id]);
                $summary->editionsdeleted++;
            }
        }

        if ($DB->get_manager()->table_exists(new \xmldb_table('local_ga_hour_history'))) {
            $DB->delete_records('local_ga_hour_history', ['workshopid' => $workshopid]);
        }

        $DB->delete_records('local_ga_workshops', ['id' => $workshopid]);
        $summary->workshopdeleted = 1;
        rebuild_course_cache((int)$workshop->courseid, true);

        return $summary;
    }

    public static function delete_workshop_course_entries(\stdClass $workshop): int {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $count = 0;
        $entryname = self::get_workshop_course_entry_name($workshop);
        $targets = [
            ['mod' => 'label', 'table' => 'label', 'alias' => 'l'],
            ['mod' => 'url', 'table' => 'url', 'alias' => 'u'],
            ['mod' => 'page', 'table' => 'page', 'alias' => 'p'],
        ];

        foreach ($targets as $target) {
            if (!$DB->record_exists('modules', ['name' => $target['mod']]) || !$DB->get_manager()->table_exists(new \xmldb_table($target['table']))) {
                continue;
            }

            $alias = $target['alias'];
            $sql = "SELECT cm.id
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                      JOIN {" . $target['table'] . "} $alias ON $alias.id = cm.instance
                     WHERE cm.course = :courseid
                       AND m.name = :modname
                       AND $alias.name LIKE :name";
            $cms = $DB->get_records_sql($sql, [
                'courseid' => (int)$workshop->courseid,
                'modname' => $target['mod'],
                'name' => $DB->sql_like_escape($entryname) . '%',
            ]);

            foreach ($cms as $cm) {
                try {
                    course_delete_module((int)$cm->id);
                    $count++;
                } catch (\Throwable $e) {
                    // Continue.
                }
            }
        }

        return $count;
    }

    public static function delete_workshop_edition(int $editionid, bool $deletegroup = true): \stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $edition = self::get_workshop_edition($editionid);
        $summary = (object)[
            'editionid' => $editionid,
            'groupid' => (int)$edition->groupid,
            'groupdeleted' => 0,
            'editiondeleted' => 0,
        ];

        $DB->delete_records('local_ga_edition_teachers', ['editionid' => $editionid]);
        $DB->delete_records('local_ga_edition_enrolments', ['editionid' => $editionid]);

        if ($deletegroup && !empty($edition->groupid) && $DB->record_exists('groups', ['id' => $edition->groupid])) {
            groups_delete_group($edition->groupid);
            $summary->groupdeleted = 1;
        }

        $DB->delete_records('local_ga_workshop_editions', ['id' => $editionid]);
        $summary->editiondeleted = 1;

        return $summary;
    }



    public static function get_course_group_options(int $courseid, bool $includeempty = true): array {
        global $DB;
        $options = [];
        if ($includeempty) {
            $options[0] = get_string('nogroupselected', 'local_gestion_actividades');
        }

        $groups = self::get_course_groups($courseid);
        foreach ($groups as $group) {
            $count = $DB->count_records('groups_members', ['groupid' => $group->id]);
            $options[$group->id] = format_string($group->name) . ' (' . $count . ' ' . get_string('studentscount', 'local_gestion_actividades') . ')';
        }

        return $options;
    }



    public static function create_group_for_edition(int $editionid): int {
        // Disabled in safe mode. Groups will be re-enabled after basic saving is confirmed.
        return 0;
    }

    public static function get_course_options(): array {
        global $DB;
        $courses = $DB->get_records_sql("SELECT id, fullname, shortname FROM {course} WHERE id <> 1 ORDER BY fullname ASC");
        $options = [];
        foreach ($courses as $course) {
            $options[$course->id] = format_string($course->fullname) . ' [' . s($course->shortname) . '] — ID ' . $course->id;
        }
        return $options;
    }



    public static function list_workshops(int $courseid = 0): array {
        global $DB;
        $params = [];
        $where = '';
        if ($courseid > 0) {
            $where = 'WHERE courseid = :courseid';
            $params['courseid'] = $courseid;
        }
        return $DB->get_records_sql("SELECT * FROM {local_ga_workshops} $where ORDER BY code ASC, name ASC", $params);
    }

    public static function get_workshop(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_ga_workshops', ['id' => $id], '*', MUST_EXIST);
    }

    
    public static function filter_record_to_existing_fields(string $tablename, \stdClass $record): \stdClass {
        global $DB;

        $filtered = new \stdClass();
        $columns = $DB->get_columns($tablename);
        foreach ((array)$record as $key => $value) {
            if (isset($columns[$key])) {
                $filtered->$key = $value;
            }
        }
        return $filtered;
    }

    public static function get_dml_debug_message(\Throwable $e): string {
        $message = $e->getMessage();
        if ($message === '') {
            $message = get_class($e);
        }
        return $message;
    }

    public static function parse_decimal_input($value): ?float {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        $value = trim((string)$value);
        $value = str_replace(' ', '', $value);
        $value = str_replace(',', '.', $value);
        return is_numeric($value) ? (float)$value : null;
    }

    public static function format_date_compact(int $timestamp): string {
        if (empty($timestamp)) {
            return '-';
        }
        return userdate($timestamp, '%A %d/%m/%Y');
    }



    public static function generate_workshop_code(string $name, int $courseid, int $excludeid = 0): string {
        global $DB;

        $clean = \core_text::strtoupper(trim($name));
        $clean = preg_replace('/[^A-ZÁÉÍÓÚÜÑ0-9 ]/u', ' ', $clean);
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);

        $stop = ['DE','DEL','LA','LAS','EL','LOS','Y','E','EN','A','PARA','POR','CON','UN','UNA','TIPO'];
        $letters = '';
        foreach ($words as $word) {
            if (in_array($word, $stop, true)) {
                continue;
            }
            $letters .= \core_text::substr($word, 0, 1);
            if (\core_text::strlen($letters) >= 4) {
                break;
            }
        }

        if ($letters === '') {
            $letters = 'TAL';
        }

        $letters = strtr($letters, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N'
        ]);
        $base = preg_replace('/[^A-Z0-9]/', '', $letters);
        if ($base === '') {
            $base = 'TAL';
        }

        $code = $base;
        $i = 2;
        while (true) {
            $params = ['courseid' => $courseid, 'code' => $code];
            $sql = "courseid = :courseid AND code = :code";
            if ($excludeid > 0) {
                $sql .= " AND id <> :excludeid";
                $params['excludeid'] = $excludeid;
            }
            if (!$DB->record_exists_select('local_ga_workshops', $sql, $params)) {
                return $code;
            }
            $code = $base . $i;
            $i++;
        }
    }

    public static function save_workshop(\stdClass $data): int {
        global $DB;
        $now = time();
        $id = !empty($data->id) ? (int)$data->id : 0;

        $code = trim($data->code ?? '');
        if ($code === '') {
            $code = self::generate_workshop_code((string)$data->name, (int)$data->courseid, $id);
        }

        $record = (object)[
            'courseid' => (int)$data->courseid,
            'code' => $code,
            'name' => trim($data->name),
            'description' => clean_param($data->description ?? '', PARAM_TEXT),
            'allowrepeat' => 0,
            'timemodified' => $now,
        ];

        $columns = $DB->get_columns('local_ga_workshops');
        if (isset($columns['hours'])) {
            $record->hours = self::parse_decimal_input($data->hours ?? null);
        }
        if (isset($columns['sectionnum'])) {
            $record->sectionnum = (int)($data->sectionnum ?? 0);
        }

        if ($id > 0) {
            $record->id = $id;
            $DB->update_record('local_ga_workshops', $record);
            $workshopid = $id;
        } else {
            $record->timecreated = $now;
            $workshopid = $DB->insert_record('local_ga_workshops', $record);
        }

        // Non-blocking: update Moodle course visual structure after saving data.
        // Visual generation only runs from the explicit button in v0.9.6.

        return $workshopid;
    }

    public static function list_workshop_editions(int $workshopid = 0): array {
        global $DB;
        if ($workshopid > 0) {
            return $DB->get_records('local_ga_workshop_editions', ['workshopid' => $workshopid], 'sessiondate ASC, id ASC');
        }
        return $DB->get_records('local_ga_workshop_editions', null, 'sessiondate ASC, id ASC');
    }

    public static function get_workshop_edition(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_ga_workshop_editions', ['id' => $id], '*', MUST_EXIST);
    }

    public static function get_course_teachers(int $courseid): array {
        $context = \context_course::instance($courseid);
        $teachers = get_enrolled_users($context, 'moodle/course:update', 0, 'u.id, u.firstname, u.lastname, u.email', 'u.lastname ASC, u.firstname ASC');
        if (!$teachers) {
            $teachers = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email', 'u.lastname ASC, u.firstname ASC');
        }
        return $teachers;
    }

    public static function save_workshop_edition(\stdClass $data): int {
        global $DB;
        $now = time();
        $id = !empty($data->id) ? (int)$data->id : 0;

        // Minimal safe edition record. Automatic group/activity/certificate creation is disabled in this version.
        $record = (object)[
            'workshopid' => (int)$data->workshopid,
            'name' => trim($data->name),
            'editioncode' => trim($data->editioncode),
            'sessiondate' => (int)($data->sessiondate ?? 0),
            'enrolenddate' => (int)($data->enrolenddate ?? 0),
            'places' => (int)($data->places ?? 0),
            'status' => trim($data->status ?? 'open'),
            'timemodified' => $now,
        ];

        $columns = $DB->get_columns('local_ga_workshop_editions');
        $optional = [
            'activityid' => (int)($data->activityid ?? 0),
            'groupid' => (int)($data->groupid ?? 0),
            'attendancecmid' => (int)($data->attendancecmid ?? 0),
            'certificatecmid' => (int)($data->certificatecmid ?? 0),
            'requiredcmid' => (int)($data->requiredcmid ?? 0),
            'requiredmodname' => trim($data->requiredmodname ?? ''),
            'activitycreationtype' => trim($data->activitycreationtype ?? ''),
            'tasknumericgrade' => self::parse_decimal_input($data->tasknumericgrade ?? null),
            'quizgradingmode' => trim($data->quizgradingmode ?? 'completion'),
            'archived' => (int)($data->archived ?? 0),
            'timearchived' => (int)($data->timearchived ?? 0),
        ];

        foreach ($optional as $field => $value) {
            if (isset($columns[$field])) {
                $record->$field = $value;
            }
        }

        if ($id > 0) {
            $old = $DB->get_record('local_ga_workshop_editions', ['id' => $id], '*', MUST_EXIST);

            foreach (['groupid', 'requiredcmid', 'requiredmodname'] as $field) {
                if (isset($columns[$field]) && empty($record->$field) && isset($old->$field)) {
                    $record->$field = $old->$field;
                }
            }

            $record->id = $id;
            $DB->update_record('local_ga_workshop_editions', $record);
            $editionid = $id;
        } else {
            $record->timecreated = $now;
            $editionid = $DB->insert_record('local_ga_workshop_editions', $record);
        }

        // Save teachers only if the table exists; do not let it break the edition save.
        if (isset($data->teachers) && is_array($data->teachers)) {
            try {
                self::save_edition_teachers($editionid, $data->teachers);
            } catch (\Throwable $e) {
                // Non-blocking in safe mode.
            }
        }

        return $editionid;
    }

    public static function save_edition_teachers(int $editionid, array $teachers): void {
        global $DB;

        $DB->delete_records('local_ga_edition_teachers', ['editionid' => $editionid]);

        $seen = [];
        foreach ($teachers as $userid) {
            $userid = (int)$userid;
            if ($userid <= 0 || isset($seen[$userid]) || !$DB->record_exists('user', ['id' => $userid])) {
                continue;
            }
            $seen[$userid] = true;

            try {
                $DB->insert_record('local_ga_edition_teachers', (object)[
                    'editionid' => $editionid,
                    'userid' => $userid,
                    'timecreated' => time(),
                ]);
            } catch (\Throwable $e) {
                continue;
            }
        }
    }

    public static function get_edition_teachers(int $editionid): array {
        global $DB;
        return $DB->get_records_sql("SELECT u.id, u.firstname, u.lastname, u.email
                                       FROM {local_ga_edition_teachers} et
                                       JOIN {user} u ON u.id = et.userid
                                      WHERE et.editionid = :editionid
                                   ORDER BY u.lastname, u.firstname", ['editionid' => $editionid]);
    }

    public static function sync_edition_enrolments_from_group(int $editionid): \stdClass {
        global $DB;
        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop($edition->workshopid);
        if (empty($edition->groupid)) {
            throw new \moodle_exception('editionnogroup', 'local_gestion_actividades');
        }
        $group = $DB->get_record('groups', ['id' => $edition->groupid], '*', MUST_EXIST);
        if ((int)$group->courseid !== (int)$workshop->courseid) {
            throw new \moodle_exception('groupwrongcourse', 'local_gestion_actividades');
        }

        $members = groups_get_members($edition->groupid, 'u.id, u.firstname, u.lastname, u.email, u.username, u.idnumber');
        $summary = (object)[
            'members' => count($members),
            'inserted' => 0,
            'blockedrepeat' => 0,
            'overplaces' => 0,
            'closed' => 0,
        ];

        $DB->delete_records('local_ga_edition_enrolments', ['editionid' => $editionid]);

        $countaccepted = 0;
        foreach ($members as $user) {
            $alreadydone = $DB->record_exists('local_ga_completions', [
                'activitykey' => $workshop->code,
                'userid' => $user->id,
            ]);

            $status = 'enrolled';
            $reason = '';
            if ($alreadydone) {
                $status = 'blocked_repeat';
                $reason = get_string('alreadycompletedworkshop', 'local_gestion_actividades');
                $summary->blockedrepeat++;
            } else if ($edition->places > 0 && $countaccepted >= $edition->places) {
                $status = 'over_places';
                $reason = get_string('editionfull', 'local_gestion_actividades');
                $summary->overplaces++;
            } else {
                $countaccepted++;
                $summary->inserted++;
            }

            $DB->insert_record('local_ga_edition_enrolments', (object)[
                'editionid' => $editionid,
                'workshopid' => $edition->workshopid,
                'userid' => $user->id,
                'groupid' => $edition->groupid,
                'status' => $status,
                'reason' => $reason,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        if ($edition->places > 0 && $countaccepted >= $edition->places) {
            $edition->status = 'closed_full';
            $edition->timemodified = time();
            $DB->update_record('local_ga_workshop_editions', $edition);
            $summary->closed = 1;
        }

        return $summary;
    }



    public static function get_course_groups(int $courseid): array {
        global $DB;
        return $DB->get_records('groups', ['courseid' => $courseid], 'name ASC');
    }

    public static function set_participants_from_group(int $activityid, int $groupid): \stdClass {
        global $DB;

        $activity = self::get_activity($activityid);
        $group = $DB->get_record('groups', ['id' => $groupid], '*', MUST_EXIST);
        if ((int)$group->courseid !== (int)$activity->courseid) {
            throw new \moodle_exception('groupwrongcourse', 'local_gestion_actividades');
        }

        $members = groups_get_members($groupid, 'u.id, u.firstname, u.lastname, u.email, u.username, u.idnumber');

        $summary = (object)[
            'groupid' => $groupid,
            'groupname' => $group->name,
            'members' => count($members),
            'inserted' => 0,
            'overplaces' => 0,
        ];

        // Replace participant list with the real enrolled/self-selected workshop group.
        $DB->delete_records('local_ga_participants', ['activityid' => $activityid]);

        $rank = 1;
        foreach ($members as $user) {
            $candidate = $DB->get_record('local_ga_candidates', ['activityid' => $activityid, 'userid' => $user->id]);
            $grade = $candidate && $candidate->grade !== null ? $candidate->grade : null;

            $record = (object)[
                'activityid' => $activityid,
                'candidateid' => $candidate ? $candidate->id : null,
                'userid' => $user->id,
                'grade' => $grade,
                'rank' => $candidate && $candidate->rank ? $candidate->rank : $rank,
                'groupid' => $groupid,
                'status' => 'enrolled',
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $DB->insert_record('local_ga_participants', $record);
            $summary->inserted++;
            $rank++;
        }

        if ((int)$activity->places > 0 && $summary->members > (int)$activity->places) {
            $summary->overplaces = $summary->members - (int)$activity->places;
        }

        return $summary;
    }



    public static function attendance_tables_available(): bool {
        global $DB;
        $dbman = $DB->get_manager();
        return $dbman->table_exists(new \xmldb_table('attendance'))
            && $dbman->table_exists(new \xmldb_table('attendance_sessions'))
            && $dbman->table_exists(new \xmldb_table('attendance_log'))
            && $dbman->table_exists(new \xmldb_table('attendance_statuses'));
    }

    public static function get_attendance_sessions(int $courseid): array {
        global $DB;
        if (!self::attendance_tables_available()) {
            return [];
        }
        $sql = "SELECT s.id,
                       a.name AS attendancename,
                       s.sessdate,
                       s.duration
                  FROM {attendance} a
                  JOIN {attendance_sessions} s ON s.attendanceid = a.id
                 WHERE a.course = :courseid
              ORDER BY s.sessdate DESC, s.id DESC";
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
    }

    public static function sync_attendance_session(int $activityid, int $sessionid): \stdClass {
        global $DB;
        $activity = self::get_activity($activityid);

        $summary = (object)[
            'sessionid' => $sessionid,
            'processed' => 0,
            'attended' => 0,
            'notpresent' => 0,
            'nolog' => 0,
            'alreadycompleted' => 0,
        ];

        if (!self::attendance_tables_available()) {
            throw new \moodle_exception('attendancenotavailable', 'local_gestion_actividades');
        }

        $session = $DB->get_record('attendance_sessions', ['id' => $sessionid], '*', MUST_EXIST);
        $attendance = $DB->get_record('attendance', ['id' => $session->attendanceid], '*', MUST_EXIST);
        if ((int)$attendance->course !== (int)$activity->courseid) {
            throw new \moodle_exception('attendancesessionwrongcourse', 'local_gestion_actividades');
        }

        $participants = $DB->get_records('local_ga_participants', ['activityid' => $activityid]);
        foreach ($participants as $participant) {
            $summary->processed++;

            $log = $DB->get_record('attendance_log', [
                'sessionid' => $sessionid,
                'studentid' => $participant->userid,
            ]);

            if (!$log) {
                $summary->nolog++;
                continue;
            }

            $status = $DB->get_record('attendance_statuses', ['id' => $log->statusid]);
            if (!$status || !empty($status->deleted) || (float)$status->grade <= 0) {
                $summary->notpresent++;
                continue;
            }

            if ($DB->record_exists('local_ga_completions', [
                'activitykey' => $activity->activitykey,
                'userid' => $participant->userid,
            ])) {
                $summary->alreadycompleted++;
            } else {
                $DB->insert_record('local_ga_completions', (object)[
                    'activitykey' => $activity->activitykey,
                    'activityid' => $activityid,
                    'userid' => $participant->userid,
                    'status' => 'attended',
                    'source' => 'attendance_session_' . $sessionid,
                    'timecompleted' => time(),
                ]);
            }

            $participant->status = 'attended';
            $participant->timemodified = time();
            $DB->update_record('local_ga_participants', $participant);

            if (!empty($participant->candidateid)) {
                $candidate = $DB->get_record('local_ga_candidates', ['id' => $participant->candidateid]);
                if ($candidate) {
                    $candidate->status = 'attended';
                    $candidate->reason = 'Asistencia registrada en Moodle Attendance. Sesión ID: ' . $sessionid;
                    $DB->update_record('local_ga_candidates', $candidate);
                }
            }

            $summary->attended++;
        }

        return $summary;
    }



    public static function process_users_csv(string $filepath, string $filename, bool $updateexisting = false): \stdClass {
        global $DB, $CFG;

        $rows = self::read_csv($filepath);
        if (empty($rows)) {
            throw new \moodle_exception('errorcsvempty', 'local_gestion_actividades');
        }

        $headers = array_map([self::class, 'normalise_header'], array_shift($rows));
        $emailindex = array_search('email', $headers, true);
        $usernameindex = array_search('username', $headers, true);
        $firstnameindex = array_search('firstname', $headers, true);
        if ($firstnameindex === false) { $firstnameindex = array_search('nombre', $headers, true); }
        $lastnameindex = array_search('lastname', $headers, true);
        if ($lastnameindex === false) { $lastnameindex = array_search('apellidos', $headers, true); }
        $idnumberindex = array_search('idnumber', $headers, true);
        $cityindex = array_search('city', $headers, true);
        $countryindex = array_search('country', $headers, true);
        $passwordindex = array_search('password', $headers, true);

        if ($emailindex === false || $firstnameindex === false || $lastnameindex === false) {
            throw new \moodle_exception('errormissingusercolumns', 'local_gestion_actividades');
        }

        $summary = (object)[
            'filename' => $filename,
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'invalid' => 0,
            'duplicates' => 0,
            'rows' => [],
        ];

        $seenemails = [];
        $seenusernames = [];
        foreach ($rows as $rownum => $row) {
            $summary->total++;
            $email = \core_text::strtolower(trim(self::cell($row, $emailindex)));
            $firstname = trim(self::cell($row, $firstnameindex));
            $lastname = trim(self::cell($row, $lastnameindex));
            $username = trim(self::cell($row, $usernameindex));
            $idnumber = trim(self::cell($row, $idnumberindex));
            $city = trim(self::cell($row, $cityindex));
            $country = strtoupper(trim(self::cell($row, $countryindex)));
            $password = trim(self::cell($row, $passwordindex));

            if ($username === '' && $email !== '') {
                $username = preg_replace('/@.*/', '', $email);
            }
            $username = \core_text::strtolower(clean_param($username, PARAM_USERNAME));

            $result = (object)[
                'row' => $rownum + 2,
                'email' => $email,
                'username' => $username,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'status' => '',
                'message' => '',
                'userid' => null,
            ];

            if ($email === '' || !validate_email($email) || $firstname === '' || $lastname === '' || $username === '') {
                $result->status = 'invalid';
                $result->message = 'Faltan email/nombre/apellidos/username o el email no es válido.';
                $summary->invalid++;
                $summary->rows[] = $result;
                continue;
            }

            if (isset($seenemails[$email]) || isset($seenusernames[$username])) {
                $result->status = 'duplicate';
                $result->message = 'Duplicado dentro del CSV.';
                $summary->duplicates++;
                $summary->rows[] = $result;
                continue;
            }
            $seenemails[$email] = true;
            $seenusernames[$username] = true;

            $existingbyemail = $DB->get_record('user', ['email' => $email, 'deleted' => 0], 'id, username, email, firstname, lastname, idnumber');
            $existingbyusername = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0], 'id, username, email, firstname, lastname, idnumber');

            if ($existingbyemail && $existingbyusername && (int)$existingbyemail->id !== (int)$existingbyusername->id) {
                $result->status = 'duplicate';
                $result->message = 'El email y el username pertenecen a usuarios Moodle distintos.';
                $summary->duplicates++;
                $summary->rows[] = $result;
                continue;
            }

            $existing = $existingbyemail ?: $existingbyusername;
            if ($existing) {
                $result->userid = $existing->id;
                if ($updateexisting) {
                    $update = (object)[
                        'id' => $existing->id,
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'idnumber' => $idnumber,
                        'timemodified' => time(),
                    ];
                    if ($city !== '') { $update->city = $city; }
                    if ($country !== '') { $update->country = $country; }
                    user_update_user($update, false, false);
                    $result->status = 'updated';
                    $result->message = 'Usuario existente actualizado.';
                    $summary->updated++;
                } else {
                    $result->status = 'skipped';
                    $result->message = 'Ya existe en Moodle. No se ha modificado.';
                    $summary->skipped++;
                }
                $summary->rows[] = $result;
                continue;
            }

            if ($password === '') {
                $password = generate_password(12);
            }

            $user = (object)[
                'auth' => 'manual',
                'confirmed' => 1,
                'mnethostid' => $CFG->mnet_localhost_id,
                'username' => $username,
                'password' => $password,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'idnumber' => $idnumber,
                'city' => ($city !== '') ? $city : '-',
                'country' => $country,
                'lang' => current_language(),
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $userid = user_create_user($user, true, false);
            $result->userid = $userid;
            $result->status = 'created';
            $result->message = 'Usuario creado.';
            $summary->created++;
            $summary->rows[] = $result;
        }

        return $summary;
    }


    private static function save_grade_history(\stdClass $activity, int $userid, float $grade, string $academicyear, int $importid): void {
        global $DB, $USER;
        $now = time();
        $existing = $DB->get_record('local_ga_grades', [
            'activitykey' => $activity->activitykey,
            'userid' => $userid,
            'academicyear' => $academicyear,
        ]);
        $record = (object)[
            'activityid' => $activity->id,
            'activitykey' => $activity->activitykey,
            'userid' => $userid,
            'academicyear' => $academicyear,
            'grade' => $grade,
            'importid' => $importid,
            'usermodified' => $USER->id,
            'timemodified' => $now,
        ];
        if ($existing) {
            $record->id = $existing->id;
            $record->timecreated = $existing->timecreated;
            $DB->update_record('local_ga_grades', $record);
        } else {
            $record->timecreated = $now;
            $DB->insert_record('local_ga_grades', $record);
        }
    }

    public static function get_grade_history(string $activitykey, int $limit = 500): array {
        global $DB;
        $sql = "SELECT g.*, u.firstname, u.lastname, u.email, u.username
                  FROM {local_ga_grades} g
                  JOIN {user} u ON u.id = g.userid
                 WHERE g.activitykey = :activitykey
              ORDER BY g.academicyear DESC, g.grade DESC, u.lastname ASC, u.firstname ASC";
        return $DB->get_records_sql($sql, ['activitykey' => $activitykey], 0, $limit);
    }

    private static function update_course_gradebook(\stdClass $activity, array $grades, string $gradeitemname = '', string $academicyear = ''): void {
        global $DB;

        if (empty($grades)) {
            return;
        }
        if ($gradeitemname === '') {
            $gradeitemname = get_string('defaultgradeitemname', 'local_gestion_actividades');
        }
        if ($academicyear !== '') {
            $gradeitemname .= ' (' . $academicyear . ')';
        }

        // Create/update a standard MANUAL grade item. This is more compatible than
        // grade_update() with itemtype=local, because some Moodle installations do not
        // accept local-plugin grade sources for visible gradebook columns.
        $idnumber = 'ga_' . (int)$activity->id . '_' . substr(sha1($gradeitemname), 0, 12);

        $gradeitem = \grade_item::fetch([
            'courseid' => (int)$activity->courseid,
            'idnumber' => $idnumber,
        ]);

        if (!$gradeitem) {
            $gradeitem = new \grade_item([
                'courseid' => (int)$activity->courseid,
                'itemtype' => 'manual',
                'itemname' => $gradeitemname,
                'idnumber' => $idnumber,
                'gradetype' => GRADE_TYPE_VALUE,
                'grademin' => 0,
                'grademax' => 10,
                'iteminfo' => 'Creado por Gestion_actividades para guardar la nota de expediente.',
            ], false);
            $gradeitem->insert('local_gestion_actividades');
        } else {
            $changed = false;
            if ($gradeitem->itemname !== $gradeitemname) {
                $gradeitem->itemname = $gradeitemname;
                $changed = true;
            }
            if ((float)$gradeitem->grademax != 10.0) {
                $gradeitem->grademax = 10;
                $changed = true;
            }
            if ((float)$gradeitem->grademin != 0.0) {
                $gradeitem->grademin = 0;
                $changed = true;
            }
            if ($changed) {
                $gradeitem->update('local_gestion_actividades');
            }
        }

        foreach ($grades as $userid => $grade) {
            $ok = $gradeitem->update_final_grade((int)$userid, (float)$grade, 'local_gestion_actividades');
            if (!$ok) {
                throw new \moodle_exception('No se ha podido guardar la calificación del usuario ID ' . (int)$userid . ' en el cuaderno de Moodle.');
            }
        }

        grade_regrade_final_grades((int)$activity->courseid);
    }

    private static function create_missing_user_from_candidate(\stdClass $candidate, string $idfield, string $identifier): ?\stdClass {
        global $CFG, $DB;

        $email = trim((string)$candidate->email);
        if ($email === '' && $idfield === 'email') {
            $email = trim($identifier);
        }
        $username = trim((string)$candidate->username);
        if ($username === '' && $idfield === 'username') {
            $username = trim($identifier);
        }
        if ($username === '' && $email !== '') {
            $username = preg_replace('/@.*/', '', $email);
        }
        $idnumber = trim((string)$candidate->idnumber);
        if ($idnumber === '' && $idfield === 'idnumber') {
            $idnumber = trim($identifier);
        }

        $firstname = trim((string)$candidate->firstname);
        $lastname = trim((string)$candidate->lastname);
        if ($email === '' || $firstname === '' || $lastname === '' || $username === '') {
            return null;
        }

        $username = \core_text::strtolower(clean_param($username, PARAM_USERNAME));
        $baseusername = $username;
        $suffix = 1;
        while ($DB->record_exists('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id])) {
            $username = $baseusername . $suffix;
            $suffix++;
        }

        if ($DB->record_exists('user', ['email' => $email, 'deleted' => 0])) {
            return null;
        }

        $password = generate_password(16);
        $user = (object)[
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
            'username' => $username,
            'password' => $password,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'idnumber' => $idnumber,
            'city' => '-',
            'country' => '',
            'lang' => current_language(),
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $userid = user_create_user($user, true, false);
        return $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname, email, username, idnumber', MUST_EXIST);
    }

    private static function find_users_by_field(string $field, string $value): array {
        global $DB;
        if (!in_array($field, ['email', 'username', 'idnumber'], true)) {
            return [];
        }
        return array_values($DB->get_records_select('user', "$field = :value AND deleted = 0", ['value' => $value], '', 'id, firstname, lastname, email, username, idnumber'));
    }

    private static function read_csv(string $filepath): array {
        $rows = [];
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return $rows;
        }
        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);
            return $rows;
        }
        $delimiter = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';
        rewind($handle);
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($data) === 1 && trim($data[0]) === '') {
                continue;
            }
            $rows[] = $data;
        }
        fclose($handle);
        return $rows;
    }

    private static function normalise_header($header): string {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header);
        $header = \core_text::strtolower(trim($header));
        $header = str_replace([' ', '-', '.'], '_', $header);
        return $header;
    }

    private static function cell(array $row, $index): string {
        if ($index === false || $index === null || !isset($row[$index])) {
            return '';
        }
        return trim((string)$row[$index]);
    }

    private static function summary(int $activityid, ?int $groupid): \stdClass {
        global $DB;
        $statuses = ['selected', 'reserve', 'notfound', 'completed', 'nograde', 'invalid', 'duplicate'];
        $summary = (object)['total' => 0, 'groupid' => $groupid];
        foreach ($statuses as $status) {
            $summary->{$status} = 0;
        }
        $records = $DB->get_records('local_ga_candidates', ['activityid' => $activityid]);
        foreach ($records as $record) {
            $summary->total++;
            if (isset($summary->{$record->status})) {
                $summary->{$record->status}++;
            }
        }
        return $summary;
    }

    

    public static function can_manage_workshop(\stdClass $course, int $userid): bool {
        if (function_exists('is_role_switched') && is_role_switched((int)$course->id)) {
            return false;
        }

        $coursecontext = \context_course::instance((int)$course->id);
        $syscontext = \context_system::instance();

        if (is_siteadmin($userid)) {
            return true;
        }

        if (has_capability('moodle/course:update', $coursecontext, $userid)) {
            return true;
        }

        if (has_capability('local/gestion_actividades:manage', $syscontext, $userid)) {
            return true;
        }

        if (self::is_authorized_manager($userid)) {
            return true;
        }

        return false;
    }

    public static function is_authorized_manager(int $userid): bool {
        global $DB;
        if (is_siteadmin($userid)) {
            return true;
        }
        if ($DB->get_manager()->table_exists(new \xmldb_table('local_ga_authorized'))
            && $DB->record_exists('local_ga_authorized', ['userid' => $userid])) {
            return true;
        }
        return false;
    }

    public static function list_authorized_users(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_authorized'))) {
            return [];
        }
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email, a.timecreated
                  FROM {local_ga_authorized} a
                  JOIN {user} u ON u.id = a.userid
                 WHERE u.deleted = 0
              ORDER BY u.lastname ASC, u.firstname ASC";
        return $DB->get_records_sql($sql);
    }

    public static function add_authorized_user(int $userid, int $addedby): bool {
        global $DB;
        if ($userid <= 0 || !$DB->record_exists('user', ['id' => $userid, 'deleted' => 0])) {
            return false;
        }
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_authorized'))) {
            return false;
        }
        if ($DB->record_exists('local_ga_authorized', ['userid' => $userid])) {
            return true;
        }
        $record = (object)[
            'userid' => $userid,
            'addedby' => $addedby,
            'timecreated' => time(),
        ];
        $DB->insert_record('local_ga_authorized', $record);
        return true;
    }

    public static function remove_authorized_user(int $userid): bool {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_authorized'))) {
            return false;
        }
        $DB->delete_records('local_ga_authorized', ['userid' => $userid]);
        return true;
    }


    public static function search_course_teachers(int $courseid, string $query): array {
        global $DB;
        $query = trim($query); if ($query === '') { return []; }
        $context = \context_course::instance($courseid); $like = '%' . $DB->sql_like_escape($query) . '%';
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
                  FROM {role_assignments} ra JOIN {role} r ON r.id = ra.roleid JOIN {user} u ON u.id = ra.userid
                 WHERE ra.contextid = :contextid AND u.deleted = 0 AND u.confirmed = 1
                   AND (r.shortname IN ('editingteacher','teacher','manager') OR r.archetype IN ('editingteacher','teacher','manager'))
                   AND (".$DB->sql_like('u.firstname', ':q1', false)." OR ".$DB->sql_like('u.lastname', ':q2', false)." OR ".$DB->sql_like('u.email', ':q3', false).")
              ORDER BY u.lastname ASC, u.firstname ASC";
        return $DB->get_records_sql($sql, ['contextid'=>$context->id,'q1'=>$like,'q2'=>$like,'q3'=>$like], 0, 20);
    }
    public static function search_course_students(int $courseid, string $query): array {
        global $DB;
        $query = trim($query); if ($query === '') { return []; }
        $context = \context_course::instance($courseid); $like = '%' . $DB->sql_like_escape($query) . '%';
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
                  FROM {role_assignments} ra JOIN {role} r ON r.id = ra.roleid JOIN {user} u ON u.id = ra.userid
                 WHERE ra.contextid = :contextid AND u.deleted = 0 AND u.confirmed = 1
                   AND (r.shortname = 'student' OR r.archetype = 'student')
                   AND (".$DB->sql_like('u.firstname', ':q1', false)." OR ".$DB->sql_like('u.lastname', ':q2', false)." OR ".$DB->sql_like('u.email', ':q3', false).")
              ORDER BY u.lastname ASC, u.firstname ASC";
        return $DB->get_records_sql($sql, ['contextid'=>$context->id,'q1'=>$like,'q2'=>$like,'q3'=>$like], 0, 20);
    }

    public static function list_edition_enrolled_users_safe(int $editionid): array {
        global $DB;

        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_edition_enrolments'))) {
            return [];
        }

        $sql = "SELECT e.id AS enrolmentid,
                       u.id AS userid,
                       u.firstname,
                       u.lastname,
                       u.email,
                       e.status,
                       e.source,
                       e.timecreated
                  FROM {local_ga_edition_enrolments} e
                  JOIN {user} u ON u.id = e.userid
                 WHERE e.editionid = :editionid
                   AND u.deleted = 0
              ORDER BY u.lastname ASC, u.firstname ASC, u.id ASC";
        return $DB->get_records_sql($sql, ['editionid' => $editionid]);
    }

    public static function list_edition_enrolled_users(int $editionid): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_edition_enrolments'))) { return []; }
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email, e.status, e.source, e.timecreated
                  FROM {local_ga_edition_enrolments} e JOIN {user} u ON u.id = e.userid
                 WHERE e.editionid = :editionid AND e.status = 'enrolled' AND u.deleted = 0
              ORDER BY u.lastname ASC, u.firstname ASC";
        return $DB->get_records_sql($sql, ['editionid'=>$editionid]);
    }
    public static function get_material_file_url(\stdClass $material, \context $context): string {
        if (empty($material->fileitemid)) { return ''; }
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_gestion_actividades', 'material', (int)$material->fileitemid, 'filename', false);
        if (!$files) { return ''; }
        $file = reset($files);
        return \moodle_url::make_pluginfile_url($context->id, 'local_gestion_actividades', 'material', (int)$material->fileitemid, $file->get_filepath(), $file->get_filename())->out(false);
    }


    public static function search_users_for_authorization(string $query): array {
        global $DB;
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $like = '%' . $DB->sql_like_escape($query) . '%';
        $sql = "SELECT id, firstname, lastname, email
                  FROM {user}
                 WHERE deleted = 0
                   AND confirmed = 1
                   AND (".$DB->sql_like('firstname', ':q1', false)."
                    OR ".$DB->sql_like('lastname', ':q2', false)."
                    OR ".$DB->sql_like('email', ':q3', false).")
              ORDER BY lastname ASC, firstname ASC";
        return $DB->get_records_sql($sql, ['q1' => $like, 'q2' => $like, 'q3' => $like], 0, 20);
    }

    public static function list_materials(int $workshopid, int $editionid = 0, bool $onlyvisible = false): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_materials'))) {
            return [];
        }
        $params = ['workshopid' => $workshopid];
        $where = 'workshopid = :workshopid';
        if ($editionid > 0) {
            $where .= ' AND (editionid = 0 OR editionid = :editionid)';
            $params['editionid'] = $editionid;
        }
        if ($onlyvisible) {
            $where .= ' AND visible = 1';
        }
        return $DB->get_records_select('local_ga_materials', $where, $params, 'timecreated DESC, id DESC');
    }

    public static function save_material(\stdClass $data): int {
        global $DB;
        $now = time();
        $record = (object)[
            'workshopid' => (int)$data->workshopid,
            'editionid' => (int)($data->editionid ?? 0),
            'name' => trim($data->name ?? ''),
            'description' => trim($data->description ?? ''),
            'url' => trim($data->url ?? ''),
            'visible' => !empty($data->visible) ? 1 : 0,
            'fileitemid' => (int)($data->fileitemid ?? 0),
            'timemodified' => $now,
        ];
        if (!empty($data->id)) {
            $record->id = (int)$data->id;
            $DB->update_record('local_ga_materials', self::filter_record_to_existing_fields('local_ga_materials', $record));
            return $record->id;
        }
        $record->timecreated = $now;
        return $DB->insert_record('local_ga_materials', self::filter_record_to_existing_fields('local_ga_materials', $record));
    }

    public static function delete_material(int $id): bool {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_materials'))) {
            return false;
        }
        $DB->delete_records('local_ga_materials', ['id' => $id]);
        return true;
    }

    public static function get_material(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_ga_materials', ['id' => $id], '*', MUST_EXIST);
    }

    public static function mark_edition_completed(int $editionid, int $userid): bool {
        global $DB;
        $edition = self::get_workshop_edition($editionid);
        $edition->completed = 1;
        $edition->timecompleted = time();
        $edition->completedby = $userid;
        $edition->status = 'completed';
        $edition->timemodified = time();
        $DB->update_record('local_ga_workshop_editions', self::filter_record_to_existing_fields('local_ga_workshop_editions', $edition));
        return true;
    }



    
    public static function cleanup_all_generated_course_entries(int $courseid = 0): \stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $summary = (object)[
            'courses' => 0,
            'modulesdeleted' => 0,
            'sectionshidden' => 0,
            'activitieshidden' => 0,
            'messages' => [],
        ];

        $params = [];
        $where = '';
        if ($courseid > 0) {
            $where = 'WHERE courseid = :courseid';
            $params['courseid'] = $courseid;
        }

        $workshops = $DB->get_records_sql("SELECT * FROM {local_ga_workshops} $where ORDER BY courseid ASC, id ASC", $params);
        $courseids = [];

        foreach ($workshops as $workshop) {
            $courseids[(int)$workshop->courseid] = true;
        }

        foreach (array_keys($courseids) as $cid) {
            $summary->courses++;
            $course = $DB->get_record('course', ['id' => $cid], '*', IGNORE_MISSING);
            if (!$course) {
                continue;
            }

            $workshopsincourse = array_filter($workshops, function($w) use ($cid) {
                return (int)$w->courseid === (int)$cid;
            });

            foreach ($workshopsincourse as $workshop) {
                try {
                    if (!empty($workshop->requiredcmid)) {
                        if (self::hide_activity_from_course_page((int)$workshop->requiredcmid)) {
                            $summary->activitieshidden++;
                        }
                    }

                    // Hide assignments/quizzes likely created for this workshop.
                    $summary->activitieshidden += self::hide_candidate_workshop_activities_from_course_page($workshop);

                    // Keep old label cleanup if function exists in this branch.
                    if (method_exists(__CLASS__, 'delete_workshop_course_entries')) {
                        $summary->modulesdeleted += self::delete_workshop_course_entries($workshop);
                    }
                } catch (\Throwable $e) {
                    $summary->messages[] = 'No se pudo limpiar elementos del taller ' . $workshop->id . ': ' . $e->getMessage();
                }
            }

            // Hide old empty generated sections if possible. Do not delete non-empty sections.
            $generatednames = [self::get_archive_workshop_section_name()];
            foreach ($workshopsincourse as $workshop) {
                if (method_exists(__CLASS__, 'get_workshop_section_name')) {
                    $generatednames[] = self::get_workshop_section_name($workshop);
                }
            }

            $sections = $DB->get_records('course_sections', ['course' => $cid]);
            foreach ($sections as $section) {
                $name = trim((string)($section->name ?? ''));
                if (!in_array($name, $generatednames, true)) {
                    continue;
                }
                if (!empty($section->sequence)) {
                    continue;
                }
                try {
                    $section->visible = 0;
                    $section->timemodified = time();
                    $DB->update_record('course_sections', $section);
                    $summary->sectionshidden++;
                } catch (\Throwable $e) {
                    $summary->messages[] = 'No se pudo ocultar sección ' . $name . ': ' . $e->getMessage();
                }
            }

            try { $summary->activitieshidden += self::hide_finished_workshop_cards_in_course((int)$cid); } catch (\Throwable $e) { }
            try { $summary->activitieshidden += self::hard_archive_required_activities_in_course((int)$cid); } catch (\Throwable $e) { }
            rebuild_course_cache($cid, true);
        }

        return $summary;
    }



    public static function list_edition_enrolled_users_ultrasafe(int $editionid): array {
        global $DB;

        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_edition_enrolments'))) {
            return [];
        }

        $records = $DB->get_records('local_ga_edition_enrolments', ['editionid' => $editionid], 'id ASC');
        $out = [];

        foreach ($records as $r) {
            $status = isset($r->status) ? (string)$r->status : 'enrolled';
            if ($status !== '' && !in_array($status, ['enrolled', 'attended', 'manual'], true)) {
                continue;
            }

            if (empty($r->userid)) {
                continue;
            }

            $u = $DB->get_record('user', ['id' => (int)$r->userid, 'deleted' => 0], 'id, firstname, lastname, email', IGNORE_MISSING);
            if (!$u) {
                continue;
            }

            $row = new \stdClass();
            $row->enrolmentid = (int)$r->id;
            $row->userid = (int)$u->id;
            $row->firstname = $u->firstname;
            $row->lastname = $u->lastname;
            $row->email = $u->email;
            $row->status = $status ?: 'enrolled';
            $row->source = isset($r->source) ? $r->source : '';
            $row->attended = (!empty($r->attended) || $status === 'attended') ? 1 : 0;
            $out[] = $row;
        }

        usort($out, function($a, $b) {
            return strcasecmp($a->lastname . ' ' . $a->firstname, $b->lastname . ' ' . $b->firstname);
        });

        return $out;
    }

    public static function store_material_upload(int $coursecontextid, int $itemid, string $inputname): int {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        if (empty($_FILES[$inputname]) || empty($_FILES[$inputname]['tmp_name']) || !is_uploaded_file($_FILES[$inputname]['tmp_name'])) {
            return $itemid;
        }

        if ($itemid <= 0) {
            $itemid = time() + random_int(1000, 999999);
        }

        $fs = get_file_storage();
        $fs->delete_area_files($coursecontextid, 'local_gestion_actividades', 'material', $itemid);

        $filename = clean_param($_FILES[$inputname]['name'], PARAM_FILE);
        if ($filename === '') {
            $filename = 'material';
        }

        $filerecord = [
            'contextid' => $coursecontextid,
            'component' => 'local_gestion_actividades',
            'filearea' => 'material',
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => $filename,
            'mimetype' => 'application/pdf',
        ];

        $fs->create_file_from_pathname($filerecord, $_FILES[$inputname]['tmp_name']);
        return $itemid;
    }



    public static function set_enrolment_attendance(int $enrolmentid, bool $attended, int $userid): bool {
        global $DB;

        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_edition_enrolments'))) {
            return false;
        }

        $columns = $DB->get_columns('local_ga_edition_enrolments');
        $record = $DB->get_record('local_ga_edition_enrolments', ['id' => $enrolmentid], '*', MUST_EXIST);

        // Always store a visible state in status, because this field exists in older installs.
        if (isset($columns['status'])) {
            $record->status = $attended ? 'attended' : 'enrolled';
        }

        if (isset($columns['attended'])) {
            $record->attended = $attended ? 1 : 0;
        }
        if (isset($columns['timeattended'])) {
            $record->timeattended = $attended ? time() : 0;
        }
        if (isset($columns['attendedby'])) {
            $record->attendedby = $attended ? $userid : 0;
        }
        if (isset($columns['timemodified'])) {
            $record->timemodified = time();
        }

        $DB->update_record('local_ga_edition_enrolments', self::filter_record_to_existing_fields('local_ga_edition_enrolments', $record));
        return true;
    }

    public static function detect_required_activity_type(\stdClass $edition): string {
        // Try all known/possible field names used by previous alpha versions.
        foreach ([
            'requiredactivitytype',
            'required_activity_type',
            'activitytype',
            'activity_type',
            'completiontype',
            'completion_type',
            'requiredtype',
            'required_type',
            'tasktype',
            'task_type',
            'modtype',
            'modulename',
            'requiredmod',
            'requiredmodule'
        ] as $field) {
            if (!empty($edition->$field)) {
                $value = strtolower(trim((string)$edition->$field));
                if (strpos($value, 'quiz') !== false || strpos($value, 'cuestion') !== false || strpos($value, 'test') !== false) {
                    return 'quiz';
                }
                if (strpos($value, 'assign') !== false || strpos($value, 'tarea') !== false || strpos($value, 'task') !== false || strpos($value, 'entrega') !== false) {
                    return 'assign';
                }
            }
        }

        foreach (['createquiz', 'autoquiz', 'quizrequired', 'hasquiz'] as $field) {
            if (!empty($edition->$field)) {
                return 'quiz';
            }
        }

        foreach (['createassignment', 'createassign', 'autocreateactivity', 'autoassign', 'assignmentrequired', 'hasassignment', 'hastask'] as $field) {
            if (!empty($edition->$field)) {
                return 'assign';
            }
        }

        // In this workflow, if the edition reaches this page it means the workshop was configured
        // to require an activity. The safe default agreed for the simulation is "tarea".
        return 'assign';
    }

    public static function update_edition_required_cmid(int $editionid, int $cmid): bool {
        global $DB;
        $edition = self::get_workshop_edition($editionid);
        $edition->requiredcmid = $cmid;
        $edition->timemodified = time();
        $DB->update_record('local_ga_workshop_editions', self::filter_record_to_existing_fields('local_ga_workshop_editions', $edition));
        return true;
    }



    public static function find_candidate_required_activities(\stdClass $edition): array {
        global $DB;

        $workshop = self::get_workshop((int)$edition->workshopid);
        $courseid = (int)$workshop->courseid;
        $type = self::detect_required_activity_type($edition);
        if ($type === '') {
            $type = 'assign';
        }
        if ($type !== 'quiz') {
            $type = 'assign';
        }

        $records = [];
        if ($type === 'assign' && $DB->get_manager()->table_exists(new \xmldb_table('assign'))) {
            $sql = "SELECT cm.id AS cmid,
                           cm.added,
                           m.name AS modname,
                           a.name AS activityname
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                      JOIN {assign} a ON a.id = cm.instance
                     WHERE cm.course = :courseid
                       AND m.name = 'assign'
                       AND cm.deletioninprogress = 0
                  ORDER BY cm.added DESC, cm.id DESC";
            $records = $DB->get_records_sql($sql, ['courseid' => $courseid], 0, 50);
        } else if ($type === 'quiz' && $DB->get_manager()->table_exists(new \xmldb_table('quiz'))) {
            $sql = "SELECT cm.id AS cmid,
                           cm.added,
                           m.name AS modname,
                           q.name AS activityname
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                      JOIN {quiz} q ON q.id = cm.instance
                     WHERE cm.course = :courseid
                       AND m.name = 'quiz'
                       AND cm.deletioninprogress = 0
                  ORDER BY cm.added DESC, cm.id DESC";
            $records = $DB->get_records_sql($sql, ['courseid' => $courseid], 0, 50);
        }

        $candidates = [];
        foreach ($records as $r) {
            $activityname = (string)($r->activityname ?? '');
            if ($activityname === '') {
                continue;
            }

            $hay = \core_text::strtolower($activityname);
            $needle1 = \core_text::strtolower((string)$workshop->name);
            $needle2 = \core_text::strtolower((string)$workshop->code);
            $needle3 = \core_text::strtolower('taller');

            if (strpos($hay, $needle1) !== false || strpos($hay, $needle2) !== false || strpos($hay, $needle3) !== false) {
                $candidates[$r->cmid] = $r;
            }
        }

        // Si no hay coincidencia por nombre, devuelve las últimas actividades de ese tipo.
        // Así se puede vincular la recién creada sin que el nombre sea perfecto.
        if (!$candidates) {
            foreach ($records as $r) {
                $candidates[$r->cmid] = $r;
            }
        }

        return $candidates;
    }

    public static function link_required_activity_to_edition(int $editionid, int $cmid): bool {
        global $DB;

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop((int)$edition->workshopid);
        if (!$DB->record_exists('course_modules', ['id' => $cmid, 'course' => (int)$workshop->courseid])) {
            return false;
        }

        self::update_edition_required_cmid($editionid, $cmid);
        self::restrict_required_activity_to_edition_group($editionid, $cmid);
        self::hard_archive_cmid_from_course_page((int)$cmid);
        self::hard_archive_required_activities_in_course((int)$workshop->courseid);
        self::add_workshop_backlink_to_required_activity($editionid, $cmid);

        return true;
    }



    public static function hide_activity_from_course_page(int $cmid): bool {
        global $DB, $CFG;

        if (!$DB->record_exists('course_modules', ['id' => $cmid])) {
            return false;
        }

        $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', MUST_EXIST);
        $columns = $DB->get_columns('course_modules');
        $changed = false;

        if (isset($columns['visibleoncoursepage'])) {
            $cm->visibleoncoursepage = 0;
            $changed = true;
        }

        // Do not set visible=0, because the activity must remain accessible from the workshop.
        if ($changed) {
            $DB->update_record('course_modules', self::filter_record_to_existing_fields('course_modules', $cm));
            require_once($CFG->dirroot . '/course/lib.php');
            rebuild_course_cache((int)$cm->course, true);
        }

        return $changed;
    }

    public static function hide_candidate_workshop_activities_from_course_page(\stdClass $workshop): int {
        global $DB, $CFG;

        $hidden = 0;
        $courseid = (int)$workshop->courseid;

        $sql = "SELECT cm.id AS cmid,
                       cm.course,
                       cm.section,
                       m.name AS modname,
                       COALESCE(a.name, q.name) AS activityname,
                       cs.name AS sectionname
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {course_sections} cs ON cs.id = cm.section
             LEFT JOIN {assign} a ON a.id = cm.instance AND m.name = 'assign'
             LEFT JOIN {quiz} q ON q.id = cm.instance AND m.name = 'quiz'
                 WHERE cm.course = :courseid
                   AND m.name IN ('assign', 'quiz')
                   AND cm.deletioninprogress = 0
              ORDER BY cm.added DESC, cm.id DESC";

        $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        $mainsection = self::get_main_workshop_section_name();
        $wname = \core_text::strtolower((string)$workshop->name);
        $wcode = \core_text::strtolower((string)$workshop->code);

        foreach ($records as $r) {
            $activityname = \core_text::strtolower((string)($r->activityname ?? ''));
            $sectionname = \core_text::strtolower((string)($r->sectionname ?? ''));

            $matches = false;

            if ($activityname !== '' && (
                strpos($activityname, $wname) !== false ||
                strpos($activityname, $wcode) !== false ||
                strpos($activityname, 'taller') !== false
            )) {
                $matches = true;
            }

            if ($sectionname === \core_text::strtolower($mainsection)) {
                $matches = true;
            }

            if ($matches && self::hide_activity_from_course_page((int)$r->cmid)) {
                $hidden++;
            }
        }

        if ($hidden > 0) {
            require_once($CFG->dirroot . '/course/lib.php');
            rebuild_course_cache($courseid, true);
        }

        return $hidden;
    }



    public static function restrict_required_activity_to_edition_group(int $editionid, int $cmid): bool {
        global $DB, $CFG;

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop((int)$edition->workshopid);
        $courseid = (int)$workshop->courseid;

        $groupid = self::get_or_create_edition_group($editionid);
        self::sync_edition_group_members($editionid);
        $groupingid = self::get_or_create_edition_grouping($editionid, $groupid);

        $cm = $DB->get_record('course_modules', ['id' => $cmid, 'course' => $courseid], '*', IGNORE_MISSING);
        if (!$cm) {
            return false;
        }

        $columns = $DB->get_columns('course_modules');

        if (isset($columns['groupmode'])) {
            $cm->groupmode = SEPARATEGROUPS;
        }
        if (isset($columns['groupingid'])) {
            $cm->groupingid = $groupingid;
        }
        if (isset($columns['availability'])) {
            $availability = [
                'op' => '&',
                'c' => [
                    [
                        'type' => 'group',
                        'id' => $groupid,
                    ],
                ],
                'showc' => [false],
            ];
            $cm->availability = json_encode($availability);
        }

        // Best Moodle stealth behaviour: visible, but not shown on course page.
        if (isset($columns['visible'])) {
            $cm->visible = 1;
        }
        if (isset($columns['visibleold'])) {
            $cm->visibleold = 1;
        }
        if (isset($columns['visibleoncoursepage'])) {
            $cm->visibleoncoursepage = 0;
        }

        $DB->update_record('course_modules', self::filter_record_to_existing_fields('course_modules', $cm));

        // Also try to configure assign plugin itself with separate groups/grouping when fields exist.
        try {
            $module = $DB->get_record('modules', ['id' => $cm->module], 'id,name', MUST_EXIST);
            if ($module->name === 'assign' && $DB->record_exists('assign', ['id' => $cm->instance])) {
                $assign = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
                $assigncolumns = $DB->get_columns('assign');
                if (isset($assigncolumns['teamsubmission'])) {
                    $assign->teamsubmission = 0;
                }
                if (isset($assigncolumns['requiresubmissionstatement'])) {
                    $assign->requiresubmissionstatement = 0;
                }
                $DB->update_record('assign', self::filter_record_to_existing_fields('assign', $assign));
            }
        } catch (\Throwable $e) {
            // Non-fatal.
        }

        require_once($CFG->dirroot . '/course/lib.php');
        rebuild_course_cache($courseid, true);

        return true;
    }



    public static function get_or_create_edition_group(int $editionid): int {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop((int)$edition->workshopid);
        $courseid = (int)$workshop->courseid;

        if (!empty($edition->groupid) && $DB->record_exists('groups', ['id' => (int)$edition->groupid, 'courseid' => $courseid])) {
            return (int)$edition->groupid;
        }

        $editioncode = '';
        foreach (['editioncode', 'code', 'name'] as $f) {
            if (!empty($edition->$f)) {
                $editioncode = clean_param((string)$edition->$f, PARAM_TEXT);
                break;
            }
        }
        if ($editioncode === '') {
            $editioncode = 'E' . (int)$edition->id;
        }

        $groupname = trim('Taller ' . (string)$workshop->code . ' - ' . (string)$workshop->name . ' - ' . $editioncode);
        if (\core_text::strlen($groupname) > 250) {
            $groupname = \core_text::substr($groupname, 0, 250);
        }

        $existing = $DB->get_record('groups', ['courseid' => $courseid, 'name' => $groupname], '*', IGNORE_MISSING);
        if ($existing) {
            $groupid = (int)$existing->id;
        } else {
            $group = new \stdClass();
            $group->courseid = $courseid;
            $group->name = $groupname;
            $group->description = get_string('editiongroupdescription', 'local_gestion_actividades', $workshop->name);
            $group->descriptionformat = FORMAT_HTML;
            $groupid = groups_create_group($group);
        }

        $edition->groupid = $groupid;
        $edition->timemodified = time();
        $DB->update_record('local_ga_workshop_editions', self::filter_record_to_existing_fields('local_ga_workshop_editions', $edition));

        return $groupid;
    }

    public static function sync_edition_group_members(int $editionid): int {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $groupid = self::get_or_create_edition_group($editionid);
        $users = self::list_edition_enrolled_users_ultrasafe($editionid);

        $added = 0;
        foreach ($users as $user) {
            if (empty($user->userid)) {
                continue;
            }
            if (!groups_is_member($groupid, (int)$user->userid)) {
                groups_add_member($groupid, (int)$user->userid);
                $added++;
            }
        }

        return $added;
    }

    public static function get_or_create_edition_grouping(int $editionid, int $groupid): int {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop((int)$edition->workshopid);
        $courseid = (int)$workshop->courseid;

        $group = $DB->get_record('groups', ['id' => $groupid], '*', MUST_EXIST);
        $groupingname = 'Agrupación ' . $group->name;
        if (\core_text::strlen($groupingname) > 250) {
            $groupingname = \core_text::substr($groupingname, 0, 250);
        }

        $grouping = $DB->get_record('groupings', ['courseid' => $courseid, 'name' => $groupingname], '*', IGNORE_MISSING);
        if ($grouping) {
            $groupingid = (int)$grouping->id;
        } else {
            $new = new \stdClass();
            $new->courseid = $courseid;
            $new->name = $groupingname;
            $new->description = get_string('editiongroupingdescription', 'local_gestion_actividades', $workshop->name);
            $new->descriptionformat = FORMAT_HTML;
            $groupingid = groups_create_grouping($new);
        }

        if (!$DB->record_exists('groupings_groups', ['groupingid' => $groupingid, 'groupid' => $groupid])) {
            groups_assign_grouping($groupingid, $groupid);
        }

        return $groupingid;
    }



    public static function get_primary_workshop_edition_for_user_view(int $workshopid): ?\stdClass {
        global $DB;
        if ($DB->get_manager()->table_exists(new \xmldb_table('local_ga_workshop_editions'))) {
            $records = $DB->get_records('local_ga_workshop_editions', ['workshopid' => $workshopid], 'id ASC', '*', 0, 1);
            if ($records) {
                return reset($records);
            }
        }
        return null;
    }

    public static function get_user_edition_enrolment(int $editionid, int $userid): ?\stdClass {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_edition_enrolments'))) {
            return null;
        }
        return $DB->get_record('local_ga_edition_enrolments', ['editionid' => $editionid, 'userid' => $userid], '*', IGNORE_MISSING) ?: null;
    }

    public static function is_user_attended_edition(int $editionid, int $userid): bool {
        $record = self::get_user_edition_enrolment($editionid, $userid);
        if (!$record) {
            return false;
        }
        if (!empty($record->attended)) {
            return true;
        }
        if (isset($record->status) && (string)$record->status === 'attended') {
            return true;
        }
        return false;
    }

    public static function add_workshop_backlink_to_required_activity(int $editionid, int $cmid): bool {
        global $DB, $CFG;

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop((int)$edition->workshopid);
        $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', IGNORE_MISSING);
        if (!$cm) {
            return false;
        }
        $module = $DB->get_record('modules', ['id' => (int)$cm->module], 'id,name', IGNORE_MISSING);
        if (!$module) {
            return false;
        }

        $url = new \moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $workshop->id]);
        $backhtml = \html_writer::div(
            \html_writer::link($url, get_string('backtoworkshopfromactivity', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']),
            'local-ga-backtoworkshop',
            ['style' => 'margin: 0 0 1rem 0;']
        );

        try {
            if ($module->name === 'assign' && $DB->record_exists('assign', ['id' => (int)$cm->instance])) {
                $assign = $DB->get_record('assign', ['id' => (int)$cm->instance], '*', MUST_EXIST);
                $intro = (string)($assign->intro ?? '');
                if (strpos($intro, 'local-ga-backtoworkshop') === false) {
                    $assign->intro = $backhtml . $intro;
                    $assign->introformat = FORMAT_HTML;
                    $DB->update_record('assign', self::filter_record_to_existing_fields('assign', $assign));
                    rebuild_course_cache((int)$workshop->courseid, true);
                }
                return true;
            }

            if ($module->name === 'quiz' && $DB->record_exists('quiz', ['id' => (int)$cm->instance])) {
                $quiz = $DB->get_record('quiz', ['id' => (int)$cm->instance], '*', MUST_EXIST);
                $intro = (string)($quiz->intro ?? '');
                if (strpos($intro, 'local-ga-backtoworkshop') === false) {
                    $quiz->intro = $backhtml . $intro;
                    $quiz->introformat = FORMAT_HTML;
                    $DB->update_record('quiz', self::filter_record_to_existing_fields('quiz', $quiz));
                    rebuild_course_cache((int)$workshop->courseid, true);
                }
                return true;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    public static function archive_finished_workshop_edition(int $editionid): bool {
        global $DB, $CFG;

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop((int)$edition->workshopid);
        $courseid = (int)$workshop->courseid;

        $columns = $DB->get_columns('local_ga_workshop_editions');
        if (isset($columns['status'])) {
            $edition->status = 'finished';
        }
        if (isset($columns['timemodified'])) {
            $edition->timemodified = time();
        }
        $DB->update_record('local_ga_workshop_editions', self::filter_record_to_existing_fields('local_ga_workshop_editions', $edition));

        if (!empty($edition->requiredcmid)) {
            try {
                self::hard_archive_cmid_from_course_page((int)$edition->requiredcmid);
            } catch (\Throwable $e) {
                // Non-fatal.
            }
        }

        try {
            self::hard_archive_workshop_course_entries($workshop);
            self::hard_archive_required_activities_in_course($courseid);
        } catch (\Throwable $e) {
            // Non-fatal.
        }

        require_once($CFG->dirroot . '/course/lib.php');
        rebuild_course_cache($courseid, true);

        return true;
    }



    public static function is_edition_finished(\stdClass $edition): bool {
        if (!empty($edition->status) && in_array((string)$edition->status, ['finished', 'archived', 'closed_finished'], true)) {
            return true;
        }
        if (!empty($edition->sessiondate) && (int)$edition->sessiondate < time() && !empty($edition->autoarchive)) {
            return true;
        }
        return false;
    }

    public static function get_active_editions_for_workshop(int $workshopid): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_workshop_editions'))) {
            return [];
        }
        $editions = $DB->get_records('local_ga_workshop_editions', ['workshopid' => $workshopid], 'id ASC');
        $out = [];
        foreach ($editions as $e) {
            if (!self::is_edition_finished($e)) {
                $out[$e->id] = $e;
            }
        }
        return $out;
    }

    public static function render_workshop_course_card(\stdClass $workshop, ?\stdClass $edition = null): string {
        global $DB;

        $url = new \moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $workshop->id]);

        $hours = !empty($workshop->hours) ? s($workshop->hours) . ' h' : '-';
        $desc = trim((string)($workshop->description ?? ''));
        $date = '';
        $places = '';

        if ($edition) {
            if (!empty($edition->sessiondate)) {
                $date = userdate((int)$edition->sessiondate, get_string('strftimedatefullshort', 'langconfig'));
            }
            if (!empty($edition->places)) {
                $enrolled = 0;
                try {
                    $enrolled = count(self::list_edition_enrolled_users_ultrasafe((int)$edition->id));
                } catch (\Throwable $e) {
                    $enrolled = 0;
                }
                $remaining = max(0, (int)$edition->places - $enrolled);
                $places = $remaining . ' ' . get_string('remainingplacesplain', 'local_gestion_actividades');
            }
        }

        $meta = [];
        if ($date !== '') {
            $meta[] = html_writer::span(get_string('date', 'local_gestion_actividades') . ': ', 'local-ga-meta-label') . s($date);
        }
        $meta[] = html_writer::span(get_string('workshophours', 'local_gestion_actividades') . ': ', 'local-ga-meta-label') . $hours;
        if ($places !== '') {
            $meta[] = html_writer::span(get_string('places', 'local_gestion_actividades') . ': ', 'local-ga-meta-label') . s($places);
        }

        $html = html_writer::start_div('local-ga-course-card', [
            'style' => 'border-left:4px solid #0f6cbf;background:#f7f9fb;padding:18px 20px;margin:14px 0;border-radius:10px;'
        ]);
        $html .= html_writer::tag('h4', s($workshop->code . ' - ' . $workshop->name), [
            'style' => 'margin:0 0 6px 0;font-weight:700;'
        ]);
        if ($desc !== '') {
            $html .= html_writer::tag('p', s($desc), ['style' => 'margin:0 0 10px 0;']);
        }
        $html .= html_writer::div(implode(' · ', $meta), 'local-ga-course-meta', [
            'style' => 'margin:0 0 12px 0;color:#1f2d3d;'
        ]);
        $html .= html_writer::link($url, get_string('viewworkshop', 'local_gestion_actividades'), [
            'class' => 'btn btn-secondary btn-sm',
            'style' => 'margin-right:8px;'
        ]);
        if ($edition && method_exists(__CLASS__, 'get_user_edition_enrolment')) {
            global $USER;
            try {
                $enrol = self::get_user_edition_enrolment((int)$edition->id, (int)$USER->id);
                if ($enrol) {
                    $html .= html_writer::span(get_string('alreadyenrolledshort', 'local_gestion_actividades'), 'badge badge-success', ['style' => 'padding:8px 10px;']);
                } else {
                    $enrolurl = new \moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $workshop->id, 'enrol' => 1, 'sesskey' => sesskey()]);
                    $html .= html_writer::link($enrolurl, get_string('enrolme', 'local_gestion_actividades'), ['class' => 'btn btn-primary btn-sm']);
                }
            } catch (\Throwable $e) {
                // Ignore.
            }
        }
        $html .= html_writer::end_div();

        return $html;
    }

    public static function hide_finished_workshop_cards_in_course(int $courseid): int {
        global $DB, $CFG;

        $hidden = 0;
        $workshops = $DB->get_records('local_ga_workshops', ['courseid' => $courseid]);
        if (!$workshops) {
            return 0;
        }

        foreach ($workshops as $workshop) {
            $active = self::get_active_editions_for_workshop((int)$workshop->id);
            if ($active) {
                continue;
            }
            $hidden += self::hard_archive_workshop_course_entries($workshop);
        }

        require_once($CFG->dirroot . '/course/lib.php');
        rebuild_course_cache($courseid, true);

        return $hidden;
    }



    public static function remove_cmid_from_course_section_sequence(int $cmid): bool {
        global $DB, $CFG;

        $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', IGNORE_MISSING);
        if (!$cm) {
            return false;
        }

        $section = $DB->get_record('course_sections', ['id' => (int)$cm->section], '*', IGNORE_MISSING);
        if (!$section) {
            return false;
        }

        $sequence = trim((string)($section->sequence ?? ''));
        if ($sequence === '') {
            return false;
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $sequence)), function($value) use ($cmid) {
            return $value !== '' && (int)$value !== (int)$cmid;
        }));

        $newsequence = implode(',', $parts);
        if ($newsequence === $sequence) {
            return false;
        }

        $section->sequence = $newsequence;
        $section->timemodified = time();
        $DB->update_record('course_sections', $section);

        require_once($CFG->dirroot . '/course/lib.php');
        rebuild_course_cache((int)$cm->course, true);

        return true;
    }

    public static function hard_archive_cmid_from_course_page(int $cmid): bool {
        global $DB, $CFG;

        $changed = false;
        $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', IGNORE_MISSING);
        if (!$cm) {
            return false;
        }

        // Remove from section sequence: it disappears for students, teachers and admins on the course page.
        if (self::remove_cmid_from_course_section_sequence($cmid)) {
            $changed = true;
        }

        // Also mark as not shown on course page where supported.
        $columns = $DB->get_columns('course_modules');
        if (isset($columns['visibleoncoursepage'])) {
            $cm->visibleoncoursepage = 0;
            $changed = true;
        }
        // Keep visible=1 so direct links from the plugin/archive still work for users with access.
        if (isset($columns['visible'])) {
            $cm->visible = 1;
        }

        if ($changed) {
            $DB->update_record('course_modules', self::filter_record_to_existing_fields('course_modules', $cm));
            require_once($CFG->dirroot . '/course/lib.php');
            rebuild_course_cache((int)$cm->course, true);
        }

        return $changed;
    }

    public static function hard_archive_workshop_course_entries(\stdClass $workshop): int {
        global $DB, $CFG;

        $courseid = (int)$workshop->courseid;
        $removed = 0;

        $patterns = [
            '%' . $DB->sql_like_escape((string)$workshop->code) . '%',
            '%' . $DB->sql_like_escape((string)$workshop->name) . '%',
        ];

        $sql = "SELECT cm.id, cm.course, cm.module, cm.instance, m.name AS modname
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
             LEFT JOIN {label} l ON l.id = cm.instance AND m.name = 'label'
             LEFT JOIN {page} p ON p.id = cm.instance AND m.name = 'page'
             LEFT JOIN {assign} a ON a.id = cm.instance AND m.name = 'assign'
             LEFT JOIN {quiz} q ON q.id = cm.instance AND m.name = 'quiz'
                 WHERE cm.course = :courseid
                   AND cm.deletioninprogress = 0
                   AND (
                        (m.name = 'label' AND (" . $DB->sql_like('l.intro', ':p1', false) . " OR " . $DB->sql_like('l.name', ':p2', false) . "))
                     OR (m.name = 'page' AND (" . $DB->sql_like('p.content', ':p3', false) . " OR " . $DB->sql_like('p.name', ':p4', false) . "))
                     OR (m.name = 'assign' AND (" . $DB->sql_like('a.name', ':p5', false) . " OR " . $DB->sql_like('a.intro', ':p6', false) . "))
                     OR (m.name = 'quiz' AND (" . $DB->sql_like('q.name', ':p7', false) . " OR " . $DB->sql_like('q.intro', ':p8', false) . "))
                   )";

        foreach ($patterns as $pattern) {
            $mods = $DB->get_records_sql($sql, [
                'courseid' => $courseid,
                'p1' => $pattern,
                'p2' => $pattern,
                'p3' => $pattern,
                'p4' => $pattern,
                'p5' => $pattern,
                'p6' => $pattern,
                'p7' => $pattern,
                'p8' => $pattern,
            ]);
            foreach ($mods as $mod) {
                if (self::hard_archive_cmid_from_course_page((int)$mod->id)) {
                    $removed++;
                }
            }
        }

        require_once($CFG->dirroot . '/course/lib.php');
        rebuild_course_cache($courseid, true);

        return $removed;
    }



    public static function hard_archive_required_activities_in_course(int $courseid): int {
        global $DB, $CFG;

        $removed = 0;
        $mainsection = self::get_main_workshop_section_name();

        // 1) Remove every explicitly linked required activity from the visible course sequence.
        if ($DB->get_manager()->table_exists(new \xmldb_table('local_ga_workshop_editions'))) {
            $editions = $DB->get_records_sql(
                "SELECT e.*
                   FROM {local_ga_workshop_editions} e
                   JOIN {local_ga_workshops} w ON w.id = e.workshopid
                  WHERE w.courseid = :courseid",
                ['courseid' => $courseid]
            );
            foreach ($editions as $edition) {
                if (!empty($edition->requiredcmid)) {
                    if (self::hard_archive_cmid_from_course_page((int)$edition->requiredcmid)) {
                        $removed++;
                    }
                }
            }
        }

        // 2) Remove assignments/quizzes located inside the TALLERES TIPO A section.
        // These are workshop-internal activities and must not live as loose course activities.
        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {course_sections} cs ON cs.id = cm.section
                 WHERE cm.course = :courseid
                   AND cm.deletioninprogress = 0
                   AND m.name IN ('assign', 'quiz')
                   AND cs.name = :sectionname";
        $mods = $DB->get_records_sql($sql, ['courseid' => $courseid, 'sectionname' => $mainsection]);
        foreach ($mods as $mod) {
            if (self::hard_archive_cmid_from_course_page((int)$mod->id)) {
                $removed++;
            }
        }

        // 3) Remove assignments/quizzes that are restricted to an automatic workshop group/grouping.
        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
             LEFT JOIN {groupings} gg ON gg.id = cm.groupingid
                 WHERE cm.course = :courseid
                   AND cm.deletioninprogress = 0
                   AND m.name IN ('assign', 'quiz')
                   AND (
                        cm.availability LIKE :availability
                        OR gg.name LIKE :groupingname
                   )";
        $mods = $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'availability' => '%"type":"group"%',
            'groupingname' => 'Agrupación Taller%',
        ]);
        foreach ($mods as $mod) {
            if (self::hard_archive_cmid_from_course_page((int)$mod->id)) {
                $removed++;
            }
        }

        require_once($CFG->dirroot . '/course/lib.php');
        rebuild_course_cache($courseid, true);

        return $removed;
    }



    public static function get_default_certificate_template(): string {
        return '<p>Se certifica que <strong>{alumno}</strong> ha participado y completado satisfactoriamente el taller <strong>{taller}</strong>, realizado el día <strong>{fecha}</strong>, con una duración de <strong>{horas}</strong> horas, dentro del programa de <strong>Talleres Tipo A</strong>.</p>';
    }

    public static function get_certificate_template_html(): string {
        $value = get_config('local_gestion_actividades', 'certificatetemplatehtml');
        if ($value === false || trim((string)$value) === '') {
            return self::get_default_certificate_template();
        }
        return (string)$value;
    }

    public static function save_certificate_template_html(string $html): void {
        set_config('certificatetemplatehtml', $html, 'local_gestion_actividades');
    }

    

    public static function user_submitted_required_activity(int $userid, int $cmid): bool {
        global $DB;
        if ($cmid <= 0) {
            return true;
        }

        $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', IGNORE_MISSING);
        if (!$cm) {
            return false;
        }
        $module = $DB->get_record('modules', ['id' => (int)$cm->module], 'id,name', IGNORE_MISSING);
        if (!$module) {
            return false;
        }

        if ($module->name === 'assign') {
            if (!$DB->get_manager()->table_exists(new \xmldb_table('assign_submission'))) {
                return false;
            }
            return $DB->record_exists_select(
                'assign_submission',
                'assignment = :assignment AND userid = :userid AND status = :status',
                [
                    'assignment' => (int)$cm->instance,
                    'userid' => $userid,
                    'status' => 'submitted',
                ]
            );
        }

        if ($module->name === 'quiz') {
            if (!$DB->get_manager()->table_exists(new \xmldb_table('quiz_attempts'))) {
                return false;
            }
            return $DB->record_exists_select(
                'quiz_attempts',
                'quiz = :quiz AND userid = :userid AND state = :state',
                [
                    'quiz' => (int)$cm->instance,
                    'userid' => $userid,
                    'state' => 'finished',
                ]
            );
        }

        return self::user_completed_required_activity($userid, $cmid);
    }

    public static function user_is_certificate_eligible(int $editionid, int $userid): bool {
        $edition = self::get_workshop_edition($editionid);
        $attended = self::is_user_attended_edition($editionid, $userid);
        if (!$attended) {
            return false;
        }
        $cmid = !empty($edition->requiredcmid) ? (int)$edition->requiredcmid : 0;
        if ($cmid <= 0) {
            return true;
        }
        return self::user_completed_required_activity($userid, $cmid) || self::user_submitted_required_activity($userid, $cmid);
    }

    public static function replace_certificate_placeholders(string $html, \stdClass $user, \stdClass $workshop, \stdClass $edition, string $certcode): string {
        $hours = !empty($workshop->hours) ? (string)(float)$workshop->hours : '';
        $date = !empty($edition->sessiondate) ? userdate((int)$edition->sessiondate, get_string('strftimedatefullshort', 'langconfig')) : userdate(time(), get_string('strftimedatefullshort', 'langconfig'));
        $replacements = [
            '{alumno}' => fullname($user),
            '{taller}' => (string)$workshop->name,
            '{codigo_taller}' => (string)$workshop->code,
            '{fecha}' => $date,
            '{horas}' => $hours,
            '{curso_academico}' => userdate(time(), '%Y'),
            '{fecha_emision}' => userdate(time(), get_string('strftimedatefullshort', 'langconfig')),
            '{codigo_certificado}' => $certcode,
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    public static function render_certificate_pdf_string(\stdClass $user, \stdClass $workshop, \stdClass $edition, string $certcode): string {
        global $CFG;

        require_once($CFG->libdir . '/pdflib.php');

        $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Gestion_actividades');
        $pdf->SetAuthor('Universidad Católica de Valencia');
        $pdf->SetTitle(get_string('certificate', 'local_gestion_actividades') . ' - ' . fullname($user));
        $pdf->SetMargins(25, 25, 25);
        $pdf->SetAutoPageBreak(false, 20);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $bg = dirname(__DIR__, 2) . '/pix/certificate_ucv_bg.jpg';
        if (file_exists($bg)) {
            $pdf->Image($bg, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        }

        $green = [43, 75, 30];

        $pdf->SetTextColor($green[0], $green[1], $green[2]);
        $pdf->SetFont('helvetica', 'B', 22);
        $pdf->SetXY(25, 72);
        $pdf->Cell(160, 12, get_string('certificatetitle', 'local_gestion_actividades'), 0, 1, 'C');

        $template = self::get_certificate_template_html();
        $content = self::replace_certificate_placeholders($template, $user, $workshop, $edition, $certcode);

        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFont('helvetica', '', 13);
        $html = '<div style="font-size:13pt;line-height:1.55;text-align:justify;color:#222;">' . $content . '</div>';
        $pdf->writeHTMLCell(160, 0, 25, 100, $html, 0, 1, false, true, 'J', true);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetXY(25, 182);
        $pdf->MultiCell(160, 8, get_string('certificateissuedon', 'local_gestion_actividades') . ' ' . userdate(time(), get_string('strftimedatefullshort', 'langconfig')), 0, 'R');

        $pdf->SetDrawColor(80, 80, 80);
        $pdf->Line(125, 212, 180, 212);
        $pdf->SetXY(120, 214);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(65, 5, get_string('certificatesignatureplaceholder', 'local_gestion_actividades'), 0, 'C');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(25, 238);
        $pdf->Cell(160, 5, get_string('certificatecode', 'local_gestion_actividades') . ': ' . $certcode, 0, 1, 'C');

        return $pdf->Output('', 'S');
    }

    public static function generate_certificate_for_user(int $editionid, int $userid): ?\stdClass {
        global $DB;

        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_certificates'))) {
            return null;
        }

        if (!self::user_is_certificate_eligible($editionid, $userid)) {
            return null;
        }

        $existing = $DB->get_record('local_ga_certificates', ['editionid' => $editionid, 'userid' => $userid], '*', IGNORE_MISSING);
        if ($existing) {
            return $existing;
        }

        $edition = self::get_workshop_edition($editionid);
        $workshop = self::get_workshop((int)$edition->workshopid);
        $course = $DB->get_record('course', ['id' => (int)$workshop->courseid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        $context = \context_course::instance((int)$course->id);

        $now = time();
        $certcode = 'TA-' . (int)$editionid . '-' . (int)$userid . '-' . strtoupper(substr(sha1($editionid . ':' . $userid . ':' . $now), 0, 8));
        $filename = clean_filename('certificado_' . $workshop->code . '_' . $userid . '.pdf');

        $record = (object)[
            'userid' => $userid,
            'courseid' => (int)$course->id,
            'workshopid' => (int)$workshop->id,
            'editionid' => $editionid,
            'certcode' => $certcode,
            'filename' => $filename,
            'status' => 'generated',
            'timeissued' => $now,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $certid = $DB->insert_record('local_ga_certificates', $record);

        $pdf = self::render_certificate_pdf_string($user, $workshop, $edition, $certcode);

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_gestion_actividades', 'certificate', $certid);
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'local_gestion_actividades',
            'filearea' => 'certificate',
            'itemid' => $certid,
            'filepath' => '/',
            'filename' => $filename,
        ];
        $fs->create_file_from_string($filerecord, $pdf);

        return $DB->get_record('local_ga_certificates', ['id' => $certid], '*', MUST_EXIST);
    }

    public static function generate_certificates_for_edition(int $editionid): \stdClass {
        $summary = (object)['eligible' => 0, 'generated' => 0, 'existing' => 0, 'skipped' => 0];

        $students = self::list_edition_enrolled_users_ultrasafe($editionid);
        foreach ($students as $student) {
            if (self::user_is_certificate_eligible($editionid, (int)$student->userid)) {
                $summary->eligible++;
                $before = self::get_user_certificate_for_edition($editionid, (int)$student->userid);
                $cert = self::generate_certificate_for_user($editionid, (int)$student->userid);
                if ($cert && $before) {
                    $summary->existing++;
                } else if ($cert) {
                    $summary->generated++;
                }
            } else {
                $summary->skipped++;
            }
        }

        return $summary;
    }

    public static function get_user_certificate_for_edition(int $editionid, int $userid): ?\stdClass {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_certificates'))) {
            return null;
        }
        return $DB->get_record('local_ga_certificates', ['editionid' => $editionid, 'userid' => $userid], '*', IGNORE_MISSING) ?: null;
    }

    public static function list_user_certificates(int $userid): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_certificates'))) {
            return [];
        }
        $sql = "SELECT c.*, w.name AS workshopname, w.code AS workshopcode, w.hours, e.name AS editionname, e.sessiondate, co.fullname AS coursename
                  FROM {local_ga_certificates} c
                  JOIN {local_ga_workshops} w ON w.id = c.workshopid
                  JOIN {local_ga_workshop_editions} e ON e.id = c.editionid
                  JOIN {course} co ON co.id = c.courseid
                 WHERE c.userid = :userid
              ORDER BY c.timeissued DESC";
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    public static function list_edition_certificates(int $editionid): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_ga_certificates'))) {
            return [];
        }
        $sql = "SELECT c.*, u.firstname, u.lastname, u.email, w.name AS workshopname, w.code AS workshopcode
                  FROM {local_ga_certificates} c
                  JOIN {user} u ON u.id = c.userid
                  JOIN {local_ga_workshops} w ON w.id = c.workshopid
                 WHERE c.editionid = :editionid
              ORDER BY u.lastname, u.firstname";
        return $DB->get_records_sql($sql, ['editionid' => $editionid]);
    }


}

<?php
namespace local_gestion_actividades\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class user_upload_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'csvfile', get_string('userscsvfile', 'local_gestion_actividades'), null, [
            'accepted_types' => ['.csv', '.txt'],
            'maxbytes' => 20 * 1024 * 1024,
        ]);
        $mform->addHelpButton('csvfile', 'userscsvfile', 'local_gestion_actividades');
        $mform->addRule('csvfile', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'updateexisting', get_string('updateexistingusers', 'local_gestion_actividades'));
        $mform->setDefault('updateexisting', 0);
        $mform->addHelpButton('updateexisting', 'updateexistingusers', 'local_gestion_actividades');

        $this->add_action_buttons(true, get_string('processuserscsv', 'local_gestion_actividades'));
    }
}

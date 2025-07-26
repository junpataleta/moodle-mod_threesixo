<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_threesixo;

use cm_info;
use coding_exception;
use context_module;
use context_system;
use dml_exception;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Class for performing DB actions for the mod_threesixo activity module.
 *
 * @package mod_threesixo
 * @copyright  2017 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /** Rated question type. */
    const QTYPE_RATED = 0;
    /** Comment question type. */
    const QTYPE_COMMENT = 1;

    /** Status when a user has not yet provided feedback to another user. */
    const STATUS_PENDING = 0;
    /** Status when a user has begun providing feedback to another user. */
    const STATUS_IN_PROGRESS = 1;
    /** Status when a user has completed providing feedback to another user. */
    const STATUS_COMPLETE = 2;
    /** Status when a user has declined to provide feedback to another user. */
    const STATUS_DECLINED = 3;

    /** Move a question item up. */
    const MOVE_UP = 1;
    /** Move a question item down. */
    const MOVE_DOWN = 2;

    /** Indicates all course participants regardless of role are the participants of the feedback activity. */
    const PARTICIPANT_ROLE_ALL = 0;

    /** Indicates that the feedback instance is not yet ready to be completed by the participants. */
    const INSTANCE_NOT_READY = 0;
    /** Indicates that the feedback instance is now ready to be completed by the participants. */
    const INSTANCE_READY = 1;

    /** Closed to participants. Participants cannot view the feedback given to them. Only those with the capability.  */
    const RELEASING_NONE = 0;
    /** Open to participants. Participants can view the feedback given to them any time. */
    const RELEASING_OPEN = 1;
    /**
     * Manual release. Participants can view the feedback given to them when released by users who have the capability to manage
     * the 360 instance (e.g. teacher, manager, admin).
     */
    const RELEASING_MANUAL = 2;
    /** Release after the activity has closed. */
    const RELEASING_AFTER = 3;

    /** Do not allow participants to undo their declined feedback submissions. */
    const UNDO_DECLINE_DISALLOW = 0;
    /** Allow participants to undo their declined feedback submissions. */
    const UNDO_DECLINE_ALLOW = 1;

    /** Activity open event type. */
    const THREESIXO_EVENT_TYPE_OPEN = 'open';
    /** Activity close event type. */
    const THREESIXO_EVENT_TYPE_CLOSE = 'close';

    /** @var int Default minimum rating (Strongly disagree). */
    const RATING_MIN = 1;

    /** @var int Default maximum rating (Strongly agree). */
    const RATING_MAX = 6;

    /** @var int Not applicable. */
    const RATING_NA = 0;

    /**
     * Fetches the 360-degree feedback instance.
     *
     * @param int $threesixtyid The 360-degree feedback ID.
     * @return mixed
     * @throws dml_exception
     */
    public static function get_instance($threesixtyid) {
        global $DB;

        return $DB->get_record('threesixo', ['id' => $threesixtyid], '*', MUST_EXIST);
    }

    /**
     * Fetches the questions from the 360-degree feedback question bank.
     *
     * @param bool $ownquestions Whether to fetch only the questions created by the current user.
     * @return array
     */
    public static function get_questions(bool $ownquestions = true): array {
        global $DB, $USER;

        $params = null;
        if ($ownquestions) {
            $params = [
                'createdby' => $USER->id,
            ];
        }
        $questions = $DB->get_records('threesixo_question', $params, 'type ASC, question ASC');
        foreach ($questions as $question) {
            switch ($question->type) {
                case self::QTYPE_RATED:
                    $question->typeName = get_string('qtyperated', 'mod_threesixo');
                    break;
                case self::QTYPE_COMMENT:
                    $question->typeName = get_string('qtypecomment', 'mod_threesixo');
                    break;
                default:
                    break;
            }
            $question->canEdit = $USER->id == $question->createdby || self::can_edit_others_question($question);
            $question->canDelete = $USER->id == $question->createdby || self::can_delete_others_question($question);
        }

        return $questions;
    }

    /**
     * Get a question from its ID.
     *
     * @param int $questionid
     * @return stdClass
     */
    public static function get_question(int $questionid): stdClass {
        global $DB;
        return $DB->get_record('threesixo_question', ['id' => $questionid], '*', MUST_EXIST);
    }

    /**
     * Adds a question into the 360-degree feedback question bank.
     *
     * @param stdClass $data The question aata.
     * @return bool|int The ID of the inserted question item. False, otherwise.
     * @throws dml_exception
     */
    public static function add_question(stdClass $data) {
        global $DB, $USER;
        if (!isset($data->createdby)) {
            $data->createdby = $USER->id;
        }
        $data->editedby = $data->createdby;
        $data->timecreated = time();
        $data->timemodified = time();
        return $DB->insert_record('threesixo_question', $data);
    }

    /**
     * Updates a question in the 360-degree feedback question bank.
     *
     * @param stdClass $data The updated question aata.
     * @return bool
     * @throws dml_exception
     */
    public static function update_question(stdClass $data) {
        global $DB, $USER;
        if (!isset($data->editedby)) {
            $data->editedby = $USER->id;
        }
        $data->timemodified = time();
        return $DB->update_record('threesixo_question', $data);
    }

    /**
     * Deletes a question from the 360-degree feedback question bank.
     *
     * @param int $id The question ID.
     * @return bool
     * @throws dml_exception
     */
    public static function delete_question($id) {
        global $DB;
        return $DB->delete_records('threesixo_question', ['id' => $id]);
    }

    /**
     * Fetches the questions assigned to a 360-degree feedback instance.
     *
     * @param int $threesixtyid The 360-degree feedback ID.
     * @return array The results.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_items($threesixtyid) {
        global $DB;

        $sql = "SELECT i.id,
                       i.threesixo as threesixtyid,
                       i.question as questionid,
                       i.position,
                       q.question,
                       q.type
                  FROM {threesixo_item} i
            INNER JOIN {threesixo_question} q
                    ON i.question = q.id
                 WHERE i.threesixo = :threesixtyid
              ORDER BY i.position;";
        $params = [
            'threesixtyid' => $threesixtyid,
        ];

        $items = $DB->get_records_sql($sql, $params);
        foreach ($items as $item) {
            // Question type.
            $item->typetext = helper::get_question_type_text($item->type);
        }
        return $items;
    }

    /**
     * Fetches the user's responses to a feedback for a specific user.
     *
     * @param int $threesixtyid The 360-degree feedback ID.
     * @param int $fromuser The ID of the user who is responding to the feedback.
     * @param int $touser The user ID of the recipient of the feedback.
     * @return array The list of the user's responses.
     * @throws dml_exception
     */
    public static function get_responses($threesixtyid, $fromuser, $touser) {
        global $DB;

        $params = [
            'threesixo' => $threesixtyid,
            'fromuser' => $fromuser,
            'touser' => $touser,
        ];

        return $DB->get_records('threesixo_response', $params, 'item ASC', 'id, item, value');
    }

    /**
     * Sets the questions for the 360 activity.
     *
     * @param int $threesixtyid The 360 ID.
     * @param int[] $questionids The array of question IDs.
     * @return bool True on success. False, otherwise.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function set_items($threesixtyid, $questionids) {
        global $DB;

        // Delete existing, but were unselected, items.
        $select = 'threesixo = :threesixo';
        $params = ['threesixo' => $threesixtyid];
        if (!empty($questionids)) {
            $subselect = ' AND question NOT IN (';
            $index = 1;
            foreach ($questionids as $qid) {
                $key = 'q' . $qid;
                $params[$key] = $qid;
                $subselect .= ":$key";
                if ($index < count($questionids)) {
                    $subselect .= ',';
                }
                $index++;
            }
            $subselect .= ')';
            $select .= $subselect;
        }
        $DB->delete_records_select('threesixo_item', $select, $params);

        // Get remaining items.
        $existingitems = $DB->get_records('threesixo_item', ['threesixo' => $threesixtyid], 'position ASC');
        // Reorder positions.
        $position = 1;
        $selectedquestions = [];
        foreach ($existingitems as $existingitem) {
            if ($existingitem->position != $position) {
                $existingitem->position = $position;
                $DB->update_record('threesixo_item', $existingitem);
            }
            $position++;
            $selectedquestions[] = $existingitem->question;
        }

        // Records to be inserted.
        $records = [];
        foreach ($questionids as $id) {
            // No need to insert existing items.
            if (in_array($id, $selectedquestions)) {
                continue;
            }
            $data = new stdClass();
            $data->question = $id;
            $data->threesixo = $threesixtyid;
            $data->position = $position++;
            $records[] = $data;
        }
        $DB->insert_records('threesixo_item', $records);
        return true;
    }

    /**
     * Returns an array of question types with key as the question type and value as the question type text.
     *
     * @return array The list of question types.
     * @throws coding_exception
     */
    public static function get_question_types() {
        return [
            self::QTYPE_RATED => get_string('qtyperated', 'mod_threesixo'),
            self::QTYPE_COMMENT => get_string('qtypecomment', 'mod_threesixo'),
        ];
    }

    /**
     * Fetches an item from the 360-degree feedback instance by ID.
     *
     * @param int $itemid The item ID.
     * @return stdClass The item data.
     * @throws dml_exception
     */
    public static function get_item_by_id($itemid) {
        global $DB;
        return $DB->get_record('threesixo_item', ['id' => $itemid], '*', MUST_EXIST);
    }

    /**
     * Moves the item up.
     *
     * @param int $itemid The item ID.
     * @return bool
     * @throws moodle_exception
     */
    public static function move_item_up($itemid) {
        return self::move_item($itemid, self::MOVE_UP);
    }

    /**
     * Moves the item down.
     *
     * @param int $itemid The item ID.
     * @return bool
     * @throws moodle_exception
     */
    public static function move_item_down($itemid) {
        return self::move_item($itemid, self::MOVE_DOWN);
    }

    /**
     * Moves an item depending on the direction provided.
     *
     * @param int $itemid The item ID.
     * @param int $direction The move direction. 1 for up, 2 for down.
     * @return bool
     * @throws moodle_exception
     */
    protected static function move_item($itemid, $direction) {
        global $DB;
        $result = false;

        // Get the feedback item.
        if ($item = $DB->get_record('threesixo_item', ['id' => $itemid])) {
            $oldposition = $item->position;
            $itemcount = $DB->count_records('threesixo_item', ['threesixo' => $item->threesixo]);

            switch ($direction) {
                case self::MOVE_UP:
                    if ($item->position > 1) {
                        $item->position--;
                    }
                    break;
                case self::MOVE_DOWN:
                    if ($item->position < $itemcount) {
                        $item->position++;
                    }
                    break;
                default:
                    break;
            }
            // Update the item to be swapped.
            if ($swapitem = $DB->get_record('threesixo_item', ['threesixo' => $item->threesixo, 'position' => $item->position])) {
                $swapitem->position = $oldposition;
                $result = $DB->update_record('threesixo_item', $swapitem);
            }
            // Update the item being moved.
            $result = $result && $DB->update_record('threesixo_item', $item);
        } else {
            throw new moodle_exception('erroritemnotfound');
        }

        return $result;
    }

    /**
     * Deletes a question item from the 360 feedback activity.
     *
     * @param int $itemid The item ID.
     * @return bool
     * @throws dml_exception
     */
    public static function delete_item($itemid) {
        global $DB;
        $itemtobedeleted = $DB->get_record('threesixo_item', ['id' => $itemid], 'id, position, threesixo');
        if ($itemtobedeleted) {
            $select = 'position > :position AND threesixo = :threesixo';
            $params = [
                'position' => $itemtobedeleted->position,
                'threesixo' => $itemtobedeleted->threesixo,
            ];
            $itemstobemoved = $DB->get_recordset_select('threesixo_item', $select, $params, 'position ASC', 'id, position');
            if ($itemstobemoved->valid()) {
                $offset = 0;
                foreach ($itemstobemoved as $item) {
                    $newposition = $itemtobedeleted->position + $offset;
                    $item->position = $newposition;
                    $DB->update_record('threesixo_item', $item);
                    $offset++;
                }
            }
            $itemstobemoved->close();
            return $DB->delete_records('threesixo_item', ['id' => $itemid]);
        }

        return false;
    }

    /**
     * Decline responding to a 360-degree feedback for a user.
     *
     * @param int $submissionid The submission ID.
     * @param string $reason The reason why the feedback is being declined.
     * @return bool
     * @throws dml_exception
     */
    public static function decline_feedback($submissionid, $reason) {
        global $DB;

        // Delete responses, if necessary.
        $submission = self::get_submission($submissionid);
        $params = [
            'threesixo' => $submission->threesixo,
            'fromuser' => $submission->fromuser,
            'touser' => $submission->touser,
        ];
        $result = $DB->delete_records('threesixo_response', $params);

        // Set declined status.
        $result &= self::set_completion($submissionid, self::STATUS_DECLINED, $reason);
        return $result;
    }

    /**
     * Sets the current completion status of a 360-feedback status record.
     *
     * @param int $submissionid The submission ID.
     * @param int $status The status. See the STATUS_* constants.
     * @param string $remarks Any comment about the completion. Usually used when the user declines to provide a feedback.
     * @return bool True if status record was successfully updated. False, otherwise.
     * @throws dml_exception
     */
    public static function set_completion($submissionid, $status, $remarks = null) {
        global $DB;

        if ($statusrecord = $DB->get_record('threesixo_submission', ['id' => $submissionid])) {
            $statusrecord->status = $status;
            if (!empty($remarks)) {
                $statusrecord->remarks = $remarks;
            }
            return $DB->update_record('threesixo_submission', $statusrecord);
        }

        return false;
    }

    /**
     * Check whether only active users in course should be shown.
     *
     * @param context_module|null $context
     * @return bool true if only active users should be shown.
     */
    public static function show_only_active_users(?context_module $context = null) {
        global $CFG;

        $defaultgradeshowactiveenrol = !empty($CFG->grade_report_showonlyactiveenrol);
        $showonlyactiveenrol = get_user_preferences('grade_report_showonlyactiveenrol', $defaultgradeshowactiveenrol);

        if (!is_null($context)) {
            $showonlyactiveenrol = $showonlyactiveenrol || !has_capability('moodle/course:viewsuspendedusers', $context);
        }
        return $showonlyactiveenrol;
    }

    /**
     * Function that retrieves the participants for the 360 feedback activity.
     *
     * @param int $threesixtyid The 360 instance ID.
     * @param int $userid The respondent's user ID.
     * @param bool $includeself Whether to include the respondent in the list.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_participants($threesixtyid, $userid, $includeself = false) {
        global $DB;

        $userssqlparams = ['threesixtyid' => $threesixtyid, 'userid' => $userid];

        $wheres = [];

        if (!$includeself) {
            $wheres[] = 'u.id <> :userid2';
            $userssqlparams['userid2'] = $userid;
        }

        $cm = get_coursemodule_from_instance('threesixo', $threesixtyid);
        $context = context_module::instance($cm->id);
        $canviewreports = self::can_view_reports($context);
        if (!$canviewreports) {
            $role = $DB->get_field('threesixo', 'participantrole', ['id' => $threesixtyid]);
            if ($role != 0) {
                $rolecondition = "u.id IN (
                                  SELECT ra.userid
                                    FROM {role_assignments} ra
                              INNER JOIN {threesixo} ff
                                      ON ra.roleid = ff.participantrole
                                         AND ff.id = :threesixtyid2
                              )
                              AND :user3 IN (
                                  SELECT ra.userid
                                    FROM {role_assignments} ra
                              INNER JOIN {threesixo} ff
                                      ON ra.roleid = ff.participantrole
                                         AND ff.id = :threesixtyid3
                              )";
                $userssqlparams['threesixtyid2'] = $threesixtyid;
                $userssqlparams['threesixtyid3'] = $threesixtyid;
                $userssqlparams['user3'] = $userid;

                $wheres[] = $rolecondition;
            }
        }

        $groupmode = groups_get_activity_groupmode($cm);
        if ($groupmode != NOGROUPS) {
            $currentgroup = groups_get_activity_group($cm, true);
            if (!$currentgroup && !has_capability('moodle/site:accessallgroups', $context)) {
                throw new moodle_exception('You don\'t belong in any groups');
            }
            $userids = get_enrolled_users($context, '', $currentgroup, 'u.id', null, 0, 0, self::show_only_active_users($context));

            $userids = array_map(
                function($user) {
                    return $user->id;
                }, $userids);
            if ($userids) {
                list($sql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

                $groupcondition = "u.id $sql";
                $userssqlparams = array_merge($userssqlparams, $params);
                $wheres[] = $groupcondition;
            }
        }

        // Add conditions to make sure user enrolments of participants are active and current.
        $wheres += [
            'ue.status <> :enrolsuspended',
            '(ue.timestart = 0 OR ue.timestart <= :enrolstart)',
            '(ue.timeend = 0 OR ue.timeend > :enrolend)',
        ];

        // Add user enrolment parameters.
        $now = time();
        $userssqlparams += [
            'enrolsuspended' => ENROL_USER_SUSPENDED,
            'enrolstart' => $now,
            'enrolend' => $now,
        ];

        // Build the where clause.
        $wherecondition = '';
        if (!empty($wheres)) {
            $wherecondition = implode(' AND ', $wheres);
            if (trim($wherecondition)) {
                $wherecondition = 'WHERE ' . $wherecondition;
            }
        }

        $userssql = "SELECT DISTINCT u.id AS userid,
                            u.firstname,
                            u.lastname,
                            u.firstnamephonetic,
                            u.lastnamephonetic,
                            u.middlename,
                            u.alternatename,
                            fs.id AS statusid,
                            fs.status
                       FROM {user} u
                 INNER JOIN {user_enrolments} ue
                         ON u.id = ue.userid
                 INNER JOIN {enrol} e
                         ON e.id = ue.enrolid
                 INNER JOIN {threesixo} f
                         ON f.course = e.courseid
                            AND f.id = :threesixtyid
                  LEFT JOIN {threesixo_submission} fs
                         ON f.id = fs.threesixo
                            AND fs.touser = u.id
                            AND fs.fromuser = :userid
                      $wherecondition
                   ORDER BY fs.status ASC,
                            u.lastname ASC";
        $participants = $DB->get_records_sql($userssql, $userssqlparams);

        return $participants;
    }

    /**
     * Generate default records for the table threesixo_submission.
     *
     * @param int $threesixtyid The 360 instance ID.
     * @param int $userid The user ID of the respondent.
     * @param bool $includeself Whether to include self.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function generate_360_feedback_statuses($threesixtyid, $userid, $includeself = false) {
        global $DB;

        $threesixo = $DB->get_record('threesixo', ['id' => $threesixtyid], '*', MUST_EXIST);
        $role = $threesixo->participantrole;
        $wheres = [
            'u.id NOT IN (
                SELECT fs.touser
                  FROM {threesixo_submission} fs
                 WHERE fs.threesixo = f.id
                       AND fs.fromuser = :fromuser2
            )',
        ];
        $params = [
            'threesixtyid' => $threesixtyid,
            'fromuser2' => $userid,
        ];

        if (!$includeself) {
            $wheres[] = 'u.id <> :fromuser';
            $params['fromuser'] = $userid;
        }

        if ($role != 0) {
            $wheres[] = "u.id IN (
                          SELECT ra.userid
                            FROM {role_assignments} ra
                      INNER JOIN {threesixo} ff
                              ON ra.roleid = ff.participantrole
                                 AND ff.id = :threesixtyid2
                      )
                      AND :fromuser3 IN (
                          SELECT ra.userid
                            FROM {role_assignments} ra
                      INNER JOIN {threesixo} ff
                              ON ra.roleid = ff.participantrole
                                 AND ff.id = :threesixtyid3
                      )";
            $params['threesixtyid2'] = $threesixtyid;
            $params['threesixtyid3'] = $threesixtyid;
            $params['fromuser3'] = $userid;
        }

        list($course, $cm) = get_course_and_cm_from_instance($threesixtyid, 'threesixo', $threesixo->course, $userid);
        $groupmode = groups_get_activity_groupmode($cm);
        if ($groupmode != NOGROUPS) {
            $currentgroup = groups_get_activity_group($cm, true);
            $context = $cm->context;
            $userids = get_enrolled_users($context, '', $currentgroup, 'u.id', null, 0, 0, self::show_only_active_users($context));

            if ($userids) {
                $userids = array_map(
                    function($user) {
                        return $user->id;
                    }, $userids);
                list($sql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
                $params = array_merge($params, $inparams);
                $wheres[] = "u.id $sql";
            }
        }

        $whereclause = implode(' AND ', $wheres);
        $usersql = "SELECT DISTINCT u.id
                               FROM {user} u
                         INNER JOIN {user_enrolments} ue
                                 ON u.id = ue.userid
                         INNER JOIN {enrol} e
                                 ON e.id = ue.enrolid
                         INNER JOIN {threesixo} f
                                 ON f.course = e.courseid AND f.id = :threesixtyid
                              WHERE {$whereclause}";

        if ($users = $DB->get_records_sql($usersql, $params)) {
            foreach ($users as $user) {
                $status = new stdClass();
                $status->threesixo = $threesixtyid;
                $status->fromuser = $userid;
                $status->touser = $user->id;
                $DB->insert_record('threesixo_submission', $status);
            }
        }
    }

    /**
     * Checks if the given user ID can give feedback to other participants in the given 360-degree feedback activity.
     *
     * @param stdClass|int $threesixtyorid The 360-degree feedback activity object or identifier.
     * @param int $userid The user ID.
     * @param context_module|null $context
     * @return bool|string True if the user can participate. An error message if not.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function can_respond($threesixtyorid, int $userid, ?context_module $context = null) {
        global $DB;

        // User can't participate if not enrolled in the course.
        if ($context !== null && !is_enrolled($context)) {
            return get_string('errornotenrolled', 'mod_threesixo');
        }

        // Get 360 ID and participant role.
        if (is_object($threesixtyorid)) {
            $threesixty = $threesixtyorid;
            $threesixtyid = $threesixty->id;
            $participantrole = $threesixty->participantrole;
        } else {
            $threesixtyid = $threesixtyorid;
            $participantrole = $DB->get_field('threesixo', 'participantrole', ['id' => $threesixtyid]);
        }

        // The user is enrolled and the 360 activity is open to all course members, so return true.
        if ($participantrole == self::PARTICIPANT_ROLE_ALL) {
            return true;
        }

        // Check if user's role is the same as the activity's participant role setting.
        $sql = "SELECT ra.userid
                  FROM {role_assignments} ra
            INNER JOIN {threesixo} t
                    ON ra.roleid = t.participantrole
                       AND t.id = :threesixtyid
                 WHERE ra.userid = :userid";

        $params = [
            'threesixtyid' => $threesixtyid,
            'userid' => $userid,
        ];

        if ($DB->record_exists_sql($sql, $params)) {
            return true;
        }

        return get_string('errorcannotparticipate', 'mod_threesixo');
    }

    /**
     * Checks whether the recipient is still has an active enrolment in the course.
     *
     * @param cm_info $cm The course module information.
     * @param int $touser The user ID of the feedback recipient.
     * @param stdClass|null $threesixo The 360-degree feedback instance.
     * @return bool
     */
    public static function can_provide_feedback_to_user(cm_info $cm, int $touser, ?stdClass $threesixo = null): bool {
        global $USER;

        // Return false if the recipient does not have active enrolment in the course.
        $context = context_module::instance($cm->id);
        if (!is_enrolled($context, $touser, '', true)) {
            return false;
        }

        // Use get_participants to check that the feedback recipient is included in the list of participants that the user can
        // provide feedback to. This is more straightforward as get_participants already counts group mode.
        if (empty($threesixo)) {
            $threesixo = self::get_instance($cm->instance);
        }
        $participants = self::get_participants($threesixo->id, $USER->id, $threesixo->with_self_review);
        foreach ($participants as $participant) {
            if ($participant->userid == $touser) {
                // Match found. We're good to go.
                return true;
            }
        }

        // Match not found.
        return false;
    }

    /**
     * Whether the current user can view the reports regarding the feedback responses.
     *
     * @param context_module $context
     * @return bool
     * @throws coding_exception
     */
    public static function can_view_reports(context_module $context) {
        return has_capability('mod/threesixo:viewreports', $context);
    }

    /**
     * Whether the user can view their own report.
     *
     * @param stdClass $threesixo The 360 instance data.
     * @return bool
     */
    public static function can_view_own_report($threesixo) {
        switch ($threesixo->releasing) {
            case self::RELEASING_OPEN:
                return true;
            case self::RELEASING_MANUAL:
                return $threesixo->released;
            case self::RELEASING_AFTER:
                return $threesixo->timeclose < time();
            default:
                return false;
        }
    }

    /**
     * Retrieves the submission record of a respondent's feedback to another user by submission ID.
     *
     * @param int $id The submission ID.
     * @param int $fromuser The respondent's ID.
     * @param string $fields The fields to be retrieved for the submission.
     * @return mixed
     * @throws dml_exception
     */
    public static function get_submission($id, $fromuser = 0, $fields = '*') {
        global $DB, $USER;
        // If from user is not provided, use the current user's ID to make sure the user's fetching their own submissions.
        if (empty($fromuser)) {
            $fromuser = $USER->id;
        }
        $params = [
            'id' => $id,
            'fromuser' => $fromuser,
        ];
        return $DB->get_record('threesixo_submission', $params, $fields, MUST_EXIST);
    }

    /**
     * Retrieves the submission record of a respondent's feedback to another user by instance ID, respondent and feedback recipient.
     *
     * @param int $threesixtyid The 360-degree feedback instance ID.
     * @param int $fromuser The respondent's user ID.
     * @param int $touser The feedback recipient's user ID.
     * @return false|stdClass
     * @throws dml_exception
     */
    public static function get_submission_by_params($threesixtyid, $fromuser, $touser) {
        global $DB;
        return $DB->get_record('threesixo_submission', [
            'threesixo' => $threesixtyid,
            'fromuser' => $fromuser,
            'touser' => $touser,
        ]);
    }

    /**
     * Get scales for rated questions.
     *
     * @return array
     * @throws coding_exception
     */
    public static function get_scales() {

        $s0 = new stdClass();
        $s0->scale = 0;
        $s0->scalelabel = 'N/A';
        $s0->description = get_string('scalenotapplicable', 'mod_threesixo');

        $s1 = new stdClass();
        $s1->scale = 1;
        $s1->scalelabel = '1';
        $s1->description = get_string('scalestronglydisagree', 'mod_threesixo');

        $s2 = new stdClass();
        $s2->scale = 2;
        $s2->scalelabel = '2';
        $s2->description = get_string('scaledisagree', 'mod_threesixo');

        $s3 = new stdClass();
        $s3->scale = 3;
        $s3->scalelabel = '3';
        $s3->description = get_string('scalesomewhatdisagree', 'mod_threesixo');

        $s4 = new stdClass();
        $s4->scale = 4;
        $s4->scalelabel = '4';
        $s4->description = get_string('scalesomewhatagree', 'mod_threesixo');

        $s5 = new stdClass();
        $s5->scale = 5;
        $s5->scalelabel = '5';
        $s5->description = get_string('scaleagree', 'mod_threesixo');

        $s6 = new stdClass();
        $s6->scale = 6;
        $s6->scalelabel = '6';
        $s6->description = get_string('scalestronglyagree', 'mod_threesixo');

        return [$s1, $s2, $s3, $s4, $s5, $s6, $s0];
    }

    /**
     * Save a user's responses to the feedback questions for another user.
     *
     * @param int $threesixty The 360-degree feedback ID.
     * @param int $touser The recipient of the feedback responses.
     * @param array $responses The responses data.
     * @return bool|int
     * @throws dml_exception
     */
    public static function save_responses($threesixty, $touser, $responses) {
        global $DB, $USER;

        $error = self::validate_responses($threesixty, $responses);
        if ($error !== '') {
            throw new moodle_exception($error);
        }

        $fromuser = $USER->id;
        $savedresponses = $DB->get_records('threesixo_response', [
            'threesixo' => $threesixty,
            'fromuser' => $fromuser,
            'touser' => $touser,
        ]);

        $result = true;
        foreach ($responses as $key => $value) {
            if ($key == 0) {
                continue;
            }
            $response = new stdClass();
            foreach ($savedresponses as $savedresponse) {
                if ($savedresponse->item == $key) {
                    $response = $savedresponse;
                    break;
                }
            }

            $response->value = $value;
            if (empty($response->id)) {
                $response->threesixo = $threesixty;
                $response->item = $key;
                $response->touser = $touser;
                $response->fromuser = $fromuser;
                $id = $DB->insert_record('threesixo_response', $response);
                $result &= !empty($id);
            } else {
                $result &= $DB->update_record('threesixo_response', $response);
            }
        }
        return $result;
    }

    /**
     * Validate user responses to the 360-degree feedback activity, especially the values for the rated questions.
     *
     * @param int $threesixty The 360-degree feedback activity identifier.
     * @param array $responses The array of responses.
     * @return string The error message if validation errors are found. Returns an empty string if validation passes.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function validate_responses(int $threesixty, array $responses): string {
        $items = self::get_items($threesixty);
        $validratings = range(self::RATING_NA, self::RATING_MAX);
        foreach ($responses as $itemid => $value) {
            $item = $items[$itemid] ?? false;
            if ($item === false) {
                return get_string('errorinvaliditem', 'mod_threesixo');
            }
            if ($item->type == self::QTYPE_RATED) {
                if (!in_array((float)$value, $validratings)) {
                    return get_string('errorinvalidratingvalue', 'mod_threesixo', $value);
                }
            }
        }
        return '';
    }

    /**
     * Anonymises the responses for a feedback submission. This is simply done by setting the fromuser field to 0.
     *
     * @param int $threesixtyid The 360-degree feedback ID.
     * @param int $fromuser The respondent.
     * @param int $touser The recipient of the feedback.
     * @return bool
     * @throws dml_exception
     */
    public static function anonymise_responses($threesixtyid, $fromuser, $touser) {
        global $DB;
        $threesixty = self::get_instance($threesixtyid);
        if (!$threesixty->anonymous) {
            // Nothing to do.
            return true;
        }
        $params = [
            'threesixo' => $threesixtyid,
            'fromuser' => $fromuser,
            'touser' => $touser,
        ];
        $updatesql = "UPDATE {threesixo_response}
                         SET fromuser = 0
                       WHERE threesixo = :threesixo
                             AND fromuser = :fromuser
                             AND touser = :touser";
        return $DB->execute($updatesql, $params);
    }

    /**
     * Fetches the feedback data for a user.
     *
     * @param int $threesixtyid The 360-degree feedback ID.
     * @param int $touser The recipient of the feedback.
     * @return array The array of feedback responses for each item in the 360-degree feedback instance.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_feedback_for_user($threesixtyid, $touser) {
        global $DB;

        // Fetch responses that are from completed submissions.
        $params = [
            'threesixo' => $threesixtyid,
            'touser' => $touser,
            'status' => self::STATUS_COMPLETE,
        ];
        $sql = "
            SELECT DISTINCT tr.id, tr.item, tr.fromuser, tr.fromuser, tr.value
                       FROM {threesixo_response} tr
                       JOIN {threesixo_submission} ts
                         ON tr.threesixo = ts.threesixo
                      WHERE tr.threesixo = :threesixo
                            AND tr.touser = :touser
                            AND ts.status = :status
                   ORDER BY tr.item ASC";
        $responses = $DB->get_records_sql($sql, $params);

        $items = self::get_items($threesixtyid);
        foreach ($items as $item) {
            if ($item->type == self::QTYPE_RATED) {
                $ratings = [];
                foreach ($responses as $response) {
                    // Skip empty responses or those who are not matching the item ID.
                    if ($item->id != $response->item || empty(trim($response->value))) {
                        continue;
                    }
                    $ratings[] = (float)$response->value;
                }
                $responsecount = count($ratings);
                if ($responsecount) {
                    $averagerating = array_sum($ratings) / $responsecount;
                    $item->averagerating = number_format($averagerating, 2);
                }
                $item->responsecount = $responsecount;
            } else {
                $comments = [];
                foreach ($responses as $response) {
                    // Skip empty responses or those who are not matching the item ID.
                    $comment = trim($response->value);
                    if ($item->id != $response->item || empty($comment)) {
                        continue;
                    }
                    if ($response->fromuser) {
                        $fromuser = \core_user::get_user($response->fromuser);
                        $fromusername = fullname($fromuser);
                    } else {
                        $fromusername = get_string('anonymous', 'mod_threesixo');
                    }
                    $comments[] = (object)[
                        'fromuser' => $fromusername,
                        'comment' => $comment,
                    ];

                }
                $item->comments = $comments;
            }
        }

        return $items;
    }

    /**
     * Whether the 360 instance is ready for use.
     *
     * @param stdClass|int $threesixtyorid The 360 object or ID.
     * @return bool
     * @throws dml_exception
     */
    public static function is_ready($threesixtyorid) {
        global $DB;
        $status = null;
        $threesixtyid = $threesixtyorid;
        if (is_object($threesixtyorid)) {
            $threesixtyid = $threesixtyorid->id;
            if (isset($threesixtyorid->status)) {
                $status = $threesixtyorid->status;
            }
        }

        // Check if this instance already has items.
        if (!self::has_items($threesixtyid)) {
            // An instance is not yet ready if doesn't have any item yet.
            return false;
        }

        // If it has items already, proceed to check the status.
        if (empty($status)) {
            $status = $DB->get_field('threesixo', 'status', ['id' => $threesixtyid]);
        }

        // An instance is ready if its status has been set to ready and it already has items.
        return $status == self::INSTANCE_READY;
    }

    /**
     * Checks whether a given 360 instance already has items.
     *
     * @param int $threesixtyid The 360 instance ID.
     * @return bool
     */
    public static function has_items($threesixtyid) {
        global $DB;
        return $DB->record_exists('threesixo_item', ['threesixo' => $threesixtyid]);
    }

    /**
     * Whether the user has the capability to edit items.
     *
     * @param int $threesixtyid The 360 instance ID.
     * @param context_module $context
     * @return bool
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function can_edit_items($threesixtyid, $context = null) {
        if (empty($context)) {
            $cm = get_coursemodule_from_instance('threesixo', $threesixtyid);
            $context = context_module::instance($cm->id);
        }
        return has_capability('mod/threesixo:edititems', $context);
    }

    /**
     * Make the 360 instance ready for use by the participants.
     *
     * @param int $threesixtyid The 360 instance ID.
     * @return bool
     * @throws moodle_exception
     */
    public static function make_ready($threesixtyid) {
        global $DB;
        $cm = get_coursemodule_from_instance('threesixo', $threesixtyid);
        $context = context_module::instance($cm->id);
        $url = new moodle_url('/mod/threesixo/view.php', ['id' => $cm->id]);
        if (!self::can_edit_items($threesixtyid, $context)) {
            throw new moodle_exception('nocaptoedititems', 'mod_threesixo', $url);
        }
        if (!self::has_items($threesixtyid)) {
            throw new moodle_exception('noitemsyet', 'mod_threesixo', $url);
        }
        return $DB->set_field('threesixo', 'status', self::INSTANCE_READY, ['id' => $threesixtyid]);
    }

    /**
     * Toggle the released flag of a 360 instance.
     *
     * @param stdClass $threesixty The 360 instance data.
     * @param int $released Value of the released flag to set.
     * @return bool
     */
    public static function toggle_released_flag($threesixty, $released) {
        global $DB;
        $cm = get_coursemodule_from_instance('threesixo', $threesixty->id);
        $context = context_module::instance($cm->id);
        $url = new moodle_url('/mod/threesixo/view.php', ['id' => $cm->id]);
        if (!self::can_edit_items($threesixty->id, $context)) {
            throw new moodle_exception('nocaptoedititems', 'mod_threesixo', $url);
        }
        if ($threesixty->releasing != self::RELEASING_MANUAL) {
            throw new moodle_exception('This operation is only permitted for instances that need to be manually released');
        }
        $allowedvalues = [0, 1];
        if (!in_array($released, $allowedvalues)) {
            throw new moodle_exception('Allowed values are only 0 and 1');
        }
        // Update this object's released value here to avoid another DB query.
        $threesixty->released = $released;
        return $DB->set_field('threesixo', 'released', $released, ['id' => $threesixty->id]);
    }

    /**
     * Counts the number of users awaiting feedback from the given user ID.
     *
     * @param int $threesixtyid The 360-degree feedback instance ID.
     * @param int $user The user ID.
     * @return int
     */
    public static function count_users_awaiting_feedback($threesixtyid, $user) {
        global $DB;

        $threesixo = self::get_instance($threesixtyid);

        // Check first if the user can write feedback to other participants.
        if (self::can_respond($threesixo, $user) === true) {
            if (!$DB->record_exists('threesixo_submission', ['threesixo' => $threesixo->id, 'fromuser' => $user])) {
                // Generate submission records if there are no submission records yet.
                self::generate_360_feedback_statuses($threesixo->id, $user, $threesixo->with_self_review);
            }

            // Count participants awaiting feedback from this user.
            list($insql, $params) = $DB->get_in_or_equal([self::STATUS_PENDING, self::STATUS_IN_PROGRESS], SQL_PARAMS_NAMED);
            $select = "threesixo = :threesixo AND fromuser = :fromuser AND status $insql";
            $params['threesixo'] = $threesixtyid;
            $params['fromuser'] = $user;
            return $DB->count_records_select('threesixo_submission', $select, $params);
        }

        return 0;
    }

    /**
     * Checks the availability of the instance based on the open and close times of the activity.
     *
     * @param int|stdClass $threesixoorid The 360-degree feedback ID or instance.
     * @param bool $messagewhenclosed Whether to return a message when the instance is not yet open.
     * @return bool|string
     */
    public static function is_open($threesixoorid, $messagewhenclosed = false) {
        // Fetch instance when only an ID was provided.
        if (is_object($threesixoorid)) {
            $threesixo = $threesixoorid;
        } else {
            $threesixo = self::get_instance($threesixoorid);
        }

        // If there's open and close times are not defined, instance is open.
        if (empty($threesixo->timeopen) && empty($threesixo->timeclose)) {
            return true;
        }

        $now = time();
        // If there's open time is before the current time, instance is not yet open.
        if (!empty($threesixo->timeopen) && $threesixo->timeopen > $now) {
            if ($messagewhenclosed) {
                return get_string('instancenotyetopen', 'threesixo', userdate($threesixo->timeopen));
            } else {
                return false;
            }
        }

        // If there's close time is after the current time, instance is not yet open.
        if (!empty($threesixo->timeclose) && $threesixo->timeclose <= $now) {
            if ($messagewhenclosed) {
                return get_string('instancealreadyclosed', 'threesixo');
            } else {
                return false;
            }
        }
        // All good, instance is open.
        return true;
    }

    /**
     * Whether a question can be deleted.
     *
     * A question can be deleted if it is not in use.
     *
     * @param int $id The question ID.
     * @return bool
     */
    public static function can_delete_question(int $id): bool {
        global $DB;

        return !$DB->record_exists('threesixo_item', ['question' => $id]);
    }

    /**
     * Check if the user can edit another user's question.
     *
     * @param stdClass $question The question record from the `threesixo_question` table.
     * @return bool
     */
    public static function can_edit_others_question(stdClass $question): bool {
        global $USER;
        $context = context_system::instance();
        return $USER->id != $question->createdby && has_capability('mod/threesixo:editothersquestions', $context);
    }

    /**
     * Check if the user can delete another user's question.
     *
     * @param stdClass $question The question record from the `threesixo_question` table.
     * @return bool
     */
    public static function can_delete_others_question(stdClass $question): bool {
        global $USER;
        $context = context_system::instance();
        if ($USER->id == $question->createdby) {
            // A user can delete their own question.
            return true;
        }
        return has_capability('mod/threesixo:deleteothersquestions', $context);
    }
}

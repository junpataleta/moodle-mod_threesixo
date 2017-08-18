<?php

namespace mod_threesixty;

use context_module;
use moodle_exception;
use moodle_url;
use stdClass;

class api {
    const TYPE_DEFAULT = 0;
    const TYPE_360 = 1;

    const QTYPE_RATED = 0;
    const QTYPE_COMMENT = 1;

    const STATUS_PENDING = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETE = 2;
    const STATUS_DECLINED = 3;

    const QBANK_MODE_DEFAULT = 0;
    const QBANK_MODE_PICKER = 1;

    const MOVE_UP = 1;
    const MOVE_DOWN = 2;

    const PARTICIPANT_ROLE_ALL = 0;

    const INSTANCE_NOT_READY = 0;
    const INSTANCE_READY = 1;

    /**
     * Fetches the 360-degree feedback instance.
     *
     * @param int $threesixtyid The 360-degree feedback ID.
     * @return mixed
     */
    public static function get_instance($threesixtyid) {
        global $DB;

        return $DB->get_record('threesixty', ['id' => $threesixtyid], '*', MUST_EXIST);
    }

    /**
     * @return array
     */
    public static function get_questions() {
        global $DB;
        $questions = $DB->get_records('threesixty_question');
        foreach ($questions as $question) {
            switch ($question->type) {
                case api::QTYPE_RATED:
                    $question->typeName = get_string('qtyperated', 'mod_threesixty');
                    break;
                case api::QTYPE_COMMENT:
                    $question->typeName = get_string('qtypecomment', 'mod_threesixty');
                    break;
                default:
                    break;
            }
        }

        return $questions;
    }

    /**
     * @param stdClass $data
     * @return bool|int
     */
    public static function add_question(stdClass $data) {
        global $DB;
        return $DB->insert_record('threesixty_question', $data);
    }

    /**
     * @param stdClass $data
     * @return bool
     */
    public static function update_question(stdClass $data) {
        global $DB;
        return $DB->update_record('threesixty_question', $data);
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function delete_question($id) {
        global $DB;
        return $DB->delete_records('threesixty_question', ['id' => $id]);
    }

    /**
     * @param int $threesixtyid
     * @return array
     */
    public static function get_items($threesixtyid) {
        global $DB;

        $sql = "SELECT i.id,
                       i.threesixty as threesixtyid,
                       i.question as questionid,
                       i.position,
                       q.question,
                       q.type
                  FROM {threesixty_item} i
            INNER JOIN {threesixty_question} q
                    ON i.question = q.id
                 WHERE i.threesixty = :threesixtyid
              ORDER BY i.position;";
        $params = [
            'threesixtyid' => $threesixtyid
        ];

        $items = $DB->get_records_sql($sql, $params);
        foreach ($items as $item) {
            // Question type.
            switch ($item->type) {
                case api::QTYPE_RATED:
                    $qtype = get_string('qtyperated', 'threesixty');
                    break;
                case api::QTYPE_COMMENT:
                    $qtype = get_string('qtypecomment', 'threesixty');
                    break;
                default:
                    $qtype = '';
            }
            $item->typetext = $qtype;
        }
        return $items;
    }

    /**
     * Fetches the user's responses.
     */
    public static function get_responses($threesixtyid, $fromuser, $touser) {
        global $DB;

        $params = [
            'threesixty' => $threesixtyid,
            'fromuser' => $fromuser,
            'touser' => $touser
        ];

        return $DB->get_records('threesixty_response', $params, 'item ASC', 'id, item, value');
    }

    /**
     * Sets the questions for the 360 activity.
     *
     * @param int $threesixtyid The 360 ID.
     * @param int[] $questionids The array of question IDs.
     * @return bool True on success. False, otherwise.
     */
    public static function set_items($threesixtyid, $questionids) {
        global $DB;

        // Delete existing, but were unselected, items.
        $select = 'threesixty = :threesixty';
        $params = ['threesixty' => $threesixtyid];
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
        $DB->delete_records_select('threesixty_item', $select, $params);

        // Get remaining items.
        $existingitems = $DB->get_records('threesixty_item', ['threesixty' => $threesixtyid], 'position ASC', '*');
        // Reorder positions.
        $position = 1;
        $selectedquestions = [];
        foreach ($existingitems as $existingitem) {
            if ($existingitem->position != $position) {
                $existingitem->position = $position;
                $DB->update_record('threesixty_item', $existingitem);
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
            $data->threesixty = $threesixtyid;
            $data->position = $position++;
            $records[] = $data;
        }
        $DB->insert_records('threesixty_item', $records);
        return true;
    }

    /**
     * Returns an array of question types with key as the question type and value as the question type text.
     */
    public static function get_question_types() {
        return [
            self::QTYPE_RATED => get_string('qtyperated', 'mod_threesixty'),
            self::QTYPE_COMMENT => get_string('qtypecomment', 'mod_threesixty')
        ];
    }

    public static function get_item_by_id($itemid) {
        global $DB;
        return $DB->get_record('threesixty_item', ['id' => $itemid], '*', MUST_EXIST);
    }

    /**
     * Moves the item up.
     *
     * @param int $itemid The item ID.
     * @return bool
     */
    public static function move_item_up($itemid) {
        return self::move_item($itemid, self::MOVE_UP);
    }

    /**
     * Moves the item down.
     *
     * @param int $itemid The item ID.
     * @return bool
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
        if ($item = $DB->get_record('threesixty_item', ['id' => $itemid])) {
            $oldposition = $item->position;
            $itemcount = $DB->count_records('threesixty_item', ['threesixty' => $item->threesixty]);

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
            if ($swapitem = $DB->get_record('threesixty_item', ['threesixty' => $item->threesixty, 'position' => $item->position])) {
                $swapitem->position = $oldposition;
                $result = $DB->update_record('threesixty_item', $swapitem);
            }
            // Update the item being moved.
            $result = $result && $DB->update_record('threesixty_item', $item);
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
     */
    public static function delete_item($itemid) {
        global $DB;
        if ($itemtobedeleted = $DB->get_record('threesixty_item', ['id' => $itemid])) {
            $itemstobemoved = $DB->get_recordset_select('threesixty_item', 'position > ?', [$itemtobedeleted->position], 'position');
            $offset = 0;
            foreach ($itemstobemoved as $item) {
                $item->position = $itemtobedeleted->position + $offset;
                $DB->update_record('threesixty_item', $item);
                $offset++;
            }
            return $DB->delete_records('threesixty_item', ['id' => $itemid]);
        }

        return false;
    }

    public static function decline_feedback($submissionid, $reason) {
        global $DB;

        // Delete responses, if necessary.
        $submission = self::get_submission($submissionid);
        $params = [
            'threesixty' => $submission->threesixty,
            'fromuser' => $submission->fromuser,
            'touser' => $submission->touser
        ];
        $result = $DB->delete_records('threesixty_response', $params);

        // Set declined status.
        $result &= self::set_completion($submissionid, self::STATUS_DECLINED, $reason);
        return $result;
    }

    /**
     * Sets the current completion status of a 360-feedback status record.
     *
     * @param int $submissionid
     * @param int $status
     * @param string $remarks
     * @return bool True if status record was successfully updated. False, otherwise.
     */
    public static function set_completion($submissionid, $status, $remarks = null) {
        global $DB;

        if ($statusrecord = $DB->get_record('threesixty_submission', array('id' => $submissionid))) {
            $statusrecord->status = $status;
            if (!empty($remarks)) {
                $statusrecord->remarks = $remarks;
            }
            return $DB->update_record('threesixty_submission', $statusrecord);
        }

        return false;
    }

    /**
     * Function that retrieves the participants for the 360 feedback activity.
     *
     * @param int $threesixtyid The 360 instance ID.
     * @param int $userid The respondent's user ID.
     * @param bool $includeself Whether to include the respondent in the list.
     * @return array
     */
    public static function get_participants($threesixtyid, $userid, $includeself = false) {
        global $DB;

        $userssqlparams = ['threesixtyid' => $threesixtyid, 'userid' => $userid];

        $wheres = [];

        if (!$includeself) {
            $wheres[] = 'u.id <> :userid2';
            $userssqlparams['userid2'] = $userid;
        }

        $cm = get_coursemodule_from_instance('threesixty', $threesixtyid);
        $context = context_module::instance($cm->id);
        $canviewreports = self::can_view_reports($context);
        if (!$canviewreports) {
            $role = $DB->get_field('threesixty', 'participantrole', ['id' => $threesixtyid]);
            if ($role != 0) {
                $rolecondition = "u.id IN (
                                  SELECT ra.userid 
                                    FROM {role_assignments} ra
                              INNER JOIN {threesixty} ff
                                      ON ra.roleid = ff.participantrole
                                         AND ff.id = :threesixtyid2
                              )
                              AND :user3 IN (
                                  SELECT ra.userid 
                                    FROM {role_assignments} ra
                              INNER JOIN {threesixty} ff
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
        if ($groupmode != NOGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
            $usergroups = groups_get_user_groups($cm->course)['0'];
            list($sql, $params) = $DB->get_in_or_equal($usergroups, SQL_PARAMS_NAMED);
            $groupcondition = "u.id IN (
                SELECT gm.userid 
                  FROM {groups_members} gm
                 WHERE gm.groupid $sql 
            )";
            $userssqlparams = array_merge($userssqlparams, $params);
            $wheres[] = $groupcondition;
        }

        $wherecondition = '';
        if (!empty($wheres)) {
            $wherecondition = implode(' AND ', $wheres);
            if (trim($wherecondition)) {
                $wherecondition = 'WHERE ' . $wherecondition;
            }
        }

        $userssql = "SELECT u.id AS userid,
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
                 INNER JOIN {threesixty} f 
                         ON f.course = e.courseid 
                            AND f.id = :threesixtyid
                  LEFT JOIN {threesixty_submission} fs
                         ON f.id = fs.threesixty 
                            AND fs.touser = u.id 
                            AND fs.fromuser = :userid
                      $wherecondition
                   ORDER BY fs.status ASC,
                            u.lastname ASC";
        $participants = $DB->get_records_sql($userssql, $userssqlparams);

        return $participants;
    }

    /**
     * Generate default records for the table threesixty_submission.
     *
     * @param int $threesixtyid The 360 instance ID.
     * @param int $userid The user ID of the respondent.
     */
    public static function generate_360_feedback_statuses($threesixtyid, $userid) {
        global $DB;

        $role = $DB->get_field('threesixty', 'participantrole', ['id' => $threesixtyid]);
        $rolecondition = '';
        $params = [
            'threesixtyid' => $threesixtyid,
            'fromuser' => $userid,
            'fromuser2' => $userid
        ];
        if ($role != 0) {
            $rolecondition = "AND u.id IN (
                                  SELECT ra.userid 
                                    FROM {role_assignments} ra
                              INNER JOIN {threesixty} ff
                                      ON ra.roleid = ff.participantrole
                                         AND ff.id = :threesixtyid2
                              )
                              AND :fromuser3 IN (
                                  SELECT ra.userid 
                                    FROM {role_assignments} ra
                              INNER JOIN {threesixty} ff
                                      ON ra.roleid = ff.participantrole
                                         AND ff.id = :threesixtyid3
                              )";
            $params['threesixtyid2'] = $threesixtyid;
            $params['threesixtyid3'] = $threesixtyid;
            $params['fromuser3'] = $userid;
        }

        $cm = get_coursemodule_from_instance('threesixty', $threesixtyid);
        $groupmode = groups_get_activity_groupmode($cm);
        $groupcondition = '';
        if ($groupmode != NOGROUPS) {
            $usergroups = groups_get_user_groups($cm->course)['0'];
            if (!empty($usergroups)) {
                list($sql, $groupparams) = $DB->get_in_or_equal($usergroups, SQL_PARAMS_NAMED);
                $groupcondition = "AND u.id IN (
                            SELECT gm.userid 
                              FROM {groups_members} gm
                             WHERE gm.groupid $sql 
                        )";
                $params = array_merge($params, $groupparams);
            }
        }

        $usersql = "SELECT DISTINCT u.id
                               FROM {user} u
                         INNER JOIN {user_enrolments} ue
                                 ON u.id = ue.userid
                         INNER JOIN {enrol} e
                                 ON e.id = ue.enrolid
                         INNER JOIN {threesixty} f
                                 ON f.course = e.courseid AND f.id = :threesixtyid
                              WHERE u.id <> :fromuser
                                    AND u.id NOT IN (
                                        SELECT fs.touser
                                          FROM {threesixty_submission} fs
                                         WHERE fs.threesixty = f.id 
                                               AND fs.fromuser = :fromuser2
                                    )
                                    $rolecondition
                                    $groupcondition";

        if ($users = $DB->get_records_sql($usersql, $params)) {
            foreach ($users as $user) {
                $status = new stdClass();
                $status->threesixty = $threesixtyid;
                $status->fromuser = $userid;
                $status->touser = $user->id;
                $DB->insert_record('threesixty_submission', $status);
            }
        }
    }

    /**
     * Checks if the given user ID can give feedback to other participants in the given 360-degree feedback activity.
     *
     * @param stdClass|int $threesixtyorid The 360-degree feedback activity object or identifier.
     * @param int $userid The user ID.
     * @param context_module $context
     * @return bool|string True if the user can participate. An error message if not.
     */
    public static function can_respond($threesixtyorid, $userid, context_module $context = null) {
        global $DB;

        // User can't participate if not enrolled in the course.
        if ($context !== null && !is_enrolled($context)) {
            return get_string('errornotenrolled', 'mod_threesixty');
        }

        // Get 360 ID and participant role.
        if (is_object($threesixtyorid)) {
            $threesixty = $threesixtyorid;
            $threesixtyid = $threesixty->id;
            $participantrole = $threesixty->participantrole;
        } else {
            $threesixtyid = $threesixtyorid;
            $participantrole = $DB->get_field('threesixty', 'participantrole', ['id' => $threesixtyid]);
        }

        // The user is enrolled and the 360 activity is open to all course members, so return true.
        if ($participantrole == self::PARTICIPANT_ROLE_ALL) {
            return true;
        }

        // Check if user's role is the same as the activity's participant role setting.
        $sql = "SELECT ra.userid
                  FROM {role_assignments} ra
            INNER JOIN {threesixty} t
                    ON ra.roleid = t.participantrole
                       AND t.id = :threesixtyid
                 WHERE ra.userid = :userid";

        $params = [
            'threesixtyid' => $threesixtyid,
            'userid' => $userid
        ];

        if ($DB->record_exists_sql($sql, $params)) {
            return true;
        }

        return get_string('errorcannotparticipate', 'mod_threesixty');
    }

    /**
     * Whether the current user can view the reports regarding the feedback responses.
     *
     * @param context_module $context
     * @return bool
     */
    public static function can_view_reports(context_module $context) {
        return has_capability('mod/threesixty:viewreports', $context);
    }

    /**
     * Retrieves the submission record of a respondent's feedback to another user.
     *
     * @param int $id The submission ID.
     * @param int $fromuser The respondent's ID.
     * @param string $fields The fields to be retrieved for the submission.
     * @return mixed
     */
    public static function get_submission($id, $fromuser = 0, $fields = '*') {
        global $DB;
        $params = [
            'id' => $id
        ];
        if (!empty($fromuser)) {
            $params['fromuser'] = $fromuser;
        }
        return $DB->get_record('threesixty_submission', $params, $fields, MUST_EXIST);
    }

    public static function get_submission_by_params($threesixtyid, $fromuser, $touser) {
        global $DB;
        return $DB->get_record('threesixty_submission', [
            'threesixty' => $threesixtyid,
            'fromuser' => $fromuser,
            'touser' => $touser,
        ]);
    }

    /**
     * Get scales for rated questions.
     *
     * @return array
     */
    public static function get_scales() {

        $s0 = new stdClass();
        $s0->scale = 0;
        $s0->scalelabel = 'N/A';
        $s0->description = get_string('scalenotapplicable', 'mod_threesixty');

        $s1 = new stdClass();
        $s1->scale = 1;
        $s1->scalelabel = '1';
        $s1->description = get_string('scalestronglydisagree', 'mod_threesixty');

        $s2 = new stdClass();
        $s2->scale = 2;
        $s2->scalelabel = '2';
        $s2->description = get_string('scaledisagree', 'mod_threesixty');

        $s3 = new stdClass();
        $s3->scale = 3;
        $s3->scalelabel = '3';
        $s3->description = get_string('scalesomewhatdisagree', 'mod_threesixty');

        $s4 = new stdClass();
        $s4->scale = 4;
        $s4->scalelabel = '4';
        $s4->description = get_string('scalesomewhatagree', 'mod_threesixty');

        $s5 = new stdClass();
        $s5->scale = 5;
        $s5->scalelabel = '5';
        $s5->description = get_string('scaleagree', 'mod_threesixty');

        $s6 = new stdClass();
        $s6->scale = 6;
        $s6->scalelabel = '6';
        $s6->description = get_string('scalestronglyagree', 'mod_threesixty');

        return [$s1, $s2, $s3, $s4, $s5, $s6, $s0];
    }

    public static function save_responses($threesixty, $touser, $responses) {
        global $DB, $USER;

        $fromuser = $USER->id; 
        $savedresponses = $DB->get_records('threesixty_response', [
            'threesixty' => $threesixty,
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
            
            if (empty($response->id)) {
                $response->threesixty = $threesixty;
                $response->item = $key;
                $response->touser = $touser;
                $response->fromuser = $fromuser;
                $response->value = $value;
                $response->salt = '';
                $result &= $DB->insert_record('threesixty_response', $response);
            } else {
                $response->value = $value;
                $result &= $DB->update_record('threesixty_response', $response);
            }
        }
        return $result;
    }

    /**
     * Anonymises the responses for a feedback submission. This is simply done by setting the fromuser field to 0.
     *
     * @param $threesixtyid
     * @param $fromuser
     * @param $touser
     * @return bool
     */
    public static function anonymise_responses($threesixtyid, $fromuser, $touser) {
        global $DB;
        $threesixty = self::get_instance($threesixtyid);
        if (!$threesixty->anonymous) {
            // Nothing to do.
            return true;
        }
        $params = [
            'threesixty' => $threesixtyid,
            'fromuser' => $fromuser,
            'touser' => $touser
        ];
        $updatesql = "UPDATE {threesixty_response}
                         SET fromuser = 0
                       WHERE threesixty = :threesixty
                             AND fromuser = :fromuser
                             AND touser = :touser";
        return $DB->execute($updatesql, $params);
    }

    public static function get_feedback_for_user($threesixtyid, $touser) {
        global $DB;

        $params = [
            'threesixty' => $threesixtyid,
            'touser' => $touser
        ];
        $responses = $DB->get_records('threesixty_response', $params, 'item ASC', 'id, item, fromuser, value');

        $items = api::get_items($threesixtyid);
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
                        $fromusername = get_string('anonymous', 'mod_threesixty');
                    }
                    $comments[] = (object)[
                        'fromuser' => $fromusername,
                        'comment' => $comment
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
     */
    public static function is_ready($threesixtyorid) {
        global $DB;
        $status = null;
        $threesixtyid = null;
        if (is_object($threesixtyorid)) {
            if (isset($threesixtyorid->status)) {
                $status = $threesixtyorid->status;
            } else {
                $threesixtyid = $threesixtyorid->id;
            }
        } else {
            $threesixtyid = $threesixtyorid;
        }
        if (!empty($threesixtyid) && empty($status)) {
            $status = $DB->get_field('threesixty', 'status', ['id' => $threesixtyid]);
        }
        return $status == self::INSTANCE_READY;
    }

    /**
     * Whether the user has the capability to edit items.
     *
     * @param int $threesixtyid The 360 instance ID.
     * @return bool
     */
    public static function can_edit_items($threesixtyid, $context = null) {
        if (empty($context)) {
            list($course, $cm) = get_course_and_cm_from_instance($threesixtyid, 'threesixty');
            $context = context_module::instance($cm->id);
        }
        return has_capability('mod/threesixty:edititems', $context);
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
        list($course, $cm) = get_course_and_cm_from_instance($threesixtyid, 'threesixty');
        $context = context_module::instance($cm->id);
        if (!self::can_edit_items($threesixtyid, $context)) {
            $url = new moodle_url('/mod/threesixty/view.php', ['id' => $cm->id]);
            throw new moodle_exception('nocaptoedititems', 'mod_threesixty', $url);
        }
        return $DB->set_field('threesixty', 'status', self::INSTANCE_READY, ['id' => $threesixtyid]);
    }
}

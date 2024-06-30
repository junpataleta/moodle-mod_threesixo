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

namespace mod_threesixo\task;

use core\message\message;
use core\task\adhoc_task;
use core_user;
use mod_threesixo\api;

/**
 * Ad-hoc task that goes through the responses and fixes invalid ratings.
 *
 * Invalid ratings for anonymous feedback activities will be reset to N/A (0).
 * Invalid ratings for non-anonymous feedback activities will be reset to null
 * and the submission status will be reset to "In-progress".
 *
 * @package mod_threesixo
 * @copyright   2024 Jun Pataleta
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fix_responses extends adhoc_task {

    /** @var array Cache of 360-degree feedback instances. */
    private $threesixos = [];

    /** @var array Cache of user records. */
    private $userlist = [];

    /** @var array Cache of course records. */
    private $courses = [];

    /**
     * Executes the task.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $validratings = range(api::RATING_NA, api::RATING_MAX);

        $sql = "SELECT r.*
                  FROM {threesixo_response} r
                  JOIN {threesixo_item} i
                    ON r.item = i.id
                  JOIN {threesixo_question} q
                    ON i.question = q.id
                 WHERE q.type = :qtype";
        $params = [
            'qtype' => api::QTYPE_RATED,
        ];
        $rs = $DB->get_recordset_sql($sql, $params);

        $resetsubmissions = [];
        $anonresetsubmissions = [];
        if ($rs->valid()) {
            foreach ($rs as $record) {
                if (in_array($record->value, $validratings)) {
                    continue;
                }

                if ($record->fromuser == 0) {
                    // Add the submission record for this response to the list of submissions to be reset.
                    $anonsubmissiontoreset = [
                        'threesixo' => $record->threesixo,
                        'touser' => $record->touser,
                    ];
                    if (!in_array($anonsubmissiontoreset, $anonresetsubmissions)) {
                        mtrace("Invalid ratings found for user $record->touser in the anonymous 360-degree feedback activity" .
                            " with instance ID $record->threesixo." .
                            " The submission records for this user will be reset to 'In progress'...");
                        $anonresetsubmissions[] = $anonsubmissiontoreset;
                    }
                    // Delete the response record.
                    mtrace("    Deleting invalid rating response for the user with response record ID $record->id...");
                    $DB->delete_records('threesixo_response', ['id' => $record->id]);
                } else {
                    // For non-anonymous feedback, reset to null.
                    $record->value = null;
                    // Reset the submission record to 'In progresss' (1) as well.
                    $resetrecord = (object)[
                        'threesixo' => $record->threesixo,
                        'fromuser' => $record->fromuser,
                        'touser' => $record->touser,
                    ];
                    if (!in_array($resetrecord, $resetsubmissions)) {
                        mtrace("Invalid ratings found for user $record->touser in the 360-degree feedback activity" .
                               " with instance ID $record->threesixo. The invalid ratings will be reset to null...");
                        $resetsubmissions[] = $resetrecord;
                    }
                    mtrace("    Resetting invalid rating response record value to null...");
                    $DB->update_record('threesixo_response', $record);
                }
            }
        }
        $rs->close();

        // Reset the status of completed anonymous feedback submissions with invalid ratings.
        // As there's no way to determine who provided the anonymous feedback for a user,
        // reset all the status of the submissions for the feedback recipient to "in-progress".
        $completedanonsubmissions = [];
        foreach ($anonresetsubmissions as $resetdata) {
            $completedanonsubmissions += $DB->get_records('threesixo_submission', [
                'threesixo' => $resetdata['threesixo'],
                'touser' => $resetdata['touser'],
                'status' => api::STATUS_COMPLETE,
            ]);

            mtrace("Resetting the anonymous feedback submission statuses for user {$resetdata['touser']} to `In progress`...");
            $resetdata['status'] = api::STATUS_IN_PROGRESS;
            $resetdata['statuscomplete'] = api::STATUS_COMPLETE;
            $sql = "UPDATE {threesixo_submission}
                       SET status = :status
                     WHERE threesixo = :threesixo
                           AND touser = :touser
                           AND status = :statuscomplete";
            $DB->execute($sql, $resetdata);
            mtrace("    Done.");
        }
        $this->notify_participants($completedanonsubmissions, true);

        // Reset the status of completed non-anonymous feedback submissions with invalid ratings.
        $completedsubmissions = [];
        foreach ($resetsubmissions as $resetdata) {
            $submission = api::get_submission_by_params($resetdata->threesixo, $resetdata->fromuser, $resetdata->touser);
            if ($submission !== false && $submission->status == api::STATUS_COMPLETE) {
                mtrace("Resetting the completed feedback submission status of user {$resetdata->fromuser}" .
                       " to user {$resetdata->touser} to `In progress`...");
                api::set_completion($submission->id, api::STATUS_IN_PROGRESS);
                mtrace("    Done.");

                // Add this submission for notifying the feedback respondent later.
                $completedsubmissions[] = $submission;
            }
        }
        $this->notify_participants($completedsubmissions);
    }

    /**
     * Notify participants that their feedback submission for another user has been reset.
     *
     * @param array $submissiondata The submission records.
     * @param bool $anonymous Whether we're sending notification for anonymous submissions.
     * @return void
     */
    protected function notify_participants(array $submissiondata, bool $anonymous = false): void {
        $message = new message();
        $message->component = 'mod_threesixo';
        $message->name = 'invalidresponses';

        foreach ($submissiondata as $submission) {
            // Get the 360-feedback instance.
            if (!isset($this->threesixos[$submission->threesixo])) {
                $threesixo = api::get_instance($submission->threesixo);
                $this->threesixos[$submission->id] = $threesixo;
            } else {
                $threesixo = $this->threesixos[$submission->threesixo];
            }
            // Set message course ID from the 360 instance record.
            $message->courseid = $threesixo->course;

            // Get the course the 360-feedback instance belongs to.
            if (!isset($this->courses[$threesixo->course])) {
                $course = get_course($threesixo->course);
                $this->courses[$threesixo->course] = $course;
            } else {
                $course = $this->courses[$threesixo->course];
            }

            // Get the feedback recipient.
            if (!isset($this->userlist[$submission->touser])) {
                $touser = core_user::get_user($submission->touser);
                $this->userlist[$submission->touser] = $touser;
            } else {
                $touser = $this->userlist[$submission->touser];
            }

            if (!isset($this->userlist[$submission->fromuser])) {
                $usertonotify = core_user::get_user($submission->fromuser);
                $this->userlist[$submission->fromuser] = $usertonotify;
            } else {
                $usertonotify = $this->userlist[$submission->fromuser];
            }

            $message->userfrom = core_user::get_noreply_user();
            $message->notification = 1;

            $subject = get_string('notifyinvalidresponsessubject', 'mod_threesixo');
            $message->subject = $subject;
            $message->fullmessageformat = FORMAT_HTML;
            $message->userto = $usertonotify;

            $submissionurl = new \moodle_url('/mod/threesixo/questionnaire.php', [
                'threesixo' => $submission->threesixo,
                'submission' => $submission->id,
            ]);
            $messageparams = [
                'respondent' => fullname($usertonotify),
                'url' => $submissionurl->out(),
                'recipient' => fullname($touser),
                'course' => format_string($course->fullname),
                'threesixo' => format_string($threesixo->name),
            ];
            if ($anonymous) {
                $bodykey = 'notifyinvalidresponsesanon';
            } else {
                $bodykey = 'notifyinvalidresponses';
            }
            $messagehtml = get_string($bodykey, 'mod_threesixo', $messageparams);
            $message->fullmessage = html_to_text($messagehtml);
            $message->fullmessagehtml = $messagehtml;

            // Send the message!
            message_send($message);
        }
    }
}

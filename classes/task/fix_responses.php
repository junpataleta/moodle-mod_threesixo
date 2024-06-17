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

use core\task\adhoc_task;
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

        if ($rs->valid()) {
            $resetsubmissions = [];
            $anonresetsubmissions = [];
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

            // Reset the anonymous responses. As there's no way to determine who provided the anonymous feedback for a user,
            // reset all the status of the submissions for the feedback recipient to "in-progress".
            foreach ($anonresetsubmissions as $resetdata) {
                mtrace("Resetting the anonymous feedback submission statuses" .
                       " for user {$resetdata['touser']} to `In progress`...");
                $resetdata['status'] = api::STATUS_IN_PROGRESS;
                $sql = "UPDATE {threesixo_submission}
                           SET status = :status
                         WHERE threesixo = :threesixo
                               AND touser = :touser";
                $DB->execute($sql, $resetdata);
                mtrace("    Done.");
            }

            // Reset the status of non-anonymous feedback submissions with invalid ratings.
            foreach ($resetsubmissions as $resetdata) {
                $submission = api::get_submission_by_params($resetdata->threesixo, $resetdata->fromuser, $resetdata->touser);
                if ($submission !== false) {
                    mtrace("Resetting the feedback submission status of user {$resetdata->fromuser}" .
                           " to user {$resetdata->touser} to `In progress`...");
                    api::set_completion($submission->id, api::STATUS_IN_PROGRESS);
                    mtrace("    Done.");
                }
            }
        }
        $rs->close();
    }
}

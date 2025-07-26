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

/**
 * Strings for component 'threesixo', language 'en', branch 'MOODLE_30_STABLE'
 *
 * @package mod_threesixo
 * @copyright 2015 Jun Pataleta
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addanewquestion'] = 'Add a new question';
$string['additem'] = 'Add item';
$string['allowundodecline'] = 'Allow participants to undo declined feedback submissions';
$string['allparticipants'] = 'All course participants';
$string['anonymous'] = 'Anonymous';
$string['averagerating'] = 'Average rating: {$a}';
$string['backto360dashboard'] = 'Back to the 360 dashboard';
$string['calendarend'] = '{$a} closes';
$string['calendarstart'] = '{$a} opens';
$string['closebeforeopen'] = 'You have specified a close date before the open date.';
$string['commentfromuser'] = '{$a->comment} ({$a->fromuser})';
$string['comments'] = 'Comments';
$string['confirmfinaliseanonymousfeedback'] = 'This will anonymise your responses on your feedback for {$a->name}. You will no longer be able to change your responses once it has been done. Proceed?';
$string['confirmquestiondeletion'] = 'Are you sure you want to delete this question?';
$string['courseinstances'] = '360° feedback instances in {$a}';
$string['dataformatinvalid'] = 'The file format that was specified for downloading this report is either invalid or not enabled. Please select a valid file format.';
$string['decline'] = 'Decline';
$string['declinefeedback'] = 'Decline feedback';
$string['declineheading'] = 'Declining 360° feedback for {$a}';
$string['declinereason'] = 'Please provide a reason why you are declining this feedback.';
$string['declinereasonplaceholdertext'] = 'Enter your reason here... (Optional)';
$string['deleteitem'] = 'Delete item';
$string['deletequestion'] = 'Delete question';
$string['downloadreportas'] = 'Download feedback report as...';
$string['edititems'] = 'Edit 360° feedback items';
$string['editquestion'] = 'Edit question';
$string['enableselfreview'] = 'Enable self-review';
$string['entercomment'] = 'Enter your comment here.';
$string['error360notfound'] = '360° feedback not found.';
$string['errorblankdeclinereason'] = 'Required.';
$string['errorblankquestion'] = 'Required.';
$string['errorcannotadditem'] = 'Cannot add the 360° feedback item.';
$string['errorcannotdeleteothersquestion'] = 'You cannot delete questions created by others.';
$string['errorcannoteditothersquestion'] = 'You cannot edit questions created by others.';
$string['errorcannotparticipate'] = 'You cannot participate in this 360° feedback activity.';
$string['errorcannotprovidefeedbacktouser'] = 'You cannot provide feedback for this user.';
$string['errorcannotupdateitem'] = 'Cannot update the 360° feedback item.';
$string['errorinvaliditem'] = 'Invalid item';
$string['errorinvalidratingvalue'] = 'Invalid rating response value: {$a}';
$string['errorinvalidstatus'] = 'Invalid status';
$string['erroritemnotfound'] = 'The 360° feedback item was not found.';
$string['errornocaptoedititems'] = 'Sorry, but you don\'t have the capability to edit 360° feedback items.';
$string['errornotenrolled'] = 'You need to be enrolled in this course in order to be able to participate in this 360° feedback activity.';
$string['errornothingtodecline'] = 'There is no feedback to decline to.';
$string['errornotingroup'] = 'You need to be in a group in order to be able to participate in this 360° feedback activity. Please contact your course administrator.';
$string['errorquestionstillinuse'] = 'This question cannot be deleted as it is still being used by at least one 360-degree feedback instance.';
$string['errorreportnotavailable'] = 'Your feedback report is not yet available.';
$string['errorresponsesavefailed'] = 'An error has occurred while the responses are being saved. Please try again later.';
$string['feedbackgiven'] = 'Feedback given';
$string['feedbackreceived'] = 'Feedback received';
$string['feedbacksurvey'] = 'Feedback survey for {$a}';
$string['finalise'] = 'Finalise';
$string['finaliseanonymousfeedback'] = 'Finalise anonymous feedback';
$string['gotoquestionbank'] = 'Go to the 360° question bank';
$string['instancealreadyclosed'] = 'The 360° feedback activity has already closed.';
$string['instancenotready'] = 'The 360° feedback activity is not yet ready. Please try again later.';
$string['instancenotyetopen'] = 'The 360° feedback activity is not yet open. It will open on {$a}.';
$string['instancenowready'] = 'The 360° feedback activity is now ready for use by the participants!';
$string['itemdeleted'] = 'Question deleted';
$string['itemmoveddown'] = 'Question moved down';
$string['itemmovedup'] = 'Question moved up';
$string['labelactions'] = 'Actions';
$string['labelcancel'] = 'Cancel';
$string['labeldone'] = 'Done';
$string['labelenterquestion'] = 'Enter question text...';
$string['labelpick'] = 'Pick';
$string['labelpickfromquestionbank'] = 'Pick a question from the question bank';
$string['labelquestion'] = 'Question';
$string['labelquestiontype'] = 'Question type';
$string['labelsave'] = 'Save';
$string['makeavailable'] = 'Make available';
$string['messageafterdecline'] = 'Feedback declined.';
$string['messageprovider:invalidresponses'] = 'Feedback to another user contains invalid responses';
$string['modulename'] = '360° feedback';
$string['modulename_help'] = 'The 360° feedback activity module enables participants to provide feedback to all the other participants.';
$string['modulenameplural'] = '360° feedbacks';
$string['moveitemdown'] = 'Move item down';
$string['moveitemup'] = 'Move item up';
$string['name'] = 'Name';
$string['noitemsyet'] = 'The 360° feedback activity doesn\'t have items yet. Add items by clicking on "Edit 360° feedback items".';
$string['notapplicableabbr'] = 'N/A';
$string['notifyinvalidresponses'] = '<p>Hi, {$a->respondent}!</p>
<p>Invalid ratings have been detected for the feedback that you provided for {$a->recipient} in the 360° feedback activity "{$a->threesixo}" in the course "{$a->course}".</p>
<p>Because of this, your feedback submission has been reset from "Completed" to "In progress".</p>
<p>Please review your <a href="{$a->url}" target="_blank">feedback submission for {$a->recipient}</a>.</p>';
$string['notifyinvalidresponsesanon'] = '<p>Hi, {$a->respondent}!</p>
<p>Invalid ratings have been detected for the feedback that were provided to {$a->recipient} in the anonymous 360° feedback activity "{$a->threesixo}" in the course "{$a->course}".</p>
<p>Because of this, your feedback submission has been reset from "Completed" to "In progress".</p>
<p>Kindly provide a new <a href="{$a->url}" target="_blank">feedback submission for {$a->recipient}</a>.</p>';
$string['notifyinvalidresponsessubject'] = '360° feedback: Your feedback submission has been reset';
$string['numrespondents'] = 'Number of respondents';
$string['openafterclose'] = 'You have specified an open date after the close date';
$string['participants'] = 'Participants';
$string['placeholderquestion'] = "Enter question text";
$string['pluginadministration'] = '360° administration';
$string['pluginname'] = '360° feedback';
$string['privacy:metadata:threesixo'] = 'The ID of the 360-degree feedback instance';
$string['privacy:metadata:threesixo_item'] = 'The ID of the 360-degree feedback item';
$string['privacy:metadata:threesixo_response'] = 'This table stores the responses of the feedback respondent to the feedback questions to the feedback recipient';
$string['privacy:metadata:threesixo_response:value'] = 'The value of the respondent\'s response to the feedback question';
$string['privacy:metadata:threesixo_submission'] = 'This table stores the information about the statuses of 360-degree feedback submissions between the participants';
$string['privacy:metadata:threesixo_submission:fromuser'] = 'The user ID of the person giving the feedback';
$string['privacy:metadata:threesixo_submission:remarks'] = 'The reason why the respondent declined to give feedback to the feedback recipient';
$string['privacy:metadata:threesixo_submission:status'] = 'The status of the feedback submission';
$string['privacy:metadata:threesixo_submission:touser'] = 'The user ID of the feedback recipient';
$string['providefeedback'] = 'Provide feedback';
$string['qtypecomment'] = 'Comment';
$string['qtypeinvalid'] = 'Invalid question type';
$string['qtyperated'] = 'Rated';
$string['question'] = 'Question';
$string['questions'] = '360° feedback questions';
$string['questiontext'] = 'Question text';
$string['questiontype'] = 'Question type';
$string['ratingaverage'] = 'Average rating';
$string['ratings'] = 'Ratings';
$string['rel_after'] = 'Release after the activity has closed';
$string['rel_closed'] = 'Closed to participants';
$string['rel_manual'] = 'Manual release';
$string['rel_open'] = 'Open to participants';
$string['release'] = 'Release reports to participants';
$string['release_close'] = 'Close reports to participants';
$string['releasing'] = 'Releasing';
$string['releasing_help'] = 'Whether to let the participants view the report of the feedback given to them.
<ul>
<li>Closed to participants. Participants cannot view their own feedback report. Only those with the capability to manage the 360-degree feedback activity (e.g. teacher, manager, admin) can view the participants\' feedback reports.</li>
<li>Open to participants. Participants can view their own feedback report any time.</li>
<li>Manual release. Participants can view their own feedback report when released by a user who has the capability to manage the 360-degree feedback activity.</li>
<li>Release after the activity has closed. Participants can view their own feedback report after the activity has ended.</li>
</ul>';
$string['responses'] = 'Responses';
$string['responsessaved'] = 'Your responses have been saved.';
$string['scaleagree'] = 'Agree';
$string['scaledisagree'] = 'Disagree';
$string['scalenotapplicable'] = 'Not applicable';
$string['scalesomewhatagree'] = 'Somewhat agree';
$string['scalesomewhatdisagree'] = 'Somewhat disagree';
$string['scalestronglyagree'] = 'Strongly agree';
$string['scalestronglydisagree'] = 'Strongly disagree';
$string['selectparticipants'] = 'Select participants';
$string['showownquestions'] = 'Only show the questions I created';
$string['status'] = 'Status';
$string['statuscompleted'] = 'Completed';
$string['statusdeclined'] = 'Declined';
$string['statusinprogress'] = 'In progress';
$string['statuspending'] = 'Pending';
$string['statusviewonly'] = 'View only';
$string['submissions'] = 'Submissions';
$string['switchtouser'] = 'Switch to user...';
$string['threesixo:addinstance'] = 'Add a new 360° feedback instance';
$string['threesixo:addquestions'] = 'Create new 360° feedback questions';
$string['threesixo:complete'] = 'Complete a 360° feedback';
$string['threesixo:deleteothersquestions'] = 'Delete 360° feedback questions created by others';
$string['threesixo:deletequestions'] = 'Delete 360° feedback questions';
$string['threesixo:edititems'] = 'Edit 360° feedback items';
$string['threesixo:editothersquestions'] = 'Edit 360° feedback questions created by others';
$string['threesixo:editquestions'] = 'Edit 360° feedback questions';
$string['threesixo:mapcourse'] = 'Map 360° feedback to course';
$string['threesixo:receivemail'] = 'Receive 360° feedback email';
$string['threesixo:view'] = 'View 360° feedback';
$string['threesixo:viewanalysepage'] = 'View 360° feedback analysis';
$string['threesixo:viewreports'] = 'View 360° feedback reports';
$string['title'] = '360° feedback';
$string['titlemanageitems'] = 'Manage 360° feedback items';
$string['todo'] = 'To Do';
$string['undodecline'] = 'Undo decline';
$string['view'] = 'View';
$string['viewfeedbackforuser'] = 'View feedback for user';
$string['viewfeedbackreport'] = 'View feedback report';

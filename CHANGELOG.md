# Changelog

All notable changes to the 360-degree feedback activity are documented here.

This is the changelog for the **main** branch, which supports Moodle 5.1 and up. It carries the plugin's full
release history, so this is the file to read for anything older than the currently maintained series.

The maintenance branches document their own series only:

- [401_STABLE](https://github.com/junpataleta/moodle-mod_threesixo/blob/401_STABLE/CHANGELOG.md) for v4.1.x
  (Moodle 4.1 to 4.5)
- [500_STABLE](https://github.com/junpataleta/moodle-mod_threesixo/blob/500_STABLE/CHANGELOG.md) for v5.0.x
  (Moodle 5.0)

Each release starts with a one-line summary, which is also used as the title of its GitHub release. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

Fixes to how shared questions are handled when an activity is backed up and restored.

### Fixed

- Backups now contain only the questions the activity actually uses. Previously a backup included every
  question on the site, and restoring it added them all to the target site's question bank.
- If a question has been deleted from the question bank since the backup was made, the restore recreates it and
  gives it to the person doing the restore. Recreated questions previously had no owner, so nobody could edit
  or delete them without site-level permissions.
- When the question bank holds more than one copy of the same question, a restore now uses the copy that was
  added first, instead of whichever the database happened to return.

## [v5.1.1] - 2026-08-23

Protects feedback that has already been given, and warns respondents about questions they have missed.

### Added

- Respondents are warned if they try to finalise their feedback with rating questions left unanswered, and can
  either go back and review them or save their progress and come back later.

### Changed

- The questions in a questionnaire can no longer be changed once respondents have started giving feedback.
  Changing them part way through left answers stranded and submissions incomplete.

### Fixed

- Test suite errors, warnings and deprecation notices, including compatibility with PHPUnit 12.

## [v5.1.0] - 2026-06-20

First release for Moodle 5.1, with the last of the old JavaScript replaced.

### Changed

- The activity no longer uses jQuery.
- The questionnaire view and its dialogues now use Moodle's current JavaScript and modal APIs.

### Fixed

- Coding style problems reported by the plugin CI.

## [v5.0.0] - 2025-10-21

First release for Moodle 5.0, adding finer control over who can manage shared questions.

### Added

- Separate permissions for editing and deleting questions created by other people, so a teacher can manage
  their own questions without being able to change everyone else's.

### Changed

- Updated for Bootstrap 5, correcting styling and spacing throughout the activity.

### Fixed

- Problems reported by the plugin CI.

## [v4.1.0] - 2025-05-07

Accessibility improvements and a round of question bank fixes.

### Added

- Test coverage reporting through Codecov.

### Changed

- Accessibility improvements to the activity's tables and to the course-level index page.
- Removed leftover styling classes from Bootstrap 2.

### Fixed

- A missing file include that broke the activity on Moodle 4.5.
- A missing notification string.
- The "add a question" dialogue appearing twice.
- The wrong question type being pre-selected when editing a question.

## [v4.0.1] - 2024-07-04

Stops out-of-range ratings being saved, and repairs any that were already stored.

### Added

- Ratings are checked before they are saved, so only valid values are stored.
- A background task that repairs existing out-of-range ratings and tells the affected respondents that their
  completed feedback has been reset so they can provide it again.

### Changed

- The questionnaire was reworked.

### Fixed

- Login requirements on the activity's web service functions.
- Assorted markup problems in the questionnaire and question list.

## [v4.0.0] - 2023-07-24

First release for Moodle 4.0, with a reworked question bank and item editing.

### Added

- A select-all checkbox when picking questions from the question bank.
- A confirmation message when a question is removed from a questionnaire.

### Changed

- The question bank and item editing screens were rebuilt on Moodle's current JavaScript APIs.

### Fixed

- Removing a question from a questionnaire, and several other problems on the edit questions screen.
- Screen reader labelling on the question bank, so buttons and checkboxes are announced correctly.

## [v3.10.1] - 2021-09-12

Corrects who can be given feedback, and fixes anonymous feedback.

### Changed

- Continuous integration moved from Travis CI to GitHub Actions.

### Fixed

- Feedback marked as anonymous is now treated as anonymous.
- Participants can only give feedback to people they are actually allowed to.
- Suspended participants and those whose enrolment has ended no longer appear in the participant list.

## [v3.10.0] - 2021-06-14

Accessibility improvements, and a move to GitHub Actions.

### Changed

- Report downloads use Moodle's current download API.
- Updated for Bootstrap 4.

### Fixed

- Accessibility improvements throughout the questionnaire.
- Compatibility with PHPUnit 9.

## [38.0.0] - 2020-06-26

First release for Moodle 3.8, adding a course-level list of activities.

### Added

- A course-level page listing the 360-degree feedback activities in that course.

### Changed

- Questions still in use by an activity can no longer be deleted.

### Fixed

- Compatibility with PHP 7.4.
- Errors when an activity had no participants yet.
- The activity description being altered when an activity was created.

## Earlier releases

These come from the Moodle 3.3 to 3.7 branches, which are no longer maintained. They are kept here for
reference; their tags remain in the repository.

## [37.1.0] - 2020-01-14

Moodle 3.7. The same changes as [38.0.0], without the PHP 7.4 work.

## [37.0.0] - 2019-06-22

Adds groups support, downloadable reports, calendar entries, and backup and restore.

Released alongside [36.1.0] and [35.4.0] for Moodle 3.6 and 3.5.

### Added

- Groups and groupings support, so participants only give feedback within their group.
- Feedback reports can be downloaded as a file.
- Activities appear in the calendar, with reminders to give feedback.
- Backup and restore support.

### Changed

- The activity's open and close dates are now enforced.
- Self-review is switched off for anonymous feedback.

### Fixed

- Assorted problems with feedback reports.

## [36.1.0] - 2019-06-22

Moodle 3.6. The same changes as [37.0.0].

## [35.4.0] - 2019-06-22

Moodle 3.5. The same changes as [37.0.0].

## [36.0.0] - 2019-03-03

Adds self-review, report release, and undoing declined feedback.

Released alongside [35.3.0] for Moodle 3.5.

### Added

- Feedback reports can be released to participants.
- Participants can undo feedback they declined to give.
- Self-review, so participants can give feedback on themselves.

### Changed

- Updated to Moodle's current privacy API.

### Fixed

- Permission checks when managing the question bank.

## [35.3.0] - 2019-03-04

Moodle 3.5. The same changes as [36.0.0].

## [34.4.0] - 2019-01-11

Moodle 3.4. Updates the activity to Moodle's current privacy API.

## [35.2.0] - 2018-09-21

Fixes permission checks in the question bank.

Released alongside [34.3.0] for Moodle 3.4.

## [34.3.0] - 2018-09-21

Moodle 3.4. The same changes as [35.2.0].

## [35.1.0] - 2018-07-25

Adds self-review.

Released alongside [34.2.0] for Moodle 3.4.

### Added

- Self-review, so participants can give feedback on themselves.

### Fixed

- The questionnaire heading and status display.

## [34.2.0] - 2018-07-25

Moodle 3.4. The same changes as [35.1.0].

## [35.0.0] - 2018-05-26

The first release of the 360-degree feedback activity.

Released alongside [34.1.0] and [33.1.0] for Moodle 3.4 and 3.3.

### Added

- The 360-degree feedback activity, where participants in a course give structured feedback to one another.
- A site-wide bank of rating and comment questions, from which each activity's questionnaire is built.
- Anonymous feedback, and the option for a participant to decline giving feedback.
- A feedback report for each participant.

## [34.1.0] - 2018-05-26

Moodle 3.4. The first release for this Moodle version, with the same features as [35.0.0].

## [33.1.0] - 2018-05-26

Moodle 3.3. The first release for this Moodle version, with the same features as [35.0.0].

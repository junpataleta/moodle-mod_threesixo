# 360° Feedback (mod_threesixo)

[![Build Status](https://github.com/junpataleta/moodle-mod_threesixo/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/junpataleta/moodle-mod_threesixo/actions/workflows/moodle-ci.yml)
[![codecov](https://codecov.io/github/junpataleta/moodle-mod_threesixo/graph/badge.svg?token=45OB4RGUK9)](https://codecov.io/github/junpataleta/moodle-mod_threesixo)

`mod_threesixo` is a Moodle activity plugin for 360-degree peer feedback. Teachers and course managers can build feedback activities from a shared question bank, assign participants, collect responses, and release personalised reports — all within the standard Moodle course environment.

## Features

- **Shared question bank** — Create and reuse rated (scale-based) and open-ended comment questions across multiple feedback instances.
- **Flexible participant scoping** — Target all enrolled course participants, or restrict feedback to users with a specific role.
- **Anonymous feedback** — Optionally hide the identity of feedback givers. Self-review is automatically disabled when anonymity is turned on.
- **Self-review** — Allow participants to complete a questionnaire about themselves alongside feedback they provide to others.
- **Configurable report releasing** — Control when feedback reports become visible: immediately on submission, manually by the teacher, automatically when the activity closes, or kept closed entirely.
- **Declined feedback with optional undo** — Participants can decline to give feedback to a specific person. When enabled, a declined submission can also be reversed.
- **Timed availability** — Set optional open and close dates to define the submission window.
- **Report download** — Export individual feedback reports in supported Moodle data formats.
- **Submission tracking** — Monitor each participant's feedback status across the activity at a glance.
- **Granular question capabilities** — Fine-grained access control over who can edit or delete shared questions, including ownership-based restrictions for questions created by other users.
- **Full backup and restore** — Activity data is fully supported by Moodle's standard backup and restore framework.

## Requirements

- **Moodle** 5.1 or later
- **PHP** 8.3 or later, matching the parent Moodle installation

> **Version compatibility**
> - Moodle 4.1–4.5: use plugin version [`v4.1.1`](https://github.com/junpataleta/moodle-mod_threesixo/releases/tag/v4.1.1)
> - Moodle 5.0: use plugin version [`v5.0.x`](https://github.com/junpataleta/moodle-mod_threesixo/releases/tag/v5.0.1)
> - Moodle 5.1+: use the current [`main`](https://github.com/junpataleta/moodle-mod_threesixo/tree/main) branch or the latest release

## Installation

### Via the Moodle Plugin Installer (recommended)

1. Log in to your Moodle site as an administrator.
2. Go to **Site administration → Plugins → Install plugins**.
3. Upload the plugin ZIP file, or search for `threesixo` or `360° feedback` in the Moodle Plugins directory.
4. Follow the on-screen prompts to complete installation and run any database upgrades.

### Manual installation

1. Download or clone this repository into `{moodle_root}/mod/threesixo/`.
2. Log in as an administrator and go to **Site administration → Notifications** to trigger the upgrade process.

## Configuration

When adding a 360° Feedback activity to a course, the activity form exposes the following settings:

| Setting | Description |
|---|---|
| **Name** | The activity name displayed to participants. |
| **Anonymous** | Hides the identity of feedback givers. Disables self-review when enabled. |
| **Enable self-review** | Allows participants to submit a questionnaire about themselves. |
| **Participants** | Scopes feedback to a specific enrolment role, or targets all enrolled users. |
| **Releasing** | Controls when reports are released: closed, open immediately, manually by the teacher, or automatically when the activity closes. |
| **Allow undo decline** | Permits participants to reverse a previously declined feedback submission. |
| **Open date / Close date** | Optional dates defining the feedback submission window. |

## Capabilities

The following capabilities govern question bank management and can be granted to roles at the **system context**:

| Capability | Description |
|---|---|
| `mod/threesixo:editothersquestions` | Edit questions created by other users. |
| `mod/threesixo:deleteothersquestions` | Delete questions created by other users. |

Site administrators hold these capabilities by default. Questions that existed before v5.0.0 are treated as unowned and can only be managed by administrators or users who hold the relevant capability.

## Main workflow

1. **Build the question bank** — Create rated or comment-based questions, or reuse existing ones from the shared bank.
2. **Set up the activity** — Add a 360° Feedback instance to a course and configure its settings, including anonymity, report releasing, and availability dates.
3. **Add questions** — Select questions from the question bank to include in the questionnaire. Use the "select all" toggle to add multiple questions at once.
4. **Assign participants** — Choose which enrolled users will give and receive feedback.
5. **Open the activity** — Make the activity available within the defined time window so participants can complete their submissions.
6. **Monitor progress** — Track submission statuses across all participants from the activity overview.
7. **Release reports** — Review responses and release individual feedback reports according to the configured releasing policy.

## Development notes

### Running tests

#### PHPUnit

Run PHPUnit tests from the Moodle root:

```bash
vendor/bin/phpunit --testsuite mod_threesixo_testsuite
```

#### Behat

Assuming your development site is already set up with Moodle's standard Behat configuration, you can run Behat tests by:

```bash
php public/admin/tool/behat/cli/run.php --tags=@mod_threesixo
```

### CI/CD

Continuous integration runs via GitHub Actions using [`moodle-plugin-ci`](https://github.com/moodlehq/moodle-plugin-ci). Code coverage is tracked on [Codecov](https://codecov.io/github/junpataleta/moodle-mod_threesixo).

## Contributing

Bug reports, fixes, and improvements are welcome. Please follow the standard Moodle plugin contribution workflow:

- Open an issue to describe the problem or feature before submitting a pull request.
- Ensure any changed behaviour is covered by PHPUnit or Behat tests.
- Code must pass PHPCS checks against Moodle's [PHP coding style](https://moodledev.io/general/development/policies/codingstyle), which is enforced in CI.

## Support the project

If `mod_threesixo` is useful to you, consider supporting its ongoing development:

- ☕ [Buy Me a Coffee](https://buymeacoffee.com/jpataleta)
- 💛 [GitHub Sponsors](https://github.com/sponsors/junpataleta)

## License

This plugin is released under the [GNU General Public License v3.0](LICENSE).

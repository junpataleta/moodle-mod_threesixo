@mod @mod_threesixo @javascript
Feature: View the feedback report for a participant
  In order to act on the feedback my peers have given me
  As a facilitator
  I need to see the ratings and comments a participant was given

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity  | name          | intro            | course | idnumber | with_self_review | participantrolename | anonymous |
      | threesixo | Team feedback | Test description | C1     | review1  | 0                | student             | 1         |

  @accessibility
  Scenario: A teacher can see the ratings a participant was given
    Given I am on the "review1" activity page logged in as "student1"
    And I click on "Provide feedback" "link" in the "Student 2" "table_row"
    And I click on "Strongly agree" "radio" in the "Treats co-workers with courtesy and respect." "fieldset"
    And I click on "Strongly agree" "radio" in the "Has a positive attitude." "fieldset"
    And I click on "Strongly agree" "radio" in the "Has initiative needed without relying on co-workers unnecessarily." "fieldset"
    And I click on "Strongly agree" "radio" in the "Can capably lead projects effectively." "fieldset"
    And I click on "Strongly agree" "radio" in the "Possesses strong technical skills for their position." "fieldset"
    And I click on "Strongly agree" "radio" in the "Appears to be efficient and well organised." "fieldset"
    And I click on "Strongly agree" "radio" in the "Delivers on their commitments." "fieldset"
    And I click on "Strongly agree" "radio" in the "Contributes to the successful functioning of the team." "fieldset"
    And I click on "Strongly agree" "radio" in the "Has good communication skills both verbal and written." "fieldset"
    And I click on "Strongly agree" "radio" in the "Expresses thoughts, opinions, and ideas, in meetings and discussions." "fieldset"
    And I click on "Strongly agree" "radio" in the "Explains ideas clearly." "fieldset"
    And I click on "Strongly agree" "radio" in the "Makes an effort to listen and tries to understand other people's ideas." "fieldset"
    And I press "Submit"
    # Finalising an anonymous instance asks for confirmation first, which is another dialogue to check.
    And the "Finalise anonymous feedback" "dialogue" should meet accessibility standards
    And I click on "Finalise" "button" in the "Finalise anonymous feedback" "dialogue"
    And I should see "Completed" in the "Student 2" "table_row"
    And I log out
    And I am on the "review1" activity page logged in as "teacher1"
    When I click on "View feedback for user" "link" in the "Student 2" "table_row"
    Then I should see "Average rating"
    And the page should meet accessibility standards

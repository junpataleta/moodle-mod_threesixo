@mod @mod_threesixo @javascript
Feature: Provide a feedback to a participant
  In order for me to be able to provide feedback to my peers
  As a user
  I need to be able to fill out the 360-degree feedback questionnaire

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | teacher2 | Teacher   | 2        | teacher2@example.com  |
      | student1 | Student   | 1        | student1@example.com  |
      | student2 | Student   | 2        | student2@example.com  |
      | student3 | Student   | 3        | student3@example.com  |
      | student4 | Student   | 4        | student4@example.com  |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C1      | editingteacher  |
      | teacher2  | C1      | editingteacher  |
      | student1  | C1      | student         |
      | student2  | C1      | student         |
      | student3  | C1      | student         |
      | student4  | C1      | student         |

  Scenario: Provide a non-anonymous feedback to another participant
    Given the following "activities" exist:
      | activity  | name          | intro            | course  | idnumber  | with_self_review | participantrolename | anonymous |
      | threesixo | Team feedback | Test description | C1      | review1   | 0                | student             | 0         |
    And I am on the "review1" activity page logged in as "student1"
    And I click on "Provide feedback" "link" in the "Student 2" "table_row"
    And I click on "Strongly agree" "radio" in the "Treats co-workers with courtesy and respect" "fieldset"
    And I click on "Agree" "radio" in the "Has a positive attitude." "fieldset"
    And I press "Save changes"
    And I follow "Cancel"
    And I should see "In progress" in the "Student 2" "table_row"
    When I click on "Provide feedback" "link" in the "Student 2" "table_row"
    Then the field "Strongly agree" in the "Treats co-workers with courtesy and respect" "fieldset" matches value "6"
    And the field "Agree" in the "Has a positive attitude" "fieldset" matches value "5"

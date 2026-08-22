@mod @mod_threesixo @javascript
Feature: Edit the items for the 360-degree feedback questionnaire
  In order for the feedback participants to provide feedback
  As a facilitator
  I need to be able to edit the list of questions that will appear in the 360-degree feedback activity

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student1@example.com  |
      | student2 | Student   | 2        | student2@example.com  |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C1      | editingteacher  |
      | student1  | C1      | student         |
      | student2  | C1      | student         |
    And the following "activities" exist:
      | activity  | name          | intro            | course  | idnumber  |
      | threesixo | Team feedback | Test description | C1      | review1   |

  Scenario: Add question to the 360 questionnaire
    Given I am on the "Team feedback" "threesixo activity" page logged in as "teacher1"
    And I follow "Edit 360° feedback items"
    And I press "Pick a question from the question bank"
    And I press "Add a new question"
    And I set the field "Question text" to "Question 1"
    And I click on "Save changes" "button" in the "Add a new question" "dialogue"
    And I click on "Question 1" "checkbox"
    When I click on "Save changes" "button" in the "Pick a question from the question bank" "dialogue"
    Then I should see "Question 1" in the "360° feedback questions" "table"

  Scenario: Delete question from the 360 questionnaire
    Given I am on the "Team feedback" "threesixo activity" page logged in as "teacher1"
    And I follow "Edit 360° feedback items"
    And I should see "Treats co-workers with courtesy and respect" in the "360° feedback questions" "table"
    When I click on "Delete question" "button" in the "Treats co-workers with courtesy and respect" "table_row"
    Then I should not see "Treats co-workers with courtesy and respect" in the "360° feedback questions" "table"

  Scenario: Questions can no longer be modified once a respondent has provided feedback
    Given I am on the "review1" activity page logged in as "student1"
    And I click on "Provide feedback" "link" in the "Student 2" "table_row"
    And I click on "Strongly agree" "radio" in the "Treats co-workers with courtesy and respect" "fieldset"
    And I press "Save changes"
    And I log out
    When I am on the "Team feedback" "threesixo activity" page logged in as "teacher1"
    And I follow "Edit 360° feedback items"
    Then I should see "The questions can no longer be modified"
    And I should see "Treats co-workers with courtesy and respect" in the "360° feedback questions" "table"
    And "Pick a question from the question bank" "button" should not exist
    And "Delete question" "button" should not exist
    And "Move item down" "button" should not exist

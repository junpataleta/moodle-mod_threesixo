@mod @mod_threesixo
Feature: Check the participants list of a 360-degree feedback instance in various modes
  In order for me to be able to effectively provide feedback to my peers
  As a user
  I need to be able to see the correct list of participants that I need to provide feedback for

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
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
      | Group 3 | C1     | G3       |
    And the following "groupings" exist:
      | name        | course | idnumber |
      | Grouping A  | C1     | GA       |
      | Grouping B  | C1     | GB       |
    And the following "group members" exist:
      | user        | group |
      | teacher1    | G1    |
      | teacher1    | G2    |
      | teacher2    | G2    |
      | teacher2    | G3    |
      | student1    | G1    |
      | student2    | G1    |
      | student2    | G2    |
      | student3    | G2    |
      | student3    | G3    |
      | student4    | G3    |
    And the following "grouping groups" exist:
      | grouping  | group   |
      | GA        | G1 |
      | GA        | G2 |
      | GB        | G2 |
      | GB        | G3 |

  Scenario Outline: Check list of threesixo participants in various configurations
    Given the following "activities" exist:
      | activity  | name          | intro            | course  | idnumber  | with_self_review | participantrolename | groupmode   |
      | threesixo | Team feedback | Test description | C1      | review1   | <selfreview>     | <role>              | <groupmode> |
    And I log in as "<respondent>"
    And I am on "Course 1" course homepage
    When I follow "Team feedback"
    Then I should <expectt1> "Teacher 1" in the "participants" "table"
    And I should <expectt2> "Teacher 2" in the "participants" "table"
    And I should <expects1> "Student 1" in the "participants" "table"
    And I should <expects2> "Student 2" in the "participants" "table"
    And I should <expects3> "Student 3" in the "participants" "table"
    And I should <expects4> "Student 4" in the "participants" "table"

    Examples:
      | respondent | selfreview | role           | groupmode | expectt1 | expectt2 | expects1 | expects2 | expects3 | expects4 |
      # All roles, without groups and groupings.
      | teacher1   | 0          | 0              | 0         | not see  | see      | see      | see      | see      | see      |
      | student1   | 0          | 0              | 0         | see      | see      | not see  | see      | see      | see      |
      # All roles, <groupmodetext> groups, no groupings.
      | teacher1   | 0          | 0              | 1         | not see  | see      | see      | see      | see      | see      |
      | student1   | 0          | 0              | 1         | see      | not see  | not see  | see      | not see  | not see  |
      # All roles, visible groups, no groupings.
      | teacher1   | 0          | 0              | 2         | not see  | see      | see      | see      | see      | see      |
      | student1   | 0          | 0              | 2         | see      | not see  | not see  | see      | not see  | not see  |
      # Teachers only, without groups and groupings.
      | teacher1   | 0          | editingteacher | 0         | not see  | see      | see      | see      | see      | see      |
      | student1   | 0          | editingteacher | 0         | not see  | not see  | not see  | not see  | not see  | not see  |
      # Teachers only, <groupmodetext> groups, no groupings.
      | teacher1   | 0          | editingteacher | 1         | not see  | see      | see      | see      | see      | see      |
      | student1   | 0          | editingteacher | 1         | not see  | not see  | not see  | not see  | not see  | not see  |
      # Teachers only, visible groups, no groupings.
      | teacher1   | 0          | editingteacher | 2         | not see  | see      | see      | see      | see      | see      |
      | student1   | 0          | editingteacher | 2         | not see  | not see  | not see  | not see  | not see  | not see  |
      # Students only, without groups and groupings.
      | teacher1   | 0          | student        | 0         | not see  | see      | see      | see      | see      | see      |
      | student1   | 0          | student        | 0         | not see  | not see  | not see  | see      | see      | see      |
      # Students only, <groupmodetext> groups, no groupings.
      | teacher1   | 0          | student        | 1         | not see  | see      | see      | see      | see      | see      |
      | student1   | 0          | student        | 1         | not see  | not see  | not see  | see      | not see  | not see  |
      # Students only, visible groups, no groupings.
      | teacher1   | 0          | student        | 2         | not see  | see      | see      | see      | see      | see      |
      | student1   | 0          | student        | 2         | not see  | not see  | not see  | see      | not see  | not see  |
      # All roles, with self-review.
      | teacher1   | 1          | 0              | 0         | see      | see      | see      | see      | see      | see      |
      | student1   | 1          | 0              | 0         | see      | see      | see      | see      | see      | see      |
      # Teachers only, with self-review.
      | teacher1   | 1          | editingteacher | 0         | see      | see      | see      | see      | see      | see      |
      | student1   | 1          | editingteacher | 0         | not see  | not see  | not see  | not see  | not see  | not see  |
      # Students only, with self-review.
      | teacher1   | 1          | student        | 0         | not see  | see      | see      | see      | see      | see      |
      | student1   | 1          | student        | 0         | not see  | not see  | see      | see      | see      | see      |

  Scenario Outline: Check list of threesixo participants in different groups mode with grouping configuration as a teacher
    Given the following "activities" exist:
      | activity  | name          | intro            | course  | idnumber  | groupmode   | grouping |
      | threesixo | Team feedback | Test description | C1      | review1   | <groupmode> | GA       |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I follow "Team feedback"
    Then the "<groupmodetext> groups (Grouping A)" select box should contain "All participants"
    And the "<groupmodetext> groups (Grouping A)" select box should contain "Group 1"
    And the "<groupmodetext> groups (Grouping A)" select box should contain "Group 2"
    And the "<groupmodetext> groups (Grouping A)" select box should not contain "Group 3"
    And I select "All participants" from the "<groupmodetext> groups (Grouping A)" singleselect
    And I should see "Teacher 2" in the "participants" "table"
    And I should see "Student 1" in the "participants" "table"
    And I should see "Student 2" in the "participants" "table"
    And I should see "Student 3" in the "participants" "table"
    And I should see "Student 4" in the "participants" "table"
    But I should not see "Teacher 1" in the "participants" "table"
    And I select "Group 1" from the "<groupmodetext> groups (Grouping A)" singleselect
    And I should see "Student 1" in the "participants" "table"
    And I should see "Student 2" in the "participants" "table"
    But I should not see "Teacher 1" in the "participants" "table"
    And I should not see "Teacher 2" in the "participants" "table"
    And I should not see "Student 3" in the "participants" "table"
    And I should not see "Student 4" in the "participants" "table"
    And I select "Group 2" from the "<groupmodetext> groups (Grouping A)" singleselect
    And I should see "Teacher 2" in the "participants" "table"
    And I should see "Student 2" in the "participants" "table"
    And I should see "Student 3" in the "participants" "table"
    But I should not see "Teacher 1" in the "participants" "table"
    And I should not see "Student 1" in the "participants" "table"
    And I should not see "Student 4" in the "participants" "table"

    Examples:
      | groupmode | groupmodetext |
      | 1         | Separate      |
      | 2         | Visible       |

  Scenario: Check list of threesixo participants in separate groups mode with grouping configuration as a student
    Given the following "activities" exist:
      | activity  | name          | intro            | course  | idnumber  | groupmode   | grouping |
      | threesixo | Team feedback | Test description | C1      | review1   | 1           | GA       |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Team feedback"
    Then I should see "Separate groups: Group 1"
    And I should see "Teacher 1" in the "participants" "table"
    And I should see "Student 2" in the "participants" "table"
    But I should not see "Teacher 2" in the "participants" "table"
    And I should not see "Student 1" in the "participants" "table"
    And I should not see "Student 3" in the "participants" "table"
    And I should not see "Student 4" in the "participants" "table"

  Scenario: Check list of threesixo participants in visible groups mode with grouping configuration as a student
    Given the following "activities" exist:
      | activity  | name          | intro            | course  | idnumber  | groupmode  | grouping |
      | threesixo | Team feedback | Test description | C1      | review1   | 2          | GA       |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Team feedback"
    Then the "Visible groups" select box should contain "Group 1"
    And the "Visible groups" select box should contain "Group 2"
    But the "Visible groups" select box should not contain "All participants"
    And the "Visible groups" select box should not contain "Group 3"
    And I select "Group 1" from the "Visible groups" singleselect
    And I should see "Teacher 1" in the "participants" "table"
    And I should see "Student 2" in the "participants" "table"
    But I should not see "Teacher 2" in the "participants" "table"
    And I should not see "Student 1" in the "participants" "table"
    And I should not see "Student 3" in the "participants" "table"
    And I should not see "Student 4" in the "participants" "table"
    And I select "Group 2" from the "Visible groups" singleselect
    And I should see "Teacher 1" in the "participants" "table"
    And I should see "Teacher 2" in the "participants" "table"
    And I should see "Student 2" in the "participants" "table"
    And I should see "Student 3" in the "participants" "table"
    But I should not see "Student 1" in the "participants" "table"
    And I should not see "Student 4" in the "participants" "table"

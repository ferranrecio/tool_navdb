@tool @tool_navdb
Feature: Show filtered table records
  In order to navigate through database
  As an administrator
  I need to see the list of records filtered

  Background:
    GGiven the following "roles" exist:
      | name                   | shortname | description      | archetype      |
      | Custom editing teacher | custom1   | My custom role 1 | editingteacher |
      | Custom student         | custom2   | My custom role 2 | student        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | Basic     | 1        | user1@example.com |
      | user2    | Basic     | 2        | user2@example.com |
      | user3    | Alter     | 3        | user3@example.com |
      | user4    | Alter     | 4        | user4@example.com |
      | user5    | Alter     | 5        | user5@example.com |
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | CAT1     |
      | Course 2 | C2        | CAT1     |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | user1 | C1     | student        |
      | user2 | C1     | student        |
      | user3 | C1     | student        |
      | user4 | C1     | editingteacher |
      | user5 | C1     | editingteacher |
      | user1 | C2     | student        |
      | user2 | C2     | student        |
      | user3 | C2     | student        |
      | user4 | C2     | editingteacher |
      | user5 | C2     | editingteacher |
    And I log in as "admin"
    And I navigate to "Development > Database Navigation" in site administration

  Scenario: display all users
    When I set the field "Table" to "user"
    And I set the field "Filter" to "*"
    And I set the field "Limit" to "500"
    And I set the field "Limit From" to "0"
    And I click on "Execute query" "button"
    Then I should see "user1"
    And I should see "user2"
    And I should see "user3"
    And I should see "user4"
    And I should see "user5"

  Scenario: Filter users
    When I set the field "Table" to "user"
    And I set the field "Filter" to "firstname='Basic'"
    And I set the field "Limit" to "500"
    And I set the field "Limit From" to "0"
    And I click on "Execute query" "button"
    Then I should see "user1"
    And I should see "user2"
    And I should not see "user3"
    And I should not see "user4"
    And I should not see "user5"
    And I should see "policyagreed" in the "table.tableview th.col_policyagreed" "css_element"
    And I should see "Row count: 2"

  Scenario: Single row view
    When I set the field "Table" to "user"
    And I set the field "Filter" to "username='user1'"
    And I set the field "Limit" to "500"
    And I set the field "Limit From" to "0"
    And I click on "Execute query" "button"
    Then I should see "user1"
    And I should not see "user2"
    And I should not see "user3"
    And I should not see "user4"
    And I should not see "user5"
    And I should see "policyagreed" in the "table.singleview th.col_policyagreed" "css_element"

  Scenario: No records found
    When I set the field "Table" to "user"
    And I set the field "Filter" to "username='nonexistent'"
    And I set the field "Limit" to "500"
    And I set the field "Limit From" to "0"
    And I click on "Execute query" "button"
    Then I should see "No results returned for filter username='nonexistent' on table user"

  Scenario: Move from multiple records to single view via ID link
    When I set the field "Table" to "user"
    And I set the field "Filter" to "firstname='Basic'"
    And I set the field "Limit" to "500"
    And I set the field "Limit From" to "0"
    And I click on "Execute query" "button"
    Then I should see "user1"
    And I should see "user2"
    And I should not see "user3"
    And I should see "policyagreed" in the "table.tableview th.col_policyagreed" "css_element"
    And I click on "//tr[@class='rownum_0']/td[@class='field col_id']/a" "xpath_element"
    And I should see "policyagreed" in the "table.singleview th.col_policyagreed" "css_element"

  Scenario: Navigate from user to role_assignments, context, course
    When I set the field "Table" to "user"
    And I set the field "Filter" to "username='user1'"
    And I set the field "Limit" to "500"
    And I set the field "Limit From" to "0"
    And I click on "Execute query" "button"
    Then I should see "user1"
    And I follow "Go to role_assignments record"
    And I should see "roleid"
    And I should see "contextid"
    And I should see "userid"
    And I should see "Row count: 2"
    And I click on ".rownum_0 .col_contextid a" "css_element"
    And I click on ".row_instanceid .col_instanceid a" "css_element"
    And I should see "Course 1"

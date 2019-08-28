@tool @tool_navdb
Feature: Visualize singular table filter form
  In order to navigate through database
  As an administrator
  I need to see the list of tables in the DB and click on one

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | testuser | Test | User | moodle@example.com |
    Given the following "courses" exist:
      | fullname        | shortname   |
      | Course fullname | C_shortname |
    And I log in as "admin"
    And I navigate to "Development > Database Navigation" in site administration

  Scenario: Display user_enrolments table form
    And I follow "user_enrolments"
    # Having clicked on it, I should also see the list of tables.
    And I should see "status"

  Scenario: Non existent table
    When I set the field "table" to "non_existent_table"
    # And I press "Execute query"
    And I click on "Execute query" "button"

    # Once filtered only some tables must appear
    Then I should see "Table non_existent_table not found"

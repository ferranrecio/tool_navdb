@tool @tool_navdb
Feature: Manage DB table list
  In order to navigate through database
  As an administrator
  I need to see the list of tables in the DB and click on one

  Scenario: Display navdb option in admin menu
    Given I log in as "admin"
    And I press "Customise this page"
    And I add the "Administration" block if not present
    And I expand "Site administration" node
    When I expand "Development" node
    Then I should see "Database Navigation"

  Scenario: Display table list
    Given I log in as "admin"
    And I navigate to "Development > Database Navigation" in site administration

    # Having clicked on it, I should also see the list of tables.
    Then I should see "Current Navigation"
    And I should see "course_modules"

  Scenario: Display table list
    Given I log in as "admin"
    And I navigate to "Development > Database Navigation" in site administration
    And I set the field "filter" to "course_mod"

    # Once filtered only some tables must appear
    Then I should see "course_modules"
    And I should see "course_modules_completion"

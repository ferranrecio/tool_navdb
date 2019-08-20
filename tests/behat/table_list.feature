@tool @tool_navdb
Feature: Manage DB table list
  In order to navigate through database
  As an administrator
  I need to see the list of tables in the DB and click on one

  @javascript
  Scenario: Display navdb option in admin menu
    Given I log in as "admin"
    And I press "Customise this page"
    And I add the "Administration" block if not present
    And I expand "Site administration" node
    When I expand "Development" node
    Then I should see "Database Navigation"

    # Go to Databse Navitagion
    And I am on homepage
    # And I navigate to "Database Navigation" node in "Site administration > Development"
    And I navigate to "Development > Database Navigation" in site administration

    # Having clicked on it, I should also see the list of tables.
    And I should see "Current Navigation"
    And I should see "auth_oauth2_linked_login"

#  @javascript
#  Scenario: Filter table list
#    And I log in as "admin"
#    And I am on site homepage
#    When I navigate to "Database Navigation" node in "Site administration > Development"
#
#    # Check the filter input exists
#    Then "Hide" "icon" should exist in the "Restriction by date" "table_row"
#
#    # Click the icon. It should toggle to hidden (title=Show).
#    And I click on "Hide" "icon" in the "Restriction by date" "table_row"
#    And "Show" "icon" should exist in the "Restriction by date" "table_row"
#
#    # Toggle it back to visible (title=Hide).
#    And I click on "Show" "icon" in the "Restriction by date" "table_row"
#    And "Hide" "icon" should exist in the "Restriction by date" "table_row"
#
#    # OK, toggling works. Set the grade one to Hide and we'll go see if it actually worked.
#    And I click on "Hide" "icon" in the "Restriction by grade" "table_row"
#    And I am on "Course 1" course homepage with editing mode on
#    And I add a "Page" to section "1"
#    And I expand all fieldsets
#    And I click on "Add restriction..." "button"
#    And "Add restriction..." "dialogue" should be visible
#    And "Date" "button" should exist in the "Add restriction..." "dialogue"
#    And "Grade" "button" should not exist in the "Add restriction..." "dialogue"

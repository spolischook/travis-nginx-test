Feature: Change menu view
  In order to have best user experience for working with menu
  As an administrator
  I want to change menu view from configuration and navigate to menu

Scenario: Try navigate on top menu
  Given I login as "admin" user with "admin" password
  And menu is at the top
  And I go to System/User Management/Users
  When click view John Doe in grid
  And username field should have admin value

Scenario: Change menu view
  Given I go to System/Configuration
  And follow "Display settings"
  And uncheck Use Default for "Position" field
  And select "Left" from "Position"
  When I save setting
  Then menu must be on left side

Scenario: Try to navigate on left menu
  Given I go to System/User Management/Users
  When click view John Doe in grid
  Then username field should have admin value

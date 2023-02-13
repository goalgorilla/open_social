@disabled @install @stability @javascript
  Feature: Install through web UI
  Benefit: In order to let our Drupal users install through the interface
  Role: AN
  Goal/desire: Install Open Social

  Scenario: I want to install Open Social via the web browser
    Given I reset the Open Social install

    And I am on "/core/install.php"
    And I should see "Open Social"
    And I press the "Save and continue" button
    And task "Choose language" is done

#    Then I should see "Requirements review"
#    And I should see "continue anyway"
#    When I follow "continue anyway"
    Then task "Verify requirements" is done

#    This is already coming from our settings.php, no need to test it but for clarity:
#    Then I fill in "edit-mysql-database" with "social"
#    Then I fill in "edit-mysql-username" with "root"
#    Then I fill in "edit-mysql-password" with "root"
#    Then I fill in "edit-mysql-host" with "db"
#    Then I fill in "edit-mysql-port" with "3306"
#    Then I submit the form
    And task "Set up database" is done

    And I should see "Install optional modules"
    And I should see "Enable additional features"
    And I should see checked the box "edit-optional-modules-social-group-flexible-group"
    When I press the "Save and continue" button
    Then I should see "Installing Open Social"
    And I wait for the installer to finish

    Then I should see "Configure site"
    And I fill in "site_name" with "Some site name"
    And I fill in "site_mail" with "site@example.com"
    And I fill in "edit-account-name" with "admin"
    And I fill in "edit-account-pass-pass1" with "password"
    And I fill in "edit-account-pass-pass2" with "password"
    And I fill in "edit-account-mail" with "admin@example.com"

    And I uncheck the box "edit-enable-update-status-emails"
    And I uncheck the box "edit-enable-update-status-module"
    And I press the "Save and continue" button
    Then task "Configure site" is done
    And I am on the homepage

# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

## [Unreleased]
[Unreleased]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.4...HEAD

## [1.8.5] - 2018-10-10
[1.8.5]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.4...1.8.5
### Added
- Survey answers can now be exported to R format (#213)

### Changed
- Export classes can now add instructions for the downloaded file(s) (#371)
- The getRespondent() method in a controller was changed to public to allow better logging (#360)
- The meta.Content-Security-Policy was moved to the headers section (#352)
- Changelog now allows .md extension for markdown formatting, including github issue links (#351)
- Mailjobs can now be executed manually. This allows a combination of automatic and semi automatic as well as deactived jobs (#361)
- Agenda setup now allows to select on the filter attribute (#353)
- SPSS export no longer cuts of text answers at 64 chars and will default to numeric more often for list type answers (#335)
- Communication templates now use token as default source instead of staff (#367)

### Removed
- The old ExcelHtml and Stata exports were removed, the new Excel and Stata exports remain (#342)
- getFullQuestionList was removed from LimeSurvey source, as it was not in the interface and unused (#186)

### Fixed
- Do not show name in Compliance and Field overview when user may not see the name (#374)
- Programming errors show debug trace in error log (#373)
- List elements in forms are no longer translated if form is set to disable translator (#370)
- Logging the organization for is improved, and logging survey export is now on by default (#360 #242)
- While browsing database tables the pagination now works when the number of items is changed (#346)
- Answer import of csv files now autosenses for colon or semicolon separator (#358)
- Bigger files can be handled during import without running out of memory (#354)
- LimeSurvey source now supports the ranking question (#341)
- Deleted tokens can be found again in overviews (#356)
- LDAP user domain is no longer hardcoded (#350)
- Respondent email can be set to empty when importing (#349)
- Tokens dates are updated when condition changes (#349)
- Fixes for login sequence (#363 #347 #365)
- Appointments can create a new track when there is no pre-existing track (#355)
- When viewing a mailjob selected token overview can be sorted by clicking on headers (#366)

## [1.8.4] - 2018-08-20
[1.8.4]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.3...1.8.4
### Added
- Appointments can now be grouped into HL7 care episodes (#306)
- Conditions can determine if a round is applicable or not (#42), this reduces the need for track events
- New before answering and after completion events synchronize track fields with code the same as answer codes (#55)
- More options for appointments to create a track (#294)
- Patients van have different e-mail addresses at each organization in a project (#310)
- Privileges can be exported with their assignments (#313)

### Fixed
- Many rare bugs solved and speed and interface improvements
- Reset password did not work when the password was expired (#307)
- Sort links stopped working after search (#312)

### Interface improvements
- Added token states incomplete and partially answered (#280)
- Answers of partially answered tokens can be seen (#280)
- Groups and organizations IP Filters can use subnet masks and asterisk range notations, including IP6 addresses (#28)
- Monitor job overviews (#194)
- Token status is shown in show token screen (#280)

### Programmability
- Simplified project specific login procedures (#298)
- Projects can allow organizations to look into each others tokens and tracks (#300)
- Upgrade Compatibility Checks for deprecated project code (#269)

### Security
- CSV Injection protection
- Enhanced parameter filtering for added security (#298)
- Enhanced password hashing (#177, #209, #257)
- LDAP Authentication enabled (#317)
- Two factor authentication for users (#237)

### Track Builder
- Conditions (on track fields) can be set for rounds (#42)
- Extended Open Rosa support including for nested rows and survey answering
- Organizations can share tracks and tokens (#300)
- Track can be created from appointments even when an older track is open (#294)
- When redoing a survey, the answers are injected before answering instead of during replacement (#301)

## [1.8.3]
[1.8.3]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.2...1.8.3
### Added
- Automatic mail
  - Email can now also be sent x days before survey expiry

## [1.8.2]
[1.8.2]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.1...1.8.2
### Added
- You can change the organization(s) a patient belongs to (and move his/her tracks)
- Correct token button added to menu
- Memo field type for tracks
- (Re)import imported files

- Automatic mail
  - Preview option for automatic mailjobs
  - Automatic mailjobs can now filter for relations, check your jobs!
  - Automatic mail execution can be logged to file when set in the project.ini
- Interface improvements:
  - When a token is corrected links are visible when seeing the token to open the original / copy
  - Improved interface for communication templates and automatic mail
  - The Contact => Bugs and GemsTracker pages have been refreshed with a default bugs url (use Roles to make invisible)
  - Editing staff and organizations uses extra defaults for smoother creation
  - Different respondent search, edit and show screens can be set for a user group
  - Different respondent edit and show screens can be set for each organization, overruling user group settings
  - Different token ask screens can be set for each organization
- Export improvements:
  - Answers from inactive patients can now be exported
  - Depending on rights patient numbers, patient gender and birth year and month can be exported with answers
- Rights:
  - The types of staff that a user can create/edit are determined at the group level
  - A default group for new users can be set at the user group level
  - Roles only determine what menu items are allowed, you no longer need to have a right to assign it to another staff
  - Private data can now be hidden or (partially) masked for groups, e.g. researchers need not see patient names
  - Added "site administrator" role and group between local admin and super admin
  - Administrators may now have the right to switch the used group to any they may set
- Security:
  - The full Gems version number is only displayed after login
  - All recalculate, check, synchronize, patch and run commands log the item they are started for
  - Delete, deactivate and reactivate actions are logged correctly
  - New security headers and meta tags can be set from the project.ini
  - All staff users have to follow the password rules for staff, even if their role does not inherit staff
- Programmability:
  - Before field save events allows changing the fields after their new values have been calculated
  - Respondent changed events can be set at the organization level
  - It is easier to change part of the display in ShowTrackTokenSnippet
  - Less compile now uses relativeUrls during compilation, added logoFile and logoHeight variables

### Fixed
- Blocked users were not blocked
- Surveys could be answered during maintenance mode
- The APC Cache was cleared incorrectly
- The var/tmp directory is created when needed and it does not exist
- Users can no longer export data from patients in other organizations

- Testing:
  - On acceptance and demonstration mail only respondent mails are bounce, staff mails are sent to receiver
  - Administrators may now have the right to switch the used group to any they may set

## [1.8.1]
[1.8.1]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.0...1.8.1
### Added
- Rounds can be sorted using drag and drop

### Changed
- Round icons are now in the token table just like the round descriptions

### Automatic email
- Mail jobs are now executed in batch, one job at a time
- Individual jobs can be executed from the interface
- Jobs can be sorted manually using the sort button, check the sort order after upgrade!

### Export
- For survey responses, when available the respondent relation is exported (field name and relation id)
- Various bugfixes, check output carefully

## [1.8.0]
[1.8.0]: https://github.com/GemsTracker/gemstracker-library/compare/1.7.1...1.8.0
- Searching for respondents by track or lack of track
- Customize respondents screen using Snippets\Respondent\RespondentTableSnippet
- Track structure can be exported, imported and merged with an existing track
- Tracks answers, fields and rounds can be checked at the respondent and respondent track level
- (Re)checking for answers now possible at the single token level
- When using the gemsdata__responses table, views will be created for each survey
- A new after completion event allows the setting of the informed consent through a survey
- Fixed survey activation when survey not active in source
- LimeSurvey equation questions now use help text for question or question code when empty
- A new cron job checks whether the mail cron job has finished correctly
- The check cron job is also checked before each login
- Imports through the interface are logged in the activity log
- The menu remains fully visible when an error occurs
- Staff import now respects organization default user class
- Inserted surveys now have class 'inserted' added to the row in track overview
- Most search screens have been updated and all work the same

## Pre 1.8.0
Changes were deleted from the changelog. Check the history in [GitHub](https://github.com/GemsTracker/gemstracker-library) if you are really interested.
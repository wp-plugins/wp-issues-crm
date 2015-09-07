=== WP Issues CRM ===
Contributors: Will Brownsberger
Donate link: 
Tags: contact, crm, constituent, customer, issues, list
Requires at least: 4.0
Tested up to: 4.3
Stable tag: 2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CRM for offices that use Wordpress as their central communication tool, but have constituencies broader than their Wordpress user universe.

== Description ==

WP Issues CRM is a constituent/customer relationship management database designed for organizations that use Wordpress
to organize their content, but have constituencies broader than their Wordpress user universe.  It uses the post and category structure of 
Wordpress to define and classify issues, but uses custom tables for high performance access to a larger constituent database.  It offers
easy uploading for initial conversion and for automation of data input.  It also offers easy downloading for outgoing communications. 
Main forms are mobile friendly (responsive CSS). 

== Installation ==

1. Load WP Issues CRM through the Add New menu or install the zip file in the plugins directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go through the WP Issues CRM Settings page and make basic configuration decisions.
1. Consider your office use of information and add any necessary constituent fields under Fields.
1. If you are adding some "Select" fields -- some typology like political party -- define the options under Options.
1. If you are planning to import data from an existing CRM, redefine WP Issues CRM standard option sets like Email Type for consistency
with your previous system. 
1. Use the powerful WP Issues CRM upload subsystem to import data from your current CRM and/or from external sources. 

== Frequently Asked Questions ==

= There are no issues in the “Activity issue?” drop down. WTF? =

By default, WP Issues CRM 2.2.7 and higher uses an autocomplete that searches all issue titles (except those closed for assignment).  
However, if you are using an older version or have chosen to disable this (either globally in settings or as in individual in preferences), then
not all issues will appear in the activity drop down. WP Issues CRM allows you to control the legacy dropdown issues list to suit your workflow:

1. To cause an issue to always appear in the dropdown, go to the issue itself and select “Always appear in issue dropdown” under the Activity Tracking section. Don’t forget to then save the issue.
1. If you want your last viewed or recently used issues to automatically appear in the drop down, go to Settings and check Allow Issue Preferences. Save Settings. Then go to your own User Preferences. Check “Last viewed” to put your last viewed issue in the drop down. Select a “Last Used” option. In addition to showing open issues (those selected to “Always Appear”), you can show either your most recently used issues or the issues that you have most frequently used among the last 30 activities you have updated.
1. If you Select “Never appear in issue dropdown” under the Activity Tracking section for an issue, it will never appear in the dropdown, even if it is the last viewed or a recently or frequently used issue.

= Where I can I get support? =

For questions, the best route is the support forum at http://wordpress.org/support/plugin/wp-issues-crm. 

If necessary, please do contact the author at help@wp-issues-crm.com 
-- we welcome feedback and do want to know how we can continue to improve this product.  

= Where can I view documenation? =

Please visit http://wp-issues-crm.com.
  
== Screenshots ==

1. WP Issues CRM allows you to configure menu, privacy and security options.  You can also accelerate data entry by 
automatically serving users their latest issues visited when they are entering constituent activity information.  The
plugin also includes an interface to the USPS Postal Service for zip code lookup. 
2. You can create your own custom fields and define options for the custom dropdowns that you create.  You can also
customize options for built-in fields like email or address type.  
3. The new constituent add screen is very comfortable to use.
4. WP Issues CRM offers powerful search capability that gives quick response over large constituent databases.  Our
office experience is subsecond response time on a database of 200,000 constituents.
5. You can assign constituent cases to users and the list will highlight cases that are overdue for action.
6. You can create new "Issues" -- these are just Wordpress posts, but are created as private.  You can convert them to 
public posts at any time and edit through the regular Wordpress editor.  Issues are used to classify activities for constituents,
like incoming emails.
7. You can retrieve issue activity counts by period, activity type, issue and category.
8. You can easily upload data from other sources -- either for conversion or for daily office automation.
9. The Uploads function is intuitive and easy to use, but also gives you a lot of control and insight into the data you are uploading.
10. The Manage Storage function allows you to safely purge outdated data from external sources. 
== WP Issues CRM Upload Facility ==
WP Issues CRM now includes a flexible upload subsystem. The upload subsystem is designed to handle large uploads as in an initial setup and also
to support frequent smaller uploads to reduce manual data entry.
== Upload Features ==
* Handles common file .csv and txt file formats
* Learns your the field mappings for your repetitive file uploads
* Validates data transparently so that you can fix problems as they emerge
* Allows you to easily control the matching/deduping strategy and to test alternative approaches before finalizing your upload
* Allows you to add default data for an upload -- so, for example, you can upload a list and identify all on the list as having 
attended signed a petition related to an issue
* Automatically breaks every task and the final upload process into chunks to minimize memory and packet sizes and avoid exceeding system limits
* Allows you to download files documenting the results of your upload to allow, for example, the manual completion of records that failed in the upload
* Allows you to automatically backout some types of uploads
== Advanced Search ==
* Includes user friendly search screens for constituents and issues retrieval and update
* Includes powerful general search facility for selecting group with complex definitions
== WP Issues CRM Manage Storage Facility ==
WP Issue CRM now includes a facility to show storage usage and to selectively purge interim files and dated external data.  So, for example, suppose you 
initially uploaded your database from a voter list.  Over time, you added information about contacts with voters.  You could then easily purge all voters with 
no contacts and add a fresh voter list, matching to the voters that you kept to avoid duplication.
== Design of WP Issues CRM ==
WP Issues CRM uses a fully modular object-oriented design.  It is built around a data dictionary so that it is fundamentally 
flexible.  It uses code recursively so that with a small code base it can offer broadly extensible functionality.  We use
this product ourselves on a daily basis and we are committed to continuous long-term improvement of it.


== Changelog ==
= 2.3 =
* Add major new function to main page -- advanced search
* Alter activity download to return single rows for each activity for constituents with multiple addresses, emails or phones --
old logic selected address/phone email type 0 or null; new logic simply groups by activity and flags constituents that have
multiple values that have been consolidated  
* Bullet proof download against case where constituent creates custom fields with same label
= 2.2.72 =
* Simplify and clean up last_update tracking -- correctly reflect multivalue deletes and carry latest values to forms 
* Fix select logic for datepicker to apply to initially hidden fields other than the hidden row-templates
* Adjust match policy on uploads -- no matching to constituents that have been marked deleted 
= 2.2.71 =
* Fix missing display of last_updated_time and last_updated_by on Option Group Add screen
* Fix field delete button in Option Group Add screen 
= 2.2.7 =
* Add autocomplete for name and address fields
* Add title search for activity issue field
* Add ability to retrieve and download activity detail from the trend search facility 
* Add ability to track amount fields for activities if financial activity types are defined in settings
* Show/hide amounts as appropriate for financial and non-financial activity types in forms and activity new detail list
* Add street address to constituent lists
* Make range comparison operators <= and >= instead of < and > in all forms; support operator change in database access and form routines
* Admin header changed from h2 to h1 for Wordpress 4.3 compatibility
* Alter search history logic so that when viewing multiple entries in succession off a constituent or issue list, the back button will return to list not previous viewed entry
* Full testing of plugin with Wordpress 4.3 Release Candidate (4.3-RC2-33605) -- no changes needed
= 2.2.6 =
* Fix bug affecting multi-user installations -- table in search log database access not multi-user prefixed
* Automatically trim leading and trailing spaces from staging table values in upload process 
* Add developer contact information to WP Issues CRM crash message
* Fix sort order in search log retrieval
= 2.2.5 =
* Add hook to support exclusion of private posts from front end even for administrators when serving infinite scroll in theme Responsive Tabs
= 2.2.4.1 =
* Add FAQ link to constituent forms.
* CSS tweaks
= 2.2.4 =
* Add responsive CSS to support small screens (<960px) for most used functions -- issue/constituent add/search/update and also trends and search log
* Hide upload, storage, field and option management on small screen devices (<960px) -- complexity not amenable to small screens; also greater security risks to allow these functions on mobile devices
* Other CSS tweaks for useability
= 2.2.3 =
* Move required check for email_address and phone_number from validation stage to final upload stage to allow files missing data on some records to be uploaded in a single pass
* Allow update of readonly custom fields on upload
* Allow addition of custom fields while constituent table is empty (previously generated error)
* Fix bug that hid system name for fields on update of custom fields
* CSS tweak on option group update form 
= 2.2.2.1 =
* Eliminate use of prepend parameter in spl_autoload_register -- unnecessary and required PHP version 5.3.0; should now be OK with PHP 5.2.4
= 2.2.2 =
* Fix bug -- prevent repetitive downloads in certain key sequences
* Add search favoriting -- suggestion of Brendan Berger

= 2.2.1 =
* Add Uploads subsystem -- major new functionality for uploads of small or very large data sets with high control and transparency 
* Technical revisions to download process to support very large data sets
* Add multiple download formats
* User interface improvements throughout
* Full compatibility with Wordpress 4.2
= 0.83 =
Test for no search log condition -- new install or purged search log previously generated warnings.
= 0.80 = 
Completion of all plugin functions except bulk upload  


== Upgrade Notice ==
= 2.2.1 = 
Adds Upload functionality, improves download functionality and includes user interface improvements throughout. 


	  	

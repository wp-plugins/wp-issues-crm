=== WP Issues CRM ===
Contributors: Will Brownsberger
Donate link: 
Tags: contact, crm, constituent, customer, issues, list
Requires at least: 4.0
Tested up to: 4.2.1
Stable tag: 2.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CRM for offices that use Wordpress as their central communication tool, but have constituencies broader than their Wordpress user universe.

== Description ==

WP Issues CRM is a constituent/customer relationship management database designed for organizations that use Wordpress
to organize their content, but have constituencies broader than their Wordpress user universe.  It uses the post and category structure of 
Wordpress to define and classify issues, but uses custom tables for high performance access to a larger constituent database.  It offers
easy uploading for initial conversion and for automation of data input.  It also offers easy downloading for outgoing communications.

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
= Upload Features =
* Handles common file .csv and txt file formats
* Learns your the field mappings for your repetitive file uploads
* Validates data transparently so that you can fix problems as they emerge
* Allows you to easily control the matching/deduping strategy and to test alternative approaches before finalizing your upload
* Allows you to add default data for an upload -- so, for example, you can upload a list and identify all on the list as having 
attended signed a petition related to an issue
* Automatically breaks every task and the final upload process into chunks to minimize memory and packet sizes and avoid exceeding system limits
* Allows you to download files documenting the results of your upload to allow, for example, the manual completion of records that failed in the upload
* Allows you to automatically backout some types of uploads
== WP Issues CRM Manage Storage Facility ==
WP Issue CRM now includes a facility to show storage usage and to selectively purge interim files and dated external data.  So, for example, suppose you 
initially uploaded your database from a voter list.  Over time, you added information about contacts with voters.  You could then easily purge all voters with 
no contacts and add a fresh voter list, matching to the voters that you kept to avoid duplication.
== Design of WP Issues CRM ==
WP Issues CRM uses a fully modular object-oriented design.  It is built around a data dictionary so that it is fundamentally 
flexible.  It uses code recursively so that with a small code base it can offer broadly extensible functionality.  We use
this product ourselves on a daily basis and we are committed to continuous long-term improvement of it.


== Changelog ==
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


	  	

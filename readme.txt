=== WP Issues CRM ===
Contributors: Will Brownsberger
Donate link: 
Tags: contact, crm, constituent, customer, issues, list
Requires at least: 4.0
Tested up to: 4.1
Stable tag: 0.81
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CRM for offices that use Wordpress as their central communication tool, but have constituencies broader than their Wordpress user universe.

== Description ==

WP Issues CRM is a constituent/customer relationship management database designed for organizations that use Wordpress
to organize their content, but have constituencies broader than their Wordpress user universe.  It uses the post and category structure of 
Wordpress to define and classify issues, but uses custom tables for high performance access to a larger constituent database.  It offers
easy downloading for outgoing mail or email communications.

== Installation ==

1. Load WP Issues CRM through the Add New menu or install the zip file in the plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go through the Settings page under WP Issues CRM and make basic configuration decisions
4. Consider your office use of information and add any necessary constituent fields under Fields
5. If you are adding some "Select" fields -- some typology like political party -- define the options under Options.
6. If you have existing data that you are importing, you will need some help from someone who understands databases to run
	necessary upload queries.  Version 1.0 will include an automatic upload function. 
7. Exporting constituents is already a snap in Version 0.8 -- you can always download the results of any constituent search,
	including a blank search that retrieves the whole database.


== Frequently Asked Questions ==

Where I can I get support?

You may be able to get answers in the Wordpress forums.  If necessary contact the author at WillBrownsberger@gmail.com or by text at 617-771-8274.
We appreciate and seek your feedback and want to know how we can continue to improve this product.

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

== Design of WP Issues CRM ==

WP Issues CRM uses a fully modular object-oriented design.  It is built around a data dictionary so that it is fundamentally 
flexible.  It uses code recursively so that with a small code base it can offer broadly extensible functionality.  We use
this product ourselves on a daily basis and we are committed to continuous long-term improvement of it.

== Known Issues ==
On initial install, until user has saved one issue or done at least one issues search, 
if preferences are set to show last activity search, php warnings are generated.

== Changelog ==
0.80 completion of all plugin functions except bulk upload

== Upgrade Notice ==
0.80 is current stable version


	  	

# Sprout Invoices
Create estimates and invoices within your WordPress site. Accept invoice payments from multiple payment processors.

Our mission at [Sprout Apps](https://sproutapps.co/) is to build a suite of apps to help small businesses and freelancers work more efficiently by reducing those tedious tasks we have to perform to get paid.


## Sprout Invoice Features

Make sure to checkout the [Sprout Invoices](https://sproutapps.co/sprout-invoices/) features page for more detailed information. As well as the full featured [demo](https://sproutapps.co/demo/playground).

* An awesome [estimate & invoice workflow](https://sproutapps.co/news/what-sprout-invoices-solves-for-freelancers-and-wordpress-sites/). 
* The best [payment experience](https://sproutapps.co/news/sprout-invoices-payment-options-deposits-checks-authorizations/) for your clients with options for them to pay via Check, PO or [Paypal](https://sproutapps.co/marketplace/paypal-payments-express-checkout/) (additional gateways available).
* Unlimited Invoices, Estimates and Clients. No restrictions!
* Fully [customizable templates](https://sproutapps.co/support/knowledgebase/sprout-invoices/customizing-templates/) with your own theme.
* Localization support for your language!
* [Payment management](https://sproutapps.co/support/knowledgebase/sprout-invoices/payments/).
* [Advanced Reporting](https://sproutapps.co/support/knowledgebase/sprout-invoices/reports/) (limited w/ free version)
* [Client management](https://sproutapps.co/support/knowledgebase/sprout-invoices/clients/).
* [Freshbooks, Harvest and WP-Invoice Importing](https://sproutapps.co/news/feature-spotlight-import-freshbooks-harvest-wp-invoice/).
* Fully [customizable notifications](https://sproutapps.co/support/knowledgebase/sprout-invoices/notifications/). Notifications are sent from your server and allow for plain-text and HTML.
* [Deposit payments](https://sproutapps.co/news/feature-spotlight-invoice-deposits/) (premium upgrade)
* [Nested line items](https://sproutapps.co/news/feature-spotlight-nested-invoice-line-items/).
* [Advanced records](https://sproutapps.co/support/knowledgebase/sprout-invoices/tools/) with any extra tables!
* Includes a [customizable estimates/lead generation form](https://sproutapps.co/support/knowledgebase/sprout-invoices/advanced/customize-estimate-submission-form/).
* Integrates with [Gravity Forms, Ninja Forms and more](https://sproutapps.co/marketplace/advanced-form-integration-gravity-ninja-forms/) (premium upgrade).
* Accept [Stripe Payments](https://sproutapps.co/marketplace/stripe-payments/) (paid add-on or premium upgrade)
* Improved user experience with AJAX.
* Taxes
* Client records with multiple points of contact
* Pre-defined tasks/line-items
* No extra database tables!

The short list...

* Time tracking (coming soon)
* Recurring payments (coming soon)
* Payment terms (coming soon)

Please note that this feature list is incomplete, since it's long enough.


**[Download the most advanced Estimates and Invoicing plugin!](http://downloads.wordpress.org/plugin/sprout-invoices.zip)**

**A [fully featured upgrade](https://sproutapps.co/sprout-invoices/) and [add-on marketplace](https://sproutapps.co/marketplace/) are available.**


### Flexibility built in

While Sprout Invoices automates many of the tasks to improve workflow the power comes from customization.

*Custom Estimate and Invoice Templates*
Estimates can be fully customized via a new theme template. If you're familiar with customizing a WordPress theme templates than you can create a custom estimate.

*Notification Editing*
Notifications can be plain-text or HTML. Editing the entire content of a notification is simple with shortcodes that add dynamic content.

*Plenty of Payment Methods*
Accept credit cards via Paypal Pro or send them to Paypal for invoice payments. P.O. and checks options are also provided.

*Hundreds of Hooks*
Over two hundred filters and actions allow you to hook into Sprout Invoices and alter whatever you'd like.


### The Sprout Invoices Process

Sprout Invoices helps streamline the complex workflow of accepting estimates and getting invoices paid.

#### Requests

Receiving estimate requests on your site is simplified with Sprout Invoices. Use the customizable default form or integrate with an existing form built a favorite form builder plugin, e.g. Gravity Forms or Ninja Forms.

#### Estimating

Estimates are automatically created based on estimate request submissions from your site. Review, update, and send the estimate to your new client without having to depend on communicating via email first.


#### Invoicing

Invoices are automatically generated from accepted estimates speeding up the process of getting paid. Sprout Invoices understands deposit payments and doesn't have the same hoops other invoice services require.

### Installation

1. Upload plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

### Frequently Asked Questions

**Please visit [Sprout Apps](https://sproutapps.co/sprout-invoices/).**


### Changelog

**10.0.7**

* Change: Option to help improve sprout invoices.
* Fix: Fields error when no payment options are available
* Fix: Account credits doesn't have an admin bar option
* Fix: Slow loading servers need feedback on AJAX requests

**10.0.6**

* Fix: Select2 Compatibility issues

**10.0.5**

* Fix: PayPal transation error when parent line items are used.
* Fix: Line item totals not formatted
* Fix: Select2 should not be loaded everywhere
* Fix: PHP7 compatibility
* Fix: Client payment processor limits fix

**10.0**

* New: Account credits and payment credits
* New: Improved payment reminder (new reminder email)
* New: Estimate approval reminder (new reminder email)
* New: Client specific payment options
* New: Archive status, removes from front-end views
* New: Limit automtic recurring creation
* New: Payments dashboard widget
* Change: Automatically change status of scheduled docs
* Change: Automatically send invoice/estimate when published from a schedule
* Change: Send to multiple recipeints with comma seperated list
* Change: Add user of time keeper
* Change: Tax and discount are seperate line item totals
* Change: Premium reports updated with HTML5 export options
* Change: Remove visual editor from notification admin
* Fix: New line adjustments for address
* Fix: Dynamic text
* Fix: New line for plain text notifications
* Fix: Code cleanup with WP coding standards (formatting)
* Fix: Misc. minor bug fixes

**9.4**

* Fix: Reporting fixes
* Fix: Email address truncated on long top level domains.
* New: Notifications action.

Security updates:

* Possible for anyone to save new importer options, including uploading CSVs.
* Possible for anyone to create a payment
* Security issue with unfinished (unreleased) JSON API.

**9.3**

* UPDATE: Default Invoice/Estimate Subject to ID
* UPDATE: Localization update, including French translation

**9.2.2**

* UPDATE: Added more line item totals within the admin
* FIX: Cloning line items would result in descriptions that couldn't be saved.
* FIX: Extreme edge case calculation issues

**9.2.1**

* UPDATE: Theme compatibility improvements, e.g select2
* FIX: discount calculation improvements

**9.2.0.1**

* FIX: Estimates issue

**9.2**

* FIX: Parent line item totals
* OPT: Slight optimization for estimates and invoices

**9.1.1**

* FIX: PayPal cart total errors with invoices that utilize discounts, deposits, and taxes with fractional totals.

**9.1**

* NEW: Notes and Terms notification shortcodes
* FIX: Zapier routing issues
* FIX: Pass estimates notes to newly created invoice from estimate
* FIX: Time tracking load order fix

**9.0.3**

* FIX: AJAX callback errors, i.e. client creation.
* FIX: Localization changes causing errors on free version.

**9.0**

* NEW: Estimate and Invoice shortcodes
* NEW: Improved reporting and filtering
* NEW: Dashboard report caches are deleted on record updates
* NEW: All strings are wrapped by WP functions not wrapper class methods.
* FIX: Payments by month filtering error
* NEW: Load custom CSS based on invoice or estimate
* CHANGE: Line items have a unique index for future features
* FIX: Line item commenting allows for reordering of comments
* NEW: Improved dashboard time tracking widget
* FIX: Fractional discounts for PayPal
* NEW: Temp status redirects user to home page
* NEW: Associated client records are removed when a client is deleted.

**8.7.1**

* NEW: Filter for sending invoices to prevent filters. i.e. fix for PDF add-on. #165
* FIX: Estimate dashboard not showing current records. #167
* FIX: Fix for line item comments not showing highlighted icon when a comment is available. #166
* FIX: Default Terms/Notes transposed in some cases.

**8.7**

* NEW: Filter to suppress notifications on an individual basis. #163
* FIX: Default Terms/Notes for All Estimate/Invoices bug priority. #162
* UPDATE: Submission Hooks & Line Item Type priority. #161
* FIX: Report Filtering/Sorting. #159
* FIX: Estimate Submission Info Missing. #158

**8.6**

* NEW: Sprout Billings Support
* NEW: Recurring dashboard updates
* NEW: Form field wrapper classes
* Fix: PayPal "Adjustment" resolution
* OPT: Prevent looping of meta_box saves

**8.5**

* NEW: Payment options templating
* Fix: Caldera Forms compatibility
* Update: Improved Sprout Clients compatibility with Client Dashboards

**8.4**

* NEW: Reduce overall size.
* Fix: CSV Importing of already imported client users
* Fix: Invoice template showing "Pending Payment" when balance is zero
* Fix: Ultimate Member compatibility

**8.3.1**

* New: Save info meta action hook.
* New: New add-on compatibility hooks.
* New: New add-on hook to disable invoice creation.
* FIX: ACF compatibility fixes.
* FIX: Select2 compatibility issues with some plugins.

**8.2**

* New: Bundled add-on for admin filtering
* New: Pricing options is a hook for invoice templates
* New: Filter for attachments

**8.1.1**

* Fix: PHP Notice suppression on old line items.

**8.1**

* NEW: MercadoPago Support (payment button link callback)
* NEW: Line item total sorting
* Fix: Misc. Error fixes

**8.0.5**

* Fix: Escaped Addresses
* Fix: Redactor fix from 8.0.4
* Fix: WooCommerce compatibility with their outdated version of select2

**8.0.3**

* Fix: Estimates and pre-defined items
* Fix: Estimates not saved advanced columns correctly
* NEW: New filters for some bundled add-ons

**8.0.2**

* Fix: Javascript error when adding new users on clients page (select2 incompatibility)
* Fix: Javascript error on some admin pages

**8.0**

Read more all about the release at [Sprout Apps](https://sproutapps.co/news/rocket-8-0-brings-a-new-invoice-line-item-management-and-more/)

* New: Line Item Types and new management
* New: Pre-defined editing with new types
* New: Pre-defined item selection search
* Update: Time Tracking update to support item types
* New: Invoices and Estimates Admin filtering
* New: New bulk send of invoices or estimates

**7.6.1**

* FIX: Possible security fix with exposed estimates/invoices with site.com?post_type=*

**7.6**

* FIX: Deposit notification sent only if the payment is complete (not pending)
* FIX: Allow for deposit total to be set before saving
* FIX: Help section added to the new reporting dashboards
* FIX: WP-Invoice Issues with duplicate clients
* FIX: PayPal line item totaling issues preventing some payments

**7.5**

* NEW: Sprout Client Compatibility

**7.4**

* NEW: Deposit filter allows for new add-ons
* CHANGE: More Responsive Admin
* CHANGE: Improved no-index via http headers

**7.3**

* FIX: Edit post link fix for notification shortcodes
* FIX: Remove "pre=" header that some SEO plugins add
* CHANGE: [dashboard_link] available on User Creation notification
* CHANGE: Free Version messaging updates

**7.2.1**

* FIX: Updates for Pro Versions

**7.1**

* NEW: Sprout Invoice specific user roles
* FIX: Multiple Sprout Apps settings conflict fix

**7.0.3**

* FIX: Free version issues with redactor add-on
* FIX: Time tracker not accepting fractions
* FIX: Time Tracker on Dashboard issue
* FIX: Deposits issue for free version

**7.0**

https://sproutapps.co/news/sprout-invoices-7-0-banners-release-party-🎉/

* NEW: WooCommerce Integration is now bundled (for pro users).
* NEW: Completely revamped Stats Dashboard.
* NEW: Web accessible Time Tracking widget.
* NEW: History Management
* NEW: Subscriber specific Time Tracking dashboard widgets.
* UPDATE: Easily import unbilled time into an invoice with a single click.
* UPDATE: Add dashboard widgets to standard WP Dashboard.
* UPDATE: Improved admin search.
* NEW: Send invoice/estimate to a new email without creating a Client user with a simple input box.
* UPDATE: Modify the sender’s email for estimates and invoices on the invoice/estimate admin.
* UPDATE: Improved pay button on invoice template.
* UPDATE: Modify the "to" email for all admin notifications without a filter.
* UPDATE: Zapier integration updates, e.g. email data.
* UPDATE: Improved responsive design for meta boxes and multi-column edit screens.
* FIX: Prevent WP SEO from caring about Sprout Invoices.
* NEW: Invoice ID dynamic text
* FIX: Deposits issue for the free version (7.0.1)
* FIX: Start CSV import without using previous files
* FIX: Fix for old PHP versions without json_last_error
* FIX: Remove project types from submission page
* FIX: Language translations updated (7.2)

**6.2**

* FIX: Estimate creation via API fix
* FIX: Pointer Dismissals
* FIX: Dashboard caching issue
* FIX: ACF Pro Compatibility
* FIX: Minor importer updates for sanitization

**6.1.6**

* FIX: Importers failing under certain circumstances.

**6.1.5**

* FIX: Some escaping fixes from 6.1.1 for some sites using PayPal

**6.1.4**

* FIX: API callback fix for activation/deactivation and updates.

**6.1.3**

* UPDATE: 4.2 Compatibility
* FIX: Some escaping issues from 6.1.1

**6.1.2**

* FIX: Some escaping issues from 6.1.1

**6.1.1**

* SECURITY: Reviewed all uses of add_query_arg, regardless if $url is passed esc_url is used. 
* SECURITY: Reviewed and updated every case of echoing an un-escaped variable; with a very strict standard of making every variable escaped or casted as an int/float.

**6.1**

* NEW: Sprout Invoices Addons Page
* NEW: Manage bundled addons (for paid users)
* NEW: Filter the Admin Notification To: email address with `si_admin_notification_to_address`
* FIX: Block Spambots from Submitting the Payment Form
* FIX: Redirect to prevent refresh issues when a check/po is submitted #65
* FIX: PayPal Totals issue with Tax + Deposit #69

**6.0.5**

* FIX: Toggl incompatibility issue
* FIX: set_invoice_id error

**6.0.3**

* FIX: Estimate template error.
* FIX: Projects page error under come configurations

**6.0.1**

* FIX: Estimate approval failing under certain circumstances.

**6.0**

* NEW: Zapier Integration (pro version)
* NEW: CSV Importing
* NEW: Toggl Integration (pro version)
* NEW: Filter for payment reminder delay, si_get_overdue_payment_reminder_delay (pro version)
* NEW: Invoice that is voided will have a new stamp plus the user can't pay
* NEW: Allow for blank terms and notes with [si_blank] shortcode
* Improvement: API Updates for Future Release
* Improvement: CSV Importing of estimates and line items (with examples)
* Improvement: View logs adjustment to prevent duplication
* Improvement: Adjust Estimate/Invoice ID after clone
* Improvement: Confirmation page template updated
* Improvement: Handle payments better when invoice is deleted
* FIX: Redactor bug fixes when used within modal
* FIX: Client dashboard: multiple clients for a single user (pro version)
* FIX: Cloned Estimates/Invoices shouldn't retain the same status
* FIX: Send estimates/invoices when saved if recipient is selected
* FIX: Project Estimates and Invoices on Project admin adjusted
* FIX: Payment date should be post_date

**5.5**

* FIX: Invoices and Estimates were being returned in public search queries.

**5.4.1**

* FIX: Import admin

**5.4**

* FIX: Return all clients on Client Dashboard
* Improvement: Freshbooks import
* Improvement: Added nofollow for robots in header meta tag
* New: Create a payment when an invoice is marked as paid.

**5.3**

* NEW: Improve WP-Invoice Importer

**5.2**

* NEW: 'si_default_due_in_days' filter added
* FIX: Dynamic text within notification shortcodes
* Misc. Fixes 

**5.1**

* New: Compatibility class to resolve other plugins problems, e.g. Gravity Forms erring out js on cusotm post type pages
* FIX: More error reports for missing notifications.
* FIX: Customizer filter should only be for the front-end
* FIX: Client Dashboard was blank when a non-client was logged in

**5.0.2**

* FIX: Client Dashboard notification error; fixed with better abstraction
* FIX: Shortcode fix
* FIX: Free version fix for PayPal
* FIX: Add Customizer to the SI menu for clarity

**5.0.1**

* FIX: Comment issue with multiple line items open
* FIX: PayPal total issue when invoice has a deposit and previous payments
* FIX: Estimate line item button styling issue
* FIX: Comment shortcode issue when client has multiple users
* FIX: Compatibility fix with other plugins/themes using .tooltip

**5.0**

* NEW: Line item commenting (pro version)
* NEW: Pre-defined line items (pro version)
* NEW: Dynamic text (pro version)
* UPDATED: Admin UI tweaks

**4.5**

* IMPROVEMENT: UI update so other plugins wont conflict.
* Fix: [invoice_total_due] should respect deposit amounts
* Update: Future status

**4.4**

* IMPROVEMENT: Estimates and Invoices can have strings for IDs
* IMPROVEMENT: Force private URL under circumstances when auto-draft is tried to use
* IMPROVEMENT: Optimize logic for screen checking
* IMPROVEMENT: Added client default currency formatting option
* IMPROVEMENT: Cross compatibility with other plugins, including Visual Composer
* FIX: Email on client dashboard error
* UPDATE: Datatables library update

**4.3.3**

* FIX: Estimate Accept/Decline not working on some setups.
* IMPROVEMENT: Improved security on estimates.
* IMPROVEMENT: Re-worked currency formatting again.
* FIX: Strict Standard notice fixes.
* UPDATE: PO Updates.

**4.3.2**

* IMPROVEMENT: Auto updates
* UPDATE: PO updates
* FIX: Better handling of client dashboard page id caching

**4.3.1**

* FIX: Expiration dates not displaying
* NEW: Currency formating options
* FIX: Client dashboard updates
* FIX: Custom currency for clients and notifications
* NEW: Tax shortcodes
* NEW: Added si_client_dashboard_page_id filter
* FIX: Newly created invoices from a recurring schedule will have it's due date and issue date set

**4.2.1**

* FIX: JS issues on client management page.

**4.2**

* FIX: Fix for nested items
* IMPROVEMENT: Nesting items UX/UI
* CHANGE: localeconv() defaults so money is always formated
* IMPROVEMENT: Compatibility with Visual Composer and other plugins
* CHANGE: Remove Client currency code option, never used.
* CHANGE: Taxes are floats not integers

**4.1.2**

* FIX: Compatibility fix with some plugins, namely Visual Composer
* FIX: Fixing bad build with white screen of death. Sorry! Seriously! Sorry!

**4.1**

* NEW: Allow to clean up notifications, which is a fix from a bug in an older version.
* CHANGE: Updates to the free version.

**4.0.3**

* CHANGE: Again...improving automatic money formatting for non-US currencies
* FIX: Translation fix
* FIXES: Misc. minor code updates

**4.0.2**

* CHANGE: Improving automatic money formatting for non-US currencies

**4.0**

* NEW: Client Dashboards
* NEW: HTML notification add-on support
* NEW: Time importing from Freshbooks
* Misc. bug fixes and updates


**3.2**


* New: Import time from Freshbooks
* New: Add PO Number and separate Tax line items to templates
* New: pt_BR and nl Translations
* Fix: Review untranslated strings
* Fix: Send paid notification after payment is complete, not created
* Fix: Some minor php warnings and notices

**3.1**

* Dashboard updates including method to flush cached values
* Notification shortcode filters

**3.0.1**

* Reporting fixes

**3.0**

* New: Recurring Payments (aka subscriptions)
* New: Recurring Invoices
* Fix: Less than a bunch more than a couple

**2.0**

* _NEW:_
	* Projects
	* Time Tracking (premium version)
	* WYSIWYG for line items (premium version)

* _Changes:_
	* Improved Client management
	* Easier user assignment and creation for Clients
	* Streamlined Invoice and Estimate edit UI
	* Freshened editing and management all around
	* New possibilities for add-ons

**1.1.6**

* _Changes:_
	* Customizer compatibility for logo uploading.

**1.1.5**

* _Changes:_
	* More hooks and filters
	* fix for unit tests
* _Bug Fixes:_
	* ID shortcode fix
	* js error with custom templates

**1.1.4**

* _Changes:_
	* No page breaks when printing large invoices/estimates
	* Better error handling for invoices without clients assigned
	* Helper functions for future payment processors
	* More hooks and filters

* _Bug Fixes:_
	* Critical Paypal EC update to capture payment

**1.1.3**

* _Changes:_
	* New hooks for estimates and invoice templates
	* Starting to create some unit tests
	* Doc changes

* _Bug Fixes:_
	* Fix qtip
	* Total calculation issue with template tag

**1.1.2**

* _Changes:_
	* No index on estimates and invoices!
	* Paypal line items will not longer use qty since PP prevents fractions (now?)
	* Some themes don't register their scripts and styles correctly, so unregistering them comes later on wp_print_scripts

* _Bug Fixes:_
	* Deposit function adjustments
	* Paypal balance calculation fix
	* Paypal qty fraction fix.
	* Misc. errors and notices
	* Estimates/Invoices auto-draft bug when doc isn't saved first
	* Other minor bug fixes


**1.1.1**

* _Bug Fixes:_
	* Estimates slug not created
	* Clone warning (strict notices)
	* Other minor bug fixes
	* Better support for sites without permalinks setup


**1.1.0.1**

* _Bug Fixes:_
	* Saving error
	* Line item width after payment

**1.1**

* _Features:_
	* Improved Invoice and Estimate templates
	* Client specific invoice templates
	* Client specific estimate templates
	* Customizable money formats
	* Improved multi-currency support
	* Client specific money format
	* Client specific currency code for payment processing
	* minor UI improvements

* _Bug Fixes:_
	* Few error prevention updates

**1.0.10.1**

* _Bug Fixes_

** Freshbooks payment import fix. FBAPI uses an unconventional amount format (i.e. 353634.980)

**1.0.10**

_Bug Fixes_
** Client could have non-user_ids associated
** Handle text input with bad formatting better

**1.0.9.1**

_Bug Fixes_

* Deposits bug for free versions
* Tasks clarification for free version
* Allow for deletion with new drop-down UI

_Features_

* Custom template messaging improvements

**1.0.8**

_Bug Fixes_
* Client creation via AJAX/Modal
* Quick send bug fixes for Estimates and Invoices
* client_name shortcode
* Minor fixes for importing from freshbooks, harvest and WP-Invoices

_Features_
* Major overhaul of importing from freshbooks, harvest and WP-Invoices.

**1.0.7**

* Much improved importing with AJAX

**1.0.6**

* Auto upgrades fix for pro users

**1.0.5**

* New templating class (select the invoice/estimate template)
* New status UI
* Notification updates
* Better rewrite handling
* Minor bug fixes

**1.0**

Welcome! This is a big update and a big step for us.

* There's a free version and most like you're using it.
* Import from WP-Invoice, Harvest or Freshbooks.
* Helpers, coming before this is truly 1.0. (shush! this is really 0.9.*)
* So many bug fixes that make this a legit 1.0 release.
* Admin bar links.
* Better dashboard let you know what's up.
* Remove some unnecessary cruft.
* Multiple taxes


**0.9.9.5 - 1.0 GM**

* Subject line fix.

**0.9.10.0.5 - 1.0 GM**

* WP-Invoice Importer Bug fix: Import any type
* WP-Invoice Importer Bug fix: Fix devision by zero error
* WP-Invoice Importer Bug fix: Add si_default_country_code filter to set default country code.

**0.9.10.0.5 - 1.0 GM**

* Strict standards fixins

**0.9.9.2 - 1.0 GM**

* Some versions of PHP will bomb when checking if method_exists on a non existant class. Silly...


**0.9.9.1**

* Minor bug fixes

**0.9.9**

* Plugin updates fix
* Add-ons not loading, e.g. Stripe.

**0.9.8.6**

* Dashboard fix

**0.9.8.6**

* Import progress fix.

**0.9.8.6**

* Fix deposit and cleanup files

**0.9.2**

* Admin bar links
* Additional hooks and filters required by submission integration add-on
* Minor updates

**0.9.1**

* Line Item UI changes.
* Invoice Style fixes
* Plugin updater conflict.
* Better documentation and linking to site.


**0.9**

* Initial Release

**0.9.1.1**

* Remove debugging logs from release branch.

**Support**

More info coming soon.

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

**0.9.9.4 - 1.0 GM**

* WP-Invoice Importer Bug fix: Import any type
* WP-Invoice Importer Bug fix: Fix devision by zero error
* WP-Invoice Importer Bug fix: Add si_default_country_code filter to set default country code.

**0.9.9.3 - 1.0 GM**

* Strict standards fixins

**0.9.9.2 - 1.0 GM**

* Some versions of PHP will bomb when checking if method_exists on a non existant class. Silly...


**0.9.9.1**

* Minor bug fixes

**0.9.9**

* Plugin updates fix
* Add-ons not loading, e.g. Stripe.

**0.9.8.4**

* Dashboard fix

**0.9.8.3**

* Import progress fix.

**0.9.8.1**

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

**0.9.0.1**

* Remove debugging logs from release branch.

**Support**

More info coming soon.

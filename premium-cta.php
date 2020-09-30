<?php
/***************
 * Premium CTA *
 ***************/

// CTA Markup function, contains logic to disable CTA when Sprout Invoices Pro is active and allows for dynamic URLs for the UTM codes
function si_premium_cta( $url ) {
    if ( ! is_plugin_active( 'sprout-invoices-pro/sprout-invoices-pro.php' ) ) {
        echo( '<div class="si_premium_cta">' );
            printf(
                __( 'You&#39;re using the free version of Sprout Invoices. <a href="%1$s" target="_blank"><b>Upgrade today</b></a> to unlock more features.' , 'sprout-invoices' ),
                esc_url( $url )
            );
        echo( '</div>' );
    }
}

// Fetches the request URI for current page without Site or Home URLs
function current_page() {
    global $pagenow;  
    $current_slug = $pagenow . '?' . $_SERVER['QUERY_STRING'];
    return $current_slug;
}

// Stores request URI information for use in switch statement to supply different UTM codes depending on the URL
$admin_url = current_page();

// Switch statement to add individual UTM codes to each admin page
switch ($admin_url) {

    // Invoices
    case 'edit.php?post_type=sa_invoice':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/invoices?utm_source=Invoices&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices' , 'si_utm' );
        break;

    // Add Invoice
    case 'post-new.php?post_type=sa_invoice':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/invoices?utm_source=Add%20Invoice&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices' , 'si_utm' );
        break;

    // Payments
    case 'edit.php?post_type=sa_invoice&page=sprout-apps%2Finvoice_payments':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/invoices?utm_source=Payments&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices' , 'si_utm' );
        break;

    // Clients
    case 'edit.php?post_type=sa_client':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/invoices?utm_source=Clients&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices' , 'si_utm' );
        break;

    //Projects
    case 'edit.php?post_type=sa_project':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/invoices?utm_source=Projects&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices' , 'si_utm' );
        break;

    // Estimates
    case 'edit.php?post_type=sa_estimate':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/estimates?utm_source=All%20Estimates&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices' , 'si_utm' );
        break;

    // Add Estimate
    case 'post-new.php?post_type=sa_estimate':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/estimates?utm_source=Estimates&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices' , 'si_utm' );
        break;

    // Getting Started
    case 'admin.php?page=sprout-invoices':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/sprout?utm_source=Getting%20Started&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices', 'si_utm' );
        break;

    // General Settings
    case 'admin.php?page=sprout-invoices-settings':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/sprout?utm_source=General%20Settings&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices', 'si_utm' );
        break;

    // Payment Settings
    case 'admin.php?page=sprout-invoices-payments':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/sprout?utm_source=Payment%20Settings&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices', 'si_utm' );
        break;

    // Notifications
    case 'admin.php?page=sprout-invoices-notifications':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/sprout?utm_source=Notifications&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices', 'si_utm' );
        break;

    // Add-ons
    case 'admin.php?page=sprout-invoices-addons':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/sprout?utm_source=Add%20Ons&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices', 'si_utm' );
        break;

    // Reports
    case 'admin.php?page=sprout-invoices-reports':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/sprout?utm_source=Reports&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices', 'si_utm' );
        break;

    // Import
    case 'admin.php?page=sprout-invoices-import':
        function si_utm() {
            si_premium_cta( 'https://sproutinvoices.com/inplugin-upgrade/sprout?utm_source=Import&utm_medium=Top%20Banner&utm_campaign=In%20Plugin%20Upgrade' );
        }
        add_action( 'all_admin_notices', 'si_utm' );
    break;

    default:
        return;
}
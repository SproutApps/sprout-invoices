<?php 

if ( class_exists( 'SA_Addons' ) ) {
	// Possibly another Sprout Apps plugin is installed and the addons class is loaded.
	return;
}
/**
* Addons: Admin purchasing, check for updates, etc.
* 
*/
class SA_Addons extends SI_Controller {
	
	public static function init() {
		# code...
	}
	
}
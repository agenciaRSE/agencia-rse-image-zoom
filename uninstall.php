<?php
/**
 * Removes the plugin settings when the plugin is deleted from the Plugins screen.
 *
 * @package AGRSE_Image_Zoom
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'agrse_zoom_settings' );

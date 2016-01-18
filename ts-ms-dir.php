<?php
/*
*	Plugin Name: Multisite Directory Page
*	Plugin URI:
*	Description:
*	Version: .20
*	Author: Trisha Salas
*	Author URI: http://www.trishasalas.com
*	License: GPLv2
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

register_activation_hook( __FILE__, 'ts_dir_plugin_activated' );
register_deactivation_hook( __FILE__, 'ts_dir_plugin_deactivated' );

/**
 * Define TS_Multisite_Directory_Public
 */

class TS_Multisite_Directory {
	public
		$sites_info,
		$site_info,
		$site_id,
		$sites,
		$site,
		$url,
		$output;


	public function __construct() {
		add_action( 'init', array( $this, 'ts_multisite_info' ) );
		add_action( 'init', array( $this, 'ts_multisite_directory' ) );
		add_action( 'init', array( $this, 'load_styles' ) );
		add_action( 'init', array( $this, 'ts_multisite_directory_i18n' ) );
		add_action( 'insert_blog', 'delete_sites_info_transient' );
		add_shortcode('multisite_directory', array( $this, 'ts_multisite_directory' ) );

	}

	function ts_multisite_directory_i18n() {
		load_plugin_textdomain( 'ts-multisite-directory', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/'  );
	}
	function load_styles() {
		wp_enqueue_style('ts-multisite-directory-styles', plugins_url( 'assets/style.css', __FILE__ ), null, null, 'screen');
	}

	function ts_multisite_info() {
		global $wpdb;
		$this->sites_info = get_transient( 'ts_multisite_info' );

		$this->sites_info = array();
		$this->sites      = wp_get_sites( array( 'network_id' => $wpdb->siteid ) );

		foreach ( $this->sites as $this->site ) {
			switch_to_blog( $this->site['blog_id'] );

			$this->url = isset( $this->site['domain'], $this->site['path'] )
				? $this->site['domain'] . $this->site['path']
				: site_url();

			$this->sites_info[ $this->site['blog_id'] ] = array(
				'url'          => $this->url,
				'name'         => get_bloginfo( 'name' ),
				'desc'         => get_bloginfo( 'description' ),
				'rss'          => get_bloginfo( 'rss2_url' ),
				'comments_rss' => get_bloginfo( 'comments_rss2_url' ),
			);
			set_transient( 'ts_multisite_info', $this->sites_info, WEEK_IN_SECONDS );

			// Restore current blog each time
			restore_current_blog();
		}
		return $this->sites_info;
	}

	public function ts_multisite_directory() {
		$this->output = '';
		$this->output .= '<div id="multisite-directory">';
		foreach ( $this->sites_info as $this->site_info ) {
			$this->output .= '<h3><a href="' . esc_url( $this->site_info['url'] ) . '">' . esc_html( $this->site_info['name'] ) . '</a></h3>';
			$this->output .= '<div class="posts-rss"><a href="' . esc_url( $this->site_info['rss'] ) . '">' . __( 'RSS for Posts', 'multisite-directory' ) . '</a></div>';
			$this->output .= '<div class="comments-rss"><a href="' . esc_url( $this->site_info['comments_rss'] ) . '">' . __( 'RSS for Comments', 'multisite-directory' ) . '</a></div></ul>';
		}
		$this->output .= '</div>';
		return $this->output;
	}

	function delete_sites_info_transient() {
		delete_transient( 'ts_multisite_info' );
	}

}

if( class_exists( 'TS_Multisite_Directory' ) ) {
	new TS_Multisite_Directory;
}
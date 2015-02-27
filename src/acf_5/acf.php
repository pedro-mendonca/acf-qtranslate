<?php

namespace acf_qtranslate\acf_5;

use acf_qtranslate\acf_5\fields\image;
use acf_qtranslate\acf_5\fields\text;
use acf_qtranslate\acf_5\fields\textarea;
use acf_qtranslate\acf_5\fields\wysiwyg;
use acf_qtranslate\acf_interface;
use acf_qtranslate\plugin;

class acf implements acf_interface {

	/*
	 * Create an instance.
	 * @return void
	 */
	public function __construct() {
		add_filter('acf/format_value',                array($this, 'format_value'));
		add_action('acf/include_fields',              array($this, 'include_fields'));
		add_action('acf/input/admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
	}

	/**
	 * Load javascript and stylesheets on admin pages.
	 */
	public function include_fields() {
		new image;
		new text;
		new textarea;
		new wysiwyg;
	}

	/**
	 * Load javascript and stylesheets on admin pages.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style('acf_qtranslate_input',  plugins_url('/assets/input.css', ACF_QTRANSLATE_PLUGIN));
		wp_enqueue_script('acf_qtranslate_input', plugins_url('/assets/input.js',  ACF_QTRANSLATE_PLUGIN));
	}

	/**
	 * This filter is applied to the $value after it is loaded from the db and
	 * before it is returned to the template via functions such as get_field().
	 */
	public function format_value($value) {
		if (is_string($value)) {
			$value = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($value);
		}
		return $value;
	}

	/**
	 * Get the visible ACF fields.
	 * @return array
	 */
	public function get_visible_acf_fields() {
		global $post, $pagenow, $typenow;

		$filter = array();
		if ($pagenow === 'post.php' || $pagenow === 'post-new.php') {
			if ($typenow !== 'acf') {
				$filter['post_id'] = $post->ID;
				$filter['post_type'] = $typenow;
			}
		}
		elseif ($pagenow === 'admin.php' && isset($_GET['page'])) {
			$filter['post_id'] = $post->ID;
			$filter['post_type'] = $typenow;
		}
		elseif ($pagenow === 'edit-tags.php' && isset($_GET['taxonomy'])) {
			$filter['taxonomy'] = filter_var($_GET['taxonomy'], FILTER_SANITIZE_STRING);
		}
		elseif ($pagenow === 'profile.php') {
			$filter['user_id'] = get_current_user_id();
			$filter['user_form'] = 'edit';
		}
		elseif ($pagenow === 'user-edit.php' && isset($_GET['user_id'])) {
			$filter['user_id'] = filter_var($_GET['user_id'], FILTER_SANITIZE_NUMBER_INT);
			$filter['user_form'] = 'edit';
		}
		elseif ($pagenow === 'user-new.php') {
			$filter['user_id'] = 'new';
			$filter['user_form'] = 'edit';
		}
		elseif ($pagenow === 'media.php' || $pagenow === 'upload.php') {
			$filter['attachment'] = 'All';
		}

		if (count($filter) === 0) {
			return array();
		}

		$supported_field_types = array(
			'email',
			'text',
			'textarea',
		);

		$visible_fields = array();
		foreach (acf_get_field_groups($args) as $field_group) {
			$fields = acf_get_fields($field_group);
			foreach ($fields as $field) {
				if (in_array($field['type'], $supported_field_types)) {
					$visible_fields[] = array('id' => 'acf-' . $field['key']);
				}
			}
		}

		return $visible_fields;
	}

}
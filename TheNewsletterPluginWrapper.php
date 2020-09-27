<?php

/*
 * Copyright © 2019  Benedict Adams
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

if(!defined('ABSPATH')) exit;


class BTNP_The_Newsletter_Plugin_Bloom extends ET_Core_API_Email_Provider {

	public $custom_fields_scope = 'account';
	
	public $name = 'TheNewsletterPlugin';
	
	public $slug = 'thenewsletterplugin';
	
	public $uses_oauth = false;

	public function __construct( $owner, $account_name) {
		parent::__construct( $owner, $account_name);
	}
	
	public function get_account_fields() {
		return array();
	}
	
	public function get_data_keymap( $keymap = array() ) {
		$keymap = array(
			'list'       => array(
				'list_id'           => 'id',
				'name'              => 'name',
			),
			'subscriber' => array(
				'email'         => 'email',
				'last_name'     => 'surname',
				'name'          => 'name',
				'custom_fields' => 'custom_fields',
			),
		);

		return parent::get_data_keymap( $keymap );
	}

	protected function _fetch_custom_fields( $list_id = '', $list = array() ) {
        // get all custom fields.
		$options = get_option('newsletter_profile', array());
		$fields = array();

		// Assign all possible fields to $fields
		foreach( $options as $key => $val ) {
			// Check if is a possible field.
			if(preg_match('/_[a-zA-Z]+$/', $key) || $key === 'subscribe' || $key === 'email' || $key === 'name' || $key === 'surname'){
				continue;
			}

			if(!empty($val)){
				$fields[$key] = array(
					'field_id' => $key,
					"name" => $val,
				);
				if(!empty($options["{$key}_options"])) $fields[$key]["options"] = explode(',', $options["{$key}_options"]);
				// Gets the type from the placeholder field because the type option has to few options.
				$fields[$key]["type"] = !empty($options["{$key}_placeholder"]) && in_array( strtolower($options["{$key}_placeholder"]), array(
					'input',
					'textarea',
					'checkbox',
					'select',
				) ) ? strtolower($options["{$key}_placeholder"]) : 'any';
			}
		}

		return $fields;
	}

	public function fetch_subscriber_lists() {
		global $wpdb;
		if ( !class_exists( 'Newsletter' ) ) {
			return 'The Newsletter Plugin in not installed or activated!';
		}

		// Gets all possible lists.
		$lists_arr = json_decode(json_encode(Newsletter::instance()->get_lists()), true);
		$lists = array();
		$error_message = 'No lists were found. Please create a Newsletter list!';

		foreach($lists_arr as $key => $val){

			$lists[ $val['id'] ]['name'] = sanitize_text_field( $val['name'] );
			$lists[ $val['id'] ]['id'] = sanitize_text_field( $val['id'] );

			// Gets Subscribers count.
			$lists[ $val['id'] ]['subscribers_count'] = sanitize_text_field( $wpdb->get_var("SELECT COUNT(*) FROM " . NEWSLETTER_USERS_TABLE . " WHERE list_{$val['id']}=1 AND status='C'") );
		}

		if ( ! empty($lists) ) {
			$error_message               = 'success';
			$this->data['lists']         = $lists;
			$this->data['is_authorized'] = true;
			$this->data['custom_fields'] = $this->_fetch_custom_fields( );
			$this->save_data();
		}

		return $error_message;
	}
	
	public function subscribe( $args, $url = '' ) {
		$error = esc_html__( 'An error occurred. Please try again later.', 'et_core' );

		if ( ! class_exists( 'TNP' ) ) {
			return $error;
		}

		$newsletter = Newsletter::instance();
		$subscription = NewsletterSubscription::instance();

		$custom = is_array($args['custom_fields']) ? $args['custom_fields'] : array();
		unset($args['custom_fields']);

		$params       = $this->transform_data_to_provider_format( $args, 'subscriber');
		$params['lists'] = array($args['list_id']);

		$user = TNP::subscribe($params);
		$use = $newsletter->get_user($args['email']);

		if(is_wp_error($user)){
			if($user->get_error_message() != 'Email address already exists'){
				return $user->get_error_message();
			}

			// Saves data if the email already exsits.
			$use->{"list_{$args['list_id']}"} = "1";
			if($subscription->is_double_optin()) $use->status = 'S';

			$subscription->send_activation_email($use);
		}

		// Saves all custom fields.
		foreach($custom as $key => $val){
			$use->{$key} = is_array($val) ? implode(',', $val) : $val;
		}
		
		$use->referrer = 'Bloom';
		$newsletter->save_user($use);

		return 'success';
	}
}
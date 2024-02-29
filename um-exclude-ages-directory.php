<?php
/**
 * Plugin Name:     Ultimate Member - Exclude ages from Members Directory
 * Description:     Extension to Ultimate Member for setting an age limit for display of users in the Members Directory.
 * Version:         1.1.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.8.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
if ( ! class_exists( 'UM' ) ) return;

Class UM_Exclude_Ages_Directory {

    public $birth_date = 'birth_date';

    function __construct() {

        add_filter( 'um_settings_structure',      array( $this, 'um_settings_structure_exclude_ages' ), 10, 1 );
        add_filter( 'um_prepare_user_query_args', array( $this, 'um_prepare_user_query_args_exclude_ages' ), 999999, 2 );
    }

    public function um_prepare_user_query_args_exclude_ages( $query_args, $directory_data ) {

        global $wpdb;

        $forms = UM()->options()->get( 'um_exclude_ages_forms' );

        if ( ! empty( $forms )) {
            $forms = array_map( 'sanitize_text_field', $forms );

            if ( isset( $directory_data['form_id'] ) && in_array( $directory_data['form_id'], $forms )) {

                $age = UM()->options()->get( 'um_exclude_ages_limit' );
                if ( ! empty( $age ) && is_numeric( $age )) {

                    $age_limit = current_time( 'timestamp' ) - (int)$age * YEAR_IN_SECONDS;
                    $age_limit_date = date_i18n( 'Y/m/d', mktime( 0,0,0,
                                                                date_i18n( 'm', current_time( 'timestamp' ) ),
                                                                date_i18n( 'd', current_time( 'timestamp' ) ),
                                                                date_i18n( 'Y', $age_limit )) );

                    $sql = "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '{$this->birth_date}' AND ( meta_value <= '%s' AND meta_value != '' AND meta_value IS NOT NULL )";
                    $users = $wpdb->get_col( $wpdb->prepare( ( $sql ), $age_limit_date ));

                    if ( ! empty( $users )) {
                        $query_args['include'] = $users;

                    } else {

                        $users = get_users( array( 'fields' => array( 'ID' ) ) );

                        foreach( $users as $user ) {
                            $query_args['exclude'][] = $user->ID;
                        }
                    }
                }
            }
        }

        return $query_args;
    }

    public function um_settings_structure_exclude_ages( $settings_structure ) {

        $um_directory_forms = get_posts( array( 'meta_key'    => '_um_mode',
                                                'numberposts' => -1,
                                                'post_type'   => 'um_directory',
                                                'post_status' => 'publish'
                                        ));

        $um_forms = array();
        foreach( $um_directory_forms as $um_form ) {
            $um_forms[$um_form->ID] = $um_form->post_title;
        }

        $settings_structure['']['sections']['users']['form_sections']['exclude_ages']['title']       = __( 'Exclude Ages', 'ultimate-member' );
        $settings_structure['']['sections']['users']['form_sections']['exclude_ages']['description'] = __( 'Plugin version 1.1.0 - tested with UM 2.8.3', 'ultimate-member' );

        $settings_structure['']['sections']['users']['form_sections']['exclude_ages']['fields'][] = array(
            'id'            => 'um_exclude_ages_forms',
            'type'          => 'select',
            'multi'         => true,
            'size'          => 'medium',
            'options'       => $um_forms,
            'label'         => __( 'Members Directory Forms', 'ultimate-member' ),
            'description'   => __( 'Select single or multiple Members Directory Forms for the age limit.', 'ultimate-member' ),
            );

        $settings_structure['']['sections']['users']['form_sections']['exclude_ages']['fields'][] = array(
            'id'            => 'um_exclude_ages_limit',
            'type'          => 'text',
            'size'          => 'small',
            'label'         => __( 'Age Limit', 'ultimate-member' ),
            'description'   => __( 'Enter the age limit number.', 'ultimate-member' ),
            );

        return $settings_structure;
    }

}

new UM_Exclude_Ages_Directory();

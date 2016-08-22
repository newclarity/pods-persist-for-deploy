<?php
/**
 * Plugin Name: Pods Persist for Deploy
 * Description: Allows locking down Pods on deployment allowing version control of Pods post types, taxonomies and fields.
 */
//

//add_action( 'plugins_loaded', array( 'Pods_Deploy', 'on_load' ), 5 );

/*
 * Wishing Pods had an autoloader...
 */
require_once( PODS_DIR . 'classes/PodsField.php' );
require_once( PODS_DIR . 'classes/fields/pick.php' );

//define( 'PODS_TABLELESS', true )
//define( 'PODS_DISABLE_ADMIN_MENU', true )
//define( 'PODS_DISABLE_CONTENT_MENU', true )

class Pods_Persist_For_Deploy {

    private static $_meta;

    private static $_meta_file;
  
    static function on_load() {

        self::$_meta = (object) array();

        self::$_meta_file = WP_CONTENT_DIR . '/pods-meta.php';

        add_filter( 'pods_view_cache_alt_get', array( __CLASS__, '_pods_view_cache_alt_get' ), 10, 4 );
        add_filter( 'pods_view_cache_alt_get_value', array( __CLASS__, '_pods_view_cache_alt_get_value' ), 10, 4 );
        add_filter( 'shutdown', array( __CLASS__, '_shutdown' ) );

        if ( self::deployed() ) {
	        define( 'PODS_DISABLE_ADMIN_MENU', true );
	        if ( ! is_file( self::$_meta_file ) ) {
		        $err_msg = __( 'ERROR for Pods Persist and Deploy. ' .
		                       'PODS_DEVELOP not defined and the file %s not found. ' .
		                       'Add the following line to wp-config.php to correct this error: ' .
		                       'define(\'PODS_DEVELOP\', true );', 'pods' );
		        trigger_error( sprintf( $err_msg, self::$_meta_file ) );
		        exit;
	        }
            self::$_meta = require( self::$_meta_file );
        }

    }

    /**
     *
     */
    static function _shutdown() {

        if ( ! self::deployed() ) {

            $prior_values = is_file( self::$_meta_file )
                ? serialize( require( self::$_meta_file ) )
                : null;

            if ( self::$_meta && $prior_values !== serialize( self::$_meta ) ) {

                $var_export = var_export( self::$_meta, true );
                $var_export = preg_replace( '#^(stdClass::__set_state)#', '(object)', $var_export );
                file_put_contents( self::$_meta_file, "<?php return {$var_export};" );

            }

        }

    }

    /**
     * @param mixed $value
     * @param string $original_key
     * @return mixed
     */
    static function _pods_view_get_transient( $value, $original_key ) {

        self::$_meta->{$original_key} = $value;

        remove_filter( trim( __FUNCTION__, '_' ), array( __CLASS__, __FUNCTION__ ), 10 );

        return $value;

    }

    /**
     * @param mixed $value
     * @param string $cache_mode
     * @param string $key
     * @param string $original_key
     * @return mixed
     */
    static function _pods_view_cache_alt_get_value( $value, $cache_mode, $key, $original_key ) {

        return self::$_meta->{$original_key};

    }

    /**
     * @param bool $do_altget
     * @param string $cache_mode
     * @param string $key
     * @param string $original_key
     * @return bool
     */
    static function _pods_view_cache_alt_get( $do_altget, $cache_mode, $key, $original_key ) {

        if ( self::deployed() ) {

            $do_altget = isset( self::$_meta->{$original_key} );

        } else {

            add_filter( 'pods_view_get_transient', array( __CLASS__, '_pods_view_get_transient' ), 10, 2 );

        }

        return $do_altget;

    }

    /**
     *
     */
    static function deployed() {
        return ! defined( 'PODS_DEVELOP' ) || false === PODS_DEVELOP;
    }

}
Pods_Persist_For_Deploy::on_load();

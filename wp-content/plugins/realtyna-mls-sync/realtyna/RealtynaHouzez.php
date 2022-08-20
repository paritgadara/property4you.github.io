<?php
// Block direct access to the main plugin file.
defined( 'ABSPATH' ) || die( 'Access Denied!' );

/**
 * Houzez Theme Prerequirement Checker
 * 
 * @author Chris A <chris.a@realtyna.net>
 * 
 * @version 1.0
 */
class RealtynaHouzez {

    /** @var array houzez requirments list as array  */
    public static $houzezRequirements = [
        "wordpress" => "4.6" ,
        "php" => "7.1" ,
        "mysql" => "5.6" ,
        "max_execution_time" => "600" ,
        "memory_limit" => "128M" ,
        "post_max_size" => "48M" ,
        "upload_max_filesize" => "48M"
    ];

    /**
     * Check Main Requirments
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function haveHouzezRequirements(){

        return (
            self::getWpVersion() >= self::$houzezRequirements['wordpress'] &&
            self::getPhpVersion() >= self::$houzezRequirements['php'] &&
            self::getMysqlVersion() >= self::$houzezRequirements['mysql'] &&
            ( self::getMaxExecutionTime() <= 0 || self::getMaxExecutionTime() >= self::$houzezRequirements['max_execution_time'] ) &&
            ( self::getMemoryLimit() <= 0 || self::getMemoryLimit() >= self::$houzezRequirements['memory_limit'] ) &&
            ( self::getPostMaxSize() <= 0 || self::getPostMaxSize() >= self::$houzezRequirements['post_max_size'] ) &&
            ( self::getUploadMaxFilesize() <= 0 || self::getUploadMaxFilesize() >= self::$houzezRequirements['upload_max_filesize'] )
        );
        
    }

    /**
     * Get details of Requirments
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return array
     */
    public static function getHouzezRequirementsStatus(){

        $requirementsStatus = array();

        foreach ( self::$houzezRequirements as $key => $value ) {
            $requiementTitle = strtoupper( $key );
            $currentValue = $label = $operator = '';
            $passed = false;

            switch ( $key ) {

                case 'wordpress':
                    $currentValue = self::getWpVersion() ;
                    $passed = $currentValue >= $value ;                     
                    $label = __("Version" , REALTYNA_MLS_SYNC_SLUG ) ;
                    $operator = __("greater than or equal to" , REALTYNA_MLS_SYNC_SLUG ) ;
                    break;

                case 'php':
                    $currentValue = self::getPhpVersion() ;
                    $passed = $currentValue >= $value ;                     
                    $label = __("Version" , REALTYNA_MLS_SYNC_SLUG ) ;
                    $operator = __("greater than or equal to" , REALTYNA_MLS_SYNC_SLUG ) ;
                    break;

                case 'mysql':
                    $currentValue = self::getMysqlVersion() ;
                    $passed = $currentValue >= $value ;                     
                    $label = __("Version" , REALTYNA_MLS_SYNC_SLUG )  ;
                    $operator = __("greater than or equal to" , REALTYNA_MLS_SYNC_SLUG ) ;
                    break;

                case 'max_execution_time':
                    $currentValue = self::getMaxExecutionTime() ;
                    $passed = ( ($currentValue <= 0 ) || ( $currentValue >= $value ) ) ;
                    $label = __("Value" , REALTYNA_MLS_SYNC_SLUG ) ;
                    $operator = __("greater than or equal to" , REALTYNA_MLS_SYNC_SLUG ) ;
                    break;
    
                case 'memory_limit':
                    $currentValue = self::getMemoryLimit() ;
                    $passed = ( ($currentValue <= 0 ) || ( $currentValue >= $value ) ) ;                    
                    $label = __("Value" , REALTYNA_MLS_SYNC_SLUG )  ;
                    $operator = __("greater than or equal to" , REALTYNA_MLS_SYNC_SLUG );
                    break;

                case 'post_max_size':
                    $currentValue = self::getPostMaxSize() ;
                    $passed = ( ($currentValue <= 0 ) || ( $currentValue >= $value ) ) ;                     
                    $label = __("Value" , REALTYNA_MLS_SYNC_SLUG ) ;
                    $operator = __("greater than or equal to" , REALTYNA_MLS_SYNC_SLUG ) ;
                    break;

                case 'upload_max_filesize':
                    $currentValue = self::getUploadMaxFilesize() ;
                    $passed = ( ($currentValue <= 0 ) || ( $currentValue >= $value ) ) ;                     
                    $label = __("Value" , REALTYNA_MLS_SYNC_SLUG ) ;
                    $operator = __("greater than or equal to" , REALTYNA_MLS_SYNC_SLUG );
                    break;
                        
            }

            $requirementsStatus[ $requiementTitle  ] = array(
                "current_value" => $currentValue ,
                "required_value" => $value ,
                "operator" => $operator ,
                "text" => $requiementTitle . " " . $label . " " . __('should be' , REALTYNA_MLS_SYNC_SLUG ) . " " . $operator . " " . $value ,
                "passed" => $passed 
            );

        }

        return $requirementsStatus ;
        
    }

    /**
     * Get WordPress Version
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return float|bool Wordpress Version or False on fails
     */
    public static function getWpVersion(){

        if ( function_exists('get_bloginfo') )
            return floatval( get_bloginfo( 'version' ) );
        
        return false;

    }

    /**
     * Get PHP Version
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return float
     */
    public static function getPhpVersion(){

        return floatval( phpversion() );

    }

    /**
     * Get MySQL Version
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return float
     */
    public static function getMysqlVersion(){

        global $wpdb;

        if ( isset($wpdb) )
            return floatval( $wpdb->get_var( "SELECT version()" ) );

        return 0;

    }

    /**
     * Get Max Execution time of PHP Scripts
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return int as seconds
     */
    public static function getMaxExecutionTime(){

        return ini_get( 'max_execution_time' );

    }

    /**
     * Get Memory Limit For PHP Scripts
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return int
     */
    public static function getMemoryLimit(){

        return intval( ini_get( 'memory_limit' ) );

    }

    /**
     * Get Post Max Size For PHP 
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return int
     */
    public static function getPostMaxSize(){

        return intval( ini_get( 'post_max_size' ) );

    }

    /**
     * Get Max File Size for upload
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return int
     */
    public static function getUploadMaxFilesize(){

        return intval( ini_get( 'upload_max_filesize' ) );

    }

    /**
     * Check activate status of houzez functionality plugin
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function isHouzezFunctionalityPluginActive(){

        if ( ! self::houzezFunctionalityPluginExists() ) 
            return false;

        $houzezFunctionlityPlugin = 'houzez-theme-functionality/houzez-theme-functionality.php';

        return is_plugin_active( $houzezFunctionlityPlugin );

    }    

    /**
     * Check existance of Houzez Functinality plugin
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function houzezFunctionalityPluginExists(){

        $houzezFunctionlityPlugin = 'houzez-theme-functionality/houzez-theme-functionality.php';
		
        if ( ! function_exists( 'get_plugins' ) )
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
	  
		$plugins = get_plugins();
	   		
		return ( !empty( $plugins[ $houzezFunctionlityPlugin ] ) ) ? true : false;

    }    

    /**
     * Check activate status of Houzez Theme
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function isHouzezThemeActive(){
        
        if ( !function_exists('get_stylesheet') )
            return false;

        return ( get_stylesheet() == 'houzez' ) ;

    }

    /**
     * Check existance of Houzez Theme 
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function houzezThemeExists(){

        $theme = wp_get_theme( 'houzez' );
							
        return $theme->exists();

    }

}
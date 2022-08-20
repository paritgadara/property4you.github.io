<?php
// Block direct access to the main plugin file.
defined( 'ABSPATH' ) || die( 'Access Denied!' );

/** @var string default root folder for views */
define("REALTYNA_VIEW_ROOT_FOLDER" , 'views' );

/** @var string template root */
define("REALTYNA_TEMPLATE_ROOT" , dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . REALTYNA_VIEW_ROOT_FOLDER . DIRECTORY_SEPARATOR );

/**
 * Render Views files in PHP format
 * 
 * @author Chris A <chris.a@realtyna.net>
 * 
 * @version 1.0
 */
class RealtynaView {

    /**
     * Render PHP Template Views
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string name of template file
     * @param array|null append data to template
     * 
     * @return void
     */
    public static function view( $template , $_REALTYNA = null ){

        self::require( $template , $_REALTYNA  );

    }

    /**
     * Get Template file Path
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string Template
     * 
     * @return string Template file path
     */
    public static function getTemplateFile( $template ){
        
        $templatePath = str_replace('.' , DIRECTORY_SEPARATOR , $template ) . '.php' ;

        return REALTYNA_TEMPLATE_ROOT . $templatePath;

    }

    /**
     * Import Template file
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string Template
     * @param array|null append Data to Template
     * 
     * @return void
     */
    public static function require( $template  , $_REALTYNA = null  ){

        if ( file_exists( self::getTemplateFile( $template ) ) )
            require_once( self::getTemplateFile( $template ) );        
        
    }

}


<?php
/** Block direct access to the main plugin file. */ 
defined( 'ABSPATH' ) || die( 'Access Denied!' );

/** @var string Path to Realtyna directory  */
define( "REALTYNA_DIR" , dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'realtyna' . DIRECTORY_SEPARATOR );

/**
 * Relatyna Autoloader Class
 * 
 * @author Chris A <chris.a@realtyna.net>
 * 
 * @version 1.0
 */
class RealtynaAutoloader 
{

    /**
     * Constructor Method
     * 
     * @return void
     */
    public function __construct()
    {

        spl_autoload_register( array( $this, 'autoload' ) );

    }

    /**
     * Autoload Classes
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string class name
     * 
     * @return void
     */
    public function autoload( $className )
    {

        $classFile = str_replace( "_" , "-" , $className );
        $filePath = REALTYNA_DIR . $classFile . ".php" ;

        $filePathPSR = REALTYNA_DIR . $className . ".php" ;

        if ( file_exists( $filePath ) ){

            require_once( $filePath ) ;

        }

        if ( file_exists( $filePathPSR ) ){

            require_once( $filePathPSR ) ;

        }


    }

}

new RealtynaAutoloader();
<?php
/** Block direct access to the main plugin file.*/ 
defined( 'ABSPATH' ) || die( 'Access Denied!' );

/**
 * Handle communication with Realtyna MLS Sync Dashboard
 * 
 * @author Chris A <chris.a@realtyna.net>
 * 
 * @version 1.0
 */
class RealtynaMlsSyncDashboard
{

    /** @var string dashboard API ENDPOINT */
    const MLS_SYNC_DASHBOARD_ENDPOINT = "https://sync.realtyna.com/api/v2/clients/";

    private $response;

    private $token;
    private $userID;

    public function __construct( $token = null , $userID = null ){

        $this->response = null;

        $this->userID = $userID;
        $this->token = $token;

    }

    private function activationSignal()
    {
        return $this->sendSignal( [ 'signal' => 'plugin_activated'] );
    }

    private function deactivationSignal()
    {
        return $this->sendSignal( [ 'signal' => 'plugin_deactivated'] );
    }

    private function sendSignal( $signalParams = [] )
    {
        
        if ( !empty( $signalParams ) ){

            return $this->sendRequest( 'signal' ,$signalParams );

        }

        return false;

    }

    private function sendRequest( $action , $additionalParams = [] )
    {
        
        if ( !empty( $action ) && function_exists('get_option') ){

            $requestParams = $additionalParams;
            $requestParams['request_action'] = $action;
            $requestParams['request_time'] = time();
            $requestParams['request_token'] = $this->token ?? '';
            $requestParams['request_plugin_ver'] = $this->getPluginVersion();
            $requestParams['site_url'] = get_option('siteurl') ?? 'none';
            $requestParams['site_title'] = get_option('blogname') ?? 'none';
            $requestParams['site_theme'] = get_option('template') ?? 'none';
            $requestParams['houzez_purchase_code'] =  get_option('houzez_purchase_code') ?? 'none';
            $requestParams['houzez_activation_status'] = get_option('houzez_activation') ?? 'none';

            return $this->request( $action , $requestParams );
    
        }

        return false;
        
    }

    private function serializedArrayHash( $arrayParams )
    {
        return sha1( serialize( $arrayParams ) ) ;
    }
    
    private function getResponse(){

        return $this->response;

    }

    private function apiHeaders( $arrayParams )
    {
       
        $headers = [];

        if ( !empty( $arrayParams ) ){
            
            $hash = $this->serializedArrayHash( $arrayParams );

            $headers = [
                "Authorization" => "Token {$hash}"
            ];
    
        }

        return $headers;

    }

    private function request( $action , $arrayParams )
    {
        
        return $this->apiRequest( $action , $arrayParams , $this->apiHeaders( $arrayParams ) );

    }


    private function apiRequest( $action , $arrayParams , $arrayHeaders = [] )
    {
        
        if ( function_exists('wp_remote_post') ){

            $apiEndpoint = self::MLS_SYNC_DASHBOARD_ENDPOINT . $action ;

            $this->response = wp_remote_post( $apiEndpoint, [
                'timeout' => 60,
                'headers' => $arrayHeaders,
                'body' => $arrayParams
            ] );
    
            if ( !is_wp_error( $this->getResponse() ) ){
    
                return ( wp_remote_retrieve_response_code( $this->getResponse() ) == 200 );
    
            }
    
        }

        return false;

    }

    private function getClientStatus(){

        if ( class_exists('RealtynaIdxApi') ){

            $api = new RealtynaIdxApi();
    
            $statusResult = $api->getStatus( $this->token , $this->userID );

            return ( $statusResult['status'] == "OK" );
    
        }
    
        return false;

    }

    private function getPluginVersion()
    {

        $pluginVersion = 0;

        if ( class_exists('RealtynaMlsSync') ){

            $pluginInfo = RealtynaMlsSync::getPluginDetails();
            
            $pluginVersion = $pluginInfo['Version'] ?? 0; 

        }
        
        return $pluginVersion;

    }

}
?>
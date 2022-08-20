<?php
/** Block direct access to the main plugin file.*/ 
defined( 'ABSPATH' ) || die( 'Access Denied!' );

/**
 * Main Plugin to Sync MLS Data with themes
 * 
 * @author Chris A <chris.a@realtyna.net>
 * 
 * @version 1.0
 */
class RealtynaMlsSync {

	/** @var object singleton object */
    public static $instance = false;
    
    /** @var array  available notice types */
    protected $noticeTypes = array( "info" , "error" , "success" , "warning" );

    /** @var string credential Key */
    const REALTYNA_IDX_CREDENTIAL = "REALTYNA_IDX_CREDENTIAL";
    
    /** @var string options Key */
    const REALTYNA_IDX_OPTIONS = "REALTYNA_IDX_OPTIONS";

    /** @var string import Key */
    const REALTYNA_IDX_IMPORT = "REALTYNA_IDX_IMPORT";

    /** @var string mls data Key */
    const REALTYNA_MLS_DATA = "REALTYNA_MLS_DATA";

    /** @var string external images mark */
    const EXTERNAL_IMAGES_MARK = "_REALTYNA_MLS_SYNC_EXTERNAL_IMAGE";

    /** @var int minimum time needed for execute scripts in seconds */
    const MINIMUM_EXECUTION_TIME_FOR_DATA_IMPORT = 3000;

    /**
     * Class Constructor Method
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    public function __construct() {
        
        add_action( 'admin_enqueue_scripts', array( $this , 'loadPluginStyles' ) );    
        add_action( 'admin_enqueue_scripts', array( $this , 'loadPluginScripts' ) ); 

        add_action( 'admin_menu' , array ( $this , 'drawMenu' ) );
        
        add_action( 'wp_ajax_realtynaidx' , array ( $this , 'ajaxResponse' ) );

        add_filter( 'get_attached_file', array( $this , 'handleExternalMedia') , 100, 2 );        
        add_filter( 'post_thumbnail_html', array( $this , 'handleExternalThumbnail' ), 100, 5);
        add_filter( 'wp_get_attachment_image', array( $this , 'fixExternalThumbnailsInEditPage' ), 100, 5);

        add_action( 'wp_loaded' , array( $this , 'autoUpdatePlugin' ) );

        $this->initNotices();
        $this->initRestRoutes();
        
    }

    /**
     * Get singlton instance of current class
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return object
     */
	public static function getInstance() {
		
		if ( !self::$instance )
			self::$instance = new self;
		
		return self::$instance;
		
	}
    
    /**
     * Draw Admin Menus
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
	public function drawMenu() {
				
		if ( is_super_admin() || current_user_can( 'administrator' ) ){

            add_menu_page( __('Realtyna MLS Sync' , REALTYNA_MLS_SYNC_SLUG ) , __('Realtyna MLS Sync' , REALTYNA_MLS_SYNC_SLUG ) , 'manage_options', REALTYNA_MLS_SYNC_SLUG , array ( $this , 'screenMain' ) , REALTYNA_MLS_SYNC_ICON );

			add_submenu_page( REALTYNA_MLS_SYNC_SLUG, __('Hosting Benchmark' , REALTYNA_MLS_SYNC_SLUG ) , __('Hosting Benchmark' , REALTYNA_MLS_SYNC_SLUG) , 'manage_options', 'realtyna-hosting-benchmark' , array ( $this , 'screenBenchmark' ) );	

            if ( $this->getIdxOptions() !== false )
			    add_submenu_page( REALTYNA_MLS_SYNC_SLUG, __('Settings' , REALTYNA_MLS_SYNC_SLUG ) , __('Settings' , REALTYNA_MLS_SYNC_SLUG) , 'manage_options', 'realtyna-mls-sync-settings' , array ( $this , 'screenSettings' ) );

        }

	}

    /**
     * Display Settings screen
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    public function screenSettings(){

        $this->showHeader();

        if ( class_exists('RealtynaView') ){

            $agents = RealtynaHouzezAgent::get();
            $agentsDisplayOptions = RealtynaHouzezAgent::getDisplayOptions();
                                                
            $realtyna['agents'] = $agents;
            $realtyna['agents_display_options'] = $agentsDisplayOptions;
            $realtyna['idx_options'] = $this->getIdxOptions();
            $realtyna['idx_import'] = $this->getIdxImport();

            RealtynaView::view('settings' , $realtyna );

        }

        $this->showFooter();

    }

    /**
     * Display Benchmarker screen
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     *  @return void
     */
    public function screenBenchmark(){

        include_once( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'addon' . DIRECTORY_SEPARATOR . 'benchmarker.php'  );

    }

    /**
     * Display Main screen
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    public function screenMain(){

        if ( isset( $_GET["payment"] ) && in_array( $_GET['payment'] , ["success" , "cancel"] ) ) {

            if ( $this->isStripeCallBack() ){

                $this->showPayment( $_GET["payment"] );

            }else{

                $this->gotoStep( $this->determineCurrentStep() );

            }


        }else{

            if ( isset( $_GET['reset_mls'] ) ){
            
                $this->resetMlsData();
                
            }
    
            $step = ( isset( $_GET['step'] ) && is_numeric( $_GET['step'] ) ) ? $_GET['step'] : $this->determineCurrentStep() ;

            if ( $step > 1 && !isset( $_GET['step'] ) ){

                $this->gotoStep( $step );

            }else {

                $this->stepsWizard( $step );
            }
    
        }
        
    }

    /**
     * Display selected step wizard
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function stepsWizard( $step = 1 ){
        
        if ( is_numeric( $step ) ){

            $this->showHeader();

            $this->showStepWizard( $step );

            switch ( $step ) {
                case 2:
                    $this->secondStep();
                    break;
                
                case 3:
                    $this->thirdStep();
                    break;
    
                case 4:
                    $this->fourthStep();
                    break;
                
                case 5:
                    $this->fifthStep();
                    break;
    
                default:
                    $this->firstStep();
                    break;
            }

            $this->showFooter();
    
        }

    }

    /**
     * Display first step wizard
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function firstStep(){

        if ( class_exists('RealtynaView') ){

            RealtynaView::view('steps.first' , RealtynaHouzez::getHouzezRequirementsStatus() );

        }

    }

    /**
     * validate first step wizard
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function validateFirstStep(){

        return ( 
            RealtynaHouzez::houzezThemeExists() && 
            RealtynaHouzez::isHouzezThemeActive() && 
            RealtynaHouzez::houzezFunctionalityPluginExists() &&
            RealtynaHouzez::isHouzezFunctionalityPluginActive() &&
            RealtynaHouzez::haveHouzezRequirements()
        );

    }

    /**
     * Display second step wizard
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function secondStep(){
        
        if ( $this->validateFirstStep() ){

            if ( class_exists('RealtynaView') ){

                $realtyna['credentials'] = $this->getCredentials();

                RealtynaView::view('steps.second' , $realtyna );

                return true;

            }

        }

        $this->gotoStep( 1 );

    }

    /**
     * validate second step wizard
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function validateSecondStep(){

        $credentials = static::getCredentials();

        return ( is_array( $credentials ) && !empty( $credentials ) ) ;

    }

    /**
     * Display third step wizard
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function thirdStep(){
        
        if ( $this->validateSecondStep() ){

            $agents = RealtynaHouzezAgent::get();
            $agentsDisplayOptions = RealtynaHouzezAgent::getDisplayOptions();
                            
            if ( class_exists('RealtynaView') ){
                    
                $realtyna['agents'] = $agents;
                $realtyna['agents_display_options'] = $agentsDisplayOptions;
                $realtyna['idx_options'] = $this->getIdxOptions();
                $realtyna['idx_import'] = $this->getIdxImport();
    
                RealtynaView::view('steps.third' , $realtyna );
    
                return true;
            }
    
        }

        $this->gotoStep( 2 );

    }

    /**
     * validate third step wizard
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function validateThirdStep(){

        $instance = new static();
        $idxOptions = $instance->getIdxOptions();

        return ( is_array( $idxOptions ) && !empty( $idxOptions ) ) ;

    }

    /**
     * Display fourth step wizard
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function fourthStep(){

            if ( !$this->validateThirdStep() ){

                if ( isset( $_GET['realtyna_idx_selected_agent'] ) && 
                    isset( $_GET['realtyna_idx_selected_agent_option'] ) && 
                    isset( $_GET['realtyna_idx_images_option'] ) && 
                    isset( $_GET['third_step_act'] ) && 
                    wp_verify_nonce( $_GET['third_step_act'], 'third_step_nonce' ) )
                {

                    $params = array(
                        "agent" => $_GET['realtyna_idx_selected_agent'],
                        "agent_option" => $_GET['realtyna_idx_selected_agent_option'],
                        "image_option" => $_GET['realtyna_idx_images_option']
                    );

                    $this->setIdxOptions( $params );

                }

            }
    
            if ( $this->validateThirdStep() ){
                
                $credentials = $this->getCredentials();

                if ( !empty( $credentials['token'] ) ){

                    if ( class_exists('RealtynaView') ){

                        $realtyna['mlsData'] = $this->getMlsData();

                        if ( !empty( $realtyna['mlsData'] ) && is_array( $realtyna['mlsData'] ) ){

                            RealtynaView::view( 'dashboard' , $realtyna );

                            return true;

                        }else{

                            $api = new RealtynaIdxApi();
                            $apiProviders = $api->getProviders( $credentials['token'] );
        
                            $realtyna['providers'] = $apiProviders['message'];

                            if ( $apiProviders['status'] == 'OK' ){

                                RealtynaView::view( 'steps.fourth' , $realtyna );

                                return true;

                            }

                        }
        
                    }

                }
                        
            }


        $this->gotoStep( 3 );

    }

    /**
     * validate fourth step wizard
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function validateFourthStep(){

        $mlsData = static::getMlsData();

        return ( is_array( $mlsData ) && !empty( $mlsData ) ) ;

    }

    /**
     * Display fifth step wizard
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function fifthStep(){

        $this->gotoStep( 4 );

    }

    /**
     * Determine current steps to continue from there
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return int step number
     */
    private function determineCurrentStep(){

        if ( $this->validateThirdStep() )
            return  4;

        if ( $this->validateSecondStep() )
            return  3;

        return 1;
    }

    /**
     * Display payments views
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string payment view
     * 
     * @return void
     */
    private function showPayment( $view ){

        if ( $view == 'success' ){

            $mlsData = $this->getMlsData();

            if ( is_array( $mlsData ) && !empty( $mlsData ) && $mlsData['status'] == 'pending' ){

                $this->updateMlsData( [ "status" => "paid" ] );

            }

        }
        
        $this->showHeader();

        $realtyna['payment'] = $view;
        $realtyna['mlsData'] = $this->getMlsData();



        if ( class_exists('RealtynaView') ){

            RealtynaView::view( 'dashboard' , $realtyna );

        }

        $this->showFooter();            

    }

    /**
     * Display step wizard view
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param int Step number
     * 
     * @return void
     */
    private function showStepWizard( $step ){

        $data = [ "step" => $step ];

        if ( class_exists('RealtynaView') )
            RealtynaView::view( 'steps.steps' , $data );


    }

    /**
     * Display header view
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function showHeader(){

        $realtyna['plugin'] = self::getPluginDetails();
        $realtyna['isUpdateAvailable'] = false;
        $realtyna['updateLastVersion'] = $realtyna['plugin']['Version'];

        if ( class_exists('RealtynaUpdater') && class_exists('RealtynaUpdaterWpPlugin') ){

            $pluginSlug = $realtyna['plugin']['TextDomain'];
            $pluginVersion = $realtyna['plugin']['Version'];

            if ( !empty( $pluginSlug ) || !empty( $pluginVersion ) ){

                $pluginUpdater = new  RealtynaUpdaterWpPlugin( $pluginSlug , $pluginVersion );
                $pluginUpdater->updateInquiry();

                $realtyna['isUpdateAvailable'] = $pluginUpdater->isUpdateAvailable() ;
                $realtyna['updateLastVersion'] = $pluginUpdater->getLastVersion() ;
    
            }

        }

        if ( class_exists('RealtynaView') )
            RealtynaView::view( 'steps.header' , $realtyna );

    }

    /**
     * Display footer view
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function showFooter(){

        if ( class_exists('RealtynaView') )
            RealtynaView::view( 'steps.footer' );

    }

    /**
     * Response to ajax requests
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
	public function ajaxResponse(){
		
		$response = [ 'status' => 'ERROR' , 'message' => __('Invalid Data' , REALTYNA_MLS_SYNC_SLUG) ];
        
        if ( isset( $_POST['method'] ) && 
             !empty( $_POST['method'] ) &&
             isset( $_POST['nonce'] ) &&
			 wp_verify_nonce( $_POST['nonce'], 'realtyna_houzez_secret_nonce' ) 
			 )
        {
            
            switch ($_POST['method']) {

                case 'demo':
                    
                    if ( isset( $_POST['agent'] ) && 
                         isset( $_POST['agent_option'] ) && 
                         isset( $_POST['image_option'] ) )
                    {
                        $wpUserID = is_user_logged_in() ? get_current_user_id() : 0;
                        $params = array(
                            "agent" => $_POST['agent'],
                            "agent_option" => $_POST['agent_option'],
                            "image_option" => $_POST['image_option'],
                            "post_author" => $wpUserID
                        );

                        $response = $this->ajaxResponseDemoImport( $params );
                    }

                    break;

                case 'demo-progress':

                    $response = $this->ajaxResponseDemoProgress();
    
                    break;    
                
                case 'client-info':

                    if ( isset( $_POST['client_name'] ) && 
                         isset( $_POST['client_email'] ) && 
                         isset( $_POST['client_phone'] ) && 
                         isset( $_POST['client_role'] ) )
                    {
                        $params = array(
                            "name" => $_POST['client_name'],
                            "email" => $_POST['client_email'],
                            "phone_number" => $_POST['client_phone'],
                            "role" => $_POST['client_role']
                        );

                        $response = $this->ajaxResponseClientInfo( $params );
                    }

                    break;

                case 'request-mls':

                    if ( isset( $_POST['provider'] ) && 
                         isset( $_POST['state'] ) )
                    {

                        $params = array(
                            "provider" => $_POST['provider'],
                            "state" => $_POST['state']
                        );
        
                        $response = $this->ajaxResponseRequestMLS( $params );    

                    }
    
                    break;    

                case 'select-mls':

                    if ( isset( $_POST['mls_id'] ) && 
                         isset( $_POST['mls_name'] ) &&
                         isset( $_POST['mls_slug'] ) )
                    {
    
                        $params = array(
                            "id" => $_POST['mls_id'],
                            "name" => $_POST['mls_name'],
                            "slug" => $_POST['mls_slug'],
                            "status" => "none",
                            "checkout" => ""
                        );
                                    
                        $response = $this->ajaxResponseSelectMLS( $params );    
    
                    }
        
                    break;

                case 'settings':

                    if ( isset( $_POST['agent'] ) && 
                        isset( $_POST['apply_agent_to_all'] ) &&
                        isset( $_POST['agent_option'] ) &&
                        isset( $_POST['apply_agent_display_option_to_all'] ) &&
                        isset( $_POST['image_option'] ) )
                    {
        
                        $params = array(
                            "agent" => $_POST['agent'],
                            "apply_agent_to_all" => $_POST['apply_agent_to_all'],
                            "agent_option" => $_POST['agent_option'],
                            "apply_agent_display_option_to_all" => $_POST['apply_agent_display_option_to_all'],
                            "image_option" => $_POST['image_option']
                        );
                                        
                        $response = $this->ajaxResponseSettings( $params );    
        
                    }
            
                    break;    

                case 'remove-demo':
            
                    if ( $this->ajaxResponseRemoveDemo() ) {

                        $response["status"] = "OK";
                        $response["message"] = __("Requested Action has been done!" , REALTYNA_MLS_SYNC_SLUG );
                            
                    }

                    break;    

                case 'update-plugin':
            
                    if ( $this->ajaxResponseUpdatePlugin() ) {
    
                        $response["status"] = "OK";
                        $response["message"] = __("Plugin updated Successfully!" , REALTYNA_MLS_SYNC_SLUG );
                                
                    }
    
                    break;    
                            
                default:
                    
                    $response["message"] = __( "Unknown Method!" , REALTYNA_MLS_SYNC_SLUG );

                    break;                
            }    
                
        }
        
        die( json_encode( $response ) );

    }

    /**
     * Check Demo import is in progress or failed
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param int $waitfor wait for seconds before check
     * 
     * @return array response array
     */
    private function ajaxResponseDemoProgress( $waitFor = 0 )
    {

        $response = [ "status" => "ERROR" , "message" => __("Demo Import Error!" , REALTYNA_MLS_SYNC_SLUG) ];

        if ( class_exists('RealtynaHouzezProperty') ){
            
            if ( $waitFor > 0 ){

                sleep( $waitFor );
                
            }

            $countImportedDemo = RealtynaHouzezProperty::countImportedProperties( true );

            if ( $countImportedDemo > 0 ){

                $response['status'] = "OK";
                $response['message'] = __( "Importing listings is in progress and will be completed in a few minutes!<br>You can proceed with next step or check imported Listings!" , REALTYNA_MLS_SYNC_SLUG );
    
            }
    
        }
        
        return $response;

    }

    /**
     * Process ajax response to update-plugin Request
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return array
     */
    private function ajaxResponseUpdatePlugin(){

        $response = [ "status" => "ERROR" , "message" => __("Update Error: Contact With Tehnical support" , REALTYNA_MLS_SYNC_SLUG) ];

        if ( self::updatePlugin() ){
            
            $response['status'] = "OK";
            $response['message'] = __( "Plugin Updated Successfully!" , REALTYNA_MLS_SYNC_SLUG );
    
        }
        
        return $response;

    }

    /**
     * Process ajax response to remove-demo Request
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    private function ajaxResponseRemoveDemo(){

        $this->removeProperties( true );
        
        return $this->deleteIdxImport();

    }

    /**
     * Process ajax response to settings Request
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param array array of needed data
     * 
     * @return array
     */
    private function ajaxResponseSettings( $params ){

        $response = [ "status" => "ERROR" , "message" => __("Unknown Error!" , REALTYNA_MLS_SYNC_SLUG) ];

        if (!empty( $params['agent'] ) &&
            !empty( $params['agent_option'] ) &&
            !empty( $params['image_option'] ) )
        {
                        
            $newOptions = array();
            $newOptions["agent"] = $params['agent'];
            $newOptions["agent_option"] = $params['agent_option'];
            $newOptions["image_option"] = $params['image_option'];

            $updateResult = $this->updateIdxOptions( $newOptions ) ;

            $response['status'] = ( $updateResult !== false ) ? "OK" : "ERROR";
            $response['message'] = ( $response['status'] == "OK" ) ? __("Settings Updated Successfully!" , REALTYNA_MLS_SYNC_SLUG ) : __("Settings Update Error!" , REALTYNA_MLS_SYNC_SLUG );

            if ( $params['apply_agent_to_all'] == "true" )
                $this->update_properties_agents( $params['agent'] );

            if ( $params['apply_agent_display_option_to_all'] == "true" )
                $this->updatePropertiesAgentDisplayOption( $params['agent_option'] );

        }else{
            $response["message"] = __("Invalid Params!" , REALTYNA_MLS_SYNC_SLUG) ;
        }

        return $response ;

    }

    /**
     * Process ajax response to select-mls Request
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param array array of needed data
     * 
     * @return array
     */
    private function ajaxResponseSelectMLS( $params ){

        $response = [ "status" => "ERROR" , "message" => __("Unknown Error!" , REALTYNA_MLS_SYNC_SLUG) ];

        if (!empty( $params['id'] ) &&
            !empty( $params['name'] ) &&
            !empty( $params['slug'] ) )
        {  

            $credentials = $this->getCredentials();

            if ( $credentials !== false ){                

                if ( class_exists('RealtynaIdxApi') ){
                    
                    $mlsData = $this->getMlsData();

                    $api = new RealtynaIdxApi();

                    $checkout = $api->checkout( $credentials['token'] , $credentials['user_id'] , (int) $params['id'] );
                    
                    if ( is_array( $checkout ) && $checkout['status'] == "OK" && !empty( $checkout['message'] ) ){

                        $params['status'] = "pending";
                        $params['checkout'] = $checkout['message'];

                        $this->setMlsData( $params );

                        $response['status'] = 'OK';                        
                        $response['message'] = '';
                        $response['payment_link'] = $checkout['message'];

                    }else{

                        $response['message'] = __('It seams there is some issues with Payment system.<br>Please call us to resolve the issue' ,REALTYNA_MLS_SYNC_SLUG )  ;

                    }

                }else{

                    $response['message'] = __("Missed Functionality!" , REALTYNA_MLS_SYNC_SLUG ) ;

                }
            
            }else {
                $response["message"] = __("Invalid Credentials!" , REALTYNA_MLS_SYNC_SLUG) ;
            }
    

        }else{
            $response["message"] = __("Invalid Params!" , REALTYNA_MLS_SYNC_SLUG) ;
        }

        return $response ;

    }

    /**
     * Process ajax response to request-mls Request
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param array array of needed data
     * 
     * @return array
     */
    private function ajaxResponseRequestMLS( $params ){

        $response = [ "status" => "ERROR" , "message" => __("Unknown Error!" , REALTYNA_MLS_SYNC_SLUG) ];

        if (!empty( $params['provider'] ) &&
            !empty( $params['state'] ) )
        {

            $credentials = $this->getCredentials();

            if ( !empty( $credentials['token'] ) && is_numeric( $credentials['user_id'] ) ){

                $api = new RealtynaIdxApi();

                $result = $api->requestProvider( $credentials['token'] , $credentials['user_id'] , $params['provider'] , $params['state'] );
    
                if ( $result['status'] == 'OK' ){
                    
                    $mlsData = array();
                    $mlsData['id'] = 0;
                    $mlsData['name'] = $params['provider'];
                    $mlsData['slug'] = '';
                    $mlsData['status'] = 'none';
                    $mlsData['checkout'] = '';

                    $this->setMlsData( $mlsData );

                    $response['status'] = 'OK';
                    $response['message'] = __( 'Your Request for MLS has been sent successfully!<br>Our team will contact you<br>If you need more information you can contact us: sync@realtyna.net' , REALTYNA_MLS_SYNC_SLUG );
    
                }elseif ( isset( $result['message'] ) ) {
    
                    $response['message'] = $result['message'];
    
                }    
    
            }else{
                $response['message'] = __( 'Credentials Error!' , REALTYNA_MLS_SYNC_SLUG );
            }

        }else{
            $response["message"] = __("Invalid Params!" , REALTYNA_MLS_SYNC_SLUG) ;
        }

        return $response ;

    }

    /**
     * Process ajax response to client-info Request
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param array array of needed data
     * 
     * @return array
     */
    private function ajaxResponseClientInfo( $params ){

        $response = [ "status" => "ERROR" , "message" => __("Unknown Error!" , REALTYNA_MLS_SYNC_SLUG) ];

        if (!empty( $params['name'] ) &&
            !empty( $params['email'] ) &&
            !empty( $params['phone_number'] ) &&
            !empty( $params['role'] ) )
        {

            if ( $this->getCredentials() === false ){

                $params['theme'] = RealtynaHouzezMapper::getClientTheme();
                
                $api = new RealtynaIdxApi();

                $result = $api->register( $params );

                if ( $result['status'] == 'OK' && is_array( $result['message'] ) ){

                    $this->setCredentials( $result['message'] );

                    $response = (array) $result['message'];
                    $response['status'] = 'OK';

                }elseif ( isset( $result['message'] ) ) {
                    $response['message'] = $result['message'];
                }
    
            }else{
                $response['status'] = "OK";
                $response['message'] = __("Client Info already exists!", REALTYNA_MLS_SYNC_SLUG ) ;
            }

        }else{
            $response["message"] = __("Invalid Params!" , REALTYNA_MLS_SYNC_SLUG) ;
        }

        return $response ;

    }

    /**
     * Process ajax response to demo Request
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param array array of needed data
     * 
     * @return array
     */
    private function ajaxResponseDemoImport( $params ){

        $response = [ "status" => "ERROR" , "message" => __("Unknown Error!" , REALTYNA_MLS_SYNC_SLUG) ];

        if (!empty( $params['agent'] ) &&
            !empty( $params['agent_option'] ) &&
            !empty( $params['image_option'] ) )
        {
            if ( $this->getIdxImport() !== false ){

                $response['message'] = __("Demo Listing Already Imported!" , REALTYNA_MLS_SYNC_SLUG);
                
            }else{

                $this->setIdxOptions( $params );

                $this->setMaxExecutionTime();
    
                $addation = [ 
                    "fave_agents" => $params['agent'] ,
                    "fave_agent_display_option" => $params['agent_option']                     
                ];
        
                $importOptions = [
                    "generate_thumbs_images" => false,
                    "max_images_import" => ( $params['image_option'] > 0 ) ? 50 : 20 ,
                    "max_property_import" => ( $params['image_option'] > 0 ) ? 50 : 20 ,
                    "use_external_images" => ( $params['image_option'] == 1 ||  $params['image_option'] == 2 ),
                    "use_external_thumbnail" => ( $params['image_option'] == 2 ) ,
                    "post_author" => $params['post_author']
                ];
        
                $mapper = new RealtynaHouzezMapper( $this->getCredentialToken() , RealtynaHouzezMapper::DEMO_PROVIDER , $addation , $importOptions );
                $result = $mapper->run();
        
        
                if ( !empty( $result ) ) {
        
                    if ( is_numeric( $result ) ){
        
                        $response['status'] = "OK";
                        $response['message'] = $result . " " . __('Properties has been added as Demo Listings!' , REALTYNA_MLS_SYNC_SLUG );
    
                        $this->setIdxImport( true );
        
                    }else{
                        $response['message'] = $result;
                    }
        
                }else{
                    $response['message'] = __('Unknown Result!' , REALTYNA_MLS_SYNC_SLUG ) ;
                }
    
            }    

        }else{
            $response["message"] = __("Invalid Params!" , REALTYNA_MLS_SYNC_SLUG ) ;
        }

        return $response ;

    }

    /**
     * Update Plugin if it's available
     * 
     * @see RealtynaUpdaterWpPlugin
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function updatePlugin()
    {
        
        if ( class_exists('RealtynaUpdater') && class_exists('RealtynaUpdaterWpPlugin') ){

            $pluginInfo = self::getPluginDetails();

            $pluginSlug = $pluginInfo['TextDomain'];
            $pluginVersion = $pluginInfo['Version'];

            if ( !empty( $pluginSlug ) || !empty( $pluginVersion ) ){

                $pluginUpdater = new  RealtynaUpdaterWpPlugin( $pluginSlug , $pluginVersion );
                $pluginUpdater->updateInquiry();

                if ( $pluginUpdater->isUpdateAvailable() ){

                    return $pluginUpdater->updatePlugin() ;

                }
    
            }

        }        

        return false;

    }

    /**
     * Auto Update Plugin Functionality
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public function autoUpdatePlugin()
    {
        
        return self::updatePlugin();

    }

    /**
     * Store mls data to DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param array array of mls data
     * 
     * @return bool
     */
    private function setMlsData( $params ){

        if ( is_array( $params ) && !empty( $params ) ){
            return update_option( self::REALTYNA_MLS_DATA , json_encode( $params ) );
        }

        return false;

    }

    /**
     * Get mls data from DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return array
     */
    public static function getMlsData(){

        return ( get_option( self::REALTYNA_MLS_DATA ) ? json_decode( get_option( self::REALTYNA_MLS_DATA ) , true ) : false );

    }

    /**
     * Update mlsData in DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param array array of mlsData
     * 
     * @return bool
     */
    private function updateMlsData( $params ){

        $mlsData = $this->getMlsData();

        if ( $mlsData !== false && !empty( $params ) ){
            
            foreach ( $params as $paramKey => $paramValue ){
                
                if ( isset( $mlsData[ $paramKey ] ) ){

                    $mlsData[ $paramKey ] = $paramValue;

                }

            }

            return $this->setMlsData( $mlsData );

        }

        return false;

    }

    /**
     * Reset MLS Data
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    private function resetMlsData(){

        if ( function_exists('delete_option') ){

            return delete_option( self::REALTYNA_MLS_DATA );
        }

        return false;

    }

    /**
     * Extract Provider from mls data in DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return string
     */
    private function getMlsProvider(){

        return $this->getMlsItem( 'slug' );

    }

    /**
     * Extract Needed Item from mls data in DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string Name of selected mls data
     * 
     * @return string
     */
    private function getMlsItem( $item ){

        if ( empty( trim( $item ) ) )
            return '';

        $mls = $this->getMlsData();

        return ( $mls !== false  && isset( $mls[ $item ] ) ) ? $mls[ $item ] : '';

    }    

    /**
     * Store Credentials in DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param array array of credentials info
     * 
     * @return bool
     */
    private function setCredentials( $params ){

        if ( is_array( $params ) && !empty( $params ) ){

            $param['wp_user_id'] = is_user_logged_in() ? get_current_user_id() : 0 ;

            return update_option( self::REALTYNA_IDX_CREDENTIAL , json_encode( $params ) );
            
        }

        return false;

    }

    /**
     * Get Credentials info from DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return array
     */
    public static function getCredentials(){

        return ( get_option( self::REALTYNA_IDX_CREDENTIAL ) ? json_decode( get_option( self::REALTYNA_IDX_CREDENTIAL ) , true ) : false );

    }

    /**
     * Extract Token from Credentials info in DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return string
     */
    private function getCredentialToken(){

        return $this->getCredentialItem( 'token' );

    }

    /**
     * Extract User ID from Credentials info in DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return string
     */
    private function getCredentialUser(){

        return $this->getCredentialItem( 'user_id' );

    }

    /**
     * Extract Selected Item from Credentials info in DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string Name of Selected Item in Credentials info
     * 
     * @return string
     */
    private function getCredentialItem( $item ){

        if ( empty( trim( $item ) ) )
            return '';

        $credentials = $this->getCredentials();

        return ( $credentials !== false  && isset( $credentials[ $item ] ) ) ? $credentials[ $item ] : '';

    }

    /**
     * Update IDX Options to DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param array array of idx options
     * 
     * @return bool
     */
    private function updateIdxOptions( $params ){

        $options = $this->getIdxOptions();

        if ( $options !== false && !empty( $params ) ){
            
            foreach ( $params as $paramKey => $paramValue ){
                
                if ( isset( $options[ $paramKey ] ) ){

                    $options[ $paramKey ] = $paramValue;

                }

            }

            return $this->setIdxOptions( $options );

        }

        return false;

    }

    /**
     * Store IDX Options to DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param array array of idx options
     * 
     * @return bool
     */
    private function setIdxOptions( $params ){

        if ( is_array( $params ) && !empty( $params ) ){

            $currentOptions = $this->getIdxOptions();

            if ( is_array( $currentOptions ) && !empty( $currentOptions ) ){

                $arrayDiff = array_diff_assoc( $params , $currentOptions );

                if ( empty( $arrayDiff ) )
                    return true;

            }

            return update_option( self::REALTYNA_IDX_OPTIONS , json_encode( $params ) );
        }

        return false;

    }

    /**
     * Get IDX Options from DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return array
     */
    private function getIdxOptions(){

        return ( get_option( self::REALTYNA_IDX_OPTIONS ) ? json_decode( get_option( self::REALTYNA_IDX_OPTIONS ) , true ) : false );

    }

    /**
     * Store IDX Import status to DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param bool
     * 
     * @return bool
     */
    private function setIdxImport( $imported ){

        return update_option( self::REALTYNA_IDX_IMPORT , $imported );

    }

    /**
     * Get IDX Import status from DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    private function getIdxImport(){

        return ( get_option( self::REALTYNA_IDX_IMPORT ) ? get_option( self::REALTYNA_IDX_IMPORT ) : false );

    }

    /**
     * Remove IDX Import from DB
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    public static function deleteIdxImport(){

        if ( function_exists('delete_option') )
            return delete_option( self::REALTYNA_IDX_IMPORT );

        return false;

    }
    
    /**
     * Get Payment Success URL
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return string
     */
    private function getPaymentSuccessURL(){

        return site_url() . "/wp-admin/admin.php?page=" . REALTYNA_MLS_SYNC_SLUG . "&payment=success";

    }

    /**
     * Get Payment Cancel URL
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return string
     */
    private function getPaymentCancelURL(){

        return site_url() . "/wp-admin/admin.php?page=" . REALTYNA_MLS_SYNC_SLUG . "&payment=cancel";

    }


    /**
     * Load Neede Styles & CSS for plugin
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    public function loadPluginStyles(){

        wp_enqueue_style( 'eweqwe-css'  , plugins_url( '../assets/css/styles.css' , __FILE__ ) );
 
    }

    /**
     * Load Neede Scripts for plugin
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    public function loadPluginScripts(){

		wp_register_script( 'ajaxHandle', plugins_url( '../assets/js/realtyna_mls_sync.js', __FILE__),  array(),  false, true );
		wp_enqueue_script( 'ajaxHandle' );
		wp_localize_script( 'ajaxHandle', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )  );
 
    }

    /**
     * Init needed admin Notices
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function initNotices()
    {
        
        if ( class_exists('RealtynaHouzez') ){
            
            if ( ! RealtynaHouzez::houzezThemeExists()  )

                add_action('admin_notices', array($this, 'houzezThemeRequiredNotice'));    

            elseif ( ! RealtynaHouzez::isHouzezThemeActive()  )

                add_action('admin_notices', array($this, 'houzezThemeRequiredAsActiveTheme'));  
                
            if ( ! RealtynaHouzez::houzezFunctionalityPluginExists()  )

                add_action('admin_notices', array($this, 'houzezFunctionalityPluginRequiredNotice'));    

            elseif ( ! RealtynaHouzez::isHouzezFunctionalityPluginActive()  )

                add_action('admin_notices', array($this, 'houzezFunctionalityPluginRequiredAsActivePlugin'));            

        }

    }

    /**
     * Dispaly Notice : Realtyna MLS Sync requires Houzez Theme Functionality Plugin as active Plugin
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    public function houzezFunctionalityPluginRequiredAsActivePlugin(){

        $this->notice( __('Realtyna MLS Sync requires Houzez Theme Functionality Plugin as active Plugin' , REALTYNA_MLS_SYNC_SLUG ) , "error" , true );

    }

    /**
     * Dispaly Notice : Realtyna MLS Sync requires Houzez Theme Functionality Plugin
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    public function houzezFunctionalityPluginRequiredNotice(){

        $this->notice( __('Realtyna MLS Sync requires Houzez Theme Functionality Plugin ' , REALTYNA_MLS_SYNC_SLUG ) , "error" );
        
    }

    /**
     * Dispaly Notice : Realtyna MLS Sync requires Houzez Theme as active theme
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    public function houzezThemeRequiredAsActiveTheme(){

        $this->notice( __('Realtyna MLS Sync requires Houzez Theme as active theme' , REALTYNA_MLS_SYNC_SLUG ) , "error" , true );

    }

    /**
     * Dispaly Notice : Realtyna MLS Sync requires Houzez Theme
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    public function houzezThemeRequiredNotice(){

        $this->notice( __('Realtyna MLS Sync requires Houzez Theme' , REALTYNA_MLS_SYNC_SLUG ) , "error" );
        
    }

    /**
     * Display External Media in Wordpress
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string Name of Media File
     * @param int ID of Media file also known as attachment ID
     * 
     * @return string External File URL
     */
    public function handleExternalMedia( $file , $attachmentId ){
        
        if (  empty( $file ) ) {

            $post = get_post( $attachmentId );

            return $post->guid;
        
        }

        return $file;

    }

    /**
     * Display External Media as Feature Image Thumbnail in Wordpress
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string HTML Code of current feature image
     * @param int Post ID
     * @param int Thumbnail Image ID
     * @param string Name of defined Images Size
     * @param array array of embeded Attributes
     * 
     * @return string IMG tag containing External File Link
     */
    public function handleExternalThumbnail( $html, $postId, $postThumbnailId, $size, $attr ){

        if ( ! get_post_meta( $postThumbnailId, self::EXTERNAL_IMAGES_MARK , true ) ){
            
            return $html;
            
        }

        $post = get_post( $postId );
        
        $alt = isset( $post->post_title ) ? $post->post_title : '';
        $class = isset( $attr['class'] ) ? $attr['class'] : '';

        $thumb = get_post( $postThumbnailId );

        if ( substr( $thumb->guid , strlen( site_url() ) ) == site_url() ){
            
            $src = wp_get_attachment_image_src( $postThumbnailId, $size );

            $url = isset( $src[0] ) ? $src[0] : '' ;

        }else{
            
            $url = $thumb->guid;

        }

        $html = '<img src="' . $url . '" alt="' . $alt . '" class="' . $class . '" />';

        return $html;
    
    }

    /**
     * Display External Media as Feature Image Thumbnail in Wordpress Edit Page
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string HTML Code of current feature image
     * @param int Media File ID or Attachment ID
     * @param string Name of defined Images Size
     * @param string icon
     * @param array array of embeded Attributes
     * 
     * @return string IMG tag containing External File Link
     */
    public function fixExternalThumbnailsInEditPage( $html, $attachmentId, $size, $icon , $attr ){

        if ( ! get_post_meta( $attachmentId, self::EXTERNAL_IMAGES_MARK , true ) ){
            
            return $html;

        }

        $thumb = get_post( $attachmentId );

        if ( substr( $thumb->guid , strlen( site_url() ) ) == site_url() ){
            
            return $html;

        }
            
        $modified_html = '<img src="' . $thumb->guid . '" alt="" loading="lazy" />';

        return $modified_html;
    
    }

    /**
     * Force Max Execution Time to minimum needed seconds
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function setMaxExecutionTime(){

        $currentTime = ini_get("max_execution_time");

        if ( ( $currentTime > 0 ) && ( $currentTime < self::MINIMUM_EXECUTION_TIME_FOR_DATA_IMPORT ) ){
            ini_set( 'max_execution_time' , self::MINIMUM_EXECUTION_TIME_FOR_DATA_IMPORT );
        }

    }

    /**
     * Generate Payment Link using billing system
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @see https://payment.realtyna.com
     * 
     * @return string|bool Return Payment Link or False
     */
    private function generatePaymentLink(){
        
        $credentials = $this->getCredentials();

        if ( is_array( $credentials ) && !empty( $credentials ) && 
            ( strpos( site_url() , "realtyna.info" ) !== false || strpos( site_url() , "localhost" ) !== false  ) ){

            $webService= "http://payment.realtyna.com/test/payment/?";

            $params = array(
                "client_name" => $credentials['name'],
                "client_email" => $credentials['email'],
                "success_url" => $this->getPaymentSuccessURL() ,
                "cancel_url" => $this->getPaymentCancelURL()
            );

            $request = wp_remote_post( $webService, array(
                'timeout' => 60,
                'body' => $params
            ));

            if ( !is_wp_error( $request ) ){

                $statusCode = wp_remote_retrieve_response_code( $request );

                if ($statusCode == 200) {

                    $response = json_decode( $request['body'] , true );

                    if ( isset( $response["url"] ) && !empty( $response["url"] ) ){
                        return $response["url"];
                    }

                }
    
            }
    
        }

        return false;

    }

    /**
     * Remove Imported Properties
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param bool Remove Only Demo Properties
     * 
     * @return bool
     */
    private function removeProperties( $demoOnly = false ){

        if ( class_exists('RealtynaHouzezProperty') ){

            $houzezProperty = new RealtynaHouzezProperty();

            return $houzezProperty->bulkRemoveProperties( $demoOnly );

        }

        return false;

    }

    /**
     * Update Agent display option for Imported Properties in Houzez Theme
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param int fave_agent_display_option code defined by Houzez
     * 
     * @return bool
     */
    private function updatePropertiesAgentDisplayOption( $agentOption ){

        return $this->updatePropertiesMeta( "fave_agent_display_option" , $agentOption );
    }

    /**
     * Update Agent for Imported Properties in Houzez Theme
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param int ID for Selected Agent
     * 
     * @return bool
     */
    private function update_properties_agents( $agent ){

        return $this->updatePropertiesMeta( "fave_agents" , $agent );

    }

    /**
     * Update Post Metas for Imported Properties in Houzez Theme
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string Post Meta Key
     * @param string Post Meta Value
     * 
     * @return bool
     */
    private function updatePropertiesMeta( $key , $value ){

        if ( class_exists('RealtynaHouzezProperty') ){

            $houzezProperty = new RealtynaHouzezProperty( true );

            return $houzezProperty->bulkUpdatePostMeta( $key , $value );

        }
        
        return false;

    }

    /**
     * Move User between Steps
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param int Step Number
     * 
     * @return void
     */
    private function gotoStep( $step ){

        $step = ( is_numeric( $step ) ? $step : 1 );
        
        $this->redirect( "admin.php?page=" . REALTYNA_MLS_SYNC_SLUG . "&step=" . $step );

    }

    /**
     * Get External Images Mark
     * 
     * @static
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return string
     */
    static public function getExternalImagesMark()
    {
        return self::EXTERNAL_IMAGES_MARK;
    }

    /**
     * Initialize REST Routes
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return void
     */
    private function initRestRoutes(){
        
        if ( class_exists('RealtynaIdxRest') ){

            $idxOptions = $this->getIdxOptions();

            $agent = ( is_array( $idxOptions ) && isset( $idxOptions['agent'] ) ) ? $idxOptions['agent'] : null ;
            $agentOption = ( is_array( $idxOptions ) && isset( $idxOptions['agent_option'] ) ) ? $idxOptions['agent_option'] : null ;
            $imageOption = ( is_array( $idxOptions ) && isset( $idxOptions['image_option'] ) ) ? $idxOptions['image_option'] : null ;
            
            $addationalFields = [ 
                "fave_agents" => $agent ,
                "fave_agent_display_option" => $agentOption
            ];

            $wpUserID = !empty( $this->getCredentialItem( 'wp_user_id' ) ) ? $this->getCredentialItem( 'wp_user_id' ) : 0 ;
            $importOptions = [
                "generate_thumbs_images" => false,
                "max_images_import" => ( $imageOption > 0 ) ? 50 : 20 ,
                "max_property_import" => -1 ,
                "use_external_images" => ( $imageOption == 1 ||  $imageOption == 2 || $imageOption == null ),
                "use_external_thumbnail" => ( $imageOption == 2 || $imageOption == null  ),
                "post_author" => $wpUserID
            ];
            
            $idxRest = new RealtynaIdxRest( $this->getCredentialToken() , $this->getMlsProvider() , $addationalFields , $importOptions );

        }

    }

    /**
     * Get plugin basename 
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return string
     */
    public static function getPluginBasename()
    {
        return dirname( __DIR__ ) . DIRECTORY_SEPARATOR . REALTYNA_MLS_SYNC_SLUG . '.php' ;
    }
    
    /**
     * Get plugin Details
     * 
     * @author Chris A <chris.a@realtyna.net>
     * @see https://developer.wordpress.org/reference/functions/get_plugin_data/
     * 
     * @return array array of plugin details
     */
    public static function getPluginDetails(){

        if( ! file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ){

            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        }

        if( function_exists('get_plugin_data') ){

            return get_plugin_data( static::getPluginBasename()  );

        }

        return false;

    }

    /**
     * Check Stripe CallBack
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @return bool
     */
    private function isStripeCallBack()
    {
        
        $mlsData = $this->getMlsData();

        if ( !empty( $mlsData ) && is_array( $mlsData ) ){
                
            return ( $mlsData['id'] > 0 && $mlsData['status'] == 'pending' && !empty( $mlsData['checkout'] ) ) ;

        }

        return false;
    }

    /**
     * Redirect User to specefic URL
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string URL Address
     * 
     * @return void
     */
	private function redirect( $location ){

        if (!headers_sent()) {

            header('Location: ' . $location);
            exit;

        } else {

            echo '
                    <script type="text/javascript">
                        window.location.href="' . $location . '";
                    </script>
                    <noscript>
                        <meta http-equiv="refresh" content="0;url=' . $location . '" />
                    </noscript>
                ';

        }

    }

    /**
     * Display Wordpress Notice
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string Notice Text
     * @param string Notice Type an index of $noticeType Array
     * @param bool Is notice Dismissible?
     * 
     * @return void
     */
    private function notice( $text , $type = 'info' , $dismissible = false){

        if ( !empty( trim( $text ) ) || !empty( trim( $type ) ) ){
            
            $noticeClass = ( in_array( $type , $this->noticeTypes ) ) ? 'notice-' . $type : '';

            $isDismissible = $dismissible ? 'is-dismissible' : '' ;

            echo    '
                    <div class="notice ' . $noticeClass . ' ' . $isDismissible .  ' "  style="margin-top: 10px;margin-bottom: 10px;">
                        
                        <p>' . $text . '</p>
                
                    </div>
                    ';
        }

    }

}
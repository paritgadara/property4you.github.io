<?php
/** Block direct access to the main plugin file.*/ 
defined( 'ABSPATH' ) || die( 'Access Denied!' );

/**
 * Simulate Scheduler for Wordpress Without using WP-Cron
 * 
 * @author Chris A <chris.a@realtyna.net>
 * 
 * @version 1.0
 */
class RealtynaScheduler
{
    
    private $schedulerCycles = [ "hourly" , "daily" , "twicedaily" , "weekly" , "monthly" ];
    
    const DEFAULT_SCHEDULE_CYCLE = "daily";
    const DEFAULT_SCHEDULE_PREFIX = "_realtyna_scheduler_";

    private $schedulerPrefix;

    private $schedulerName;

    private $schedulerFunction;

    

    public function __construct( $schedulerPrefix = null )
    {

        $this->setSchedulerPrefix( $schedulerPrefix ) ;

    }

    private function setSchedulerPrefix( $schedulerPrefix )
    {
        $this->schedulerPrefix = !empty( $schedulerPrefix ) ? $schedulerPrefix . self::DEFAULT_SCHEDULE_PREFIX : self::DEFAULT_SCHEDULE_PREFIX ;
    }

    private function getSchedulerKey(){
        
        return $this->$schedulerPrefix . $this->schedulerName;

    }

    private function schedulerTime(){

        if ( function_exists('get_option') ){

            return get_option( $this->getSchedulerKey() );

        }

        return false;

    }

    private function schedulerExists()
    {

        return !empty( $this->schedulerTime() );

    }

    private function schedulerCreate(){

        if ( function_exists('update_option') ){

            return update_option( $this->getSchedulerKey() , time() );

        }

        return false;

    }

    private function schedulerUpdate(){
        
        return $this->schedulerCreate();

    }

    private function schedulerCycleTime( $schedulerCycle = null ){

        $cycleTime = 0;
        
        $cycle = in_array( $schedulerCycle , $this->schedulerCycles ) ? $schedulerCycle : self::DEFAULT_SCHEDULE_CYCLE ;


        switch ($cycle) {
            case 'hourly':
                $cycleTime = 60 * 60;
                break;
                
            case 'daily':
                $cycleTime = 60 * 60 * 24;
                break;
                
            case 'twicedaily':
                $cycleTime = 60 * 60 * 24 * 2;
                break;
                    
            case 'weekly':
                $cycleTime = 60 * 60 * 24 * 7;
                break;
            
            case 'monthly':
                $cycleTime = 60 * 60 * 24 * 30;
                break;

            default:
                $cycleTime = 60 * 60 * 24;
                break;
        }

        if ( $this->schedulerExists() ){
        
            $cycleTime += $this->schedulerTime() ;

        }

        return $cycleTime;

    }

    private function schedule( $schedulerName , $schedulerFunction , $schedulerCycle = null )
    {

        if ( !empty( $schedulerName ) && !empty( $schedulerFunction ) ){

            $this->schedulerName = $schedulerName;
            $this->schedulerFunction = $schedulerFunction;

            if ( $this->schedulerExists() ){

            }else{

                $this->schedulerCreate();

            }

        }

        return false;
        
    }

}
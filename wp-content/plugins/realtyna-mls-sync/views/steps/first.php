<?php
// Block direct access to the main plugin file.
defined( 'ABSPATH' ) || die( 'Access Denied!' );

$requirementsAreMet = $_REALTYNA['requirements-are-met'] ?? false;
$requirements = $_REALTYNA['requirements-list'] ;

$nextStepDirector = ( $requirementsAreMet ? 'admin.php?page=' . REALTYNA_MLS_SYNC_SLUG . '&step=2' : 'javascript:voided();' );
$nextButtonDisabledClass = ( $requirementsAreMet ? '' : ' disabled' );

?>


<div class="wrap">
    <form> 
        <div class="realtyna_houzez_form">
            <p class="realtyna_mls_sync_step_title">
                <i class="dashicons dashicons-plugins-checked"></i> <?php _e("Check Requirements" , REALTYNA_MLS_SYNC_SLUG);?>
            </p>

            <?php
                foreach ( $requirements as $requirement ):
            ?>
                <p style="text-align:left;">
                    <i class="dashicons <?php echo ( $requirement['result'] ) ? ' dashicons-yes realtyna_success_text ' : ' dashicons-no realtyna_error_text'  ?>"></i>
                    <b><?php echo $requirement['label']?></b>&nbsp;<?php _e("current value" , REALTYNA_MLS_SYNC_SLUG );?> : <i><?php echo $requirement['current_value']?></i>
                </p>
                <p style="text-align:left; padding-left:30px; color:#666;">
                    <i class="dashicons dashicons-info"></i>
                    <span><?php echo $requirement['hint']?>  <?php echo ( ( !empty( $requirement['manual'] ) && !$requirement['result'] ) ? '[<a href="'. $requirement['manual'] .'" target="_blank">' . __( 'manual' , REALTYNA_MLS_SYNC_SLUG ) . '</a> ]' : '' ); ?></span>
                </p>

                <hr>

            <?php
                endforeach;
            
            ?>
            
            <?php
            if ( $requirementsAreMet ) {
            ?>
                <p class="realtyna_success_bg" style="padding-top:10px; padding-bottom:10px;"> 
                    <i class="realtyna_success_text dashicons dashicons-yes"></i> <?php _e("Requirements are met!" , REALTYNA_MLS_SYNC_SLUG);?>
                </p>
            <?php
            }else{
            ?>
                <p class="realtyna_error_bg" style="padding-top:10px; padding-bottom:10px;"> 
                    <i class="realtyna_error_text dashicons dashicons-no"></i> <?php _e("Requirements not met!" , REALTYNA_MLS_SYNC_SLUG );?>
                </p>
            <?php
            }
            
            ?>
            
            <p class="" style="padding-top:10px; padding-bottom:10px;"> 
                <i class=" dashicons dashicons-info"></i> <?php _e("You can also check your hosting benchmark points" , REALTYNA_MLS_SYNC_SLUG);?>, <a href="admin.php?page=realtyna-hosting-benchmark" target="_blank"> <?php _e("Click here to run a test" , REALTYNA_MLS_SYNC_SLUG );?> </a>
            </p>

            <p>
                <a class="button button-primary <?php echo $nextButtonDisabledClass;?>" href="<?php echo $nextStepDirector;?>" ><?php _e("Next Step", REALTYNA_MLS_SYNC_SLUG);?></a>
            </p>
        </div>
    </form>
</div>

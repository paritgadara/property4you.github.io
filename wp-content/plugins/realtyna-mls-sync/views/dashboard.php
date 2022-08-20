<?php
// Block direct access to the main plugin file.
defined( 'ABSPATH' ) || die( 'Access Denied!' );

$nextButtonDisabledClass = 'disabled';
$mlsData = $_REALTYNA['mlsData'];
$idxData = $_REALTYNA['idxData'];
$mlsID = ( is_array( $mlsData ) && isset( $mlsData['id'] )  ) ? $mlsData['id']  : -1 ;

?>

<div class="wrap">

	<form action="admin.php" id="fourth_step_form">
	<input type="hidden" name="realtyna_houzez_nonce" id="realtyna_houzez_nonce" value="<?php echo wp_create_nonce( 'realtyna_houzez_secret_nonce' )?>"/>

	<div class="realtyna_houzez_form">
			<p class="realtyna_mls_sync_step_title">
				<i class="dashicons dashicons-dashboard"></i> <?php echo __("Dashboard" , REALTYNA_MLS_SYNC_SLUG );?>
			</p>

			<div id="request_for_mls_box" class="realtyna_box_shadow" style="background-color: #ceebf5;padding-top:10px;padding-bottom:10px;border-radius:15px;">

				<p>
					<b><?php echo __("Requested Provider", REALTYNA_MLS_SYNC_SLUG );?></b> :
				</p>

				<p>
					<input type="text" name="realtyna_request_provider" id="realtyna_request_provider" value="<?php echo $mlsData['name'];?>" disabled />
				</p>

				<p>
					<b><?php echo __("Current Status", REALTYNA_MLS_SYNC_SLUG );?></b> :
				</p>

				<p>
					<input type="text" name="realtyna_request_provider" id="realtyna_request_provider" value="<?php if ( isset( $mlsData['status'] ) ) {
    echo ucfirst($mlsData['status']) ; } else { echo 'Pending'; }?>" disabled />
				</p>

			</div>

            <hr>

            <?php

            if ( $mlsData['id'] > 0 && $mlsData['status'] == 'Active' ) :
            ?>
            <p id="progress_details" class="realtyna_success_bg realtyna_success_text" style="text-align:center; margin:10px;padding:10px; font-weight:bold;">
                <?php
                    echo '<span class="dashicons dashicons-update"></span> ' . __( 'MLS Sync is in progress... ' , REALTYNA_MLS_SYNC_SLUG );
                ?>
            </p>
            <?php
            endif;

            if ( $mlsData['id'] == 0 || $mlsData['status'] == 'paid' ) :
            ?>
			<p id="payment_details" class="realtyna_success_bg realtyna_success_text" style="text-align:center; margin:10px;padding:10px; font-weight:bold;">
                <?php
                    if ( $mlsData['id'] == 0 ){

                        _e( 'Your Request for MLS has been sent successfully!<br>Our team will contact you soon.<br>If you need more information you can contact us: sync@realtyna.net' , REALTYNA_MLS_SYNC_SLUG );

                    }else{

                        printf ( __( "Payment has been processed successfully.<br><br>Please send Broker name, Email, Brokerage Name, (Agent's info if the client is not a broker), Website URL, Staging URL along with this number: (CID : %s ) to sync@realtyna.net <br><br>Our team will contact you for the paperwork of your MLS provider. Please stay tuned.<br>If you need more information you can contact us: sync@realtyna.net" , REALTYNA_MLS_SYNC_SLUG) , $idxData['user_id']  );

                    }
                ?>
            </p>
            <?php
            endif;

            if ( $mlsData['id'] > 0 && $mlsData['status'] == 'pending' ) :            
            ?>
			<p id="payment_details" class="realtyna_error_bg realtyna_error_text" style="text-align:center; margin:10px;padding:10px; font-weight:bold;">
                <?php
                    if ( $_REALTYNA['payment'] == 'cancel' ){

                        echo __( "The payment has NOT been processed.<br>You can proceed with payemnt again or contact us: sync@realtyna.net" , REALTYNA_MLS_SYNC_SLUG);

                    }else{

                        echo __( "You have an unpaid invoice, please proceed with the invoice to finalize your order " , REALTYNA_MLS_SYNC_SLUG);

                    }
                ?>
            </p>

            <p> 
                <a class="button button-secondary" href="<?php echo $mlsData['checkout']; ?>" ><?php _e("Proceed With Payment" , REALTYNA_MLS_SYNC_SLUG);?></a>                
            </p>
            <?php
            endif;
            ?>

            <?php
            if ( $mlsData['status'] == 'pending' ) :
            ?>
			<p>
				<a class="button button-secondary" href="admin.php?page=<?php echo REALTYNA_MLS_SYNC_SLUG ; ?>&reset_mls=1" ><?php _e("Select Another MLS Provider" , REALTYNA_MLS_SYNC_SLUG);?></a>
			</p>
            <?php
            endif;
            ?>

		</div>

	</form>
	
</div>

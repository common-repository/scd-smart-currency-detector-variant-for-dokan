<?php
include 'scd_pro_currencies.php';


add_filter('scd_multivendors_activate', 'scd_multivendors_activate_func', 10, 1);

function scd_multivendors_activate_func($scd_multi_activate) {
    return true;
}

function scd_check_license_active() {
    $opt_license_key = get_option('scd_license_key');
    $opt_license_start_date = get_option('scd_license_start_date');
    $opt_license_expiry_date = get_option('scd_license_expiry_date');

    if (empty($opt_license_key) && empty($opt_license_start_date) && !file_exists($GLOBALS['scd_license_file'])) {
        return FALSE;
    } else {
        if (!empty($opt_license_start_date)) {
            $startdate = new DateTime(base64_decode(get_option('scd_license_start_date')));
        } else if (file_exists($GLOBALS['scd_license_file'])) {
            $startdate = new DateTime(base64_decode(file_get_contents($GLOBALS['scd_license_file'])));
        } else { //only the license key varable remains
            return FALSE;
        }

        if (empty($opt_license_expiry_date) && is_admin()) {
            scd_set_expiry($opt_license_key, $startdate);
            $opt_license_expiry_date = get_option('scd_license_expiry_date');
        }

        $todaydate = new DateTime(date('Y-m-d'));
        $duration = $startdate->diff($todaydate);

        if (!empty($opt_license_expiry_date)) {
            $expirydate = new DateTime(base64_decode($opt_license_expiry_date));
            if ($todaydate < $expirydate) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            // For backward compatibility with older activations prior to 4.5.2 
            if ($duration->days > $GLOBALS['scd_license_duration']) {
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }
}

add_action( 'wp_ajax_scd_dokan_get_user_currency', 'scd_dokan_get_user_currency' );
    function scd_dokan_get_user_currency() {
           $user_curr= get_user_meta(get_current_user_id(), 'scd-user-currency',true);
            if( $user_curr){
				echo $user_curr;
			 return $user_curr;
            } else {
				$default_curr = get_option( 'woocommerce_currency');
				echo $default_curr;
                return FALSE;    
            }
       }
       
function scd_get_user_currency() {
    $user_curr = get_user_meta(get_current_user_id(), 'scd-user-currency', true);
    if ($user_curr) {
        return $user_curr;
    } else {
        $default_curr = get_option( 'woocommerce_currency');
        return $default_curr;
    }
}

function scd_get_user_currency_option() {
    $curr_opt = get_user_meta(get_current_user_id(), 'user-currency-option');
    if (count($curr_opt) > 0) {
        return $curr_opt[0];
    } else {
        return 'only-default-currency';
    }
}

add_action('wp_ajax_scd_show_user_currency', 'scd_show_user_currency');

function scd_show_user_currency() {
    $options = array(
        'base-currency' => __( 'Base currency only', 'scd_dokan_marketplace' ),
        'only-default-currency' => __( 'Your default currency only', 'scd_dokan_marketplace' ),
        //'base-and-default-currency' => 'Base and default currency',
        //'selected-currencies' => 'Selected currencies'
    );
    ?>
    <div class="scd-container">
        <p id="scd-action-status" style="margin-left:15%;"></p>
         <div class="scd-form-grp">
             <p class="scd-label"> <?php echo __( 'Select your default currency', 'scd_dokan_marketplace' );  ?></p>
             <div class="scd-form-input">
                <select id="scd-currency-list" class="scd-user-curr">
                    <?php
                    $user_curr = scd_get_user_currency();
                    //if($user_curr!==FALSE) $user_curr=$user_curr[0];
                    foreach (scd_get_list_currencies() as $key => $val) {
                        if ($user_curr == $key) {
                            echo '<option selected value="' . $key . '" >' . $key . '(' . get_woocommerce_currency_symbol($key) . ')</option>';
                        } else {
                            echo '<option value="' . $key . '" >' . $key . '(' . get_woocommerce_currency_symbol($key) . ')</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="scd-form-btn">
                <?php
                //echo '<a  style="color:black; " class="scd-btn-control button" href="#" id="scd-save-curr">Save change<a>';
                echo '<br>';
                
                ?>
            </div>
        </div>
        <div class="scd-form-grp">
            <p class="scd-label"> <?php echo __( 'Set products price in', 'scd_dokan_marketplace' ); ?> </p>
            <div class="scd-form-input">
                <select id="scd-currency-option" class="scd-user-curr">
                    <?php
                    $currency_opt = scd_get_user_currency_option();
                    foreach ($options as $key => $val) {
                        if ($currency_opt == $key) {
                            echo '<option selected value="' . $key . '" >' . $val . '</option>';
                        } else {
                            echo '<option value="' . $key . '" >' . $val . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="scd-form-btn">
                <?php
                echo '<br>';
                
                ?>
            </div>           
            <div class="scd-form-btn">
                <?php
				echo '<a  style="color:black; " class="scd-btn-control button" href="#" id="scd-save-currency-option">' . __( ' Save change', 'scd_dokan_marketplace' ) . '<a>';
                echo '</p>';
                ?>
            </div>
        </div>
    </div>
    <?php
    die();
}

add_action('wp_ajax_scd_update_user_currency', 'scd_update_user_currency');

function scd_update_user_currency() {
    if (isset($_POST['user_currency'])) {

        update_user_meta(get_current_user_id(), 'scd-user-currency', $_POST['user_currency']);
        echo __('Information saved. Your new custom currency is ', 'scd_dokan_marketplace' ) . get_user_meta(get_current_user_id(), 'scd-user-currency')[0];
    } else {
        echo __('Currency not saved please try again', 'scd_dokan_marketplace' );
    }
    die();
}

add_action('wp_ajax_scd_update_user_currency_option', 'scd_update_user_currency_option');

function scd_update_user_currency_option() {
    if (isset($_POST['user_currency_option'])) {

        update_user_meta(get_current_user_id(), 'user-currency-option', $_POST['user_currency_option']);
        echo __('Information saved', 'scd_dokan_marketplace' );
    } else {
        echo __('Option not saved please try again', 'scd_dokan_marketplace' );
    }
    die();
}

//when vendor is connected set the target currency to his default currency
function scd_multivendor_currency($scd_target_currency) {

    $user_currency = scd_get_user_currency();
    if ($user_currency !== false) {
        $scd_target_currency = $user_currency;
    }
    return $scd_target_currency;
}

//add_filter('scd_target_currency','scd_multivendor_currency',10,1);
//export import products with woocommerce

add_filter('woocommerce_product_export_column_names', 'scd_add_export_column');
add_filter('woocommerce_product_export_product_default_columns', 'scd_add_export_column');

function scd_add_export_column($columns) {

    // column slug => column name
    $columns['scd_other_options'] = 'Meta: scd_other_options';

    return $columns;
}

function scd_add_export_data($value, $product) {
    $value = get_post_meta($product->get_id(), 'scd_other_options', true);

    return serialize($value);
}

// Filter you want to hook into will be: 'woocommerce_product_export_product_column_{$column_slug}'.
add_filter('woocommerce_product_export_product_column_scd_other_options', 'scd_add_export_data', 10, 2);

// Hook into the filter
add_filter("woocommerce_product_importer_parsed_data", "scd_csv_import_serialized", 10, 2);

function scd_csv_import_serialized($data, $importer) {
    if (isset($data["meta_data"]) && is_array($data["meta_data"])) {
        foreach (array_keys($data["meta_data"]) as $k) {
            $data["meta_data"][$k]["value"] = maybe_unserialize($data["meta_data"][$k]["value"]);
        }
    }
    return $data;
}

//filter in the free version
add_filter('is_scd_multivendor', 'is_scd_multivendor', 10, 1);

function is_scd_multivendor($multi) {
    return true;
}

add_filter('scd_disable_sidebar_currencies', 'fct_scd_disable_sidebar_currencies', 10, 1);

function fct_scd_disable_sidebar_currencies() {
    return false;
}

// change withdraw symbol currency
add_filter( 'dokan_withdraw_content','ffscd_dokan_add_woocommerce_currency_symbol_filter',19);
function ffscd_dokan_add_woocommerce_currency_symbol_filter(){
    add_filter( 'woocommerce_currency_symbol','scd_dokan_woocommerce_currency_symbol',10,2);
}

function scd_dokan_woocommerce_currency_symbol($currency_symbol, $currency ){
    remove_filter( 'woocommerce_currency_symbol','scd_dokan_woocommerce_currency_symbol',10);
    return get_woocommerce_currency_symbol(scd_get_user_currency());
}

////////// Start fixed shipping flat rate cost in Dokan /////////
add_action( 'wp_ajax_dokan-update-shipping-method-settings', 'scd_update_shipping_methods_settings',1);
function scd_update_shipping_methods_settings(){
    global $_POST;
    $vendor_curr= get_user_meta(get_current_user_id(), 'scd-user-currency',true);
    $_POST['data']['settings']['cost'] =  scd_function_convert_subtotal($_POST['data']['settings']['cost'], $vendor_curr, get_option( 'woocommerce_currency'));
                       
}
////////// End fixed shipping flat rate cost in Dokan /////////
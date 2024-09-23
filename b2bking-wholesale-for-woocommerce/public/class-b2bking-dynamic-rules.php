<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/*
* Functions in this file, in order:
* b2bking_dynamic_rule_hidden_price - Dynamic rule: Hidden Price
b2bking_dynamic_rule_hidden_price_disable_purchasable - Dynamic rule: Hidden Price - Disables purchasable capability 
* b2bking_dynamic_rule_cart_discount - Dynamic rules: Discount Amount and Discount Percentage
b2bking_dynamic_rule_discount_regular_price
b2bking_dynamic_rule_discount_sale_price
b2bking_dynamic_rule_discount_display_dynamic_price
b2bking_dynamic_rule_discount_display_dynamic_price_in_cart
b2bking_dynamic_rule_discount_display_dynamic_price_in_cart_item
b2bking_dynamic_rule_discount_display_dynamic_sale_badge
*/
        
class B2bkingcore_Dynamic_Rules {

        // Dynamic rule Hidden price
        public static function b2bking_dynamic_rule_hidden_price($price, $product){
            // Get current product
            $current_product_id = $product->get_id();

            // 1) Get all rules and check if any rules apply to the product
            $rules_that_apply_to_product = get_transient('b2bking_hidden_price_rules_apply_'.$current_product_id);
            if (!$rules_that_apply_to_product){

                $rules_that_apply = array();
                $hidden_price_rules_option = get_option('b2bking_have_hidden_price_rules_list_ids', '');
                if (!empty($hidden_price_rules_option)){
                    $hidden_price_rules_v2_ids = explode(',',$hidden_price_rules_option);
                }

                foreach ($hidden_price_rules_v2_ids as $rule_id){
                    $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                    if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                        array_push($rules_that_apply, $rule_id);
                    } else if ($applies === 'multiple_options'){
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            array_push($rules_that_apply, $rule_id);
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                    break;
                                }
                            }
                        }
                        
                    } else if (explode('_', $applies)[0] === 'category'){
                        // check category
                        $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                        $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                        if (in_array($applies, $current_product_belongsto_array)){
                            array_push($rules_that_apply, $rule_id);
                        }
                    } else if ($applies === 'excluding_multiple_options'){
                        // check that current product is not in list
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);

                        $product_is_excluded = 'no';
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            $product_is_excluded = 'yes';
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                    break;
                                }
                            }
                        }
                        if ($product_is_excluded === 'no'){
                            // product is not excluded, therefore rule applies
                            array_push($rules_that_apply, $rule_id);
                        }
                    }
                }

                set_transient('b2bking_hidden_price_rules_apply_'.$current_product_id,$rules_that_apply);
                $rules_that_apply_to_product = $rules_that_apply;
            }
            // 2) If no rules apply for product, set transient for current user to empty array
            if (empty($rules_that_apply_to_product)){
                set_transient('b2bking_hidden_price_'.$current_product_id.'_'.get_current_user_id(), array());
            } else {
                // if transient does not already exist
                if (!get_transient('b2bking_hidden_price_'.$current_product_id.'_'.get_current_user_id())){

                    // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                    $rules_that_apply_to_user = array();
                    $user_id = get_current_user_id();
                    $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                    if ($account_type === 'subaccount'){
                        // for all intents and purposes set current user as the subaccount parent
                        $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                        $user_id = $parent_user_id;
                    }

                    $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                    $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                    foreach ($rules_that_apply_to_product as $rule_id){
                        $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                        // first check guest users
                        if ($user_id === 0){
                            if ($who === 'user_0'){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else if ($who === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                $multiple_options_array = explode(',',$multiple_options);
                                if (in_array('user_0',$multiple_options_array)){
                                    array_push($rules_that_apply_to_user, $rule_id);
                                }
                            }
                        } else {
                            // user is not guest
                            if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else {
                                if ($user_is_b2b !== 'yes'){
                                    // user is b2c
                                    if ($who === 'everyone_registered_b2c'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {    
                                    // user is b2b
                                    if ($who === 'everyone_registered_b2b'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'group_'.$user_group){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // either an empty array or an array with rules
                    set_transient('b2bking_hidden_price_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                }
            }

            // 5. If there are no rules that apply to the product, check if this product is a variation and if 
            // there are any parent rules
            if (!get_transient('b2bking_hidden_price_'.$current_product_id.'_'.get_current_user_id())){
                $post_parent_id = wp_get_post_parent_id($current_product_id);
                if ($post_parent_id !== 0){
                    // check if there are parent rules
                    $current_product_id = $post_parent_id;

                    // based on code above
                    // 1) Get all rules and check if any rules apply to the product
                    $rules_that_apply_to_product = get_transient('b2bking_hidden_price_parent_rules_apply_'.$current_product_id);
                    if (!$rules_that_apply_to_product){

                        $rules_that_apply = array();
                        $hidden_price_rules_option = get_option('b2bking_have_hidden_price_rules_list_ids', '');
                        if (!empty($hidden_price_rules_option)){
                            $hidden_price_rules_v2_ids = explode(',',$hidden_price_rules_option);
                        }

                        foreach ($hidden_price_rules_v2_ids as $rule_id){
                            $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                            if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                                array_push($rules_that_apply, $rule_id);
                            } else if ($applies === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            array_push($rules_that_apply, $rule_id);
                                            break;
                                        }
                                    }
                                }
                                
                            } else if (explode('_', $applies)[0] === 'category'){
                                // check category
                                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                                if (in_array($applies, $current_product_belongsto_array)){
                                    array_push($rules_that_apply, $rule_id);
                                }
                            } else if ($applies === 'excluding_multiple_options'){
                                // check that current product is not in list
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);

                                $product_is_excluded = 'no';
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            $product_is_excluded = 'yes';
                                            break;
                                        }
                                    }
                                }
                                if ($product_is_excluded === 'no'){
                                    // product is not excluded, therefore rule applies
                                    array_push($rules_that_apply, $rule_id);
                                }
                            }
                        }

                        set_transient('b2bking_hidden_price_parent_rules_apply_'.$current_product_id,$rules_that_apply);
                        $rules_that_apply_to_product = $rules_that_apply;
                    }
                    // 2) If no rules apply for product, set transient for current user to empty array
                    if (empty($rules_that_apply_to_product)){
                        set_transient('b2bking_hidden_price_parent_'.$current_product_id.'_'.get_current_user_id(), array());
                    } else {
                        // if transient does not already exist
                        if (!get_transient('b2bking_hidden_price_parent_'.$current_product_id.'_'.get_current_user_id())){

                            // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                            $rules_that_apply_to_user = array();
                            $user_id = get_current_user_id();
                            $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                            if ($account_type === 'subaccount'){
                                // for all intents and purposes set current user as the subaccount parent
                                $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                                $user_id = $parent_user_id;
                            }

                            $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                            $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                            foreach ($rules_that_apply_to_product as $rule_id){
                                $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                                // first check guest users
                                if ($user_id === 0){
                                    if ($who === 'user_0'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('user_0',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {
                                    // user is not guest
                                    if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else {
                                        if ($user_is_b2b !== 'yes'){
                                            // user is b2c
                                            if ($who === 'everyone_registered_b2c'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        } else {    
                                            // user is b2b
                                            if ($who === 'everyone_registered_b2b'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'group_'.$user_group){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $current_product_id = $product->get_id();
                            // either an empty array or an array with rules
                            set_transient('b2bking_hidden_price_parent_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                        }
                    }

                }   
            }
            
            $hidden_price_rules = get_transient('b2bking_hidden_price_'.$current_product_id.'_'.get_current_user_id());
            $hidden_price_parent_rules = get_transient('b2bking_hidden_price_parent_'.$current_product_id.'_'.get_current_user_id());

            // if there are no hidden price rules, than price is not hidden, and viceversa
            if (empty($hidden_price_rules) && empty($hidden_price_parent_rules)){
                return $price;
            } else {
                return get_option('b2bking_hidden_price_dynamic_rule_text_setting', esc_html__('Price is unavailable','b2bking'));
            }

        }

        // Dynamic rule hidden price disable purchasable ability on product
        public static function b2bking_dynamic_rule_hidden_price_disable_purchasable($purchasable, $product){

            // Get current product
            $current_product_id = $product->get_id();

            // 1) Get all rules and check if any rules apply to the product
            $rules_that_apply_to_product = get_transient('b2bking_hidden_price_rules_apply_'.$current_product_id);
            if (!$rules_that_apply_to_product){

                $rules_that_apply = array();
                $hidden_price_rules_option = get_option('b2bking_have_hidden_price_rules_list_ids', '');
                if (!empty($hidden_price_rules_option)){
                    $hidden_price_rules_v2_ids = explode(',',$hidden_price_rules_option);
                }

                foreach ($hidden_price_rules_v2_ids as $rule_id){
                    $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                    if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                        array_push($rules_that_apply, $rule_id);
                    } else if ($applies === 'multiple_options'){
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            array_push($rules_that_apply, $rule_id);
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                    break;
                                }
                            }
                        }
                        
                    } else if (explode('_', $applies)[0] === 'category'){
                        // check category
                        $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                        $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                        if (in_array($applies, $current_product_belongsto_array)){
                            array_push($rules_that_apply, $rule_id);
                        }
                    } else if ($applies === 'excluding_multiple_options'){
                        // check that current product is not in list
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);

                        $product_is_excluded = 'no';
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            $product_is_excluded = 'yes';
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                    break;
                                }
                            }
                        }
                        if ($product_is_excluded === 'no'){
                            // product is not excluded, therefore rule applies
                            array_push($rules_that_apply, $rule_id);
                        }
                    }
                }

                set_transient('b2bking_hidden_price_rules_apply_'.$current_product_id,$rules_that_apply);
                $rules_that_apply_to_product = $rules_that_apply;
            }
            // 2) If no rules apply for product, set transient for current user to empty array
            if (empty($rules_that_apply_to_product)){
                set_transient('b2bking_hidden_price_'.$current_product_id.'_'.get_current_user_id(), array());
            } else {
                // if transient does not already exist
                if (!get_transient('b2bking_hidden_price_'.$current_product_id.'_'.get_current_user_id())){

                    // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                    $rules_that_apply_to_user = array();
                    $user_id = get_current_user_id();
                    $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                    if ($account_type === 'subaccount'){
                        // for all intents and purposes set current user as the subaccount parent
                        $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                        $user_id = $parent_user_id;
                    }

                    $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                    $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                    foreach ($rules_that_apply_to_product as $rule_id){
                        $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                        // first check guest users
                        if ($user_id === 0){
                            if ($who === 'user_0'){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else if ($who === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                $multiple_options_array = explode(',',$multiple_options);
                                if (in_array('user_0',$multiple_options_array)){
                                    array_push($rules_that_apply_to_user, $rule_id);
                                }
                            }
                        } else {
                            // user is not guest
                            if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else {
                                if ($user_is_b2b !== 'yes'){
                                    // user is b2c
                                    if ($who === 'everyone_registered_b2c'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {    
                                    // user is b2b
                                    if ($who === 'everyone_registered_b2b'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'group_'.$user_group){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // either an empty array or an array with rules
                    set_transient('b2bking_hidden_price_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                }
            }

            // 5. If there are no rules that apply to the product, check if this product is a variation and if 
            // there are any parent rules
            if (!get_transient('b2bking_hidden_price_'.$current_product_id.'_'.get_current_user_id())){
                $post_parent_id = wp_get_post_parent_id($current_product_id);
                if ($post_parent_id !== 0){
                    // check if there are parent rules
                    $current_product_id = $post_parent_id;

                    // based on code above
                    // 1) Get all rules and check if any rules apply to the product
                    $rules_that_apply_to_product = get_transient('b2bking_hidden_price_parent_rules_apply_'.$current_product_id);
                    if (!$rules_that_apply_to_product){

                        $rules_that_apply = array();
                        $hidden_price_rules_option = get_option('b2bking_have_hidden_price_rules_list_ids', '');
                        if (!empty($hidden_price_rules_option)){
                            $hidden_price_rules_v2_ids = explode(',',$hidden_price_rules_option);
                        }

                        foreach ($hidden_price_rules_v2_ids as $rule_id){
                            $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                            if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                                array_push($rules_that_apply, $rule_id);
                            } else if ($applies === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            array_push($rules_that_apply, $rule_id);
                                            break;
                                        }
                                    }
                                }
                                
                            } else if (explode('_', $applies)[0] === 'category'){
                                // check category
                                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                                if (in_array($applies, $current_product_belongsto_array)){
                                    array_push($rules_that_apply, $rule_id);
                                }
                            } else if ($applies === 'excluding_multiple_options'){
                                // check that current product is not in list
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);

                                $product_is_excluded = 'no';
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            $product_is_excluded = 'yes';
                                            break;
                                        }
                                    }
                                }
                                if ($product_is_excluded === 'no'){
                                    // product is not excluded, therefore rule applies
                                    array_push($rules_that_apply, $rule_id);
                                }
                            }
                        }

                        set_transient('b2bking_hidden_price_parent_rules_apply_'.$current_product_id,$rules_that_apply);
                        $rules_that_apply_to_product = $rules_that_apply;
                    }
                    // 2) If no rules apply for product, set transient for current user to empty array
                    if (empty($rules_that_apply_to_product)){
                        set_transient('b2bking_hidden_price_parent_'.$current_product_id.'_'.get_current_user_id(), array());
                    } else {
                        // if transient does not already exist
                        if (!get_transient('b2bking_hidden_price_parent_'.$current_product_id.'_'.get_current_user_id())){

                            // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                            $rules_that_apply_to_user = array();
                            $user_id = get_current_user_id();
                            $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                            if ($account_type === 'subaccount'){
                                // for all intents and purposes set current user as the subaccount parent
                                $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                                $user_id = $parent_user_id;
                            }

                            $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                            $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                            foreach ($rules_that_apply_to_product as $rule_id){
                                $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                                // first check guest users
                                if ($user_id === 0){
                                    if ($who === 'user_0'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('user_0',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {
                                    // user is not guest
                                    if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else {
                                        if ($user_is_b2b !== 'yes'){
                                            // user is b2c
                                            if ($who === 'everyone_registered_b2c'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        } else {    
                                            // user is b2b
                                            if ($who === 'everyone_registered_b2b'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'group_'.$user_group){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $current_product_id = $product->get_id();
                            // either an empty array or an array with rules
                            set_transient('b2bking_hidden_price_parent_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                        }
                    }

                }   
            }
            
            $hidden_price_rules = get_transient('b2bking_hidden_price_'.$current_product_id.'_'.get_current_user_id());
            $hidden_price_parent_rules = get_transient('b2bking_hidden_price_parent_'.$current_product_id.'_'.get_current_user_id());

            // if there are no hidden price rules, than price is not hidden, and viceversa
            if (empty($hidden_price_rules) && empty($hidden_price_parent_rules)){
                return $purchasable;
            } else {
                return false;
            }
        }

        // Dynamic rule cart discount
        public static function b2bking_dynamic_rule_cart_discount( WC_Cart $cart ){

            $user_id = get_current_user_id();
            $account_type = get_user_meta($user_id,'b2bking_account_type', true);
            if ($account_type === 'subaccount'){
                // for all intents and purposes set current user as the subaccount parent
                $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                $user_id = $parent_user_id;
            }

            $currentusergroupidnr = get_the_author_meta( 'b2bking_customergroup', $user_id );

            /*
            * Apply all discounts for "all products excluding"
            */

            $array_who_multiple = array(
                'relation' => 'OR',
                array(
                    'key' => 'b2bking_rule_who_multiple_options',
                    'value' => 'group_'.$currentusergroupidnr,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'b2bking_rule_who_multiple_options',
                    'value' => 'user_'.$user_id,
                    'compare' => 'LIKE'
                ),
            );

            if ($user_id !== 0){
                array_push($array_who_multiple, array(
                                'key' => 'b2bking_rule_who_multiple_options',
                                'value' => 'everyone_registered',
                                'compare' => 'LIKE'
                            ));

                // add rules that apply to all registered b2b/b2c users
                $user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
                if ($user_is_b2b === 'yes'){
                    array_push($array_who_multiple, array(
                                'key' => 'b2bking_rule_who_multiple_options',
                                'value' => 'everyone_registered_b2b',
                                'compare' => 'LIKE'
                            ));
                } else if ($user_is_b2b === 'no'){
                    array_push($array_who_multiple, array(
                                'key' => 'b2bking_rule_who_multiple_options',
                                'value' => 'everyone_registered_b2c',
                                'compare' => 'LIKE'
                            ));
                }

            }

            $array_who = array(
                            'relation' => 'OR',
                            array(
                                'key' => 'b2bking_rule_who',
                                'value' => 'group_'.$currentusergroupidnr
                            ),
                            array(
                                'key' => 'b2bking_rule_who',
                                'value' => 'user_'.$user_id
                            ),
                            array(
                                'relation' => 'AND',
                                array(
                                    'key' => 'b2bking_rule_who',
                                    'value' => 'multiple_options'
                                ),
                                $array_who_multiple
                            ),
                        );
            // if user is registered, also select rules that apply to all registered users
            if ($user_id !== 0){
                array_push($array_who, array(
                                'key' => 'b2bking_rule_who',
                                'value' => 'everyone_registered'
                            ));

                // add rules that apply to all registered b2b/b2c users
                $user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
                if ($user_is_b2b === 'yes'){
                    array_push($array_who, array(
                                'key' => 'b2bking_rule_who',
                                'value' => 'everyone_registered_b2b'
                            ));
                } else if ($user_is_b2b === 'no'){
                    array_push($array_who, array(
                                'key' => 'b2bking_rule_who',
                                'value' => 'everyone_registered_b2c'
                            ));
                }
            }

            $discount_rules_excluding = get_posts([
                'post_type' => 'b2bking_rule',
                'post_status' => 'publish',
                'fields' => 'ids',
                'numberposts' => -1,
                'meta_query'=> array(
                    'relation' => 'AND',
                    $array_who,
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => 'b2bking_rule_what',
                            'value' => 'discount_percentage'
                        ),
                        array(
                            'key' => 'b2bking_rule_what',
                            'value' => 'discount_amount'
                        ),
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => 'b2bking_rule_discount_show_everywhere',
                            'value' => '0'
                        ),
                        array(
                            'key' => 'b2bking_rule_discount_show_everywhere',
                            'value' => ''
                        ),
                        array(
                            'key' => 'b2bking_rule_discount_show_everywhere',
                            'compare' => 'NOT EXISTS'
                        ),
                    ),
                    array(
                        'key' => 'b2bking_rule_applies',
                        'value' => 'excluding_multiple_options'
                    ),
                )
            ]);

            // foreach item in cart, check if any rule applies
            if(is_object($cart)) {
                foreach($cart->get_cart() as $cart_item){

                    if (isset($cart_item['variation_id']) && intval($cart_item['variation_id']) !== 0){
                        $current_product_id = $cart_item['variation_id'];
                    } else {
                        $current_product_id = $cart_item['product_id'];
                    }

                    foreach ($discount_rules_excluding as $rule_id){
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);

                        $product_is_excluded = 'no';
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            $product_is_excluded = 'yes';
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                    break;
                                }
                            }
                        }

                        if ($product_is_excluded === 'no'){
                            // check rule conditions
                            $passconditions = 'yes';
                            $conditions = get_post_meta($rule_id, 'b2bking_rule_conditions', true);
                            $conditions = explode('|',$conditions);
                            foreach ($conditions as $condition){
                                $condition_details = explode(';',$condition);
                                switch ($condition_details[0]){
                                    case 'cart_total_quantity':
                                        switch ($condition_details[1]){
                                            case 'greater':
                                                if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                            case 'equal':
                                                if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                            case 'smaller':
                                                if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                        }
                                        break;
                                    case 'cart_total_value':
                                        switch ($condition_details[1]){
                                            case 'greater':
                                                if (! (floatval($cart->subtotal) > floatval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                            case 'equal':
                                                if (! (floatval($cart->subtotal) === floatval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                            case 'smaller':
                                                if (! (floatval($cart->subtotal) < floatval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                        }
                                        break;
                                }
                            }

                            // Passed conditions
                            if ($passconditions === 'yes'){

                                // calculate discount amount. If bigger, replace total
                                $type = get_post_meta($rule_id, 'b2bking_rule_what', true);
                                $howmuch = get_post_meta($rule_id, 'b2bking_rule_howmuch', true);
                                $discount_name = get_post_meta($rule_id, 'b2bking_rule_discountname', true);

                                if ($type === 'discount_amount'){
                                    $howmuch = floatval ($howmuch) * $cart_item['quantity'];
                                } else if ($type === 'discount_percentage') {
                                    $howmuch = (floatval($howmuch)/100) * $cart_item['line_total'];
                                }

                                if ($howmuch > 0){
                                    // if user gave discount a name, use that
                                    if($discount_name !== NULL && $discount_name !== ''){
                                        $cart->add_fee( get_the_title($current_product_id).' '.$discount_name, - $howmuch);
                                    } else {
                                        $cart->add_fee( get_the_title($current_product_id).esc_html__(' Discount','b2bking'), - $howmuch);
                                    }
                                }
                            }

                        }
                    }

                }
            }


            /*
            * Apply all cart total discounts
            */

            $array_who_multiple = array(
                'relation' => 'OR',
                array(
                    'key' => 'b2bking_rule_who_multiple_options',
                    'value' => 'group_'.$currentusergroupidnr,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'b2bking_rule_who_multiple_options',
                    'value' => 'user_'.$user_id,
                    'compare' => 'LIKE'
                ),
            );

            if ($user_id !== 0){
                array_push($array_who_multiple, array(
                                'key' => 'b2bking_rule_who_multiple_options',
                                'value' => 'everyone_registered',
                                'compare' => 'LIKE'
                            ));

                // add rules that apply to all registered b2b/b2c users
                $user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
                if ($user_is_b2b === 'yes'){
                    array_push($array_who_multiple, array(
                                'key' => 'b2bking_rule_who_multiple_options',
                                'value' => 'everyone_registered_b2b',
                                'compare' => 'LIKE'
                            ));
                } else if ($user_is_b2b === 'no'){
                    array_push($array_who_multiple, array(
                                'key' => 'b2bking_rule_who_multiple_options',
                                'value' => 'everyone_registered_b2c',
                                'compare' => 'LIKE'
                            ));
                }

            }

            $array_who = array(
                            'relation' => 'OR',
                            array(
                                'key' => 'b2bking_rule_who',
                                'value' => 'group_'.$currentusergroupidnr
                            ),
                            array(
                                'key' => 'b2bking_rule_who',
                                'value' => 'user_'.$user_id
                            ),
                            array(
                                'relation' => 'AND',
                                array(
                                    'key' => 'b2bking_rule_who',
                                    'value' => 'multiple_options'
                                ),
                                $array_who_multiple
                            ),
                        );
            // if user is registered, also select rules that apply to all registered users
            if ($user_id !== 0){
                array_push($array_who, array(
                                'key' => 'b2bking_rule_who',
                                'value' => 'everyone_registered'
                            ));

                // add rules that apply to all registered b2b/b2c users
                $user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
                if ($user_is_b2b === 'yes'){
                    array_push($array_who, array(
                                'key' => 'b2bking_rule_who',
                                'value' => 'everyone_registered_b2b'
                            ));
                } else if ($user_is_b2b === 'no'){
                    array_push($array_who, array(
                                'key' => 'b2bking_rule_who',
                                'value' => 'everyone_registered_b2c'
                            ));
                }
            }

            $discount_rule_ids = get_option('b2bking_have_discount_rules_list_ids', '');
            if (!empty($discount_rule_ids)){
                $discount_rule_ids = explode(',',$discount_rule_ids);
            } else {
                $discount_rule_ids = array();
            }

            $total_cart_rules = get_transient('b2bking_total_cart_rules_'.get_current_user_id());

            if (!$total_cart_rules){
                // Get all dynamic rule total cart discounts that apply to the user or the user's group
                $total_cart_rules = get_posts([
                    'post_type' => 'b2bking_rule',
                    'post_status' => 'publish',
                    'fields' => 'ids',
                    'post__in' => $discount_rule_ids,
                    'numberposts' => -1,
                    'meta_query'=> array(
                        'relation' => 'AND',
                        $array_who,
                        array(
                            'relation' => 'OR',
                            array(
                                'key' => 'b2bking_rule_what',
                                'value' => 'discount_percentage'
                            ),
                            array(
                                'key' => 'b2bking_rule_what',
                                'value' => 'discount_amount'
                            ),
                        ),
                        array(
                                'key' => 'b2bking_rule_applies',
                                'value' => 'cart_total'
                            ),
                        array(
                            'relation' => 'OR',
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'value' => '0'
                            ),
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'value' => ''
                            ),
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'compare' => 'NOT EXISTS'
                            ),
                        ),
                        

                    )
                ]);

                set_transient('b2bking_total_cart_rules_'.get_current_user_id(), $total_cart_rules);

            }

            // If multiply discounts apply, give only the bigger discount (rather than cumulated)
            $current_total_cart_discount = 0;
            $current_total_cart_discount_name = '';

            foreach($total_cart_rules as $total_cart_rule){
                // Check discount conditions
                $passconditions = 'yes';
                $conditions = get_post_meta($total_cart_rule, 'b2bking_rule_conditions', true);
                $conditions = explode('|',$conditions);
                foreach ($conditions as $condition){
                    $condition_details = explode(';',$condition);
                    switch ($condition_details[0]){
                        case 'cart_total_quantity':
                            switch ($condition_details[1]){
                                case 'greater':
                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'equal':
                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'smaller':
                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                            }
                            break;
                        case 'cart_total_value':
                            switch ($condition_details[1]){
                                case 'greater':
                                    if (! (floatval($cart->subtotal) > floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'equal':
                                    if (! (floatval($cart->subtotal) === floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'smaller':
                                    if (! (floatval($cart->subtotal) < floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                            }
                            break;
                    }
                }

                // Passed conditions
                if ($passconditions === 'yes'){
                    
                    // calculate discount amount. If bigger, replace total
                    $type = get_post_meta($total_cart_rule, 'b2bking_rule_what', true);
                    $howmuch = get_post_meta($total_cart_rule, 'b2bking_rule_howmuch', true);
                    $discount_name = get_post_meta($total_cart_rule, 'b2bking_rule_discountname', true);
                    if ($type === 'discount_amount'){
                        $howmuch = floatval ($howmuch);
                    } else if ($type === 'discount_percentage') {
                        $howmuch = (floatval($howmuch)/100) * WC()->cart->get_subtotal();
                    }

                    if($howmuch > $current_total_cart_discount){
                        $current_total_cart_discount = $howmuch;
                        $current_total_cart_discount_name = $discount_name;
                    }
                }
            }

            // Apply the biggest total cart discount, if any
            if($current_total_cart_discount > 0){
                $discount_display_name = esc_html__('Total Cart Discount','b2bking');
                if ($current_total_cart_discount_name !== '' && $current_total_cart_discount_name !== NULL){
                    $discount_display_name = $current_total_cart_discount_name;
                }
                $cart->add_fee( $discount_display_name, -$current_total_cart_discount);
            }


            /*
            * Apply all product category discounts
            */

            $categorydiscounts = array();


            // Get all dynamic rule product category discounts discounts that apply to the user or the user's group

            $category_discount_rules = get_transient('b2bking_category_discount_rules_'.get_current_user_id());

            if (!$category_discount_rules){
                $category_discount_rules = get_posts([
                    'post_type' => 'b2bking_rule',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'fields' => 'ids',
                    'post__in' => $discount_rule_ids,
                    'meta_query'=> array(
                        'relation' => 'AND',
                        $array_who,
                        array(
                            'relation' => 'OR',
                            array(
                                'key' => 'b2bking_rule_what',
                                'value' => 'discount_percentage'
                            ),
                            array(
                                'key' => 'b2bking_rule_what',
                                'value' => 'discount_amount'
                            ),
                        ),
                        array(
                                'key' => 'b2bking_rule_applies',
                                'value' => 'category', // values are of the form: category_idnumber, category_5, category_47 etc
                                'compare' => 'LIKE'
                            ),
                        array(
                            'relation' => 'OR',
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'value' => '0'
                            ),
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'value' => ''
                            ),
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'compare' => 'NOT EXISTS'
                            ),
                        ),
                    )
                ]);
                set_transient('b2bking_category_discount_rules_'.get_current_user_id(), $category_discount_rules);
            }
            foreach ($category_discount_rules as $category_discount_rule){
                
                // Get discount details
                $type = get_post_meta($category_discount_rule, 'b2bking_rule_what', true);
                $howmuch = get_post_meta($category_discount_rule, 'b2bking_rule_howmuch', true);
                $category_id = explode('_',get_post_meta($category_discount_rule, 'b2bking_rule_applies', true))[1];
                $discount_name = get_post_meta($category_discount_rule, 'b2bking_rule_discountname', true);

                $category_title = get_term( $category_id )->name;
                $number_products = 0;
                $total_price_products = 0;

                // Calculate number of products in cart of this category AND total price of these products
                if(is_object($cart)) {
                    foreach($cart->get_cart() as $cart_item){

                        if(has_term($category_id, 'product_cat', $cart_item['product_id'])){
                            $item_price = $cart_item['data']->get_price(); 
                            $item_qty = $cart_item["quantity"];// Quantity
                            if (!isset($cart_item["line_total"])){
                                $cart_item["line_total"] = 0;
                            }
                            $item_line_total = $cart_item["line_total"]; // Item total price (price x quantity)
                            $number_products += $item_qty; // ctotal number of items in cart
                            $total_price_products += $item_line_total; // calculated total items amount
                        }
                    }
                }

                // Check discount conditions
                $passconditions = 'yes';
                $conditions = get_post_meta($category_discount_rule, 'b2bking_rule_conditions', true);
                $conditions = explode('|',$conditions);
                foreach ($conditions as $condition){
                    $condition_details = explode(';',$condition);
                    switch ($condition_details[0]){
                        case 'category_product_quantity':
                            switch ($condition_details[1]){
                                case 'greater':
                                    if (! ($number_products > intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'equal':
                                    if (! ($number_products === intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'smaller':
                                    if (! ($number_products < intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                            }
                            break;
                        case 'category_product_value':
                            switch ($condition_details[1]){
                                case 'greater':
                                    if (! (floatval($total_price_products) > floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'equal':
                                    if (! (floatval($total_price_products) === floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'smaller':
                                    if (! (floatval($total_price_products) < floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                            }
                            break;  
                        case 'cart_total_quantity':
                            switch ($condition_details[1]){
                                case 'greater':
                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'equal':
                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'smaller':
                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                            }
                            break;
                        case 'cart_total_value':
                            switch ($condition_details[1]){
                                case 'greater':
                                    if (! (floatval($cart->subtotal) > floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'equal':
                                    if (! (floatval($cart->subtotal) === floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'smaller':
                                    if (! (floatval($cart->subtotal) < floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                            }
                            break;
                    }
                }

                // Passed conditions
                if ($passconditions === 'yes'){

                    if ($type === 'discount_amount'){
                        $howmuch = floatval ($howmuch) * $number_products;
                    } else if ($type === 'discount_percentage') {
                        $howmuch = (floatval($howmuch)/100) * $total_price_products;
                    }

                    if ($howmuch > 0){
                        if (!isset($categorydiscounts[$category_id])){
                            $categorydiscounts[$category_id] = array($category_title.esc_html__(' Discount','b2bking'), $howmuch);
                            // if the user gave the discount a name, use that
                            if ($discount_name !== NULL && $discount_name !== ''){
                                $categorydiscounts[$category_id][0] = $discount_name;
                            }
                        } else {
                            if ($howmuch > $categorydiscounts[$category_id][1]){
                                $categorydiscounts[$category_id][1] = $howmuch;
                                $categorydiscounts[$category_id][0] = $category_title.esc_html__(' Discount','b2bking');
                                // if the user gave the discount a name, use that
                                if ($discount_name !== NULL && $discount_name !== ''){
                                    $categorydiscounts[$category_id][0] = $discount_name;
                                }
                            }
                        }
                    }
                }
            }

            // Apply all the category discounts
            if (!empty($categorydiscounts)){
                foreach ($categorydiscounts as $discount){
                    $cart->add_fee( $discount[0], - $discount[1]);
                }
            }
            
            /*
            * Apply all individual product discounts
            */

            $productdiscounts = array();

            $product_discount_rules = get_transient('b2bking_product_discount_rules_'.get_current_user_id());

            if (!$product_discount_rules){
                // Get all dynamic rule individual product discounts  that apply to the user or the user's group
                $product_discount_rules = get_posts([
                    'post_type' => 'b2bking_rule',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'fields' => 'ids',
                    'post__in' => $discount_rule_ids,
                    'meta_query'=> array(
                        'relation' => 'AND',
                        $array_who,
                        array(
                            'relation' => 'OR',
                            array(
                                'key' => 'b2bking_rule_what',
                                'value' => 'discount_percentage'
                            ),
                            array(
                                'key' => 'b2bking_rule_what',
                                'value' => 'discount_amount'
                            ),
                        ),
                        array(
                                'key' => 'b2bking_rule_applies',
                                'value' => 'product', // values are of the form: product_idnumber
                                'compare' => 'LIKE'
                            ),
                        array(
                            'relation' => 'OR',
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'value' => '0'
                            ),
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'value' => ''
                            ),
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'compare' => 'NOT EXISTS'
                            ),
                        ),
                    )
                ]);

                set_transient('b2bking_product_discount_rules_'.get_current_user_id(), $product_discount_rules);
            }

            foreach ($product_discount_rules as $product_discount_rule){
                // Get discount details
                $type = get_post_meta($product_discount_rule, 'b2bking_rule_what', true);
                $howmuch = get_post_meta($product_discount_rule, 'b2bking_rule_howmuch', true);
                $product_id = explode('_',get_post_meta($product_discount_rule, 'b2bking_rule_applies', true))[1];
                $discount_name = get_post_meta($product_discount_rule, 'b2bking_rule_discountname', true);

                $product_title = get_the_title( $product_id );
                $number_products = 0;
                $total_price_products = 0;

                if(is_object($cart)) {
                    foreach($cart->get_cart() as $cart_item){

                        if(intval($product_id) === intval($cart_item['product_id']) || intval($product_id) === intval($cart_item['variation_id'])){
                            $item_price = $cart_item['data']->get_price(); 
                            $item_qty = $cart_item["quantity"];// Quantity
                            if (!isset($cart_item["line_total"])){
                                $cart_item["line_total"] = 0;
                            }
                            $item_line_total = $cart_item["line_total"]; // Item total price (price x quantity)
                            $number_products += $item_qty; // ctotal number of items in cart
                            $total_price_products += $item_line_total; // calculated total items amount
                        }

                    }
                }
                // Check discount conditions
                $passconditions = 'yes';
                $conditions = get_post_meta($product_discount_rule, 'b2bking_rule_conditions', true);
                $conditions = explode('|',$conditions);
                foreach ($conditions as $condition){
                    $condition_details = explode(';',$condition);
                    switch ($condition_details[0]){
                        case 'product_quantity':
                            switch ($condition_details[1]){
                                case 'greater':
                                    if (! ($number_products > intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'equal':
                                    if (! ($number_products === intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'smaller':
                                    if (! ($number_products < intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                            }
                            break;
                        case 'product_value':
                            switch ($condition_details[1]){
                                case 'greater':
                                    if (! (floatval($total_price_products) > floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'equal':
                                    if (! (floatval($total_price_products) === floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'smaller':
                                    if (! (floatval($total_price_products) < floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                            }
                            break;  
                        case 'cart_total_quantity':
                            switch ($condition_details[1]){
                                case 'greater':
                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'equal':
                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'smaller':
                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                            }
                            break;
                        case 'cart_total_value':
                            switch ($condition_details[1]){
                                case 'greater':
                                    if (! (floatval($cart->subtotal) > floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'equal':
                                    if (! (floatval($cart->subtotal) === floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                                case 'smaller':
                                    if (! (floatval($cart->subtotal) < floatval($condition_details[2]))){
                                        $passconditions = 'no';
                                        break 3;
                                    }
                                break;
                            }
                            break;
                    }
                }

                // Passed conditions
                if ($passconditions === 'yes'){

                    if ($type === 'discount_amount'){
                        $howmuch = floatval ($howmuch) * $number_products;
                    } else if ($type === 'discount_percentage') {
                        $howmuch = (floatval($howmuch)/100) * $total_price_products;
                    }

                    if ($howmuch > 0){
                        if (!isset($productdiscounts[$product_id])){
                            $productdiscounts[$product_id] = array($product_title.esc_html__(' Discount','b2bking'), $howmuch);
                            // if user gave discount a name, use that
                            if($discount_name !== NULL && $discount_name !== ''){
                                $productdiscounts[$product_id][0] = $discount_name;
                            }
                        } else {
                            if ($howmuch > $productdiscounts[$product_id][1]){
                                $productdiscounts[$product_id][1] = $howmuch;
                                $productdiscounts[$product_id][0] = $product_title.esc_html__(' Discount','b2bking');
                                // if user gave discount a name, use that
                                if($discount_name !== NULL && $discount_name !== ''){
                                    $productdiscounts[$product_id][0] = $discount_name;
                                }
                            }
                        }
                    }
                }   
            }

            // Apply all the product discounts
            if (!empty($productdiscounts)){
                foreach ($productdiscounts as $discount){
                    $cart->add_fee( $discount[0], - $discount[1]);
                }
            }

            /*
            * Apply all multi select discounts
            */
            
            $multiselect_discount_rules = get_transient('b2bking_multiselect_discount_rules_'.get_current_user_id());

            if (!$multiselect_discount_rules){
                // Get all multiselect discounts that apply to the user or the user's group
                $multiselect_discount_rules = get_posts([
                    'post_type' => 'b2bking_rule',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'fields' => 'ids',
                    'post__in' => $discount_rule_ids,
                    'meta_query'=> array(
                        'relation' => 'AND',
                        $array_who,
                        array(
                            'relation' => 'OR',
                            array(
                                'key' => 'b2bking_rule_what',
                                'value' => 'discount_percentage'
                            ),
                            array(
                                'key' => 'b2bking_rule_what',
                                'value' => 'discount_amount'
                            ),
                        ),
                        array(
                                'key' => 'b2bking_rule_applies',
                                'value' => 'multiple_options', 
                            ),
                        array(
                            'relation' => 'OR',
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'value' => '0'
                            ),
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'value' => ''
                            ),
                            array(
                                'key' => 'b2bking_rule_discount_show_everywhere',
                                'compare' => 'NOT EXISTS'
                            ),
                        ),
                    )
                ]);
                set_transient('b2bking_multiselect_discount_rules_'.get_current_user_id(), $multiselect_discount_rules);

            }

            // product discounts rules as part of multiselect
            foreach ($multiselect_discount_rules as $multiselect_discount_rule){
                // Get discount details
                $type = get_post_meta($multiselect_discount_rule, 'b2bking_rule_what', true);
                $howmuch = get_post_meta($multiselect_discount_rule, 'b2bking_rule_howmuch', true);
                $discount_name = get_post_meta($multiselect_discount_rule, 'b2bking_rule_discountname', true);

                $rule_multiple_options = get_post_meta($multiselect_discount_rule, 'b2bking_rule_applies_multiple_options', true);
                $rule_multiple_options_array = explode(',',$rule_multiple_options);
                foreach ($rule_multiple_options_array as $rule_element){
                    $rule_element_array = explode('_',$rule_element);
                    if ($rule_element_array[0] === 'category'){
                        $howmuch = get_post_meta($multiselect_discount_rule, 'b2bking_rule_howmuch', true);
                        $categorydiscountsmulti = array();
                        $category_id = $rule_element_array[1];
                        $category_title = get_term( $category_id )->name;
                        $number_products = 0;
                        $total_price_products = 0;

                        // Calculate number of products in cart of this category AND total price of these products
                        if(is_object($cart)) {
                            foreach($cart->get_cart() as $cart_item){

                                if(has_term($category_id, 'product_cat', $cart_item['product_id'])){
                                    $item_price = $cart_item['data']->get_price(); 
                                    $item_qty = $cart_item["quantity"];// Quantity
                                    if (!isset($cart_item["line_total"])){
                                        $cart_item["line_total"] = 0;
                                    }
                                    $item_line_total = $cart_item["line_total"]; // Item total price (price x quantity)
                                    $number_products += $item_qty; // ctotal number of items in cart
                                    $total_price_products += $item_line_total; // calculated total items amount
                                }
                            }
                        }

                        // Check discount conditions
                        $passconditions = 'yes';
                        $conditions = get_post_meta($multiselect_discount_rule, 'b2bking_rule_conditions', true);
                        $conditions = explode('|',$conditions);
                        foreach ($conditions as $condition){
                            $condition_details = explode(';',$condition);
                            switch ($condition_details[0]){
                                case 'category_product_quantity':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($number_products > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($number_products === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($number_products < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                                case 'category_product_value':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! (floatval($total_price_products) > floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! (floatval($total_price_products) === floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! (floatval($total_price_products) < floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;  
                                case 'cart_total_quantity':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                                case 'cart_total_value':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! (floatval($cart->subtotal) > floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! (floatval($cart->subtotal) === floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! (floatval($cart->subtotal) < floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                            }
                        }

                        // Passed conditions
                        if ($passconditions === 'yes'){

                            if ($type === 'discount_amount'){
                                $howmuch = floatval ($howmuch) * $number_products;
                            } else if ($type === 'discount_percentage') {
                                $howmuch = (floatval($howmuch)/100) * $total_price_products;
                            }

                            if ($howmuch > 0){
                                if (!isset($categorydiscountsmulti[$category_id])){
                                    $categorydiscountsmulti[$category_id] = array($category_title.esc_html__(' Discount','b2bking'), $howmuch);
                                    // if the user gave the discount a name, use that
                                    if ($discount_name !== NULL && $discount_name !== ''){
                                        $categorydiscountsmulti[$category_id][0] = $discount_name.' '.$category_title;
                                    }
                                } else {
                                    if ($howmuch > $categorydiscounts[$category_id][1]){
                                        $categorydiscountsmulti[$category_id][1] = $howmuch;
                                        $categorydiscountsmulti[$category_id][0] = $category_title.esc_html__(' Discount','b2bking');
                                        // if the user gave the discount a name, use that
                                        if ($discount_name !== NULL && $discount_name !== ''){
                                            $categorydiscountsmulti[$category_id][0] = $discount_name.' '.$category_title;
                                        }
                                    }
                                }
                            }
                        }

                        // Apply all the category discounts
                        if (!empty($categorydiscountsmulti)){
                            foreach ($categorydiscountsmulti as $discount){
                                $cart->add_fee( $discount[0], - $discount[1]);
                            }
                        }

                    } else if ($rule_element_array[0] === 'product'){
                        $howmuch = get_post_meta($multiselect_discount_rule, 'b2bking_rule_howmuch', true);
                        $productdiscountsmulti = array();
                        $product_id = $rule_element_array[1];
                        $product_title = get_the_title( $product_id );
                        $number_products = 0;
                        $total_price_products = 0;

                        if(is_object($cart)) {
                            foreach($cart->get_cart() as $cart_item){

                                if(intval($product_id) === intval($cart_item['product_id']) || intval($product_id) === intval($cart_item['variation_id'])){
                                    $item_price = $cart_item['data']->get_price(); 
                                    $item_qty = $cart_item["quantity"];// Quantity
                                    if (!isset($cart_item["line_total"])){
                                        $cart_item["line_total"] = 0;
                                    }
                                    $item_line_total = $cart_item["line_total"]; // Item total price (price x quantity)
                                    $number_products += $item_qty; // ctotal number of items in cart
                                    $total_price_products += $item_line_total; // calculated total items amount
                                }

                            }
                        }
                        // Check discount conditions
                        $passconditions = 'yes';
                        $conditions = get_post_meta($multiselect_discount_rule, 'b2bking_rule_conditions', true);
                        $conditions = explode('|',$conditions);
                        foreach ($conditions as $condition){
                            $condition_details = explode(';',$condition);
                            switch ($condition_details[0]){
                                case 'product_quantity':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($number_products > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($number_products === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($number_products < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                                case 'product_value':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! (floatval($total_price_products) > floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! (floatval($total_price_products) === floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! (floatval($total_price_products) < floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;  
                                case 'cart_total_quantity':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                                case 'cart_total_value':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! (floatval($cart->subtotal) > floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! (floatval($cart->subtotal) === floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! (floatval($cart->subtotal) < floatval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                            }
                        }

                        // Passed conditions
                        if ($passconditions === 'yes'){

                            if ($type === 'discount_amount'){
                                $howmuch = floatval ($howmuch) * $number_products;
                            } else if ($type === 'discount_percentage') {
                                $howmuch = (floatval($howmuch)/100) * $total_price_products;
                            }

                            if ($howmuch > 0){
                                if (!isset($productdiscountsmulti[$product_id])){
                                    $productdiscountsmulti[$product_id] = array($product_title.esc_html__(' Discount','b2bking'), $howmuch);
                                    // if user gave discount a name, use that
                                    if($discount_name !== NULL && $discount_name !== ''){
                                        $productdiscountsmulti[$product_id][0] = $discount_name.' '.$product_title;
                                    }
                                } else {
                                    if ($howmuch > $productdiscountsmulti[$product_id][1]){
                                        $productdiscountsmulti[$product_id][1] = $howmuch;
                                        $productdiscountsmulti[$product_id][0] = $product_title.esc_html__(' Discount','b2bking');
                                        // if user gave discount a name, use that
                                        if($discount_name !== NULL && $discount_name !== ''){
                                            $productdiscountsmulti[$product_id][0] = $discount_name.' '.$product_title;
                                        }
                                    }
                                }
                            }
                        }   

                        
                        // Apply all the category discounts
                        if (!empty($productdiscountsmulti)){
                            foreach ($productdiscountsmulti as $discount){
                                $cart->add_fee( $discount[0], - $discount[1]);
                            }
                        }
                    }
                }


            }
        }

        public static function b2bking_dynamic_rule_discount_regular_price( $regular_price, $product ){

            // Get current product
            $current_product_id = $product->get_id();
            // skip offers
            $offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
            $offer_id = apply_filters('b2bking_get_offer_product_id', $offer_id, $product->get_id());
            if (intval($current_product_id) === $offer_id || intval($current_product_id) === 3225464){ //3225464 is deprecated
                return $regular_price;
            }

            if( empty($regular_price) || $regular_price === 0 ){
                return $product->get_price();
            } else {
                return $regular_price;
            }
        }

        public static function b2bking_dynamic_rule_discount_sale_price( $sale_price, $product ){

            // Get current product
            $current_product_id = $product->get_id();

            // skip offers
            $offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
            $offer_id = apply_filters('b2bking_get_offer_product_id', $offer_id, $current_product_id);
            if (intval($current_product_id) === $offer_id || intval($current_product_id) === 3225464){ //3225464 is deprecated
                return $sale_price;
            }

            // 1) Get all rules and check if any rules apply to the product
            $rules_that_apply_to_product = get_transient('b2bking_discount_everywhere_rules_apply_'.$current_product_id);
            if (!$rules_that_apply_to_product){

                $rules_that_apply = array();
                $discount_everywhere_rules_option = get_option('b2bking_have_discount_everywhere_rules_list_ids', '');
                if (!empty($discount_everywhere_rules_option)){
                    $discount_everywhere_rules_v2_ids = explode(',',$discount_everywhere_rules_option);
                }

                foreach ($discount_everywhere_rules_v2_ids as $rule_id){
                    $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                    if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                        array_push($rules_that_apply, $rule_id);
                    } else if ($applies === 'multiple_options'){
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            array_push($rules_that_apply, $rule_id);
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                    break;
                                }
                            }
                        }
                        
                    } else if (explode('_', $applies)[0] === 'category'){
                        // check category
                        $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                        $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                        if (in_array($applies, $current_product_belongsto_array)){
                            array_push($rules_that_apply, $rule_id);
                        }
                    } else if ($applies === 'excluding_multiple_options'){
                        // check that current product is not in list
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);

                        $product_is_excluded = 'no';
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            $product_is_excluded = 'yes';
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                    break;
                                }
                            }
                        }
                        if ($product_is_excluded === 'no'){
                            // product is not excluded, therefore rule applies
                            array_push($rules_that_apply, $rule_id);
                        }
                    }

                }

                set_transient('b2bking_discount_everywhere_rules_apply_'.$current_product_id,$rules_that_apply);
                $rules_that_apply_to_product = $rules_that_apply;
            }
            // 2) If no rules apply for product, set transient for current user to empty array
            if (empty($rules_that_apply_to_product)){
                set_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id(), array());
            } else {
                // if transient does not already exist
                if (!get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id())){

                    // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                    $rules_that_apply_to_user = array();
                    $user_id = get_current_user_id();
                    $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                    if ($account_type === 'subaccount'){
                        // for all intents and purposes set current user as the subaccount parent
                        $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                        $user_id = $parent_user_id;
                    }

                    $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                    $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                    foreach ($rules_that_apply_to_product as $rule_id){
                        $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                        // first check guest users
                        if ($user_id === 0){
                            if ($who === 'user_0'){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else if ($who === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                $multiple_options_array = explode(',',$multiple_options);
                                if (in_array('user_0',$multiple_options_array)){
                                    array_push($rules_that_apply_to_user, $rule_id);
                                }
                            }
                        } else {
                            // user is not guest
                            if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else {
                                if ($user_is_b2b !== 'yes'){
                                    // user is b2c
                                    if ($who === 'everyone_registered_b2c'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {    
                                    // user is b2b
                                    if ($who === 'everyone_registered_b2b'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'group_'.$user_group){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // either an empty array or an array with rules
                    set_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                }
            }

            // 5. If there are no rules that apply to the product, check if this product is a variation and if 
            // there are any parent rules
            $post_parent_id = wp_get_post_parent_id($current_product_id);
            if (!get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id())){
                if ($post_parent_id !== 0){
                    // check if there are parent rules
                    $current_product_id = $post_parent_id;

                    // based on code above
                    // 1) Get all rules and check if any rules apply to the product
                    $rules_that_apply_to_product = get_transient('b2bking_discount_everywhere_parent_rules_apply_'.$current_product_id);
                    if (!$rules_that_apply_to_product){

                        $rules_that_apply = array();
                        $discount_everywhere_rules_option = get_option('b2bking_have_discount_everywhere_rules_list_ids', '');
                        if (!empty($discount_everywhere_rules_option)){
                            $discount_everywhere_rules_v2_ids = explode(',',$discount_everywhere_rules_option);
                        }

                        foreach ($discount_everywhere_rules_v2_ids as $rule_id){
                            $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                            if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                                array_push($rules_that_apply, $rule_id);
                            } else if ($applies === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            array_push($rules_that_apply, $rule_id);
                                            break;
                                        }
                                    }
                                }
                                
                            } else if (explode('_', $applies)[0] === 'category'){
                                // check category
                                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                if (in_array($applies, $current_product_belongsto_array)){
                                    array_push($rules_that_apply, $rule_id);
                                }
                            } else if ($applies === 'excluding_multiple_options'){
                                // check that current product is not in list
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);

                                $product_is_excluded = 'no';
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            $product_is_excluded = 'yes';
                                            break;
                                        }
                                    }
                                }
                                if ($product_is_excluded === 'no'){
                                    // product is not excluded, therefore rule applies
                                    array_push($rules_that_apply, $rule_id);
                                }
                            }
                        }

                        set_transient('b2bking_discount_everywhere_parent_rules_apply_'.$current_product_id,$rules_that_apply);
                        $rules_that_apply_to_product = $rules_that_apply;
                    }
                    // 2) If no rules apply for product, set transient for current user to empty array
                    if (empty($rules_that_apply_to_product)){
                        set_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id(), array());
                    } else {
                        // if transient does not already exist
                        if (!get_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id())){

                            // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                            $rules_that_apply_to_user = array();
                            $user_id = get_current_user_id();
                            $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                            if ($account_type === 'subaccount'){
                                // for all intents and purposes set current user as the subaccount parent
                                $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                                $user_id = $parent_user_id;
                            }

                            $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                            $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                            foreach ($rules_that_apply_to_product as $rule_id){
                                $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                                // first check guest users
                                if ($user_id === 0){
                                    if ($who === 'user_0'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('user_0',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {
                                    // user is not guest
                                    if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else {
                                        if ($user_is_b2b !== 'yes'){
                                            // user is b2c
                                            if ($who === 'everyone_registered_b2c'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        } else {    
                                            // user is b2b
                                            if ($who === 'everyone_registered_b2b'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'group_'.$user_group){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $current_product_id = $product->get_id();
                            // either an empty array or an array with rules
                            set_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                        }
                    }

                }   
            }
            
            $discount_everywhere_rules = get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id());
            $discount_everywhere_parent_rules = get_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id());

            if (empty($discount_everywhere_rules)){
                $discount_rules = $discount_everywhere_parent_rules;
                $current_product_categories = wc_get_product_term_ids( $post_parent_id, 'product_cat' );
                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                // add the product to the array to search for all relevant rules
                array_push($current_product_belongsto_array, 'product_'.$post_parent_id);
            } else {
                $discount_rules = $discount_everywhere_rules;
                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                // add the product to the array to search for all relevant rules
                array_push($current_product_belongsto_array, 'product_'.$current_product_id);
            }

            if (empty($discount_rules)){
                $discount_rules = array();
            }

            $regular_price = floatval($product->get_regular_price());

            // if multiple discount rules apply, give the smallest price to the user
            $have_discounted_price = NULL;
            $smallest_discounted_price = 0;

            foreach ($discount_rules as $discount_rule){

                // Get rule details
                $type = get_post_meta($discount_rule, 'b2bking_rule_what', true);
                $howmuch = get_post_meta($discount_rule, 'b2bking_rule_howmuch', true);
                $applies = explode('_',get_post_meta($discount_rule, 'b2bking_rule_applies', true));
                $rule_multiple_options = get_post_meta($discount_rule, 'b2bking_rule_applies_multiple_options', true);
                $rule_multiple_options_array = explode(',',$rule_multiple_options);
                $cart = WC()->cart;
                // Get conditions
                $passconditions = 'yes';
                $conditions = get_post_meta($discount_rule, 'b2bking_rule_conditions', true);
                $conditions = explode('|',$conditions);

                if ($applies[0] === 'excluding'){
                    // check that current product is not in excluded list
                    $product_is_excluded = 'no';
                    foreach ($rule_multiple_options_array as $excluded_option){
                        if ('product_'.$current_product_id === $excluded_option){
                            $product_is_excluded = 'yes';
                            break;
                        } else {
                            // check categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if ($item_category === $excluded_option){
                                    $product_is_excluded = 'yes';
                                    break 2;
                                }
                            }
                        }
                    }
                    if ($product_is_excluded === 'no'){
                        // go forward with discount, check conditions
                        foreach ($conditions as $condition){
                            $condition_details = explode(';',$condition);
                            switch ($condition_details[0]){
                                case 'cart_total_value':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($cart->cart_contents_total > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($cart->cart_contents_total === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($cart->cart_contents_total < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                                
                                case 'cart_total_quantity':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                            }
                        }
                    }
                } else if ($applies[0] === 'multiple'){ // if rule is multiple products / categories rule
                    $temporary_pass_conditions = 'no'; // if at least 1 element passes, change to yes
                    // for each element that applies to the product and is part of the rule, check if at least 1 passes the conditions
                    foreach($current_product_belongsto_array as $element){
                        if(in_array($element, $rule_multiple_options_array)){
                            $element_array = explode('_', $element);
                            // if element is product or if element is category
                            if ($element_array[0] === 'product'){
                                $passes_inside_conditions = 'yes';
                                $product_quantity = 0;

                                if(is_object($cart)) {
                                    foreach($cart->get_cart() as $cart_item){
                                        if(intval($element_array[1]) === intval($cart_item['product_id']) || intval($element_array[1]) === intval($cart_item['variation_id'])){
                                            $product_quantity = $cart_item["quantity"];// Quantity
                                            break;
                                        }
                                    }
                                }
                                // check all product conditions against it
                                foreach ($conditions as $condition){
                                    $condition_details = explode(';',$condition);
                                    switch ($condition_details[0]){
                                        case 'product_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($product_quantity > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($product_quantity === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($product_quantity < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                        case 'cart_total_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                    }
                                }
                                if ($passes_inside_conditions === 'yes'){
                                    $temporary_pass_conditions = 'yes';
                                    break; // if 1 element passed, no need to check all other elements
                                }
                            } else if ($element_array[0] === 'category'){
                                // check all category conditions against it + car total conditions
                                $passes_inside_conditions = 'yes';
                                $category_quantity = 0;
                                if(is_object($cart)) {
                                    foreach($cart->get_cart() as $cart_item){
                                        if(has_term($element_array[1], 'product_cat', $cart_item['product_id'])){
                                            $category_quantity += $cart_item["quantity"]; // add item quantity
                                        }
                                    }
                                }
                                foreach ($conditions as $condition){
                                    $condition_details = explode(';',$condition);
                                    switch ($condition_details[0]){
                                        case 'category_product_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($category_quantity > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($category_quantity === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($category_quantity < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                        case 'cart_total_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                    }
                                }
                                if ($passes_inside_conditions === 'yes'){
                                    $temporary_pass_conditions = 'yes';
                                    break; // if 1 element passed, no need to check all other elements
                                }
                            }
                        }
                    } //foreach element end

                    if ($temporary_pass_conditions === 'no'){
                        $passconditions = 'no';
                    }

                } else {

                    $category_products_number = 0;
                    $category_products_value = 0;
                    $products_number = 0;
                    $products_value = 0;
                    $cart = WC()->cart;

                    // Check rule is category rule or product rule
                    if ($applies[0] === 'category'){

                        // Calculate number of products in cart of this category AND total price of these products
                        if(is_object($cart)) {
                            foreach($cart->get_cart() as $cart_item){
                                if(has_term($applies[1], 'product_cat', $cart_item['product_id'])){
                                    $item_qty = $cart_item["quantity"];// Quantity
                                    if (!isset($cart_item["line_total"])){
                                        $cart_item["line_total"] = 0;
                                    }
                                    $item_line_total = $cart_item["line_total"]; // Item total price (price x quantity)
                                    $category_products_number += $item_qty; // ctotal number of items in cart
                                    $category_products_value += $item_line_total; // calculated total items amount
                                }
                            }
                        }
                    } else if ($applies[0] === 'product') {

                        if(is_object($cart)) {
                            foreach($cart->get_cart() as $cart_item){
                                if(intval($current_product_id) === intval($cart_item['product_id']) || intval($current_product_id) === intval($cart_item['variation_id'])){
                                    $item_qty = $cart_item["quantity"];// Quantity
                                    $products_number += $item_qty; // ctotal number of items in cart
                                    if (isset($cart_item["line_total"])){
                                        $item_line_total = $cart_item["line_total"]; // Item total price (price x quantity)
                                        $products_value += $item_line_total; // calculated total items amount
                                    }
                                }
                            }
                        }
                    }

                    // Check discount conditions
                    $passconditions = 'yes';
                    $conditions = get_post_meta($discount_rule, 'b2bking_rule_conditions', true);
                    $conditions = explode('|',$conditions);
                    foreach ($conditions as $condition){
                        $condition_details = explode(';',$condition);
                        switch ($condition_details[0]){
                            case 'product_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($products_number > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($products_number === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($products_number < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                            
                            case 'category_product_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($category_products_number > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($category_products_number === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($category_products_number < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                            
                            case 'cart_total_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                        }
                    }
                }

                // Rule passed conditions, so it applies. Calculate discounted price
                if ($passconditions === 'yes'){
                    if ($have_discounted_price === NULL){
                        $have_discounted_price = 'yes';
                        // calculate discount and regular price based on $howmuch and discount type
                        if ($type === 'discount_amount'){
                            $smallest_discounted_price = floatval($regular_price - $howmuch);
                        } else if ($type === 'discount_percentage') {
                            $smallest_discounted_price = floatval($regular_price - ($howmuch/100 * $regular_price));
                        }
                    } else {
                        if ($type === 'discount_amount'){
                            $temporary_discounted_price = floatval($regular_price - $howmuch);
                        } else if ($type === 'discount_percentage') {
                            $temporary_discounted_price = floatval($regular_price - ($howmuch/100 * $regular_price));
                        }
                        if ($temporary_discounted_price < $smallest_discounted_price){
                            $smallest_discounted_price = $temporary_discounted_price;
                        }   
                    }
                } else {
                    // do nothing
                }
            } //foreach end

            if($have_discounted_price !== NULL){
                return round($smallest_discounted_price, 2);
            } else {
                return $sale_price;
            }
        }

        public static function b2bking_dynamic_rule_discount_sale_price_variation_hash( $hash ) {
            // if dynamic rules have changed, clear pricing cache
            $rules_have_changed = get_option('b2bking_dynamic_rules_have_changed', 'no');
            if ($rules_have_changed === 'yes'){
                // clear cache
                WC_Cache_Helper::get_transient_version( 'product', true );
                update_option('b2bking_dynamic_rules_have_changed', 'no');
            }

            $hash[] = get_current_user_id();
            return $hash;
        }

        public static function b2bking_dynamic_rule_discount_display_dynamic_price( $price_html, $product ) {

            // Get current product
            $current_product_id = $product->get_id();

            // skip offers
            $offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
            $offer_id = apply_filters('b2bking_get_offer_product_id', $offer_id, $current_product_id);
            if (intval($current_product_id) === $offer_id || intval($current_product_id) === 3225464){ //3225464 is deprecated
                return $price_html;
            }

            if( $product->is_type('variable') && !class_exists('WOOCS')) { // add WOOCS compatibility
                return $price_html;
            }
            // check if discount sale rules apply. If they do, show formatted sale price

            // 1) Get all rules and check if any rules apply to the product
            $rules_that_apply_to_product = get_transient('b2bking_discount_everywhere_rules_apply_'.$current_product_id);
            if (!$rules_that_apply_to_product){

                $rules_that_apply = array();
                $discount_everywhere_rules_option = get_option('b2bking_have_discount_everywhere_rules_list_ids', '');
                if (!empty($discount_everywhere_rules_option)){
                    $discount_everywhere_rules_v2_ids = explode(',',$discount_everywhere_rules_option);
                }

                foreach ($discount_everywhere_rules_v2_ids as $rule_id){
                    $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                    if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                        array_push($rules_that_apply, $rule_id);
                    } else if ($applies === 'multiple_options'){
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            array_push($rules_that_apply, $rule_id);
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                    break;
                                }
                            }
                        }
                        
                    } else if (explode('_', $applies)[0] === 'category'){
                        // check category
                        $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                        $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                        if (in_array($applies, $current_product_belongsto_array)){
                            array_push($rules_that_apply, $rule_id);
                        }
                    } else if ($applies === 'excluding_multiple_options'){
                        // check that current product is not in list
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);

                        $product_is_excluded = 'no';
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            $product_is_excluded = 'yes';
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                    break;
                                }
                            }
                        }
                        if ($product_is_excluded === 'no'){
                            // product is not excluded, therefore rule applies
                            array_push($rules_that_apply, $rule_id);
                        }
                    }
                }

                set_transient('b2bking_discount_everywhere_rules_apply_'.$current_product_id,$rules_that_apply);
                $rules_that_apply_to_product = $rules_that_apply;
            }
            // 2) If no rules apply for product, set transient for current user to empty array
            if (empty($rules_that_apply_to_product)){
                set_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id(), array());
            } else {
                // if transient does not already exist
                if (!get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id())){

                    // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                    $rules_that_apply_to_user = array();
                    $user_id = get_current_user_id();
                    $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                    if ($account_type === 'subaccount'){
                        // for all intents and purposes set current user as the subaccount parent
                        $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                        $user_id = $parent_user_id;
                    }

                    $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                    $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                    foreach ($rules_that_apply_to_product as $rule_id){
                        $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                        // first check guest users
                        if ($user_id === 0){
                            if ($who === 'user_0'){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else if ($who === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                $multiple_options_array = explode(',',$multiple_options);
                                if (in_array('user_0',$multiple_options_array)){
                                    array_push($rules_that_apply_to_user, $rule_id);
                                }
                            }
                        } else {
                            // user is not guest
                            if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else {
                                if ($user_is_b2b !== 'yes'){
                                    // user is b2c
                                    if ($who === 'everyone_registered_b2c'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {    
                                    // user is b2b
                                    if ($who === 'everyone_registered_b2b'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'group_'.$user_group){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // either an empty array or an array with rules
                    set_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                }
            }

            // 5. If there are no rules that apply to the product, check if this product is a variation and if 
            // there are any parent rules
            $post_parent_id = wp_get_post_parent_id($current_product_id);
            if (!get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id())){
                if ($post_parent_id !== 0){
                    // check if there are parent rules
                    $current_product_id = $post_parent_id;

                    // based on code above
                    // 1) Get all rules and check if any rules apply to the product
                    $rules_that_apply_to_product = get_transient('b2bking_discount_everywhere_parent_rules_apply_'.$current_product_id);
                    if (!$rules_that_apply_to_product){

                        $rules_that_apply = array();
                        $discount_everywhere_rules_option = get_option('b2bking_have_discount_everywhere_rules_list_ids', '');
                        if (!empty($discount_everywhere_rules_option)){
                            $discount_everywhere_rules_v2_ids = explode(',',$discount_everywhere_rules_option);
                        }

                        foreach ($discount_everywhere_rules_v2_ids as $rule_id){
                            $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                            if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                                array_push($rules_that_apply, $rule_id);
                            } else if ($applies === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            array_push($rules_that_apply, $rule_id);
                                            break;
                                        }
                                    }
                                }
                                
                            } else if (explode('_', $applies)[0] === 'category'){
                                // check category
                                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                if (in_array($applies, $current_product_belongsto_array)){
                                    array_push($rules_that_apply, $rule_id);
                                }
                            } else if ($applies === 'excluding_multiple_options'){
                                // check that current product is not in list
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);

                                $product_is_excluded = 'no';
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            $product_is_excluded = 'yes';
                                            break;
                                        }
                                    }
                                }
                                if ($product_is_excluded === 'no'){
                                    // product is not excluded, therefore rule applies
                                    array_push($rules_that_apply, $rule_id);
                                }
                            }
                        }

                        set_transient('b2bking_discount_everywhere_parent_rules_apply_'.$current_product_id,$rules_that_apply);
                        $rules_that_apply_to_product = $rules_that_apply;
                    }
                    // 2) If no rules apply for product, set transient for current user to empty array
                    if (empty($rules_that_apply_to_product)){
                        set_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id(), array());
                    } else {
                        // if transient does not already exist
                        if (!get_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id())){

                            // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                            $rules_that_apply_to_user = array();
                            $user_id = get_current_user_id();
                            $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                            if ($account_type === 'subaccount'){
                                // for all intents and purposes set current user as the subaccount parent
                                $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                                $user_id = $parent_user_id;
                            }

                            $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                            $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                            foreach ($rules_that_apply_to_product as $rule_id){
                                $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                                // first check guest users
                                if ($user_id === 0){
                                    if ($who === 'user_0'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('user_0',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {
                                    // user is not guest
                                    if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else {
                                        if ($user_is_b2b !== 'yes'){
                                            // user is b2c
                                            if ($who === 'everyone_registered_b2c'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        } else {    
                                            // user is b2b
                                            if ($who === 'everyone_registered_b2b'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'group_'.$user_group){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $current_product_id = $product->get_id();
                            // either an empty array or an array with rules
                            set_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                        }
                    }

                }   
            }
            
            $discount_everywhere_rules = get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id());
            $discount_everywhere_parent_rules = get_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id());

            if (empty($discount_everywhere_rules)){
                $discount_rules = $discount_everywhere_parent_rules;
                $current_product_categories = wc_get_product_term_ids( $post_parent_id, 'product_cat' );
                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                // add the product to the array to search for all relevant rules
                array_push($current_product_belongsto_array, 'product_'.$post_parent_id);
            } else {
                $discount_rules = $discount_everywhere_rules;
                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                // add the product to the array to search for all relevant rules
                array_push($current_product_belongsto_array, 'product_'.$current_product_id);
            }

            if (empty($discount_rules)){
                $discount_rules = array();
            }

            // if multiple discount rules apply, give the smallest price to the user
            $have_discounted_price = NULL;
            $smallest_discounted_price = 0;

            foreach ($discount_rules as $discount_rule){
                // Get rule details
                $type = get_post_meta($discount_rule, 'b2bking_rule_what', true);
                $howmuch = get_post_meta($discount_rule, 'b2bking_rule_howmuch', true);
                $applies = explode('_',get_post_meta($discount_rule, 'b2bking_rule_applies', true));
                $rule_multiple_options = get_post_meta($discount_rule, 'b2bking_rule_applies_multiple_options', true);
                $rule_multiple_options_array = explode(',',$rule_multiple_options);
                $cart = WC()->cart;
                // Get conditions
                $passconditions = 'yes';
                $conditions = get_post_meta($discount_rule, 'b2bking_rule_conditions', true);
                $conditions = explode('|',$conditions);

                if ($applies[0] === 'excluding'){
                    // check that current product is not in excluded list
                    $product_is_excluded = 'no';
                    foreach ($rule_multiple_options_array as $excluded_option){
                        if ('product_'.$current_product_id === $excluded_option){
                            $product_is_excluded = 'yes';
                            break;
                        } else {
                            // check categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if ($item_category === $excluded_option){
                                    $product_is_excluded = 'yes';
                                    break 2;
                                }
                            }
                        }
                    }
                    if ($product_is_excluded === 'no'){
                        // go forward with discount, check conditions
                        foreach ($conditions as $condition){
                            $condition_details = explode(';',$condition);
                            switch ($condition_details[0]){
                                case 'cart_total_value':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($cart->cart_contents_total > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($cart->cart_contents_total === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($cart->cart_contents_total < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                                
                                case 'cart_total_quantity':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                            }
                        }
                    }
                } else if ($applies[0] === 'multiple'){ // if rule is multiple products / categories rule
                    $temporary_pass_conditions = 'no'; // if at least 1 element passes, change to yes
                    // for each element that applies to the product and is part of the rule, check if at least 1 passes the conditions
                    foreach($current_product_belongsto_array as $element){
                        if(in_array($element, $rule_multiple_options_array)){
                            $element_array = explode('_', $element);
                            // if element is product or if element is category
                            if ($element_array[0] === 'product'){
                                $passes_inside_conditions = 'yes';
                                $product_quantity = 0;
                                if(is_object($cart)) {
                                    foreach($cart->get_cart() as $cart_item){
                                        if(intval($element_array[1]) === intval($cart_item['product_id']) || intval($element_array[1]) === intval($cart_item['variation_id'])){
                                            $product_quantity = $cart_item["quantity"];// Quantity
                                            break;
                                        }
                                    }
                                }
                                // check all product conditions against it
                                foreach ($conditions as $condition){
                                    $condition_details = explode(';',$condition);
                                    switch ($condition_details[0]){
                                        case 'product_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($product_quantity > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($product_quantity === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($product_quantity < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                        case 'cart_total_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                    }
                                }
                                if ($passes_inside_conditions === 'yes'){
                                    $temporary_pass_conditions = 'yes';
                                    break; // if 1 element passed, no need to check all other elements
                                }
                            } else if ($element_array[0] === 'category'){
                                // check all category conditions against it + car total conditions
                                $passes_inside_conditions = 'yes';
                                $category_quantity = 0;
                                if(is_object($cart)) {
                                    foreach($cart->get_cart() as $cart_item){
                                        if(has_term($element_array[1], 'product_cat', $cart_item['product_id'])){
                                            $category_quantity += $cart_item["quantity"]; // add item quantity
                                        }
                                    }
                                }
                                foreach ($conditions as $condition){
                                    $condition_details = explode(';',$condition);
                                    switch ($condition_details[0]){
                                        case 'category_product_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($category_quantity > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($category_quantity === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($category_quantity < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                        case 'cart_total_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                    }
                                }
                                if ($passes_inside_conditions === 'yes'){
                                    $temporary_pass_conditions = 'yes';
                                    break; // if 1 element passed, no need to check all other elements
                                }
                            }
                        }
                    } //foreach element end

                    if ($temporary_pass_conditions === 'no'){
                        $passconditions = 'no';
                    }

                } else {
                    // Get rule details
                    $type = get_post_meta($discount_rule, 'b2bking_rule_what', true);
                    $howmuch = get_post_meta($discount_rule, 'b2bking_rule_howmuch', true);
                    $applies = explode('_',get_post_meta($discount_rule, 'b2bking_rule_applies', true));

                    $category_products_number = 0;
                    $category_products_value = 0;
                    $products_number = 0;
                    $products_value = 0;
                    $cart = WC()->cart;

                    // Check rule is category rule or product rule
                    if ($applies[0] === 'category'){

                        // Calculate number of products in cart of this category AND total price of these products
                        if(is_object($cart)) {
                            foreach($cart->get_cart() as $cart_item){
                                if(has_term($applies[1], 'product_cat', $cart_item['product_id'])){
                                    $item_qty = $cart_item["quantity"];// Quantity
                                    if (!isset($cart_item["line_total"])){
                                        $cart_item["line_total"] = 0;
                                    }
                                    $item_line_total = $cart_item["line_total"]; // Item total price (price x quantity)
                                    $category_products_number += $item_qty; // ctotal number of items in cart
                                    $category_products_value += $item_line_total; // calculated total items amount
                                }
                            }
                        }
                    } else if ($applies[0] === 'product') {

                        if(is_object($cart)) {
                            foreach($cart->get_cart() as $cart_item){
                                if(intval($current_product_id) === intval($cart_item['product_id']) || intval($current_product_id) === intval($cart_item['variation_id'])){
                                    $item_qty = $cart_item["quantity"];// Quantity
                                    if (!isset($cart_item["line_total"])){
                                        $cart_item["line_total"] = 0;
                                    }
                                    $item_line_total = $cart_item["line_total"]; // Item total price (price x quantity)
                                    $products_number += $item_qty; // ctotal number of items in cart
                                    $products_value += $item_line_total; // calculated total items amount
                                }
                            }
                        }
                    }

                    // Check discount conditions
                    $passconditions = 'yes';
                    $conditions = get_post_meta($discount_rule, 'b2bking_rule_conditions', true);
                    $conditions = explode('|',$conditions);
                    foreach ($conditions as $condition){
                        $condition_details = explode(';',$condition);
                        switch ($condition_details[0]){
                            case 'product_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($products_number > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($products_number === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($products_number < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                            
                            case 'category_product_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($category_products_number > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($category_products_number === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($category_products_number < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                            
                            case 'cart_total_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                        }
                    }
                }

                // Rule passed conditions, so it applies. Calculate discounted price
                if ($passconditions === 'yes'){
                    if ($have_discounted_price === NULL){
                        $have_discounted_price = 'yes';
                    }
                } else {
                    // do nothing
                }
            } //foreach end

            if($have_discounted_price !== NULL){
                if( $product->is_type('variable') && class_exists('WOOCS')) { // add WOOCS compatibility

                    global $WOOCS;
                    $currrent = $WOOCS->current_currency;
                    if ($currrent != $WOOCS->default_currency) {
                        $currencies = $WOOCS->get_currencies();
                        $rate = $currencies[$currrent]['rate'];

                        // apply WOOCS rate to price_html
                        $min_price = $product->get_variation_price( 'min' ) / ($rate);
                        $max_price = $product->get_variation_price( 'max' ) / ($rate);
                        $price_html = wc_format_price_range( $min_price, $max_price );

                        WC_Cache_Helper::get_transient_version( 'product', true );
                    }

                } else { 

                    $price_html = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), wc_get_price_to_display(  $product, array( 'price' => $product->get_sale_price() ) ) ) . $product->get_price_suffix();
                }
            } else {
                // do nothing
            }

            return $price_html;
        }

        public static function b2bking_dynamic_rule_discount_display_dynamic_price_in_cart( $cart ) {
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ){
                return;
            }

            if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ){
                return;
            }

            // Get current user
            $user_id = get_current_user_id();
            $account_type = get_user_meta($user_id,'b2bking_account_type', true);
            if ($account_type === 'subaccount'){
                // for all intents and purposes set current user as the subaccount parent
                $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                $user_id = $parent_user_id;
            }

            // Iterate through each cart item
            if(is_object($cart)) {
                foreach( $cart->get_cart() as $cart_item ) {

                    if (isset($cart_item['free_product'])){
                        continue;
                    }

                    if (isset($cart_item['wc_sc_product_source'])){
                        continue;
                    }

                    if (isset($cart_item['sample-product'])){
                        continue;
                    }

                    // integration with woo chained products
                    if (isset($cart_item['chained_item_of'])){
                        continue;
                    }

                    // skip offers
                    $offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
                    $offer_id = apply_filters('b2bking_get_offer_product_id', $offer_id, $cart_item['product_id']);
                    if (intval($cart_item['product_id']) === intval($offer_id) || intval($cart_item['product_id']) === 3225464){ //3225464 is deprecated
                        continue;
                    }

                    if (isset($cart_item['variation_id']) && intval($cart_item['variation_id']) !== 0){
                        $current_product_id = $cart_item['variation_id'];
                        $product = wc_get_product($current_product_id);
                    } else {
                        $current_product_id = $cart_item['product_id'];
                        $product = wc_get_product($current_product_id);
                    }

                    // 1) Get all rules and check if any rules apply to the product
                    $rules_that_apply_to_product = get_transient('b2bking_discount_everywhere_rules_apply_'.$current_product_id);
                    if (!$rules_that_apply_to_product){

                        $rules_that_apply = array();
                        $discount_everywhere_rules_option = get_option('b2bking_have_discount_everywhere_rules_list_ids', '');
                        if (!empty($discount_everywhere_rules_option)){
                            $discount_everywhere_rules_v2_ids = explode(',',$discount_everywhere_rules_option);
                        }

                        foreach ($discount_everywhere_rules_v2_ids as $rule_id){
                            $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                            if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                                array_push($rules_that_apply, $rule_id);
                            } else if ($applies === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            array_push($rules_that_apply, $rule_id);
                                            break;
                                        }
                                    }
                                }
                                
                            } else if (explode('_', $applies)[0] === 'category'){
                                // check category
                                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                                if (in_array($applies, $current_product_belongsto_array)){
                                    array_push($rules_that_apply, $rule_id);
                                }
                            } else if ($applies === 'excluding_multiple_options'){
                                // check that current product is not in list
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);

                                $product_is_excluded = 'no';
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            $product_is_excluded = 'yes';
                                            break;
                                        }
                                    }
                                }
                                if ($product_is_excluded === 'no'){
                                    // product is not excluded, therefore rule applies
                                    array_push($rules_that_apply, $rule_id);
                                }
                            }
                        }

                        set_transient('b2bking_discount_everywhere_rules_apply_'.$current_product_id,$rules_that_apply);
                        $rules_that_apply_to_product = $rules_that_apply;
                    }
                    // 2) If no rules apply for product, set transient for current user to empty array
                    if (empty($rules_that_apply_to_product)){
                        set_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id(), array());
                    } else {
                        // if transient does not already exist
                        if (!get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id())){

                            // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                            $rules_that_apply_to_user = array();
                            $user_id = get_current_user_id();
                            $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                            if ($account_type === 'subaccount'){
                                // for all intents and purposes set current user as the subaccount parent
                                $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                                $user_id = $parent_user_id;
                            }

                            $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                            $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                            foreach ($rules_that_apply_to_product as $rule_id){
                                $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                                // first check guest users
                                if ($user_id === 0){
                                    if ($who === 'user_0'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('user_0',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {
                                    // user is not guest
                                    if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else {
                                        if ($user_is_b2b !== 'yes'){
                                            // user is b2c
                                            if ($who === 'everyone_registered_b2c'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        } else {    
                                            // user is b2b
                                            if ($who === 'everyone_registered_b2b'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'group_'.$user_group){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // either an empty array or an array with rules
                            set_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                        }
                    }

                    // 5. If there are no rules that apply to the product, check if this product is a variation and if 
                    // there are any parent rules
                    $post_parent_id = wp_get_post_parent_id($current_product_id);
                    if (!get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id())){
                        if ($post_parent_id !== 0){
                            // check if there are parent rules
                            $current_product_id = $post_parent_id;

                            // based on code above
                            // 1) Get all rules and check if any rules apply to the product
                            $rules_that_apply_to_product = get_transient('b2bking_discount_everywhere_parent_rules_apply_'.$current_product_id);
                            if (!$rules_that_apply_to_product){

                                $rules_that_apply = array();
                                $discount_everywhere_rules_option = get_option('b2bking_have_discount_everywhere_rules_list_ids', '');
                                if (!empty($discount_everywhere_rules_option)){
                                    $discount_everywhere_rules_v2_ids = explode(',',$discount_everywhere_rules_option);
                                }

                                foreach ($discount_everywhere_rules_v2_ids as $rule_id){
                                    $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                                    if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                                        array_push($rules_that_apply, $rule_id);
                                    } else if ($applies === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                        $multiple_options_array = explode(',', $multiple_options);
                                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                            array_push($rules_that_apply, $rule_id);
                                        } else {
                                            // try categories
                                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                            foreach ($current_product_belongsto_array as $item_category){
                                                if (in_array($item_category, $multiple_options_array)){
                                                    array_push($rules_that_apply, $rule_id);
                                                    break;
                                                }
                                            }
                                        }
                                        
                                    } else if (explode('_', $applies)[0] === 'category'){
                                        // check category
                                        $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                        $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                        if (in_array($applies, $current_product_belongsto_array)){
                                            array_push($rules_that_apply, $rule_id);
                                        }
                                    } else if ($applies === 'excluding_multiple_options'){
                                    // check that current product is not in list
                                    $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                    $multiple_options_array = explode(',', $multiple_options);

                                    $product_is_excluded = 'no';
                                    if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                        $product_is_excluded = 'yes';
                                    } else {
                                        // try categories
                                        $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                        $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                        foreach ($current_product_belongsto_array as $item_category){
                                            if (in_array($item_category, $multiple_options_array)){
                                                $product_is_excluded = 'yes';
                                                break;
                                            }
                                        }
                                    }
                                    if ($product_is_excluded === 'no'){
                                        // product is not excluded, therefore rule applies
                                        array_push($rules_that_apply, $rule_id);
                                    }
                                }
                                }

                                set_transient('b2bking_discount_everywhere_parent_rules_apply_'.$current_product_id,$rules_that_apply);
                                $rules_that_apply_to_product = $rules_that_apply;
                            }
                            // 2) If no rules apply for product, set transient for current user to empty array
                            if (empty($rules_that_apply_to_product)){
                                set_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id(), array());
                            } else {
                                // if transient does not already exist
                                if (!get_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id())){

                                    // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                                    $rules_that_apply_to_user = array();
                                    $user_id = get_current_user_id();
                                    $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                                    if ($account_type === 'subaccount'){
                                        // for all intents and purposes set current user as the subaccount parent
                                        $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                                        $user_id = $parent_user_id;
                                    }

                                    $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                                    $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                                    foreach ($rules_that_apply_to_product as $rule_id){
                                        $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                                        // first check guest users
                                        if ($user_id === 0){
                                            if ($who === 'user_0'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('user_0',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        } else {
                                            // user is not guest
                                            if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else {
                                                if ($user_is_b2b !== 'yes'){
                                                    // user is b2c
                                                    if ($who === 'everyone_registered_b2c'){
                                                        array_push($rules_that_apply_to_user, $rule_id);
                                                    } else if ($who === 'multiple_options'){
                                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                        $multiple_options_array = explode(',',$multiple_options);
                                                        if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                                            array_push($rules_that_apply_to_user, $rule_id);
                                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                            array_push($rules_that_apply_to_user, $rule_id);
                                                        }
                                                    }
                                                } else {    
                                                    // user is b2b
                                                    if ($who === 'everyone_registered_b2b'){
                                                        array_push($rules_that_apply_to_user, $rule_id);
                                                    } else if ($who === 'group_'.$user_group){
                                                        array_push($rules_that_apply_to_user, $rule_id);
                                                    } else if ($who === 'multiple_options'){
                                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                        $multiple_options_array = explode(',',$multiple_options);
                                                        if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                                            array_push($rules_that_apply_to_user, $rule_id);
                                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                            array_push($rules_that_apply_to_user, $rule_id);
                                                        } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                                            array_push($rules_that_apply_to_user, $rule_id);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $current_product_id = $product->get_id();
                                    // either an empty array or an array with rules
                                    set_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                                }
                            }

                        }   
                    }
                    
                    $discount_everywhere_rules = get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id());
                    $discount_everywhere_parent_rules = get_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id());

                    if (empty($discount_everywhere_rules)){
                        $discount_rules = $discount_everywhere_parent_rules;
                        $current_product_categories = wc_get_product_term_ids( $post_parent_id, 'product_cat' );
                        $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                        // add the product to the array to search for all relevant rules
                        array_push($current_product_belongsto_array, 'product_'.$post_parent_id);
                    } else {
                        $discount_rules = $discount_everywhere_rules;
                        $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                        $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                        // add the product to the array to search for all relevant rules
                        array_push($current_product_belongsto_array, 'product_'.$current_product_id);
                    }

                    if (empty($discount_rules)){
                        $discount_rules = array();
                    }

                    $regular_price = floatval($product->get_regular_price());

                    
                    // if multiple discount rules apply, give the smallest price to the user
                    $have_discounted_price = NULL;
                    $smallest_discounted_price = 0;

                    foreach ($discount_rules as $discount_rule){
                        // Get rule details
                        $type = get_post_meta($discount_rule, 'b2bking_rule_what', true);
                        $howmuch = get_post_meta($discount_rule, 'b2bking_rule_howmuch', true);
                        $applies = explode('_',get_post_meta($discount_rule, 'b2bking_rule_applies', true));
                        $rule_multiple_options = get_post_meta($discount_rule, 'b2bking_rule_applies_multiple_options', true);
                        $rule_multiple_options_array = explode(',',$rule_multiple_options);
                        $cart = WC()->cart;
                        // Get conditions
                        $passconditions = 'yes';
                        $conditions = get_post_meta($discount_rule, 'b2bking_rule_conditions', true);
                        $conditions = explode('|',$conditions);

                        if ($applies[0] === 'excluding'){
                            // check that current product is not in excluded list
                            $product_is_excluded = 'no';
                            foreach ($rule_multiple_options_array as $excluded_option){
                                if ('product_'.$current_product_id === $excluded_option){
                                    $product_is_excluded = 'yes';
                                    break;
                                } else {
                                    // check categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if ($item_category === $excluded_option){
                                            $product_is_excluded = 'yes';
                                            break 2;
                                        }
                                    }
                                }
                            }
                            if ($product_is_excluded === 'no'){
                                // go forward with discount, check conditions
                                foreach ($conditions as $condition){
                                    $condition_details = explode(';',$condition);
                                    switch ($condition_details[0]){
                                        case 'cart_total_value':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($cart->cart_contents_total > intval($condition_details[2]))){
                                                        $passconditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($cart->cart_contents_total === intval($condition_details[2]))){
                                                        $passconditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($cart->cart_contents_total < intval($condition_details[2]))){
                                                        $passconditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                        
                                        case 'cart_total_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                        $passconditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                        $passconditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                        $passconditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                    }
                                }
                            }
                        } else if ($applies[0] === 'multiple'){ // if rule is multiple products / categories rule
                            $temporary_pass_conditions = 'no'; // if at least 1 element passes, change to yes
                            // for each element that applies to the product and is part of the rule, check if at least 1 passes the conditions
                            foreach($current_product_belongsto_array as $element){
                                if(in_array($element, $rule_multiple_options_array)){
                                    $element_array = explode('_', $element);
                                    // if element is product or if element is category
                                    if ($element_array[0] === 'product'){
                                        $passes_inside_conditions = 'yes';
                                        $product_quantity = 0;
                                        foreach($cart->get_cart() as $cart_item2){
                                            if(intval($element_array[1]) === intval($cart_item2['product_id']) || intval($element_array[1]) === intval($cart_item2['variation_id'])){
                                                $product_quantity = $cart_item2["quantity"];// Quantity
                                                break;
                                            }
                                        }
                                        // check all product conditions against it
                                        foreach ($conditions as $condition){
                                            $condition_details = explode(';',$condition);
                                            switch ($condition_details[0]){
                                                case 'product_quantity':
                                                    switch ($condition_details[1]){
                                                        case 'greater':
                                                            if (! ($product_quantity > intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                        case 'equal':
                                                            if (! ($product_quantity === intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                        case 'smaller':
                                                            if (! ($product_quantity < intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                    }
                                                    break;
                                                case 'cart_total_quantity':
                                                    switch ($condition_details[1]){
                                                        case 'greater':
                                                            if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                        case 'equal':
                                                            if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                        case 'smaller':
                                                            if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                    }
                                                    break;
                                            }
                                        }
                                        if ($passes_inside_conditions === 'yes'){
                                            $temporary_pass_conditions = 'yes';
                                            break; // if 1 element passed, no need to check all other elements
                                        }
                                    } else if ($element_array[0] === 'category'){
                                        // check all category conditions against it + car total conditions
                                        $passes_inside_conditions = 'yes';
                                        $category_quantity = 0;
                                        foreach($cart->get_cart() as $cart_item2){
                                            if(has_term($element_array[1], 'product_cat', $cart_item2['product_id'])){
                                                $category_quantity += $cart_item2["quantity"]; // add item quantity
                                            }
                                        }
                                        foreach ($conditions as $condition){
                                            $condition_details = explode(';',$condition);
                                            switch ($condition_details[0]){
                                                case 'category_product_quantity':
                                                    switch ($condition_details[1]){
                                                        case 'greater':
                                                            if (! ($category_quantity > intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                        case 'equal':
                                                            if (! ($category_quantity === intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                        case 'smaller':
                                                            if (! ($category_quantity < intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                    }
                                                    break;
                                                case 'cart_total_quantity':
                                                    switch ($condition_details[1]){
                                                        case 'greater':
                                                            if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                        case 'equal':
                                                            if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                        case 'smaller':
                                                            if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                                $passes_inside_conditions = 'no';
                                                                break 3;
                                                            }
                                                        break;
                                                    }
                                                    break;
                                            }
                                        }
                                        if ($passes_inside_conditions === 'yes'){
                                            $temporary_pass_conditions = 'yes';
                                            break; // if 1 element passed, no need to check all other elements
                                        }
                                    }
                                }
                            } //foreach element end

                            if ($temporary_pass_conditions === 'no'){
                                $passconditions = 'no';
                            }

                        } else {
                            // Get rule details
                            $have_discounted_price = NULL;
                            $type = get_post_meta($discount_rule, 'b2bking_rule_what', true);
                            $howmuch = get_post_meta($discount_rule, 'b2bking_rule_howmuch', true);
                            $applies = explode('_',get_post_meta($discount_rule, 'b2bking_rule_applies', true));

                            $category_products_number = 0;
                            $category_products_value = 0;
                            $products_number = 0;
                            $products_value = 0;
                            $cart = WC()->cart;

                            // Check rule is category rule or product rule
                            if ($applies[0] === 'category'){

                                // Calculate number of products in cart of this category AND total price of these products
                                foreach($cart->get_cart() as $cart_item2){
                                    if(has_term($applies[1], 'product_cat', $cart_item2['product_id'])){
                                        $item_qty = $cart_item2["quantity"];// Quantity
                                        if (!isset($cart_item2["line_total"])){
                                            $cart_item2["line_total"] = 0;
                                        }
                                        $item_line_total = $cart_item2["line_total"]; // Item total price (price x quantity)
                                        $category_products_number += $item_qty; // ctotal number of items in cart
                                        $category_products_value += $item_line_total; // calculated total items amount
                                    }
                                }
                            } else if ($applies[0] === 'product') {

                                foreach($cart->get_cart() as $cart_item2){
                                    if(intval($current_product_id) === intval($cart_item2['product_id']) || intval($current_product_id) === intval($cart_item2['variation_id'])){
                                        $item_qty = $cart_item2["quantity"];// Quantity
                                        if (!isset($cart_item2["line_total"])){
                                            $cart_item2["line_total"] = 0;
                                        }
                                        $item_line_total = $cart_item2["line_total"]; // Item total price (price x quantity)
                                        $products_number += $item_qty; // ctotal number of items in cart
                                        $products_value += $item_line_total; // calculated total items amount
                                    }
                                }
                            }

                            // Check discount conditions
                            $passconditions = 'yes';
                            $conditions = get_post_meta($discount_rule, 'b2bking_rule_conditions', true);
                            $conditions = explode('|',$conditions);
                            foreach ($conditions as $condition){
                                $condition_details = explode(';',$condition);
                                switch ($condition_details[0]){
                                    case 'product_quantity':
                                        switch ($condition_details[1]){
                                            case 'greater':
                                                if (! ($products_number > intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                            case 'equal':
                                                if (! ($products_number === intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                            case 'smaller':
                                                if (! ($products_number < intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                        }
                                        break;
                                    
                                    case 'category_product_quantity':
                                        switch ($condition_details[1]){
                                            case 'greater':
                                                if (! ($category_products_number > intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                            case 'equal':
                                                if (! ($category_products_number === intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                            case 'smaller':
                                                if (! ($category_products_number < intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                        }
                                        break;
                                    
                                    case 'cart_total_quantity':
                                        switch ($condition_details[1]){
                                            case 'greater':
                                                if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                            case 'equal':
                                                if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                            case 'smaller':
                                                if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                    $passconditions = 'no';
                                                    break 3;
                                                }
                                            break;
                                        }
                                        break;
                                }
                            }
                        }


                        // Rule passed conditions, so it applies. Calculate discounted price
                        if ($passconditions === 'yes'){
                            if ($have_discounted_price === NULL){
                                $have_discounted_price = 'yes';
                            }
                        } else {
                            // do nothing
                        }
                    } //foreach end

                    if($have_discounted_price !== NULL){

                        $price = $cart_item['data']->get_sale_price(); // get sale price

                        // add WOOCS compatibility
                        if (class_exists('WOOCS')) {
                            global $WOOCS;
                            $currrent = $WOOCS->current_currency;
                            if ($currrent != $WOOCS->default_currency) {
                                $currencies = $WOOCS->get_currencies();
                                $rate = $currencies[$currrent]['rate'];
                                $price = $price / ($rate);
                            }
                        }
                        
                        if ($price !== NULL && $price !== ''){
                            $cart_item['data']->set_price( $price ); // Set the sale price
                            if ($cart_item['variation_id'] !== 0 && $cart_item['variation_id'] !== NULL){
                                $product_id_set = $cart_item['variation_id'];
                            } else {
                                $product_id_set = $cart_item['product_id'];
                            }

                            set_transient('b2bking_user_'.$user_id.'_product_'.$product_id_set.'_custom_set_price', $price);

                        }
                    } else {
                        // do nothing
                    }

                }
            }
        }

        public static function b2bking_dynamic_rule_discount_display_dynamic_price_in_cart_item( $price, $cart_item, $cart_item_key){

            if (isset($_POST['_inline_edit'])){
                return $price;
            }
            if (isset($_REQUEST['bulk_edit'])){
                return $price;
            }

            // integration with woo chained products
            if (isset($cart_item['chained_item_of'])){
                return $price;
            }
            if (isset($cart_item['sample-product'])){
                return $price;
            }

            if (isset($cart_item['wc_sc_product_source'])){
                return $price;
            }

            if (apply_filters('b2bking_disallow_b2b_cart_bundle_pricing', true)){

                if (isset($cart_item['woosb_parent_id']) || isset($cart_item['bundled_by']) || isset($cart_item['b2bking_is_offer_child'])){
                    return $price;
                }
            }
            // Get current product

            // skip offers
            $offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
            $offer_id = apply_filters('b2bking_get_offer_product_id', $offer_id, $cart_item['product_id']);
            if (intval($cart_item['product_id']) === intval($offer_id) || intval($cart_item['product_id']) === 3225464){ //3225464 is deprecated
                return $price;
            }
            
            if (isset($cart_item['variation_id']) && intval($cart_item['variation_id']) !== 0){
                $current_product_id = $cart_item['variation_id'];
                $product = wc_get_product($current_product_id);
            } else {
                $current_product_id = $cart_item['product_id'];
                $product = wc_get_product($current_product_id);
            }

            // 1) Get all rules and check if any rules apply to the product
            $rules_that_apply_to_product = get_transient('b2bking_discount_everywhere_rules_apply_'.$current_product_id);
            if (!$rules_that_apply_to_product){

                $rules_that_apply = array();
                $discount_everywhere_rules_option = get_option('b2bking_have_discount_everywhere_rules_list_ids', '');
                if (!empty($discount_everywhere_rules_option)){
                    $discount_everywhere_rules_v2_ids = explode(',',$discount_everywhere_rules_option);
                }

                foreach ($discount_everywhere_rules_v2_ids as $rule_id){
                    $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                    if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                        array_push($rules_that_apply, $rule_id);
                    } else if ($applies === 'multiple_options'){
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            array_push($rules_that_apply, $rule_id);
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                    break;
                                }
                            }
                        }
                        
                    } else if (explode('_', $applies)[0] === 'category'){
                        // check category
                        $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                        $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                        if (in_array($applies, $current_product_belongsto_array)){
                            array_push($rules_that_apply, $rule_id);
                        }
                    }  else if ($applies === 'excluding_multiple_options'){
                        // check that current product is not in list
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);

                        $product_is_excluded = 'no';
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            $product_is_excluded = 'yes';
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                    break;
                                }
                            }
                        }
                        if ($product_is_excluded === 'no'){
                            // product is not excluded, therefore rule applies
                            array_push($rules_that_apply, $rule_id);
                        }
                    }
                }

                set_transient('b2bking_discount_everywhere_rules_apply_'.$current_product_id,$rules_that_apply);
                $rules_that_apply_to_product = $rules_that_apply;
            }
            // 2) If no rules apply for product, set transient for current user to empty array
            if (empty($rules_that_apply_to_product)){
                set_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id(), array());
            } else {
                // if transient does not already exist
                if (!get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id())){

                    // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                    $rules_that_apply_to_user = array();
                    $user_id = get_current_user_id();
                    $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                    if ($account_type === 'subaccount'){
                        // for all intents and purposes set current user as the subaccount parent
                        $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                        $user_id = $parent_user_id;
                    }

                    $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                    $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                    foreach ($rules_that_apply_to_product as $rule_id){
                        $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                        // first check guest users
                        if ($user_id === 0){
                            if ($who === 'user_0'){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else if ($who === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                $multiple_options_array = explode(',',$multiple_options);
                                if (in_array('user_0',$multiple_options_array)){
                                    array_push($rules_that_apply_to_user, $rule_id);
                                }
                            }
                        } else {
                            // user is not guest
                            if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else {
                                if ($user_is_b2b !== 'yes'){
                                    // user is b2c
                                    if ($who === 'everyone_registered_b2c'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {    
                                    // user is b2b
                                    if ($who === 'everyone_registered_b2b'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'group_'.$user_group){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // either an empty array or an array with rules
                    set_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                }
            }

            // 5. If there are no rules that apply to the product, check if this product is a variation and if 
            // there are any parent rules
            $post_parent_id = wp_get_post_parent_id($current_product_id);
            if (!get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id())){
                if ($post_parent_id !== 0){
                    // check if there are parent rules
                    $current_product_id = $post_parent_id;

                    // based on code above
                    // 1) Get all rules and check if any rules apply to the product
                    $rules_that_apply_to_product = get_transient('b2bking_discount_everywhere_parent_rules_apply_'.$current_product_id);
                    if (!$rules_that_apply_to_product){

                        $rules_that_apply = array();
                        $discount_everywhere_rules_option = get_option('b2bking_have_discount_everywhere_rules_list_ids', '');
                        if (!empty($discount_everywhere_rules_option)){
                            $discount_everywhere_rules_v2_ids = explode(',',$discount_everywhere_rules_option);
                        }

                        foreach ($discount_everywhere_rules_v2_ids as $rule_id){
                            $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                            if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                                array_push($rules_that_apply, $rule_id);
                            } else if ($applies === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            array_push($rules_that_apply, $rule_id);
                                            break;
                                        }
                                    }
                                }
                                
                            } else if (explode('_', $applies)[0] === 'category'){
                                // check category
                                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                if (in_array($applies, $current_product_belongsto_array)){
                                    array_push($rules_that_apply, $rule_id);
                                }
                            } else if ($applies === 'excluding_multiple_options'){
                                // check that current product is not in list
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);

                                $product_is_excluded = 'no';
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            $product_is_excluded = 'yes';
                                            break;
                                        }
                                    }
                                }
                                if ($product_is_excluded === 'no'){
                                    // product is not excluded, therefore rule applies
                                    array_push($rules_that_apply, $rule_id);
                                }
                            }
                        }

                        set_transient('b2bking_discount_everywhere_parent_rules_apply_'.$current_product_id,$rules_that_apply);
                        $rules_that_apply_to_product = $rules_that_apply;
                    }
                    // 2) If no rules apply for product, set transient for current user to empty array
                    if (empty($rules_that_apply_to_product)){
                        set_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id(), array());
                    } else {
                        // if transient does not already exist
                        if (!get_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id())){

                            // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                            $rules_that_apply_to_user = array();
                            $user_id = get_current_user_id();
                            $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                            if ($account_type === 'subaccount'){
                                // for all intents and purposes set current user as the subaccount parent
                                $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                                $user_id = $parent_user_id;
                            }

                            $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                            $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                            foreach ($rules_that_apply_to_product as $rule_id){
                                $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                                // first check guest users
                                if ($user_id === 0){
                                    if ($who === 'user_0'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('user_0',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {
                                    // user is not guest
                                    if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else {
                                        if ($user_is_b2b !== 'yes'){
                                            // user is b2c
                                            if ($who === 'everyone_registered_b2c'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        } else {    
                                            // user is b2b
                                            if ($who === 'everyone_registered_b2b'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'group_'.$user_group){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $current_product_id = $product->get_id();
                            // either an empty array or an array with rules
                            set_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                        }
                    }

                }   
            }
            
            $discount_everywhere_rules = get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id());
            $discount_everywhere_parent_rules = get_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id());

            if (empty($discount_everywhere_rules)){
                $discount_rules = $discount_everywhere_parent_rules;
                $current_product_categories = wc_get_product_term_ids( $post_parent_id, 'product_cat' );
                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                // add the product to the array to search for all relevant rules
                array_push($current_product_belongsto_array, 'product_'.$post_parent_id);
            } else {
                $discount_rules = $discount_everywhere_rules;
                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                // add the product to the array to search for all relevant rules
                array_push($current_product_belongsto_array, 'product_'.$current_product_id);
            }

            if (empty($discount_rules)){
                $discount_rules = array();
            }

            $regular_price = floatval($product->get_regular_price());

            // if multiple discount rules apply, give the smallest price to the user
            $have_discounted_price = NULL;
            $smallest_discounted_price = 0;

            foreach ($discount_rules as $discount_rule){
                // Get rule details
                $type = get_post_meta($discount_rule, 'b2bking_rule_what', true);
                $howmuch = get_post_meta($discount_rule, 'b2bking_rule_howmuch', true);
                $applies = explode('_',get_post_meta($discount_rule, 'b2bking_rule_applies', true));
                $rule_multiple_options = get_post_meta($discount_rule, 'b2bking_rule_applies_multiple_options', true);
                $rule_multiple_options_array = explode(',',$rule_multiple_options);
                $cart = WC()->cart;
                // Get conditions
                $passconditions = 'yes';
                $conditions = get_post_meta($discount_rule, 'b2bking_rule_conditions', true);
                $conditions = explode('|',$conditions);

                if ($applies[0] === 'excluding'){
                    // check that current product is not in excluded list
                    $product_is_excluded = 'no';
                    foreach ($rule_multiple_options_array as $excluded_option){
                        if ('product_'.$current_product_id === $excluded_option){
                            $product_is_excluded = 'yes';
                            break;
                        } else {
                            // check categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if ($item_category === $excluded_option){
                                    $product_is_excluded = 'yes';
                                    break 2;
                                }
                            }
                        }
                    }
                    if ($product_is_excluded === 'no'){
                        // go forward with discount, check conditions
                        foreach ($conditions as $condition){
                            $condition_details = explode(';',$condition);
                            switch ($condition_details[0]){
                                case 'cart_total_value':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($cart->cart_contents_total > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($cart->cart_contents_total === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($cart->cart_contents_total < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                                
                                case 'cart_total_quantity':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                            }
                        }
                    }
                } else if ($applies[0] === 'multiple'){ // if rule is multiple products / categories rule
                    $temporary_pass_conditions = 'no'; // if at least 1 element passes, change to yes
                    // for each element that applies to the product and is part of the rule, check if at least 1 passes the conditions
                    foreach($current_product_belongsto_array as $element){
                        if(in_array($element, $rule_multiple_options_array)){
                            $element_array = explode('_', $element);
                            // if element is product or if element is category
                            if ($element_array[0] === 'product'){
                                $passes_inside_conditions = 'yes';
                                $product_quantity = 0;

                                if(is_object($cart)) {
                                    foreach($cart->get_cart() as $cart_item2){
                                        if(intval($element_array[1]) === intval($cart_item2['product_id']) || intval($element_array[1]) === intval($cart_item2['variation_id'])){
                                            $product_quantity = $cart_item2["quantity"];// Quantity
                                            break;
                                        }
                                    }
                                }
                                // check all product conditions against it
                                foreach ($conditions as $condition){
                                    $condition_details = explode(';',$condition);
                                    switch ($condition_details[0]){
                                        case 'product_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($product_quantity > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($product_quantity === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($product_quantity < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                        case 'cart_total_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                    }
                                }
                                if ($passes_inside_conditions === 'yes'){
                                    $temporary_pass_conditions = 'yes';
                                    break; // if 1 element passed, no need to check all other elements
                                }
                            } else if ($element_array[0] === 'category'){
                                // check all category conditions against it + car total conditions
                                $passes_inside_conditions = 'yes';
                                $category_quantity = 0;

                                if(is_object($cart)) {
                                    foreach($cart->get_cart() as $cart_item2){
                                        if(has_term($element_array[1], 'product_cat', $cart_item2['product_id'])){
                                            $category_quantity += $cart_item2["quantity"]; // add item quantity
                                        }
                                    }
                                }
                                foreach ($conditions as $condition){
                                    $condition_details = explode(';',$condition);
                                    switch ($condition_details[0]){
                                        case 'category_product_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($category_quantity > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($category_quantity === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($category_quantity < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                        case 'cart_total_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                    }
                                }
                                if ($passes_inside_conditions === 'yes'){
                                    $temporary_pass_conditions = 'yes';
                                    break; // if 1 element passed, no need to check all other elements
                                }
                            }
                        }
                    } //foreach element end

                    if ($temporary_pass_conditions === 'no'){
                        $passconditions = 'no';
                    }

                } else {
                    // Get rule details
                    $have_discounted_price = NULL;
                    $type = get_post_meta($discount_rule, 'b2bking_rule_what', true);
                    $howmuch = get_post_meta($discount_rule, 'b2bking_rule_howmuch', true);
                    $applies = explode('_',get_post_meta($discount_rule, 'b2bking_rule_applies', true));

                    $category_products_number = 0;
                    $category_products_value = 0;
                    $products_number = 0;
                    $products_value = 0;
                    $cart = WC()->cart;

                    // Check rule is category rule or product rule
                    if ($applies[0] === 'category'){

                        // Calculate number of products in cart of this category AND total price of these products
                        if(is_object($cart)) {
                            foreach($cart->get_cart() as $cart_item2){
                                if(has_term($applies[1], 'product_cat', $cart_item2['product_id'])){
                                    $item_qty = $cart_item2["quantity"];// Quantity
                                    if (!isset($cart_item2["line_total"])){
                                        $cart_item2["line_total"] = 0;
                                    }
                                    $item_line_total = $cart_item2["line_total"]; // Item total price (price x quantity)
                                    $category_products_number += $item_qty; // ctotal number of items in cart
                                    $category_products_value += $item_line_total; // calculated total items amount
                                }
                            }
                        }
                    } else if ($applies[0] === 'product') {

                        if(is_object($cart)) {
                            foreach($cart->get_cart() as $cart_item2){
                                if(intval($current_product_id) === intval($cart_item2['product_id']) || intval($current_product_id) === intval($cart_item2['variation_id'])){
                                    $item_qty = $cart_item2["quantity"];// Quantity
                                    if (!isset($cart_item2["line_total"])){
                                        $cart_item2["line_total"] = 0;
                                    }
                                    $item_line_total = $cart_item2["line_total"]; // Item total price (price x quantity)
                                    $products_number += $item_qty; // ctotal number of items in cart
                                    $products_value += $item_line_total; // calculated total items amount
                                }
                            }
                        }
                    }

                    // Check discount conditions
                    $passconditions = 'yes';
                    $conditions = get_post_meta($discount_rule, 'b2bking_rule_conditions', true);
                    $conditions = explode('|',$conditions);
                    foreach ($conditions as $condition){
                        $condition_details = explode(';',$condition);
                        switch ($condition_details[0]){
                            case 'product_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($products_number > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($products_number === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($products_number < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                            
                            case 'category_product_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($category_products_number > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($category_products_number === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($category_products_number < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                            
                            case 'cart_total_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                        }
                    }
                }


                // Rule passed conditions, so it applies. Calculate discounted price
                if ($passconditions === 'yes'){
                    if ($have_discounted_price === NULL){
                        $have_discounted_price = 'yes';
                    }
                } else {
                    // do nothing
                }
            } //foreach end

            if($have_discounted_price !== NULL){

                if (!defined('B2BKING_DIR') && get_option('b2bking_main_active', 'no') === 'no'){
                    
                    $discount_price = b2bking()->b2bking_wc_get_price_to_display( $product, array( 'price' => $cart_item['data']->get_sale_price() ) ); // get sale price
                    
                    if ($discount_price !== NULL && $discount_price !== ''){
                        $price = wc_price(round($discount_price,2)); 
                    }

                } else {
                    // not sure why here, error
                }
            } else {
                // do nothing
            }
            return $price;
        }

        public static function b2bking_dynamic_rule_discount_display_dynamic_sale_badge($text, $post, $product){
            // Check product and get discount text, if any
            // Get current product
            $current_product_id = $product->get_id();

            // 1) Get all rules and check if any rules apply to the product
            $rules_that_apply_to_product = get_transient('b2bking_discount_everywhere_rules_apply_'.$current_product_id);
            if (!$rules_that_apply_to_product){

                $rules_that_apply = array();
                $discount_everywhere_rules_option = get_option('b2bking_have_discount_everywhere_rules_list_ids', '');
                if (!empty($discount_everywhere_rules_option)){
                    $discount_everywhere_rules_v2_ids = explode(',',$discount_everywhere_rules_option);
                }

                foreach ($discount_everywhere_rules_v2_ids as $rule_id){
                    $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                    if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                        array_push($rules_that_apply, $rule_id);
                    } else if ($applies === 'multiple_options'){
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            array_push($rules_that_apply, $rule_id);
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                    break;
                                }
                            }
                        }
                        
                    } else if (explode('_', $applies)[0] === 'category'){
                        // check category
                        $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                        $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                        if (in_array($applies, $current_product_belongsto_array)){
                            array_push($rules_that_apply, $rule_id);
                        }
                    } else if ($applies === 'excluding_multiple_options'){
                        // check that current product is not in list
                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                        $multiple_options_array = explode(',', $multiple_options);

                        $product_is_excluded = 'no';
                        if (in_array('product_'.$current_product_id, $multiple_options_array)){
                            $product_is_excluded = 'yes';
                        } else {
                            // try categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if (in_array($item_category, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                    break;
                                }
                            }
                        }
                        if ($product_is_excluded === 'no'){
                            // product is not excluded, therefore rule applies
                            array_push($rules_that_apply, $rule_id);
                        }
                    }
                }

                set_transient('b2bking_discount_everywhere_rules_apply_'.$current_product_id,$rules_that_apply);
                $rules_that_apply_to_product = $rules_that_apply;
            }
            // 2) If no rules apply for product, set transient for current user to empty array
            if (empty($rules_that_apply_to_product)){
                set_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id(), array());
            } else {
                // if transient does not already exist
                if (!get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id())){

                    // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                    $rules_that_apply_to_user = array();
                    $user_id = get_current_user_id();
                    $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                    if ($account_type === 'subaccount'){
                        // for all intents and purposes set current user as the subaccount parent
                        $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                        $user_id = $parent_user_id;
                    }

                    $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                    $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                    foreach ($rules_that_apply_to_product as $rule_id){
                        $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                        // first check guest users
                        if ($user_id === 0){
                            if ($who === 'user_0'){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else if ($who === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                $multiple_options_array = explode(',',$multiple_options);
                                if (in_array('user_0',$multiple_options_array)){
                                    array_push($rules_that_apply_to_user, $rule_id);
                                }
                            }
                        } else {
                            // user is not guest
                            if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                array_push($rules_that_apply_to_user, $rule_id);
                            } else {
                                if ($user_is_b2b !== 'yes'){
                                    // user is b2c
                                    if ($who === 'everyone_registered_b2c'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {    
                                    // user is b2b
                                    if ($who === 'everyone_registered_b2b'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'group_'.$user_group){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // either an empty array or an array with rules
                    set_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                }
            }

            // 5. If there are no rules that apply to the product, check if this product is a variation and if 
            // there are any parent rules
            $post_parent_id = wp_get_post_parent_id($current_product_id);
            if (!get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id())){
                if ($post_parent_id !== 0){
                    // check if there are parent rules
                    $current_product_id = $post_parent_id;

                    // based on code above
                    // 1) Get all rules and check if any rules apply to the product
                    $rules_that_apply_to_product = get_transient('b2bking_discount_everywhere_parent_rules_apply_'.$current_product_id);
                    if (!$rules_that_apply_to_product){

                        $rules_that_apply = array();
                        $discount_everywhere_rules_option = get_option('b2bking_have_discount_everywhere_rules_list_ids', '');
                        if (!empty($discount_everywhere_rules_option)){
                            $discount_everywhere_rules_v2_ids = explode(',',$discount_everywhere_rules_option);
                        }

                        foreach ($discount_everywhere_rules_v2_ids as $rule_id){
                            $applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
                            if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
                                array_push($rules_that_apply, $rule_id);
                            } else if ($applies === 'multiple_options'){
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    array_push($rules_that_apply, $rule_id);
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            array_push($rules_that_apply, $rule_id);
                                            break;
                                        }
                                    }
                                }
                                
                            } else if (explode('_', $applies)[0] === 'category'){
                                // check category
                                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                if (in_array($applies, $current_product_belongsto_array)){
                                    array_push($rules_that_apply, $rule_id);
                                }
                            } else if ($applies === 'excluding_multiple_options'){
                                // check that current product is not in list
                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
                                $multiple_options_array = explode(',', $multiple_options);

                                $product_is_excluded = 'no';
                                if (in_array('product_'.$current_product_id, $multiple_options_array)){
                                    $product_is_excluded = 'yes';
                                } else {
                                    // try categories
                                    $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                                    $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                                    foreach ($current_product_belongsto_array as $item_category){
                                        if (in_array($item_category, $multiple_options_array)){
                                            $product_is_excluded = 'yes';
                                            break;
                                        }
                                    }
                                }
                                if ($product_is_excluded === 'no'){
                                    // product is not excluded, therefore rule applies
                                    array_push($rules_that_apply, $rule_id);
                                }
                            }
                        }

                        set_transient('b2bking_discount_everywhere_parent_rules_apply_'.$current_product_id,$rules_that_apply);
                        $rules_that_apply_to_product = $rules_that_apply;
                    }
                    // 2) If no rules apply for product, set transient for current user to empty array
                    if (empty($rules_that_apply_to_product)){
                        set_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id(), array());
                    } else {
                        // if transient does not already exist
                        if (!get_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id())){

                            // 3) If some rules apply, for each rule, check if it applies to the current user and build array.
                            $rules_that_apply_to_user = array();
                            $user_id = get_current_user_id();
                            $account_type = get_user_meta($user_id,'b2bking_account_type', true);
                            if ($account_type === 'subaccount'){
                                // for all intents and purposes set current user as the subaccount parent
                                $parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
                                $user_id = $parent_user_id;
                            }

                            $user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
                            $user_group = get_user_meta($user_id,'b2bking_customergroup', true);

                            foreach ($rules_that_apply_to_product as $rule_id){
                                $who = get_post_meta($rule_id,'b2bking_rule_who', true);
                                // first check guest users
                                if ($user_id === 0){
                                    if ($who === 'user_0'){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else if ($who === 'multiple_options'){
                                        $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                        $multiple_options_array = explode(',',$multiple_options);
                                        if (in_array('user_0',$multiple_options_array)){
                                            array_push($rules_that_apply_to_user, $rule_id);
                                        }
                                    }
                                } else {
                                    // user is not guest
                                    if ($who === 'everyone_registered' || $who === 'user_'.$user_id){
                                        array_push($rules_that_apply_to_user, $rule_id);
                                    } else {
                                        if ($user_is_b2b !== 'yes'){
                                            // user is b2c
                                            if ($who === 'everyone_registered_b2c'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2c',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        } else {    
                                            // user is b2b
                                            if ($who === 'everyone_registered_b2b'){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'group_'.$user_group){
                                                array_push($rules_that_apply_to_user, $rule_id);
                                            } else if ($who === 'multiple_options'){
                                                $multiple_options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
                                                $multiple_options_array = explode(',',$multiple_options);
                                                if (in_array('everyone_registered_b2b',$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('user_'.$user_id,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                } else if (in_array('group_'.$user_group,$multiple_options_array)){
                                                    array_push($rules_that_apply_to_user, $rule_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $current_product_id = $product->get_id();
                            // either an empty array or an array with rules
                            set_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id(), $rules_that_apply_to_user);
                        }
                    }

                }   
            }
            
            $discount_everywhere_rules = get_transient('b2bking_discount_everywhere_'.$current_product_id.'_'.get_current_user_id());
            $discount_everywhere_parent_rules = get_transient('b2bking_discount_everywhere_parent_'.$current_product_id.'_'.get_current_user_id());

            if (empty($discount_everywhere_rules)){
                $discount_rules = $discount_everywhere_parent_rules;
                $current_product_categories = wc_get_product_term_ids( $post_parent_id, 'product_cat' );
                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                // add the product to the array to search for all relevant rules
                array_push($current_product_belongsto_array, 'product_'.$post_parent_id);
            } else {
                $discount_rules = $discount_everywhere_rules;
                $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
                // add the product to the array to search for all relevant rules
                array_push($current_product_belongsto_array, 'product_'.$current_product_id);
            }

            if (empty($discount_rules)){
                $discount_rules = array();
            }

            $regular_price = floatval($product->get_regular_price());

            // if multiple discount rules apply, give the smallest price to the user
            $have_discounted_price = NULL;
            $smallest_discount_name = '';
            $smallest_discounted_price = 0;

            foreach ($discount_rules as $discount_rule){
                // Get rule details
                $type = get_post_meta($discount_rule, 'b2bking_rule_what', true);
                $howmuch = get_post_meta($discount_rule, 'b2bking_rule_howmuch', true);
                $applies = explode('_',get_post_meta($discount_rule, 'b2bking_rule_applies', true));
                $rule_multiple_options = get_post_meta($discount_rule, 'b2bking_rule_applies_multiple_options', true);
                $rule_multiple_options_array = explode(',',$rule_multiple_options);
                $cart = WC()->cart;
                $discount_name = get_post_meta($discount_rule, 'b2bking_rule_discountname', true);
                // Get conditions
                $passconditions = 'yes';
                $conditions = get_post_meta($discount_rule, 'b2bking_rule_conditions', true);
                $conditions = explode('|',$conditions);

                if ($applies[0] === 'excluding'){
                    // check that current product is not in excluded list
                    $product_is_excluded = 'no';
                    foreach ($rule_multiple_options_array as $excluded_option){
                        if ('product_'.$current_product_id === $excluded_option){
                            $product_is_excluded = 'yes';
                            break;
                        } else {
                            // check categories
                            $current_product_categories = wc_get_product_term_ids( $current_product_id, 'product_cat' );
                            $current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);

                            foreach ($current_product_belongsto_array as $item_category){
                                if ($item_category === $excluded_option){
                                    $product_is_excluded = 'yes';
                                    break 2;
                                }
                            }
                        }
                    }
                    if ($product_is_excluded === 'no'){
                        // go forward with discount, check conditions
                        foreach ($conditions as $condition){
                            $condition_details = explode(';',$condition);
                            switch ($condition_details[0]){
                                case 'cart_total_value':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($cart->cart_contents_total > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($cart->cart_contents_total === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($cart->cart_contents_total < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                                
                                case 'cart_total_quantity':
                                    switch ($condition_details[1]){
                                        case 'greater':
                                            if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'equal':
                                            if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                        case 'smaller':
                                            if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                $passconditions = 'no';
                                                break 3;
                                            }
                                        break;
                                    }
                                    break;
                            }
                        }
                    }
                } else if ($applies[0] === 'multiple'){ // if rule is multiple products / categories rule
                    $temporary_pass_conditions = 'no'; // if at least 1 element passes, change to yes
                    // for each element that applies to the product and is part of the rule, check if at least 1 passes the conditions
                    foreach($current_product_belongsto_array as $element){
                        if(in_array($element, $rule_multiple_options_array)){
                            $element_array = explode('_', $element);
                            // if element is product or if element is category
                            if ($element_array[0] === 'product'){
                                $passes_inside_conditions = 'yes';
                                $product_quantity = 0;
                                if(is_object($cart)) {
                                    foreach($cart->get_cart() as $cart_item){
                                        if(intval($element_array[1]) === intval($cart_item['product_id']) || intval($element_array[1]) === intval($cart_item['variation_id'])){
                                            $product_quantity = $cart_item["quantity"];// Quantity
                                            break;
                                        }
                                    }
                                }
                                // check all product conditions against it
                                foreach ($conditions as $condition){
                                    $condition_details = explode(';',$condition);
                                    switch ($condition_details[0]){
                                        case 'product_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($product_quantity > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($product_quantity === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($product_quantity < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                        case 'cart_total_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                    }
                                }
                                if ($passes_inside_conditions === 'yes'){
                                    $temporary_pass_conditions = 'yes';
                                    break; // if 1 element passed, no need to check all other elements
                                }
                            } else if ($element_array[0] === 'category'){
                                // check all category conditions against it + car total conditions
                                $passes_inside_conditions = 'yes';
                                $category_quantity = 0;

                                if(is_object($cart)) {
                                    foreach($cart->get_cart() as $cart_item){
                                        if(has_term($element_array[1], 'product_cat', $cart_item['product_id'])){
                                            $category_quantity += $cart_item["quantity"]; // add item quantity
                                        }
                                    }
                                }
                                foreach ($conditions as $condition){
                                    $condition_details = explode(';',$condition);
                                    switch ($condition_details[0]){
                                        case 'category_product_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($category_quantity > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($category_quantity === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($category_quantity < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                        case 'cart_total_quantity':
                                            switch ($condition_details[1]){
                                                case 'greater':
                                                    if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'equal':
                                                    if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                                case 'smaller':
                                                    if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                                        $passes_inside_conditions = 'no';
                                                        break 3;
                                                    }
                                                break;
                                            }
                                            break;
                                    }
                                }
                                if ($passes_inside_conditions === 'yes'){
                                    $temporary_pass_conditions = 'yes';
                                    break; // if 1 element passed, no need to check all other elements
                                }
                            }
                        }
                    } //foreach element end

                    if ($temporary_pass_conditions === 'no'){
                        $passconditions = 'no';
                    }

                } else {
                    // Get rule details
                    $type = get_post_meta($discount_rule, 'b2bking_rule_what', true);
                    $howmuch = get_post_meta($discount_rule, 'b2bking_rule_howmuch', true);
                    $applies = explode('_',get_post_meta($discount_rule, 'b2bking_rule_applies', true));
                    $discount_name = get_post_meta($discount_rule, 'b2bking_rule_discountname', true);

                    $category_products_number = 0;
                    $category_products_value = 0;
                    $products_number = 0;
                    $products_value = 0;
                    $cart = WC()->cart;

                    // Check rule is category rule or product rule
                    if ($applies[0] === 'category'){

                        // Calculate number of products in cart of this category AND total price of these products
                        if(is_object($cart)) {
                            foreach($cart->get_cart() as $cart_item){
                                if(has_term($applies[1], 'product_cat', $cart_item['product_id'])){
                                    $item_qty = $cart_item["quantity"];// Quantity
                                    if (!isset($cart_item["line_total"])){
                                        $cart_item["line_total"] = 0;
                                    }
                                    $item_line_total = $cart_item["line_total"]; // Item total price (price x quantity)
                                    $category_products_number += $item_qty; // ctotal number of items in cart
                                    $category_products_value += $item_line_total; // calculated total items amount
                                }
                            }
                        }
                    } else if ($applies[0] === 'product') {

                        if(is_object($cart)) {
                            foreach($cart->get_cart() as $cart_item){
                                if(intval($current_product_id) === intval($cart_item['product_id']) || intval($current_product_id) === intval($cart_item['variation_id'])){
                                    $item_qty = $cart_item["quantity"];// Quantity
                                    if (!isset($cart_item["line_total"])){
                                        $cart_item["line_total"] = 0;
                                    }
                                    $item_line_total = $cart_item["line_total"]; // Item total price (price x quantity)
                                    $products_number += $item_qty; // ctotal number of items in cart
                                    $products_value += $item_line_total; // calculated total items amount
                                }
                            }
                        }
                    }

                    // Check discount conditions
                    $passconditions = 'yes';
                    $conditions = get_post_meta($discount_rule, 'b2bking_rule_conditions', true);
                    $conditions = explode('|',$conditions);
                    foreach ($conditions as $condition){
                        $condition_details = explode(';',$condition);
                        switch ($condition_details[0]){
                            case 'product_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($products_number > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($products_number === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($products_number < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                            
                            case 'category_product_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($category_products_number > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($category_products_number === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($category_products_number < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                            
                            case 'cart_total_quantity':
                                switch ($condition_details[1]){
                                    case 'greater':
                                        if (! ($cart->cart_contents_count > intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'equal':
                                        if (! ($cart->cart_contents_count === intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                    case 'smaller':
                                        if (! ($cart->cart_contents_count < intval($condition_details[2]))){
                                            $passconditions = 'no';
                                            break 3;
                                        }
                                    break;
                                }
                                break;
                        }
                    }
                }

                // Rule passed conditions, so it applies. Calculate discounted price
                if ($passconditions === 'yes'){
                    if ($have_discounted_price === NULL){
                        $have_discounted_price = 'yes';
                        $smallest_discount_name = $discount_name;
                        // calculate discount and regular price based on $howmuch and discount type
                        if ($type === 'discount_amount'){
                            $smallest_discounted_price = floatval($regular_price - $howmuch);
                        } else if ($type === 'discount_percentage') {
                            $smallest_discounted_price = floatval($regular_price - ($howmuch/100 * $regular_price));
                        }
                    } else {
                        if ($type === 'discount_amount'){
                            $temporary_discounted_price = floatval($regular_price - $howmuch);
                        } else if ($type === 'discount_percentage') {
                            $temporary_discounted_price = floatval($regular_price - ($howmuch/100 * $regular_price));
                        }
                        if ($temporary_discounted_price < $smallest_discounted_price){
                            $smallest_discounted_price = $temporary_discounted_price;
                            $smallest_discount_name = $discount_name;
                        }   
                    }
                } else {
                    // do nothing
                }
            } //foreach end

            if($have_discounted_price !== NULL && $smallest_discount_name !== '' && $smallest_discount_name !== NULL){
                return str_replace( __( 'Sale!', 'woocommerce' ), $smallest_discount_name, $text );
            } else {
                return $text;
            }
   
        }
        

    
}
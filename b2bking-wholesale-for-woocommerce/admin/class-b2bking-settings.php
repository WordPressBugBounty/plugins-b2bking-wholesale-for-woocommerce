<?php

/**
*
* PHP File that handles Settings management
*
*/

class B2bkingcore_Settings {

	public function register_all_settings() {

		// Set plugin status (Disabled, B2B & B2C, or B2B)
		register_setting('b2bking', 'b2bking_plugin_status_setting');

		// Current Tab Setting - Misc setting, hidden, only saves the last opened menu tab
		register_setting( 'b2bking', 'b2bking_current_tab_setting');
		add_settings_field('b2bking_current_tab_setting', '', array($this, 'b2bking_current_tab_setting_content'), 'b2bking', 'b2bking_hiddensettings');

		/* Registration Settings */
		add_settings_section('b2bking_registration_settings_section', '',	'',	'b2bking');
		add_settings_section('b2bking_registration_settings_section_advanced', '',	'',	'b2bking');

		// Registration Role Dropdown enable (enabled by default)
		register_setting('b2bking', 'b2bking_registration_roles_dropdown_setting');
		add_settings_field('b2bking_registration_roles_dropdown_setting', $this->b2bking_registration_roles_dropdown_setting_description(), array($this,'b2bking_registration_roles_dropdown_setting_content'), 'b2bking', 'b2bking_registration_settings_section');
		
		// Require approval for all users' registration
		register_setting('b2bking', 'b2bking_approval_required_all_users_setting');
		add_settings_field('b2bking_approval_required_all_users_setting', $this->b2bking_approval_required_all_users_setting_description(), array($this,'b2bking_approval_required_all_users_setting_content'), 'b2bking', 'b2bking_registration_settings_section_advanced');

		// Enable custom registration in checkout 
		register_setting('b2bking', 'b2bking_registration_at_checkout_setting');
		add_settings_field('b2bking_registration_at_checkout_setting', $this->b2bking_registration_at_checkout_setting_description(), array($this,'b2bking_registration_at_checkout_setting_content'), 'b2bking', 'b2bking_registration_settings_section_advanced');
		
	}

	// This function remembers the current tab as a hidden input setting. When the page loads, it goes to the saved tab
	function b2bking_current_tab_setting_content(){
		echo '
		 <input type="hidden" id="b2bking_current_tab_setting_input" name="b2bking_current_tab_setting" value="'.esc_attr(get_option( 'b2bking_current_tab_setting', 'accessrestriction' )).'">
		';
	}

	function b2bking_registration_roles_dropdown_setting_description(){
		ob_start();

		$tip = esc_html__('Shows user type dropdown on WooCommerce registration pages.','b2bking').'<br><img class="b2bking_tooltip_img" src="https://kingsplugins.com/wp-content/uploads/2024/08/enabledropdown2.jpeg">';

		echo esc_html__('Enable Dropdown & Fields', 'b2bking').'&nbsp;'.wc_help_tip($tip, false);
		echo '<p class="b2bking_setting_description">'.esc_html__('Show user type dropdown and custom fields on WooCommerce registration pages','b2bking').'</p>';
		return ob_get_clean();
	}


	function b2bking_registration_roles_dropdown_setting_content(){
		echo '
		<div class="ui toggle checkbox">
		  <input type="checkbox" name="b2bking_registration_roles_dropdown_setting" value="1" '.checked(1,get_option( 'b2bking_registration_roles_dropdown_setting', 1 ), false).'">
		</div>
		';
	}

	function b2bking_approval_required_all_users_setting_description(){
		ob_start();

		$tip = esc_html__('Approval can be controlled for each registration option in B2BKing -> Registration Roles. This setting is useful in cases where registration is done through external pages or plugins that do not show B2BKing registration options.','b2bking');

		echo esc_html__('Manual Approval for All', 'b2bking').'&nbsp;'.wc_help_tip($tip, false);
		echo '<p class="b2bking_setting_description">'.esc_html__('Require manual approval for all user registrations, including for B2C users.','b2bking').'</p>';
		return ob_get_clean();
	}

	function b2bking_approval_required_all_users_setting_content(){
		echo '
		<div class="ui toggle checkbox">
		  <input type="checkbox" name="b2bking_approval_required_all_users_setting" value="1" '.checked(1,get_option( 'b2bking_approval_required_all_users_setting', 0 ), false).'">
		</div>
		';	
	}

	function b2bking_registration_at_checkout_setting_description(){
		ob_start();
		echo esc_html__('Registration at Checkout', 'b2bking');
		echo '<p class="b2bking_setting_description">'.esc_html__('Adds B2BKing registration options to checkout registration.','b2bking').'</p>';
		return ob_get_clean();
	}

	function b2bking_registration_at_checkout_setting_content(){
		echo '
		<div class="ui toggle checkbox">
		  <input type="checkbox" name="b2bking_registration_at_checkout_setting" value="1" '.checked(1,get_option( 'b2bking_registration_at_checkout_setting', 0 ), false).'">
		</div>
		';	
	}


		
	public function render_settings_page_content() {
		?>

		<!-- Admin Menu Page Content -->
		<form id="b2bking_admin_form" method="POST" action="options.php">
			<?php settings_fields('b2bking'); ?>
			<?php do_settings_fields( 'b2bking', 'b2bking_hiddensettings' ); ?>

			<div id="b2bking_admin_wrapper" >

				<!-- Admin Menu Tabs --> 
				<div id="b2bking_admin_menu" class="ui labeled stackable large vertical menu attached">
					<img id="b2bking_menu_logo" src="<?php echo plugins_url('../includes/assets/images/logo.png', __FILE__); ?>">
					<a class="green item <?php echo $this->b2bking_isactivetab('mainsettings'); ?>" data-tab="mainsettings">
						<i class=" icon"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M10 4H14C18.42 4 22 7.58 22 12C22 16.42 18.42 20 14 20H10C5.58 20 2 16.42 2 12C2 7.58 5.58 4 10 4Z" stroke="#f3f4f5" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M14 16C16.2091 16 18 14.2091 18 12C18 9.79086 16.2091 8 14 8C11.7909 8 10 9.79086 10 12C10 14.2091 11.7909 16 14 16Z" stroke="#f3f4f5" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg></i>						<div class="header"><?php esc_html_e('Main Settings','b2bking'); ?></div>
						<span class="b2bking_menu_description"><?php esc_html_e('Primary plugin settings','b2bking'); ?></span>
					</a>
					<a class="green item <?php echo $this->b2bking_isactivetab('registration'); ?>" data-tab="registration">
						<i class=" icon"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M14 18L16 20L22 14M4 20V19C4 16.2386 6.23858 14 9 14H12.75M15 7C15 9.20914 13.2091 11 11 11C8.79086 11 7 9.20914 7 7C7 4.79086 8.79086 3 11 3C13.2091 3 15 4.79086 15 7Z" stroke="#f3f4f5" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg></i>						<div class="header"><?php esc_html_e('Registration','b2bking'); ?></div>
						<span class="b2bking_menu_description"><?php esc_html_e('Registration settings','b2bking'); ?></span>
					</a>
					<a class="green item <?php echo $this->b2bking_isactivetab('upgrade'); ?>" data-tab="upgrade">
						<i class=" icon"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g clip-path="url(#clip0_303_1103)"> <path d="M17 12C15.8757 13.1243 11.5 16.5 11.5 16.5M17 12C20 9 20 7 20 4C17 4 15 4 12 7M17 12L17.4752 14.376C17.8031 16.0153 17.29 17.71 16.1078 18.8922L14.9374 20.0626C14.4676 20.5324 13.6788 20.4219 13.3562 19.8411L11.5 16.5M12 7L7.5 12.5M12 7L9.62395 6.52479C7.98465 6.19693 6.28996 6.71004 5.10784 7.89216L3.93743 9.06257C3.46765 9.53235 3.57813 10.3212 4.1589 10.6438L7.5 12.5M7.5 12.5L11.5 16.5M15.5 8.50049L14.5 9.50049M3 21L6.39223 20.3216C7.32708 20.1346 8 19.3138 8 18.3604L8 18C8 16.8954 7.10457 16 6 16L5.63961 16C4.68625 16 3.86542 16.6729 3.67845 17.6078L3 21Z" stroke="#f3f4f5" stroke-width="0.984" stroke-linecap="round" stroke-linejoin="round"></path> </g> <defs> <clipPath id="clip0_303_1103"> <rect width="24" height="24" fill="white"></rect> </clipPath> </defs> </g></svg></i>
						<div class="header"><?php esc_html_e('Upgrade to B2BKing','b2bking'); ?></div>
						<span class="b2bking_menu_description"><?php esc_html_e('Get 137+ Premium Features','b2bking'); ?></span>
					</a>
				
				</div>
			
				<!-- Admin Menu Tabs Content--> 
				<div id="b2bking_tabs_wrapper">

					<!-- Main Settings Tab--> 
					<div class="ui bottom attached tab segment <?php echo $this->b2bking_isactivetab('mainsettings'); ?>" data-tab="mainsettings">
						<div class="b2bking_attached_content_wrapper">
							<h2 class="ui block header" style="display: flex;">
								<i class="icon"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="position: relative;bottom: 2px;width: 36px !important;
								"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M10 4H14C18.42 4 22 7.58 22 12C22 16.42 18.42 20 14 20H10C5.58 20 2 16.42 2 12C2 7.58 5.58 4 10 4Z" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M14 16C16.2091 16 18 14.2091 18 12C18 9.79086 16.2091 8 14 8C11.7909 8 10 9.79086 10 12C10 14.2091 11.7909 16 14 16Z" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg></i>
								<div class="content">
									<?php esc_html_e('Main Settings','b2bking'); ?>
								</div>
							</h2>
							<table class="form-table">
								<?php
								if (!defined('B2BKINGLABEL_DIR')){
									?>
									<div class="ui ignored info icon message">
								      <div class="content">
								        <h3 class="header">	<?php esc_html_e('Documentation','b2bking'); ?></h3>
								        <p>
								            <?php
								            $learn_more_link = '<a href="https://woocommerce-b2b-plugin.com/docs/plugin-status/" target="_blank" style="color: #295b6b"><span style="color: #295b6b">&nbsp;<strong style="text-decoration: underline;">%s</strong></span></a>';

								            $translated_text = sprintf(
								                /* translators: %s: Linked text for 'Learn more' */
								                esc_html__('In B2B shop mode, plugin features are visible to all users. In B2B & B2C hybrid mode, features are available only to designated B2B users. %s', 'b2bking'),
								                sprintf($learn_more_link, esc_html__('Learn more', 'b2bking'))
								            );

								            echo wp_kses($translated_text, array(
								                'a' => array(
								                    'href' => array(),
								                    'target' => array(),
								                    'style' => array()
								                ),
								                'span' => array(
								                    'style' => array()
								                ),
								                'strong' => array(
								                    'style' => array()
								                )
								            ));
								            ?>
								        </p>
								      </div>
								      <svg viewBox="0 0 24 24" fill="#333" xmlns="http://www.w3.org/2000/svg" style="width: 50px;"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M6.27103 2.11151C5.46135 2.21816 5.03258 2.41324 4.72718 2.71244C4.42179 3.01165 4.22268 3.43172 4.11382 4.225C4.00176 5.04159 4 6.12387 4 7.67568V16.2442C4.38867 15.9781 4.82674 15.7756 5.29899 15.6517C5.82716 15.513 6.44305 15.5132 7.34563 15.5135L20 15.5135V7.67568C20 6.12387 19.9982 5.04159 19.8862 4.22499C19.7773 3.43172 19.5782 3.01165 19.2728 2.71244C18.9674 2.41324 18.5387 2.21816 17.729 2.11151C16.8955 2.00172 15.7908 2 14.2069 2H9.7931C8.2092 2 7.10452 2.00172 6.27103 2.11151ZM6.75862 6.59459C6.75862 6.1468 7.12914 5.78378 7.58621 5.78378H16.4138C16.8709 5.78378 17.2414 6.1468 17.2414 6.59459C17.2414 7.04239 16.8709 7.40541 16.4138 7.40541H7.58621C7.12914 7.40541 6.75862 7.04239 6.75862 6.59459ZM7.58621 9.56757C7.12914 9.56757 6.75862 9.93058 6.75862 10.3784C6.75862 10.8262 7.12914 11.1892 7.58621 11.1892H13.1034C13.5605 11.1892 13.931 10.8262 13.931 10.3784C13.931 9.93058 13.5605 9.56757 13.1034 9.56757H7.58621Z" fill="#6aacc0"></path> <path d="M8.68965 17.1351H7.47341C6.39395 17.1351 6.01657 17.1421 5.72738 17.218C4.93365 17.4264 4.30088 18.0044 4.02952 18.7558C4.0463 19.1382 4.07259 19.4746 4.11382 19.775C4.22268 20.5683 4.42179 20.9884 4.72718 21.2876C5.03258 21.5868 5.46135 21.7818 6.27103 21.8885C7.10452 21.9983 8.2092 22 9.7931 22H14.2069C15.7908 22 16.8955 21.9983 17.729 21.8885C18.5387 21.7818 18.9674 21.5868 19.2728 21.2876C19.5782 20.9884 19.7773 20.5683 19.8862 19.775C19.9776 19.1088 19.9956 18.2657 19.9991 17.1351H13.1034V20.1417C13.1034 20.4397 13.1034 20.5886 12.9988 20.6488C12.8941 20.709 12.751 20.6424 12.4647 20.5092L11.0939 19.8713C10.9971 19.8262 10.9486 19.8037 10.8966 19.8037C10.8445 19.8037 10.796 19.8262 10.6992 19.8713L9.32842 20.5092C9.04213 20.6424 8.89899 20.709 8.79432 20.6488C8.68965 20.5886 8.68965 20.4397 8.68965 20.1417V17.1351Z" fill="#6aacc0"></path></g></svg>
									</div>

									<?php
								}
								?>
								<div class="ui large form b2bking_plugin_status_container">
								  <div class="inline fields">
								    <label><?php esc_html_e('Plugin Status','b2bking'); ?></label>&nbsp;&nbsp;
								    <div class="field">
								      <div class="ui checkbox">
								        <input type="radio" tabindex="0" class="hidden" name="b2bking_plugin_status_setting" value="hybrid" <?php checked('hybrid',get_option( 'b2bking_plugin_status_setting', 'b2b' ), true); ?>">
								        <label><i class="shopping basket icon"></i>&nbsp;<?php esc_html_e('B2B & B2C Hybrid Shop','b2bking'); ?>&nbsp;&nbsp;</label>
								      </div>
								    </div>
								    <div class="field">
								      <div class="ui checkbox">
								        <input type="radio" tabindex="0" class="hidden" name="b2bking_plugin_status_setting" value="b2b" <?php checked('b2b',get_option( 'b2bking_plugin_status_setting', 'b2b' ), true); ?>">
								        <label><i class="dolly icon"></i>&nbsp;<?php esc_html_e('B2B Shop','b2bking'); ?>&nbsp;&nbsp;</label>
								      </div>
								    </div>
								    
								  </div>
								</div>
							</table>
								
						</div>
					</div>


					<!-- Registration Tab--> 
					<div class="ui bottom attached tab segment b2bking_registrationsettings_tab <?php echo $this->b2bking_isactivetab('registration'); ?>" data-tab="registration">
						<div class="b2bking_attached_content_wrapper">
							<h2 class="ui block header">
								<i class="users icon"></i>
								<div class="content">
									<?php esc_html_e('Registration','b2bking'); ?>
									<div class="sub header">
										<?php esc_html_e('User registration settings','b2bking'); ?>
									</div>
								</div>
							</h2>
							<table class="form-table">
								<div class="ui ignored info icon message" style="position: relative; top: 3px;">
							      <div class="content">
							        <h3 class="header">	<?php esc_html_e('Documentation','b2bking'); ?></h3>
								    <p>
								        <?php								        
								        $extended_registration_link = '<a href="https://woocommerce-b2b-plugin.com/docs/extended-registration-and-custom-fields/" target="_blank" style="color: #295b6b"><span style="color: #295b6b"><strong style="text-decoration: underline;">%s</strong></span></a>';

								        $translated_text = sprintf(
								            /* translators: %1$s: Linked text for 'completely separate B2B and B2C registration', %2$s: Linked text for 'extended registration and custom fields' */
								            esc_html__('Learn about the %1$s and custom fields functionalities.', 'b2bking'),
								            sprintf($extended_registration_link, esc_html__('extended registration', 'b2bking'))
								        );

								        echo wp_kses($translated_text, array(
								            'a' => array(
								                'href' => array(),
								                'target' => array(),
								                'style' => array()
								            ),
								            'span' => array(
								                'style' => array()
								            ),
								            'strong' => array(
								                'style' => array()
								            )
								        ));
								        ?>
								    </p>

							      </div>
							      <svg viewBox="0 0 24 24" fill="#333" xmlns="http://www.w3.org/2000/svg" style="width: 50px;"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M6.27103 2.11151C5.46135 2.21816 5.03258 2.41324 4.72718 2.71244C4.42179 3.01165 4.22268 3.43172 4.11382 4.225C4.00176 5.04159 4 6.12387 4 7.67568V16.2442C4.38867 15.9781 4.82674 15.7756 5.29899 15.6517C5.82716 15.513 6.44305 15.5132 7.34563 15.5135L20 15.5135V7.67568C20 6.12387 19.9982 5.04159 19.8862 4.22499C19.7773 3.43172 19.5782 3.01165 19.2728 2.71244C18.9674 2.41324 18.5387 2.21816 17.729 2.11151C16.8955 2.00172 15.7908 2 14.2069 2H9.7931C8.2092 2 7.10452 2.00172 6.27103 2.11151ZM6.75862 6.59459C6.75862 6.1468 7.12914 5.78378 7.58621 5.78378H16.4138C16.8709 5.78378 17.2414 6.1468 17.2414 6.59459C17.2414 7.04239 16.8709 7.40541 16.4138 7.40541H7.58621C7.12914 7.40541 6.75862 7.04239 6.75862 6.59459ZM7.58621 9.56757C7.12914 9.56757 6.75862 9.93058 6.75862 10.3784C6.75862 10.8262 7.12914 11.1892 7.58621 11.1892H13.1034C13.5605 11.1892 13.931 10.8262 13.931 10.3784C13.931 9.93058 13.5605 9.56757 13.1034 9.56757H7.58621Z" fill="#6aacc0"></path> <path d="M8.68965 17.1351H7.47341C6.39395 17.1351 6.01657 17.1421 5.72738 17.218C4.93365 17.4264 4.30088 18.0044 4.02952 18.7558C4.0463 19.1382 4.07259 19.4746 4.11382 19.775C4.22268 20.5683 4.42179 20.9884 4.72718 21.2876C5.03258 21.5868 5.46135 21.7818 6.27103 21.8885C7.10452 21.9983 8.2092 22 9.7931 22H14.2069C15.7908 22 16.8955 21.9983 17.729 21.8885C18.5387 21.7818 18.9674 21.5868 19.2728 21.2876C19.5782 20.9884 19.7773 20.5683 19.8862 19.775C19.9776 19.1088 19.9956 18.2657 19.9991 17.1351H13.1034V20.1417C13.1034 20.4397 13.1034 20.5886 12.9988 20.6488C12.8941 20.709 12.751 20.6424 12.4647 20.5092L11.0939 19.8713C10.9971 19.8262 10.9486 19.8037 10.8966 19.8037C10.8445 19.8037 10.796 19.8262 10.6992 19.8713L9.32842 20.5092C9.04213 20.6424 8.89899 20.709 8.79432 20.6488C8.68965 20.5886 8.68965 20.4397 8.68965 20.1417V17.1351Z" fill="#6aacc0"></path></g></svg>
								</div>
							
								<?php do_settings_fields( 'b2bking', 'b2bking_registration_settings_section' ); ?>
							</table>

							<table class="form-table">
								<h3 class="ui block header">
									<i class="wrench icon"></i>
									<?php esc_html_e('Advanced Registration Settings','b2bking'); ?>
								</h3>
								<?php do_settings_fields( 'b2bking', 'b2bking_registration_settings_section_advanced' ); ?>
							</table>

						</div>
					</div>

					<!-- Upgrade Tab--> 
					<div class="ui bottom attached tab segment <?php echo $this->b2bking_isactivetab('upgrade'); ?>" data-tab="upgrade">
						<div class="b2bking_attached_content_wrapper">
							<h2 class="ui block header">
								<i class="dolly icon"></i>
								<div class="content">
									<?php esc_html_e('Get B2BKing - The Ultimate WooCommerce B2B & Wholesale Plugin','b2bking'); ?>
									<div class="sub header">
										<?php esc_html_e('Get the #1 wholesale solution for WooCommerce','b2bking'); ?>
									</div>
								</div>
							</h2>
							<a class="b2bking_button_color_gold b2bking_button_color_gold_main" href="https://woocommerce-b2b-plugin.com" target="_blank"><button class="ui orange large button" type="button"><i class="dolly icon"></i>&nbsp;<?php esc_html_e('Get B2BKing for the Premium Wholesale / B2B Store Experience','b2bking'); ?></button></a>
							<div class="ui icon message">
								<i class="briefcase icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Extended Business Registration','b2bking'); ?>
									</div>
									<p><?php esc_html_e('Business registration with multiple roles dropdown, 9 types of custom fields, manual and automatic approval, VAT nr support, VIES API validation and much more. ','b2bking'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="alternate list icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Wholesale Bulk Order Form','b2bking'); ?>
									</div>
									<p>
										<?php esc_html_e('Bulk order form with AJAX instant search, Search by SKU, search in product description, variation support, save form as purchase list and more! ','b2bking'); ?>
									</p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="shopping basket icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('B2B & B2C Hybrid Store Mode','b2bking'); ?>
										
									</div>
									<p>
										<?php esc_html_e('Dedicated modes for pure B2B, and B2B&B2C hybrid stores. B2B features hidden for B2C. Separate registrations. Manual approval for B2B but automatic for B2C.','b2bking'); ?>
									
									</p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="boxes icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Offers & Bundles','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Create product bundles or make negotiated offers for either groups or specific users. Set offer visibility.','b2bking'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="comments icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Conversations & Messaging','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Built-in messaging system between admin and b2b users for negotiation, quotes and inquiries.','b2bking'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="tags icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Request a Quote','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Receive custom quote requests in a normal store or operate exclusively via quote','b2bking'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="th list icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Purchase Lists','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Allow B2B users to save lists, re-order, replenish stock, add lists to cart, etc.'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="users icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Subaccounts (Multiple Buyers on Account)','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Support multiple buyers inside a company, with permissions setup for who can view products, place orders, message, etc.'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="file alternate outline icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Invoice Payment Gateway','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Allow users to select an invoice option at checkout, so you can manually finalise the transaction later','b2bking'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="money bill alternate outline icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Tax Exemptions','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Exempt business users from paying tax or make them pay tax in cart, depending on your required legal setup'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="dollar sign icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Wholesale Prices','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Powerful pricing setups with prices by group, by user, complex discounts, dynamic rules, minimum orders, free shipping rules, etc.'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="eye slash icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Product and Category Visibility','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Hide or show products to each user or group, to create unique personalized catalogs for your users.'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="retweet icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Complex Dynamic Rules','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Set complex condition-based rules for: discounts, minimum and maximum orders, free shipping, add custom taxes, zero tax products, tax exemptions, hidden prices (by product / category), etc.'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="eye icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Display Price Including or Excluding Tax','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Show or hide tax, or set tax to be payable in cart. Alternatively set "withholding tax" (Ritenuta D\'acconto), for tax display only','b2bking'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="rocket icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('High Performance Caching & Usage Detection','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Caching and smart algorithms ensure the plugin and your site run smoothly.'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="file excel icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('CSV Import and Export Tools','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Download price lists and set prices in excel and then import them into WooComemrce through B2BKing.'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="level up alternate icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Tiered Pricing','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Through pricing conditions you can set tiered prices and bulk discounts: e.g. 1 price for 1-10 items, but another price for 10-100 items'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="building icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Groups Management','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Organize users into groups and apply all rules, pricing, discount and visibility conditions by group'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="paint brush icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Full Theme Compatibility','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Compatible with any WooCommerce theme and any store. Tested with hundreds of themes including the most popular themes in the market.'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="lock icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('Private Store','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('Even more features: hide b2b portal option, replace prices with quote requests, hide prices by category or by product, etc.'); ?></p>
							  	</div>
							</div>
							<div class="ui icon message">
								<i class="tasks icon b2bking_upgrade_icon"></i>
								<div class="content">
									<div class="header">
										<?php esc_html_e('137+ Features and Functionalities','b2bking'); ?>
										
									</div>
									<p><?php esc_html_e('B2BKing has many other features and we are constantly adding even more! From shortcodes that restrict content by group or user, to quantity discounts, to extended re-ordering system, multisite support, b2b customers panel, and many others, B2BKing is the ultimate solution for your b2b or wholesale store.'); ?></p>
							  	</div>
							</div>
							<a class="b2bking_button_color_gold" href="https://woocommerce-b2b-plugin.com" target="_blank"><button class="ui orange large button" type="button"><i class="dolly icon"></i>&nbsp;<?php esc_html_e('Get Started!','b2bking'); ?></button></a>
							</div>

						</div>
					</div>

					
				

					<!-- Offers Tab--> 
					<div class="ui bottom attached tab segment <?php echo $this->b2bking_isactivetab('offers'); ?>" data-tab="offers">
						<div class="b2bking_attached_content_wrapper">
							<div class="b2bking_upgrade_premium_settings">
								<i class="mdi mdi-rocket"></i>
								<?php
								esc_html_e('Upgrade to B2BKing Premium to Unlock','b2bking');
								echo '<br />';
								esc_html_e('Offers & Product Bundles','b2bking');
								?>
							</div>

						</div>
					</div>

					<!-- Language Tab--> 
					<div class="ui bottom attached tab segment <?php echo $this->b2bking_isactivetab('language'); ?>" data-tab="language">
						<div class="b2bking_attached_content_wrapper">
							<div class="b2bking_upgrade_premium_settings">
								<i class="mdi mdi-rocket"></i>
								<?php
								esc_html_e('Upgrade to B2BKing Premium to Unlock','b2bking');
								echo '<br />';
								esc_html_e('Language and Text Settings','b2bking');
								?>
							</div>
							
						</div>
					</div>

					<!-- Performance Tab--> 
					<div class="ui bottom attached tab segment <?php echo $this->b2bking_isactivetab('performance'); ?>" data-tab="performance">
						<div class="b2bking_attached_content_wrapper">
							<div class="b2bking_upgrade_premium_settings">
								<i class="mdi mdi-rocket"></i>
								<?php
								esc_html_e('Upgrade to B2BKing Premium to Unlock','b2bking');
								echo '<br />';
								esc_html_e('Components & Speed','b2bking');
								?>
							</div>
							
						</div>
					</div>

					<!-- Other settings tab--> 
					<div class="ui bottom attached tab segment <?php echo $this->b2bking_isactivetab('othersettings'); ?>" data-tab="othersettings">
						<div class="b2bking_attached_content_wrapper">
							<div class="b2bking_upgrade_premium_settings">
								<i class="mdi mdi-rocket"></i>
								<?php
								esc_html_e('Upgrade to B2BKing Premium to Unlock','b2bking');
								echo '<br />';
								esc_html_e('Advanced Settings / Bulk Order Form / Multisite / etc.','b2bking');
								?>
							</div>
							
					
						</div>
					</div>
				</div>
			</div>

			<br>
			<input type="submit" name="submit" id="b2bking-admin-submit" class="ui primary button" value="Save Settings">
		</form>

		<?php
	}

	function b2bking_isactivetab($tab){
		$gototab = get_option( 'b2bking_current_tab_setting', 'mainsettings' );
		if (empty($gototab) || !($gototab)){
			$gototab = 'mainsettings';
		}
		if ($tab === $gototab){
			return 'active';
		} 
	}

}
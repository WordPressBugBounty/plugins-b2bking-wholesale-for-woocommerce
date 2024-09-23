/**
*
* JavaScript file that has global action in the admin menu
*
*/
(function($){

	"use strict";

	$( document ).ready(function() {

		var buttonclass = 'b2bking-btn-gray';
		var last_reports_loading_time = 0;
		var latest_reports_data = '';
		var reportstable;
		var rules_in_queue = 0;
		var last_toast_success_fired_time = Date.now();
		var page_slug = b2bking.pageslug;
		var old_page_slug = 'none';
		var availablepages = ['groups', 'b2c_users', 'logged_out_users', 'customers', 'dashboard', 'reports', 'orderform', 'tieredpricing', 'businessregistration' ,'profeatures'];
		var availablepagesb2bking = ['b2bking_groups', 'b2bking_b2c_users', 'b2bking_logged_out_users', 'b2bking_customers', 'b2bking_dashboard', 'b2bking_reports', 'b2bking_orderform', 'b2bking_tieredpricing', 'b2bking_businessregistration', 'b2bking_profeatures'];
		var availableposts = ['b2bking_conversation', 'b2bking_offer', 'b2bking_rule', 'b2bking_custom_role', 'b2bking_custom_field','b2bking_group','b2bking_grule'];

		// if current menu open is b2bking, hide the update count
		if (jQuery('.toplevel_page_b2bkingcore').hasClass('wp-menu-open')){
			// remove
			jQuery('.toplevel_page_b2bkingcore').find('.wp-menu-name .update-plugins').css('display','none');
		}

		function page_switch(switchto, userid = 0){

			// 1. Replace current page content with loader
			// add overlay and loader
			jQuery('#wpbody-content').prepend('<div id="b2bking_admin_overlay"><img class="b2bking_loader_icon_button" src="'+b2bking.loaderurl+'">');

			// 2. Get page content
			var datavar = {
		        action: 'b2bking_get_page_content',
		        security: b2bking.security,
		        page: switchto,
		        userid: userid
		    };

			jQuery.post(ajaxurl, datavar, function(response){

				// the current one becomes the old one
				old_page_slug = page_slug;

				// response is the HTML content of the page
				// if page is dashboard, drop preloader first
				let preloaderhtml = '<div class="b2bkingpreloader"><img class="b2bking_loader_icon_button" src="'+b2bking.loaderurl+'"></div>';
				if (switchto === 'dashboard' || switchto === 'reports'){
					jQuery('#wpbody-content').html(preloaderhtml);
					setTimeout(function(){
						jQuery('.b2bkingpreloader').after(response);

					}, 10);

				} else {
					jQuery('#wpbody-content').html(response);

				}

				// if pageslug contains user, remove it
				let slugtemp = page_slug.split('&user=')[0];			

				// remove current page slug and set new page slug
				jQuery('body').removeClass('admin_page_'+slugtemp);
				jQuery('body').removeClass('b2bking_page_'+slugtemp);
				jQuery('body').removeClass('toplevel_page_b2bkingcore');

				if (b2bking.whitelabelname !== 'b2bking'){
					jQuery('body').removeClass(b2bking.whitelabelname+'_page_'+slugtemp);
				}

				jQuery('#b2bking_admin_style-css').prop('disabled', true);
				jQuery('#b2bking_style-css').prop('disabled', true);
				jQuery('#semantic-css').prop('disabled', true);


				// remove post php because page switch can never switch to a single post yet
				jQuery('body').removeClass('post-php');

				let new_page_slug = 'b2bking_'+switchto;

				// if post type, remove 'b2bking_edit'
				if (new_page_slug.startsWith('b2bking_edit')){
					new_page_slug = new_page_slug.split('b2bking_edit_')[1];	
				}

				if (userid!== 0){
					new_page_slug = new_page_slug+'&user='+userid;
				}

				// link difference between pages and posts
				let newlocation = window.location.href.replace('='+page_slug,'='+new_page_slug);

				// removed paged
				newlocation = newlocation.split('&paged=')[0];
				newlocation = newlocation.split('&action=edit')[0];

				if (newlocation.includes('admin.php?page=') && availableposts.includes(new_page_slug)){
					newlocation = newlocation.replace('admin.php?page=','edit.php?post_type=');
				}

				if (newlocation.includes('edit.php?post_type=') && ( availablepages.includes(new_page_slug) || availablepagesb2bking.includes(new_page_slug)) ){
					newlocation = newlocation.replace('edit.php?post_type=','admin.php?page=');
				}

				if (newlocation.includes('post.php?post=') && ( availablepages.includes(new_page_slug) || availablepagesb2bking.includes(new_page_slug)) ){
					newlocation = newlocation.replace('post.php?post=','admin.php?page=');
				}

				if (newlocation.includes('post.php?post=') && availableposts.includes(new_page_slug)){
					newlocation = newlocation.replace('post.php?post=','edit.php?post_type=');
				}

				// set page url
				window.history.pushState('b2bking_'+switchto, '', newlocation);

				page_slug = new_page_slug;

				// if pageslug contains user, remove it
				slugtemp = page_slug.split('&user=')[0];
				jQuery('body').addClass('b2bking_page_'+slugtemp);

				jQuery('body').removeClass('b2bking_page_initial');
				jQuery('body').addClass('b2bking_page_not_initial');

				if (b2bking.whitelabelname !== 'b2bking'){
					jQuery('body').addClass(b2bking.whitelabelname+'_page_'+slugtemp);
				}

				// expand b2bking menu if not already open (expanded)
				$('.toplevel_page_b2bkingcore').removeClass('wp-not-current-submenu');
				$('.toplevel_page_b2bkingcore').addClass('wp-has-current-submenu wp-menu-open');

				
				// initialize JS
				initialize_elements();

				initialize_on_b2bkingcore_page_load();

				$('#b2bking-admin-submit, #footer-thankyou, #footer-upgrade').remove();

				// remove browser 'Leave Page?' warning
				jQuery(window).off('beforeunload');



			});

		}

		if (window.location.href.indexOf("b2bking") > -1) {
			jQuery('body').addClass('b2bking_page_initial');
		}

		initialize_elements();

		$('body').on('click','.b2bking_admin_groups_main_container_main_row_right_box_first', function(e){
			if (b2bking.ajax_pages_load === 'enabled'){
				e.preventDefault();
				page_switch('b2c_users');
			}
		});

		$('body').on('click','.b2bking_admin_groups_main_container_main_row_right_box_second', function(e){
			if (b2bking.ajax_pages_load === 'enabled'){
				e.preventDefault();
				page_switch('logged_out_users');
			}
		});

		$('body').on('click','.b2bking_above_top_title_button_right_button, .b2bking_go_groups', function(e){
			if (b2bking.ajax_pages_load === 'enabled'){
				e.preventDefault();
				page_switch('groups');
			}
		});

		var linkstext = '<a href="'+b2bking.groupspage+'" class="page-title-action b2bking_go_groups">'+b2bking.goback_text+'</a>';
		setTimeout(function(){
				$('.edit-php.post-type-b2bking_group .page-title-action').after(linkstext);
		}, 650);

		var linkstext3 = '<a href="'+b2bking.b2bgroups_link+'" class="page-title-action b2bking_go_edit_groups">'+b2bking.goback_text+'</a>';
		setTimeout(function(){
				$('.post-php.post-type-b2bking_group .page-title-action').after(linkstext3);
		}, 650);

		// GROUP RULES BACK BUTTON
		var linkstext4 = '<a href="'+b2bking.group_rules_link+'" class="page-title-action b2bking_go_edit_grules">'+b2bking.goback_text+'</a>';
		setTimeout(function(){
				$('.post-php.post-type-b2bking_grule .page-title-action').after(linkstext4);
		}, 650);

		$('body').on('click','.b2bking_go_edit_grules', function(e){
			if (b2bking.ajax_pages_load === 'enabled'){
				e.preventDefault();
				page_switch('edit_b2bking_grule');

				setTimeout(function(){
					$('.post-php.post-type-b2bking_grule .page-title-action').after(linkstext4);
				}, 650);
			}
		});

		// settings have changed, you should save them
		jQuery('.toplevel_page_b2bkingcore #b2bking_admin_wrapper input[type="checkbox"], .toplevel_page_b2bkingcore #b2bking_admin_wrapper input[type="radio"], .toplevel_page_b2bkingcore #b2bking_admin_wrapper input[type="color"], .toplevel_page_b2bkingcore #b2bking_admin_wrapper input[type="text"], .toplevel_page_b2bkingcore #b2bking_admin_wrapper select').on('change input', function(){
			jQuery('#b2bking-admin-submit').addClass('active');
			jQuery('#b2bking-admin-submit').val('Click to Save Settings');
		});

		jQuery('body').on('change', '.b2bking_group_payment_shipping_methods_container_element_method_checkbox', function(){
			jQuery('.b2bking_settings_button').addClass('active');
			jQuery('.b2bking_settings_button').text('Click to Save Settings');
		});

		// whitelabel
		var field = document.querySelector('[name="b2bking_whitelabel_pluginname_setting"]');
		if (field !== null && field !== undefined){
			field.addEventListener('keypress', function ( event ) {  
			   var key = event.keyCode;
			    if (key === 32) {
			      event.preventDefault();
			    }
			});
		}
		

		// CONVERSATION BACK BUTTON
		var linkstext5 = '<a href="'+b2bking.conversations_link+'" class="page-title-action b2bking_go_edit_conversations">'+b2bking.goback_text+'</a>';
		setTimeout(function(){
				$('.post-php.post-type-b2bking_conversation .page-title-action').after(linkstext5);
		}, 650);

		$('body').on('click','.b2bking_go_edit_conversations', function(e){
			if (b2bking.ajax_pages_load === 'enabled'){
				e.preventDefault();
				page_switch('edit_b2bking_conversation');

				setTimeout(function(){
					$('.post-php.post-type-b2bking_conversation .page-title-action').after(linkstext5);
				}, 650);
			}
		});

		// OFFER BACK BUTTON
		var linkstext6 = '<a href="'+b2bking.offers_link+'" class="page-title-action b2bking_go_edit_offers">'+b2bking.goback_text+'</a>';
		setTimeout(function(){
				$('.post-php.post-type-b2bking_offer .page-title-action').after(linkstext6);
		}, 650);

		$('body').on('click','.b2bking_go_edit_offers', function(e){
			if (b2bking.ajax_pages_load === 'enabled'){
				e.preventDefault();
				page_switch('edit_b2bking_offer');

				setTimeout(function(){
					$('.post-php.post-type-b2bking_offer .page-title-action').after(linkstext6);
				}, 650);
			}
		});

		// DYNAMIC RULES BACK BUTTON
		var linkstext7 = '<a href="'+b2bking.dynamic_rules_link+'" class="page-title-action b2bking_go_edit_rules">'+b2bking.goback_text+'</a>';
		setTimeout(function(){
				$('.post-php.post-type-b2bking_rule .page-title-action').after(linkstext7);
		}, 650);

		$('body').on('click','.b2bking_go_edit_rules', function(e){
			if (b2bking.ajax_pages_load === 'enabled'){
				e.preventDefault();
				page_switch('edit_b2bking_rule');

				setTimeout(function(){
					$('.post-php.post-type-b2bking_rule .page-title-action').after(linkstext7);
				}, 650);
			}
		});

		// REGISTRATION ROLES BACK BUTTON
		var linkstext8 = '<a href="'+b2bking.roles_link+'" class="page-title-action b2bking_go_edit_roles">'+b2bking.goback_text+'</a>';
		setTimeout(function(){
				$('.post-php.post-type-b2bking_custom_role .page-title-action').after(linkstext8);
		}, 650);

		$('body').on('click','.b2bking_go_edit_roles', function(e){
			if (b2bking.ajax_pages_load === 'enabled'){
				e.preventDefault();
				page_switch('edit_b2bking_custom_role');

				setTimeout(function(){
					$('.post-php.post-type-b2bking_custom_role .page-title-action').after(linkstext8);
				}, 650);
			}
		});

		// REGISTRATION FIELDS BACK BUTTON
		var linkstext9 = '<a href="'+b2bking.roles_link+'" class="page-title-action b2bking_go_edit_fields">'+b2bking.goback_text+'</a>';
		setTimeout(function(){
				$('.post-php.post-type-b2bking_custom_field .page-title-action').after(linkstext9);
		}, 650);

		$('body').on('click','.b2bking_go_edit_fields', function(e){
			if (b2bking.ajax_pages_load === 'enabled'){
				e.preventDefault();
				page_switch('edit_b2bking_custom_field');

				setTimeout(function(){
					$('.post-php.post-type-b2bking_custom_field .page-title-action').after(linkstext9);
				}, 650);
			}
		});

		$('body').on('click','.b2bking_admin_groups_main_container_main_row_left_box, .b2bking_go_edit_groups', function(e){
			if (b2bking.ajax_pages_load === 'enabled'){
				e.preventDefault();
				page_switch('edit_b2bking_group');
				var linkstext2 = '<a href="'+b2bking.groupspage+'" class="page-title-action b2bking_go_groups">'+b2bking.goback_text+'</a>';

				setTimeout(function(){
					$(".page-title-action").after(linkstext2);
				}, 650);
			}
		});

		

		

		function initialize_elements(){

			// Move header to top of page
			jQuery('#wpbody-content').prepend(jQuery('#b2bking_admin_header_bar').detach());

			/* Customers */
			//initialize admin customers table if function exists (we are in the Customers panel)
			if (typeof $('#b2bking_admin_customers_table').DataTable === "function") { 
				if (parseInt(b2bking.b2bking_customers_panel_ajax_setting) !== 1){
					$('#b2bking_admin_customers_table').DataTable({
						"retrieve": true,
			            "language": {
			                "url": b2bking.datatables_folder+b2bking.purchase_lists_language_option+'.json'
			            },
			            dom: 'lBfrtip',
			            buttons: {
			                buttons: [
			                    { extend: 'csvHtml5', className: buttonclass, text: '↓ CSV', exportOptions: { columns: ":visible" } },
			                    { extend: 'pdfHtml5', className: buttonclass, text: '↓ PDF', exportOptions: { columns: ":visible" } },
			                    { extend: 'print', className: buttonclass, text: b2bking.print, exportOptions: { columns: ":visible" } },
			                    { extend: 'colvis', className: buttonclass, text: b2bking.edit_columns },
			                ]
			            },
			            initComplete: function (settings, json) {
		                    // move buttons
		                    jQuery('<span class="b2bking_customers_export_text">Export: </span>').insertBefore('.dt-buttons');
		                }
			        });
				} else {
		       		$('#b2bking_admin_customers_table').DataTable({
		       			"retrieve": true,
		       			"language": {
		       			    "url": b2bking.datatables_folder+b2bking.purchase_lists_language_option+'.json'
		       			},
		       			"processing": true,
		       			"serverSide": true,
		       			"info": true,
		       		    "ajax": {
		       		   		"url": ajaxurl,
		       		   		"type": "POST",
		       		   		"data":{
		       		   			action: 'b2bking_admin_customers_ajax',
		       		   			security: b2bking.security,
		       		   		}
		       		   	},
			            dom: 'lBfrtip',
			            buttons: {
			                buttons: [
			                    { extend: 'csvHtml5', className: buttonclass, text: '↓ CSV', exportOptions: { columns: ":visible" } },
			                    { extend: 'pdfHtml5', className: buttonclass, text: '↓ PDF', exportOptions: { columns: ":visible" } },
			                    { extend: 'print', className: buttonclass, text: b2bking.print, exportOptions: { columns: ":visible" } },
			                    { extend: 'colvis', className: buttonclass, text: b2bking.edit_columns },
			                ]
			            },
			            initComplete: function (settings, json) {
		                    // move buttons
		                    jQuery('<span class="b2bking_customers_export_text">Export: </span>').insertBefore('.dt-buttons');
		                }

		            });
				}
			}

			// Dashboard
			if ($(".b2bkingpreloader").val()!== undefined){
				if (jQuery('#b2bking_admin_dashboard-css').val() === undefined){
					// add it to page
					jQuery('#chartist-css').after('<link rel="stylesheet" id="b2bking_admin_dashboard-css" href="'+b2bking.dashboardstyleurl+'" media="all">');
				}
				jQuery('#b2bking_admin_dashboard-css').prop('disabled', false);

				setTimeout(function(){
					// hide preloader and show page
					$(".b2bkingpreloader").fadeOut();
					$(".b2bking_dashboard_page_wrapper").show();

					if (jQuery('body').hasClass('b2bking_page_b2bking_reports')){
						// reports
					} else {
						// dashboard
						// draw chart
						drawDashboardSalesChart();
						$('#b2bking_dashboard_days_select').change(drawDashboardSalesChart);
					}
					
					//failsafe in case the page did not show, try again in 50 ms
					setTimeout(function(){
						dashboard_failsafe();
					}, 60);	
					setTimeout(function(){
						dashboard_failsafe();
					}, 110);
					setTimeout(function(){
						dashboard_failsafe();
					}, 150);
					setTimeout(function(){
						dashboard_failsafe();
					}, 350);		
					
				}, 35);
				
			} else {
				jQuery('#b2bking_admin_dashboard-css').prop('disabled', true);
			}

			// reports
			// load first chart in reports
			setTimeout(function(){
				$('#b2bking_reports_link_thismonth').click();
			}, 150);

			set_registration_fields_opacity();

			// toolbar
			let toolbarhtml = b2bking.toolbarhtml;
			if ( ($('body').hasClass('post-type-b2bking_rule') && $('body').hasClass('b2bking_page_initial')) || $('body').hasClass('b2bking_page_b2bking_rule') ){
				jQuery(toolbarhtml).insertBefore('.tablenav.top .tablenav-pages');
			}
			if ( ($('body').hasClass('post-type-b2bking_grule') && $('body').hasClass('b2bking_page_initial')) || $('body').hasClass('b2bking_page_b2bking_grule') ){
				jQuery(toolbarhtml).insertBefore('.tablenav.top .tablenav-pages');
			}
			if ( ($('body').hasClass('post-type-b2bking_offer') && $('body').hasClass('b2bking_page_initial')) || $('body').hasClass('b2bking_page_b2bking_offer') ){
				jQuery(toolbarhtml).insertBefore('.tablenav.top .tablenav-pages');
			}
			if ( ($('body').hasClass('post-type-b2bking_quote_field') && $('body').hasClass('b2bking_page_initial')) || $('body').hasClass('b2bking_page_b2bking_quote_field') ){
				jQuery(toolbarhtml).insertBefore('.tablenav.top .tablenav-pages');
			}
			// to add toolbar, activate this code
			/*
			if ( ($('body').hasClass('post-type-b2bking_custom_role') && $('body').hasClass('b2bking_page_initial')) || $('body').hasClass('b2bking_page_b2bking_custom_role') ){
				jQuery(toolbarhtml).insertBefore('.tablenav.top .tablenav-pages');
			}
			if ( ($('body').hasClass('post-type-b2bking_custom_field') && $('body').hasClass('b2bking_page_initial')) || $('body').hasClass('b2bking_page_b2bking_custom_field') ){
				jQuery(toolbarhtml).insertBefore('.tablenav.top .tablenav-pages');
			}
			*/

			// searchbar
			let searchbarhtml = b2bking.searchbarhtml;
			if ( ($('body').hasClass('post-type-b2bking_offer') && $('body').hasClass('b2bking_page_initial')) || $('body').hasClass('b2bking_page_b2bking_offer') ){
				jQuery(searchbarhtml).insertBefore('.tablenav.top .tablenav-pages');
			}
			if ( ($('body').hasClass('post-type-b2bking_conversation') && $('body').hasClass('b2bking_page_initial')) || $('body').hasClass('b2bking_page_b2bking_conversation') ){
				jQuery(searchbarhtml).insertBefore('.tablenav.top .tablenav-pages');
			}
			if ( ($('body').hasClass('post-type-b2bking_rule') && $('body').hasClass('b2bking_page_initial')) || $('body').hasClass('b2bking_page_b2bking_rule') ){
				jQuery(searchbarhtml).insertBefore('.tablenav.top .tablenav-pages');
			}
			/*
			// add registration form shortcodes button
			if ($('body').hasClass('post-type-b2bking_custom_field') && $('body').hasClass('b2bking_page_initial')){
				jQuery('<button id="b2bking_registration_form_shortcode_button" type="button"><span class="dashicons dashicons-id-alt"></span></span> &nbsp;&nbsp;'+b2bking.registration_form_shortcodes_text+'</button>').insertBefore('.tablenav.top .tablenav-pages');
				jQuery('.tablenav-pages').css('display','none');
			} else {
				if ($('body').hasClass('b2bking_page_b2bking_custom_field') && $('body').hasClass('b2bking_page_not_initial')){
					jQuery('<button id="b2bking_registration_form_shortcode_button" type="button"><span class="dashicons dashicons-id-alt"></span> &nbsp;&nbsp;'+b2bking.registration_form_shortcodes_text+'</button>').insertBefore('.tablenav.top .tablenav-pages');
					jQuery('.tablenav-pages').css('display','none');
				}
			}
			if ($('body').hasClass('post-type-b2bking_custom_role') && $('body').hasClass('b2bking_page_initial')){
				jQuery('<button id="b2bking_registration_form_shortcode_button" type="button"><span class="dashicons dashicons-id-alt"></span></span> &nbsp;&nbsp;'+b2bking.registration_form_shortcodes_text+'</button>').insertBefore('.tablenav.top .tablenav-pages');
				jQuery('.tablenav-pages').css('display','none');
			} else {
				if ($('body').hasClass('b2bking_page_b2bking_custom_role') && $('body').hasClass('b2bking_page_not_initial')){
					jQuery('<button id="b2bking_registration_form_shortcode_button" type="button"><span class="dashicons dashicons-id-alt"></span> &nbsp;&nbsp;'+b2bking.registration_form_shortcodes_text+'</button>').insertBefore('.tablenav.top .tablenav-pages');
					jQuery('.tablenav-pages').css('display','none');
				}
			}
			jQuery('<button id="b2bking_registration_form_shortcode_button" type="button"><span class="dashicons dashicons-id-alt"></span></span> &nbsp;&nbsp;'+b2bking.registration_form_shortcodes_text+'</button>').insertAfter('.b2bking_registrationsettings_tab h2.block.header .content');
			jQuery('<button id="b2bking_bulkorder_form_shortcode_button" type="button"><span class="dashicons dashicons-id-alt"></span></span> &nbsp;&nbsp;'+b2bking.bulkorder_form_shortcodes_text+'</button>').insertAfter('.b2bking_bulkordersettings_tab h2.block.header .content');
			*/
		}

		function initialize_on_b2bkingcore_page_load(){
			// run default WP ADMIN JS FILES
			$.ajax({ url: b2bking.inlineeditpostjsurl, dataType: "script", });
			$.ajax({ url: b2bking.commonjsurl, dataType: "script", });

			/* sort drag drop backend */
	 		if (parseInt(jQuery('.post-type-b2bking_custom_field').length) !== 0 || parseInt(jQuery('.b2bking_page_b2bking_custom_field').length) !== 0){
	 			enable_sortable_type('b2bking_custom_field');
	 		}

	 		if (parseInt(jQuery('.post-type-b2bking_custom_role').length) !== 0 || parseInt(jQuery('.b2bking_page_b2bking_custom_role').length) !== 0){
	 			enable_sortable_type('b2bking_custom_role');
	 		}

	 		if (parseInt(jQuery('.post-type-b2bking_quote_field').length) !== 0 || parseInt(jQuery('.b2bking_page_b2bking_quote_field').length) !== 0){
	 			enable_sortable_type('b2bking_quote_field');
	 		}

		}

		function dashboard_failsafe(){
			if ($(".b2bking_dashboard_page_wrapper").css('display') !== 'block'){

				if (jQuery('body').hasClass('b2bking_page_b2bking_reports')){
					// reports
					setTimeout(function(){
						$(".b2bking_dashboard_page_wrapper").show();

					}, 50);	
				} else {
					// dashboard
					setTimeout(function(){
						$(".b2bking_dashboard_page_wrapper").show();
						drawDashboardSalesChart();
					}, 50);	
				}

			}
		}


		function reports_set_chart(preload = 'yes'){

			if (preload === 'yes'){
				show_reports_preloader();
			}

			let customers = jQuery('#b2bking_reports_days_select').val();
			let firstday = jQuery('.b2bking_reports_date_input_from').val();
			let lastday = jQuery('.b2bking_reports_date_input_to').val();

			// if dates are set
			if (firstday !== '' && lastday !== ''){

				// get data
				var datavar = {
		            action: 'b2bking_reports_get_data',
		            security: b2bking.security,
		            customers: customers,
		            firstday: firstday,
		            lastday: lastday,
		        };

				$.post(ajaxurl, datavar, function(response){

					let data = response.split('*');

					let labels = JSON.parse(data[0]);
					let grosssalestotal = JSON.parse(data[1]);
					let netsalestotal = JSON.parse(data[2]);
					let ordernumbers = JSON.parse(data[3]);

					let gross_sales_wc = data[4];
					let net_total_wc = data[5];
					let order_number = data[6];
					let items_purchased = data[7];
					let average_order_value = data[8];
					let refund_amount = data[9];
					let coupons_amount = data[10];
					let shipping_charges = data[11];
					

					$('.b2bking_reports_gross_sales, .b2bking_total_b2b_sales_today').html(gross_sales_wc);
					$('.b2bking_reports_net_sales').html(net_total_wc);
					$('.b2bking_reports_number_orders, .b2bking_number_orders_today').html(order_number);
					$('.b2bking_reports_items_purchased').html(items_purchased);

					$('.b2bking_reports_average_order_value').html(average_order_value);
					$('.b2bking_reports_refund_amount').html(refund_amount);
					$('.b2bking_reports_coupons_amount').html(coupons_amount);
					$('.b2bking_reports_shipping_charges').html(shipping_charges);

					drawReportsSalesChart(labels, netsalestotal, ordernumbers, grosssalestotal);

					// save data for CSV export
					latest_reports_data = data;

				});
			}
		}

		function trigger_update_modal(){
			Swal.fire({
			  title: '<strong>-</strong>',
			  width: 534,
			  html: b2bking.upgradehtml,			    
			  showCloseButton: true,
			  showCancelButton: false,
			  showConfirmButton: false,
			  customClass: {
			    icon: 'b2bking_registration_shortcodes_icon',
			    title: 'b2bking_upgrade_modal_title',
			    container: 'b2bking_upgrade_modal',
			  }
			});
		}

		// add target blank to b2bking upgrade
		$('.b2bking-upgrade-to-premium').attr('target','_blank');

		$('body').on('click', '.b2bking_upgrade_modal_trigger', function(){
			trigger_update_modal();
		});
		
		$('body').on('click', '#b2bking_export_report_button', function(){
			trigger_update_modal();
		});

		function create_download_report(format){
			let data = latest_reports_data;

			let labels = JSON.parse(data[0]);
			let grosssalestotal = JSON.parse(data[1]);
			let netsalestotal = JSON.parse(data[2]);
			let ordernumbers = JSON.parse(data[3]);

			let itemnumbers = JSON.parse(data[12]);
			let refunds = JSON.parse(data[13]);
			let coupons = JSON.parse(data[14]);
			let shipping = JSON.parse(data[15]);

			// show table temporarily
			jQuery('#b2bking_admin_reports_export_table').css('display','');

			// clear existing table info 
			jQuery('#b2bking_admin_reports_export_table tbody tr').remove();

			// build html row to add to exports table
			labels.forEach(function(item, index){
				let row = '<tr>';

				row += '<td>' + item + '</td>';
				row += '<td>' + grosssalestotal[index] + '</td>';
				row += '<td>' + netsalestotal[index] + '</td>';
				row += '<td>' + ordernumbers[index] + '</td>';
				row += '<td>' + itemnumbers[index] + '</td>';
				row += '<td>' + refunds[index] + '</td>';
				row += '<td>' + coupons[index] + '</td>';
				row += '<td>' + shipping[index] + '</td>';

				row += '</tr>';

				jQuery('#b2bking_admin_reports_export_table tbody').append(row);
			});

			// make table into datatables
			let filen = 'report-'+jQuery('.b2bking_reports_date_input_from').val()+'-to-'+jQuery('.b2bking_reports_date_input_to').val();
			var reportstable = jQuery('#b2bking_admin_reports_export_table').DataTable({
				dom: 'Bfrtip',
				buttons: {
				    buttons: [
				        { extend: 'csvHtml5',  text: '↓ CSV', exportOptions: { columns: ":visible" }, filename: filen },
				        { extend: 'pdfHtml5',  text: '↓ PDF', exportOptions: { columns: ":visible" }, filename: filen, title: '' },
				    ]
				}
			});

			

			if (format === 'csv'){
				$('.buttons-csv').click();
			}
			if (format === 'pdf'){
				$('.buttons-pdf').click();
			}

			

			reportstable.destroy();

			// hide table again
			jQuery('#b2bking_admin_reports_export_table').css('display','none');

		}

		$('body').on('click', '.b2bking_reports_link' ,function(){
		   	let quicklink = jQuery(this).prop('hreflang');

		   	if (quicklink === 'thismonth'){
		   		var date = new Date(), y = date.getFullYear(), m = date.getMonth();
		   		var firstDay = new Date(y, m, 1);
		   		var lastDay = new Date(y, m + 1, 0);

		   	}

		   	if (quicklink === 'lastmonth'){
		   		var date = new Date(), y = date.getFullYear(), m = date.getMonth()-1;

		   		var firstDay = new Date(y, m, 1);
		   		var lastDay = new Date(y, m + 1, 0);
		   	}

		   	if (quicklink === 'thisyear'){
		   		var date = new Date(), y = date.getFullYear();
		   		var firstDay = new Date(y, 0, 1);
		   		var lastDay = new Date(y, 11, 31);

		   	}
		   	if (quicklink === 'lastyear'){
		   		var date = new Date(), y = date.getFullYear()-1;
		   		var firstDay = new Date(y, 0, 1);
		   		var lastDay = new Date(y, 11, 31);
		   	}

		   	var day = firstDay.getDate();
		   	if (day<10) { day="0"+day;}

		   	var month = firstDay.getMonth()+1;
		   	if (month<10) { month="0"+month;}

		   	jQuery('.b2bking_reports_date_input_from').val(firstDay.getFullYear()+'-'+month+'-'+day);

		   	var day = lastDay.getDate();
		   	if (day<10) { day="0"+day;}

		   	var month = lastDay.getMonth()+1;
		   	if (month<10) { month="0"+month;}

		   	jQuery('.b2bking_reports_date_input_to').val(lastDay.getFullYear()+'-'+month+'-'+day);

		   	reports_set_chart();
		   
		});

		$('body').on('change', '.b2bking_reports_date_input', function(){
			reports_set_chart();
		});
		$('body').on('change', '#b2bking_reports_days_select', function(){
			reports_set_chart();
		});

		function show_reports_preloader(){
			$('.b2bking_reports_icon_loader').show();
			$('.ct-charts, #b2bking_reports_first_row, #b2bking_reports_second_row').css('opacity', 0.12);
			// minimum time between show / hide, otherwise looks wrong
			last_reports_loading_time = Date.now();
		}
		function drawReportsSalesChart(labelsdraw, salestotal, ordernumbers, commissiontotal){

			// hide preloader only if minimum 250 ms for good effect
			if (last_reports_loading_time === 0 || (Date.now()-last_reports_loading_time) > 250 ){

				// First, hide preloader
				$('.b2bking_reports_icon_loader').hide();
				$('.ct-charts, #b2bking_reports_first_row, #b2bking_reports_second_row').css('opacity', 1);

				// Second draw chart
				if ($(".b2bking_reports_page_wrapper").val()!== undefined ){

				    $('#b2bking_dashboard_blue_button').text($('#b2bking_reports_days_select option:selected').text());

				    var chart = new Chartist.Line('.campaign', {
				        labels: labelsdraw,
				        series: [
				            salestotal,commissiontotal
				        ]
				    }, {
				        low: 0,
				        high: Math.max(commissiontotal,salestotal),

				        showArea: true,
				        fullWidth: true,
				        plugins: [
				            Chartist.plugins.tooltip()
				        ],
				        axisY: {
				            onlyInteger: true,
				            scaleMinSpace: 40,
				            offset: 55,
				            labelInterpolationFnc: function(value) {
				                return b2bking_dashboard.currency_symbol + (value / 1);
				            }
				        },
				    });

				    var chart = new Chartist.Line('.campaign2', {
				        labels: labelsdraw,
				        series: [
				            [],ordernumbers
				        ]
				    }, {
				        low: 0,
				        high: Math.max(ordernumbers),

				        showArea: true,
				        fullWidth: true,
				        plugins: [
				            Chartist.plugins.tooltip()
				        ],
				        axisY: {
				            onlyInteger: true,
				            scaleMinSpace: 40,
				            offset: 55,
				        },
				    });

				    // Offset x1 a tiny amount so that the straight stroke gets a bounding box
				    // Straight lines don't get a bounding box 
				    // Last remark on -> http://www.w3.org/TR/SVG11/coords.html#ObjectBoundingBox
				    chart.on('draw', function(ctx) {
				        if (ctx.type === 'area') {
				            ctx.element.attr({
				                x1: ctx.x1 + 0.001
				            });
				        }
				    });

				    // Create the gradient definition on created event (always after chart re-render)
				    chart.on('created', function(ctx) {
				        var defs = ctx.svg.elem('defs');
				        defs.elem('linearGradient', {
				            id: 'gradient',
				            x1: 0,
				            y1: 1,
				            x2: 0,
				            y2: 0
				        }).elem('stop', {
				            offset: 0,
				            'stop-color': 'rgba(255, 255, 255, 1)'
				        }).parent().elem('stop', {
				            offset: 1,
				            'stop-color': 'rgba(64, 196, 255, 1)'
				        });
				    });

				    var chart = [chart];

				}
		   	
		   	} else {

				// try again later
				setTimeout(function(){
					drawReportsSalesChart(labelsdraw, salestotal, ordernumbers, commissiontotal);
				}, 100);
			}

		}

		function drawDashboardSalesChart(){
		    var selectValue = parseInt($('#b2bking_dashboard_days_select').val());
		    $('#b2bking_dashboard_blue_button').text($('#b2bking_dashboard_days_select option:selected').text());

		    if (selectValue === 0){
		        $('.b2bking_total_b2b_sales_seven_days,.b2bking_total_b2b_sales_thirtyone_days, .b2bking_number_orders_seven, .b2bking_number_orders_thirtyone, .b2bking_number_customers_seven, .b2bking_number_customers_thirtyone, .b2bking_net_earnings_seven, .b2bking_net_earnings_thirtyone').css('display', 'none');
		        $('.b2bking_total_b2b_sales_today, .b2bking_number_orders_today, .b2bking_number_customers_today, .b2bking_net_earnings_today').css('display', 'block');
		    } else if (selectValue === 1){
		        $('.b2bking_total_b2b_sales_today,.b2bking_total_b2b_sales_thirtyone_days, .b2bking_number_orders_today, .b2bking_number_orders_thirtyone, .b2bking_number_customers_today, .b2bking_number_customers_thirtyone, .b2bking_net_earnings_today, .b2bking_net_earnings_thirtyone').css('display', 'none');
		        $('.b2bking_total_b2b_sales_seven_days, .b2bking_number_orders_seven, .b2bking_number_customers_seven, .b2bking_net_earnings_seven').css('display', 'block');
		    } else if (selectValue === 2){
		        $('.b2bking_total_b2b_sales_today,.b2bking_total_b2b_sales_seven_days, .b2bking_number_orders_today, .b2bking_number_orders_seven, .b2bking_number_customers_today, .b2bking_number_customers_seven, .b2bking_net_earnings_today, .b2bking_net_earnings_seven').css('display', 'none');
		        $('.b2bking_total_b2b_sales_thirtyone_days, .b2bking_number_orders_thirtyone, .b2bking_number_customers_thirtyone, .b2bking_net_earnings_thirtyone').css('display', 'block');
		    }

		    if (selectValue === 0){
		        // set label
		        var labelsdraw = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];
		        // set series
		        var seriesdrawb2b = b2bking_dashboard.hours_sales_b2b.concat();
		        var seriesdrawb2c = b2bking_dashboard.hours_sales_b2c.concat();

		    } else if (selectValue === 1){
		        // set label
		        var date = new Date();
		        var d = date.getDate();
		        var labelsdraw = [d-6, d-5, d-4, d-3, d-2, d-1, d];
		        labelsdraw.forEach(myFunction);
		        function myFunction(item, index) {
		          if (parseInt(item)<=0){
		            let last = new Date();
		            let month = last.getMonth()-1;
		            let year = last.getFullYear();
		            let lastMonthDays = new Date(year, month, 0).getDate();
		            labelsdraw[index] = lastMonthDays+item;
		          }
		        }
		        // set series
		        var seriesdrawb2b = b2bking_dashboard.days_sales_b2b.concat();
		        var seriesdrawb2c = b2bking_dashboard.days_sales_b2c.concat();
		        seriesdrawb2b.splice(7,24);
		        seriesdrawb2c.splice(7,24);
		        seriesdrawb2b.reverse();
		        seriesdrawb2c.reverse();
		    } else if (selectValue === 2){
		        // set label
		        var labelsdraw = [];
		        let i = 0;
		        while (i<32){
		            let now = new Date();
		            let pastDate = new Date(now.setDate(now.getDate() - i));
		            let day = pastDate.getDate();
		            labelsdraw.unshift(day);
		            i++;
		        }
		        // set series
		        var seriesdrawb2b = b2bking_dashboard.days_sales_b2b.concat();
		        var seriesdrawb2c = b2bking_dashboard.days_sales_b2c.concat();
		        seriesdrawb2b.reverse();
		        seriesdrawb2c.reverse();
		    }

		    if (parseInt(b2bking_dashboard.b2bking_demo) === 1){
		    	labelsdraw = [1, 2, 3, 4, 5, 6, 7, 8];
		    	seriesdrawb2b = [0, 5, 6, 8, 25, 9, 8, 24];
		    	seriesdrawb2c = [0, 3, 1, 2, 8, 1, 5, 1];
		    }

		    var chart = new Chartist.Line('.campaign', {
		        labels: labelsdraw,
		        series: [
		            seriesdrawb2b,
		            seriesdrawb2c
		        ]
		    }, {
		        low: 0,
		        high: Math.max(seriesdrawb2c, seriesdrawb2b),

		        showArea: true,
		        fullWidth: true,
		        plugins: [
		            Chartist.plugins.tooltip()
		        ],
		        axisY: {
		            onlyInteger: true,
		            scaleMinSpace: 40,
		            offset: 55,
		            labelInterpolationFnc: function(value) {
		                return b2bking_dashboard.currency_symbol + (value / 1);
		            }
		        },
		    });

		    // Offset x1 a tiny amount so that the straight stroke gets a bounding box
		    // Straight lines don't get a bounding box 
		    // Last remark on -> http://www.w3.org/TR/SVG11/coords.html#ObjectBoundingBox
		    chart.on('draw', function(ctx) {
		        if (ctx.type === 'area') {
		            ctx.element.attr({
		                x1: ctx.x1 + 0.001
		            });
		        }
		    });

		    // Create the gradient definition on created event (always after chart re-render)
		    chart.on('created', function(ctx) {
		        var defs = ctx.svg.elem('defs');
		        defs.elem('linearGradient', {
		            id: 'gradient',
		            x1: 0,
		            y1: 1,
		            x2: 0,
		            y2: 0
		        }).elem('stop', {
		            offset: 0,
		            'stop-color': 'rgba(255, 255, 255, 1)'
		        }).parent().elem('stop', {
		            offset: 1,
		            'stop-color': 'rgba(64, 196, 255, 1)'
		        });
		    });

		    var chart = [chart];
		}

		$('#toplevel_page_b2bkingcore a').on('click', function(e){
			// check list of pages with ajax switch. If page is in list, prevent default and load via ajax
			// make sure current page is a b2bking page but not settings

			if (b2bking.ajax_pages_load === 'enabled'){
				let location = $(this).prop('href');
				let page = location.split('page=b2bking_');
				let switchto = page[1];


				if (availablepages.includes(switchto) && (page_slug.startsWith('b2bking') || b2bking.current_post_type.startsWith('b2bking') )){
					// prevent link click
					e.preventDefault();
					page_switch(switchto);

					// change link classes
					$('#adminmenu #toplevel_page_b2bkingcore').find('.current').each(function(i){
						$(this).removeClass('current');
					});
					$(this).addClass('current');
					$(this).parent().addClass('current');
					$(this).blur();
				}

				// edit post type
				page = location.split('post_type=');
				switchto = page[1];

				if (availableposts.includes(switchto) && (page_slug.startsWith('b2bking') || b2bking.current_post_type.startsWith('b2bking') ) ){
					// prevent link click
					e.preventDefault();
					page_switch('edit_'+switchto);

					// change link classes
					$('#adminmenu #toplevel_page_b2bkingcore').find('.current').each(function(i){
						$(this).removeClass('current');
					});
					$(this).addClass('current');
					$(this).parent().addClass('current');
					$(this).blur();
				}
			}
		});

		// separate stock variable
		$('body').on('change', '.b2bking_separate_stock select', quantityseparatestockvariable);

		function quantityseparatestockvariable(){
			
			let val = $(this).val();
			let id = $(this).attr('id');
			let fieldnr = id.split('_')[3];

			if (val === 'yes'){
				$('.variable_stock_b2b_'+fieldnr+'_field').css('display','block');
				$('.variable_stock_b2b_'+fieldnr+'_field').removeClass('b2bking_hidden_wrapper');

			} else if (val === 'no'){
				$('.variable_stock_b2b_'+fieldnr+'_field').css('display','none');
			}

		}

		// separate stock simple
		quantityseparatestocksimple();
		$('#_separate_stock_quantities_b2b').on('change', quantityseparatestocksimple);
		$('.inventory_tab').on('click', quantityseparatestocksimple);

		function quantityseparatestocksimple(){
			
			let val = $('#_separate_stock_quantities_b2b').val();
			if (val === 'yes'){
				$('._stock_b2b_field').css('display','block');
			} else if (val === 'no'){
				$('._stock_b2b_field').css('display','none');
			}

		}


		// activate plugin
		$('#b2bking-activate-license').on('click', function(){
			var datavar = {
	            action: 'b2bkingactivatelicense',
	            email: $('input[name="b2bking_license_email_setting"]').val(),
	            key: $('input[name="b2bking_license_key_setting"]').val(),
	            security: b2bking.security,
	        };
	        
	        const Toast = Swal.mixin({
	          toast: true,
	          position: 'center',
	          showConfirmButton: false,
	          timerProgressBar: true,
	          didOpen: (toast) => {
	            toast.addEventListener('mouseenter', Swal.stopTimer)
	            toast.addEventListener('mouseleave', Swal.resumeTimer)
	          }
	        })

	        Toast.fire({
	          icon: 'info',
	          title: b2bking.sending_request
	        });

			$.post(ajaxurl, datavar, function(response){
				if (response === 'success'){
					$('#b2bking-admin-submit').click();
				} else {

					Swal.fire(
	    	            b2bking.issue_occurred, 
	    	            response, 
	    	            "warning"
	    	        );
				}
			});
		});


		$('#b2bking_clear_caches_button').on('click', function(){
			var datavar = {
	            action: 'b2bkingclearcaches',
	            security: b2bking.security,
	        };

			const Toast = Swal.mixin({
			  toast: true,
			  position: 'center',
			  showConfirmButton: false,
			  timer: 10000,
			  timerProgressBar: true,
			  didOpen: (toast) => {
			    toast.addEventListener('mouseenter', Swal.stopTimer)
			    toast.addEventListener('mouseleave', Swal.resumeTimer)
			  }
			})

			Toast.fire({
			  icon: 'info',
			  title: b2bking.caches_are_clearing
			});
			      
			$.post(ajaxurl, datavar, function(response){
				Swal.fire(
    	            b2bking.caches_have_cleared, 
    	            "", 
    	            "success"
    	        );
			});

		});

		// Quote fields
		$("body.post-type-b2bking_quote_field .wrap a.page-title-action").after('&nbsp;<a href="'+b2bking.quote_fields_link+'" class="page-title-action">'+b2bking.view_quote_fields+'</a>');

		// In admin emails, modify email path for theme folder.
		if (($('#woocommerce_b2bking_new_customer_email_enabled').val() !== undefined)||($('#woocommerce_b2bking_your_account_approved_email_enabled').val() !== undefined)||($('#woocommerce_b2bking_new_customer_requires_approval_email_enabled').val() !== undefined)||($('#woocommerce_b2bking_new_message_email_enabled').val() !== undefined)){
			var text = $('.template_html').html();
			var newtext = text.replace("/woocommerce/", "/");
			$('.template_html').html(newtext);
			$('.template_html p a:nth-child(2)').remove();
			$('.template_html a').remove();
		}

		/* Special Groups: B2C Users and Guests - Payment and Shipping Methods */
		$('body').on('click', '.b2bking_b2c_special_group_container_save_settings_button', function(){
			var datavar = {
	            action: 'b2bking_b2c_special_group_save_settings',
	            security: b2bking.security,
	        };

    		$("input:checkbox").each(function(){
    			let name = $(this).attr('name');
    			if ($(this).is(':checked')){
    				datavar[name] = 1;
    			} else {
    				datavar[name] = 0;
    			}
            });

			const Toast = Swal.mixin({
			  toast: true,
			  position: 'bottom',
			  showConfirmButton: false,
			  timer: 10000,
			  timerProgressBar: true,
			  didOpen: (toast) => {
			    toast.addEventListener('mouseenter', Swal.stopTimer)
			    toast.addEventListener('mouseleave', Swal.resumeTimer)
			  }
			})

			Toast.fire({
			  icon: 'info',
			  title: b2bking.saving
			});

			jQuery('.b2bking_settings_button').removeClass('active');
			jQuery('.b2bking_settings_button').text('Save Settings');
	        
			$.post(ajaxurl, datavar, function(response){
				const Toast = Swal.mixin({
				  toast: true,
				  position: 'bottom',
				  showConfirmButton: false,
				  timer: 1000,
				  timerProgressBar: false,
				  didOpen: (toast) => {
				    toast.addEventListener('mouseenter', Swal.stopTimer)
				    toast.addEventListener('mouseleave', Swal.resumeTimer)
				  }
				})

				Toast.fire({
				  icon: 'success',
				  title: b2bking.settings_saved
				});
			});

		})

		$('.b2bking_email_offer_button').on('click', function(){

			Swal.fire({
			  title: b2bking.are_you_sure,
			  text: b2bking.email_offer_confirm,
			  icon: 'warning',
			  showCancelButton: true,
			  confirmButtonColor: '#20d580',
			  cancelButtonColor: '#e85347',
			  confirmButtonText: b2bking.yes_confirm
			}).then((result) => {
			  if (result.isConfirmed) {
			  		// show alert
	    			var datavar = {
	    	            action: 'b2bkingemailoffer',
	    	            security: b2bking.security,
	    	            offerid: $('#post_ID').val(),
	    	            offerlink: b2bking.offers_endpoint_link,
	    	        };

	    			$.post(ajaxurl, datavar, function(response){
	    				Swal.fire(
	        	            b2bking.email_has_been_sent, 
	        	            "", 
	        	            "success"
	        	        );
	    			});
			  }
			});
			
		});

		$('.b2bking_make_offer').on('click', function(){
			
			window.location = b2bking.new_offer_link+'&quote='+$('#post_ID').val();

		});
		

		// download offer
		$('.b2bking_download_offer_button').on('click', function(){

			var logoimg = b2bking.offers_logo;

			var imgToExport = document.getElementById('b2bking_img_logo');
			var canvas = document.createElement('canvas');
	        canvas.width = imgToExport.width; 
	        canvas.height = imgToExport.height; 
	        canvas.getContext('2d').drawImage(imgToExport, 0, 0);
	  		var dataURL = canvas.toDataURL("image/png"); 

	  		// get all thumbnails 
	  		var thumbnails = [];
	  		var thumbnr = 0;
	  		if (parseInt(b2bking.offers_images_setting) === 1){
		  		// get field;
		  		let field = $('#b2bking_offers_thumbnails_str').val();
		  		let itemsArray = field.split('|');
		  		// foreach condition, add condition, add new item
		  		itemsArray.forEach(function(item){
		  			if (item !== 'no'){
		  				var idimg = 'b2bking_img_logo'+thumbnr;
  						var imgToExport = document.getElementById(idimg);
  						var canvas = document.createElement('canvas');
  				        canvas.width = imgToExport.width; 
  				        canvas.height = imgToExport.height; 
  				        canvas.getContext('2d').drawImage(imgToExport, 0, 0);
  				  		let datau = canvas.toDataURL("image/png"); 
  				  		thumbnr++;
  				  		thumbnails.push(datau);
		  			} else {
		  				thumbnails.push('no');
		  			}
		  		});
		  	}

		  	var names = $('#b2bking_offers_names_str').val();
		  	let namesArray = names.split('*|||*');
		  	var namenr=0;

		  	thumbnr = 0;
			var customtext = jQuery('#b2bking_offer_customtext_textarea').val();
			var customtexttitle = b2bking.offer_custom_text;
			if (customtext.length === 0){
				customtexttitle = '';
			}

			var bodyarray = [];
			bodyarray.push([{ text: b2bking.item_name, style: 'tableHeader', margin: [7, 7, 7, 7] }, { text: b2bking.item_quantity, style: 'tableHeader', margin: [7, 7, 7, 7] }, { text: b2bking.unit_price, style: 'tableHeader', margin: [7, 7, 7, 7] }, { text: b2bking.item_subtotal, style: 'tableHeader', margin: [7, 7, 7, 7] }]);

			// get values
			jQuery('.b2bking_offer_line_number').each(function(i){
				let tempvalues = [];

				// let namevalue = jQuery(this).find('.b2bking_offer_item_name option:selected').text();
				let namevalue = namesArray[namenr];
				namenr++;

				if (parseInt(b2bking.offers_images_setting) === 1){
					if (thumbnails[thumbnr] !== 'no'){
						// add name + images
						tempvalues.push([{ text: namevalue, margin: [7, 7, 7, 7] },{
								image: thumbnails[thumbnr],
								width: 40,
								margin: [15, 5, 5, 5]
							}]);
					} else {
						// add name only
						tempvalues.push({ text: namevalue, margin: [7, 7, 7, 7] });
					}
					thumbnr++;
				} else {
					// add name only
					tempvalues.push({ text: namevalue, margin: [7, 7, 7, 7] });
				}




				tempvalues.push({ text: jQuery(this).find('.b2bking_offer_item_quantity').val(), margin: [7, 7, 7, 7] });
				tempvalues.push({ text: jQuery(this).find('.b2bking_offer_item_price').val(), margin: [7, 7, 7, 7] });
				tempvalues.push({ text: jQuery(this).find('.b2bking_item_subtotal').text(), margin: [7, 7, 7, 7] });
				bodyarray.push(tempvalues);

				console.log(tempvalues);
			});

			console.log(bodyarray);


			bodyarray.push(['','',{ text: b2bking.offer_total+': ', margin: [7, 7, 7, 7], bold: true },{ text: jQuery('#b2bking_offer_total_text_number').text(), margin: [7, 7, 7, 7], bold: true }]);

			let imgobj = {
						image: dataURL,
						width: 150,
						margin: [0, 0, 0, 30],
					};


			var contentarray =[
					{ text: b2bking.offer_details, fontSize: 14, bold: true, margin: [0, 20, 0, 20] },
					{
						style: 'tableExample',
						table: {
							headerRows: 1,
							widths: ['*', '*', '*', '*'],
							body: bodyarray,
						},
						layout: 'lightHorizontalLines'
					},
					{ text: b2bking.offer_go_to, link: b2bking.offers_endpoint_link, decoration: 'underline', fontSize: 13, bold: true, margin: [0, 20, 40, 8], alignment:'right' },
					{ text: customtexttitle, fontSize: 14, bold: true, margin: [0, 50, 0, 8] },
					{ text: customtext, fontSize: 12, bold: false, margin: [0, 8, 0, 8] },

					
				];

			if (logoimg.length !== 0){
				contentarray.unshift(imgobj);
			}

			var custom_content_center_1 = b2bking.custom_content_center_1;
			var custom_content_center_2 = b2bking.custom_content_center_2;
			var custom_content_left_1 = b2bking.custom_content_left_1;
			var custom_content_left_2 = b2bking.custom_content_left_2;
			if (custom_content_center_1.length !== 0){
				let custom_content = { text: custom_content_center_1, fontSize: 12, bold: true, margin: [0, 12, 0, 12], alignment:'center' };
				contentarray.unshift(custom_content);
			}
			if (custom_content_center_2.length !== 0){
				let custom_content = { text: custom_content_center_2, fontSize: 12, bold: true, margin: [0, 12, 0, 12], alignment:'center' };
				contentarray.unshift(custom_content);
			}
			if (custom_content_left_1.length !== 0){
				let custom_content = { text: custom_content_left_1, fontSize: 12, bold: true, margin: [0, 12, 0, 12], alignment:'left' };
				contentarray.unshift(custom_content);
			}
			if (custom_content_left_2.length !== 0){
				let custom_content = { text: custom_content_left_2, fontSize: 12, bold: true, margin: [0, 12, 0, 12], alignment:'left' };
				contentarray.unshift(custom_content);
			}

			var docDefinition = {
				content: contentarray
			};

			if(b2bking.pdf_download_lang === 'thai'){

				pdfMake.fonts = {
				  THSarabunNew: {
				    normal: 'THSarabunNew.ttf',
				    bold: 'THSarabunNew-Bold.ttf',
				    italics: 'THSarabunNew-Italic.ttf',
				    bolditalics: 'THSarabunNew-BoldItalic.ttf'
				  }
				};

				docDefinition = {
				  content: contentarray,
				  defaultStyle: {
				    font: 'THSarabunNew'
				  }
				}
			}


			pdfMake.createPdf(docDefinition).download('offer.pdf');

		});

		function ajax_page_reload(){
			// 1. Replace current page content with loader
			// add overlay and loader
			jQuery('#wpbody-content').prepend('<div id="b2bking_admin_overlay"><img class="b2bking_loader_icon_button" src="'+b2bking.loaderurl+'">');

			// if pageslug contains user, remove it
			let slugsplit = window.location.href.split('&user=');
			let switchto = slugsplit[0].split('b2bking_')[1];
			let userid = 0;
			if (slugsplit[1] !== undefined){
				userid = slugsplit[1];
			}

			// 2. Get page content
			var datavar = {
	            action: 'b2bking_get_page_content',
	            security: b2bking.security,
	            page: switchto,
	            userid: userid
	        };

			jQuery.post(ajaxurl, datavar, function(response){

				// response is the HTML content of the page
				jQuery('#wpbody-content').html(response);

				// initialize JS
				initialize_elements();

				initialize_on_b2bkingcore_page_load();
			});
		}
	
		$('body').on('click', '.b2bking_logged_out_special_group_container_save_settings_button', function(){
			var datavar = {
	            action: 'b2bking_logged_out_special_group_save_settings',
	            security: b2bking.security,
	        };

    		$("input:checkbox").each(function(){
    			let name = $(this).attr('name');
    			if ($(this).is(':checked')){
    				datavar[name] = 1;
    			} else {
    				datavar[name] = 0;
    			}
            });

           const Toast = Swal.mixin({
             toast: true,
             position: 'bottom',
             showConfirmButton: false,
             timer: 10000,
             timerProgressBar: true,
             didOpen: (toast) => {
               toast.addEventListener('mouseenter', Swal.stopTimer)
               toast.addEventListener('mouseleave', Swal.resumeTimer)
             }
           })

           jQuery('.b2bking_settings_button').removeClass('active');
           jQuery('.b2bking_settings_button').text('Save Settings');

           Toast.fire({
             icon: 'info',
             title: b2bking.saving
           });
                 
           $.post(ajaxurl, datavar, function(response){
	           	const Toast = Swal.mixin({
	           	  toast: true,
	           	  position: 'bottom',
	           	  showConfirmButton: false,
	           	  timer: 1000,
	           	  timerProgressBar: false,
	           	  didOpen: (toast) => {
	           	    toast.addEventListener('mouseenter', Swal.stopTimer)
	           	    toast.addEventListener('mouseleave', Swal.resumeTimer)
	           	  }
	           	})

	           	Toast.fire({
	           	  icon: 'success',
	           	  title: b2bking.settings_saved
	           	});
           });

		});

		/* Conversations */
		// On load conversation, scroll to conversation end
		// if conversation exists
		if ($('#b2bking_conversation_messages_container').length){
			$("#b2bking_conversation_messages_container").scrollTop($("#b2bking_conversation_messages_container")[0].scrollHeight);
		}

		/* Product Category Visibility */
		// On clicking the "Add user button in the Product Category User Visibility table"
		$("#b2bking_category_add_user").on("click",function(){
			// Get username
			let username = $("#b2bking_all_users_dropdown").children("option:selected").text();
			// Get content and check if username already exists
			let content = $("#b2bking_category_users_textarea").val();
			let usersarray = content.split(',');
			let exists = 0;

			$.each( usersarray, function( i, val ) {
				if (val.trim() === username){
					exists = 1;
				}
			});

			if (exists === 1){
				// Show "Username already in the list" for 3 seconds
				$("#b2bking_category_add_user").text(b2bking.username_already_list);
				setTimeout(function(){
					$("#b2bking_category_add_user").text(b2bking.add_user);
				}, 2000);

			} else {
				// remove last comma and whitespace after
				content = content.replace(/,\s*$/, "");
				// if list is not empty, add comma
				if (content.length > 0){
					content = content + ', ';
				}
				// add username
				content = content + username;
				$("#b2bking_category_users_textarea").val(content);
			}
		});

		/* Product Visibility */
		// On page load, update product visibility options
		updateProductVisibilityOptions();

		// On Product Visibility option change, update product visibility options 
		$('#b2bking_product_visibility_override').change(function() {
			updateProductVisibilityOptions();
		});

		// Checks the selected Product Visibility option and hides or shows Automatic / Manual visibility options
		function updateProductVisibilityOptions(){
			let selectedValue = $("#b2bking_product_visibility_override").children("option:selected").val();
			if(selectedValue === "manual") {
		      	$("#b2bking_metabox_product_categories_wrapper").css("display","none");
		      	$("#b2bking_product_visibility_override_options_wrapper").css("display","block");
		   	} else if (selectedValue === "default"){
				$("#b2bking_product_visibility_override_options_wrapper").css("display","none");
				$("#b2bking_metabox_product_categories_wrapper").css("display","block");
			}
		}


		/* Dynamic Rules */
		// On page load, before everything, set up conditions from hidden field to selectors
		setUpConditionsFromHidden();
		// update dynamic pricing rules
		updateDynamicRulesOptionsConditions();

		// Initialize Select2s
		$('.post-type-b2bking_rule #b2bking_rule_select_who').select2();
		$('.post-type-b2bking_rule #b2bking_rule_select_applies').select2();

		showHideMultipleAgentsSelector();
		$('#b2bking_rule_select_agents_who').change(showHideMultipleAgentsSelector);
		function showHideMultipleAgentsSelector(){
			let selectedValue = $('#b2bking_rule_select_agents_who').val();
			if (selectedValue === 'multiple_options'){
				$('#b2bking_select_multiple_agents_selector').css('display','block');
			} else {
				$('#b2bking_select_multiple_agents_selector').css('display','none');
			}
		}

		// Value Condition Error - show discount everywhere
		jQuery('#publish').on('click', function(e){
			
			if (jQuery('#b2bking_dynamic_rule_discount_show_everywhere_checkbox_input').is(':checked')){
			// check for value conditions

				let have_value_conditions = 'no';
				jQuery('.b2bking_rule_condition_container').each(function(){
					let value = jQuery(this).find('.b2bking_dynamic_rule_condition_number').val();
					if(value !== ''){
						// check if condition is value condition
						let cond = jQuery(this).find('.b2bking_dynamic_rule_condition_name').val();
						if (cond === 'cart_total_value'){
							have_value_conditions = 'yes';
						} 
						if (cond === 'category_product_value'){
							have_value_conditions = 'yes';
						} 
						if (cond === 'product_value'){
							have_value_conditions = 'yes';
						} 
					}
				});
				if (have_value_conditions === 'yes'){
					e.preventDefault();

	    				Swal.fire(
	        	            b2bking.issue_occurred, 
	        	            b2bking.value_conditions_error, 
	        	            "warning"
	        	        );
				}
				// if any value conditions, show error
			}
		});
		

		// initialize multiple products / categories selector as Select2
		$('.b2bking_select_multiple_product_categories_selector_select, .b2bking_select_multiple_users_selector_select').select2({'width':'100%', 'theme':'classic'});
		// show hide multiple products categories selector
		showHideMultipleProductsCategoriesSelector();
		$('#b2bking_rule_select_what').change(showHideMultipleProductsCategoriesSelector);
		$('#b2bking_rule_select_applies').change(showHideMultipleProductsCategoriesSelector);
		function showHideMultipleProductsCategoriesSelector(){
			let selectedValue = $('#b2bking_rule_select_applies').val();
			let hiddenwhat = ['replace_prices_quote','set_currency_symbol','payment_method_minmax_order','payment_method_discount','rename_purchase_order'];
			let selectedWhat = $('#b2bking_rule_select_what').val();
			if ( (selectedValue === 'multiple_options' && selectedWhat !== 'tax_exemption_user') || (selectedValue === 'excluding_multiple_options' && selectedWhat !== 'tax_exemption_user')){
				$('#b2bking_select_multiple_product_categories_selector').css('display','block');
			} else {
				$('#b2bking_select_multiple_product_categories_selector').css('display','none');
			}

			if (hiddenwhat.includes(selectedWhat)){
				$('#b2bking_select_multiple_product_categories_selector').css('display','none');
			}
		}

		showHideMultipleUsersSelector();
		$('#b2bking_rule_select_who').change(showHideMultipleUsersSelector);
		function showHideMultipleUsersSelector(){
			let selectedValue = $('#b2bking_rule_select_who').val();
			if (selectedValue === 'multiple_options'){
				$('#b2bking_select_multiple_users_selector').css('display','block');
			} else {
				$('#b2bking_select_multiple_users_selector').css('display','none');
			}
		}

		function setUpConditionsFromHidden(){
			// get all conditions
			let conditions = $('#b2bking_rule_select_conditions').val();
			if (conditions === undefined) {
				conditions = '';
			}

			if(conditions.trim() !== ''){  
				let conditionsArray = conditions.split('|');
				let i=1;
				// foreach condition, create selectors
				conditionsArray.forEach(function(item){
					let conditionDetails = item.split(';');
					// if condition not empty
					if (conditionDetails[0] !== ''){
						$('.b2bking_dynamic_rule_condition_name.b2bking_condition_identifier_'+i).val(conditionDetails[0]);
						$('.b2bking_dynamic_rule_condition_operator.b2bking_condition_identifier_'+i).val(conditionDetails[1]);
						$('.b2bking_dynamic_rule_condition_number.b2bking_condition_identifier_'+i).val(conditionDetails[2]);
						addNewCondition(i, 'programatically');
						i++;
					}
				});
			}
		}

		// On clicking "add condition" in Dynamic rule
		$('body').on('click', '.b2bking_dynamic_rule_condition_add_button', function(event) {
		    addNewCondition(1,'user');
		});

		function addNewCondition(buttonNumber = 1, type = 'user'){
			let currentNumber;
			let nextNumber;

			// If condition was added by user
			if (type === 'user'){
				// get its current number
				let classList = $('.b2bking_dynamic_rule_condition_add_button').attr('class').split(/\s+/);
				$.each(classList, function(index, item) {
				    if (item.includes('identifier')) {
				    	var itemArray = item.split("_");
				    	currentNumber = parseInt(itemArray[3]);
				    }
				});
				// set next number
				nextNumber = (currentNumber+1);
			} else {
				// If condition was added at page load automatically
				currentNumber = buttonNumber;
				nextNumber = currentNumber+1;
			}

			// add delete button same condition
			$('.b2bking_dynamic_rule_condition_add_button.b2bking_condition_identifier_'+currentNumber).after('<button type="button" class="b2bking_dynamic_rule_condition_delete_button b2bking_condition_identifier_'+currentNumber+'">'+b2bking.delete+'</button>');
			// add next condition
			$('#b2bking_condition_number_'+currentNumber).after('<div id="b2bking_condition_number_'+nextNumber+'" class="b2bking_rule_condition_container">'+
				'<select class="b2bking_dynamic_rule_condition_name b2bking_condition_identifier_'+nextNumber+'">'+
					'<option value="cart_total_quantity" selected="selected">'+b2bking.cart_total_quantity+'</option>'+
					'<option value="cart_total_value">'+b2bking.cart_total_value+'</option>'+
					'<option value="category_product_quantity">'+b2bking.category_product_quantity+'</option>'+
					'<option value="category_product_value">'+b2bking.category_product_value+'</option>'+
					'<option value="product_quantity">'+b2bking.product_quantity+'</option>'+
					'<option value="product_value">'+b2bking.product_value+'</option>'+
				'</select>'+
				'<select class="b2bking_dynamic_rule_condition_operator b2bking_condition_identifier_'+nextNumber+'">'+
					'<option value="greater">'+b2bking.greater+'</option>'+
					'<option value="equal">'+b2bking.equal+'</option>'+
					'<option value="smaller">'+b2bking.smaller+'</option>'+
				'</select>'+
				'<input type="number" step="0.00001" class="b2bking_dynamic_rule_condition_number b2bking_condition_identifier_'+nextNumber+'" placeholder="'+b2bking.enter_quantity_value+'">'+
				'<button type="button" class="b2bking_dynamic_rule_condition_add_button b2bking_condition_identifier_'+nextNumber+'">'+b2bking.add_condition+'</button>'+
			'</div>');

			// remove self 
			$('.b2bking_dynamic_rule_condition_add_button.b2bking_condition_identifier_'+currentNumber).remove();

			// update available options
			updateDynamicRulesOptionsConditions();
		}

		// On clicking "delete condition" in Dynamic rule
		$('body').on('click', '.b2bking_dynamic_rule_condition_delete_button', function () {
			// get its current number
			let currentNumber;
			let classList = $(this).attr('class').split(/\s+/);
			$.each(classList, function(index, item) {
			    if (item.includes('identifier')) {
			    	var itemArray = item.split("_");
			    	currentNumber = parseInt(itemArray[3]);
			    }
			});
			// remove current element
			$('#b2bking_condition_number_'+currentNumber).remove();

			// update conditions hidden field
			updateConditionsHiddenField();
		});

		// On Rule selector change, update dynamic rule conditions
		$('#b2bking_rule_select_what, #b2bking_rule_select_who, #b2bking_rule_select_applies, #b2bking_rule_select, #b2bking_rule_select_showtax, #b2bking_container_tax_shipping').change(function() {
			updateDynamicRulesOptionsConditions();
		});

		function updateDynamicRulesOptionsConditions(){
			$('#b2bking_rule_select_applies_replaced_container, #b2bking_rule_select_who_replaced_container').css('display','none');
			// Hide one-time fee
			$('#b2bking_one_time').css('display','none');
			// Hide all condition options
			$('.b2bking_dynamic_rule_condition_name option').css('display','none');
			// Hide quantity/value
			$('#b2bking_container_quantity_value').css('display','none');
			// Hide currency
			$('#b2bking_container_currency').css('display','none');
			// Hide payment methods
			$('#b2bking_container_paymentmethods, #b2bking_container_paymentmethods_minmax, #b2bking_container_paymentmethods_percentamount').css('display','none');
			// Hide countries and requires
			$('#b2bking_container_countries, #b2bking_container_requires, #b2bking_container_showtax').css('display','none');
			// Hide tax name
			$('#b2bking_container_taxname, #b2bking_container_tax_taxable, #b2bking_container_tax_shipping, #b2bking_container_tax_shipping_rate, #b2bking_container_tiered_price, #b2bking_container_rulepriority').css('display','none');
			// Hide discount checkbox
			$('.b2bking_dynamic_rule_minimum_all_checkbox_container, .b2bking_dynamic_rule_discount_show_everywhere_checkbox_container, .b2bking_dynamic_rule_quotes_checkbox_container, .b2bking_discount_options_information_box, .b2bking_minimum_options_information_box').css('display','none');
			$('#b2bking_container_discountname').css('display','none');
			$('.b2bking_rule_label_discount, .b2bking_rule_label_minimum, .b2bking_rule_label_quotes').css('display','none');

			// conditions box text
			$('#b2bking_rule_conditions_information_box_text').text(b2bking.conditions_apply_cumulatively);

			// Show all options
			$("#b2bking_container_howmuch").css('display','inline-block');
			$('#b2bking_container_applies').css('display','inline-block');
			// Show conditions + conditions info box
			$('#b2bking_rule_select_conditions_container').css('display','inline-block');
			$('.b2bking_rule_conditions_information_box').css('display','flex');

			let selectedWhat = $("#b2bking_rule_select_what").val();
			let selectedWho = $("#b2bking_rule_select_who").val();
			let selectedApplies = $("#b2bking_rule_select_applies").val();
			// Select Discount Amount or Percentage
			if (selectedWhat === 'discount_amount' || selectedWhat === 'discount_percentage'){
				// if select Cart: cart_total_quantity and cart_total_value
				if (selectedApplies === 'cart_total'){
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value]').css('display','block');
				} else if (selectedApplies.startsWith("category")){
				// if select Category also have: category_product_quantity and category_product_value
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value], .b2bking_dynamic_rule_condition_name option[value=category_product_quantity], .b2bking_dynamic_rule_condition_name option[value=category_product_value]').css('display','block');
				} else if (selectedApplies.startsWith("product")){
				// if select Product also have: product_quantity and product_value  
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value], .b2bking_dynamic_rule_condition_name option[value=product_quantity], .b2bking_dynamic_rule_condition_name option[value=product_value]').css('display','block');
				} else if (selectedApplies === 'multiple_options' || selectedApplies === 'excluding_multiple_options' || selectedApplies === 'replace_ids'){
					$('.b2bking_dynamic_rule_condition_name option').css('display','block');
					// conditions box text
					$('#b2bking_rule_conditions_information_box_text').text(b2bking.conditions_multiselect);
				}
				// Show discount everywhere checkbox
				$('.b2bking_dynamic_rule_discount_show_everywhere_checkbox_container, .b2bking_discount_options_information_box').css('display','flex');
				$('.b2bking_rule_label_discount').css('display','block');
				$('#b2bking_container_discountname').css('display','inline-block');

				$('#b2bking_container_rulepriority').css('display','block');

			} else if (selectedWhat === 'fixed_price'){
				if (selectedApplies === 'cart_total'){
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value]').css('display','block');
				} else if (selectedApplies.startsWith("category")){
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=category_product_quantity]').css('display','block');
				} else if (selectedApplies.startsWith("product")){
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=product_quantity]').css('display','block');
				} else if (selectedApplies === 'multiple_options' || selectedApplies === 'replace_ids'){
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=category_product_quantity], .b2bking_dynamic_rule_condition_name option[value=product_quantity]').css('display','block');
					$('#b2bking_rule_conditions_information_box_text').text(b2bking.conditions_multiselect);
				}

				$('#b2bking_container_rulepriority').css('display','block');

			} else if (selectedWhat === 'free_shipping'){
				// How much does not apply - hide
				$('#b2bking_container_howmuch').css('display','none');
				// if select Cart: cart_total_quantity and cart_total_value
				if (selectedApplies === 'cart_total'){
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value]').css('display','block');
				} else if (selectedApplies.startsWith("category")){
				// if select Category also have: category_product_quantity and category_product_value
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value], .b2bking_dynamic_rule_condition_name option[value=category_product_quantity], .b2bking_dynamic_rule_condition_name option[value=category_product_value]').css('display','block');
				} else if (selectedApplies.startsWith("product")){
				// if select Product also have: product_quantity and product_value 
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value], .b2bking_dynamic_rule_condition_name option[value=product_quantity], .b2bking_dynamic_rule_condition_name option[value=product_value]').css('display','block'); 
				} else if (selectedApplies === 'multiple_options' || selectedApplies === 'replace_ids'){
					$('.b2bking_dynamic_rule_condition_name option').css('display','block');
					$('#b2bking_rule_conditions_information_box_text').text(b2bking.conditions_multiselect);
				}
			} else if (selectedWhat === 'tiered_price'){
				// How much does not apply - hide
				$('#b2bking_container_howmuch').css('display','none');
				$('#b2bking_container_tiered_price').css('display','block');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');


			} else if (selectedWhat === 'hidden_price'){
				// How much does not apply - hide
				$('#b2bking_container_howmuch').css('display','none');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');

			} else if (selectedWhat === 'quotes_products'){
				// How much does not apply - hide
				$('#b2bking_container_howmuch').css('display','none');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');

				$('.b2bking_dynamic_rule_quotes_checkbox_container, .b2bking_quotes_options_information_box').css('display','flex');
				$('.b2bking_rule_label_quotes').css('display','block');

			} else if (selectedWhat === 'unpurchasable'){
				// How much does not apply - hide
				$('#b2bking_container_howmuch').css('display','none');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');

			} else if (selectedWhat === 'required_multiple'){

				// Show discount everywhere checkbox
				$('.b2bking_dynamic_rule_minimum_all_checkbox_container, .b2bking_minimum_options_information_box').css('display','flex');
				$('.b2bking_rule_label_minimum').css('display','block');

				// if select Cart: cart_total_quantity and cart_total_value
				if (selectedApplies === 'cart_total'){
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value]').css('display','block');
				} else if (selectedApplies.startsWith("category")){
				// if select Category also have: category_product_quantity and category_product_value
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value], .b2bking_dynamic_rule_condition_name option[value=category_product_quantity], .b2bking_dynamic_rule_condition_name option[value=category_product_value]').css('display','block');
				} else if (selectedApplies.startsWith("product")){
				// if select Product also have: product_quantity and product_value  
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value], .b2bking_dynamic_rule_condition_name option[value=product_quantity], .b2bking_dynamic_rule_condition_name option[value=product_value]').css('display','block');
				} else if (selectedApplies === 'multiple_options' || selectedApplies === 'replace_ids'){
					$('.b2bking_dynamic_rule_condition_name option').css('display','block');
					$('#b2bking_rule_conditions_information_box_text').text(b2bking.conditions_multiselect);
				}

				$('#b2bking_container_rulepriority').css('display','block');


			} else if (selectedWhat === 'minimum_order' || selectedWhat === 'maximum_order' ) {
				// show Quantity/value
				$('#b2bking_container_quantity_value').css('display','inline-block');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');

				// Show discount everywhere checkbox
				$('.b2bking_dynamic_rule_minimum_all_checkbox_container, .b2bking_minimum_options_information_box').css('display','flex');
				$('.b2bking_rule_label_minimum').css('display','block');

				$('#b2bking_container_rulepriority').css('display','block');

			} else if (selectedWhat === 'tax_exemption' ) {
				// How much does not apply - hide
				$('#b2bking_container_howmuch').css('display','none');
				// show countries and requires
				$('#b2bking_container_countries, #b2bking_container_requires').css('display','inline-block');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');
			} else if (selectedWhat === 'tax_exemption_user' ) {
				// How much does not apply - hide
				$('#b2bking_container_howmuch').css('display','none');
				// Applies does not apply - hide
				$('#b2bking_container_applies').css('display','none');
				// show countries and requires
				$('#b2bking_container_countries, #b2bking_container_requires, #b2bking_container_showtax').css('display','inline-block');
				if ($('#b2bking_rule_select_showtax').val() === 'display_only'){
					$('#b2bking_container_tax_shipping').css('display','inline-block');
					if ($('#b2bking_rule_select_tax_shipping').val() === 'yes'){
						$('#b2bking_container_tax_shipping_rate').css('display', 'inline-block');
					}
				}
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');
			} else if (selectedWhat === 'add_tax_amount' || selectedWhat === 'add_tax_percentage' ) {
				// show one time
				$('#b2bking_one_time').css('display','inline-block');
				// show tax name
				$('#b2bking_container_taxname').css('display','inline-block');
				$('#b2bking_container_tax_taxable').css('display','inline-block');

				if (selectedApplies === 'one_time' && selectedWhat === 'add_tax_percentage'){
					$('#b2bking_container_tax_shipping').css('display','inline-block');
				}
				// if select Cart: cart_total_quantity and cart_total_value
				if (selectedApplies === 'cart_total' || selectedApplies === 'one_time'){
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value]').css('display','block');
				} else if (selectedApplies.startsWith("category")){
				// if select Category also have: category_product_quantity and category_product_value
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value], .b2bking_dynamic_rule_condition_name option[value=category_product_quantity], .b2bking_dynamic_rule_condition_name option[value=category_product_value]').css('display','block');
				} else if (selectedApplies.startsWith("product")){
				// if select Product also have: product_quantity and product_value  
					$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value], .b2bking_dynamic_rule_condition_name option[value=product_quantity], .b2bking_dynamic_rule_condition_name option[value=product_value]').css('display','block');
				} else if (selectedApplies === 'multiple_options' || selectedApplies === 'replace_ids'){
					$('.b2bking_dynamic_rule_condition_name option').css('display','block');
					$('#b2bking_rule_conditions_information_box_text').text(b2bking.conditions_multiselect);
				}

				$('#b2bking_container_rulepriority').css('display','block');

			} else if (selectedWhat === 'replace_prices_quote'){
				// How much does not apply - hide
				$('#b2bking_container_howmuch, #b2bking_container_applies').css('display','none');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');
			} else if (selectedWhat === 'rename_purchase_order'){
				// How much does not apply - hide
				$('#b2bking_container_howmuch, #b2bking_container_applies').css('display','none');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');
				$('#b2bking_container_taxname').css('display','inline-block');

				$('#b2bking_container_paymentmethods').css('display','inline-block');

			} else if (selectedWhat === 'set_currency_symbol'){
				// How much does not apply - hide
				$('#b2bking_container_howmuch, #b2bking_container_applies').css('display','none');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');
				$('#b2bking_container_currency').css('display','inline-block');
			} else if (selectedWhat === 'payment_method_minmax_order'){
				// How much does not apply - hide
				$('#b2bking_container_applies').css('display','none');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');
				$('#b2bking_container_paymentmethods, #b2bking_container_paymentmethods_minmax').css('display','inline-block');
			}  else if (selectedWhat === 'payment_method_discount'){
				// How much does not apply - hide
				$('#b2bking_container_applies').css('display','none');
				// hide Conditions input and available conditions text
				$('#b2bking_rule_select_conditions_container').css('display','none');
				$('.b2bking_rule_conditions_information_box').css('display','none');
				$('#b2bking_container_paymentmethods, #b2bking_container_paymentmethods_percentamount').css('display','inline-block');
			}  else if (selectedWhat === 'bogo_discount'){
				$('.b2bking_dynamic_rule_condition_name option[value=cart_total_quantity], .b2bking_dynamic_rule_condition_name option[value=cart_total_value], .b2bking_dynamic_rule_condition_name option[value=category_product_quantity], .b2bking_dynamic_rule_condition_name option[value=category_product_value]').css('display','block');
				$('.b2bking_dynamic_rule_condition_name option[value=product_quantity], .b2bking_dynamic_rule_condition_name option[value=product_value]').css('display','block');
				$('#b2bking_container_rulepriority').css('display','block');

			}

			if (selectedApplies === 'replace_ids' && selectedWhat !== 'tax_exemption_user'){
				$('#b2bking_rule_select_applies_replaced_container').css('display','block');
			}

			if (selectedWho === 'replace_ids'){
				$('#b2bking_rule_select_who_replaced_container').css('display','block');
			}

			// Check all conditions. If selected condition what is display none, change to Cart Total Quantity (available for all)
			$(".b2bking_dynamic_rule_condition_name").each(function (i) {
				let selected = $(this).val();
				let selectedOption = $(this).find("option[value="+selected+"]");
				if (selectedOption.css('display')==='none'){
					$(this).val('cart_total_quantity');
				}
			});

			// Update Conditions
			updateConditionsHiddenField();
		}

		// On condition text change, update conditions hidden field
		$('body').on('input', '.b2bking_dynamic_rule_condition_number, .b2bking_dynamic_rule_condition_operator, .b2bking_dynamic_rule_condition_name', function () {
			updateConditionsHiddenField();
		});

		function updateConditionsHiddenField(){
			// Clear condtions field
			$('#b2bking_rule_select_conditions').val('');
			// For each condition, if not empty, add to field
			let conditions = '';

			$(".b2bking_dynamic_rule_condition_name").each(function (i) {
				// get its current number
				let currentNumber;
				let classList = $(this).attr('class').split(/\s+/);
				$.each(classList, function(index, item) {
				    if (item.includes('identifier')) {
				    	var itemArray = item.split("_");
				    	currentNumber = parseInt(itemArray[3]);
				    }
				});

				let numberField = $(".b2bking_dynamic_rule_condition_number.b2bking_condition_identifier_"+currentNumber).val();
				if (numberField === undefined){
					numberField = '';
				}

				if (numberField.trim() !== ''){
					conditions+=$(this).val()+';';
					conditions+=$(".b2bking_dynamic_rule_condition_operator.b2bking_condition_identifier_"+currentNumber).val()+';';
					conditions+=$(".b2bking_dynamic_rule_condition_number.b2bking_condition_identifier_"+currentNumber).val()+'|';
				}
			});
			// remove last character
			conditions = conditions.substring(0, conditions.length - 1);
			$('#b2bking_rule_select_conditions').val(conditions);
		}

		/* Offers */

		if (b2bking.current_post_type === 'b2bking_offer' || $('#b2bking_offer_access_metabox').length){
			// On load, retrieve offers
			var offerItemsCounter = 1;
			offerRetrieveHiddenField();
			offerCalculateTotals();
		}

		// When click "add item" add new offer item
		$('body').on('click', '.b2bking_offer_add_item_button', addNewOfferItem);
		
		// initialize offer select2
		$('.b2bking_offer_product_selector').select2();

		function addNewOfferItem(){
			// destroy select2
			$('.b2bking_offer_product_selector').select2();
			$('.b2bking_offer_product_selector').select2('destroy');

			let currentItem = offerItemsCounter;
			let nextItem = currentItem+1;
			offerItemsCounter++;
			$('#b2bking_offer_number_1').clone().attr('id', 'b2bking_offer_number_'+nextItem).insertBefore('#b2bking_addbefore_offer_spacer');
			// clear values from clone
			$('#b2bking_offer_number_'+nextItem+' .b2bking_offer_text_input').val('');
			$('#b2bking_offer_number_'+nextItem+' .b2bking_offer_product_selector').val('').trigger('change');

			$('#b2bking_offer_number_'+nextItem+' .b2bking_item_subtotal').text(b2bking.currency_symbol+'0');
			// add delete button to new item
			$('<button type="button" class="secondary-button button b2bking_offer_delete_item_button">'+b2bking.delete+'</button>').insertAfter('#b2bking_offer_number_'+nextItem+' .b2bking_offer_add_item_button');
			
			//reinitialize select2
			$('.b2bking_offer_product_selector').select2();
		}

		// on change item, set price per unit

		jQuery('body').on('change', '.b2bking_offer_product_selector', function($ab){
			let price = jQuery(this).find('option:selected').data('price');
			if (price !== '' && price !== undefined){
				$(this).parent().parent().find('.b2bking_offer_item_price').val(price);
				offerCalculateTotals();
			}
		});



		// On click "delete"
		$('body').on('click', '.b2bking_offer_delete_item_button', function(){
			$(this).parent().parent().remove();
			offerCalculateTotals();
			offerSetHiddenField();
		});

		// On quantity or price change, calculate totals
		$('body').on('input', '.b2bking_offer_item_quantity, .b2bking_offer_item_name, .b2bking_offer_item_price', function(){
			offerCalculateTotals();
			offerSetHiddenField();
		});
		
		function offerCalculateTotals(){
			var currency_symbol_used = b2bking.currency_symbol;
			if (currency_symbol_used === 'ر.س'){
				currency_symbol_used = b2bking.modified_currency_symbol;
			}
			let total = 0;
			// foreach item calculate subtotal
			$('.b2bking_offer_item_quantity').each(function(){
				let quantity = $(this).val();
				let price = $(this).parent().parent().find('.b2bking_offer_item_price').val();
				if (quantity !== undefined && price !== undefined){
					// set subtotal
					total+=price*quantity;
					$(this).parent().parent().find('.b2bking_item_subtotal').text(currency_symbol_used+Number((price*quantity).toFixed(4)));
				}
			});

			// finished, add up subtotals to get total
			$('#b2bking_offer_total_text_number').text(currency_symbol_used+Number((total).toFixed(4)));
		}

		function offerSetHiddenField(){
			let field = '';
			// clear textarea
			$('#b2bking_admin_offer_textarea').val('');
			// go through all items and list them IF they have PRICE AND QUANTITY
			$('.b2bking_offer_item_quantity').each(function(){
				let quantity = $(this).val();
				let price = $(this).parent().parent().find('.b2bking_offer_item_price').val();
				if (quantity !== undefined && price !== undefined && quantity !== null && price !== null && quantity !== '' && price !== ''){
					// Add it to string
					let name = $(this).parent().parent().find('.b2bking_offer_item_name').val();
					if (name === undefined || name === ''){
						name = '(no title)';
					}
					field+= name+';'+quantity+';'+price+'|';
				}
			});

			// at the end, remove last character
			field = field.substring(0, field.length - 1);
			$('#b2bking_admin_offer_textarea').val(field);
		}

		function offerRetrieveHiddenField(){
			// get field;
			let field = $('#b2bking_admin_offer_textarea').val();
			let itemsArray = field.split('|');
			// foreach condition, add condition, add new item
			itemsArray.forEach(function(item){
				let itemDetails = item.split(';');
				if (itemDetails[0] !== undefined && itemDetails[0] !== ''){
					$('#b2bking_offer_number_'+offerItemsCounter+' .b2bking_offer_item_name').val(itemDetails[0]);
					$('#b2bking_offer_number_'+offerItemsCounter+' .b2bking_offer_item_quantity').val(itemDetails[1]);
					$('#b2bking_offer_number_'+offerItemsCounter+' .b2bking_offer_item_price').val(itemDetails[2]);
					addNewOfferItem();
				}
			});
			// at the end, remove the last Item added
			if (offerItemsCounter > 1){
				$('#b2bking_offer_number_'+offerItemsCounter).remove();
			}

		}

		/* USER SHIPPING AND PAYMENT METHODS PANEL */

		// On load, update 
		updateUserShippingPayment();
		// On change, update
		$('.b2bking_user_shipping_payment_methods_container_content_override_select').change(updateUserShippingPayment);

		function updateUserShippingPayment(){
			let selectedValue = $('.b2bking_user_shipping_payment_methods_container_content_override_select').val();
			if (selectedValue === 'default'){
				// hide shipping and payment methods
				$('.b2bking_user_payment_shipping_methods_container').css('display','none');
			} else if (selectedValue === 'manual'){
				// show shipping and payment methods
				$('.b2bking_user_payment_shipping_methods_container').css('display','flex');
			}
		}

		/* REGISTRATION FIELD */

		// On load, show hide user choices 
		showHideUserChoices();

		$('.b2bking_custom_field_settings_metabox_bottom_field_type_select').change(showHideUserChoices);

		function showHideUserChoices(){
			let selectedValue = $('.b2bking_custom_field_settings_metabox_bottom_field_type_select').val();
			if (selectedValue === 'select' || selectedValue === 'checkbox' || selectedValue === 'radio'){
				$('.b2bking_custom_field_settings_metabox_bottom_user_choices').css('display','block');
			} else {
				$('.b2bking_custom_field_settings_metabox_bottom_user_choices').css('display','none');
			}
		}

		/* USER REGISTRATION DATA - APPROVE REJECT */
		$('.b2bking_user_registration_user_data_container_element_approval_button_approve').on('click', function(){

			Swal.fire({
			  title: b2bking.are_you_sure,
			  text: b2bking.are_you_sure_approve,
			  icon: 'warning',
			  showCancelButton: true,
			  confirmButtonColor: '#20d580',
			  cancelButtonColor: '#e85347',
			  confirmButtonText: b2bking.yes_confirm
			}).then((result) => {
			  if (result.isConfirmed) {
			  		var datavar = {
				            action: 'b2bkingapproveuser',
				            security: b2bking.security,
				            chosen_group: $('.b2bking_user_registration_user_data_container_element_select_group').val(),
				            credit: $('#b2bking_approval_credit_user').val(),
				            salesagent: $('#salesking_assign_sales_agent').val(),
				            user: $('#b2bking_user_registration_data_id').val(),
				        };

						$.post(ajaxurl, datavar, function(response){
							location.reload();
						});
			  }
			});
		});

		$('.b2bking_user_registration_user_data_container_element_approval_button_reject').on('click', function(){

			Swal.fire({
			  title: b2bking.are_you_sure,
			  text: b2bking.are_you_sure_reject,
			  icon: 'warning',
			  showCancelButton: true,
			  confirmButtonColor: '#3085d6',
			  cancelButtonColor: '#e85347',
			  confirmButtonText: b2bking.yes_confirm
			}).then((result) => {
			  if (result.isConfirmed) {
	  				var datavar = {
	  		            action: 'b2bkingrejectuser',
	  		            security: b2bking.security,
	  		            user: $('#b2bking_user_registration_data_id').val(),
	  		        };

	  				$.post(ajaxurl, datavar, function(response){
	  					window.location = b2bking.admin_url+'/users.php';
	  				});
			  }
			});

		});

		$('.b2bking_user_registration_user_data_container_element_approval_button_deactivate').on('click', function(){

			Swal.fire({
			  title: b2bking.are_you_sure,
			  text: b2bking.are_you_sure_deactivate,
			  icon: 'warning',
			  showCancelButton: true,
			  confirmButtonColor: '#3085d6',
			  cancelButtonColor: '#e85347',
			  confirmButtonText: b2bking.yes_confirm
			}).then((result) => {
			  if (result.isConfirmed) {
					var datavar = {
			            action: 'b2bkingdeactivateuser',
			            security: b2bking.security,
			            user: $('#b2bking_user_registration_data_id').val(),
			        };

					$.post(ajaxurl, datavar, function(response){
						location.reload();
					});
			  }
			});

		});

		// Download registration files
		$('.b2bking_user_registration_user_data_container_element_download').on('click', function(){
			let attachment = $(this).val();
			if (parseInt(b2bking.download_go_to_file) === 1){
				var datavar = {
		            action: 'b2bkinghandledownloadrequest',
		            security: b2bking.security,
		            attachment: attachment,
		        };

				$.post(ajaxurl, datavar, function(response){

					let url = response;
					var a = document.createElement("a");
					a.href = url;
					let fileName = url.split("/").pop();
					a.download = fileName;
					document.body.appendChild(a);
					a.click();
					window.URL.revokeObjectURL(url);
					a.remove();

				});
			} else if (parseInt(b2bking.download_go_to_file) === 2){
				window.location = b2bking.adminurl+'upload.php?item='+attachment;
			} else {
				window.location = ajaxurl + '?action=b2bkinghandledownloadrequest&attachment='+attachment+'&security=' + b2bking.security;
			}


			
		});
		
		updateAddToBilling();
		$('#b2bking_custom_field_billing_connection_metabox_select').change(updateAddToBilling);
		// Billing field connection, show add to billing only if default connection is none
		function updateAddToBilling(){
			let billingConnectionSelected = $('#b2bking_custom_field_billing_connection_metabox_select').val();
			if (billingConnectionSelected === 'none' || billingConnectionSelected === 'billing_vat'){
				$('.b2bking_add_to_billing_container').css('display', '');
			} else {
				$('.b2bking_add_to_billing_container').css('display', 'none');
			}

			// Show VAT container only if selected billing connection is VAT
			if (billingConnectionSelected === 'billing_vat'){
				$('.b2bking_VAT_container').css('display', 'flex');
			} else {
				$('.b2bking_VAT_container').css('display', 'none');
			}

			if (billingConnectionSelected === 'custom_mapping'){
				$('.b2bking_custom_mapping_container').css('display', 'flex');
			}  else {
				$('.b2bking_custom_mapping_container').css('display', 'none');
			}
		}

		// show hide Registration Role Automatic Approval - show only if automatic approval is selected
		showHideAutomaticApprovalGroup();
		$('.b2bking_custom_role_settings_metabox_container_element_select').change(showHideAutomaticApprovalGroup);
		function showHideAutomaticApprovalGroup(){
			let selectedValue = $('.b2bking_custom_role_settings_metabox_container_element_select').val();
			if (selectedValue === 'automatic'){
				$('.b2bking_automatic_approval_customer_group_container').css('display','block');
			} else {
				$('.b2bking_automatic_approval_customer_group_container').css('display','none');
			}
		}

		// show hide multiple roles selector
		showHideMultipleRolesSelector();
		$('.b2bking_custom_field_settings_metabox_top_column_registration_role_select').change(showHideMultipleRolesSelector);
		function showHideMultipleRolesSelector(){
			let selectedValue = $('.b2bking_custom_field_settings_metabox_top_column_registration_role_select').val();
			if (selectedValue === 'multipleroles'){
				$('#b2bking_select_multiple_roles_selector').css('display','block');
			} else {
				$('#b2bking_select_multiple_roles_selector').css('display','none');
			}
		}

		// Tools
		// On clicking download price list
		$('#b2bking_download_products_button').on('click', function() {
		    window.location = ajaxurl + '?action=b2bkingdownloadpricelist&security=' + b2bking.security;
	    });

	    // Download troubleshooting file
	    $('#b2bking_download_troubleshooting_button').on('click', function() {
		    window.location = ajaxurl + '?action=b2bkingdownloadtroubleshooting&security=' + b2bking.security;
	    });

	    // On clicking set all users to group
	    $('#b2bking_set_users_in_group').on('click', function(){

			Swal.fire({
			  title: b2bking.are_you_sure,
			  text: b2bking.are_you_sure_set_users,
			  icon: 'warning',
			  showCancelButton: true,
			  confirmButtonColor: '#20d580',
			  cancelButtonColor: '#e85347',
			  confirmButtonText: b2bking.yes_confirm
			}).then((result) => {
			  if (result.isConfirmed) {

		  			var datavar = {
		  	            action: 'b2bkingbulksetusers',
		  	            security: b2bking.security,
		  	            chosen_group: $('#b2bking_customergroup').val(),
		  	        };

		  			$.post(ajaxurl, datavar, function(response){
						Swal.fire(
		    	            b2bking.users_have_been_moved, 
		    	            "", 
		    	            "success"
		    	        );
		  			});
			  }
			});

	    });

        // On clicking set category in bulk
        $('#b2bking_set_category_in_bulk').on('click', function(){
        
    		Swal.fire({
    		  title: b2bking.are_you_sure,
    		  text: b2bking.are_you_sure_set_categories,
    		  icon: 'warning',
    		  showCancelButton: true,
    		  confirmButtonColor: '#20d580',
    		  cancelButtonColor: '#e85347',
    		  confirmButtonText: b2bking.yes_confirm
    		}).then((result) => {
    		  if (result.isConfirmed) {

  	    			var datavar = {
  	    	            action: 'b2bkingbulksetcategory',
  	    	            security: b2bking.security,
  	    	            chosen_option: $('#b2bking_categorybulk').val(),
  	    	        };

  	    			$.post(ajaxurl, datavar, function(response){

						Swal.fire(
		    	            b2bking.categories_have_been_set, 
		    	            "", 
		    	            "success"
		    	        );
  		        	});
    		  }
    		});
        });

        // On clicking set accounts as subaccounts
        $('#b2bking_set_accounts_as_subaccounts').on('click', function(){

    		Swal.fire({
    		  title: b2bking.are_you_sure,
    		  text: b2bking.are_you_sure_set_subaccounts,
    		  icon: 'warning',
    		  showCancelButton: true,
    		  confirmButtonColor: '#20d580',
    		  cancelButtonColor: '#e85347',
    		  confirmButtonText: b2bking.yes_confirm
    		}).then((result) => {
    		  if (result.isConfirmed) {

		  			var datavar = {
		  	            action: 'b2bkingbulksetsubaccounts',
		  	            security: b2bking.security,
		  	            option_first: $('#b2bking_set_user_subaccounts_first').val(),
		  	            option_second: $('#b2bking_set_user_subaccounts_second').val(),
		  	        };

		  			$.post(ajaxurl, datavar, function(response){
						Swal.fire(
		    	            b2bking.subaccounts_have_been_set, 
		    	            "", 
		    	            "success"
		    	        );
		  			});
    		  }
    		});
        });

        // On clicking set accounts as regular accounts
        $('#b2bking_set_subaccounts_regular_button').on('click', function(){


    		Swal.fire({
    		  title: b2bking.are_you_sure,
    		  text: b2bking.are_you_sure_set_subaccounts_regular,
    		  icon: 'warning',
    		  showCancelButton: true,
    		  confirmButtonColor: '#20d580',
    		  cancelButtonColor: '#e85347',
    		  confirmButtonText: b2bking.yes_confirm
    		}).then((result) => {
    		  if (result.isConfirmed) {

		  			var datavar = {
		  	            action: 'b2bkingbulksetsubaccountsregular',
		  	            security: b2bking.security,
		  	            option_first: $('#b2bking_set_subaccounts_regular_input').val(),
		  	        };

		  			$.post(ajaxurl, datavar, function(response){
							Swal.fire(
			    	            b2bking.subaccounts_have_been_set, 
			    	            "", 
			    	            "success"
			    	        );
		  			});
    		  }
    		});
        });

        // On clicking update b2bking user data (registration data)
        $('#b2bking_update_registration_data_button').on('click', function(){

			Swal.fire({
			  title: b2bking.are_you_sure,
			  text: b2bking.are_you_sure_update_user,
			  icon: 'warning',
			  showCancelButton: true,
			  confirmButtonColor: '#20d580',
			  cancelButtonColor: '#e85347',
			  confirmButtonText: b2bking.yes_confirm
			}).then((result) => {
			  if (result.isConfirmed) {
			  		var fields = $('#b2bking_admin_user_fields_string').val();
		    		var fieldsArray = fields.split(',');

					var datavar = {
			            action: 'b2bkingupdateuserdata',
			            security: b2bking.security,
			            userid: $('#b2bking_admin_user_id').val(),
			            field_strings: fields,
			        };

			        fieldsArray.forEach(myFunction);

			        function myFunction(item, index) {
			        	if (parseInt(item.length) !== 0){
			        		let value = $('input[name=b2bking_custom_field_'+item+']').val();
			        		if (value !== null){
			        			let key = 'field_'+item;
			        			datavar[key] = value;
			        		}
			        	}
			        }

					$.post(ajaxurl, datavar, function(response){
						if (response.startsWith('vatfailed')){

							Swal.fire(
			    	            b2bking.issue_occurred, 
			    	            b2bking.user_has_been_updated_vat_failed, 
			    	            "warning"
			    	        );

						} else {

							Swal.fire(
			    	            b2bking.success, 
			    	            b2bking.user_has_been_updated, 
			    	            "success"
			    	        );
						}

						
					});
			  }
			});
        });

        // on clicking "add tier" in the product page
        $('.b2bking_product_add_tier').on('click', function(){
        	var groupid = $(this).parent().find('.b2bking_groupid').val();
        	$('<span class="wrap b2bking_product_wrap"><input name="b2bking_group_'+groupid+'_pricetiers_quantity[]" placeholder="'+b2bking.min_quantity_text+'" class="b2bking_tiered_pricing_element" type="number" step="any" min="0" /><input name="b2bking_group_'+groupid+'_pricetiers_price[]" placeholder="'+b2bking.final_price_text+'" class="b2bking_tiered_pricing_element short wc_input_price" type="text"  /></span>').insertBefore($(this).parent());
        });

        // on clicking "add row" in the product page
        $('.b2bking_product_add_row').on('click', function(){
        	var groupid = $(this).parent().find('.b2bking_groupid').val();
        	$('<span class="wrap b2bking_customrows_wrap"><input name="b2bking_group_'+groupid+'_customrows_label[]" placeholder="'+b2bking.label_text+'" class="b2bking_customrow_element" type="text" /><input name="b2bking_group_'+groupid+'_customrows_text[]" placeholder="'+b2bking.text_text+'" class="b2bking_customrow_element" type="text" /></span>').insertBefore($(this).parent());
        });

        // on clicking "add tier" in the product variation page
        $('body').on('click', '.b2bking_product_add_tier_variation', function(event) {
        	var groupid = $(this).parent().find('.b2bking_groupid').val();
        	var variationid = $(this).parent().find('.b2bking_variationid').val();
            $('<span class="wrap b2bking_product_wrap_variation"><input name="b2bking_group_'+groupid+'_'+variationid+'_pricetiers_quantity[]" placeholder="'+b2bking.min_quantity_text+'" class="b2bking_tiered_pricing_element_variation" type="number" step="any" min="0" /><input name="b2bking_group_'+groupid+'_'+variationid+'_pricetiers_price[]" placeholder="'+b2bking.final_price_text+'" class="b2bking_tiered_pricing_element_variation short wc_input_price" type="text" /></span>').insertBefore($(this).parent());
        });

        $('#b2bking_b2b_pricing_variations').detach().insertAfter('option[value=delete_all]');

        // bulk edit variations
        $( '.wc-metaboxes-wrapper' ).on('change', '#field_to_edit', function(){
        	var do_variation_action = $( 'select.variation_actions' ).val();
        	if (do_variation_action.startsWith('b2bking')){
        		var value = prompt(woocommerce_admin_meta_boxes_variations.i18n_enter_a_value);
        		var values = do_variation_action.split('_');

        		var regularsale = values[1];
        		var productid = values[4];
        		var groupid = values[6];

				var datavar = {
		            action: 'b2bkingbulksetvariationprices',
		            security: b2bking.security,
		            price: value,
		            regular_sale: regularsale,
		            product_id: productid,
		            group_id: groupid,
		        };

				$.post(ajaxurl, datavar, function(response){
					location.reload();
				});
        	}
        });

        // print user registration data
        $('#b2bking_print_user_data').on('click', function(){
        	var printContents = document.getElementById('b2bking_registration_data_container').innerHTML;
			var originalContents = document.body.innerHTML;

			document.body.innerHTML = printContents;

			window.print();

			document.body.innerHTML = originalContents;
        });

 	
 		// on click Select all
 		$('.b2bking_select_all_products_backend').on('click', function(){
 		    $('.b2bking_select_multiple_product_categories_selector_select').select2('destroy').find('optgroup:nth-child(2) option').prop('selected', 'selected').end().select2();
 		});
 		$('.b2bking_select_all_variations_backend').on('click', function(){
 		    $('.b2bking_select_multiple_product_categories_selector_select').select2('destroy').find('optgroup:nth-child(3) option').prop('selected', 'selected').end().select2();
 		});
 		$('.b2bking_select_all_categories_backend').on('click', function(){
 		    $('.b2bking_select_multiple_product_categories_selector_select').select2('destroy').find('optgroup:nth-child(1) option').prop('selected', 'selected').end().select2();
 		});
 		$('.b2bking_unselect_all_products_backend').on('click', function(){
 		    $(".b2bking_select_multiple_product_categories_selector_select").val(null).trigger("change");
 		});

 		// product and variation ID(s)
 		$('.b2bking_select_all_products_variations_backend').on('click', function(){
 		    $('#b2bking_rule_select_applies_replaced').val($('#b2bking_all_products_variations').val());
 		});
 		$('.b2bking_select_all_variations_variations_backend').on('click', function(){
 		    $('#b2bking_rule_select_applies_replaced').val($('#b2bking_all_variations_variations').val());
 		});
 		$('.b2bking_unselect_all_products_variations_backend').on('click', function(){
 		    $("#b2bking_rule_select_applies_replaced").val(null).trigger("change");
 		});

 		/* sort drag drop backend */

 		function enable_sortable_type(type){
 			jQuery('.post-type-b2bking_custom_field .wp-list-table tbody, .b2bking_page_b2bking_custom_field .wp-list-table tbody, .post-type-b2bking_quote_field .wp-list-table tbody,  .b2bking_page_b2bking_quote_field .wp-list-table tbody, .post-type-b2bking_custom_role .wp-list-table tbody,  .b2bking_page_b2bking_custom_role .wp-list-table tbody').sortable({
 			    placeholder: {
 			        element: function(currentItem) {
 			            var cols    =   jQuery(currentItem).children('td:visible').length + 1;
 			            return jQuery('<tr class="ui-sortable-placeholder"><td colspan="' + cols + '">&nbsp;</td></tr>')[0];
 			        },
 			        update: function(container, p) {
 			            return;
 			        }
 			    },

 			    'items': 'tr',
 			    'handle': ".b2bking_sort",
 			    'axis': 'y',
 			    'update' : function(e, ui) {
 			       
 			        var post_type           =   type;
 			        var order               =   jQuery('#the-list').sortable('serialize');
 			        var queryString = { "action": "b2bking_update_sort_menu_order", "post_type" : post_type, "order" : order};
 			        //send the data through ajax
 			        jQuery.ajax({
 			          type: 'POST',
 			          url: ajaxurl,
 			          data: queryString,
 			          cache: false,
 			          dataType: "html",
 			          success: function(data){
 						const Toast = Swal.mixin({
		        		  toast: true,
		        		  position: 'bottom-end',
		        		  showConfirmButton: false,
		        		  timer: 1000,
		        		  timerProgressBar: false,
		        		  didOpen: (toast) => {
		        		    toast.addEventListener('mouseenter', Swal.stopTimer)
		        		    toast.addEventListener('mouseleave', Swal.resumeTimer)
		        		  }
		        		});
 						Toast.fire({
	        			  icon: 'success',
	        			  title: b2bking.settings_saved
	        			});
 			          },
 			          error: function(html){

 			              }
 			        });
 			    }
 			});

 			tippy('.sort_order_tip', {
		       animation: 'shift-away',
		       theme: 'material',
		       content: '<span style="text-align:center;line-height:25px;padding:5px;display:block">'+b2bking.sort_order_help_tip+'</span>',
		       allowHTML: true,
		    });

 			tippy('.form_preview_help_tip', {
		       animation: 'shift-away',
		       theme: 'material',
		       content: '<span style="text-align:center;line-height:25px;padding:5px;display:block">'+b2bking.form_preview_help_tip+'</span>',
		       allowHTML: true,
		    });
		    

 			tippy('.b2bking_edit_icon_hover_placeholder, .b2bking_edit_icon_hover_label', {
		       animation: 'shift-away',
		       theme: 'material',
		       content: '<span style="text-align:center;line-height:15px;padding:0px;display:block;font-size:11px">'+b2bking.quick_edit+'</span>',
		       allowHTML: true,
		    });

 		}
 		if (parseInt(jQuery('.post-type-b2bking_custom_field').length) !== 0 || parseInt(jQuery('.b2bking_page_b2bking_custom_field').length) !== 0){
 			enable_sortable_type('b2bking_custom_field');
 		}
 		if (parseInt(jQuery('.post-type-b2bking_quote_field').length) !== 0 || parseInt(jQuery('.b2bking_page_b2bking_quote_field').length) !== 0){
 			enable_sortable_type('b2bking_quote_field');
 		}
 		if (parseInt(jQuery('.post-type-b2bking_custom_role').length) !== 0 || parseInt(jQuery('.b2bking_page_b2bking_custom_role').length) !== 0){
 			enable_sortable_type('b2bking_custom_role');
 		}

	 	/* registration fields opacity */
	 	function set_registration_fields_opacity(){
 		 	jQuery('tr.type-b2bking_custom_field, tr.type-b2bking_custom_role, tr.type-b2bking_offer, tr.type-b2bking_grule, tr.type-b2bking_rule, tr.type-b2bking_quote_field').each(function(){
 		 		let row = $(this);
 				let checkbox = $(this).find('.b2bking_status .b2bking_switch_input');
 				if (checkbox.is(':checked')){
 					row.css('opacity','1');
 				} else {
 					row.css('opacity','0.45');
 				}
 			});
	 	}
	 	
		jQuery('body').on('change', '.b2bking_switch_input', function(){
			set_registration_fields_opacity();

			// call ajax
			var datavar = {
	            action: 'b2bkingchangefield',
	            enabled: $(this).is(':checked'),
	            security: b2bking.security,
	            fieldid: $(this).attr('id').split('_')[3]
	        };

	        var checkbox = $(this);

	        // for dynamic rules, show/hide "disabled / draft" status.
	        let poststate = $(this).parent().parent().parent().find('.type-b2bking_rule .post-state');
	        if (datavar.enabled){
	        	$(poststate).text(b2bking.enabled);
	        } else {
	        	$(poststate).text(b2bking.disabled);
	        }

	        	        
			$.post(ajaxurl, datavar, function(response){
				if (response === 'success'){

					// if rules, delay further to also clear rule caches, clear cache only once
					if (jQuery('.type-b2bking_rule').length > 0){

						// rules page, must clear cache
						rules_in_queue--; // one less rule in queue

						// clear caches only if there are no more rules in queue
						if (rules_in_queue <= 0){
							rules_in_queue = 0;
							// call ajax
							var datavar = {
					            action: 'b2bking_clear_rules_caches',
					            security: b2bking.security,
					        };

					        $.post(ajaxurl, datavar, function(response){
					        	if (response === 'success'){

					        		// fire success
					        		const Toast = Swal.mixin({
					        		  toast: true,
					        		  position: 'bottom-end',
					        		  showConfirmButton: false,
					        		  timer: 1000,
					        		  timerProgressBar: false,
					        		  didOpen: (toast) => {
					        		    toast.addEventListener('mouseenter', Swal.stopTimer)
					        		    toast.addEventListener('mouseleave', Swal.resumeTimer)
					        		  }
					        		});

					        		// set delay so that if select all / enable disable is clicked, there's not a rapid fire of 20 toasts
					        		let current_time = Date.now(); // milliseconds
					        		// if 1000ms passed, you can fire it again
					        		if ((current_time - last_toast_success_fired_time) > 250) {
					        			Toast.fire({
					        			  icon: 'success',
					        			  title: b2bking.settings_saved
					        			});
					        			last_toast_success_fired_time = Date.now();
					        		}
					        	}
					        });
						}

					} else {
						// fire success
						const Toast = Swal.mixin({
						  toast: true,
						  position: 'bottom-end',
						  showConfirmButton: false,
						  timer: 1000,
						  timerProgressBar: false,
						  didOpen: (toast) => {
						    toast.addEventListener('mouseenter', Swal.stopTimer)
						    toast.addEventListener('mouseleave', Swal.resumeTimer)
						  }
						})

						// set delay so that if select all / enable disable is clicked, there's not a rapid fire of 20 toasts
						let current_time = Date.now(); // milliseconds
						// if 1000ms passed, you can fire it again
						if ((current_time - last_toast_success_fired_time) > 250) {
							Toast.fire({
							  icon: 'success',
							  title: b2bking.settings_saved
							});
							last_toast_success_fired_time = Date.now();
						}
					}

					
					
				}
			});
		});

		jQuery('body').on('change', '.b2bking_switch_input_required', function(){

			// add or remove *
			// call ajax
			var datavar = {
	            action: 'b2bkingchangefieldrequired',
	            enabled: $(this).is(':checked'),
	            security: b2bking.security,
	            fieldid: $(this).attr('id').split('_')[4]
	        };

	        if (datavar.enabled){
	        	$(this).parent().parent().find('.b2bking_preview .required').css('display','inline-block');
	        } else {
	        	$(this).parent().parent().find('.b2bking_preview .required').css('display','none');
	        }

	        var checkbox = $(this);
	        	        
			$.post(ajaxurl, datavar, function(response){
				if (response === 'success'){

					const Toast = Swal.mixin({
					  toast: true,
					  position: 'bottom-end',
					  showConfirmButton: false,
					  timer: 1000,
					  timerProgressBar: false,
					  didOpen: (toast) => {
					    toast.addEventListener('mouseenter', Swal.stopTimer)
					    toast.addEventListener('mouseleave', Swal.resumeTimer)
					  }
					})

					Toast.fire({
					  icon: 'success',
					  title: b2bking.settings_saved
					});
				}
			});
		});

		// on clicking Registration Form Shortcodes button
		$('body').on('click','#b2bking_registration_form_shortcode_button', function(){
			Swal.fire({
			  title: '<strong>Registration Form Shortcodes List</strong>',
			  width: 1000,
			  icon: 'question',
			  html: b2bking.registration_form_shortcodes_html,			    
			  showCloseButton: true,
			  showCancelButton: false,
			  showConfirmButton: false,
			  customClass: {
			    icon: 'b2bking_registration_shortcodes_icon',
			    title: 'b2bking_registration_shortcodes_title',
			    container: 'b2bking_registration_shortcodes_container',
			  }
			});

 			tippy('.b2bking_rshortcode_icon', {
		       animation: 'shift-away',
		       theme: 'material',
		       content: '<span style="text-align:center;line-height:25px;padding:5px;display:block">'+b2bking.click_to_copy+'</span>',
		       allowHTML: true,
		    });
		    tippy('#b2bking_copied_rform', {
		      // default
		      trigger: 'click',
		      content: '<span style="text-align:center;line-height:25px;padding:5px;display:block">'+b2bking.copied+'</span>',
		      allowHTML: true,
		      arrow: false,
		      placement:'right',
		    });

		    $('.b2bking_rshortcode_icon').on('click', function(){

		    	// get current id
		    	let roleid = $(this).parent().parent().find('.roleid').val();
		    	let buildid = 'b2bking_rshortcode_form_'+roleid;

		    	var copyText = document.getElementById(buildid);
		    	copyText.select();
		    	copyText.setSelectionRange(0, 99999); /* For mobile devices */

		    	/* Copy the text inside the text field */
		    	document.execCommand("copy");
		    	$('#b2bking_copied_rform').click();

		    });
		    $('.b2bking_rinclude_login_form').on('change', function(){
		    	let shortcodeinput = $(this).parent().parent().find('.b2bking_rshortcode_form');
		    	let shortcode = shortcodeinput.val();

		    	if ($(this).is(':checked')){
		    		shortcode = shortcode.replace('b2bking_b2b_registration_only','b2bking_b2b_registration');
		    	} else {
		    		shortcode = shortcode.replace('b2bking_b2b_registration_only','b2bking_b2b_registration');
		    		shortcode = shortcode.replace('b2bking_b2b_registration','b2bking_b2b_registration_only');
		    	}

		    	shortcodeinput.val(shortcode);
		    });
		});

		// on clicking BULK ORDER Form Shortcodes button
		$('body').on('click','#b2bking_bulkorder_form_shortcode_button', function(){
			Swal.fire({
			  title: '<strong>Order Form Shortcodes List</strong>',
			  width: 1000,
			  icon: 'question',
			  html: b2bking.bulkorder_form_shortcodes_html,			    
			  showCloseButton: true,
			  showCancelButton: false,
			  showConfirmButton: false,
			  customClass: {
			    icon: 'b2bking_registration_shortcodes_icon',
			    title: 'b2bking_registration_shortcodes_title',
			    container: 'b2bking_registration_shortcodes_container b2bking_bulkorder_shortcodes_container',
			  }
			});

 			tippy('.b2bking_rshortcode_icon', {
		       animation: 'shift-away',
		       theme: 'material',
		       content: '<span style="text-align:center;line-height:25px;padding:5px;display:block">'+b2bking.click_to_copy+'</span>',
		       allowHTML: true,
		    });
		    tippy('#b2bking_copied_rform', {
		      // default
		      trigger: 'click',
		      content: '<span style="text-align:center;line-height:25px;padding:5px;display:block">'+b2bking.copied+'</span>',
		      allowHTML: true,
		      arrow: false,
		      placement:'right',
		    });

		    $('.b2bking_rshortcode_icon').on('click', function(){

		    	// get current id
		    	let roleid = $(this).parent().parent().find('.roleid').val();
		    	let buildid = 'b2bking_rshortcode_form_'+roleid;

		    	var copyText = document.getElementById(buildid);
		    	copyText.select();
		    	copyText.setSelectionRange(0, 99999); /* For mobile devices */

		    	/* Copy the text inside the text field */
		    	document.execCommand("copy");
		    	$('#b2bking_copied_rform').click();

		    });
		});

		/* registration fields on click quick edit PLACEHOLDER */
		$('body').on('click', '.b2bking_edit_icon_hover_placeholder', function(){
			let row = $(this).parent();
			let startingtextfield = row.find('.b2bking_text');
			let edittextinput = row.find('.b2bking_edit_placeholder_input');
			let startingtext = startingtextfield.text();

			// hide field, show input edit
			startingtextfield.css('display','none');
			edittextinput.css('display','block');

			// copy text from starting to input 
			edittextinput.val(startingtext).focus().val(startingtext); // double needed for focus

			// hide edit icon, show confirm icon
			$(this).css('display','none');
			row.find('.b2bking_confirm_icon_hover').css('display','flex');
		});

		// save placeholder text on focusout, eand on enter
		$('body').on('focusout', '.b2bking_edit_placeholder_input', function(){
			save_placeholder_text($(this));
		});

		$('body').on('keypress', '.b2bking_edit_placeholder_input', function(e) {
		    var code = e.keyCode || e.which;
		    if(code==13){
		        $(this).blur();
		        e.preventDefault();
		        e.stopPropagation();
		    }
		});

		$('body').on('input', '.b2bking_edit_placeholder_input', function(){
			let row = $(this).parent().parent();
			let newtext = $(this).val();
			let previewinput = row.find('.b2bking_field_preview input');
			previewinput.attr('placeholder',newtext);

		});

		function save_placeholder_text(element){
			let row = $(element).parent();
			let startingtextfield = row.find('.b2bking_text');
			let edittextinput = row.find('.b2bking_edit_placeholder_input');
			let startingtext = startingtextfield.text();
			let editedtext = edittextinput.val();

			// hide field, show input edit
			startingtextfield.css('display','block');
			edittextinput.css('display','none');

			// copy text from input to starting
			startingtextfield.text(editedtext);

			// hide edit icon, show confirm icon
			$(element).css('display','none');
			row.find('.b2bking_edit_icon_hover_placeholder').css('display','');

			// save and show "saved successfully"
			var datavar = {
	            action: 'b2bkingsavefieldplaceholder',
	            security: b2bking.security,
	            fieldid: $(element).parent().parent().attr('id').split('-')[1],
	            text: editedtext,
	        };
	       	
			$.post(ajaxurl, datavar, function(response){
				if (response === 'success'){

					const Toast = Swal.mixin({
					  toast: true,
					  position: 'bottom-end',
					  showConfirmButton: false,
					  timer: 1000,
					  timerProgressBar: false,
					  didOpen: (toast) => {
					    toast.addEventListener('mouseenter', Swal.stopTimer)
					    toast.addEventListener('mouseleave', Swal.resumeTimer)
					  }
					})

					Toast.fire({
					  icon: 'success',
					  title: b2bking.settings_saved
					});
				}
			});
		}

		/* registration fields on click quick edit LABEL */
		$('body').on('click', '.b2bking_edit_icon_hover_label', function(){
			let row = $(this).parent();
			let startingtextfield = row.find('.b2bking_text');
			let edittextinput = row.find('.b2bking_edit_label_input');
			let startingtext = startingtextfield.text();

			// hide field, show input edit
			startingtextfield.css('display','none');
			edittextinput.css('display','block');

			// copy text from starting to input 
			edittextinput.val(startingtext).focus().val(startingtext); // double needed for focus

			// hide edit icon, show confirm icon
			$(this).css('display','none');
			row.find('.b2bking_confirm_icon_hover').css('display','flex');
		});

		// save label text on focusout, eand on enter
		$('body').on('focusout', '.b2bking_edit_label_input', function(){
			save_label_text($(this));
		});

		$('body').on('keypress', '.b2bking_edit_label_input', function(e) {
		    var code = e.keyCode || e.which;
		    if(code==13){
		        $(this).blur();
		        e.preventDefault();
		        e.stopPropagation();
		    }
		});

		$('body').on('input', '.b2bking_edit_label_input', function(){
			let row = $(this).parent().parent();
			let newtext = $(this).val();
			let previewinput = row.find('.b2bking_field_preview .b2bking_label_text_preview');
			previewinput.text(newtext);

		});

		function save_label_text(element){
			let row = $(element).parent();
			let startingtextfield = row.find('.b2bking_text');
			let edittextinput = row.find('.b2bking_edit_label_input');
			let startingtext = startingtextfield.text();
			let editedtext = edittextinput.val();

			// hide field, show input edit
			startingtextfield.css('display','block');
			edittextinput.css('display','none');

			// copy text from input to starting
			startingtextfield.text(editedtext);

			// hide edit icon, show confirm icon
			$(element).css('display','none');
			row.find('.b2bking_edit_icon_hover_label').css('display','');

			// save and show "saved successfully"
			var datavar = {
		        action: 'b2bkingsavefieldlabel',
		        security: b2bking.security,
		        fieldid: $(element).parent().parent().attr('id').split('-')[1],
		        text: editedtext,
		    };
		   	
			$.post(ajaxurl, datavar, function(response){
				if (response === 'success'){

					const Toast = Swal.mixin({
					  toast: true,
					  position: 'bottom-end',
					  showConfirmButton: false,
					  timer: 1000,
					  timerProgressBar: false,
					  didOpen: (toast) => {
					    toast.addEventListener('mouseenter', Swal.stopTimer)
					    toast.addEventListener('mouseleave', Swal.resumeTimer)
					  }
					})

					Toast.fire({
					  icon: 'success',
					  title: b2bking.settings_saved
					});
				}
			});
		}

		// Toolbar actions
		$('body').on('click','.b2bking_toolbar_select.b2bking_select', function(){
			//$('.wp-list-table thead .check-column input').click();
			$('.type-b2bking_rule .check-column input, .type-b2bking_grule .check-column input, .type-b2bking_offer .check-column input, .type-b2bking_custom_role .check-column input, .type-b2bking_custom_field .check-column input, .type-b2bking_quote_field .check-column input').each(function(i){
				$(this).prop('checked', true);
				$(this).change();
			});
			set_button_unselect();
		});
		$('body').on('click','.b2bking_toolbar_select.b2bking_unselect', function(){
			//$('.wp-list-table thead .check-column input').click();
			$('.type-b2bking_rule .check-column input, .type-b2bking_grule .check-column input, .type-b2bking_offer .check-column input, .type-b2bking_custom_role .check-column input, .type-b2bking_custom_field .check-column input, .type-b2bking_quote_field .check-column input').each(function(i){
				$(this).prop('checked', false);
				$(this).change();
			});
			set_button_select();
		});


		$('body').on('change','.wp-list-table thead .check-column input, .wp-list-table tfoot .check-column input', function(){
			if ($(this).is(':checked')){
				set_button_unselect();
			} else {
				set_button_select();
			}
			// force change event on child checkboxes
			$(this).parent().parent().parent().parent().find('.type-b2bking_rule .check-column input, .type-b2bking_grule .check-column input, .type-b2bking_offer .check-column input, .type-b2bking_custom_role .check-column input, .type-b2bking_custom_field .check-column input, .type-b2bking_quote_field .check-column input').each(function(i){
				$(this).change();
			});
		});

		// change row color when checkboxes are activated
		$('body').on('change','.type-b2bking_rule .check-column input, .type-b2bking_grule .check-column input, .type-b2bking_offer .check-column input, .type-b2bking_custom_role .check-column input, .type-b2bking_custom_field .check-column input, .type-b2bking_quote_field .check-column input', function(){
			if ($(this).is(':checked')){
				$(this).parent().parent().addClass('b2bking_row_selected')
			} else {
				$(this).parent().parent().removeClass('b2bking_row_selected')
			}

			let selectedcount = get_selected_count();
			if (selectedcount > 0) {
				// show X selected message
				$('.b2bking_toolbar_selected_count').removeClass('b2bking_toolbar_selected_inactive');
				// enable action buttons
				$('.b2bking_toolbar_enable_disable').removeClass('b2bking_toolbar_inactive');

				set_button_unselect();
			} else {
				// hide X selected message
				$('.b2bking_toolbar_selected_count').addClass('b2bking_toolbar_selected_inactive');
				// disable action buttons
				$('.b2bking_toolbar_enable_disable').addClass('b2bking_toolbar_inactive');
				set_button_select();
			}
			$('.b2bking_toolbar_selected_count_number').text(selectedcount);


		});

		$('body').on('click','.b2bking_toolbar_enable', function(){
			
			$('.type-b2bking_rule .check-column input, .type-b2bking_grule .check-column input, .type-b2bking_offer .check-column input, .type-b2bking_custom_role .check-column input, .type-b2bking_custom_field .check-column input, .type-b2bking_quote_field .check-column input').each(function(i){
				if ($(this).is(':checked')){
					let status = $(this).parent().parent().find('.b2bking_status .b2bking_switch_input');
					if (!$(status).is(':checked')){
						// add rules in queue 
						if (jQuery('.type-b2bking_rule').length > 0){
							rules_in_queue++; // one less rule in queue
						}

						// check it
						$(status).click();
					}
				} 
			});
			clear_all_checkboxes();

		});
		$('body').on('click','.b2bking_toolbar_disable', function(){
			$('.type-b2bking_rule .check-column input, .type-b2bking_grule .check-column input, .type-b2bking_offer .check-column input, .type-b2bking_custom_role .check-column input, .type-b2bking_custom_field .check-column input, .type-b2bking_quote_field .check-column input').each(function(i){
				if ($(this).is(':checked')){
					let status = $(this).parent().parent().find('.b2bking_status .b2bking_switch_input');
					if ($(status).is(':checked')){
						// add rules in queue 
						if (jQuery('.type-b2bking_rule').length > 0){
							rules_in_queue++; // one less rule in queue
						}
						// check it
						$(status).click();
					}
				} 
			});
			clear_all_checkboxes();
		});
		function clear_all_checkboxes(){
			$('.type-b2bking_rule .check-column input, .type-b2bking_grule .check-column input, .type-b2bking_offer .check-column input, .type-b2bking_custom_role .check-column input, .type-b2bking_custom_field .check-column input, .type-b2bking_quote_field .check-column input').each(function(i){
				$(this).prop('checked', false);
				$(this).change();
			});
			set_button_select();

		}
		function get_selected_count(){
			let selected = 0;
			$('.type-b2bking_rule .check-column input, .type-b2bking_grule .check-column input, .type-b2bking_offer .check-column input, .type-b2bking_custom_role .check-column input, .type-b2bking_custom_field .check-column input, .type-b2bking_quote_field .check-column input').each(function(i){
				if ($(this).is(':checked')){
					selected++;
				} 
			});
			return selected;
		}
		function set_button_unselect(){
			$('.b2bking_toolbar_select .b2bking_toolbar_select_text').text(b2bking.unselect);
			$('.b2bking_toolbar_select').removeClass('b2bking_select').addClass('b2bking_unselect');
		}
		function set_button_select(){
			$('.b2bking_toolbar_select .b2bking_toolbar_select_text').text(b2bking.select_all);
			$('.b2bking_toolbar_select').addClass('b2bking_select').removeClass('b2bking_unselect');
		}

		// search bar searches real post
		$('body').on('input','.b2bking_searchbar_input', function(){
			let text = $(this).val();
			$('#post-search-input').val(text);
		});

		// toolbar settings open tab
		$('body').on('click', '#b2bking_toolbar_settings', function(){
			// show settings tab if hidden
			if ($('#b2bking_toolbar_settings_tab').hasClass('b2bking_toolbar_settings_tab_inactive')){
				$('#b2bking_toolbar_settings').addClass('b2bking_toolbar_settings_button_active');
				$('#b2bking_toolbar_settings_tab').removeClass('b2bking_toolbar_settings_tab_inactive');
			} else {
				// hide it if visible
				$('#b2bking_toolbar_settings').removeClass('b2bking_toolbar_settings_button_active');
				$('#b2bking_toolbar_settings_tab').addClass('b2bking_toolbar_settings_tab_inactive');
			}
			
		});
		// hide toolbar settings on click outside
		$(document).on('click', function(e) {
			if( e.target.id != 'b2bking_toolbar_settings_tab' && e.target.id != 'b2bking_toolbar_settings') {
				$("#b2bking_toolbar_settings_tab").addClass('b2bking_toolbar_settings_tab_inactive');
				$('#b2bking_toolbar_settings').removeClass('b2bking_toolbar_settings_button_active');
			}
		});
		// on click on number of posts per page
		$('body').on('click', '.b2bking_show_per_page_number', function(){
			let value = parseInt($(this).text());
			// save and show "saved successfully"
			var datavar = {
		        action: 'b2bking_save_posts_per_page',
		        security: b2bking.security,
		        value: value,
		    };
		   	
			$.post(ajaxurl, datavar, function(response){
				if (response === 'success'){
					location.reload();
				}
			});
		});
		// clear search button 
		$('body').on('click', '.b2bking_post_searchbar_clear', function(){
			$('#post-search-input').val('');
			$('#search-submit').click();
		});

		// refresh dashboard data
		$('body').on('click', '#b2bking_refresh_data_container', function(){
			var datavar = {
		        action: 'b2bking_refresh_dashboard_data',
		        security: b2bking.security,
		    };
		   	
			$.post(ajaxurl, datavar, function(response){
				if (response === 'success'){
					location.reload();
				}
			});
		});

		// backend pricing save customer when searching in backend orders
		$('#customer_user.wc-customer-search').on('change', function(){
			let customer = $(this).val();
			var datavar = {
	            action: 'b2bkingsaveordercustomer',
	            security: b2bking.security,
	            customer: customer
	        };
			$.post(ajaxurl, datavar, function(response){
				
			});
		});

		
		
		


	});

})(jQuery);
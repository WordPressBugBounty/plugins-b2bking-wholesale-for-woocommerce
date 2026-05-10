(function($){

	"use strict";

	$( document ).ready(function() {

		/**
		* General Functions
		*/

		// Initialize SemanticUI Menu Functions

		// radio buttons
		$('.ui.checkbox').checkbox();

		// Tab transition effect
		var previous = $('.ui.tab.segment.active');
	    $(".menu .item").tab({
	        onVisible: function (e) {
	            var current = $('.ui.tab.segment.active');
	            // hide the current and show the previous, so that we can animate them
	            previous.show();
	            current.hide();

	            // hide the previous tab - once this is done, we can show the new one
	            previous.find('.b2bking_attached_content_wrapper').css('opacity','0');
	            current.find('.b2bking_attached_content_wrapper').css('opacity','0');
	            setTimeout(function(){
	            	previous.hide();
	            	current.show();
	            	setTimeout(function(){
		            	current.find('.b2bking_attached_content_wrapper').css('opacity','1');
		            	// remember the current tab for next change
		            	previous = current;
		            },10);
	            },150);
	            
	        }
	    });
	    
		$('.ui.dropdown').dropdown();
		$('.b2bking_purchase_lists_language_setting').dropdown('set selected', b2bking.purchase_lists_language_option);
	
		$('.message .close').on('click', function() {
		    $(this).closest('.message').transition('fade');
		});

		var settingsSavedNoticeKey = 'b2bking_settings_saved_notice';

		// On Submit (Save Settings), Get Current Tab and Pass The Tab as a Setting. 
		$('#b2bking_admin_form').on('submit', function() {
			let tabInput = document.querySelector('#b2bking_current_tab_setting_input');
		    tabInput.value = document.querySelector('.item.active').dataset.tab;
		    sessionStorage.setItem(settingsSavedNoticeKey, '1');
		    return true; 
		});

		const urlParams = new URLSearchParams(window.location.search);
		if ((urlParams.get('settings-updated') === 'true' || sessionStorage.getItem(settingsSavedNoticeKey) === '1') && typeof Swal !== 'undefined'){
			sessionStorage.removeItem(settingsSavedNoticeKey);
			const Toast = Swal.mixin({
			  toast: true,
			  position: 'bottom-end',
			  showConfirmButton: false,
			  timer: 1000,
			  timerProgressBar: false,
			  customClass: {
			    title: 'b2bking_settings_saved_swal_title'
			  },
			  didOpen: (toast) => {
			    toast.addEventListener('mouseenter', Swal.stopTimer)
			    toast.addEventListener('mouseleave', Swal.resumeTimer)
			  }
			})

			Toast.fire({
			  icon: 'success',
			  title: b2bking.settings_saved_successfully
			});
		}

		

	});

})(jQuery);

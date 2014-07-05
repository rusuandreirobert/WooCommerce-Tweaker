<?php
/*
Plugin Name: WooCommerce Tweaker
Plugin URI: https://github.com/darkdelphin/WooCommerce-Tweaker
Description: Plugin that provides some additional options and tweaks for WooCommerce.
Author: Pavel Burov (Dark Delphin)
Author URI: http://pavelburov.com
Version: 1.2.0
*/

require_once( 'updater/github.php' );
require_once( 'import-export/import-export.php' );

class WooTweak2 {
     
    public $options;
    
    public $billing_array = array(
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_address_1',
				'billing_address_2',
				'billing_city',
				'billing_postcode',
				'billing_country',
				'billing_state',
				'billing_email',
				'billing_phone'
				  );
    public $shipping_array = array(
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_city',
				'shipping_postcode',
				'shipping_country',
				'shipping_state'
				   );
    public $order_array = array(
				'order_comments'
				);
     
    function __construct()
    {
	    $o = get_option('WooTweak2_options');

		add_action('admin_init', array($this, 'wt2_init'));

		add_action('admin_menu', array($this, 'add_pages'));

		add_action('wp_head', array($this, 'wt2_frontend_enhancement'));

		add_filter('woocommerce_checkout_fields' , array($this, 'wt2_override_checkout_fields'));
		
		add_action('woocommerce_before_checkout_form', array($this, 'wt2_checkout_form_width_function'));
		
		// In Admin panel
		// add_action('woocommerce_product_write_panel_tabs', array($this, 'wt2_variations_description_tab'));
		// add_action('woocommerce_product_write_panels', array($this, 'wt2_variations_description_tab_fields'));
		// add_action('woocommerce_process_product_meta_variable', array($this, 'wt2_variations_description_tab_fields_process'));

		add_filter('woocommerce_product_tabs', array($this, 'wt2_variations_tab'));

		if(isset($o['wt2_disable_tabs_on_product_page']) && $o['wt2_disable_tabs_on_product_page'])
		{
			add_action('woocommerce_after_single_product_summary', array($this, 'wt2_disable_tabs_on_product_page'), 5);
			// remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);		
		}
		
		if(isset($o['wt2_apply_price_filters']) && $o['wt2_apply_price_filters'])
		{
			add_filter('woocommerce_variable_price_html', array($this, 'wt2_variable_default_price_html_filter'), 10 , 2);
			add_filter('woocommerce_variable_sale_price_html', array($this, 'wt2_variable_default_sale_price_filter'), 10 , 2);
		}

		add_action('woocommerce_init', array($this, 'wt2_tweak_shop_manager_role'));
		add_action('woocommerce_init', array($this, 'wt2_use_wp_pagenavi_func'));
		add_action('woocommerce_init', array($this, 'wt2_remove_related_products_on_product_page'));
		
		add_action('woocommerce_init', array($this, 'wt2_show_sorting_feild_before_products'));
		
		
		add_filter('woocommerce_product_add_to_cart_text', array($this,'wt2_custom_addtocart_button_text_func'));
		add_filter('woocommerce_product_single_add_to_cart_text', array($this,'wt2_custom_addtocart_button_text_func'));

		add_filter('woocommerce_is_sold_individually', array($this,'wt2_remove_quantity_if_downloadable'), 10 , 2);

		add_action('admin_notices', array($this,'wt2_admin_notice'));

		add_action('plugins_loaded', array($this, 'wt2_translate'));

		add_action('wp_before_admin_bar_render', array($this, 'wt2_remove_admin_bar_links') );

		add_action('get_header',array($this, 'wt2_remove_woo_commerce_generator_tag'));

		if(isset($o['wt2_disable_cart_functions']) && $o['wt2_disable_cart_functions'])
		{
			add_action('init', array($this, 'wt2_disable_cart_functions_callback'));
		}

	    // Fields
		add_action('woocommerce_product_after_variable_attributes', array($this, 'wt2_variable_fields'), 10, 2 );
		
		add_action('woocommerce_product_options_sku', array($this, 'wt2_variable_default_price_field'), 10, 2 );
		
		// Some additional JS to add fields if needed for new variations
		// add_action( 'woocommerce_product_after_variable_attributes_js', array($this, 'wt2_variable_fields_js') );

		// Save variation
		add_action('woocommerce_process_product_meta_variable', array($this, 'wt2_variable_fields_process'), 10, 1 );
		
		add_action('woocommerce_process_product_meta_variable', array($this, 'wt2_variable_default_price_field_update'), 10, 1 );

		if ( is_admin() ) {
		    new GitHubPluginUpdater( __FILE__, 'darkdelphin', "WooCommerce-Tweaker" );
		}

		if ( is_admin() ) {
		    new ImportExportEnhancement( 'WooTweak2_options', 'wt2' );
		}

		add_action( 'admin_init', array($this, 'devhelpers_page_process') );

		remove_action( 'admin_notices', 'woothemes_updater_notice' );

		if(isset($o['wt2_hide_other_shipping_methods']) && $o['wt2_hide_other_shipping_methods'])
		{
			add_filter( 'woocommerce_package_rates', array($this, 'wt2_hide_shipping_when_free_is_available'), 10, 2 );
		}
    }

    function wt2_init()
    {
		//delete_option('WooTweak2'); // use to clear previous data if needed
		$this->options = get_option('WooTweak2_options');
		$this->reg_settings_and_fields();
		
		// add_filter( 'woocommerce_checkout_fields' ,'wt2_override_checkout_fields');
    }

    function wt2_translate()
    {
	    // $locale = get_locale();
	    // load_textdomain( 'WooTweak2', dirname( plugin_basename( __FILE__ ) ) . '/languages/'.$locale.'.mo' );
	    load_plugin_textdomain( 'WooTweak2', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );	
	    // load_plugin_textdomain( 'WooTweak2', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    function add_pages()
    {
		//add_options_page('Page Title', 'Menu Title', 'administrator', __FILE__, array('WooTweak2', 'display_options_page'));
		$page = add_submenu_page('woocommerce', 'Tweaker', 'Tweaker', 'administrator', 'woocommerce-tweaker', array('WooTweak2', 'display_options_page'));
	}

	function wt2_admin_notice()
	{
		if(isset($_GET['page']) && $_GET['page'] == 'wootweak/wootweak2.php' && isset($_GET['settings-updated']) && $_GET['settings-updated'] == true)
		{
		    ?>
		    <div id="message" class="updated fade"><p><?php echo __( 'Your settings have been saved.', 'woocommerce' ); ?></p></div>
		    <?php
		}
    }  
    
    function display_options_page()
    {
	    ?>
	    
	    <div class="wrap">
		<?php 
		// screen_icon();
		$o = get_option('WooTweak2_options');
		$admin_link = 'admin.php?page=woocommerce-tweaker';

	    $active_tab = 'general';
	    if( !isset($_GET['tab']) ) $active_tab = 'general';

	    if( isset($_GET['tab']) ) 
	    {
	        $active_tab = $_GET['tab'];
	    }
		?>

		<h2 class="nav-tab-wrapper">
        <a href="<?php echo $admin_link; ?>&tab=general" class="nav-tab <?php if($active_tab == 'general') echo 'nav-tab-active'; ?>"><?php echo __('General Options', 'woocommerce'); ?></a>
        <a href="<?php echo $admin_link; ?>&tab=visual" class="nav-tab <?php if($active_tab == 'visual') echo 'nav-tab-active'; ?>"><?php echo __('Visual tweaks', 'woocommerce'); ?></a>
        <a href="<?php echo $admin_link; ?>&tab=capabilities" class="nav-tab <?php if($active_tab == 'capabilities') echo 'nav-tab-active'; ?>"><?php echo __('Capabilities', 'woocommerce'); ?></a>
        <a href="<?php echo $admin_link; ?>&tab=billing" class="nav-tab <?php if($active_tab == 'billing') echo 'nav-tab-active'; ?>"><?php echo __('Checkout', 'woocommerce').' - '.__('Billing', 'woocommerce'); ?></a>
        <a href="<?php echo $admin_link; ?>&tab=shipping" class="nav-tab <?php if($active_tab == 'shipping') echo 'nav-tab-active'; ?>"><?php echo __('Checkout', 'woocommerce').' - '.__('Shipping', 'woocommerce'); ?></a>
        <a href="<?php echo $admin_link; ?>&tab=importexport" class="nav-tab <?php if($active_tab == 'importexport') echo 'nav-tab-active'; ?>"><?php echo __('Import/export', 'woocommerce'); ?></a>
        <a href="<?php echo $admin_link; ?>&tab=devhelpers" class="nav-tab <?php if($active_tab == 'devhelpers') echo 'nav-tab-active'; ?>"><?php echo __('Developer', 'woocommerce'); ?></a>
    	</h2>

		<form method="post" action="options.php" enctype="multipart/form-data">
		
		<?php settings_fields('WooTweak2_plugin_options_group'); ?>
		<?php //do_settings_sections(__FILE__); ?>
		
		<div class="tab general <?php if( $active_tab == 'general') echo 'active'; ?>">
			<?php do_settings_sections( 'main_section' ); ?>
			<p class="submit">
			    <input type="submit" name="submit" value="<?php echo __('Save changes', 'woocommerce'); ?>" class="button-primary"  />
			</p>
		</div>
		<div class="tab visual <?php if( $active_tab == 'visual') echo 'active'; ?>">
			<?php do_settings_sections( 'visual_section' ); ?>
			<p class="submit">
			    <input type="submit" name="submit" value="<?php echo __('Save changes', 'woocommerce'); ?>" class="button-primary"  />
			</p>
		</div>
		<div class="tab capabilities <?php if( $active_tab == 'capabilities') echo 'active'; ?>">
			<?php do_settings_sections( 'capabilities_section' ); ?>
			<p class="submit">
			    <input type="submit" name="submit" value="<?php echo __('Save changes', 'woocommerce'); ?>" class="button-primary"  />
			</p>
		</div>
		<div class="tab billing <?php if( $active_tab == 'billing') echo 'active'; ?>">
			<?php do_settings_sections( 'order_section' ); ?>
			<p class="submit">
			    <input type="submit" name="submit" value="<?php echo __('Save changes', 'woocommerce'); ?>" class="button-primary"  />
			</p>
		</div>
		<div class="tab shipping <?php if( $active_tab == 'shipping') echo 'active'; ?>">
			<?php
				do_settings_sections( 'shipping_section' );
            	do_settings_sections( 'order_comments_section' );
			?>
			<p class="submit">
			    <input type="submit" name="submit" value="<?php echo __('Save changes', 'woocommerce'); ?>" class="button-primary"  />
			</p>
		</div>

		</form>

		<div class="tab shipping <?php if( $active_tab == 'importexport') echo 'active'; ?>">
			<?php ImportExportEnhancement::import_export_plugin_buttons(); ?>
		</div>

		<div class="tab devhelpers <?php if( $active_tab == 'devhelpers') echo 'active'; ?>">
			<?php self::devhelpers_page();//ImportExportEnhancement::import_export_plugin_buttons(); ?>
		</div>
	    
	    </div>
	    <?php
    }
     
    function reg_settings_and_fields()
    {
	    register_setting('WooTweak2_plugin_options_group', 'WooTweak2_options', array($this, 'WooTweak2_validate_settings')); //3rd param optional callback func

	    add_settings_section('WooTweak2_main_section', __('General Options', 'woocommerce'), array($this, 'WooTweak2_main_section_cb'), 'main_section'); //id, title, callback, page
	    add_settings_section('WooTweak2_main_section', __('Visual tweaks', 'woocommerce'), array($this, 'WooTweak2_visual_section_cb'), 'visual_section'); //id, title, callback, page
	    add_settings_section('WooTweak2_capabilities_section', __('Capabilities', 'woocommerce'), array($this, 'WooTweak2_capabilities_section_cb'), 'capabilities_section'); //id, title, callback, page
	    add_settings_section('WooTweak2_order_section', __('Checkout Page', 'woocommerce').' - '.__('Billing', 'woocommerce'), array($this, 'WooTweak2_order_section_cb'), 'order_section'); //id, title, callback, page
	    add_settings_section('WooTweak2_shipping_section', __('Checkout Page', 'woocommerce').' - '.__('Shipping', 'woocommerce'), array($this, 'WooTweak2_shipping_section_cb'), 'shipping_section'); //id, title, callback, page
	    add_settings_section('WooTweak2_order_comments_section', __('Checkout Page', 'woocommerce').' - '.__('Customer Notes', 'woocommerce'), array($this, 'WooTweak2_order_comments_section_cb'), 'order_comments_section'); //id, title, callback, page
	    
	    
	    // ADD ALL add_settings_field FUNCTIONS HERE
	    add_settings_field('wt2_disable_tabs_on_product_page', __('Disable tabs on product page (Show all panels in one static column)', 'WooTweak2'), array($this,'wt2_disable_tabs_on_product_page_generate_field'), 'visual_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    
	    add_settings_field('wt2_checkout_form_width', __('One column checkout form', 'WooTweak2'), array($this,'wt2_checkout_form_width_generate_field'), 'visual_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    
	    add_settings_field('wt2_show_sort_before_products', __('Show sorting field before products', 'WooTweak2'), array($this,'wt2_show_sort_before_products_generate_field'), 'visual_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
		
		add_settings_field('wt2_disable_dashbord_logo_menu', __('Disable logo menu in admin dashboard'), array($this,'wt2_disable_dashbord_logo_menu_generate_field'), 'visual_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    
	    add_settings_field('wt2_custom_addtocart_button_text', __('Custom text for "Add to Cart" button (Single product)', 'WooTweak2'), array($this,'wt2_custom_addtocart_button_text_generate_field'), 'visual_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    
	    add_settings_field('wt2_use_flexbox_layout', __('Use FlexBox layout enhancement', 'WooTweak2'), array($this,'wt2_use_flexbox_layout_generate_field'), 'visual_section', 'WooTweak2_main_section'); // id, title, cb func, page , section



	    add_settings_field('wt2_variations_descriptions', __('Add description field for variantions', 'WooTweak2'), array($this,'wt2_variations_descriptions_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    
	    add_settings_field('wt2_variations_tab_on_product_page', __('Add variations tab on product page', 'WooTweak2'), array($this,'wt2_variations_tab_on_product_page_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    
	    add_settings_field('wt2_apply_price_filters', __('Use the following format for prices', 'WooTweak2'), array($this,'wt2_apply_price_filters_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    
	    add_settings_field('wt2_variation_price_formating', __('Variation price format', 'WooTweak2'), array($this,'wt2_variation_price_formating_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    
	    add_settings_field('wt2_use_wysiwyg_for_variation_description', __('Use WYSIWYG editor for variation description', 'WooTweak2'), array($this,'wt2_use_wysiwyg_for_variation_description_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    	    
	    add_settings_field('wt2_use_wp_pagenavi', __('Use WP PageNavi plugin for pagination (if installed and active)', 'WooTweak2'), array($this,'wt2_use_wp_pagenavi_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section

		add_settings_field('wt2_enable_checkout_fields_customization', __('Enable checkout fields customization','WooTweak2'), array($this,'wt2_enable_checkout_fields_customization_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section

		add_settings_field('wt2_remove_related_products_on_product_page', __('Remove related products on product page'), array($this,'wt2_remove_related_products_on_product_page_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section

		add_settings_field('wt2_disable_cart_functions', __('Disable cart funcionality to simulate catalog'), array($this,'wt2_disable_cart_functions_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section

	    add_settings_field('wt2_enhance_product_category_widget', __('Enhance product category widget with accordion for subcategories'), array($this,'wt2_enhance_product_category_widget_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    
	    add_settings_field('wt2_hide_other_shipping_methods', __('Hide other shipping methods when FREE SHIPPING is available'), array($this,'wt2_hide_other_shipping_methods_generate_field'), 'main_section', 'WooTweak2_main_section'); // id, title, cb func, page , section
	    

		add_settings_field('wt2_manage_pages', __('Edit pages', 'WooTweak2'), array($this,'wt2_manage_pages_generate_field'), 'capabilities_section', 'WooTweak2_capabilities_section'); // id, title, cb func, page , section
		add_settings_field('wt2_manage_posts', __('Edit posts'), array($this,'wt2_manage_posts_generate_field'), 'capabilities_section', 'WooTweak2_capabilities_section'); // id, title, cb func, page , section
		add_settings_field('wt2_manage_tools', __('Tools'), array($this,'wt2_manage_tools_generate_field'), 'capabilities_section', 'WooTweak2_capabilities_section'); // id, title, cb func, page , section
		add_settings_field('wt2_manage_orders', __('Orders', 'WooTweak2'), array($this,'wt2_manage_orders_generate_field'), 'capabilities_section', 'WooTweak2_capabilities_section'); // id, title, cb func, page , section
		add_settings_field('wt2_manage_coupons', __('Coupons', 'WooTweak2'), array($this,'wt2_manage_coupons_generate_field'), 'capabilities_section', 'WooTweak2_capabilities_section'); // id, title, cb func, page , section
		add_settings_field('wt2_manage_reports', __('Reports', 'WooTweak2'), array($this,'wt2_manage_reports_generate_field'), 'capabilities_section', 'WooTweak2_capabilities_section'); // id, title, cb func, page , section

		foreach($this->billing_array as $item)
		{
			$name = str_replace('billing_','', $item);
			$name = str_replace('_',' ',$name);
			$name = ucwords($name);
		    add_settings_field('wt2_disabled_'.$item, __($name, 'woocommerce'), array($this,'wt2_billing_disabled_fields'), 'order_section', 'WooTweak2_order_section',$item); // id, title, cb func, page , section
		}
		foreach($this->shipping_array as $item)
		{
			$name = str_replace('shipping_','', $item);
			$name = str_replace('_',' ',$name);
			$name = ucwords($name);
		    add_settings_field('wt2_disabled_'.$item, __($name, 'woocommerce'), array($this,'wt2_billing_disabled_fields'), 'shipping_section', 'WooTweak2_shipping_section',$item); // id, title, cb func, page , section
		}
		foreach($this->order_array as $item)
		{
		    add_settings_field('wt2_disabled_'.$item, __('Order Notes', 'woocommerce'), array($this,'wt2_billing_disabled_fields'), 'order_comments_section', 'WooTweak2_order_comments_section',$item); // id, title, cb func, page , section
		}
	}
     
    function WooTweak2_main_section_cb(){// Optional
	}
    function WooTweak2_visual_section_cb(){// Optional
	}
    function WooTweak2_order_section_cb(){// Optional
	}
    function WooTweak2_shipping_section_cb(){// Optional
	}
    function WooTweak2_order_comments_section_cb(){// Optional
	}
    function WooTweak2_capabilities_section_cb(){// Optional
    	echo '<p>'.__('Remove following capabilities from "Shop Manager" role:').'</p>';
	}
	
    function WooTweak2_validate_settings($plugin_options)
    {
		return $plugin_options;
    }
     
    // Input functions *************************************************************************************************************************
     
    function wt2_disable_tabs_on_product_page_generate_field()
    {
    	$checked = ( 1 == $this->options['wt2_disable_tabs_on_product_page'] ) ? 'checked="checked"' : '' ;
    	echo '<input name="WooTweak2_options[wt2_disable_tabs_on_product_page]" type="checkbox" value="1" '.$checked.'>';
    }
    
    function wt2_checkout_form_width_generate_field()
    {
    	$checked = ( 1 == $this->options['wt2_checkout_form_width'] ) ? 'checked="checked"' : '' ;
    	echo '<input name="WooTweak2_options[wt2_checkout_form_width]" type="checkbox" value="1" '.$checked.'>';
    }
	 
    function wt2_billing_disabled_fields($item)
    {	
	    $o = get_option('WooTweak2_options');
	    $enabled = ($o['wt2_disabled_'.$item]) ? ' disabled="disabled"' : '' ;
	    $checked = ( 1 == $this->options['wt2_disabled_'.$item] ) ? 'checked="checked"' : '' ;
	    echo '<label><input name="WooTweak2_options[wt2_disabled_'.$item.']" type="checkbox" value="1" '.$checked.'> '.__('Disabled', 'WooTweak2').'</label>&nbsp;&nbsp;&nbsp;';
	    $checked2 = ( 1 == $this->options['wt2_required_'.$item] ) ? ' checked="checked"' : '' ;
	    echo '<label><input name="WooTweak2_options[wt2_required_'.$item.']" type="checkbox" value="1"'.$checked2.''.$enabled.'> '.__('Required', 'WooTweak2').'</label>';
    }
    
    function wt2_variations_descriptions_generate_field()
    {
    	$checked = ( 1 == $this->options['wt2_variations_descriptions'] ) ? 'checked="checked"' : '' ;
    	echo '<input name="WooTweak2_options[wt2_variations_descriptions]" type="checkbox" value="1" '.$checked.'>';
    }
	
	function wt2_variations_tab_on_product_page_generate_field()
	{
		$o = get_option('WooTweak2_options');
		$enabled = ($o['wt2_variations_descriptions']) ? '' : ' disabled="disabled"' ;
		$checked = ( 1 == $this->options['wt2_variations_tab_on_product_page'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_variations_tab_on_product_page]" type="checkbox" value="1" '.$checked.''.$enabled.'>';
	}

	function wt2_use_wysiwyg_for_variation_description_generate_field()
    {
    	$checked = ( 1 == $this->options['wt2_use_wysiwyg_for_variation_description'] ) ? 'checked="checked"' : '' ;
    	echo '<input name="WooTweak2_options[wt2_use_wysiwyg_for_variation_description]" type="checkbox" value="1" '.$checked.'>';
    }

    function wt2_apply_price_filters_generate_field()
    {
    	$checked = ( 1 == $this->options['wt2_apply_price_filters'] ) ? 'checked="checked"' : '' ;
    	echo '<input name="WooTweak2_options[wt2_apply_price_filters]" type="checkbox" value="1" '.$checked.'>';
    }

    function wt2_variation_price_formating_generate_field()
    {
    	$o = get_option('WooTweak2_options');
    	echo '<select name="WooTweak2_options[wt2_variation_price_formating]">';
	    	echo '<option value="dash" '.selected('dash', $o['wt2_variation_price_formating']).'>Min–Max</option>';
	    	echo '<option value="fromto" '.selected('fromto', $o['wt2_variation_price_formating']).'>From Min to Max</option>';
    	echo '</select>';
    }
	
    function wt2_use_wp_pagenavi_generate_field()
	{
	    if(is_plugin_active('wp-pagenavi/wp-pagenavi.php'))
	    {
	    	$checked = ( 1 == $this->options['wt2_use_wp_pagenavi'] ) ? 'checked="checked"' : '' ;
	    	echo '<input name="WooTweak2_options[wt2_use_wp_pagenavi]" type="checkbox" value="1" '.$checked.'>';
	    }
	}
    
    function wt2_show_sort_before_products_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_show_sort_before_products'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_show_sort_before_products]" type="checkbox" value="1" '.$checked.'>';
	}

	function wt2_use_flexbox_layout_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_use_flexbox_layout'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_use_flexbox_layout]" type="checkbox" value="1" '.$checked.'>';
	}
	
    function wt2_custom_addtocart_button_text_generate_field()
	{
		echo '<input name="WooTweak2_options[wt2_custom_addtocart_button_text]" type="text" value="'.$this->options['wt2_custom_addtocart_button_text'].'">';
	}

	// Capabilities

	function wt2_manage_pages_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_manage_pages'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_manage_pages]" type="checkbox" value="1" '.$checked.'>';
	}

	function wt2_manage_tools_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_manage_tools'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_manage_tools]" type="checkbox" value="1" '.$checked.'>';
	}

	function wt2_manage_posts_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_manage_posts'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_manage_posts]" type="checkbox" value="1" '.$checked.'>';
	}
	
	function wt2_manage_orders_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_manage_orders'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_manage_orders]" type="checkbox" value="1" '.$checked.'>';
	}
	
	function wt2_manage_coupons_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_manage_coupons'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_manage_coupons]" type="checkbox" value="1" '.$checked.'>';
	}
	
	function wt2_manage_reports_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_manage_reports'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_manage_reports]" type="checkbox" value="1" '.$checked.'>';
	}
	
	function wt2_disable_dashbord_logo_menu_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_disable_dashbord_logo_menu'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_disable_dashbord_logo_menu]" type="checkbox" value="1" '.$checked.'>';
	}

	function wt2_enable_checkout_fields_customization_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_enable_checkout_fields_customization'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_enable_checkout_fields_customization]" type="checkbox" value="1" '.$checked.'>';
	}

	function wt2_remove_related_products_on_product_page_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_remove_related_products_on_product_page'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_remove_related_products_on_product_page]" type="checkbox" value="1" '.$checked.'>';
	}

	function wt2_disable_cart_functions_generate_field()
	{
		$checked = ( 1 == $this->options['wt2_disable_cart_functions'] ) ? 'checked="checked"' : '' ;
		echo '<input name="WooTweak2_options[wt2_disable_cart_functions]" type="checkbox" value="1" '.$checked.'>';
	} 
    
    function wt2_enhance_product_category_widget_generate_field()
    {
    	$checked = ( 1 == $this->options['wt2_enhance_product_category_widget'] ) ? 'checked="checked"' : '' ;
    	echo '<input name="WooTweak2_options[wt2_enhance_product_category_widget]" type="checkbox" value="1" '.$checked.'>';
    }

    function wt2_hide_other_shipping_methods_generate_field()
    {
    	$checked = ( 1 == $this->options['wt2_hide_other_shipping_methods'] ) ? 'checked="checked"' : '' ;
    	echo '<input name="WooTweak2_options[wt2_hide_other_shipping_methods]" type="checkbox" value="1" '.$checked.'>';
    }

    // Disable (hide) tabs on product page *************************************************************************************************************************
    
    function wt2_hide_shipping_when_free_is_available( $rates, $package ) 
    {
	 	// Only modify rates if free_shipping is present
	  	if ( isset( $rates['free_shipping'] ) ) {
	  	
	  		// To unset a single rate/method, do the following. This example unsets flat_rate shipping
	  		unset( $rates['flat_rate'] );
	  		
	  		// To unset all methods except for free_shipping, do the following
	  		$free_shipping          = $rates['free_shipping'];
	  		$rates                  = array();
	  		$rates['free_shipping'] = $free_shipping;
		}
		
		return $rates;
	}

    // Remove related products on product page *************************************************************************************************************************

    function wt2_remove_related_products_on_product_page()
    {
    	$o = get_option('WooTweak2_options');
    	if(isset($o['wt2_remove_related_products_on_product_page']) && $o['wt2_remove_related_products_on_product_page'])
    	{
    		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
    	}
    }

    function wt2_disable_cart_functions_callback()
    {
		// Remove cart button from the product loop
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10, 2);
		 
		// Remove cart button from the product details page
		// remove_action( 'woocommerce_before_add_to_cart_form', 'woocommerce_template_single_product_add_to_cart', 10, 2);
		// remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		 
		// disabled actions (add to cart, checkout and pay)
		remove_action( 'init', 'woocommerce_add_to_cart_action', 10);
		remove_action( 'init', 'woocommerce_checkout_action', 10 );
		remove_action( 'init', 'woocommerce_pay_action', 10 );

		// remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
		remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
		// remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
		remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );

		add_action('woocommerce_before_add_to_cart_button', array($this, 'wt2_hide_add_to_cart_variable_wrapper_begin'));
		// add_action('woocommerce_after_add_to_cart_button', array($this, 'wt2_hide_add_to_cart_variable_wrapper_end'));
    }

    function wt2_hide_add_to_cart_variable_wrapper_begin()
    {
    	?>
    	<style>
    		form.cart .single_add_to_cart_button, .input-text.qty.text, .variations_button {
    			display: none !important;
    		}
    	</style>
    	<?php
    }

    function wt2_hide_add_to_cart_variable_wrapper_end()
    {
    	?>
    	<?php
    }
    
    // Output all product info on one panel without tabs *************************************************************************************************************************

    function wt2_disable_tabs_on_product_page()
    {
    	remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);		

    	$tabs = apply_filters( 'woocommerce_product_tabs', array() );

		if ( ! empty( $tabs ) ) : ?>

		<div class="woocommerce-panels">
			
			<?php foreach ( $tabs as $key => $tab ) : ?>

				<div class="panel entry-content" id="panel-<?php echo $key ?>">
					<?php call_user_func( $tab['callback'], $key, $tab ) ?>
				</div>

			<?php endforeach; ?>
		</div>

		<?php endif; 
    }
    
    
    // Manage checkout fields *************************************************************************************************************************
    
    function wt2_override_checkout_fields( $fields )
    {
	$o = get_option('WooTweak2_options');
	
	if($o['wt2_enable_checkout_fields_customization'])
	{
		foreach($this->billing_array as $item)
		{
		    if($o['wt2_required_'.$item])
		    {
				$fields['billing'][$item]['required'] = true;
		    }
		    else if(!$o['wt2_required_'.$item])
		    {
				$fields['billing'][$item]['required'] = false;
		    }
		    
		    if($o['wt2_disabled_'.$item])
		    {
				unset($fields['billing'][$item]);
		    }
		}
		foreach($this->shipping_array as $item)
		{
		    if($o['wt2_required_'.$item])
		    {
				$fields['shipping'][$item]['required'] = true;
		    }
		    else if(!$o['wt2_required_'.$item])
		    {
				$fields['shipping'][$item]['required'] = false;
		    }
		    if($o['wt2_disabled_'.$item])
		    {
				unset($fields['shipping'][$item]);
		    }
		}
		foreach($this->order_array as $item)
		{
		    if($o['wt2_required_'.$item])
		    {
				$fields['order'][$item]['required'] = true;
		    }
		    else if(!$o['wt2_required_'.$item])
		    {
				$fields['order'][$item]['required'] = false;
		    }
		    if($o['wt2_disabled_'.$item])
		    {
				unset($fields['order'][$item]);
		    }
		
		}	
	}
		// $fields['billing']['billing_company']['placeholder'] = __('Company Name', 'woocommerce');
		// $fields['billing']['billing_address_2']['placeholder'] = __('Address 2', 'woocommerce');
		// $fields['shipping']['shipping_company']['placeholder'] = __('Company Name', 'woocommerce');
		// $fields['shipping']['shipping_address_2']['placeholder'] = __('Address 2', 'woocommerce');
		// $fields['billing']['billing_email']['placeholder'] = 'email@yourmail.com';
    
    return $fields;
    }
    
    // Set all checkout fields to one column and 100% wide
    
    function wt2_checkout_form_width_function()
    {
		$o = get_option('WooTweak2_options');
		if($o['wt2_checkout_form_width'])
		{
		    ?>
		    <style type="text/css">
		    form .form-row, form .form-row-first, form .form-row-last {
			float: none;
			width: 100%;
			}
			.col2-set .col-1, .col2-set .col-2 {
			float: none;
			width: 100%;
			}
		    </style>
		    <?php
		}
    }
    
    // Shop manager role: remove links and pages capabilities.
    
    function wt2_tweak_shop_manager_role()
    {
		global $wp_roles;
		
		$o = get_option('WooTweak2_options');
		if(isset($o['wt2_manage_pages']) && $o['wt2_manage_pages']) 
		{
			$wp_roles->remove_cap('shop_manager', 'edit_pages');
			$wp_roles->remove_cap('shop_manager', 'edit_published_pages');
			$wp_roles->remove_cap('shop_manager', 'edit_private_pages');
			$wp_roles->remove_cap('shop_manager', 'publish_pages');
			$wp_roles->remove_cap('shop_manager', 'delete_pages');
			$wp_roles->remove_cap('shop_manager', 'delete_private_pages');
			$wp_roles->remove_cap('shop_manager', 'delete_published_pages');
			$wp_roles->remove_cap('shop_manager', 'delete_others_pages');
		}
		else
		{
			$wp_roles->add_cap('shop_manager', 'edit_pages');
			$wp_roles->add_cap('shop_manager', 'edit_published_pages');
			$wp_roles->add_cap('shop_manager', 'edit_private_pages');
			$wp_roles->add_cap('shop_manager', 'publish_pages');
			$wp_roles->add_cap('shop_manager', 'delete_pages');
			$wp_roles->add_cap('shop_manager', 'delete_private_pages');
			$wp_roles->add_cap('shop_manager', 'delete_published_pages');
			$wp_roles->add_cap('shop_manager', 'delete_others_pages');	
		}

		if(isset($o['wt2_manage_posts']) && $o['wt2_manage_posts']) 
		{
			$wp_roles->remove_cap('shop_manager', 'edit_posts');
			$wp_roles->remove_cap('shop_manager', 'edit_others_posts');
			$wp_roles->remove_cap('shop_manager', 'edit_published_posts');
			$wp_roles->remove_cap('shop_manager', 'publish_posts');
			$wp_roles->remove_cap('shop_manager', 'delete_posts');
			$wp_roles->remove_cap('shop_manager', 'delete_others_posts');
			$wp_roles->remove_cap('shop_manager', 'delete_published_posts');
			$wp_roles->remove_cap('shop_manager', 'delete_private_posts');
			$wp_roles->remove_cap('shop_manager', 'edit_private_posts');
			$wp_roles->remove_cap('shop_manager', 'read_private_posts');
			$wp_roles->remove_cap('shop_manager', 'manage_categories');

			
		}
		else
		{
			$wp_roles->add_cap('shop_manager', 'edit_posts');
			$wp_roles->add_cap('shop_manager', 'edit_others_posts');
			$wp_roles->add_cap('shop_manager', 'edit_published_posts');
			$wp_roles->add_cap('shop_manager', 'publish_posts');
			$wp_roles->add_cap('shop_manager', 'delete_posts');
			$wp_roles->add_cap('shop_manager', 'delete_others_posts');
			$wp_roles->add_cap('shop_manager', 'delete_published_posts');
			$wp_roles->add_cap('shop_manager', 'delete_private_posts');
			$wp_roles->add_cap('shop_manager', 'edit_private_posts');
			$wp_roles->add_cap('shop_manager', 'read_private_posts');
			$wp_roles->add_cap('shop_manager', 'manage_categories');

		}

		if(isset($o['wt2_manage_tools']) && $o['wt2_manage_tools']) 
		{
			$wp_roles->remove_cap('shop_manager', 'import');
			$wp_roles->remove_cap('shop_manager', 'export');
		}
		else
		{
			$wp_roles->add_cap('shop_manager', 'import');	
			$wp_roles->add_cap('shop_manager', 'export');	
		}

		if(isset($o['wt2_manage_orders']) && $o['wt2_manage_orders']) 
		{
			$wp_roles->remove_cap('shop_manager', 'manage_woocommerce_orders');
		}
		else
		{
			$wp_roles->add_cap('shop_manager', 'manage_woocommerce_orders');	
		}

		if(isset($o['wt2_manage_coupons']) && $o['wt2_manage_coupons']) 
		{
			$wp_roles->remove_cap('shop_manager', 'manage_woocommerce_coupons');
		}
		else
		{
			$wp_roles->add_cap('shop_manager', 'manage_woocommerce_coupons');	
		}

		if(isset($o['wt2_manage_reports']) && $o['wt2_manage_reports']) 
		{
			$wp_roles->remove_cap('shop_manager', 'view_woocommerce_reports');
		}
		else
		{
			$wp_roles->add_cap('shop_manager', 'view_woocommerce_reports');	
		}

		$wp_roles->remove_cap('shop_manager', 'manage_links');
		$wp_roles->remove_cap('shop_manager', 'woocommerce_debug');
		$wp_roles->remove_cap('shop_manager', 'manage_woocommerce');
    }
    
   // Use WP PageNavi plugin for pagination instead of kit pagination 
    
   function wt2_use_wp_pagenavi_func()
    {
		$o = get_option('WooTweak2_options');
		if (isset($o['wt2_use_wp_pagenavi']) && $o['wt2_use_wp_pagenavi'])
		{
			remove_action('woocommerce_pagination', 'woocommerce_pagination', 10);
			
			function new_woocommerce_pagination() 
			{
			    wp_pagenavi();
			}

			add_action( 'woocommerce_pagination', 'new_woocommerce_pagination', 10);
		}
    } 
    
    // Chose where to show sorting form
    
    function wt2_show_sorting_feild_before_products()
    {
		$o = get_option('WooTweak2_options');
		if (isset($o['wt2_show_sort_before_products']) && $o['wt2_show_sort_before_products'])
		{
			remove_action( 'woocommerce_pagination', 'woocommerce_catalog_ordering', 20 );
			add_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 20);
		}
    }
    
    // Custom text for Add ot Cart button
    
    function wt2_custom_addtocart_button_text_func()
    {
		$o = get_option('WooTweak2_options');
		if (isset($o['wt2_custom_addtocart_button_text']) && $o['wt2_custom_addtocart_button_text'])
		{
		    return $o['wt2_custom_addtocart_button_text'];
		}
		else
		{
		    return __('Add to cart', 'woocommerce');
		}
    }

    // Remove quantity if product is downloadable

    function wt2_remove_quantity_if_downloadable($return, $product)
    {
    	$id = get_the_ID();
    	$meta = get_post_meta($id, '_downloadable', true);
    	if($meta == 'yes') return true;
    }
    
    // Disable logo menu in admin dashboard

    function wt2_remove_admin_bar_links() 
    {
    	$o = get_option('WooTweak2_options');
		if (isset($o['wt2_disable_dashbord_logo_menu']) && $o['wt2_disable_dashbord_logo_menu'])
		{
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu('wp-logo');
			$wp_admin_bar->remove_menu('updates');
		}
	}

	// Disable generator tag in head

	function wt2_remove_woo_commerce_generator_tag()
	{
	    remove_action('wp_head',array($GLOBALS['woocommerce'], 'generator'));
	}

	// Widget enhancement stuff *****************************************************************************************************************
	
	function wt2_frontend_enhancement() //wt2_product_category_widget_enhancement
	{
		$o = get_option('WooTweak2_options');
		?>
		<script>
			/* WooCommerce Tweaker data*/
		<?php
			if($o['wt2_enhance_product_category_widget'])
			{
				?>
				var product_category_widget_enhancement = true; 
				<?php
			}
			else
			{
				?>
				var product_category_widget_enhancement = false; 
				<?php	
			}
			if($o['wt2_disable_tabs_on_product_page'])
			{
				?>
				var disable_tabs_on_product_page = true; 
				<?php
			}
			else
			{
				?>
				var disable_tabs_on_product_page = false; 
				<?php
			}
		?>
		</script>
		<?php	
	}

    // Variations stuff *************************************************************************************************************************

	function wt2_variable_fields( $loop, $variation_data ) {
		$o = get_option('WooTweak2_options');
	?>	
		<tr>
			<td colspan="2">
				<div>
						<label>Description <a class="tips" data-tip="Individual description for a variation that would be displayed on a variation description tab" href="#">[?]</a></label>
						<?php 
						if($o['wt2_use_wysiwyg_for_variation_description']) wp_editor( $variation_data['_description'][0], $variation_data['_sku'][0], array(
							'textarea_name' => 'description[]' 
							) );
						else
						{
							?>
							<textarea name="description[]" id="description" cols="30" rows="10"><?php echo $variation_data['_description'][0]; ?></textarea>
							<?php
						}
						?>
				</div>
			</td>
		</tr>
	<?php
	}

	function wt2_variable_default_price_field( $post_id )
	{
		$post_id = get_the_ID();
		$pf = new WC_Product_Factory();
		$product = $pf->get_product($post_id);
		if($product->product_type == 'variable')
		{
		?>
		<p class="form-field">
			<label>Default variation price (<?php echo get_woocommerce_currency_symbol(); ?>)</label> 
			<!-- <a class="tips" data-tip="Price that would be displayed in 'From:' label instead of the lowest one" href="#">[?]</a> -->
			<input type="text" class="short" name="default_variation_price" id="default_variation_price" value="<?php echo get_post_meta($post_id, '_default_variation_price', true); ?>">
			<img class="help_tip" data-tip="Price that would be displayed in 'From:' label instead of the lowest one. If you don't need it - leave blank or set to 0" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" height="16" width="16" />
		</p>
		<p class="form-field">
			<label>Default variation sale price (<?php echo get_woocommerce_currency_symbol(); ?>)</label> 
			<!-- <a class="tips" data-tip="Price that would be displayed in 'From:' label instead of the lowest one" href="#">[?]</a> -->
			<input type="text" class="short" name="default_variation_sale_price" id="default_variation_sale_price" value="<?php echo get_post_meta($post_id, '_default_variation_sale_price', true); ?>">
			<img class="help_tip" data-tip="Price that would be displayed on sales in 'From:' label instead of the lowest one. If you don't need it - leave blank or set to 0" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" height="16" width="16" />
		</p>

		<p class="form-field">
			<label>Variation tab title</label> 
			<!-- <a class="tips" data-tip="Price that would be displayed in 'From:' label instead of the lowest one" href="#">[?]</a> -->
			<input type="text" class="short" name="variation_tab_title" id="variation_tab_title" value="<?php echo get_post_meta($post_id, '_variation_tab_title', true); ?>">
			<img class="help_tip" data-tip="Custom title for variation description tab on product page" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" height="16" width="16" />
		</p>	
		<?php
		}
	}

	function wt2_variable_default_price_field_update( $post_id )
	{
		if (isset( $_POST['default_variation_price'] ) ) 
		{
			update_post_meta( $post_id, '_default_variation_price', sanitize_text_field( $_POST['default_variation_price'] ) );
		}
		if (isset( $_POST['default_variation_sale_price'] ) ) 
		{
			update_post_meta( $post_id, '_default_variation_sale_price', sanitize_text_field( $_POST['default_variation_sale_price'] ) );
		}
		if (isset( $_POST['variation_tab_title'] ) ) 
		{
			update_post_meta( $post_id, '_variation_tab_title', sanitize_text_field( $_POST['variation_tab_title'] ) );
		}
	}

	function wt2_variable_default_sale_price_filter( $price )
	{
		global $post, $woocommerce, $product;

		$o = get_option('WooTweak2_options');

		$id = get_the_ID();
		$def = get_post_meta($id, '_default_variation_price', true);
		$def_sale = get_post_meta($id, '_default_variation_sale_price', true);

		$price = '';

		$available_variations = $product->get_available_variations();

		$min_price = 0;
		$max_price = 0;
		$min_sale_price = 0;
		$max_sale_price = 0;
		$counter = 0;

		foreach($available_variations as $prod_variation) {
		    $post_id = $prod_variation['variation_id'];
		    $meta = get_post_meta($post_id);

		    if($meta['_regular_price'][0] >= $max_price) $max_price = $meta['_regular_price'][0];
		    if($meta['_sale_price'][0] >= $max_sale_price) $max_sale_price = $meta['_sale_price'][0];

		    if($counter != 0)
		    {
		    	if($meta['_regular_price'][0] <= $min_price && $meta['_regular_price'][0] != 0) $min_price = $meta['_regular_price'][0];
		    	if($meta['_sale_price'][0] <= $min_sale_price && $meta['_sale_price'][0] != 0) $min_sale_price = $meta['_sale_price'][0];
		    	$counter++;
		    }
		    else
		    {
		    	$min_price = $meta['_regular_price'][0];
		    	$min_sale_price = $meta['_sale_price'][0];
		    	$counter++;
		    }
		}

		if($o['wt2_variation_price_formating'] == 'dash')
		{	
			$price = '<del><span class="amount">';
			
			if( $def != '' && $def != 0 && $def <= $max_price) $price .= woocommerce_price($def);
			else $price .= woocommerce_price($min_price);

			$price .= '</span>–<span class="amount">' . woocommerce_price($max_price) . '</span></del>';

			$price .= '<ins><span class="amount">';
			
			if( $def_sale != '' && $def_sale != 0 && $def_sale <= $max_sale_price) $price .= woocommerce_price($def_sale);
			else $price .= woocommerce_price($min_sale_price);

			$price .= '</span>–<span class="amount">';

			if($min_sale_price < $max_sale_price) $price .= woocommerce_price($max_sale_price);
			else $price .= woocommerce_price($max_price);
			
			$price .= '</span></ins>';
		}

		if($o['wt2_variation_price_formating'] == 'fromto')
		{
			$price = '<del><span class="from">' . _x('From', 'min_price', 'woocommerce') . ' ';

			if( $def != '' && $def != 0 && $def <= $max_price) $price .= woocommerce_price($def);
			else $price .= woocommerce_price($min_price);

			$price .= '</span>';

			$price .= ' <span class="to">' . _x('to', 'max_price', 'woocommerce') . ' ' . woocommerce_price($max_price) . '</span></del>';

			$price .= '<ins><span class="from">' . _x('From', 'min_price', 'woocommerce') . ' ';

			if( $def_sale != '' && $def_sale != 0 && $def_sale <= $max_sale_price) $price .= woocommerce_price($def_sale);
			else $price .= woocommerce_price($min_sale_price);

			$price .= '</span>'; 

			$price .= ' <span class="to">' . _x('to', 'max_price', 'woocommerce') . ' ';

			if($min_sale_price < $max_sale_price) $price .= woocommerce_price($max_sale_price);
			else $price .= woocommerce_price($max_price);

			$price .= '</span></ins>';
		}

		return $price;
	}

	function wt2_variable_default_price_html_filter( $price )
	{
		global $post, $woocommerce, $product;
		
		$o = get_option('WooTweak2_options');

		$id = get_the_ID();
		$def = get_post_meta($id, '_default_variation_price', true);
		$def_sale = get_post_meta($id, '_default_variation_sale_price', true);

		// $price = $product->get_variation_sale_price('min');
		$price = '';

		$available_variations = $product->get_available_variations();

		$min_price = 0;
		$max_price = 0;
		$min_sale_price = 0;
		$max_sale_price = 0;
		$counter = 0;

		foreach($available_variations as $prod_variation) {
		    $post_id = $prod_variation['variation_id'];
		    $meta = get_post_meta($post_id);

		    if($meta['_regular_price'][0] >= $max_price) $max_price = $meta['_regular_price'][0];
		    if($meta['_sale_price'][0] >= $max_sale_price) $max_sale_price = $meta['_sale_price'][0];

		    if($counter != 0)
		    {
		    	if($meta['_regular_price'][0] <= $min_price && $meta['_regular_price'][0] != 0) $min_price = $meta['_regular_price'][0];
		    	if($meta['_sale_price'][0] <= $min_sale_price && $meta['_sale_price'][0] != 0) $min_sale_price = $meta['_sale_price'][0];
		    	$counter++;
		    }
		    else
		    {
		    	$min_price = $meta['_regular_price'][0];
		    	$min_sale_price = $meta['_sale_price'][0];
		    	$counter++;
		    }
		}

		if($o['wt2_variation_price_formating'] == 'dash')
		{	
			$price = '<span class="amount">';
			
			if( $def != '' && $def != 0 && $def <= $max_price) $price .= woocommerce_price($def);
			else $price .= woocommerce_price($min_price);

			$price .= '</span>–<span class="amount">';

			$price .= woocommerce_price($max_price);
			
			$price .= '</span>';
		}

		if($o['wt2_variation_price_formating'] == 'fromto')
		{
			$price = '<span class="from">' . _x('From', 'min_price', 'woocommerce') . ' ';

			if( $def != '' && $def != 0 && $def <= $max_price) $price .= woocommerce_price($def);
			else $price .= woocommerce_price($min_price);

			$price .= '</span>';

			$price .= ' <span class="to">' . _x('to', 'max_price', 'woocommerce') . ' ';

			$price .= woocommerce_price($max_price);

			$price .= '</span>';
		}

		return $price;
	}
	 
	function wt2_variable_fields_process( $post_id ) {
		if (isset( $_POST['variable_sku'] ) ) 
		{
			$variable_sku = $_POST['variable_sku'];
			$variable_post_id = $_POST['variable_post_id'];

			$variable_custom_field = $_POST['description'];

			for ( $i = 0; $i < sizeof( $variable_sku ); $i++ ) 
			{
				$variation_id = (int) $variable_post_id[$i];
				if ( isset( $variable_custom_field[$i] ) ) {
					update_post_meta( $variation_id, '_description', stripslashes( $variable_custom_field[$i] ) );
				}
			}
		}
	}   
    
    function wt2_variations_tab($array)
    {
    	global $post, $woocommerce, $product;

		$o = get_option('WooTweak2_options');

		$title = get_post_meta($product->id, '_variation_tab_title', true);

		if($o['wt2_variations_tab_on_product_page'] && $product->product_type == 'variable')
		{
			$array['variation_description'] = array(
				//'title' => __('Variation', 'woocommerce').' ('.__('Description', 'woocommerce').')',
				'priority' => 30,
				'callback' => array('WooTweak2', 'wt2_variations_panel')
				);
			if($title && $title != '') $array['variation_description']['title'] = $title;
			else $array['variation_description']['title'] = __('Variation', 'woocommerce').' ('.__('Description', 'woocommerce').')';
		}
		return $array;
    }

    function wt2_variations_panel($loop, $variation_data)
    {
    	global $post, $woocommerce, $product;

		$o = get_option('WooTweak2_options');

		if($o['wt2_variations_tab_on_product_page'] && $product->product_type == 'variable' || $product->product_type == 'bundle')
		{
			if($product->product_type == 'bundle')
			{
				
				foreach($product->bundled_products as $prod)
				{
					
					foreach($prod->children as $child)
					{
						$meta = get_post_meta($child);
						$post = get_post($child);
						$post = get_post($post->post_parent);
						?>
						<div class="variation item<?php echo $child; ?>">
							<?php // echo get_the_title($child); ?>
							<h2><?php echo $post->post_title; ?></h2>
							<?php // echo $meta['_description'][0]; ?>
							<?php echo $meta['_description'][0]; ?>
						</div>
						<?php
					}
				}
			}

			if($product->children)
			{
				foreach($product->children as $item)
		    	{
		    		$meta_values = get_post_meta($item);
		    		?>
		    		<div class="variation item<?php echo $item; ?>">
						<?php // echo $meta_values['_description'][0]; ?>
						<?php echo $meta_values['_description'][0]; ?>
		    		</div><!-- /item<?php echo $item; ?> -->
		    		<?php
	    		}
	    	}
		}
    }

    // Dev Helpers *************************************************************************************************************************

    function devhelpers_page()
    {
    	?>
    	<h3>Product categories Levels</h3>
    	<p>Add "_level" metadata to each product category representing its depth of nesting in categories hierarchy.</p>
    	<p>For top level parent categories count starts with 0.</p>
    	<form method="post">
			<p>
				<input type="hidden" name="add_levels_to_terms" value="add_levels" />
				<?php wp_nonce_field( 'add_levels_to_terms_nonce', 'add_levels_to_terms_nonce' ); ?>
				<input type="submit" name="submit" value="<?php echo __('Add levels', 'woocommerce'); ?>" class="button-primary"  />
				<input type="submit" name="show_current_levels" value="<?php echo __('Show current levels', 'woocommerce'); ?>" class="button"  />
			</p>
		</form>

		<p>Enable/Disable comments for all products</p>
    	<form method="post">
			<p>
				<input type="hidden" name="all_products_comments" value="all_products_comments" />
				<?php wp_nonce_field( 'all_products_comments_nonce', 'all_products_comments_nonce' ); ?>
				<input type="submit" name="enable_comments" value="<?php echo __('Enable comments', 'woocommerce'); ?>" class="button-primary"  />
				<input type="submit" name="disable_comments" value="<?php echo __('Disable comments', 'woocommerce'); ?>" class="button"  />
			</p>
		</form>
    	<?php
    	

    	if( !empty( $_POST['show_current_levels'] ) )
    	{
    		$terms = get_terms( 'product_cat' );
    		?>
    		<table class="wc_status_table widefat">
    			<thead>
    				<th>Name</th>
    				<th>Slug</th>
    				<th>Level</th>
    			</thead>
    			<tbody>
    		<?php
	    	foreach($terms as $term)
	    	{
	    		?>
	    		<tr>
	    			<td><?php echo $term->name; ?></td>
	    			<td><?php echo $term->slug; ?></td>
	    			<td><?php echo get_post_meta( $term->term_id, '_level', true ); ?></td>
	    		</tr>
	    		<?php
	    	}
	    	?>
    			</tbody>
    		</table>
    		<?php
    	}
    }

    function devhelpers_page_process()
    {
  //   	if( empty( $_POST['add_levels_to_terms'] ) || 'add_levels' != $_POST['add_levels_to_terms'] )
		// 	return;

		// if( ! wp_verify_nonce( $_POST['add_levels_to_terms_nonce'], 'add_levels_to_terms_nonce' ) )
		// 	return;

		// self::add_levels_to_terms();

		if( !empty( $_POST['add_levels_to_terms'] ) && 'add_levels' == $_POST['add_levels_to_terms'] && wp_verify_nonce( $_POST['add_levels_to_terms_nonce'], 'add_levels_to_terms_nonce' ) )
		{
			self::add_levels_to_terms();	
		} 

		if( !empty( $_POST['all_products_comments'] ) && !empty( $_POST['enable_comments'] ) && 'all_products_comments' == $_POST['all_products_comments'] && wp_verify_nonce( $_POST['all_products_comments_nonce'], 'all_products_comments_nonce' ) )
		{
			global $wpdb;
			file_put_contents('lot.txt', 'in');
			$wpdb->query(
				"
				UPDATE $wpdb->posts 
				SET comment_status = 'open'
				WHERE post_type = 'product' 
				"
			);
		}

		if( !empty( $_POST['all_products_comments'] ) && !empty( $_POST['disable_comments'] ) && 'all_products_comments' == $_POST['all_products_comments'] && wp_verify_nonce( $_POST['all_products_comments_nonce'], 'all_products_comments_nonce' ) )
		{
			global $wpdb;
			file_put_contents('lot.txt', 'in');
			$wpdb->query(
				"
				UPDATE $wpdb->posts 
				SET comment_status = 'closed'
				WHERE post_type = 'product' 
				"
			);
		}

    }

    function add_levels_to_terms()
    {
    	$args = array(
				'hide_empty'    => false
			);
	    
	    $allterms =  get_terms( 'product_cat', $args );
	    
	    foreach( $allterms as $term )
		{
			if($term->parent == 0)
			{
				update_post_meta($term->term_id, '_level', 0);
			}
			else
			{
				$level = self::walk($term, 0);
				update_post_meta($term->term_id, '_level', $level);
			}
		}
    }

    function walk($term, $level)
    {
    	$term = get_post_meta($term->parent);
    	if($term->parent == 0)
    	{
    		$level++;
    		return $level;
    	}
    	else
    	{
    		$level++;
    		$this->walk($term, $level);
    	}
    }


    function add_all_terms_to_options_db()
    {
		$o = get_option('WooTweak2_options');

		$args = array(
				'hide_empty'    => false
			);
	    
	    $allterms =  get_terms( 'product_cat', $args );

	    $largeterms = array();

	    foreach( $allterms as $term )
		{
			$largeterms[] = $term;
		}

		$o['wt2_largeterms_slugs'] = $largeterms;
		
		update_option('WooTweak2_options', $o);
    }


    function load_terms_from_wp_db($taxonomy)
    {
	    global $wpdb;

	    $query = 'SELECT DISTINCT 
                    t.term_id, t.name, t.slug 
                FROM
                    wp_terms t 
                INNER JOIN 
                    wp_term_taxonomy tax 
                ON 
                	`tax`.term_id = `t`.term_id
                WHERE 
                    ( `tax`.taxonomy = \'' . $taxonomy . '\')';

	    $result =  $wpdb->get_results($query , ARRAY_A);

	    return $result;                 
	}

	function load_woo_terms_from_wp_db($taxonomy)
    {
	    global $wpdb;

	    $query = 'SELECT DISTINCT 
                    t.term_id, t.name, t.slug 
                FROM
                    wp_terms t 
                INNER JOIN 
                    wp_term_taxonomy tax 
                ON 
                	`tax`.term_id = `t`.term_id
                INNER JOIN
                    wp_woocommerce_termmeta woo
                ON
                    `t`.term_id = `woo`.woocommerce_term_id
                WHERE 
                    ( `tax`.taxonomy = \'' . $taxonomy . '\')';

	    $result =  $wpdb->get_results($query , ARRAY_A);

	    return $result;                 
	}
}

    
add_action('wp_enqueue_scripts', 'wt2_styles');

function wt2_styles()
{
	$o = get_option('WooTweak2_options');

	// wp_register_style('wootweak2', plugins_url('/css/woocommerce-tweaker.css', __FILE__), array('woocommerce-general') );
	// wp_enqueue_style('wootweak2');

	if(isset($o['wt2_use_flexbox_layout']) && $o['wt2_use_flexbox_layout'])
	{
		wp_register_style('wootweak2flex', plugins_url('/css/woocommerce-tweaker-flexbox.css', __FILE__), array('woocommerce-general') );
		wp_enqueue_style('wootweak2flex');
	}
}

add_action('admin_enqueue_scripts', 'wt2_admin_styles');

function wt2_admin_styles()
{
	wp_register_style('wootweak2admin', plugins_url('/css/woocommerce-tweaker-admin.css', __FILE__) );
	wp_enqueue_style('wootweak2admin');
}

add_action('wp_enqueue_scripts', 'wt2_scripts');

function wt2_scripts()
{
	wp_enqueue_script('wootweak2', plugins_url('/js/woocommerce-tweaker.js', __FILE__), array('jquery', 'wc-single-product'), '1.0', true );
}

$woot = new WooTweak2();

// Cart widget ----------------------------------------------------------------------------------------

class WooTweak2_cart_widget extends WP_Widget {
    
    function __construct()
    {
	    $params = array(
			'name' => __('WooCommerce small Cart'),
			'description' => __('Small cart. Amount and total only', 'WooTweak2') // plugin description that is showed in Widget section of admin panel
	    );
    
	    parent::__construct('WooTweak2_cart_widget', '', $params);
    }
    
    function form($instance)
    {
	    extract($instance);
	    ?>
		<!--some html with input fields-->
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woocommerce'); ?></label>
			<input
			type="text"
			class="widefat"
			id="<?php echo $this->get_field_id('title'); ?>"
			name="<?php echo $this->get_field_name('title'); ?>"
			value="<?php if(isset($title)) echo esc_attr($title) ?>"
			/>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Custom text before price:', 'WooTweak2'); ?></label>
			<input
			type="text"
			class="widefat"
			id="<?php echo $this->get_field_id('prepricetext'); ?>"
			name="<?php echo $this->get_field_name('prepricetext'); ?>"
			value="<?php if(isset($prepricetext)) echo esc_attr($prepricetext) ?>"
			/>
		</p>
	    <?php
    }
    
    function widget($args, $instance)
    {
	    global $woo_options, $woocommerce;
	    if ( is_cart() || is_checkout() ) return;

	    extract($args);
	    extract($instance);
	    
	    echo $before_widget;
		echo $before_title . $title . $after_title;
		?>
		<ul class="cart_list product_list_widget widget_shopping_cart">
		<a href="<?php echo $woocommerce->cart->get_cart_url(); ?>" title="<?php _e('View your shopping cart', 'WooTweak2'); ?>">
		    <span> 
		    <?php
		    if(isset($prepricetext)) 
		    {
		    	echo $prepricetext;
		    }
		    else
		    {
		    	echo sprintf(_n('%d item &ndash; ', '%d items &ndash; ', $woocommerce->cart->cart_contents_count, 'WooTweak2'), $woocommerce->cart->cart_contents_count);	
		    }	
		    echo $woocommerce->cart->get_cart_subtotal();
		    ?>
		    </span>
		</a>
		</ul>
		<?php
	    echo $after_widget;
    }
}

function WooTweak2_cart_widget_register_function()
{
    register_widget('WooTweak2_cart_widget');
}

add_action('widgets_init', 'WooTweak2_cart_widget_register_function');



?>

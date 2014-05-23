<?php

class ImportExportEnhancement {

	private $pluginOptions;
	private $prefix;
	private $page;

	function __construct( $pluginOptions, $prefix = 'import_export_enhancement', $page = '' ) 
	{
        $this->pluginOptions = $pluginOptions;
        $this->prefix = $prefix;
        $this->page = $page;

        add_action( 'admin_init', array($this, 'import_export_plugin_process_settings_export') );
		add_action( 'admin_init', array($this, 'import_export_plugin_process_settings_import') );
    }

	function import_export_plugin_buttons($own_forms = false) 
	{
		?>
			<h3><?php _e( 'Export Settings' ); ?></h3>
			<form method="post">
				<p>
					<input type="hidden" name="import_export_action" value="export_settings" />
					<?php wp_nonce_field( 'plugin_settings_export_nonce', 'plugin_settings_export_nonce' ); ?>
					<input type="submit" name="submit" value="<?php echo __('Export', 'woocommerce'); ?>" class="button-primary"  />
				</p>
			</form>

			<h3><?php _e( 'Import Settings' ); ?></h3>
			<form method="post" enctype="multipart/form-data">
				<p>
					<input type="file" name="import_file"/>
				</p>
				<p>
					<input type="hidden" name="import_export_action" value="import_settings" />
					<?php wp_nonce_field( 'plugin_settings_import_nonce', 'plugin_settings_import_nonce' ); ?>
					<input type="submit" name="submit" value="<?php echo __('Import', 'woocommerce'); ?>" class="button-primary"  />
				</p>
			</form>
		<?php
	}

	function import_export_plugin_process_settings_export() 
	{

		if( empty( $_POST['import_export_action'] ) || 'export_settings' != $_POST['import_export_action'] )
			return;

		if( ! wp_verify_nonce( $_POST['plugin_settings_export_nonce'], 'plugin_settings_export_nonce' ) )
			return;

		if( ! current_user_can( 'manage_options' ) )
			return;

		file_put_contents('log.txt', $_POST['import_export_action']);
		$settings = get_option( $this->pluginOptions );

		ignore_user_abort( true );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename='.$this->prefix.'-settings-export-' . date( 'm-d-Y' ) . '.json' );
		header( "Expires: 0" );

		echo json_encode( $settings );

		exit;
	}

	
	function import_export_plugin_process_settings_import() 
	{

		if( empty( $_POST['import_export_action'] ) || 'import_settings' != $_POST['import_export_action'] )
			return;

		if( ! wp_verify_nonce( $_POST['plugin_settings_import_nonce'], 'plugin_settings_import_nonce' ) )
			return;

		if( ! current_user_can( 'manage_options' ) )
			return;

		if( empty($_FILES['import_file']['name']) ) return;

		$extension = end( explode( '.', $_FILES['import_file']['name'] ) );

		if( $extension != 'json' ) {
			wp_die( __( 'Please upload a valid .json file' ) );
		}

		$import_file = $_FILES['import_file']['tmp_name'];

		if( empty( $import_file ) ) {
			wp_die( __( 'Please upload a file to import' ) );
		}

		$settings = (array) json_decode( file_get_contents( $import_file ) );

		update_option( $this->pluginOptions, $settings );

		$url = end(explode('wp-admin/', $_SERVER['REQUEST_URI']));

		// wp_safe_redirect( admin_url( 'options-general.php?page='.$pluginOptions ) ); exit;
		wp_safe_redirect( admin_url( $url ) ); exit;

	}

}
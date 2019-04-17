<table class="form-table">
    <tbody>
    <tr valign="top">
        <th scope="row" colspan="2"><h3><?php _e( 'Privacy and Data Protection', 'wp-statistics' ); ?></h3></th>
    </tr>

    <tr valign="top">
        <td scope="row" colspan="2"><?php echo sprintf( __( 'If you want to delete visitor data, please <a href="%s">click here</a>.', 'wp-statistics' ), WP_Statistics_Admin_Pages::admin_url( 'optimization', array( 'tab' => 'purging' ) ) ); ?></td>
    </tr>

    <tr valign="top">
        <th scope="row">
            <label for="anonymize_ips"><?php _e( 'Anonymize IP Addresses:', 'wp-statistics' ); ?></label>
        </th>

        <td>
            <input id="anonymize_ips" type="checkbox" value="1" name="wps_anonymize_ips" <?php echo WP_STATISTICS\Option::get( 'anonymize_ips' ) == true
				? "checked='checked'" : ''; ?>>
            <label for="anonymize_ips"><?php _e( 'Enable', 'wp-statistics' ); ?></label>

            <p class="description"><?php echo __( 'This option anonymizes the user IP address for GDPR compliance. For example, 888.888.888.888 > 888.888.888.000.', 'wp-statistics' ); ?></p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row">
            <label for="hash_ips"><?php _e( 'Hash IP Addresses:', 'wp-statistics' ); ?></label>
        </th>

        <td>
            <input id="hash_ips" type="checkbox" value="1" name="wps_hash_ips" <?php echo WP_STATISTICS\Option::get( 'hash_ips' ) == true
				? "checked='checked'" : ''; ?>>
            <label for="hash_ips"><?php _e( 'Enable', 'wp-statistics' ); ?></label>

            <p class="description"><?php echo __(
				                                  'This feature will not store IP addresses in the database but instead used a unique hash.',
				                                  'wp-statistics'
			                                  ) . ' ' . __(
				                                  'The "Store entire user agent string" setting will be disabled if this is selected.',
				                                  'wp-statistics'
			                                  ) . ' ' . __(
				                                  'You will not be able to recover the IP addresses in the future to recover location information if this is enabled.',
				                                  'wp-statistics'
			                                  ); ?></p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row">
            <label for="store_ua"><?php _e( 'Store entire user agent string:', 'wp-statistics' ); ?></label>
        </th>

        <td>
            <input id="store_ua" type="checkbox" value="1" name="wps_store_ua" <?php echo WP_STATISTICS\Option::get( 'store_ua' ) == true
				? "checked='checked'" : ''; ?>>
            <label for="store_ua"><?php _e( 'Enable', 'wp-statistics' ); ?></label>

            <p class="description"><?php _e( 'Only enabled for debugging. (If the IP hash\'s are enabled, This option disabling automatically.)', 'wp-statistics' ); ?></p>
        </td>
    </tr>
    </tbody>
</table>

<?php submit_button( __( 'Update', 'wp-statistics' ), 'primary', 'submit' );

<?php
/**
 * Inpost Delivery Point Selection
 *
 * @package Chocante_Delivery_Point
 */

defined( 'ABSPATH' ) || exit;
?>

<tr class="chocante-delivery-point">
	<th><?php esc_html_e( 'Delivery Point', 'chocante-delivery-point' ); ?></th>
	<td>		
		<address>
			<select id="chocanteDeliveryPointInpost" data-language="<?php echo esc_attr( $current_language ); ?>" data-config="<?php echo esc_attr( $widget_config ); ?>" style="width: 100%;">
				<?php if ( ! empty( $delivery_point_number ) && ! empty( $delivery_point_address ) ) : ?>
					<option>
						<?php echo esc_html( $delivery_point_number ) . ' (' . esc_html( $delivery_point_address ) . ')'; ?>
					</option>
				<?php else : ?>
					<option><?php esc_html_e( 'Search for Parcel Locker', 'chocante-delivery-point' ); ?></option>
				<?php endif; ?>
			</select>
		</address>
		<button type="button" class="button" disabled="disabled" id="chocanteSelectInpostParcelLocker" data-token="<?php echo esc_attr( defined( 'INPOST_GEO_TOKEN' ) ? INPOST_GEO_TOKEN : '' ); ?>" data-widget-language="<?php echo esc_attr( $widget_language ); ?>" data-widget-config="<?php echo esc_attr( $widget_config ); ?>" data-point="<?php echo esc_attr( $delivery_point_number ); ?>">
			<?php esc_html_e( 'Select on map', 'chocante-delivery-point' ); ?>
		</button>
	</td>
</tr>
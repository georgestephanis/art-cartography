<?php
/*
 * Plugin Name: Art Cartography
 * Plugin URI: https://github.com/georgestephanis/art-cartography/
 * Description: Managing artwork and its placement for galleries.
 * Version: 0.1-alpha
 * Author: George Stephanis
 * Author URI: http://stephanis.info
 * License: GNU General Public License v2 or later
 */

define( 'ART_CART_POST_TYPE',       'ac-art' );
define( 'ART_CART_TAXONOMY',        'ac-cartography' );
define( 'ART_CART_ART_DIMENSIONS',  'ac-art-dimensions' );
define( 'ART_CART_SURFACE_DETAILS', 'ac-surface-details' );

/**
 * Registers the post types and taxonomies for Art Cartography.
 */
function register_art_cart_post_types() {
	register_post_type( ART_CART_POST_TYPE, array(
		'label'         => __( 'Art' ),
		'public'        => true,
		'menu_icon'     => 'dashicons-art',
		'menu_position' => 8,
		'supports'      => array(
			'title',
			'editor',
			'thumbnail',
			'revisions',
		),
		'show_in_rest'  => true,
		'rest_base'     => 'art',
		'register_meta_box_cb' => 'ac_art_post_type_meta_boxes',
	) );

	register_taxonomy( ART_CART_TAXONOMY, ART_CART_POST_TYPE, array(
		'label'         => __( 'Display Locations' ),
		'public'        => true,
		'hierarchical'  => true,
	) );
}
add_action( 'init', 'register_art_cart_post_types' );

function ac_art_post_type_meta_boxes() {
	add_meta_box( 'ac-artwork-details', __( 'Artwork Details' ), 'ac_artwork_details_meta_box' );
	add_action( 'save_post', 'ac_save_artwork', 10, 2 );
}

function ac_artwork_details_meta_box( $post ) {
	$dimensions = get_post_meta( $post->ID, ART_CART_ART_DIMENSIONS, true );
	$dimensions = wp_parse_args( $dimensions, array(
			'h' => 12,
			'w' => 12,
			'd' => 1,
		) );
	?>
	<style>
		#ac-artwork-details input[type=number] {
			width: 80px;
		}
		#ratio-preview-wrap {
			float: right;
		}
		#ratio-preview {
			position: relative;
			border: 1px solid #000;
		}
		#ratio-preview-width {
			position: absolute;
			left: 0;
			right: 0;
			bottom: 105%;
			font-size: 1em;
			text-align: center;
		}
		#ratio-preview-height {
			position: absolute;
			right: 105%;
			top: 40%;
			font-size: 1em;
			text-align: center;

		}
	</style>
	<table class="form-table">
		<?php wp_nonce_field( "edit-art-{$post->ID}-details", '_artnonce', false ); ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Dimensions:' ); ?></th>
			<td>
				<figure id="ratio-preview-wrap"></figure>
				<input id="<?php echo esc_attr( ART_CART_ART_DIMENSIONS ); ?>-w" type="number" min="0" size="6" name="<?php echo esc_attr( ART_CART_ART_DIMENSIONS ); ?>[w]" value="<?php echo esc_attr( $dimensions['w'] ); ?>" />
					<label for="<?php echo esc_attr( ART_CART_ART_DIMENSIONS ); ?>-w"><?php esc_html_e( 'Width (in inches)' ); ?></label><br />
				<input id="<?php echo esc_attr( ART_CART_ART_DIMENSIONS ); ?>-h" type="number" min="0" size="6" name="<?php echo esc_attr( ART_CART_ART_DIMENSIONS ); ?>[h]" value="<?php echo esc_attr( $dimensions['h'] ); ?>" />
					<label for="<?php echo esc_attr( ART_CART_ART_DIMENSIONS ); ?>-h"><?php esc_html_e( 'Height (in inches)' ); ?></label><br />
				<input id="<?php echo esc_attr( ART_CART_ART_DIMENSIONS ); ?>-d" type="number" min="0" size="4" name="<?php echo esc_attr( ART_CART_ART_DIMENSIONS ); ?>[d]" value="<?php echo esc_attr( $dimensions['d'] ); ?>" />
					<label for="<?php echo esc_attr( ART_CART_ART_DIMENSIONS ); ?>-d"><?php esc_html_e( 'Depth (in inches)' ); ?></label>
			</td>
		</tr>
	</table>
	<script type="text/html" id="tmpl-ratio-preview">
		<div id="ratio-preview" style="width: {{ data.css_width }}; height: {{ data.css_height }};{{ data.css_bg }}">
			<div id="ratio-preview-width">{{ data.display_width }}</div>
			<div id="ratio-preview-height">{{ data.display_height }}</div>
		</div>
	</script>
	<script>
		jQuery(document).ready(function($){
			var ratiopreview = wp.template('ratio-preview'),
				$previewWrap = $('#ratio-preview-wrap'),
				$field_w = $('#<?php echo esc_js( ART_CART_ART_DIMENSIONS ); ?>-w'),
				$field_h = $('#<?php echo esc_js( ART_CART_ART_DIMENSIONS ); ?>-h'),
				$postImageDiv = $('#postimagediv'),
				regenerateRenderPreview = function(){
					var w = parseFloat( $field_w.val() ),
						h = parseFloat( $field_h.val() ),
						css_bg = 'background: #ccc;',
						css_w = 200,
						css_h = 200,
						display_w = Math.floor( w / 12 ) + '\'' + ( w % 12 ) + '"',
						display_h = Math.floor( h / 12 ) + '\'' + ( h % 12 ) + '"',
						img = $postImageDiv.find('img').attr('src');

					if ( h > w ) {
						css_w = 200 * w / h;
					} else if ( w > h ) {
						css_h = 200 * h / w;
					}

					if ( w <= 12 ) {
						display_w = w + '"';
					}
					if ( h <= 12 ) {
						display_h = h + '"';
					}

					if ( img ) {
						img = img.replace( /-\d+x\d+\./, '.' );
						css_bg = 'background: url(\'' + img + '\') 100% 100% no-repeat; background-size: 100% 100%;';
					}

					$previewWrap.html( ratiopreview( {
						css_bg         : css_bg,
						css_width      : css_w + 'px',
						css_height     : css_h + 'px',
						display_width  : display_w,
						display_height : display_h
					} ) );
				};
			$( $field_w ).add( $field_h).on( 'change', regenerateRenderPreview );
			regenerateRenderPreview();
		});
	</script>
	<?php
}

function ac_save_artwork( $post_id, $post ) {
	if ( empty( $_POST['_artnonce'] ) || ! wp_verify_nonce( $_POST['_artnonce'], "edit-art-{$post_id}-details" ) ) {
		return;
	}

	$post_type = get_post_type_object( $post->post_type );
	if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
		return;
	}

	if ( ! empty( $_POST[ ART_CART_ART_DIMENSIONS ] ) ) {
		$val = array(
			'h' => isset( $_POST[ ART_CART_ART_DIMENSIONS ]['h'] ) ? $_POST[ ART_CART_ART_DIMENSIONS ]['h'] : 12,
			'w' => isset( $_POST[ ART_CART_ART_DIMENSIONS ]['w'] ) ? $_POST[ ART_CART_ART_DIMENSIONS ]['w'] : 12,
			'd' => isset( $_POST[ ART_CART_ART_DIMENSIONS ]['d'] ) ? $_POST[ ART_CART_ART_DIMENSIONS ]['d'] : 1,
		);

		update_post_meta( $post_id, ART_CART_ART_DIMENSIONS, $val );
	}
}
add_action( 'save_post', 'ac_save_artwork', 10, 2 );

function ac_edit_cartography_form( $term ) {
	$surface_details = get_term_meta( $term->term_id, ART_CART_SURFACE_DETAILS, true );
	$surface_details = wp_parse_args( $surface_details, array(
		'h'     => 8 * 12,
		'w'     => 16 * 12,
		'color' => '#227733',
	) );
	wp_enqueue_script( 'wp-util' );
	?>
	<table class="form-table">
		<?php wp_nonce_field( "edit-surface-{$term->term_id}-details", '_artnonce', false ); ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Details:' ); ?></th>
			<td>
				<figure id="ratio-preview-wrap"></figure>
				<input id="<?php echo esc_attr( ART_CART_SURFACE_DETAILS ); ?>-w" type="number" min="0" size="6" name="<?php echo esc_attr( ART_CART_SURFACE_DETAILS ); ?>[w]" value="<?php echo esc_attr( $surface_details['w'] ); ?>" />
					<label for="<?php echo esc_attr( ART_CART_SURFACE_DETAILS ); ?>-w"><?php esc_html_e( 'Width (in inches)' ); ?></label><br />
				<input id="<?php echo esc_attr( ART_CART_SURFACE_DETAILS ); ?>-h" type="number" min="0" size="6" name="<?php echo esc_attr( ART_CART_SURFACE_DETAILS ); ?>[h]" value="<?php echo esc_attr( $surface_details['h'] ); ?>" />
					<label for="<?php echo esc_attr( ART_CART_SURFACE_DETAILS ); ?>-h"><?php esc_html_e( 'Height (in inches)' ); ?></label><br />
				<input id="<?php echo esc_attr( ART_CART_SURFACE_DETAILS ); ?>-color" type="color" name="<?php echo esc_attr( ART_CART_SURFACE_DETAILS ); ?>[color]" value="<?php echo esc_attr( $surface_details['color'] ); ?>" />
					<label for="<?php echo esc_attr( ART_CART_SURFACE_DETAILS ); ?>-color"><?php esc_html_e( 'Color' ); ?></label>
			</td>
		</tr>
		<tr>
			<td id="surface-wrap" colspan="2"></td>
		</tr>
	</table>
	<script type="text/html" id="tmpl-surface">
		<div id="surface" style="width: {{ data.css_width }}; height: {{ data.css_height }}; background-color: {{ data.css_color }};">

		</div>
	</script>
	<script>
		jQuery(document).ready(function($){
			var surfaceTmpl = wp.template('surface'),
				$surfaceWrap = $('#surface-wrap'),
				$surface_w = $('#<?php echo esc_js( ART_CART_SURFACE_DETAILS ); ?>-w'),
				$surface_h = $('#<?php echo esc_js( ART_CART_SURFACE_DETAILS ); ?>-h'),
				$surface_color = $('#<?php echo esc_js( ART_CART_SURFACE_DETAILS ); ?>-color'),
				regenerateSurface = function(){
					var css_w = $surface_w.val() * 5,
						css_h = $surface_h.val() * 5,
						css_color = $surface_color.val();
					$surfaceWrap.html( surfaceTmpl( {
						css_width      : css_w + 'px',
						css_height     : css_h + 'px',
						css_color      : css_color
					} ) );
				};
			$( $surface_w ).add( $surface_h ).add( $surface_color ).on( 'change', regenerateSurface );
			regenerateSurface();
		});
	</script>
	<?php
}
add_action( ART_CART_TAXONOMY . '_edit_form', 'ac_edit_cartography_form' );

function ac_edit_tax_submission( $term_id ) {
	if ( empty( $_POST['_artnonce'] ) || ! wp_verify_nonce( $_POST['_artnonce'], "edit-surface-{$term_id}-details" ) ) {
		return;
	}

	$taxonomy = get_taxonomy( ART_CART_TAXONOMY );
	if ( ! current_user_can( $taxonomy->cap->edit_terms, $term_id ) ) {
		return;
	}

	if ( ! empty( $_POST[ ART_CART_SURFACE_DETAILS ] ) ) {
		$val = array(
			'h'     => isset( $_POST[ ART_CART_SURFACE_DETAILS ]['h'] )     ? floatval( $_POST[ ART_CART_SURFACE_DETAILS ]['h'] )                  : 8 * 12,
			'w'     => isset( $_POST[ ART_CART_SURFACE_DETAILS ]['w'] )     ? floatval( $_POST[ ART_CART_SURFACE_DETAILS ]['w'] )                  : 16 * 12,
			'color' => isset( $_POST[ ART_CART_SURFACE_DETAILS ]['color'] ) ? ac_sanitize_hex_color( $_POST[ ART_CART_SURFACE_DETAILS ]['color'] ) : '#227733',
		);

		update_term_meta( $term_id, ART_CART_SURFACE_DETAILS, $val );
	}
}
add_action( 'edit_' . ART_CART_TAXONOMY, 'ac_edit_tax_submission' );

function ac_sanitize_hex_color( $color ) {
	if ( '' === $color )
		return '';

	// 3 or 6 hex digits, or the empty string.
	if ( preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) )
		return $color;
}
<?php
/**
 * "Hover zoom" switch on the image widgets of the common page builders.
 *
 * Every switch only adds the AGRSE_Zoom_Builders::CSS_CLASS class to the
 * image or to its wrapper. The front-end script always includes that class in
 * its selector, so the switch works whatever selector is set in the settings.
 *
 * - Block editor: core/image (adds the class to the block's className).
 * - Elementor: Image widget (v3) and the atomic Image element (v4, e-image).
 * - WPBakery: Single Image (vc_single_image).
 * - Beaver Builder: Photo module.
 *
 * @package agencia-rse-image-zoom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AGRSE_Zoom_Builders {

	const CSS_CLASS = 'ov-image-zoom';

	/** Setting name used by every builder. */
	const SETTING = 'ov_image_zoom';

	/** Prop of the Elementor v4 atomic Image element. */
	const ATOMIC_PROP = 'ov-image-zoom';

	public static function init() {
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'block_editor_assets' ) );

		add_action( 'elementor/element/image/section_image/before_section_end', array( __CLASS__, 'elementor_control' ) );
		add_filter( 'elementor/atomic-widgets/props-schema', array( __CLASS__, 'atomic_props' ) );
		add_filter( 'elementor/atomic-widgets/controls', array( __CLASS__, 'atomic_controls' ), 10, 2 );
		add_filter( 'elementor/widget/render_content', array( __CLASS__, 'atomic_render' ), 10, 2 );

		add_action( 'vc_after_init', array( __CLASS__, 'wpbakery_param' ) );
		add_filter( 'vc_shortcodes_css_class', array( __CLASS__, 'wpbakery_class' ), 10, 3 );

		add_filter( 'fl_builder_register_settings_form', array( __CLASS__, 'beaver_field' ), 10, 2 );
		add_filter( 'fl_builder_module_attributes', array( __CLASS__, 'beaver_class' ), 10, 2 );
	}

	/* --- Block editor ------------------------------------------------------ */

	public static function block_editor_assets() {
		wp_enqueue_script(
			'agencia-rse-image-zoom-editor',
			AGRSE_ZOOM_URL . 'assets/js/block-editor.js',
			array( 'wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			AGRSE_ZOOM_VERSION,
			true
		);
		wp_add_inline_script( 'agencia-rse-image-zoom-editor', 'window.AgrseZoomEditor = ' . wp_json_encode( array( 'cssClass' => self::CSS_CLASS ) ) . ';', 'before' );
		wp_set_script_translations( 'agencia-rse-image-zoom-editor', 'agencia-rse-image-zoom' );
	}

	/* --- Elementor v3: Image widget ------------------------------------------ */

	public static function elementor_control( $widget ) {
		$widget->add_control(
			self::SETTING,
			array(
				'label'        => __( 'Hover zoom', 'agencia-rse-image-zoom' ),
				'description'  => __( 'Zoom in on the image under the cursor.', 'agencia-rse-image-zoom' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => self::CSS_CLASS,
				'prefix_class' => '',
				'separator'    => 'before',
			)
		);
	}

	/* --- Elementor v4: atomic Image (e-image) ---------------------------------- */

	/**
	 * The schema filter gets no element. e-image is the only atomic schema with
	 * both an "image" and a "link" prop.
	 */
	public static function atomic_props( $schema ) {
		$bool = '\Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type';
		if ( is_array( $schema ) && isset( $schema['image'], $schema['link'] ) && ! isset( $schema[ self::ATOMIC_PROP ] ) && class_exists( $bool ) ) {
			$schema[ self::ATOMIC_PROP ] = $bool::make()->default( false );
		}
		return $schema;
	}

	public static function atomic_controls( $controls, $element ) {
		$section = '\Elementor\Modules\AtomicWidgets\Controls\Section';
		$switch  = '\Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control';
		if ( ! is_object( $element ) || 'e-image' !== $element->get_name() || ! class_exists( $switch ) ) {
			return $controls;
		}
		foreach ( $controls as $control ) {
			if ( $control instanceof $section && 'content' === $control->get_id() ) {
				$control->add_item( $switch::bind_to( self::ATOMIC_PROP )->set_label( __( 'Hover zoom', 'agencia-rse-image-zoom' ) ) );
				break;
			}
		}
		return $controls;
	}

	public static function atomic_render( $content, $widget ) {
		if ( ! is_object( $widget ) || 'e-image' !== $widget->get_name() || ! method_exists( $widget, 'get_atomic_setting' ) ) {
			return $content;
		}
		if ( true !== $widget->get_atomic_setting( self::ATOMIC_PROP ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $content;
		}
		$html = new WP_HTML_Tag_Processor( $content );
		if ( $html->next_tag( 'img' ) ) {
			$html->add_class( self::CSS_CLASS );
			return $html->get_updated_html();
		}
		return $content;
	}

	/* --- WPBakery: Single Image ------------------------------------------------ */

	public static function wpbakery_param() {
		if ( ! function_exists( 'vc_add_param' ) ) {
			return;
		}
		vc_add_param(
			'vc_single_image',
			array(
				'type'        => 'checkbox',
				'heading'     => __( 'Hover zoom', 'agencia-rse-image-zoom' ),
				'description' => __( 'Zoom in on the image under the cursor.', 'agencia-rse-image-zoom' ),
				'param_name'  => self::SETTING,
				'value'       => array( __( 'Yes', 'agencia-rse-image-zoom' ) => 'yes' ),
			)
		);
	}

	public static function wpbakery_class( $classes, $base, $atts = array() ) {
		if ( 'vc_single_image' === $base && is_array( $atts ) && isset( $atts[ self::SETTING ] ) && 'yes' === $atts[ self::SETTING ] ) {
			$classes .= ' ' . self::CSS_CLASS;
		}
		return $classes;
	}

	/* --- Beaver Builder: Photo module ----------------------------------------- */

	public static function beaver_field( $form, $id ) {
		if ( 'photo' !== $id || ! isset( $form['general']['sections']['general']['fields'] ) ) {
			return $form;
		}
		$form['general']['sections']['general']['fields'][ self::SETTING ] = array(
			'type'    => 'select',
			'label'   => __( 'Hover zoom', 'agencia-rse-image-zoom' ),
			'default' => '0',
			'options' => array(
				'0' => __( 'No', 'agencia-rse-image-zoom' ),
				'1' => __( 'Yes', 'agencia-rse-image-zoom' ),
			),
		);
		return $form;
	}

	public static function beaver_class( $attrs, $module ) {
		if ( is_object( $module ) && isset( $module->slug, $module->settings ) && 'photo' === $module->slug && ! empty( $module->settings->{self::SETTING} ) ) {
			$attrs['class'][] = self::CSS_CLASS;
		}
		return $attrs;
	}
}

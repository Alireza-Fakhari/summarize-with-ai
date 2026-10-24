<?php
/**
 * Elementor widget. Only autoloaded from the elementor/widgets/register hook,
 * so the Elementor base class is guaranteed to exist.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI\Integrations;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use SummarizeWithAI\Renderer;
use SummarizeWithAI\Services;
use SummarizeWithAI\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor widget for the AI buttons.
 */
class ElementorWidget extends Widget_Base {

	/**
	 * Widget machine name.
	 */
	public function get_name() {
		return 'swai_summarize';
	}

	/**
	 * Widget title.
	 */
	public function get_title() {
		return __( 'Summarize with AI', 'summarize-with-ai' );
	}

	/**
	 * Widget icon.
	 */
	public function get_icon() {
		return 'eicon-ai';
	}

	/**
	 * Widget categories.
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Search keywords.
	 */
	public function get_keywords() {
		return array( 'ai', 'chatgpt', 'perplexity', 'claude', 'summary', 'خلاصه' );
	}

	/**
	 * Style handles the widget needs.
	 */
	public function get_style_depends() {
		return array( 'swai' );
	}

	/**
	 * Script handles the widget needs.
	 */
	public function get_script_depends() {
		return array( 'swai' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		$s        = Settings::get();
		$services = array();
		foreach ( Services::all() as $id => $svc ) {
			$services[ $id ] = $svc['name'];
		}
		$modes = array();
		foreach ( Services::active_modes() as $id => $m ) {
			$modes[ $id ] = $m['label'];
		}
		$with_default = static function ( $choices ) {
			return array( '' => __( 'Default (from settings)', 'summarize-with-ai' ) ) + $choices;
		};

		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'summarize-with-ai' ) ) );
		$this->add_control(
			'services',
			array(
				'label'       => __( 'AI assistants', 'summarize-with-ai' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $services,
				'default'     => $s['enabled'],
				'label_block' => true,
			)
		);
		$this->add_control(
			'label',
			array(
				'label'   => __( 'Label', 'summarize-with-ai' ),
				'type'    => Controls_Manager::TEXT,
				'default' => $s['label'],
			)
		);
		$this->add_control(
			'show_modes',
			array(
				'label'        => __( 'Show mode switcher', 'summarize-with-ai' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => $s['show_modes'] ? 'yes' : '',
			)
		);
		$this->add_control(
			'mode',
			array(
				'label'   => __( 'Default mode', 'summarize-with-ai' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $with_default( $modes ),
				'default' => '',
			)
		);
		$this->add_control(
			'prompt',
			array(
				'label'       => __( 'Custom prompt', 'summarize-with-ai' ),
				'type'        => Controls_Manager::TEXTAREA,
				'description' => '{url} {title} {site} {excerpt}',
			)
		);
		$this->add_control(
			'template',
			array(
				'label'   => __( 'Style', 'summarize-with-ai' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $with_default( Settings::choices( 'template' ) ),
				'default' => '',
			)
		);
		$this->add_control(
			'theme',
			array(
				'label'   => __( 'Color scheme', 'summarize-with-ai' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $with_default( Settings::choices( 'theme' ) ),
				'default' => '',
			)
		);
		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'summarize-with-ai' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Start', 'summarize-with-ai' ),
						'icon'  => 'eicon-h-align-left',
					),
					'center'     => array(
						'title' => __( 'Center', 'summarize-with-ai' ),
						'icon'  => 'eicon-h-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'End', 'summarize-with-ai' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'selectors' => array( '{{WRAPPER}} .swai' => '--swai-justify: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'style_label',
			array(
				'label' => __( 'Label', 'summarize-with-ai' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Text color', 'summarize-with-ai' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .swai__label' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .swai__label',
			)
		);
		$this->add_control(
			'label_icon_color',
			array(
				'label'     => __( 'Icon color', 'summarize-with-ai' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .swai__label-icon' => 'color: {{VALUE}} !important;' ),
			)
		);
		$this->add_responsive_control(
			'label_icon_size',
			array(
				'label'      => __( 'Icon size', 'summarize-with-ai' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 10,
						'max' => 48,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .swai__label-icon' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'style_buttons',
			array(
				'label' => __( 'Buttons', 'summarize-with-ai' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'btn_typography',
				'selector'       => '{{WRAPPER}} .swai__btn',
				'fields_options' => array(
					'font_family'    => array( 'selectors' => array( '{{SELECTOR}}' => 'font-family: "{{VALUE}}", sans-serif !important;' ) ),
					'font_size'      => array( 'selectors' => array( '{{SELECTOR}}' => 'font-size: {{SIZE}}{{UNIT}} !important;' ) ),
					'font_weight'    => array( 'selectors' => array( '{{SELECTOR}}' => 'font-weight: {{VALUE}} !important;' ) ),
					'text_transform' => array( 'selectors' => array( '{{SELECTOR}}' => 'text-transform: {{VALUE}} !important;' ) ),
					'letter_spacing' => array( 'selectors' => array( '{{SELECTOR}}' => 'letter-spacing: {{SIZE}}{{UNIT}} !important;' ) ),
				),
			)
		);
		$this->start_controls_tabs( 'btn_states' );
		foreach (
			array(
				'normal' => array( __( 'Normal', 'summarize-with-ai' ), '' ),
				'hover'  => array( __( 'Hover', 'summarize-with-ai' ), ':hover' ),
			) as $state => $meta
		) {
			$selector = '{{WRAPPER}} .swai__btn' . $meta[1];
			$this->start_controls_tab( 'btn_' . $state, array( 'label' => $meta[0] ) );
			$this->add_control(
				'btn_color_' . $state,
				array(
					'label'     => __( 'Text color', 'summarize-with-ai' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $selector => 'color: {{VALUE}} !important;' ),
				)
			);
			$this->add_control(
				'btn_bg_' . $state,
				array(
					'label'     => __( 'Background', 'summarize-with-ai' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $selector => 'background-color: {{VALUE}} !important;' ),
				)
			);
			$this->add_control(
				'btn_border_' . $state,
				array(
					'label'     => __( 'Border color', 'summarize-with-ai' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $selector => 'border-color: {{VALUE}} !important;' ),
				)
			);
			$this->add_group_control(
				Group_Control_Box_Shadow::get_type(),
				array(
					'name'           => 'btn_shadow_' . $state,
					'selector'       => $selector,
					'fields_options' => array(
						'box_shadow' => array(
							'selectors' => array( '{{SELECTOR}}' => 'box-shadow: {{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{SPREAD}}px {{COLOR}} {{box_shadow_position.VALUE}} !important;' ),
						),
					),
				)
			);
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'           => 'btn_border',
				'selector'       => '{{WRAPPER}} .swai__btn',
				'separator'      => 'before',
				// Our CSS is !important so themes cannot restyle it; match that here.
				'fields_options' => array(
					'border' => array( 'selectors' => array( '{{SELECTOR}}' => 'border-style: {{VALUE}} !important;' ) ),
					'width'  => array( 'selectors' => array( '{{SELECTOR}}' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;' ) ),
					'color'  => array( 'selectors' => array( '{{SELECTOR}}' => 'border-color: {{VALUE}} !important;' ) ),
				),
			)
		);
		$this->add_responsive_control(
			'btn_radius',
			array(
				'label'      => __( 'Corner radius', 'summarize-with-ai' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .swai__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;' ),
			)
		);
		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => __( 'Padding', 'summarize-with-ai' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .swai__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;' ),
			)
		);
		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Spacing', 'summarize-with-ai' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .swai__row' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => __( 'Icon size', 'summarize-with-ai' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 10,
						'max' => 40,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .swai__btn .swai__icon' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$st   = $this->get_settings_for_display();
		$args = array(
			'context'    => 'elementor',
			'post_id'    => get_the_ID(),
			'label'      => (string) $st['label'],
			'show_modes' => 'yes' === $st['show_modes'],
		);
		if ( ! empty( $st['services'] ) ) {
			$args['services'] = array_map( 'sanitize_key', (array) $st['services'] );
		}
		foreach ( array( 'mode', 'template', 'theme' ) as $key ) {
			if ( ! empty( $st[ $key ] ) ) {
				$args[ $key ] = sanitize_key( $st[ $key ] );
			}
		}
		if ( ! empty( $st['prompt'] ) ) {
			$args['prompt'] = sanitize_textarea_field( $st['prompt'] );
		}

		$html = Renderer::render( $args );
		if ( '' === $html && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$html = '<p style="padding:12px;border:1px dashed #cbd5e1;border-radius:8px">' . esc_html__( 'Pick at least one AI assistant.', 'summarize-with-ai' ) . '</p>';
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer.
	}
}

<?php
namespace ElementorGoogleMapExtended\Widgets;
//namespace Elementor;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Typography;
use Elementor\Group_Control_Text_Shadow;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class EB_Google_Map_Extended extends Widget_Base {

	/**
	 * Retrieve heading widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'eb-google-map-extended';
	}

	/**
	 * Retrieve heading widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Google Map Extended', 'extended-google-map-for-elementor' );
	}

	/**
	 * Retrieve heading widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-google-maps';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'eb-elementor-extended' ];
	}

	public function get_script_depends() {
		return [ 'eb-google-maps-api', 'eb-google-map' ];
	}


	/**
	 * Register google maps widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function _register_controls() {
		$this->start_controls_section(
			'section_map',
			[
				'label' => __( 'Map', 'extended-google-map-for-elementor' ),
			]
		);

		/**
		 * InternetCSS is the first who came up with this idea for Seaching Latitude & Longitude right inside Elementor Widget. If you wish to use, please credit and link us back.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @return string Copyright.
		 */
		$this->add_control(
		    'map_notice',
		    [
			    'label' => __( 'Find Latitude & Longitude', 'extended-google-map-for-elementor' ),
			    'type'  => Controls_Manager::RAW_HTML,
			    'raw'   => '<form onsubmit="ebMapFindAddress(this);" action="javascript:void(0);"><input type="text" id="eb-map-find-address" class="eb-map-find-address" style="margin-top:10px; margin-bottom:10px;"><input type="submit" value="Search" class="elementor-button elementor-button-default" onclick="ebMapFindAddress(this)"></form><div id="eb-output-result" class="eb-output-result" style="margin-top:10px; line-height: 1.3; font-size: 12px;"></div>',
			    'label_block' => true,
		    ]
	    );

		$this->add_control(
			'map_lat',
			[
				'label' => __( 'Latitude', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => '1.2833754',
				'default' => '1.2833754',
				'dynamic' => [ 'active' => true ]
			]
		);

		$this->add_control(
			'map_lng',
			[
				'label' => __( 'Longitude', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => '103.86072639999998',
				'default' => '103.86072639999998',
				'separator' => true,
				'dynamic' => [ 'active' => true ]
			]
		);

		$this->add_control(
			'zoom',
			[
				'label' => __( 'Zoom Level', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 15,
				],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 25,
					],
				],
			]
		);

		$this->add_responsive_control(
			'height',
			[
				'label' => __( 'Height', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'default'   => [
					'size' => 300,
				],
				'range' => [
					'vh' => [
						'min' => 10,
						'max' => 100,
					],
					'px' => [
						'min' => 40,
						'max' => 1440,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .eb-map' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'map_type',
			[
				'label' => __( 'Map Type', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'roadmap' => __( 'Road Map', 'extended-google-map-for-elementor' ),
					'satellite' => __( 'Satellite', 'extended-google-map-for-elementor' ),
					'hybrid' => __( 'Hybrid', 'extended-google-map-for-elementor' ),
					'terrain' => __( 'Terrain', 'extended-google-map-for-elementor' ),
				],
				'default' => 'roadmap',
			]
		);

		$this->add_control(
			'gesture_handling',
			[
				'label' => __( 'Gesture Handling', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'auto' => __( 'Auto (Default)', 'extended-google-map-for-elementor' ),
					'cooperative' => __( 'Cooperative', 'extended-google-map-for-elementor' ),
					'greedy' => __( 'Greedy', 'extended-google-map-for-elementor' ),
					'none' => __( 'None', 'extended-google-map-for-elementor' ),
				],
				'default' => 'auto',
				'description' => __('Understand more about Gesture Handling by reading it <a href="https://developers.google.com/maps/documentation/javascript/reference/3/#MapOptions" target="_blank">here.</a> Basically it control how it handles gestures on the map. Used to be draggable and scroll wheel function which is deprecated.'),
			]
		);


		/*$this->add_control(
			'scroll_wheel',
			[
				'label' => __( 'Scroll Wheel', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'no'
			]
		);*/

		$this->add_control(
			'zoom_control',
			[
				'label' => __( 'Show Zoom Control', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes'
			]
		);

		$this->add_control(
			'zoom_control_position',
			[
				'label' => __( 'Control Position', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'RIGHT_BOTTOM' => __( 'Bottom Right (Default)', 'extended-google-map-for-elementor' ),
					'TOP_LEFT' => __( 'Top Left', 'extended-google-map-for-elementor' ),
					'TOP_CENTER' => __( 'Top Center', 'extended-google-map-for-elementor' ),
					'TOP_RIGHT' => __( 'Top Right', 'extended-google-map-for-elementor' ),
					'LEFT_CENTER' => __( 'Left Center', 'extended-google-map-for-elementor' ),
					'RIGHT_CENTER' => __( 'Right Center', 'extended-google-map-for-elementor' ),
					'BOTTOM_LEFT' => __( 'Bottom Left', 'extended-google-map-for-elementor' ),
					'BOTTOM_CENTER' => __( 'Bottom Center', 'extended-google-map-for-elementor' ),
					'BOTTOM_RIGHT' => __( 'Bottom Right', 'extended-google-map-for-elementor' ),
				],
				'default' => 'RIGHT_BOTTOM',
				'condition' => [
					'zoom_control' => 'yes',
				],
				'separator' => false,
			]
		);

		$this->add_control(
			'default_ui',
			[
				'label' => __( 'Show Default UI', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes'
			]
		);

		$this->add_control(
			'map_type_control',
			[
				'label' => __( 'Map Type Control', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes'
			]
		);

		$this->add_control(
			'map_type_control_style',
			[
				'label' => __( 'Control Styles', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'DEFAULT' => __( 'Default', 'extended-google-map-for-elementor' ),
					'HORIZONTAL_BAR' => __( 'Horizontal Bar', 'extended-google-map-for-elementor' ),
					'DROPDOWN_MENU' => __( 'Dropdown Menu', 'extended-google-map-for-elementor' ),
				],
				'default' => 'DEFAULT',
				'condition' => [
					'map_type_control' => 'yes',
				],
				'separator' => false,
			]
		);

		$this->add_control(
			'map_type_control_position',
			[
				'label' => __( 'Control Position', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'TOP_LEFT' => __( 'Top Left (Default)', 'extended-google-map-for-elementor' ),
					'TOP_CENTER' => __( 'Top Center', 'extended-google-map-for-elementor' ),
					'TOP_RIGHT' => __( 'Top Right', 'extended-google-map-for-elementor' ),
					'LEFT_CENTER' => __( 'Left Center', 'extended-google-map-for-elementor' ),
					'RIGHT_CENTER' => __( 'Right Center', 'extended-google-map-for-elementor' ),
					'BOTTOM_LEFT' => __( 'Bottom Left', 'extended-google-map-for-elementor' ),
					'BOTTOM_CENTER' => __( 'Bottom Center', 'extended-google-map-for-elementor' ),
					'BOTTOM_RIGHT' => __( 'Bottom Right', 'extended-google-map-for-elementor' ),
				],
				'default' => 'TOP_LEFT',
				'condition' => [
					'map_type_control' => 'yes',
				],
				'separator' => false,
			]
		);

		$this->add_control(
			'streetview_control',
			[
				'label' => __( 'Show Streetview Control', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'no'
			]
		);

		$this->add_control(
			'streetview_control_position',
			[
				'label' => __( 'Streetview Position', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'RIGHT_BOTTOM' => __( 'Bottom Right (Default)', 'extended-google-map-for-elementor' ),
					'TOP_LEFT' => __( 'Top Left', 'extended-google-map-for-elementor' ),
					'TOP_CENTER' => __( 'Top Center', 'extended-google-map-for-elementor' ),
					'TOP_RIGHT' => __( 'Top Right', 'extended-google-map-for-elementor' ),
					'LEFT_CENTER' => __( 'Left Center', 'extended-google-map-for-elementor' ),
					'RIGHT_CENTER' => __( 'Right Center', 'extended-google-map-for-elementor' ),
					'BOTTOM_LEFT' => __( 'Bottom Left', 'extended-google-map-for-elementor' ),
					'BOTTOM_CENTER' => __( 'Bottom Center', 'extended-google-map-for-elementor' ),
					'BOTTOM_RIGHT' => __( 'Bottom Right', 'extended-google-map-for-elementor' ),
				],
				'default' => 'RIGHT_BOTTOM',
				'condition' => [
					'streetview_control' => 'yes',
				],
				'separator' => false,
			]
		);

		$this->add_control(
			'custom_map_style',
			[
				'label' => __( 'Custom Map Style', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::TEXTAREA,
				'description' => __('Add style from <a href="https://mapstyle.withgoogle.com/" target="_blank">Google Map Styling Wizard</a> or <a href="https://snazzymaps.com/explore" target="_blank">Snazzy Maps</a>. Copy and Paste the style in the textarea.'),
				'condition' => [
					'map_type' => 'roadmap',
				],
			]
		);

		$this->add_control(
			'view',
			[
				'label' => __( 'View', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::HIDDEN,
				'default' => 'traditional',
			]
		);

		$this->end_controls_section();

		/*Pins Option*/
		$this->start_controls_section(
			'map_marker_pin',
			[
				'label' => __( 'Marker Pins', 'extended-google-map-for-elementor' ),
			]
		);

		$this->add_control(
			'infowindow_max_width',
			[
				'label' => __( 'InfoWindow Max Width', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => '250',
				'default' => '250',
			]
		);

		$this->add_control(
			'tabs',
			[
				'label' => __( 'Pin Item', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::REPEATER,
				'default' => [
					[
						'pin_title' => __( 'Pin #1', 'extended-google-map-for-elementor' ),
						'pin_notice' => __( 'Find Latitude & Longitude', 'extended-google-map-for-elementor' ),
						'pin_lat' => __( '1.2833754', 'extended-google-map-for-elementor' ),
						'pin_lng' => __( '103.86072639999998', 'extended-google-map-for-elementor' ),
						'pin_content' => __( 'I am item content. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'extended-google-map-for-elementor' ),
					],
				], 
				'fields' => [
				    [
				    	'name' => 'pin_notice',
				    	'label' => __( 'Find Latitude & Longitude', 'extended-google-map-for-elementor' ),
					    'type'  => Controls_Manager::RAW_HTML,
					    'raw'   => '<form onsubmit="ebMapFindPinAddress(this);" action="javascript:void(0);"><input type="text" id="eb-map-find-address" class="eb-map-find-address" style="margin-top:10px; margin-bottom:10px;"><input type="submit" value="Search" class="elementor-button elementor-button-default" onclick="ebMapFindPinAddress(this)"></form><div id="eb-output-result" class="eb-output-result" style="margin-top:10px; line-height: 1.3; font-size: 12px;"></div>',
					    'label_block' => true,
				    ],
					[
						'name' => 'pin_lat',
						'label' => __( 'Latitude', 'extended-google-map-for-elementor' ),
						'type' => Controls_Manager::TEXT,
						'dynamic' => [ 'active' => true ]
					],
					[
						'name' => 'pin_lng',
						'label' => __( 'Longitude', 'extended-google-map-for-elementor' ),
						'type' => Controls_Manager::TEXT,
						'dynamic' => [ 'active' => true ]
					],
					[
						'name' => 'pin_icon',
						'label' => __( 'Marker Icon', 'extended-google-map-for-elementor' ),
						'type' => Controls_Manager::SELECT,
						'options' => [
							'' => __( 'Default (Google)', 'extended-google-map-for-elementor' ),
							'red' => __( 'Red', 'extended-google-map-for-elementor' ),
							'blue' => __( 'Blue', 'extended-google-map-for-elementor' ),
							'yellow' => __( 'Yellow', 'extended-google-map-for-elementor' ),
							'purple' => __( 'Purple', 'extended-google-map-for-elementor' ),
							'green' => __( 'Green', 'extended-google-map-for-elementor' ),
							'orange' => __( 'Orange', 'extended-google-map-for-elementor' ),
							'grey' => __( 'Grey', 'extended-google-map-for-elementor' ),
							'white' => __( 'White', 'extended-google-map-for-elementor' ),
							'black' => __( 'Black', 'extended-google-map-for-elementor' ),
						],
						'default' => '',
					],
					[
						'name' => 'pin_title',
						'label' => __( 'Title', 'extended-google-map-for-elementor' ),
						'type' => Controls_Manager::TEXT,
						'default' => __( 'Pin Title' , 'extended-google-map-for-elementor' ),
						'label_block' => true,
						'dynamic' => [ 'active' => true ]
					],
					[
						'name' => 'pin_content',
						'label' => __( 'Content', 'extended-google-map-for-elementor' ),
						'type' => Controls_Manager::WYSIWYG,
						'default' => __( 'Pin Content', 'extended-google-map-for-elementor' ),
						'dynamic' => [ 'active' => true ]
					],
				],
				'title_field' => '{{{ pin_title }}}',
			]
		);

		$this->end_controls_section();

		/*Main Style*/
		$this->start_controls_section(
			'section_main_style',
			[
				'label' => __( 'Pin Global Styles', 'extended-google-map-for-elementor' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pin_header_color',
			[
				'label' => __( 'Title Color', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eb-map-container h6' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'label' => __( 'Title Typography', 'extended-google-map-for-elementor' ),
				'name' => 'pin_header_typography',
				'scheme' => Scheme_Typography::TYPOGRAPHY_1,
				'selector' => '{{WRAPPER}} .eb-map-container h6',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'label' => __( 'Title Text Shadow', 'extended-google-map-for-elementor' ),
				'name' => 'pin_header_text_shadow',
				'selector' => '{{WRAPPER}} .eb-map-container h6',
				'separator' => true,
			]
		);


		$this->add_control(
			'pin_content_color',
			[
				'label' => __( 'Content Color', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eb-map-container .eb-map-content' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'label' => __( 'Content Typography', 'extended-google-map-for-elementor' ),
				'name' => 'pin_content_typography',
				'scheme' => Scheme_Typography::TYPOGRAPHY_1,
				'selector' => '{{WRAPPER}} .eb-map-container .eb-map-content',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'label' => __( 'Content Text Shadow', 'extended-google-map-for-elementor' ),
				'name' => 'pin_content_text_shadow',
				'selector' => '{{WRAPPER}} .eb-map-container .eb-map-content',
				'separator' => true,
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label' => __( 'Alignment', 'extended-google-map-for-elementor' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'extended-google-map-for-elementor' ),
						'icon' => 'fa fa-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'extended-google-map-for-elementor' ),
						'icon' => 'fa fa-align-center',
					],
					'right' => [
						'title' => __( 'Right', 'extended-google-map-for-elementor' ),
						'icon' => 'fa fa-align-right',
					],
					'justify' => [
						'title' => __( 'Justified', 'extended-google-map-for-elementor' ),
						'icon' => 'fa fa-align-justify',
					],
				],
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .eb-map-container' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render google maps widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function _content_template() {
	}

	/**
	 * Render google maps widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$eb_map_styles = $settings['custom_map_style'];
		$eb_replace_code_content = strip_tags($eb_map_styles);
        $eb_new_replace_code_content = preg_replace('/\s/', '', $eb_replace_code_content);

		if ( 0 === absint( $settings['zoom']['size'] ) ) {
			$settings['zoom']['size'] = 10;
		}

		$this->add_render_attribute('extended-google-map-for-elementor', 'data-eb-map-style', $eb_new_replace_code_content);

		$mapmarkers = array();

		foreach ( $settings['tabs'] as $index => $item ) :
			$mapmarkers[] = array(
				'lat' => $item['pin_lat'],
				'lng' => $item['pin_lng'],
				'title' => htmlspecialchars($item['pin_title'], ENT_QUOTES & ~ENT_COMPAT ),
				'content' => htmlspecialchars($item['pin_content'], ENT_QUOTES & ~ENT_COMPAT ),
				'pin_icon' => $item['pin_icon']
			);
		endforeach; 

		?>
		<div id="eb-map-<?php echo esc_attr($this->get_id()); ?>" class="eb-map" data-eb-map-gesture-handling="<?php echo $settings['gesture_handling'] ?>" <?php if ( 'yes' == $settings['zoom_control'] ) { ?> data-eb-map-zoom-control="true" data-eb-map-zoom-control-position="<?php echo $settings['zoom_control_position']; ?>" <?php } else { ?> data-eb-map-zoom-control="false"<?php } ?> data-eb-map-defaultui="<?php if ( 'yes' == $settings['default_ui'] ) { ?>false<?php } else { ?>true<?php } ?>" <?php echo $this->get_render_attribute_string('extended-google-map-for-elementor'); ?> data-eb-map-type="<?php echo $settings['map_type']; ?>" <?php if ( 'yes' == $settings['map_type_control'] ) { ?> data-eb-map-type-control="true" data-eb-map-type-control-style="<?php echo $settings['map_type_control_style']; ?>" data-eb-map-type-control-position="<?php echo $settings['map_type_control_position']; ?>"<?php } else { ?> data-eb-map-type-control="false"<?php } ?> <?php if ( 'yes' == $settings['streetview_control'] ) { ?> data-eb-map-streetview-control="true" data-eb-map-streetview-position="<?php echo $settings['streetview_control_position']; ?>"<?php } else {?> data-eb-map-streetview-control="false"<?php } ?> data-eb-map-lat="<?php echo $settings['map_lat']; ?>" data-eb-map-lng="<?php echo $settings['map_lng']; ?>" data-eb-map-zoom="<?php echo $settings['zoom']['size']; ?>" data-eb-map-infowindow-width="<?php echo $settings['infowindow_max_width']; ?>" data-eb-locations='<?php echo json_encode($mapmarkers);?>'></div>

	<?php }
}
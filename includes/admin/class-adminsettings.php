<?php
/**
 * MDPoetry Admin: The admin settings for the plugin.
 *
 * @package MDPoetry
 */

namespace MDPoetry\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a settings page for the plugin.
 *
 * This includes displaying admin messages if required.
 *
 * @since 0.0.1
 */
class AdminSettings {

	/**
	 * Menu slug for the options page.
	 *
	 * @since 0.0.1
	 * @var string
	 */
	private $menu_slug;

	/**
	 * Setting name for the DB option.
	 *
	 * @since 0.0.1
	 * @var string
	 */
	private $settings_name;

	/**
	 * Array of settings details.
	 *
	 * @since 0.0.1
	 * @var array
	 */
	private $settings;

	/**
	 * Contains any messages sent.
	 *
	 * @since 0.0.1
	 * @var string
	 */
	protected $message_text = '';

	/**
	 * Construct the setting class.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->menu_slug = MDP_PLUGIN_SHORTNAME . '_settings';
		$this->settings_name = MDP_PLUGIN_SHORTNAME . '_settings';
		// See this function if you need to change the available settings.
		$this->set_settings_details();

		// Add actions.
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
	}

	/**
	 * Initialises the settings details.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function admin_init() {
		// Call any other code.
		$this->register_settings();
	}

	/**
	 * Adds the settings page to the menu.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function add_options_page() {
		$page_id = add_options_page(
			MDP_PLUGIN_NAME . ' Settings',
			MDP_PLUGIN_NAME,
			'manage_options',
			$this->menu_slug,
			array( $this, 'render_page' )
		);

		// Add an action on load to deal with any messages set.
		add_action( "load-$page_id", array( $this, 'parse_message' ) );
	}

	/**
	 * Renders the settings page.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function render_page() {
		$options_name = $this->settings_name;
		$page_name = $this->menu_slug;
		include dirname( __FILE__ ) . '/views/admin-settings.php';
	}

	/**
	 * Parses any message added on page load.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function parse_message() {
		if ( ! isset( $_GET['msg'] ) ) {
			return;
		}
		$received = sanitize_text_field( wp_unslash( $_GET['msg'] ) );
		$message_text = false;
		// This allows us to make the messages more user-friendly.
		switch ( $received ) {
			default:
				// If we haven't set up an alternative, just display.
				$message_text = $received;
		}
		if ( $message_text ) {
			// Escape the message.
			$this->message_text = $message_text;
			add_action( 'admin_notices', array( $this, 'render_message' ) );
		}
	}

	/**
	 * Renders any admin message.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function render_message() {
		echo '<div class="updated"><p>'
			. esc_html( $this->message_text ) . '</p></div>';
	}

	/**
	 * Renders a settings field.
	 *
	 * The default here is to always render a text field.
	 *
	 * @param array $args The arguments passed when add_settings_field called.
	 * @return void       This needs to echo the HTML output.
	 */
	public function render_field( array $args ) {
		$option_name = $args['option_name'];
		$field_type = $args['type'];
		$default_value = isset( $args['default_value'] ) ? $args['default_value'] : '';

		switch ( $field_type ) {
			case 'checkbox':
				$this->setting_checkbox_value( $option_name, $default_value );
				break;
			case 'text':
			default:
				$this->setting_text_value( $option_name, $default_value );
		}
	}

	/**
	 * Catch any calls to display section text.
	 *
	 * @since 0.0.1
	 * @throws BadMethodCallException If not a recognised function.
	 * @param  string $name Name of the function being called.
	 * @param  array  $args  Arguments being passed to the function.
	 * @return void
	 */
	public function __call( $name, $args ) {
		if ( 0 < strpos( $name, '_section_text' ) ) {
			$section_name = str_replace( '_section_text', '', $name );
			$this->display_section_text( $section_name );
		} else {
			throw new BadMethodCallException( 'Method [' . $name . '] does not exist.' );
		}
	}

	/**
	 * Sets the database options to match default if necessary.
	 *
	 * @since 0.0.2
	 * @return bool  True if update successful, false otherwise.
	 */
	public function update_default_options() {
		$options = get_option( $this->setting_name, array() );
		$settings = $this->settings;
		foreach ( $settings as $section_name => $section ) {
			foreach ( $section['settings'] as $setting_name => $setting ) {
				if ( ! isset( $options[ $setting_name ] ) ) {
					$default_value = isset( $setting['default_value'] ) ? $setting['default_value'] : '';
					$options[ $setting_name ] = $default_value;
				}
			}
		}
		return update_option( $this->setting_name, $options );
	}

	/**
	 * This sets up all of settings for the page.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private function register_settings() {
		// We set the option group and option name to the same thing here.
		// Also, everything gets saved under a single options entry.
		register_setting( $this->settings_name, $this->settings_name );
		$settings = $this->settings;

		foreach ( $settings as $section_name => $section ) {
			// Add the section.
			add_settings_section(
				$section_name,
				$section['title'],
				array( $this, $section_name . '_section_text' ),
				$this->menu_slug
			);
			foreach ( $section['settings'] as $setting_name => $setting ) {
				// Then add the fields to this section. Pass the setting name in the final args array.
				add_settings_field(
					$setting_name,
					$setting['title'],
					array( $this, 'render_field' ),
					$this->settings_name,
					$section_name,
					array(
						'option_name' => $setting_name,
						'type'        => $setting['type'],
					)
				);
			}
		}
	}

	/**
	 * Displays the section text at the top of any section.
	 *
	 * Note that the text to display needs to be added to the settings array.
	 *
	 * @since 0.0.1
	 * @param  string $section_name The name of the section being displayed.
	 * @return void
	 */
	private function display_section_text( $section_name ) {
		$settings = $this->settings;
		if ( ! isset( $settings[ $section_name ] ) ) {
			return;
		}
		echo $settings[ $section_name ]['section_text']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Set up a text settings field.
	 *
	 * @since 0.0.1
	 * @param  string  $option_name  Name of the option.
	 * @param  integer $field_length How long should the text field be.
	 * @return void
	 */
	private function setting_text_value( $option_name, $field_length = 50 ) {
		$options = get_option( $this->settings_name );
		$value = ! empty( $options[ $option_name ] ) ? $options[ $option_name ] : '';
		$field_name = $this->settings_name . '[' . $option_name . ']';
		echo '<input type="text" size="' . $field_length . '" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '"/>'; // phpcs:ignore
	}

	/**
	 * Set up a checkbox setting field.
	 *
	 * This is a standalone checkbox, not a group.
	 *
	 * @since 0.0.2
	 * @param  string $option_name Name of the option.
	 * @param  string $default_value Default value for this field.
	 * @return void
	 */
	private function setting_checkbox_value( $option_name, $default_value ) {
		$options = get_option( $this->settings_name );
		$value = ! empty( $options[ $option_name ] ) ? $options[ $option_name ] : $default_value;
		$field_name = $this->settings_name . '[' . $option_name . ']';
		$checked = checked( 1, $value, false );
		echo '<input type="checkbox" id="' . $field_name . '" name="'. $field_name . '" value="1" ' . $checked . '>'; // phpcs:ignore
	}

	/**
	 * Set up a number settings field.
	 *
	 * @since 0.0.3
	 * @param string $option_name   Name of the option.
	 * @param string $default_value Default string.
	 * @param string $description   Help text.
	 * @param array  $args          Field specific args.
	 * @return void
	 */
	protected function setting_number_value( $option_name, $default_value, $description, $args ) {
		$step = ! empty( $args['step'] ) && is_numeric( $arg['step'] ) ? $args['step'] : 1;

		$options = $this->get_current_options();
		$value = ! empty( $options[ $option_name ] ) ? $options[ $option_name ] : $default_value;
		$field_name = $this->settings_name . '[' . $option_name . ']';
		echo '<input type="number" step="' . $step . '" min="0" id="' . $field_name . '" name="' . $field_name . '" value="' . $value . '"/>'; // phpcs:ignore

		if ( ! empty( $description ) ) {
			echo '<p class="description" id="' . $field_name . '-description">' . $description .'</p>'; // phpcs:ignore
		}
	}

	/**
	 * Provides a single place to change the settings for the plugin.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private function set_settings_details() {
		$settings         = array();
		$settings['main'] = array(
			'section_name' => $this->settings_name . '_main',
			'title'        => MDP_PLUGIN_NAME . ' Main',
			'section_text' => '<p>Main section for settings</p>',
			'settings'     => array(
				'first_field' => array(
					'title'         => 'First Setting Field',
					'type'          => 'text',
					'default_value' => '',
				),
			),
		);
		$this->settings   = $settings;
	}
}

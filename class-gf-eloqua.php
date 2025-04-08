<?php

// don't load directly
if (! defined('ABSPATH')) {
  die();
}

GFForms::include_addon_framework();

/**
 * Class GF_Eloqua
 *
 * Eloqua Add-On class.
 */
class GF_Eloqua extends GFAddOn
{

  /**
   * Contains an instance of this class, if available.
   *
   * @since  0.0.1
   * @access private
   * @var    GF_Eloqua $_instance If available, contains an instance of this class.
   */
  private static $_instance = null;

  /**
   * Eloqua Add-On Version
   *
   * @var string
   */
  protected $_version = GF_ELOQUA_VERSION;

  /**
   * Minimum Gravity Forms version required for this Add-On to work.
   *
   * @var string
   */
  protected $_min_gravityforms_version = '2.5.0';

  /**
   * Eloqua Add-On Slug
   *
   * @var string
   */
  protected $_slug = 'gravityforms-eloqua';

  /**
   * Eloqua Add-On Path
   *
   * @var string
   */
  protected $_path = 'gravityforms-eloqua/eloqua.php';

  /**
   * Eloqua file full path
   *
   * @var string
   */
  protected $_full_path = __FILE__;

  /**
   * Eloqua Add-On URL
   *
   * @var string
   */
  protected $_title = 'Gravity Forms Eloqua Add-On';

  /**
   * Eloqua Add-On Short Title
   *
   * @var string
   */
  protected $_short_title = 'Eloqua';

  /**
   * Eloqua i18n domain
   *
   * @var string
   */
  private static $_domain = 'gravityformseloqua';

  /**
   * Eloqua default language
   *
   * @var string
   */
  private static $_default_language = 'fr';

  /**
   * Eloqua languages references
   *
   * @var array
   */
  private static $_languages = array(
    'fr' => 'LANGUE_A',
    'en' => 'LANGUE_B'
  );

  /**
   * Get an instance of this class.
   *
   * @since  0.0.1
   * @access public
   *
   * @return GF_Eloqua
   */
  public static function get_instance()
  {
    if (null === self::$_instance) {
      self::$_instance = new self;
    }
    return self::$_instance;
  }

  /**
   * Eloqua init_admin
   *
   * @since  0.0.1
   * @access public
   *
   * return void
   */
  public function init_admin()
  {
    parent::init_admin();
    add_action('gform_field_standard_settings', array($this, 'gform_field_standard_settings'), 10, 2);
    add_action('gform_editor_js', array($this, 'gform_editor_js'));
    add_filter('gform_after_submission', array($this, 'gform_after_submission'), 10, 2);
  }

  /**
   * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
   *
   * @return array
   */
  public function form_settings_fields($form)
  {
    return array(
      array(
        'title'  => esc_html__('Eloqua form data settings', self::$_domain),
        'fields' => array(
          [
            'name' => 'enabled',
            'type' => 'checkbox',
            'label' => __('Submit data to Eloqua API ?', self::$_domain),
            'tooltip' => __('Determine if the Eloqua Add-On is enabled for this form.', self::$_domain),
            'choices' => array(
              array(
                'label' => esc_html__('Enabled', self::$_domain),
                'name'  => 'eloqua_enabled',
              ),
            ),
          ],
          [
            'name' => 'eloqua_api_url',
            'icon' => 'gform-icon--choice',
            'type' => 'text',
            'label' => __('Eloqua API URL', self::$_domain),
            'tooltip' => __('Eloqua API Endpoint URL, example : https://tracking.info.xxx.com/e/f2?LP=XXX', self::$_domain),
            'required' => false,
          ],
          [
            'name' => 'eloqua_site_id',
            'type' => 'text',
            'label' => __('Eloqua Site ID', self::$_domain),
            'tooltip' => __('Eloqua Site ID, example : 956780691', self::$_domain),
            'required' => false,
          ],
          [
            'name' => 'eloqua_campaign_id',
            'type' => 'text',
            'label' => __('Eloqua Campaign ID', self::$_domain),
            'tooltip' => __('Eloqua Campaign ID, example : fall-2025', self::$_domain),
            'required' => false,
          ],
          [
            'name' => 'eloqua_form_name',
            'type' => 'text',
            'label' => __('Eloqua Form Name', self::$_domain),
            'tooltip' => __('Eloqua Form Name, example: CLIENT-webinars-IDXXX', self::$_domain),
            'required' => false,
          ]
        )
      )
    );
  }

  /**
   * Return the plugin's icon for the plugin/form settings menu.
   *
   * @since 0.0.1
   *
   * @return string
   */
  public function get_menu_icon()
  {
    return $this->is_gravityforms_supported($this->_min_gravityforms_version) ? 'gform-icon--api' : 'dashicons-admin-generic';
  }

  /**
   * Register needed styles.
   *
   * @since  0.0.1
   * @access public
   *
   * @return void
   */
  public function styles()
  {
    $styles = array(
      array(
        'handle'  => 'gravityforms_eloqua_form_settings',
        'src'     => $this->get_base_url() . "/css/form_settings.css",
        'version' => $this->_version,
        'enqueue' => array(
          array(
            'admin_page' => array('plugin_settings', 'form_settings'),
            'tab'        => $this->_slug,
          ),
        ),
      ),
    );

    return array_merge(parent::styles(), $styles);
  }

  /**
   * Register needed scripts.
   *
   * @since  0.0.1
   * @access public
   *
   * @return void
   */
  public function scripts() {
    $scripts = array(
      array(
        'handle'  => 'gravityforms_eloqua_form_settings',
        'src'     => $this->get_base_url() . "/js/eloqua-gf-settings.js",
        'version' => $this->_version,
        'enqueue' => array(
          array(
            'admin_page' => array('plugin_settings', 'form_settings'),
            'tab'        => $this->_slug,
          ),
        ),
      ),
    );

    return array_merge(parent::scripts(), $scripts);
  }

  /**
   * Add settings to the field settings.
   *
   * @since  0.0.1
   * @access public
   *
   * @return string
   */
  public function gform_field_standard_settings($position, $form_id = 0)
  {
    // if the form is not enabled, return
    if ($this->is_enabled($form_id) == false) return;

    $label = esc_html__('Eloqua Field', self::$_domain);
    $aria_label = esc_html__('Copy form ID here to map the field in Eloqua.', self::$_domain);

    if ($position == 25) {
      echo <<<HTML
        <li class="eloqua-field field_setting">
          <label for="eloqua_field_value" class="section_label">
            {$label}
            <button onclick="return false;" onkeypress="return false;"
              class="gf_tooltip tooltip tooltip_eloqua_field_tooltip"
              aria-label="{$aria_label}">
              <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
            </button>
          </label>
          <input type="text" id="eloqua_field_value" onblur="SetFieldProperty('eloquaField', this.value);">
        </li>
      HTML;
    }
  }

  /**
   * Add fieldSettings to jQuery event hook 'gform_load_field_settings'
   *
   * @since  0.0.1
   * @access public
   *
   * @return string
   */
  public function gform_editor_js()
  {
    // if the form is not enabled, return
    if ($this->is_enabled( rgget('id') ) == false) return;

    echo <<<HTML
      <script type='text/javascript'>
        fieldSettings.text += ', .eloqua-field';
        fieldSettings.select += ', .eloqua-field';
        fieldSettings.address += ', .eloqua-field';
        fieldSettings.phone += ', .eloqua-field';
        fieldSettings.radio += ', .eloqua-field';
        fieldSettings.textarea += ', .eloqua-field';
        fieldSettings.email += ', .eloqua-field';
        fieldSettings.checkbox += ', .eloqua-field';
        fieldSettings.hidden += ', .eloqua-field';
        jQuery(document).on('gform_load_field_settings', function(event, field, form) {
          jQuery('#eloqua_field_value').prop('value', rgar(field, 'eloquaField'));
        });
      </script>
    HTML;
  }

  /**
   * After submission, send data to Eloqua API
   *
   * @param array $entry
   * @param array $form
   *
   * @return void
   */
  public function gform_after_submission($entry, $form)
  {
    if ($this->is_enabled($form['id']) == false) return;

    $settings = $this->get_form_settings($form);

    // log to debug information
    GFCommon::log_debug('gform_after_submission: eloqua_api_url => ' . $settings['eloqua_api_url']);
    GFCommon::log_debug('gform_after_submission: eloqua_site_id => ' . $settings['eloqua_site_id']);
    GFCommon::log_debug('gform_after_submission: eloqua_campaign_id => ' . $settings['eloqua_campaign_id']);
    GFCommon::log_debug('gform_after_submission: eloqua_form_name => ' . $settings['eloqua_form_name']);

    // grab Eloqua API URL from form settings
    $endpoint_url = $settings['eloqua_api_url'];

    if (empty($endpoint_url)) {
        GFCommon::log_debug('gform_after_submission: Eloqua API URL is empty, cannot send data');
        return;
    }

    // set the body data basic information
    $body_data = array(
        'elqFormName' => $settings['eloqua_form_name'],
        'elqSiteId' => $settings['eloqua_site_id'],
        'varsLangue' => self::$_languages[$this->get_current_language()],
        'elq_gf_input_lang' => self::$_languages[$this->get_current_language()]
    );

    foreach ($form['fields'] as $field) {
        $inputs = $field->get_entry_inputs();
        if (is_array($inputs)) {
            foreach ($inputs as $input) {
                $value = rgar($entry, (string)$input['id']);
                if (isset($field['eloquaField']) && $value) {
                    $body_data[$field['eloquaField']] = $value;
                }
            }
        } else {
            $value = rgar($entry, (string)$field->id);
            if (isset($field['eloquaField']) && $value) {
                $body_data[$field['eloquaField']] = $value;
            }
        };
    }

    // set the body data
    $options = [
        'body' => $body_data,
        'headers' => [
            'cache-control' => 'no-cache',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'connection' => 'keep-alive',
            'pragma' => 'no-cache',
        ],
        'cookie' => 'ELOQUA=GUID%3D4E219A49767D40CD9F68B7E9FEBC2350; ELQSTATUS=OK'
    ];

    GFCommon::log_debug('gform_after_submission: body => ' . print_r($body_data, true));

    $response = wp_remote_post($endpoint_url, $options);

    GFCommon::log_debug('gform_after_submission: response => ' . print_r($response, true));
  }

  /**
   * Check if Eloqua is enabled for the form
   *
   * @param int $form_id
   *
   * @return boolean
   */
  private function is_enabled($form_id)
  {
    $form = GFAPI::get_form($form_id);

    // get form settings
    $settings = $this->get_form_settings($form);

    // return if not enabled
    if (empty($settings['eloqua_enabled']) || $settings['eloqua_enabled'] === '0') {
      return false;
    }

    return true;
  }

  /**
   * get current language from Polylang
   *
   * @return string
   */
  private function get_current_language() {
    if( function_exists('pll_current_language') ) {
        return pll_current_language();
    } else {
        return self::$_default_language;
    }
  }

}

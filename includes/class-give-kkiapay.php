<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

class Kkiapay_Give
{
  /**
   * API public key
   *
   * @var string
   */
  private $public_key;

  /**
   * API private key
   *
   * @var string
   */
  private $private_key;

  /**
   * API secret key
   *
   * @var string
   */
  private $secret;

  /**
   * Is test mode active?
   *
   * @var bool
   */
  private $testmode;

  /**
   * KKiapay unique instance?
   *
   * @var KkiapayGateway
   */
  private $kkiapay;

  public function __construct()
  {
    $this->public_key         = give_get_option('public_key_kkiapay');
    $this->private_key        = give_get_option('private_key_kkiapay');
    $this->secret             = give_get_option('secret_key_kkiapay');
    $this->testmode           = give_is_test_mode();



    add_filter('give_payment_gateways', array($this, 'register_gateway'));
    add_action('give_gateway_kkiapay', array($this, 'pay'));
    add_action('give_kkiapay_cc_form', array($this, 'generate_form'));
    add_filter('give_currencies', array($this, 'give_kkiapay_currency'));


    $this->import_kkiapay();

    if (is_admin()) {
      add_filter('give_get_sections_gateways', array($this, 'register_sections'));
      add_filter('give_get_settings_gateways', array($this, 'register'));
      add_action('give_admin_field_my_custom_subtitle', 'my_custom_subtitle', 10, 5);
    }

    Give_Scripts::register_script('give-kkiapay-checkout-js',  plugins_url('../includes/assets/js/kkiapay.js', __FILE__));

    $kkiapay_vars = [
      'position' => give_get_option('position_kkiapay'),
      'paymentmethod' => give_get_option('payment_method_kkiapay'),
      'theme' => give_get_option('theme_kkiapay'),
      'key' => give_get_option('public_key_kkiapay'),
      'sandbox' => give_is_test_mode() ? 'true' : 'false'
    ];

    Give_Scripts::register_script('give-kkiapay-popup-js', plugins_url('../includes/assets/js/kkiapay.js', __FILE__));
    wp_enqueue_script('give-kkiapay-popup-js');
    wp_localize_script('give-kkiapay-popup-js', 'give_kkiapay_vars', $kkiapay_vars);
  }



  public function import_kkiapay()
  {
    require_once  __DIR__ . '/class-kkiapay-gateway.php';
    $this->kkiapay = new KkiapayGateway($this->public_key, $this->private_key, $this->secret, $this->testmode);
  }

  public function register_gateway($gateways)
  {
    $gateways['kkiapay'] = array(
      'admin_label'    =>  'Kkiapay',
      'checkout_label' => 'Kkiapay'
    );
    return $gateways;
  }



  public function pay($purchase_data)
  {

    try {
      $payment_data = array(
        'price'           => $purchase_data['price'],
        'give_form_title' => $purchase_data['post_data']['give-form-title'],
        'give_form_id'    => intval($purchase_data['post_data']['give-form-id']),
        'give_price_id'   => isset($purchase_data['post_data']['give-price-id']) ? $purchase_data['post_data']['give-price-id'] : '',
        'date'            => $purchase_data['date'],
        'user_email'      => $purchase_data['user_email'],
        'purchase_key'    => $purchase_data['purchase_key'],
        'currency'        => give_get_currency(),
        'user_info'       => $purchase_data['user_info'],
        'phone'       => $purchase_data['phone'],
        'status'          => 'pending',
        'gateway'         => 'kkiapay'
      );

      // record the pending payment
      $payment = give_insert_payment($payment_data);

      //verify payment status
      $response = $this->kkiapay->verifyTransaction($_POST['give_kkiapay_transaction_id']);



      if ($response->status === STATUS::SUCCESS) {
        give_update_payment_status($payment, 'publish');
        give_set_payment_transaction_id($payment, $response->transactionId);
        give_insert_payment_note($payment, 'Kkiapay');
        give_send_to_success_page();
      } else {
        give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);
        give_set_error('api_error', 'Impossible de contacter le serveur');
      }
    } catch (\Throwable $th) {
      give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);
      give_set_error('api_error', 'Impossible de contacter le serveur');
      //throw $th;
    }
  }


  function give_kkiapay_currency($currencies)
  {
    // $currencies['XOF'] = 'Fcfa';
    $currencies['XOF'] = [
      'admin_label' => 'Fcfa',
      "symbol" => 'XOF',
      "setting" => [
        'currency_position' => 'after',
        "thousands_separator" => " ",
        "decimal_separator" => ",",
        "number_decimals" => 0
      ]
    ];
    return $currencies;
  }



  public function register_sections($sections)
  {
    $sections['kkiapay-settings'] = 'Kkiapay';
    return $sections;
  }

  public function generate_form()
  {
    return null;
  }

  function plant_config_advanced_settings_display($field_options)
  {
    return null;
  }

  public function register($settings)
  {
    switch (give_get_current_setting_section()) {

      case 'kkiapay-settings':
        $settings = array(
          array(
            'id'   => 'give_title_kkiapay',
            'type' => 'title',
          ),
        );


        $settings[] = array(
          'title' => __('Clé publique', 'kkiapay-give'),
          'type' => 'password',
          'desc_tip'    => true,
          'id' => 'public_key_kkiapay',
          'description' => __("Veuillez specifiez votre clé d'api public en tenant en compte le fait que Give soit en mode Test ou non", 'kkiapay-give')
        );


        $settings[] = array(
          'title' => __('Clé Privée', 'kkiapay-give'),
          'type' => 'password',
          'desc_tip'    => true,
          'id' => 'private_key_kkiapay',
          'description' => __("Veuillez specifiez votre clé d'api privé en tenant en compte le fait que Give soit en mode Test ou non", 'kkiapay-give'),
        );

        $settings[] = array(
          'title' => __('Clé Secrete', 'kkiapay-give'),
          'type' => 'password',
          'desc_tip'    => true,
          'id' => 'secret_key_kkiapay',
          'description' => __("Veuillez specifiez votre clé d'api secrete en tenant en compte le fait que Give soit en mode Test ou non", 'kkiapay-give')
        );

        $settings[] = array(
          'title' => __('(Optionnel) Moyens de paiement', 'kkiapay-give'),
          'description' => __("Définissez les moyens de paiement que vous choisissez de prendre en charge.", 'kkiapay-give'),
          'type' => 'select',
          'default' => 'all',
          'desc_tip'    => true,
          'id' => 'payment_method_kkiapay',
          'options' => array(
            'all' => (__('Tout', 'kkiapay-give')),
            'momo' => (__('Mobile Money', 'kkiapay-give')),
            'card' => (__('Cartes Bancaires', 'kkiapay-give'))
          )
        );

        $settings[] = array(
          'title' => __('(Optionnel) Disposition du Widget Kkiapay', 'kkiapay-give'),
          'type' => 'select',
          'description' => __("Utilisez cette option pour contrôler l'endroit où la fenêtre Kkiapay devrait s'afficher sur votre site", 'kkiapay-give'),
          'default' => 'center',
          'desc_tip'    => true,
          'id' => 'position_kkiapay',
          'options' => array(
            'right' => (__('Du côté droit de la page', 'kkiapay-give')),
            'left' => (__('Du côté gauche de la page', 'kkiapay-give')),
            'center' => (__('Au centre de la page', 'kkiapay-give'))
          )
        );

        $settings[] = array(
          'row' => true,
          'title' => __('(Optionnel) Thème du widget', 'kkiapay-give'),
          'type' => 'text',
          'desc_tip'    => true,
          'id' => 'theme_kkiapay',
          'description' => __('Paramétrez la couleur de la fenêtre Kkiapay. Pour une meilleure ,harmonisation, utilisez la couleur dominante de votre site ou laissez vide (Recommandée). ', 'kkiapay-give')
        );

        $settings[] = array(
          'id'   => 'give_kkiapay',
          'type' => 'sectionend',
        );
        break;
    } // End switch().

    return $settings;
  }
}

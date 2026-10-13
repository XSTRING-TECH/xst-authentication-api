<?php
/**
 * Plugin Name: xString Tech Passwordless Authentication
 * Description: Adds the iRC Passwordless Authentication Method as an additional WordPress signup and login option without replacing WordPress's native authentication.
 * Version: 1.0.0
 * Author: XSTRING TECH PTY LTD
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class XString_Tech_Passwordless {

    const OPTION = 'irc_passwordless_settings';
    const STATE_PREFIX = 'xstring_pw_state_';
    const NONCE_ACTION = 'irc_passwordless_start';
    const CALLBACK_PATH = 'code-exchange';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        add_action('login_form', [$this, 'login_button']);
        add_action('login_footer', [$this, 'login_footer']);

        add_action('init', [$this, 'register_rewrite']);
        add_action('init', [$this, 'handle_start']);
        add_action('template_redirect', [$this, 'handle_callback']);

        add_shortcode('irc_passwordless', [$this, 'shortcode']);

        add_action('show_user_profile', [$this, 'profile_npk']);
        add_action('edit_user_profile', [$this, 'profile_npk']);

        register_activation_hook(
            __FILE__,
            [$this, 'activate']
        );

        register_deactivation_hook(
            __FILE__,
            [$this, 'deactivate']
        );
    }

    /*
     * -------------------------------------------------------------------------
     * Settings
     * -------------------------------------------------------------------------
     */

    private function settings() {
        $defaults = [
            'client_npk' => '',
            'auth_scope' => '',
            'aas_base_url' => 'https://saas.xstring.tech',
            'access_key_id' => '',
            'secret_access_key' => '',
            'auth_code_url' => '',
            'landing_pg_uri' => '',
        ];

        $settings = get_option(
            self::OPTION,
            []
        );

        return wp_parse_args(
            is_array($settings) ? $settings : [],
            $defaults
        );
    }

    public function register_settings() {
        register_setting(
            'irc_passwordless',
            self::OPTION,
            [
                'sanitize_callback' => [
                    $this,
                    'sanitize_settings'
                ],
            ]
        );
    }

    public function sanitize_settings($input) {
        return [
            'client_npk' => sanitize_text_field(
                trim($input['client_npk'] ?? '')
            ),

            'auth_scope' => sanitize_text_field(
                trim($input['auth_scope'] ?? '')
            ),

            'aas_base_url' => esc_url_raw(
                trim($input['aas_base_url'] ?? '')
            ),

            'access_key_id' => sanitize_text_field(
                trim($input['access_key_id'] ?? '')
            ),

            'secret_access_key' => trim(
                $input['secret_access_key'] ?? ''
            ),

            'auth_code_url' => esc_url_raw(
                trim($input['auth_code_url'] ?? '')
            ),

            'landing_pg_uri' => esc_url_raw(
                trim($input['landing_pg_uri'] ?? '')
            ),
        ];
    }

    public function admin_menu() {
        add_options_page(
            'xString Passwordless',
            'xString Passwordless',
            'manage_options',
            'xstring-passwordless',
            [$this, 'settings_page']
        );
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $s = $this->settings();

        ?>

    <div class="wrap">

        <h1>xString Tech Passwordless Method</h1>

        <p>
            Adds xString Tech Passwordless Method as an additional
            authentication and signup option. WordPress's existing
            username/password authentication remains unchanged.
        </p>

        <form method="post" action="options.php">

            <?php settings_fields('irc_passwordless'); ?>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">
                        <label for="xstring_client_npk">
                            Client NPK
                        </label>
                    </th>

                    <td>
                        <input
                            class="regular-text"
                            type="text"
                            id="xstring_client_npk"
                            name="<?php echo esc_attr(self::OPTION); ?>[client_npk]"
                            value="<?php echo esc_attr($s['client_npk']); ?>"
                            autocomplete="off"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="xstring_auth_scope">
                            Auth Scope
                        </label>
                    </th>

                    <td>
                        <input
                            class="regular-text"
                            type="text"
                            id="xstring_auth_scope"
                            name="<?php echo esc_attr(self::OPTION); ?>[auth_scope]"
                            value="<?php echo esc_attr($s['auth_scope']); ?>"
                            autocomplete="off"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="xstring_aas_base_url">
                            AAS Base URL
                        </label>
                    </th>

                    <td>
                        <input
                            class="regular-text"
                            type="url"
                            id="xstring_aas_base_url"
                            name="<?php echo esc_attr(self::OPTION); ?>[aas_base_url]"
                            value="<?php echo esc_attr($s['aas_base_url']); ?>"
                        >

                        <p class="description">
                            Example:
                            https://saas.xstring.tech
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="xstring_access_key_id">
                            Access Key ID
                        </label>
                    </th>

                    <td>
                        <input
                            class="regular-text"
                            type="text"
                            id="xstring_access_key_id"
                            name="<?php echo esc_attr(self::OPTION); ?>[access_key_id]"
                            value="<?php echo esc_attr($s['access_key_id']); ?>"
                            autocomplete="off"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="xstring_secret_access_key">
                            Secret Access Key
                        </label>
                    </th>

                    <td>
                        <input
                            class="regular-text"
                            type="password"
                            id="xstring_secret_access_key"
                            name="<?php echo esc_attr(self::OPTION); ?>[secret_access_key]"
                            value="<?php echo esc_attr($s['secret_access_key']); ?>"
                            autocomplete="new-password"
                        >

                        <p class="description">
                            Store production credentials securely. This plugin
                            stores the value in WordPress options; a dedicated
                            secrets-management solution is preferable for
                            higher-security deployments.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="xstring_auth_code_url">
                            Auth Code URL
                        </label>
                    </th>

                    <td>
                        <input
                            class="regular-text"
                            type="url"
                            id="xstring_auth_code_url"
                            name="<?php echo esc_attr(self::OPTION); ?>[auth_code_url]"
                            value="<?php echo esc_attr($s['auth_code_url']); ?>"
                            placeholder="https://www.yourdomain.com/code-exchange"
                        >

                        <p class="description">
                            The URL on this WordPress site that receives
                            the xString authentication callback.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="xstring_landing_pg_uri">
                            Landing Page URL
                        </label>
                    </th>
            
                    <td>
                        <input
                            class="regular-text"
                            type="url"
                            id="xstring_landing_pg_uri"
                            name="<?php echo esc_attr(self::OPTION); ?>[landing_pg_uri]"
                            value="<?php echo esc_attr($s['landing_pg_uri']); ?>"
                            placeholder="https://www.yourdomain.com"
                        >
            
                        <p class="description">
                            The URL of the page users return to after
                            initiating authentication.
                        </p>
                    </td>
                </tr>

            </table>

            <?php submit_button('Save Settings'); ?>

        </form>

        <hr>

        <h2>Shortcode</h2>

        <p>
            Use
            <code>[irc_passwordless]</code>
            on any page to display the xString Tech Passwordless Method button.
        </p>

        <p>
            The standard WordPress login page also displays the button automatically.
        </p>

        <h2>Callback</h2>

        <p>
            The plugin handles the configured Auth Code URL directly.
            No redirect to the WordPress login URL is required.
        </p>

    </div>
    <?php
}

/*
 * -------------------------------------------------------------------------
 * WordPress /code-exchange endpoint
 * -------------------------------------------------------------------------
 */

public function activate() {
    $this->register_rewrite();

    flush_rewrite_rules();
}

public function deactivate() {
    flush_rewrite_rules();
}

public function register_rewrite() {
    add_rewrite_rule(
        '^' . self::CALLBACK_PATH . '/?$',
        'index.php?xstring_code_exchange=1',
        'top'
    );

    add_rewrite_tag(
        '%xstring_code_exchange%',
        '1'
    );
}

private function is_callback_request() {
    return get_query_var('xstring_code_exchange') === '1'
        || (
            isset($_GET['xstring_code_exchange']) &&
            $_GET['xstring_code_exchange'] === '1'
        );
}

private function callback_url() {
    return trailingslashit(
        home_url()
    ) . self::CALLBACK_PATH;
}

/*
 * -------------------------------------------------------------------------
 * Login button
 * -------------------------------------------------------------------------
 */

public function login_button() {
    if (is_user_logged_in()) {
        return;
    }

    $url = $this->start_url();

    ?>
    <p class="xstring-passwordless-login">

        <a
            class="button button-secondary"
            style="width:100%; text-align:center;"
            href="<?php echo esc_url($url); ?>"
        >
            Continue with iRC Authentication
        </a>

    </p>
    <?php
}

public function login_footer() {
    if (!isset($_GET['xstring_error'])) {
        return;
    }

    $message = sanitize_text_field(
        wp_unslash($_GET['xstring_error'])
    );

    if ($message === '') {
        return;
    }

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        var p = document.createElement('p');

        p.style.color = '#b32d2e';
        p.style.marginTop = '16px';

        p.textContent =
            <?php echo wp_json_encode($message); ?>;

        var form =
            document.getElementById('loginform');

        if (form) {
            form.parentNode.insertBefore(
                p,
                form.nextSibling
            );
        }

    });
    </script>
    <?php
}

/*
 * -------------------------------------------------------------------------
 * Shortcode
 * -------------------------------------------------------------------------
 */

public function shortcode() {
    if (is_user_logged_in()) {
        return '';
    }

    return sprintf(
        '<p><a class="button" href="%s">Continue with iRC Authentication</a></p>',
        esc_url($this->start_url())
    );
}

private function start_url() {
    return wp_nonce_url(
        add_query_arg(
            'xstring_start',
            '1',
            wp_login_url()
        ),
        self::NONCE_ACTION,
        '_xstring_nonce'
    );
}

/*
 * -------------------------------------------------------------------------
 * Start xString authentication
 * -------------------------------------------------------------------------
 */

public function handle_start() {
    if (!isset($_GET['xstring_start'])) {
        return;
    }

    $nonce = isset($_GET['_xstring_nonce'])
        ? sanitize_text_field(
            wp_unslash($_GET['_xstring_nonce'])
        )
        : '';

    if (!wp_verify_nonce(
        $nonce,
        self::NONCE_ACTION
    )) {
        $this->fail(
            'Unable to start xString authentication.'
        );
    }

    $s = $this->settings();

    if (       
        empty($s['client_npk']) ||
        empty($s['auth_scope']) ||
        empty($s['aas_base_url']) ||
        empty($s['access_key_id']) ||
        empty($s['secret_access_key']) ||
        empty($s['auth_code_url']) ||
        empty($s['landing_pg_uri'])
    ) {
        $this->fail(
            'xString Passwordless Method is not configured.'
        );
    }

    /*
     * Validate the Landing Page URL.
     */
    $landing_scheme = wp_parse_url(
        $s['landing_pg_uri'],
        PHP_URL_SCHEME
    );
    
    if (
        !wp_http_validate_url($s['landing_pg_uri']) ||
        strtolower($landing_scheme ?? '') !== 'https'
    ) {
        $this->fail(
            'The configured Landing Page URL must be a valid HTTPS URL.'
        );
    }

    /*
     * The Auth Code URL supplied by the site must match the
     * actual WordPress /code-exchange endpoint.
     */
    $expected_callback = $this->callback_url();

    if (
        untrailingslashit($s['auth_code_url']) !==
        untrailingslashit($expected_callback)
    ) {
        $this->fail(
            'The configured Auth Code URL must be ' .
            $expected_callback
        );
    }

    $state = wp_generate_uuid4();

    set_transient(
        self::STATE_PREFIX . hash(
            'sha256',
            $state
        ),
        [
            'created' => time(),
            'redirect_uri' => $expected_callback,
        ],
        10 * MINUTE_IN_SECONDS
    );

    $payload = [
        'state' => $state,
        'scope' => $s['auth_scope'],
        'response_type' => 'code',
        'redirect_uri' => $expected_callback,
        'landing_pg_uri' => $s['landing_pg_uri'],
    ];

    $response = $this->api_post(
        '/anoi',
        [
            'data' => wp_json_encode($payload)
        ],
        $s
    );

    if (is_wp_error($response)) {
        $this->fail(
            $response->get_error_message()
        );
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($status < 200 || $status >= 300) {
        $this->fail(
            'xString API HTTP ' . $status . ': ' . $body
        );
    }

    $params = json_decode($body, true);

    if (
        !is_array($params) ||
        empty($params['rse']) ||
        empty($params['trn'])
    ) {
        $this->fail(
            'The xString authentication service returned an invalid response.'
        );
    }

    $url =
        trailingslashit(
            $s['aas_base_url']
        ) .
        'authenticate';

    $url =
        add_query_arg(
            $params,
            $url
        );

    $allowed_host = wp_parse_url(
        $s['aas_base_url'],
        PHP_URL_HOST
    );
    
    $auth_host = wp_parse_url(
        $url,
        PHP_URL_HOST
    );
    
    if (
        !$allowed_host ||
        !$auth_host ||
        strtolower($allowed_host) !== strtolower($auth_host) ||
        wp_parse_url($url, PHP_URL_SCHEME) !== 'https'
    ) {
        $this->fail(
            'The xString authentication URL is invalid.'
        );
    }
    
    wp_redirect($url);
    exit;
}

/*
 * -------------------------------------------------------------------------
 * /code-exchange callback
 * -------------------------------------------------------------------------
 */

public function handle_callback() {
    if (!$this->is_callback_request()) {
        return;
    }

    $code = isset($_GET['code'])
        ? sanitize_text_field(
            wp_unslash($_GET['code'])
        )
        : '';

    $state = isset($_GET['state'])
        ? sanitize_text_field(
            wp_unslash($_GET['state'])
        )
        : '';

    if ($code === '' || $state === '') {
        $this->fail(
            'The xString authentication response was incomplete.'
        );
    }

    $state_key =
        self::STATE_PREFIX .
        hash(
            'sha256',
            $state
        );

    $stored =
        get_transient($state_key);

    delete_transient($state_key);

    if (
        !is_array($stored) ||
        empty($stored['redirect_uri'])
    ) {
        $this->fail(
            'The xString authentication session is invalid or has expired.'
        );
    }

    $s = $this->settings();

    $expected_callback =
        $this->callback_url();

    if (
        untrailingslashit(
            $stored['redirect_uri']
        ) !==
        untrailingslashit(
            $expected_callback
        )
    ) {
        $this->fail(
            'The xString authentication callback URL is invalid.'
        );
    }

    $auth_payload = [
        'state' => $state,
        'authcode' => $code,
        'redirect_uri' => $stored['redirect_uri'],
    ];

    $response = $this->api_post(
        '/token-exchange',
        [
            'data' => wp_json_encode($auth_payload)
        ],
        $s
    );

    if (is_wp_error($response)) {
        $this->fail(
            $response->get_error_message()
        );
    }

    $status = wp_remote_retrieve_response_code($response);

    if ($status < 200 || $status >= 300) {
        $this->fail(
            'The xString authentication service returned an unsuccessful response.'
        );
    }

    $body = json_decode(
        wp_remote_retrieve_body($response),
        true
    );

    if (
        !is_array($body) ||
        empty($body['jwt'])
    ) {
        $this->fail(
            'The xString authentication service returned an invalid token response.'
        );
    }

    $claims =
        $this->verify_jwt(
            $body['jwt'],
            $s['aas_base_url']
        );

    if (is_wp_error($claims)) {
        $this->fail(
            $claims->get_error_message()
        );
    }

    $npk =
        $this->extract_npk($claims);

    if ($npk === '') {
        $this->fail(
            'The xString authentication response did not contain an NPK.'
        );
    }

    $user =
        $this->find_user_by_npk($npk);

    if (!$user) {

        $user_id =
            $this->create_user_from_npk($npk);

        if (is_wp_error($user_id)) {
            $this->fail(
                $user_id->get_error_message()
            );
        }

        $user =
            get_user_by(
                'id',
                $user_id
            );
    }

    if (!$user) {
        $this->fail(
            'Unable to establish the WordPress account.'
        );
    }

    /*
     * WordPress remains responsible for the normal session.
     * xString authentication only establishes the authenticated
     * WordPress user.
     */
    wp_set_auth_cookie(
        $user->ID,
        false,
        is_ssl()
    );

    wp_set_current_user(
        $user->ID
    );

    wp_safe_redirect(
        admin_url()
    );

    exit;
}

/*
 * -------------------------------------------------------------------------
 * xString API
 * -------------------------------------------------------------------------
 */

private function api_post(
    $path,
    $body,
    $s
) {
    $api_key =
        $this->generate_api_key($s);

    $url =
        trailingslashit(
            $s['aas_base_url']
        ) .
        ltrim(
            $path,
            '/'
        );

    return wp_remote_post(
        $url,
        [
            'timeout' => 15,

            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'x-key-npk' => $s['client_npk'],
                'x-key-id' => $s['access_key_id'],
            ],

            'body' =>
                wp_json_encode($body),
        ]
    );
}

private function generate_api_key($s) {
    /*
     * Matches the rolling 30-day HMAC calculation used by
     * the xst-authentication-api Node.js example.
     */
    $interval =
        30 * 24 * 60 * 60;

    $now =
        (int) floor(
            time() / $interval
        );

    return hash_hmac(
        'sha256',
        $s['access_key_id'] . $now,
        $s['secret_access_key']
    );
}

/*
 * -------------------------------------------------------------------------
 * JWT / JWKS verification
 * -------------------------------------------------------------------------
 */

private function verify_jwt(
    $jwt,
    $api_base_url
) {
    $parts =
        explode('.', $jwt);

    if (count($parts) !== 3) {
        return new WP_Error(
            'xstring_invalid_jwt',
            'The returned JWT is invalid.'
        );
    }

    $header =
        json_decode(
            $this->base64url_decode(
                $parts[0]
            ),
            true
        );

    if (
        !is_array($header) ||
        empty($header['kid']) ||
        ($header['alg'] ?? '') !== 'RS256'
    ) {
        return new WP_Error(
            'xstring_invalid_jwt_header',
            'The returned JWT header is invalid.'
        );
    }

    $cache_key =
    'xstring_passwordless_jwks_' .
    md5(untrailingslashit($api_base_url));

    $jwks = get_transient($cache_key);

    if (
        !is_array($jwks) ||
        empty($jwks['keys']) ||
        !is_array($jwks['keys'])
    ) {
        $jwks_url =
            trailingslashit($api_base_url) .
            '.well-known/jwks.json';

        $response = wp_remote_get(
            $jwks_url,
            [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        if (is_wp_error($response)) {
            return new WP_Error(
                'xstring_jwks_error',
                'Unable to retrieve the xString public key.'
            );
        }

        $status = wp_remote_retrieve_response_code($response);

        if ($status < 200 || $status >= 300) {
            return new WP_Error(
                'xstring_jwks_http_error',
                'Unable to retrieve the xString public key.'
            );
        }

        $jwks = json_decode(
            wp_remote_retrieve_body($response),
            true
        );

        if (
            !is_array($jwks) ||
            empty($jwks['keys']) ||
            !is_array($jwks['keys'])
        ) {
            return new WP_Error(
                'xstring_jwks_invalid',
                'The xString public key response is invalid.'
            );
        }

        set_transient(
            $cache_key,
            $jwks,
            HOUR_IN_SECONDS
        );
    }
    
    $jwk = null;

    foreach ($jwks['keys'] as $key) {
        if (
            ($key['kid'] ?? '') ===
            $header['kid']
        ) {
            $jwk = $key;
            break;
        }
    }

    if (
        !$jwk ||
        empty($jwk['n']) ||
        empty($jwk['e'])
    ) {
        return new WP_Error(
            'xstring_jwk_not_found',
            'The xString signing key was not found.'
        );
    }

    $public_key =
        $this->jwk_to_pem($jwk);

    if (is_wp_error($public_key)) {
        return $public_key;
    }

    $signing_input =
        $parts[0] . '.' . $parts[1];

    $signature =
        $this->base64url_decode(
            $parts[2]
        );

    $verified =
        openssl_verify(
            $signing_input,
            $signature,
            $public_key,
            OPENSSL_ALGO_SHA256
        );

    if ($verified !== 1) {
        return new WP_Error(
            'xstring_invalid_signature',
            'The xString JWT signature could not be verified.'
        );
    }

    $claims =
        json_decode(
            $this->base64url_decode(
                $parts[1]
            ),
            true
        );
    if (isset($claims['payload']) && is_array($claims['payload'])) {
        $claims = array_merge($claims, $claims['payload']);
    }
    error_log(
        'xString JWT claim keys: ' .
        (
            is_array($claims)
                ? implode(', ', array_keys($claims))
                : 'INVALID JSON'
        )
    );

    if (
        isset($claims['payload']) &&
        is_array($claims['payload'])
    ) {
        $claims = array_merge(
            $claims,
            $claims['payload']
        );
    }
    
    if (!is_array($claims)) {
        return new WP_Error(
            'xstring_invalid_claims',
            'The xString JWT claims are invalid.'
        );
    }

    if (
    !isset($claims['exp']) ||
    !is_numeric($claims['exp']) ||
    time() >= (int) $claims['exp']
    ) {
        return new WP_Error(
            'xstring_expired',
            'The xString authentication token is expired or has no valid expiry.'
        );
    }
    
    if (
        !isset($claims['iss']) ||
        $claims['iss'] !== 'https://saas.xstring.tech/sa_QjvcjPaxFi'
    ) {
        return new WP_Error(
            'xstring_invalid_issuer',
            'The xString authentication token issuer is invalid.'
        );
    }
    
    return $claims;
}

private function extract_npk($claims) {
    /*
     * Prefer aas:npk from the verified JWT.
     */
    if (!empty($claims['aas:npk'])) {
        return sanitize_text_field(
            $claims['aas:npk']
        );
    }

    if (!empty($claims['npk'])) {
        return sanitize_text_field(
            $claims['npk']
        );
    }

    return '';
}

/*
 * -------------------------------------------------------------------------
 * WordPress user handling
 * -------------------------------------------------------------------------
 */

private function find_user_by_npk($npk) {
    $users =
        get_users(
            [
                'meta_key' =>
                    'irc_npk',

                'meta_value' =>
                    $npk,

                'number' => 1,

                'count_total' =>
                    false,
            ]
        );

    return !empty($users)
        ? $users[0]
        : false;
}

private function create_user_from_npk($npk) {
    /*
     * The NPK is unique and immutable, so it can be used
     * as the initial WordPress username.
     */
    $username =
        sanitize_user(
            $npk,
            true
        );

    if ($username === '') {
        return new WP_Error(
            'xstring_username_invalid',
            'The returned NPK cannot be used as a WordPress username.'
        );
    }

    if (username_exists($username)) {
        /*
         * Do not silently attach the xString credential to
         * an unrelated existing WordPress account.
         */
        return new WP_Error(
            'xstring_username_collision',
            'The xString NPK matches an existing WordPress username but is not linked to that account.'
        );
    }

    $random_password =
        wp_generate_password(
            32,
            true,
            true
        );

    $user_id =
        wp_create_user(
            $username,
            $random_password
        );

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    wp_update_user([
        'ID'           => $user_id,
        'display_name' => 'NPK.' . trim(chunk_split(preg_replace('/^npk\./i', '', $npk), 4, ' ')),
    ]);

    update_user_meta(
        $user_id,
        'irc_npk',
        $npk
    );

    return $user_id;
}

public function profile_npk($profileuser) {
    if (!current_user_can(
        'edit_user',
        $profileuser->ID
    )) {
        return;
    }

    $npk =
        get_user_meta(
            $profileuser->ID,
            'irc_npk',
            true
        );

    ?>
    <h2>xString Tech</h2>

    <table class="form-table">

        <tr>

            <th>
                <label for="irc_npk">
                    iRC NPK
                </label>
            </th>

            <td>

                <input
                    type="text"
                    class="regular-text"
                    value="<?php echo esc_attr($npk); ?>"
                    readonly
                >

                <p class="description">
                    The xString NPK is a unique credential
                    identifier and is not editable by the user.
                </p>

            </td>

        </tr>

    </table>
    <?php
}

/*
 * -------------------------------------------------------------------------
 * JWK to PEM
 * -------------------------------------------------------------------------
 */

private function jwk_to_pem($jwk) {
    $n =
        $this->base64url_decode(
            $jwk['n']
        );

    $e =
        $this->base64url_decode(
            $jwk['e']
        );

    if ($n === '' || $e === '') {
        return new WP_Error(
            'xstring_jwk_invalid',
            'The xString JWK is invalid.'
        );
    }

    $modulus =
        $this->der_encode_integer($n);

    $exponent =
        $this->der_encode_integer($e);

    $rsa_public_key =
        "\x30" .
        $this->der_length(
            strlen(
                $modulus . $exponent
            )
        ) .
        $modulus .
        $exponent;

    $rsa_oid =
        "\x30\x0d" .
        "\x06\x09" .
        "\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01" .
        "\x05\x00";

    $bit_string =
        "\x03" .
        $this->der_length(
            strlen($rsa_public_key) + 1
        ) .
        "\x00" .
        $rsa_public_key;

    $subject_public_key_info =
        "\x30" .
        $this->der_length(
            strlen(
                $rsa_oid . $bit_string
            )
        ) .
        $rsa_oid .
        $bit_string;

    return
        "-----BEGIN PUBLIC KEY-----\n" .
        chunk_split(
            base64_encode(
                $subject_public_key_info
            ),
            64,
            "\n"
        ) .
        "-----END PUBLIC KEY-----\n";
}

private function der_encode_integer($value) {
    if (ord($value[0]) > 0x7f) {
        $value =
            "\x00" . $value;
    }

    return
        "\x02" .
        $this->der_length(
            strlen($value)
        ) .
        $value;
}

private function der_length($length) {
    if ($length < 128) {
        return chr($length);
    }

    $bytes = '';

    while ($length > 0) {
        $bytes =
            chr($length & 0xff) .
            $bytes;

        $length =
            $length >> 8;
    }

    return
        chr(
            0x80 | strlen($bytes)
        ) .
        $bytes;
}

private function base64url_decode($value) {
    $value =
        strtr(
            $value,
            '-_',
            '+/'
        );

    $padding =
        strlen($value) % 4;

    if ($padding) {
        $value .= str_repeat(
            '=',
            4 - $padding
        );
    }

    $decoded =
        base64_decode(
            $value,
            true
        );

    return
        $decoded === false
            ? ''
            : $decoded;
}

/*
 * -------------------------------------------------------------------------
 * Error handling
 * -------------------------------------------------------------------------
 */

private function fail($message) {
    $message =
        wp_strip_all_tags(
            $message
        );

    $url =
        add_query_arg(
            'xstring_error',
            rawurlencode($message),
            wp_login_url()
        );

    wp_safe_redirect($url);
    exit;
}

}

new XString_Tech_Passwordless();

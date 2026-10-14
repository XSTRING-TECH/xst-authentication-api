# xString Tech Passwordless Authentication for WordPress

This directory contains the WordPress PHP plugin and supporting documentation for integrating xString Tech Passwordless Authentication.

## Adds the iRC Passwordless Method to WordPress

A lightweight WordPress plugin that adds the Impersonation-Resistant Credential (iRC) Passwordless Method as an additional signup and login option. It does not replace WordPress's existing username-and-password authentication.

## What it does

1. Adds the iRC Passwordless Method button to the standard WordPress login page.
2. Initiates the xString Tech SaaS Authentication API flow and redirects the user to the authentication endpoint.
3. Receives the authorisation code at the WordPress `/code-exchange` endpoint.
4. Exchanges the code for a JWT through the SaaS Authentication API.
5. Retrieves the public signing key from the JWKS endpoint and verifies the JWT signature.
6. Checks the JWT issuer and expiry, then reads the user's NPK from the verified JWT.
7. Finds an existing WordPress account linked through `irc_npk`, or creates a new account and stores the NPK as user metadata.
8. Establishes a normal WordPress login session.

## Account handling

When creating a new WordPress account, the plugin uses the NPK as the WordPress username.

Example:

```text
WordPress username: NPK.123456789012
irc_npk: NPK.123456789012
```

The plugin uses `irc_npk` user metadata to identify an account linked to the authenticated credential.

Account handling follows these rules:

1. **Existing account linked to the same NPK:** The plugin finds the account through `irc_npk` and proceeds with login after successful JWT verification.
2. **No linked account and username available:** The plugin creates a new WordPress account, stores the NPK in `irc_npk` and logs the user in after successful JWT verification.
3. **NPK-derived username already belongs to an unlinked account:** The plugin rejects the iRC signup rather than automatically linking the credential to that account.

The third rule applies only to the iRC Passwordless Method. It does not affect the existing account's ability to sign in using WordPress's normal username-and-password authentication.

The NPK field displayed on the WordPress user profile is read-only in the plugin interface. The plugin does not replace WordPress's native account management, role management, password, profile or authentication functions.
   
### Existing WordPress Users

Users can continue using their existing WordPress username and password. However, the plugin does not currently provide a process for linking an existing password-based account to an iRC.

A possible future enhancement is to allow a signed-in user to link an iRC to their existing account through a verified authentication flow. The system would then store the verified NPK in `irc_npk`, allowing the user to sign in using either method.

Enhancement detailed steps:
1. The user signs in to their existing WordPress account using their normal username and password.
2. The user enters their iRC NPK (e.g. `NPK.123456789012`) in their WordPress account settings.
3. The system would use the existing xString Tech SaaS Authentication API to verify the iRC through an appropriate authentication flow. Once verified, the system would associate the iRC with the signed-in WordPress account by storing the NPK in `irc_npk`.
4. Once the link is established, the user can sign in using either their existing WordPress username and password or the iRC Passwordless Method.

## Configuration

After activating the plugin, go to:

**WordPress → Settings → xString Passwordless**

Configure the following settings:

- **AAS Base URL** — defaults to `https://saas.xstring.tech`.
- **Client NPK**
- **Auth Scope**
- **Access Key ID**
- **Secret Access Key**
- **Auth Code URL** — must match this site's `/code-exchange` callback URL.
- **Landing Page URL**

The configured Auth Code URL is checked against the callback URL derived from the WordPress site's configured home URL. Register the same callback URL with the xString Authentication API.

The authentication redirect is checked to ensure it uses HTTPS and the same hostname as the configured AAS Base URL. However, the current implementation does not consistently enforce HTTPS for all API requests.

The plugin stores its settings, including the Secret Access Key, in WordPress options. For higher-security deployments, consider an appropriate secrets-management approach and restrict administrative access.

## Shortcode

Use the following shortcode on a WordPress page to display the iRC Passwordless Method button:

```text
[irc_passwordless]
```

The button is also added to the standard WordPress login page.

## Security controls

The current implementation includes the following controls:

- Generates a unique state value for each authentication attempt.
- Stores a hash of the state server-side in a WordPress transient with a 10-minute expiry.
- Checks and consumes the stored state during the callback.
- Validates the configured Auth Code URL against the expected WordPress `/code-exchange` callback URL.
- Exchanges the authorisation code server-to-server.
- Retrieves and caches JWKS for one hour.
- Accepts only an `RS256` JWT with a `kid` identifying a signing key containing the required RSA public-key parameters.
- Verifies the JWT signature using the public key obtained from JWKS.
- Checks the JWT issuer and expiry.
- Stores the NPK server-side as WordPress user metadata.

### Implementation notes

The plugin provides a starting point for integrating xString Tech Passwordless Authentication with WordPress. Developers can review, modify and extend the PHP implementation to suit their requirements.

The current implementation does not yet:

- Bind the authentication state to the initiating browser.
- Enforce HTTPS consistently for all API requests.
- Validate the JWT `nbf` (not-before), `iat` (issued-at) and `aud` (audience) claims.
- Automatically refresh the cached JWKS when a JWT's `kid` is not found.

These notes describe the current PHP plugin implementation, not limitations of the SaaS Authentication API. Developers should assess the controls against their own requirements and the API's token contract.

The plugin is released under the MIT licence, allowing developers to modify and extend the implementation under its terms.

## Requirements

- WordPress
- PHP with OpenSSL enabled
- HTTPS on the WordPress site and the xString API endpoint
- Access to the xString SaaS Authentication API
- Valid xString SaaS API credentials

## Testing and deployment

Test in a staging WordPress environment before deploying to production. Testing should cover:

- New-user signup and repeat login
- Invalid, expired and not-yet-valid tokens
- JWT issuer and audience validation
- State mismatch, expiry and replay attempts
- Browser-bound state validation
- Signing-key rotation and JWKS refresh
- HTTPS enforcement and callback URL validation
- Native WordPress login
- Logout and password reset
- User roles and post-login redirects
- Multisite behaviour
- Caching and concurrent authentication attempts

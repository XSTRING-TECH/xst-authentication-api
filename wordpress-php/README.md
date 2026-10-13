# xString Tech Passwordless Authentication for WordPress

This directory contains the WordPress PHP plugin and supporting
documentation for integrating xString Tech Passwordless Authentication.

## Adds the Impersonation-Resistant Credential (iRC) Passwordless Authentication Method to WordPress

A lightweight WordPress plugin that adds the **iRC Passwordless Method** as an additional signup and login option.

It does **not replace WordPress's existing username-and-password authentication**.

## What it does

1. Adds the iRC Passwordless Method button to the standard WordPress login page.
2. Initiates the xString Tech SaaS Authentication API flow.
3. Redirects the user to the xString Tech authentication endpoint.
4. Receives the authorisation code at the WordPress `/code-exchange` endpoint.
5. Exchanges the code for a JWT through the xString Tech SaaS Authentication API.
6. Retrieves the xString Tech public signing key from the JWKS endpoint and verifies the JWT signature.
7. Checks the JWT issuer and expiry.
8. Reads the user's NPK from the verified JWT.
9. Finds an existing WordPress account linked through `irc_npk`, or creates a new account and stores the NPK as user metadata.
10. Establishes a normal WordPress login session.

## Account handling

When creating a new WordPress account, the plugin uses the NPK as the WordPress username.

Example:

```text
WordPress username: NPK.123456789012
irc_npk: NPK.123456789012
```

The plugin uses `irc_npk` to find an account already linked to the authenticated credential. It does not automatically link a credential to an existing account merely because the account's username matches the NPK.

If the NPK-derived username is already taken by an account that is not linked to the authenticated credential, the plugin rejects the signup rather than linking the credential to that account.

The NPK field displayed on the WordPress user profile is read-only in the plugin interface. The plugin does not replace WordPress's native account management, role management, password, profile or authentication functions.

### iRC Passwordless Method Logic — After iRC Authentication

1. **NPK-derived username already belongs to another account:** If the username derived from the NPK already belongs to an existing WordPress account that is not linked to the authenticated credential through `irc_npk`, the plugin rejects the iRC passwordless signup. It does not automatically link the iRC to that existing account.

   This restriction applies only to iRC passwordless login and signup. It does not affect the existing account's ability to sign in using WordPress's normal username-and-password authentication.

2. **No account linked to the NPK and username available:** The plugin creates a new WordPress account, stores the NPK in its `irc_npk` user metadata, and logs the user in after successful JWT verification. This establishes the link between the new account and iRC as part of the signup process.

3. **Account linked to the same NPK:** The plugin finds the account through its `irc_npk` user metadata and proceeds with login after successful JWT verification.
   
### Configure for Existing WordPress Users

**Existing account using both login methods:** Users can continue to sign in with their existing WordPress username and password while also using the iRC Passwordless Method, provided their WordPress account is linked to the authenticated iRC through `irc_npk`. The plugin does not replace or disable WordPress’s existing username-and-password authentication.

**Current limitation:** The plugin does not currently provide a process for linking an existing WordPress password-based account to an iRC.

**Proposed simple implementation:**

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

The configured Auth Code URL is checked against the callback URL derived from the WordPress site's configured home URL. Register the same callback URL with the xString Authentication API.

The authentication redirect is checked to ensure it uses HTTPS and the same hostname as the configured AAS Base URL. However, the current implementation does not consistently enforce HTTPS for all API requests.

The plugin stores its settings, including the Secret Access Key, in WordPress options. For higher-security deployments, consider an appropriate secrets-management approach and restrict administrative access.

## Shortcode

Use the following shortcode on a page to display the button:

```text
[irc_passwordless]
```

The button is also added to the standard WordPress login page.

## Security controls

The current implementation:

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

The current implementation does not yet:

- Bind the authentication state to the initiating browser using a cookie.
- Explicitly enforce HTTPS for all AAS Base URL requests.
- Validate the JWT `nbf` (not-before), `iat` (issued-at) or `aud` (audience) claims.
- Refresh the cached JWKS automatically when a JWT's `kid` is not found.

These controls should be implemented and tested before production deployment. Audience validation should use the expected value and format confirmed by the xString Authentication API.

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

The plugin can be further enhanced with additional security and validation controls. The items identified above should be addressed and verified against the implementation before the plugin is considered ready for production deployment.

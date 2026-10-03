# xst-authentication-api

Example integration showing how a **CCS** can use the **xString Tech SaaS Authentication API** for passwordless authentication without installing or self-hosting **xstAuth Light Server**.

The xString Tech Authentication API provides the interface between the CCS Web Server and the xString Tech hosted authentication service.

The API interface is **language-agnostic**. This repository provides a Node.js example, but the same integration can be implemented in other programming languages, including Python, C#, Go, PHP, or any language capable of making HTTPS requests.

## What is CCS?

**Confidential Client System (CCS)** is a proprietary term used by **XSTRING TECH PTY LTD** to describe user-facing digital systems designed to protect user data, control access to information and services, and require user authentication and/or age assurance.

As you use XSTRING TECH technologies and solutions, the term **CCS** may be used throughout our documentation, technical specifications, examples, and implementation materials.

### CCS — Your System

CCS is effectively **your own user-facing system** that connects your end users to the **xString Tech Authentication Service** for passwordless authentication.

Your CCS delegates the authentication service to the **Advanced Authentication System (AAS)**, which in this example refers to the **xString Tech SaaS Authentication API**. The xString Tech hosted authentication service operates within **xString Tech infrastructure** rather than within the CCS's own environment.

---

## Deployment options

A CCS can integrate with xString Tech authentication in multiple ways. The two main options are:

### (1) SaaS Authentication API

The CCS uses the xString Tech hosted Authentication API. The CCS does not need to install or operate the authentication server.

```text
User
  │
  │ HTTPS
  ▼
CCS Web Server
  │
  │ HTTPS
  ▼
xString Tech Authentication API
  │
  ▼
Passwordless Authentication
```

This option allows the CCS to use xString Tech authentication without hosting the authentication service within its own environment.

### (2) Self-hosted xstAuth Light Server

Alternatively, the CCS can install and operate [xstAuth Light Server](https://github.com/XSTRING-TECH/software-downloads?utm_source=chatgpt.com) within its own environment.

This repository provides an example integration for the **SaaS Authentication API** option. It does not provide the xstAuth Light Server itself.

## About this example

The `server.js` file provides a working Node.js implementation of a CCS Web Server.

The example demonstrates:

1. A user starts sign-up/sign-in from the CCS.
2. The CCS generates a `state` value.
3. The CCS sends an Authentication Notice of Intent (ANoI) to the xString Tech Authentication API.
4. The user's browser is redirected to the xString Tech authentication endpoint.
5. The user completes passwordless authentication.
6. The xString Tech Authentication API redirects the browser back to the CCS with an authorisation code and state.
7. The CCS exchanges the authorisation code with the xString Tech Authentication API.
8. The CCS receives an ID Token.
9. The CCS obtains the public keys from the xString Tech JWKS endpoint and verifies the ID Token signature.
10. The CCS establishes a signed session cookie for the authenticated user.
11. The user is redirected to the protected CCS portal.

## Repository contents

```text
xst-authentication-api/
│
├── .env.example
├── .gitignore
├── LICENSE
├── README.md
├── package.json
└── server.js
```

### `server.js`

A complete Node.js example implementing the CCS Web Server and its integration with the xString Tech Authentication API.

### `.env.example`

Example environment variables required by the Node.js implementation.

Copy this file to `.env` and provide the appropriate values for the CCS environment.

**Do not commit `.env` or any real credentials, keys, secrets, certificates, or other sensitive information to the repository.**

### `package.json`

Defines the Node.js project and its required dependencies.

## Node.js requirements

If the CCS Web Server is implemented using Node.js, install the required dependencies:

```bash
npm install express axios dotenv body-parser jsonwebtoken cookie-parser
```

The example uses:

* **Express** — web server
* **Axios** — HTTPS API requests
* **dotenv** — environment variable configuration
* **body-parser** — request body parsing
* **jsonwebtoken** — ID Token verification
* **cookie-parser** — signed session cookie handling

If the CCS Web Server is developed using another programming language, implement the equivalent functionality using the libraries and frameworks appropriate to that language.

## Language-agnostic API

The interface between the CCS Web Server and the xString Tech Authentication API does not depend on Node.js.

The CCS can be implemented using any suitable programming language or framework capable of making HTTPS requests and processing the required requests and responses.

For example:

* Python
* C#
* Go
* PHP
* Java
* Ruby
* JavaScript / Node.js
* Other languages capable of making HTTPS requests

The Node.js implementation in this repository is provided as an example only.

## Obtain API credentials

Before using the xString Tech Authentication API, the CCS operator must obtain API credentials from the xString Tech website.

Go to [www.xstring.tech](https://www.xstring.tech?utm_source=chatgpt.com), sign in, and create the API credentials required for the CCS integration.

Credential creation is currently behind a **$1 payment paywall**. The $1 payment is required to access the credential-creation process and is **not an API usage charge**. **There is currently no separate charge for API usage.**

Additional contributions are optional. A CCS operator may make an additional contribution if they wish, but no additional contribution is required to obtain or use the API credentials.

The payment does not constitute a guarantee by XSTRING TECH PTY LTD of API availability, reliability, performance or uninterrupted service. Users should have no expectation of guaranteed availability or performance.

For CCS operators requiring greater control over availability or performance, an alternative is to install and operate [xstAuth Light Server](https://github.com/XSTRING-TECH/software-downloads) within their own environment.

The credentials/configuration required by this example are:

```text
CLIENT_NPK=4...00
AUTH_SCOPE=pw...h
AAS_BASE_URL=https://saas.xstring.tech
ACCESS_KEY_ID=vu82...s9
SECRET_ACCESS_KEY=I6spA.....tUKQ
AUTH_CODE_URL=https://www.yourdomain.com/code-exchange
```

Replace the example values with the credentials and configuration issued for the CCS.

### Keep credentials confidential

The `CLIENT_NPK`, `ACCESS_KEY_ID` and `SECRET_ACCESS_KEY` values are credentials associated with the CCS integration.

Do not publish, commit, or otherwise expose the actual values in source code, Git repositories, browser-side code, screenshots, logs, or other publicly accessible locations.

Store them securely using environment variables or another appropriate secret-management mechanism.

The `AUTH_CODE_URL` is the HTTPS endpoint on the CCS Web Server to which the user's browser is redirected after successful authentication. Replace `https://www.yourdomain.com/code-exchange` with the actual HTTPS endpoint of the CCS.

## User App Requirements

End users of your CCS require a compatible **Authenticator App** to perform passwordless authentication.

Supported Authenticator Apps include:

* **myAge**
* **iRC Auth**

When a user signs up or signs in to your CCS website, the user is delegated to **xstAuth Light Server** for passwordless authentication.

The user then uses the **"Confirm it's you"** feature in one of the supported Authenticator Apps to approve the authentication request.

> **IMPORTANT:** End users must have a compatible Authenticator App installed on their device to complete passwordless authentication.

### Get the Authenticator App

Users can obtain the supported Authenticator Apps from the official app stores:

### myAge Authenticator

**[Download on the App Store](https://apps.apple.com/us/app/myage/id6763018516)**

**[Get it on Google Play](https://play.google.com/store/apps/details?id=com.xstring.poadc&pcampaignid=web_share)**

### xString Advanced Authenticator (iRC Auth)

**Download on the App Store — Coming Soon**

**[Get it on Google Play](https://play.google.com/store/apps/details?id=com.xstring.ircauth&pcampaignid=web_share)**

---

## Configuration

The example uses environment variables for configuration.

The following values are required by the example:

```text
COOKIE_SECRET
CLIENT_NPK
AUTH_SCOPE
ACCESS_KEY_ID
SECRET_ACCESS_KEY
AAS_BASE_URL
PRIVATE_KEY
CERTIFICATE
```

Refer to `.env.example` for the configuration format.

### Important

Use the appropriate credentials, certificates, keys and endpoint values for the CCS environment.

Do not use example or development credentials in a production environment.

## Authentication flow

The CCS initiates authentication through:

```text
POST /auth
```

The CCS creates an Authentication Notice of Intent (ANoI), including:

* `state`
* `scope`
* `response_type`
* `redirect_uri`
* `landing_pg_uri`

The CCS then redirects the user's browser to the xString Tech authentication endpoint.

After successful authentication, the browser is redirected to:

```text
GET /code-exchange
```

The CCS receives the authorisation code and state and exchanges them with the xString Tech Authentication API for an ID Token.

The CCS then verifies the ID Token using the public signing key published through the xString Tech JWKS endpoint.

After successful verification, the example establishes a signed session cookie and redirects the user to:

```text
/portal
```

### Public key verification

The example retrieves the xString Tech public signing keys from the following JWKS endpoint:

`https://saas.xstring.tech/.well-known/jwks.json`

The JWKS is used to obtain the public key corresponding to the `kid` in the JWT header. The CCS uses the matching public key to verify the RS256 signature of the ID Token returned by the xString Tech Authentication API.

The public JWKS endpoint does not require API credentials.

## Security considerations

This repository is a working integration example and is intended to demonstrate the interface between a CCS and the xString Tech Authentication API.

A production CCS should implement security controls appropriate to its own environment and application, including appropriate request filtering, input validation, session management, error handling, logging, certificate management, secret management and other application security controls.

The example's `/` endpoint is intentionally simple and does not include production security or filtering mechanisms such as CAPTCHA or other controls that may be appropriate for a particular CCS.

Keep all credentials and private keys outside the source code and do not commit them to Git.

## Terminology

### CCS

**Confidential Client System (CCS)**

In this repository, the CCS is the application and web server integrating with the xString Tech Authentication API.

### AAS

**Advanced Authentication System (AAS)**

In `server.js`, **AAS** is used to refer to the xString Tech hosted SaaS Authentication API used by this implementation.

The term is retained in the example to correspond with the API endpoints and integration terminology.

### xstAuth Light Server

**xstAuth Light Server** is the self-hosted authentication server provided by xString Tech.

The hosted Authentication API described in this repository provides an alternative for CCS deployments that do not want to install and operate xstAuth Light Server.

## License

This repository contains example integration code. See the included `LICENSE` file for the applicable licence terms.

## xString Tech

**XSTRING TECH PTY LTD**

The xString Tech Authentication API is a hosted service provided by XSTRING TECH PTY LTD.

For further information about xString Tech authentication services, refer to the applicable service documentation and terms.

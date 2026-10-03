# xst-authentication-api

Example integration showing how a **Confidential Client System (CCS)** can use the **xString Tech hosted Authentication API** for passwordless authentication without installing or self-hosting **xstAuth Light Server**.

The xString Tech Authentication API provides the interface between the CCS Web Server and the xString Tech hosted authentication service.

The API interface is **language-agnostic**. This repository provides a Node.js example, but the same integration can be implemented in other programming languages, including Python, C#, Go, PHP, or any language capable of making HTTPS requests.

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

Credential creation is currently behind a **$1 payment requirement**. The $1 payment is required to access the credential-creation process and is **not an API usage charge**. Payment does not constitute a guarantee by XSTRING TECH PTY LTD of API availability, reliability, performance or uninterrupted service. Users should have no expectation of guaranteed availability or performance.

Additional contributions are optional. A CCS operator may provide an additional donation if they wish, but no additional contribution is required to obtain or use the API credentials.

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

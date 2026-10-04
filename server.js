// These code snippets enable Confidential Client System (CCS) to consume xStringTech's SaaS API for authenticating their users. 
// The SaaS is useful for CCS who do not want to self-host xStringTech's Adavanced Authentication System (AAS): xstAuth Light Server
// Required to install these dependencies if you use nodejs `npm install express axios dotenv body-parser jsonwebtoken cookie-parser`
// If your CCS Web Server is developed using another programming language, such as Python, C#, Go, or PHP, implement the equivalent functionality in that language.
// The interface between your CCS Web Server and the xStringTech's SaaS API  is language-agnostic.

// Terminology: "AAS" is used throughout this server.js to refer specifically
// to xString Tech SaaS Authentication API in this implementation.
//
// Terminology: "CCS" is used throughout this server.js to refer to the
// Confidential Client System (CCS) represented by this web server.
// In this example, server.js provides the web-server implementation of the CCS.

const fs = require('fs');
const https = require('https');
const { randomUUID } = require('crypto');
const crypto = require('crypto');
const jwt = require("jsonwebtoken");
const path = require('path');
const express = require('express');
const axios = require('axios');
const cookieParser = require("cookie-parser");
require('dotenv').config();

// Enviroment variables from .env or from other method of your choice
const cookieSecret = process.env.COOKIE_SECRET;
const clientNpk = process.env.CLIENT_NPK;
const accessScope = process.env.AUTH_SCOPE;
const accessKeyId = process.env.ACCESS_KEY_ID;
const secrtAccessKey = process.env.SECRET_ACCESS_KEY;
const aasBaseUrl = process.env.AAS_BASE_URL; // AAS endpoint is only accesible to external Browser/UI if referred/redirected by the CCS

const CCS = express();

const bodyParser = require('body-parser');
CCS.use(bodyParser.json());

// Serve user's account page post successful authentication
CCS.use(express.static(path.join(__dirname, 'public')));
CCS.use(express.urlencoded({ extended: true })); 
CCS.use(express.json()); 
CCS.use(cookieParser(cookieSecret));

CCS.get("/", (req, res) => {
    // Front-channel — CCS-facing external browser/UI over HTTPS.
    // This example simulates a simple welcome page with a sign-up/sign-in button.
    // It is provided as a working example and does not include reCAPTCHA or other
    // production security/filtering mechanisms that may be required by your CCS.
        res.send(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Confidential Client User Interface</title>
        </head>
        <body>
          Passwordless Authentication with xString Tech API
          <br></br>
          <form action="/auth" method="POST"><button type="submit">Sign up / sign in</button></form>
        </body>
        </html>
          `);
});

// Function to compute API key locally
const KEY_ROTATION_INTERVAL = 30 * 24 * 60 * 60 * 1000;
function generateApiKey() {
    const now = Math.floor(Date.now() / KEY_ROTATION_INTERVAL);
    return crypto
        .createHmac('sha256', secrtAccessKey)
        .update(accessKeyId + now)
        .digest('hex');
}

// CCS Endpoints
const CCS_BASE_URL = 'https://www.yourdomain.com/code-exchange'; // Post authencation AAS redirects Browser/UI to this endpoint of CCS with the state and authorisation code.
const CCS_LANDING_PG_URL = 'https://www.yourdomain.com.au'; // Home page showing signup/signin buttons. This is where the user first arrives or is sent after failed login. AAS redirects the user here after multiple unsuccessful signup/signin.

CCS.post('/auth', async (req, res) => { // this endpoint is for both sign up and sign in
  // After filtering out invalid or suspicious requests, allow only genuine requests to pass through to the next stage of your application.

  let state = randomUUID();  // Generate state for a stateful integration with AAS. 
  // ANoI payload - Authentication Notice of Intent (ANoI)
  const anoiPayload = JSON.stringify({
    state: state,
    canonical_username: "abc123_john_smith",
    // The canonical username is the CCS's authoritative internal account
    // identifier used to uniquely identify the user's account. It may be a
    // conventional username or another unique internal identifier maintained
    // by the CCS. This is particularly useful when the CCS needs to retain
    // existing password-based authentication while using xst-authentication-api
    // as a second factor of MFA for existing users.
    //
    // If xst-authentication-api is used as the primary passwordless authentication
    // method, canonical_username does not need to be provided. The user's NPK
    // can be used as the authoritative canonical account identifier.
    scope: accessScope,
    response_type: 'code', // Authorisation code (authCode) for CCS to exchange for security token.
    redirect_uri: CCS_BASE_URL, // After user is successfully authenticated the user's browser/UI is redirected to this endpoint with current state and authCode.
    landing_pg_uri: CCS_LANDING_PG_URL  // CCS Webpage, this is where the user first arrives and see signup/signin button. AAS will redirect the user here after multiple unsuccessful authentication attempts. 
  })

  const apiKey = generateApiKey();

  try {     
    const responsed = await axios.post(`${aasBaseUrl}/anoi`, { data: anoiPayload }, { headers: { 'x-api-key': apiKey, 'x-key-npk': clientNpk, 'x-key-id': accessKeyId }, httpsAgent: httpsagent, });
      const params = new URLSearchParams(responsed.data);
      res.redirect(`${aasBaseUrl}/authenticate?${params.toString()}`);
      //res.redirect(`${AAS_AUTH_URL}?rse=${responsed.data.state}`);
  } catch (error) {
      console.log({ error: error.response?.data || error.message });
      res.status(400).json({ error: error.response?.data || error.message });
  }
});

// CCS obtains .well-known public key for verifying ID Token post sucessfull authentication.
let jwtpublickey;
async function getmepubkey(){
  const response = await axios.get('https://saas.xstring.tech/.well-known/jwks.json', { httpsAgent: httpsagent } );
  console.log('status', response.status);
  jwtpublickey = response.data;
  }; getmepubkey();

// CCS facing Browser/UI using https
CCS.get('/code-exchange', async (req, res) => {  // this endpoint code-exchange for ID Token
  const { code, state } = req.query; 
  const authPayload = JSON.stringify({ state: state, authcode: code, redirect_uri: CCS_BASE_URL });
  const apiKey = generateApiKey();
  try {     
      const responsed = await axios.post(`${aasBaseUrl}/token-exchange`, { data: authPayload }, { headers: { 'x-api-key': apiKey, 'x-key-npk': clientNpk, 'x-key-id': accessKeyId }, });
      const idToken = responsed.data.jwt;
      let verifiedIdToken;
      try {
        // 1 Read JWT header
        const [encodedHeader] = idToken.split(".");
        const header = JSON.parse( Buffer.from(encodedHeader, "base64url").toString("utf8") );
        // 2. Find matching JWK
        const jwk = jwtpublickey.keys.find( key => key.kid === header.kid );
        if (!jwk) { throw new Error(`No public key found for kid: ${header.kid}`); }
        // 3. Convert JWK to Node KeyObject
        const publicKey = crypto.createPublicKey({ key: jwk, format: "jwk" });
        // 4. Verify JWT
        verifiedIdToken = jwt.verify( idToken, publicKey, { algorithms: ["RS256"] });
        console.log("✅ ID token JWT signature verified");
      } catch (err) {
          // If even one character of the JWT payload or other signed content is altered, signature verification will fail.
          console.log("❌ ID token JWT signature invalid");
          console.log(err.message);
      }

      const npk = verifiedIdToken.payload['aas:npk']; // You may use the npk as canonical username for the user. The canonical username is the CCS's authoritative internal account identifier used to uniquely identify the user's account.
      const poolname = "User Directory";
      const usertype = "Member";
      // Set a signed session cookie.
      // The signed cookie allows the CCS to detect whether the cookie has been 
      // modified after it was issued by the server.
      res.cookie(
        "longTTLSession",
        JSON.stringify({ npk, poolname, usertype }),
        {
          signed: true, // Cryptographically sign the cookie using the CCS cookie secret.
          httpOnly: true, // Prevent client-side JavaScript from accessing the cookie.
          secure: true, // Send the cookie only over HTTPS connections.
          sameSite: "Lax",
          maxAge: 24 * 60 * 60 * 1000, // 1 day
        },
      );
      res.redirect("/portal");

  } catch (error) {
      console.log({ error: error.response?.data || error.message });
      res.status(400).json({ error: error.response?.data || error.message });
  }
});

// Protected route
CCS.get("/portal", (req, res) => {
  const sessionCookie = req.signedCookies.longTTLSession;
  if (!sessionCookie) {
    return res.status(401).send("Not allowed");
  }
  let parsed;
  try {
      parsed = JSON.parse(sessionCookie);
  } catch (error) {
      return res.status(401).send("Not allowed");
  }
  const { npk, poolname, usertype } = parsed;
  const randomNum = Math.floor(Math.random() * 100);
  const [prefix, digits] = npk.split(".");
  // Format the NPK into groups of four digits to make it easier for the user to read and recognise.
  const groupedDigits = digits.replace(/(.{4})/g, "$1 ").trim(); 
  const NPK = `${prefix.toUpperCase()}.${groupedDigits}`;
  // Prevent the protected page from being cached by the browser or intermediate caching systems.
  res.setHeader("Cache-Control", "no-store");
  res.send(`
  <!DOCTYPE html>
  <html>
  <head>
    <title>Confidential Client User Interface</title>
  </head>
  <body>
  <h1>${usertype} Web Portal</h1><h4>Welcome, ${NPK}. You have successfully signed in or signed up (if you are a new user).</h4>
  <h3> Random Number: ${randomNum.toString()} </h3>
  <p id="normalSite">This site has no age restrictions.</p>
    <form action="/logout" method="POST">
      <input type="hidden" name="npk" value=${npk}>
      <button type="submit">Logout</buton>
    </form>
  </body>
  </html>
  `);
});

// Logout route.
// The CCS should invalidate the user's session and perform any other
// necessary logout processing before redirecting the user to the logout confirmation page.
CCS.post("/logout", (req, res) => {
  const npk = req.body.npk;
  res.clearCookie("longTTLSession");
  res.redirect("/logout-confirm?ts=" + Date.now());
});

// Logout confirmation.
// Display a confirmation page after the user's session has been terminated.
CCS.get("/logout-confirm", (req, res) => {
  // Prevent the logout confirmation page from being cached.
  res.set("Cache-Control", "no-store, no-cache, must-revalidate, private");
  res.set("Pragma", "no-cache");
  res.set("Expires", "0");

  res.send(`
  <h2>Sucessfully signed out</h2>
  <a href=/>Start Live Demo</a>
  `);
});

const ipaddress = '0.0.0.0';
const port = '443';
https.createServer({
  key: fs.readFileSync(process.env.PRIVATE_KEY),
  cert: fs.readFileSync(process.env.CERTIFICATE)
}, CCS).listen(port, ipaddress, () => {
  console.log('SaaS Client Server is running on https://www.yourdomain:443 for authentication only.');
});

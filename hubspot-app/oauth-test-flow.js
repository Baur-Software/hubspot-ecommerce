/**
 * HubSpot OAuth Test Flow
 *
 * This script helps you complete the OAuth flow to get an access token for testing.
 *
 * Steps:
 * 1. Opens browser to HubSpot authorization page
 * 2. User grants permissions
 * 3. HubSpot redirects back with authorization code
 * 4. Script exchanges code for access token
 * 5. Displays access token for testing
 */

const http = require('http');
const https = require('https');
const url = require('url');
const querystring = require('querystring');
const config = require('./config');

// Validate required config
try {
    config.validate();
} catch (error) {
    console.error('\n' + error.message);
    process.exit(1);
}

// OAuth credentials from config
const CLIENT_ID = config.hubspot.clientId;
const CLIENT_SECRET = config.hubspot.clientSecret;
const REDIRECT_URI = config.oauth.redirectUri;
const PORT = config.oauth.port;

// Required scopes (from config)
const SCOPES = config.oauth.scopes.join(' ');

console.log('\n🔐 HubSpot OAuth Test Flow\n');
console.log(`Starting local server on port ${PORT}...\n`);

// Create local server to receive OAuth callback
const server = http.createServer(async (req, res) => {
    const parsedUrl = url.parse(req.url, true);

    if (parsedUrl.pathname === '/') {
        // Root path - show authorization link
        const authUrl = `${config.hubspot.appBaseUrl}/oauth/authorize?` +
            `client_id=${CLIENT_ID}&` +
            `redirect_uri=${encodeURIComponent(REDIRECT_URI)}&` +
            `scope=${encodeURIComponent(SCOPES)}`;

        res.writeHead(200, { 'Content-Type': 'text/html' });
        res.end(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>HubSpot OAuth Test</title>
                <style>
                    body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                    .button {
                        display: inline-block;
                        padding: 15px 30px;
                        background: #ff7a59;
                        color: white;
                        text-decoration: none;
                        border-radius: 5px;
                        font-size: 16px;
                    }
                    .button:hover { background: #ff6443; }
                    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
                    h1 { color: #33475b; }
                    .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <h1>🔐 HubSpot OAuth Test Flow</h1>
                <div class="info">
                    <strong>App:</strong> Baur Software HubSpot Ecommerce for WordPress<br>
                    <strong>Client ID:</strong> ${CLIENT_ID}<br>
                    <strong>Scopes:</strong> e-commerce, CRM objects, communication preferences
                </div>
                <p>Click the button below to authorize the app and get an access token:</p>
                <p>
                    <a href="${authUrl}" class="button">Authorize App in HubSpot</a>
                </p>
                <p style="color: #666; font-size: 14px;">
                    This will redirect to HubSpot where you'll grant permissions.<br>
                    After authorization, you'll be redirected back here with an access token.
                </p>
            </body>
            </html>
        `);

    } else if (parsedUrl.pathname === '/callback') {
        // OAuth callback - exchange code for token
        const code = parsedUrl.query.code;

        if (!code) {
            res.writeHead(400, { 'Content-Type': 'text/html' });
            res.end(`
                <html>
                <body style="font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px;">
                    <h1 style="color: red;">❌ Error</h1>
                    <p>No authorization code received.</p>
                    <p>Error: ${parsedUrl.query.error || 'Unknown error'}</p>
                    <a href="/">Try again</a>
                </body>
                </html>
            `);
            return;
        }

        console.log('✅ Received authorization code:', code.substring(0, 20) + '...');
        console.log('🔄 Exchanging code for access token...\n');

        try {
            const tokenData = await exchangeCodeForToken(code);

            console.log('✅ Successfully obtained access token!');
            console.log('\n' + '='.repeat(60));
            console.log('ACCESS TOKEN (copy this for testing):');
            console.log('='.repeat(60));
            console.log(tokenData.access_token);
            console.log('='.repeat(60) + '\n');
            console.log('Refresh Token:', tokenData.refresh_token);
            console.log('Expires in:', tokenData.expires_in, 'seconds');
            console.log('\nYou can now test the API with:');
            console.log(`node test-app-api.js ${tokenData.access_token}\n`);

            res.writeHead(200, { 'Content-Type': 'text/html' });
            res.end(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>OAuth Success!</title>
                    <style>
                        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
                        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
                        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        .token { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
                        h1 { color: #28a745; }
                    </style>
                </head>
                <body>
                    <h1>✅ OAuth Authorization Successful!</h1>

                    <div class="success">
                        <strong>Your app is now authorized!</strong><br>
                        You have been granted access to the HubSpot APIs with the following scopes:
                        <ul>
                            <li>e-commerce (Products API)</li>
                            <li>CRM Objects (Contacts, Deals, Line Items)</li>
                            <li>Communication Preferences</li>
                        </ul>
                    </div>

                    <h2>Access Token</h2>
                    <div class="token">
                        <strong>Copy this token to test the API:</strong>
                        <pre id="accessToken">${tokenData.access_token}</pre>
                        <button onclick="copyToken()" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Copy to Clipboard
                        </button>
                    </div>

                    <h2>Next Steps</h2>
                    <p>Run this command to test the Products API:</p>
                    <pre>node test-app-api.js ${tokenData.access_token}</pre>

                    <h2>Token Details</h2>
                    <ul>
                        <li><strong>Expires in:</strong> ${tokenData.expires_in} seconds (${Math.floor(tokenData.expires_in / 60)} minutes)</li>
                        <li><strong>Refresh Token:</strong> ${tokenData.refresh_token.substring(0, 40)}...</li>
                        <li><strong>Token Type:</strong> ${tokenData.token_type}</li>
                    </ul>

                    <p style="color: #666; font-size: 14px;">
                        Check the terminal for the full token details.<br>
                        You can close this window and the server.
                    </p>

                    <script>
                        function copyToken() {
                            const token = document.getElementById('accessToken').textContent;
                            navigator.clipboard.writeText(token).then(() => {
                                alert('Access token copied to clipboard!');
                            });
                        }
                    </script>
                </body>
                </html>
            `);

            // Shutdown server after successful auth
            setTimeout(() => {
                console.log('Shutting down server...');
                server.close();
            }, 2000);

        } catch (error) {
            console.error('❌ Error exchanging code for token:', error.message);
            res.writeHead(500, { 'Content-Type': 'text/html' });
            res.end(`
                <html>
                <body style="font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px;">
                    <h1 style="color: red;">❌ Error</h1>
                    <p>Failed to exchange authorization code for token.</p>
                    <pre>${error.message}</pre>
                    <a href="/">Try again</a>
                </body>
                </html>
            `);
        }
    } else {
        res.writeHead(404, { 'Content-Type': 'text/plain' });
        res.end('Not found');
    }
});

// Function to exchange authorization code for access token
function exchangeCodeForToken(code) {
    return new Promise((resolve, reject) => {
        const postData = querystring.stringify({
            grant_type: 'authorization_code',
            client_id: CLIENT_ID,
            client_secret: CLIENT_SECRET,
            redirect_uri: REDIRECT_URI,
            code: code
        });

        const apiUrl = new URL(`${config.hubspot.apiBaseUrl}/oauth/v1/token`);
        const options = {
            hostname: apiUrl.hostname,
            path: apiUrl.pathname,
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Content-Length': Buffer.byteLength(postData)
            }
        };

        const req = https.request(options, (res) => {
            let data = '';

            res.on('data', (chunk) => {
                data += chunk;
            });

            res.on('end', () => {
                if (res.statusCode === 200) {
                    resolve(JSON.parse(data));
                } else {
                    reject(new Error(`HTTP ${res.statusCode}: ${data}`));
                }
            });
        });

        req.on('error', (error) => {
            reject(error);
        });

        req.write(postData);
        req.end();
    });
}

server.listen(PORT, () => {
    console.log('✅ Server started successfully!\n');
    console.log(`📝 Open your browser to: http://localhost:${PORT}\n`);
    console.log('Then click "Authorize App in HubSpot" to start the OAuth flow.\n');
});

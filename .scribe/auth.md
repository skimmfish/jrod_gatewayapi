# Authenticating requests

To authenticate requests, include a **`Authorization`** header with the value **`"Bearer AUTH_TOKEN"`** where the **`AUTH_TOKEN`** is the token returned in the response body of the /login endpoint call.

You can retrieve your token by using the 'auth_token' object in the json_decode object returned in the response body of the ** `/login` ** endpoint call. The token is returned as "auth_token: Bearer token" in the response body</b>.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below and also all endpoints requires authentication except '/login' endpoint.

**`/login`** endpoint doesn't require the Host and Connection headers


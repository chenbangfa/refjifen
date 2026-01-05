<?php
// backend/utils/jwt.php
require_once __DIR__ . '/../config/database.php';

class JWTHandler
{
    private $secret_key = "YOUR_SUPER_SECRET_KEY_CHANGE_THIS"; // In prod, use ENV
    private $alg = 'HS256';

    public function generate_jwt($data)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => $this->alg]);
        $payload = json_encode(array_merge($data, [
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7) // 7 days expiration
        ]));

        $base64UrlHeader = $this->base64url_encode($header);
        $base64UrlPayload = $this->base64url_encode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
        $base64UrlSignature = $this->base64url_encode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function validate_jwt($jwt)
    {
        try {
            $tokenParts = explode('.', $jwt);
            if (count($tokenParts) != 3)
                return null;

            $header = base64_decode($tokenParts[0]);
            $payload = base64_decode($tokenParts[1]);
            $signature_provided = $tokenParts[2];

            // Re-sign to verify
            $base64UrlHeader = $tokenParts[0];
            $base64UrlPayload = $tokenParts[1];
            $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
            $base64UrlSignature = $this->base64url_encode($signature);

            if ($base64UrlSignature === $signature_provided) {
                $decoded = json_decode($payload, true);
                if ($decoded['exp'] < time()) {
                    return null; // Expired
                }
                return $decoded;
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
?>
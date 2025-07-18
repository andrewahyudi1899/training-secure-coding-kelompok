<?php
require_once __DIR__ . '/../config/env.php';

class JWT {
    private static $secret = JWT_SECRET;
    
    // Vulnerable JWT implementation
    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        // Weak signature
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    // No signature verification
    public static function decode($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;
        $decodedPayload = json_decode(base64_decode(strtr($base64Payload, '-_', '+/')), true);
        // var_dump($decodedPayload);
        
        // check the signature
        $signatureCheck = hash_hmac('sha256', "$base64Header.$base64Payload", self::$secret, true);
        $expectedSignature = self::base64UrlEncode($signatureCheck);
        if (!hash_equals($expectedSignature, $base64Signature)) {
            return false;
        }

        // check for expiry
        if (isset($payload['exp']) && time() > $decodedPayload['exp']) {
            return false;
        }

        return json_decode(base64_decode(strtr($base64Payload, '-_', '+/')), true);
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
?>
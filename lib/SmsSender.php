<?php
/**
 * Multi-provider SMS sender for Orion school system.
 * Supported providers: twilio, unifonic, 4jawaly
 */
class SmsSender {
    private $provider;
    private $apiKey;
    private $senderName;
    private $apiUrl;
    private $accountSid; // Twilio only

    public function __construct($provider = 'twilio') {
        $this->provider  = $provider;
        $this->apiKey    = getSetting('sms_api_key', '');
        $this->senderName = getSetting('sms_sender_name', 'Orion');
        $this->apiUrl    = getSetting('sms_api_url', '');
        $this->accountSid = getSetting('twilio_account_sid', '');
    }

    public function send($to, $message) {
        $to = $this->normalizeNumber($to);
        if (!$to) {
            return ['success' => false, 'error' => 'رقم هاتف غير صالح'];
        }

        switch ($this->provider) {
            case 'twilio':
                return $this->sendTwilio($to, $message);
            case 'unifonic':
                return $this->sendUnifonic($to, $message);
            case '4jawaly':
                return $this->send4jawaly($to, $message);
            default:
                return ['success' => false, 'error' => 'مزود خدمة غير معروف: ' . $this->provider];
        }
    }

    private function sendTwilio($to, $message) {
        if (!$this->accountSid || !$this->apiKey) {
            return ['success' => false, 'error' => 'Twilio Account SID أو Auth Token غير مضبوط'];
        }
        $from = $this->senderName;
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
        $data = [
            'From' => $from,
            'To'   => $to,
            'Body' => $message,
        ];
        $auth = base64_encode("{$this->accountSid}:{$this->apiKey}");

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'Twilio error: ' . $error];
        }
        $result = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && !empty($result['sid'])) {
            return ['success' => true, 'id' => $result['sid'], 'provider' => 'twilio'];
        }
        $errMsg = $result['message'] ?? $result['error_message'] ?? 'Unknown Twilio error';
        return ['success' => false, 'error' => $errMsg];
    }

    private function sendUnifonic($to, $message) {
        if (!$this->apiKey) {
            return ['success' => false, 'error' => 'Unifonic API Key غير مضبوط'];
        }
        $url = $this->apiUrl ?: 'https://api.unifonic.com/rest/Messages/Send';
        $data = [
            'AppSid' => $this->apiKey,
            'SenderID' => $this->senderName,
            'Recipient' => $to,
            'Body' => $message,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'Unifonic error: ' . $error];
        }
        $result = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && ($result['success'] ?? false)) {
            return ['success' => true, 'id' => $result['data']['MessageID'] ?? '', 'provider' => 'unifonic'];
        }
        $errMsg = $result['message'] ?? $result['error']['message'] ?? 'Unifonic error';
        return ['success' => false, 'error' => $errMsg];
    }

    private function send4jawaly($to, $message) {
        if (!$this->apiKey) {
            return ['success' => false, 'error' => '4jawaly API Key غير مضبوط'];
        }
        $url = $this->apiUrl ?: 'https://api.4jawaly.com/api/v1/sms/send';
        $payload = json_encode([
            'messages' => [
                [
                    'sender' => $this->senderName,
                    'text'   => $message,
                    'number' => $to,
                ],
            ],
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => '4jawaly error: ' . $error];
        }
        $result = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && ($result['success'] ?? false)) {
            return ['success' => true, 'id' => $result['data']['id'] ?? '', 'provider' => '4jawaly'];
        }
        $errMsg = $result['message'] ?? $result['error'] ?? '4jawaly error';
        return ['success' => false, 'error' => $errMsg];
    }

    private function normalizeNumber($number) {
        $number = preg_replace('/[^0-9]/', '', $number);
        if (strlen($number) < 7) return null;
        if (substr($number, 0, 2) === '00') $number = substr($number, 2);
        if (substr($number, 0, 1) === '0') $number = '964' . substr($number, 1);
        if (substr($number, 0, 2) !== '00' && substr($number, 0, 1) !== '+') {
            if (!preg_match('/^[1-9]\d{1,14}$/', $number)) {
                $number = '964' . ltrim($number, '0');
            }
        }
        return '+' . $number;
    }
}

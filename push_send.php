<?php
// FCM V1 API ile Web Push gönderme

function nargile_bildirim_gonder($conn, $masa_no, $urun_listesi) {

    $service_account = [
        "type"                        => "service_account",
        "project_id"                  => "macakizicafe-b3747",
        "private_key_id"              => "2344357ddad2412af7708fa0ab296b89f230783f",
        "private_key"                 => "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDZOvJ5COwnEE9f\nfaWwNyHXCpKE+vUMXlwfqZ15+M1YsWk5/fiOMVBzsfgSQmxEbIXusAbqqCRpXqGk\nsTpgRrFC9hFCos75HOkhbUa224lS+AIFpGNhqscQrWoQ5WkvD3LLZRXIqJoYAuKu\nDRi5s5CTXUS/KHpwuLMUeS1l69Vod0ITFu64NvqIib53FS2egizogmrHWdNqpNJz\nBo5pEhrpcmu337y7V8ACFDz4z/V/3oPtROgsFLZgt+x2sqMmOYd0TSb9L2P1otuD\ndEFh41SiYaMvpgFqZBAjddtuOF1u8m+zVDlgzQcZEUDQshDaex1cN7C3Mk0ukxDm\ny9Mb/EpDAgMBAAECggEAMIBNUAHSfDxThH4QxuHgX9t/8En5+Yt95SHhQ7Dt5E4k\nMOsIGnNfQ52qRiVnd/WFB9Bskur8yjvzOkrJvmI6RLmdC/Q+4vb2BF0aK0ykAg7W\noLzd5ZoUPSCC7IfULsoztr74mKcwVIkcZIEPErNJZeWuqkGW6OEMNteBb96ydNK2\nWIz1dr52PcfBHvxEg/e0cu84gOdnXS9kXPyfX/H0wq5Alz+4Dbqws1XPoHnPzVo0\naSE0i8ZG1cxq4es0aBFt8tulVPRvHPJ2/fppMBcROC+kRI33c9VAqb/A4x2nD0ku\niBr0sr19tjOa0T5H1NRvwSNBMl3NRY0y6Xrj2lXfoQKBgQD2lm0/IGQasXWaGpDd\n0hUHuYvVTTht4E1LvgzsE19Hf75y/b2qYkDA5wxfOFaLoqtSXDccc2zIZjZw1B3i\njeULqGuGleehZIZjhkdItSuikDF7oEXiz29US9jHBEjr3y6SjkAjw/Hapij25hlM\nQZ6041BfAqNJtabggflf4Ts/oQKBgQDhhaZsO4vKjYmJiFO372nAq6Y2KRXIqJG9\nsCNPcxf2aynk1/mDJCCVzQlWJAhIt2I+HRfPC7fQPDa2r0FAFBp4B6cDlSF/Ih6h\nZIEunxWR6QYqpz5jc+ueaAevWr+ra2xSD5gtaWG0YA806ns3kwWHgfUOTXM6hh1V\nf4yzA3BPYwKBgQCEA3itSRwOpl79h6dy+hvELPPN2C+Ts3fuSusEIELsNlmxmmLG\nKx/bplabS8AZtZbe2vuuQaVFjGMs+IKfXbP4D8yxGuQNJZJFCghzxnp755J2SxHf\nIYrKqGh/861OigeW8o0oGKkMk9RuijWU+7SJxwCqPPFKbvPOUgc964kawQKBgAoy\nM7MDAi/3NDeunTJctahLWmlqdBytWmF8HCyUZIn0kGFTTmmacYq0SW7GlEVNXTi3\nsgLfYcEdZ8/cnpOaFRRGLEviKzCHR2E+nQSRlrURFwFIrG5sqENyKp7H+JB0e+I9\n6EBtWkiAa0+WJln94KseugiagdIsjBj4elEBn8tHAoGBAIt/VVRZMEP8rZcvvTQo\nfPews7ZVHcHcTwpcNm/wOdwEBYP9/Z8WP4eKqAE/svePZPCy7ev7eR3kpla5CwzR\nrfXC6lX/chj+H8AyjZr/nFpVi/Qd8UnuJkjQLeWpJk+SA0gQrtfYr221/70v/L3T\nupdMYaIrARhYGuRUFbzqmntX\n-----END PRIVATE KEY-----\n",
        "client_email"                => "firebase-adminsdk-fbsvc@macakizicafe-b3747.iam.gserviceaccount.com",
        "token_uri"                   => "https://oauth2.googleapis.com/token",
    ];

    $access_token = _get_fcm_access_token($service_account);
    if (!$access_token) return;

    $title = '🪔 Masa ' . $masa_no . ' — Nargile!';
    $body  = implode(', ', $urun_listesi);

    $subs = $conn->query("SELECT * FROM push_subscriptions");
    if (!$subs || $subs->num_rows === 0) return;

    while ($row = $subs->fetch_assoc()) {
        _fcm_send($access_token, $row['endpoint'], $row['p256dh'], $row['auth'],
                  $title, $body, $service_account['project_id'], $conn);
    }
}

// Debug versiyonu — push_test.php için, tek bir subscription alır ve sonucu döner
function nargile_bildirim_gonder_debug($conn, $masa_no, $urun_listesi, $row) {

    $service_account = [
        "type"                        => "service_account",
        "project_id"                  => "macakizicafe-b3747",
        "private_key_id"              => "2344357ddad2412af7708fa0ab296b89f230783f",
        "private_key"                 => "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDZOvJ5COwnEE9f\nfaWwNyHXCpKE+vUMXlwfqZ15+M1YsWk5/fiOMVBzsfgSQmxEbIXusAbqqCRpXqGk\nsTpgRrFC9hFCos75HOkhbUa224lS+AIFpGNhqscQrWoQ5WkvD3LLZRXIqJoYAuKu\nDRi5s5CTXUS/KHpwuLMUeS1l69Vod0ITFu64NvqIib53FS2egizogmrHWdNqpNJz\nBo5pEhrpcmu337y7V8ACFDz4z/V/3oPtROgsFLZgt+x2sqMmOYd0TSb9L2P1otuD\ndEFh41SiYaMvpgFqZBAjddtuOF1u8m+zVDlgzQcZEUDQshDaex1cN7C3Mk0ukxDm\ny9Mb/EpDAgMBAAECggEAMIBNUAHSfDxThH4QxuHgX9t/8En5+Yt95SHhQ7Dt5E4k\nMOsIGnNfQ52qRiVnd/WFB9Bskur8yjvzOkrJvmI6RLmdC/Q+4vb2BF0aK0ykAg7W\noLzd5ZoUPSCC7IfULsoztr74mKcwVIkcZIEPErNJZeWuqkGW6OEMNteBb96ydNK2\nWIz1dr52PcfBHvxEg/e0cu84gOdnXS9kXPyfX/H0wq5Alz+4Dbqws1XPoHnPzVo0\naSE0i8ZG1cxq4es0aBFt8tulVPRvHPJ2/fppMBcROC+kRI33c9VAqb/A4x2nD0ku\niBr0sr19tjOa0T5H1NRvwSNBMl3NRY0y6Xrj2lXfoQKBgQD2lm0/IGQasXWaGpDd\n0hUHuYvVTTht4E1LvgzsE19Hf75y/b2qYkDA5wxfOFaLoqtSXDccc2zIZjZw1B3i\njeULqGuGleehZIZjhkdItSuikDF7oEXiz29US9jHBEjr3y6SjkAjw/Hapij25hlM\nQZ6041BfAqNJtabggflf4Ts/oQKBgQDhhaZsO4vKjYmJiFO372nAq6Y2KRXIqJG9\nsCNPcxf2aynk1/mDJCCVzQlWJAhIt2I+HRfPC7fQPDa2r0FAFBp4B6cDlSF/Ih6h\nZIEunxWR6QYqpz5jc+ueaAevWr+ra2xSD5gtaWG0YA806ns3kwWHgfUOTXM6hh1V\nf4yzA3BPYwKBgQCEA3itSRwOpl79h6dy+hvELPPN2C+Ts3fuSusEIELsNlmxmmLG\nKx/bplabS8AZtZbe2vuuQaVFjGMs+IKfXbP4D8yxGuQNJZJFCghzxnp755J2SxHf\nIYrKqGh/861OigeW8o0oGKkMk9RuijWU+7SJxwCqPPFKbvPOUgc964kawQKBgAoy\nM7MDAi/3NDeunTJctahLWmlqdBytWmF8HCyUZIn0kGFTTmmacYq0SW7GlEVNXTi3\nsgLfYcEdZ8/cnpOaFRRGLEviKzCHR2E+nQSRlrURFwFIrG5sqENyKp7H+JB0e+I9\n6EBtWkiAa0+WJln94KseugiagdIsjBj4elEBn8tHAoGBAIt/VVRZMEP8rZcvvTQo\nfPews7ZVHcHcTwpcNm/wOdwEBYP9/Z8WP4eKqAE/svePZPCy7ev7eR3kpla5CwzR\nrfXC6lX/chj+H8AyjZr/nFpVi/Qd8UnuJkjQLeWpJk+SA0gQrtfYr221/70v/L3T\nupdMYaIrARhYGuRUFbzqmntX\n-----END PRIVATE KEY-----\n",
        "client_email"                => "firebase-adminsdk-fbsvc@macakizicafe-b3747.iam.gserviceaccount.com",
        "token_uri"                   => "https://oauth2.googleapis.com/token",
    ];

    $access_token = _get_fcm_access_token($service_account);
    if (!$access_token) {
        return ['http' => 0, 'response' => 'ACCESS TOKEN ALINAMADI — Service Account JWT hatası'];
    }

    $title = '🪔 Masa ' . $masa_no . ' — Nargile!';
    $body  = implode(', ', $urun_listesi);

    return _fcm_send_debug($access_token, $row['endpoint'], $title, $body, $service_account['project_id']);
}

function _get_fcm_access_token($sa) {
    $now = time();
    $header  = _b64url(json_encode(['alg'=>'RS256','typ'=>'JWT']));
    $payload = _b64url(json_encode([
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud'   => $sa['token_uri'],
        'iat'   => $now,
        'exp'   => $now + 3600,
    ]));
    $signing_input = $header . '.' . $payload;
    $key = openssl_pkey_get_private($sa['private_key']);
    if (!$key) return null;
    openssl_sign($signing_input, $sig, $key, 'SHA256');
    $jwt = $signing_input . '.' . _b64url($sig);

    $ch = curl_init($sa['token_uri']);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res  = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    return $data['access_token'] ?? null;
}

function _fcm_send($token, $endpoint, $p256dh, $auth, $title, $body, $project_id, $conn) {
    $reg_token = substr($endpoint, strrpos($endpoint, '/') + 1);

    $payload = json_encode([
        'message' => [
            'token' => $reg_token,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'webpush' => [
                'notification' => [
                    'title'              => $title,
                    'body'               => $body,
                    'icon'               => '/uploads/masalar/normalmasa.png',
                    'requireInteraction' => true,
                    'vibrate'            => [300, 100, 300],
                ],
            ],
        ],
    ]);

    $url = 'https://fcm.googleapis.com/v1/projects/' . $project_id . '/messages:send';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res  = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Geçersiz token ise sil
    if ($http === 404 || $http === 410) {
        $ep = $conn->real_escape_string($endpoint);
        $conn->query("DELETE FROM push_subscriptions WHERE endpoint='$ep'");
    }
}

// Debug versiyonu — yanıtı geri döner, silmez
function _fcm_send_debug($token, $endpoint, $title, $body, $project_id) {
    $reg_token = substr($endpoint, strrpos($endpoint, '/') + 1);

    $payload = json_encode([
        'message' => [
            'token' => $reg_token,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'webpush' => [
                'notification' => [
                    'title'              => $title,
                    'body'               => $body,
                    'icon'               => '/uploads/masalar/normalmasa.png',
                    'requireInteraction' => true,
                    'vibrate'            => [300, 100, 300],
                ],
            ],
        ],
    ]);

    $url = 'https://fcm.googleapis.com/v1/projects/' . $project_id . '/messages:send';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res  = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['http' => $http, 'response' => $res];
}

function _b64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

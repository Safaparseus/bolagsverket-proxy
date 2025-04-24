<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$orgnr = $_GET['orgnr'] ?? null;
if (!$orgnr) {
    http_response_code(400);
    echo json_encode(['error' => 'Måste ange ?orgnr=...']);
    exit;
}

$token_url = "https://portal.api.bolagsverket.se/oauth2/token";
$token_body = http_build_query([
    'grant_type' => 'client_credentials',
    'client_id' => 'fRsqrvM327JzhCpCSETfkCr58LMa',
    'client_secret' => 'iApgA0TpPNBDf0BvnRelOn3ga8Qa',
    'scope' => 'vardefulla-datamangder:read vardefulla-datamangder:ping'
]);

$token_options = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-type: application/x-www-form-urlencoded",
        'content' => $token_body,
        'timeout' => 10
    ]
];
$context  = stream_context_create($token_options);
$token_response = file_get_contents($token_url, false, $context);
$token_data = json_decode($token_response, true);

$access_token = $token_data['access_token'] ?? null;
if (!$access_token) {
    http_response_code(500);
    echo json_encode(['error' => 'Token kunde inte hämtas', 'debug' => $token_data]);
    exit;
}

$doclist_url = "https://gw.api.bolagsverket.se/vardefulla-datamangder/v1/dokumentlista";
$doclist_data = json_encode(['organisationsnummer' => $orgnr]);
$doclist_options = [
    'http' => [
        'method'  => 'POST',
        'header'  => [
            "Authorization: Bearer $access_token",
            "Content-Type: application/json"
        ],
        'content' => $doclist_data,
        'timeout' => 10
    ]
];
$context = stream_context_create($doclist_options);
$doclist_response = file_get_contents($doclist_url, false, $context);
$doclist = json_decode($doclist_response, true);

$first_doc_id = $doclist['dokumentlista'][0]['dokumentId'] ?? null;
if (!$first_doc_id) {
    http_response_code(404);
    echo json_encode(['error' => 'Inga dokument hittades', 'debug' => $doclist]);
    exit;
}

$doc_url = "https://gw.api.bolagsverket.se/vardefulla-datamangder/v1/dokument/$first_doc_id";
$opts = [
    'http' => [
        'method'  => 'GET',
        'header'  => "Authorization: Bearer $access_token",
        'timeout' => 10
    ]
];
$context = stream_context_create($opts);
$doc_response = file_get_contents($doc_url, false, $context);

echo $doc_response;


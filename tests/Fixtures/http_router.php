<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
header('Content-Type: application/json');
switch ($path) {
    case '/invalid-json': echo '{'; break;
    case '/array': echo '[]'; break;
    case '/http-error': http_response_code(503); echo '{"status":"error","message":"Unavailable"}'; break;
    case '/redirect': http_response_code(302); header('Location: /ok'); echo '{}'; break;
    case '/empty': http_response_code(204); break;
    case '/slow': sleep(2); echo '{"status":"success"}'; break;
    default:
        echo json_encode(['status'=>'success','method'=>$_SERVER['REQUEST_METHOD'],
            'content_type'=>$_SERVER['CONTENT_TYPE'] ?? '', 'parameters'=>$_POST,
            'authorization'=>$_SERVER['HTTP_AUTHORIZATION'] ?? null],JSON_THROW_ON_ERROR);
}

<?php

$app->get('/api/health', function ($request, $response) {
    $response->getBody()->write(json_encode([
        "status" => "ok",
        "message" => "CampusEats API is running"
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});
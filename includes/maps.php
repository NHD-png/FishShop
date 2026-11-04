<?php
require_once __DIR__ . '/location.php';

function fishshop_maps_config(): array
{
    return require __DIR__ . '/../config/maps.php';
}

function fishshop_store_point(array $config): ?array
{
    try {
        return shipping_location_from_values($config['store_latitude'], $config['store_longitude']);
    } catch (DomainException $ex) {
        return null;
    }
}

function fishshop_store_directions_url(array $config): string
{
    $point = fishshop_store_point($config);
    $destination = $point ? $point['latitude'] . ',' . $point['longitude'] : $config['store_address'];
    return 'https://www.google.com/maps/dir/?' . http_build_query(['api' => '1', 'destination' => $destination], '', '&', PHP_QUERY_RFC3986);
}

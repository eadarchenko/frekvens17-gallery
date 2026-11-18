<?php

return [
    'hosted_demo' => env('SHOP_HOSTED_DEMO', false),
    // Deliberately disabled until the artist confirms commercial terms.
    'demo' => (bool) env('SHOP_DEMO', false),
    'mode' => env('SHOP_CHECKOUT_MODE', 'disabled'),
    'currency' => env('SHOP_CURRENCY'),
    'shipping_cents' => env('SHOP_TEST_SHIPPING_CENTS', 0),
    'tax_basis_points' => env('SHOP_TEST_TAX_BASIS_POINTS', 0),
];

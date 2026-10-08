<?php

use App\Services\Payments\CashOnDeliveryGateway;
use App\Services\Payments\StripeGateway;

return [
    /*
    | Registered payment gateways (key => class). To add Moyasar, Tap, PayTabs, Apple Pay or
    | STC Pay later, implement App\Contracts\PaymentGateway and register it here.
    */
    'gateways' => [
        'cod' => CashOnDeliveryGateway::class,
        'stripe' => StripeGateway::class,
    ],
];

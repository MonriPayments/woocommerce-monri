<?php

require_once __DIR__ . '/monri-api.php';

$monri = new MonriApi();
$monri->resolvePaymentStatus($_GET['payment_token']); 

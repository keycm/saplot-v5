<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $amount = $_POST['amount'] ?? 0;

  if ($amount < 100) {
    die("Amount must be at least PHP 100.00");
  }

  $secret_key = 'sk_test_Nwnbxn5jUyjGn2nNFZuMLuZS';

  $data = [
    "data" => [
      "attributes" => [
        "line_items" => [[
          "currency" => "PHP",
          "amount" => intval($amount * 100), // Convert to centavos
          "name" => "Saplot Order",
          "quantity" => 1
        ]],
        "payment_method_types" => ["gcash"],
        "description" => "GCash Order from Saplot de Manila",
        "success_url" => "http://localhost/saplot-69/saplot-69/success.html",
        "cancel_url" => "http://localhost/saplot-69/saplot-69/failed.html"
      ]
    ]
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/checkout_sessions");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($ch, CURLOPT_USERPWD, $secret_key . ":");
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

  $response = curl_exec($ch);
  curl_close($ch);

  $result = json_decode($response, true);
  $checkout_url = $result['data']['attributes']['checkout_url'] ?? null;

  if ($checkout_url) {
    header("Location: $checkout_url");
    exit;
  } else {
    echo "Error creating GCash payment. Please try again.";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
  }
}
?>
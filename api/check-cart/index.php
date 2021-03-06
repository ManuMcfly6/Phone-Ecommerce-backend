
<?php

header('Access-Control-Allow-Origin: https://ecommerce-client-test.herokuapp.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding,Origin,X-Requested-width,Accept');
header('Content-type:application/json;charset=utf-8');

include  "../../dotenvLoader.php"; //only in dev mode

if (isset($_POST['checkCart']) && isset($_POST['checkTotal'])&&isset($_POST['debtId'])) {
 
    $cart_raw = $_POST['checkCart'];
    $debt = $_POST['checkTotal'];
    $id_debt = $_POST['debtId'];
    $api_url = 'https://staging.adamspay.com/api/v1/debts?update_if_exists=1';
    $api_key= $_ENV['API_KEY']; //GET ENV
    

    //the true 2d arg allow to use as an array
    $cart_info = json_decode($cart_raw, true);


    $label = '';
    

    $comma_counter = 1;

    foreach ($cart_info as $product) {

        $comma = count($cart_info) > $comma_counter ? "," : "";

        $label = $label . $product['title'] . $comma;

        $comma_counter++;
    }



    /*-----create debt-----*/
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $end = $now->add(new DateInterval('P3D'));

    //model of debt
    $debt = [
        'docId' => $id_debt,
        'label' => $label,
        'amount' => ['currency' => 'PYG', 'value' => $debt],
        'validPeriod' => [
            'start' => $now->format(DateTime::ATOM),
            'end' => $end->format(DateTime::ATOM)
        ]
    ];

    //create JSON for post
    $post = json_encode(['debt' => $debt]);

    //make post
    $curl = curl_init();
    

    curl_setopt_array($curl, [
        CURLOPT_URL => $api_url,
        CURLOPT_HTTPHEADER => ['apikey: ' . $api_key, 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $post
    ]);

    $response = curl_exec($curl);


    if ($response) {
        $data = json_decode($response, true);



        $payUrl = isset($data['debt']) ? $data['debt']['payUrl'] : null;

        if ($payUrl) {
            echo json_encode(array(
                'status' => "Deuda creada exitosamente",
                'url' => $payUrl

            ));
        } else {
            echo json_encode(array(
                'status' => "No se pudo crear la deuda",
                'error' => $data['meta']
            ));
        }
    } else {

        echo 'curl_error: ', curl_error($curl);
    }

    curl_close($curl);
} else {

    die();
}

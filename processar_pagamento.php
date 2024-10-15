<?php
// Access Token do Mercado Pago
$access_token = 'APP_USR-1467490049744598-100818-ffa4f18f828f0907e91a03d3c78a8302-512066256';

// Iniciar sessão para armazenar os dados do usuário
session_start();

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Armazenar os dados do formulário na sessão
    $_SESSION['nick'] = $_POST['nick'];
    $_SESSION['id'] = $_POST['id'];
    $_SESSION['bits'] = intval($_POST['bits']);
    $valor = $_SESSION['bits'] * 1.00; // 1 bit = 1 real

    // Configurar a preferência de pagamento
    $preference_data = [
        'items' => [
            [
                'title' => 'Compra de Bits',
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => $valor
            ]
        ],
        'external_reference' => json_encode([
            'nick' => $_SESSION['nick'],
            'id' => $_SESSION['id'],
            'bits' => $_SESSION['bits']
        ]),
        'payer' => [
            'name' => $_SESSION['nick'],
        ],
        'back_urls' => [
            'success' => 'https://f069-2804-14d-1c8b-943a-c1fe-16bb-1fb7-e6c1.ngrok-free.app/abc/sucesso.html',
            'failure' => ' https://f069-2804-14d-1c8b-943a-c1fe-16bb-1fb7-e6c1.ngrok-free.app/abc/falha.php',
            'pending' => 'http://seusite.com/pendente.php'
        ],
        'auto_return' => 'approved',
        'notification_url' => ' https://f069-2804-14d-1c8b-943a-c1fe-16bb-1fb7-e6c1.ngrok-free.app/abc/processar_pagamento.php',
        'payment_methods' => [
            'installments' => 1 // Limitar a 1 parcela
        ]
    ];

    // Enviar requisição para a API do Mercado Pago
    $url = 'https://api.mercadopago.com/checkout/preferences';
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                        "Authorization: Bearer $access_token\r\n",
            'content' => json_encode($preference_data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $response_data = json_decode($response, true);

    // Redirecionar para o pagamento
    if (isset($response_data['init_point'])) {
        header("Location: " . $response_data['init_point']);
        exit;
    } else {
        echo "Erro ao gerar a preferência de pagamento.";
    }
}

// Processar a notificação do pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);

    // Verificar se é uma notificação de pagamento
    if (isset($input['data']['id'])) {
        $payment_id = $input['data']['id'];

        // Recuperar informações do pagamento
        $url = "https://api.mercadopago.com/v1/payments/{$payment_id}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $payment_info = json_decode($response, true);

        // Se o pagamento foi aprovado, registrar os bits no banco
        if (isset($payment_info['status']) && $payment_info['status'] == 'approved') {
            // Recuperar dados do external_reference
            $external_reference = json_decode($payment_info['external_reference'], true);

            // Conectar ao banco de dados
            $host = "190.102.40.7";
            $banco = "rafaelc_irp";
            $user = "rafaelc_2739";
            $senha_user = "AVuWCST06!Pr";
            
            $con = mysqli_connect($host, $user, $senha_user, $banco);

            if (!$con) {
                die("Falha na conexão: " . mysqli_connect_error());
            }

            // Inserir os dados no banco
            $nick = $external_reference['nick'];
            $id = $external_reference['id'];
            $bits = $external_reference['bits'];
            $sql = "INSERT INTO irp (nome, id, bits) VALUES ('$nick', '$id', '$bits')";

            if (mysqli_query($con, $sql)) {
                echo "Bits atualizados com sucesso!";
            } else {
                echo "Erro ao atualizar bits: " . mysqli_error($con);
            }

            mysqli_close($con);
        } else {
            // Logar erro se o pagamento não foi aprovado
            file_put_contents('log.txt', "Erro: pagamento não aprovado ou resposta inválida.\n", FILE_APPEND);
        }
    }
}

// Log para depuração
file_put_contents('log.txt', print_r($input, true), FILE_APPEND);
?>

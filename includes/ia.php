<?php
// ===============================================================
//  ARCHIVO: includes/ia.php
//  PROPOSITO: Llamar a la API de OpenAI (ChatGPT) usando cURL.
//  MODELO SUGERIDO: gpt-4.1-mini o el que tú decidas
// ===============================================================

if (!defined('OPENAI_API_KEY')) {
    // Si no tienes esta constante en config.php, puedes activarla aquí:
    // define('OPENAI_API_KEY', 'TU_API_KEY_AQUI');
}

/**
 * Llama a OpenAI para generar un informe psicopedagógico.
 *
 * @param string $systemPrompt
 * @param array  $perfilData   (array de datos que enviamos como JSON)
 * @return string  Texto del informe generado
 */
function generarInformeIA(string $systemPrompt, array $perfilData): string
{
    if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY == "") {
        throw new Exception("No se encontró OPENAI_API_KEY en config.php");
    }

    $url = "https://api.openai.com/v1/chat/completions";

    // Construir payload
    $payload = [
        "model" => "gpt-4.1-mini",  // puedes cambiar a otro modelo
        "temperature" => 0.2,
        "messages" => [
            [
                "role" => "system",
                "content" => $systemPrompt
            ],
            [
                "role"  => "user",
                "content" => json_encode($perfilData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ]
        ]
    ];

    // Inicializar CURL
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . OPENAI_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception("Error CURL: " . curl_error($ch));
    }

    curl_close($ch);

    $json = json_decode($response, true);

    if (!isset($json['choices'][0]['message']['content'])) {
        throw new Exception("Respuesta inesperada de OpenAI: " . $response);
    }

    return $json['choices'][0]['message']['content'];
}

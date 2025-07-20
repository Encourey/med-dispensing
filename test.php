<!-- <?php
$url = "http://localhost/logins/process_dispense_esp.php";
    $postData = [
        'patient_id' => 1,
        'medication_id' => [2,3,5],
        // 'dosage' => $total_dosage
    ];

    $postData = json_encode($postData);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch); // Capture the response
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE); // HTTP status code

    echo $http_status;
    echo $response;
    print_r($postData);

    curl_close($ch);

?>  -->
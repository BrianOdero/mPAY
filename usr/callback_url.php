<?php
    header("Content-Type: application/json");

    $response = '{
        "ResultCode": 0, 
        "ResultDesc": "Confirmation Received Successfully"
    }';

    // Receive the M-PESA response
    $mpesaResponse = file_get_contents('php://input');
    $logFile = "M_PESAConfirmationResponse.txt";
    $log = fopen($logFile, "a");

    // Log the M-PESA response
    fwrite($log, $mpesaResponse);
    fclose($log);

    // Decode the JSON M-PESA response
    $mpesaResponse = json_decode($mpesaResponse, true);

    // Extract useful information from the M-PESA response
    $resultCode = $mpesaResponse['Body']['stkCallback']['ResultCode'];
    $amount = $mpesaResponse['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
    $mpesaReceiptNumber = $mpesaResponse['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
    $phoneNumber = $mpesaResponse['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];

    // Check if the transaction was successful
    if ($resultCode == 0) {
        // Transaction was successful, update the booking status in the database

        // Include the database configuration file
        include('vendor/inc/config.php');

        // Prepare SQL statement to update booking status
        $query = "UPDATE tms_user SET u_car_book_status = 'Pending' WHERE u_phone = ? AND u_car_book_status = ''";

        // Prepare the statement
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('s', $phoneNumber);

        // Execute the query
        $stmt->execute();

        // Check if the booking was updated successfully
        if ($stmt->affected_rows > 0) {
            // Booking confirmed successfully
            fwrite($log, "Booking confirmed successfully for phone number: $phoneNumber\n");
        } else {
            // Booking confirmation failed
            fwrite($log, "Booking confirmation failed for phone number: $phoneNumber\n");
        }
    } else {
        // Transaction failed, log the failure
        fwrite($log, "Transaction failed for phone number: $phoneNumber, ResultCode: $resultCode\n");
    }

    // Respond to Safaricom to confirm receipt of the transaction
    echo $response;
?>
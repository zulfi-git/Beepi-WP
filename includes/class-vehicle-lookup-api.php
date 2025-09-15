<?php
class VehicleLookupAPI {
    
    /**
     * Make API request to worker
     */
    public function lookup($regNumber) {
        $start_time = microtime(true);
        
        // Get worker URL and timeout from admin settings
        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        $timeout = get_option('vehicle_lookup_timeout', 15);
        
        $response = wp_remote_post($worker_url . '/lookup', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Origin' => get_site_url()
            ),
            'body' => json_encode(array(
                'registrationNumber' => $regNumber
            )),
            'timeout' => $timeout
        ));

        $response_time = round((microtime(true) - $start_time) * 1000);
        
        return array(
            'response' => $response,
            'response_time' => $response_time
        );
    }

    /**
     * Validate Norwegian registration number format
     */
    public function validate_registration_number($regNumber) {
        return Vehicle_Lookup_Helpers::validate_registration_number($regNumber);
    }

    /**
     * Process API response with proper error handling
     */
    public function process_response($response, $regNumber) {
        if (is_wp_error($response)) {
            return array('error' => 'Tilkoblingsfeil. Prøv igjen om litt.', 'failure_type' => 'connection_error');
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return array('error' => 'Tjenesten er ikke tilgjengelig for øyeblikket. Prøv igjen senere.', 'failure_type' => 'http_error');
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON Decode Error: ' . json_last_error_msg());
            return array('error' => 'Ugyldig svar fra server. Prøv igjen.', 'failure_type' => 'http_error');
        }

        if (empty($data)) {
            error_log('Empty Data Response for: ' . $regNumber);
            return array('error' => 'Fant ingen kjøretøyinformasjon for dette registreringsnummeret', 'failure_type' => 'http_error');
        }

        // Check if API returned an error in the response data
        if (isset($data['responser']) && is_array($data['responser'])) {
            foreach ($data['responser'] as $respons) {
                if (isset($respons['feilmelding'])) {
                    $error_code = $respons['feilmelding'];

                    // Map API error codes to user-friendly messages
                    switch ($error_code) {
                        case 'KJENNEMERKE_UKJENT':
                            return array('error' => 'Registreringsnummeret finnes ikke i det norske kjøretøyregisteret', 'failure_type' => 'invalid_plate');
                        case 'KJENNEMERKE_UGYLDIG':
                            return array('error' => 'Ugyldig registreringsnummer format', 'failure_type' => 'invalid_plate');
                        case 'TJENESTE_IKKE_TILGJENGELIG':
                            return array('error' => 'Vegvesenets tjeneste er ikke tilgjengelig for øyeblikket', 'failure_type' => 'http_error');
                        default:
                            return array('error' => 'Kunne ikke hente kjøretøyinformasjon. Sjekk at registreringsnummeret er korrekt', 'failure_type' => 'invalid_plate');
                    }
                }

                // Check if we have valid vehicle data
                if (!isset($respons['kjoretoydata']) || empty($respons['kjoretoydata'])) {
                    return array('error' => 'Fant ingen kjøretøyinformasjon for dette registreringsnummeret', 'failure_type' => 'invalid_plate');
                }
            }
        }

        return array('success' => true, 'data' => $data);
    }
}

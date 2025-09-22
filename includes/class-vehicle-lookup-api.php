<?php
class VehicleLookupAPI {
    
    /**
     * Phase 1: Get immediate vehicle data from worker
     * 
     * @param string $regNumber Registration number
     * @param bool $includeSummary Whether to start AI summary generation (default: false)
     */
    public function lookup($regNumber, $includeSummary = false) {
        $start_time = microtime(true);
        
        // Get worker URL and timeout from admin settings
        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        $timeout = get_option('vehicle_lookup_timeout', 15);
        
        // Build request body - includeSummary triggers background AI generation
        $request_body = array(
            'registrationNumber' => $regNumber,
            'includeSummary' => $includeSummary
        );
        
        $response = wp_remote_post($worker_url . '/lookup', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Origin' => get_site_url()
            ),
            'body' => json_encode($request_body),
            'timeout' => $timeout
        ));

        $response_time = round((microtime(true) - $start_time) * 1000);
        
        return array(
            'response' => $response,
            'response_time' => $response_time
        );
    }

    /**
     * Phase 2: Poll for AI summary generation status
     * 
     * @param string $regNumber Registration number
     */
    public function poll_ai_summary($regNumber) {
        $start_time = microtime(true);
        
        // Get worker URL and timeout from admin settings
        $worker_url = get_option('vehicle_lookup_worker_url', VEHICLE_LOOKUP_WORKER_URL);
        $timeout = get_option('vehicle_lookup_timeout', 15);
        
        $response = wp_remote_get($worker_url . '/ai-summary/' . urlencode($regNumber), array(
            'headers' => array(
                'Origin' => get_site_url()
            ),
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
     * Process API response with proper error handling for structured Cloudflare Worker responses
     */
    public function process_response($response, $regNumber) {
        if (is_wp_error($response)) {
            return array(
                'error' => 'Tilkoblingsfeil. Prøv igjen om litt.',
                'failure_type' => 'connection_error',
                'code' => 'CONNECTION_ERROR',
                'correlation_id' => null
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Try to parse JSON response first to check for structured errors
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON Decode Error: ' . json_last_error_msg());
            return array(
                'error' => 'Ugyldig svar fra server. Prøv igjen.',
                'failure_type' => 'http_error',
                'code' => 'INVALID_JSON',
                'correlation_id' => null
            );
        }

        // Handle new error response format from worker
        if (isset($data['error']) && is_array($data['error']) && isset($data['error']['code'])) {
            // New error format: { "error": { "code": "...", "message": "...", "correlationId": "..." } }
            $error_data = $data['error'];
            $correlation_id = isset($error_data['correlationId']) ? $error_data['correlationId'] : null;
            
            // Validate correlation ID format if present
            if ($correlation_id && !Vehicle_Lookup_Helpers::is_valid_correlation_id($correlation_id)) {
                error_log('Invalid correlation ID format: ' . $correlation_id);
                $correlation_id = null;
            }
            
            $failure_type = $this->map_error_code_to_failure_type($error_data['code']);
            
            return array(
                'error' => $error_data['message'],
                'failure_type' => $failure_type,
                'code' => $error_data['code'],
                'correlation_id' => $correlation_id,
                'timestamp' => isset($error_data['timestamp']) ? $error_data['timestamp'] : null
            );
        }
        
        // Handle legacy structured error responses (backward compatibility)
        if (isset($data['error']) && isset($data['code'])) {
            // Legacy error format: { "error": "...", "code": "...", "correlationId": "..." }
            $correlation_id = isset($data['correlationId']) ? $data['correlationId'] : null;
            
            // Validate correlation ID format if present
            if ($correlation_id && !Vehicle_Lookup_Helpers::is_valid_correlation_id($correlation_id)) {
                error_log('Invalid correlation ID format: ' . $correlation_id);
                $correlation_id = null; // Don't use invalid correlation IDs
            }
            
            // Map error codes to failure types for internal processing
            $failure_type = $this->map_error_code_to_failure_type($data['code']);
            
            // Handle circuit breaker state awareness for SERVICE_UNAVAILABLE
            $circuit_breaker_state = null;
            if ($data['code'] === 'SERVICE_UNAVAILABLE' && isset($data['circuitBreakerState'])) {
                $circuit_breaker_state = $data['circuitBreakerState'];
                
                // Don't retry immediately when circuit breaker is open
                if ($circuit_breaker_state === 'OPEN') {
                    $failure_type = 'circuit_breaker_open';
                    // Extend retry time for circuit breaker open state
                    $data['retryAfter'] = isset($data['retryAfter']) ? max($data['retryAfter'], 60) : 60;
                }
            }
            
            return array(
                'error' => $data['error'], // Use the human-readable error message directly
                'failure_type' => $failure_type,
                'code' => $data['code'],
                'correlation_id' => $correlation_id,
                'timestamp' => isset($data['timestamp']) ? $data['timestamp'] : null,
                'retry_after' => isset($data['retryAfter']) ? $data['retryAfter'] : null,
                'circuit_breaker_state' => $circuit_breaker_state
            );
        }

        // Handle HTTP error status codes for non-structured responses
        if ($status_code !== 200) {
            // Special handling for rate limiting
            if ($status_code === 429) {
                $retry_after = wp_remote_retrieve_header($response, 'Retry-After');
                return array(
                    'error' => 'For mange forespørsler. Prøv igjen om litt.',
                    'failure_type' => 'rate_limit',
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'correlation_id' => null,
                    'retry_after' => $retry_after ? intval($retry_after) : 60
                );
            }
            
            return array(
                'error' => 'Tjenesten er ikke tilgjengelig for øyeblikket. Prøv igjen senere.',
                'failure_type' => 'http_error',
                'code' => 'HTTP_ERROR_' . $status_code,
                'correlation_id' => null
            );
        }

        if (empty($data)) {
            error_log('Empty Data Response for: ' . $regNumber);
            return array(
                'error' => 'Fant ingen kjøretøyinformasjon for dette registreringsnummeret',
                'failure_type' => 'http_error',
                'code' => 'EMPTY_RESPONSE',
                'correlation_id' => null
            );
        }

        // Legacy handling for old Norwegian API error responses (backward compatibility)
        if (isset($data['responser']) && is_array($data['responser'])) {
            foreach ($data['responser'] as $respons) {
                if (isset($respons['feilmelding'])) {
                    $error_code = $respons['feilmelding'];

                    // Map legacy API error codes to user-friendly messages
                    switch ($error_code) {
                        case 'KJENNEMERKE_UKJENT':
                            return array(
                                'error' => 'Registreringsnummeret finnes ikke i det norske kjøretøyregisteret',
                                'failure_type' => 'invalid_plate',
                                'code' => 'KJENNEMERKE_UKJENT',
                                'correlation_id' => null
                            );
                        case 'KJENNEMERKE_UGYLDIG':
                            return array(
                                'error' => 'Ugyldig registreringsnummer format',
                                'failure_type' => 'invalid_plate',
                                'code' => 'UGYLDIG_KJENNEMERKE',
                                'correlation_id' => null
                            );
                        case 'TJENESTE_IKKE_TILGJENGELIG':
                            return array(
                                'error' => 'Vegvesenets tjeneste er ikke tilgjengelig for øyeblikket',
                                'failure_type' => 'http_error',
                                'code' => 'SERVICE_UNAVAILABLE',
                                'correlation_id' => null
                            );
                        default:
                            return array(
                                'error' => 'Kunne ikke hente kjøretøyinformasjon. Sjekk at registreringsnummeret er korrekt',
                                'failure_type' => 'invalid_plate',
                                'code' => 'UNKNOWN_ERROR',
                                'correlation_id' => null
                            );
                    }
                }

                // Check if we have valid vehicle data
                if (!isset($respons['kjoretoydata']) || empty($respons['kjoretoydata'])) {
                    return array(
                        'error' => 'Fant ingen kjøretøyinformasjon for dette registreringsnummeret',
                        'failure_type' => 'invalid_plate',
                        'code' => 'NO_DATA_AVAILABLE',
                        'correlation_id' => null
                    );
                }
            }
        }

        // Extract correlation ID from successful response for tracking
        $correlation_id = isset($data['correlationId']) ? $data['correlationId'] : null;
        
        return array(
            'success' => true, 
            'data' => $data,
            'correlation_id' => $correlation_id
        );
    }

    /**
     * Process AI summary polling response
     * 
     * @param mixed $response WordPress HTTP response
     * @param string $regNumber Registration number for context
     */
    public function process_ai_summary_response($response, $regNumber) {
        if (is_wp_error($response)) {
            return array(
                'error' => 'Tilkoblingsfeil ved henting av AI sammendrag. Prøv igjen om litt.',
                'failure_type' => 'connection_error',
                'code' => 'CONNECTION_ERROR',
                'correlation_id' => null
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Try to parse JSON response
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('AI Summary JSON Decode Error: ' . json_last_error_msg());
            return array(
                'error' => 'Ugyldig svar fra AI tjeneste. Prøv igjen.',
                'failure_type' => 'http_error',
                'code' => 'INVALID_JSON',
                'correlation_id' => null
            );
        }

        // Handle HTTP error status codes
        if ($status_code !== 200) {
            return array(
                'error' => 'AI sammendrag tjeneste ikke tilgjengelig. Prøv igjen senere.',
                'failure_type' => 'http_error',
                'code' => 'HTTP_ERROR_' . $status_code,
                'correlation_id' => isset($data['correlationId']) ? $data['correlationId'] : null
            );
        }

        // Validate response has required fields
        if (!isset($data['status']) || !isset($data['registrationNumber'])) {
            return array(
                'error' => 'Ugyldig AI sammendrag respons.',
                'failure_type' => 'http_error',
                'code' => 'INVALID_RESPONSE',
                'correlation_id' => isset($data['correlationId']) ? $data['correlationId'] : null
            );
        }

        // Return the AI summary response data directly
        return array('success' => true, 'data' => $data);
    }

    /**
     * Map error codes from Cloudflare Worker to internal failure types
     */
    private function map_error_code_to_failure_type($error_code) {
        switch ($error_code) {
            case 'INVALID_INPUT':
            case 'VALIDATION_ERROR':
            case 'KJENNEMERKE_UKJENT':
            case 'UGYLDIG_KJENNEMERKE':
            case 'NOT_FOUND':
            case 'NO_DATA_AVAILABLE':
                return 'invalid_plate';
                
            case 'RATE_LIMIT_EXCEEDED':
                return 'rate_limit';
                
            case 'AUTHENTICATION_FAILED':
            case 'FORBIDDEN':
                return 'auth_error';
                
            case 'SERVICE_UNAVAILABLE':
            case 'TIMEOUT':
            case 'NETWORK_ERROR':
                return 'http_error';
                
            default:
                return 'unknown_error';
        }
    }
}

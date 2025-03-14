<?php
if (!defined('ABSPATH')) {
    exit;
}

function beepi_vehicle_search_shortcode() {
    ob_start();
    ?>
    <div class="beepi-vehicle-lookup-container">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title mb-4">Vehicle Information Lookup</h3>
                
                <form id="vehicle-search-form" class="mb-4">
                    <div class="form-group mb-3">
                        <label for="registration-number" class="form-label">Vehicle Registration Number</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="registration-number" 
                            name="registration-number"
                            placeholder="Enter registration number" 
                            required 
                            pattern="^[A-Za-z0-9]{1,8}$"
                            title="Please enter a valid registration number"
                        >
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Search Vehicle
                    </button>
                </form>

                <!-- Alert container for messages -->
                <div id="alert-container"></div>

                <!-- Results container -->
                <div id="vehicle-results" class="mt-4"></div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

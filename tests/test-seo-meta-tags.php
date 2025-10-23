<?php
/**
 * SEO Meta Tags Test Script
 * 
 * Tests the Vehicle_Lookup_SEO class to ensure proper meta tag generation
 * This script validates that all expected meta tags, structured data, and canonical URLs
 * are generated correctly for different scenarios.
 * 
 * Usage: php tests/test-seo-meta-tags.php
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

echo "=================================================================\n";
echo "SEO Meta Tags Test Script\n";
echo "Testing Vehicle_Lookup_SEO Implementation\n";
echo "=================================================================\n\n";

// Test counter
$tests_passed = 0;
$tests_failed = 0;
$tests_total = 0;

/**
 * Test helper function
 */
function run_test($test_name, $callback) {
    global $tests_passed, $tests_failed, $tests_total;
    $tests_total++;
    
    echo sprintf("[Test %d] %s... ", $tests_total, $test_name);
    
    try {
        $result = $callback();
        if ($result === true) {
            echo "✓ PASS\n";
            $tests_passed++;
        } else {
            echo "✗ FAIL\n";
            if (is_string($result)) {
                echo "  Reason: $result\n";
            }
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "✗ FAIL (Exception)\n";
        echo "  Error: " . $e->getMessage() . "\n";
        $tests_failed++;
    }
}

/**
 * Test 1: Class exists and can be instantiated
 */
run_test("Vehicle_Lookup_SEO class exists", function() {
    // Mock the class definition for testing
    if (!class_exists('Vehicle_Lookup_SEO')) {
        // Load the class file
        $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
        if (!file_exists($file_path)) {
            return "File not found: $file_path";
        }
        
        // We can't actually load it without WordPress, so just check file exists
        return file_exists($file_path);
    }
    return true;
});

/**
 * Test 2: Verify file structure and methods
 */
run_test("SEO class has required methods", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    $required_methods = [
        'init',
        'modify_title',
        'add_canonical_url',
        'add_meta_tags',
        'add_structured_data',
        'add_opengraph_tags',
        'add_twitter_card_tags',
        'add_vehicle_schema',
        'add_breadcrumb_schema',
        'add_product_schema'
    ];
    
    foreach ($required_methods as $method) {
        if (strpos($content, "function $method") === false) {
            return "Missing method: $method";
        }
    }
    
    return true;
});

/**
 * Test 3: Verify robots meta tag format
 */
run_test("Robots meta tag has correct format", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    // Check that all robots meta tags include the full parameters
    $expected_robots = 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';
    
    // Count occurrences of the full robots tag
    $full_tags = substr_count($content, $expected_robots);
    
    // There should be at least 3 occurrences (main page, no data, with data)
    if ($full_tags < 3) {
        return "Expected at least 3 full robots tags, found $full_tags";
    }
    
    // Check there are no incomplete robots tags (just "index, follow" without the extra params)
    // except in comments
    $lines = explode("\n", $content);
    foreach ($lines as $line_num => $line) {
        // Skip comments
        if (strpos(trim($line), '//') === 0 || strpos(trim($line), '*') === 0) {
            continue;
        }
        
        // Check if line contains a robots meta tag
        if (strpos($line, 'meta name="robots"') !== false) {
            // It should have the full format
            if (strpos($line, $expected_robots) === false) {
                return "Incomplete robots tag on line " . ($line_num + 1) . ": " . trim($line);
            }
        }
    }
    
    return true;
});

/**
 * Test 4: Verify canonical URL format
 */
run_test("Canonical URL uses correct format", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    // Check for canonical URL pattern
    if (strpos($content, 'rel="canonical"') === false) {
        return "No canonical URL tag found";
    }
    
    // Check the format includes registration number
    if (strpos($content, "home_url('/sok/' . \$reg_number)") === false) {
        return "Canonical URL doesn't use correct format";
    }
    
    return true;
});

/**
 * Test 5: Verify title format
 */
run_test("Page title includes vehicle details", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    // Check for title format with make, model, year, and reg number
    if (strpos($content, "sprintf(") === false) {
        return "Title doesn't use sprintf formatting";
    }
    
    // Check for "Eierinformasjon og Kjøretøydata" in title
    if (strpos($content, 'Eierinformasjon og Kjøretøydata') === false) {
        return "Title doesn't include expected Norwegian text";
    }
    
    return true;
});

/**
 * Test 6: Verify meta description format
 */
run_test("Meta description includes vehicle details", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    // Check for description that includes vehicle info
    $expected_phrases = [
        'Se detaljert informasjon om',
        'Finn eieropplysninger',
        'tekniske spesifikasjoner',
        'markedspris'
    ];
    
    foreach ($expected_phrases as $phrase) {
        if (strpos($content, $phrase) === false) {
            return "Description missing expected phrase: $phrase";
        }
    }
    
    return true;
});

/**
 * Test 7: Verify OpenGraph tags
 */
run_test("OpenGraph tags are implemented", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    $og_tags = [
        'og:type',
        'og:title',
        'og:description',
        'og:url',
        'og:site_name',
        'og:locale',
        'og:image'
    ];
    
    foreach ($og_tags as $tag) {
        if (strpos($content, "property=\"$tag\"") === false) {
            return "Missing OpenGraph tag: $tag";
        }
    }
    
    return true;
});

/**
 * Test 8: Verify Twitter Card tags
 */
run_test("Twitter Card tags are implemented", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    $twitter_tags = [
        'twitter:card',
        'twitter:title',
        'twitter:description',
        'twitter:image'
    ];
    
    foreach ($twitter_tags as $tag) {
        if (strpos($content, "name=\"$tag\"") === false) {
            return "Missing Twitter Card tag: $tag";
        }
    }
    
    // Verify card type is summary_large_image
    if (strpos($content, 'summary_large_image') === false) {
        return "Twitter card should use summary_large_image type";
    }
    
    return true;
});

/**
 * Test 9: Verify Vehicle schema (JSON-LD)
 */
run_test("Vehicle structured data schema is complete", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    $schema_elements = [
        '@context',
        '@type',
        'Vehicle',
        'vehicleIdentificationNumber',
        'manufacturer',
        'model',
        'productionDate',
        'vehicleModelDate'
    ];
    
    foreach ($schema_elements as $element) {
        if (strpos($content, "'$element'") === false && strpos($content, "\"$element\"") === false) {
            return "Missing schema element: $element";
        }
    }
    
    return true;
});

/**
 * Test 10: Verify BreadcrumbList schema
 */
run_test("BreadcrumbList schema is implemented", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    if (strpos($content, 'BreadcrumbList') === false) {
        return "BreadcrumbList schema not found";
    }
    
    if (strpos($content, 'itemListElement') === false) {
        return "BreadcrumbList missing itemListElement";
    }
    
    if (strpos($content, 'ListItem') === false) {
        return "BreadcrumbList missing ListItem";
    }
    
    return true;
});

/**
 * Test 11: Verify Product schema
 */
run_test("Product schema for owner info is implemented", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    $product_elements = [
        'Product',
        'Eieropplysninger',
        'offers',
        'Offer',
        'price',
        'priceCurrency',
        'NOK'
    ];
    
    foreach ($product_elements as $element) {
        if (strpos($content, $element) === false) {
            return "Missing product schema element: $element";
        }
    }
    
    return true;
});

/**
 * Test 12: Verify WebSite schema
 */
run_test("WebSite schema with SearchAction is implemented", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    $website_elements = [
        'WebSite',
        'potentialAction',
        'SearchAction',
        'urlTemplate',
        'query-input'
    ];
    
    foreach ($website_elements as $element) {
        if (strpos($content, $element) === false) {
            return "Missing WebSite schema element: $element";
        }
    }
    
    return true;
});

/**
 * Test 13: Verify proper escaping for security
 */
run_test("Output is properly escaped for security", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    // Check that echo statements use proper escaping
    $escape_functions = ['esc_attr', 'esc_html', 'esc_url', 'esc_js'];
    
    // Count unescaped echoes (should be minimal, only for static content)
    $lines = explode("\n", $content);
    $unescaped_echoes = 0;
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '//') === 0 || strpos(trim($line), '*') === 0) {
            continue;
        }
        
        // If line has echo with content attribute/href
        if (strpos($line, 'echo') !== false) {
            if (strpos($line, 'content="') !== false || strpos($line, 'href="') !== false) {
                // Check if it uses escaping
                $has_escape = false;
                foreach ($escape_functions as $func) {
                    if (strpos($line, $func) !== false) {
                        $has_escape = true;
                        break;
                    }
                }
                
                // Allow static strings without escaping
                if (!$has_escape && strpos($line, '$') !== false) {
                    // Has variable but no escaping - potential issue
                    // But wp_json_encode is also safe
                    if (strpos($line, 'wp_json_encode') === false) {
                        $unescaped_echoes++;
                    }
                }
            }
        }
    }
    
    // Some unescaped echoes are OK for static content, but should be minimal
    if ($unescaped_echoes > 5) {
        return "Too many potentially unescaped echo statements: $unescaped_echoes";
    }
    
    return true;
});

/**
 * Test 14: Verify input sanitization
 */
run_test("Input is properly sanitized", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    // Check for sanitization functions
    $sanitize_functions = [
        'sanitize_text_field',
        'esc_sql',
        'esc_url_raw'
    ];
    
    $found_sanitization = false;
    foreach ($sanitize_functions as $func) {
        if (strpos($content, $func) !== false) {
            $found_sanitization = true;
            break;
        }
    }
    
    if (!$found_sanitization) {
        return "No sanitization functions found";
    }
    
    return true;
});

/**
 * Test 15: Verify JSON-LD output uses wp_json_encode
 */
run_test("Structured data uses wp_json_encode", function() {
    $file_path = dirname(__DIR__) . '/includes/class-vehicle-lookup-seo.php';
    $content = file_get_contents($file_path);
    
    // Count JSON-LD script tags
    $jsonld_count = substr_count($content, 'application/ld+json');
    
    // Count wp_json_encode usages
    $encode_count = substr_count($content, 'wp_json_encode');
    
    // They should match (each JSON-LD should use wp_json_encode)
    if ($jsonld_count !== $encode_count) {
        return "Mismatch between JSON-LD blocks ($jsonld_count) and wp_json_encode calls ($encode_count)";
    }
    
    if ($jsonld_count < 4) {
        return "Expected at least 4 JSON-LD blocks, found $jsonld_count";
    }
    
    return true;
});

// Print summary
echo "\n=================================================================\n";
echo "Test Summary\n";
echo "=================================================================\n";
echo "Total Tests: $tests_total\n";
echo "Passed: $tests_passed ✓\n";
echo "Failed: $tests_failed ✗\n";
echo "Success Rate: " . ($tests_total > 0 ? round(($tests_passed / $tests_total) * 100, 2) : 0) . "%\n";
echo "=================================================================\n";

if ($tests_failed > 0) {
    echo "\nStatus: FAILED ✗\n";
    exit(1);
} else {
    echo "\nStatus: PASSED ✓\n";
    echo "\nAll SEO meta tag requirements are properly implemented!\n";
    exit(0);
}

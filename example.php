<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Config/CountryRates.php';
require_once __DIR__ . '/src/Model/CountryInput.php';
require_once __DIR__ . '/src/Model/ProjectInput.php';
require_once __DIR__ . '/src/Model/DerivedInputs.php';
require_once __DIR__ . '/src/Model/CostBreakdown.php';
require_once __DIR__ . '/src/Calculator/DerivedInputsCalculator.php';
require_once __DIR__ . '/src/Calculator/StartupCostCalculator.php';
require_once __DIR__ . '/src/Calculator/ActivePhaseCostCalculator.php';
require_once __DIR__ . '/src/Calculator/BallparkCalculator.php';

use BallparkCalculator\Calculator\BallparkCalculator;
use BallparkCalculator\Config\CountryRates;
use BallparkCalculator\Model\CountryInput;
use BallparkCalculator\Model\ProjectInput;
use BallparkCalculator\Model\VisitType;

// Load configuration
$config = new CountryRates(__DIR__ . '/ballpark_constants.php');

// Create calculator
$calculator = new BallparkCalculator($config);

// Example: Create project with sample inputs (matching Excel defaults)
$project = new ProjectInput(
    enrollmentMonths: 12,
    treatmentMonths: 4,
    followupMonths: 12,
    qualificationVisitType: VisitType::ON_SITE,
    initiationVisitType: VisitType::ON_SITE,
    closeoutVisitType: VisitType::ON_SITE,
    saeRate: 0.15,
    susarsWeeks: 13,
    vendors: 1,
);

// Add US data (from Excel column E)
//$project->addCountry(new CountryInput(
//    country: 'US',
//    sites: 5,
//    patients: 15,
//    monitoringVisitsOnsite: 4,
//    monitoringVisitsRemote: 12,
//    unblindedVisits: 0,
//    // US: always 1 country (default)
//));

$project->addCountry(new CountryInput(
    country: 'Non_EU',
    sites: 15,
    patients:60,
    monitoringVisitsOnsite: 10,
    monitoringVisitsRemote: 0,
    unblindedVisits: 0,
    countriesInRegion:5
));

// $project->addCountry(new CountryInput(
//     country: 'EU_CEE',
//     sites:5,
//     patients: 15,
//     monitoringVisitsOnsite: 10,
//     monitoringVisitsRemote: 0,
//     unblindedVisits: 0,
//     countriesInRegion: 1
// ));

// $project->addCountry(new CountryInput(
//     country: 'EU_West',
//     sites:5,
//     patients: 15,
//     monitoringVisitsOnsite: 10,
//     monitoringVisitsRemote: 0,
//     unblindedVisits: 0,
//     countriesInRegion: 1
// ));

// Calculate
$results = $calculator->calculateAsArray($project);

// Output
//echo "=== BALLPARK COST ESTIMATE ===\n\n";
//
//echo "GLOBAL COSTS:\n";
//echo sprintf("  Startup Service:     $%s\n", number_format($results['global']['startup']['service_total']));
//echo sprintf("  Startup Passthrough: $%s\n", number_format($results['global']['startup']['passthrough_total']));
//echo sprintf("  Active Service:      $%s\n", number_format($results['global']['active']['service_total']));
//echo sprintf("  Active Passthrough:  $%s\n", number_format($results['global']['active']['passthrough_total']));
//echo "\n";
//
//foreach ($results['countries'] as $country => $breakdown) {
//    echo "{$country}:\n";
//    echo sprintf("  Startup Service:     $%s\n", number_format($breakdown['startup']['service_total']));
//    echo sprintf("  Startup Passthrough: $%s\n", number_format($breakdown['startup']['passthrough_total']));
//    echo sprintf("  Active Service:      $%s\n", number_format($breakdown['active']['service_total']));
//    echo sprintf("  Active Passthrough:  $%s\n", number_format($breakdown['active']['passthrough_total']));
//    echo sprintf("  Country Total:       $%s\n", number_format($breakdown['totals']['grand_total']));
//    echo "\n";
//}
//
//echo "=== TOTALS ===\n";
//echo sprintf("  Startup:     $%s\n", number_format($results['totals']['startup']));
//echo sprintf("  Active:      $%s\n", number_format($results['totals']['active']));
//echo sprintf("  GRAND TOTAL: $%s\n", number_format($results['totals']['grand_total']));
//
//// JSON output for API use
//echo "\n=== JSON OUTPUT ===\n";
echo json_encode($results, JSON_PRETTY_PRINT);

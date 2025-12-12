<?php

declare(strict_types=1);

return [
    // ============================================================
    // GLOBAL CONSTANTS (not country-specific)
    // ============================================================
    'global' => [
        // Site selection multipliers (Row 37-39)
        'site_multiplier_contacted' => 1.75,
        'site_multiplier_cdas' => 1.5,
        'site_multiplier_questionnaires' => 1.35,
        
        // Visit percentages (Row 41-44)
        'qualification_onsite_pct' => 1.20,
        'qualification_remote_pct' => 1.20,
        'initiation_onsite_pct' => 1.00,
        'initiation_remote_pct' => 1.00,
        'closeout_onsite_pct' => 1.00,
        'closeout_remote_pct' => 1.00,
        
        // Time defaults
        'closeout_months' => 3,
        'sae_rate_default' => 0.15,
        'susars_weeks_default' => 13,
        'vendors_default' => 1,
        
        // EU-specific global costs
        'eu_legal_rep_setup' => 1500,
        'eu_legal_rep_annual' => 1500,
        'eu_part1_dossier' => 19690,
        
        // Project setup costs (global PM rates)
        'vendors_setup' => 2424,
        'team_setup' => 1616,
        'tmf_maintenance_global' => 433,
        'tracking_reporting' => 481,
        'budget_invoicing' => 481,
        
        // QA costs
        'protocol_checklist' => 2148,
        'tmf_audit_initial' => 2864,
        'tmf_audit_unblinded_initial' => 1432, // D107/2
        'tmf_audit_annual' => 3222,
        'tmf_audit_unblinded_annual' => 3222,
        
        // Contract hours
        'contract_template_hours' => 16,
        'contract_negotiation_hours' => 32,
        
        // Questionnaire development (Row 61)
        'questionnaire_development' => 1818, // 404+606+808
        
        // US-only fees
        'central_irb_fee' => 750,

        // other
        'internal_communication' => 404,
        'passthrough_management' => 308
    ],

    // ============================================================
    // STARTUP MONTHS PER COUNTRY (Row 26)
    // ============================================================
    'startup_months' => [
        'US' => 4,
        'EU_CEE' => 6,
        'EU_West' => 6,
        'Non_EU' => 6,
        'Georgia' => 5.3,
        'Turkiye' => 6,
        'Ukraine' => 6,
    ],

    // ============================================================
    // HOURLY RATES BY ROLE AND COUNTRY
    // ============================================================
    'hourly_rates' => [
        // CRA (Clinical Research Associate) - most common
        'cra' => [
            'US' => 190,
            'EU_CEE' => 119,
            'EU_West' => 190,
            'Non_EU' => 99,
            'Georgia' => 99,
            'Turkiye' => 99,
            'Ukraine' => 65,
        ],
        // PM (Project Manager)
        'pm' => [
            'US' => 202, 'EU_CEE' => 202, 'EU_West' => 202,
            'Non_EU' => 202, 'Georgia' => 202, 'Turkiye' => 202, 'Ukraine' => 202,
        ],
        // Admin/Support
        'admin' => [
            'US' => 124,
            'EU_CEE' => 77,
            'EU_West' => 124,
            'Non_EU' => 64,
            'Georgia' => 64,
            'Turkiye' => 64,
            'Ukraine' => 42,
        ],
        // Regulatory Affairs
        'ra' => [
            'US' => 285,
            'EU_CEE' => 179,
            'EU_West' => 285,
            'Non_EU' => 149, # eu * 83% discount
            'Georgia' => 149,
            'Turkiye' => 149, 'Ukraine' => 98,
        ],
        // QA
        'qa' => [
            'US' => 77, 'EU_CEE' => 77, 'EU_West' => 77,
            'Non_EU' => 77, 'Georgia' => 77, 'Turkiye' => 77, 'Ukraine' => 51,
        ],
        // Contract specialist
        'contract' => [
            'US' => 238,
            'EU_CEE' => 149,
            'EU_West' => 238,
            'Non_EU' => 124, // 124 ?
            'Georgia' => 124,
            'Turkiye' => 124,
            'Ukraine' => 81,
        ],
        // Investigator meeting rate (Row 78)
        'investigator_meeting' => [
            'US' => 190, 'EU_CEE' => 119, 'EU_West' => 225,
            'Non_EU' => 99, 'Georgia' => 99, 'Turkiye' => 99, 'Ukraine' => 65,
        ],
    ],

    // ============================================================
    // VISIT HOURS BY TYPE AND COUNTRY
    // Pattern: prep + travel + onsite + report
    // ============================================================
    'visit_hours' => [
        'US' => [
            'qualification_onsite' => 22,  // 2+12+4+4
            'qualification_remote' => 7,   // 1+0+4+2
            'initiation_onsite' => 26,     // 3+12+8+3
            'initiation_remote' => 7,      // 1+0+4+2
            'monitoring_onsite' => 26,     // 3+12+8+3
            'monitoring_remote' => 7,      // 1+0+4+2
            'unblinded' => 8,              // 2+0+4+2
            'closeout_onsite' => 28,       // 4+12+8+4
            'closeout_remote' => 7,        // 1+0+4+2
        ],
        'EU_CEE' => [
            'qualification_onsite' => 14,  // 2+4+4+4
            'qualification_remote' => 7,
            'initiation_onsite' => 18,     // 3+4+8+3
            'initiation_remote' => 7,
            'monitoring_onsite' => 18,
            'monitoring_remote' => 7,
            'unblinded' => 8,
            'closeout_onsite' => 20,       // 4+4+8+4
            'closeout_remote' => 7,
        ],
        'EU_West' => [
            'qualification_onsite' => 22,
            'qualification_remote' => 7,
            'initiation_onsite' => 26,
            'initiation_remote' => 7,
            'monitoring_onsite' => 26,
            'monitoring_remote' => 7,
            'unblinded' => 8,
            'closeout_onsite' => 28,
            'closeout_remote' => 7,
        ],
        'Non_EU' => [
            'qualification_onsite' => 14,
            'qualification_remote' => 7,
            'initiation_onsite' => 18,
            'initiation_remote' => 7,
            'monitoring_onsite' => 18,
            'monitoring_remote' => 7,
            'unblinded' => 8,
            'closeout_onsite' => 20,
            'closeout_remote' => 7,
        ],
        'Georgia' => [
            'qualification_onsite' => 14,
            'qualification_remote' => 7,
            'initiation_onsite' => 19,     // 3+5+8+3
            'initiation_remote' => 7,
            'monitoring_onsite' => 18,
            'monitoring_remote' => 7,
            'unblinded' => 8,
            'closeout_onsite' => 20,
            'closeout_remote' => 7,
        ],
        'Turkiye' => [
            'qualification_onsite' => 16,  // 2+6+4+4
            'qualification_remote' => 7,
            'initiation_onsite' => 19,
            'initiation_remote' => 7,
            'monitoring_onsite' => 20,     // 3+6+8+3
            'monitoring_remote' => 7,
            'unblinded' => 8,
            'closeout_onsite' => 22,       // 4+6+8+4
            'closeout_remote' => 7,
        ],
        'Ukraine' => [
            'qualification_onsite' => 16,
            'qualification_remote' => 7,
            'initiation_onsite' => 20,     // 3+6+8+3
            'initiation_remote' => 7,
            'monitoring_onsite' => 20,
            'monitoring_remote' => 7,
            'unblinded' => 8,
            'closeout_onsite' => 22,
            'closeout_remote' => 7,
        ],
    ],

    // ============================================================
    // FIXED COSTS BY COUNTRY (Pass-through, travel, fees)
    // ============================================================
    'fixed_costs' => [
        'US' => [
            'travel_sqv' => 950,
            'travel_siv' => 950,
            'travel_omv' => 950,
            'travel_cov' => 950,
            'translation_cost' => 850,
            'copying_printing' => 2000,
            'communication_expense' => 380,
            'site_startup_fee' => 40000,
            'site_contract_fee' => 2000,
            'monitor_visit_fee' => 500,
            'site_regulatory_annual' => 12000,
            'pharmacy_annual' => 1900,
            'site_closeout_fee' => 3500,
            'pharmacy_closeout_fee' => 1900,
            'various_ongoing' => 150,
        ],
        'EU_CEE' => [
            'travel_sqv' => 350,
            'travel_siv' => 350,
            'travel_omv' => 350,
            'travel_cov' => 350,
            'translation_cost' => 3500,
            'copying_printing' => 1500,
            'communication_expense' => 230,
            'site_startup_fee' => 3000,
            'site_contract_fee' => 1200,
            'monitor_visit_fee' => 0,
            'site_regulatory_annual' => 0,
            'pharmacy_annual' => 0,
            'site_closeout_fee' => 0,
            'pharmacy_closeout_fee' => 0,
            'various_ongoing' => 150,
        ],
        'EU_West' => [
            'travel_sqv' => 500,
            'travel_siv' => 350,
            'travel_omv' => 500,
            'travel_cov' => 350,
            'translation_cost' => 4500,
            'copying_printing' => 2000,
            'communication_expense' => 300,
            'site_startup_fee' => 4000,
            'site_contract_fee' => 2000,
            'monitor_visit_fee' => 0,
            'site_regulatory_annual' => 0,
            'pharmacy_annual' => 0,
            'site_closeout_fee' => 0,
            'pharmacy_closeout_fee' => 0,
            'various_ongoing' => 150,
        ],
        'Non_EU' => [
            'travel_sqv' => 250,
            'travel_siv' => 250,
            'travel_omv' => 250,
            'travel_cov' => 250,
            'translation_cost' => 3000,
            'copying_printing' => 1000,
            'communication_expense' => 175,
            'site_startup_fee' => 2500,
            'site_contract_fee' => 0,
            'monitor_visit_fee' => 0,
            'site_regulatory_annual' => 0,
            'pharmacy_annual' => 0,
            'site_closeout_fee' => 0,
            'pharmacy_closeout_fee' => 0,
            'various_ongoing' => 75,
        ],
        'Georgia' => [
            'travel_sqv' => 130,
            'travel_siv' => 130,
            'travel_omv' => 130,
            'travel_cov' => 130,
            'translation_cost' => 15000,
            'copying_printing' => 1500,
            'communication_expense' => 175,
            'site_startup_fee' => 2500,
            'site_contract_fee' => 1000,
            'monitor_visit_fee' => 0,
            'site_regulatory_annual' => 0,
            'pharmacy_annual' => 0,
            'site_closeout_fee' => 0,
            'pharmacy_closeout_fee' => 0,
            'various_ongoing' => 150,
        ],
        'Turkiye' => [
            'travel_sqv' => 400,
            'travel_siv' => 180,
            'travel_omv' => 400,
            'travel_cov' => 180,
            'translation_cost' => 15000,
            'copying_printing' => 1500,
            'communication_expense' => 175,
            'site_startup_fee' => 2500,
            'site_contract_fee' => 1000,
            'monitor_visit_fee' => 0,
            'site_regulatory_annual' => 0,
            'pharmacy_annual' => 0,
            'site_closeout_fee' => 0,
            'pharmacy_closeout_fee' => 0,
            'various_ongoing' => 150,
        ],
        'Ukraine' => [
            'travel_sqv' => 300,
            'travel_siv' => 300,
            'travel_omv' => 300,
            'travel_cov' => 300,
            'translation_cost' => 4000,
            'copying_printing' => 1000,
            'communication_expense' => 150,
            'site_startup_fee' => 2500,
            'site_contract_fee' => 750,
            'monitor_visit_fee' => 0,
            'site_regulatory_annual' => 0,
            'pharmacy_annual' => 0,
            'site_closeout_fee' => 0,
            'pharmacy_closeout_fee' => 0,
            'various_ongoing' => 150,
        ],
    ],

    // ============================================================
    // CALCULATED MONTHLY COSTS (per country)
    // ============================================================
    'monthly_costs' => [
        // TMF Maintenance (Row 92)
        'tmf_maintenance' => [
            'US' => 449,
            'EU_CEE' => 308,
            'EU_West' => 449,
            'Non_EU' => 255.64, // 308*0.83
            'Georgia' => 269,
            'Turkiye' => 269,
            'Ukraine' => 203,
        ],
        // CTMS Update (Row 96)
        'ctms_update' => [
            'US' => 942, 'EU_CEE' => 588, 'EU_West' => 942,
            'Non_EU' => 488.04, // 588*0.83
            'Georgia' => 489, 'Turkiye' => 489, 'Ukraine' => 321,
        ],
    ],

    // ============================================================
    // REGULATORY SUBMISSION HOURS
    // ============================================================
    'regulatory_hours' => [
        // Country-specific dossier (Row 73)
        'country_dossier' => 100, // hours * ra_rate
        
        // Initial EC submissions (Row 74)
        'initial_ec_submission' => 12, // hours * ra_rate
        
        // Major RA submissions (Row 126) - hours breakdown
        'major_ra_submission' => [
            'ra_hours' => 24,
            'cra_hours' => 16,
            'ra_followup_hours' => 10,
        ],
        
        // Minor RA submissions (Row 127)
        'minor_ra_submission' => 4, // hours * ra_rate
        
        // Major EC submissions (Row 128)
        'major_ec_submission' => 8, // hours * ra_rate
        
        // Minor EC submissions (Row 129)
        'minor_ec_submission' => 2, // hours * ra_rate
        
        // Safety notification hours
        'expedited_safety_ra' => 8,
        'periodic_safety_ra' => 8,
    ],

    // ============================================================
    // NON-EU DISCOUNT FACTOR (Row 69, 70, 92, 96 pattern)
    // ============================================================
    'non_eu_discount' => 0.83,

    // ============================================================
    // REGION GROUPS (for conditional logic)
    // ============================================================
    'regions' => [
        'eu' => ['EU_CEE', 'EU_West'],
        'non_eu' => ['Non_EU', 'Georgia', 'Turkiye', 'Ukraine'],
        'us' => ['US'],
        'all' => ['US', 'EU_CEE', 'EU_West', 'Non_EU', 'Georgia', 'Turkiye', 'Ukraine'],
    ],
];

# Ballpark Calculator

Калькулятор стоимости клинических исследований. Портирован с Excel.

## Пример использования

```php
$config = new CountryRates(__DIR__ . '/ballpark_constants.php');
$calculator = new BallparkCalculator($config);

$project = new ProjectInput(
    enrollmentMonths: 12,
    treatmentMonths: 4,
    followupMonths: 12,
    qualificationVisitType: VisitType::ON_SITE,
    initiationVisitType: VisitType::ON_SITE,
    closeoutVisitType: VisitType::ON_SITE,
);

$project->addCountry(new CountryInput(
    country: 'US',
    sites: 5,
    patients: 50,
    monitoringVisitsOnsite: 5,
    monitoringVisitsRemote: 7,
    unblindedVisits: 2,
));

$project->addCountry(new CountryInput(
    country: 'EU_CEE',
    sites: 5,
    patients: 21,
    monitoringVisitsOnsite: 10,
    monitoringVisitsRemote: 0,
    unblindedVisits: 3,
    countriesInRegion: 3,
));

$results = $calculator->calculateAsArray($project);
```

## Интеграция в API

```php
class BallparkController extends Controller
{
    private BallparkCalculator $calculator;

    public function __construct()
    {
        $config = new CountryRates(config_path('ballpark_constants.php'));
        $this->calculator = new BallparkCalculator($config);
    }

    public function calculate(Request $request): JsonResponse
    {
        $project = new ProjectInput(
            enrollmentMonths: $request->input('enrollment_months'),
            treatmentMonths: $request->input('treatment_months', 0),
            followupMonths: $request->input('followup_months', 0),
            // ...
        );

        foreach ($request->input('countries', []) as $data) {
            $project->addCountry(new CountryInput(
                country: $data['region'],
                sites: $data['sites'],
                patients: $data['patients'],
                monitoringVisitsOnsite: $data['monitoring_onsite'] ?? 0,
                monitoringVisitsRemote: $data['monitoring_remote'] ?? 0,
                unblindedVisits: $data['unblinded_visits'] ?? 0,
                countriesInRegion: $data['countries_in_region'] ?? null,
            ));
        }

        return response()->json($this->calculator->calculateAsArray($project));
    }
}
```

## Структура файлов

```
src/
├── Config/CountryRates.php      — загрузка констант
├── Model/
│   ├── CountryInput.php         — входные данные по региону
│   ├── ProjectInput.php         — входные данные проекта
│   ├── DerivedInputs.php        — промежуточные расчёты
│   └── CostBreakdown.php        — результаты
└── Calculator/
    ├── BallparkCalculator.php       — главный класс
    ├── DerivedInputsCalculator.php  — расчёт промежуточных значений
    ├── StartupCostCalculator.php    — расходы startup-фазы
    └── ActivePhaseCostCalculator.php — расходы активной фазы

ballpark_constants.php           — все ставки и константы
```

## Изменение констант

Все ставки лежат в `ballpark_constants.php`.

**Почасовые ставки:**
```php
'hourly_rates' => [
    'cra' => [
        'US' => 190,       // ставка CRA в США
        'EU_CEE' => 119,   // ставка CRA в Восточной Европе
    ],
],
```

**Фиксированные расходы:**
```php
'fixed_costs' => [
    'US' => [
        'travel_sqv' => 950,         // командировка на SQV
        'site_startup_fee' => 40000, // стартовый взнос сайта
    ],
],
```

**Часы на визиты:**
```php
'visit_hours' => [
    'US' => [
        'qualification_onsite' => 22,  // подготовка + дорога + визит + отчёт
        'monitoring_onsite' => 26,
    ],
],
```


## Изменение логики расчётов

**Добавить статью расходов** — в `StartupCostCalculator.php`:
```php
$costs->addStartupService(
    'new_cost_item',
    $this->config->hourlyRate('cra', $c) * 10 * $derived->sites
);
```

**Изменить формулу** — в `DerivedInputsCalculator.php`:
```php
private function calcQualificationOnsite(...): int
{
    // было: 120% от сайтов
    // стало: 150% от сайтов + 2 на страну
    return (int) round(1.5 * $country->sites) + (2 * $country->getEcIrbCount());
}
```

## Структура ответа

```json
{
    "countries": {
        "US": {
            "country": "US",
            "startup": {
                "service": {
                    "sites_contacted": 855,
                    "cdas_signed": 2280,
                    "questionnaires_collected": 2660,
                    "site_regulatory_docs": 7600,
                    "qualification_visits_onsite": 25080,
                    "contract_templates": 3808,
                    "contract_negotiation": 38080,
                    "initial_ec_submissions": 17100,
                    "investigator_meeting": 1520,
                    "team_setup": 808,
                    "team_training": 8360,
                    "tmf_maintenance": 1796,
                    "ctms_update": 3768,
                    "internal_communication": 1520,
                    "team_management": 1616,
                    "visit_report_review": 2222,
                    "country_issues_resolution": 3232,
                    "sites_setup": 13300,
                    "initiation_visits_onsite": 24700,
                    "passthrough_management": 1984
                },
                "service_total": 162000,
                "passthrough": {
                    "travel_qualification": 5700,
                    "travel_initiation": 4750,
                    "translation": 850,
                    "copying_printing": 2000,
                    "communication": 1900,
                    "central_irb": 15000,
                    "site_startup_fee": 200000,
                    "site_contract_fee": 10000,
                    "monitor_visit_fee": 5500
                },
                "passthrough_total": 246000,
                "total": 408000
            },
            "active": {
                "service": {
                    "major_ec_submissions": 6840,
                    "minor_ec_submissions": 4560,
                    "team_retraining": 7524,
                    "tmf_maintenance": 13919,
                    "ctms_update": 29202,
                    "internal_communication": 11780,
                    "team_management": 12524,
                    "visit_report_review": 15150,
                    "country_issues_resolution": 25048,
                    "passthrough_management": 15376,
                    "site_management": 262570,
                    "monitoring_visits_onsite": 123500,
                    "monitoring_visits_remote": 46550,
                    "unblinded_visits": 15200,
                    "closeout_visits_onsite": 26600,
                    "site_payment_admin": 147250,
                    "sae_assistance": 6080,
                    "expedited_safety_ec": 1900,
                    "periodic_safety_ec": 570
                },
                "service_total": 772000,
                "passthrough": {
                    "travel_monitoring": 23750,
                    "travel_unblinded": 9500,
                    "travel_closeout": 4750,
                    "various_ongoing": 23250,
                    "monitor_visit_fee": 20000,
                    "site_regulatory_annual": 180000,
                    "pharmacy_annual": 28500,
                    "site_closeout_fee": 17500,
                    "pharmacy_closeout_fee": 9500,
                    "central_irb": 116250
                },
                "passthrough_total": 433000,
                "total": 1205000
            },
            "totals": {
                "service": 934000,
                "passthrough": 679000,
                "grand_total": 1613000
            }
        },
        "Non_EU": {
            "country": "Non_EU",
            "startup": {
                "service": {
                    "sites_contacted": 445.5,
                    "cdas_signed": 1188,
                    "questionnaires_collected": 1386,
                    "site_regulatory_docs": 3960,
                    "qualification_visits_onsite": 8316,
                    "contract_templates": 5952,
                    "contract_negotiation": 19840,
                    "initial_ec_submissions": 8880,
                    "country_dossier": 44400,
                    "investigator_meeting": 2376,
                    "team_setup": 2424,
                    "team_training": 13068,
                    "tmf_maintenance": 1533.84,
                    "ctms_update": 2928.2400000000002,
                    "internal_communication": 1188,
                    "team_management": 7272,
                    "visit_report_review": 2222,
                    "country_issues_resolution": 14544,
                    "sites_setup": 6930,
                    "initiation_visits_onsite": 8910,
                    "passthrough_management": 1536
                },
                "service_total": 159000,
                "passthrough": {
                    "travel_qualification": 1500,
                    "travel_initiation": 1250,
                    "translation": 9000,
                    "copying_printing": 3000,
                    "communication": 875,
                    "site_startup_fee": 12500,
                    "site_contract_fee": 0,
                    "monitor_visit_fee": 0
                },
                "passthrough_total": 28000,
                "total": 187000
            },
            "active": {
                "service": {
                    "major_ra_submissions": 19848,
                    "minor_ra_submissions": 3552,
                    "major_ec_submissions": 17760,
                    "minor_ec_submissions": 5328,
                    "team_retraining": 11761.199999999999,
                    "tmf_maintenance": 7924.839999999999,
                    "ctms_update": 15129.24,
                    "internal_communication": 6138,
                    "team_management": 37572,
                    "visit_report_review": 16160,
                    "country_issues_resolution": 75144,
                    "passthrough_management": 7936,
                    "site_management": 136245,
                    "monitoring_visits_onsite": 89100,
                    "unblinded_visits": 19800,
                    "closeout_visits_onsite": 9900,
                    "site_payment_admin": 25740,
                    "sae_assistance": 792,
                    "expedited_safety_ra": 11840,
                    "periodic_safety_ra": 3552,
                    "expedited_safety_ec": 4950,
                    "periodic_safety_ec": 1485
                },
                "service_total": 528000,
                "passthrough": {
                    "travel_monitoring": 12500,
                    "travel_unblinded": 6250,
                    "travel_closeout": 1250,
                    "various_ongoing": 11625,
                    "monitor_visit_fee": 0
                },
                "passthrough_total": 32000,
                "total": 560000
            },
            "totals": {
                "service": 687000,
                "passthrough": 60000,
                "grand_total": 747000
            }
        },
        "EU_CEE": {
            "country": "EU_CEE",
            "startup": {
                "service": {
                    "sites_contacted": 535.5,
                    "cdas_signed": 1428,
                    "questionnaires_collected": 1666,
                    "site_regulatory_docs": 4760,
                    "qualification_visits_onsite": 9996,
                    "contract_templates": 7152,
                    "contract_negotiation": 23840,
                    "initial_ec_submissions": 10740,
                    "country_dossier": 53700,
                    "investigator_meeting": 2856,
                    "team_setup": 2424,
                    "team_training": 15708,
                    "tmf_maintenance": 1848,
                    "ctms_update": 3528,
                    "internal_communication": 1428,
                    "team_management": 7272,
                    "visit_report_review": 2222,
                    "country_issues_resolution": 14544,
                    "sites_setup": 8330,
                    "initiation_visits_onsite": 10710,
                    "passthrough_management": 1848
                },
                "service_total": 187000,
                "passthrough": {
                    "travel_qualification": 2100,
                    "travel_initiation": 1750,
                    "translation": 10500,
                    "copying_printing": 4500,
                    "communication": 1150,
                    "site_startup_fee": 15000,
                    "site_contract_fee": 6000,
                    "monitor_visit_fee": 0
                },
                "passthrough_total": 41000,
                "total": 228000
            },
            "active": {
                "service": {
                    "major_ra_submissions": 23970,
                    "minor_ra_submissions": 3580,
                    "team_retraining": 14137.199999999999,
                    "tmf_maintenance": 9548,
                    "ctms_update": 18228,
                    "internal_communication": 7378,
                    "team_management": 37572,
                    "visit_report_review": 14140,
                    "country_issues_resolution": 75144,
                    "passthrough_management": 9548,
                    "site_management": 163835,
                    "monitoring_visits_onsite": 107100,
                    "unblinded_visits": 14280,
                    "closeout_visits_onsite": 11900,
                    "site_payment_admin": 30940,
                    "sae_assistance": 1428,
                    "periodic_safety_ra": 4296
                },
                "service_total": 547000,
                "passthrough": {
                    "travel_monitoring": 17500,
                    "travel_unblinded": 5250,
                    "travel_closeout": 1750,
                    "various_ongoing": 15500,
                    "monitor_visit_fee": 0
                },
                "passthrough_total": 40000,
                "total": 587000
            },
            "totals": {
                "service": 734000,
                "passthrough": 81000,
                "grand_total": 815000
            }
        },
        "EU_West": {
            "country": "EU_West",
            "startup": {
                "service": {
                    "sites_contacted": 855,
                    "cdas_signed": 2280,
                    "questionnaires_collected": 2660,
                    "site_regulatory_docs": 7600,
                    "qualification_visits_onsite": 25080,
                    "contract_templates": 7616,
                    "contract_negotiation": 38080,
                    "initial_ec_submissions": 17100,
                    "country_dossier": 57000,
                    "investigator_meeting": 3600,
                    "team_setup": 1616,
                    "team_training": 16720,
                    "tmf_maintenance": 2694,
                    "ctms_update": 5652,
                    "internal_communication": 2280,
                    "team_management": 4848,
                    "visit_report_review": 2222,
                    "country_issues_resolution": 9696,
                    "sites_setup": 13300,
                    "initiation_visits_onsite": 24700,
                    "passthrough_management": 2976
                },
                "service_total": 249000,
                "passthrough": {
                    "travel_qualification": 3000,
                    "travel_initiation": 1750,
                    "translation": 9000,
                    "copying_printing": 4000,
                    "communication": 1500,
                    "site_startup_fee": 20000,
                    "site_contract_fee": 10000,
                    "monitor_visit_fee": 0
                },
                "passthrough_total": 49000,
                "total": 298000
            },
            "active": {
                "service": {
                    "major_ra_submissions": 25460,
                    "minor_ra_submissions": 6840,
                    "team_retraining": 15048,
                    "tmf_maintenance": 13919,
                    "ctms_update": 29202,
                    "internal_communication": 11780,
                    "team_management": 25048,
                    "visit_report_review": 15150,
                    "country_issues_resolution": 50096,
                    "passthrough_management": 15376,
                    "site_management": 262570,
                    "monitoring_visits_onsite": 247000,
                    "unblinded_visits": 30400,
                    "closeout_visits_onsite": 26600,
                    "site_payment_admin": 49400,
                    "sae_assistance": 1520,
                    "periodic_safety_ra": 6840
                },
                "service_total": 832000,
                "passthrough": {
                    "travel_monitoring": 25000,
                    "travel_unblinded": 7000,
                    "travel_closeout": 1750,
                    "various_ongoing": 23250,
                    "monitor_visit_fee": 0
                },
                "passthrough_total": 57000,
                "total": 889000
            },
            "totals": {
                "service": 1081000,
                "passthrough": 106000,
                "grand_total": 1187000
            }
        }
    },
    "global": {
        "country": "GLOBAL",
        "startup": {
            "service": {
                "questionnaire_development": 1818,
                "eu_part1_dossier": 19690,
                "eu_legal_rep_setup": 1500,
                "external_kickoff": 3307.5,
                "investigator_meeting_global": 5916,
                "client_calls": 19760,
                "project_management_plan": 4242,
                "tmf_management_plan": 2020,
                "monitoring_plan": 2828,
                "unblinded_monitoring_plan": 1414,
                "risk_management_plan": 2020,
                "deviation_handling_plan": 1616,
                "quality_management_plan": 2020,
                "vendors_setup": 7272,
                "team_setup": 1616,
                "team_training": 8080,
                "internal_communication": 2424,
                "tmf_maintenance": 2598,
                "vendor_management": 4848,
                "tracking_reporting": 2886,
                "budget_invoicing": 2886,
                "protocol_checklist": 2148,
                "tmf_audit_initial": 2864,
                "tmf_audit_unblinded_initial": 1432,
                "passthrough_management": 1848,
                "eu_ctis_minor": 2864
            },
            "service_total": 112000,
            "passthrough": [],
            "passthrough_total": 0,
            "total": 112000
        },
        "active": {
            "service": {
                "eu_legal_rep": 46500,
                "eu_ctis_major": 5728,
                "periodic_safety_ra": 4296,
                "client_calls": 23560,
                "project_management_plan_update": 4199.58,
                "tmf_management_plan_update": 1999.8000000000002,
                "monitoring_plan_update": 2799.7200000000003,
                "unblinded_monitoring_plan_update": 1399.8600000000001,
                "risk_management_plan_update": 1999.8000000000002,
                "deviation_handling_plan_update": 1599.84,
                "quality_management_plan_update": 1999.8000000000002,
                "team_retraining": 7272,
                "tmf_maintenance": 13423,
                "vendor_management": 25048,
                "tracking_reporting": 14911,
                "budget_invoicing": 14911,
                "internal_communication": 12524,
                "tmf_audit_annual": 9666,
                "tmf_audit_unblinded_annual": 9666,
                "passthrough_management": 9548
            },
            "service_total": 213000,
            "passthrough": [],
            "passthrough_total": 0,
            "total": 213000
        },
        "totals": {
            "service": 325000,
            "passthrough": 0,
            "grand_total": 325000
        }
    },
    "totals": {
        "startup": 1233000,
        "active": 3454000,
        "grand_total": 4687000
    }
}
```


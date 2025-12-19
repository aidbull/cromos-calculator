(function() {
    'use strict';

    const REGIONS = ['us', 'eu_cee', 'eu_west', 'non_eu'];
    const REGION_API_MAP = {
        'us': 'US',
        'eu_cee': 'EU_CEE', 
        'eu_west': 'EU_West',
        'non_eu': 'Non_EU'
    };

    function getInput(name) {
        const el = document.querySelector(`[name="${name}"]`);
        return el ? el.value : null;
    }

    function getNumeric(name, defaultVal = 0) {
        const val = getInput(name);
        if (!val) return defaultVal;
        return parseFloat(val.replace(',', '.').replace('%', '')) || defaultVal;
    }

    function getInt(name, defaultVal = 0) {
        return Math.round(getNumeric(name, defaultVal));
    }

    function collectFormData() {
        const countries = {};

        // Collect per-region data
        REGIONS.forEach(region => {
            const apiRegion = REGION_API_MAP[region];
            countries[apiRegion] = {
                sites: getInt(`sites_${region}`),
                patients: getInt(`patients_${region}`),
                countries_in_region: getInt(`countries_${region}`, 1),
                monitoring_onsite: getInt(`monitoring_onsite_${region}`),
                monitoring_remote: region === 'us' ? getInt(`monitoring_remote_${region}`) : 0,
                unblinded_visits: getInt(`unblinded_${region}`)
            };
        });

        // Check toggle buttons for active regions
        const toggleBtns = document.querySelectorAll('.calc-form-btn .calc-click-btn');
        toggleBtns.forEach((btn, idx) => {
            const isActive = btn.querySelector('.calc-click-btn__on.active');
            const region = REGIONS[idx];
            if (region && !isActive) {
                const apiRegion = REGION_API_MAP[region];
                countries[apiRegion].sites = 0;
                countries[apiRegion].patients = 0;
            }
        });

        // Visit type selects
        const selects = document.querySelectorAll('.calc-select-style select');
        const qualType = (selects[0]?.value === '1') ? 'on-site' : 'remote';
        const initType = (selects[1]?.value === '1') ? 'on-site' : 'remote';
        const closeType = (selects[2]?.value === '1') ? 'on-site' : 'remote';

        // SAE rate as decimal
        let saeRate = getNumeric('sae_rate', 15);
        if (saeRate > 1) saeRate = saeRate / 100;

        // SUSAR weeks - extract number from formatted string
        let susarsWeeks = 13;
        const susarVal = getInput('susars_weeks');
        if (susarVal) {
            const match = susarVal.match(/[\d.,]+/);
            if (match) {
                susarsWeeks = parseFloat(match[0].replace(',', '.')) || 13;
            }
        }

        return {
            enrollment_months: getNumeric('enrollment_months'),
            treatment_months: getNumeric('treatment_months'),
            followup_months: getNumeric('followup_months'),
            qualification_visit_type: qualType,
            initiation_visit_type: initType,
            closeout_visit_type: closeType,
            sae_rate: saeRate,
            susars_weeks: susarsWeeks,
            vendors: 1,
            countries: countries
        };
    }

    function formatCurrency(num) {
        if (!num || isNaN(num)) return '$0';
        return '$' + Math.round(num).toLocaleString('en-US').replace(/,/g, '.');
    }

    function displayResults(results) {
        const block2 = document.querySelector('.block-calc2');
        if (!block2) return;

        const sections = block2.querySelectorAll('.calc-form-wrapper1');
        const global = results.global || {};
        const countries = results.countries || {};
        const REGION_ORDER = ['US', 'EU_CEE', 'EU_West', 'Non_EU'];

        function getVal(region, phase, type) {
            return countries[region]?.[phase]?.[type + '_total'] || 0;
        }

        function fillRow(section, rowIndex, values) {
            if (!section) return;
            const rows = section.querySelectorAll('.calc-form-input');
            if (!rows[rowIndex]) return;
            const inputs = rows[rowIndex].querySelectorAll('input');
            values.forEach((val, idx) => {
                if (inputs[idx]) inputs[idx].value = formatCurrency(val);
            });
        }

        const gSS = global.startup?.service_total || 0;
        const gSP = global.startup?.passthrough_total || 0;
        const gAS = global.active?.service_total || 0;
        const gAP = global.active?.passthrough_total || 0;

        // Start-Up
        if (sections[0]) {
            const svc = [gSS, ...REGION_ORDER.map(r => getVal(r, 'startup', 'service'))];
            svc.push(svc.reduce((a,b) => a+b, 0));
            const pass = [gSP, ...REGION_ORDER.map(r => getVal(r, 'startup', 'passthrough'))];
            pass.push(pass.reduce((a,b) => a+b, 0));
            const sub = svc.map((v,i) => v + pass[i]);
            fillRow(sections[0], 0, svc);
            fillRow(sections[0], 1, pass);
            fillRow(sections[0], 2, sub);
        }

        // Active Phase
        if (sections[1]) {
            const svc = [gAS, ...REGION_ORDER.map(r => getVal(r, 'active', 'service'))];
            svc.push(svc.reduce((a,b) => a+b, 0));
            const pass = [gAP, ...REGION_ORDER.map(r => getVal(r, 'active', 'passthrough'))];
            pass.push(pass.reduce((a,b) => a+b, 0));
            const sub = svc.map((v,i) => v + pass[i]);
            fillRow(sections[1], 0, svc);
            fillRow(sections[1], 1, pass);
            fillRow(sections[1], 2, sub);
        }

        // Start-Up + Active
        if (sections[2]) {
            const svc = [gSS+gAS, ...REGION_ORDER.map(r => getVal(r,'startup','service') + getVal(r,'active','service'))];
            svc.push(svc.reduce((a,b) => a+b, 0));
            const pass = [gSP+gAP, ...REGION_ORDER.map(r => getVal(r,'startup','passthrough') + getVal(r,'active','passthrough'))];
            pass.push(pass.reduce((a,b) => a+b, 0));
            fillRow(sections[2], 0, svc);
            fillRow(sections[2], 1, pass);
        }

        // Grand Total
        if (sections[3]) {
            const grand = [gSS+gAS+gSP+gAP, ...REGION_ORDER.map(r => countries[r]?.totals?.grand_total || 0)];
            grand.push(grand.reduce((a,b) => a+b, 0));
            fillRow(sections[3], 0, grand);
        }

        block2.style.display = 'block';
    }

    async function calculate() {
        const data = collectFormData();
        console.log('Ballpark: Request', JSON.stringify(data, null, 2));

        try {
            const res = await fetch(ballparkConfig.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ballparkConfig.nonce
                },
                body: JSON.stringify(data)
            });

            if (!res.ok) throw new Error('API error: ' + res.status);

            const results = await res.json();
            console.log('Ballpark: Response', results);
            displayResults(results);
            return true;
        } catch (e) {
            console.error('Ballpark:', e);
            return false;
        }
    }

    function updateTotals() {
        // Countries total
        let total = 0;
        REGIONS.forEach(r => { total += getInt(`countries_${r}`); });
        const countriesTotal = document.querySelector('[name="countries_total"]');
        if (countriesTotal) countriesTotal.value = total;

        // Sites total
        total = 0;
        REGIONS.forEach(r => { total += getInt(`sites_${r}`); });
        const sitesTotal = document.querySelector('[name="sites_total"]');
        if (sitesTotal) sitesTotal.value = total;

        // Patients total
        total = 0;
        REGIONS.forEach(r => { total += getInt(`patients_${r}`); });
        const patientsTotal = document.querySelector('[name="patients_total"]');
        if (patientsTotal) patientsTotal.value = total;

        // Monitoring on-site total
        total = 0;
        REGIONS.forEach(r => { total += getInt(`monitoring_onsite_${r}`); });
        const onsiteTotal = document.querySelector('[name="monitoring_onsite_total"]');
        if (onsiteTotal) onsiteTotal.value = total;

        // Monitoring remote total (US only)
        const remoteTotal = document.querySelector('[name="monitoring_remote_total"]');
        if (remoteTotal) remoteTotal.value = getInt('monitoring_remote_us');

        // Unblinded total
        total = 0;
        REGIONS.forEach(r => { total += getInt(`unblinded_${r}`); });
        const unblindedTotal = document.querySelector('[name="unblinded_total"]');
        if (unblindedTotal) unblindedTotal.value = total;
    }

    function setupSusarFormatting() {
        const input = document.querySelector('[name="susars_weeks"]');
        if (!input) return;

        input.addEventListener('blur', function() {
            const rawVal = this.value.replace(/[^\d.,]/g, '').replace(',', '.');
            const num = parseFloat(rawVal);
            if (!isNaN(num) && num > 0) {
                this.value = 'every ' + num.toString().replace('.', ',') + ' wk(s)';
            }
        });

        input.addEventListener('focus', function() {
            const match = this.value.match(/[\d.,]+/);
            if (match) this.value = match[0];
            this.select();
        });

        // Initial format
        const val = parseFloat(input.value.replace(',', '.'));
        if (!isNaN(val) && val > 0) {
            input.value = 'every ' + val.toString().replace('.', ',') + ' wk(s)';
        }
    }

    // Init
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.querySelector('.block-calc1')) return;

        const successWrapper = document.querySelector('.calc-success-wrapper');
        const resultsSection = document.querySelector('.block-calc2');

        if (resultsSection) resultsSection.style.display = 'none';

        document.addEventListener('wpcf7mailsent', function() {
            if (successWrapper) successWrapper.style.display = 'block';
        });

        const seeBtn = document.querySelector('.calc-btn-wrp2 .calc-btn');
        if (seeBtn) {
            seeBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                const ok = await calculate();
                if (ok && resultsSection) {
                    resultsSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }

        document.querySelectorAll('.calc-click-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.querySelector('.calc-click-btn__on')?.classList.toggle('active');
                this.querySelector('.calc-click-btn__off')?.classList.toggle('active');
            });
        });

        // Listen for input changes
        document.querySelector('.block-calc1')?.addEventListener('input', updateTotals);
        
        updateTotals();
        setupSusarFormatting();
    });
})();

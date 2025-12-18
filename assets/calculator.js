(function() {
    'use strict';

    const REGION_MAP = {0: 'US', 1: 'EU_CEE', 2: 'EU_West', 3: 'Non_EU'};
    const REGION_ORDER = ['US', 'EU_CEE', 'EU_West', 'Non_EU'];

    function collectFormData() {
        const countries = {};
        
        // 1. Toggle buttons - which regions are active
        const toggleBtns = document.querySelectorAll('.calc-form-btn .calc-click-btn');
        const activeRegions = [];
        toggleBtns.forEach((btn, idx) => {
            if (btn.querySelector('.calc-click-btn__on.active') && REGION_MAP[idx]) {
                activeRegions.push(REGION_MAP[idx]);
            }
        });

        // 2. Countries / Sites / Patients inputs
        const hiddenForm = document.querySelector('.calc-form-hidden');
        if (hiddenForm) {
            hiddenForm.querySelectorAll('.calc-form-input').forEach(row => {
                const label = row.querySelector('.title-loc');
                if (!label) return;
                
                const labelText = label.textContent.trim().toLowerCase();
                const inputs = row.querySelectorAll('.calc-form-item input');
                
                inputs.forEach((input, colIdx) => {
                    if (colIdx >= 4) return;
                    const region = REGION_MAP[colIdx];
                    if (!region) return;
                    
                    if (!countries[region]) {
                        countries[region] = {
                            sites: 0, patients: 0, countries_in_region: 1,
                            monitoring_onsite: 0, monitoring_remote: 0, unblinded_visits: 0
                        };
                    }
                    
                    const val = parseInt(input.value) || 0;
                    if (labelText.includes('countries')) countries[region].countries_in_region = val;
                    else if (labelText.includes('sites')) countries[region].sites = val;
                    else if (labelText.includes('patients')) countries[region].patients = val;
                });
            });
        }

        // 3. Duration inputs
        let enrollment = 0, treatment = 0, followup = 0;
        document.querySelectorAll('.calc-form-wrapper1 > .calc-form .calc-form-input').forEach(row => {
            const label = row.querySelector('.title-loc');
            const input = row.querySelector('input');
            if (!label || !input) return;
            
            const labelText = label.textContent.trim().toLowerCase();
            const val = parseFloat(input.value.replace(',', '.')) || 0;
            
            if (labelText.includes('enrollment')) enrollment = val;
            else if (labelText.includes('treatment')) treatment = val;
            else if (labelText.includes('follow')) followup = val;
        });

        // 4. Visits section
        let monitoringOnsite = 0, monitoringRemote = 0, unblindedVisits = 0;
        let saeRate = 0.15, susarsWeeks = 13;
        
        const visitsSection = document.querySelector('.calc-form-wrapper2');
        if (visitsSection) {
            visitsSection.querySelectorAll('.calc-form-input').forEach(row => {
                const label = row.querySelector('.title-loc');
                const input = row.querySelector('input');
                if (!label || !input) return;
                
                const labelText = label.textContent.trim().toLowerCase();
                const rawVal = input.value.replace(',', '.').replace('%', '').trim();
                
                if (labelText.includes('monitoring') && labelText.includes('on-site')) {
                    monitoringOnsite = parseInt(rawVal) || 0;
                } else if (labelText.includes('monitoring') && labelText.includes('remote')) {
                    monitoringRemote = parseInt(rawVal) || 0;
                } else if (labelText.includes('unblinded')) {
                    unblindedVisits = parseInt(rawVal) || 0;
                } else if (labelText.includes('sae')) {
                    saeRate = (parseFloat(rawVal) || 15) / 100;
                } else if (labelText.includes('susar')) {
                    const match = rawVal.match(/[\d.]+/);
                    susarsWeeks = match ? parseInt(match[0]) || 13 : 13;
                }
            });
        }

        // 5. Apply visits to active regions
        Object.keys(countries).forEach(region => {
            if (countries[region].sites > 0 && activeRegions.includes(region)) {
                countries[region].monitoring_onsite = monitoringOnsite;
                countries[region].monitoring_remote = monitoringRemote;
                countries[region].unblinded_visits = unblindedVisits;
            } else if (!activeRegions.includes(region)) {
                countries[region].sites = 0;
                countries[region].patients = 0;
            }
        });

        // 6. Visit type selects
        const selects = document.querySelectorAll('.calc-select-style select');
        const qualType = (selects[0]?.value === '1') ? 'on-site' : 'remote';
        const initType = (selects[1]?.value === '1') ? 'on-site' : 'remote';
        const closeType = (selects[2]?.value === '1') ? 'on-site' : 'remote';

        return {
            enrollment_months: Math.round(enrollment),
            treatment_months: Math.round(treatment),
            followup_months: Math.round(followup),
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
        if (!data) return false;

        console.log('Ballpark: Request', data);

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
        document.querySelectorAll('.calc-form-hidden .calc-form-input').forEach(row => {
            const inputs = row.querySelectorAll('.calc-form-item input');
            if (inputs.length < 5) return;
            let total = 0;
            for (let i = 0; i < 4; i++) total += parseInt(inputs[i].value) || 0;
            inputs[4].value = total;
        });
    }

    // Init
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.querySelector('.block-calc1')) return;

        const successWrapper = document.querySelector('.calc-success-wrapper');
        const resultsSection = document.querySelector('.block-calc2');

        // Hide results initially
        if (resultsSection) resultsSection.style.display = 'none';

        // CF7 success → show "See the results" button
        document.addEventListener('wpcf7mailsent', function() {
            if (successWrapper) successWrapper.style.display = 'block';
        });

        // "See the results" → calculate and show
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

        // Toggle buttons
        document.querySelectorAll('.calc-click-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.querySelector('.calc-click-btn__on')?.classList.toggle('active');
                this.querySelector('.calc-click-btn__off')?.classList.toggle('active');
            });
        });

        // Auto-update totals
        const hidden = document.querySelector('.calc-form-hidden');
        if (hidden) hidden.addEventListener('input', updateTotals);
        updateTotals();
    });
})();

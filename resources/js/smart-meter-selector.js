document.addEventListener('alpine:init', () => {
    Alpine.data('smartMeterSelector', () => ({
        open: false,
        selectedMeter: null,
        search: '',
        smartMeters: [],
        
        init() {
            if (this.initialMeter) {
                this.selectedMeter = this.initialMeter;
                this.search = this.selectedMeter.meter_id + ' - ' + this.selectedMeter.location;
            }
        },
        
        filteredMeters() {
            if (!this.search) return this.smartMeters;
            
            const searchLower = this.search.toLowerCase();
            return this.smartMeters.filter(meter => 
                meter.meter_id.toLowerCase().includes(searchLower) || 
                meter.location.toLowerCase().includes(searchLower)
            );
        },
        
        selectMeter(meter) {
            this.selectedMeter = meter;
            this.search = meter.meter_id + ' - ' + meter.location;
            this.open = false;
        },
        
        clearSelection() {
            this.selectedMeter = null;
            this.search = '';
        }
    }));
});
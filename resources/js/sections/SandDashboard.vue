<template>
  <div class="sand-dashboard">
    <div class="row g-3 mb-4">
      <div class="col-md-3" v-for="card in summaryCards" :key="card.label">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="text-muted mb-1 small">{{ card.label }}</p>
                <h3 class="mb-0 fw-bold">{{ card.value }}</h3>
              </div>
              <div class="rounded-3 p-2" :style="{ backgroundColor: card.bgColor }">
                <i :class="card.icon" :style="{ color: card.color }" style="font-size: 1.5rem"></i>
              </div>
            </div>
            <small class="text-muted">{{ card.subtext }}</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-8">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $t('Revenue Trend') }}</h5>
            <select class="form-select form-select-sm w-auto" v-model="revenueRange">
              <option value="12">{{ $t('Last 12 Months') }}</option>
              <option value="6">{{ $t('Last 6 Months') }}</option>
              <option value="3">{{ $t('Last Quarter') }}</option>
            </select>
          </div>
          <div class="card-body">
            <div ref="revenueChart" style="height: 300px"></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h5 class="mb-0">{{ $t('Booking Funnel') }}</h5>
          </div>
          <div class="card-body">
            <div ref="funnelChart" style="height: 300px"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h5 class="mb-0">{{ $t('Escrow Balance (30 Days)') }}</h5>
          </div>
          <div class="card-body">
            <div ref="escrowChart" style="height: 250px"></div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h5 class="mb-0">{{ $t('Insurance Status') }}</h5>
          </div>
          <div class="card-body">
            <div ref="insuranceChart" style="height: 250px"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import ApexCharts from 'apexcharts';

export default {
  data() {
    return {
      summaryCards: [
        { label: 'Inspection Requests', value: 0, icon: 'bi bi-clipboard-check', color: '#397D8D', bgColor: '#E8F2F5', subtext: '0 pending quotes' },
        { label: 'Held Payments', value: '0 SAR', icon: 'bi bi-shield-lock', color: '#C9A84C', bgColor: '#F8F3E0', subtext: '0 active escrows' },
        { label: 'Active Jobs', value: 0, icon: 'bi bi-gear', color: '#3CAE5C', bgColor: '#E8F5E9', subtext: '0 disputes' },
        { label: 'Insurance Balance', value: '0 SAR', icon: 'bi bi-shield-check', color: '#397D8D', bgColor: '#E8F2F5', subtext: '0 active, 0 pending' },
      ],
      revenueRange: '12',
      charts: { revenue: null, funnel: null, escrow: null, insurance: null },
    };
  },
  mounted() {
    this.fetchMetrics();
    this.fetchCharts();
  },
  methods: {
    async fetchMetrics() {
      try {
        const { data } = await axios.get('/api/admin/dashboard-metrics');
        if (data.status === 'true') {
          const m = data.data;
          this.summaryCards = [
            { label: this.$t('Inspection Requests'), value: m.inspection_requests, icon: 'bi bi-clipboard-check', color: '#397D8D', bgColor: '#E8F2F5', subtext: `${m.pending_quotes} ${this.$t('pending quotes')}` },
            { label: this.$t('Held Payments'), value: `${m.held_payments} SAR`, icon: 'bi bi-shield-lock', color: '#C9A84C', bgColor: '#F8F3E0', subtext: `${m.escrow_count_active} ${this.$t('active escrows')}` },
            { label: this.$t('Active Jobs'), value: m.active_jobs, icon: 'bi bi-gear', color: '#3CAE5C', bgColor: '#E8F5E9', subtext: `${m.disputes} ${this.$t('disputes')}` },
            { label: this.$t('Insurance Balance'), value: `${m.insurance_total_held} SAR`, icon: 'bi bi-shield-check', color: '#397D8D', bgColor: '#E8F2F5', subtext: `${m.insurance_active} ${this.$t('active')}, ${m.insurance_pending} ${this.$t('pending')}` },
          ];
        }
      } catch (e) { console.error('Sand dashboard metrics error:', e); }
    },
    async fetchCharts() {
      try {
        const { data } = await axios.get('/api/admin/dashboard-charts');
        if (data.status === 'true') {
          this.$nextTick(() => {
            this.renderRevenueChart(data.data.revenue_trend);
            this.renderFunnelChart(data.data.booking_funnel);
            this.renderEscrowChart(data.data.escrow_trend);
            this.renderInsuranceChart(data.data.insurance_status);
          });
        }
      } catch (e) { console.error('Sand dashboard charts error:', e); }
    },
    renderRevenueChart(chartData) {
      if (this.charts.revenue) this.charts.revenue.destroy();
      this.charts.revenue = new ApexCharts(this.$refs.revenueChart, {
        chart: { type: 'area', height: 300, toolbar: { show: false } },
        colors: ['#397D8D'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0 } },
        series: [{ name: this.$t('Revenue'), data: chartData.data }],
        xaxis: { categories: chartData.labels, labels: { style: { colors: '#6c757d' } } },
        yaxis: { labels: { formatter: v => `${v} SAR` } },
        tooltip: { y: { formatter: v => `${v} SAR` } },
        grid: { borderColor: '#f1f1f1' },
      });
      this.charts.revenue.render();
    },
    renderFunnelChart(chartData) {
      if (this.charts.funnel) this.charts.funnel.destroy();
      this.charts.funnel = new ApexCharts(this.$refs.funnelChart, {
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        colors: ['#397D8D', '#4A9AAD', '#3CAE5C', '#C9A84C', '#FB2F2F'],
        plotOptions: { bar: { borderRadius: 4, horizontal: true, distributed: true } },
        series: [{ data: chartData.map(s => s.count) }],
        xaxis: { categories: chartData.map(s => s.stage) },
        grid: { borderColor: '#f1f1f1' },
      });
      this.charts.funnel.render();
    },
    renderEscrowChart(chartData) {
      if (this.charts.escrow) this.charts.escrow.destroy();
      this.charts.escrow = new ApexCharts(this.$refs.escrowChart, {
        chart: { type: 'line', height: 250, toolbar: { show: false } },
        colors: ['#397D8D', '#3CAE5C'],
        series: chartData.series,
        xaxis: { categories: chartData.labels, labels: { style: { colors: '#6c757d' } } },
        grid: { borderColor: '#f1f1f1' },
      });
      this.charts.escrow.render();
    },
    renderInsuranceChart(chartData) {
      if (this.charts.insurance) this.charts.insurance.destroy();
      this.charts.insurance = new ApexCharts(this.$refs.insuranceChart, {
        chart: { type: 'donut', height: 250 },
        labels: [this.$t('Active'), this.$t('Pending'), this.$t('Frozen'), this.$t('Refunded')],
        series: [chartData.active, chartData.pending, chartData.frozen, chartData.refunded],
        colors: ['#3CAE5C', '#faa938', '#FB2F2F', '#6c757d'],
        legend: { position: 'bottom' },
      });
      this.charts.insurance.render();
    },
  },
};
</script>

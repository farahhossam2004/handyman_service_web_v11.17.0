import re

with open('resources/views/dashboard/dashboard.blade.php', 'r') as f:
    content = f.read()

start_marker = '<div class="col-md-12">\n                <div class="row">'
end_marker = '                </div>\n            </div>\n            <div class="col-md-12">\n                <div class="card">'

replacement = """<div class="col-md-12">
                @php
                $sandCards = [
                    ['title' => 'Total Inspection Requests', 'value' => $data['dashboard']['count_total_inspection_requests'] ?? 0, 'icon' => 'fas fa-search', 'color' => 'bg-primary'],
                    ['title' => 'Pending Quotes', 'value' => $data['dashboard']['count_pending_quotes'] ?? 0, 'icon' => 'fas fa-file-invoice', 'color' => 'bg-warning'],
                    ['title' => 'Approved Quotes', 'value' => $data['dashboard']['count_approved_quotes'] ?? 0, 'icon' => 'fas fa-file-invoice-dollar', 'color' => 'bg-success'],
                    ['title' => 'Active Orders', 'value' => $data['dashboard']['count_active_orders'] ?? 0, 'icon' => 'fas fa-briefcase', 'color' => 'bg-info'],
                    ['title' => 'Completed Orders', 'value' => $data['dashboard']['count_completed_orders'] ?? 0, 'icon' => 'fas fa-check-circle', 'color' => 'bg-success'],
                    ['title' => 'Cancelled Orders', 'value' => $data['dashboard']['count_cancelled_orders'] ?? 0, 'icon' => 'fas fa-times-circle', 'color' => 'bg-danger'],
                    ['title' => 'Held Payments', 'value' => $data['dashboard']['count_held_payments'] ?? 0, 'icon' => 'fas fa-lock', 'color' => 'bg-warning'],
                    ['title' => 'Released Payments', 'value' => $data['dashboard']['count_released_payments'] ?? 0, 'icon' => 'fas fa-unlock', 'color' => 'bg-success'],
                    ['title' => 'Refunded Payments', 'value' => $data['dashboard']['count_refunded_payments'] ?? 0, 'icon' => 'fas fa-undo', 'color' => 'bg-danger'],
                    ['title' => 'Active Providers', 'value' => $data['dashboard']['count_active_providers'] ?? 0, 'icon' => 'fas fa-users', 'color' => 'bg-primary'],
                    ['title' => 'Elite Technicians', 'value' => $data['dashboard']['count_elite_technicians'] ?? 0, 'icon' => 'fas fa-medal', 'color' => 'bg-warning'],
                    ['title' => 'Total Platform Revenue', 'value' => getPriceFormat($data['total_revenue'] ?? 0), 'icon' => 'fas fa-chart-line', 'color' => 'bg-success'],
                ];
                @endphp
                <div class="row">
                    @foreach($sandCards as $card)
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0 text-muted">{{ $card['title'] }}</p>
                                        <h4 class="mb-0 fw-bold">{{ $card['value'] }}</h4>
                                    </div>
                                    <div class="icon-shape rounded-circle {{ $card['color'] }} text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="{{ $card['icon'] }} fs-5"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="col-md-12">
                <div class="card">"""

# Use regex to find and replace
pattern = re.compile(r'<div class="col-md-12">\s*<div class="row">.*?</div>\s*</div>\s*<div class="col-md-12">\s*<div class="card">', re.DOTALL)
new_content = pattern.sub(replacement, content)

with open('resources/views/dashboard/dashboard.blade.php', 'w') as f:
    f.write(new_content)

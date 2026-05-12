<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="datatable" class="table table-striped" data-url="{{ route('quote.index_data') }}">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.booking_id') }}</th>
                                        <th>{{ __('messages.provider') }}</th>
                                        <th>{{ __('messages.price') }}</th>
                                        <th>{{ __('messages.notes') ?? 'Notes' }}</th>
                                        <th>{{ __('messages.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @section('bottom_script')
        <script>
            $(document).ready(function() {
                var table = $('#datatable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: $('#datatable').data('url'),
                    },
                    columns: [
                        {data: 'booking_id', name: 'booking_id'},
                        {data: 'provider_id', name: 'provider.display_name'},
                        {data: 'price', name: 'price'},
                        {data: 'notes', name: 'notes'},
                        {data: 'status', name: 'status'},
                    ]
                });
            });
        </script>
    @endsection
</x-master-layout>

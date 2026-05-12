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
                            <table id="datatable" class="table table-striped" data-url="{{ route('elite.index_data') }}">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.provider_name') }}</th>
                                        <th>{{ __('messages.email') }}</th>
                                        <th>{{ __('messages.rating') }}</th>
                                        <th>{{ __('messages.complaint_count') }}</th>
                                        <th>{{ __('messages.elite_technician') ?? 'Elite Technician' }}</th>
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
                        {data: 'display_name', name: 'display_name'},
                        {data: 'email', name: 'email'},
                        {data: 'rating', name: 'rating', searchable: false},
                        {data: 'complaint_count', name: 'complaint_count'},
                        {data: 'is_elite', name: 'is_elite'},
                    ]
                });

                $(document).on('change', '.change_elite_status', function() {
                    var is_elite = $(this).prop('checked') == true ? 1 : 0;
                    var id = $(this).data('id');
                    $.ajax({
                        type: "POST",
                        dataType: "json",
                        url: "{{ route('elite.toggle') }}",
                        data: {
                            '_token': '{{ csrf_token() }}',
                            'is_elite': is_elite,
                            'id': id
                        },
                        success: function(data){
                            if(data.status){
                                toastr.success(data.message);
                            } else {
                                toastr.error('Failed to update status');
                            }
                        }
                    });
                });
            });
        </script>
    @endsection
</x-master-layout>

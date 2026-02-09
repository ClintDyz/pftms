@extends('layouts.app')

@section('main-content')

<link href="{{ asset('datatables/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<link href="{{ asset('datatables/css/bootstrap.css') }}" rel="stylesheet">

<style>
    /* Pagination styling */
    .pagination {
        margin: 15px 0;
    }

    .pagination .page-link {
        color: #007bff;
        border: 1px solid #dee2e6;
        padding: 8px 12px;
        margin: 0 2px;
        border-radius: 4px;
    }

    .pagination .page-link:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    .pagination .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        pointer-events: none;
        background-color: #fff;
        border-color: #dee2e6;
    }
</style>

<div class="row wow animated fadeIn">
    <section class="mb-5 col-12 module-container">
        <div class="card mdb-color darken-3">
            <div class="card-body">
                <h5 class="card-title white-text">
                    <strong>
                        <i class="fas fa-box"></i> &#8594;
                        Inventory of Property Acknowledgement Receipt (PAR)
                    </strong>
                </h5>
                <hr class="white hidden-xs">
                <ul class="breadcrumb mdb-color darken-3 mb-0 p-1 white-text hidden-xs">
                    <li>
                        <i class="fa fa-caret-right mx-2" aria-hidden="true"></i>
                    </li>
                    <li class="active">
                        <a href="/par" class="waves-effect waves-light cyan-text">
                            Inventory of Property Acknowledgement Receipt (PAR)
                        </a>
                    </li>
                </ul>

                <!-- Table with panel -->
                <div class="card card-cascade narrower">

                    <!--Card image-->
                    <div class="gradient-card-header unique-color narrower py-2 px-2 mb-1 d-flex justify-content-between align-items-center">
                        <div>
                           {{-- <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="modal" data-target=".modal">
                                    <i class="fas fa-pencil-alt"></i> Create
                           </button> --}}
                        </div>
                        <div>
                            <a href="/par" class="btn btn-outline-white btn-rounded btn-sm px-2">
                                <i class="fas fa-sync-alt fa-pulse"></i>
                            </a>
                        </div>
                    </div>
                    <!--/Card image-->

                    <div class="px-2">
                        <div class="table-wrapper table-responsive border rounded">

                            <!--Table-->
                            @if ($description->isEmpty())
                                <p class="text-center p-4">No records found.</p>
                            @else
                                <table class="table table-striped table-bordered table-hover" style="width:100%">
                                    <!--Table head-->
                                    <thead class="mdb-color darken-3 mb-0 p-1 white-text">
                                        <tr>
                                            <th>No.</th>
                                            <th>Description</th>
                                            <th>PR_NO</th>
                                            <th>Inventory No.</th>
                                            <th>Quantity</th>
                                            <th>Unit Value</th>
                                            <th>Total Cost</th>
                                            <th>Funding</th>
                                            <th>Acquisition Date</th>
                                            <th>Classification Name</th>
                                            <th>Issued To</th>
                                            <th>Care of To</th>
                                            <th>Date of Issuance</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($description as $item)
                                            <tr>
                                                <td>
                                                    <span class="item_class">{{ ($description->currentPage() - 1) * $description->perPage() + $loop->iteration }}</span>
                                                </td>
                                                <td>
                                                    <input type="hidden" id="id" value="{!! $item->id !!}">
                                                    <span class="item_class">{!! $item->description ?? 'N/A' !!}</span>
                                                </td>
                                                <td>
                                                    <span class="date">{{ $item->pr_no ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="date">{{ $item->inventory_no ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="date">{{ $item->quantity ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="date">₱{{ number_format($item->unit_cost ?? 0, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="date">₱{{ number_format($item->total_cost ?? 0, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="date">{{ $item->sector_name ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="date">{{ $item->date_po ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="date">{{ $item->classification_name ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="date">
                                                        @if($item->firstname || $item->lastname)
                                                            {{ $item->firstname }}, {{ $item->lastname }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="care_of_to">{{ $item->care_of_to ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="date_of_issuance">{{ $item->date_of_issuance ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="status">{{ $item->status ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <a type="button" class="btn-floating btn-sm btn-orange p-2 waves-effect material-tooltip-main mr-0 jel-update-user" title="Update" data-placement="left" align="center">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="mdb-color darken-3 mb-0 p-1 white-text">
                                        <tr>
                                            <th>No.</th>
                                            <th>Description</th>
                                            <th>PR_NO</th>
                                            <th>Inventory No.</th>
                                            <th>Quantity</th>
                                            <th>Unit Value</th>
                                            <th>Total Cost</th>
                                            <th>Funding</th>
                                            <th>Acquisition Date</th>
                                            <th>Classification Name</th>
                                            <th>Issued To</th>
                                            <th>Care of To</th>
                                            <th>Date of Issuance</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            @endif
                            <!--Table-->
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if(!$description->isEmpty())
                    <div class="mt-3 px-3 pb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-0">
                                    Showing {{ $description->firstItem() }} to {{ $description->lastItem() }} of {{ $description->total() }} entries
                                </p>
                            </div>
                            <div>
                                {{ $description->links() }}
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
                <!-- Table with panel -->
            </div>
        </div>
    </section>
</div>

<!-- Update Modal -->
<div class="modal" id="update-user-mdl" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header stylish-color-dark white-text">
                <h5 class="modal-title" id="exampleModalLabel">Update</h5>
                <button type="button" class="close white-text" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/updatepar" method="post" autocomplete="off" enctype="multipart/form-data">
                {!! csrf_field() !!}
                <div class="modal-body">
                    <div class="form-row">
                        <input type="hidden" id="update-id" name="id" value="">
                        <label for="inputPassword3">Care of To <span style="color: red;">*</span></label>
                        <div class="col-sm-12 md-form form-sm">
                            <input type="text" class="form-control" id="u-care_of_to" placeholder="Search here.." name="care_of_to" list="list-item-class" required="">
                            <datalist id="list-item-class">
                                @foreach ($emp_accounts as $item_class)
                                    <option value="{!! $item_class->firstname!!} {!! $item_class->lastname !!}">
                                @endforeach
                            </datalist>
                        </div>
                        <label for="inputPassword3">Date of Issuance <span style="color: red;">*</span></label>
                        <div class="col-sm-12 md-form form-sm">
                            <input type="date" class="form-control" id="u-date_of_issuance" name="date_of_issuance" required="">
                        </div>
                        <label for="inputPassword3">Status <span style="color: red;">*</span></label>
                        <div class="col-sm-12 md-form form-sm">
                            <input type="text" class="form-control" id="u-status" name="status" required="">
                        </div>
                    </div>
                </div>
                <div class="modal-footer rgba-stylish-strong p-1">
                    <button type="button" class="btn btn-light btn-sm waves-effect" data-dismiss="modal">
                        <i class="far fa-window-close"></i> Close
                    </button>
                    <button type="submit" class="btn btn-orange btn-sm waves-effect waves-light">
                        <i class="fas fa-pencil-alt"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('custom-js')

<script src="{{ asset('datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('datatables/js/dataTables.bootstrap4.min.js') }}"></script>

<script>
    $('body').on('click', '.jel-update-user', function(event) {
        event.preventDefault();

        // Clear the values
        $('#u-care_of_to').val('');
        $('#u-date_of_issuance').val('');
        $('#u-status').val('');

        // Find closest tr
        var trJel = $(this).closest('tr');

        // Get the values
        var care_of_to = $(trJel).find('.care_of_to').html().trim();
        var id = $(trJel).find('#id').val();
        var date_of_issuance = $(trJel).find('.date_of_issuance').html().trim();
        var status = $(trJel).find('.status').html().trim();

        // Handle N/A values
        if (care_of_to === 'N/A') care_of_to = '';
        if (date_of_issuance === 'N/A') date_of_issuance = '';
        if (status === 'N/A') status = '';

        // Add values to the input fields
        $('#update-id').val(id);
        $('#u-care_of_to').val(care_of_to);
        $('#u-date_of_issuance').val(date_of_issuance);
        $('#u-status').val(status);

        $("#update-user-mdl").modal("show");
    });
</script>

{{-- DataTables removed since we're using Laravel pagination --}}

@endsection

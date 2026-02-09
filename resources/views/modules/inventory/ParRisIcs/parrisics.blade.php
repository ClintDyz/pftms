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
                        Inventory of All (PAR/RIS/ICS)
                    </strong>
                </h5>
                <hr class="white hidden-xs">
                <ul class="breadcrumb mdb-color darken-3 mb-0 p-1 white-text hidden-xs">
                    <li>
                        <i class="fa fa-caret-right mx-2" aria-hidden="true"></i>
                    </li>
                    <li class="active">
                        <a href="/parrisics" class="waves-effect waves-light cyan-text">
                            Inventory of All (PAR/RIS/ICS)
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
                            <a href="/parrisics" class="btn btn-outline-white btn-rounded btn-sm px-2">
                                <i class="fas fa-sync-alt fa-pulse"></i>
                            </a>
                        </div>
                    </div>
                    <!--/Card image-->

                    <div class="px-2">
                        <div class="table-wrapper table-responsive border rounded">

                            <!--Table-->
                            @if ($parrisics->isEmpty())
                                <p class="text-center p-4">No (PAR/RIS/ICS) found.</p>
                            @else
                                <table class="table table-striped table-bordered table-hover" style="width:100%">
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($parrisics as $item)
                                            <tr>
                                                <td>
                                                    <span class="item_class">{{ ($parrisics->currentPage() - 1) * $parrisics->perPage() + $loop->iteration }}</span>
                                                </td>
                                                <td>
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
                                        </tr>
                                    </tfoot>
                                </table>
                            @endif
                            <!--Table-->
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if(!$parrisics->isEmpty())
                    <div class="mt-3 px-3 pb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-0">
                                    Showing {{ $parrisics->firstItem() }} to {{ $parrisics->lastItem() }} of {{ $parrisics->total() }} entries
                                </p>
                            </div>
                            <div>
                                {{ $parrisics->links() }}
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

<!--Create Modal -->
<div class="modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header stylish-color-dark white-text">
                <h5 class="modal-title" id="exampleModalLabel">Create Equipment Name (Classifications)</h5>
                <button type="button" class="close white-text" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="/create" method="post" autocomplete="off" enctype="multipart/form-data">
                    {!! csrf_field() !!}
                    <div class="form-row">
                        <div class="md-form form-sm col-md-12">
                            <label for="inputEmail4">Equipment Name (Classifications) <span style="color: red;">*</span></label>
                            <input type="text" name="item_class" class="form-control" id="inputEmail4" required="">
                        </div>
                    </div>
            </div>
            <div class="modal-footer rgba-stylish-strong p-1">
                <button type="button" class="btn btn-light btn-sm waves-effect" data-dismiss="modal">
                    <i class="far fa-window-close"></i> Close</button>
                <button type="submit" class="btn btn-success btn-sm waves-effect waves-light">
                    <i class="fas fa-file-import"></i> Create</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Modal -->
<div class="modal" id="update-user-mdl" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header stylish-color-dark white-text">
                <h5 class="modal-title" id="exampleModalLabel">Update Equipment Name (Classifications)</h5>
                <button type="button" class="close white-text" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/update" method="post" autocomplete="off" enctype="multipart/form-data">
                {!! csrf_field() !!}
                <div class="modal-body">
                    <div class="form-row">
                        <label for="inputPassword3">Equipment Name (Classifications) <span style="color: red;">*</span></label>
                        <div class="col-sm-12 md-form form-sm">
                            <input type="hidden" id="update-id" name="id" value="">
                            <input type="text" class="form-control" id="u-item_class" name="item_class" required="">
                        </div>
                    </div>
                </div>
                <div class="modal-footer rgba-stylish-strong p-1">
                    <button type="button" class="btn btn-light btn-sm waves-effect" data-dismiss="modal">
                        <i class="far fa-window-close"></i> Close</button>
                    <button type="submit" class="btn btn-orange btn-sm waves-effect waves-light">
                        <i class="fas fa-pencil-alt"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE Modal -->
<div class="modal" id="delate-user-mdl" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header danger-color-dark white-text">
                <h5 class="modal-title"><span class="fas fa-trash"> </span> Delete Equipment Name (Classifications)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="/delete" method="post">
                    {!! csrf_field() !!}
                    <input type="hidden" id="delate_id" name="id" value="">
                    <h6 class="sndbox-del-con">Are You Sure To Delete?</h6>
            </div>
            <div class="modal-footer p-1">
                <button type="button" class="btn btn-light btn-sm waves-effect" data-dismiss="modal">
                    <span class="fas fa-window-close"></span> Close</button>
                <button type="submit" class="btn btn-red btn-sm waves-effect waves-light">
                    <i class="fas fa-trash"></i> Delete</button>
            </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('custom-js')

<script src="{{ asset('datatables/js/jquery.min.js') }}"></script>
<script src="{{ asset('datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('datatables/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="new/js/item.js"></script>

{{-- DataTables removed since we're using Laravel pagination --}}

@endsection

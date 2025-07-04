@extends('layouts.app')

@section('main-content')

<link href="{{ asset('datatables/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<link href="{{ asset('datatables/css/bootstrap.css') }}" rel="stylesheet">

<div class="row wow animated fadeIn">
    <section class="mb-5 col-12 module-container">
        <div class="card mdb-color darken-3">
            <div class="card-body">
                <h5 class="card-title white-text">
                    <strong>
                        <i class="fas fa-box"></i> &#8594;
                        Purchase Request Monthly Report
                    </strong>
                </h5>
                <hr class="white hidden-xs">
                <ul class="breadcrumb mdb-color darken-3 mb-0 p-1 white-text hidden-xs">
                    <li>
                        <i class="fa fa-caret-right mx-2" aria-hidden="true"></i>
                    </li>
                    <li class="active">
                        <a href="/purchase-request-monthly-report" class="waves-effect waves-light cyan-text">
                            Purchase Request Monthly Report
                        </a>
                    </li>
                </ul>
                <br>
                <br>

                <div class="row">
                <form method="GET" action="{{ route('report.index') }}" class="form-inline mb-3">
                    <label class="mr-2 white-text ">Filter by Month:</label>

                    <select name="month" class="form-control mr-2">
                        <option value="">Select Month</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endfor
                    </select>

                    <select name="year" class="form-control mr-2">
                        <option value="">Select Year</option>
                        @for ($y = now()->year; $y >= 2000; $y--)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>

                </form>

                <form method="GET" action="{{ route('report.purchase.print') }}" target="_blank" class="mb-3">
                        <input type="hidden" name="month" value="{{ request('month') }}">
                        <input type="hidden" name="year" value="{{ request('year') }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-print"></i> Print PDF
                        </button>
                    </form>

                </div>

                <!-- Table with panel -->
                <div class="card card-cascade narrower">

                    <!--Card image-->
                    <div class="gradient-card-header unique-color
                                narrower py-2 px-2 mb-1 d-flex justify-content-between
                                align-items-center">
                        <div>
                           {{-- <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="modal" data-target=".modal">
                                    <i class="fas fa-pencil-alt"></i> Create
                           </button> --}}
                        </div>
                        <div>

                            <a href="/purchase-request-monthly-report" class="btn btn-outline-white btn-rounded btn-sm px-2">
                                <i class="fas fa-sync-alt fa-pulse"></i>
                            </a>
                        </div>
                    </div>
                    <!--/Card image-->

                    <div class="px-2">
                        <div class="table-wrapper table-responsive border rounded">

                    <!--Table-->
                    <table id="Table" class="table table-striped table-bordered table-hover" style="width:100%">

                        <!--Table head-->
                        <thead class="mdb-color darken-3 mb-0 p-1 white-text">
                            <tr>
                                <th>Item No.</th>
                                <th>Purchase Request No</th>
                                <th>Particulars</th>
                                <th>Total Approved Budget for Contract (ABC)</th>
                                <th>Bids must be submitted to the following offices on or before</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($purchase as $purchaseItem)
                                <tr>
                                    <td>
                                        <input type="hidden" id="id" value="{{ $purchaseItem->id }}">
                                        <span class="item_class">{{ $loop->iteration }}</span>
                                    </td>
                                    <td>
                                        <span class="date">{{ $purchaseItem->pr_no }}</span>
                                    </td>
                                    <td>
                                        <span class="date">{{ $purchaseItem->purpose }}</span>
                                    </td>
                                    <td>
                                        <span class="date">₱{{ number_format($purchaseItem->total_cost, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="date">{{ \Carbon\Carbon::parse($purchaseItem->created_at)->format('F d, Y') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot class="mdb-color darken-3 mb-0 p-1 white-text">
                            <tr>
                                <th>Item No.</th>
                                <th>Purchase Request No</th>
                                <th>Particulars</th>
                                <th>Total Approved Budget for Contract (ABC)</th>
                                <th>Bids must be submitted to the following offices on or before</th>
                            </tr>
                        </tfoot>

                    </table>
                    <!--Table-->

                        </div>
                    </div>
                    <div class="mt-3">
                    </div>
                </div>
                <!-- Table with panel -->
            </div>
        </div>
    </section>
</div>



@endsection

@section('custom-js')

<script src="{{ asset('datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('datatables/js/dataTables.bootstrap4.min.js') }}"></script>

<script type="text/javascript">
	// Call the dataTables jQuery plugin
        $(document).ready(function() {
          $('#Table').DataTable();
        });
	</script>

@endsection

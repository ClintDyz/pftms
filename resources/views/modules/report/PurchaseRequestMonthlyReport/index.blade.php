@extends('layouts.app')

@section('main-content')

<link href="{{ asset('datatables/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<link href="{{ asset('datatables/css/bootstrap.css') }}" rel="stylesheet">

<style>
    .dataTables_wrapper .dataTables_length select {
        padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px;
    }
    .dataTables_wrapper .dataTables_filter input {
        padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px; margin-left: 5px;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 5px 10px; margin: 0 2px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f5f5f5; border-color: #999; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #007bff; color: white !important; border-color: #007bff;
    }
    .dataTables_wrapper .dataTables_info { padding-top: 8px; font-size: 14px; }

    .no-data-message {
        text-align: center; padding: 40px; background: #f8f9fa; border-radius: 5px; margin: 20px 0;
    }
    .no-data-message i { font-size: 48px; color: #6c757d; margin-bottom: 15px; display: block; }
    .no-data-message h5 { color: #495057; margin-bottom: 10px; }
    .no-data-message p  { color: #6c757d; }

    /* Period tabs */
    .period-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
    .period-tab {
        padding: 6px 18px; border-radius: 20px; border: 2px solid rgba(255,255,255,0.4);
        color: #fff; background: transparent; cursor: pointer; font-size: 13px; font-weight: 500;
        transition: all .2s; text-decoration: none;
    }
    .period-tab:hover { background: rgba(255,255,255,0.15); color: #fff; text-decoration: none; }
    .period-tab.active { background: #007bff; border-color: #007bff; color: #fff; }

    /* Filter panel */
    .filter-label { color: #fff; font-weight: 500; margin-bottom: 4px; font-size: 13px; }
    .filter-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; margin-bottom: 14px; }
    .filter-row .form-group { margin-bottom: 0; }
    .filter-row .form-control { min-width: 160px; }
    .filter-divider { color: #fff; font-size: 14px; padding-bottom: 6px; }

    .table-no-records td { padding: 30px !important; color: #6c757d; font-size: 14px; }
</style>

<div class="row wow animated fadeIn">
    <section class="mb-5 col-12 module-container">
        <div class="card mdb-color darken-3">
            <div class="card-body">

                <h5 class="card-title white-text">
                    <strong><i class="fas fa-box"></i> &#8594; Purchase Request Monthly Report</strong>
                </h5>
                <hr class="white hidden-xs">
                <ul class="breadcrumb mdb-color darken-3 mb-0 p-1 white-text hidden-xs">
                    <li><i class="fa fa-caret-right mx-2" aria-hidden="true"></i></li>
                    <li class="active">
                        <a href="{{ route('report.index') }}" class="waves-effect waves-light cyan-text">
                            Purchase Request Monthly Report
                        </a>
                    </li>
                </ul>
                <br><br>

                {{-- ── PERIOD TABS ── --}}
                @php $activePeriod = request('period', 'custom'); @endphp

                <div class="period-tabs">
                    {{-- <a href="{{ route('report.index', ['period' => 'daily']) }}"
                       class="period-tab {{ $activePeriod === 'daily' ? 'active' : '' }}">
                        <i class="fas fa-calendar-day mr-1"></i> Daily
                    </a>
                    <a href="{{ route('report.index', ['period' => 'weekly']) }}"
                       class="period-tab {{ $activePeriod === 'weekly' ? 'active' : '' }}">
                        <i class="fas fa-calendar-week mr-1"></i> Weekly
                    </a> --}}
                    <a href="{{ route('report.index', ['period' => 'monthly']) }}"
                       class="period-tab {{ $activePeriod === 'monthly' ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt mr-1"></i> Monthly
                    </a>
                    <a href="{{ route('report.index', ['period' => 'yearly']) }}"
                       class="period-tab {{ $activePeriod === 'yearly' ? 'active' : '' }}">
                        <i class="fas fa-calendar mr-1"></i> Yearly
                    </a>
                    <a href="{{ route('report.index', ['period' => 'custom']) }}"
                       class="period-tab {{ $activePeriod === 'custom' ? 'active' : '' }}">
                        <i class="fas fa-sliders-h mr-1"></i> Custom Date
                    </a>
                </div>

                {{-- ── MONTHLY FILTER: Month + Year selects ── --}}
                @if($activePeriod === 'monthly')
                <form method="GET" action="{{ route('report.index') }}" class="mb-3">
                    <input type="hidden" name="period" value="monthly">
                    <div class="filter-row">
                        <div class="form-group">
                            <div class="filter-label">Month:</div>
                            <select name="month" class="form-control">
                                <option value="">Select Month</option>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0,0,0,$m,1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="filter-label">Year:</div>
                            <select name="year" class="form-control">
                                <option value="">Select Year</option>
                                @for ($y = now()->year; $y >= 2000; $y--)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group align-self-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="{{ route('report.index', ['period' => 'monthly']) }}"
                               class="btn btn-secondary ml-1">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
                @endif

                {{-- ── YEARLY FILTER: Year select only ── --}}
                @if($activePeriod === 'yearly')
                <form method="GET" action="{{ route('report.index') }}" class="mb-3">
                    <input type="hidden" name="period" value="yearly">
                    <div class="filter-row">
                        <div class="form-group">
                            <div class="filter-label">Year:</div>
                            <select name="year" class="form-control">
                                <option value="">Select Year</option>
                                @for ($y = now()->year; $y >= 2000; $y--)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group align-self-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="{{ route('report.index', ['period' => 'yearly']) }}"
                               class="btn btn-secondary ml-1">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
                @endif

                {{-- ── CUSTOM DATE FILTER: From / To date pickers ── --}}
                @if($activePeriod === 'custom')
                <form method="GET" action="{{ route('report.index') }}" class="mb-3">
                    <input type="hidden" name="period" value="custom">
                    <div class="filter-row">
                        <div class="form-group">
                            <div class="filter-label">From Date:</div>
                            <input type="date" name="date_from" class="form-control"
                                   value="{{ request('date_from') }}">
                        </div>
                        <div class="filter-divider align-self-end pb-1">&#8212;</div>
                        <div class="form-group">
                            <div class="filter-label">To Date:</div>
                            <input type="date" name="date_to" class="form-control"
                                   value="{{ request('date_to') }}">
                        </div>
                        <div class="form-group align-self-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="{{ route('report.index', ['period' => 'custom']) }}"
                               class="btn btn-secondary ml-1">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
                @endif

                {{-- ── PRINT BUTTON ── --}}
                @if($hasFilter)
                <form method="GET" action="{{ route('report.purchase.print') }}" target="_blank" class="mb-3">
                    <input type="hidden" name="period"    value="{{ $activePeriod }}">
                    <input type="hidden" name="month"     value="{{ request('month') }}">
                    <input type="hidden" name="year"      value="{{ request('year') }}">
                    <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to"   value="{{ request('date_to') }}">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-print"></i> Print PDF
                    </button>
                </form>
                @endif

                {{-- ── NO FILTER STATE ── --}}
                @if(!$hasFilter)
                <div class="no-data-message">
                    <i class="fas fa-filter"></i>
                    <h5>No Filter Applied</h5>
                    <p>
                        @if($activePeriod === 'daily')   Today's data will show automatically once you click Daily.
                        @elseif($activePeriod === 'weekly')  This week's data will show automatically once you click Weekly.
                        @elseif($activePeriod === 'monthly') Select a month and year above, then click Filter.
                        @elseif($activePeriod === 'yearly')  Select a year above, then click Filter.
                        @else Select a date range above and click Filter.
                        @endif
                    </p>
                </div>

                @else

                    {{-- Active badge --}}
                    <div class="mb-2">
                        <span class="badge badge-info" style="font-size:13px; padding:6px 12px;">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            @if($activePeriod === 'daily')
                                Today - {{ now()->format('F d, Y') }}
                            @elseif($activePeriod === 'weekly')
                                This Week - {{ now()->startOfWeek()->format('M d') }} to {{ now()->copy()->endOfWeek()->format('M d, Y') }}
                            @elseif($activePeriod === 'monthly')
                                @if(request('month') && request('year'))
                                    {{ date('F', mktime(0,0,0,request('month'),1)) }} {{ request('year') }}
                                @elseif(request('year'))
                                    All months of {{ request('year') }}
                                @else
                                    {{ now()->format('F Y') }}
                                @endif
                            @elseif($activePeriod === 'yearly')
                                Year {{ request('year') ?? now()->year }}
                            @elseif(request('date_from') && request('date_to'))
                                {{ \Carbon\Carbon::parse(request('date_from'))->format('F d, Y') }}
                                &nbsp;-&nbsp;
                                {{ \Carbon\Carbon::parse(request('date_to'))->format('F d, Y') }}
                            @elseif(request('date_from'))
                                From {{ \Carbon\Carbon::parse(request('date_from'))->format('F d, Y') }}
                            @elseif(request('date_to'))
                                Up to {{ \Carbon\Carbon::parse(request('date_to'))->format('F d, Y') }}
                            @endif
                        </span>
                    </div>

                    {{-- TABLE CARD --}}
                    <div class="card card-cascade narrower">
                        <div class="gradient-card-header unique-color narrower py-2 px-2 mb-1
                                    d-flex justify-content-between align-items-center">
                            <div></div>
                            <div>
                                <a href="{{ route('report.index', array_filter([
                                        'period' => $activePeriod,
                                        'month'  => request('month'),
                                        'year'   => request('year'),
                                        'date_from' => request('date_from'),
                                        'date_to'   => request('date_to'),
                                    ])) }}"
                                   class="btn btn-outline-white btn-rounded btn-sm px-2">
                                    <i class="fas fa-sync-alt fa-pulse"></i>
                                </a>
                            </div>
                        </div>

                        <div class="px-2">
                            <div class="table-wrapper table-responsive border rounded">

                                @if($purchase->isEmpty())
                                {{-- Plain table, NO DataTables -- prevents column-count error --}}
                                <table class="table table-bordered" style="width:100%">
                                    <thead class="mdb-color darken-3 white-text">
                                        <tr>
                                            <th>Item No.</th>
                                            <th>Purchase Request No</th>
                                            <th>Particulars</th>
                                            <th>Total Approved Budget for Contract (ABC)</th>
                                            <th>Bids must be submitted to the following offices on or before</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="table-no-records">
                                            <td colspan="5" class="text-center">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block text-muted"></i>
                                                No records found for the selected period.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                @else
                                {{-- DataTables table -- only when records exist --}}
                                <table id="Table" class="table table-striped table-bordered table-hover" style="width:100%">
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
                                                <input type="hidden" value="{{ $purchaseItem->id }}">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td>{{ $purchaseItem->pr_no }}</td>
                                            <td>{{ $purchaseItem->purpose }}</td>
                                            <td>&#8369;{{ number_format($purchaseItem->total_cost, 2) }}</td>
                                            <td>{{ \Carbon\Carbon::parse($purchaseItem->created_at)->format('F d, Y') }}</td>
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
                                @endif

                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </section>
</div>

@endsection

@section('custom-js')

<script src="{{ asset('datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('datatables/js/dataTables.bootstrap4.min.js') }}"></script>

<script type="text/javascript">
$(document).ready(function () {

    /* Custom date-picker constraints */
    var dateFrom = document.querySelector('input[name="date_from"]');
    var dateTo   = document.querySelector('input[name="date_to"]');
    if (dateFrom && dateTo) {
        dateFrom.addEventListener('change', function () {
            dateTo.min = this.value;
            if (dateTo.value && dateTo.value < this.value) dateTo.value = this.value;
        });
        dateTo.addEventListener('change', function () { dateFrom.max = this.value; });
        if (dateFrom.value) dateTo.min   = dateFrom.value;
        if (dateTo.value)   dateFrom.max = dateTo.value;
    }

    /* Only initialize DataTables when records exist */
    @if($hasFilter && $purchase->isNotEmpty())
    if ($.fn.DataTable.isDataTable('#Table')) {
        $('#Table').DataTable().destroy();
    }
    $('#Table').DataTable({
        "destroy"    : true,
        "pageLength" : 10,
        "lengthMenu" : [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "ordering"   : true,
        "searching"  : true,
        "info"       : true,
        "autoWidth"  : false,
        "responsive" : true,
        "language": {
            "lengthMenu"   : "Show _MENU_ entries",
            "zeroRecords"  : "No matching records found",
            "info"         : "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty"    : "Showing 0 to 0 of 0 entries",
            "infoFiltered" : "(filtered from _MAX_ total entries)",
            "search"       : "Search:",
            "paginate"     : { "first":"First","last":"Last","next":"Next","previous":"Previous" }
        },
        "columnDefs" : [{ "orderable": false, "targets": 0 }],
        "order"      : [[4, "desc"]]
    });
    @endif

});
</script>

@endsection

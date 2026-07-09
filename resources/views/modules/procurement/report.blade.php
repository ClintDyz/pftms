@extends('layouts.app')

@section('main-content')

<style>
    /* ── Page wrapper ── */
    .pmr-wrapper {
        background-color: #0f1e35;
        min-height: 100vh;
        padding: 24px;
        color: #fff;
    }

    /* ── Title / breadcrumb ── */
    .pmr-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 18px;
        font-weight: 600;
        color: #fff;
        margin-bottom: 6px;
    }
    .pmr-breadcrumb {
        font-size: 13px;
        margin-bottom: 28px;
    }
    .pmr-breadcrumb a {
        color: #4fc3f7;
        text-decoration: none;
    }
    .pmr-breadcrumb a:hover { text-decoration: underline; }
    .pmr-breadcrumb .arrow { color: #4fc3f7; margin-right: 4px; }

    /* ── Toggle buttons ── */
    .filter-toggle-group {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .filter-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 22px;
        border-radius: 999px;
        border: 1.5px solid #4a5568;
        background: transparent;
        color: #cbd5e0;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all .2s;
        outline: none;
    }
    .filter-toggle-btn:hover {
        border-color: #90cdf4;
        color: #fff;
    }
    .filter-toggle-btn.active {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border-color: transparent;
        color: #fff;
        box-shadow: 0 2px 12px rgba(99,102,241,.5);
    }

    /* ── Filter panels ── */
    .filter-panel { display: none; margin-bottom: 20px; }
    .filter-panel.active { display: block; }

    .filter-row {
        display: flex;
        align-items: flex-end;
        gap: 14px;
        flex-wrap: wrap;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .filter-group label {
        font-size: 13px;
        color: #a0aec0;
        font-weight: 500;
        margin-bottom: 0;
    }
    .filter-group select,
    .filter-group input[type="date"] {
        background: #1a2942;
        border: 1.5px solid #2d3f5a;
        color: #e2e8f0;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 14px;
        min-width: 160px;
        height: 40px;
        outline: none;
        transition: border-color .2s;
        -webkit-appearance: none;
        appearance: none;
    }
    .filter-group select:focus,
    .filter-group input[type="date"]:focus { border-color: #ffffff; }
    .filter-group select option { background: #e2e8f0; color: #000000; }

    .date-dash {
        color: #a0aec0;
        font-size: 20px;
        line-height: 40px;
        padding-top: 22px; /* align with inputs after label */
    }

    /* ── Action buttons ── */
    .btn-filter {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 0 22px; height: 40px; margin-top: 22px;
        border-radius: 6px; border: none;
        background: #3b82f6; color: #fff;
        font-size: 14px; font-weight: 600; cursor: pointer;
        transition: background .2s; text-decoration: none;
    }
    .btn-filter:hover { background: #2563eb; }

    .btn-clear {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 0 22px; height: 40px; margin-top: 22px;
        border-radius: 6px; border: none;
        background: #8b5cf6; color: #fff;
        font-size: 14px; font-weight: 600; cursor: pointer;
        transition: background .2s; text-decoration: none;
    }
    .btn-clear:hover { background: #7c3aed; color: #fff; }

    .btn-export-csv {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 0 18px; height: 36px;
        border-radius: 6px; border: none;
        background: #059669; color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer;
        transition: background .2s; text-decoration: none;
    }
    .btn-export-csv:hover { background: #047857; color: #fff; }

    /* ── Results card ── */
    .results-card {
        background: #f8f9fa;
        border-radius: 10px;
        overflow: hidden;
    }

    /* Empty / no-filter state */
    .empty-state {
        text-align: center;
        padding: 70px 20px;
        color: #6b7280;
        background: #f9fafb;
    }
    .empty-state .empty-icon {
        font-size: 38px;
        margin-bottom: 14px;
        align-content: center;
        color: #374151;
    }
    .empty-state .empty-title {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 6px;
    }
    .empty-state .empty-sub {
        font-size: 14px;
        color: #6b7280;
    }

    /* Report header inside white card */
    .report-header-block {
        text-align: center;
        padding: 20px 20px 12px;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
    }
    .report-header-block h4 { font-weight: 700; margin-bottom: 4px; color: #111827; }
    .report-header-block h5 { margin-bottom: 3px; color: #374151; font-size: 15px; }

    /* Export bar */
    .export-bar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 8px 16px;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
    }

    /* Table */
    .pmr-table-wrap {
        overflow-x: auto;
        background: #fff;
    }
    .pmr-table {
        font-size: 11px;
        width: 100%;
        border-collapse: collapse;
        min-width: 1600px;
    }
    .pmr-table th {
        background-color: #4472C4;
        color: #fff;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        padding: 8px 4px;
        border: 1px solid #b0bec5;
    }
    .pmr-table td {
        padding: 5px 6px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
        color: #1a202c;
    }
    .pmr-table tbody tr:nth-child(even) td { background-color: #f3f4f6; }
    .pmr-table tbody tr:hover td { background-color: #dbeafe; }

    @media print {
        .pmr-wrapper { background: #fff; padding: 0; }
        .no-print { display: none !important; }
        .pmr-table { font-size: 9px; min-width: unset; }
    }
</style>

<div class="pmr-wrapper">

    {{-- ── Title & Breadcrumb ── --}}
    <div class="pmr-title no-print">
        <i class="fas fa-file-invoice"></i>
        <span>→ Purchase Request Monthly Report</span>
    </div>
    <div class="pmr-breadcrumb no-print">
        <span class="arrow">▶</span>
        <a href="{{ route('procurement.report.index') }}">Purchase Request Monthly Report</a>
    </div>

    {{-- ══════════════════════════════════════════
         FILTER FORM
    ══════════════════════════════════════════ --}}
    <form action="{{ route('procurement.report.generate') }}" method="GET" id="pmrForm" class="no-print">
        <input type="hidden" name="filter_mode" id="filter_mode" value="{{ $filterMode ?? 'monthly' }}">

        {{-- Toggle buttons --}}
        <div class="filter-toggle-group">
            <button type="button"
                class="filter-toggle-btn {{ ($filterMode ?? 'monthly') === 'monthly' ? 'active' : '' }}"
                onclick="setFilterMode('monthly', this)">
                <i class="fas fa-calendar-alt"></i> Monthly
            </button>
            <button type="button"
                class="filter-toggle-btn {{ ($filterMode ?? '') === 'yearly' ? 'active' : '' }}"
                onclick="setFilterMode('yearly', this)">
                <i class="fas fa-calendar"></i> Yearly
            </button>
            <button type="button"
                class="filter-toggle-btn {{ ($filterMode ?? '') === 'custom' ? 'active' : '' }}"
                onclick="setFilterMode('custom', this)">
                <i class="fas fa-sliders-h"></i> Custom Date
            </button>
        </div>

        {{-- ── Panel: Monthly ── --}}
        <div id="panel-monthly" class="filter-panel {{ ($filterMode ?? 'monthly') === 'monthly' ? 'active' : '' }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="sel_month">Month:</label>
                    <select name="month" id="sel_month">
                        <option value="">Select Month</option>
                        @php
                            $monthNames = [
                                1=>'January',2=>'February',3=>'March',4=>'April',
                                5=>'May',6=>'June',7=>'July',8=>'August',
                                9=>'September',10=>'October',11=>'November',12=>'December'
                            ];
                        @endphp
                        @foreach($monthNames as $num => $name)
                            <option value="{{ $num }}"
                                {{ (isset($month) && (int)$month === $num && ($filterMode ?? 'monthly') === 'monthly') ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label for="sel_year_monthly">Year:</label>
                    <select name="year" id="sel_year_monthly">
                        <option value="">Select Year</option>
                        @for($y = date('Y'); $y >= 2017; $y--)
                            <option value="{{ $y }}"
                                {{ (isset($year) && (int)$year === $y && ($filterMode ?? 'monthly') === 'monthly') ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <a href="{{ route('procurement.report.index') }}" class="btn-clear">
                    <i class="fas fa-times"></i> CLEAR
                </a>
            </div>
        </div>

        {{-- ── Panel: Yearly ── --}}
        <div id="panel-yearly" class="filter-panel {{ ($filterMode ?? '') === 'yearly' ? 'active' : '' }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="sel_year_yearly">Year:</label>
                    <select name="year_only" id="sel_year_yearly">
                        <option value="">Select Year</option>
                        @for($y = date('Y'); $y >= 2015; $y--)
                            <option value="{{ $y }}"
                                {{ (isset($yearOnly) && (int)$yearOnly === $y) ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <a href="{{ route('procurement.report.index') }}" class="btn-clear">
                    <i class="fas fa-times"></i> CLEAR
                </a>
            </div>
        </div>

        {{-- ── Panel: Custom Date Range ── --}}
        <div id="panel-custom" class="filter-panel {{ ($filterMode ?? '') === 'custom' ? 'active' : '' }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="date_from">From Date:</label>
                    <input type="date" name="date_from" id="date_from"
                           value="{{ $dateFrom ?? '' }}">
                </div>
                <span class="date-dash">—</span>
                <div class="filter-group">
                    <label for="date_to">To Date:</label>
                    <input type="date" name="date_to" id="date_to"
                           value="{{ $dateTo ?? '' }}">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <a href="{{ route('procurement.report.index') }}" class="btn-clear">
                    <i class="fas fa-times"></i> CLEAR
                </a>
            </div>
        </div>

    </form>

    {{-- ══════════════════════════════════════════
         RESULTS CARD
    ══════════════════════════════════════════ --}}
    <div class="results-card">

        @if(isset($reportData) && count($reportData) > 0)

            {{-- Report title --}}
            <div class="report-header-block">
                <h4>DEPARTMENT OF SCIENCE AND TECHNOLOGY</h4>
                <h5>
                    Procurement Monitoring Report as of
                    @if(($filterMode ?? '') === 'custom' && isset($dateFrom, $dateTo))
                        {{ date('F d, Y', strtotime($dateFrom)) }} – {{ date('F d, Y', strtotime($dateTo)) }}
                    @elseif(($filterMode ?? '') === 'yearly' && isset($yearOnly))
                        {{ $yearOnly }}
                    @elseif(isset($month) && $month)
                        {{ date('F', mktime(0,0,0,$month,1)) }} {{ $year }}
                    @else
                        {{ $year }}
                    @endif
                </h5>
                <h5><strong>CORDILLERA ADMINISTRATIVE REGION</strong></h5>
            </div>

            {{-- Export bar --}}
            <div class="export-bar no-print">
                <a href="{{ route('procurement.report.export.excel', array_filter([
                        'filter_mode' => $filterMode ?? 'monthly',
                        'month'       => $month    ?? '',
                        'year'        => $year     ?? '',
                        'year_only'   => $yearOnly ?? '',
                        'date_from'   => $dateFrom ?? '',
                        'date_to'     => $dateTo   ?? '',
                    ])) }}"
                   class="btn-export-csv">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>

            {{-- Table --}}
            <div class="pmr-table-wrap">
                <table class="pmr-table">
                    <thead>
                        <tr>
                            <th rowspan="2">PR No.</th>
                            <th rowspan="2">Supplier</th>
                            <th rowspan="2">Code<br>(PAP)</th>
                            <th rowspan="2">Procurement<br>Project</th>
                            <th rowspan="2">PMO/End-User</th>
                            <th rowspan="2">Is this an<br>Early<br>Procurement<br>Activity?</th>
                            <th rowspan="2">Mode of<br>Procurement</th>
                            <th colspan="13">Actual Procurement Activities</th>
                            <th rowspan="2">Source of<br>Funds</th>
                            <th colspan="3">ABC (PhP)</th>
                            <th colspan="3">Contract Cost (PhP)</th>
                            <th rowspan="2">PR No.</th>
                            <th rowspan="2">Supplier</th>
                            <th rowspan="2">Remarks</th>
                        </tr>
                        <tr>
                            <th>Pre-Proc<br>Conference</th>
                            <th>Ads/Post<br>of IB</th>
                            <th>Pre-bid<br>Conf</th>
                            <th>Eligibility<br>Check</th>
                            <th>Sub/Open<br>of Bids</th>
                            <th>Bid<br>Evaluation</th>
                            <th>Post<br>Qual</th>
                            <th>Date of BAC<br>Resolution<br>Recommending<br>Award</th>
                            <th>Notice<br>of Award</th>
                            <th>Contract<br>Signing</th>
                            <th>Notice to<br>Proceed</th>
                            <th>Delivery/<br>Completion</th>
                            <th>Inspection &amp;<br>Acceptance</th>
                            <th>Total</th>
                            <th>MOOE</th>
                            <th>CO</th>
                            <th>Total</th>
                            <th>MOOE</th>
                            <th>CO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $completedData = $reportData->where('status', 'Completed');
                            $ongoingData   = $reportData->whereNotIn('status', ['Completed']);
                        @endphp

                        @foreach($completedData as $item)
                        <tr>
                            <td>{{ $item->pr_no }}</td>
                            <td>{{ $item->supplier }}</td>
                            <td></td>
                            <td>{{ $item->purpose }}</td>
                            <td>{{ $item->division_name }}</td>
                            <td></td>
                            <td>{{ $item->mode_procurement_name }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_abstract  ? date('m/d/Y', strtotime($item->date_abstract))  : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_iar       ? date('m/d/Y', strtotime($item->date_iar))       : '' }}</td>
                            <td class="text-center">{{ $item->date_iar       ? date('m/d/Y', strtotime($item->date_iar))       : '' }}</td>
                            <td style="font-size:9px;">Government of the Philippines (current year's budget)</td>
                            <td class="text-right">{{ number_format($item->total_abc,      2) }}</td>
                            <td class="text-right">{{ number_format($item->total_mooe,     2) }}</td>
                            <td class="text-right">{{ number_format($item->total_co,       2) }}</td>
                            <td class="text-right">{{ number_format($item->contract_total, 2) }}</td>
                            <td class="text-right">{{ number_format($item->contract_mooe,  2) }}</td>
                            <td class="text-right">{{ number_format($item->contract_co,    2) }}</td>
                            <td>{{ $item->pr_no }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        @endforeach

                        @foreach($ongoingData as $item)
                        <tr>
                            <td>{{ $item->pr_no }}</td>
                            <td>{{ $item->supplier }}</td>
                            <td></td>
                            <td>{{ $item->purpose }}</td>
                            <td>{{ $item->division_name }}</td>
                            <td></td>
                            <td>{{ $item->mode_procurement_name }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_abstract  ? date('m/d/Y', strtotime($item->date_abstract))  : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_canvass   ? date('m/d/Y', strtotime($item->date_canvass))   : '' }}</td>
                            <td class="text-center">{{ $item->date_iar       ? date('m/d/Y', strtotime($item->date_iar))       : '' }}</td>
                            <td class="text-center">{{ $item->date_iar       ? date('m/d/Y', strtotime($item->date_iar))       : '' }}</td>
                            <td style="font-size:9px;">Government of the Philippines (current year's budget)</td>
                            <td class="text-right">{{ number_format($item->total_abc,      2) }}</td>
                            <td class="text-right">{{ number_format($item->total_mooe,     2) }}</td>
                            <td class="text-right">{{ number_format($item->total_co,       2) }}</td>
                            <td class="text-right">{{ number_format($item->contract_total, 2) }}</td>
                            <td class="text-right">{{ number_format($item->contract_mooe,  2) }}</td>
                            <td class="text-right">{{ number_format($item->contract_co,    2) }}</td>
                            <td>{{ $item->pr_no }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @elseif(isset($reportData))
            <div class="empty-state">
                <i class="fas fa-filter empty-icon"></i>
                <div class="empty-title">No Data Found</div>
                <div class="empty-sub">No procurement records match the selected filter.</div>
            </div>

        @else
            <div class="empty-state">
                <i class="fas fa-filter empty-icon"></i>
                <div class="empty-title">No Filter Applied</div>
                <div class="empty-sub">Select a date range above and click Filter.</div>
            </div>
        @endif

    </div>{{-- /results-card --}}

</div>{{-- /pmr-wrapper --}}

<script>
    function setFilterMode(mode, btn) {
        ['monthly', 'yearly', 'custom'].forEach(function(p) {
            var el = document.getElementById('panel-' + p);
            if (el) el.classList.remove('active');
        });
        var target = document.getElementById('panel-' + mode);
        if (target) target.classList.add('active');

        document.getElementById('filter_mode').value = mode;

        document.querySelectorAll('.filter-toggle-btn').forEach(function(b) {
            b.classList.remove('active');
        });
        if (btn) btn.classList.add('active');
    }
</script>

@endsection

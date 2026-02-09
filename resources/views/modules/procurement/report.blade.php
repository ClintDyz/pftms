@extends('layouts.app')

@section('main-content')

<style>
    .pmr-table {
        font-size: 11px;
    }
    .pmr-table th {
        background-color: #4472C4;
        color: white;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        padding: 8px 4px;
        border: 1px solid #ddd;
    }
    .pmr-table td {
        padding: 4px;
        border: 1px solid #ddd;
        vertical-align: middle;
    }
    .section-header {
        background-color: #D9E1F2;
        font-weight: bold;
        padding: 8px;
        text-align: center;
    }
    .report-header {
        text-align: center;
        margin-bottom: 20px;
    }
    .signature-section {
        margin-top: 50px;
        page-break-inside: avoid;
    }
    .signature-box {
        text-align: center;
        min-height: 100px;
    }
    @media print {
        .no-print {
            display: none;
        }
        .pmr-table {
            font-size: 9px;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header no-print">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice"></i>
                        Procurement Monthly  Monitoring Report
                    </h3>
                </div>

                <div class="card-body">
                    <!-- Filter Form -->
                    <form action="{{ route('procurement.report.generate') }}" method="GET" class="form-inline mb-4 no-print">
                        <div class="form-group mr-3">
                            <label for="year" class="mr-2">Year:</label>
                            <select name="year" id="year" class="form-control" required>
                                <option value="">Select Year</option>
                                @php
                                    $currentYear = date('Y');
                                    $startYear = 2020;
                                @endphp
                                @for($y = $currentYear; $y >= $startYear; $y--)
                                    <option value="{{ $y }}" {{ (isset($year) && $year == $y) ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="form-group mr-3">
                            <label for="month" class="mr-2">Month:</label>
                            <select name="month" id="month" class="form-control">
                                <option value="">All Months</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ (isset($month) && $month == $i) ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search"></i> Generate Report
                        </button>

                        @if(isset($reportData))
                            {{-- <a href="{{ route('procurement.report.export.excel', ['month' => $month ?? '', 'year' => $year]) }}"
                               class="btn btn-success mr-2">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </a> --}}

                            <a href="{{ route('procurement.report.export.csv', ['month' => $month ?? '', 'year' => $year]) }}"
                               class="btn btn-success mr-2">
                                <i class="fas fa-file-csv"></i> Export
                            </a>

                            {{-- <button onclick="window.print()" class="btn btn-secondary">
                                <i class="fas fa-print"></i> Print
                            </button> --}}
                        @endif
                    </form>

                    @if(isset($reportData) && count($reportData) > 0)
                        <!-- Report Header -->
                        <div class="report-header">
                            <h4><strong>DEPARTMENT OF SCIENCE AND TECHNOLOGY</strong></h4>
                            <h5>Procurement Monitoring Report as of
                                @if(isset($month) && $month)
                                    {{ date('F', mktime(0, 0, 0, $month, 1)) }} {{ $year }}
                                @else
                                    {{ $year }}
                                @endif
                            </h5>
                            <h5><strong>CORDILLERA ADMINISTRATIVE REGION</strong></h5>
                        </div>

                        <!-- Summary Statistics -->
                        {{-- <div class="row mb-4 no-print">
                            <div class="col-md-3">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3>{{ count($reportData) }}</h3>
                                        <p>Total Procurement Activities</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h3>{{ $reportData->where('status', 'Completed')->count() }}</h3>
                                        <p>Completed</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="small-box bg-warning">
                                    <div class="inner">
                                        <h3>{{ $reportData->whereNotIn('status', ['Completed'])->count() }}</h3>
                                        <p>On-going</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-spinner"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="small-box bg-primary">
                                    <div class="inner">
                                        <h3>₱{{ number_format($reportData->sum('total_abc') - $reportData->where('status', 'Completed')->sum('contract_total'), 2) }}</h3>
                                        <p>Total Savings</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-piggy-bank"></i>
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered pmr-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2">PR No.</th>
                                        <th rowspan="2">Supplier</th>
                                        <th rowspan="2">Code<br>(PAP)</th>
                                        <th rowspan="2">Procurement<br>Project</th>
                                        <th rowspan="2">PMO/End-User</th>
                                        <th rowspan="2">Is this an<br>Early<br>Procurement<br>Activity?</th>
                                        <th rowspan="2">Mode of<br>Procurement</th>
                                        <th colspan="13" class="text-center">Actual Procurement Activities</th>
                                        <th rowspan="2">Source of<br>Funds</th>
                                        <th colspan="3" class="text-center">ABC (PhP)</th>
                                        <th colspan="3" class="text-center">Contract Cost (PhP)</th>
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
                                        <th>Inspection &<br>Acceptance</th>
                                        <th>Total</th>
                                        <th>MOOE</th>
                                        <th>CO</th>
                                        <th>Total</th>
                                        <th>MOOE</th>
                                        <th>CO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- COMPLETED PROCUREMENT ACTIVITIES -->
                                    {{-- <tr class="section-header">
                                        <td colspan="28"><strong>COMPLETED PROCUREMENT ACTIVITIES</strong></td>
                                    </tr> --}}
                                    @php
                                        $completedData = $reportData->where('status', 'Completed');
                                        $index = 1;
                                    @endphp
                                    @foreach($completedData as $item)
                                    <tr>
                                        <td>{{ $item->pr_no }}</td>
                                        <td>{{ $item->supplier }} </td>
                                        <td> </td>
                                        <td>{{ $item->purpose }}</td>
                                        <td>{{ $item->division_name }}</td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_abstract ? date('m/d/Y', strtotime($item->date_abstract)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_iar ? date('m/d/Y', strtotime($item->date_iar)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_iar ? date('m/d/Y', strtotime($item->date_iar)) : '' }}</td>
                                        <td style="font-size: 9px;">Government of the Philippines (current year's budget)</td>
                                        <td class="text-right">{{ number_format($item->total_abc, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->total_mooe, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->total_co, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->contract_total, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->contract_mooe, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->contract_co, 2) }}</td>
                                        <td>{{ $item->pr_no }}</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    @endforeach

                                    <!-- ON-GOING PROCUREMENT ACTIVITIES -->
                                    {{-- <tr class="section-header">
                                        <td colspan="28"><strong>ON-GOING PROCUREMENT ACTIVITIES</strong></td>
                                    </tr> --}}
                                    @php
                                        $ongoingData = $reportData->whereNotIn('status', ['Completed']);
                                    @endphp
                                    @foreach($ongoingData as $item)
                                    <tr>
                                        <td>{{ $item->pr_no }}</td>
                                        <td>{{ $item->supplier }} </td>
                                        <td> </td>
                                        <td>{{ $item->purpose }}</td>
                                        <td>{{ $item->division_name }}</td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_abstract ? date('m/d/Y', strtotime($item->date_abstract)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_canvass ? date('m/d/Y', strtotime($item->date_canvass)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_iar ? date('m/d/Y', strtotime($item->date_iar)) : '' }}</td>
                                        <td class="text-center">{{ $item->date_iar ? date('m/d/Y', strtotime($item->date_iar)) : '' }}</td>
                                        <td style="font-size: 9px;">Government of the Philippines (current year's budget)</td>
                                        <td class="text-right">{{ number_format($item->total_abc, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->total_mooe, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->total_co, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->contract_total, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->contract_mooe, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->contract_co, 2) }}</td>
                                        <td>{{ $item->pr_no }}</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                {{-- <tfoot>
                                    <tr style="background-color: #FFF2CC;">
                                        <td colspan="19" class="text-right"><strong>Total Allotted Budget of Procurement Activities:</strong></td>
                                        <td class="text-right"><strong>{{ number_format($reportData->sum('total_abc'), 2) }}</strong></td>
                                        <td class="text-right"><strong>{{ number_format($reportData->sum('total_mooe'), 2) }}</strong></td>
                                        <td class="text-right"><strong>{{ number_format($reportData->sum('total_co'), 2) }}</strong></td>
                                        <td colspan="6"></td>
                                    </tr>
                                    <tr style="background-color: #E2EFDA;">
                                        <td colspan="19" class="text-right"><strong>Total Contract Price of Procurement Activities Conducted:</strong></td>
                                        <td class="text-right"><strong>{{ number_format($completedData->sum('contract_total'), 2) }}</strong></td>
                                        <td class="text-right"><strong>{{ number_format($completedData->sum('contract_mooe'), 2) }}</strong></td>
                                        <td class="text-right"><strong>{{ number_format($completedData->sum('contract_co'), 2) }}</strong></td>
                                        <td colspan="6"></td>
                                    </tr>
                                    <tr style="background-color: #DDEBF7;">
                                        <td colspan="19" class="text-right"><strong>Total Savings (Total Allotted Budget - Total Contract Price):</strong></td>
                                        <td class="text-right"><strong>{{ number_format($reportData->sum('total_abc') - $completedData->sum('contract_total'), 2) }}</strong></td>
                                        <td colspan="7"></td>
                                    </tr>
                                </tfoot> --}}
                            </table>
                        </div>

                        <!-- Report Footer / Signatures -->
                        {{-- <div class="row signature-section">
                            <div class="col-md-4 signature-box">
                                <p><strong>Prepared by:</strong></p>
                                <br><br><br>
                                <p style="border-top: 1px solid black; display: inline-block; padding-top: 5px;">
                                    <strong>MARIA CELESTE R. DELA CRUZ</strong><br>
                                    BAC Secretariat
                                </p>
                            </div>
                            <div class="col-md-4 signature-box">
                                <p><strong>Recommended for Approval by:</strong></p>
                                <br><br><br>
                                <p style="border-top: 1px solid black; display: inline-block; padding-top: 5px;">
                                    <strong>NANCY A. BANTOG</strong><br>
                                    BAC Chairperson
                                </p>
                            </div>
                            <div class="col-md-4 signature-box">
                                <p><strong>APPROVED:</strong></p>
                                <br><br><br>
                                <p style="border-top: 1px solid black; display: inline-block; padding-top: 5px;">
                                    <strong>SHEILA MARIE B. SINGA-CLAVER</strong><br>
                                    Head of the Procuring Entity
                                </p>
                            </div>
                        </div> --}}

                    @elseif(isset($reportData))
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            No procurement data found for the selected period.
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Please select a year and optionally a specific month to generate the report.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

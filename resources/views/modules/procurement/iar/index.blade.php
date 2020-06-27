@extends('layouts.app')

@section('main-content')

<div class="row wow animated fadeIn">
    <section class="mb-5 col-12 module-container">
        <div class="card mdb-color darken-3">
            <div class="card-body">
                <h5 class="card-title white-text">
                    <strong>
                        <i class="fas fa-shopping-cart"></i> Inspection and Acceptance Report
                    </strong>
                </h5>
                <hr class="white">
                <ul class="breadcrumb mdb-color darken-3 mb-0 p-1 white-text">
                    <li>
                        <i class="fa fa-caret-right mx-2" aria-hidden="true"></i>
                    </li>
                    <li>
                        <a href="{{ url('procurement/pr') }}" class="waves-effect waves-light white-text">
                            Purchase Request & Status
                        </a>
                    </li>
                    <li>
                        <i class="fa fa-caret-right mx-2" aria-hidden="true"></i>
                    </li>
                    <li>
                        <a href="{{ url('procurement/rfq') }}" class="waves-effect waves-light white-text">
                            Request for Quotations
                        </a>
                    </li>
                    <li>
                        <i class="fa fa-caret-right mx-2" aria-hidden="true"></i>
                    </li>
                    <li>
                        <a href="{{ url('procurement/abstract') }}" class="waves-effect waves-light white-text">
                            Abstract of Bids & Quotations
                        </a>
                    </li>
                    <li>
                        <i class="fa fa-caret-right mx-2" aria-hidden="true"></i>
                    </li>
                    <li>
                        <a href="{{ url('procurement/po-jo') }}" class="waves-effect waves-light white-text">
                            Purchase/Job Order
                        </a>
                    </li>
                    <li>
                        <i class="fa fa-caret-right mx-2" aria-hidden="true"></i>
                    </li>
                    <li class="active">
                        <a href="{{ url('procurement/iar') }}" class="waves-effect waves-light cyan-text">
                            Inspection and Acceptance Report
                        </a>
                    </li>
                </ul>

                <!-- Table with panel -->
                <div class="card card-cascade narrower">

                    <!--Card image-->
                    <div class="gradient-card-header unique-color
                                narrower py-2 px-2 mb-1 d-flex justify-content-between
                                align-items-center">
                        <div></div>
                        <div>
                            <button class="btn btn-outline-white btn-rounded btn-sm px-2"
                                    data-target="#top-fluid-modal" data-toggle="modal"
                                    onclick="$('#search').focus()">
                                <i class="fas fa-search"></i> {{ !empty($keyword) ? (strlen($keyword) > 15) ?
                                'Search: '.substr($keyword, 0, 15).'...' : 'Search: '.$keyword : '' }}
                            </button>
                            <a href="{{ route('iar') }}" class="btn btn-outline-white btn-rounded btn-sm px-2">
                                <i class="fas fa-sync-alt fa-pulse"></i>
                            </a>
                        </div>
                    </div>
                    <!--/Card image-->

                    <div class="px-2">
                        <div class="table-wrapper table-responsive border rounded">

                            <!--Table-->
                            <table id="dtmaterial" class="table table-hover" cellspacing="0" width="100%">

                                <!--Table head-->
                                <thead class="mdb-color darken-3 white-text">
                                    <tr>
                                        <th class="th-md" width="3%"></th>
                                        <th class="th-md" width="3%" style="text-align: center;"></th>
                                        <th class="th-md" width="8%">
                                            @sortablelink('pr_no', 'PR No', [], ['class' => 'white-text'])
                                        </th>
                                        <th class="th-md" width="10%">
                                            @sortablelink('date_pr', 'PR Date', [], ['class' => 'white-text'])
                                        </th>
                                        <th class="th-md" width="10%">
                                            @sortablelink('funding.source_name', 'Funding/Charging', [], ['class' => 'white-text'])
                                        </th>
                                        <th class="th-md" width="53%">
                                            @sortablelink('purpose', 'Purpose', [], ['class' => 'white-text'])
                                        </th>
                                        <th class="th-md" width="13%">
                                            @sortablelink('requestor.firstname', 'Requested By', [], ['class' => 'white-text'])
                                        </th>
                                    </tr>
                                </thead>
                                <!--Table head-->

                                <!--Table body-->
                                <tbody>
                                    @if (count($list) > 0)
                                        @foreach ($list as $listCtr => $pr)
                                    <tr>
                                        <td align="center">
                                            <i class="fas fa-folder fa-lg material-tooltip-main"
                                               data-toggle="tooltip" data-placement="right" title="PR Document"></i>
                                        </td>
                                        <td align="center">
                                        </td>
                                        <td align="center">
                                            <a class="btn btn-link p-0" href="{{ route('iar', ['keyword' => $pr->pr_no]) }}">
                                                {{ $pr->pr_no }}
                                            </a>
                                        </td>
                                        <td>{{ $pr->date_pr }}</td>
                                        <td>{{ $pr->project }}</td>
                                        <td>
                                            <i class="fas fa-caret-right"></i> {{
                                                (strlen($pr->purpose) > 150) ?
                                                substr($pr->purpose, 0, 150).'...' : $pr->purpose
                                            }}
                                        </td>
                                        <td>{{ Auth::user()->getEmployee($pr->requestor['id'])->name }}</td>
                                    </tr>
                                    <tr class="blue-grey lighten-2">
                                        <td colspan="7">
                                            <div class="card card-cascade narrower mx-3 my-2 z-depth-4">
                                                <div class="card-body p-2">
                                                    <table class="table table-sm z-depth-1 mb-0">

                                                        @if (count($pr->iar) > 0)
                                                        <thead class="mdb-color darken-1 white-text">
                                                            <tr>
                                                                <th class="th-md" width="3%"></th>
                                                                <th class="th-md" width="10%" style="text-align: center;"><strong>PO/JO Number</strong></th>
                                                                <th class="th-md" width="84%" style="text-align: center;"><strong>Awarded To</strong></th>
                                                                <th class="th-md" width="3%"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($pr->iar as $listCtr1 => $item)
                                                            <tr class="row-item">
                                                                <td align="center">
                                                                    @if ($pr->po[$listCtr1]->status > 9)
                                                                    <i class="fas fa-search fa-lg green-text material-tooltip-main"
                                                                       data-toggle="tooltip" data-placement="right" title="Inspected"></i>
                                                                    @else
                                                                        @if (!empty($item->doc_status->date_issued))
                                                                    <i class="fas fa-lg fa-paper-plane orange-text material-tooltip-main"
                                                                       data-toggle="tooltip" data-placement="right" title="Issued"></i>
                                                                        @else
                                                                    <i class="far fa-lg fa-file material-tooltip-main"
                                                                       data-toggle="tooltip" data-placement="right" title="Pending"></i>
                                                                        @endif
                                                                    @endif
                                                                </td>
                                                                <td align="center"><strong>{{ $item->iar_no }}</strong></td>
                                                                <td align="center">
                                                                    <strong>
                                                                        <h6 class="mb-0">
                                                                            {{ $item->company_name }}
                                                                            <span class="mdb-color-text">
                                                                                [ {{ strtoupper($pr->po[$listCtr1]->document_type) }} Document ]
                                                                            </span>
                                                                        </h6>
                                                                        <span class="text-info">
                                                                            [ IAR Created On {{ $item->created_at }} ]
                                                                        </span>
                                                                    </strong>

                                                                @if (!empty($pr->po[$listCtr1]->date_cancelled))
                                                                    <strong class="text-danger">[ Cancelled On: {{ $pr->po[$listCtr1]->date_cancelled }}]</strong>
                                                                @endif

                                                                </td>
                                                                <td align="center">
                                                                    <a class="btn-floating btn-sm btn-mdb-color p-2 waves-effect material-tooltip-main mr-0"
                                                                        data-target="#right-modal-{{ ($listCtr + 1) + (($list->currentpage() - 1) * $list->perpage()) }}-{{ $listCtr1 }}"
                                                                        data-toggle="modal" data-toggle="tooltip" data-placement="left" title="Open">
                                                                        <i class="fas fa-folder-open"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                        @endif

                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                        @endforeach
                                    @else
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <h6 class="red-text">
                                                No available data.
                                            </h6>
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                                <!--Table body-->
                            </table>
                            <!--Table-->
                        </div>

                        <div class="mt-3">
                            {{ $list->links('pagination') }}
                        </div>
                    </div>
                </div>
                <!-- Table with panel -->
            </div>
        </div>
    </section>
</div>

<!-- Modals -->
@if (count($list) > 0)
    @foreach ($list as $listCtr => $pr)
        @if (count($pr->iar) > 0)
            @foreach ($pr->iar as $listCtr1 => $item)
<div id="right-modal-{{ ($listCtr + 1) + (($list->currentpage() - 1) * $list->perpage()) }}-{{ $listCtr1 }}"
     tabindex="-1" class="modal custom-rightmenu-modal fade right" role="dialog">
    <div class="modal-dialog modal-full-height modal-right" role="document">
        <!--Content-->
        <div class="modal-content">
            <!--Header-->
            <div class="modal-header stylish-color-dark white-text">
                <h7>
                    <i class="fas fa-shopping-cart"></i>
                    <strong>IAR NO: {{ $item->iar_no }}</strong>
                </h7>
                <button type="button" class="close white-text" data-dismiss="modal"
                        aria-label="Close">
                    &times;
                </button>
            </div>
            <!--Body-->
            <div class="modal-body">
                <div class="card card-cascade z-depth-1 mb-3">
                    <div class="gradient-card-header rgba-white-light p-0">
                        <div class="p-0">
                            <div class="btn-group btn-menu-1 p-0">
                                <button type="button" class="btn btn-outline-mdb-color
                                        btn-sm px-2 waves-effect waves-light"
                                        onclick="$(this).showPrint('{{ $item->id }}', 'proc_iar');">
                                    <i class="fas fa-print blue-text"></i> Print IAR
                                </button>
                                <button type="button" class="btn btn-outline-mdb-color
                                        btn-sm px-2 waves-effect waves-light"
                                        onclick="$(this).showEdit('{{ route('iar-show-edit', ['id' => $item->id]) }}');">
                                    <i class="fas fa-edit orange-text"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p>
                            <strong>PR Date: </strong> {{ $pr->date_pr }}<br>
                            <strong>{{ strtoupper($pr->po[$listCtr1]->document_type) }} Date: </strong> {{ $item->date_po }}<br>
                            <strong>IAR Date: </strong> {{ $item->date_iar }}<br>
                            <strong>Charging: </strong> {{ $pr->funding['source_name'] }}<br>
                            <strong>Purpose: </strong> {{
                                (strlen($pr->purpose) > 150) ?
                                substr($pr->purpose, 0, 150).'...' : $pr->purpose
                            }}<br>
                            <strong>Awarded To: </strong> {{ $item->company_name }}<br>
                            <strong>Requested By: </strong> {{ Auth::user()->getEmployee($pr->requestor['id'])->name }}<br>
                        </p>
                        <button type="button" class="btn btn-sm btn-mdb-color btn-rounded
                                btn-block waves-effect mb-2"
                                onclick="$(this).showPrint('{{ $pr->id }}', 'proc-po-jo');">
                            <i class="far fa-list-alt fa-lg"></i> View Items
                        </button>
                    </div>
                </div>
                <hr>
                <ul class="list-group z-depth-0">
                    <li class="list-group-item justify-content-between">
                        <h5><strong><i class="fas fa-pen-nib"></i> Actions</strong></h5>
                    </li>
                    <li class="list-group-item justify-content-between">
                        <a onclick="$(this).redirectToDoc('{{ route('po-jo') }}', '{{ $pr->id }}');"
                           class="btn btn-outline-mdb-color waves-effect btn-block btn-md btn-rounded">
                            <i class="fas fa-angle-double-left"></i> Regenerate PO/JO
                        </a>
                    </li>

                    @if (!empty($item->doc_status->date_issued))
                        @if ($pr->po[$listCtr1]->status > 9)
                            @if ($item->inventory_count == 0)
                    <li class="list-group-item justify-content-between">
                        <button type="button" class="btn btn-outline-success waves-effect btn-block btn-md btn-rounded"
                                onclick="$(this).showCreateStocks(`{{ route('stocks-show-create-iar',
                                                                  ['poID' => $item->po_id]) }}`);">
                            <i class="fas fa-box green-text"></i> Issue/Inventory
                        </button>
                    </li>
                            @else
                    <li class="list-group-item justify-content-between">
                        <button type="button" class="btn btn-outline-warning waves-effect btn-block btn-md btn-rounded"
                                onclick="$(this).showEditStocks(`{{ route('stocks-show-edit-iar',
                                                                ['poID' => $item->po_id]) }}`);">
                            <i class="fas fa-box"></i> Update Inventory
                        </button>
                    </li>
                            @endif
                    <li class="list-group-item justify-content-between">
                        <a onclick="$(this).redirectToDoc('{{ route('proc-dv') }}', '{{ $pr->id }}');"
                           class="btn btn-outline-mdb-color waves-effect btn-block btn-md btn-rounded">
                            Generate DV <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                        @else
                    <li class="list-group-item justify-content-between">
                        <button type="button" class="btn btn-outline-success waves-effect btn-block btn-md btn-rounded"
                                onclick="$(this).showInspect('{{ route('iar-show-inspect', ['id' => $item->id]) }}',
                                                             `{{ $item->iar_no }}`);">
                            <i class="fas fa-search"></i> Inspect
                        </button>
                    </li>
                        @endif
                    @else
                    <li class="list-group-item justify-content-between">
                        <button type="button" class="btn btn-outline-warning waves-effect btn-block btn-md btn-rounded"
                                onclick="$(this).showIssue('{{ route('iar-show-issue', ['id' => $item->id]) }}',
                                                           `{{ $item->iar_no }}`);">
                            <i class="fas fa-paper-plane"></i> Issue
                        </button>
                    </li>
                    @endif
                </ul>
            </div>
            <!--Footer-->
            <div class="modal-footer justify-content-end rgba-stylish-strong p-1">
                <a type="button" class="btn btn-sm btn btn-light waves-effect py-1" data-dismiss="modal">
                    <i class="far fa-window-close"></i> Close
                </a>
            </div>
        </div>
      <!--/.Content-->
    </div>
</div>
            @endforeach
        @endif
    @endforeach
@endif

@include('modals.search-post')
@include('modals.create')
@include('modals.edit')
@include('modals.issue')
@include('modals.inspect')
@include('modals.print')

@endsection

@section('custom-js')

<script type="text/javascript" src="{{ asset('assets/js/input-validation.js') }}"></script>
<script src="{{ asset('assets/js/iar.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/print.js') }}"></script>

@if (!empty(session("success")))
    @include('modals.alert')
    <script type="text/javascript">
        $(function() {
            $('#modal-success').modal();
        });
    </script>
@elseif (!empty(session("warning")))
    @include('modals.alert')
    <script type="text/javascript">
        $(function() {
            $('#modal-warning').modal();
        });
    </script>
@elseif (!empty(session("failed")))
    @include('modals.alert')
    <script type="text/javascript">
        $(function() {
            $('#modal-failed').modal();
        });
    </script>
@endif

@endsection

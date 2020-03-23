@extends('layouts.app')

@section('custom-css')

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="{{ asset('plugins/mdb/css/addons/datatables.min.css') }}" rel="stylesheet">

<!-- DataTables Select CSS -->
<link rel="stylesheet" type="text/css" href="{{ asset('plugins/mdb/css/addons/datatables-select.min.css') }}" rel="stylesheet">

@endsection

@section('main-content')

<div class="row wow animated fadeIn">
    <section class="mb-5 col-12 module-container">
        <div class="card mdb-color darken-3">
            <div class="card-body">
                <h5 class="card-title white-text">
                    <strong>
                        <i class="fas fa-shopping-cart"></i> Purchase Request
                    </strong>
                </h5>
                <hr class="white">
                <ul class="breadcrumb mdb-color darken-3 mb-0 p-1 white-text">
                    <li>
                        <i class="fa fa-caret-right mx-2" aria-hidden="true"></i>
                    </li>
                    <li class="active">
                        <a href="{{ route('pr') }}" class="waves-effect waves-light cyan-text">
                            Purchase Request
                        </a>
                    </li>
                </ul>

                <!-- Table with panel -->
                <div class="card card-cascade narrower">

                    <!--Card image-->
                    <div class="gradient-card-header unique-color
                                narrower py-2 px-2 mb-1 d-flex justify-content-between
                                align-items-center">
                        <div>
                            @if ($isAllowedCreate)
                            <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2"
                                    onclick="$(this).showCreate('{{ route('pr-show-create') }}');">
                                <i class="fas fa-pencil-alt"></i> Create
                            </button>
                            @endif
                        </div>
                        <div>
                            <button class="btn btn-outline-white btn-rounded btn-sm px-2"
                                    data-target="#top-fluid-modal" data-toggle="modal">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="{{ route('pr') }}" class="btn btn-outline-white btn-rounded btn-sm px-2">
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
                                    <tr class="hidden-xs">
                                        <th class="th-md" width="3%"></th>
                                        <th class="th-md" width="3%"></th>
                                        <th class="th-md" width="8%">
                                            <strong>PR No</strong>
                                        </th>
                                        <th class="th-md" width="10%">
                                            <strong>Date</strong>
                                        </th>
                                        <th class="th-md" width="10%">
                                            <strong>Funding/Charging</strong>
                                        </th>
                                        <th class="th-md" width="41%">
                                            <strong>Purpose</strong>
                                        </th>
                                        <th class="th-md" width="13%">
                                            <strong>Requested By</strong>
                                        </th>
                                        <th class="th-md" width="9%">
                                            <strong>Status</strong>
                                        </th>
                                        <th class="th-md" width="3%"></th>
                                    </tr>
                                </thead>
                                <!--Table head-->

                                <!--Table body-->
                                <tbody>
                                    @if (count($list) > 0)
                                        @foreach ($list as $listCtr => $pr)
                                    <tr>
                                        <td align="center"></td>
                                        <td align="center">
                                            @if ($pr->status == 1)
                                            <i class="fas fa-spinner fa-lg faa-spin fa-pulse material-tooltip-main"
                                               data-toggle="tooltip" data-placement="right" title="Pending"></i>
                                            @elseif ($pr->status == 2)
                                            <i class="fas fa-thumbs-down fa-lg material-tooltip-main"
                                               data-toggle="tooltip" data-placement="right" title="Disapproved"></i>
                                            @elseif ($pr->status == 3)
                                            <i class="fas fa-ban fa-lg text-danger material-tooltip-main"
                                               data-toggle="tooltip" data-placement="right" title="Cancelled"></i>
                                            @elseif ($pr->status == 4)
                                            <i class="fas fa-door-closed fa-lg material-tooltip-main"
                                               data-toggle="tooltip" data-placement="right" title="Closed"></i>
                                            @elseif ($pr->status >= 5)
                                            <i class="fas fa-thumbs-up fa-lg green-text material-tooltip-main"
                                               data-toggle="tooltip" data-placement="right" title="Approved"></i>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $pr->pr_no }}
                                        </td>
                                        <td>{{ $pr->date_pr }}</td>
                                        <td>{{ $pr->funding_source }}</td>
                                        <td>
                                            <i class="fas fa-caret-right"></i> {{
                                                (strlen($pr->purpose) > 150) ?
                                                substr($pr->purpose, 0, 150).'...' : $pr->purpose
                                            }}
                                        </td>
                                        <td>{{ $pr->name }}</td>
                                        <td align="center">
                                            <a class="btn btn-link p-0" href="{{ route('pr-tracker', ['prNo' => $pr->pr_no]) }}">
                                                <strong>{{ $pr->status_name }}</strong></td>
                                            </a>
                                        <td align="center">
                                            <a class="btn-floating btn-sm btn-mdb-color p-2 waves-effect material-tooltip-main mr-0"
                                               data-target="#right-modal-{{ $listCtr + 1 }}" data-toggle="modal"
                                               data-toggle="tooltip" data-placement="left" title="Open">
                                                <i class="fas fa-folder-open"></i>
                                            </a>
                                        </td>
                                    </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <!--Table body-->

                            </table>
                            <!--Table-->

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
<div class="modal custom-rightmenu-modal fade right" id="right-modal-{{ $listCtr + 1 }}" tabindex="-1"
     role="dialog">
    <div class="modal-dialog modal-full-height modal-right" role="document">
        <!--Content-->
        <div class="modal-content">
            <!--Header-->
            <div class="modal-header stylish-color-dark white-text">
                <h7>
                    <i class="fas fa-shopping-cart"></i>
                    <strong>PR NO: {{ $pr->pr_no }}</strong>
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
                                        onclick="$(this).showPrint('{{ $pr->id }}', 'pr');">
                                    <i class="fas fa-print blue-text"></i> Print PR
                                </button>

                                @if ($isAllowedUpdate)
                                <button type="button" class="btn btn-outline-mdb-color
                                        btn-sm px-2 waves-effect waves-light"
                                        onclick="$(this).showEdit('{{ route('pr-show-edit', ['id' => $pr->id]) }}');">
                                    <i class="fas fa-edit orange-text"></i> Edit
                                </button>
                                @endif

                                @if ($isAllowedDelete)
                                    @if ($pr->status == 1)
                                <button type="button" class="btn btn-outline-mdb-color
                                        btn-sm px-2 waves-effect waves-light"
                                        onclick="$(this).showDelete('{{ route('pr-delete', ['id' => $pr->id]) }}',
                                                                              '{{ $pr->pr_no }}');">
                                    <i class="fas fa-trash-alt red-text"></i> Delete
                                </button>
                                    @else
                                <button type="button" class="btn btn-outline-mdb-color
                                        btn-sm px-2 waves-effect waves-light"
                                        disabled="disabled">
                                    <i class="fas fa-trash-alt red-text"></i> Delete
                                </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p>
                            <strong>PR Date: </strong> {{ $pr->date_pr }}<br>
                            <strong>Charging: </strong> {{ $pr->funding_source }}<br>
                            <strong>Purpose: </strong> {{
                                (strlen($pr->purpose) > 150) ?
                                substr($pr->purpose, 0, 150).'...' : $pr->purpose
                            }}<br>
                            <strong>Requested By: </strong> {{ $pr->name }}<br>
                        </p>
                        <button type="button" class="btn btn-sm btn-mdb-color btn-rounded
                                btn-block waves-effect mb-2"
                                onclick="$(this).showItem('{{ route('pr-show-items', ['id' => $pr->id]) }}');">
                            <i class="far fa-list-alt fa-lg"></i> View Items
                        </button>
                        <a class="btn btn-sm btn-outline-mdb-color btn-rounded
                                  btn-block waves-effect"
                           href="{{ route('pr-tracker', ['prNo' => $pr->pr_no] ) }}">
                            <i class="far fa-eye"></i> Track PR Status
                        </a>
                    </div>
                </div>
                <hr>
                <ul class="list-group z-depth-1">
                    <li class="list-group-item justify-content-between">
                        <h5><strong><i class="fas fa-pen-nib"></i> Actions</strong></h5>
                    </li>

                    @if ($pr->status == 1)
                        @if ($isAllowedApprove)
                    <li class="list-group-item justify-content-between">
                        <button type="button" class="btn btn-outline-green waves-effect btn-md btn-block btn-rounded"
                                onclick="$(this).showApprove('{{ route('pr-approve', ['id' => $pr->id]) }}',
                                                             '{{ $pr->pr_no }}');">
                            <i class="fas fa-thumbs-up"></i> Approve
                        </button>
                    </li>
                        @endif

                        @if ($isAllowedApprove)
                    <li class="list-group-item justify-content-between">
                        <button type="button" class="btn btn-outline-black waves-effect btn-md btn-block btn-rounded"
                                onclick="$(this).showDisapprove('{{ route('pr-disapprove', ['id' => $pr->id]) }}',
                                                                '{{ $pr->pr_no }}');">
                            <i class="fas fa-thumbs-down"></i> Disapprove
                        </button>
                    </li>
                        @endif

                        @if ($isAllowedCancel)
                    <li class="list-group-item justify-content-between">
                        <button type="button" class="btn btn-outline-red waves-effect btn-md btn-block btn-rounded"
                                onclick="$(this).showCancel('{{ route('pr-cancel', ['id' => $pr->id]) }}',
                                                            '{{ $pr->pr_no }}');">
                            <i class="fas fa-ban"></i> Cancel
                        </button>
                    </li>
                        @endif
                    @endif

                    @if ($pr->status >= 5)
                        @if ($isAllowedRFQ)
                    <li class="list-group-item justify-content-between">
                        <a onclick="$(this).redirectToDoc('{{ route('rfq') }}', '{{ $pr->id }}');"
                           class="btn btn-outline-mdb-color waves-effect btn-block btn-md btn-rounded">
                            Generate RFQ <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                        @endif
                    @endif

                    @if ($pr->status > 1 && $pr->status < 5)
                    <ul class="list-group z-depth-0">
                        <li class="list-group-item justify-content-between text-center">
                            No more available actions.
                        </li>
                    </ul>
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

@include('modals.search')
@include('modals.show')
@include('modals.create')
@include('modals.edit')
@include('modals.delete')
@include('modals.approve')
@include('modals.disapprove')
@include('modals.cancel')
@include('modals.print')

@endsection

@section('custom-js')

<!-- DataTables JS -->
<script type="text/javascript" src="{{ asset('plugins/mdb/js/addons/datatables.min.js') }}"></script>

<!-- DataTables Select JS -->
<script type="text/javascript" src="{{ asset('plugins/mdb/js/addons/datatables-select.min.js') }}"></script>

<script type="text/javascript" src="{{ asset('assets/js/input-validation.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/pr.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/print.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/custom-datatables.js') }}"></script>

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

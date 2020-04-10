<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\RequestQuotation;
use App\Models\AbstractQuotation;
use App\Models\AbstractQuotationItem;
use App\Models\PurchaseJobOrder;
use App\Models\PurchaseJobOrderItem;
use App\Models\ObligationRequestStatus;
use App\Models\InspectionAcceptance;
use App\Models\DisbursementVoucher;
use App\Models\InventoryStock;
use App\Models\InventoryStockIssue;

use App\User;
use App\Models\EmpGroup;
use App\Models\EmpDivision;
use App\Models\ItemUnitIssue;
use App\Models\FundingSource;
use App\Models\Signatory;
use App\Models\DocumentLog as DocLog;
use App\Models\PaperSize;
use App\Models\Supplier;
use DB;
use Auth;
use Carbon\Carbon;

class PurchaseRequestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        //$this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $keyword = trim($request->keyword);

        // Get module access
        $module = 'proc_pr';
        $isAllowedCreate = Auth::user()->getModuleAccess($module, 'create');
        $isAllowedUpdate = Auth::user()->getModuleAccess($module, 'update');
        $isAllowedDelete = Auth::user()->getModuleAccess($module, 'delete');
        $isAllowedDestroy = Auth::user()->getModuleAccess($module, 'destroy');
        $isAllowedCancel = Auth::user()->getModuleAccess($module, 'cancel');
        $isAllowedApprove = Auth::user()->getModuleAccess($module, 'approve');
        $isAllowedDisapprove = Auth::user()->getModuleAccess($module, 'disapprove');
        $isAllowedRFQ = Auth::user()->getModuleAccess('proc_rfq', 'is_allowed');

        // User groups
        $roleHasOrdinary = Auth::user()->hasOrdinaryRole();
        $empDivisionAccess = !$roleHasOrdinary ? Auth::user()->getDivisionAccess() :
                             [Auth::user()->division];

        // Main data
        $paperSizes = PaperSize::orderBy('paper_type')->get();
        $prData = PurchaseRequest::with(['funding', 'requestor', 'stat'])
                                 ->whereHas('division', function($query)
                                            use($empDivisionAccess) {
            $query->whereIn('id', $empDivisionAccess);
        });

        if ($roleHasOrdinary) {
            $prData = $prData->where('requested_by', Auth::user()->id);
        }

        if (!empty($keyword)) {
            $prData = $prData->where(function($qry) use ($keyword) {
                $qry->where('id', 'like', "%$keyword%")
                    ->orWhere('pr_no', 'like', "%$keyword%")
                    ->orWhere('date_pr', 'like', "%$keyword%")
                    ->orWhere('purpose', 'like', "%$keyword%")
                    ->orWhereHas('funding', function($query) use ($keyword) {
                        $query->where('source_name', 'like', "%$keyword%");
                    })->orWhereHas('stat', function($query) use ($keyword) {
                        $query->where('status_name', 'like', "%$keyword%");
                    })->orWhereHas('requestor', function($query) use ($keyword) {
                        $query->where('firstname', 'like', "%$keyword%")
                              ->orWhere('middlename', 'like', "%$keyword%")
                              ->orWhere('lastname', 'like', "%$keyword%");
                    })->orWhereHas('items', function($query) use ($keyword) {
                        $query->where('item_description', 'like', "%$keyword%");
                    })->orWhereHas('division', function($query) use ($keyword) {
                        $query->where('division_name', 'like', "%$keyword%");
                    });
            });
        }

        $prData = $prData->sortable(['pr_no' => 'desc'])->paginate(20);

        return view('modules.procurement.pr.index', [
            'list' => $prData,
            'keyword' => $keyword,
            'paperSizes' => $paperSizes,
            'isAllowedCreate' => $isAllowedCreate,
            'isAllowedUpdate' => $isAllowedUpdate,
            'isAllowedDelete' => $isAllowedDelete,
            'isAllowedDestroy' => $isAllowedDestroy,
            'isAllowedCancel' => $isAllowedCancel,
            'isAllowedApprove' => $isAllowedApprove,
            'isAllowedDisapprove' => $isAllowedDisapprove,
            'isAllowedRFQ' => $isAllowedRFQ,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showItems($id) {
        $prItemData = PurchaseRequestItem::addSelect([
            'unit_issue' => ItemUnitIssue::select('unit_name')
                                         ->whereColumn('id', 'purchase_request_items.unit_issue')
                                         ->limit(1),
            'awarded_to' => Supplier::select('company_name')
                                    ->whereColumn('id', 'purchase_request_items.awarded_to')
                                    ->limit(0)
        ])->where('pr_id', $id)->orderBy('item_no')->get();

        return view('modules.procurement.pr.show-item', ['prItems' => $prItemData]);
    }

    public function showTrackPR($prNo) {
        $mainStatusColor = "";
        $mainStatusSymbol = "-";

        // Purchase Request
        $pr = PurchaseRequest::where('pr_no', $prNo)->first();

        if ($pr) {
            $prApprovedStatusColor = "";
            $prApprovedStatusSymbol = "";

            if (!empty($pr->date_pr_approve)) {
                $mainStatusColor = "green";
                $mainStatusSymbol = '<i class="fas fa-check"></i>';
                $prApprovedStatusColor =  $mainStatusColor;
                $prApprovedStatusSymbol = $mainStatusSymbol;
            } else {
                $mainStatusColor = "blue";
                $mainStatusSymbol = '<i class="fas fa-chevron-right"></i>';
                $prApprovedStatusColor =  "";
                $prApprovedStatusSymbol = "-";
            }

            $prTrackData = (object) ['main_status_color' => $mainStatusColor,
                                    'main_status_symbol' => $mainStatusSymbol,
                                    '_approved_status_color' => $prApprovedStatusColor,
                                    '_approved_status_symbol' => $prApprovedStatusSymbol];
            // -- Reset Variable
            $mainStatusColor = "";
            $mainStatusSymbol = "-";

            // Request for Quotation
            $rfq = Canvass::where('pr_id', $pr->id)->first();
            $rfqCode = isset($rfq->code) ? $rfq->code: '';
            $rfqDocStatus = $this->checkDocStatus($rfqCode);

            $rfqIssuedStatusColor = "";
            $rfqIssuedStatusSymbol = "-";
            $rfqReceivedStatusColor = "";
            $rfqReceivedStatusSymbol = "-";

            if (!empty($rfq) && !empty($rfqDocStatus->date_issued) &&
                !empty($rfqDocStatus->date_received)) {
                $mainStatusColor = "green";
                $mainStatusSymbol = '<i class="fas fa-check"></i>';
            } else if (!empty($rfq) && empty($rfqDocStatus->date_issued) &&
                    empty($rfqDocStatus->date_received)) {
                $mainStatusColor = "blue";
                $mainStatusSymbol = '<i class="fas fa-chevron-right"></i>';
            }

            if (!empty($rfqDocStatus->date_issued)) {
                $rfqIssuedStatusColor = "green";
                $rfqIssuedStatusSymbol = '<i class="fas fa-check"></i>';
            }

            if (!empty($rfqDocStatus->date_received)) {
                $rfqReceivedStatusColor = "green";
                $rfqReceivedStatusSymbol = '<i class="fas fa-check"></i>';
            }

            $rfqTrackData = (object) ['main_status_color' => $mainStatusColor,
                                    'main_status_symbol' => $mainStatusSymbol,
                                    '_issued_status_color' => $rfqIssuedStatusColor,
                                    '_issued_status_symbol' => $rfqIssuedStatusSymbol,
                                    '_received_status_color' => $rfqReceivedStatusColor,
                                    '_received_status_symbol' => $rfqReceivedStatusSymbol];

            // -- Reset Variable
            $mainStatusColor = "";
            $mainStatusSymbol = "-";

            // Abstract of Bids and Quotation
            $abstract = Abstracts::where('pr_id', $pr->id)->first();

            $abstractApprovedStatusColor = "";
            $abstractApprovedStatusSymbol = "-";

            if (!empty($abstract) > 0 && !empty($abstract->date_abstract_approve)) {
                $mainStatusColor = "green";
                $mainStatusSymbol = '<i class="fas fa-check"></i>';
                $abstractApprovedStatusColor = "green";
                $abstractApprovedStatusSymbol = '<i class="fas fa-check"></i>';
            } else if (!empty($abstract) > 0 && empty($abstract->date_abstract_approve)) {
                $mainStatusColor = "blue";
                $mainStatusSymbol = '<i class="fas fa-chevron-right"></i>';
                $abstractApprovedStatusColor = "";
                $abstractApprovedStatusSymbol = '-';
            }

            $abstractTrackData = (object) ['main_status_color' => $mainStatusColor,
                                        'main_status_symbol' => $mainStatusSymbol,
                                        '_approved_status_color' => $abstractApprovedStatusColor,
                                        '_approved_status_symbol' => $abstractApprovedStatusSymbol];

            // -- Reset Variable
            $mainStatusColor = "";
            $mainStatusSymbol = "-";

            // Purchase/Job Order
            $po = DB::table('tblpo_jo')->where('pr_id', $pr->id)
                                       ->get();
            $poCount = $po->count();
            $poCountComplete = 0;

            foreach ($po as $dat) {
                // Individual Purchase/Job Order
                $poCode = isset($dat->code) ? $dat->code: '';
                $poDocStatus = $this->checkDocStatus($poCode);

                $poStatusColor = "blue";
                $poStatusSymbol = '<i class="fas fa-chevron-right"></i>';
                $_poSignedStatusColor = "";
                $_poSignedStatusSymbol = "-";
                $_poApprovedStatusColor = "";
                $_poApprovedStatusSymbol = "-";
                $_poIssuedStatusColor = "";
                $_poIssuedStatusSymbol = "-";
                $_poReceivedStatusColor = "";
                $_poReceivedStatusSymbol = "-";

                if (!empty($dat->date_accountant_signed)) {
                    $_poSignedStatusColor = "green";
                    $_poSignedStatusSymbol = '<i class="fas fa-check"></i>';
                }

                if (!empty($dat->date_po_approved)) {
                    $_poApprovedStatusColor = "green";
                    $_poApprovedStatusSymbol = '<i class="fas fa-check"></i>';
                }

                if (!empty($poDocStatus->date_issued)) {
                    $_poIssuedStatusColor = "green";
                    $_poIssuedStatusSymbol = '<i class="fas fa-check"></i>';
                }

                if (!empty($poDocStatus->date_issued)) {
                    $_poReceivedStatusColor = "green";
                    $_poReceivedStatusSymbol = '<i class="fas fa-check"></i>';
                }

                // Obligation / Budget Utilization and Request Status
                $ors = OrsBurs::where('po_no', $dat->po_no)->first();
                $orsID = isset($ors->id) ? $ors->id: '';
                $orsCode = isset($ors->code) ? $ors->code: '';
                $orsDocStatus = $this->checkDocStatus($orsCode);

                $orsStatusColor = "";
                $orsStatusSymbol = "-";
                $_orsIssuedStatusColor = "";
                $_orsIssuedStatusSymbol = "-";
                $_orsReceivedStatusColor = "";
                $_orsReceivedStatusSymbol = "-";
                $_orsObligatedStatusColor = "";
                $_orsObligatedStatusSymbol = "-";

                if (!empty($ors)) {
                    if (!empty($orsDocStatus->date_issued) && !empty($orsDocStatus->date_received) &&
                        !empty($ors->date_obligated)) {
                        $orsStatusColor = "green";
                        $orsStatusSymbol = '<i class="fas fa-check"></i>';
                    } else {
                        $orsStatusColor = "blue";
                        $orsStatusSymbol = '<i class="fas fa-chevron-right"></i>';
                    }

                    if (!empty($orsDocStatus->date_issued)) {
                        $_orsIssuedStatusColor = "green";
                        $_orsIssuedStatusSymbol = '<i class="fas fa-check"></i>';
                    }

                    if (!empty($orsDocStatus->date_received)) {
                        $_orsReceivedStatusColor = "green";
                        $_orsReceivedStatusSymbol = '<i class="fas fa-check"></i>';
                    }

                    if (!empty($ors->date_obligated)) {
                        $_orsObligatedStatusColor = "green";
                        $_orsObligatedStatusSymbol = '<i class="fas fa-check"></i>';
                    }
                }

                // Inspection and Acceptance Report
                $iar = InspectionAcceptance::where('ors_id', $orsID)->first();
                $inventoryCount = InventoryStock::where('po_no', $dat->po_no)->count();
                $iarCode = isset($iar->code) ? $iar->code: '';
                $iarDocStatus = $this->checkDocStatus($iarCode);

                $iarStatusColor = "";
                $iarStatusSymbol = "-";
                $_iarIssuedStatusColor = "";
                $_iarIssuedStatusSymbol = "-";
                $_iarInspectedStatusColor = "";
                $_iarInspectedStatusSymbol = "-";
                $_iarIssuedInventoryStatusColor = "";
                $_iarIssuedInventoryStatusSymbol = "-";

                if (!empty($iar)) {
                    if (!empty($iarDocStatus->date_issued) && !empty($iarDocStatus->date_received) &&
                        $inventoryCount > 0) {
                        $iarStatusColor = "green";
                        $iarStatusSymbol = '<i class="fas fa-check"></i>';
                    } else {
                        $iarStatusColor = "blue";
                        $iarStatusSymbol = '<i class="fas fa-chevron-right"></i>';
                    }

                    if (!empty($iarDocStatus->date_issued)) {
                        $_iarIssuedStatusColor = "green";
                        $_iarIssuedStatusSymbol = '<i class="fas fa-check"></i>';
                    }

                    if (!empty($iarDocStatus->date_received)) {
                        $_iarInspectedStatusColor = "green";
                        $_iarInspectedStatusSymbol = '<i class="fas fa-check"></i>';
                    }

                    if ($inventoryCount > 0) {
                        $_iarIssuedInventoryStatusColor = "green";
                        $_iarIssuedInventoryStatusSymbol = '<i class="fas fa-check"></i>';
                    }
                }

                // Disbursement Voucher
                $dv = DisbursementVoucher::where('ors_id', $orsID)->first();
                $dvCode = isset($dv->code) ? $dv->code: '';
                $dvDocStatus = $this->checkDocStatus($dvCode);

                $dvStatusColor = "";
                $dvStatusSymbol = "-";
                $_dvIssuedStatusColor = "";
                $_dvIssuedStatusSymbol = "-";
                $_dvReceivedStatusColor = "";
                $_dvReceivedStatusSymbol = "-";
                $_dvDisbursedStatusColor = "";
                $_dvDisbursedStatusSymbol = "-";

                if (!empty($dv)) {
                    if (!empty($dvDocStatus->date_issued) && !empty($dvDocStatus->date_received) &&
                        !empty($dv->date_disbursed)) {
                        $dvStatusColor = "green";
                        $dvStatusSymbol = '<i class="fas fa-check"></i>';
                    } else {
                        $dvStatusColor = "blue";
                        $dvStatusSymbol = '<i class="fas fa-chevron-right"></i>';
                    }

                    if (!empty($dvDocStatus->date_issued)) {
                        $_dvIssuedStatusColor = "green";
                        $_dvIssuedStatusSymbol = '<i class="fas fa-check"></i>';
                    }

                    if (!empty($dvDocStatus->date_received)) {
                        $_dvReceivedStatusColor = "green";
                        $_dvReceivedStatusSymbol = '<i class="fas fa-check"></i>';
                    }

                    if (!empty($dv->date_disbursed)) {
                        $_dvDisbursedStatusColor = "green";
                        $_dvDisbursedStatusSymbol = '<i class="fas fa-check"></i>';
                    }
                }

                // Continuation of individual Purchase/Job Order
                if ($_poApprovedStatusColor == "green" && $_poIssuedStatusColor == "green" &&
                    $_poReceivedStatusColor == "green" && $orsStatusColor == "green" &&
                    $iarStatusColor == "green" && $dvStatusColor == "green") {
                    $poStatusColor = "green";
                    $poStatusSymbol = '<i class="fas fa-check"></i>';
                    $poCountComplete++;
                }

                $dat->po_status = (object) ['main_status_color' => $poStatusColor,
                                            'main_status_symbol' => $poStatusSymbol,
                                            '_signed_status_color' => $_poSignedStatusColor,
                                            '_signed_status_symbol' => $_poSignedStatusSymbol,
                                            '_approved_status_color' => $_poApprovedStatusColor,
                                            '_approved_status_symbol' => $_poApprovedStatusSymbol,
                                            '_issued_status_color' => $_poIssuedStatusColor,
                                            '_issued_status_symbol' => $_poIssuedStatusSymbol,
                                            '_received_status_color' => $_poReceivedStatusColor,
                                            '_received_status_symbol' => $_poReceivedStatusSymbol];
                $dat->ors_status = (object) ['main_status_color' => $orsStatusColor,
                                            'main_status_symbol' => $orsStatusSymbol,
                                            '_issued_status_color' => $_orsIssuedStatusColor,
                                            '_issued_status_symbol' => $_orsIssuedStatusSymbol,
                                            '_received_status_color' => $_orsReceivedStatusColor,
                                            '_received_status_symbol' => $_orsReceivedStatusSymbol,
                                            '_obligated_status_color' => $_orsObligatedStatusColor,
                                            '_obligated_status_symbol' => $_orsObligatedStatusSymbol];
                $dat->iar_status = (object) ['main_status_color' => $iarStatusColor,
                                            'main_status_symbol' => $iarStatusSymbol,
                                            '_issued_status_color' => $_iarIssuedStatusColor,
                                            '_issued_status_symbol' => $_iarIssuedStatusSymbol,
                                            '_inspected_status_color' => $_iarInspectedStatusColor,
                                            '_inspected_status_symbol' => $_iarInspectedStatusSymbol,
                                            '_issued_inventory_status_color' => $_iarIssuedInventoryStatusColor,
                                            '_issued_inventory_status_symbol' => $_iarIssuedInventoryStatusSymbol];
                $dat->dv_status = (object) ['main_status_color' => $dvStatusColor,
                                            'main_status_symbol' => $dvStatusSymbol,
                                            '_issued_status_color' => $_dvIssuedStatusColor,
                                            '_issued_status_symbol' => $_dvIssuedStatusSymbol,
                                            '_received_status_color' => $_dvReceivedStatusColor,
                                            '_received_status_symbol' => $_dvReceivedStatusSymbol,
                                            '_disbursed_status_color' => $_dvDisbursedStatusColor,
                                            '_disbursed_status_symbol' => $_dvDisbursedStatusSymbol];
            }

            if ($poCount == $poCountComplete && $poCount > 0) {
                $mainStatusColor = "green";
                $mainStatusSymbol = '<i class="fas fa-check"></i>';
            } else if ($poCount != $poCountComplete && $poCount > 0) {
                $mainStatusColor = "blue";
                $mainStatusSymbol = '<i class="fas fa-chevron-right"></i>';
            }

            $mainPOTrackData = (object) ['main_status_color' => $mainStatusColor,
                                        'main_status_symbol' => $mainStatusSymbol];

            return view('pages.pr-tracker', ['prNo' => $prNo,
                                            'isPrDisapproved' => !empty($pr->date_pr_disapprove) ? true: false,
                                            'isPrCancelled' => !empty($pr->date_pr_cancel) ? true: false,
                                            'prTrackData' => $prTrackData,
                                            'rfqTrackData' => $rfqTrackData,
                                            'abstractTrackData' => $abstractTrackData,
                                            'mainPOTrackData' => $mainPOTrackData,
                                            'po' => $po]);
        } else {
            return "No data found.";
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showCreate() {
        $itemNo = 0;
        $roleHasOrdinary = Auth::user()->hasOrdinaryRole();
        $unitIssues = ItemUnitIssue::orderBy('unit_name')->get();
        $fundingSources = FundingSource::orderBy('source_name')->get();
        $divisions = $roleHasOrdinary ?
                    EmpDivision::where('id', Auth::user()->division)
                               ->orderBy('division_name')
                               ->get() :
                     EmpDivision::orderBy('division_name')->get();
        $users = $roleHasOrdinary ?
                User::where('id', Auth::user()->id)
                    ->orderBy('firstname')
                    ->get() :
                User::where('is_active', 'y')
                    ->orderBy('firstname')->get();
        $signatories = Signatory::addSelect([
            'name' => User::select(DB::raw('CONCAT(firstname, " ", lastname) AS name'))
                          ->whereColumn('id', 'signatories.emp_id')
                          ->limit(1)
        ])->where('is_active', 'y')->get();

        foreach ($signatories as $sig) {
            $sig->module = json_decode($sig->module);
        }

        return view('modules.procurement.pr.create', [
            'users' => $users,
            'signatories' => $signatories,
            'unitIssues' => $unitIssues,
            'fundingSources' => $fundingSources,
            'divisions' => $divisions,
            'itemNo' => $itemNo,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showEdit($id) {
        $prData = PurchaseRequest::where('id', $id)->first();
        $prItemData = PurchaseRequestItem::where('pr_id', $id)
                                         ->orderBy('item_no')
                                         ->get();
        $itemNo = $prItemData->count();
        $office = $prData->office;
        $prNo = $prData->pr_no;
        $prDate = $prData->date_pr;
        $fundingSource = $prData->funding_source;
        $purpose = $prData->purpose;
        $remarks = $prData->remarks;
        $requestedBy = $prData->requested_by;
        $division = $prData->division;
        $approvedBy = $prData->approved_by;
        $recommendedBy = $prData->recommended_by;
        $roleHasOrdinary = Auth::user()->hasOrdinaryRole();
        $unitIssues = ItemUnitIssue::orderBy('unit_name')->get();
        $fundingSources = FundingSource::orderBy('source_name')->get();
        $divisions = $roleHasOrdinary ?
                    EmpDivision::where('id', Auth::user()->division)
                               ->orderBy('division_name')
                               ->get() :
                     EmpDivision::orderBy('division_name')->get();
        $users = $roleHasOrdinary ?
                User::where('id', Auth::user()->id)
                    ->orderBy('firstname')
                    ->get() :
                User::where('is_active', 'y')
                    ->orderBy('firstname')->get();
        $signatories = Signatory::addSelect([
            'name' => User::select(DB::raw('CONCAT(firstname, " ", lastname) AS name'))
                          ->whereColumn('id', 'signatories.emp_id')
                          ->limit(1)
        ])->where('is_active', 'y')->get();

        foreach ($signatories as $sig) {
            $sig->module = json_decode($sig->module);
        }

        return view('modules.procurement.pr.update', [
            'id' => $id,
            'users' => $users,
            'signatories' => $signatories,
            'unitIssues' => $unitIssues,
            'fundingSources' => $fundingSources,
            'divisions' => $divisions,
            'itemNo' => $itemNo,
            'office' => $office,
            'prNo' => $prNo,
            'prDate' => $prDate,
            'fundingSource' => $fundingSource,
            'purpose' => $purpose,
            'remarks' => $remarks,
            'requestedBy' => $requestedBy,
            'division' => $division,
            'approvedBy' => $approvedBy,
            'recommendedBy' => $recommendedBy,
            'prItems' => $prItemData
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $instanceDocLog = new DocLog;

        // Parent variables
        $prDate = $request->date_pr;
        $divisionID = $request->division;
        $purpose = $request->purpose;
        $remarks = $request->remarks;
        $projectID = $request->project;
        $requestedBy = $request->requested_by;
        $approvedBy = $request->approved_by;
        $recommendedBy = $request->recommended_by;
        $office = $request->office;

        // PR Item variables
        $unitIssues = $request->unit;
        $itemDescriptions = $request->item_description;
        $quantities = $request->quantity;
        $unitCosts = $request->unit_cost;
        $totalCosts = $request->total_cost;

        try {
            // Auto Generate pr_no if empty
            $prSequence = PurchaseRequest::select('id', 'pr_no')
                                         ->orderBy('pr_no')
                                         ->get();
            $currentYearMonth = date('y') . date('m');

            if (count($prSequence) > 0) {
                $prNumber = "";

                foreach ($prSequence as $key => $_prNumber) {
                    if (substr($_prNumber->pr_no, 0, 4) == $currentYearMonth) {
                        $prNumber = $_prNumber->pr_no;
                    }
                }

                $prSequenceNumber = (int)substr($prNumber, 4) + 1;
                $prNo = $currentYearMonth . str_pad($prSequenceNumber, 3, '0', STR_PAD_LEFT);
            } else {
                $prNo = $currentYearMonth . '001';
            }

            $instancePR = new PurchaseRequest;

            if (!$instancePR->checkDuplication($prNo)) {
                // Storing main PR data
                $instancePR->date_pr = $prDate;
                $instancePR->pr_no = $prNo;
                $instancePR->funding_source = $projectID;
                $instancePR->purpose = $purpose;
                $instancePR->remarks = $remarks;
                $instancePR->division = $divisionID;
                $instancePR->requested_by = $requestedBy;
                $instancePR->approved_by = $approvedBy;
                $instancePR->recommended_by = $recommendedBy;
                $instancePR->office = $office;
                $instancePR->status = 1;
                $instancePR->save();

                // Storing PR Items data
                $prData = PurchaseRequest::where('pr_no', $prNo)->first();
                $prID = $prData->id;

                foreach ($unitIssues as $arrayKey => $unit) {
                    $description = $itemDescriptions[$arrayKey];
                    $quantity = $quantities[$arrayKey];
                    $unitCost = $unitCosts[$arrayKey];
                    $totalCost =  $quantity * $unitCost;

                    $instancePRItem = new PurchaseRequestItem;
                    $instancePRItem->pr_id = $prID;
                    $instancePRItem->item_no = $arrayKey + 1;
                    $instancePRItem->quantity = $quantity;
                    $instancePRItem->unit_issue = $unit;
                    $instancePRItem->item_description = $description;
                    $instancePRItem->est_unit_cost = $unitCost;
                    $instancePRItem->est_total_cost = $totalCost;
                    $instancePRItem->save();
                }

                //$instancePR->notifyForApproval($prNo, $requestedBy);
                //$instanceDocLog->logDocument($prID, Auth::user()->id, NULL, 'issued');

                $msg = "Purchase Request '$prNo' successfully created.";
                //Auth::user()->log($request, $msg);
                return redirect(url()->previous())->with('success', $msg);
            } else {
                $msg = "Purchase Request '$prNo' has a duplicate.";
                Auth::user()->log($request, $msg);
                return redirect()->route('pr', ['keyword' => $prID])
                                 ->with('warning', $msg);
            }
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $instanceDocLog = new DocLog;

        // Parent variables
        $prDate = $request->date_pr;
        $divisionID = $request->division;
        $purpose = $request->purpose;
        $remarks = $request->remarks;
        $projectID = $request->project;
        $requestedBy = $request->requested_by;
        $approvedBy = $request->approved_by;
        $recommendedBy = $request->recommended_by;
        $office = $request->office;

        // PR items variables
        $itemIDs = $request->item_id;
        $unitIssues = $request->unit;
        $itemDescriptions = $request->item_description;
        $quantities = $request->quantity;
        $unitCosts = $request->unit_cost;
        $totalCosts = $request->total_cost;

        try {
            $instancePR = PurchaseRequest::find($id);
            $instancePR->date_pr = $prDate;
            $instancePR->funding_source = $projectID;
            $instancePR->purpose = $purpose;
            $instancePR->remarks = $remarks;
            $instancePR->division = $divisionID;
            $instancePR->requested_by = $requestedBy;
            $instancePR->approved_by = $approvedBy;
            $instancePR->recommended_by = $recommendedBy;
            $instancePR->office = $office;

            $prNo = $instancePR->pr_no;

            // Delete other dependent documents
            if ($instancePR->status >= 5) {
                RequestQuotation::where('pr_id', $id)->forceDelete();
                AbstractQuotation::where('pr_id', $id)->forceDelete();
                AbstractQuotationItem::where('pr_id', $id)->delete();
                PurchaseJobOrder::where('pr_id', $id)->forceDelete();
                PurchaseJobOrderItem::where('pr_id', $id)->delete();
                ObligationRequestStatus::where('pr_id', $id)->forceDelete();
                InspectionAcceptance::where('pr_id', $id)->forceDelete();
                DisbursementVoucher::where('pr_id', $id)->forceDelete();
                InventoryStock::where('pr_id', $id)->forceDelete();
                InventoryStockIssue::where('pr_id', $id)->delete();
            }

            // Update pr items
            foreach ($unitIssues as $arrayKey => $unit) {
                $itemID = $itemIDs[$arrayKey];
                $itemCount = count($itemIDs) - 1;
                $description = $itemDescriptions[$arrayKey];
                $quantity = $quantities[$arrayKey];
                $unitCost = $unitCosts[$arrayKey];
                $totalCost =  $quantity * $unitCost;

                if ($instancePR->status < 5) {
                    if ($arrayKey == 0) {
                        PurchaseRequestItem::where('pr_id', $id)->delete();
                    }

                    $instancePRItem = new PurchaseRequestItem;
                    $instancePRItem->pr_id = $id;
                    $instancePRItem->item_no = $arrayKey + 1;
                    $instancePRItem->quantity = $quantity;
                    $instancePRItem->unit_issue = $unit;
                    $instancePRItem->item_description = $description;
                    $instancePRItem->est_unit_cost = $unitCost;
                    $instancePRItem->est_total_cost = $totalCost;
                    $instancePRItem->save();
                } else {
                    if ($itemCount == $arrayKey) {
                        $instancePR->status = 1;
                        $instancePR->notifyForApproval($prNo, $requestedBy);
                        $instanceDocLog->logDocument($id, Auth::user()->id, NULL, '-');
                        $instanceDocLog->logDocument($id, Auth::user()->id, NULL, 'issued');
                    }

                    $instancePRItem = PurchaseRequestItem::find($itemID);
                    $instancePRItem = $instancePRItem ? $instancePRItem : new PurchaseRequestItem;
                    $instancePRItem->pr_id = $id;
                    $instancePRItem->item_no = $arrayKey + 1;
                    $instancePRItem->quantity = $quantity;
                    $instancePRItem->unit_issue = $unit;
                    $instancePRItem->item_description = $description;
                    $instancePRItem->est_unit_cost = $unitCost;
                    $instancePRItem->est_total_cost = $totalCost;
                    $instancePRItem->save();
                }
            }

            $instancePR->save();

            $msg = "Purchase Request '$prNo' successfully updated.";
            Auth::user()->log($request, $msg);
            return redirect()->route('pr', ['keyword' => $id])
                             ->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }
    }

    public function delete(Request $request, $id) {
        try {
            $instancePR = PurchaseRequest::find($id);
            $prNo = $instancePR->pr_no;
            $instancePR->delete();

            RequestQuotation::where('pr_id', $id)->delete();
            AbstractQuotation::where('pr_id', $id)->delete();
            PurchaseJobOrder::where('pr_id', $id)->delete();
            ObligationRequestStatus::where('pr_id', $id)->delete();
            InspectionAcceptance::where('pr_id', $id)->delete();
            DisbursementVoucher::where('pr_id', $id)->delete();
            InventoryStock::where('pr_id', $id)->delete();

            $msg = "Purchase request '$prNo' successfully deleted.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }

    }

    public function approve(Request $request, $id) {
        try {
            $instanceDocLog = new DocLog;
            $instanceRFQ = RequestQuotation::where('pr_id', $id)->first();
            $instancePR = PurchaseRequest::find($id);
            $prNo = $instancePR->pr_no;
            $requestedBy = $instancePR->requested_by;
            $instancePR->date_pr_approved = Carbon::now();
            $instancePR->status = 5;
            $instancePR->save();

            if (!$instanceRFQ) {
                $instanceRFQ = new RequestQuotation;
                $instanceRFQ->pr_id = $id;
                $instanceRFQ->save();

                $rfqData = RequestQuotation::where('pr_id', $id)->first();
                $rfqID = $rfqData->id;
                $instanceDocLog->logDocument($id, Auth::user()->id, NULL, 'received');
            }

            $instancePR->notifyApproved($prNo, $requestedBy);

            $msg = "Purchase request '$prNo' successfully approved.";
            Auth::user()->log($request, $msg);
            return redirect()->route('rfq', ['keyword' => $id])
                             ->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }
    }

    public function disapprove(Request $request, $id) {
        try {
            $instanceDocLog = new DocLog;
            $instancePR = PurchaseRequest::find($id);
            $prNo = $instancePR->pr_no;
            $requestedBy = $instancePR->requested_by;
            $instancePR->date_pr_disapproved = Carbon::now();
            $instancePR->status = 2;
            $instancePR->save();

            $instanceDocLog->logDocument($id, Auth::user()->id, NULL, '-');
            $instancePR->notifyDisapproved($prNo, $requestedBy);

            $msg = "Purchase request '$prNo' successfully disapproved.";
            Auth::user()->log($request, $msg);
            return redirect()->route('pr', ['keyword' => $id])
                             ->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }
    }

    public function cancel(Request $request, $id) {
        try {
            $instanceDocLog = new DocLog;
            $instancePR = PurchaseRequest::find($id);
            $prNo = $instancePR->pr_no;
            $requestedBy = $instancePR->requested_by;
            $instancePR->date_pr_cancelled = Carbon::now();
            $instancePR->status = 3;
            $instancePR->save();

            $instanceDocLog->logDocument($id, Auth::user()->id, NULL, '-');
            $instancePR->notifyCancelled($prNo, $requestedBy);

            $msg = "Purchase request '$prNo' successfully cancelled.";
            Auth::user()->log($request, $msg);
            return redirect()->route('pr', ['keyword' => $id])
                             ->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }
    }
}
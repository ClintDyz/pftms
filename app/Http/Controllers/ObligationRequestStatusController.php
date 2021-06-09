<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\AbstractQuotation;
use App\Models\AbstractQuotationItem;
use App\Models\PurchaseJobOrder;
use App\Models\PurchaseJobOrderItem;
use App\Models\ObligationRequestStatus;
use App\Models\InspectionAcceptance;
use App\Models\DisbursementVoucher;
use App\Models\InventoryStock;

use App\Models\EmpAccount as User;
use App\Models\DocumentLog as DocLog;
use App\Models\PaperSize;
use App\Models\Supplier;
use App\Models\Signatory;
use App\Models\ItemUnitIssue;
use App\Models\FundingProject;
use Carbon\Carbon;
use Auth;
use DB;

use App\Plugins\Notification as Notif;

class ObligationRequestStatusController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function indexProc(Request $request) {
        $data = $this->getIndexData($request, 'procurement');

        $roleHasOrdinary = Auth::user()->hasOrdinaryRole();
        $roleHasBudget = Auth::user()->hasBudgetRole();
        $roleHasAccountant = Auth::user()->hasAccountantRole();

        // Get module access
        $module = 'proc_ors_burs';
        $isAllowedUpdate = Auth::user()->getModuleAccess($module, 'update');
        $isAllowedIssue = Auth::user()->getModuleAccess($module, 'issue');
        $isAllowedIssueBack = Auth::user()->getModuleAccess($module, 'issue_back');
        $isAllowedReceive = Auth::user()->getModuleAccess($module, 'receive');
        $isAllowedReceiveBack = Auth::user()->getModuleAccess($module, 'receive_back');
        $isAllowedObligate = Auth::user()->getModuleAccess($module, 'obligate');
        $isAllowedPO = Auth::user()->getModuleAccess('proc_po_jo', 'is_allowed');

        return view('modules.procurement.ors-burs.index', [
            'list' => $data->ors_data,
            'keyword' => $data->keyword,
            'paperSizes' => $data->paper_sizes,
            'isAllowedUpdate' => $isAllowedUpdate,
            'isAllowedObligate' => $isAllowedObligate,
            'isAllowedIssue' => $isAllowedIssue,
            'isAllowedIssueBack'=> $isAllowedIssueBack,
            'isAllowedReceive' => $isAllowedReceive,
            'isAllowedReceiveBack'=> $isAllowedReceiveBack,
            'isAllowedPO' => $isAllowedPO,
            'roleHasOrdinary' => $roleHasOrdinary,
            'roleHasBudget' => $roleHasBudget,
            'roleHasAccountant' => $roleHasAccountant,
        ]);
    }

    public function indexCA(Request $request) {
        $data = $this->getIndexData($request, 'cashadvance');

        $roleHasOrdinary = Auth::user()->hasOrdinaryRole();

        // Get module access
        $module = 'ca_ors_burs';
        $isAllowedCreate = Auth::user()->getModuleAccess($module, 'create');
        $isAllowedUpdate = Auth::user()->getModuleAccess($module, 'update');
        $isAllowedDelete = Auth::user()->getModuleAccess($module, 'delete');
        $isAllowedDestroy = Auth::user()->getModuleAccess($module, 'destroy');
        $isAllowedIssue = Auth::user()->getModuleAccess($module, 'issue');
        $isAllowedIssueBack = Auth::user()->getModuleAccess($module, 'issue_back');
        $isAllowedReceive = Auth::user()->getModuleAccess($module, 'receive');
        $isAllowedReceiveBack = Auth::user()->getModuleAccess($module, 'receive_back');
        $isAllowedObligate = Auth::user()->getModuleAccess($module, 'obligate');
        $isAllowedDV = Auth::user()->getModuleAccess('ca_dv', 'create');
        $isAllowedDVCreate = Auth::user()->getModuleAccess('ca_dv', 'is_allowed');

        return view('modules.voucher.ors-burs.index', [
            'list' => $data->ors_data,
            'keyword' => $data->keyword,
            'paperSizes' => $data->paper_sizes,
            'isAllowedCreate' => $isAllowedCreate,
            'isAllowedUpdate' => $isAllowedUpdate,
            'isAllowedDelete' => $isAllowedDelete,
            'isAllowedDestroy' => $isAllowedDestroy,
            'isAllowedObligate' => $isAllowedObligate,
            'isAllowedIssue' => $isAllowedIssue,
            'isAllowedIssueBack'=> $isAllowedIssueBack,
            'isAllowedReceive' => $isAllowedReceive,
            'isAllowedReceiveBack'=> $isAllowedReceiveBack,
            'isAllowedDV' => $isAllowedDV,
            'isAllowedDVCreate' => $isAllowedDVCreate,
            'roleHasOrdinary' => $roleHasOrdinary,
        ]);
    }

    private function getIndexData($request, $type) {
        $keyword = trim($request->keyword);
        $instanceDocLog = new DocLog;

        // User groups
        $roleHasOrdinary = Auth::user()->hasOrdinaryRole();
        $empDivisionAccess = !$roleHasOrdinary ? Auth::user()->getDivisionAccess() :
                             [Auth::user()->division];

        // Main data
        $paperSizes = PaperSize::orderBy('paper_type')->get();

        if ($type == 'procurement') {
            $orsData = PurchaseJobOrder::with('bidpayee')->whereHas('pr', function($query)
                                                use($empDivisionAccess) {
                $query->whereIn('division', $empDivisionAccess)
                      ->whereNull('date_pr_cancelled');
            })->whereHas('ors', function($query) {
                $query->whereNull('deleted_at');
            })->whereNull('date_cancelled');

            if (!empty($keyword)) {
                $orsData = $orsData->where(function($qry) use ($keyword) {
                    $qry->where('id', 'like', "%$keyword%")
                        ->orWhere('po_no', 'like', "%$keyword%")
                        ->orWhere('date_po', 'like', "%$keyword%")
                        ->orWhere('grand_total', 'like', "%$keyword%")
                        ->orWhere('document_type', 'like', "%$keyword%")
                        ->orWhereHas('stat', function($query) use ($keyword) {
                            $query->where('status_name', 'like', "%$keyword%");
                        })->orWhereHas('bidpayee', function($query) use ($keyword) {
                            $query->where('company_name', 'like', "%$keyword%")
                                ->orWhere('address', 'like', "%$keyword%");
                        })->orWhereHas('poitems', function($query) use ($keyword) {
                            $query->where('item_description', 'like', "%$keyword%");
                        })->orWhereHas('ors', function($query) use ($keyword) {
                            $query->where('id', 'like', "%$keyword%")
                                ->orWhere('particulars', 'like', "%$keyword%")
                                ->orWhere('document_type', 'like', "%$keyword%")
                                ->orWhere('transaction_type', 'like', "%$keyword%")
                                ->orWhere('serial_no', 'like', "%$keyword%")
                                ->orWhere('date_ors_burs', 'like', "%$keyword%")
                                ->orWhere('date_obligated', 'like', "%$keyword%")
                                ->orWhere('responsibility_center', 'like', "%$keyword%")
                                ->orWhere('uacs_object_code', 'like', "%$keyword%")
                                ->orWhere('amount', 'like', "%$keyword%")
                                ->orWhere('office', 'like', "%$keyword%")
                                ->orWhere('address', 'like', "%$keyword%")
                                ->orWhere('fund_cluster', 'like', "%$keyword%");
                        });
                });
            }

            $orsData = $orsData->sortable(['po_no' => 'desc'])->paginate(15);

            foreach ($orsData as $orsDat) {
                $orsDat->doc_status = $instanceDocLog->checkDocStatus($orsDat->ors['id']);
            }
        } else {
            $orsData = ObligationRequestStatus::whereHas('emppayee', function($query)
                                                use($empDivisionAccess) {
                $query->whereIn('division', $empDivisionAccess);
            })->whereNull('deleted_at')->where('module_class', 2);

            if ($roleHasOrdinary) {
                $orsData = $orsData->where('payee', Auth::user()->id);
            }

            if (!empty($keyword)) {
                $orsData = $orsData->where(function($qry) use ($keyword) {
                    $qry->where('id', 'like', "%$keyword%")
                        ->orWhere('particulars', 'like', "%$keyword%")
                        ->orWhere('document_type', 'like', "%$keyword%")
                        ->orWhere('transaction_type', 'like', "%$keyword%")
                        ->orWhere('serial_no', 'like', "%$keyword%")
                        ->orWhere('date_ors_burs', 'like', "%$keyword%")
                        ->orWhere('date_obligated', 'like', "%$keyword%")
                        ->orWhere('responsibility_center', 'like', "%$keyword%")
                        ->orWhere('uacs_object_code', 'like', "%$keyword%")
                        ->orWhere('amount', 'like', "%$keyword%")
                        ->orWhere('office', 'like', "%$keyword%")
                        ->orWhere('address', 'like', "%$keyword%")
                        ->orWhere('fund_cluster', 'like', "%$keyword%")
                        ->orWhereHas('emppayee', function($query) use ($keyword) {
                            $query->where('firstname', 'like', "%$keyword%")
                                  ->orWhere('middlename', 'like', "%$keyword%")
                                  ->orWhere('lastname', 'like', "%$keyword%")
                                  ->orWhere('position', 'like', "%$keyword%");
                        });
                });
            }

            $orsData = $orsData->sortable(['created_at' => 'desc'])->paginate(15);

            foreach ($orsData as $orsDat) {
                $orsDat->doc_status = $instanceDocLog->checkDocStatus($orsDat->id);
                $orsDat->has_dv = DisbursementVoucher::where('ors_id', $orsDat->id)->count();
            }
        }

        return (object) [
            'keyword' => $keyword,
            'ors_data' => $orsData,
            'paper_sizes' => $paperSizes
        ];
    }

    /**
     * Store a newly created resource from PO/JO in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  uuid $poID
     * @return \Illuminate\Http\Response
     */
    public function storeORSFromPO(Request $request, $poID) {
        $orsDocumentType = $request->ors_document_type;

        try {
            $instanceDocLog = new DocLog;
            $instanceNotif = new Notif;

            $instancePO = PurchaseJobOrder::find($poID);
            $poNo = $instancePO->po_no;
            $prID = $instancePO->pr_id;
            $documentType = $instancePO->document_type;
            $documentType = $documentType == 'po' ? 'Purchase Order' : 'Job Order';
            $grandTotal = $instancePO->grand_total;

            if ($grandTotal > 0) {
                $instanceORS = ObligationRequestStatus::withTrashed()
                                                      ->where('po_no', $poNo)
                                                      ->first();
                $prData = PurchaseRequest::find($prID);
                $project = $prData->funding_source;

                if (!$instanceORS) {
                    $instanceORS = new ObligationRequestStatus;
                    $instanceORS->pr_id = $prID;
                    $instanceORS->po_no = $poNo;
                    $instanceORS->responsibility_center = "19 001 03000 14";
                    $instanceORS->particulars = "To obligate...";
                    $instanceORS->mfo_pap = "3-Regional Office\nA.III.c.1\nA.III.b.1\nA.III.c.2";
                    $instanceORS->payee = $instancePO->awarded_to;
                    $instanceORS->amount = $instancePO->grand_total;
                    $instanceORS->module_class = 3;
                    $instanceORS->funding_source = $project;
                    $instanceORS->save();
                } else {
                    $instanceDocLog->logDocument($instanceORS->id, Auth::user()->id, NULL, '-');
                    $instanceORS->date_obligated = NULL;
                    $instanceORS->obligated_by = NULL;
                    $instanceORS->save();
                    ObligationRequestStatus::withTrashed()->where('po_no', $poNo)->restore();
                }

                $instancePO->for_approval = 'y';
                $instancePO->with_ors_burs = 'y';

                if ($instanceORS->date_obligated) {
                    $instancePO->status = 7;
                }

                $instancePO->save();

                $instanceNotif->notifyCreatedORS($poNo);

                $documentType = $documentType == 'po' ? 'Purchase Order' : 'Job Order';
                $msg = "$documentType '$poNo' successfully created the ORS/BURS document.";
                Auth::user()->log($request, $msg);
                return redirect()->route('proc-ors-burs', ['keyword' => $poNo])
                                 ->with('success', $msg);
            } else {
                $documentType = $documentType == 'po' ? 'Purchase Order' : 'Job Order';
                $msg = "$documentType '$poNo' should have a grand total greater than 0 and
                        no existing ORS/BURS document.";
                Auth::user()->log($request, $msg);
                return redirect()->route('po-jo', ['keyword' => $poNo])
                                 ->with('warning', $msg);
            }

            if ($ountORS > 0) {
                ObligationRequestStatus::where('po_no', $poNo)->restore();
            }
        } catch (\Throwable $th) {
            $instanceORS = PurchaseJobOrder::find($poID);
            $poNo = $instanceORS->po_no;

            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect()->route('po-jo', ['keyword' => $poNo])
                             ->with('failed', $msg);
        }
    }

    /**
     * Show the form for creatingr the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showCreate() {
        $roleHasOrdinary = Auth::user()->hasOrdinaryRole();
        $empDivisionAccess = !$roleHasOrdinary ? Auth::user()->getDivisionAccess() :
                             [Auth::user()->division];
        $projects = FundingProject::orderBy('project_title')->get();
        $payees = $roleHasOrdinary ?
                User::where('id', Auth::user()->id)
                    ->orderBy('firstname')
                    ->get() :
                User::where('is_active', 'y')
                    ->whereIn('division', $empDivisionAccess)
                    ->orderBy('firstname')->get();
        $signatories = Signatory::addSelect([
            'name' => User::select(DB::raw('CONCAT(firstname, " ", lastname) AS name'))
                          ->whereColumn('id', 'signatories.emp_id')
                          ->limit(1)
        ])->where('is_active', 'y')->get();

        foreach ($signatories as $sig) {
            $sig->module = json_decode($sig->module);
        }

        return view('modules.voucher.ors-burs.create', compact(
            'signatories', 'payees', 'projects'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $documentType = $request->document_type;
        $transactionType = !empty($request->transaction_type) ? $request->transaction_type: 'others';
        $serialNo = $request->serial_no;
        $dateORS = !empty($request->date_ors_burs) ? $request->date_ors_burs: NULL;
        $fundCluster = $request->fund_cluster;
        $payee = $request->payee;
        $office = $request->office;
        $address = $request->address;
        $responsibilityCenter = $request->responsibility_center;
        $particulars = $request->particulars;
        $mfoPAP = $request->mfo_pap;
        $uacsObjectCode = $request->uacs_object_code;
        $project = $request->funding_source;
        $amount = $request->amount;
        $sigCertified1 = !empty($request->sig_certified_1) ? $request->sig_certified_1: NULL;
        $sigCertified2 = !empty($request->sig_certified_2) ? $request->sig_certified_2: NULL;
        $dateCertified1 = !empty($request->date_certified_1) ? $request->date_certified_1: NULL;
        $dateCertified2 = !empty($request->date_certified_2) ? $request->date_certified_2: NULL;

        $routeName = 'ca-ors-burs';

        try {
            $instanceORS = new ObligationRequestStatus;
            $instanceORS->document_type = $documentType;
            $instanceORS->transaction_type = $transactionType;
            $instanceORS->serial_no = $serialNo;
            $instanceORS->date_ors_burs = $dateORS;
            $instanceORS->fund_cluster = $fundCluster;
            $instanceORS->payee = $payee;
            $instanceORS->office = $office;
            $instanceORS->address = $address;
            $instanceORS->responsibility_center = $responsibilityCenter;
            $instanceORS->particulars = $particulars;
            $instanceORS->mfo_pap = $mfoPAP;
            $instanceORS->uacs_object_code = $uacsObjectCode;
            $instanceORS->sig_certified_1 = $sigCertified1;
            $instanceORS->sig_certified_2 = $sigCertified2;
            $instanceORS->date_certified_1 = $dateCertified1;
            $instanceORS->date_certified_2 = $dateCertified2;
            $instanceORS->funding_source = $project;
            $instanceORS->amount = $amount;
            $instanceORS->module_class = 2;
            $instanceORS->save();

            $documentType = $documentType == 'ors' ? 'Obligation Request & Status' :
                            'Budget Utilization Request & Status';

            $msg = "$documentType successfully created.";
            Auth::user()->log($request, $msg);
            return redirect()->route($routeName)
                             ->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())
                                 ->with('failed', $msg);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showEdit($id) {
        $orsData = ObligationRequestStatus::find($id);
        $isObligated = !empty($orsData->date_obligated) ? 1 : 0;
        $moduleClass = $orsData->module_class;
        $documentType = $orsData->document_type;
        $serialNo = $orsData->serial_no;
        $dateORS = $orsData->date_ors_burs;
        $fundCluster = $orsData->fund_cluster;
        $payee = $orsData->payee;
        $office = $orsData->office;
        $address = $orsData->address;
        $responsibilityCenter = $orsData->responsibility_center;
        $particulars = $orsData->particulars;
        $mfoPAP = $orsData->mfo_pap;
        $uacsObjectCode = $orsData->uacs_object_code;
        $amount = $orsData->amount;
        $sigCertified1 = $orsData->sig_certified_1;
        $sigCertified2 = $orsData->sig_certified_2;
        $dateCertified1 = $orsData->date_certified_1;
        $dateCertified2 = $orsData->date_certified_2;
        $transactionType = $orsData->transaction_type;
        $project = $orsData->funding_source;
        $projects = FundingProject::orderBy('project_title')->get();
        $signatories = Signatory::addSelect([
            'name' => User::select(DB::raw('CONCAT(firstname, " ", lastname) AS name'))
                          ->whereColumn('id', 'signatories.emp_id')
                          ->limit(1)
        ])->where('is_active', 'y')->get();

        if ($moduleClass == 3) {
            $viewFile = 'modules.procurement.ors-burs.update';
            $payees = Supplier::orderBy('company_name')->get();
        } else if ($moduleClass == 2) {
            $viewFile = 'modules.voucher.ors-burs.update';
            $payees = User::orderBy('firstname')->get();
        }

        foreach ($signatories as $sig) {
            $sig->module = json_decode($sig->module);
        }

        return view($viewFile, compact(
            'id', 'documentType', 'serialNo', 'dateORS',
            'fundCluster', 'payee', 'office', 'address',
            'responsibilityCenter', 'particulars', 'mfoPAP',
            'uacsObjectCode', 'uacsObjectCode', 'amount',
            'sigCertified1', 'sigCertified2', 'dateCertified1',
            'dateCertified2', 'signatories', 'payees', 'isObligated',
            'transactionType', 'projects', 'project'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $documentType = $request->document_type;
        $transactionType = !empty($request->transaction_type) ? $request->transaction_type: 'others';
        $serialNo = $request->serial_no;
        $dateORS = !empty($request->date_ors_burs) ? $request->date_ors_burs: NULL;
        $fundCluster = $request->fund_cluster;
        $office = $request->office;
        $address = $request->address;
        $responsibilityCenter = $request->responsibility_center;
        $particulars = $request->particulars;
        $mfoPAP = $request->mfo_pap;
        $uacsObjectCode = $request->uacs_object_code;
        $project = $request->funding_source;
        $amount = $request->amount;
        $sigCertified1 = !empty($request->sig_certified_1) ? $request->sig_certified_1: NULL;
        $sigCertified2 = !empty($request->sig_certified_2) ? $request->sig_certified_2: NULL;
        $dateCertified1 = !empty($request->date_certified_1) ? $request->date_certified_1: NULL;
        $dateCertified2 = !empty($request->date_certified_2) ? $request->date_certified_2: NULL;

        try {
            $instanceORS = ObligationRequestStatus::find($id);
            $moduleClass = $instanceORS->module_class;

            if ($moduleClass == 3) {
                $routeName = 'proc-ors-burs';
            } else if ($moduleClass == 2) {
                $routeName = 'ca-ors-burs';
                $instanceORS->transaction_type = $transactionType;
            }

            $instanceORS->document_type = $documentType;
            $instanceORS->serial_no = $serialNo;
            $instanceORS->date_ors_burs = $dateORS;
            $instanceORS->fund_cluster = $fundCluster;
            $instanceORS->office = $office;
            $instanceORS->address = $address;
            $instanceORS->responsibility_center = $responsibilityCenter;
            $instanceORS->particulars = $particulars;
            $instanceORS->mfo_pap = $mfoPAP;
            $instanceORS->uacs_object_code = $uacsObjectCode;
            $instanceORS->sig_certified_1 = $sigCertified1;
            $instanceORS->sig_certified_2 = $sigCertified2;
            $instanceORS->date_certified_1 = $dateCertified1;
            $instanceORS->date_certified_2 = $dateCertified2;
            $instanceORS->funding_source = $project;
            $instanceORS->amount = $amount;
            $instanceORS->save();

            $documentType = $documentType == 'ors' ? 'Obligation Request & Status' :
                            'Budget Utilization Request & Status';

            $msg = "$documentType '$id' successfully updated.";
            Auth::user()->log($request, $msg);
            return redirect()->route($routeName, ['keyword' => $id])
                             ->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())
                                 ->with('failed', $msg);
        }
    }

    /**
     * Soft deletes the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $id) {
        $isDestroy = $request->destroy;

        if ($isDestroy) {
            $response = $this->destroy($request, $id);

            if ($response->alert_type == 'success') {
                return redirect()->route('ca-ors-burs', ['keyword' => $response->id])
                                 ->with($response->alert_type, $response->msg);
            } else {
                return redirect()->route('ca-ors-burs')
                                 ->with($response->alert_type, $response->msg);
            }
        } else {
            try {
                $instanceORS = ObligationRequestStatus::find($id);
                //$instanceDV = DisbursementVoucher::where('ors_id', $id)->first();
                $documentType = $instanceORS->document_type;
                $documentType = $documentType == 'ors' ? 'Obligation Request and Status' :
                                                 'Budget Utilization and Request Status';
                $orsID = $instanceORS->id;
                $instanceORS->delete();

                /*
                if ($instanceDV) {
                    $instanceDV->delete();
                }*/

                $msg = "$documentType '$orsID' successfully deleted.";
                Auth::user()->log($request, $msg);
                return redirect()->route('ca-ors-burs', ['keyword' => $id])
                                 ->with('success', $msg);
            } catch (\Throwable $th) {
                $msg = "Unknown error has occured. Please try again.";
                Auth::user()->log($request, $msg);
                return redirect()->route('ca-ors-burs')
                                 ->with('failed', $msg);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($request, $id) {
        try {
            $instanceORS = ObligationRequestStatus::find($id);
            //$instanceDV = DisbursementVoucher::where('ors_id', $id)->first();
            $documentType = $instanceORS->document_type;
            $documentType = $documentType == 'ors' ? 'Obligation Request and Status' :
                                             'Budget Utilization and Request Status';
            $orsID = $instanceORS->id;
            $instanceORS->forceDelete();

            /*
            if ($instanceDV) {
                $instanceDV->forceDelete();
            }*/

            $msg = "$documentType '$orsID' permanently deleted.";
            Auth::user()->log($request, $msg);

            return (object) [
                'msg' => $msg,
                'alert_type' => 'success',
                'id' => $id
            ];
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);

            return (object) [
                'msg' => $msg,
                'alert_type' => 'failed'
            ];
        }
    }

    public function showIssue($id) {
        $instanceORS = ObligationRequestStatus::find($id);
        $moduleClass = $instanceORS->module_class;

        if ($moduleClass == 3) {
            $viewFile = 'modules.procurement.ors-burs.issue';
        } else if ($moduleClass == 2) {
            $viewFile = 'modules.voucher.ors-burs.issue';
        }

        return view($viewFile, [
            'id' => $id
        ]);
    }

    public function issue(Request $request, $id) {
        $remarks = $request->remarks;

        try {
            $instanceDocLog = new DocLog;
            $instanceNotif = new Notif;
            $instanceORS = ObligationRequestStatus::find($id);
            $moduleClass = $instanceORS->module_class;
            $documentType = $instanceORS->document_type;
            $documentType = $documentType == 'ors' ? 'Obligation Request & Status' :
                                             'Budget Utilization Request & Status';

            $isDocGenerated = $instanceDocLog->checkDocGenerated($id);
            $docStatus = $instanceDocLog->checkDocStatus($id);

            if ($moduleClass == 3) {
                $routeName = 'proc-ors-burs';
            } else if ($moduleClass == 2) {
                $routeName = 'ca-ors-burs';
            }

            if (empty($docStatus->date_issued)) {
                if ($isDocGenerated) {
                    $instanceDocLog->logDocument($id, Auth::user()->id, NULL, "issued", $remarks);

                    $instanceNotif->notifyIssuedORS($id, $routeName);

                    $msg = "$documentType '$id' successfully submitted to budget unit.";
                    Auth::user()->log($request, $msg);
                    return redirect()->route($routeName, ['keyword' => $id])
                                     ->with('success', $msg);
                } else {
                    $msg = "Document for $documentType '$id' should be generated first.";
                    Auth::user()->log($request, $msg);
                    return redirect()->route($routeName, ['keyword' => $id])
                                     ->with('warning', $msg);
                }
            } else {
                $msg = "$documentType '$id' already submitted.";
                Auth::user()->log($request, $msg);
                return redirect()->route($routeName, ['keyword' => $id])
                                 ->with('warning', $msg);
            }
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }
    }

    public function showReceive($id) {
        $instanceORS = ObligationRequestStatus::find($id);
        $moduleClass = $instanceORS->module_class;

        if ($moduleClass == 3) {
            $viewFile = 'modules.procurement.ors-burs.receive';
        } else if ($moduleClass == 2) {
            $viewFile = 'modules.voucher.ors-burs.receive';
        }

        return view($viewFile, [
            'id' => $id
        ]);
    }

    public function receive(Request $request, $id) {
        $remarks = $request->remarks;

        try {
            $instanceDocLog = new DocLog;
            $instanceNotif = new Notif;
            $instanceORS = ObligationRequestStatus::find($id);
            $moduleClass = $instanceORS->module_class;
            $documentType = $instanceORS->document_type;
            $documentType = $documentType == 'ors' ? 'Obligation Request & Status' :
                                             'Budget Utilization Request & Status';

            if ($moduleClass == 3) {
                $routeName = 'proc-ors-burs';
            } else if ($moduleClass == 2) {
                $routeName = 'ca-ors-burs';
            }

            $instanceDocLog->logDocument($id, Auth::user()->id, NULL, "received", $remarks);
            $instanceNotif->notifyReceivedORS($id, $routeName);

            $msg = "$documentType '$id' successfully received.";
            Auth::user()->log($request, $msg);
            return redirect()->route($routeName, ['keyword' => $id])
                             ->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }
    }

    public function showIssueBack($id) {
        $instanceORS = ObligationRequestStatus::find($id);
        $moduleClass = $instanceORS->module_class;

        if ($moduleClass == 3) {
            $viewFile = 'modules.procurement.ors-burs.issue-back';
        } else if ($moduleClass == 2) {
            $viewFile = 'modules.voucher.ors-burs.issue-back';
        }

        return view($viewFile, [
            'id' => $id
        ]);
    }

    public function issueBack(Request $request, $id) {
        $remarks = $request->remarks;

        try {
            $instanceDocLog = new DocLog;
            $instanceNotif = new Notif;
            $instanceORS = ObligationRequestStatus::find($id);
            $moduleClass = $instanceORS->module_class;
            $documentType = $instanceORS->document_type;
            $documentType = $documentType == 'ors' ? 'Obligation Request & Status' :
                                             'Budget Utilization Request & Status';

            if ($moduleClass == 3) {
                $routeName = 'proc-ors-burs';
            } else if ($moduleClass == 2) {
                $routeName = 'ca-ors-burs';
            }

            $instanceDocLog->logDocument($id, Auth::user()->id, NULL, "issued_back", $remarks);
            //$instanceNotif->notifyIssuedBackORS($id, $routeName);

            $msg = "$documentType '$id' successfully submitted back.";
            Auth::user()->log($request, $msg);
            return redirect()->route($routeName, ['keyword' => $id])
                             ->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }
    }

    public function showReceiveBack($id) {
        $instanceORS = ObligationRequestStatus::find($id);
        $moduleClass = $instanceORS->module_class;

        if ($moduleClass == 3) {
            $viewFile = 'modules.procurement.ors-burs.receive-back';
        } else if ($moduleClass == 2) {
            $viewFile = 'modules.voucher.ors-burs.receive-back';
        }

        return view($viewFile, [
            'id' => $id
        ]);
    }

    public function receiveBack(Request $request, $id) {
        $remarks = $request->remarks;

        try {
            $instanceDocLog = new DocLog;
            $instanceNotif = new Notif;
            $instanceORS = ObligationRequestStatus::find($id);
            $moduleClass = $instanceORS->module_class;
            $documentType = $instanceORS->document_type;
            $documentType = $documentType == 'ors' ? 'Obligation Request & Status' :
                                             'Budget Utilization Request & Status';

            if ($moduleClass == 3) {
                $routeName = 'proc-ors-burs';
            } else if ($moduleClass == 2) {
                $routeName = 'ca-ors-burs';
            }

            $instanceDocLog->logDocument($id, NULL, NULL, "-", NULL);
            $instanceDocLog->logDocument($id, Auth::user()->id, NULL, "received_back", $remarks);
            //$instanceNotif->notifyReceivedBackORS($id, $routeName);

            $msg = "$documentType '$id' successfully received back.";
            Auth::user()->log($request, $msg);
            return redirect()->route($routeName, ['keyword' => $id])
                             ->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }
    }

    public function showObligate($id) {
        $instanceORS = ObligationRequestStatus::find($id);
        $moduleClass = $instanceORS->module_class;
        $serialNo = $instanceORS->serial_no;

        if ($moduleClass == 3) {
            $viewFile = 'modules.procurement.ors-burs.obligate';
        } else if ($moduleClass == 2) {
            $viewFile = 'modules.voucher.ors-burs.obligate';
        }

        return view($viewFile, [
            'id' => $id,
            'serialNo' => $serialNo
        ]);
    }

    public function obligate(Request $request, $id) {
        $serialNo = $request->serial_no;

        try {
            $instanceDocLog = new DocLog;
            $instanceNotif = new Notif;
            $instanceORS = ObligationRequestStatus::with('po')->find($id);
            $moduleClass = $instanceORS->module_class;
            $documentType = $instanceORS->document_type;
            $documentType = $documentType == 'ors' ? 'Obligation Request & Status' :
                                             'Budget Utilization Request & Status';

            if ($moduleClass == 3) {
                $routeName = 'proc-ors-burs';
                $instancePO = PurchaseJobOrder::find($instanceORS->po->id);
                $instancePO->status = 7;
                $instancePO->save();
            } else if ($moduleClass == 2) {
                $routeName = 'ca-ors-burs';
            }

            $instanceORS->date_obligated = Carbon::now();
            $instanceORS->obligated_by = Auth::user()->id;
            $instanceORS->serial_no = $serialNo;
            $instanceORS->save();

            $instanceNotif->notifyObligatedORS($id, $routeName);

            $msg = "$documentType with a serial number of '$serialNo'
                    is successfully obligated.";
            Auth::user()->log($request, $msg);
            return redirect()->route($routeName, ['keyword' => $id])
                             ->with('success', $msg);
        } catch (\Throwable $th) {
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())->with('failed', $msg);
        }
    }

    public function showLogRemarks($id) {
        $instanceDocLog = DocLog::where('doc_id', $id)
                                ->whereNotNull('remarks')
                                ->orderBy('logged_at', 'desc')
                                ->get();
        $instanceORS = ObligationRequestStatus::find($id);
        $moduleClass = $instanceORS->class;

        if ($moduleClass == 3) {
            $viewFile = 'modules.procurement.ors-burs.remarks';
        } else {
            $viewFile = 'modules.voucher.ors-burs.remarks';
        }

        return view($viewFile, [
            'id' => $id,
            'docRemarks' => $instanceDocLog
        ]);
    }

    public function logRemarks(Request $request, $id) {
        $message = $request->message;

        if (!empty($message)) {
            $instanceORS = ObligationRequestStatus::find($id);
            $instanceDocLog = new DocLog;
            $instanceORS->notifyMessage($id, Auth::user()->id, $message);
            $instanceDocLog->logDocument($id, Auth::user()->id, NULL, "message", $message);
            return 'Sent!';
        }
    }
}

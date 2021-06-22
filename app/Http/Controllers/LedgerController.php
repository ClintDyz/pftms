<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\FundingProject;
use App\Models\FundingBudget;
use App\Models\FundingAllotment;
use App\Models\FundingLedger;
use App\Models\FundingLedgerItem;
use App\Models\FundingBudgetRealignment;
use App\Models\FundingAllotmentRealignment;
use App\Models\ObligationRequestStatus;
use App\Models\AllotmentClass;
use App\Models\PaperSize;
use App\Models\EmpAccount as User;
use App\Models\Supplier;

use Carbon\Carbon;
use Auth;
use DB;

class LedgerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexObligation(Request $request) {
        $keyword = trim($request->keyword);

        // Get module access
        $module = 'report_orsledger';
        $isAllowedCreate = Auth::user()->getModuleAccess($module, 'create');
        $isAllowedUpdate = Auth::user()->getModuleAccess($module, 'update');
        $isAllowedDelete = Auth::user()->getModuleAccess($module, 'delete');
        $isAllowedDestroy = Auth::user()->getModuleAccess($module, 'destroy');

        // Main data
        $paperSizes = PaperSize::orderBy('paper_type')->get();
        $fundProject = $this->getIndexData($request, 'obligation');

        return view('modules.report.obligation-ledger.index', [
            'list' => $fundProject,
            'keyword' => $keyword,
            'paperSizes' => $paperSizes,
            'isAllowedCreate' => $isAllowedCreate,
            'isAllowedUpdate' => $isAllowedUpdate,
            'isAllowedDelete' => $isAllowedDelete,
            'isAllowedDestroy' => $isAllowedDestroy,
        ]);
    }

    public function indexDisbursement(Request $request) {
        $keyword = trim($request->keyword);

        // Get module access
        $module = 'report_dvledger';
        $isAllowedCreate = Auth::user()->getModuleAccess($module, 'create');
        $isAllowedUpdate = Auth::user()->getModuleAccess($module, 'update');
        $isAllowedDelete = Auth::user()->getModuleAccess($module, 'delete');
        $isAllowedDestroy = Auth::user()->getModuleAccess($module, 'destroy');

        // Main data
        $paperSizes = PaperSize::orderBy('paper_type')->get();
        $fundProject = $this->getIndexData($request, 'disbursement');

        return view('modules.report.disbursement-ledger.index', [
            'list' => $fundProject,
            'keyword' => $keyword,
            'paperSizes' => $paperSizes,
            'isAllowedCreate' => $isAllowedCreate,
            'isAllowedUpdate' => $isAllowedUpdate,
            'isAllowedDelete' => $isAllowedDelete,
            'isAllowedDestroy' => $isAllowedDestroy,
        ]);
    }

    private function getIndexData($request, $type) {
        $keyword = trim($request->keyword);
        $userID = Auth::user()->id;

        $roleHasOrdinary = Auth::user()->hasOrdinaryRole();
        $roleHasBudget = Auth::user()->hasBudgetRole();
        $roleHasAdministrator = Auth::user()->hasAdministratorRole();
        $roleHasDeveloper = Auth::user()->hasDeveloperRole();

        $fundProject = FundingProject::has('budget');

        if (!empty($keyword)) {
            $fundProject = $fundProject->where(function($qry) use ($keyword) {
                $qry->where('id', 'like', "%$keyword%")
                    ->orWhere('project_title', 'like', "%$keyword%")
                    ->orWhere('project_leader', 'like', "%$keyword%")
                    ->orWhere('date_from', 'like', "%$keyword%")
                    ->orWhere('date_to', 'like', "%$keyword%")
                    ->orWhere('project_cost', 'like', "%$keyword%")
                    ->orWhereHas('allotments', function($query) use ($keyword) {
                        $query->where('budget_id', 'like', "%$keyword%")
                              ->orWhere('allotment_name', "%$keyword%")
                              ->orWhere('allotment_cost', "%$keyword%");
                    })->orWhereHas('site', function($query) use ($keyword) {
                        $query->where('id', 'like', "%$keyword%")
                              ->orWhere('municipality_name', 'like', "%$keyword%");
                    });
            });
        }

        $fundProject = $fundProject->sortable(['project_title' => 'desc'])
                                   ->paginate(15);

        foreach ($fundProject as $project) {
            $ledgerDat = FundingLedger::where('project_id', $project->id)->first();
            $project->has_ledger = $ledgerDat ? true : false;
            $project->ledger_id = $ledgerDat ? $ledgerDat->id : NULL;
        }

        return $fundProject;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showCreate(Request $request, $type, $projectID) {
        $itemCounter = 0;
        $allotmentCounter = 0;
        $allotmentClasses = AllotmentClass::orderBy('order_no')->get();
        $libData = FundingBudget::where('project_id', $projectID)
                                ->whereNotNull('date_approved')
                                ->first();
        $libID = $libData->id;
        $allotments = FundingAllotment::where('budget_id', $libID)
                                      ->orderBy('order_no')
                                      ->get();
        $libRealignments = FundingBudgetRealignment::orderBy('realignment_order')
                                                   ->where('project_id', $projectID)
                                                   ->whereNotNull('date_approved')
                                                   ->get();
        $lastBudgetData = $libRealignments->count() > 0 ?
                          $libRealignments->last() :
                          ($libData ? $libData : NULL);
        $isRealignment = $libRealignments->count() > 0 ? true : false;

        $approvedBudgets = [
            (object) [
                'label' => 'Approved Budget',
                'total' => $libData->approved_budget
            ]
        ];
        $groupedAllotments = $this->groupAllotments(
            $allotments, $isRealignment, $lastBudgetData
        );
        $allotments = $groupedAllotments->grouped_allotments;
        $classItemCounts = $groupedAllotments->class_item_counts;

        foreach ($libRealignments as $realignCtr => $libRealign) {
            $approvedBudgets[] = (object) [
                'label' => $this->convertToOrdinal($realignCtr + 1) . ' Re-alignment',
                'total' => $libRealign->approved_realigned_budget];
        }

        $obligations = ObligationRequestStatus::where([['funding_source', $projectID]])
                                      ->whereNotNull('date_obligated')
                                      ->orderBy('date_ors_burs')
                                      ->get();
        $payees = json_decode($this->getPayees($request)->content(), true);

        foreach ($payees as $payCtr => $pay) {
            $payees[$payCtr] = (object) $pay;
        }

        if ($type == 'obligation') {
            $viewFile = 'modules.report.obligation-ledger.create';
        } else {
            $viewFile = 'modules.report.disbursement-ledger.create';
        }

        return view($viewFile, compact(
            'allotments', 'classItemCounts', 'approvedBudgets', 'isRealignment',
            'obligations', 'itemCounter', 'allotmentCounter', 'payees'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        dd($request);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showEdit($id) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
    }

    public function getPayees(Request $request) {
        $keyword = trim($request->search);

        $payees = [];
        $empPayees = User::select('id', 'firstname', 'lastname');
        $supplierPayees = Supplier::select('id', 'company_name');

        if ($keyword) {
            $empPayees = $empPayees->where(function($qry) use ($keyword) {
                $qry->where('id', 'like', "%$keyword%")
                    ->orWhere('firstname', 'like', "%$keyword%")
                    ->orWhere('lastname', 'like', "%$keyword%");
                $keywords = explode('/\s+/', $keyword);

                if (count($keywords) > 0) {
                    foreach ($keywords as $tag) {
                        $qry->orWhere('firstname', 'like', "%$tag%")
                            ->orWhere('lastname', 'like', "%$tag%");
                    }
                }
            });

            $supplierPayees = $supplierPayees->where(function($qry) use ($keyword) {
                $qry->where('id', 'like', "%$keyword%")
                    ->orWhere('company_name', 'like', "%$keyword%");
                $keywords = explode('/\s+/', $keyword);

                if (count($keywords) > 0) {
                    foreach ($keywords as $tag) {
                        $qry->orWhere('company_name', 'like', "%$tag%");
                    }
                }
            });
        }

        $empPayees = $empPayees->orderBy('firstname')->get();
        $supplierPayees = $supplierPayees->orderBy('company_name')->get();

        foreach ($empPayees as $emp) {
            $payees[] = (object) [
                'id' => $emp->id,
                'name' => $emp->firstname.' '.$emp->lastname
            ];
        }

        foreach ($supplierPayees as $bid) {
            $payees[] = (object) [
                'id' => $bid->id,
                'name' => $bid->company_name
            ];
        }

        return response()->json($payees);
    }

    private function groupAllotments($allotments, $isRealignment = false, $currRealignData = NULL) {
        $groupedAllotments = [];
        $classItemCounts = [];
        $allotClassData = DB::table('allotment_classes')
                            ->orderBy('order_no')
                            ->get();

        if ($isRealignment) {
            $realignOrder = $currRealignData->realignment_order;
            $budgetID = $currRealignData->budget_id;
            $currRealignID = $currRealignData->id;

            $currRealignAllotments = DB::table('funding_allotment_realignments')
                                       ->where('budget_realign_id', $currRealignID)
                                       ->orderBy('order_no')
                                       ->get();

            $_allotments = [];

            foreach ($currRealignAllotments as $realignAllotCtr => $realignAllot) {
                $_allotments[$realignAllotCtr] = (object) [
                    'allotment_class' => $realignAllot->allotment_class,
                    'allotment_name' => $realignAllot->allotment_name,
                    "realignment_$realignOrder" => (object) [
                        'allotment_cost' => $realignAllot->realigned_allotment_cost,
                    ],
                ];

                if ($realignAllot->allotment_id) {
                    $allotmentData = DB::table('funding_allotments')
                                       ->where('id', $realignAllot->allotment_id)
                                       ->first();
                    $_allotments[$realignAllotCtr]->allotment_id = $allotmentData->id;
                    $_allotments[$realignAllotCtr]->allotment_cost = $allotmentData->allotment_cost;
                    $allotCoimplementers = unserialize($allotmentData->coimplementers);

                    foreach ($allotCoimplementers as $coimp) {
                        $_allotments[$realignAllotCtr]->allotment_cost += $coimp['coimplementor_budget'];
                    }
                } else {
                    $_allotments[$realignAllotCtr]->allotment_id = NULL;
                    $_allotments[$realignAllotCtr]->allotment_cost = 0;
                }

                for ($realignOrderCtr = 1; $realignOrderCtr <= $realignOrder; $realignOrderCtr++) {
                    $realignIndex = "realignment_$realignOrderCtr";

                    $budgetRealignData = DB::table('funding_budget_realignments')
                                        ->where([
                                            ['budget_id', $budgetID],
                                            ['realignment_order', $realignOrderCtr],
                                        ])->first();
                    $realignID = $budgetRealignData->id;
                    $realignAllotmentData = DB::table('funding_allotment_realignments')
                                            ->where('budget_realign_id', $realignID)
                                            ->orderBy('order_no')
                                            ->get();
                    $hasRealignAllot = false;

                    foreach ($realignAllotmentData as $rAllotCtr => $rAllot) {
                        if (strtolower(trim($realignAllot->allotment_name)) == strtolower(trim($rAllot->allotment_name)) ||
                            $realignAllot->allotment_id == $rAllot->allotment_id) {
                            $coimplementers = unserialize($rAllot->coimplementers);

                            $_allotments[$realignAllotCtr]->{$realignIndex} = (object) [
                                'allotment_id' => $rAllot->allotment_id,
                                'allotment_realign_id' => $rAllot->id,
                                'allotment_cost' => $rAllot->realigned_allotment_cost,
                            ];

                            foreach ($coimplementers as $coimp) {
                                $_allotments[$realignAllotCtr]->{$realignIndex}->allotment_cost += $coimp['coimplementor_budget'];
                            }

                            $hasRealignAllot = true;
                            break;
                        }
                    }

                    if (!$hasRealignAllot) {
                        $_allotments[$realignAllotCtr]->{$realignIndex} = (object) [
                            'allotment_id' => NULL,
                            'allotment_realign_id' => NULL,
                            'allotment_cost' => 0,
                        ];
                    }
                }
            }

            $allotments = $_allotments;
        }

        foreach ($allotClassData as $class) {
            //$keyClass = preg_replace("/\s+/", "-", $class->class_name);
            $keyClass = preg_replace("/\s+/", "-", $class->code);
            $classItemCounts[$keyClass] = 0;

            foreach ($allotments as $itmCtr => $item) {
                if ($class->id == $item->allotment_class) {
                    if (!$isRealignment) {
                        $coimplementers = unserialize($item->coimplementers);

                        foreach ($coimplementers as $coimp) {
                            $item->allotment_cost += $coimp['coimplementor_budget'];
                        }
                    }

                    if (count(explode('::', $item->allotment_name)) > 1) {
                        $keyAllotment = preg_replace(
                            "/\s+/", "-", explode('::', $item->allotment_name)[0]
                        );
                        $groupedAllotments[$keyClass][$keyAllotment][] = $item;
                        $classItemCounts[$keyClass]++;
                    } else {
                        $groupedAllotments[$keyClass][$itmCtr + 1] = $item;
                        $classItemCounts[$keyClass]++;
                    }
                }
            }
        }



        return (object) [
            'grouped_allotments' => $groupedAllotments,
            'class_item_counts' => $classItemCounts
        ];
    }

    private function convertToOrdinal($number) {
        $suffix = [
            'th',
            'st',
            'nd',
            'rd',
            'th',
            'th',
            'th',
            'th',
            'th',
            'th'
        ];

        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number. 'th';
        } else {
            return $number. $suffix[$number % 10];
        }
    }
}
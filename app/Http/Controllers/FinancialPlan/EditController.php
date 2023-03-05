<?php
/**
 * EditController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\FinancialPlan;

use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\FinancialPlanFormUpdateRequest;
use FireflyIII\Models\AutoFinancialPlan;
use FireflyIII\Models\FinancialPlan;
use FireflyIII\Repositories\FinancialPlan\FinancialPlanRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 *
 * Class EditController
 */
class EditController extends Controller
{
    private AttachmentHelperInterface $attachments;
    private FinancialPlanRepositoryInterface $repository;

    /**
     * EditController constructor.
     *

     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.FinancialPlans'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->repository  = app(FinancialPlanRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * FinancialPlan edit form.
     *
     * @param  Request  $request
     * @param  FinancialPlan  $FinancialPlan
     *
     * @return Factory|View
     */
    public function edit(Request $request, FinancialPlan $FinancialPlan)
    {
        $subTitle   = (string)trans('firefly.edit_FinancialPlan', ['name' => $FinancialPlan->name]);
        $autoFinancialPlan = $this->repository->getAutoFinancialPlan($FinancialPlan);

        // auto FinancialPlan types
        $autoFinancialPlanTypes   = [
            0                                => (string)trans('firefly.auto_FinancialPlan_none'),
            AutoFinancialPlan::AUTO_FinancialPlan_RESET    => (string)trans('firefly.auto_FinancialPlan_reset'),
            AutoFinancialPlan::AUTO_FinancialPlan_ROLLOVER => (string)trans('firefly.auto_FinancialPlan_rollover'),
        ];
        $autoFinancialPlanPeriods = [
            'daily'     => (string)trans('firefly.auto_FinancialPlan_period_daily'),
            'weekly'    => (string)trans('firefly.auto_FinancialPlan_period_weekly'),
            'monthly'   => (string)trans('firefly.auto_FinancialPlan_period_monthly'),
            'quarterly' => (string)trans('firefly.auto_FinancialPlan_period_quarterly'),
            'half_year' => (string)trans('firefly.auto_FinancialPlan_period_half_year'),
            'yearly'    => (string)trans('firefly.auto_FinancialPlan_period_yearly'),
        ];

        // code to handle active-checkboxes
        $hasOldInput = null !== $request->old('_token');
        $currency    = app('amount')->getDefaultCurrency();
        $preFilled   = [
            'active'                  => $hasOldInput ? (bool)$request->old('active') : $FinancialPlan->active,
            'auto_FinancialPlan_currency_id' => $hasOldInput ? (int)$request->old('auto_FinancialPlan_currency_id') : $currency->id,
        ];
        if ($autoFinancialPlan) {
            $amount                          = $hasOldInput ? $request->old('auto_FinancialPlan_amount') : $autoFinancialPlan->amount;
            $preFilled['auto_FinancialPlan_amount'] = app('steam')->bcround($amount, $autoFinancialPlan->transactionCurrency->decimal_places);
        }

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('FinancialPlans.edit.fromUpdate')) {
            $this->rememberPreviousUrl('FinancialPlans.edit.url');
        }
        $request->session()->forget('FinancialPlans.edit.fromUpdate');
        $request->session()->flash('preFilled', $preFilled);

        return view('FinancialPlans.edit', compact('FinancialPlan', 'subTitle', 'autoFinancialPlanTypes', 'autoFinancialPlanPeriods', 'autoFinancialPlan'));
    }

    /**
     * FinancialPlan update routine.
     *
     * @param  FinancialPlanFormUpdateRequest  $request
     * @param  FinancialPlan  $FinancialPlan
     *
     * @return RedirectResponse
     */
    public function update(FinancialPlanFormUpdateRequest $request, FinancialPlan $FinancialPlan): RedirectResponse
    {
        $data = $request->getFinancialPlanData();
        $this->repository->update($FinancialPlan, $data);

        $request->session()->flash('success', (string)trans('firefly.updated_FinancialPlan', ['name' => $FinancialPlan->name]));
        $this->repository->cleanupFinancialPlans();
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUrl('FinancialPlans.edit.url'));

        // store new attachment(s):
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($FinancialPlan, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            session()->flash('info', (string)trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        if (1 === (int)$request->get('return_to_edit')) {
            $request->session()->put('FinancialPlans.edit.fromUpdate', true);

            $redirect = redirect(route('FinancialPlans.edit', [$FinancialPlan->id]))->withInput(['return_to_edit' => 1]);
        }

        return $redirect;
    }
}

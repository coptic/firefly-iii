<?php
/**
 * DeleteController.php
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

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\FinancialPlan;
use FireflyIII\Repositories\FinancialPlan\FinancialPlanRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 *
 * Class DeleteController
 */
class DeleteController extends Controller
{
    /** @var FinancialPlanRepositoryInterface The FinancialPlan repository */
    private $repository;

    /**
     * DeleteController constructor.
     *

     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.FinancialPlans'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->repository = app(FinancialPlanRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Deletes a FinancialPlan.
     *
     * @param  FinancialPlan  $FinancialPlan
     *
     * @return Factory|View
     */
    public function delete(FinancialPlan $FinancialPlan)
    {
        $subTitle = (string)trans('firefly.delete_FinancialPlan', ['name' => $FinancialPlan->name]);

        // put previous url in session
        $this->rememberPreviousUrl('FinancialPlans.delete.url');

        return view('FinancialPlans.delete', compact('FinancialPlan', 'subTitle'));
    }

    /**
     * Destroys a FinancialPlan.
     *
     * @param  Request  $request
     * @param  FinancialPlan  $FinancialPlan
     *
     * @return RedirectResponse|Redirector
     */
    public function destroy(Request $request, FinancialPlan $FinancialPlan)
    {
        $name = $FinancialPlan->name;
        $this->repository->destroy($FinancialPlan);
        $request->session()->flash('success', (string)trans('firefly.deleted_FinancialPlan', ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUrl('FinancialPlans.delete.url'));
    }
}

<?php
/**
 * ShowController.php
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

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\FinancialPlan;
use FireflyIII\Models\FinancialPlanLimit;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\FinancialPlan\FinancialPlanRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 *
 * Class ShowController
 */
class ShowController extends Controller
{
    use PeriodOverview;
    use AugumentData;

    protected JournalRepositoryInterface $journalRepos;
    private FinancialPlanRepositoryInterface    $repository;

    /**
     * ShowController constructor.
     *

     */
    public function __construct()
    {
        app('view')->share('showCategory', true);
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.FinancialPlans'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->journalRepos = app(JournalRepositoryInterface::class);
                $this->repository   = app(FinancialPlanRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show transactions without a FinancialPlan.
     *
     * @param  Request  $request
     * @param  Carbon|null  $start
     * @param  Carbon|null  $end
     *
     * @return Factory|View
     * @throws FireflyException
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function noFinancialPlan(Request $request, Carbon $start = null, Carbon $end = null)
    {
        /** @var Carbon $start */
        $start = $start ?? session('start');
        /** @var Carbon $end */
        $end      = $end ?? session('end');
        $subTitle = trans(
            'firefly.without_FinancialPlan_between',
            ['start' => $start->isoFormat($this->monthAndDayFormat), 'end' => $end->isoFormat($this->monthAndDayFormat)]
        );

        // get first journal ever to set off the FinancialPlan period overview.
        $first     = $this->journalRepos->firstNull();
        $firstDate = null !== $first ? $first->date : $start;
        $periods   = $this->getNoFinancialPlanPeriodOverview($firstDate, $end);
        $page      = (int)$request->get('page');
        $pageSize  = (int)app('preferences')->get('listPageSize', 50)->data;

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setLimit($pageSize)->setPage($page)
                  ->withoutFinancialPlan()->withAccountInformation()->withCategoryInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath(route('FinancialPlans.no-FinancialPlan'));

        return view('FinancialPlans.no-FinancialPlan', compact('groups', 'subTitle', 'periods', 'start', 'end'));
    }

    /**
     * Shows ALL transactions without a FinancialPlan.
     *
     * @param  Request  $request
     *
     * @return Factory|View
     * @throws FireflyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function noFinancialPlanAll(Request $request)
    {
        $subTitle = (string)trans('firefly.all_journals_without_FinancialPlan');
        $first    = $this->journalRepos->firstNull();
        $start    = null === $first ? new Carbon() : $first->date;
        $end      = today(config('app.timezone'));
        $page     = (int)$request->get('page');
        $pageSize = (int)app('preferences')->get('listPageSize', 50)->data;

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setLimit($pageSize)->setPage($page)
                  ->withoutFinancialPlan()->withAccountInformation()->withCategoryInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath(route('FinancialPlans.no-FinancialPlan-all'));

        return view('FinancialPlans.no-FinancialPlan', compact('groups', 'subTitle', 'start', 'end'));
    }

    /**
     * Show a single FinancialPlan.
     *
     * @param  Request  $request
     * @param  FinancialPlan  $FinancialPlan
     *
     * @return Factory|View
     * @throws FireflyException
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function show(Request $request, FinancialPlan $FinancialPlan)
    {
        /** @var Carbon $allStart */
        $allStart    = session('first', today(config('app.timezone'))->startOfYear());
        $allEnd      = today();
        $page        = (int)$request->get('page');
        $pageSize    = (int)app('preferences')->get('listPageSize', 50)->data;
        $limits      = $this->getLimits($FinancialPlan, $allStart, $allEnd);
        $repetition  = null;
        $attachments = $this->repository->getAttachments($FinancialPlan);

        // collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($allStart, $allEnd)->setFinancialPlan($FinancialPlan)
                  ->withAccountInformation()
                  ->setLimit($pageSize)->setPage($page)->withFinancialPlanInformation()->withCategoryInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath(route('FinancialPlans.show', [$FinancialPlan->id]));

        $subTitle = (string)trans('firefly.all_journals_for_FinancialPlan', ['name' => $FinancialPlan->name]);

        return view('FinancialPlans.show', compact('limits', 'attachments', 'FinancialPlan', 'repetition', 'groups', 'subTitle'));
    }

    /**
     * Show a single FinancialPlan by a FinancialPlan limit.
     *
     * @param  Request  $request
     * @param  FinancialPlan  $FinancialPlan
     * @param  FinancialPlanLimit  $FinancialPlanLimit
     *
     * @return Factory|View
     * @throws FireflyException
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function showByFinancialPlanLimit(Request $request, FinancialPlan $FinancialPlan, FinancialPlanLimit $FinancialPlanLimit)
    {
        if ($FinancialPlanLimit->FinancialPlan->id !== $FinancialPlan->id) {
            throw new FireflyException('This FinancialPlan limit is not part of this FinancialPlan.');
        }

        $page     = (int)$request->get('page');
        $pageSize = (int)app('preferences')->get('listPageSize', 50)->data;
        $subTitle = trans(
            'firefly.FinancialPlan_in_period',
            [
                'name'     => $FinancialPlan->name,
                'start'    => $FinancialPlanLimit->start_date->isoFormat($this->monthAndDayFormat),
                'end'      => $FinancialPlanLimit->end_date->isoFormat($this->monthAndDayFormat),
                'currency' => $FinancialPlanLimit->transactionCurrency->name,
            ]
        );

        // collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setRange($FinancialPlanLimit->start_date, $FinancialPlanLimit->end_date)->withAccountInformation()
                  ->setFinancialPlan($FinancialPlan)->setLimit($pageSize)->setPage($page)->withFinancialPlanInformation()->withCategoryInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath(route('FinancialPlans.show', [$FinancialPlan->id, $FinancialPlanLimit->id]));
        /** @var Carbon $start */
        $start       = session('first', today(config('app.timezone'))->startOfYear());
        $end         = today(config('app.timezone'));
        $attachments = $this->repository->getAttachments($FinancialPlan);
        $limits      = $this->getLimits($FinancialPlan, $start, $end);

        return view('FinancialPlans.show', compact('limits', 'attachments', 'FinancialPlan', 'FinancialPlanLimit', 'groups', 'subTitle'));
    }
}

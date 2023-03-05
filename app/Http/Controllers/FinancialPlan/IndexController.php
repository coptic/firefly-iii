<?php
/**
 * IndexController.php
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
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AvailableFinancialPlan;
use FireflyIII\Models\FinancialPlan;
use FireflyIII\Models\FinancialPlanLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\FinancialPlan\AvailableFinancialPlanRepositoryInterface;
use FireflyIII\Repositories\FinancialPlan\FinancialPlanLimitRepositoryInterface;
use FireflyIII\Repositories\FinancialPlan\FinancialPlanRepositoryInterface;
use FireflyIII\Repositories\FinancialPlan\OperationsRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use JsonException;
use Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 *
 * Class IndexController
 */
class IndexController extends Controller
{
    use DateCalculation;

    private AvailableFinancialPlanRepositoryInterface $abRepository;
    private FinancialPlanLimitRepositoryInterface     $blRepository;
    private CurrencyRepositoryInterface        $currencyRepository;
    private OperationsRepositoryInterface      $opsRepository;
    private FinancialPlanRepositoryInterface          $repository;

    /**
     * IndexController constructor.
     *

     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.FinancialPlans'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->repository         = app(FinancialPlanRepositoryInterface::class);
                $this->opsRepository      = app(OperationsRepositoryInterface::class);
                $this->abRepository       = app(AvailableFinancialPlanRepositoryInterface::class);
                $this->currencyRepository = app(CurrencyRepositoryInterface::class);
                $this->blRepository       = app(FinancialPlanLimitRepositoryInterface::class);
                $this->repository->cleanupFinancialPlans();

                return $next($request);
            }
        );
    }

    /**
     * Show all FinancialPlans.
     *
     * @param  Request  $request
     *
     * @param  Carbon|null  $start
     * @param  Carbon|null  $end
     *
     * @return Factory|View
     * @throws FireflyException
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(Request $request, Carbon $start = null, Carbon $end = null)
    {
        Log::debug('Start of IndexController::index()');

        // collect some basic vars:
        $range           = app('navigation')->getViewRange(true);
        $start           = $start ?? session('start', today(config('app.timezone'))->startOfMonth());
        $end             = $end ?? app('navigation')->endOfPeriod($start, $range);
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $currencies      = $this->currencyRepository->get();
        $FinancialPlaned        = '0';
        $spent           = '0';

        // new period stuff:
        $periodTitle = app('navigation')->periodShow($start, $range);
        $prevLoop    = $this->getPreviousPeriods($start, $range);
        $nextLoop    = $this->getNextPeriods($start, $range);

        // get all available FinancialPlans:
        $availableFinancialPlans = $this->getAllAvailableFinancialPlans($start, $end);

        // get all active FinancialPlans:
        $FinancialPlans = $this->getAllFinancialPlans($start, $end, $currencies, $defaultCurrency);
        $sums    = $this->getSums($FinancialPlans);

        // get FinancialPlaned for default currency:
        if (0 === count($availableFinancialPlans)) {
            $FinancialPlaned = $this->blRepository->FinancialPlaned($start, $end, $defaultCurrency, );
            $spentArr = $this->opsRepository->sumExpenses($start, $end, null, null, $defaultCurrency);
            $spent    = $spentArr[$defaultCurrency->id]['sum'] ?? '0';
            unset($spentArr);
        }

        // number of days for consistent FinancialPlaning.
        $activeDaysPassed = $this->activeDaysPassed($start, $end); // see method description.
        $activeDaysLeft   = $this->activeDaysLeft($start, $end);   // see method description.

        // get all inactive FinancialPlans, and simply list them:
        $inactive = $this->repository->getInactiveFinancialPlans();

        return view(
            'FinancialPlans.index',
            compact(
                'availableFinancialPlans',
                'FinancialPlaned',
                'spent',
                'prevLoop',
                'nextLoop',
                'FinancialPlans',
                'currencies',
                'periodTitle',
                'defaultCurrency',
                'activeDaysPassed',
                'activeDaysLeft',
                'inactive',
                'FinancialPlans',
                'start',
                'end',
                'sums'
            )
        );
    }

    /**
     * @param  Carbon  $start
     * @param  Carbon  $end
     *
     * @return array
     */
    private function getAllAvailableFinancialPlans(Carbon $start, Carbon $end): array
    {
        // get all available FinancialPlans.
        $ab               = $this->abRepository->get($start, $end);
        $availableFinancialPlans = [];
        // for each, complement with spent amount:
        /** @var AvailableFinancialPlan $entry */
        foreach ($ab as $entry) {
            $array               = $entry->toArray();
            $array['start_date'] = $entry->start_date;
            $array['end_date']   = $entry->end_date;

            // spent in period:
            $spentArr       = $this->opsRepository->sumExpenses($entry->start_date, $entry->end_date, null, null, $entry->transactionCurrency);
            $array['spent'] = $spentArr[$entry->transaction_currency_id]['sum'] ?? '0';

            // FinancialPlaned in period:
            $FinancialPlaned           = $this->blRepository->FinancialPlaned($entry->start_date, $entry->end_date, $entry->transactionCurrency, );
            $array['FinancialPlaned']  = $FinancialPlaned;
            $availableFinancialPlans[] = $array;
            unset($spentArr);
        }

        return $availableFinancialPlans;
    }

    /**
     * @param  Carbon  $start
     * @param  Carbon  $end
     * @param  Collection  $currencies
     * @param  TransactionCurrency  $defaultCurrency
     *
     * @return array
     */
    private function getAllFinancialPlans(Carbon $start, Carbon $end, Collection $currencies, TransactionCurrency $defaultCurrency): array
    {
        // get all FinancialPlans, and paginate them into $FinancialPlans.
        $collection = $this->repository->getActiveFinancialPlans();
        $FinancialPlans    = [];
        Log::debug(sprintf('7) Start is "%s", end is "%s"', $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));

        // complement FinancialPlan with FinancialPlan limits in range, and expenses in currency X in range.
        /** @var FinancialPlan $current */
        foreach ($collection as $current) {
            Log::debug(sprintf('Working on FinancialPlan #%d ("%s")', $current->id, $current->name));
            $array                = $current->toArray();
            $array['spent']       = [];
            $array['FinancialPlaned']    = [];
            $array['attachments'] = $this->repository->getAttachments($current);
            $array['auto_FinancialPlan'] = $this->repository->getAutoFinancialPlan($current);
            $FinancialPlanLimits         = $this->blRepository->getFinancialPlanLimits($current, $start, $end);
            /** @var FinancialPlanLimit $limit */
            foreach ($FinancialPlanLimits as $limit) {
                Log::debug(sprintf('Working on FinancialPlan limit #%d', $limit->id));
                $currency            = $limit->transactionCurrency ?? $defaultCurrency;
                $array['FinancialPlaned'][] = [
                    'id'                      => $limit->id,
                    'amount'                  => app('steam')->bcround($limit->amount, $currency->decimal_places),
                    'start_date'              => $limit->start_date->isoFormat($this->monthAndDayFormat),
                    'end_date'                => $limit->end_date->isoFormat($this->monthAndDayFormat),
                    'in_range'                => $limit->start_date->isSameDay($start) && $limit->end_date->isSameDay($end),
                    'currency_id'             => $currency->id,
                    'currency_symbol'         => $currency->symbol,
                    'currency_name'           => $currency->name,
                    'currency_decimal_places' => $currency->decimal_places,
                ];
            }

            /** @var TransactionCurrency $currency */
            foreach ($currencies as $currency) {
                $spentArr = $this->opsRepository->sumExpenses($start, $end, null, new Collection([$current]), $currency);
                if (array_key_exists($currency->id, $spentArr) && array_key_exists('sum', $spentArr[$currency->id])) {
                    $array['spent'][$currency->id]['spent']                   = $spentArr[$currency->id]['sum'];
                    $array['spent'][$currency->id]['currency_id']             = $currency->id;
                    $array['spent'][$currency->id]['currency_symbol']         = $currency->symbol;
                    $array['spent'][$currency->id]['currency_decimal_places'] = $currency->decimal_places;
                }
            }
            $FinancialPlans[] = $array;
        }

        return $FinancialPlans;
    }

    /**
     * @param  array  $FinancialPlans
     *
     * @return array
     */
    private function getSums(array $FinancialPlans): array
    {
        $sums = [
            'FinancialPlaned' => [],
            'spent'    => [],
            'left'     => [],
        ];

        /** @var array $FinancialPlan */
        foreach ($FinancialPlans as $FinancialPlan) {
            /** @var array $spent */
            foreach ($FinancialPlan['spent'] as $spent) {
                $currencyId                           = $spent['currency_id'];
                $sums['spent'][$currencyId]
                                                      = $sums['spent'][$currencyId]
                                                        ?? [
                                                            'amount'                  => '0',
                                                            'currency_id'             => $spent['currency_id'],
                                                            'currency_symbol'         => $spent['currency_symbol'],
                                                            'currency_decimal_places' => $spent['currency_decimal_places'],
                                                        ];
                $sums['spent'][$currencyId]['amount'] = bcadd($sums['spent'][$currencyId]['amount'], $spent['spent']);
            }

            /** @var array $FinancialPlaned */
            foreach ($FinancialPlan['FinancialPlaned'] as $FinancialPlaned) {
                $currencyId                              = $FinancialPlaned['currency_id'];
                $sums['FinancialPlaned'][$currencyId]
                                                         = $sums['FinancialPlaned'][$currencyId]
                                                           ?? [
                                                               'amount'                  => '0',
                                                               'currency_id'             => $FinancialPlaned['currency_id'],
                                                               'currency_symbol'         => $FinancialPlaned['currency_symbol'],
                                                               'currency_decimal_places' => $FinancialPlaned['currency_decimal_places'],
                                                           ];
                $sums['FinancialPlaned'][$currencyId]['amount'] = bcadd($sums['FinancialPlaned'][$currencyId]['amount'], $FinancialPlaned['amount']);

                // also calculate how much left from FinancialPlaned:
                $sums['left'][$currencyId] = $sums['left'][$currencyId]
                                             ?? [
                                                 'amount'                  => '0',
                                                 'currency_id'             => $FinancialPlaned['currency_id'],
                                                 'currency_symbol'         => $FinancialPlaned['currency_symbol'],
                                                 'currency_decimal_places' => $FinancialPlaned['currency_decimal_places'],
                                             ];
            }
        }
        // final calculation for 'left':
        /**
         * @var int $currencyId
         * @var array $info
         */
        foreach ($sums['FinancialPlaned'] as $currencyId => $info) {
            $spent                               = $sums['spent'][$currencyId]['amount'] ?? '0';
            $FinancialPlaned                            = $sums['FinancialPlaned'][$currencyId]['amount'] ?? '0';
            $sums['left'][$currencyId]['amount'] = bcadd($spent, $FinancialPlaned);
        }

        return $sums;
    }

    /**
     * @param  Request  $request
     * @param  FinancialPlanRepositoryInterface  $repository
     *
     * @return JsonResponse
     */
    public function reorder(Request $request, FinancialPlanRepositoryInterface $repository): JsonResponse
    {
        $FinancialPlanIds = $request->get('FinancialPlanIds');

        foreach ($FinancialPlanIds as $index => $FinancialPlanId) {
            $FinancialPlanId = (int)$FinancialPlanId;
            $FinancialPlan   = $repository->find($FinancialPlanId);
            if (null !== $FinancialPlan) {
                Log::debug(sprintf('Set FinancialPlan #%d ("%s") to position %d', $FinancialPlan->id, $FinancialPlan->name, $index + 1));
                $repository->setFinancialPlanOrder($FinancialPlan, $index + 1);
            }
        }
        app('preferences')->mark();

        return response()->json(['OK']);
    }
}
